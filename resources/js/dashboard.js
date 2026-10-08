import Chart from "chart.js/auto";

Chart.defaults.font.family = 'Inter, "Segoe UI", sans-serif';
Chart.defaults.font.size = 12;

const payload = document.getElementById("qpos-dashboard-chart-data");

const reportPayload = document.getElementById("qpos-report-chart-data");
if (reportPayload) {
    const data = JSON.parse(reportPayload.textContent);
    const charts = [];
    const palette = () => {
        const style = getComputedStyle(document.documentElement);
        return { ink: style.getPropertyValue("--qpos-text").trim(), brand: style.getPropertyValue("--qpos-brand").trim(), line: style.getPropertyValue("--qpos-border").trim() };
    };
    document.querySelectorAll("[data-report-chart]").forEach(canvas => {
        const hourly = canvas.dataset.reportChart === "hours";
        const rows = hourly ? data.hours : data.daily;
        const c = palette();
        charts.push(new Chart(canvas, {
            type: hourly ? "bar" : "line",
            data: { labels: rows.map(r => hourly ? r.hour : r.label), datasets: [{ label: data.label + " (XAF)", data: rows.map(r => Number(hourly ? r.average : r.value)), backgroundColor: c.brand, borderColor: c.brand, borderWidth: 2 }] },
            options: { responsive: true, maintainAspectRatio: false, animation: false, plugins: { legend: { labels: { color: c.ink } } }, scales: { x: { ticks: { color: c.ink }, grid: { display: false } }, y: { beginAtZero: true, ticks: { color: c.ink }, grid: { color: c.line } } } },
        }));
    });
    new MutationObserver(() => {
        const c = palette();
        charts.forEach(chart => {
            chart.options.plugins.legend.labels.color = c.ink;
            chart.options.scales.x.ticks.color = c.ink;
            chart.options.scales.y.ticks.color = c.ink;
            chart.options.scales.y.grid.color = c.line;
            chart.data.datasets[0].backgroundColor = c.brand;
            chart.data.datasets[0].borderColor = c.brand;
            chart.update("none");
        });
    }).observe(document.documentElement, { attributes: true, attributeFilter: ["data-theme", "data-palette"] });
}

if (payload) {
    const { dates, dailySales, months, monthlySales, salesLabel } = JSON.parse(
        payload.textContent
    );
    const locale = window.qposLocale === "fr" ? "fr-FR" : "en-US";
    const formatAmount = (value) =>
        new Intl.NumberFormat(locale, {
            minimumFractionDigits: 2,
            maximumFractionDigits: 2,
        }).format(value);

    const charts = [];

    function palette() {
        const styles = getComputedStyle(document.documentElement);
        return {
            brand: styles.getPropertyValue("--qpos-brand").trim() || "#1e5f74",
            text: styles.getPropertyValue("--qpos-text").trim() || "#182230",
            border:
                styles.getPropertyValue("--qpos-border").trim() || "#e3e8ef",
        };
    }

    function createChart(elementId, type, labels, values) {
        const canvas = document.getElementById(elementId);
        if (!canvas) return;

        const colorsNow = palette();
        const chart = new Chart(canvas, {
            type,
            data: {
                labels,
                datasets: [
                    {
                        label: salesLabel,
                        data: values,
                        borderColor: colorsNow.brand,
                        backgroundColor:
                            type === "line"
                                ? `${colorsNow.brand}22`
                                : colorsNow.brand,
                        borderWidth: 2,
                        borderRadius: type === "bar" ? 6 : 0,
                        fill: type === "line",
                        tension: 0.3,
                        pointRadius: type === "line" ? 2 : 0,
                        pointHoverRadius: 5,
                    },
                ],
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                animation: false,
                locale,
                interaction: { intersect: false, mode: "index" },
                plugins: {
                    legend: {
                        labels: { color: colorsNow.text, usePointStyle: true },
                    },
                    tooltip: {
                        callbacks: {
                            label: (context) =>
                                `${context.dataset.label}: ${formatAmount(context.parsed.y)}`,
                        },
                    },
                },
                scales: {
                    x: {
                        grid: { display: false },
                        ticks: { color: colorsNow.text, maxRotation: 0 },
                    },
                    y: {
                        beginAtZero: true,
                        grid: { color: colorsNow.border },
                        ticks: {
                            color: colorsNow.text,
                            callback: (value) => formatAmount(value),
                        },
                    },
                },
            },
        });

        charts.push(chart);
    }

    createChart("dailySaleLineChart", "line", dates, dailySales);
    createChart("barChartYear", "bar", months, monthlySales);

    new MutationObserver(() => {
        const colors = palette();
        charts.forEach((chart) => {
            chart.options.plugins.legend.labels.color = colors.text;
            chart.options.scales.x.ticks.color = colors.text;
            chart.options.scales.y.ticks.color = colors.text;
            chart.options.scales.y.grid.color = colors.border;
            chart.data.datasets[0].borderColor = colors.brand;
            chart.data.datasets[0].backgroundColor =
                chart.config.type === "line" ? `${colors.brand}22` : colors.brand;
            chart.update("none");
        });
    }).observe(document.documentElement, {
        attributes: true,
        attributeFilter: ["data-theme", "data-palette"],
    });
}
