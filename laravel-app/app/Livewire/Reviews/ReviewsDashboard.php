<?php

namespace App\Livewire\Reviews;

use App\Livewire\Concerns\HasDashboardFilters;
use App\Models\Review;
use App\Services\Metrics\ReviewInsightsService;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('layouts.app')]
#[Title('Reviews')]
class ReviewsDashboard extends Component
{
    use HasDashboardFilters;
    use WithPagination;

    #[Url]
    public string $sentiment = '';

    public function updating($property): void
    {
        if (in_array($property, ['range', 'app', 'sentiment'])) {
            $this->resetPage();
        }
    }

    public function render(ReviewInsightsService $insights)
    {
        [$start, $end, $appId] = [$this->rangeStart(), $this->rangeEnd(), $this->selectedAppId()];

        $reviews = Review::query()
            ->with('storeListing.app')
            ->when($appId, fn ($q) => $q->whereHas('storeListing', fn ($l) => $l->where('app_id', $appId)))
            ->whereBetween('review_created_at', [$start->startOfDay(), $end->copy()->endOfDay()])
            ->when($this->sentiment !== '', fn ($q) => $q->where('sentiment_label', $this->sentiment))
            ->orderByDesc('review_created_at')
            ->paginate(10);

        return view('livewire.reviews.reviews-dashboard', [
            'totals' => $insights->totals($start, $end, $appId),
            'series' => $insights->dailySeries($start, $end, $appId),
            'reviews' => $reviews,
            'apps' => $this->availableApps(),
        ]);
    }
}
