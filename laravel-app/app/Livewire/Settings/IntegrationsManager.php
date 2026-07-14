<?php

namespace App\Livewire\Settings;

use App\Models\Integration;
use App\Models\StoreListing;
use App\Models\SyncRun;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('layouts.app')]
#[Title('Integrations')]
class IntegrationsManager extends Component
{
    public bool $showCreateForm = false;

    public string $newProvider = '';

    public string $newName = '';

    public ?int $newStoreListingId = null;

    /**
     * Write-only credential inputs keyed by field name. Values are stored
     * encrypted and are never repopulated into the form.
     *
     * @var array<string, string>
     */
    public array $newCredentials = [];

    public function updatedNewProvider(): void
    {
        $this->newCredentials = [];
    }

    public function createIntegration(): void
    {
        $providers = array_keys(config('integrations.providers', []));

        $this->validate([
            'newProvider' => ['required', Rule::in($providers)],
            'newName' => ['required', 'string', 'max:120'],
            'newStoreListingId' => ['required', 'exists:store_listings,id'],
        ], attributes: [
            'newProvider' => 'provider',
            'newName' => 'name',
            'newStoreListingId' => 'store listing',
        ]);

        Integration::create([
            'provider' => $this->newProvider,
            'name' => $this->newName,
            'store_listing_id' => $this->newStoreListingId,
            'is_enabled' => true,
            'credentials' => $this->prepareCredentials(),
        ]);

        $this->reset('showCreateForm', 'newProvider', 'newName', 'newStoreListingId', 'newCredentials');
    }

    /**
     * Blank fields are dropped; a value written as "env:KEY_NAME" is stored
     * as {"ref":"env","key":"KEY_NAME"} so the secret itself can live only
     * in the environment (resolved by Integration::credential()).
     *
     * @return array<string, mixed>|null
     */
    private function prepareCredentials(): ?array
    {
        $credentials = [];

        foreach ($this->newCredentials as $field => $value) {
            $value = trim((string) $value);

            if ($value === '') {
                continue;
            }

            $credentials[$field] = str_starts_with($value, 'env:')
                ? ['ref' => 'env', 'key' => substr($value, 4)]
                : $value;
        }

        return $credentials === [] ? null : $credentials;
    }

    public function toggleEnabled(int $integrationId): void
    {
        $integration = Integration::findOrFail($integrationId);
        $integration->update(['is_enabled' => ! $integration->is_enabled]);
    }

    public function syncNow(int $integrationId): void
    {
        Artisan::call('integrations:sync', ['--integration' => $integrationId]);

        $this->dispatch('sync-queued');
    }

    public function render()
    {
        $providers = collect(config('integrations.providers', []))
            ->map(fn (array $config) => [
                'label' => $config['label'] ?? 'Unknown',
                'credential_fields' => $config['credential_fields'] ?? [],
            ]);

        return view('livewire.settings.integrations-manager', [
            'integrations' => Integration::with('storeListing.app')->orderBy('name')->get(),
            'recentRuns' => SyncRun::with('integration')->latest('started_at')->limit(10)->get(),
            'providers' => $providers,
            'listings' => StoreListing::with('app')->get(),
        ]);
    }
}
