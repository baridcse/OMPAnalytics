<?php

namespace App\Integrations\Data;

use Spatie\LaravelData\Data;

class AdRevenueRow extends Data
{
    public function __construct(
        public string $date,
        public string $network,
        public int $impressions,
        public int $clicks,
        public float $estimatedRevenue,
        public ?float $ecpm = null,
        public ?float $ctr = null,
        public string $currency = 'USD',
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function toUpsertRow(int $storeListingId): array
    {
        return [
            'store_listing_id' => $storeListingId,
            'network' => $this->network,
            'date' => $this->date,
            'impressions' => $this->impressions,
            'clicks' => $this->clicks,
            'ctr' => $this->ctr ?? ($this->impressions > 0 ? round($this->clicks / $this->impressions, 5) : 0),
            'ecpm' => $this->ecpm ?? ($this->impressions > 0 ? round($this->estimatedRevenue / $this->impressions * 1000, 4) : 0),
            'estimated_revenue' => $this->estimatedRevenue,
            'currency' => $this->currency,
            'created_at' => now(),
            'updated_at' => now(),
        ];
    }
}
