<?php

namespace App\Filament\Widgets;

use App\Models\Visit;
use App\Support\GeoIp;
use Filament\Widgets\Widget;
use Illuminate\Support\Carbon;

class VisitorsOverviewWidget extends Widget
{
    protected string $view = 'filament.widgets.visitors-overview';

    protected static ?int $sort = 1;

    protected int|string|array $columnSpan = 'full';

    public function getViewData(): array
    {
        $since = now()->subDays(30);

        $total = (int) Visit::where('visited_at', '>=', $since)->count();
        $unique = (int) Visit::where('visited_at', '>=', $since)->distinct('session_id')->count('session_id');
        $today = (int) Visit::whereDate('visited_at', today())->count();

        $rows = Visit::selectRaw("DATE(visited_at) as d, COUNT(*) as c")
            ->where('visited_at', '>=', Carbon::now()->subDays(29)->startOfDay())
            ->groupBy('d')->orderBy('d')
            ->pluck('c', 'd')->all();

        $labels = [];
        $data = [];
        for ($i = 29; $i >= 0; $i--) {
            $day = Carbon::now()->subDays($i)->format('Y-m-d');
            $labels[] = Carbon::parse($day)->isoFormat('D MMM');
            $data[] = (int) ($rows[$day] ?? 0);
        }

        $countryRows = Visit::selectRaw('country, COUNT(*) as visits')
            ->where('visited_at', '>=', $since)
            ->groupBy('country')
            ->orderByDesc('visits')
            ->limit(10)
            ->get();
        $maxCountry = max(1, (int) ($countryRows[0]->visits ?? 1));

        $countries = $countryRows->map(fn ($r) => [
            'code' => $r->country,
            'name' => GeoIp::name($r->country, app()->getLocale()),
            'flag' => GeoIp::flag($r->country),
            'visits' => (int) $r->visits,
            'pct' => round($r->visits / $maxCountry * 100),
        ])->all();

        $chartData = [
            'datasets' => [[
                'label' => 'Besök',
                'data' => $data,
                'backgroundColor' => 'rgba(99, 102, 241, 0.75)',
                'borderColor' => 'rgb(79, 70, 229)',
                'borderRadius' => 4,
            ]],
            'labels' => $labels,
        ];

        $chartOptions = [
            'plugins' => ['legend' => ['display' => false]],
            'scales' => [
                'y' => ['beginAtZero' => true, 'ticks' => ['precision' => 0]],
                'x' => ['ticks' => ['maxTicksLimit' => 8, 'autoSkip' => true]],
            ],
            'maintainAspectRatio' => false,
        ];

        return [
            'total' => $total,
            'unique' => $unique,
            'today' => $today,
            'uniquePct' => $total > 0 ? round($unique / $total * 100) : 0,
            'chartData' => $chartData,
            'chartOptions' => $chartOptions,
            'countries' => $countries,
        ];
    }
}
