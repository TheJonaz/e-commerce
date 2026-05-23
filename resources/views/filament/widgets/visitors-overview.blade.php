<x-filament-widgets::widget>
    <x-filament::section>
        <x-slot name="heading">Besöksstatistik (senaste 30 dagarna)</x-slot>

        @php
            $fmt = fn ($n) => number_format($n, 0, ',', ' ');
        @endphp

        {{-- 3 stat cards in Filament's native look --}}
        <div class="grid grid-cols-1 sm:grid-cols-3 gap-4 mb-6">
            <div class="fi-wi-stats-overview-stat rounded-xl bg-white dark:bg-white/5 shadow-sm ring-1 ring-gray-950/5 dark:ring-white/10 p-6">
                <div class="fi-wi-stats-overview-stat-content">
                    <div class="fi-wi-stats-overview-stat-label-ctn flex items-center gap-2">
                        <x-filament::icon icon="heroicon-m-chart-bar" class="h-4 w-4 text-gray-400" />
                        <span class="fi-wi-stats-overview-stat-label text-sm font-medium text-gray-500 dark:text-gray-400">Totala besök</span>
                    </div>
                    <div class="fi-wi-stats-overview-stat-value text-3xl font-semibold tabular-nums mt-2">{{ $fmt($total) }}</div>
                    <div class="fi-wi-stats-overview-stat-description text-sm text-gray-500 dark:text-gray-400 mt-1">alla sidvisningar</div>
                </div>
            </div>

            <div class="fi-wi-stats-overview-stat rounded-xl bg-white dark:bg-white/5 shadow-sm ring-1 ring-gray-950/5 dark:ring-white/10 p-6">
                <div class="fi-wi-stats-overview-stat-content">
                    <div class="fi-wi-stats-overview-stat-label-ctn flex items-center gap-2">
                        <x-filament::icon icon="heroicon-m-users" class="h-4 w-4 text-gray-400" />
                        <span class="fi-wi-stats-overview-stat-label text-sm font-medium text-gray-500 dark:text-gray-400">Unika besökare</span>
                    </div>
                    <div class="fi-wi-stats-overview-stat-value text-3xl font-semibold tabular-nums mt-2 text-success-600 dark:text-success-400">{{ $fmt($unique) }}</div>
                    <div class="fi-wi-stats-overview-stat-description text-sm text-gray-500 dark:text-gray-400 mt-1">{{ $uniquePct }} % av träffarna</div>
                </div>
            </div>

            <div class="fi-wi-stats-overview-stat rounded-xl bg-white dark:bg-white/5 shadow-sm ring-1 ring-gray-950/5 dark:ring-white/10 p-6">
                <div class="fi-wi-stats-overview-stat-content">
                    <div class="fi-wi-stats-overview-stat-label-ctn flex items-center gap-2">
                        <x-filament::icon icon="heroicon-m-clock" class="h-4 w-4 text-gray-400" />
                        <span class="fi-wi-stats-overview-stat-label text-sm font-medium text-gray-500 dark:text-gray-400">Idag</span>
                    </div>
                    <div class="fi-wi-stats-overview-stat-value text-3xl font-semibold tabular-nums mt-2 text-warning-600 dark:text-warning-400">{{ $fmt($today) }}</div>
                    <div class="fi-wi-stats-overview-stat-description text-sm text-gray-500 dark:text-gray-400 mt-1">{{ $today > 0 ? 'sidvisningar idag' : 'inga besök idag' }}</div>
                </div>
            </div>
        </div>

        {{-- Chart (2/3) + Countries (1/3) --}}
        <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
            <div class="md:col-span-2">
                <div class="text-xs uppercase tracking-wide font-medium text-gray-500 dark:text-gray-400 mb-3">Besök per dag</div>
                <div
                    x-load
                    x-load-src="{{ \Filament\Support\Facades\FilamentAsset::getAlpineComponentSrc('chart', 'filament/widgets') }}"
                    x-data="chart({
                        cachedData: @js($chartData),
                        options: @js($chartOptions),
                        type: 'bar',
                    })"
                    class="fi-color-primary relative"
                    style="height: 240px"
                >
                    <canvas x-ref="canvas" style="width: 100%; height: 100%"></canvas>
                    <span x-ref="backgroundColorElement" class="fi-wi-chart-bg-color"></span>
                    <span x-ref="borderColorElement" class="fi-wi-chart-border-color"></span>
                </div>
            </div>

            <div>
                <div class="text-xs uppercase tracking-wide font-medium text-gray-500 dark:text-gray-400 mb-3">Top 10 länder</div>
                @if (empty($countries))
                    <p class="text-sm text-gray-500 dark:text-gray-400">Ingen besöksdata än.</p>
                @else
                    <div class="space-y-2">
                        @foreach ($countries as $c)
                            <div class="flex items-center gap-2 text-sm">
                                <span class="text-base leading-none w-5">{{ $c['flag'] }}</span>
                                <span class="flex-1 truncate">{{ $c['name'] }}</span>
                                <span class="w-14 h-1.5 bg-gray-100 dark:bg-gray-800 rounded overflow-hidden">
                                    <span class="block h-full bg-primary-500" style="width: {{ $c['pct'] }}%;"></span>
                                </span>
                                <span class="w-12 text-right tabular-nums text-gray-600 dark:text-gray-300">{{ $fmt($c['visits']) }}</span>
                            </div>
                        @endforeach
                    </div>
                @endif
            </div>
        </div>
    </x-filament::section>
</x-filament-widgets::widget>
