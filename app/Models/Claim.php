<?php

namespace App\Models;

use Database\Factories\ClaimFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

/**
 * @property string $id
 * @property string $policy_number
 * @property string $insured_name
 * @property Carbon $loss_date
 * @property Carbon $date_notified
 * @property string $loss_nature
 * @property string $reserve_currency
 * @property int $estimated_loss_amount
 * @property int|null $approved_amount
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read string $total_paid
 * @property-read string|null $outstanding_balance
 * @property-read string $status
 */
#[Fillable([
    'policy_number',
    'insured_name',
    'loss_date',
    'date_notified',
    'loss_nature',
    'reserve_currency',
    'estimated_loss_amount',
    'approved_amount',
])]
class Claim extends Model
{
    /** @use HasFactory<ClaimFactory> */
    use HasFactory, HasUuids;

    protected function casts(): array
    {
        return [
            'loss_date' => 'date',
            'date_notified' => 'date',
            'estimated_loss_amount' => 'decimal:0',
            'approved_amount' => 'decimal:0',
        ];
    }

    /**
     * @return HasMany<Payment, $this>
     */
    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class);
    }

    /**
     * Get the total paid amount converted to the claim's reserve currency.
     */
    public function getTotalPaidAttribute(): string
    {
        $total = $this->payments->reduce(function (int $carry, Payment $payment): int {
            return $carry + (int) $payment->converted_amount;
        }, 0);

        return (string) $total;
    }

    /**
     * Get the outstanding balance (approved - paid).
     * Returns null if no approved amount is set.
     */
    public function getOutstandingBalanceAttribute(): ?string
    {
        if ($this->approved_amount === null) {
            return null;
        }

        $balance = (int) $this->approved_amount - (int) $this->total_paid;

        return (string) max(0, $balance);
    }

    /**
     * Derive the claim's status based on approved amount and balance.
     */
    public function getStatusAttribute(): string
    {
        if ($this->approved_amount === null) {
            return 'Reserved, not yet settled';
        }

        if ((int) $this->approved_amount <= (int) $this->total_paid) {
            return 'Settled and paid';
        }

        return 'Settled, payment outstanding';
    }
}
