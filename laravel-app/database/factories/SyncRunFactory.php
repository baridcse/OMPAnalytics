<?php

namespace Database\Factories;

use App\Enums\Capability;
use App\Enums\SyncStatus;
use App\Models\Integration;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<\App\Models\SyncRun>
 */
class SyncRunFactory extends Factory
{
    public function definition(): array
    {
        $started = fake()->dateTimeBetween('-7 days');

        return [
            'integration_id' => Integration::factory(),
            'provider' => 'fake',
            'capability' => Capability::Metrics,
            'started_at' => $started,
            'finished_at' => (clone $started)->modify('+45 seconds'),
            'status' => SyncStatus::Success,
            'records_processed' => fake()->numberBetween(1, 500),
            'error' => null,
            'meta' => null,
        ];
    }
}
