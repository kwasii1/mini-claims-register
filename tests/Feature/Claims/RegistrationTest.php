<?php

use App\Models\Claim;
use App\Models\User;
use Livewire\Livewire;

test('guest is redirected to login', function () {
    $this->get(route('claims.register'))->assertRedirect(route('login'));
});

test('authenticated user can visit registration page', function () {
    $this->actingAs(User::factory()->create());

    $this->get(route('claims.register'))->assertOk();
});

test('policy number is required', function () {
    $this->actingAs(User::factory()->create());

    Livewire::test('pages::register-claim')
        ->set('policy_number', '')
        ->set('insured_name', 'Acme Corp')
        ->set('loss_date', '2025-01-01')
        ->set('date_notified', '2025-01-05')
        ->set('loss_nature', 'Fire damage')
        ->set('reserve_currency', 'USD')
        ->set('estimated_loss_amount', '1500.50')
        ->call('createClaim')
        ->assertHasErrors(['policy_number']);
});

test('insured name is required', function () {
    $this->actingAs(User::factory()->create());

    Livewire::test('pages::register-claim')
        ->set('policy_number', 'POL-0001-0001')
        ->set('insured_name', '')
        ->set('loss_date', '2025-01-01')
        ->set('date_notified', '2025-01-05')
        ->set('loss_nature', 'Fire damage')
        ->set('reserve_currency', 'USD')
        ->set('estimated_loss_amount', '1500.50')
        ->call('createClaim')
        ->assertHasErrors(['insured_name']);
});

test('loss date is required', function () {
    $this->actingAs(User::factory()->create());

    Livewire::test('pages::register-claim')
        ->set('policy_number', 'POL-0001-0001')
        ->set('insured_name', 'Acme Corp')
        ->set('loss_date', '')
        ->set('date_notified', '2025-01-05')
        ->set('loss_nature', 'Fire damage')
        ->set('reserve_currency', 'USD')
        ->set('estimated_loss_amount', '1500.50')
        ->call('createClaim')
        ->assertHasErrors(['loss_date']);
});

test('loss date cannot be in the future', function () {
    $this->actingAs(User::factory()->create());

    Livewire::test('pages::register-claim')
        ->set('policy_number', 'POL-0001-0001')
        ->set('insured_name', 'Acme Corp')
        ->set('loss_date', now()->addDay()->format('Y-m-d'))
        ->set('date_notified', now()->format('Y-m-d'))
        ->set('loss_nature', 'Fire damage')
        ->set('reserve_currency', 'USD')
        ->set('estimated_loss_amount', '1500.50')
        ->call('createClaim')
        ->assertHasErrors(['loss_date']);
});

test('date notified must be after or equal to loss date', function () {
    $this->actingAs(User::factory()->create());

    Livewire::test('pages::register-claim')
        ->set('policy_number', 'POL-0001-0001')
        ->set('insured_name', 'Acme Corp')
        ->set('loss_date', '2025-01-10')
        ->set('date_notified', '2025-01-05')
        ->set('loss_nature', 'Fire damage')
        ->set('reserve_currency', 'USD')
        ->set('estimated_loss_amount', '1500.50')
        ->call('createClaim')
        ->assertHasErrors(['date_notified']);
});

test('nature of loss is required', function () {
    $this->actingAs(User::factory()->create());

    Livewire::test('pages::register-claim')
        ->set('policy_number', 'POL-0001-0001')
        ->set('insured_name', 'Acme Corp')
        ->set('loss_date', '2025-01-01')
        ->set('date_notified', '2025-01-05')
        ->set('loss_nature', '')
        ->set('reserve_currency', 'USD')
        ->set('estimated_loss_amount', '1500.50')
        ->call('createClaim')
        ->assertHasErrors(['loss_nature']);
});

test('reserve currency must be valid', function () {
    $this->actingAs(User::factory()->create());

    Livewire::test('pages::register-claim')
        ->set('policy_number', 'POL-0001-0001')
        ->set('insured_name', 'Acme Corp')
        ->set('loss_date', '2025-01-01')
        ->set('date_notified', '2025-01-05')
        ->set('loss_nature', 'Fire damage')
        ->set('reserve_currency', 'XYZ')
        ->set('estimated_loss_amount', '1500.50')
        ->call('createClaim')
        ->assertHasErrors(['reserve_currency']);
});

