<?php

namespace App\Services\Monobank;

use App\Jobs\SyncStatementsJob;
use App\Models\MonobankAccount;
use App\Models\MonobankToken;
use App\Models\SyncLog;
use App\Models\Transaction;
use Carbon\Carbon;

class MonobankSyncService
{
    public function syncClientInfo(MonobankToken $token): void
    {
        $log = SyncLog::create([
            'type' => 'client_info',
            'status' => 'running',
        ]);

        try {
            $client = new MonobankClient($token->token);
            $info = $client->getClientInfo();

            $token->update([
                'client_name' => $info['name'] ?? $info['clientId'] ?? null,
                'last_synced_at' => now(),
            ]);

            foreach ($info['accounts'] as $accountData) {
                MonobankAccount::updateOrCreate(
                    ['account_id' => $accountData['id']],
                    [
                        'monobank_token_id' => $token->id,
                        'currency_code' => $accountData['currencyCode'],
                        'balance' => $accountData['balance'],
                        'credit_limit' => $accountData['creditLimit'] ?? 0,
                        'masked_pan' => $accountData['maskedPan'] ?? null,
                        'type' => $accountData['type'] ?? null,
                        'iban' => $accountData['iban'] ?? null,
                    ]
                );
            }

            $log->update([
                'status' => 'success',
                'records_fetched' => count($info['accounts']),
            ]);
        } catch (\Throwable $e) {
            $log->update([
                'status' => 'failed',
                'error_message' => $e->getMessage(),
            ]);
            throw $e;
        }
    }

    public function dispatchStatementSync(MonobankAccount $account, ?Carbon $from = null, ?Carbon $to = null): void
    {
        $to = $to ?? now();
        $from = $from ?? ($account->last_statement_sync_at ?? now()->subDays(30));

        $chunks = $this->splitIntoChunks($from, $to);

        $delay = 0;
        foreach ($chunks as $chunk) {
            SyncStatementsJob::dispatch($account->id, $chunk['from']->timestamp, $chunk['to']->timestamp)
                ->delay(now()->addSeconds($delay));
            $delay += 65;
        }
    }

    public function syncStatements(MonobankAccount $account, int $from, int $to): int
    {
        $log = SyncLog::create([
            'monobank_account_id' => $account->id,
            'type' => 'statements',
            'status' => 'running',
            'date_from' => Carbon::createFromTimestamp($from),
            'date_to' => Carbon::createFromTimestamp($to),
        ]);

        try {
            $token = $account->token;
            $client = new MonobankClient($token->token);
            $statements = $client->getStatements($account->account_id, $from, $to);

            $count = 0;
            foreach ($statements as $stmt) {
                Transaction::updateOrCreate(
                    ['transaction_id' => $stmt['id']],
                    [
                        'monobank_account_id' => $account->id,
                        'time' => Carbon::createFromTimestamp($stmt['time']),
                        'description' => $stmt['description'] ?? null,
                        'mcc' => $stmt['mcc'] ?? null,
                        'amount' => $stmt['amount'],
                        'operation_amount' => $stmt['operationAmount'] ?? $stmt['amount'],
                        'currency_code' => $stmt['currencyCode'],
                        'commission_rate' => $stmt['commissionRate'] ?? 0,
                        'cashback_amount' => $stmt['cashbackAmount'] ?? 0,
                        'balance' => $stmt['balance'],
                        'hold' => $stmt['hold'] ?? false,
                        'comment' => $stmt['comment'] ?? null,
                        'receipt_id' => $stmt['receiptId'] ?? null,
                        'counter_edrpou' => $stmt['counterEdrpou'] ?? null,
                        'counter_iban' => $stmt['counterIban'] ?? null,
                        'counter_name' => $stmt['counterName'] ?? null,
                    ]
                );
                $count++;
            }

            $account->update([
                'last_statement_sync_at' => Carbon::createFromTimestamp($to),
            ]);

            $log->update([
                'status' => 'success',
                'records_fetched' => $count,
            ]);

            return $count;
        } catch (\Throwable $e) {
            $log->update([
                'status' => 'failed',
                'error_message' => $e->getMessage(),
            ]);
            throw $e;
        }
    }

    public function getSaldoReport(MonobankAccount $account, Carbon $from, Carbon $to, string $groupBy = 'month'): array
    {
        $periods = [];
        $current = $from->copy()->startOfMonth();

        while ($current->lt($to)) {
            $periodEnd = $groupBy === 'quarter'
                ? $current->copy()->addMonths(3)->subSecond()
                : $current->copy()->endOfMonth();

            if ($periodEnd->gt($to)) {
                $periodEnd = $to->copy();
            }

            $income = Transaction::where('monobank_account_id', $account->id)
                ->whereBetween('time', [$current, $periodEnd])
                ->where('amount', '>', 0)
                ->sum('amount');

            $expense = Transaction::where('monobank_account_id', $account->id)
                ->whereBetween('time', [$current, $periodEnd])
                ->where('amount', '<', 0)
                ->sum('amount');

            $openingBalance = Transaction::where('monobank_account_id', $account->id)
                ->where('time', '<', $current)
                ->orderByDesc('time')
                ->value('balance') ?? 0;

            $closingBalance = Transaction::where('monobank_account_id', $account->id)
                ->where('time', '<=', $periodEnd)
                ->orderByDesc('time')
                ->value('balance') ?? $openingBalance;

            $periodLabel = $groupBy === 'quarter'
                ? $current->format('Y') . ' Q' . ceil($current->month / 3)
                : $current->translatedFormat('F Y');

            $periods[] = [
                'period' => $periodLabel,
                'opening_balance' => $openingBalance,
                'income' => $income,
                'expense' => $expense,
                'closing_balance' => $closingBalance,
            ];

            $current = $groupBy === 'quarter'
                ? $current->addMonths(3)->startOfMonth()
                : $current->addMonth()->startOfMonth();
        }

        return $periods;
    }

    private function splitIntoChunks(Carbon $from, Carbon $to): array
    {
        $chunks = [];
        $current = $from->copy();

        while ($current->lt($to)) {
            $chunkEnd = $current->copy()->addDays(30);
            if ($chunkEnd->gt($to)) {
                $chunkEnd = $to->copy();
            }
            $chunks[] = ['from' => $current->copy(), 'to' => $chunkEnd];
            $current = $chunkEnd->copy()->addSecond();
        }

        return $chunks;
    }
}
