<?php

namespace App\Integrations\Data;

use Spatie\LaravelData\Data;

class ReviewRow extends Data
{
    public function __construct(
        public string $externalReviewId,
        public int $rating,
        public string $reviewCreatedAt,
        public ?string $authorName = null,
        public ?string $title = null,
        public ?string $body = null,
        public ?string $language = null,
        public ?string $appVersion = null,
        public ?string $device = null,
        public ?string $country = null,
        public ?string $reviewUpdatedAt = null,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function toUpsertRow(int $storeListingId): array
    {
        return [
            'store_listing_id' => $storeListingId,
            'external_review_id' => $this->externalReviewId,
            'author_name' => $this->authorName,
            'rating' => $this->rating,
            'title' => $this->title,
            'body' => $this->body,
            'language' => $this->language,
            'app_version' => $this->appVersion,
            'device' => $this->device,
            'country' => $this->country,
            'review_created_at' => $this->reviewCreatedAt,
            'review_updated_at' => $this->reviewUpdatedAt ?? $this->reviewCreatedAt,
            'created_at' => now(),
            'updated_at' => now(),
        ];
    }
}
