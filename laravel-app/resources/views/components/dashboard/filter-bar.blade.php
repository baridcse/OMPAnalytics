@props(['apps', 'range', 'showApps' => true])

<div class="flex flex-wrap items-center gap-3">
    @if ($showApps)
        <select wire:model.live="app"
                class="rounded-lg border-gray-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
            <option value="">All apps</option>
            @foreach ($apps as $filterApp)
                <option value="{{ $filterApp->slug }}">{{ $filterApp->name }}</option>
            @endforeach
        </select>
    @endif

    <div class="inline-flex rounded-lg border border-gray-300 bg-white p-0.5 shadow-sm">
        @foreach (\App\Livewire\Concerns\HasDashboardFilters::$ranges as $value => $label)
            <button type="button" wire:click="$set('range', '{{ $value }}')"
                    @class([
                        'rounded-md px-3 py-1.5 text-sm font-medium transition',
                        'bg-indigo-600 text-white shadow' => $range === $value,
                        'text-gray-600 hover:text-gray-900' => $range !== $value,
                    ])>
                {{ $label }}
            </button>
        @endforeach
    </div>

    <div wire:loading class="text-xs text-gray-400">Updating…</div>
</div>
