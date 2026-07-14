<?php

namespace App\Integrations\Testing;

use App\Contracts\Providers\ReviewsProvider;
use App\Integrations\Data\ReviewRow;
use App\Models\Integration;
use Illuminate\Support\Carbon;

/**
 * Deterministic review source: emits a fixed set of reviews (some clearly
 * negative) so the sentiment + alert pipeline can be exercised end to end.
 */
class FakeReviewsProvider implements ReviewsProvider
{
    /**
     * @var list<array{rating: int, body: string, author: string}>
     */
    public const SAMPLES = [
        ['rating' => 5, 'body' => 'Amazing app, works perfectly. Love the new design!', 'author' => 'Alice'],
        ['rating' => 4, 'body' => 'Great overall, really useful and easy to use.', 'author' => 'Bikash'],
        ['rating' => 3, 'body' => 'Decent app. Does the job, nothing special.', 'author' => 'Carol'],
        ['rating' => 1, 'body' => 'Terrible update, it crashes constantly. Total waste of time.', 'author' => 'Dan'],
        ['rating' => 2, 'body' => 'Too many ads and popups, very annoying. Uninstalling soon.', 'author' => 'Erin'],
    ];

    public function fetchReviews(Integration $integration, ?Carbon $since): iterable
    {
        $listingId = $integration->store_listing_id ?? 0;

        foreach (self::SAMPLES as $i => $sample) {
            $createdAt = Carbon::today()->subDays($i)->setTime(9 + $i, 30);

            if ($since !== null && $createdAt->lte($since)) {
                continue;
            }

            yield new ReviewRow(
                externalReviewId: "fake-{$listingId}-{$i}",
                rating: $sample['rating'],
                reviewCreatedAt: $createdAt->toDateTimeString(),
                authorName: $sample['author'],
                body: $sample['body'],
                language: 'en',
                appVersion: '3.0.0',
                country: 'US',
            );
        }
    }
}
