<?php

namespace App\Integrations\Testing;

use App\Contracts\Providers\LeadsProvider;
use App\Integrations\Data\LeadRow;
use App\Models\Integration;
use Illuminate\Support\Carbon;

class FakeLeadsProvider implements LeadsProvider
{
    public function fetchLeads(Integration $integration, ?Carbon $since): iterable
    {
        $seed = $integration->id;

        for ($i = 0; $i < 5; $i++) {
            $receivedAt = Carbon::today()->subDays($i)->setTime(11, 15);

            if ($since !== null && $receivedAt->lte($since)) {
                continue;
            }

            yield new LeadRow(
                source: 'fake',
                externalId: "fake-lead-{$seed}-{$i}",
                receivedAt: $receivedAt->toDateTimeString(),
                name: "Demo Lead {$i}",
                email: "lead{$seed}{$i}@example.com",
                campaign: 'demo-campaign',
                medium: 'cpc',
            );
        }
    }
}
