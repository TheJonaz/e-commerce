<?php

namespace App\Filament\Pages;

use App\Models\Setting;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
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
            'shop_prices_include_vat' => (bool) Setting::get('shop.prices_include_vat', '1'),
            'flat_rate_price' => Setting::get('shipping.flat_rate.price', '49'),
            'flat_rate_free_threshold' => Setting::get('shipping.flat_rate.free_threshold', '0'),
            'flat_rate_vat_rate' => Setting::get('shipping.flat_rate.vat_rate', '25'),
            'flat_rate_lead_time' => Setting::get('shipping.flat_rate.lead_time', '2–5'),
            'pickup_address' => Setting::get('shipping.pickup.address', ''),
            'bank_transfer_bg' => Setting::get('payment.bank_transfer.bg', ''),
            'stripe_publishable_key' => Setting::get('payment.stripe.publishable_key', ''),
            'stripe_secret_key' => Setting::get('payment.stripe.secret_key', ''),
            'stripe_webhook_secret' => Setting::get('payment.stripe.webhook_secret', ''),
            'stripe_test_mode' => (bool) Setting::get('payment.stripe.test_mode', '1'),
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
                        Toggle::make('shop_prices_include_vat')
                            ->label('Priser inkluderar moms (standard för privatkunder)')
                            ->helperText('Det priset du anger för en produkt är samma pris kunden ser och betalar i kassan. Företagskonton (B2B) kommer i framtiden kunna växla till exkl. moms-vy.')
                            ->default(true)
                            ->columnSpanFull(),
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

                Section::make('Stripe')
                    ->description('Lämna tomt för att stänga av Stripe i kassan. Skaffa nycklar på dashboard.stripe.com → Developers → API keys.')
                    ->columns(2)
                    ->schema([
                        Toggle::make('stripe_test_mode')
                            ->label('Testläge')
                            ->helperText('Använd test-nycklar (pk_test_… / sk_test_…) tills du är redo att ta riktig betalning.')
                            ->columnSpanFull(),
                        TextInput::make('stripe_publishable_key')
                            ->label('Publishable key')
                            ->placeholder('pk_test_… eller pk_live_…'),
                        TextInput::make('stripe_secret_key')
                            ->label('Secret key')
                            ->password()->revealable()
                            ->placeholder('sk_test_… eller sk_live_…'),
                        TextInput::make('stripe_webhook_secret')
                            ->label('Webhook signing secret')
                            ->password()->revealable()
                            ->helperText('Skapa en webhook i Stripe-dashboard mot /webhooks/stripe → kopiera "whsec_..." hit.')
                            ->columnSpanFull(),
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
                        'shop.prices_include_vat' => $data['shop_prices_include_vat'] ? '1' : '0',
                        'shipping.flat_rate.price' => $data['flat_rate_price'],
                        'shipping.flat_rate.free_threshold' => $data['flat_rate_free_threshold'],
                        'shipping.flat_rate.vat_rate' => $data['flat_rate_vat_rate'],
                        'shipping.flat_rate.lead_time' => $data['flat_rate_lead_time'],
                        'shipping.pickup.address' => $data['pickup_address'],
                        'payment.bank_transfer.bg' => $data['bank_transfer_bg'],
                        'payment.stripe.publishable_key' => $data['stripe_publishable_key'] ?? '',
                        'payment.stripe.secret_key' => $data['stripe_secret_key'] ?? '',
                        'payment.stripe.webhook_secret' => $data['stripe_webhook_secret'] ?? '',
                        'payment.stripe.test_mode' => $data['stripe_test_mode'] ? '1' : '0',
                    ]);

                    Notification::make()
                        ->title('Settings saved')
                        ->success()
                        ->send();
                }),
        ];
    }
}
