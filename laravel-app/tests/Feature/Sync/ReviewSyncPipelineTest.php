<?php

use App\Enums\AlertStatus;
use App\Enums\SentimentLabel;
use App\Enums\SyncStatus;
use App\Integrations\ProviderFactory;
use App\Jobs\SyncReviewsJob;
use App\Models\Integration;
use App\Models\Review;
use App\Models\ReviewAlert;
use App\Models\StoreListing;
use App\Models\SyncRun;
use App\Models\User;
use App\Notifications\BadReviewAlertNotification;
use Database\Seeders\RoleSeeder;
use Illuminate\Support\Facades\Notification;

beforeEach(function () {
    $this->seed(RoleSeeder::class);

    $this->listing = StoreListing::factory()->create();
    $this->integration = Integration::factory()->create([
        'provider' => 'fake',
        'store_listing_id' => $this->listing->id,
    ]);
});

it('syncs reviews, analyzes sentiment, opens alerts and notifies responders', function () {
    Notification::fake();

    $responder = User::factory()->create();
    $responder->assignRole('reviews-responder');

    (new SyncReviewsJob($this->integration))->handle(app(ProviderFactory::class));

    // 5 fake reviews upserted and analyzed
    expect(Review::count())->toBe(5)
        ->and(Review::whereNull('analyzed_at')->count())->toBe(0);

    // The 1-star crash review and 2-star ads review must be negative + alerted
    $negative = Review::where('rating', '<=', 2)->get();
    expect($negative)->toHaveCount(2)
        ->and($negative->pluck('sentiment_label')->unique()->all())->toBe([SentimentLabel::Negative]);

    expect(ReviewAlert::count())->toBe(2)
        ->and(ReviewAlert::where('status', AlertStatus::Open)->count())->toBe(2);

    Notification::assertSentTo($responder, BadReviewAlertNotification::class, 2);

    // Audit trail + integration state recorded
    $run = SyncRun::firstOrFail();
    expect($run->status)->toBe(SyncStatus::Success)
        ->and($run->records_processed)->toBe(5)
        ->and($this->integration->refresh()->last_synced_at)->not->toBeNull()
        ->and($this->integration->last_sync_status)->toBe(SyncStatus::Success);
});

it('is idempotent: re-running the sync creates no duplicate rows or alerts', function () {
    Notification::fake();

    $factory = app(ProviderFactory::class);

    (new SyncReviewsJob($this->integration))->handle($factory);

    // Clear the cursor so the second run re-fetches the exact same rows.
    $this->integration->refresh()->update(['sync_cursor' => null]);

    (new SyncReviewsJob($this->integration->refresh()))->handle($factory);

    expect(Review::count())->toBe(5)
        ->and(ReviewAlert::count())->toBe(2)
        ->and(SyncRun::count())->toBe(2);
});

it('advances the sync cursor so already-seen reviews are skipped', function () {
    Notification::fake();

    $factory = app(ProviderFactory::class);

    (new SyncReviewsJob($this->integration))->handle($factory);

    $firstCursor = $this->integration->refresh()->sync_cursor['reviews_since'];

    $secondRun = new SyncReviewsJob($this->integration);
    $secondRun->handle($factory);

    expect(SyncRun::latest('id')->first()->records_processed)->toBe(0)
        ->and($this->integration->refresh()->sync_cursor['reviews_since'])->toBe($firstCursor);
});
