(function () {
  'use strict';

  var charts = {};
  var initialized = false;

  var colors = {
    blue: '#2d7dd2',
    teal: '#0ab5a6',
    blueLight: 'rgba(45, 125, 210, 0.15)',
    tealLight: 'rgba(10, 181, 166, 0.2)',
    pie: ['#2d7dd2', '#0ab5a6', '#f4a261', '#7c3aed', '#22c55e'],
  };

  function currentLang() {
    return (window.FINKID_I18N && window.FINKID_I18N.lang) || 'ru';
  }

  function readByLang(id) {
    var el = document.getElementById(id);
    if (!el) return null;
    try {
      var raw = JSON.parse(el.textContent || '{}');
      if (raw.byLang) {
        var lang = currentLang();
        return raw.byLang[lang] || raw.byLang.ru || null;
      }
      return raw;
    } catch (e) {
      return null;
    }
  }

  function readData() {
    return readByLang('admin-charts-data');
  }

  function destroyCharts() {
    Object.keys(charts).forEach(function (key) {
      if (charts[key]) {
        charts[key].destroy();
        charts[key] = null;
      }
    });
    charts = {};
    initialized = false;
  }

  function baseOptions() {
    return {
      responsive: true,
      maintainAspectRatio: false,
      plugins: {
        legend: {
          labels: {
            font: { family: 'Nunito, sans-serif', weight: '600' },
            color: '#6b82a8',
          },
        },
      },
      scales: {
        x: {
          grid: { color: 'rgba(226, 232, 240, 0.8)' },
          ticks: { color: '#6b82a8', font: { size: 11 } },
        },
        y: {
          beginAtZero: true,
          grid: { color: 'rgba(226, 232, 240, 0.8)' },
          ticks: { color: '#6b82a8', font: { size: 11 }, precision: 0 },
        },
      },
    };
  }

  function chartLabel(data, fallbackKey) {
    if (data && data.label) {
      return data.label;
    }
    if (typeof window.finkidT === 'function') {
      return window.finkidT(fallbackKey);
    }
    return fallbackKey;
  }

  function initCharts() {
    if (typeof Chart === 'undefined') return;
    var data = readData();
    if (!data) return;

    destroyCharts();

    var growthEl = document.getElementById('chart-users-growth');
    if (growthEl && data.users_growth) {
      charts.growth = new Chart(growthEl, {
        type: 'line',
        data: {
          labels: data.users_growth.labels,
          datasets: [
            {
              label: chartLabel(data.users_growth, 'admin.dash_chart_growth'),
              data: data.users_growth.values,
              borderColor: colors.blue,
              backgroundColor: colors.blueLight,
              fill: true,
              tension: 0.35,
              pointRadius: 3,
              pointHoverRadius: 6,
            },
          ],
        },
        options: baseOptions(),
      });
    }

    var regEl = document.getElementById('chart-registrations');
    if (regEl && data.registrations_daily) {
      charts.reg = new Chart(regEl, {
        type: 'bar',
        data: {
          labels: data.registrations_daily.labels,
          datasets: [
            {
              label: chartLabel(data.registrations_daily, 'admin.dash_chart_regs'),
              data: data.registrations_daily.values,
              backgroundColor: colors.teal,
              borderRadius: 8,
              maxBarThickness: 28,
            },
          ],
        },
        options: baseOptions(),
      });
    }

    var certEl = document.getElementById('chart-certificates');
    if (certEl && data.certificates_monthly) {
      charts.certs = new Chart(certEl, {
        type: 'bar',
        data: {
          labels: data.certificates_monthly.labels,
          datasets: [
            {
              label: chartLabel(data.certificates_monthly, 'admin.dash_chart_certs'),
              data: data.certificates_monthly.values,
              backgroundColor: colors.blue,
              borderRadius: 8,
              maxBarThickness: 36,
            },
          ],
        },
        options: baseOptions(),
      });
    }

    var rolesEl = document.getElementById('chart-roles');
    if (rolesEl && data.roles) {
      charts.roles = new Chart(rolesEl, {
        type: 'pie',
        data: {
          labels: data.roles.labels,
          datasets: [
            {
              data: data.roles.values,
              backgroundColor: colors.pie,
              borderWidth: 2,
              borderColor: '#fff',
            },
          ],
        },
        options: {
          responsive: true,
          maintainAspectRatio: false,
          plugins: {
            legend: {
              position: 'bottom',
              labels: {
                font: { family: 'Nunito, sans-serif', weight: '600' },
                color: '#6b82a8',
                padding: 14,
              },
            },
          },
        },
      });
    }

    initialized = true;
  }

  function onDashboardVisible() {
    var section = document.getElementById('tab-dashboard');
    if (!section || !section.classList.contains('show')) return;
    if (!initialized) {
      initCharts();
    } else {
      Object.keys(charts).forEach(function (key) {
        if (charts[key]) charts[key].resize();
      });
    }
  }

  document.addEventListener('DOMContentLoaded', function () {
    if (document.getElementById('tab-dashboard')?.classList.contains('show')) {
      initCharts();
    }

    document.querySelectorAll('.admin-tabs .atab').forEach(function (btn) {
      btn.addEventListener('click', function () {
        setTimeout(onDashboardVisible, 50);
      });
    });

    window.addEventListener('resize', function () {
      onDashboardVisible();
    });
  });

  function refreshProgressCards() {
    var items = readByLang('admin-progress-data');
    if (!items || !items.length || typeof window.finkidT !== 'function') {
      return;
    }
    items.forEach(function (item) {
      var card = document.querySelector('[data-admin-progress="' + item.key + '"]');
      if (!card) {
        return;
      }
      var labelEl = card.querySelector('.dash-progress-card__head [data-i18n]');
      if (labelEl && item.label_key) {
        labelEl.textContent = window.finkidT(item.label_key);
      }
      var hintKeyEl = card.querySelector('.dash-progress-hint [data-i18n]');
      if (hintKeyEl && item.hint_key) {
        hintKeyEl.textContent = window.finkidT(item.hint_key);
      }
    });
  }

  window.FinkidAdminDashboard = {
    refresh: function () {
      initCharts();
    },
    refreshI18n: function () {
      refreshProgressCards();
      initCharts();
    },
  };
})();
