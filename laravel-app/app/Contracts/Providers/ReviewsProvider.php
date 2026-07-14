<?php

namespace App\Contracts\Providers;

use App\Integrations\Data\ReviewRow;
use App\Models\Integration;
use Illuminate\Support\Carbon;

interface ReviewsProvider
{
    /**
     * @return iterable<ReviewRow>
     */
    public function fetchReviews(Integration $integration, ?Carbon $since): iterable;
}
