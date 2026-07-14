<?php

namespace App\Livewire\Reviews;

use App\Enums\AlertStatus;
use App\Models\ReviewAlert;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('layouts.app')]
#[Title('Bad Review Alerts')]
class BadReviewAlerts extends Component
{
    use WithPagination;

    #[Url]
    public string $status = 'open';

    public function updatingStatus(): void
    {
        $this->resetPage();
    }

    public function acknowledge(int $alertId): void
    {
        $this->transition($alertId, AlertStatus::Acknowledged);
    }

    public function resolve(int $alertId): void
    {
        $this->transition($alertId, AlertStatus::Resolved);
    }

    public function assignToMe(int $alertId): void
    {
        ReviewAlert::findOrFail($alertId)->update(['assigned_to' => auth()->id()]);
    }

    private function transition(int $alertId, AlertStatus $status): void
    {
        ReviewAlert::findOrFail($alertId)->update(['status' => $status]);
    }

    public function render()
    {
        $alerts = ReviewAlert::query()
            ->with(['review.storeListing.app', 'assignee'])
            ->when($this->status !== '', fn ($q) => $q->where('status', $this->status))
            ->orderByDesc('created_at')
            ->paginate(10);

        return view('livewire.reviews.bad-review-alerts', [
            'alerts' => $alerts,
            'counts' => [
                'open' => ReviewAlert::where('status', AlertStatus::Open)->count(),
                'acknowledged' => ReviewAlert::where('status', AlertStatus::Acknowledged)->count(),
                'resolved' => ReviewAlert::where('status', AlertStatus::Resolved)->count(),
            ],
        ]);
    }
}
