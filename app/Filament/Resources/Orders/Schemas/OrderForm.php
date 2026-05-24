<?php

namespace App\Filament\Resources\Orders\Schemas;

use App\Models\Order;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class OrderForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Order')
                ->columns(2)
                ->schema([
                    TextInput::make('order_number')->disabled(),
                    Select::make('status')
                        ->options([
                            Order::STATUS_PENDING => 'Pending',
                            Order::STATUS_PAID => 'Paid',
                            Order::STATUS_SHIPPED => 'Shipped',
                            Order::STATUS_DELIVERED => 'Delivered',
                            Order::STATUS_CANCELLED => 'Cancelled',
                            Order::STATUS_REFUNDED => 'Refunded',
                        ])
                        ->required(),
                    TextInput::make('email')->email()->required(),
                    Select::make('customer_id')->relationship('customer', 'name'),
                    Select::make('payment_status')->options([
                        'unpaid' => 'Unpaid', 'paid' => 'Paid',
                        'awaiting_invoice' => 'Awaiting invoice',
                        'awaiting_transfer' => 'Awaiting transfer',
                        'awaiting_payment' => 'Awaiting payment',
                        'pending_review' => 'Pending review',
                        'declined' => 'Declined', 'cancelled' => 'Cancelled',
                        'refunded' => 'Refunded',
                    ]),
                    Select::make('shipping_status')->options([
                        'not_shipped' => 'Not shipped', 'shipped' => 'Shipped',
                        'delivered' => 'Delivered', 'returned' => 'Returned',
                    ]),
                ]),

            Section::make('Shipping')
                ->columns(2)
                ->schema([
                    TextInput::make('shipping_method')->disabled(),
                    TextInput::make('shipping_total')->numeric()->disabled(),
                    TextInput::make('tracking_number')->label('Tracking number'),
                    TextInput::make('tracking_url')->label('Tracking URL')->url(),
                    DateTimePicker::make('shipped_at')->label('Skickad'),
                    DateTimePicker::make('delivered_at')->label('Levererad'),
                ]),

            Section::make('Amounts')
                ->columns(3)
                ->schema([
                    TextInput::make('currency')->disabled(),
                    TextInput::make('subtotal_excl_vat')->numeric()->disabled(),
                    TextInput::make('vat_total')->numeric()->disabled(),
                    TextInput::make('discount_total')->numeric()->disabled(),
                    TextInput::make('discount_code')->disabled(),
                    TextInput::make('grand_total')->numeric()->disabled(),
                ]),

            Section::make('Payment')
                ->columns(2)
                ->schema([
                    TextInput::make('payment_method')->disabled(),
                    TextInput::make('payment_reference'),
                ]),

            Section::make('Notes')
                ->schema([
                    Textarea::make('notes')->columnSpanFull()->rows(3),
                ]),

            Section::make('Addresses')
                ->columns(2)
                ->schema([
                    Textarea::make('shipping_address')->rows(6),
                    Textarea::make('billing_address')->rows(6),
                ]),
        ]);
    }
}
