<?php

namespace App\Providers;

use App\Models\ReviewAlert;
use App\Observers\ReviewAlertObserver;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        ReviewAlert::observe(ReviewAlertObserver::class);
    }
}
