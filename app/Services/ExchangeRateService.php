<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use InvalidArgumentException;

class ExchangeRateService
{
    private const CACHE_TTL = 3600;

    public function getRate(string $from, string $to): float
    {
        $from = strtoupper($from);
        $to = strtoupper($to);

        if ($from === $to) {
            return 1.0;
        }

        $cacheKey = "fx_rate:{$from}:{$to}";

        return (float) Cache::remember($cacheKey, self::CACHE_TTL, function () use ($from, $to) {
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
        });
    }
}
