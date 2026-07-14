<?php

namespace App\Jobs;

use App\Enums\Capability;
use App\Integrations\Support\DateRange;
use App\Models\AdRevenueDaily;
use App\Models\SyncRun;

class SyncAdRevenueJob extends AbstractSyncJob
{
    protected function capability(): Capability
    {
        return Capability::AdRevenue;
    }

    protected function process(object $provider, SyncRun $run): int
    {
        $range = DateRange::lastDays((int) config('integrations.sync.metrics_window_days'));
        $listingId = $this->integration->store_listing_id;

        $rows = [];
        foreach ($provider->fetchAdRevenue($this->integration, $range) as $row) {
            $rows[] = $row->toUpsertRow($listingId);
        }

        foreach (array_chunk($rows, 500) as $chunk) {
            AdRevenueDaily::upsert(
                $chunk,
                ['store_listing_id', 'network', 'date'],
                ['impressions', 'clicks', 'ctr', 'ecpm', 'estimated_revenue', 'currency', 'updated_at'],
            );
        }

        return count($rows);
    }
}
