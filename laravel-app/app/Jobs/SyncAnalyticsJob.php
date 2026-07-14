<?php

namespace App\Jobs;

use App\Enums\Capability;
use App\Integrations\Support\DateRange;
use App\Models\AnalyticsDaily;
use App\Models\SyncRun;

class SyncAnalyticsJob extends AbstractSyncJob
{
    protected function capability(): Capability
    {
        return Capability::Analytics;
    }

    protected function process(object $provider, SyncRun $run): int
    {
        $range = DateRange::lastDays((int) config('integrations.sync.metrics_window_days'));
        $listingId = $this->integration->store_listing_id;

        $rows = [];
        foreach ($provider->fetchAnalytics($this->integration, $range) as $row) {
            $rows[] = $row->toUpsertRow($listingId);
        }

        foreach (array_chunk($rows, 500) as $chunk) {
            AnalyticsDaily::upsert(
                $chunk,
                ['store_listing_id', 'date'],
                ['sessions', 'total_users', 'new_users', 'engaged_sessions', 'avg_engagement_time', 'conversions', 'updated_at'],
            );
        }

        return count($rows);
    }
}
