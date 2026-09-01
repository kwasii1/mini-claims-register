<?php

use App\Models\Claim;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithPagination;

new #[Title('All claims')] class extends Component {
    use WithPagination;

    public string $search = '';
    public ?string $statusFilter = null;
    public ?string $currencyFilter = null;
    public string $dateField = 'loss_date';
    public ?string $dateFrom = null;
    public ?string $dateTo = null;
    public int $perPage = 10;

    protected array $statusOptions = [
        'reserved' => 'Reserved, not yet settled',
        'outstanding' => 'Settled, payment outstanding',
        'settled' => 'Settled and paid',
    ];

    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    public function updatedStatusFilter(): void
    {
        $this->resetPage();
    }

    public function updatedCurrencyFilter(): void
    {
        $this->resetPage();
    }

    public function updatedDateFrom(): void
    {
        $this->resetPage();
    }

    public function updatedDateTo(): void
    {
        $this->resetPage();
    }

    public function updatedDateField(): void
    {
        $this->resetPage();
    }

    public function updatedPerPage(): void
    {
        $this->resetPage();
    }

    public function resetFilters(): void
    {
        $this->search = '';
        $this->statusFilter = null;
        $this->currencyFilter = null;
        $this->dateField = 'loss_date';
        $this->dateFrom = null;
        $this->dateTo = null;
        $this->resetPage();
    }

    public function claims()
    {
        $query = Claim::with('payments');

        if (filled($this->search)) {
            $search = "%{$this->search}%";
            $query->where(function ($q) use ($search) {
                $q->where('policy_number', 'like', $search)
                    ->orWhere('insured_name', 'like', $search);
            });
        }

        if (filled($this->currencyFilter)) {
            $query->where('reserve_currency', $this->currencyFilter);
        }

        if (filled($this->dateFrom)) {
            $query->where($this->dateField, '>=', $this->dateFrom);
        }

        if (filled($this->dateTo)) {
            $query->where($this->dateField, '<=', $this->dateTo);
        }

        $allClaims = $query->orderBy('created_at', 'desc')->get();

        if (filled($this->statusFilter)) {
            $targetStatus = $this->statusOptions[$this->statusFilter] ?? $this->statusFilter;
            $allClaims = $allClaims->filter(fn (Claim $claim) => $claim->status === $targetStatus);
        }

        $perPage = $this->perPage;
        $page = $this->getPage();
        $paged = $allClaims->slice(($page - 1) * $perPage, $perPage)->values();

        return new \Illuminate\Pagination\LengthAwarePaginator(
            $paged,
            $allClaims->count(),
            $perPage,
            $page,
            ['path' => request()->url(), 'query' => request()->query()]
        );
    }

    public function totals()
    {
        $query = Claim::with('payments');

        if (filled($this->search)) {
            $search = "%{$this->search}%";
            $query->where(function ($q) use ($search) {
                $q->where('policy_number', 'like', $search)
                    ->orWhere('insured_name', 'like', $search);
            });
        }

        if (filled($this->currencyFilter)) {
            $query->where('reserve_currency', $this->currencyFilter);
        }

        if (filled($this->dateFrom)) {
            $query->where($this->dateField, '>=', $this->dateFrom);
        }

        if (filled($this->dateTo)) {
            $query->where($this->dateField, '<=', $this->dateTo);
        }

        $allClaims = $query->get();

        if (filled($this->statusFilter)) {
            $targetStatus = $this->statusOptions[$this->statusFilter] ?? $this->statusFilter;
            $allClaims = $allClaims->filter(fn (Claim $claim) => $claim->status === $targetStatus);
        }

        return $allClaims
            ->groupBy('reserve_currency')
            ->map(fn ($claims, $currency) => [
                'currency' => $currency,
                'count' => $claims->count(),
                'approved' => $claims->sum('approved_amount'),
                'paid' => $claims->sum('total_paid'),
            ])
            ->values();
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
        <flux:heading size="xl" level="1">{{ __('All claims') }}</flux:heading>
        <flux:text class="mt-1">{{ __('View and filter insurance claims') }}</flux:text>
    </div>

    <div class="rounded-xl border border-zinc-200 p-4 dark:border-zinc-700">
        <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-4">
            <flux:input
                wire:model.live="search"
                :label="__('Search')"
                type="text"
                placeholder="{{ __('Policy # or name...') }}"
                autocomplete="off"
            />

            <flux:select wire:model.live="statusFilter" :label="__('Status')">
                <flux:select.option value="">{{ __('All statuses') }}</flux:select.option>
                <flux:select.option value="reserved">{{ __('Reserved, not yet settled') }}</flux:select.option>
                <flux:select.option value="outstanding">{{ __('Settled, payment outstanding') }}</flux:select.option>
                <flux:select.option value="settled">{{ __('Settled and paid') }}</flux:select.option>
            </flux:select>

            <flux:select wire:model.live="currencyFilter" :label="__('Currency')">
                <flux:select.option value="">{{ __('All currencies') }}</flux:select.option>
                <flux:select.option value="USD">USD</flux:select.option>
                <flux:select.option value="GBP">GBP</flux:select.option>
                <flux:select.option value="EUR">EUR</flux:select.option>
            </flux:select>

            <flux:select wire:model.live="dateField" :label="__('Date field')">
                <flux:select.option value="loss_date">{{ __('Loss date') }}</flux:select.option>
                <flux:select.option value="date_notified">{{ __('Date notified') }}</flux:select.option>
            </flux:select>
        </div>

        <div class="mt-4 grid grid-cols-1 gap-4 sm:grid-cols-2">
            <flux:input
                wire:model.live="dateFrom"
                :label="__('Date from')"
                type="date"
            />

            <flux:input
                wire:model.live="dateTo"
                :label="__('Date to')"
                type="date"
            />
        </div>

        <div class="mt-4 flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
            <flux:button variant="subtle" wire:click="resetFilters" data-test="reset-filters-button">
                {{ __('Reset filters') }}
            </flux:button>

            <div class="flex items-center gap-2">
                <flux:text variant="subtle">{{ __('Per page') }}</flux:text>
                <flux:select wire:model.live="perPage" class="w-20">
                    <flux:select.option value="10">10</flux:select.option>
                    <flux:select.option value="25">25</flux:select.option>
                    <flux:select.option value="50">50</flux:select.option>
                    <flux:select.option value="100">100</flux:select.option>
                </flux:select>
            </div>
        </div>
    </div>

    <div class="mt-6">
        @if ($this->claims()->isEmpty())
            <div class="rounded-xl border border-dashed border-zinc-300 p-8 text-center dark:border-zinc-600">
                <div class="mx-auto mb-4 flex size-14 items-center justify-center rounded-2xl bg-zinc-100 dark:bg-zinc-800">
                    <flux:icon.document-text class="size-7 text-zinc-400 dark:text-zinc-500" />
                </div>
                <p class="font-medium">{{ __('No claims found') }}</p>
                <flux:text class="mt-1">{{ __('Try adjusting your filters or register a new claim.') }}</flux:text>
            </div>
        @else
            <div class="overflow-x-auto rounded-xl border border-zinc-200 dark:border-zinc-700">
                <table class="w-full text-sm">
                    <thead>
                        <tr class="border-b border-zinc-200 bg-zinc-50 dark:border-zinc-700 dark:bg-zinc-800/50">
                            <th class="px-4 py-3 text-left font-medium">{{ __('Policy #') }}</th>
                            <th class="hidden px-4 py-3 text-left font-medium sm:table-cell">{{ __('Insured') }}</th>
                            <th class="px-4 py-3 text-left font-medium">{{ __('Status') }}</th>
                            <th class="hidden px-4 py-3 text-right font-medium md:table-cell">{{ __('Approved') }}</th>
                            <th class="hidden px-4 py-3 text-right font-medium md:table-cell">{{ __('Paid') }}</th>
                            <th class="hidden px-4 py-3 text-right font-medium md:table-cell">{{ __('Balance') }}</th>
                            <th class="hidden px-4 py-3 text-center font-medium sm:table-cell">{{ __('Currency') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($this->claims() as $claim)
                            <tr class="{{ !$loop->last ? 'border-b border-zinc-200 dark:border-zinc-700' : '' }} hover:bg-zinc-50 dark:hover:bg-zinc-800/50">
                                <td class="px-4 py-3">
                                    <a href="{{ route('claims.show', $claim) }}" class="font-medium text-blue-600 hover:underline dark:text-blue-400" data-test="claim-link">
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
                                <td class="hidden px-4 py-3 text-right font-mono md:table-cell">
                                    @if ($claim->approved_amount !== null)
                                        {{ number_format($claim->approved_amount / 100, 2) }}
                                    @else
                                        <span class="text-zinc-400">&mdash;</span>
                                    @endif
                                </td>
                                <td class="hidden px-4 py-3 text-right font-mono md:table-cell">
                                    {{ number_format($claim->total_paid / 100, 2) }}
                                </td>
                                <td class="hidden px-4 py-3 text-right font-mono md:table-cell">
                                    @if ($claim->outstanding_balance !== null)
                                        {{ number_format($claim->outstanding_balance / 100, 2) }}
                                    @else
                                        <span class="text-zinc-400">&mdash;</span>
                                    @endif
                                </td>
                                <td class="hidden px-4 py-3 text-center sm:table-cell">{{ $claim->reserve_currency }}</td>
                            </tr>
                        @endforeach
                    </tbody>

                    @if ($this->totals()->isNotEmpty())
                        <tfoot>
                            @foreach ($this->totals() as $total)
                                <tr class="border-t border-zinc-200 bg-zinc-50 font-medium dark:border-zinc-700 dark:bg-zinc-800/50">
                                    <td colspan="3" class="px-4 py-3 text-right">
                                        {{ $total['count'] }} {{ __('claim') }}{{ $total['count'] !== 1 ? 's' : '' }} ({{ $total['currency'] }})
                                    </td>
                                    <td class="hidden px-4 py-3 text-right font-mono md:table-cell">
                                        {{ number_format($total['approved'] / 100, 2) }}
                                    </td>
                                    <td class="hidden px-4 py-3 text-right font-mono md:table-cell">
                                        {{ number_format($total['paid'] / 100, 2) }}
                                    </td>
                                    <td colspan="2"></td>
                                </tr>
                            @endforeach
                        </tfoot>
                    @endif
                </table>
            </div>

            <div class="mt-4">
                {{ $this->claims()->links() }}
            </div>
        @endif
    </div>
</section>
