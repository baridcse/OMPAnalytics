@props([
    'labels' => [],
    'values' => [],
    'height' => 260,
    'colors' => ['#22c55e', '#94a3b8', '#ef4444', '#6366f1', '#f59e0b'],
])

@php
    $options = [
        'chart' => ['type' => 'donut', 'height' => $height, 'fontFamily' => 'inherit'],
        'series' => array_map(fn ($v) => (float) $v, $values),
        'labels' => $labels,
        'colors' => $colors,
        'dataLabels' => ['enabled' => false],
        'legend' => ['position' => 'bottom'],
        'stroke' => ['width' => 0],
        'plotOptions' => ['pie' => ['donut' => ['size' => '72%']]],
    ];
@endphp

<div {{ $attributes }} x-data="apexChart({{ Js::from($options) }})" wire:ignore></div>
