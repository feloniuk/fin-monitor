<?php

namespace App\Jobs;

use App\Models\MonobankAccount;
use App\Services\Monobank\MonobankSyncService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class SyncStatementsJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 5;

    public function backoff(): array
    {
        return [65, 120, 180];
    }

    public function __construct(
        public int $accountId,
        public int $from,
        public int $to
    ) {
    }

    public function handle(MonobankSyncService $service): void
    {
        $account = MonobankAccount::findOrFail($this->accountId);

        try {
            $service->syncStatements($account, $this->from, $this->to);
        } catch (\RuntimeException $e) {
            if (str_contains($e->getMessage(), 'зачекати')) {
                $this->release(65);
                return;
            }
            throw $e;
        }
    }
}
