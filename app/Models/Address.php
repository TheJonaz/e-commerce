<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'customer_id', 'type', 'name', 'company', 'street', 'street2',
    'zip', 'city', 'country', 'phone', 'is_default',
])]
class Address extends Model
{
    public const TYPE_BILLING = 'billing';
    public const TYPE_SHIPPING = 'shipping';

    protected function casts(): array
    {
        return [
            'is_default' => 'boolean',
        ];
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }
}
