<?php

namespace App\Jobs;

use App\Models\Review;
use App\Support\Sentiment\SentimentManager;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

/**
 * Scores every unanalyzed review for a store listing with the configured
 * sentiment driver.
 */
class AnalyzeReviewSentimentJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(public int $storeListingId) {}

    public function handle(SentimentManager $sentiment): void
    {
        $analyzer = $sentiment->driver();

        Review::query()
            ->where('store_listing_id', $this->storeListingId)
            ->needsAnalysis()
            ->each(function (Review $review) use ($analyzer) {
                $result = $analyzer->analyze(trim(($review->title ?? '').' '.($review->body ?? '')));

                $review->update([
                    'sentiment_score' => $result->score,
                    'sentiment_label' => $result->label,
                    'sentiment_magnitude' => $result->magnitude,
                    'analyzer' => $analyzer->name(),
                    'analyzed_at' => now(),
                ]);
            });
    }
}
