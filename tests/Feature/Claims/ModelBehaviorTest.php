<?php

use App\Models\Claim;
use App\Models\Payment;

test('same-currency converted_amount equals amount', function () {
    $claim = Claim::factory()->create(['reserve_currency' => 'USD']);

    $payment = Payment::factory()->create([
        'claim_id' => $claim->id,
        'amount' => 25050,
        'currency' => 'USD',
        'fx_rate_snapshot' => '1.000000',
    ]);

    expect($payment->converted_amount)->toBe('25050');
});

test('cross-currency converted_amount uses snapshot rate', function () {
    $claim = Claim::factory()->create(['reserve_currency' => 'USD']);

    $payment = Payment::factory()->create([
        'claim_id' => $claim->id,
        'amount' => 10000,
        'currency' => 'GBP',
        'fx_rate_snapshot' => '1.270000',
    ]);

    expect($payment->converted_amount)->toBe('12700');
});

test('claim total_paid sums converted amounts', function () {
    $claim = Claim::factory()->create(['reserve_currency' => 'USD']);

    Payment::factory()->create([
        'claim_id' => $claim->id,
        'amount' => 10000,
        'currency' => 'GBP',
        'fx_rate_snapshot' => '1.270000',
    ]);

    Payment::factory()->create([
        'claim_id' => $claim->id,
        'amount' => 20000,
        'currency' => 'EUR',
        'fx_rate_snapshot' => '1.090000',
    ]);

    expect($claim->total_paid)->toBe('34500');
});

test('outstanding balance is clamped at zero', function () {
    $claim = Claim::factory()->create([
        'reserve_currency' => 'USD',
        'approved_amount' => 5000,
    ]);

    Payment::factory()->create([
        'claim_id' => $claim->id,
        'amount' => 10000,
        'currency' => 'USD',
        'fx_rate_snapshot' => '1.000000',
    ]);

    expect($claim->outstanding_balance)->toBe('0');
});

test('status derivation with payments - outstanding', function () {
    $claim = Claim::factory()->create([
        'reserve_currency' => 'USD',
        'approved_amount' => 50000,
    ]);

    Payment::factory()->create([
        'claim_id' => $claim->id,
        'amount' => 25000,
        'currency' => 'USD',
        'fx_rate_snapshot' => '1.000000',
    ]);

    expect($claim->status)->toBe('Settled, payment outstanding');
});

test('status derivation with payments - settled and paid', function () {
    $claim = Claim::factory()->create([
        'reserve_currency' => 'USD',
        'approved_amount' => 25000,
    ]);

    Payment::factory()->create([
        'claim_id' => $claim->id,
        'amount' => 25000,
        'currency' => 'USD',
        'fx_rate_snapshot' => '1.000000',
    ]);

    expect($claim->status)->toBe('Settled and paid');
});

test('status derivation - reserved not yet settled', function () {
    $claim = Claim::factory()->create([
        'reserve_currency' => 'USD',
        'approved_amount' => null,
    ]);

    Payment::factory()->create([
        'claim_id' => $claim->id,
        'amount' => 5000,
        'currency' => 'USD',
        'fx_rate_snapshot' => '1.000000',
    ]);

    expect($claim->status)->toBe('Reserved, not yet settled');
});
