@props([
    'type' => 'area',
    'series' => [],
    'categories' => [],
    'height' => 280,
    'colors' => ['#6366f1', '#22c55e', '#f59e0b', '#ef4444', '#06b6d4'],
])

@php
    $options = [
        'chart' => [
            'type' => $type,
            'height' => $height,
            'toolbar' => ['show' => false],
            'zoom' => ['enabled' => false],
            'fontFamily' => 'inherit',
        ],
        'series' => $series,
        'xaxis' => [
            'categories' => $categories,
            'type' => 'datetime',
            'labels' => ['datetimeUTC' => false],
        ],
        'colors' => $colors,
        'dataLabels' => ['enabled' => false],
        'stroke' => ['curve' => 'smooth', 'width' => 2],
        'fill' => $type === 'area'
            ? ['type' => 'gradient', 'gradient' => ['opacityFrom' => 0.35, 'opacityTo' => 0.02]]
            : [],
        'grid' => ['borderColor' => '#e5e7eb', 'strokeDashArray' => 3],
        'legend' => ['position' => 'top', 'horizontalAlign' => 'left'],
        'tooltip' => ['x' => ['format' => 'dd MMM yyyy']],
    ];
@endphp

<div {{ $attributes }} x-data="apexChart({{ Js::from($options) }})" wire:ignore></div>
