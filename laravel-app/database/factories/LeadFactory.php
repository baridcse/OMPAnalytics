<?php

namespace Database\Factories;

use App\Models\App;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<\App\Models\Lead>
 */
class LeadFactory extends Factory
{
    public function definition(): array
    {
        return [
            'source' => fake()->randomElement(['webhook', 'google_ads', 'meta_ads', 'crm']),
            'external_id' => (string) Str::uuid(),
            'app_id' => App::factory(),
            'campaign' => fake()->randomElement(['summer-launch', 'retargeting-q3', 'brand-search', 'install-boost']),
            'medium' => fake()->randomElement(['cpc', 'social', 'email', 'organic']),
            'name' => fake()->name(),
            'email' => fake()->unique()->safeEmail(),
            'status' => fake()->randomElement(['new', 'contacted', 'qualified', 'converted', 'lost']),
            'value' => fake()->optional(0.4)->randomFloat(2, 5, 500),
            'received_at' => fake()->dateTimeBetween('-90 days'),
            'raw' => null,
        ];
    }
}
