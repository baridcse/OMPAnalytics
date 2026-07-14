<?php

namespace Database\Seeders;

use App\Enums\AlertReason;
use App\Enums\AlertStatus;
use App\Enums\Platform;
use App\Models\AdRevenueDaily;
use App\Models\AnalyticsDaily;
use App\Models\App;
use App\Models\AppPerformanceDaily;
use App\Models\Integration;
use App\Models\Lead;
use App\Models\Review;
use App\Models\ReviewAlert;
use App\Models\StoreListing;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;

/**
 * Seeds ~90 days of realistic portfolio data for four demo apps so every
 * dashboard renders without any live integration or credentials.
 */
class DemoDataSeeder extends Seeder
{
    private const DAYS = 90;

    public function run(): void
    {
        $this->call(RoleSeeder::class);
        $this->seedUsers();

        $apps = $this->seedApps();

        foreach ($apps as $app) {
            foreach ($app->storeListings as $listing) {
                $this->seedDailyMetrics($listing);
                $this->seedReviews($listing);
            }
            $this->seedLeads($app);
        }

        $this->seedAlerts();
        $this->seedIntegrations();
    }

    private function seedUsers(): void
    {
        $users = [
            ['name' => 'Demo Admin', 'email' => 'admin@example.com', 'role' => 'admin'],
            ['name' => 'Demo Analyst', 'email' => 'analyst@example.com', 'role' => 'analyst'],
            ['name' => 'Demo Responder', 'email' => 'responder@example.com', 'role' => 'reviews-responder'],
        ];

        foreach ($users as $data) {
            User::firstOrCreate(
                ['email' => $data['email']],
                ['name' => $data['name'], 'password' => 'password'],
            )->syncRoles([$data['role']]);
        }
    }

    /**
     * @return list<App>
     */
    private function seedApps(): array
    {
        $definitions = [
            ['name' => 'FitTrack Pro', 'platforms' => [Platform::Android, Platform::Ios]],
            ['name' => 'Star Puzzle', 'platforms' => [Platform::Android, Platform::Ios]],
            ['name' => 'Budget Buddy', 'platforms' => [Platform::Android]],
            ['name' => 'Sleep Sounds', 'platforms' => [Platform::Ios]],
        ];

        $apps = [];
        foreach ($definitions as $def) {
            $app = App::firstOrCreate(
                ['slug' => Str::slug($def['name'])],
                ['name' => $def['name'], 'is_active' => true],
            );

            foreach ($def['platforms'] as $platform) {
                $storeAppId = $platform === Platform::Android
                    ? 'com.demo.'.Str::slug($def['name'], '')
                    : (string) (1500000000 + $app->id);

                StoreListing::firstOrCreate(
                    ['platform' => $platform, 'store_app_id' => $storeAppId],
                    [
                        'app_id' => $app->id,
                        'bundle_id' => 'com.demo.'.Str::slug($def['name'], ''),
                        'developer_account' => 'demo-studio',
                    ],
                );
            }

            $apps[] = $app->load('storeListings');
        }

        return $apps;
    }

