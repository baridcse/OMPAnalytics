<?php

namespace Database\Factories;

use App\Models\StoreListing;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<\App\Models\Integration>
 */
class IntegrationFactory extends Factory
{
    public function definition(): array
    {
        return [
            'provider' => 'fake',
            'name' => 'Fake integration',
            'is_enabled' => true,
            'credentials' => null,
            'config' => null,
            'store_listing_id' => StoreListing::factory(),
            'last_synced_at' => null,
            'last_sync_status' => null,
            'last_error' => null,
            'sync_cursor' => null,
        ];
    }
}
