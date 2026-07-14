<?php

namespace App\Livewire\Analytics;

use App\Livewire\Concerns\HasDashboardFilters;
use App\Services\Metrics\AnalyticsQueryService;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('layouts.app')]
#[Title('Analytics')]
class ProductAnalyticsDashboard extends Component
{
    use HasDashboardFilters;

    public function render(AnalyticsQueryService $analytics)
    {
        [$start, $end, $appId] = [$this->rangeStart(), $this->rangeEnd(), $this->selectedAppId()];

        return view('livewire.analytics.product-analytics-dashboard', [
            'totals' => $analytics->totals($start, $end, $appId),
            'series' => $analytics->dailySeries($start, $end, $appId),
            'apps' => $this->availableApps(),
        ]);
    }
}
