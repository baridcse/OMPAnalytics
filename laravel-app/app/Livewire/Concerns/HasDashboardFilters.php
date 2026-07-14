<?php

namespace App\Livewire\Concerns;

use App\Models\App;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Livewire\Attributes\Url;

/**
 * Shared date-range + app filters for dashboard pages, persisted to the
 * URL so filtered views are shareable and bookmarkable.
 */
trait HasDashboardFilters
{
    #[Url]
    public string $range = '30d';

    #[Url]
    public ?string $app = null;

    /** @var array<string, string> */
    public static array $ranges = [
        '7d' => 'Last 7 days',
        '30d' => 'Last 30 days',
        '90d' => 'Last 90 days',
    ];

    public function rangeStart(): Carbon
    {
        $days = match ($this->range) {
            '7d' => 7,
            '90d' => 90,
            default => 30,
        };

        return Carbon::today()->subDays($days - 1);
    }

    public function rangeEnd(): Carbon
    {
        return Carbon::today();
    }

    public function selectedAppId(): ?int
    {
        return $this->availableApps()->firstWhere('slug', $this->app)?->id;
    }

    /**
     * @return Collection<int, App>
     */
    public function availableApps(): Collection
    {
        return once(fn () => App::where('is_active', true)->orderBy('name')->get());
    }

    /**
     * Changes whenever the filter state changes — used as wire:key on chart
     * containers so Alpine re-mounts ApexCharts with fresh data.
     */
    public function filterSignature(): string
    {
        return md5($this->range.'|'.($this->app ?? 'all'));
    }
}
