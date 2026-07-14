<?php

namespace App\Contracts\Providers;

use App\Integrations\Data\AnalyticsRow;
use App\Integrations\Support\DateRange;
use App\Models\Integration;

interface AnalyticsProvider
{
    /**
     * @return iterable<AnalyticsRow>
     */
    public function fetchAnalytics(Integration $integration, DateRange $range): iterable;
}
