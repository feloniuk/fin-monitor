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

    public function __construct(
        public int $accountId,
        public int $from,
        public int $to
    ) {
    }

    public function handle(MonobankSyncService $service): void
    {
        $account = MonobankAccount::findOrFail($this->accountId);
        $service->syncStatements($account, $this->from, $this->to);
    }

    public function failed(\Throwable $exception): void
    {
        if (str_contains($exception->getMessage(), '429')) {
            $this->release(65);
        }
    }
}
