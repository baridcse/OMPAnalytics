<?php

use App\Livewire\Analytics\ProductAnalyticsDashboard;
use App\Livewire\Dashboard\OverviewKpi;
use App\Livewire\Leads\LeadsDashboard;
use App\Livewire\Performance\AppDetail;
use App\Livewire\Performance\PerformanceDashboard;
use App\Livewire\Reviews\BadReviewAlerts;
use App\Livewire\Reviews\ReviewsDashboard;
use App\Livewire\Revenue\AdRevenueDashboard;
use App\Livewire\Settings\AppsManager;
use App\Livewire\Settings\IntegrationsManager;
use Illuminate\Support\Facades\Route;

Route::redirect('/', '/dashboard');

Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('dashboard', OverviewKpi::class)->name('dashboard');
    Route::get('performance', PerformanceDashboard::class)->name('performance');
    Route::get('apps/{app:slug}', AppDetail::class)->name('apps.show');
    Route::get('revenue', AdRevenueDashboard::class)->name('revenue');
    Route::get('analytics', ProductAnalyticsDashboard::class)->name('analytics');
    Route::get('reviews', ReviewsDashboard::class)->name('reviews.index');
    Route::get('reviews/alerts', BadReviewAlerts::class)->name('reviews.alerts');
    Route::get('leads', LeadsDashboard::class)->name('leads');

    Route::prefix('settings')->name('settings.')->group(function () {
        Route::get('apps', AppsManager::class)->name('apps');
        Route::get('integrations', IntegrationsManager::class)->name('integrations');
    });
});

Route::view('profile', 'profile')
    ->middleware(['auth'])
    ->name('profile');

require __DIR__.'/auth.php';
