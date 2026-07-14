@props([
    'label',
    'value',
    'delta' => null,       // percent change vs previous period, null = n/a
    'invert' => false,     // true when a decrease is good (e.g. crash rate)
    'hint' => null,
])

@php
    $positive = $delta !== null && ($invert ? $delta < 0 : $delta > 0);
    $negative = $delta !== null && ($invert ? $delta > 0 : $delta < 0);
@endphp

<div {{ $attributes->merge(['class' => 'rounded-xl border border-gray-200 bg-white p-5 shadow-sm']) }}>
    <div class="text-sm font-medium text-gray-500">{{ $label }}</div>
    <div class="mt-2 flex items-baseline gap-2">
        <div class="text-2xl font-semibold tracking-tight text-gray-900">{{ $value }}</div>
        @if ($delta !== null)
            <span @class([
                'inline-flex items-center rounded-full px-2 py-0.5 text-xs font-medium',
                'bg-green-50 text-green-700' => $positive,
                'bg-red-50 text-red-700' => $negative,
                'bg-gray-100 text-gray-600' => ! $positive && ! $negative,
            ])>
                {{ $delta > 0 ? '+' : '' }}{{ $delta }}%
            </span>
        @endif
    </div>
    @if ($hint)
        <div class="mt-1 text-xs text-gray-400">{{ $hint }}</div>
    @endif
</div>
