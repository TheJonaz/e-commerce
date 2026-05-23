<x-filament-widgets::widget>
    <x-filament::section>
        <x-slot name="heading">Besöksstatistik (senaste 30 dagarna)</x-slot>

        @php
            $fmt = fn ($n) => number_format($n, 0, ',', ' ');
            $maxDay = max(1, max(array_map(fn ($v) => (int) $v, $chartData['datasets'][0]['data'])));
        @endphp

        <style>
            .vs { font-size: 0.875rem; }
            .vs-stats { display: grid; grid-template-columns: repeat(3, 1fr); gap: 0.65rem; margin-bottom: 0.9rem; }
            .vs-stat { background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 0.5rem; padding: 0.6rem 0.85rem; }
            .dark .vs-stat { background: rgba(255,255,255,0.03); border-color: rgba(255,255,255,0.08); }
            .vs-stat-label { font-size: 0.68rem; font-weight: 500; color: #64748b; text-transform: uppercase; letter-spacing: 0.05em; }
            .vs-stat-value { font-size: 1.35rem; font-weight: 600; font-variant-numeric: tabular-nums; margin-top: 0.1rem; line-height: 1.1; color: inherit; }
            .vs-stat-value .pct { font-size: 0.75rem; font-weight: 400; color: #94a3b8; }
            .vs-stat-desc { font-size: 0.68rem; color: #64748b; margin-top: 0.1rem; }
            .vs-row { display: grid; grid-template-columns: minmax(0, 0.9fr) minmax(0, 1.6fr); gap: 1.1rem; align-items: start; }
            @media (max-width: 768px) { .vs-stats { grid-template-columns: 1fr; } .vs-row { grid-template-columns: 1fr; } }
            .vs-sub { font-size: 0.66rem; font-weight: 600; text-transform: uppercase; letter-spacing: 0.06em; color: #64748b; margin-bottom: 0.45rem; }
            .vs-country { display: grid; grid-template-columns: 1.2rem 1fr 2.5rem 2.5rem; gap: 0.4rem; align-items: center; font-size: 0.78rem; padding: 0.12rem 0; line-height: 1.2; }
            .vs-country-name { overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
            .vs-country-bar { height: 0.3rem; background: #e2e8f0; border-radius: 999px; overflow: hidden; }
            .dark .vs-country-bar { background: rgba(255,255,255,0.06); }
            .vs-country-bar > span { display: block; height: 100%; background: #6366f1; border-radius: 999px; }
            .vs-count { text-align: right; font-variant-numeric: tabular-nums; color: #475569; }
            .dark .vs-count { color: #cbd5e1; }
            .vs-chart { display: flex; align-items: flex-end; gap: 2px; height: 120px; border-bottom: 1px solid #e2e8f0; }
            .dark .vs-chart { border-bottom-color: rgba(255,255,255,0.08); }
            .vs-bar { flex: 1; min-width: 0; height: 100%; position: relative; }
            .vs-bar > span { position: absolute; bottom: 0; left: 0; right: 0; background: #6366f1; border-radius: 2px 2px 0 0; }
            .vs-bar:hover > span { background: #818cf8; }
            .vs-axis { display: flex; justify-content: space-between; font-size: 0.6rem; color: #94a3b8; margin-top: 0.25rem; font-variant-numeric: tabular-nums; }
        </style>

        <div class="vs">
            <div class="vs-stats">
                <div class="vs-stat">
                    <div class="vs-stat-label">Totala besök</div>
                    <div class="vs-stat-value">{{ $fmt($total) }}</div>
                    <div class="vs-stat-desc">alla sidvisningar</div>
                </div>
                <div class="vs-stat">
                    <div class="vs-stat-label">Unika besökare</div>
                    <div class="vs-stat-value">{{ $fmt($unique) }} <span class="pct">({{ $uniquePct }} %)</span></div>
                    <div class="vs-stat-desc">distinkta sessioner</div>
                </div>
                <div class="vs-stat">
                    <div class="vs-stat-label">Idag</div>
                    <div class="vs-stat-value">{{ $fmt($today) }}</div>
                    <div class="vs-stat-desc">{{ $today > 0 ? 'sidvisningar idag' : 'inga besök idag' }}</div>
                </div>
            </div>

            <div class="vs-row">
                <div>
                    <div class="vs-sub">Top 10 länder</div>
                    @if (empty($countries))
                        <p style="font-size:0.8rem;color:#64748b">Ingen besöksdata än.</p>
                    @else
                        @foreach ($countries as $c)
                            <div class="vs-country">
                                <span style="font-size:0.95rem;line-height:1">{{ $c['flag'] }}</span>
                                <span class="vs-country-name">{{ $c['name'] }}</span>
                                <span class="vs-country-bar"><span style="width: {{ $c['pct'] }}%;"></span></span>
                                <span class="vs-count">{{ $fmt($c['visits']) }}</span>
                            </div>
                        @endforeach
                    @endif
                </div>

                <div>
                    <div class="vs-sub">Besök per dag</div>
                    <div class="vs-chart">
                        @foreach ($chartData['datasets'][0]['data'] as $i => $v)
                            @php $h = round($v / $maxDay * 100); @endphp
                            <div class="vs-bar" title="{{ $chartData['labels'][$i] }}: {{ $fmt($v) }} besök">
                                <span style="height: {{ $h }}%"></span>
                            </div>
                        @endforeach
                    </div>
                    <div class="vs-axis">
                        <span>{{ $chartData['labels'][0] }}</span>
                        <span>{{ $chartData['labels'][14] }}</span>
                        <span>{{ $chartData['labels'][29] }}</span>
                    </div>
                </div>
            </div>
        </div>
    </x-filament::section>
</x-filament-widgets::widget>
