<?php

namespace App\Livewire\Performance;

use App\Livewire\Concerns\HasDashboardFilters;
use App\Services\Metrics\PerformanceMetricsService;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('layouts.app')]
#[Title('App Performance')]
class PerformanceDashboard extends Component
{
    use HasDashboardFilters;

    public function render(PerformanceMetricsService $performance)
    {
        [$start, $end, $appId] = [$this->rangeStart(), $this->rangeEnd(), $this->selectedAppId()];

        return view('livewire.performance.performance-dashboard', [
            'totals' => $performance->totals($start, $end, $appId),
            'series' => $performance->dailySeries($start, $end, $appId),
            'apps' => $this->availableApps(),
        ]);
    }
}
