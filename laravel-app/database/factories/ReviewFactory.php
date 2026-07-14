<?php

namespace Database\Factories;

use App\Enums\SentimentLabel;
use App\Models\StoreListing;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<\App\Models\Review>
 */
class ReviewFactory extends Factory
{
    /**
     * Sample review bodies keyed by rating band, so seeded sentiment
     * roughly matches the star rating.
     *
     * @var array<string, list<string>>
     */
    private const BODIES = [
        'positive' => [
            'Love this app, works perfectly and the design is beautiful.',
            'Great app! Super easy to use and really helpful every day.',
            'Fantastic update, everything is smooth and fast now. Highly recommend.',
            'Best app in its category. Simple, clean and reliable.',
            'Really enjoy using this, wonderful features and no problems at all.',
        ],
        'neutral' => [
            'Does the job. Some screens could be simpler but overall fine.',
            'Decent app. A few features are behind a subscription though.',
            'Okay so far, still exploring the features.',
            'Works as described. Nothing special but no major issues either.',
        ],
        'negative' => [
            'App keeps crashing after the last update. Really frustrating.',
            'Too many ads, popups every few seconds. Uninstalled.',
            'Terrible experience, it freezes and lags constantly. Waste of time.',
            'Horrible update. Everything is broken now and support is useless.',
            'Constant bugs and glitches, the worst app I have used. Refund please.',
        ],
    ];

    public function definition(): array
    {
        $rating = fake()->numberBetween(1, 5);
        $band = $rating >= 4 ? 'positive' : ($rating === 3 ? 'neutral' : 'negative');
        $createdAt = fake()->dateTimeBetween('-90 days');

        return [
            'store_listing_id' => StoreListing::factory(),
            'external_review_id' => (string) Str::uuid(),
            'author_name' => fake()->firstName(),
            'rating' => $rating,
            'title' => null,
            'body' => fake()->randomElement(self::BODIES[$band]),
            'language' => 'en',
            'app_version' => fake()->randomElement(['2.1.0', '2.2.0', '2.3.1', '3.0.0']),
            'device' => fake()->randomElement(['Pixel 8', 'Galaxy S24', 'iPhone 15', 'iPhone 14', null]),
            'country' => fake()->randomElement(['US', 'GB', 'DE', 'IN', 'BR', 'BD']),
            'review_created_at' => $createdAt,
            'review_updated_at' => $createdAt,
            'sentiment_score' => null,
            'sentiment_label' => null,
            'analyzed_at' => null,
            'raw' => null,
        ];
    }

    public function analyzed(): static
    {
        return $this->state(function (array $attributes) {
            $rating = $attributes['rating'] ?? 3;
            [$label, $score] = match (true) {
                $rating >= 4 => [SentimentLabel::Positive, fake()->randomFloat(4, 0.3, 0.95)],
                $rating === 3 => [SentimentLabel::Neutral, fake()->randomFloat(4, -0.15, 0.25)],
                default => [SentimentLabel::Negative, fake()->randomFloat(4, -0.95, -0.3)],
            };

            return [
                'sentiment_score' => $score,
                'sentiment_label' => $label,
                'sentiment_magnitude' => abs($score),
                'analyzer' => 'lexicon',
                'analyzed_at' => now(),
            ];
        });
    }
}
