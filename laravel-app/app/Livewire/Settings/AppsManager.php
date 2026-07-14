<?php

namespace App\Livewire\Settings;

use App\Enums\Platform;
use App\Models\App;
use App\Models\StoreListing;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('layouts.app')]
#[Title('Apps')]
class AppsManager extends Component
{
    public string $name = '';

    public ?int $listingAppId = null;

    public string $listingPlatform = 'android';

    public string $listingStoreAppId = '';

    public function createApp(): void
    {
        $this->validate(['name' => ['required', 'string', 'max:120', Rule::unique('apps', 'name')]]);

        App::create([
            'name' => $this->name,
            'slug' => str($this->name)->slug(),
            'is_active' => true,
        ]);

        $this->reset('name');
    }

    public function addListing(): void
    {
        $this->validate([
            'listingAppId' => ['required', 'exists:apps,id'],
            'listingPlatform' => ['required', Rule::enum(Platform::class)],
            'listingStoreAppId' => [
                'required', 'string', 'max:190',
                Rule::unique('store_listings', 'store_app_id')->where('platform', $this->listingPlatform),
            ],
        ]);

        StoreListing::create([
            'app_id' => $this->listingAppId,
            'platform' => $this->listingPlatform,
            'store_app_id' => $this->listingStoreAppId,
        ]);

        $this->reset('listingAppId', 'listingStoreAppId');
    }

    public function toggleActive(int $appId): void
    {
        $app = App::findOrFail($appId);
        $app->update(['is_active' => ! $app->is_active]);
    }

    public function render()
    {
        return view('livewire.settings.apps-manager', [
            'allApps' => App::with('storeListings')->withCount('storeListings')->orderBy('name')->get(),
        ]);
    }
}
