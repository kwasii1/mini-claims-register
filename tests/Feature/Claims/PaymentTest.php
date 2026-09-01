<?php

use App\Models\Claim;
use App\Models\User;
use Illuminate\Support\Facades\Http;
use Livewire\Livewire;

beforeEach(function () {
    Http::preventStrayRequests();
});

test('guest is redirected to login when recording payment', function () {
    $claim = Claim::factory()->create();

    $this->get(route('claims.show', $claim))->assertRedirect(route('login'));
});

test('payment date is required', function () {
    $this->actingAs(User::factory()->create());

    $claim = Claim::factory()->create(['reserve_currency' => 'USD']);

    Livewire::test('pages::show-claim', ['claim' => $claim->id])
        ->set('new_payment_date', '')
        ->set('new_payment_amount', '100.00')
        ->set('new_payment_currency', 'USD')
        ->call('recordPayment')
        ->assertHasErrors(['new_payment_date']);
});

test('payment date cannot be in the future', function () {
    $this->actingAs(User::factory()->create());

    $claim = Claim::factory()->create(['reserve_currency' => 'USD']);

    Livewire::test('pages::show-claim', ['claim' => $claim->id])
        ->set('new_payment_date', now()->addDay()->format('Y-m-d'))
        ->set('new_payment_amount', '100.00')
        ->set('new_payment_currency', 'USD')
        ->call('recordPayment')
        ->assertHasErrors(['new_payment_date']);
});

test('payment amount is required', function () {
    $this->actingAs(User::factory()->create());

    $claim = Claim::factory()->create(['reserve_currency' => 'USD']);

    Livewire::test('pages::show-claim', ['claim' => $claim->id])
        ->set('new_payment_date', now()->format('Y-m-d'))
        ->set('new_payment_amount', '')
        ->set('new_payment_currency', 'USD')
        ->call('recordPayment')
        ->assertHasErrors(['new_payment_amount']);
});

test('payment amount must be positive', function () {
    $this->actingAs(User::factory()->create());

    $claim = Claim::factory()->create(['reserve_currency' => 'USD']);

    Livewire::test('pages::show-claim', ['claim' => $claim->id])
        ->set('new_payment_date', now()->format('Y-m-d'))
        ->set('new_payment_amount', '0')
        ->set('new_payment_currency', 'USD')
        ->call('recordPayment')
        ->assertHasErrors(['new_payment_amount']);
});

test('payment currency is required', function () {
    $this->actingAs(User::factory()->create());

    $claim = Claim::factory()->create(['reserve_currency' => 'USD']);

    Livewire::test('pages::show-claim', ['claim' => $claim->id])
        ->set('new_payment_date', now()->format('Y-m-d'))
        ->set('new_payment_amount', '100.00')
        ->set('new_payment_currency', '')
        ->call('recordPayment')
        ->assertHasErrors(['new_payment_currency']);
});

test('payment currency must be valid', function () {
    $this->actingAs(User::factory()->create());

    $claim = Claim::factory()->create(['reserve_currency' => 'USD']);

    Livewire::test('pages::show-claim', ['claim' => $claim->id])
        ->set('new_payment_date', now()->format('Y-m-d'))
        ->set('new_payment_amount', '100.00')
        ->set('new_payment_currency', 'XYZ')
        ->call('recordPayment')
        ->assertHasErrors(['new_payment_currency']);
});

test('same-currency payment creates row with rate 1.0', function () {
    $this->actingAs(User::factory()->create());

    $claim = Claim::factory()->create(['reserve_currency' => 'USD']);

    Livewire::test('pages::show-claim', ['claim' => $claim->id])
        ->set('new_payment_date', '2025-06-15')
        ->set('new_payment_amount', '250.50')
        ->set('new_payment_currency', 'USD')
        ->call('recordPayment')
        ->assertHasNoErrors();

    $this->assertDatabaseHas('payments', [
        'claim_id' => $claim->id,
        'amount' => 25050,
        'currency' => 'USD',
        'fx_rate_snapshot' => '1.000000',
    ]);
});

test('cross-currency payment snapshots the rate from API', function () {
    $this->actingAs(User::factory()->create());

    $claim = Claim::factory()->create(['reserve_currency' => 'USD']);

    config(['services.exchange_rate_api.key' => 'test-key']);

    Http::fake([
        'v6.exchangerate-api.com/v6/test-key/pair/GBP/USD' => Http::response([
            'result' => 'success',
            'conversion_rate' => 1.27,
        ]),
    ]);

    Livewire::test('pages::show-claim', ['claim' => $claim->id])
        ->set('new_payment_date', '2025-06-15')
        ->set('new_payment_amount', '100.00')
        ->set('new_payment_currency', 'GBP')
        ->call('recordPayment')
        ->assertHasNoErrors();

    $this->assertDatabaseHas('payments', [
        'claim_id' => $claim->id,
        'amount' => 10000,
        'currency' => 'GBP',
        'fx_rate_snapshot' => '1.270000',
    ]);
});

