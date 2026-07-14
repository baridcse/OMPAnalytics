<?php

namespace App\Integrations\Support;

use Illuminate\Support\Carbon;

final readonly class DateRange
{
    public function __construct(
        public Carbon $start,
        public Carbon $end,
    ) {}

    public static function lastDays(int $days): self
    {
        return new self(Carbon::today()->subDays($days - 1), Carbon::today());
    }
}
