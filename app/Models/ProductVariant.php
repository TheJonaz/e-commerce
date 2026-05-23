<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'product_id', 'sku', 'options', 'price', 'stock',
    'weight_grams', 'is_active', 'position',
])]
class ProductVariant extends Model
{
    protected function casts(): array
    {
        return [
            'options' => 'array',
            'price' => 'decimal:2',
            'is_active' => 'boolean',
        ];
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    /** Human label like "M / Röd". */
    public function label(): string
    {
        return collect($this->options ?? [])
            ->filter(fn ($v) => $v !== '' && $v !== null)
            ->values()
            ->implode(' / ');
    }

    public function vatRate(): float
    {
        return (float) $this->product?->vat_rate;
    }
}
