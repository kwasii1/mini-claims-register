<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use InvalidArgumentException;

class ExchangeRateService
{
    private const CACHE_TTL = 3600;

    private const LAST_KNOWN_TTL = 604800; // 7 days

    public function getRate(string $from, string $to): float
    {
        $from = strtoupper($from);
        $to = strtoupper($to);

        if ($from === $to) {
            return 1.0;
        }

        $cacheKey = "fx_rate:{$from}:{$to}";
        $lastKnownKey = "fx_rate_last:{$from}:{$to}";

        $cached = Cache::get($cacheKey);

        if ($cached !== null) {
            return (float) $cached;
        }

        try {
            $rate = $this->fetchFromApi($from, $to);

            Cache::put($cacheKey, $rate, self::CACHE_TTL);
            Cache::put($lastKnownKey, $rate, self::LAST_KNOWN_TTL);

            return $rate;
        } catch (\Exception $e) {
            $lastKnown = Cache::get($lastKnownKey);

            if ($lastKnown !== null) {
                Log::warning('Exchange rate API failed, using last-known rate', [
                    'from' => $from,
                    'to' => $to,
                    'last_known_rate' => $lastKnown,
                    'error' => $e->getMessage(),
                ]);

                return (float) $lastKnown;
            }

            throw new ExchangeRateUnavailableException(
                "Exchange rate unavailable for {$from}/{$to}. No cached rate available.",
                previous: $e
            );
        }
    }

    private function fetchFromApi(string $from, string $to): float
    {
        $key = config('services.exchange_rate_api.key');

        if (! $key) {
            throw new \RuntimeException('Exchange rate API key is not configured.');
        }

        $response = Http::connectTimeout(3)
            ->timeout(5)
            ->get("https://v6.exchangerate-api.com/v6/{$key}/pair/{$from}/{$to}");

        $data = $response->json();

        if ($data['result'] !== 'success') {
            throw new InvalidArgumentException(
                'Exchange rate API error: '.($data['error-type'] ?? 'unknown')
            );
        }

        return $data['conversion_rate'];
    }
}
