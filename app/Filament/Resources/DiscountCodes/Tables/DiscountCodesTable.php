<?php

namespace App\Filament\Resources\DiscountCodes\Tables;

use App\Models\DiscountCode;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;

class DiscountCodesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('code')->searchable()->copyable()->fontFamily('mono')->weight('bold'),
                TextColumn::make('description')->limit(40)->placeholder('—'),
                TextColumn::make('type')
                    ->badge()
                    ->color(fn (string $state): string => $state === DiscountCode::TYPE_PERCENT ? 'info' : 'warning'),
                TextColumn::make('value')
                    ->formatStateUsing(fn ($state, $record) => $record->type === DiscountCode::TYPE_PERCENT
                        ? $state . ' %'
                        : \App\Support\Money::format($state, setting('shop.currency', 'SEK'))),
                TextColumn::make('times_used')
                    ->label('Använd')
                    ->formatStateUsing(fn ($state, $record) => $state . ($record->max_uses ? ' / ' . $record->max_uses : ''))
                    ->sortable(),
                TextColumn::make('valid_until')->dateTime('Y-m-d')->placeholder('—')->toggleable(),
                IconColumn::make('is_active')->boolean(),
            ])
            ->defaultSort('id', 'desc')
            ->filters([
                TernaryFilter::make('is_active'),
            ])
            ->recordActions([EditAction::make()])
            ->toolbarActions([
                BulkActionGroup::make([DeleteBulkAction::make()]),
            ]);
    }
}
