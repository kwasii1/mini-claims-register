<?php

use App\Models\Claim;
use Livewire\Attributes\Title;
use Livewire\Component;

new #[Title('Dashboard')] class extends Component {
    public int $totalClaims = 0;
    public int $reservedCount = 0;
    public int $outstandingCount = 0;
    public int $settledCount = 0;
    public string $totalApproved = '0';
    public string $totalPaid = '0';
    public string $totalOutstanding = '0';
    /** @var \Illuminate\Support\Collection<int, Claim> */
    public $recentClaims;

    public function mount(): void
    {
        $claims = Claim::with('payments')->get();

        $this->totalClaims = $claims->count();
        $this->reservedCount = $claims->filter(fn (Claim $c) => $c->status === 'Reserved, not yet settled')->count();
        $this->outstandingCount = $claims->filter(fn (Claim $c) => $c->status === 'Settled, payment outstanding')->count();
        $this->settledCount = $claims->filter(fn (Claim $c) => $c->status === 'Settled and paid')->count();

        $this->totalApproved = number_format($claims->sum('approved_amount') / 100, 2);
        $this->totalPaid = number_format($claims->sum('total_paid') / 100, 2);

        $outstandingSum = $claims->sum(function (Claim $c) {
            $balance = $c->outstanding_balance;
            return $balance !== null ? (int) $balance : 0;
        });
        $this->totalOutstanding = number_format($outstandingSum / 100, 2);

        $this->recentClaims = $claims->sortByDesc('created_at')->take(5)->values();
    }

    public function statusColor(string $status): string
    {
        return match ($status) {
            'Settled and paid' => 'green',
            'Settled, payment outstanding' => 'amber',
            default => 'zinc',
        };
    }
}; ?>

