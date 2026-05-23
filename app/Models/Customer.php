<?php

namespace App\Models;

use Illuminate\Auth\Authenticatable;
use Illuminate\Contracts\Auth\Authenticatable as AuthenticatableContract;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Notifications\Notifiable;

#[Fillable([
    'email', 'password', 'name', 'phone',
    'accepts_marketing', 'is_business', 'vat_number', 'notes',
])]
#[Hidden(['password', 'remember_token'])]
class Customer extends Model implements AuthenticatableContract
{
    use Authenticatable, Notifiable;

    protected function casts(): array
    {
        return [
            'password' => 'hashed',
            'accepts_marketing' => 'boolean',
            'is_business' => 'boolean',
            'email_verified_at' => 'datetime',
        ];
    }

    public function addresses(): HasMany
    {
        return $this->hasMany(Address::class);
    }

    public function orders(): HasMany
    {
        return $this->hasMany(Order::class);
    }

    public function defaultShippingAddress(): ?Address
    {
        return $this->addresses()
            ->where('type', Address::TYPE_SHIPPING)
            ->orderByDesc('is_default')
            ->first();
    }

    public function hasPassword(): bool
    {
        return ! empty($this->getAuthPassword());
    }
}
