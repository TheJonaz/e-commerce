<x-filament-widgets::widget>
    <x-filament::section>
        <x-slot name="heading">Top 10 länder (30 d)</x-slot>

        @if (empty($countries))
            <p class="text-sm text-gray-500 dark:text-gray-400">Ingen besöksdata än.</p>
        @else
            <div class="space-y-2">
                @foreach ($countries as $c)
                    <div class="flex items-center gap-3">
                        <div class="w-7 text-lg leading-none">{{ $c['flag'] }}</div>
                        <div class="w-32 text-sm font-medium truncate">{{ $c['name'] }}</div>
                        <div class="flex-1 h-2 bg-gray-100 dark:bg-gray-800 rounded overflow-hidden">
                            <div class="h-full bg-primary-500" style="width: {{ $c['pct'] }}%;"></div>
                        </div>
                        <div class="w-20 text-right text-sm tabular-nums">{{ number_format($c['visits'], 0, ',', ' ') }}</div>
                    </div>
                @endforeach
            </div>
        @endif
    </x-filament::section>
</x-filament-widgets::widget>