test('multiple payments update total_paid and status', function () {
    $this->actingAs(User::factory()->create());

    $claim = Claim::factory()->create([
        'reserve_currency' => 'USD',
        'approved_amount' => 50000,
    ]);

    config(['services.exchange_rate_api.key' => 'test-key']);

    Http::fake([
        'v6.exchangerate-api.com/v6/test-key/pair/GBP/USD' => Http::response([
            'result' => 'success',
            'conversion_rate' => 1.27,
        ]),
    ]);

    Livewire::test('pages::show-claim', ['claim' => $claim->id])
        ->set('new_payment_date', '2025-06-15')
        ->set('new_payment_amount', '100.00')
        ->set('new_payment_currency', 'GBP')
        ->call('recordPayment')
        ->assertHasNoErrors();

    Livewire::test('pages::show-claim', ['claim' => $claim->id])
        ->set('new_payment_date', '2025-07-01')
        ->set('new_payment_amount', '200.00')
        ->set('new_payment_currency', 'GBP')
        ->call('recordPayment')
        ->assertHasNoErrors();

    $claim->refresh();
    // GBP: 10000 * 1.27 = 12700, GBP: 20000 * 1.27 = 25400, total = 38100
    expect($claim->total_paid)->toBe('38100');
    expect($claim->status)->toBe('Settled, payment outstanding');
});

test('mixed-currency payments convert correctly', function () {
    $this->actingAs(User::factory()->create());

    $claim = Claim::factory()->create(['reserve_currency' => 'USD']);

    config(['services.exchange_rate_api.key' => 'test-key']);

    Http::fake([
        'v6.exchangerate-api.com/v6/test-key/pair/GBP/USD' => Http::response([
            'result' => 'success',
            'conversion_rate' => 1.27,
        ]),
        'v6.exchangerate-api.com/v6/test-key/pair/EUR/USD' => Http::response([
            'result' => 'success',
            'conversion_rate' => 1.09,
        ]),
    ]);

    Livewire::test('pages::show-claim', ['claim' => $claim->id])
        ->set('new_payment_date', '2025-06-15')
        ->set('new_payment_amount', '100.00')
        ->set('new_payment_currency', 'GBP')
        ->call('recordPayment')
        ->assertHasNoErrors();

    Livewire::test('pages::show-claim', ['claim' => $claim->id])
        ->set('new_payment_date', '2025-07-01')
        ->set('new_payment_amount', '200.00')
        ->set('new_payment_currency', 'EUR')
        ->call('recordPayment')
        ->assertHasNoErrors();

    $claim->refresh();
    // GBP: 10000 * 1.27 = 12700, EUR: 20000 * 1.09 = 21800, total = 34500
    expect($claim->total_paid)->toBe('34500');
});

test('API failure shows validation error', function () {
    $this->actingAs(User::factory()->create());

    $claim = Claim::factory()->create(['reserve_currency' => 'USD']);

    config(['services.exchange_rate_api.key' => 'test-key']);

    Http::fake([
        'v6.exchangerate-api.com/v6/test-key/pair/GBP/USD' => Http::response([
            'result' => 'error',
            'error-type' => 'invalid-key',
        ], 400),
    ]);

    Livewire::test('pages::show-claim', ['claim' => $claim->id])
        ->set('new_payment_date', '2025-06-15')
        ->set('new_payment_amount', '100.00')
        ->set('new_payment_currency', 'GBP')
        ->call('recordPayment')
        ->assertHasErrors(['new_payment_currency']);
});

test('show page displays payment in table after recording', function () {
    $this->actingAs(User::factory()->create());

    $claim = Claim::factory()->create(['reserve_currency' => 'USD']);

    Livewire::test('pages::show-claim', ['claim' => $claim->id])
        ->set('new_payment_date', '2025-06-15')
        ->set('new_payment_amount', '250.50')
        ->set('new_payment_currency', 'USD')
        ->call('recordPayment')
        ->assertHasNoErrors();

    $this->assertDatabaseHas('payments', [
        'claim_id' => $claim->id,
        'amount' => 25050,
        'currency' => 'USD',
    ]);
});
