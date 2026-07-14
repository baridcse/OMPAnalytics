<?php

namespace App\Jobs;

use App\Enums\Capability;
use App\Enums\SyncStatus;
use App\Integrations\ProviderFactory;
use App\Models\Integration;
use App\Models\SyncRun;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\Middleware\WithoutOverlapping;
use Illuminate\Queue\SerializesModels;
use Throwable;

/**
 * Template for capability sync jobs: resolves the provider driver, streams
 * rows into an idempotent upsert, and records a SyncRun audit trail plus
 * last-sync state on the integration.
 */
abstract class AbstractSyncJob implements ShouldBeUnique, ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;

    public function __construct(public Integration $integration) {}

    abstract protected function capability(): Capability;

    /**
     * Pull rows from the provider and persist them. Returns records processed.
     */
    abstract protected function process(object $provider, SyncRun $run): int;

    public function handle(ProviderFactory $factory): void
    {
        if (! $this->integration->is_enabled) {
            return;
        }

        $run = SyncRun::create([
            'integration_id' => $this->integration->id,
            'provider' => $this->integration->provider,
            'capability' => $this->capability(),
            'started_at' => now(),
            'status' => SyncStatus::Running,
        ]);

        try {
            $provider = $factory->forCapability($this->integration, $this->capability());
            $count = $this->process($provider, $run);

            $run->update([
                'status' => SyncStatus::Success,
                'finished_at' => now(),
                'records_processed' => $count,
            ]);

            $this->integration->update([
                'last_synced_at' => now(),
                'last_sync_status' => SyncStatus::Success,
                'last_error' => null,
            ]);
        } catch (Throwable $e) {
            $run->update([
                'status' => SyncStatus::Failed,
                'finished_at' => now(),
                'error' => $e->getMessage(),
            ]);

            $this->integration->update([
                'last_sync_status' => SyncStatus::Failed,
                'last_error' => $e->getMessage(),
            ]);

            throw $e; // surface to the queue for retry/backoff
        }
    }

    /**
     * @return array<int, int>
     */
    public function backoff(): array
    {
        return [30, 120, 300];
    }

    public function uniqueId(): string
    {
        return $this->integration->id.':'.$this->capability()->value;
    }

    /**
     * @return array<int, object>
     */
    public function middleware(): array
    {
        return [
            (new WithoutOverlapping('sync:'.$this->uniqueId()))->releaseAfter(60),
        ];
    }
}
