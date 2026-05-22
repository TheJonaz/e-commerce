<?php

namespace App\Filament\Resources\Tenants\Schemas;

use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Schema;

class TenantForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('slug')
                    ->required(),
                TextInput::make('name')
                    ->required(),
                TextInput::make('domain'),
                TextInput::make('currency')
                    ->required()
                    ->default('SEK'),
                TextInput::make('locale')
                    ->required()
                    ->default('sv'),
                Toggle::make('is_active')
                    ->required(),
                Textarea::make('settings')
                    ->columnSpanFull(),
            ]);
    }
}
