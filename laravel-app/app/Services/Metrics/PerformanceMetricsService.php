<?php

namespace App\Services\Metrics;

use App\Models\AppPerformanceDaily;
use Illuminate\Support\Carbon;

class PerformanceMetricsService extends BaseMetricsService
{
    /**
     * @return array{installs: int, uninstalls: int, avg_active_users: int, avg_crash_rate: float, avg_rating: float, deltas: array<string, ?float>}
     */
    public function totals(Carbon $start, Carbon $end, ?int $appId = null): array
    {
        $current = $this->aggregate($start, $end, $appId);

        $days = (int) $start->diffInDays($end) + 1;
        $previous = $this->aggregate($start->copy()->subDays($days), $start->copy()->subDay(), $appId);

        return [
            'installs' => (int) $current->installs,
            'uninstalls' => (int) $current->uninstalls,
            'avg_active_users' => (int) round((float) $current->avg_active_users),
            'avg_crash_rate' => round((float) $current->avg_crash_rate, 5),
            'avg_rating' => round((float) $current->avg_rating, 2),
            'deltas' => [
                'installs' => $this->delta((int) $current->installs, (int) $previous->installs),
                'avg_active_users' => $this->delta((float) $current->avg_active_users, (float) $previous->avg_active_users),
                'avg_crash_rate' => $this->delta((float) $current->avg_crash_rate, (float) $previous->avg_crash_rate),
            ],
        ];
    }

    /**
     * @return array{categories: list<string>, series: array<string, list<float|int>>}
     */
    public function dailySeries(Carbon $start, Carbon $end, ?int $appId = null): array
    {
        $rows = AppPerformanceDaily::query()
            ->whereIntegerInRaw('store_listing_id', $this->listingIds($appId))
            ->whereBetween('date', [$start->toDateString(), $end->toDateString()])
            ->selectRaw('date, SUM(installs) as installs, SUM(uninstalls) as uninstalls, SUM(active_users) as active_users, AVG(crash_rate) as crash_rate, AVG(rating_avg) as rating_avg')
            ->groupBy('date')
            ->get()
            ->keyBy(fn ($row) => Carbon::parse($row->date)->toDateString());

        return $this->fillDates($rows, $start, $end, ['installs', 'uninstalls', 'active_users', 'crash_rate', 'rating_avg']);
    }

    private function aggregate(Carbon $start, Carbon $end, ?int $appId): object
    {
        return AppPerformanceDaily::query()
            ->whereIntegerInRaw('store_listing_id', $this->listingIds($appId))
            ->whereBetween('date', [$start->toDateString(), $end->toDateString()])
            ->selectRaw('COALESCE(SUM(installs),0) as installs, COALESCE(SUM(uninstalls),0) as uninstalls, COALESCE(AVG(active_users),0) as avg_active_users, COALESCE(AVG(crash_rate),0) as avg_crash_rate, COALESCE(AVG(rating_avg),0) as avg_rating')
            ->first();
    }
}
