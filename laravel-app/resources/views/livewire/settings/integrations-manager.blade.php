<div>
    <div class="mb-6">
        <h1 class="text-xl font-semibold text-gray-900">Integrations</h1>
        <p class="mt-0.5 text-sm text-gray-500">
            Data sources feeding the dashboards. Live providers (Google Play, App Store,
            AdMob, GA4) are wired one at a time — credentials stay in your <code>.env</code>.
        </p>
    </div>

    <div class="overflow-x-auto rounded-xl border border-gray-200 bg-white shadow-sm">
        <table class="min-w-full divide-y divide-gray-200 text-sm">
            <thead class="bg-gray-50 text-left text-xs font-semibold uppercase tracking-wide text-gray-500">
                <tr>
                    <th class="px-5 py-3">Integration</th>
                    <th class="px-5 py-3">Provider</th>
                    <th class="px-5 py-3">App</th>
                    <th class="px-5 py-3">Last sync</th>
                    <th class="px-5 py-3">Status</th>
                    <th class="px-5 py-3"></th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
                @forelse ($integrations as $integration)
                    <tr>
                        <td class="px-5 py-3 font-medium text-gray-900">{{ $integration->name }}</td>
                        <td class="px-5 py-3"><code class="rounded bg-gray-100 px-1.5 py-0.5 text-xs">{{ $integration->provider }}</code></td>
                        <td class="px-5 py-3 text-gray-600">{{ $integration->storeListing?->app?->name ?? '—' }}</td>
                        <td class="px-5 py-3 text-gray-500">{{ $integration->last_synced_at?->diffForHumans() ?? 'never' }}</td>
                        <td class="px-5 py-3">
                            @if ($integration->last_error)
                                <span class="rounded-full bg-red-50 px-2 py-0.5 text-xs font-medium text-red-700" title="{{ $integration->last_error }}">error</span>
                            @else
                                <span @class([
                                    'rounded-full px-2 py-0.5 text-xs font-medium',
                                    'bg-green-50 text-green-700' => $integration->is_enabled,
                                    'bg-gray-100 text-gray-500' => ! $integration->is_enabled,
                                ])>{{ $integration->is_enabled ? 'enabled' : 'disabled' }}</span>
                            @endif
                        </td>
                        <td class="px-5 py-3 text-right">
                            <div class="inline-flex gap-2">
                                @if ($integration->is_enabled)
                                    <button wire:click="syncNow({{ $integration->id }})"
                                            class="rounded-lg bg-indigo-600 px-3 py-1 text-xs font-medium text-white hover:bg-indigo-500">
                                        Sync now
                                    </button>
                                @endif
                                <button wire:click="toggleEnabled({{ $integration->id }})"
                                        class="rounded-lg border border-gray-300 px-3 py-1 text-xs font-medium text-gray-700 hover:bg-gray-50">
                                    {{ $integration->is_enabled ? 'Disable' : 'Enable' }}
                                </button>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="6" class="px-5 py-10 text-center text-gray-400">No integrations configured yet.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="mt-6 rounded-xl border border-gray-200 bg-white shadow-sm">
        <h2 class="border-b border-gray-200 px-5 py-4 text-sm font-semibold text-gray-700">Recent sync runs</h2>
        <ul class="divide-y divide-gray-100 text-sm">
            @forelse ($recentRuns as $run)
                <li class="flex items-center justify-between px-5 py-3">
                    <div>
                        <span class="font-medium text-gray-900">{{ $run->integration->name }}</span>
                        <span class="ml-2 text-xs text-gray-400">{{ $run->capability->value }} · {{ $run->started_at->diffForHumans() }}</span>
                    </div>
                    <div class="flex items-center gap-3">
                        <span class="text-xs text-gray-500">{{ number_format($run->records_processed) }} records</span>
                        <span @class([
                            'rounded-full px-2 py-0.5 text-xs font-medium',
                            'bg-green-50 text-green-700' => $run->status->value === 'success',
                            'bg-red-50 text-red-700' => $run->status->value === 'failed',
                            'bg-amber-50 text-amber-700' => $run->status->value === 'running',
                        ])>{{ $run->status->value }}</span>
                    </div>
                </li>
            @empty
                <li class="px-5 py-8 text-center text-gray-400">No sync runs yet — they appear once the sync engine runs.</li>
            @endforelse
        </ul>
    </div>
</div>
