<?php

namespace Database\Factories;

use App\Enums\AlertReason;
use App\Enums\AlertStatus;
use App\Models\Review;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<\App\Models\ReviewAlert>
 */
class ReviewAlertFactory extends Factory
{
    public function definition(): array
    {
        return [
            'review_id' => Review::factory()->state(['rating' => 1]),
            'store_listing_id' => fn (array $attributes) => Review::find($attributes['review_id'])->store_listing_id,
            'reason' => AlertReason::LowRating,
            'severity' => 'high',
            'status' => AlertStatus::Open,
            'assigned_to' => null,
            'notified_at' => null,
            'notified_channels' => null,
        ];
    }
}
