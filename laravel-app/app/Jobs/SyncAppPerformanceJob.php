<?php

namespace App\Jobs;

use App\Enums\Capability;
use App\Integrations\Support\DateRange;
use App\Models\AppPerformanceDaily;
use App\Models\SyncRun;

class SyncAppPerformanceJob extends AbstractSyncJob
{
    protected function capability(): Capability
    {
        return Capability::Metrics;
    }

    protected function process(object $provider, SyncRun $run): int
    {
        $range = DateRange::lastDays((int) config('integrations.sync.metrics_window_days'));
        $listingId = $this->integration->store_listing_id;

        $rows = [];
        foreach ($provider->fetchAppPerformance($this->integration, $range) as $row) {
            $rows[] = $row->toUpsertRow($listingId, $this->integration->provider);
        }

        foreach (array_chunk($rows, 500) as $chunk) {
            AppPerformanceDaily::upsert(
                $chunk,
                ['store_listing_id', 'date'],
                ['active_users', 'installs', 'uninstalls', 'crash_rate', 'anr_rate', 'rating_avg', 'rating_count', 'source', 'updated_at'],
            );
        }

        return count($rows);
    }
}
