<?php

use App\Livewire\Settings\IntegrationsManager;
use App\Models\Integration;
use App\Models\StoreListing;
use App\Models\User;
use Database\Seeders\RoleSeeder;
use Illuminate\Support\Facades\DB;
use Livewire\Livewire;

beforeEach(function () {
    $this->seed(RoleSeeder::class);
    $this->actingAs(User::factory()->create());
    $this->listing = StoreListing::factory()->ios()->create(['store_app_id' => '1500000001']);
});

it('creates an App Store Connect integration with encrypted credentials', function () {
    Livewire::test(IntegrationsManager::class)
        ->set('newProvider', 'app_store')
        ->set('newName', 'Sleep Sounds — App Store')
        ->set('newStoreListingId', $this->listing->id)
        ->set('newCredentials.issuer_id', 'issuer-abc')
        ->set('newCredentials.key_id', 'KEY123')
        ->set('newCredentials.private_key', 'env:ASC_PRIVATE_KEY')
        ->set('newCredentials.vendor_number', '88888888')
        ->call('createIntegration')
        ->assertHasNoErrors();

    $integration = Integration::where('provider', 'app_store')->firstOrFail();

    expect($integration->name)->toBe('Sleep Sounds — App Store')
        ->and($integration->is_enabled)->toBeTrue()
        ->and($integration->credential('issuer_id'))->toBe('issuer-abc')
        ->and($integration->credentials['private_key'])->toBe(['ref' => 'env', 'key' => 'ASC_PRIVATE_KEY']);

    // Encrypted at rest: the raw DB value must not contain the plaintext.
    $raw = DB::table('integrations')->where('id', $integration->id)->value('credentials');
    expect($raw)->not->toContain('issuer-abc')
        ->and($raw)->not->toContain('88888888');
});

it('never renders stored secrets back into the page', function () {
    Integration::factory()->create([
        'provider' => 'app_store',
        'name' => 'Existing ASC',
        'store_listing_id' => $this->listing->id,
        'credentials' => ['issuer_id' => 'super-secret-issuer', 'key_id' => 'SECRETKEY99'],
    ]);

    Livewire::test(IntegrationsManager::class)
        ->assertSee('Existing ASC')
        ->assertDontSee('super-secret-issuer')
        ->assertDontSee('SECRETKEY99');
});

it('drops blank credential fields and resets the form after creation', function () {
    $component = Livewire::test(IntegrationsManager::class)
        ->set('newProvider', 'app_store')
        ->set('newName', 'Partial creds')
        ->set('newStoreListingId', $this->listing->id)
        ->set('newCredentials.issuer_id', 'only-this-one')
        ->set('newCredentials.key_id', '   ')
        ->call('createIntegration')
        ->assertHasNoErrors();

    $integration = Integration::where('name', 'Partial creds')->firstOrFail();
    expect(array_keys($integration->credentials))->toBe(['issuer_id']);

    $component->assertSet('newProvider', '')
        ->assertSet('newName', '')
        ->assertSet('newCredentials', []);
});

it('validates provider and store listing', function () {
    Livewire::test(IntegrationsManager::class)
        ->set('newProvider', 'not-a-provider')
        ->set('newName', '')
        ->set('newStoreListingId', 999999)
        ->call('createIntegration')
        ->assertHasErrors(['newProvider', 'newName', 'newStoreListingId']);

    expect(Integration::count())->toBe(0);
});
