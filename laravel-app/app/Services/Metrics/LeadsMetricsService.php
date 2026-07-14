<?php

namespace App\Services\Metrics;

use App\Models\Lead;
use Illuminate\Support\Carbon;

class LeadsMetricsService extends BaseMetricsService
{
    /**
     * @return array{total: int, converted: int, conversion_rate: float, pipeline_value: float, by_source: array<string, int>, by_status: array<string, int>, deltas: array<string, ?float>}
     */
    public function totals(Carbon $start, Carbon $end, ?int $appId = null): array
    {
        $base = $this->query($start, $end, $appId);

        $total = (clone $base)->count();
        $converted = (clone $base)->where('status', 'converted')->count();

        $days = (int) $start->diffInDays($end) + 1;
        $previousTotal = $this->query($start->copy()->subDays($days), $start->copy()->subDay(), $appId)->count();

        return [
            'total' => $total,
            'converted' => $converted,
            'conversion_rate' => $total > 0 ? round($converted / $total * 100, 1) : 0.0,
            'pipeline_value' => round((float) (clone $base)->sum('value'), 2),
            'by_source' => (clone $base)->selectRaw('source, COUNT(*) as n')->groupBy('source')->orderByDesc('n')->pluck('n', 'source')->all(),
            'by_status' => (clone $base)->selectRaw('status, COUNT(*) as n')->groupBy('status')->pluck('n', 'status')->all(),
            'deltas' => [
                'total' => $this->delta($total, $previousTotal),
            ],
        ];
    }

    /**
     * @return array{categories: list<string>, series: array<string, list<float|int>>}
     */
    public function dailySeries(Carbon $start, Carbon $end, ?int $appId = null): array
    {
        $rows = $this->query($start, $end, $appId)
            ->selectRaw('DATE(received_at) as day, COUNT(*) as count')
            ->groupBy('day')
            ->get()
            ->keyBy(fn ($row) => Carbon::parse($row->day)->toDateString());

        return $this->fillDates($rows, $start, $end, ['count']);
    }

    private function query(Carbon $start, Carbon $end, ?int $appId)
    {
        return Lead::query()
            ->when($appId, fn ($q) => $q->where('app_id', $appId))
            ->whereBetween('received_at', [$start->startOfDay(), $end->copy()->endOfDay()]);
    }
}
