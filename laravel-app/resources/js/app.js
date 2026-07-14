import ApexCharts from 'apexcharts';

// ApexCharts is mounted through an Alpine component so Livewire can morph
// around it. Chart containers carry a filter-derived wire:key, so a filter
// change swaps the element and re-runs init() with fresh data.
document.addEventListener('alpine:init', () => {
    window.Alpine.data('apexChart', (options) => ({
        chart: null,
        init() {
            this.chart = new ApexCharts(this.$el, options);
            this.chart.render();
        },
        destroy() {
            this.chart?.destroy();
        },
    }));
});
