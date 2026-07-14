<?php

namespace App\Console\Commands;

use App\Enums\Capability;
use App\Integrations\ProviderFactory;
use App\Jobs\SyncAdRevenueJob;
use App\Jobs\SyncAnalyticsJob;
use App\Jobs\SyncAppPerformanceJob;
use App\Jobs\SyncLeadsJob;
use App\Jobs\SyncReviewsJob;
use App\Models\Integration;
use Illuminate\Console\Command;

class SyncIntegrationsCommand extends Command
{
    protected $signature = 'integrations:sync
                            {capability? : Limit to one capability (metrics|reviews|ad_revenue|analytics|leads)}
                            {--integration= : Limit to one integration id}';

    protected $description = 'Dispatch sync jobs for enabled integrations';

    /** @var array<string, class-string> */
    private const JOBS = [
        Capability::Metrics->value => SyncAppPerformanceJob::class,
        Capability::Reviews->value => SyncReviewsJob::class,
        Capability::AdRevenue->value => SyncAdRevenueJob::class,
        Capability::Analytics->value => SyncAnalyticsJob::class,
        Capability::Leads->value => SyncLeadsJob::class,
    ];

    public function handle(ProviderFactory $factory): int
    {
        $capabilityArg = $this->argument('capability');

        if ($capabilityArg !== null && Capability::tryFrom($capabilityArg) === null) {
            $this->error("Unknown capability [{$capabilityArg}]. Valid: ".implode('|', array_keys(self::JOBS)));

            return self::FAILURE;
        }

        $integrations = Integration::query()
            ->where('is_enabled', true)
            ->when($this->option('integration'), fn ($q, $id) => $q->whereKey($id))
            ->get();

        $dispatched = 0;

        foreach ($integrations as $integration) {
            foreach ($factory->capabilitiesFor($integration) as $capability) {
                if ($capabilityArg !== null && $capability->value !== $capabilityArg) {
                    continue;
                }

                $job = self::JOBS[$capability->value];
                $job::dispatch($integration);
                $dispatched++;

                $this->line("Dispatched {$job} for [{$integration->name}]");
            }
        }

        $this->info("Dispatched {$dispatched} sync job(s) for {$integrations->count()} integration(s).");

        return self::SUCCESS;
    }
}
