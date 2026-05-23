<?php

namespace App\Filament\Widgets;

use App\Models\Visit;
use App\Support\GeoIp;
use Filament\Widgets\Widget;

class TopCountriesWidget extends Widget
{
    protected string $view = 'filament.widgets.top-countries';

    protected static ?int $sort = 3;

    protected int|string|array $columnSpan = ['default' => 'full', 'md' => 1];

    public function getViewData(): array
    {
        $since = now()->subDays(30);

        $rows = Visit::selectRaw('country, COUNT(*) as visits')
            ->where('visited_at', '>=', $since)
            ->groupBy('country')
            ->orderByDesc('visits')
            ->limit(10)
            ->get();

        $maxVisits = max(1, (int) ($rows[0]->visits ?? 1));

        $countries = $rows->map(fn ($r) => [
            'code' => $r->country,
            'name' => GeoIp::name($r->country, app()->getLocale()),
            'flag' => GeoIp::flag($r->country),
            'visits' => (int) $r->visits,
            'pct' => round($r->visits / $maxVisits * 100),
        ])->all();

        return ['countries' => $countries];
    }
}
