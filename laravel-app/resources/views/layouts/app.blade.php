<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <title>{{ config('app.name', 'OMP Analytics') }}</title>

        <!-- Fonts -->
        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=figtree:400,500,600,700&display=swap" rel="stylesheet" />

        <!-- Scripts -->
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="font-sans antialiased">
        <div class="min-h-screen bg-gray-50 lg:flex">
            <!-- Sidebar -->
            <aside class="hidden w-60 shrink-0 flex-col border-r border-gray-800 bg-gray-900 lg:flex">
                <div class="flex h-16 items-center gap-2 px-5">
                    <div class="flex h-8 w-8 items-center justify-center rounded-lg bg-indigo-500 text-sm font-bold text-white">O</div>
                    <span class="text-sm font-semibold tracking-wide text-white">OMP Analytics</span>
                </div>

                <nav class="mt-2 flex-1 space-y-0.5 px-3">
                    <x-sidebar-link route="dashboard" label="Overview" />
                    <x-sidebar-link route="performance" label="App Performance" />
                    <x-sidebar-link route="revenue" label="Ads Revenue" />
                    <x-sidebar-link route="analytics" label="Analytics" />
                    <x-sidebar-link route="reviews.index" label="Reviews" />
                    <x-sidebar-link route="reviews.alerts" label="Bad Review Alerts" :badge="\App\Models\ReviewAlert::open()->count()" />
                    <x-sidebar-link route="leads" label="Leads" />

                    <div class="px-3 pb-1 pt-5 text-xs font-semibold uppercase tracking-wider text-gray-500">Settings</div>
                    <x-sidebar-link route="settings.apps" label="Apps" />
                    <x-sidebar-link route="settings.integrations" label="Integrations" />
                </nav>

                <div class="border-t border-gray-800 p-4">
                    <div class="text-sm font-medium text-gray-200">{{ auth()->user()?->name }}</div>
                    <div class="mt-1 flex gap-3 text-xs text-gray-400">
                        <a href="{{ route('profile') }}" class="hover:text-white" wire:navigate>Profile</a>
                        <livewire:layout.logout-link />
                    </div>
                </div>
            </aside>

            <!-- Main column -->
            <div class="flex min-w-0 flex-1 flex-col">
                <!-- Mobile top bar -->
                <div class="flex h-14 items-center justify-between border-b border-gray-200 bg-white px-4 lg:hidden">
                    <span class="text-sm font-semibold">OMP Analytics</span>
                    <a href="{{ route('profile') }}" class="text-sm text-gray-500" wire:navigate>{{ auth()->user()?->name }}</a>
                </div>

                @if (isset($header))
                    <header class="border-b border-gray-200 bg-white">
                        <div class="px-6 py-5">{{ $header }}</div>
                    </header>
                @endif

                <main class="flex-1 p-6">
                    {{ $slot }}
                </main>
            </div>
        </div>
    </body>
</html>
