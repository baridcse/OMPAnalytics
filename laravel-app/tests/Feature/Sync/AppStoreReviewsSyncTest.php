<?php

use App\Enums\SyncStatus;
use App\Integrations\ProviderFactory;
use App\Jobs\SyncReviewsJob;
use App\Models\Integration;
use App\Models\Review;
use App\Models\ReviewAlert;
use App\Models\StoreListing;
use App\Models\SyncRun;
use Database\Seeders\RoleSeeder;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Notification;

beforeEach(function () {
    $this->seed(RoleSeeder::class);
    Notification::fake();

    [$privatePem] = makeAscTestKeyPair();

    $this->listing = StoreListing::factory()->ios()->create(['store_app_id' => '1500000001']);
    $this->integration = Integration::factory()->create([
        'provider' => 'app_store',
        'name' => 'ASC reviews test',
        'store_listing_id' => $this->listing->id,
        'credentials' => [
            'issuer_id' => 'issuer-abc',
            'key_id' => 'KEY123',
            'private_key' => $privatePem,
            'vendor_number' => '88888888',
        ],
    ]);

    $this->dates = [
        'DATE_NEWEST' => Carbon::today()->setTime(10, 0)->format('Y-m-d\TH:i:sP'),
        'DATE_SECOND' => Carbon::today()->subDay()->setTime(9, 0)->format('Y-m-d\TH:i:sP'),
        'DATE_THIRD' => Carbon::today()->subDays(2)->setTime(8, 0)->format('Y-m-d\TH:i:sP'),
        'DATE_OLDEST' => Carbon::today()->subDays(3)->setTime(7, 0)->format('Y-m-d\TH:i:sP'),
    ];

    Http::fake(function (Request $request) {
        if (str_contains($request->url(), 'cursor=NEXTPAGE')) {
            return Http::response(appStoreFixture('customer_reviews_page2.json', ['{{DATE_THIRD}}' => $this->dates['DATE_THIRD'], '{{DATE_OLDEST}}' => $this->dates['DATE_OLDEST']]), 200);
        }

        if (str_contains($request->url(), 'customerReviews')) {
            return Http::response(appStoreFixture('customer_reviews_page1.json', ['{{DATE_NEWEST}}' => $this->dates['DATE_NEWEST'], '{{DATE_SECOND}}' => $this->dates['DATE_SECOND']]), 200);
        }

        return Http::response('unexpected request: '.$request->url(), 500);
    });
});

it('syncs paginated ASC reviews with correct field mapping and auth', function () {
    (new SyncReviewsJob($this->integration))->handle(app(ProviderFactory::class));

    expect(Review::count())->toBe(4);

    $crash = Review::where('external_review_id', 'asc-review-0002')->firstOrFail();
    expect($crash->rating)->toBe(1)
        ->and($crash->author_name)->toBe('AngryUser')
        ->and($crash->title)->toBe('Crashes')
        ->and($crash->country)->toBe('GBR')
        ->and($crash->review_created_at->toDateTimeString())
        ->toBe(Carbon::parse($this->dates['DATE_SECOND'])->utc()->toDateTimeString());

    Http::assertSent(function (Request $request) {
        return str_contains($request->url(), '/v1/apps/1500000001/customerReviews')
            && str_starts_with($request->header('Authorization')[0] ?? '', 'Bearer ')
            && ($request['sort'] ?? null) === '-createdDate'
            && (int) ($request['limit'] ?? 0) === 200;
    });

    // Sentiment + alert pipeline ran on the synced reviews.
    expect(Review::whereNull('analyzed_at')->count())->toBe(0)
        ->and(ReviewAlert::count())->toBeGreaterThanOrEqual(1)
        ->and($this->integration->refresh()->last_sync_status)->toBe(SyncStatus::Success);
});

it('advances the cursor and stops pagination early on the next run', function () {
    $factory = app(ProviderFactory::class);

    (new SyncReviewsJob($this->integration))->handle($factory);

    $cursor = $this->integration->refresh()->sync_cursor['reviews_since'];
    expect($cursor)->toBe(Carbon::parse($this->dates['DATE_NEWEST'])->utc()->toDateTimeString());

    // Second run: newest fixture item <= cursor, so only page 1 is fetched
    // and nothing is processed.
    (new SyncReviewsJob($this->integration))->handle($factory);

    expect(SyncRun::latest('id')->first()->records_processed)->toBe(0)
        ->and(Review::count())->toBe(4);

    Http::assertSentCount(3); // 2 pages on run one + 1 page on run two
});

it('re-syncs idempotently when the cursor is cleared', function () {
    $factory = app(ProviderFactory::class);

    (new SyncReviewsJob($this->integration))->handle($factory);
    $this->integration->refresh()->update(['sync_cursor' => null]);
    (new SyncReviewsJob($this->integration->refresh()))->handle($factory);

    expect(Review::count())->toBe(4)
        ->and(SyncRun::count())->toBe(2);
});
