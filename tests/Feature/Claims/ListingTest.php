<?php

use App\Models\Claim;
use App\Models\User;
use Livewire\Livewire;

test('guest is redirected to login', function () {
    $this->get(route('claims.index'))->assertRedirect(route('login'));
});

test('authenticated user can visit listing page', function () {
    $this->actingAs(User::factory()->create());

    $this->get(route('claims.index'))->assertOk();
});

test('claims are displayed in the table', function () {
    $this->actingAs(User::factory()->create());

    Claim::factory()->create([
        'policy_number' => 'POL-0001-0001',
        'insured_name' => 'Acme Corp',
    ]);

    Livewire::test('pages::index-claims')
        ->assertSee('POL-0001-0001')
        ->assertSee('Acme Corp');
});

test('search filters by policy number', function () {
    $this->actingAs(User::factory()->create());

    Claim::factory()->create(['policy_number' => 'POL-0001-0001']);
    Claim::factory()->create(['policy_number' => 'POL-0002-0002']);

    Livewire::test('pages::index-claims')
        ->set('search', 'POL-0001')
        ->assertSee('POL-0001-0001')
        ->assertDontSee('POL-0002-0002');
});

test('search filters by insured name', function () {
    $this->actingAs(User::factory()->create());

    Claim::factory()->create(['insured_name' => 'Acme Corp']);
    Claim::factory()->create(['insured_name' => 'Beta Inc']);

    Livewire::test('pages::index-claims')
        ->set('search', 'Acme')
        ->assertSee('Acme Corp')
        ->assertDontSee('Beta Inc');
});

test('status filter works', function () {
    $this->actingAs(User::factory()->create());

    Claim::factory()->reserved()->create(['policy_number' => 'RES-001']);
    Claim::factory()->settled()->create(['policy_number' => 'SET-001']);

    Livewire::test('pages::index-claims')
        ->set('statusFilter', 'reserved')
        ->assertSee('RES-001')
        ->assertDontSee('SET-001');
});

test('currency filter works', function () {
    $this->actingAs(User::factory()->create());

    Claim::factory()->create(['policy_number' => 'USD-001', 'reserve_currency' => 'USD']);
    Claim::factory()->create(['policy_number' => 'GBP-001', 'reserve_currency' => 'GBP']);

    Livewire::test('pages::index-claims')
        ->set('currencyFilter', 'USD')
        ->assertSee('USD-001')
        ->assertDontSee('GBP-001');
});

test('date range filter on loss date', function () {
    $this->actingAs(User::factory()->create());

    Claim::factory()->create([
        'policy_number' => 'OLD-001',
        'loss_date' => '2024-01-15',
    ]);

    Claim::factory()->create([
        'policy_number' => 'NEW-001',
        'loss_date' => '2025-06-15',
    ]);

    Livewire::test('pages::index-claims')
        ->set('dateField', 'loss_date')
        ->set('dateFrom', '2025-01-01')
        ->set('dateTo', '2025-12-31')
        ->assertSee('NEW-001')
        ->assertDontSee('OLD-001');
});

test('date range filter on date notified', function () {
    $this->actingAs(User::factory()->create());

    Claim::factory()->create([
        'policy_number' => 'OLD-001',
        'date_notified' => '2024-01-15',
    ]);

    Claim::factory()->create([
        'policy_number' => 'NEW-001',
        'date_notified' => '2025-06-15',
    ]);

    Livewire::test('pages::index-claims')
        ->set('dateField', 'date_notified')
        ->set('dateFrom', '2025-01-01')
        ->set('dateTo', '2025-12-31')
        ->assertSee('NEW-001')
        ->assertDontSee('OLD-001');
});

test('filters can be combined', function () {
    $this->actingAs(User::factory()->create());

    Claim::factory()->reserved()->create([
        'policy_number' => 'RES-USD',
        'reserve_currency' => 'USD',
    ]);

    Claim::factory()->reserved()->create([
        'policy_number' => 'RES-GBP',
        'reserve_currency' => 'GBP',
    ]);

    Claim::factory()->settled()->create([
        'policy_number' => 'SET-USD',
        'reserve_currency' => 'USD',
    ]);

    Livewire::test('pages::index-claims')
        ->set('statusFilter', 'reserved')
        ->set('currencyFilter', 'USD')
        ->assertSee('RES-USD')
        ->assertDontSee('RES-GBP')
        ->assertDontSee('SET-USD');
});

test('reset filters clears all filters', function () {
    $this->actingAs(User::factory()->create());

    Claim::factory()->create(['policy_number' => 'POL-0001']);
    Claim::factory()->create(['policy_number' => 'POL-0002']);

    Livewire::test('pages::index-claims')
        ->set('search', 'POL-0001')
        ->call('resetFilters')
        ->assertSee('POL-0001')
        ->assertSee('POL-0002');
});

test('totals show correct sums per currency', function () {
    $this->actingAs(User::factory()->create());

    Claim::factory()->create([
        'reserve_currency' => 'USD',
        'approved_amount' => 10000,
    ]);

    Claim::factory()->create([
        'reserve_currency' => 'USD',
        'approved_amount' => 20000,
    ]);

    Livewire::test('pages::index-claims')
        ->assertSee('2 claims (USD)')
        ->assertSee('300.00');
});

test('totals update when filters change', function () {
    $this->actingAs(User::factory()->create());

    Claim::factory()->create([
        'policy_number' => 'USD-001',
        'reserve_currency' => 'USD',
        'approved_amount' => 10000,
    ]);

    Claim::factory()->create([
        'policy_number' => 'GBP-001',
        'reserve_currency' => 'GBP',
        'approved_amount' => 20000,
    ]);

    Livewire::test('pages::index-claims')
        ->set('currencyFilter', 'USD')
        ->assertSee('1 claim (USD)')
        ->assertSee('USD-001')
        ->assertDontSee('GBP-001');
});

test('empty state shows when no claims match', function () {
    $this->actingAs(User::factory()->create());

    Claim::factory()->create(['policy_number' => 'POL-0001']);

    Livewire::test('pages::index-claims')
        ->set('search', 'NONEXISTENT')
        ->assertSee('No claims found');
});

test('claim links to detail page', function () {
    $this->actingAs(User::factory()->create());

    $claim = Claim::factory()->create(['policy_number' => 'POL-0001-0001']);

    Livewire::test('pages::index-claims')
        ->assertSee(route('claims.show', $claim));
});
