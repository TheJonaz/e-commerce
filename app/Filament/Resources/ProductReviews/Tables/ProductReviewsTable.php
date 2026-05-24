<?php

namespace App\Filament\Resources\ProductReviews\Tables;

use App\Models\ProductReview;
use Filament\Actions\Action;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;

class ProductReviewsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('rating')
                    ->formatStateUsing(fn ($state) => str_repeat('★', (int) $state) . str_repeat('☆', 5 - (int) $state))
                    ->color('warning'),
                TextColumn::make('product.slug')->label('Produkt')->searchable()->limit(28),
                TextColumn::make('name')->searchable(),
                TextColumn::make('title')->limit(36)->placeholder('—'),
                IconColumn::make('is_verified_purchase')->label('Köp')->boolean()->toggleable(),
                IconColumn::make('is_published')->label('Publicerad')->boolean(),
                TextColumn::make('created_at')->dateTime('Y-m-d H:i')->sortable(),
            ])
            ->defaultSort('created_at', 'desc')
            ->filters([
                TernaryFilter::make('is_published'),
                TernaryFilter::make('is_verified_purchase'),
            ])
            ->recordActions([
                Action::make('togglePublished')
                    ->label(fn (ProductReview $r) => $r->is_published ? 'Avpublicera' : 'Publicera')
                    ->icon('heroicon-m-eye')
                    ->action(fn (ProductReview $r) => $r->update(['is_published' => ! $r->is_published])),
                EditAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([DeleteBulkAction::make()]),
            ]);
    }
}
