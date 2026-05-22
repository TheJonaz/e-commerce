<?php

namespace App\Models;

use App\Models\Concerns\BelongsToTenant;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable([
    'tenant_id', 'customer_id', 'order_number', 'email', 'currency',
    'subtotal_excl_vat', 'vat_total', 'shipping_total', 'discount_total', 'grand_total',
    'status', 'payment_status', 'shipping_status',
    'payment_method', 'payment_reference', 'shipping_method',
    'shipping_address', 'billing_address', 'notes', 'placed_at',
])]
class Order extends Model
{
    use BelongsToTenant;

    public const STATUS_PENDING = 'pending';
    public const STATUS_PAID = 'paid';
    public const STATUS_SHIPPED = 'shipped';
    public const STATUS_DELIVERED = 'delivered';
    public const STATUS_CANCELLED = 'cancelled';
    public const STATUS_REFUNDED = 'refunded';

    protected function casts(): array
    {
        return [
            'subtotal_excl_vat' => 'decimal:2',
            'vat_total' => 'decimal:2',
            'shipping_total' => 'decimal:2',
            'discount_total' => 'decimal:2',
            'grand_total' => 'decimal:2',
            'shipping_address' => 'array',
            'billing_address' => 'array',
            'placed_at' => 'datetime',
        ];
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(OrderItem::class);
    }
}
