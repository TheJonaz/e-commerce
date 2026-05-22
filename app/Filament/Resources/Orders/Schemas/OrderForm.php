<?php

namespace App\Filament\Resources\Orders\Schemas;

use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Schemas\Schema;

class OrderForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('tenant_id')
                    ->relationship('tenant', 'name')
                    ->required(),
                Select::make('customer_id')
                    ->relationship('customer', 'name'),
                TextInput::make('order_number')
                    ->required(),
                TextInput::make('email')
                    ->label('Email address')
                    ->email()
                    ->required(),
                TextInput::make('currency')
                    ->required()
                    ->default('SEK'),
                TextInput::make('subtotal_excl_vat')
                    ->required()
                    ->numeric(),
                TextInput::make('vat_total')
                    ->required()
                    ->numeric(),
                TextInput::make('shipping_total')
                    ->required()
                    ->numeric()
                    ->default(0),
                TextInput::make('discount_total')
                    ->required()
                    ->numeric()
                    ->default(0),
                TextInput::make('grand_total')
                    ->required()
                    ->numeric(),
                TextInput::make('status')
                    ->required()
                    ->default('pending'),
                TextInput::make('payment_status')
                    ->required()
                    ->default('unpaid'),
                TextInput::make('shipping_status')
                    ->required()
                    ->default('not_shipped'),
                TextInput::make('payment_method'),
                TextInput::make('payment_reference'),
                TextInput::make('shipping_method'),
                Textarea::make('shipping_address')
                    ->columnSpanFull(),
                Textarea::make('billing_address')
                    ->columnSpanFull(),
                Textarea::make('notes')
                    ->columnSpanFull(),
                DateTimePicker::make('placed_at'),
            ]);
    }
}
