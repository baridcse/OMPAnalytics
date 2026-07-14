<?php

use App\Enums\Capability;
use App\Integrations\AppStore\AppStoreMetricsProvider;
use App\Integrations\AppStore\AppStoreReviewsProvider;
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
            'credential_fields' => [],
        ],

        'app_store' => [
            'label' => 'App Store Connect',
            'base_url' => 'https://api.appstoreconnect.apple.com',
            'lookup_country' => env('APP_STORE_LOOKUP_COUNTRY', 'us'),
            'capabilities' => [
                Capability::Metrics->value => AppStoreMetricsProvider::class,
                Capability::Reviews->value => AppStoreReviewsProvider::class,
            ],
            // Drives the write-only credential inputs on the add-integration
            // form. Key requires Admin, or App Manager + Sales and Reports.
            'credential_fields' => [
                'issuer_id' => 'Issuer ID',
                'key_id' => 'Key ID',
                'private_key' => 'Private key (.p8 contents, plain or base64)',
                'vendor_number' => 'Vendor number (Sales & Trends)',
            ],
        ],

        // 'google_play' => [...Phase 4...]
        // 'admob'       => [...Phase 5...]
        // 'ga4'         => [...Phase 5...]
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
