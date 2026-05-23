<x-filament-widgets::widget>
    <x-filament::section>
        <x-slot name="heading">Besöksstatistik (senaste 30 dagarna)</x-slot>

        @php
            $fmt = fn ($n) => number_format($n, 0, ',', ' ');
        @endphp

        {{-- Top row: 3 stats --}}
        <div class="grid grid-cols-3 gap-4 mb-5">
            <div>
                <div class="text-xs uppercase tracking-wide text-gray-500 dark:text-gray-400">Totala besök</div>
                <div class="text-2xl font-semibold tabular-nums">{{ $fmt($total) }}</div>
            </div>
            <div>
                <div class="text-xs uppercase tracking-wide text-gray-500 dark:text-gray-400">Unika besökare</div>
                <div class="text-2xl font-semibold tabular-nums">{{ $fmt($unique) }}<span class="text-sm font-normal text-gray-400 ml-1">({{ $uniquePct }} %)</span></div>
            </div>
            <div>
                <div class="text-xs uppercase tracking-wide text-gray-500 dark:text-gray-400">Idag</div>
                <div class="text-2xl font-semibold tabular-nums">{{ $fmt($today) }}</div>
            </div>
        </div>

        {{-- Bottom row: chart + countries side by side --}}
        <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
            {{-- Chart, 2/3 --}}
            <div class="md:col-span-2">
                <div class="text-xs uppercase tracking-wide text-gray-500 dark:text-gray-400 mb-2">Besök per dag</div>
                <div class="flex items-end gap-[2px] h-40 border-b border-gray-200 dark:border-gray-700" role="img" aria-label="Stapeldiagram över besök per dag">
                    @foreach ($series as $d)
                        @php $h = round($d['count'] / $maxCount * 100); @endphp
                        <div class="flex-1 group relative" style="height: 100%;">
                            <div class="absolute bottom-0 left-0 right-0 bg-primary-500 hover:bg-primary-400 rounded-t transition-all" style="height: {{ $h }}%;" title="{{ $d['label'] }}: {{ $fmt($d['count']) }} besök"></div>
                        </div>
                    @endforeach
                </div>
                <div class="flex justify-between text-[10px] text-gray-400 mt-1 tabular-nums">
                    <span>{{ $series[0]['label'] }}</span>
                    <span>{{ $series[14]['label'] }}</span>
                    <span>{{ $series[29]['label'] }}</span>
                </div>
            </div>

            {{-- Top countries, 1/3 --}}
            <div>
                <div class="text-xs uppercase tracking-wide text-gray-500 dark:text-gray-400 mb-2">Top 10 länder</div>
                @if (empty($countries))
                    <p class="text-sm text-gray-500 dark:text-gray-400">Ingen besöksdata än.</p>
                @else
                    <div class="space-y-1">
                        @foreach ($countries as $c)
                            <div class="flex items-center gap-2 text-sm">
                                <span class="text-base leading-none w-5">{{ $c['flag'] }}</span>
                                <span class="flex-1 truncate">{{ $c['name'] }}</span>
                                <span class="w-16 h-1.5 bg-gray-100 dark:bg-gray-800 rounded overflow-hidden">
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
