<?php

namespace App\Livewire\Leads;

use App\Livewire\Concerns\HasDashboardFilters;
use App\Models\Lead;
use App\Services\Metrics\LeadsMetricsService;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('layouts.app')]
#[Title('Leads')]
class LeadsDashboard extends Component
{
    use HasDashboardFilters;
    use WithPagination;

    public function updating($property): void
    {
        if (in_array($property, ['range', 'app'])) {
            $this->resetPage();
        }
    }

    public function render(LeadsMetricsService $leads)
    {
        [$start, $end, $appId] = [$this->rangeStart(), $this->rangeEnd(), $this->selectedAppId()];

        $latest = Lead::query()
            ->with('app')
            ->when($appId, fn ($q) => $q->where('app_id', $appId))
            ->whereBetween('received_at', [$start->startOfDay(), $end->copy()->endOfDay()])
            ->orderByDesc('received_at')
            ->paginate(10);

        return view('livewire.leads.leads-dashboard', [
            'totals' => $leads->totals($start, $end, $appId),
            'series' => $leads->dailySeries($start, $end, $appId),
            'latest' => $latest,
            'apps' => $this->availableApps(),
        ]);
    }
}
