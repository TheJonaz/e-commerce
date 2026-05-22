<?php

namespace App\Filament\Resources\Products\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class ProductsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('tenant.name')->label('Shop')->searchable(),
                TextColumn::make('sku')->label('SKU')->searchable(),
                TextColumn::make('name')
                    ->label('Name')
                    ->formatStateUsing(fn ($state) => is_array($state) ? ($state[app()->getLocale()] ?? array_values($state)[0] ?? '') : $state)
                    ->searchable(query: function ($query, string $search) {
                        $query->where('name', 'like', "%{$search}%");
                    }),
                TextColumn::make('price')
                    ->money(fn ($record) => $record->tenant?->currency ?? 'SEK')
                    ->sortable(),
                TextColumn::make('vat_rate')->suffix(' %')->sortable(),
                TextColumn::make('stock')->numeric()->sortable(),
                TextColumn::make('type')->badge(),
                IconColumn::make('is_active')->boolean(),
                TextColumn::make('updated_at')->dateTime()->since()->sortable()->toggleable(isToggledHiddenByDefault: true),
            ])
            ->defaultSort('updated_at', 'desc')
            ->filters([])
            ->recordActions([EditAction::make()])
            ->toolbarActions([
                BulkActionGroup::make([DeleteBulkAction::make()]),
            ]);
    }
}
