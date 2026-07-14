<?php

namespace App\Services\Metrics;

use App\Models\AnalyticsDaily;
use Illuminate\Support\Carbon;

class AnalyticsQueryService extends BaseMetricsService
{
    /**
     * @return array{sessions: int, total_users: int, new_users: int, engaged_sessions: int, avg_engagement_time: float, conversions: int, deltas: array<string, ?float>}
     */
    public function totals(Carbon $start, Carbon $end, ?int $appId = null): array
    {
        $current = $this->aggregate($start, $end, $appId);

        $days = (int) $start->diffInDays($end) + 1;
        $previous = $this->aggregate($start->copy()->subDays($days), $start->copy()->subDay(), $appId);

        return [
            'sessions' => (int) $current->sessions,
            'total_users' => (int) round((float) $current->avg_users),
            'new_users' => (int) $current->new_users,
            'engaged_sessions' => (int) $current->engaged_sessions,
            'avg_engagement_time' => round((float) $current->avg_engagement_time, 1),
            'conversions' => (int) $current->conversions,
            'deltas' => [
                'sessions' => $this->delta((int) $current->sessions, (int) $previous->sessions),
                'new_users' => $this->delta((int) $current->new_users, (int) $previous->new_users),
                'conversions' => $this->delta((int) $current->conversions, (int) $previous->conversions),
            ],
        ];
    }

    /**
     * @return array{categories: list<string>, series: array<string, list<float|int>>}
     */
    public function dailySeries(Carbon $start, Carbon $end, ?int $appId = null): array
    {
        $rows = AnalyticsDaily::query()
            ->whereIntegerInRaw('store_listing_id', $this->listingIds($appId))
            ->whereBetween('date', [$start->toDateString(), $end->toDateString()])
            ->selectRaw('date, SUM(sessions) as sessions, SUM(total_users) as total_users, SUM(new_users) as new_users, SUM(conversions) as conversions')
            ->groupBy('date')
            ->get()
            ->keyBy(fn ($row) => Carbon::parse($row->date)->toDateString());

        return $this->fillDates($rows, $start, $end, ['sessions', 'total_users', 'new_users', 'conversions']);
    }

    private function aggregate(Carbon $start, Carbon $end, ?int $appId): object
    {
        return AnalyticsDaily::query()
            ->whereIntegerInRaw('store_listing_id', $this->listingIds($appId))
            ->whereBetween('date', [$start->toDateString(), $end->toDateString()])
            ->selectRaw('COALESCE(SUM(sessions),0) as sessions, COALESCE(AVG(total_users),0) as avg_users, COALESCE(SUM(new_users),0) as new_users, COALESCE(SUM(engaged_sessions),0) as engaged_sessions, COALESCE(AVG(avg_engagement_time),0) as avg_engagement_time, COALESCE(SUM(conversions),0) as conversions')
            ->first();
    }
}
