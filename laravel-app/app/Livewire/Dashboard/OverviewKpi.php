<?php

namespace App\Livewire\Dashboard;

use App\Livewire\Concerns\HasDashboardFilters;
use App\Services\Metrics\KpiRollupService;
use App\Services\Metrics\LeadsMetricsService;
use App\Services\Metrics\PerformanceMetricsService;
use App\Services\Metrics\RevenueMetricsService;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('layouts.app')]
#[Title('Overview')]
class OverviewKpi extends Component
{
    use HasDashboardFilters;

    public function render(
        KpiRollupService $kpi,
        PerformanceMetricsService $performance,
        RevenueMetricsService $revenue,
        LeadsMetricsService $leads,
    ) {
        [$start, $end, $appId] = [$this->rangeStart(), $this->rangeEnd(), $this->selectedAppId()];

        return view('livewire.dashboard.overview-kpi', [
            'kpis' => $kpi->headline($start, $end, $appId),
            'installsSeries' => $performance->dailySeries($start, $end, $appId),
            'revenueSeries' => $revenue->dailySeries($start, $end, $appId),
            'leadsSeries' => $leads->dailySeries($start, $end, $appId),
            'apps' => $this->availableApps(),
        ]);
    }
}
