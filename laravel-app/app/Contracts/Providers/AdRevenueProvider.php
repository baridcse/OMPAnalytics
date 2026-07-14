<?php

namespace App\Contracts\Providers;

use App\Integrations\Data\AdRevenueRow;
use App\Integrations\Support\DateRange;
use App\Models\Integration;

interface AdRevenueProvider
{
    /**
     * @return iterable<AdRevenueRow>
     */
    public function fetchAdRevenue(Integration $integration, DateRange $range): iterable;
}
