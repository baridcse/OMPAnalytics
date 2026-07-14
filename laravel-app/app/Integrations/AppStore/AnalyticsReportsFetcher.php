<?php

namespace App\Integrations\AppStore;

use App\Integrations\Support\AppStoreConnectClient;
use App\Integrations\Support\DateRange;
use App\Models\Integration;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Drives the asynchronous App Store Connect Analytics Reports flow:
 * an ONGOING report request (created once per app, first data 24-48h later)
 * exposes reports, each report has daily instances, each instance has
 * downloadable gzipped TSV segments on short-lived pre-signed URLs.
 *
 * Instances restate earlier dates as data completes (up to ~5 days), and
 * Apple's rule is latest-processingDate wins — implemented here by walking
 * instances in ascending processingDate order and replacing per-date values.
 *
 * Numbers are approximate by design: analytics cover opted-in users only,
 * rows under a 5-user privacy threshold are omitted, and Apple adds noise.
 */
class AnalyticsReportsFetcher
{
    /**
     * Exact API report names (they differ from the doc page titles).
     *
     * @var array<string, string>
     */
    public const REPORTS = [
        'sessions' => 'App Sessions Standard',
        'installs_deletions' => 'App Store Installation and Deletion Standard',
        'crashes' => 'App Crashes',
    ];

    public function __construct(private readonly AppStoreConnectClient $client) {}

    /**
     * Build per-date metric maps for the range. Every stage degrades to
     * empty maps (rows fall back to 0/null) rather than failing the sync —
     * a freshly created ONGOING request has no data for 24-48 hours.
     *
     * @return array{active_users: array<string, int>, sessions: array<string, int>, deletions: array<string, int>, crashes: array<string, int>}
     */
    public function dailyMetrics(Integration $integration, DateRange $range): array
    {
        $empty = ['active_users' => [], 'sessions' => [], 'deletions' => [], 'crashes' => []];

        $state = $integration->sync_cursor['analytics'] ?? [];

        $requestId = $this->ensureReportRequest($integration, $state);
        if ($requestId === null) {
            $this->persistState($integration, $state);

            return $empty;
        }

        $reportIds = $this->resolveReportIds($integration, $requestId, $state);
        $this->persistState($integration, $state);

        if ($reportIds === []) {
            Log::info("ASC analytics reports not yet generated for [{$integration->name}] — first data arrives 24-48h after the report request.");

            return $empty;
        }

        $appleId = $integration->storeListing->store_app_id;
        $maps = $empty;

        foreach ($reportIds as $key => $reportId) {
            $byDate = $this->collectReport($integration, $reportId, $appleId, $range, $key);

            foreach ($byDate as $date => $values) {
                if ($key === 'sessions') {
                    $maps['active_users'][$date] = $values['unique_devices'];
                    $maps['sessions'][$date] = $values['sessions'];
                } elseif ($key === 'installs_deletions') {
                    $maps['deletions'][$date] = $values['deletions'];
                } else {
                    $maps['crashes'][$date] = $values['crashes'];
                }
            }
        }

        return $maps;
    }

    /**
     * Find a live ONGOING request (or create one). Returns null while no
     * usable request exists yet.
     *
     * @param  array<string, mixed>  $state
     */
    private function ensureReportRequest(Integration $integration, array &$state): ?string
    {
        $appleId = $integration->storeListing->store_app_id;

        if (isset($state['request_id'])) {
            $response = $this->client->get($integration, "/v1/analyticsReportRequests/{$state['request_id']}");

            if ($response->ok() && $response->json('data.attributes.stoppedDueToInactivity') === false) {
                return $state['request_id'];
            }

            // Stopped or gone: discard and start over.
            Log::warning("ASC analytics report request for [{$integration->name}] stopped or missing — recreating.");
            $this->client->delete($integration, "/v1/analyticsReportRequests/{$state['request_id']}");
            unset($state['request_id'], $state['report_ids']);
        }

        $list = $this->client->get($integration, "/v1/apps/{$appleId}/analyticsReportRequests", [
            'filter[accessType]' => 'ONGOING',
        ]);

        if ($list->ok()) {
            foreach ($list->json('data') ?? [] as $item) {
                if (($item['attributes']['stoppedDueToInactivity'] ?? true) === false) {
                    return $state['request_id'] = $item['id'];
                }
            }
        }

        $created = $this->client->post($integration, '/v1/analyticsReportRequests', [
            'data' => [
                'type' => 'analyticsReportRequests',
                'attributes' => ['accessType' => 'ONGOING'],
                'relationships' => [
                    'app' => ['data' => ['type' => 'apps', 'id' => $appleId]],
                ],
            ],
        ]);

        if ($created->status() === 201) {
            Log::info("Created ONGOING ASC analytics report request for [{$integration->name}] — first data in 24-48h.");

            return $state['request_id'] = $created->json('data.id');
        }

        // 409 = a request already exists (race); anything else degrades.
        if ($created->status() !== 409) {
            Log::warning("Could not create ASC analytics report request for [{$integration->name}]: HTTP {$created->status()}.");
        }

        return null;
    }

