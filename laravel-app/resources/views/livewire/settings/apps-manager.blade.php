<div>
    <div class="mb-6">
        <h1 class="text-xl font-semibold text-gray-900">Apps</h1>
        <p class="mt-0.5 text-sm text-gray-500">Portfolio apps and their store listings</p>
    </div>

    <div class="grid gap-6 xl:grid-cols-3">
        <div class="space-y-4 xl:col-span-2">
            @foreach ($allApps as $portfolioApp)
                <div class="rounded-xl border border-gray-200 bg-white p-5 shadow-sm">
                    <div class="flex items-center justify-between">
                        <div class="flex items-center gap-3">
                            <div class="flex h-9 w-9 items-center justify-center rounded-lg bg-indigo-100 text-sm font-bold text-indigo-600">
                                {{ mb_substr($portfolioApp->name, 0, 1) }}
                            </div>
                            <div>
                                <a href="{{ route('apps.show', $portfolioApp) }}" class="font-medium text-gray-900 hover:text-indigo-600" wire:navigate>
                                    {{ $portfolioApp->name }}
                                </a>
                                <div class="text-xs text-gray-400">{{ $portfolioApp->store_listings_count }} listing(s)</div>
                            </div>
                        </div>
                        <button wire:click="toggleActive({{ $portfolioApp->id }})"
                                @class([
                                    'rounded-full px-3 py-1 text-xs font-medium',
                                    'bg-green-50 text-green-700' => $portfolioApp->is_active,
                                    'bg-gray-100 text-gray-500' => ! $portfolioApp->is_active,
                                ])>
                            {{ $portfolioApp->is_active ? 'Active' : 'Inactive' }}
                        </button>
                    </div>
                    @if ($portfolioApp->storeListings->isNotEmpty())
                        <ul class="mt-3 space-y-1 border-t border-gray-100 pt-3 text-sm text-gray-600">
                            @foreach ($portfolioApp->storeListings as $listing)
                                <li class="flex items-center gap-2">
                                    <span class="rounded bg-gray-100 px-1.5 py-0.5 text-xs font-medium text-gray-600">{{ $listing->platform->label() }}</span>
                                    <code class="text-xs">{{ $listing->store_app_id }}</code>
                                </li>
                            @endforeach
                        </ul>
                    @endif
                </div>
            @endforeach
        </div>

        <div class="space-y-6">
            <form wire:submit="createApp" class="rounded-xl border border-gray-200 bg-white p-5 shadow-sm">
                <h2 class="text-sm font-semibold text-gray-700">Add app</h2>
                <div class="mt-3">
                    <input type="text" wire:model="name" placeholder="App name"
                           class="w-full rounded-lg border-gray-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                    <x-input-error :messages="$errors->get('name')" class="mt-1" />
                </div>
                <button type="submit" class="mt-3 w-full rounded-lg bg-indigo-600 px-3 py-2 text-sm font-medium text-white hover:bg-indigo-500">
                    Create app
                </button>
            </form>

            <form wire:submit="addListing" class="rounded-xl border border-gray-200 bg-white p-5 shadow-sm">
                <h2 class="text-sm font-semibold text-gray-700">Add store listing</h2>
                <div class="mt-3 space-y-3">
                    <select wire:model="listingAppId" class="w-full rounded-lg border-gray-300 text-sm shadow-sm">
                        <option value="">Select app…</option>
                        @foreach ($allApps as $portfolioApp)
                            <option value="{{ $portfolioApp->id }}">{{ $portfolioApp->name }}</option>
                        @endforeach
                    </select>
                    <x-input-error :messages="$errors->get('listingAppId')" />
                    <select wire:model="listingPlatform" class="w-full rounded-lg border-gray-300 text-sm shadow-sm">
                        <option value="android">Google Play (Android)</option>
                        <option value="ios">App Store (iOS)</option>
                    </select>
                    <input type="text" wire:model="listingStoreAppId" placeholder="Package name or Apple app ID"
                           class="w-full rounded-lg border-gray-300 text-sm shadow-sm">
                    <x-input-error :messages="$errors->get('listingStoreAppId')" />
                </div>
                <button type="submit" class="mt-3 w-full rounded-lg bg-indigo-600 px-3 py-2 text-sm font-medium text-white hover:bg-indigo-500">
                    Add listing
                </button>
            </form>
        </div>
    </div>
</div>
