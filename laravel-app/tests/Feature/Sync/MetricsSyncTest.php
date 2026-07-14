<?php

use App\Enums\SyncStatus;
use App\Integrations\ProviderFactory;
use App\Jobs\SyncAdRevenueJob;
use App\Jobs\SyncAnalyticsJob;
use App\Jobs\SyncAppPerformanceJob;
use App\Models\AdRevenueDaily;
use App\Models\AnalyticsDaily;
use App\Models\AppPerformanceDaily;
use App\Models\Integration;
use App\Models\StoreListing;

beforeEach(function () {
    config(['integrations.sync.metrics_window_days' => 10]);

    $this->integration = Integration::factory()->create([
        'provider' => 'fake',
        'store_listing_id' => StoreListing::factory()->create()->id,
    ]);
    $this->factory = app(ProviderFactory::class);
});

it('upserts one performance row per day in the window, idempotently', function () {
    $job = new SyncAppPerformanceJob($this->integration);

    $job->handle($this->factory);
    expect(AppPerformanceDaily::count())->toBe(10);

    // Regression: Carbon 3 signed diffs once produced negative metrics.
    expect(AppPerformanceDaily::where('installs', '<', 0)->orWhere('active_users', '<', 0)->count())->toBe(0);

    $before = AppPerformanceDaily::orderBy('date')->pluck('active_users')->all();

    $job->handle($this->factory);
    expect(AppPerformanceDaily::count())->toBe(10)
        ->and(AppPerformanceDaily::orderBy('date')->pluck('active_users')->all())->toBe($before);
});

it('syncs ad revenue and analytics idempotently', function () {
    (new SyncAdRevenueJob($this->integration))->handle($this->factory);
    (new SyncAnalyticsJob($this->integration))->handle($this->factory);
    (new SyncAdRevenueJob($this->integration))->handle($this->factory);
    (new SyncAnalyticsJob($this->integration))->handle($this->factory);

    expect(AdRevenueDaily::count())->toBe(10)
        ->and(AnalyticsDaily::count())->toBe(10);
});

it('skips disabled integrations', function () {
    $this->integration->update(['is_enabled' => false]);

    (new SyncAppPerformanceJob($this->integration->refresh()))->handle($this->factory);

    expect(AppPerformanceDaily::count())->toBe(0);
});

it('records failures on the sync run and integration', function () {
    config(['integrations.providers.fake.capabilities.metrics' => null]);

    $job = new SyncAppPerformanceJob($this->integration);

    expect(fn () => $job->handle($this->factory))->toThrow(InvalidArgumentException::class);

    expect($this->integration->refresh()->last_sync_status)->toBe(SyncStatus::Failed)
        ->and($this->integration->last_error)->toContain('does not support');
});
