<?php

namespace App\Models;

use Database\Factories\PaymentFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * @property string $id
 * @property string $claim_id
 * @property Carbon $payment_date
 * @property int $amount
 * @property string $currency
 * @property string $fx_rate_snapshot
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read string $converted_amount
 */
#[Fillable([
    'claim_id',
    'payment_date',
    'amount',
    'currency',
    'fx_rate_snapshot',
])]
class Payment extends Model
{
    /** @use HasFactory<PaymentFactory> */
    use HasFactory, HasUuids;

    protected function casts(): array
    {
        return [
            'payment_date' => 'date',
            'amount' => 'decimal:0',
            'fx_rate_snapshot' => 'decimal:6',
        ];
    }

    public function claim(): BelongsTo
    {
        return $this->belongsTo(Claim::class);
    }

    /**
     * Convert this payment's amount into the claim's reserve currency
     * using the snapshot rate stored at save time.
     */
    public function getConvertedAmountAttribute(): string
    {
        if ($this->currency === $this->claim->reserve_currency) {
            return $this->amount;
        }

        return (string) bcmul($this->amount, $this->fx_rate_snapshot, 0);
    }
}
