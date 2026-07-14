<?php

namespace App\Services\Metrics;

use App\Enums\SentimentLabel;
use App\Models\Review;
use Illuminate\Support\Carbon;

class ReviewInsightsService extends BaseMetricsService
{
    /**
     * @return array{total: int, avg_rating: float, negative_share: float, sentiment: array<string, int>, histogram: array<int, int>}
     */
    public function totals(Carbon $start, Carbon $end, ?int $appId = null): array
    {
        $base = $this->query($start, $end, $appId);

        $total = (clone $base)->count();
        $avgRating = (float) (clone $base)->avg('rating');

        $sentiment = (clone $base)
            ->whereNotNull('sentiment_label')
            ->selectRaw('sentiment_label, COUNT(*) as n')
            ->groupBy('sentiment_label')
            ->pluck('n', 'sentiment_label')
            ->all();

        $histogram = (clone $base)
            ->selectRaw('rating, COUNT(*) as n')
            ->groupBy('rating')
            ->pluck('n', 'rating')
            ->all();

        $negative = (int) ($sentiment[SentimentLabel::Negative->value] ?? 0);

        return [
            'total' => $total,
            'avg_rating' => round($avgRating, 2),
            'negative_share' => $total > 0 ? round($negative / $total * 100, 1) : 0.0,
            'sentiment' => [
                'positive' => (int) ($sentiment[SentimentLabel::Positive->value] ?? 0),
                'neutral' => (int) ($sentiment[SentimentLabel::Neutral->value] ?? 0),
                'negative' => $negative,
            ],
            'histogram' => collect(range(1, 5))
                ->mapWithKeys(fn (int $r) => [$r => (int) ($histogram[$r] ?? 0)])
                ->all(),
        ];
    }

    /**
     * Daily average rating + review volume, for trend charts.
     *
     * @return array{categories: list<string>, series: array<string, list<float|int>>}
     */
    public function dailySeries(Carbon $start, Carbon $end, ?int $appId = null): array
    {
        $rows = $this->query($start, $end, $appId)
            ->selectRaw("DATE(review_created_at) as day, AVG(rating) as avg_rating, COUNT(*) as count, SUM(CASE WHEN sentiment_label = 'negative' THEN 1 ELSE 0 END) as negative")
            ->groupBy('day')
            ->get()
            ->keyBy(fn ($row) => Carbon::parse($row->day)->toDateString());

        return $this->fillDates($rows, $start, $end, ['avg_rating', 'count', 'negative']);
    }

    private function query(Carbon $start, Carbon $end, ?int $appId)
    {
        return Review::query()
            ->whereIntegerInRaw('store_listing_id', $this->listingIds($appId))
            ->whereBetween('review_created_at', [$start->startOfDay(), $end->copy()->endOfDay()]);
    }
}
