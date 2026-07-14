<?php

namespace Database\Factories;

use App\Enums\Platform;
use App\Models\App;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<\App\Models\StoreListing>
 */
class StoreListingFactory extends Factory
{
    public function definition(): array
    {
        return [
            'app_id' => App::factory(),
            'platform' => Platform::Android,
            'store_app_id' => 'com.example.'.fake()->unique()->word(),
            'bundle_id' => null,
            'developer_account' => 'demo-developer',
            'meta' => null,
        ];
    }

    public function android(): static
    {
        return $this->state(fn () => ['platform' => Platform::Android]);
    }

    public function ios(): static
    {
        return $this->state(fn () => [
            'platform' => Platform::Ios,
            'store_app_id' => (string) fake()->unique()->numberBetween(1000000000, 1999999999),
            'bundle_id' => 'com.example.'.fake()->unique()->word(),
        ]);
    }
}
