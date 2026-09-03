<?php

use App\Models\Claim;
use Flux\Flux;
use Livewire\Attributes\Title;
use Livewire\Component;

new #[Title('Register claim')] class extends Component {
    public string $policy_number = '';
    public string $insured_name = '';
    public ?string $loss_date = null;
    public ?string $date_notified = null;
    public string $loss_nature = '';
    public string $reserve_currency = 'USD';
    public ?string $estimated_loss_amount = null;

    public array $currencies = ['USD', 'GBP', 'EUR', 'GHS'];

    public function rules(): array
    {
        return [
            'policy_number' => ['required', 'string', 'max:255', 'unique:claims,policy_number'],
            'insured_name' => ['required', 'string', 'max:255'],
            'loss_date' => ['required', 'date', 'before_or_equal:today'],
            'date_notified' => ['required', 'date', 'after_or_equal:loss_date'],
            'loss_nature' => ['required', 'string', 'max:255'],
            'reserve_currency' => ['required', 'in:USD,GBP,EUR,GHS'],
            'estimated_loss_amount' => ['required', 'numeric', 'min:0.01'],
        ];
    }

    protected function validationAttributes(): array
    {
        return [
            'policy_number' => 'policy number',
            'insured_name' => 'insured name',
            'loss_date' => 'loss date',
            'date_notified' => 'date notified',
            'loss_nature' => 'nature of loss',
            'reserve_currency' => 'reserve currency',
            'estimated_loss_amount' => 'estimated loss amount',
        ];
    }

    public function createClaim(): void
    {
        $validated = $this->validate();

        $amountInMinorUnits = (int) round($validated['estimated_loss_amount'] * 100);

        $claim = Claim::create([
            ...$validated,
            'estimated_loss_amount' => $amountInMinorUnits,
        ]);

        Flux::toast(variant: 'success', text: __('Claim registered successfully.'));

        $this->redirect(route('claims.show', $claim), navigate: true);
    }
}; ?>

<section class="w-full max-w-2xl">
    <flux:heading size="xl" level="1">{{ __('Register claim') }}</flux:heading>
    <flux:text class="mt-1">{{ __('Record a new insurance claim') }}</flux:text>

    <form wire:submit="createClaim" class="mt-6 space-y-6">
        <div class="grid grid-cols-1 gap-6 sm:grid-cols-2">
            <flux:input
                wire:model="policy_number"
                :label="__('Policy number')"
                type="text"
                required
                autofocus
                autocomplete="off"
            />

            <flux:input
                wire:model="insured_name"
                :label="__('Insured name')"
                type="text"
                required
                autocomplete="off"
            />
        </div>

        <div class="grid grid-cols-1 gap-6 sm:grid-cols-2">
            <flux:input
                wire:model="loss_date"
                :label="__('Loss date')"
                type="date"
                required
            />

            <flux:input
                wire:model="date_notified"
                :label="__('Date notified')"
                type="date"
                required
            />
        </div>

        <div class="grid grid-cols-1 gap-6 sm:grid-cols-2">
            <flux:input
                wire:model="loss_nature"
                :label="__('Nature of loss')"
                type="text"
                required
                autocomplete="off"
            />

            <flux:select wire:model="reserve_currency" :label="__('Reserve currency')" required>
                @foreach ($currencies as $currency)
                    <flux:select.option :value="$currency">{{ $currency }}</flux:select.option>
                @endforeach
            </flux:select>
        </div>

        <div class="grid grid-cols-1 gap-6 sm:grid-cols-2">
            <flux:input
                wire:model="estimated_loss_amount"
                :label="__('Estimated loss amount')"
                type="number"
                step="0.01"
                min="0.01"
                required
                placeholder="0.00"
            />

            <div></div>
        </div>

        <div class="flex items-center gap-4">
            <flux:button variant="primary" type="submit" data-test="register-claim-button">
                {{ __('Register claim') }}
            </flux:button>
        </div>
    </form>
</section>
