<?php

namespace App\Integrations\Data;

use Spatie\LaravelData\Data;

class AppPerformanceRow extends Data
{
    public function __construct(
        public string $date,
        public int $activeUsers,
        public int $installs,
        public int $uninstalls,
        public ?float $crashRate = null,
        public ?float $anrRate = null,
        public ?float $ratingAvg = null,
        public int $ratingCount = 0,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function toUpsertRow(int $storeListingId, string $source): array
    {
        return [
            'store_listing_id' => $storeListingId,
            'date' => $this->date,
            'active_users' => $this->activeUsers,
            'installs' => $this->installs,
            'uninstalls' => $this->uninstalls,
            'crash_rate' => $this->crashRate,
            'anr_rate' => $this->anrRate,
            'rating_avg' => $this->ratingAvg,
            'rating_count' => $this->ratingCount,
            'source' => $source,
            'created_at' => now(),
            'updated_at' => now(),
        ];
    }
}
