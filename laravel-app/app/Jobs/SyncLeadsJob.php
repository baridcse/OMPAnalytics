<?php

namespace App\Jobs;

use App\Enums\Capability;
use App\Models\Lead;
use App\Models\SyncRun;
use Illuminate\Support\Carbon;

class SyncLeadsJob extends AbstractSyncJob
{
    protected function capability(): Capability
    {
        return Capability::Leads;
    }

    protected function process(object $provider, SyncRun $run): int
    {
        $appId = $this->integration->storeListing?->app_id;

        $cursor = $this->integration->sync_cursor['leads_since'] ?? null;
        $since = $cursor ? Carbon::parse($cursor) : null;

        $rows = [];
        $latestSeen = $since;

        foreach ($provider->fetchLeads($this->integration, $since) as $row) {
            $rows[] = $row->toUpsertRow($appId);
            $receivedAt = Carbon::parse($row->receivedAt);
            if ($latestSeen === null || $receivedAt->gt($latestSeen)) {
                $latestSeen = $receivedAt;
            }
        }

        foreach (array_chunk($rows, 500) as $chunk) {
            Lead::upsert(
                $chunk,
                ['source', 'external_id'],
                ['campaign', 'medium', 'name', 'email', 'status', 'value', 'updated_at'],
            );
        }

        if ($latestSeen !== null) {
            $this->integration->update([
                'sync_cursor' => array_merge($this->integration->sync_cursor ?? [], [
                    'leads_since' => $latestSeen->toDateTimeString(),
                ]),
            ]);
        }

        return count($rows);
    }
}
