<?php

use App\Enums\Capability;
use App\Integrations\Testing\FakeAdRevenueProvider;
use App\Integrations\Testing\FakeAnalyticsProvider;
use App\Integrations\Testing\FakeLeadsProvider;
use App\Integrations\Testing\FakeMetricsProvider;
use App\Integrations\Testing\FakeReviewsProvider;

return [

    /*
    |--------------------------------------------------------------------------
    | Provider capability map
    |--------------------------------------------------------------------------
    |
    | Maps each integration provider key to the concrete driver class per
    | capability. Live providers (google_play, app_store, admob, ga4) are
    | added here as they are wired in; the ProviderFactory resolves from
    | this map at sync time.
    |
    */

    'providers' => [

        'fake' => [
            'label' => 'Demo data',
            'capabilities' => [
                Capability::Metrics->value => FakeMetricsProvider::class,
                Capability::Reviews->value => FakeReviewsProvider::class,
                Capability::AdRevenue->value => FakeAdRevenueProvider::class,
                Capability::Analytics->value => FakeAnalyticsProvider::class,
                Capability::Leads->value => FakeLeadsProvider::class,
            ],
        ],

        // 'google_play' => [...Phase 3...]
        // 'admob'       => [...Phase 5...]
        // 'ga4'         => [...Phase 5...]
        // 'app_store'   => [...Phase 6...]
    ],

    /*
    |--------------------------------------------------------------------------
    | Sync defaults
    |--------------------------------------------------------------------------
    */

    'sync' => [
        'metrics_window_days' => (int) env('SYNC_METRICS_WINDOW_DAYS', 30),
        'reviews_lookback_days' => (int) env('SYNC_REVIEWS_LOOKBACK_DAYS', 7),
    ],

];
