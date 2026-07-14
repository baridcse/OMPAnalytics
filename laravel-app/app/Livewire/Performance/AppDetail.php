<?php

namespace App\Livewire\Performance;

use App\Livewire\Concerns\HasDashboardFilters;
use App\Models\App;
use App\Services\Metrics\AnalyticsQueryService;
use App\Services\Metrics\PerformanceMetricsService;
use App\Services\Metrics\ReviewInsightsService;
use App\Services\Metrics\RevenueMetricsService;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('layouts.app')]
#[Title('App Detail')]
class AppDetail extends Component
{
    use HasDashboardFilters;

    public App $subject;

    public function mount(App $app): void
    {
        $this->subject = $app->load('storeListings');
    }

    public function render(
        PerformanceMetricsService $performance,
        RevenueMetricsService $revenue,
        AnalyticsQueryService $analytics,
        ReviewInsightsService $reviews,
    ) {
        [$start, $end] = [$this->rangeStart(), $this->rangeEnd()];

        return view('livewire.performance.app-detail', [
            'perfTotals' => $performance->totals($start, $end, $this->subject->id),
            'perfSeries' => $performance->dailySeries($start, $end, $this->subject->id),
            'revTotals' => $revenue->totals($start, $end, $this->subject->id),
            'revSeries' => $revenue->dailySeries($start, $end, $this->subject->id),
            'anTotals' => $analytics->totals($start, $end, $this->subject->id),
            'rvTotals' => $reviews->totals($start, $end, $this->subject->id),
            'apps' => $this->availableApps(),
        ]);
    }
}
