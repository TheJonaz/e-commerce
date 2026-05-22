<?php

namespace App\Filament\Resources\Products\Schemas;

use App\Models\Product;
use App\Support\Vat;
use Filament\Forms\Components\KeyValue;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class ProductForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Identification')
                ->columns(2)
                ->schema([
                    TextInput::make('sku')->label('SKU'),
                    TextInput::make('slug')->required(),
                    Select::make('type')
                        ->options([
                            Product::TYPE_PHYSICAL => 'Physical',
                            Product::TYPE_DIGITAL => 'Digital',
                            Product::TYPE_SUBSCRIPTION => 'Subscription',
                        ])
                        ->default(Product::TYPE_PHYSICAL)
                        ->required(),
                    Toggle::make('is_active')->default(true),
                ]),

            Section::make('Translations')
                ->columns(2)
                ->schema([
                    KeyValue::make('name')
                        ->keyLabel('Locale')
                        ->valueLabel('Name')
                        ->default(['sv' => '', 'en' => ''])
                        ->required()
                        ->columnSpanFull(),
                    Textarea::make('short_description')
                        ->helperText('JSON object keyed by locale')
                        ->rows(3)
                        ->columnSpanFull(),
                    Textarea::make('description')
                        ->helperText('JSON object keyed by locale')
                        ->rows(5)
                        ->columnSpanFull(),
                ]),

            Section::make('Pricing & Stock')
                ->columns(3)
                ->schema([
                    TextInput::make('price')
                        ->required()
                        ->numeric()
                        ->suffix(setting('shop.currency', 'SEK'))
                        ->step(0.01)
                        ->helperText('Including VAT'),
                    Select::make('vat_rate')
                        ->options(array_combine(
                            array_map(fn ($r) => (string) number_format($r, 2, '.', ''), [Vat::RATE_STANDARD, Vat::RATE_REDUCED, Vat::RATE_LOW, Vat::RATE_NONE]),
                            ['25 %', '12 %', '6 %', '0 %']
                        ))
                        ->default((string) number_format(Vat::RATE_STANDARD, 2, '.', ''))
                        ->required(),
                    TextInput::make('stock')->numeric()->minValue(0),
                ]),
        ]);
    }
}
