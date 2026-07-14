<?php

use App\Enums\SyncStatus;
use App\Integrations\ProviderFactory;
use App\Jobs\SyncAppPerformanceJob;
use App\Models\AppPerformanceDaily;
use App\Models\Integration;
use App\Models\StoreListing;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Http;

/**
 * Full async Analytics Reports flow against fixtures: request lookup,
 * report resolution, daily instances (with restatement), segment download,
 * TSV aggregation, and the merge into app_performance_daily rows.
 */
beforeEach(function () {
    config(['integrations.sync.metrics_window_days' => 3]);

    [$privatePem] = makeAscTestKeyPair();

    $this->listing = StoreListing::factory()->ios()->create(['store_app_id' => '1500000001']);
    $this->integration = Integration::factory()->create([
        'provider' => 'app_store',
        'name' => 'ASC analytics test',
        'store_listing_id' => $this->listing->id,
        'credentials' => [
            'issuer_id' => 'issuer-abc',
            'key_id' => 'KEY123',
            'private_key' => $privatePem,
            'vendor_number' => '88888888',
        ],
    ]);

    $this->date1 = Carbon::today()->subDays(2)->toDateString();
    $this->date2 = Carbon::yesterday()->toDateString();
});

/**
 * Fakes the whole happy-path analytics stack: live ONGOING request, three
 * resolved reports, instances (sessions has a restated day), segments,
 * gzipped TSV downloads, plus sales + lookup.
 */
function fakeAscAnalyticsHappyPath(string $date1, string $date2): void
{
    $dates = ['{{DATE1}}' => $date1, '{{DATE2}}' => $date2];

    Http::fake(function (Request $request) use ($dates, $date1, $date2) {
        $url = $request->url();
        parse_str((string) parse_url($url, PHP_URL_QUERY), $query);

        if (str_contains($url, 'itunes.apple.com/lookup')) {
            return Http::response(appStoreFixture('itunes_lookup.json'), 200);
        }

        if (str_contains($url, '/v1/salesReports')) {
            return Http::response(gzencode(appStoreFixture('sales_summary.tsv', ['{{REPORT_DATE}}' => $date1])), 200);
        }

        // Request lookup by app: one live ONGOING request exists.
        if (str_contains($url, '/v1/apps/1500000001/analyticsReportRequests')) {
            return Http::response(json_encode(['data' => [[
                'type' => 'analyticsReportRequests',
                'id' => 'req-live',
                'attributes' => ['accessType' => 'ONGOING', 'stoppedDueToInactivity' => false],
            ]]]), 200);
        }

        // Cached-id verification on subsequent runs.
        if (str_contains($url, '/v1/analyticsReportRequests/req-live/reports')) {
            $reportId = match ($query['filter']['name'] ?? '') {
                'App Sessions Standard' => 'rep-sessions',
                'App Store Installation and Deletion Standard' => 'rep-deletions',
                'App Crashes' => 'rep-crashes',
                default => null,
            };

            return Http::response(json_encode(['data' => $reportId ? [[
                'type' => 'analyticsReports',
                'id' => $reportId,
                'attributes' => ['name' => $query['filter']['name'], 'category' => 'APP_USAGE'],
            ]] : []]), 200);
        }

        if (str_contains($url, '/v1/analyticsReportRequests/req-live')) {
            return Http::response(json_encode(['data' => [
                'type' => 'analyticsReportRequests',
                'id' => 'req-live',
                'attributes' => ['accessType' => 'ONGOING', 'stoppedDueToInactivity' => false],
            ]]), 200);
        }

        // Instances: sessions has an older instance restated by a newer one.
        if (str_contains($url, '/v1/analyticsReports/rep-sessions/instances')) {
            return Http::response(json_encode(['data' => [
                ['type' => 'analyticsReportInstances', 'id' => 'inst-s-new', 'attributes' => ['granularity' => 'DAILY', 'processingDate' => Carbon::today()->toDateString()]],
                ['type' => 'analyticsReportInstances', 'id' => 'inst-s-old', 'attributes' => ['granularity' => 'DAILY', 'processingDate' => $date2]],
            ]]), 200);
        }
        if (str_contains($url, '/v1/analyticsReports/rep-deletions/instances')) {
            return Http::response(json_encode(['data' => [
                ['type' => 'analyticsReportInstances', 'id' => 'inst-d', 'attributes' => ['granularity' => 'DAILY', 'processingDate' => Carbon::today()->toDateString()]],
            ]]), 200);
        }
        if (str_contains($url, '/v1/analyticsReports/rep-crashes/instances')) {
            return Http::response(json_encode(['data' => [
                ['type' => 'analyticsReportInstances', 'id' => 'inst-c', 'attributes' => ['granularity' => 'DAILY', 'processingDate' => Carbon::today()->toDateString()]],
            ]]), 200);
        }

        // Segments: each instance exposes one pre-signed download URL.
        if (preg_match('#/v1/analyticsReportInstances/(inst-[a-z-]+)/segments#', $url, $m)) {
            return Http::response(json_encode(['data' => [[
                'type' => 'analyticsReportSegments',
                'id' => 'seg-'.$m[1],
                'attributes' => ['url' => "https://reports.test/{$m[1]}.gz", 'checksum' => 'x', 'sizeInBytes' => 1],
            ]]]), 200);
        }

        // Pre-signed downloads (no auth header expected).
        if (str_contains($url, 'reports.test/inst-s-new.gz')) {
            return Http::response(gzencode(appStoreFixture('analytics_sessions.tsv', $dates)), 200);
        }
        if (str_contains($url, 'reports.test/inst-s-old.gz')) {
            return Http::response(gzencode(appStoreFixture('analytics_sessions_old.tsv', $dates)), 200);
        }
        if (str_contains($url, 'reports.test/inst-d.gz')) {
            return Http::response(gzencode(appStoreFixture('analytics_installs_deletions.tsv', $dates)), 200);
        }
        if (str_contains($url, 'reports.test/inst-c.gz')) {
            return Http::response(gzencode(appStoreFixture('analytics_crashes.tsv', $dates)), 200);
        }

        return Http::response('unexpected request: '.$url, 500);
    });
}

