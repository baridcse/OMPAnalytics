<?php

use App\Models\App;
use App\Models\User;
use Database\Seeders\DemoDataSeeder;

beforeEach(function () {
    $this->seed(DemoDataSeeder::class);
    $this->actingAs(User::where('email', 'admin@example.com')->firstOrFail());
});

it('renders every dashboard page', function (string $route) {
    $this->get(route($route))->assertOk();
})->with([
    'dashboard',
    'performance',
    'revenue',
    'analytics',
    'reviews.index',
    'reviews.alerts',
    'leads',
    'settings.apps',
    'settings.integrations',
]);

it('renders the app detail page', function () {
    $app = App::firstOrFail();

    $this->get(route('apps.show', $app))->assertOk()->assertSee($app->name);
});

it('redirects guests to login', function () {
    auth()->logout();

    $this->get(route('dashboard'))->assertRedirect(route('login'));
});
