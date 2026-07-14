<div>
    <div class="mb-6 flex flex-wrap items-center justify-between gap-4">
        <div>
            <h1 class="text-xl font-semibold text-gray-900">Review analysis</h1>
            <p class="mt-0.5 text-sm text-gray-500">Store reviews with sentiment breakdown</p>
        </div>
        <x-dashboard.filter-bar :apps="$apps" :range="$range" />
    </div>

    <div class="grid grid-cols-2 gap-4 md:grid-cols-4">
        <x-kpi.stat-card label="Reviews" :value="number_format($totals['total'])" />
        <x-kpi.stat-card label="Avg rating" :value="number_format($totals['avg_rating'], 2).' ★'" />
        <x-kpi.stat-card label="Negative share" :value="$totals['negative_share'].'%'" hint="of analyzed reviews" />
        <x-kpi.stat-card label="Positive" :value="number_format($totals['sentiment']['positive'])" :hint="$totals['sentiment']['negative'].' negative / '.$totals['sentiment']['neutral'].' neutral'" />
    </div>

    <div class="mt-6 grid gap-6 xl:grid-cols-3">
        <div class="rounded-xl border border-gray-200 bg-white p-5 shadow-sm xl:col-span-2">
            <h2 class="text-sm font-semibold text-gray-700">Average rating trend</h2>
            <x-charts.chart wire:key="rv-trend-{{ $this->filterSignature() }}" type="line"
                :categories="$series['categories']"
                :colors="['#f59e0b', '#ef4444']"
                :series="[
                    ['name' => 'Avg rating', 'data' => $series['series']['avg_rating']],
                    ['name' => 'Negative reviews', 'data' => $series['series']['negative']],
                ]" />
        </div>

        <div class="rounded-xl border border-gray-200 bg-white p-5 shadow-sm">
            <h2 class="text-sm font-semibold text-gray-700">Sentiment split</h2>
            <x-charts.donut wire:key="rv-sent-{{ $this->filterSignature() }}"
                :labels="['Positive', 'Neutral', 'Negative']"
                :values="array_values($totals['sentiment'])" />
        </div>
    </div>

    <div class="mt-6 rounded-xl border border-gray-200 bg-white shadow-sm">
        <div class="flex items-center justify-between border-b border-gray-200 px-5 py-4">
            <h2 class="text-sm font-semibold text-gray-700">Latest reviews</h2>
            <select wire:model.live="sentiment" class="rounded-lg border-gray-300 text-sm shadow-sm">
                <option value="">All sentiment</option>
                <option value="positive">Positive</option>
                <option value="neutral">Neutral</option>
                <option value="negative">Negative</option>
            </select>
        </div>
        <ul class="divide-y divide-gray-100">
            @forelse ($reviews as $review)
                <li class="px-5 py-4">
                    <div class="flex flex-wrap items-center gap-2 text-sm">
                        <span class="font-medium text-gray-900">{{ $review->author_name ?? 'Anonymous' }}</span>
                        <span class="text-amber-500">{{ str_repeat('★', $review->rating) }}{{ str_repeat('☆', 5 - $review->rating) }}</span>
                        @if ($review->sentiment_label)
                            <span @class([
                                'rounded-full px-2 py-0.5 text-xs font-medium',
                                'bg-green-50 text-green-700' => $review->sentiment_label->value === 'positive',
                                'bg-gray-100 text-gray-600' => $review->sentiment_label->value === 'neutral',
                                'bg-red-50 text-red-700' => $review->sentiment_label->value === 'negative',
                            ])>{{ $review->sentiment_label->value }}</span>
                        @endif
                        <span class="text-xs text-gray-400">
                            {{ $review->storeListing->app->name }} · {{ $review->storeListing->platform->label() }} · {{ $review->review_created_at->diffForHumans() }}
                        </span>
                    </div>
                    <p class="mt-1 text-sm text-gray-600">{{ $review->body }}</p>
                </li>
            @empty
                <li class="px-5 py-10 text-center text-sm text-gray-400">No reviews in this period.</li>
            @endforelse
        </ul>
        <div class="border-t border-gray-200 px-5 py-3">
            {{ $reviews->links() }}
        </div>
    </div>
</div>
