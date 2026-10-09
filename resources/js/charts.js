/* Dashboard charts (ApexCharts), loaded only on pages that have a [data-chart] element. */
import ApexCharts from 'apexcharts';

const GREEN = '#2f7d5b';
const BRASS = '#b08d57';

const isDark = () => document.documentElement.getAttribute('data-bs-theme') === 'dark';

const options = (config) => ({
    chart: {
        type: config.type,
        height: config.height ?? 280,
        toolbar: { show: false },
        background: 'transparent',
        fontFamily: 'inherit',
        animations: { enabled: !window.matchMedia('(prefers-reduced-motion: reduce)').matches },
    },
    theme: { mode: isDark() ? 'dark' : 'light' },
    colors: config.colors ?? [GREEN, BRASS],
    series: config.series,
    xaxis: { categories: config.labels, tickAmount: config.type === 'line' ? 6 : undefined, labels: { rotate: 0 } },
    yaxis: { min: 0, forceNiceScale: true, labels: { formatter: (value) => Math.round(value) } },
    stroke: { width: config.type === 'line' ? 2.5 : 0, curve: 'smooth' },
    plotOptions: { bar: { borderRadius: 3, columnWidth: '45%' } },
    dataLabels: { enabled: false },
    grid: { borderColor: isDark() ? '#26385a' : '#e4e4e7', strokeDashArray: 3 },
    legend: { position: 'top', horizontalAlign: 'left' },
    noData: { text: 'No data yet' },
});

export function initCharts() {
    const charts = [...document.querySelectorAll('[data-chart]')].map((element) => {
        const config = JSON.parse(element.dataset.chart);
        const chart = new ApexCharts(element, options(config));
        chart.render();

        return { chart, config };
    });

    // Follow the admin light/dark switch.
    document.addEventListener('changed.lte.color-mode', () => {
        charts.forEach(({ chart, config }) => chart.updateOptions(options(config)));
    });
}
