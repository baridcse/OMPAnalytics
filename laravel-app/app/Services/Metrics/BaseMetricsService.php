<?php

namespace App\Services\Metrics;

use App\Models\StoreListing;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

abstract class BaseMetricsService
{
    /**
     * IDs of store listings in scope for an optional app filter.
     *
     * @return list<int>
     */
    protected function listingIds(?int $appId): array
    {
        return StoreListing::query()
            ->when($appId, fn ($q) => $q->where('app_id', $appId))
            ->pluck('id')
            ->all();
    }

    /**
     * Re-key a by-date collection into a dense, zero-filled day series so
     * charts have one point per day.
     *
     * @param  Collection<string, object>  $rows  keyed by Y-m-d
     * @param  list<string>  $fields
     * @return array{categories: list<string>, series: array<string, list<float|int>>}
     */
    protected function fillDates(Collection $rows, Carbon $start, Carbon $end, array $fields): array
    {
        $categories = [];
        $series = array_fill_keys($fields, []);

        for ($day = $start->copy(); $day->lte($end); $day->addDay()) {
            $key = $day->toDateString();
            $categories[] = $key;
            foreach ($fields as $field) {
                $series[$field][] = round((float) ($rows[$key]->{$field} ?? 0), 4);
            }
        }

        return ['categories' => $categories, 'series' => $series];
    }

    /**
     * Percent change between a current and previous value (null when the
     * previous period has no data to compare against).
     */
    protected function delta(float|int $current, float|int $previous): ?float
    {
        if ((float) $previous === 0.0) {
            return null;
        }

        return round((($current - $previous) / $previous) * 100, 1);
    }
}
