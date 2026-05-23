<?php

namespace App\Filament\Pages;

use App\Models\Setting;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Concerns\InteractsWithSchemas;
use Filament\Schemas\Contracts\HasSchemas;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;

class Settings extends Page implements HasSchemas
{
    use InteractsWithSchemas;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedCog6Tooth;

    protected static ?int $navigationSort = 90;

    protected string $view = 'filament.pages.settings';

    public ?array $data = [];

    public function mount(): void
    {
        $this->form->fill([
            'shop_name' => Setting::get('shop.name', config('app.name')),
            'shop_currency' => Setting::get('shop.currency', 'SEK'),
            'shop_locale' => Setting::get('shop.locale', 'sv'),
            'shop_admin_email' => Setting::get('shop.admin_email', ''),
            'flat_rate_price' => Setting::get('shipping.flat_rate.price', '49'),
            'flat_rate_free_threshold' => Setting::get('shipping.flat_rate.free_threshold', '0'),
            'flat_rate_vat_rate' => Setting::get('shipping.flat_rate.vat_rate', '25'),
            'flat_rate_lead_time' => Setting::get('shipping.flat_rate.lead_time', '2–5'),
            'pickup_address' => Setting::get('shipping.pickup.address', ''),
            'bank_transfer_bg' => Setting::get('payment.bank_transfer.bg', ''),
        ]);
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->statePath('data')
            ->components([
                Section::make('Shop')
                    ->columns(2)
                    ->schema([
                        TextInput::make('shop_name')->label('Shop name')->required(),
                        TextInput::make('shop_admin_email')
                            ->label('Admin email')
                            ->email()
                            ->helperText('Receives a copy of every order. Falls back to the first admin user.'),
                        Select::make('shop_currency')
                            ->label('Currency')
                            ->options(array_combine(
                                ['SEK', 'EUR', 'USD', 'NOK', 'DKK'],
                                ['SEK', 'EUR', 'USD', 'NOK', 'DKK']
                            ))
                            ->required(),
                        Select::make('shop_locale')
                            ->label('Language')
                            ->options(['sv' => 'Svenska', 'en' => 'English'])
                            ->required(),
                    ]),

                Section::make('Flat-rate shipping')
                    ->columns(4)
                    ->schema([
                        TextInput::make('flat_rate_price')->label('Price (incl VAT)')->numeric()->step(0.01),
                        TextInput::make('flat_rate_vat_rate')->label('VAT %')->numeric()->step(0.01),
                        TextInput::make('flat_rate_free_threshold')->label('Free over (0 = never)')->numeric()->step(0.01),
                        TextInput::make('flat_rate_lead_time')->label('Lead time text'),
                    ]),

                Section::make('Local pickup')
                    ->schema([
                        TextInput::make('pickup_address')->label('Pickup address (shown to customer)')->columnSpanFull(),
                    ]),

                Section::make('Bank transfer')
                    ->schema([
                        TextInput::make('bank_transfer_bg')->label('Bankgiro / account number')->columnSpanFull(),
                    ]),
            ]);
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('save')
                ->label('Save')
                ->keyBindings(['mod+s'])
                ->action(function () {
                    $data = $this->form->getState();

                    Setting::many([
                        'shop.name' => $data['shop_name'],
                        'shop.admin_email' => $data['shop_admin_email'] ?? '',
                        'shop.currency' => $data['shop_currency'],
                        'shop.locale' => $data['shop_locale'],
                        'shipping.flat_rate.price' => $data['flat_rate_price'],
                        'shipping.flat_rate.free_threshold' => $data['flat_rate_free_threshold'],
                        'shipping.flat_rate.vat_rate' => $data['flat_rate_vat_rate'],
                        'shipping.flat_rate.lead_time' => $data['flat_rate_lead_time'],
                        'shipping.pickup.address' => $data['pickup_address'],
                        'payment.bank_transfer.bg' => $data['bank_transfer_bg'],
                    ]);

                    Notification::make()
                        ->title('Settings saved')
                        ->success()
                        ->send();
                }),
        ];
    }
}
