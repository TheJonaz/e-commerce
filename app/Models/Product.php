<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

#[Fillable([
    'sku', 'slug', 'name', 'short_description', 'description',
    'price', 'vat_rate', 'stock', 'type', 'is_active', 'settings',
])]
class Product extends Model
{
    public const TYPE_PHYSICAL = 'physical';
    public const TYPE_DIGITAL = 'digital';
    public const TYPE_SUBSCRIPTION = 'subscription';

    protected function casts(): array
    {
        return [
            'name' => 'array',
            'short_description' => 'array',
            'description' => 'array',
            'price' => 'decimal:2',
            'vat_rate' => 'decimal:2',
            'is_active' => 'boolean',
            'settings' => 'array',
        ];
    }

    public function categories(): BelongsToMany
    {
        return $this->belongsToMany(Category::class)->withPivot('position');
    }

    public function priceExclVat(): float
    {
        return round((float) $this->price / (1 + ((float) $this->vat_rate / 100)), 2);
    }

    public function vatAmount(): float
    {
        return round((float) $this->price - $this->priceExclVat(), 2);
    }
}
