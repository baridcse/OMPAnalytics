<?php

use App\Enums\SyncStatus;
use App\Integrations\ProviderFactory;
use App\Jobs\SyncAppPerformanceJob;
use App\Models\AppPerformanceDaily;
use App\Models\Integration;
use App\Models\StoreListing;
use App\Models\SyncRun;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Http;

beforeEach(function () {
    // Window of 3 ending today; the provider clamps to yesterday => 2 days.
    config(['integrations.sync.metrics_window_days' => 3]);

    [$privatePem] = makeAscTestKeyPair();

    $this->listing = StoreListing::factory()->ios()->create(['store_app_id' => '1500000001']);
    $this->integration = Integration::factory()->create([
        'provider' => 'app_store',
        'name' => 'ASC metrics test',
        'store_listing_id' => $this->listing->id,
        'credentials' => [
            'issuer_id' => 'issuer-abc',
            'key_id' => 'KEY123',
            'private_key' => $privatePem,
            'vendor_number' => '88888888',
        ],
    ]);

    $this->dayWithSales = Carbon::today()->subDays(2)->toDateString();
    $this->dayWithoutSales = Carbon::yesterday()->toDateString();

    Http::fake(function (Request $request) {
        $url = $request->url();

        if (str_contains($url, 'itunes.apple.com/lookup')) {
            return Http::response(appStoreFixture('itunes_lookup.json'), 200);
        }

        if (str_contains($url, '/v1/salesReports')) {
            if (($request['filter[reportDate]'] ?? null) === $this->dayWithoutSales) {
                // Apple's zero-transactions day: 404, not an error.
                return Http::response('{"errors":[{"detail":"There were no sales for the date specified."}]}', 404);
            }

            $tsv = appStoreFixture('sales_summary.tsv', ['{{REPORT_DATE}}' => $this->dayWithSales]);

            return Http::response(gzencode($tsv), 200, ['Content-Type' => 'application/a-gzip']);
        }

        // Analytics reports: newly-created ONGOING request, nothing
        // generated yet (the 24-48h pending window) -> zeros/null.
        if (str_contains($url, '/v1/apps/1500000001/analyticsReportRequests')) {
            return Http::response(json_encode(['data' => []]), 200);
        }
        if (str_contains($url, '/v1/analyticsReportRequests/req-new/reports')) {
            return Http::response(json_encode(['data' => []]), 200);
        }
        if (str_contains($url, '/v1/analyticsReportRequests/req-new')) {
            return Http::response(json_encode(['data' => [
                'type' => 'analyticsReportRequests', 'id' => 'req-new',
                'attributes' => ['accessType' => 'ONGOING', 'stoppedDueToInactivity' => false],
            ]]), 200);
        }
        if (str_contains($url, '/v1/analyticsReportRequests') && $request->method() === 'POST') {
            return Http::response(json_encode(['data' => [
                'type' => 'analyticsReportRequests', 'id' => 'req-new',
                'attributes' => ['accessType' => 'ONGOING', 'stoppedDueToInactivity' => false],
            ]]), 201);
        }

        return Http::response('unexpected request: '.$url, 500);
    });
});

it('upserts daily installs from the sales TSV with a rating snapshot on the latest day', function () {
    (new SyncAppPerformanceJob($this->integration))->handle(app(ProviderFactory::class));

    // Only days up to yesterday are requested.
    expect(AppPerformanceDaily::count())->toBe(2);

    // Apple returns 406 NOT_ACCEPTABLE without this header (live-verified).
    Http::assertSent(fn (Request $request) => str_contains($request->url(), '/v1/salesReports')
        && ($request->header('Accept')[0] ?? '') === 'application/a-gzip');

    // A fresh ONGOING analytics request was created and its id cached.
    Http::assertSent(fn (Request $request) => $request->method() === 'POST'
        && str_contains($request->url(), '/v1/analyticsReportRequests'));
    expect($this->integration->refresh()->sync_cursor['analytics']['request_id'])->toBe('req-new');

    $salesDay = AppPerformanceDaily::whereDate('date', $this->dayWithSales)->firstOrFail();
    // 1F (25) + 1T (5) count; 7F update rows and other apps' rows do not.
    expect($salesDay->installs)->toBe(30)
        ->and($salesDay->rating_avg)->toBeNull();

    $zeroDay = AppPerformanceDaily::whereDate('date', $this->dayWithoutSales)->firstOrFail();
    expect($zeroDay->installs)->toBe(0)
        ->and($zeroDay->rating_avg)->toBe(4.53)
        ->and($zeroDay->rating_count)->toBe(1234);

    expect($this->integration->refresh()->last_sync_status)->toBe(SyncStatus::Success);
});

it('is idempotent across repeated runs', function () {
    $factory = app(ProviderFactory::class);
    $job = new SyncAppPerformanceJob($this->integration);

    $job->handle($factory);
    $before = AppPerformanceDaily::orderBy('date')->pluck('installs')->all();

    $job->handle($factory);

    expect(AppPerformanceDaily::count())->toBe(2)
        ->and(AppPerformanceDaily::orderBy('date')->pluck('installs')->all())->toBe($before);
});

it('fails with an actionable error when vendor_number is missing', function () {
    $credentials = $this->integration->credentials;
    unset($credentials['vendor_number']);
    $this->integration->update(['credentials' => $credentials]);

    expect(fn () => (new SyncAppPerformanceJob($this->integration->refresh()))->handle(app(ProviderFactory::class)))
        ->toThrow(RuntimeException::class, 'vendor_number');

    expect(SyncRun::firstOrFail()->status)->toBe(SyncStatus::Failed)
        ->and($this->integration->refresh()->last_error)->toContain('vendor_number');
});
