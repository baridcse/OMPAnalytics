<?php

namespace Database\Factories;

use App\Models\StoreListing;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<\App\Models\AdRevenueDaily>
 */
class AdRevenueDailyFactory extends Factory
{
    public function definition(): array
    {
        $impressions = fake()->numberBetween(10000, 900000);
        $clicks = (int) round($impressions * fake()->randomFloat(4, 0.005, 0.04));
        $ecpm = fake()->randomFloat(4, 0.4, 6.0);

        return [
            'store_listing_id' => StoreListing::factory(),
            'network' => 'admob',
            'date' => fake()->unique()->dateTimeBetween('-90 days')->format('Y-m-d'),
            'impressions' => $impressions,
            'clicks' => $clicks,
            'ctr' => round($clicks / $impressions, 5),
            'ecpm' => $ecpm,
            'estimated_revenue' => round($impressions / 1000 * $ecpm, 4),
            'currency' => 'USD',
        ];
    }
}
