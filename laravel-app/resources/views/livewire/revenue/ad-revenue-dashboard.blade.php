<div>
    <div class="mb-6 flex flex-wrap items-center justify-between gap-4">
        <div>
            <h1 class="text-xl font-semibold text-gray-900">Ads revenue</h1>
            <p class="mt-0.5 text-sm text-gray-500">Monetization across ad networks</p>
        </div>
        <x-dashboard.filter-bar :apps="$apps" :range="$range" />
    </div>

    <div class="grid grid-cols-2 gap-4 md:grid-cols-5">
        <x-kpi.stat-card label="Revenue" :value="'$'.number_format($totals['revenue'], 2)" :delta="$totals['deltas']['revenue']" />
        <x-kpi.stat-card label="Impressions" :value="number_format($totals['impressions'])" :delta="$totals['deltas']['impressions']" />
        <x-kpi.stat-card label="Clicks" :value="number_format($totals['clicks'])" />
        <x-kpi.stat-card label="Avg eCPM" :value="'$'.number_format($totals['avg_ecpm'], 2)" :delta="$totals['deltas']['avg_ecpm']" />
        <x-kpi.stat-card label="Avg CTR" :value="number_format($totals['avg_ctr'] * 100, 2).'%'" />
    </div>

    <div class="mt-6 grid gap-6 xl:grid-cols-3">
        <div class="rounded-xl border border-gray-200 bg-white p-5 shadow-sm xl:col-span-2">
            <h2 class="text-sm font-semibold text-gray-700">Revenue per day (USD)</h2>
            <x-charts.chart wire:key="rev-daily-{{ $this->filterSignature() }}" height="300"
                :categories="$series['categories']"
                :colors="['#22c55e']"
                :series="[['name' => 'Revenue', 'data' => $series['series']['revenue']]]" />
        </div>

        <div class="rounded-xl border border-gray-200 bg-white p-5 shadow-sm">
            <h2 class="text-sm font-semibold text-gray-700">Revenue by app</h2>
            <x-charts.donut wire:key="rev-byapp-{{ $this->filterSignature() }}" height="300"
                :labels="$byApp['labels']"
                :values="$byApp['values']"
                :colors="['#6366f1', '#22c55e', '#f59e0b', '#06b6d4', '#ef4444']" />
        </div>

        <div class="rounded-xl border border-gray-200 bg-white p-5 shadow-sm xl:col-span-3">
            <h2 class="text-sm font-semibold text-gray-700">eCPM trend (USD)</h2>
            <x-charts.chart wire:key="rev-ecpm-{{ $this->filterSignature() }}" type="line"
                :categories="$series['categories']"
                :colors="['#f59e0b']"
                :series="[['name' => 'eCPM', 'data' => $series['series']['ecpm']]]" />
        </div>
    </div>
</div>
