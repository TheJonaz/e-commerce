<?php

namespace App\Filament\Resources\DiscountCodes\Schemas;

use App\Models\DiscountCode;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class DiscountCodeForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->components([
            Section::make()
                ->columns(2)
                ->schema([
                    TextInput::make('code')
                        ->required()
                        ->unique(ignoreRecord: true)
                        ->placeholder('VINTER25')
                        ->helperText('Versaler. Kunder skriver in den i kassan.')
                        ->dehydrateStateUsing(fn ($state) => strtoupper(trim((string) $state))),
                    TextInput::make('description')
                        ->placeholder('Vinterrea — 25 % på allt'),
                    Select::make('type')
                        ->options([
                            DiscountCode::TYPE_PERCENT => 'Procent (%)',
                            DiscountCode::TYPE_FIXED => 'Fast belopp',
                        ])
                        ->default(DiscountCode::TYPE_PERCENT)
                        ->required(),
                    TextInput::make('value')
                        ->required()
                        ->numeric()
                        ->step(0.01)
                        ->helperText('Antingen procentsats (0-100) eller fast belopp i butikens valuta.'),
                    TextInput::make('min_order_amount')
                        ->label('Minsta ordervärde')
                        ->numeric()->step(0.01)
                        ->helperText('Lämna tomt för inget minimum.'),
                    TextInput::make('max_uses')
                        ->label('Max antal användningar')
                        ->numeric()->minValue(1)
                        ->helperText('Lämna tomt för obegränsat.'),
                    DateTimePicker::make('valid_from')->label('Giltig från'),
                    DateTimePicker::make('valid_until')->label('Giltig till'),
                    Toggle::make('is_active')->default(true)->columnSpanFull(),
                ]),
        ]);
    }
}
