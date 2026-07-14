<?php

namespace App\Services\Metrics;

use App\Models\AdRevenueDaily;
use Illuminate\Support\Carbon;

class RevenueMetricsService extends BaseMetricsService
{
    /**
     * @return array{revenue: float, impressions: int, clicks: int, avg_ecpm: float, avg_ctr: float, deltas: array<string, ?float>}
     */
    public function totals(Carbon $start, Carbon $end, ?int $appId = null): array
    {
        $current = $this->aggregate($start, $end, $appId);

        $days = (int) $start->diffInDays($end) + 1;
        $previous = $this->aggregate($start->copy()->subDays($days), $start->copy()->subDay(), $appId);

        return [
            'revenue' => round((float) $current->revenue, 2),
            'impressions' => (int) $current->impressions,
            'clicks' => (int) $current->clicks,
            'avg_ecpm' => round((float) $current->avg_ecpm, 2),
            'avg_ctr' => round((float) $current->avg_ctr, 5),
            'deltas' => [
                'revenue' => $this->delta((float) $current->revenue, (float) $previous->revenue),
                'impressions' => $this->delta((int) $current->impressions, (int) $previous->impressions),
                'avg_ecpm' => $this->delta((float) $current->avg_ecpm, (float) $previous->avg_ecpm),
            ],
        ];
    }

    /**
     * @return array{categories: list<string>, series: array<string, list<float|int>>}
     */
    public function dailySeries(Carbon $start, Carbon $end, ?int $appId = null): array
    {
        $rows = AdRevenueDaily::query()
            ->whereIntegerInRaw('store_listing_id', $this->listingIds($appId))
            ->whereBetween('date', [$start->toDateString(), $end->toDateString()])
            ->selectRaw('date, SUM(estimated_revenue) as revenue, SUM(impressions) as impressions, AVG(ecpm) as ecpm')
            ->groupBy('date')
            ->get()
            ->keyBy(fn ($row) => Carbon::parse($row->date)->toDateString());

        return $this->fillDates($rows, $start, $end, ['revenue', 'impressions', 'ecpm']);
    }

    /**
     * Revenue per app, for portfolio breakdowns.
     *
     * @return array{labels: list<string>, values: list<float>}
     */
    public function revenueByApp(Carbon $start, Carbon $end): array
    {
        $rows = AdRevenueDaily::query()
            ->join('store_listings', 'store_listings.id', '=', 'ad_revenue_daily.store_listing_id')
            ->join('apps', 'apps.id', '=', 'store_listings.app_id')
            ->whereBetween('date', [$start->toDateString(), $end->toDateString()])
            ->selectRaw('apps.name as name, SUM(estimated_revenue) as revenue')
            ->groupBy('apps.name')
            ->orderByDesc('revenue')
            ->get();

        return [
            'labels' => $rows->pluck('name')->all(),
            'values' => $rows->pluck('revenue')->map(fn ($v) => round((float) $v, 2))->all(),
        ];
    }

    private function aggregate(Carbon $start, Carbon $end, ?int $appId): object
    {
        return AdRevenueDaily::query()
            ->whereIntegerInRaw('store_listing_id', $this->listingIds($appId))
            ->whereBetween('date', [$start->toDateString(), $end->toDateString()])
            ->selectRaw('COALESCE(SUM(estimated_revenue),0) as revenue, COALESCE(SUM(impressions),0) as impressions, COALESCE(SUM(clicks),0) as clicks, COALESCE(AVG(ecpm),0) as avg_ecpm, COALESCE(AVG(ctr),0) as avg_ctr')
            ->first();
    }
}
