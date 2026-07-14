<div>
    <div class="mb-6 flex flex-wrap items-center justify-between gap-4">
        <div>
            <h1 class="text-xl font-semibold text-gray-900">Leads</h1>
            <p class="mt-0.5 text-sm text-gray-500">Acquisition leads across sources</p>
        </div>
        <x-dashboard.filter-bar :apps="$apps" :range="$range" />
    </div>

    <div class="grid grid-cols-2 gap-4 md:grid-cols-4">
        <x-kpi.stat-card label="Total leads" :value="number_format($totals['total'])" :delta="$totals['deltas']['total']" />
        <x-kpi.stat-card label="Converted" :value="number_format($totals['converted'])" />
        <x-kpi.stat-card label="Conversion rate" :value="$totals['conversion_rate'].'%'" />
        <x-kpi.stat-card label="Pipeline value" :value="'$'.number_format($totals['pipeline_value'], 2)" />
    </div>

    <div class="mt-6 grid gap-6 xl:grid-cols-3">
        <div class="rounded-xl border border-gray-200 bg-white p-5 shadow-sm xl:col-span-2">
            <h2 class="text-sm font-semibold text-gray-700">Leads per day</h2>
            <x-charts.chart wire:key="ld-daily-{{ $this->filterSignature() }}" type="bar" height="300"
                :categories="$series['categories']"
                :colors="['#6366f1']"
                :series="[['name' => 'Leads', 'data' => $series['series']['count']]]" />
        </div>

        <div class="rounded-xl border border-gray-200 bg-white p-5 shadow-sm">
            <h2 class="text-sm font-semibold text-gray-700">By source</h2>
            <x-charts.donut wire:key="ld-src-{{ $this->filterSignature() }}" height="300"
                :labels="array_keys($totals['by_source'])"
                :values="array_values($totals['by_source'])"
                :colors="['#6366f1', '#22c55e', '#f59e0b', '#06b6d4']" />
        </div>
    </div>

    <div class="mt-6 overflow-x-auto rounded-xl border border-gray-200 bg-white shadow-sm">
        <table class="min-w-full divide-y divide-gray-200 text-sm">
            <thead class="bg-gray-50 text-left text-xs font-semibold uppercase tracking-wide text-gray-500">
                <tr>
                    <th class="px-5 py-3">Lead</th>
                    <th class="px-5 py-3">App</th>
                    <th class="px-5 py-3">Source / campaign</th>
                    <th class="px-5 py-3">Status</th>
                    <th class="px-5 py-3">Value</th>
                    <th class="px-5 py-3">Received</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
                @forelse ($latest as $lead)
                    <tr>
                        <td class="px-5 py-3">
                            <div class="font-medium text-gray-900">{{ $lead->name }}</div>
                            <div class="text-xs text-gray-400">{{ $lead->email }}</div>
                        </td>
                        <td class="px-5 py-3 text-gray-600">{{ $lead->app?->name ?? '—' }}</td>
                        <td class="px-5 py-3 text-gray-600">{{ $lead->source }} @if($lead->campaign)<span class="text-xs text-gray-400">/ {{ $lead->campaign }}</span>@endif</td>
                        <td class="px-5 py-3">
                            <span @class([
                                'rounded-full px-2 py-0.5 text-xs font-medium capitalize',
                                'bg-blue-50 text-blue-700' => $lead->status === 'new',
                                'bg-amber-50 text-amber-700' => in_array($lead->status, ['contacted', 'qualified']),
                                'bg-green-50 text-green-700' => $lead->status === 'converted',
                                'bg-gray-100 text-gray-500' => $lead->status === 'lost',
                            ])>{{ $lead->status }}</span>
                        </td>
                        <td class="px-5 py-3 text-gray-600">{{ $lead->value ? '$'.number_format($lead->value, 2) : '—' }}</td>
                        <td class="px-5 py-3 text-gray-500">{{ $lead->received_at->format('M j, Y') }}</td>
                    </tr>
                @empty
                    <tr><td colspan="6" class="px-5 py-10 text-center text-gray-400">No leads in this period.</td></tr>
                @endforelse
            </tbody>
        </table>
        <div class="border-t border-gray-200 px-5 py-3">
            {{ $latest->links() }}
        </div>
    </div>
</div>
