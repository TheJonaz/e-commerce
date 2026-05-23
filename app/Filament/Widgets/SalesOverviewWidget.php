<?php

namespace App\Filament\Widgets;

use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\Visit;
use App\Support\Money;
use Filament\Widgets\Widget;
use Illuminate\Support\Facades\DB;

class SalesOverviewWidget extends Widget
{
    protected string $view = 'filament.widgets.sales-overview';

    protected static ?int $sort = 2;

    protected int|string|array $columnSpan = 'full';

    public function getViewData(): array
    {
        $since = now()->subDays(30);
        $currency = setting('shop.currency', 'SEK');

        $orderCount = (int) Order::where('placed_at', '>=', $since)->count();
        $revenue = (float) Order::where('placed_at', '>=', $since)->sum('grand_total');
        $todayRevenue = (float) Order::whereDate('placed_at', today())->sum('grand_total');
        $aov = $orderCount > 0 ? $revenue / $orderCount : 0;

        // Most sold products by qty
        $topSold = OrderItem::selectRaw('product_id, name_snapshot, SUM(qty) as units, SUM(line_total_incl_vat) as revenue')
            ->whereNotNull('product_id')
            ->whereHas('order', fn ($q) => $q->where('placed_at', '>=', $since))
            ->groupBy('product_id', 'name_snapshot')
            ->orderByDesc('units')
            ->limit(10)
            ->get();
        $maxUnits = max(1, (int) ($topSold[0]->units ?? 1));

        $mostSold = $topSold->map(fn ($r) => [
            'name' => $r->name_snapshot,
            'units' => (int) $r->units,
            'revenue' => (float) $r->revenue,
            'pct' => round($r->units / $maxUnits * 100),
        ])->all();

        // Most visited products (extract slug from /products/<slug>)
        $visits = Visit::selectRaw("SUBSTRING(url, LOCATE('/products/', url) + 10) as raw_slug, COUNT(*) as visits")
            ->where('visited_at', '>=', $since)
            ->where('url', 'like', '%/products/%')
            ->groupBy('raw_slug')
            ->orderByDesc('visits')
            ->limit(20)
            ->get();

        // The raw_slug may contain a trailing query string — clean and dedupe
        $slugCounts = [];
        foreach ($visits as $v) {
            $slug = explode('?', (string) $v->raw_slug)[0];
            $slug = trim($slug, '/');
            if ($slug === '') continue;
            $slugCounts[$slug] = ($slugCounts[$slug] ?? 0) + (int) $v->visits;
        }
        arsort($slugCounts);
        $slugCounts = array_slice($slugCounts, 0, 10, true);

        $productNames = Product::whereIn('slug', array_keys($slugCounts))
            ->get()->keyBy('slug');

        $maxVisits = max(1, $slugCounts ? max($slugCounts) : 1);
        $mostVisited = [];
        foreach ($slugCounts as $slug => $count) {
            $product = $productNames->get($slug);
            $mostVisited[] = [
                'name' => $product?->localized('name') ?? $slug,
                'slug' => $slug,
                'visits' => $count,
                'pct' => round($count / $maxVisits * 100),
            ];
        }

        return [
            'orders' => $orderCount,
            'revenue' => $revenue,
            'todayRevenue' => $todayRevenue,
            'aov' => $aov,
            'currency' => $currency,
            'mostSold' => $mostSold,
            'mostVisited' => $mostVisited,
        ];
    }
}
