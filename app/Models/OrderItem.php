<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'order_id', 'product_id', 'variant_id',
    'name_snapshot', 'sku_snapshot', 'variant_options_snapshot',
    'qty', 'unit_price_incl_vat', 'vat_rate',
    'line_total_incl_vat', 'line_vat_amount',
])]
class OrderItem extends Model
{
    protected function casts(): array
    {
        return [
            'unit_price_incl_vat' => 'decimal:2',
            'vat_rate' => 'decimal:2',
            'line_total_incl_vat' => 'decimal:2',
            'line_vat_amount' => 'decimal:2',
            'variant_options_snapshot' => 'array',
        ];
    }

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }
}
