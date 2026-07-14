<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

/*
|--------------------------------------------------------------------------
| Integration sync schedule
|--------------------------------------------------------------------------
|
| Reviews sync hourly (store APIs only expose a short review window);
| daily metrics/revenue/analytics land staggered in the early morning
| after upstream reporting has settled. Leads pull every six hours.
|
*/

Schedule::command('integrations:sync reviews')->hourly()->withoutOverlapping();
Schedule::command('integrations:sync metrics')->dailyAt('05:00')->withoutOverlapping();
Schedule::command('integrations:sync ad_revenue')->dailyAt('05:20')->withoutOverlapping();
Schedule::command('integrations:sync analytics')->dailyAt('05:40')->withoutOverlapping();
Schedule::command('integrations:sync leads')->everySixHours()->withoutOverlapping();
