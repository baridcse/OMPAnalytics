<?php

namespace App\Livewire\Settings;

use App\Models\Integration;
use App\Models\SyncRun;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('layouts.app')]
#[Title('Integrations')]
class IntegrationsManager extends Component
{
    public function toggleEnabled(int $integrationId): void
    {
        $integration = Integration::findOrFail($integrationId);
        $integration->update(['is_enabled' => ! $integration->is_enabled]);
    }

    public function render()
    {
        return view('livewire.settings.integrations-manager', [
            'integrations' => Integration::with('storeListing.app')->orderBy('name')->get(),
            'recentRuns' => SyncRun::with('integration')->latest('started_at')->limit(10)->get(),
        ]);
    }
}
