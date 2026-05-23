<x-filament-widgets::widget>
    <x-panel panel-key="sales-overview" heading="Säljstatistik (senaste 30 dagarna)">

        @php
            $fmt = fn ($n) => number_format($n, 0, ',', ' ');
            $money = fn ($n) => App\Support\Money::format($n, $currency);
        @endphp

        <style>
            .ss { font-size: 0.875rem; }
            .ss-stats { display: grid; grid-template-columns: repeat(3, 1fr); gap: 0.65rem; margin-bottom: 0.9rem; }
            .ss-stat { background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 0.5rem; padding: 0.6rem 0.85rem; }
            .dark .ss-stat { background: rgba(255,255,255,0.03); border-color: rgba(255,255,255,0.08); }
            .ss-stat-label { font-size: 0.68rem; font-weight: 500; color: #64748b; text-transform: uppercase; letter-spacing: 0.05em; }
            .ss-stat-value { font-size: 1.35rem; font-weight: 600; font-variant-numeric: tabular-nums; margin-top: 0.1rem; line-height: 1.1; color: inherit; }
            .ss-stat-desc { font-size: 0.68rem; color: #64748b; margin-top: 0.1rem; }
            .ss-row { display: grid; grid-template-columns: 1fr 1fr; gap: 1.1rem; align-items: start; }
            @media (max-width: 768px) { .ss-stats { grid-template-columns: 1fr; } .ss-row { grid-template-columns: 1fr; } }
            .ss-sub { font-size: 0.66rem; font-weight: 600; text-transform: uppercase; letter-spacing: 0.06em; color: #64748b; margin-bottom: 0.45rem; }
            .ss-item { display: grid; grid-template-columns: 1fr 3rem 3.5rem; gap: 0.5rem; align-items: center; font-size: 0.78rem; padding: 0.18rem 0; line-height: 1.2; }
            .ss-item-name { overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
            .ss-item-bar { height: 0.3rem; background: #e2e8f0; border-radius: 999px; overflow: hidden; }
            .dark .ss-item-bar { background: rgba(255,255,255,0.06); }
            .ss-item-bar > span { display: block; height: 100%; background: #6366f1; border-radius: 999px; }
            .ss-item-val { text-align: right; font-variant-numeric: tabular-nums; color: #475569; }
            .dark .ss-item-val { color: #cbd5e1; }
        </style>

        <div class="ss">
            <div class="ss-stats">
                <div class="ss-stat">
                    <div class="ss-stat-label">Ordrar</div>
                    <div class="ss-stat-value">{{ $fmt($orders) }}</div>
                    <div class="ss-stat-desc">senaste 30 d</div>
                </div>
                <div class="ss-stat">
                    <div class="ss-stat-label">Omsättning</div>
                    <div class="ss-stat-value">{{ $money($revenue) }}</div>
                    <div class="ss-stat-desc">inkl. moms</div>
                </div>
                <div class="ss-stat">
                    <div class="ss-stat-label">Snittordervärde</div>
                    <div class="ss-stat-value">{{ $money($aov) }}</div>
                    <div class="ss-stat-desc">{{ $todayRevenue > 0 ? 'idag: ' . $money($todayRevenue) : 'idag: ingen försäljning' }}</div>
                </div>
            </div>

            <div class="ss-row">
                <div>
                    <div class="ss-sub">Mest sålda produkter</div>
                    @if (empty($mostSold))
                        <p style="font-size:0.8rem;color:#64748b">Inga ordrar än.</p>
                    @else
                        @foreach ($mostSold as $p)
                            <div class="ss-item">
                                <span class="ss-item-name" title="{{ $p['name'] }}">{{ $p['name'] }}</span>
                                <span class="ss-item-bar"><span style="width: {{ $p['pct'] }}%;"></span></span>
                                <span class="ss-item-val">{{ $fmt($p['units']) }} st</span>
                            </div>
                        @endforeach
                    @endif
                </div>

                <div>
                    <div class="ss-sub">Mest besökta produkter</div>
                    @if (empty($mostVisited))
                        <p style="font-size:0.8rem;color:#64748b">Ingen besöksdata än.</p>
                    @else
                        @foreach ($mostVisited as $p)
                            <div class="ss-item">
                                <span class="ss-item-name" title="{{ $p['name'] }}">{{ $p['name'] }}</span>
                                <span class="ss-item-bar"><span style="width: {{ $p['pct'] }}%;"></span></span>
                                <span class="ss-item-val">{{ $fmt($p['visits']) }}</span>
                            </div>
                        @endforeach
                    @endif
                </div>
            </div>
        </div>
    </x-panel>
</x-filament-widgets::widget>