    /**
     * @param  array<string, mixed>  $state
     * @return array<string, string> report key => report id
     */
    private function resolveReportIds(Integration $integration, string $requestId, array &$state): array
    {
        if (isset($state['report_ids']) && count($state['report_ids']) === count(self::REPORTS)) {
            return $state['report_ids'];
        }

        $ids = [];
        foreach (self::REPORTS as $key => $name) {
            $response = $this->client->get($integration, "/v1/analyticsReportRequests/{$requestId}/reports", [
                'filter[name]' => $name,
            ]);

            $id = $response->ok() ? ($response->json('data.0.id') ?? null) : null;
            if ($id !== null) {
                $ids[$key] = $id;
            }
        }

        // Only cache once all three exist, so missing ones are retried.
        if (count($ids) === count(self::REPORTS)) {
            $state['report_ids'] = $ids;
        }

        return $ids;
    }

    /**
     * Walk DAILY instances in ascending processingDate order, replacing
     * per-date entries so the latest restatement wins.
     *
     * @return array<string, array<string, int>> date => summed metrics
     */
    private function collectReport(Integration $integration, string $reportId, string $appleId, DateRange $range, string $key): array
    {
        $response = $this->client->get($integration, "/v1/analyticsReports/{$reportId}/instances", [
            'filter[granularity]' => 'DAILY',
            'limit' => 200,
        ]);

        if (! $response->ok()) {
            return [];
        }

        $instances = collect($response->json('data') ?? [])
            ->filter(fn (array $i) => ($i['attributes']['processingDate'] ?? '') >= $range->start->toDateString())
            ->sortBy(fn (array $i) => $i['attributes']['processingDate'])
            ->values();

        $byDate = [];

        foreach ($instances as $instance) {
            foreach ($this->downloadSegments($integration, $instance['id']) as $tsv) {
                foreach ($this->sumByDate($tsv, $appleId, $range, $key) as $date => $values) {
                    $byDate[$date] = $values; // latest processingDate wins
                }
            }
        }

        return $byDate;
    }

    /**
     * @return iterable<string> decoded TSV contents
     */
    private function downloadSegments(Integration $integration, string $instanceId): iterable
    {
        $segments = $this->client->get($integration, "/v1/analyticsReportInstances/{$instanceId}/segments", [
            'limit' => 200,
        ]);

        foreach ($segments->json('data') ?? [] as $segment) {
            $url = $segment['attributes']['url'] ?? null;
            if ($url === null) {
                continue;
            }

            // Pre-signed S3 URL (expires in ~5 minutes): no bearer token.
            $download = Http::retry(2, 500, throw: false)->get($url);

            if (! $download->ok()) {
                Log::warning("ASC analytics segment download failed (HTTP {$download->status()}) — day will restate on the next sync.");

                continue;
            }

            $tsv = gzdecode($download->body());
            if ($tsv !== false) {
                yield $tsv;
            }
        }
    }

    /**
     * @return array<string, array<string, int>> date => summed metrics for this file
     */
    private function sumByDate(string $tsv, string $appleId, DateRange $range, string $key): array
    {
        $lines = array_values(array_filter(explode("\n", $tsv), fn (string $line) => trim($line) !== ''));
        if (count($lines) < 2) {
            return [];
        }

        $header = array_flip(str_getcsv(array_shift($lines), "\t", '"', '\\'));
        $column = fn (array $row, string $name): ?string => isset($header[$name]) ? ($row[$header[$name]] ?? null) : null;

        $out = [];

        foreach ($lines as $line) {
            $row = str_getcsv($line, "\t", '"', '\\');

            $date = $column($row, 'Date');
            if ($date === null
                || $date < $range->start->toDateString()
                || $date > $range->end->toDateString()
                || $column($row, 'App Apple Identifier') !== $appleId) {
                continue;
            }

            $out[$date] ??= ['unique_devices' => 0, 'sessions' => 0, 'deletions' => 0, 'crashes' => 0];

            if ($key === 'sessions') {
                $out[$date]['unique_devices'] += (int) $column($row, 'Unique Devices');
                $out[$date]['sessions'] += (int) $column($row, 'Sessions');
            } elseif ($key === 'installs_deletions') {
                if ($column($row, 'Event') === 'Delete') {
                    $out[$date]['deletions'] += (int) $column($row, 'Counts');
                }
            } else {
                $out[$date]['crashes'] += (int) $column($row, 'Crashes');
            }
        }

        return $out;
    }

    /**
     * @param  array<string, mixed>  $state
     */
    private function persistState(Integration $integration, array $state): void
    {
        $integration->update([
            'sync_cursor' => array_merge($integration->sync_cursor ?? [], ['analytics' => $state]),
        ]);
    }
}
