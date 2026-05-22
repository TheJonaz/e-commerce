<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['cart_id', 'product_id', 'qty', 'price_snapshot', 'vat_rate_snapshot'])]
class CartItem extends Model
{
    protected function casts(): array
    {
        return [
            'price_snapshot' => 'decimal:2',
            'vat_rate_snapshot' => 'decimal:2',
        ];
    }

    public function cart(): BelongsTo
    {
        return $this->belongsTo(Cart::class);
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function lineTotal(): float
    {
        return round($this->qty * (float) $this->price_snapshot, 2);
    }
}
