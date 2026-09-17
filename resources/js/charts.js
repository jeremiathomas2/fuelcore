import Chart from 'chart.js/auto';

const BASE = {
  color: '#718096',
  grid: 'rgba(229, 233, 239, 0.6)',
  font: 'Nunito Sans',
};

const CURRENCY = document.body.dataset.currency || '';

Chart.defaults.font.family = BASE.font;
Chart.defaults.color = BASE.color;

document.addEventListener('DOMContentLoaded', () => {
  /* Primary sales trend chart */
  const primary = document.getElementById('salesChart');
  if (primary && primary.dataset.labels) {
    const labels = JSON.parse(primary.dataset.labels);
    const values = JSON.parse(primary.dataset.values).map(Number);

    new Chart(primary, {
      type: 'line',
      data: {
        labels,
        datasets: [{
          label: 'Revenue',
          data: values,
          borderColor: '#2389C9',
          backgroundColor: (ctx) => {
            const { chart } = ctx;
            const { ctx: c, chartArea } = chart;
            if (!chartArea) return 'rgba(35,137,201,0.12)';
            const gradient = c.createLinearGradient(0, chartArea.top, 0, chartArea.bottom);
            gradient.addColorStop(0, 'rgba(35,137,201,0.28)');
            gradient.addColorStop(1, 'rgba(35,137,201,0)');
            return gradient;
          },
          fill: true,
          tension: 0.4,
          borderWidth: 3,
          pointRadius: 4,
          pointBackgroundColor: '#fff',
          pointBorderColor: '#2389C9',
          pointBorderWidth: 2,
        }],
      },
      options: {
        responsive: true,
        maintainAspectRatio: false,
        plugins: {
          legend: { display: false },
          tooltip: {
            callbacks: {
              label: (item) => ` ${CURRENCY} ${item.formattedValue}`.trimEnd(),
            },
          },
        },
        scales: {
          x: { grid: { color: BASE.grid }, border: { display: false } },
          y: {
            grid: { color: BASE.grid },
            border: { display: false },
            ticks: { callback: (v) => (v >= 1_000_000 ? (v / 1_000_000).toFixed(1) + 'M' : v >= 1_000 ? (v / 1_000).toFixed(0) + 'k' : v) },
          },
        },
      },
    });
  }

  /* Payment method donut */
  const donut = document.getElementById('paymentChart');
  if (donut && donut.dataset.labels) {
    new Chart(donut, {
      type: 'doughnut',
      data: {
        labels: JSON.parse(donut.dataset.labels),
        datasets: [{
          data: JSON.parse(donut.dataset.values).map(Number),
          backgroundColor: ['#2389C9', '#20B486', '#F2A93B', '#7C6FF0', '#E55353'],
          borderWidth: 0,
          hoverOffset: 6,
        }],
      },
      options: {
        responsive: true,
        maintainAspectRatio: false,
        cutout: '68%',
        plugins: { legend: { display: false } },
      },
    });
  }

  /* Top stations bar */
  const stations = document.getElementById('stationChart');
  if (stations && stations.dataset.labels) {
    new Chart(stations, {
      type: 'bar',
      data: {
        labels: JSON.parse(stations.dataset.labels),
        datasets: [{
          label: 'Today revenue',
          data: JSON.parse(stations.dataset.values).map(Number),
          backgroundColor: '#2389C9',
          borderRadius: 6,
          barThickness: 18,
        }],
      },
      options: {
        indexAxis: 'y',
        responsive: true,
        maintainAspectRatio: false,
        plugins: { legend: { display: false } },
        scales: {
          x: { grid: { color: BASE.grid }, border: { display: false }, ticks: { callback: (v) => (v >= 1_000_000 ? (v / 1_000_000).toFixed(1) + 'M' : v) } },
          y: { grid: { display: false }, border: { display: false } },
        },
      },
    });
  }
});