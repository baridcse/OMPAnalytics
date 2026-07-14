@props(['route', 'label', 'badge' => null])

@php
    $active = request()->routeIs($route) || request()->routeIs($route.'.*');
@endphp

<a href="{{ route($route) }}" wire:navigate
   @class([
       'flex items-center justify-between rounded-lg px-3 py-2 text-sm font-medium transition',
       'bg-gray-800 text-white' => $active,
       'text-gray-400 hover:bg-gray-800/60 hover:text-gray-100' => ! $active,
   ])>
    <span>{{ $label }}</span>
    @if ($badge)
        <span class="rounded-full bg-red-500/90 px-2 py-0.5 text-xs font-semibold text-white">{{ $badge }}</span>
    @endif
</a>
