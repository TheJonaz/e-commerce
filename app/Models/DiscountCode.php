<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;

#[Fillable([
    'code', 'description', 'type', 'value',
    'min_order_amount', 'max_uses', 'times_used',
    'valid_from', 'valid_until', 'is_active',
])]
class DiscountCode extends Model
{
    public const TYPE_PERCENT = 'percent';
    public const TYPE_FIXED = 'fixed';

    protected function casts(): array
    {
        return [
            'value' => 'decimal:2',
            'min_order_amount' => 'decimal:2',
            'valid_from' => 'datetime',
            'valid_until' => 'datetime',
            'is_active' => 'boolean',
        ];
    }

    /**
     * @return array{valid: bool, reason?: string}
     */
    public function checkValidity(float $orderAmount = 0): array
    {
        if (! $this->is_active) {
            return ['valid' => false, 'reason' => 'Koden är inaktiv.'];
        }

        $now = now();
        if ($this->valid_from && $now->lt($this->valid_from)) {
            return ['valid' => false, 'reason' => 'Koden är inte aktiv ännu.'];
        }
        if ($this->valid_until && $now->gt($this->valid_until)) {
            return ['valid' => false, 'reason' => 'Koden har gått ut.'];
        }

        if ($this->max_uses !== null && $this->times_used >= $this->max_uses) {
            return ['valid' => false, 'reason' => 'Koden har nått sin användningsgräns.'];
        }

        if ($this->min_order_amount !== null && $orderAmount < (float) $this->min_order_amount) {
            return ['valid' => false, 'reason' => 'Lägsta ordervärde för koden är ' .
                \App\Support\Money::format((float) $this->min_order_amount, setting('shop.currency', 'SEK')) . '.'];
        }

        return ['valid' => true];
    }

    /** Apply this code to a gross amount, returning the discount in absolute money. */
    public function discountFor(float $orderAmount): float
    {
        if ($this->type === self::TYPE_PERCENT) {
            return round($orderAmount * ((float) $this->value / 100), 2);
        }

        // Fixed-amount: never discount more than the order itself.
        return min(round((float) $this->value, 2), round($orderAmount, 2));
    }
}
