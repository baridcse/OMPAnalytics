<div>
    <div class="mb-6 flex flex-wrap items-center justify-between gap-4">
        <div>
            <h1 class="text-xl font-semibold text-gray-900">Portfolio overview</h1>
            <p class="mt-0.5 text-sm text-gray-500">Cross-domain KPIs for {{ strtolower(static::$ranges[$range]) }}</p>
        </div>
        <x-dashboard.filter-bar :apps="$apps" :range="$range" />
    </div>

    <div class="grid grid-cols-2 gap-4 md:grid-cols-3 xl:grid-cols-6">
        <x-kpi.stat-card label="Installs" :value="number_format($kpis['performance']['installs'])"
                         :delta="$kpis['performance']['deltas']['installs']" />
        <x-kpi.stat-card label="Ad revenue" :value="'$'.number_format($kpis['revenue']['revenue'], 2)"
                         :delta="$kpis['revenue']['deltas']['revenue']" />
        <x-kpi.stat-card label="Sessions" :value="number_format($kpis['analytics']['sessions'])"
                         :delta="$kpis['analytics']['deltas']['sessions']" />
        <x-kpi.stat-card label="New leads" :value="number_format($kpis['leads']['total'])"
                         :delta="$kpis['leads']['deltas']['total']" />
        <x-kpi.stat-card label="Avg rating" :value="number_format($kpis['reviews']['avg_rating'], 2).' ★'"
                         :hint="number_format($kpis['reviews']['total']).' reviews'" />
        <x-kpi.stat-card label="Open alerts" :value="$kpis['open_alerts']"
                         hint="bad reviews to triage" />
    </div>

    <div class="mt-6 grid gap-6 xl:grid-cols-2">
        <div class="rounded-xl border border-gray-200 bg-white p-5 shadow-sm">
            <h2 class="text-sm font-semibold text-gray-700">Installs vs uninstalls</h2>
            <x-charts.chart wire:key="ov-installs-{{ $this->filterSignature() }}"
                :categories="$installsSeries['categories']"
                :series="[
                    ['name' => 'Installs', 'data' => $installsSeries['series']['installs']],
                    ['name' => 'Uninstalls', 'data' => $installsSeries['series']['uninstalls']],
                ]" />
        </div>

        <div class="rounded-xl border border-gray-200 bg-white p-5 shadow-sm">
            <h2 class="text-sm font-semibold text-gray-700">Ad revenue (USD)</h2>
            <x-charts.chart wire:key="ov-revenue-{{ $this->filterSignature() }}"
                :categories="$revenueSeries['categories']"
                :colors="['#22c55e']"
                :series="[['name' => 'Revenue', 'data' => $revenueSeries['series']['revenue']]]" />
        </div>

        <div class="rounded-xl border border-gray-200 bg-white p-5 shadow-sm">
            <h2 class="text-sm font-semibold text-gray-700">Review sentiment</h2>
            <x-charts.donut wire:key="ov-sentiment-{{ $this->filterSignature() }}"
                :labels="['Positive', 'Neutral', 'Negative']"
                :values="array_values($kpis['reviews']['sentiment'])" />
        </div>

        <div class="rounded-xl border border-gray-200 bg-white p-5 shadow-sm">
            <h2 class="text-sm font-semibold text-gray-700">Leads per day</h2>
            <x-charts.chart wire:key="ov-leads-{{ $this->filterSignature() }}" type="bar"
                :categories="$leadsSeries['categories']"
                :colors="['#6366f1']"
                :series="[['name' => 'Leads', 'data' => $leadsSeries['series']['count']]]" />
        </div>
    </div>
</div>
