<?php

namespace App\Filament\Resources\Products\Schemas;

use App\Models\Product;
use App\Support\Vat;
use Filament\Forms\Components\Actions;
use Filament\Forms\Components\Actions\Action;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\KeyValue;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Illuminate\Support\Str;
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

            Section::make('Images')
                ->schema([
                    Repeater::make('images')
                        ->relationship()
                        ->orderColumn('position')
                        ->reorderable()
                        ->collapsed()
                        ->itemLabel(fn (array $state): ?string => $state['alt']['sv'] ?? $state['path'] ?? 'Bild')
                        ->columnSpanFull()
                        ->schema([
                            FileUpload::make('path')
                                ->label('Bild')
                                ->image()
                                ->imageEditor()
                                ->disk('shop')
                                ->directory('products')
                                ->maxSize(4096)
                                ->required()
                                ->columnSpanFull(),
                            KeyValue::make('alt')
                                ->keyLabel('Locale')
                                ->valueLabel('Alt text (för skärmläsare/SEO)')
                                ->default(['sv' => '', 'en' => ''])
                                ->columnSpanFull(),
                        ]),
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
                        ->helperText(fn () => (bool) setting('shop.prices_include_vat', '1')
                            ? 'Pris inkl. moms (det kunden betalar)'
                            : 'Pris exkl. moms (moms räknas på vid checkout)'),
                    Select::make('vat_rate')
                        ->options(array_combine(
                            array_map(fn ($r) => (string) number_format($r, 2, '.', ''), [Vat::RATE_STANDARD, Vat::RATE_REDUCED, Vat::RATE_LOW, Vat::RATE_NONE]),
                            ['25 %', '12 %', '6 %', '0 %']
                        ))
                        ->default((string) number_format(Vat::RATE_STANDARD, 2, '.', ''))
                        ->required(),
                    TextInput::make('stock')->numeric()->minValue(0),
                    TextInput::make('weight_grams')
                        ->label('Vikt (g)')
                        ->numeric()->minValue(0)
                        ->helperText('Används av viktbaserade fraktmoduler (t.ex. PostNord).'),
                ]),

            Section::make('SEO & GEO')
                ->description('Söktitel och beskrivning som visas i Google och delas på sociala medier. Lämna tomt för auto-genererade värden från produktens namn/beskrivning.')
                ->columns(2)
                ->schema([
                    Actions::make([
                        Action::make('autofillSeo')
                            ->label('Auto-fyll från produktdata')
                            ->icon('heroicon-m-sparkles')
                            ->color('primary')
                            ->action(function (Set $set, Get $get) {
                                $names = (array) ($get('name') ?? []);
                                $shorts = (array) ($get('short_description') ?? []);
                                $descs = (array) ($get('description') ?? []);
                                $name = $names['sv'] ?? array_values($names)[0] ?? '';
                                $short = $shorts['sv'] ?? array_values($shorts)[0] ?? '';
                                $desc = $descs['sv'] ?? array_values($descs)[0] ?? '';
                                $shopName = setting('shop.name', config('app.name'));

                                $title = $name ? ($name . ' — ' . $shopName) : $shopName;
                                $description = $short ?: Str::limit(strip_tags($desc), 155);

                                $set('meta_title', $title);
                                $set('meta_description', $description);
                                $set('brand', setting('shop.name', config('app.name')));
                            }),
                    ])->columnSpanFull(),

                    TextInput::make('meta_title')
                        ->label('SEO-titel')
                        ->maxLength(60)
                        ->helperText('Max ~60 tecken. Visas som sidans titel i Google.')
                        ->columnSpanFull(),
                    Textarea::make('meta_description')
                        ->label('SEO-beskrivning')
                        ->maxLength(160)
                        ->rows(2)
                        ->helperText('Max ~160 tecken. Visas under titeln i sökresultat och vid delning.')
                        ->columnSpanFull(),
                    TextInput::make('brand')
                        ->label('Varumärke')
                        ->helperText('Används i Schema.org Product (Google Shopping, AI-assistenter).'),
                    TextInput::make('gtin')
                        ->label('GTIN / EAN / UPC')
                        ->helperText('Streckkod. Förbättrar produktens synlighet i Google Shopping.'),
                ]),

            Section::make('Variants')
                ->description('Lägg till varianter (t.ex. storlek, färg). När produkten har minst en aktiv variant måste kunden välja en innan add-to-cart.')
                ->schema([
                    Repeater::make('variants')
                        ->relationship()
                        ->orderColumn('position')
                        ->reorderable()
                        ->collapsed()
                        ->columnSpanFull()
                        ->itemLabel(fn (array $state): ?string => trim(($state['sku'] ?? '') . ' ' . collect($state['options'] ?? [])->filter()->implode(' / ')))
                        ->columns(3)
                        ->schema([
                            TextInput::make('sku')->label('SKU'),
                            TextInput::make('price')->required()->numeric()->step(0.01)->suffix(setting('shop.currency', 'SEK')),
                            TextInput::make('stock')->numeric()->minValue(0),
                            TextInput::make('weight_grams')->label('Vikt (g)')->numeric()->minValue(0),
                            Toggle::make('is_active')->default(true),
                            KeyValue::make('options')
                                ->keyLabel('Attribut')
                                ->valueLabel('Värde')
                                ->default(['size' => '', 'color' => ''])
                                ->required()
                                ->columnSpanFull(),
                        ]),
                ]),
        ]);
    }
}
