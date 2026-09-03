<?php

use App\Models\Claim;
use App\Models\Payment;
use App\Services\ExchangeRateService;
use App\Services\ExchangeRateUnavailableException;
use Flux\Flux;
use Livewire\Attributes\Locked;
use Livewire\Attributes\Title;
use Livewire\Component;

new #[Title('Claim detail')] class extends Component {
    #[Locked]
    public string $claimId;

    public ?Claim $claimModel = null;

    public ?string $new_payment_date = null;
    public ?string $new_payment_amount = null;
    public string $new_payment_currency = 'USD';

    public array $currencies = ['USD', 'GBP', 'EUR', 'GHS'];

    public function mount(string $claim): void
    {
        $this->claimId = $claim;
        $this->loadClaim();
    }

    public function loadClaim(): void
    {
        $this->claimModel = Claim::with('payments')->findOrFail($this->claimId);
    }

    public function statusColor(string $status): string
    {
        return match ($status) {
            'Settled and paid' => 'green',
            'Settled, payment outstanding' => 'amber',
            default => 'zinc',
        };
    }

    public function rules(): array
    {
        return [
            'new_payment_date' => ['required', 'date', 'before_or_equal:today'],
            'new_payment_amount' => ['required', 'numeric', 'min:0.01'],
            'new_payment_currency' => ['required', 'in:USD,GBP,EUR,GHS'],
        ];
    }

    protected function validationAttributes(): array
    {
        return [
            'new_payment_date' => 'payment date',
            'new_payment_amount' => 'payment amount',
            'new_payment_currency' => 'payment currency',
        ];
    }

    public function recordPayment(ExchangeRateService $fx): void
    {
        $validated = $this->validate();

        $amountInMinorUnits = (int) round($validated['new_payment_amount'] * 100);

        try {
            $rate = $fx->getRate(
                $validated['new_payment_currency'],
                $this->claimModel->reserve_currency
            );
        } catch (ExchangeRateUnavailableException $e) {
            $this->addError('new_payment_currency', __('Exchange rate is currently unavailable. Please try again later.'));
            return;
        } catch (\Exception $e) {
            $this->addError('new_payment_currency', __('Unable to fetch exchange rate. Please try again.'));
            return;
        }

        Payment::create([
            'claim_id' => $this->claimId,
            'payment_date' => $validated['new_payment_date'],
            'amount' => $amountInMinorUnits,
            'currency' => $validated['new_payment_currency'],
            'fx_rate_snapshot' => $rate,
        ]);

        $this->reset(['new_payment_date', 'new_payment_amount', 'new_payment_currency']);
        $this->new_payment_currency = 'USD';

        $this->loadClaim();

        Flux::toast(variant: 'success', text: __('Payment recorded successfully.'));
    }
}; ?>

