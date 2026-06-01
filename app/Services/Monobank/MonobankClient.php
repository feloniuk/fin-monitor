<?php

namespace App\Services\Monobank;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;

class MonobankClient
{
    private const BASE_URL = 'https://api.monobank.ua';
    private const RATE_LIMIT_SECONDS = 60;
    private const CACHE_KEY = 'monobank_last_api_call';

    public function __construct(private string $token)
    {
    }

    public function getClientInfo(): array
    {
        return $this->request('/personal/client-info');
    }

    public function getStatements(string $accountId, int $from, ?int $to = null): array
    {
        $path = "/personal/statement/{$accountId}/{$from}";
        if ($to) {
            $path .= "/{$to}";
        }

        return $this->request($path);
    }

    public static function secondsUntilNextCall(): int
    {
        $lastCall = Cache::get(self::CACHE_KEY);
        if (!$lastCall) {
            return 0;
        }

        $elapsed = time() - $lastCall;
        return max(0, self::RATE_LIMIT_SECONDS - $elapsed);
    }

    private function request(string $path): array
    {
        $wait = self::secondsUntilNextCall();
        if ($wait > 0) {
            throw new \RuntimeException(
                "Monobank API: потрібно зачекати ще {$wait} сек. між запитами (ліміт: 1 запит на 60 сек.)."
            );
        }

        try {
            $response = Http::withHeaders([
                'X-Token' => $this->token,
            ])->get(self::BASE_URL . $path);

            // Record call time regardless of result
            Cache::put(self::CACHE_KEY, time(), self::RATE_LIMIT_SECONDS);

            if ($response->status() === 429) {
                throw new \RuntimeException(
                    'Monobank API: перевищено ліміт запитів (429). Зачекайте 60 секунд.'
                );
            }

            if ($response->failed()) {
                $error = $response->json('errorDescription') ?? $response->body();
                throw new \RuntimeException("Monobank API помилка: {$error}");
            }

            return $response->json();
        } catch (\RuntimeException $e) {
            throw $e;
        } catch (\Throwable $e) {
            throw new \RuntimeException("Monobank API помилка з'єднання: {$e->getMessage()}");
        }
    }
}
