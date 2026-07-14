<?php

namespace App\Integrations\Testing;

use App\Contracts\Providers\AnalyticsProvider;
use App\Integrations\Data\AnalyticsRow;
use App\Integrations\Support\DateRange;
use App\Models\Integration;

class FakeAnalyticsProvider implements AnalyticsProvider
{
    public function fetchAnalytics(Integration $integration, DateRange $range): iterable
    {
        $seed = $integration->store_listing_id ?? 1;

        for ($day = $range->start->copy(); $day->lte($range->end); $day->addDay()) {
            $i = $day->diffInDays($range->start);
            $sessions = 5000 + (($seed + $i) * 419) % 30000;

            yield new AnalyticsRow(
                date: $day->toDateString(),
                sessions: $sessions,
                totalUsers: (int) round($sessions * 0.7),
                newUsers: (int) round($sessions * 0.15),
                engagedSessions: (int) round($sessions * 0.55),
                avgEngagementTime: round(150 + (($seed + $i) % 20) * 10, 2),
                conversions: (int) round($sessions * 0.012),
            );
        }
    }
}