<section class="w-full">
    <div class="mb-6">
        <flux:heading size="xl" level="1">{{ __('Claim detail') }}</flux:heading>
        <flux:text class="mt-1">{{ __('View claim details and payments') }}</flux:text>
    </div>

    <div class="rounded-xl border border-zinc-200 dark:border-zinc-700">
        <div class="grid grid-cols-1 gap-6 p-6 sm:grid-cols-2">
            <div>
                <flux:text variant="subtle">{{ __('Policy number') }}</flux:text>
                <p class="mt-1 font-medium">{{ $this->claimModel->policy_number }}</p>
            </div>

            <div>
                <flux:text variant="subtle">{{ __('Insured name') }}</flux:text>
                <p class="mt-1 font-medium">{{ $this->claimModel->insured_name }}</p>
            </div>

            <div>
                <flux:text variant="subtle">{{ __('Loss date') }}</flux:text>
                <p class="mt-1 font-medium">{{ $this->claimModel->loss_date->format('d M Y') }}</p>
            </div>

            <div>
                <flux:text variant="subtle">{{ __('Date notified') }}</flux:text>
                <p class="mt-1 font-medium">{{ $this->claimModel->date_notified->format('d M Y') }}</p>
            </div>

            <div>
                <flux:text variant="subtle">{{ __('Nature of loss') }}</flux:text>
                <p class="mt-1 font-medium">{{ $this->claimModel->loss_nature }}</p>
            </div>

            <div>
                <flux:text variant="subtle">{{ __('Reserve currency') }}</flux:text>
                <p class="mt-1 font-medium">{{ $this->claimModel->reserve_currency }}</p>
            </div>

            <div>
                <flux:text variant="subtle">{{ __('Estimated loss') }}</flux:text>
                <p class="mt-1 font-medium">{{ number_format($this->claimModel->estimated_loss_amount / 100, 2) }} {{ $this->claimModel->reserve_currency }}</p>
            </div>

            <div>
                <flux:text variant="subtle">{{ __('Approved amount') }}</flux:text>
                <p class="mt-1 font-medium">
                    @if ($this->claimModel->approved_amount !== null)
                        {{ number_format($this->claimModel->approved_amount / 100, 2) }} {{ $this->claimModel->reserve_currency }}
                    @else
                        <span class="text-zinc-400">{{ __('Not yet set') }}</span>
                    @endif
                </p>
            </div>

            <div>
                <flux:text variant="subtle">{{ __('Total paid') }}</flux:text>
                <p class="mt-1 font-medium">{{ number_format($this->claimModel->total_paid / 100, 2) }} {{ $this->claimModel->reserve_currency }}</p>
            </div>

            <div>
                <flux:text variant="subtle">{{ __('Outstanding balance') }}</flux:text>
                <p class="mt-1 font-medium">
                    @if ($this->claimModel->outstanding_balance !== null)
                        {{ number_format($this->claimModel->outstanding_balance / 100, 2) }} {{ $this->claimModel->reserve_currency }}
                    @else
                        <span class="text-zinc-400">{{ __('N/A') }}</span>
                    @endif
                </p>
            </div>

            <div>
                <flux:text variant="subtle">{{ __('Status') }}</flux:text>
                <p class="mt-1">
                    <flux:badge :color="$this->statusColor($this->claimModel->status)">
                        {{ $this->claimModel->status }}
                    </flux:badge>
                </p>
            </div>

            <div>
                <flux:text variant="subtle">{{ __('Created') }}</flux:text>
                <p class="mt-1 font-medium">{{ $this->claimModel->created_at->format('d M Y') }}</p>
            </div>
        </div>
    </div>

    <div class="mt-8">
        <flux:heading level="2">{{ __('Payments') }}</flux:heading>

        @if ($this->claimModel->payments->isEmpty())
            <div class="mt-4 rounded-xl border border-dashed border-zinc-300 dark:border-zinc-600 p-8 text-center">
                <div class="mx-auto mb-4 flex size-14 items-center justify-center rounded-2xl bg-zinc-100 dark:bg-zinc-800">
                    <flux:icon.banknotes class="size-7 text-zinc-400 dark:text-zinc-500" />
                </div>
                <p class="font-medium">{{ __('No payments yet') }}</p>
                <flux:text class="mt-1">{{ __('Payments made against this claim will appear here.') }}</flux:text>
            </div>
        @else
            <div class="mt-4 overflow-x-auto rounded-xl border border-zinc-200 dark:border-zinc-700">
                <table class="w-full text-sm">
                    <thead>
                        <tr class="border-b border-zinc-200 bg-zinc-50 dark:border-zinc-700 dark:bg-zinc-800/50">
                            <th class="px-4 py-3 text-left font-medium">{{ __('Date') }}</th>
                            <th class="px-4 py-3 text-right font-medium">{{ __('Amount') }}</th>
                            <th class="hidden px-4 py-3 text-center font-medium sm:table-cell">{{ __('Currency') }}</th>
                            <th class="px-4 py-3 text-right font-medium">{{ __('Converted') }}</th>
                            <th class="hidden px-4 py-3 text-left font-medium sm:table-cell">{{ __('Created') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($this->claimModel->payments as $payment)
                            <tr class="{{ !$loop->last ? 'border-b border-zinc-200 dark:border-zinc-700' : '' }}">
                                <td class="px-4 py-3">{{ $payment->payment_date->format('d M Y') }}</td>
                                <td class="px-4 py-3 text-right font-mono">{{ number_format($payment->amount / 100, 2) }}</td>
                                <td class="hidden px-4 py-3 text-center sm:table-cell">{{ $payment->currency }}</td>
                                <td class="px-4 py-3 text-right font-mono">{{ number_format($payment->converted_amount / 100, 2) }} {{ $this->claimModel->reserve_currency }}</td>
                                <td class="hidden px-4 py-3 text-left text-zinc-500 sm:table-cell">{{ $payment->created_at->format('d M Y') }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </div>

    <div class="mt-8">
        <flux:heading level="2">{{ __('Record payment') }}</flux:heading>

        <form wire:submit="recordPayment" class="mt-4 rounded-xl border border-zinc-200 p-6 dark:border-zinc-700">
            <div class="grid grid-cols-1 gap-6 sm:grid-cols-3">
                <flux:input
                    wire:model="new_payment_date"
                    :label="__('Payment date')"
                    type="date"
                    required
                />

                <flux:input
                    wire:model="new_payment_amount"
                    :label="__('Amount')"
                    type="number"
                    step="0.01"
                    min="0.01"
                    required
                    placeholder="0.00"
                />

                <flux:select wire:model="new_payment_currency" :label="__('Currency')" required>
                    @foreach ($currencies as $currency)
                        <flux:select.option :value="$currency">{{ $currency }}</flux:select.option>
                    @endforeach
                </flux:select>
            </div>

            <div class="mt-4 flex items-center gap-4">
                <flux:button variant="primary" type="submit" data-test="record-payment-button">
                    {{ __('Record payment') }}
                </flux:button>
            </div>
        </form>
    </div>
</section>
