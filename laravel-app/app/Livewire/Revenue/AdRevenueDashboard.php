<?php

namespace App\Livewire\Revenue;

use App\Livewire\Concerns\HasDashboardFilters;
use App\Services\Metrics\RevenueMetricsService;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('layouts.app')]
#[Title('Ads Revenue')]
class AdRevenueDashboard extends Component
{
    use HasDashboardFilters;

    public function render(RevenueMetricsService $revenue)
    {
        [$start, $end, $appId] = [$this->rangeStart(), $this->rangeEnd(), $this->selectedAppId()];

        return view('livewire.revenue.ad-revenue-dashboard', [
            'totals' => $revenue->totals($start, $end, $appId),
            'series' => $revenue->dailySeries($start, $end, $appId),
            'byApp' => $revenue->revenueByApp($start, $end),
            'apps' => $this->availableApps(),
        ]);
    }
}
