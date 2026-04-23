(function () {
  'use strict';

  const config = window.UTP_REPORTES || {};
  const apiUrl = config.apiUrl;
  const instances = [];

  if (!apiUrl || typeof Chart === 'undefined') {
    return;
  }

  const palette = {
    green: '#198754',
    blue: '#0d6efd',
    yellow: '#ffc107',
    red: '#dc3545',
    gray: '#6c757d',
    softGreen: 'rgba(25, 135, 84, 0.18)',
    softBlue: 'rgba(13, 110, 253, 0.18)',
    softYellow: 'rgba(255, 193, 7, 0.22)',
    softRed: 'rgba(220, 53, 69, 0.18)',
    softGray: 'rgba(108, 117, 125, 0.18)'
  };

  function toggleEmptyState(canvasId, isVisible) {
    const emptyState = document.getElementById('empty' + canvasId.charAt(0).toUpperCase() + canvasId.slice(1));
    const canvas = document.getElementById(canvasId);

    if (emptyState) {
      emptyState.classList.toggle('is-visible', !!isVisible);
    }

    if (canvas) {
      canvas.style.display = isVisible ? 'none' : 'block';
    }
  }

  function createChart(canvasId, definition) {
    const canvas = document.getElementById(canvasId);
    if (!canvas) {
      return;
    }

    const chart = new Chart(canvas, definition);
    instances.push(chart);
  }

  function hasAnyPositiveValue(values) {
    return values.some(function (value) {
      return Number(value || 0) > 0;
    });
  }

  function buildBarChart(labels, data, label, color) {
    return {
      type: 'bar',
      data: {
        labels: labels,
        datasets: [{
          label: label,
          data: data,
          backgroundColor: color,
          borderRadius: 10,
          maxBarThickness: 42
        }]
      },
      options: {
        responsive: true,
        maintainAspectRatio: false,
        animation: false,
        plugins: {
          legend: { display: false }
        },
        scales: {
          y: {
            beginAtZero: true,
            ticks: { precision: 0 }
          }
        }
      }
    };
  }

  function buildDoughnutChart(labels, data, colors) {
    return {
      type: 'doughnut',
      data: {
        labels: labels,
        datasets: [{
          data: data,
          backgroundColor: colors,
          borderWidth: 0
        }]
      },
      options: {
        responsive: true,
        maintainAspectRatio: false,
        animation: false,
        plugins: {
          legend: {
            position: 'bottom'
          }
        },
        cutout: '62%'
      }
    };
  }

  fetch(apiUrl, { credentials: 'same-origin' })
    .then(function (response) { return response.json(); })
    .then(function (payload) {
      if (!payload || !payload.ok || !payload.data) {
        return;
      }

      const data = payload.data;
      const approvedByMonth = data.approvedByMonth || [];
      const postulations = data.postulaciones || {};
      const employment = data.employmentSummary || {};
      const topCompanies = data.topCompanies || [];
      const laborMetrics = data.laborMetrics || {};

      const offersValues = approvedByMonth.map(function (item) { return item.value; });
      const postulationValues = [
        postulations.pendientes || 0,
        postulations.preseleccionadas || 0,
        postulations.contactadas || 0,
        postulations.rechazadas || 0,
        postulations.retiradas || 0
      ];
      const employmentValues = [
        employment.empleados || 0,
        employment.no_empleados || 0,
        employment.en_ti || 0,
        employment.fuera_ti || 0
      ];
      const companyValues = topCompanies.map(function (item) { return item.value; });
      const indicatorValues = [
        laborMetrics.salario_promedio_estimado || 0,
        laborMetrics.promedio_meses_laborando || 0
      ];

      if (hasAnyPositiveValue(offersValues)) {
        toggleEmptyState('chartOfertasLiberadas', false);
        createChart(
          'chartOfertasLiberadas',
          buildBarChart(
            approvedByMonth.map(function (item) { return item.label; }),
            offersValues,
            'Ofertas liberadas',
            palette.softGreen
          )
        );
      } else {
        toggleEmptyState('chartOfertasLiberadas', true);
      }

      if (hasAnyPositiveValue(postulationValues)) {
        toggleEmptyState('chartPostulaciones', false);
        createChart(
          'chartPostulaciones',
          buildDoughnutChart(
            ['Pendientes', 'Preseleccionadas', 'Contactadas', 'Rechazadas', 'Retiradas'],
            postulationValues,
            [palette.yellow, palette.blue, palette.green, palette.red, palette.gray]
          )
        );
      } else {
        toggleEmptyState('chartPostulaciones', true);
      }

      if (hasAnyPositiveValue(employmentValues)) {
        toggleEmptyState('chartSeguimiento', false);
        createChart(
          'chartSeguimiento',
          buildDoughnutChart(
            ['Empleados', 'Sin empleo', 'En TI', 'Fuera de TI'],
            employmentValues,
            [palette.green, palette.softRed, palette.blue, palette.softYellow]
          )
        );
      } else {
        toggleEmptyState('chartSeguimiento', true);
      }

      if (hasAnyPositiveValue(companyValues)) {
        toggleEmptyState('chartEmpresas', false);
        createChart(
          'chartEmpresas',
          buildBarChart(
            topCompanies.map(function (item) { return item.label; }),
            companyValues,
            'Egresados por empresa',
            palette.softBlue
          )
        );
      } else {
        toggleEmptyState('chartEmpresas', true);
      }

      if (hasAnyPositiveValue(indicatorValues)) {
        toggleEmptyState('chartIndicadores', false);
        createChart(
          'chartIndicadores',
          buildBarChart(
            ['Salario promedio estimado (MXN)', 'Promedio meses laborando'],
            indicatorValues,
            'Indicadores laborales',
            palette.softYellow
          )
        );
      } else {
        toggleEmptyState('chartIndicadores', true);
      }
    })
    .catch(function (error) {
      console.warn('[reportes]', error);
    });

  window.addEventListener('beforeunload', function () {
    instances.forEach(function (chart) {
      if (chart && typeof chart.destroy === 'function') {
        chart.destroy();
      }
    });
  });
})();