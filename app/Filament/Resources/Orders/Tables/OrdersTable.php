<?php

namespace App\Filament\Resources\Orders\Tables;

use App\Models\Order;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class OrdersTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('order_number')->label('Order')->searchable()->sortable(),
                TextColumn::make('placed_at')->label('Placed')->dateTime('Y-m-d H:i')->sortable(),
                TextColumn::make('customer.name')->searchable()->placeholder('—'),
                TextColumn::make('email')->searchable()->toggleable(),
                TextColumn::make('grand_total')
                    ->label('Total')
                    ->money(fn ($record) => $record->currency)
                    ->sortable(),
                TextColumn::make('status')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        Order::STATUS_PENDING => 'warning',
                        Order::STATUS_PAID => 'success',
                        Order::STATUS_SHIPPED => 'info',
                        Order::STATUS_DELIVERED => 'success',
                        Order::STATUS_CANCELLED => 'danger',
                        Order::STATUS_REFUNDED => 'gray',
                        default => 'gray',
                    }),
                TextColumn::make('payment_status')->badge()->color('gray')->toggleable(),
                TextColumn::make('shipping_method')->badge()->toggleable(),
                TextColumn::make('payment_method')->badge()->toggleable(),
            ])
            ->defaultSort('placed_at', 'desc')
            ->filters([
                SelectFilter::make('status')->options([
                    Order::STATUS_PENDING => 'Pending',
                    Order::STATUS_PAID => 'Paid',
                    Order::STATUS_SHIPPED => 'Shipped',
                    Order::STATUS_DELIVERED => 'Delivered',
                    Order::STATUS_CANCELLED => 'Cancelled',
                    Order::STATUS_REFUNDED => 'Refunded',
                ]),
            ])
            ->recordActions([EditAction::make()])
            ->toolbarActions([
                BulkActionGroup::make([DeleteBulkAction::make()]),
            ]);
    }
}
