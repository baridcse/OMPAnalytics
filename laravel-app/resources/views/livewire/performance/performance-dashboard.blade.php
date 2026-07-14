<div>
    <div class="mb-6 flex flex-wrap items-center justify-between gap-4">
        <div>
            <h1 class="text-xl font-semibold text-gray-900">App performance</h1>
            <p class="mt-0.5 text-sm text-gray-500">Installs, usage, stability and ratings</p>
        </div>
        <x-dashboard.filter-bar :apps="$apps" :range="$range" />
    </div>

    <div class="grid grid-cols-2 gap-4 md:grid-cols-5">
        <x-kpi.stat-card label="Installs" :value="number_format($totals['installs'])" :delta="$totals['deltas']['installs']" />
        <x-kpi.stat-card label="Uninstalls" :value="number_format($totals['uninstalls'])" />
        <x-kpi.stat-card label="Avg daily active users" :value="number_format($totals['avg_active_users'])" :delta="$totals['deltas']['avg_active_users']" />
        <x-kpi.stat-card label="Avg crash rate" :value="number_format($totals['avg_crash_rate'] * 100, 2).'%'" :delta="$totals['deltas']['avg_crash_rate']" :invert="true" />
        <x-kpi.stat-card label="Avg rating" :value="number_format($totals['avg_rating'], 2).' ★'" />
    </div>

    <div class="mt-6 grid gap-6 xl:grid-cols-2">
        <div class="rounded-xl border border-gray-200 bg-white p-5 shadow-sm xl:col-span-2">
            <h2 class="text-sm font-semibold text-gray-700">Daily active users</h2>
            <x-charts.chart wire:key="perf-dau-{{ $this->filterSignature() }}" height="300"
                :categories="$series['categories']"
                :series="[['name' => 'Active users', 'data' => $series['series']['active_users']]]" />
        </div>

        <div class="rounded-xl border border-gray-200 bg-white p-5 shadow-sm">
            <h2 class="text-sm font-semibold text-gray-700">Installs vs uninstalls</h2>
            <x-charts.chart wire:key="perf-inst-{{ $this->filterSignature() }}"
                :categories="$series['categories']"
                :series="[
                    ['name' => 'Installs', 'data' => $series['series']['installs']],
                    ['name' => 'Uninstalls', 'data' => $series['series']['uninstalls']],
                ]" />
        </div>

        <div class="rounded-xl border border-gray-200 bg-white p-5 shadow-sm">
            <h2 class="text-sm font-semibold text-gray-700">Crash rate (%)</h2>
            <x-charts.chart wire:key="perf-crash-{{ $this->filterSignature() }}" type="line"
                :categories="$series['categories']"
                :colors="['#ef4444']"
                :series="[['name' => 'Crash rate', 'data' => collect($series['series']['crash_rate'])->map(fn ($v) => round($v * 100, 3))->all()]]" />
        </div>
    </div>
</div>
