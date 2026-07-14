<?php

namespace App\Integrations\Data;

use Spatie\LaravelData\Data;

class AnalyticsRow extends Data
{
    public function __construct(
        public string $date,
        public int $sessions,
        public int $totalUsers,
        public int $newUsers,
        public int $engagedSessions = 0,
        public ?float $avgEngagementTime = null,
        public int $conversions = 0,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function toUpsertRow(int $storeListingId): array
    {
        return [
            'store_listing_id' => $storeListingId,
            'date' => $this->date,
            'sessions' => $this->sessions,
            'total_users' => $this->totalUsers,
            'new_users' => $this->newUsers,
            'engaged_sessions' => $this->engagedSessions,
            'avg_engagement_time' => $this->avgEngagementTime,
            'conversions' => $this->conversions,
            'created_at' => now(),
            'updated_at' => now(),
        ];
    }
}
