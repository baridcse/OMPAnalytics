<?php

namespace App\Integrations\AppStore;

use App\Contracts\Providers\MetricsProvider;
use App\Integrations\Data\AppPerformanceRow;
use App\Integrations\Support\AppStoreConnectClient;
use App\Integrations\Support\DateRange;
use App\Models\Integration;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Http;
use RuntimeException;

/**
 * Daily install counts from App Store Connect Sales & Trends reports
 * (gzipped TSV, one request per day) plus a current average-rating snapshot
 * from the public iTunes lookup endpoint (attached to the latest day only).
 *
 * Not available from Apple in this phase: uninstalls (never exposed),
 * active users and crash rate (async Analytics Reports API — Phase 3b).
 * ANR rate is Android-only.
 */
class AppStoreMetricsProvider implements MetricsProvider
{
    /**
     * Product Type Identifiers that count as first-time app downloads.
     */
    private const INSTALL_TYPES = ['1', '1F', '1T', 'F1', '1E', '1EP', '1EU'];

    public function __construct(private readonly AppStoreConnectClient $client) {}

    /**
     * @return iterable<AppPerformanceRow>
     */
    public function fetchAppPerformance(Integration $integration, DateRange $range): iterable
    {
        $listing = $integration->storeListing
            ?? throw new RuntimeException("Integration [{$integration->name}] has no store listing attached.");

        $vendorNumber = $integration->credential('vendor_number');
        if ($vendorNumber === null || $vendorNumber === '') {
            throw new RuntimeException(
                "App Store Connect integration [{$integration->name}] is missing the [vendor_number] credential (required for Sales & Trends reports)."
            );
        }

        // Daily reports appear the following day (~8am PT); never ask for today.
        $end = $range->end->copy()->min(Carbon::yesterday());
        $rating = $this->ratingSnapshot($listing->store_app_id);

        for ($day = $range->start->copy(); $day->lte($end); $day->addDay()) {
            $isLatestDay = $day->isSameDay($end);

            yield new AppPerformanceRow(
                date: $day->toDateString(),
                activeUsers: 0,
                installs: $this->installsFor($integration, $vendorNumber, $listing->store_app_id, $day),
                uninstalls: 0,
                ratingAvg: $isLatestDay ? $rating['avg'] : null,
                ratingCount: $isLatestDay ? $rating['count'] : 0,
            );
        }
    }

    private function installsFor(Integration $integration, string $vendorNumber, string $appleId, Carbon $day): int
    {
        $response = $this->client->get($integration, '/v1/salesReports', [
            'filter[frequency]' => 'DAILY',
            'filter[reportType]' => 'SALES',
            'filter[reportSubType]' => 'SUMMARY',
            'filter[vendorNumber]' => $vendorNumber,
            'filter[reportDate]' => $day->toDateString(),
            'filter[version]' => '1_1',
        ], headers: ['Accept' => 'application/a-gzip']);

        // Apple returns 404 for days with zero transactions — a real zero.
        if ($response->status() === 404) {
            return 0;
        }

        $response->throw();

        $tsv = gzdecode($response->body());
        if ($tsv === false) {
            throw new RuntimeException("Sales report for {$day->toDateString()} was not valid gzip data.");
        }

        return $this->sumInstallUnits($tsv, $appleId);
    }

    private function sumInstallUnits(string $tsv, string $appleId): int
    {
        $lines = array_filter(explode("\n", $tsv), fn (string $line) => trim($line) !== '');
        if (count($lines) < 2) {
            return 0;
        }

        $header = array_flip(str_getcsv(array_shift($lines), "\t", '"', '\\'));

        foreach (['Apple Identifier', 'Product Type Identifier', 'Units'] as $column) {
            if (! isset($header[$column])) {
                throw new RuntimeException("Sales report is missing the [{$column}] column.");
            }
        }

        $installs = 0;
        foreach ($lines as $line) {
            $row = str_getcsv($line, "\t", '"', '\\');

            if (($row[$header['Apple Identifier']] ?? null) !== $appleId) {
                continue;
            }

            if (in_array($row[$header['Product Type Identifier']] ?? '', self::INSTALL_TYPES, true)) {
                $installs += (int) ($row[$header['Units']] ?? 0);
            }
        }

        return $installs;
    }

    /**
     * @return array{avg: ?float, count: int}
     */
    private function ratingSnapshot(string $appleId): array
    {
        $response = Http::retry(2, 500, throw: false)->get('https://itunes.apple.com/lookup', [
            'id' => $appleId,
            'country' => config('integrations.providers.app_store.lookup_country', 'us'),
        ]);

        $result = $response->ok() ? ($response->json('results.0') ?? []) : [];

        return [
            'avg' => isset($result['averageUserRating']) ? round((float) $result['averageUserRating'], 2) : null,
            'count' => (int) ($result['userRatingCount'] ?? 0),
        ];
    }
}
