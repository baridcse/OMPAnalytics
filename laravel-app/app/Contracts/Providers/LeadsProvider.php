<?php

namespace App\Contracts\Providers;

use App\Integrations\Data\LeadRow;
use App\Models\Integration;
use Illuminate\Support\Carbon;

interface LeadsProvider
{
    /**
     * @return iterable<LeadRow>
     */
    public function fetchLeads(Integration $integration, ?Carbon $since): iterable;
}
