<?php

namespace App\Contracts\Providers;

use App\Integrations\Data\AppPerformanceRow;
use App\Integrations\Support\DateRange;
use App\Models\Integration;

interface MetricsProvider
{
    /**
     * @return iterable<AppPerformanceRow>
     */
    public function fetchAppPerformance(Integration $integration, DateRange $range): iterable;
}
