<?php

namespace App\Integrations\Testing;

use App\Contracts\Providers\MetricsProvider;
use App\Integrations\Data\AppPerformanceRow;
use App\Integrations\Support\DateRange;
use App\Models\Integration;

/**
 * Deterministic in-memory metrics source used for local development and
 * pipeline tests. Values are seeded from the listing id so repeated syncs
 * produce identical rows (proving idempotent upserts).
 */
class FakeMetricsProvider implements MetricsProvider
{
    public function fetchAppPerformance(Integration $integration, DateRange $range): iterable
    {
        $seed = $integration->store_listing_id ?? 1;

        for ($day = $range->start->copy(); $day->lte($range->end); $day->addDay()) {
            $i = $day->diffInDays($range->start);
            $base = 1000 + ($seed * 997) % 20000;

            yield new AppPerformanceRow(
                date: $day->toDateString(),
                activeUsers: $base + ($i * 13) % 500,
                installs: 50 + (($seed + $i) * 31) % 200,
                uninstalls: 10 + (($seed + $i) * 17) % 80,
                crashRate: round(0.002 + (($seed + $i) % 10) / 2000, 5),
                anrRate: round(0.001 + (($seed + $i) % 5) / 4000, 5),
                ratingAvg: round(3.8 + (($seed + $i) % 10) / 10, 2),
                ratingCount: ($seed + $i) % 20,
            );
        }
    }
}
