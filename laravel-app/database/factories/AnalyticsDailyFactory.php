<?php

namespace Database\Factories;

use App\Models\StoreListing;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<\App\Models\AnalyticsDaily>
 */
class AnalyticsDailyFactory extends Factory
{
    public function definition(): array
    {
        $sessions = fake()->numberBetween(1000, 80000);

        return [
            'store_listing_id' => StoreListing::factory(),
            'date' => fake()->unique()->dateTimeBetween('-90 days')->format('Y-m-d'),
            'sessions' => $sessions,
            'total_users' => (int) round($sessions * fake()->randomFloat(2, 0.5, 0.9)),
            'new_users' => (int) round($sessions * fake()->randomFloat(2, 0.05, 0.3)),
            'engaged_sessions' => (int) round($sessions * fake()->randomFloat(2, 0.3, 0.7)),
            'avg_engagement_time' => fake()->randomFloat(2, 45, 600),
            'conversions' => fake()->numberBetween(0, 500),
        ];
    }
}