    /**
     * Generate coherent time-series: per-listing base level + gentle growth
     * trend + weekend seasonality + noise, so charts look like a real app.
     */
    private function seedDailyMetrics(StoreListing $listing): void
    {
        $seed = $listing->id;
        $baseUsers = 2000 + ($seed * 3500) % 40000;
        $growth = 1 + (($seed % 5) - 1) / 400;         // -0.25%..+0.75% daily
        $baseEcpm = 0.8 + ($seed % 7) * 0.55;

        $performance = $revenue = $analytics = [];
        $start = Carbon::today()->subDays(self::DAYS);

        for ($i = 0; $i < self::DAYS; $i++) {
            $date = $start->copy()->addDays($i)->toDateString();
            $weekend = in_array($start->copy()->addDays($i)->dayOfWeek, [0, 6]) ? 1.18 : 1.0;
            $noise = fn (float $spread = 0.12) => 1 + (mt_rand(-1000, 1000) / 1000) * $spread;
            $level = $baseUsers * ($growth ** $i) * $weekend;

            $activeUsers = (int) round($level * $noise());
            $installs = (int) round($level * 0.035 * $noise(0.3));
            $uninstalls = (int) round($installs * (0.25 + ($seed % 3) * 0.1) * $noise(0.3));
            $crashRate = round(max(0.0004, 0.004 + ($seed % 4) * 0.002 * $noise(0.5) - $i * 0.00001), 5);

            $performance[] = [
                'store_listing_id' => $listing->id,
                'date' => $date,
                'active_users' => $activeUsers,
                'installs' => $installs,
                'uninstalls' => $uninstalls,
                'crash_rate' => $crashRate,
                'anr_rate' => round($crashRate * 0.35, 5),
                'rating_avg' => round(min(4.9, max(3.0, 4.4 - ($seed % 3) * 0.3 + ($i / self::DAYS) * 0.2)), 2),
                'rating_count' => (int) round($installs * 0.02),
                'source' => 'demo',
                'created_at' => now(),
                'updated_at' => now(),
            ];

            $impressions = (int) round($activeUsers * (6 + $seed % 5) * $noise(0.2));
            $clicks = (int) round($impressions * 0.015 * $noise(0.4));
            $ecpm = round($baseEcpm * $weekend * $noise(0.25), 4);

            $revenue[] = [
                'store_listing_id' => $listing->id,
                'network' => 'admob',
                'date' => $date,
                'impressions' => $impressions,
                'clicks' => $clicks,
                'ctr' => $impressions > 0 ? round($clicks / $impressions, 5) : 0,
                'ecpm' => $ecpm,
                'estimated_revenue' => round($impressions / 1000 * $ecpm, 4),
                'currency' => 'USD',
                'created_at' => now(),
                'updated_at' => now(),
            ];

            $sessions = (int) round($activeUsers * 1.6 * $noise());

            $analytics[] = [
                'store_listing_id' => $listing->id,
                'date' => $date,
                'sessions' => $sessions,
                'total_users' => $activeUsers,
                'new_users' => (int) round($installs * 0.9),
                'engaged_sessions' => (int) round($sessions * 0.55 * $noise(0.15)),
                'avg_engagement_time' => round(120 + ($seed % 6) * 40 * $noise(0.3), 2),
                'conversions' => (int) round($sessions * 0.01 * $noise(0.5)),
                'created_at' => now(),
                'updated_at' => now(),
            ];
        }

        AppPerformanceDaily::upsert($performance, ['store_listing_id', 'date']);
        AdRevenueDaily::upsert($revenue, ['store_listing_id', 'network', 'date']);
        AnalyticsDaily::upsert($analytics, ['store_listing_id', 'date']);
    }

    private function seedReviews(StoreListing $listing): void
    {
        Review::factory()
            ->count(40)
            ->analyzed()
            ->for($listing)
            ->create();
    }

    private function seedLeads(App $app): void
    {
        Lead::factory()->count(150)->for($app)->create();
    }

    /**
     * Open alerts for recent bad reviews, mirroring what the Phase 2
     * evaluation job will produce for live data.
     */
    private function seedAlerts(): void
    {
        Review::query()
            ->where('rating', '<=', 2)
            ->whereDoesntHave('alert')
            ->where('review_created_at', '>=', now()->subDays(21))
            ->each(function (Review $review) {
                ReviewAlert::create([
                    'review_id' => $review->id,
                    'store_listing_id' => $review->store_listing_id,
                    'reason' => $review->sentiment_score !== null && $review->sentiment_score < 0
                        ? AlertReason::Both
                        : AlertReason::LowRating,
                    'severity' => $review->rating === 1 ? 'high' : 'medium',
                    'status' => AlertStatus::Open,
                ]);
            });
    }

    private function seedIntegrations(): void
    {
        StoreListing::all()->each(function (StoreListing $listing) {
            Integration::firstOrCreate(
                ['provider' => 'fake', 'store_listing_id' => $listing->id],
                [
                    'name' => "Demo data ({$listing->app->name} / {$listing->platform->label()})",
                    'is_enabled' => true,
                    'last_synced_at' => now()->subHours(2),
                ],
            );
        });
    }
}
