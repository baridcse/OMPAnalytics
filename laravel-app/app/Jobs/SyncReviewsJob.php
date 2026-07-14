<?php

namespace App\Jobs;

use App\Enums\Capability;
use App\Models\Review;
use App\Models\SyncRun;
use Illuminate\Support\Carbon;

class SyncReviewsJob extends AbstractSyncJob
{
    protected function capability(): Capability
    {
        return Capability::Reviews;
    }

    protected function process(object $provider, SyncRun $run): int
    {
        $listingId = $this->integration->store_listing_id;

        $cursor = $this->integration->sync_cursor['reviews_since'] ?? null;
        $since = $cursor
            ? Carbon::parse($cursor)
            : Carbon::today()->subDays((int) config('integrations.sync.reviews_lookback_days'));

        $rows = [];
        $latestSeen = $since;

        foreach ($provider->fetchReviews($this->integration, $since) as $row) {
            $rows[] = $row->toUpsertRow($listingId);
            $createdAt = Carbon::parse($row->reviewCreatedAt);
            if ($createdAt->gt($latestSeen)) {
                $latestSeen = $createdAt;
            }
        }

        foreach (array_chunk($rows, 500) as $chunk) {
            Review::upsert(
                $chunk,
                ['store_listing_id', 'external_review_id'],
                ['author_name', 'rating', 'title', 'body', 'language', 'app_version', 'device', 'country', 'review_updated_at', 'updated_at'],
            );
        }

        $this->integration->update([
            'sync_cursor' => array_merge($this->integration->sync_cursor ?? [], [
                'reviews_since' => $latestSeen->toDateTimeString(),
            ]),
        ]);

        // New rows arrive with analyzed_at = null; analyze then evaluate alerts.
        if ($rows !== []) {
            AnalyzeReviewSentimentJob::withChain([
                new EvaluateReviewAlertsJob($listingId),
            ])->dispatch($listingId);
        }

        return count($rows);
    }
}
