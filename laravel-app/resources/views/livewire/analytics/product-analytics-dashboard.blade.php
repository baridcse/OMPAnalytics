<div>
    <div class="mb-6 flex flex-wrap items-center justify-between gap-4">
        <div>
            <h1 class="text-xl font-semibold text-gray-900">Product analytics</h1>
            <p class="mt-0.5 text-sm text-gray-500">Sessions, users, engagement and conversions</p>
        </div>
        <x-dashboard.filter-bar :apps="$apps" :range="$range" />
    </div>

    <div class="grid grid-cols-2 gap-4 md:grid-cols-6">
        <x-kpi.stat-card label="Sessions" :value="number_format($totals['sessions'])" :delta="$totals['deltas']['sessions']" />
        <x-kpi.stat-card label="Avg daily users" :value="number_format($totals['total_users'])" />
        <x-kpi.stat-card label="New users" :value="number_format($totals['new_users'])" :delta="$totals['deltas']['new_users']" />
        <x-kpi.stat-card label="Engaged sessions" :value="number_format($totals['engaged_sessions'])" />
        <x-kpi.stat-card label="Avg engagement" :value="gmdate('i:s', (int) $totals['avg_engagement_time'])" hint="minutes:seconds" />
        <x-kpi.stat-card label="Conversions" :value="number_format($totals['conversions'])" :delta="$totals['deltas']['conversions']" />
    </div>

    <div class="mt-6 grid gap-6 xl:grid-cols-2">
        <div class="rounded-xl border border-gray-200 bg-white p-5 shadow-sm xl:col-span-2">
            <h2 class="text-sm font-semibold text-gray-700">Sessions & users</h2>
            <x-charts.chart wire:key="an-sessions-{{ $this->filterSignature() }}" height="300"
                :categories="$series['categories']"
                :series="[
                    ['name' => 'Sessions', 'data' => $series['series']['sessions']],
                    ['name' => 'Users', 'data' => $series['series']['total_users']],
                ]" />
        </div>

        <div class="rounded-xl border border-gray-200 bg-white p-5 shadow-sm">
            <h2 class="text-sm font-semibold text-gray-700">New users</h2>
            <x-charts.chart wire:key="an-new-{{ $this->filterSignature() }}" type="bar"
                :categories="$series['categories']"
                :colors="['#06b6d4']"
                :series="[['name' => 'New users', 'data' => $series['series']['new_users']]]" />
        </div>

        <div class="rounded-xl border border-gray-200 bg-white p-5 shadow-sm">
            <h2 class="text-sm font-semibold text-gray-700">Conversions</h2>
            <x-charts.chart wire:key="an-conv-{{ $this->filterSignature() }}" type="line"
                :categories="$series['categories']"
                :colors="['#a855f7']"
                :series="[['name' => 'Conversions', 'data' => $series['series']['conversions']]]" />
        </div>
    </div>
</div>
