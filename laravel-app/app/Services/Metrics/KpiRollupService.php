<?php

namespace App\Services\Metrics;

use App\Models\ReviewAlert;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;

/**
 * Cross-domain headline KPIs for the overview dashboard (goal 7).
 */
class KpiRollupService
{
    public function __construct(
        private readonly PerformanceMetricsService $performance,
        private readonly RevenueMetricsService $revenue,
        private readonly AnalyticsQueryService $analytics,
        private readonly ReviewInsightsService $reviews,
        private readonly LeadsMetricsService $leads,
    ) {}

    /**
     * @return array{performance: array, revenue: array, analytics: array, reviews: array, leads: array, open_alerts: int}
     */
    public function headline(Carbon $start, Carbon $end, ?int $appId = null): array
    {
        $key = sprintf('kpi:headline:%s:%s:%s', $start->toDateString(), $end->toDateString(), $appId ?? 'all');

        return Cache::remember($key, now()->addMinutes(10), fn () => [
            'performance' => $this->performance->totals($start, $end, $appId),
            'revenue' => $this->revenue->totals($start, $end, $appId),
            'analytics' => $this->analytics->totals($start, $end, $appId),
            'reviews' => $this->reviews->totals($start, $end, $appId),
            'leads' => $this->leads->totals($start, $end, $appId),
            'open_alerts' => ReviewAlert::open()->count(),
        ]);
    }
}
