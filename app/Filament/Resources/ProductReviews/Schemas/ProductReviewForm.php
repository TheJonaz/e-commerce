<?php

namespace App\Filament\Resources\ProductReviews\Schemas;

use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class ProductReviewForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->components([
            Section::make()
                ->columns(2)
                ->schema([
                    Select::make('product_id')->relationship('product', 'slug')->required()->disabled(),
                    Select::make('rating')->options([1 => '★', 2 => '★★', 3 => '★★★', 4 => '★★★★', 5 => '★★★★★'])->required(),
                    TextInput::make('name')->required(),
                    TextInput::make('email')->email()->required(),
                    TextInput::make('title')->columnSpanFull(),
                    Textarea::make('body')->rows(5)->columnSpanFull(),
                    Toggle::make('is_published'),
                    Toggle::make('is_verified_purchase')->disabled(),
                ]),
        ]);
    }
}
