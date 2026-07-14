<div>
    <div class="mb-6 flex flex-wrap items-center justify-between gap-4">
        <div>
            <h1 class="text-xl font-semibold text-gray-900">Bad review alerts</h1>
            <p class="mt-0.5 text-sm text-gray-500">Triage low-rated and negative reviews</p>
        </div>

        <div class="inline-flex rounded-lg border border-gray-300 bg-white p-0.5 shadow-sm">
            @foreach (['open', 'acknowledged', 'resolved'] as $tab)
                <button type="button" wire:click="$set('status', '{{ $tab }}')"
                        @class([
                            'rounded-md px-3 py-1.5 text-sm font-medium capitalize transition',
                            'bg-indigo-600 text-white shadow' => $status === $tab,
                            'text-gray-600 hover:text-gray-900' => $status !== $tab,
                        ])>
                    {{ $tab }} ({{ $counts[$tab] }})
                </button>
            @endforeach
        </div>
    </div>

    <div class="space-y-4">
        @forelse ($alerts as $alert)
            <div class="rounded-xl border border-gray-200 bg-white p-5 shadow-sm">
                <div class="flex flex-wrap items-start justify-between gap-3">
                    <div class="min-w-0">
                        <div class="flex flex-wrap items-center gap-2 text-sm">
                            <span @class([
                                'rounded-full px-2 py-0.5 text-xs font-semibold uppercase tracking-wide',
                                'bg-red-100 text-red-700' => $alert->severity === 'high',
                                'bg-amber-100 text-amber-700' => $alert->severity === 'medium',
                                'bg-gray-100 text-gray-600' => $alert->severity === 'low',
                            ])>{{ $alert->severity }}</span>
                            <span class="text-xs text-gray-500">{{ $alert->reason->label() }}</span>
                            <span class="font-medium text-gray-900">{{ $alert->review->storeListing->app->name }}</span>
                            <span class="text-xs text-gray-400">{{ $alert->review->storeListing->platform->label() }} · {{ $alert->created_at->diffForHumans() }}</span>
                        </div>
                        <div class="mt-2 flex items-center gap-2 text-sm">
                            <span class="text-amber-500">{{ str_repeat('★', $alert->review->rating) }}{{ str_repeat('☆', 5 - $alert->review->rating) }}</span>
                            <span class="font-medium text-gray-700">{{ $alert->review->author_name ?? 'Anonymous' }}</span>
                        </div>
                        <p class="mt-1 text-sm text-gray-600">{{ $alert->review->body }}</p>
                        @if ($alert->assignee)
                            <p class="mt-2 text-xs text-gray-400">Assigned to {{ $alert->assignee->name }}</p>
                        @endif
                    </div>

                    <div class="flex shrink-0 gap-2">
                        @if ($alert->status->value === 'open')
                            <button wire:click="acknowledge({{ $alert->id }})"
                                    class="rounded-lg border border-gray-300 px-3 py-1.5 text-xs font-medium text-gray-700 hover:bg-gray-50">
                                Acknowledge
                            </button>
                        @endif
                        @if ($alert->status->value !== 'resolved')
                            @if (! $alert->assigned_to)
                                <button wire:click="assignToMe({{ $alert->id }})"
                                        class="rounded-lg border border-gray-300 px-3 py-1.5 text-xs font-medium text-gray-700 hover:bg-gray-50">
                                    Assign to me
                                </button>
                            @endif
                            <button wire:click="resolve({{ $alert->id }})"
                                    class="rounded-lg bg-indigo-600 px-3 py-1.5 text-xs font-medium text-white hover:bg-indigo-500">
                                Resolve
                            </button>
                        @endif
                    </div>
                </div>
            </div>
        @empty
            <div class="rounded-xl border border-dashed border-gray-300 bg-white px-5 py-14 text-center text-sm text-gray-400">
                No {{ $status }} alerts. 🎉
            </div>
        @endforelse
    </div>

    <div class="mt-4">
        {{ $alerts->links() }}
    </div>
</div>
