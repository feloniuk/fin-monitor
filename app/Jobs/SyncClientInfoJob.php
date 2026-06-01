<?php

namespace App\Jobs;

use App\Models\MonobankToken;
use App\Services\Monobank\MonobankSyncService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class SyncClientInfoJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 5;

    public function __construct(public int $tokenId)
    {
    }

    public function handle(MonobankSyncService $service): void
    {
        $token = MonobankToken::findOrFail($this->tokenId);
        $service->syncClientInfo($token);
    }

    public function failed(\Throwable $exception): void
    {
        if (str_contains($exception->getMessage(), '429')) {
            $this->release(65);
        }
    }
}
