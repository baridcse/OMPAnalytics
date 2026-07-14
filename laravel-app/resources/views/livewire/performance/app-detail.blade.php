<div>
    <div class="mb-6 flex flex-wrap items-center justify-between gap-4">
        <div>
            <div class="flex items-center gap-3">
                <div class="flex h-10 w-10 items-center justify-center rounded-xl bg-indigo-100 text-lg font-bold text-indigo-600">
                    {{ mb_substr($subject->name, 0, 1) }}
                </div>
                <div>
                    <h1 class="text-xl font-semibold text-gray-900">{{ $subject->name }}</h1>
                    <p class="text-sm text-gray-500">
                        @foreach ($subject->storeListings as $listing)
                            <span class="mr-2">{{ $listing->platform->label() }} · {{ $listing->store_app_id }}</span>
                        @endforeach
                    </p>
                </div>
            </div>
        </div>
        <x-dashboard.filter-bar :apps="$apps" :range="$range" :show-apps="false" />
    </div>

    <div class="grid grid-cols-2 gap-4 md:grid-cols-4 xl:grid-cols-8">
        <x-kpi.stat-card label="Installs" :value="number_format($perfTotals['installs'])" :delta="$perfTotals['deltas']['installs']" />
        <x-kpi.stat-card label="Active users" :value="number_format($perfTotals['avg_active_users'])" />
        <x-kpi.stat-card label="Crash rate" :value="number_format($perfTotals['avg_crash_rate'] * 100, 2).'%'" :invert="true" :delta="$perfTotals['deltas']['avg_crash_rate']" />
        <x-kpi.stat-card label="Rating" :value="number_format($perfTotals['avg_rating'], 2).' ★'" />
        <x-kpi.stat-card label="Revenue" :value="'$'.number_format($revTotals['revenue'], 2)" :delta="$revTotals['deltas']['revenue']" />
        <x-kpi.stat-card label="eCPM" :value="'$'.number_format($revTotals['avg_ecpm'], 2)" />
        <x-kpi.stat-card label="Sessions" :value="number_format($anTotals['sessions'])" :delta="$anTotals['deltas']['sessions']" />
        <x-kpi.stat-card label="Reviews" :value="number_format($rvTotals['total'])" :hint="$rvTotals['negative_share'].'% negative'" />
    </div>

    <div class="mt-6 grid gap-6 xl:grid-cols-2">
        <div class="rounded-xl border border-gray-200 bg-white p-5 shadow-sm">
            <h2 class="text-sm font-semibold text-gray-700">Active users</h2>
            <x-charts.chart wire:key="ad-dau-{{ $this->filterSignature() }}"
                :categories="$perfSeries['categories']"
                :series="[['name' => 'Active users', 'data' => $perfSeries['series']['active_users']]]" />
        </div>

        <div class="rounded-xl border border-gray-200 bg-white p-5 shadow-sm">
            <h2 class="text-sm font-semibold text-gray-700">Revenue (USD)</h2>
            <x-charts.chart wire:key="ad-rev-{{ $this->filterSignature() }}"
                :categories="$revSeries['categories']"
                :colors="['#22c55e']"
                :series="[['name' => 'Revenue', 'data' => $revSeries['series']['revenue']]]" />
        </div>
    </div>
</div>
