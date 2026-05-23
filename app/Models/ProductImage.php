<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['product_id', 'path', 'alt', 'position'])]
class ProductImage extends Model
{
    protected function casts(): array
    {
        return ['alt' => 'array'];
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function url(): string
    {
        return rtrim((string) config('filesystems.disks.shop.url'), '/').'/'.ltrim($this->path, '/');
    }

    public function localizedAlt(?string $locale = null): string
    {
        $locale ??= app()->getLocale();
        $alt = $this->alt ?? [];

        if (is_array($alt)) {
            return $alt[$locale] ?? array_values($alt)[0] ?? '';
        }

        return (string) $alt;
    }
}
