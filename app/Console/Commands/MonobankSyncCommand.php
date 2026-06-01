<?php

namespace App\Console\Commands;

use App\Models\MonobankToken;
use App\Services\Monobank\MonobankSyncService;
use Illuminate\Console\Command;

class MonobankSyncCommand extends Command
{
    protected $signature = 'monobank:sync';
    protected $description = 'Синхронізувати дані з Monobank (рахунки та нові транзакції)';

    public function handle(): int
    {
        $tokens = MonobankToken::all();

        if ($tokens->isEmpty()) {
            $this->warn('Немає збережених токенів Monobank.');
            return self::SUCCESS;
        }

        $service = new MonobankSyncService();

        foreach ($tokens as $token) {
            $this->info("Синхронізація токену: {$token->client_name}...");

            try {
                $service->syncClientInfo($token);
                $this->info('  Інформацію про клієнта оновлено.');

                foreach ($token->accounts()->where('is_active', true)->get() as $account) {
                    $this->info("  Рахунок: {$account->display_name}");
                    $service->dispatchStatementSync($account);
                    $this->info('    Завдання синхронізації заплановано.');
                }
            } catch (\Throwable $e) {
                $this->error("  Помилка: {$e->getMessage()}");
            }
        }

        return self::SUCCESS;
    }
}
