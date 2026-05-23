<?php

namespace App\Filament\Widgets;

use App\Models\Visit;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class VisitorStatsWidget extends StatsOverviewWidget
{
    protected static ?int $sort = 1;

    protected function getStats(): array
    {
        $since = now()->subDays(30);

        $total = Visit::where('visited_at', '>=', $since)->count();
        $unique = Visit::where('visited_at', '>=', $since)
            ->distinct('session_id')->count('session_id');
        $today = Visit::whereDate('visited_at', today())->count();

        // 7-day chart for the stats card sparkline
        $byDay = Visit::selectRaw("DATE(visited_at) as d, COUNT(*) as c")
            ->where('visited_at', '>=', now()->subDays(7)->startOfDay())
            ->groupBy('d')->orderBy('d')->pluck('c')->all();

        return [
            Stat::make('Totala besök (30 d)', number_format($total, 0, ',', ' '))
                ->description('alla sidvisningar')
                ->descriptionIcon('heroicon-m-chart-bar')
                ->color('primary')
                ->chart($byDay ?: [0]),

            Stat::make('Unika besökare (30 d)', number_format($unique, 0, ',', ' '))
                ->description($total > 0 ? round($unique / max($total, 1) * 100) . ' % av träffarna' : '—')
                ->descriptionIcon('heroicon-m-users')
                ->color('success'),

            Stat::make('Idag', number_format($today, 0, ',', ' '))
                ->description($today > 0 ? 'sidvisningar idag' : 'inga besök idag')
                ->descriptionIcon('heroicon-m-clock')
                ->color('warning'),
        ];
    }
}
