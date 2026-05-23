<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable([
    'sku', 'slug', 'name', 'short_description', 'description',
    'image_path', 'price', 'vat_rate', 'stock', 'weight_grams',
    'type', 'is_active', 'settings',
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

    public function images(): HasMany
    {
        return $this->hasMany(ProductImage::class)->orderBy('position');
    }

    public function variants(): HasMany
    {
        return $this->hasMany(ProductVariant::class)->orderBy('position');
    }

    public function activeVariants(): HasMany
    {
        return $this->variants()->where('is_active', true);
    }

    public function hasVariants(): bool
    {
        if ($this->relationLoaded('variants')) {
            return $this->variants->where('is_active', true)->isNotEmpty();
        }

        return $this->variants()->where('is_active', true)->exists();
    }

    public function priceExclVat(): float
    {
        return round((float) $this->price / (1 + ((float) $this->vat_rate / 100)), 2);
    }

    public function vatAmount(): float
    {
        return round((float) $this->price - $this->priceExclVat(), 2);
    }

    /** Display price honoring the shop.prices_include_vat setting (gross by default). */
    public function displayPrice(): float
    {
        return (bool) setting('shop.prices_include_vat', '1')
            ? (float) $this->price
            : $this->priceExclVat();
    }

    public function vatLabel(): string
    {
        return (bool) setting('shop.prices_include_vat', '1')
            ? __('shop.product.price_incl_vat')
            : __('shop.product.price_excl_vat');
    }

    public function imageUrl(): ?string
    {
        $first = $this->relationLoaded('images')
            ? $this->images->first()
            : $this->images()->first();

        if ($first) {
            return $first->url();
        }

        // Legacy single-image fallback for products created before the gallery.
        if ($this->image_path) {
            return rtrim(config('filesystems.disks.shop.url'), '/').'/'.ltrim($this->image_path, '/');
        }

        return null;
    }

    public function localized(string $field, ?string $locale = null): string
    {
        $locale ??= app()->getLocale();
        $value = $this->{$field};

        if (is_array($value)) {
            return $value[$locale] ?? $value[config('shop.default_locale', 'sv')] ?? array_values($value)[0] ?? '';
        }

        return (string) ($value ?? '');
    }
}
