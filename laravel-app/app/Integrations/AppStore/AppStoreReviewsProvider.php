<?php

namespace App\Integrations\AppStore;

use App\Contracts\Providers\ReviewsProvider;
use App\Integrations\Data\ReviewRow;
use App\Integrations\Support\AppStoreConnectClient;
use App\Models\Integration;
use Illuminate\Support\Carbon;
use RuntimeException;

/**
 * Pulls customer reviews from the App Store Connect API. Reviews are walked
 * newest-first, so the walk stops at the first review at or before the sync
 * cursor; pagination follows links.next (an absolute URL).
 *
 * Note: ASC exposes no appVersion/device/language on reviews, and territory
 * is a 3-letter code (e.g. USA) — stored as-is.
 */
class AppStoreReviewsProvider implements ReviewsProvider
{
    /**
     * Safety cap: 20 pages x 200 reviews per sync run.
     */
    private const MAX_PAGES = 20;

    public function __construct(private readonly AppStoreConnectClient $client) {}

    /**
     * @return iterable<ReviewRow>
     */
    public function fetchReviews(Integration $integration, ?Carbon $since): iterable
    {
        $listing = $integration->storeListing
            ?? throw new RuntimeException("Integration [{$integration->name}] has no store listing attached.");

        $url = "/v1/apps/{$listing->store_app_id}/customerReviews";
        $query = ['sort' => '-createdDate', 'limit' => 200];

        for ($page = 0; $url !== null && $page < self::MAX_PAGES; $page++) {
            $response = $this->client->get($integration, $url, $query)->throw();
            $payload = $response->json();

            foreach ($payload['data'] ?? [] as $item) {
                $attributes = $item['attributes'] ?? [];
                $createdAt = Carbon::parse($attributes['createdDate'])->utc();

                if ($since !== null && $createdAt->lte($since)) {
                    return; // newest-first: everything after this is already synced
                }

                yield new ReviewRow(
                    externalReviewId: $item['id'],
                    rating: (int) $attributes['rating'],
                    reviewCreatedAt: $createdAt->toDateTimeString(),
                    authorName: $attributes['reviewerNickname'] ?? null,
                    title: $attributes['title'] ?? null,
                    body: $attributes['body'] ?? null,
                    country: $attributes['territory'] ?? null,
                );
            }

            // links.next is absolute and already carries the cursor params;
            // null query keeps its query string intact.
            $url = $payload['links']['next'] ?? null;
            $query = null;
        }
    }
}
