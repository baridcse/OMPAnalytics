<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<\App\Models\App>
 */
class AppFactory extends Factory
{
    public function definition(): array
    {
        $name = fake()->unique()->randomElement([
                'Pixel Notes', 'FitTrack Pro', 'Recipe Vault', 'Star Puzzle',
                'Budget Buddy', 'Sleep Sounds', 'Photo Wizard', 'Word Rush',
            ]).' '.fake()->numberBetween(1, 99);

        return [
            'name' => $name,
            'slug' => Str::slug($name),
            'icon_url' => null,
            'is_active' => true,
        ];
    }
}
