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
            'klarna_username' => Setting::get('payment.klarna.username', ''),
            'klarna_password' => Setting::get('payment.klarna.password', ''),
            'klarna_test_mode' => (bool) Setting::get('payment.klarna.test_mode', '1'),
            'swish_payee_alias' => Setting::get('payment.swish.payee_alias', ''),
            'swish_cert_path' => Setting::get('payment.swish.cert_path', ''),
            'swish_cert_password' => Setting::get('payment.swish.cert_password', ''),
            'swish_test_mode' => (bool) Setting::get('payment.swish.test_mode', '1'),
            'postnord_tier1_max_grams' => Setting::get('shipping.postnord.tier1_max_grams', '2000'),
            'postnord_tier1_price' => Setting::get('shipping.postnord.tier1_price', '79'),
            'postnord_tier2_max_grams' => Setting::get('shipping.postnord.tier2_max_grams', '5000'),
            'postnord_tier2_price' => Setting::get('shipping.postnord.tier2_price', '109'),
            'postnord_tier3_max_grams' => Setting::get('shipping.postnord.tier3_max_grams', '10000'),
            'postnord_tier3_price' => Setting::get('shipping.postnord.tier3_price', '149'),
            'postnord_tier4_max_grams' => Setting::get('shipping.postnord.tier4_max_grams', '20000'),
            'postnord_tier4_price' => Setting::get('shipping.postnord.tier4_price', '229'),
            'postnord_overflow_price' => Setting::get('shipping.postnord.overflow_price', '349'),
            'postnord_free_threshold' => Setting::get('shipping.postnord.free_threshold', '0'),
            'postnord_intl_multiplier' => Setting::get('shipping.postnord.intl_multiplier', '2.0'),
            'postnord_lead_time' => Setting::get('shipping.postnord.lead_time', '2–4'),
            'seo_default_description' => Setting::get('seo.default_description', ''),
            'seo_default_og_image' => Setting::get('seo.default_og_image', ''),
            'seo_google_verification' => Setting::get('seo.google_verification', ''),
            'seo_ga_id' => Setting::get('seo.ga_id', ''),
            'cookie_banner_enabled' => (bool) Setting::get('cookie.banner_enabled', '1'),
            'cookie_text' => Setting::get('cookie.text', 'Vi använder cookies för att förbättra din upplevelse och förstå hur sajten används. Du kan välja att acceptera eller avvisa analys-cookies.'),
            'cookie_policy_url' => Setting::get('cookie.policy_url', ''),
            'reviews_enabled' => (bool) Setting::get('reviews.enabled', '1'),
            'reviews_auto_publish' => (bool) Setting::get('reviews.auto_publish', '1'),
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

                Section::make('PostNord MyPack')
                    ->description('Viktbaserad prissättning. Default-värden följer PostNord MyPack Collect list-priser inkl. moms (anpassa till ditt företagsavtal).')
                    ->columns(4)
                    ->schema([
                        TextInput::make('postnord_tier1_max_grams')->label('Tier 1 ≤ g')->numeric(),
                        TextInput::make('postnord_tier1_price')->label('Tier 1 pris')->numeric()->step(0.01),
                        TextInput::make('postnord_tier2_max_grams')->label('Tier 2 ≤ g')->numeric(),
                        TextInput::make('postnord_tier2_price')->label('Tier 2 pris')->numeric()->step(0.01),
                        TextInput::make('postnord_tier3_max_grams')->label('Tier 3 ≤ g')->numeric(),
                        TextInput::make('postnord_tier3_price')->label('Tier 3 pris')->numeric()->step(0.01),
                        TextInput::make('postnord_tier4_max_grams')->label('Tier 4 ≤ g')->numeric(),
                        TextInput::make('postnord_tier4_price')->label('Tier 4 pris')->numeric()->step(0.01),
                        TextInput::make('postnord_overflow_price')->label('Pris > tier 4')->numeric()->step(0.01),
                        TextInput::make('postnord_free_threshold')->label('Fri över (0 = aldrig)')->numeric()->step(0.01),
                        TextInput::make('postnord_intl_multiplier')->label('Intl-multiplikator')->numeric()->step(0.1),
                        TextInput::make('postnord_lead_time')->label('Leveranstid')->placeholder('2–4'),
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

                Section::make('SEO & GEO')
                    ->description('Site-globala värden som påverkar Google, sociala medier och AI-assistenter.')
                    ->columns(2)
                    ->schema([
                        \Filament\Forms\Components\Textarea::make('seo_default_description')
                            ->label('Standard-beskrivning')
                            ->rows(2)
                            ->maxLength(320)
                            ->helperText('Används som meta-beskrivning på sidor utan egen text (hem, varukorg etc.).')
                            ->columnSpanFull(),
                        TextInput::make('seo_default_og_image')
                            ->label('Standard Open Graph-bild (URL)')
                            ->placeholder('https://example.com/og-image.jpg')
                            ->helperText('Visas vid delning på sociala medier när sidan saknar egen bild. Rekommenderad storlek: 1200×630 px.')
                            ->columnSpanFull(),
                        TextInput::make('seo_google_verification')
                            ->label('Google Search Console verification')
                            ->placeholder('innehållet från meta-taggen google-site-verification'),
                        TextInput::make('seo_ga_id')
                            ->label('Google Analytics ID')
                            ->placeholder('G-XXXXXXXXXX'),
                    ]),

                Section::make('Recensioner')
                    ->columns(2)
                    ->schema([
                        Toggle::make('reviews_enabled')
                            ->label('Tillåt recensioner på produktsidor')
                            ->default(true),
                        Toggle::make('reviews_auto_publish')
                            ->label('Publicera nya recensioner direkt')
                            ->helperText('Stäng av för att granska innan visning.')
                            ->default(true),
                    ]),

                Section::make('Cookies & samtycke')
                    ->description('GDPR-banner som visas tills besökaren godkänner eller avvisar. Google Analytics laddas bara när samtycke finns.')
                    ->columns(2)
                    ->schema([
                        Toggle::make('cookie_banner_enabled')
                            ->label('Visa cookie-banner')
                            ->default(true)
                            ->columnSpanFull(),
                        \Filament\Forms\Components\Textarea::make('cookie_text')
                            ->label('Bannertext')
                            ->rows(3)
                            ->columnSpanFull(),
                        TextInput::make('cookie_policy_url')
                            ->label('URL till cookie-policy (valfri)')
                            ->placeholder('/policy/cookies')
                            ->columnSpanFull(),
                    ]),

                Section::make('Bank transfer')
                    ->schema([
                        TextInput::make('bank_transfer_bg')->label('Bankgiro / account number')->columnSpanFull(),
                    ]),

                Section::make('Swish')
                    ->description('Kräver Swish Handel-avtal från din bank + klientcertifikat. För testläge använder du Swish MSS (Merchant Simulator System) med ett färdigt testcert från Swishs utvecklarsida.')
                    ->columns(2)
                    ->schema([
                        Toggle::make('swish_test_mode')
                            ->label('Testläge (MSS)')
                            ->columnSpanFull(),
                        TextInput::make('swish_payee_alias')
                            ->label('Swish-nummer')
                            ->placeholder('1234679304 (test) eller ditt riktiga')
                            ->helperText('Endast siffror. Testnummer i MSS: 1234679304.')
                            ->columnSpanFull(),
                        TextInput::make('swish_cert_path')
                            ->label('Sökväg till klientcert (.pem)')
                            ->placeholder('/var/www/secrets/swish.pem')
                            ->helperText('Filen ska innehålla både cert och private key i PEM-format.')
                            ->columnSpanFull(),
                        TextInput::make('swish_cert_password')
                            ->label('Cert-lösenord (om nödvändigt)')
                            ->password()->revealable()
                            ->columnSpanFull(),
                    ]),

                Section::make('Klarna')
                    ->description('Lämna tomt för att stänga av Klarna i kassan. API-credentials hittar du i Klarna Merchant Portal → Settings → API credentials.')
                    ->columns(2)
                    ->schema([
                        Toggle::make('klarna_test_mode')
                            ->label('Testläge (playground)')
                            ->helperText('Använd playground-credentials tills du är redo att ta riktig betalning.')
                            ->columnSpanFull(),
                        TextInput::make('klarna_username')
                            ->label('Username / UID')
                            ->placeholder('PK… (test) eller K… (live)'),
                        TextInput::make('klarna_password')
                            ->label('Password')
                            ->password()->revealable(),
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
                        'payment.klarna.username' => $data['klarna_username'] ?? '',
                        'payment.klarna.password' => $data['klarna_password'] ?? '',
                        'payment.klarna.test_mode' => $data['klarna_test_mode'] ? '1' : '0',
                        'payment.swish.payee_alias' => $data['swish_payee_alias'] ?? '',
                        'payment.swish.cert_path' => $data['swish_cert_path'] ?? '',
                        'payment.swish.cert_password' => $data['swish_cert_password'] ?? '',
                        'payment.swish.test_mode' => $data['swish_test_mode'] ? '1' : '0',
                        'shipping.postnord.tier1_max_grams' => $data['postnord_tier1_max_grams'] ?? '2000',
                        'shipping.postnord.tier1_price' => $data['postnord_tier1_price'] ?? '79',
                        'shipping.postnord.tier2_max_grams' => $data['postnord_tier2_max_grams'] ?? '5000',
                        'shipping.postnord.tier2_price' => $data['postnord_tier2_price'] ?? '109',
                        'shipping.postnord.tier3_max_grams' => $data['postnord_tier3_max_grams'] ?? '10000',
                        'shipping.postnord.tier3_price' => $data['postnord_tier3_price'] ?? '149',
                        'shipping.postnord.tier4_max_grams' => $data['postnord_tier4_max_grams'] ?? '20000',
                        'shipping.postnord.tier4_price' => $data['postnord_tier4_price'] ?? '229',
                        'shipping.postnord.overflow_price' => $data['postnord_overflow_price'] ?? '349',
                        'shipping.postnord.free_threshold' => $data['postnord_free_threshold'] ?? '0',
                        'shipping.postnord.intl_multiplier' => $data['postnord_intl_multiplier'] ?? '2.0',
                        'shipping.postnord.lead_time' => $data['postnord_lead_time'] ?? '2–4',
                        'seo.default_description' => $data['seo_default_description'] ?? '',
                        'seo.default_og_image' => $data['seo_default_og_image'] ?? '',
                        'seo.google_verification' => $data['seo_google_verification'] ?? '',
                        'seo.ga_id' => $data['seo_ga_id'] ?? '',
                        'cookie.banner_enabled' => $data['cookie_banner_enabled'] ? '1' : '0',
                        'cookie.text' => $data['cookie_text'] ?? '',
                        'cookie.policy_url' => $data['cookie_policy_url'] ?? '',
                        'reviews.enabled' => $data['reviews_enabled'] ? '1' : '0',
                        'reviews.auto_publish' => $data['reviews_auto_publish'] ? '1' : '0',
                    ]);

                    Notification::make()
                        ->title('Settings saved')
                        ->success()
                        ->send();
                }),
        ];
    }
}