test('estimated loss amount is required', function () {
    $this->actingAs(User::factory()->create());

    Livewire::test('pages::register-claim')
        ->set('policy_number', 'POL-0001-0001')
        ->set('insured_name', 'Acme Corp')
        ->set('loss_date', '2025-01-01')
        ->set('date_notified', '2025-01-05')
        ->set('loss_nature', 'Fire damage')
        ->set('reserve_currency', 'USD')
        ->set('estimated_loss_amount', '')
        ->call('createClaim')
        ->assertHasErrors(['estimated_loss_amount']);
});

test('estimated loss amount must be positive', function () {
    $this->actingAs(User::factory()->create());

    Livewire::test('pages::register-claim')
        ->set('policy_number', 'POL-0001-0001')
        ->set('insured_name', 'Acme Corp')
        ->set('loss_date', '2025-01-01')
        ->set('date_notified', '2025-01-05')
        ->set('loss_nature', 'Fire damage')
        ->set('reserve_currency', 'USD')
        ->set('estimated_loss_amount', '0')
        ->call('createClaim')
        ->assertHasErrors(['estimated_loss_amount']);
});

test('policy number must be unique', function () {
    $this->actingAs(User::factory()->create());

    Claim::factory()->create(['policy_number' => 'POL-0001-0001']);

    Livewire::test('pages::register-claim')
        ->set('policy_number', 'POL-0001-0001')
        ->set('insured_name', 'Acme Corp')
        ->set('loss_date', '2025-01-01')
        ->set('date_notified', '2025-01-05')
        ->set('loss_nature', 'Fire damage')
        ->set('reserve_currency', 'USD')
        ->set('estimated_loss_amount', '1500.50')
        ->call('createClaim')
        ->assertHasErrors(['policy_number']);
});

test('claim can be created successfully', function () {
    $this->actingAs(User::factory()->create());

    Livewire::test('pages::register-claim')
        ->set('policy_number', 'POL-0001-0001')
        ->set('insured_name', 'Acme Corp')
        ->set('loss_date', '2025-01-01')
        ->set('date_notified', '2025-01-05')
        ->set('loss_nature', 'Fire damage')
        ->set('reserve_currency', 'USD')
        ->set('estimated_loss_amount', '1500.50')
        ->call('createClaim')
        ->assertHasNoErrors();

    $this->assertDatabaseHas('claims', [
        'policy_number' => 'POL-0001-0001',
        'insured_name' => 'Acme Corp',
        'loss_nature' => 'Fire damage',
        'reserve_currency' => 'USD',
        'estimated_loss_amount' => 150050,
        'approved_amount' => null,
    ]);
});

test('amount is converted to minor units', function () {
    $this->actingAs(User::factory()->create());

    Livewire::test('pages::register-claim')
        ->set('policy_number', 'POL-0001-0001')
        ->set('insured_name', 'Acme Corp')
        ->set('loss_date', '2025-01-01')
        ->set('date_notified', '2025-01-05')
        ->set('loss_nature', 'Fire damage')
        ->set('reserve_currency', 'USD')
        ->set('estimated_loss_amount', '2500.75')
        ->call('createClaim')
        ->assertHasNoErrors();

    $claim = Claim::where('policy_number', 'POL-0001-0001')->first();
    expect($claim->estimated_loss_amount)->toBe('250075');
});

test('claim starts with no approved amount', function () {
    $this->actingAs(User::factory()->create());

    Livewire::test('pages::register-claim')
        ->set('policy_number', 'POL-0001-0001')
        ->set('insured_name', 'Acme Corp')
        ->set('loss_date', '2025-01-01')
        ->set('date_notified', '2025-01-05')
        ->set('loss_nature', 'Fire damage')
        ->set('reserve_currency', 'USD')
        ->set('estimated_loss_amount', '1500.50')
        ->call('createClaim')
        ->assertHasNoErrors();

    $claim = Claim::where('policy_number', 'POL-0001-0001')->first();
    expect($claim->approved_amount)->toBeNull();
    expect($claim->status)->toBe('Reserved, not yet settled');
});

test('show page displays claim details', function () {
    $this->actingAs(User::factory()->create());

    $claim = Claim::factory()->create([
        'policy_number' => 'POL-0001-0001',
        'insured_name' => 'Acme Corp',
        'reserve_currency' => 'USD',
        'estimated_loss_amount' => 150050,
    ]);

    $this->get(route('claims.show', $claim))->assertOk();
});
