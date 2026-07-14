<?php

namespace App\Integrations\Testing;

use App\Contracts\Providers\AdRevenueProvider;
use App\Integrations\Data\AdRevenueRow;
use App\Integrations\Support\DateRange;
use App\Models\Integration;

class FakeAdRevenueProvider implements AdRevenueProvider
{
    public function fetchAdRevenue(Integration $integration, DateRange $range): iterable
    {
        $seed = $integration->store_listing_id ?? 1;

        for ($day = $range->start->copy(); $day->lte($range->end); $day->addDay()) {
            $i = $day->diffInDays($range->start);
            $impressions = 50000 + (($seed + $i) * 641) % 100000;
            $ecpm = round(1.0 + (($seed + $i) % 8) / 4, 4);

            yield new AdRevenueRow(
                date: $day->toDateString(),
                network: 'admob',
                impressions: $impressions,
                clicks: (int) round($impressions * 0.015),
                estimatedRevenue: round($impressions / 1000 * $ecpm, 4),
                ecpm: $ecpm,
            );
        }
    }
}
