<?php

namespace App\Jobs;

use App\Enums\AlertReason;
use App\Enums\SentimentLabel;
use App\Models\Review;
use App\Models\ReviewAlert;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

/**
 * Applies the bad-review rules (config/reviews.php) to analyzed reviews of
 * a listing and opens alerts for matches. The ReviewAlert observer handles
 * notification fan-out.
 */
class EvaluateReviewAlertsJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(public int $storeListingId) {}

    public function handle(): void
    {
        $maxRating = (int) config('reviews.alerts.max_rating', 2);

        Review::query()
            ->where('store_listing_id', $this->storeListingId)
            ->whereNotNull('analyzed_at')
            ->whereDoesntHave('alert')
            ->where(fn ($q) => $q
                ->where('rating', '<=', $maxRating)
                ->orWhere('sentiment_label', SentimentLabel::Negative))
            ->each(function (Review $review) use ($maxRating) {
                $lowRating = $review->rating <= $maxRating;
                $negative = $review->sentiment_label === SentimentLabel::Negative;

                ReviewAlert::create([
                    'review_id' => $review->id,
                    'store_listing_id' => $review->store_listing_id,
                    'reason' => match (true) {
                        $lowRating && $negative => AlertReason::Both,
                        $lowRating => AlertReason::LowRating,
                        default => AlertReason::NegativeSentiment,
                    },
                    'severity' => ($review->rating === 1 || ($lowRating && $negative)) ? 'high' : 'medium',
                ]);
            });
    }
}