it('merges active users, deletions and crash rate from analytics reports', function () {
    fakeAscAnalyticsHappyPath($this->date1, $this->date2);

    (new SyncAppPerformanceJob($this->integration))->handle(app(ProviderFactory::class));

    expect(AppPerformanceDaily::count())->toBe(2);

    $day1 = AppPerformanceDaily::whereDate('date', $this->date1)->firstOrFail();
    // Latest instance restates date1: 6000+4000 uniques (foreign app excluded),
    // NOT the older instance's 8000.
    expect($day1->active_users)->toBe(10000)
        ->and($day1->uninstalls)->toBe(200)      // Delete events only: 120+80, Install row ignored
        ->and($day1->crash_rate)->toBe(0.003)    // 45 crashes / 15000 sessions
        ->and($day1->installs)->toBe(30);        // still from Sales & Trends

    $day2 = AppPerformanceDaily::whereDate('date', $this->date2)->firstOrFail();
    expect($day2->active_users)->toBe(9500)
        ->and($day2->uninstalls)->toBe(150)
        ->and($day2->crash_rate)->toBe(0.002)    // 28 / 14000
        ->and($day2->rating_avg)->toBe(4.53);

    // Request + report ids cached for future syncs.
    $state = $this->integration->refresh()->sync_cursor['analytics'];
    expect($state['request_id'])->toBe('req-live')
        ->and($state['report_ids'])->toHaveCount(3);
});

it('is idempotent and reuses the cached request id on the second run', function () {
    fakeAscAnalyticsHappyPath($this->date1, $this->date2);

    $factory = app(ProviderFactory::class);

    (new SyncAppPerformanceJob($this->integration))->handle($factory);
    (new SyncAppPerformanceJob($this->integration->refresh()))->handle($factory);

    expect(AppPerformanceDaily::count())->toBe(2)
        ->and(AppPerformanceDaily::whereDate('date', $this->date1)->first()->active_users)->toBe(10000)
        ->and($this->integration->refresh()->last_sync_status)->toBe(SyncStatus::Success);

    // Second run verifies the cached request instead of re-listing by app.
    Http::assertSent(fn (Request $request) => str_ends_with(
        (string) parse_url($request->url(), PHP_URL_PATH),
        '/v1/analyticsReportRequests/req-live'
    ));
});

it('recreates a request that was stopped due to inactivity', function () {
    $this->integration->update([
        'sync_cursor' => ['analytics' => ['request_id' => 'req-stopped', 'report_ids' => ['sessions' => 'x', 'installs_deletions' => 'y', 'crashes' => 'z']]],
    ]);

    Http::fake(function (Request $request) {
        $url = $request->url();

        if (str_contains($url, 'itunes.apple.com/lookup')) {
            return Http::response(appStoreFixture('itunes_lookup.json'), 200);
        }
        if (str_contains($url, '/v1/salesReports')) {
            return Http::response('no sales', 404);
        }
        if (str_contains($url, '/v1/analyticsReportRequests/req-stopped') && $request->method() === 'GET') {
            return Http::response(json_encode(['data' => [
                'type' => 'analyticsReportRequests', 'id' => 'req-stopped',
                'attributes' => ['accessType' => 'ONGOING', 'stoppedDueToInactivity' => true],
            ]]), 200);
        }
        if ($request->method() === 'DELETE') {
            return Http::response('', 204);
        }
        if (str_contains($url, '/v1/apps/1500000001/analyticsReportRequests')) {
            return Http::response(json_encode(['data' => []]), 200);
        }
        if (str_contains($url, '/v1/analyticsReportRequests') && $request->method() === 'POST') {
            return Http::response(json_encode(['data' => [
                'type' => 'analyticsReportRequests', 'id' => 'req-fresh',
                'attributes' => ['accessType' => 'ONGOING', 'stoppedDueToInactivity' => false],
            ]]), 201);
        }
        if (str_contains($url, '/v1/analyticsReportRequests/req-fresh/reports')) {
            return Http::response(json_encode(['data' => []]), 200); // not generated yet
        }

        return Http::response('unexpected request: '.$url, 500);
    });

    (new SyncAppPerformanceJob($this->integration->refresh()))->handle(app(ProviderFactory::class));

    Http::assertSent(fn (Request $request) => $request->method() === 'DELETE'
        && str_contains($request->url(), 'req-stopped'));
    Http::assertSent(fn (Request $request) => $request->method() === 'POST'
        && str_contains($request->url(), '/v1/analyticsReportRequests'));

    $state = $this->integration->refresh()->sync_cursor['analytics'];
    expect($state['request_id'])->toBe('req-fresh')
        ->and($state)->not->toHaveKey('report_ids')
        ->and($this->integration->last_sync_status)->toBe(SyncStatus::Success);
});
