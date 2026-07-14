<?php

namespace Database\Factories;

use App\Models\StoreListing;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<\App\Models\AppPerformanceDaily>
 */
class AppPerformanceDailyFactory extends Factory
{
    public function definition(): array
    {
        return [
            'store_listing_id' => StoreListing::factory(),
            'date' => fake()->unique()->dateTimeBetween('-90 days')->format('Y-m-d'),
            'active_users' => fake()->numberBetween(500, 50000),
            'installs' => fake()->numberBetween(20, 2000),
            'uninstalls' => fake()->numberBetween(5, 800),
            'crash_rate' => fake()->randomFloat(5, 0.0005, 0.03),
            'anr_rate' => fake()->randomFloat(5, 0.0001, 0.01),
            'rating_avg' => fake()->randomFloat(2, 3.2, 4.9),
            'rating_count' => fake()->numberBetween(0, 60),
            'source' => 'demo',
        ];
    }
}
