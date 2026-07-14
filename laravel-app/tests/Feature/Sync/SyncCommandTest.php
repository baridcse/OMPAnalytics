<?php

use App\Jobs\SyncAdRevenueJob;
use App\Jobs\SyncAnalyticsJob;
use App\Jobs\SyncAppPerformanceJob;
use App\Jobs\SyncLeadsJob;
use App\Jobs\SyncReviewsJob;
use App\Models\Integration;
use Illuminate\Support\Facades\Queue;

beforeEach(function () {
    Queue::fake();
});

it('dispatches every supported capability job for enabled integrations', function () {
    Integration::factory()->create(['provider' => 'fake']);

    $this->artisan('integrations:sync')->assertSuccessful();

    Queue::assertPushed(SyncAppPerformanceJob::class, 1);
    Queue::assertPushed(SyncReviewsJob::class, 1);
    Queue::assertPushed(SyncAdRevenueJob::class, 1);
    Queue::assertPushed(SyncAnalyticsJob::class, 1);
    Queue::assertPushed(SyncLeadsJob::class, 1);
});

it('limits dispatch to one capability when given as an argument', function () {
    Integration::factory()->create(['provider' => 'fake']);

    $this->artisan('integrations:sync reviews')->assertSuccessful();

    Queue::assertPushed(SyncReviewsJob::class, 1);
    Queue::assertNotPushed(SyncAppPerformanceJob::class);
});

it('skips disabled integrations entirely', function () {
    Integration::factory()->create(['provider' => 'fake', 'is_enabled' => false]);

    $this->artisan('integrations:sync')->assertSuccessful();

    Queue::assertNothingPushed();
});

it('rejects an unknown capability', function () {
    $this->artisan('integrations:sync bogus')->assertFailed();
});
