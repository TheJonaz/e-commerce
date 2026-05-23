<?php

namespace App\Filament\Widgets;

use App\Models\Visit;
use Filament\Widgets\ChartWidget;
use Illuminate\Support\Carbon;

class VisitorsChartWidget extends ChartWidget
{
    protected ?string $heading = 'Besök senaste 30 dagarna';

    protected static ?int $sort = 2;

    protected int|string|array $columnSpan = ['default' => 'full', 'md' => 1];

    protected ?string $maxHeight = '240px';

    protected function getData(): array
    {
        $start = Carbon::now()->subDays(29)->startOfDay();
        $end = Carbon::now()->endOfDay();

        $rows = Visit::selectRaw("DATE(visited_at) as d, COUNT(*) as c")
            ->whereBetween('visited_at', [$start, $end])
            ->groupBy('d')->orderBy('d')
            ->pluck('c', 'd')->all();

        $labels = [];
        $data = [];
        for ($i = 29; $i >= 0; $i--) {
            $day = Carbon::now()->subDays($i)->format('Y-m-d');
            $labels[] = Carbon::parse($day)->format('j M');
            $data[] = (int) ($rows[$day] ?? 0);
        }

        return [
            'datasets' => [
                [
                    'label' => 'Besök',
                    'data' => $data,
                    'backgroundColor' => 'rgba(99, 102, 241, 0.7)',
                    'borderColor' => 'rgb(79, 70, 229)',
                    'borderRadius' => 4,
                ],
            ],
            'labels' => $labels,
        ];
    }

    protected function getType(): string
    {
        return 'bar';
    }

    protected function getOptions(): array
    {
        return [
            'plugins' => ['legend' => ['display' => false]],
            'scales' => [
                'y' => ['beginAtZero' => true, 'ticks' => ['precision' => 0]],
            ],
        ];
    }
}