<section class="w-full">
    <div class="mb-6">
        <flux:heading size="xl" level="1">{{ __('Dashboard') }}</flux:heading>
        <flux:text class="mt-1">{{ __('Claims overview and recent activity') }}</flux:text>
    </div>

    <div class="grid grid-cols-2 gap-4 lg:grid-cols-4">
        <div class="rounded-xl border border-zinc-200 p-4 dark:border-zinc-700">
            <div class="flex items-center gap-3">
                <div class="flex size-10 shrink-0 items-center justify-center rounded-lg bg-blue-100 dark:bg-blue-900/30">
                    <flux:icon.document-text class="size-5 text-blue-600 dark:text-blue-400" />
                </div>
                <div class="min-w-0">
                    <flux:text variant="subtle">{{ __('Total claims') }}</flux:text>
                    <p class="text-2xl font-semibold">{{ $this->totalClaims }}</p>
                </div>
            </div>
        </div>

        <div class="rounded-xl border border-zinc-200 p-4 dark:border-zinc-700">
            <div class="flex items-center gap-3">
                <div class="flex size-10 shrink-0 items-center justify-center rounded-lg bg-zinc-100 dark:bg-zinc-800">
                    <flux:icon.clock class="size-5 text-zinc-600 dark:text-zinc-400" />
                </div>
                <div class="min-w-0">
                    <flux:text variant="subtle">{{ __('Reserved') }}</flux:text>
                    <p class="text-2xl font-semibold">{{ $this->reservedCount }}</p>
                </div>
            </div>
        </div>

        <div class="rounded-xl border border-zinc-200 p-4 dark:border-zinc-700">
            <div class="flex items-center gap-3">
                <div class="flex size-10 shrink-0 items-center justify-center rounded-lg bg-amber-100 dark:bg-amber-900/30">
                    <flux:icon.currency-dollar class="size-5 text-amber-600 dark:text-amber-400" />
                </div>
                <div class="min-w-0">
                    <flux:text variant="subtle">{{ __('Outstanding') }}</flux:text>
                    <p class="text-2xl font-semibold">{{ $this->outstandingCount }}</p>
                </div>
            </div>
        </div>

        <div class="rounded-xl border border-zinc-200 p-4 dark:border-zinc-700">
            <div class="flex items-center gap-3">
                <div class="flex size-10 shrink-0 items-center justify-center rounded-lg bg-green-100 dark:bg-green-900/30">
                    <flux:icon.check-circle class="size-5 text-green-600 dark:text-green-400" />
                </div>
                <div class="min-w-0">
                    <flux:text variant="subtle">{{ __('Settled') }}</flux:text>
                    <p class="text-2xl font-semibold">{{ $this->settledCount }}</p>
                </div>
            </div>
        </div>
    </div>

    <div class="mt-6 grid grid-cols-1 gap-4 sm:grid-cols-3">
        <div class="rounded-xl border border-zinc-200 p-4 dark:border-zinc-700">
            <flux:text variant="subtle">{{ __('Total approved') }}</flux:text>
            <p class="mt-1 text-xl font-semibold font-mono">{{ $this->totalApproved }}</p>
        </div>

        <div class="rounded-xl border border-zinc-200 p-4 dark:border-zinc-700">
            <flux:text variant="subtle">{{ __('Total paid') }}</flux:text>
            <p class="mt-1 text-xl font-semibold font-mono">{{ $this->totalPaid }}</p>
        </div>

        <div class="rounded-xl border border-zinc-200 p-4 dark:border-zinc-700">
            <flux:text variant="subtle">{{ __('Total outstanding') }}</flux:text>
            <p class="mt-1 text-xl font-semibold font-mono">{{ $this->totalOutstanding }}</p>
        </div>
    </div>

    <div class="mt-8">
        <div class="flex items-center justify-between">
            <flux:heading level="2">{{ __('Recent claims') }}</flux:heading>
            <flux:link :href="route('claims.index')" variant="subtle" wire:navigate>
                {{ __('View all') }} &rarr;
            </flux:link>
        </div>

        @if ($this->recentClaims->isEmpty())
            <div class="mt-4 rounded-xl border border-dashed border-zinc-300 p-8 text-center dark:border-zinc-600">
                <p class="font-medium">{{ __('No claims yet') }}</p>
                <flux:text class="mt-1">{{ __('Register your first claim to get started.') }}</flux:text>
            </div>
        @else
            <div class="mt-4 overflow-x-auto rounded-xl border border-zinc-200 dark:border-zinc-700">
                <table class="w-full text-sm">
                    <thead>
                        <tr class="border-b border-zinc-200 bg-zinc-50 dark:border-zinc-700 dark:bg-zinc-800/50">
                            <th class="px-4 py-3 text-left font-medium">{{ __('Policy #') }}</th>
                            <th class="hidden px-4 py-3 text-left font-medium sm:table-cell">{{ __('Insured') }}</th>
                            <th class="px-4 py-3 text-left font-medium">{{ __('Status') }}</th>
                            <th class="hidden px-4 py-3 text-right font-medium sm:table-cell">{{ __('Estimated loss') }}</th>
                            <th class="hidden px-4 py-3 text-center font-medium sm:table-cell">{{ __('Currency') }}</th>
                            <th class="hidden px-4 py-3 text-left font-medium lg:table-cell">{{ __('Created') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($this->recentClaims as $claim)
                            <tr class="{{ !$loop->last ? 'border-b border-zinc-200 dark:border-zinc-700' : '' }} hover:bg-zinc-50 dark:hover:bg-zinc-800/50">
                                <td class="px-4 py-3">
                                    <a href="{{ route('claims.show', $claim) }}" class="font-medium text-blue-600 hover:underline dark:text-blue-400">
                                        {{ $claim->policy_number }}
                                    </a>
                                    <span class="mt-0.5 block text-xs text-zinc-500 sm:hidden">{{ $claim->insured_name }}</span>
                                </td>
                                <td class="hidden px-4 py-3 sm:table-cell">{{ $claim->insured_name }}</td>
                                <td class="px-4 py-3">
                                    <flux:badge :color="$this->statusColor($claim->status)">
                                        {{ $claim->status }}
                                    </flux:badge>
                                </td>
                                <td class="hidden px-4 py-3 text-right font-mono sm:table-cell">
                                    {{ number_format($claim->estimated_loss_amount / 100, 2) }}
                                </td>
                                <td class="hidden px-4 py-3 text-center sm:table-cell">{{ $claim->reserve_currency }}</td>
                                <td class="hidden px-4 py-3 text-left text-zinc-500 lg:table-cell">{{ $claim->created_at->format('d M Y') }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </div>
</section>
