<div>
    <div class="mb-6 flex flex-wrap items-start justify-between gap-4">
        <div>
            <h1 class="text-xl font-semibold text-gray-900">Integrations</h1>
            <p class="mt-0.5 text-sm text-gray-500">
                Data sources feeding the dashboards. Credentials are stored encrypted and
                never displayed again; use <code>env:KEY_NAME</code> to reference a value
                from your <code>.env</code> instead.
            </p>
        </div>
        <button wire:click="$toggle('showCreateForm')"
                class="rounded-lg bg-indigo-600 px-4 py-2 text-sm font-medium text-white hover:bg-indigo-500">
            {{ $showCreateForm ? 'Close' : 'Add integration' }}
        </button>
    </div>

    @if ($showCreateForm)
        <form wire:submit="createIntegration" class="mb-6 rounded-xl border border-gray-200 bg-white p-5 shadow-sm">
            <h2 class="text-sm font-semibold text-gray-700">New integration</h2>

            <div class="mt-4 grid gap-4 md:grid-cols-3">
                <div>
                    <label class="text-xs font-medium uppercase tracking-wide text-gray-500">Provider</label>
                    <select wire:model.live="newProvider" class="mt-1 w-full rounded-lg border-gray-300 text-sm shadow-sm">
                        <option value="">Select provider…</option>
                        @foreach ($providers as $key => $provider)
                            <option value="{{ $key }}">{{ $provider['label'] }}</option>
                        @endforeach
                    </select>
                    <x-input-error :messages="$errors->get('newProvider')" class="mt-1" />
                </div>

                <div>
                    <label class="text-xs font-medium uppercase tracking-wide text-gray-500">Name</label>
                    <input type="text" wire:model="newName" placeholder="e.g. Sleep Sounds — App Store"
                           class="mt-1 w-full rounded-lg border-gray-300 text-sm shadow-sm">
                    <x-input-error :messages="$errors->get('newName')" class="mt-1" />
                </div>

                <div>
                    <label class="text-xs font-medium uppercase tracking-wide text-gray-500">Store listing</label>
                    <select wire:model="newStoreListingId" class="mt-1 w-full rounded-lg border-gray-300 text-sm shadow-sm">
                        <option value="">Select app listing…</option>
                        @foreach ($listings as $listing)
                            <option value="{{ $listing->id }}">{{ $listing->app->name }} · {{ $listing->platform->label() }}</option>
                        @endforeach
                    </select>
                    <x-input-error :messages="$errors->get('newStoreListingId')" class="mt-1" />
                </div>
            </div>

            @if ($newProvider !== '' && count($providers[$newProvider]['credential_fields'] ?? []) > 0)
                <div class="mt-4">
                    <h3 class="text-xs font-medium uppercase tracking-wide text-gray-500">
                        Credentials <span class="normal-case text-gray-400">(write-only; stored encrypted)</span>
                    </h3>
                    <div class="mt-2 grid gap-4 md:grid-cols-2">
                        @foreach ($providers[$newProvider]['credential_fields'] as $field => $label)
                            <div>
                                <label class="text-xs text-gray-500">{{ $label }}</label>
                                <input type="password" autocomplete="new-password"
                                       wire:model="newCredentials.{{ $field }}"
                                       placeholder="value or env:KEY_NAME"
                                       class="mt-1 w-full rounded-lg border-gray-300 text-sm shadow-sm">
                            </div>
                        @endforeach
                    </div>
                </div>
            @endif

            <div class="mt-4">
                <button type="submit" class="rounded-lg bg-indigo-600 px-4 py-2 text-sm font-medium text-white hover:bg-indigo-500">
                    Create integration
                </button>
            </div>
        </form>
    @endif

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
