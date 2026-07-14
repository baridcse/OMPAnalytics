<?php

use App\Enums\SyncStatus;
use App\Integrations\ProviderFactory;
use App\Jobs\SyncReviewsJob;
use App\Models\Integration;
use App\Models\Review;
use App\Models\StoreListing;
use Database\Seeders\RoleSeeder;
use Illuminate\Http\Client\RequestException;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Notification;

beforeEach(function () {
    $this->seed(RoleSeeder::class);
    Notification::fake();

    [$privatePem] = makeAscTestKeyPair();

    $this->integration = Integration::factory()->create([
        'provider' => 'app_store',
        'name' => 'ASC retry test',
        'store_listing_id' => StoreListing::factory()->ios()->create(['store_app_id' => '1500000001'])->id,
        'credentials' => [
            'issuer_id' => 'issuer-abc',
            'key_id' => 'KEY123',
            'private_key' => $privatePem,
        ],
    ]);
});

it('retries a 429 (honoring Retry-After) and then succeeds', function () {
    $page = appStoreFixture('customer_reviews_page2.json', [
        '{{DATE_THIRD}}' => Carbon::today()->subDays(2)->setTime(8, 0)->format('Y-m-d\TH:i:sP'),
        '{{DATE_OLDEST}}' => Carbon::today()->subDays(3)->setTime(7, 0)->format('Y-m-d\TH:i:sP'),
    ]);

    Http::fakeSequence()
        ->push('rate limited', 429, ['Retry-After' => '0'])
        ->push($page, 200);

    (new SyncReviewsJob($this->integration))->handle(app(ProviderFactory::class));

    expect(Review::count())->toBe(2)
        ->and($this->integration->refresh()->last_sync_status)->toBe(SyncStatus::Success);

    Http::assertSentCount(2);
});

it('records a failed sync when the rate limit persists', function () {
    Http::fakeSequence()
        ->push('rate limited', 429, ['Retry-After' => '0'])
        ->push('rate limited', 429, ['Retry-After' => '0'])
        ->push('rate limited', 429, ['Retry-After' => '0']);

    expect(fn () => (new SyncReviewsJob($this->integration))->handle(app(ProviderFactory::class)))
        ->toThrow(RequestException::class);

    expect($this->integration->refresh()->last_sync_status)->toBe(SyncStatus::Failed)
        ->and($this->integration->last_error)->not->toBeNull();

    Http::assertSentCount(3);
});
