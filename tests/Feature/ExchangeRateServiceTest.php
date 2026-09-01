<?php

use App\Services\ExchangeRateService;
use App\Services\ExchangeRateUnavailableException;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;

beforeEach(function () {
    Http::preventStrayRequests();
    config(['services.exchange_rate_api.key' => 'test-key']);
});

test('same-currency returns 1.0 without API call', function () {
    $service = new ExchangeRateService;

    $rate = $service->getRate('USD', 'USD');

    expect($rate)->toBe(1.0);
    Http::assertNothingSent();
});

test('successful rate fetch stores in cache', function () {
    Http::fake([
        'v6.exchangerate-api.com/v6/test-key/pair/GBP/USD' => Http::response([
            'result' => 'success',
            'conversion_rate' => 1.27,
        ]),
    ]);

    $service = new ExchangeRateService;
    $rate = $service->getRate('GBP', 'USD');

    expect($rate)->toBe(1.27);
    expect(Cache::get('fx_rate:GBP:USD'))->toBe(1.27);
    expect(Cache::get('fx_rate_last:GBP:USD'))->toBe(1.27);
});

test('cache hit avoids API call', function () {
    Cache::put('fx_rate:GBP:USD', 1.30, 3600);

    $service = new ExchangeRateService;
    $rate = $service->getRate('GBP', 'USD');

    expect($rate)->toBe(1.30);
    Http::assertNothingSent();
});

test('API failure falls back to last-known rate', function () {
    Cache::put('fx_rate_last:GBP:USD', 1.25, 604800);

    Http::fake([
        'v6.exchangerate-api.com/v6/test-key/pair/GBP/USD' => Http::response([
            'result' => 'error',
            'error-type' => 'quota-reached',
        ], 429),
    ]);

    $service = new ExchangeRateService;
    $rate = $service->getRate('GBP', 'USD');

    expect($rate)->toBe(1.25);
});

test('API failure with no last-known rate throws exception', function () {
    Http::fake([
        'v6.exchangerate-api.com/v6/test-key/pair/GBP/USD' => Http::response([
            'result' => 'error',
            'error-type' => 'invalid-key',
        ], 400),
    ]);

    $service = new ExchangeRateService;

    $service->getRate('GBP', 'USD');
})->throws(ExchangeRateUnavailableException::class);

test('network failure falls back to last-known rate', function () {
    Cache::put('fx_rate_last:GBP:USD', 1.25, 604800);

    Http::fake([
        'v6.exchangerate-api.com/v6/test-key/pair/GBP/USD' => Http::failedConnection(),
    ]);

    $service = new ExchangeRateService;
    $rate = $service->getRate('GBP', 'USD');

    expect($rate)->toBe(1.25);
});

test('network failure with no last-known rate throws exception', function () {
    Http::fake([
        'v6.exchangerate-api.com/v6/test-key/pair/GBP/USD' => Http::failedConnection(),
    ]);

    $service = new ExchangeRateService;

    $service->getRate('GBP', 'USD');
})->throws(ExchangeRateUnavailableException::class);

test('case insensitive currency codes', function () {
    Http::fake([
        'v6.exchangerate-api.com/v6/test-key/pair/GBP/USD' => Http::response([
            'result' => 'success',
            'conversion_rate' => 1.27,
        ]),
    ]);

    $service = new ExchangeRateService;
    $rate = $service->getRate('gbp', 'usd');

    expect($rate)->toBe(1.27);
});
