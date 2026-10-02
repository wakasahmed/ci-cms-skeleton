/**
 * Manage dashboard charts (Chart.js 4).
 *
 * All figures are calculated by Dashboard_model and embedded in the page as
 * JSON (#dashboard-chart-data); this file only draws them.
 */
(function () {
    'use strict';

    /*
     * Whole-row links for the appointment tables. Each row also holds a real
     * link for keyboard and screen-reader users, so clicks on links and
     * controls inside the row are left alone.
     */
    document.querySelectorAll('tr[data-href]').forEach(function (row) {
        row.addEventListener('click', function (event) {
            if (event.target.closest('a, button, input, select, textarea, label')) {
                return;
            }

            // Let people select text in a row without navigating away.
            if (window.getSelection && String(window.getSelection()) !== '') {
                return;
            }

            var url = row.getAttribute('data-href');

            if (event.ctrlKey || event.metaKey) {
                window.open(url, '_blank', 'noopener');
            } else {
                window.location.assign(url);
            }
        });
    });

    var dataElement = document.getElementById('dashboard-chart-data');

    if (!dataElement || !window.Chart) {
        return;
    }

    var data;

    try {
        data = JSON.parse(dataElement.textContent);
    } catch (error) {
        return;
    }

    var styles = getComputedStyle(document.documentElement);
    var COLORS = {
        primary: styles.getPropertyValue('--admin-primary').trim() || '#a73a9b',
        primaryRgb: styles.getPropertyValue('--admin-primary-rgb').trim() || '167, 58, 155',
        success: '#157347',
        warning: '#d98a1f',
        neutral: '#98a2b3',
        info: '#2563eb',
        text: styles.getPropertyValue('--admin-muted').trim() || '#6b7280',
        grid: '#eef0f3'
    };

    // Matches .admin-dashboard-swatch-* in admin.css.
    var STATUS_COLORS = {
        New: COLORS.warning,
        Confirmed: COLORS.info,
        Completed: COLORS.success,
        Cancelled: COLORS.neutral
    };

    var reduceMotion = window.matchMedia('(prefers-reduced-motion: reduce)').matches;
    var numberFormat = new Intl.NumberFormat('en-GB', { maximumFractionDigits: 0 });

    Chart.defaults.font.family = getComputedStyle(document.body).fontFamily;
    Chart.defaults.font.size = 12;
    Chart.defaults.color = COLORS.text;
    Chart.defaults.animation = reduceMotion ? false : { duration: 400 };

    function parseDay(iso) {
        return new Date(iso + 'T00:00:00');
    }

    function shortDate(iso) {
        return parseDay(iso).toLocaleDateString('en-GB', { day: 'numeric', month: 'short' });
    }

    function longDate(iso) {
        return parseDay(iso).toLocaleDateString('en-GB', {
            weekday: 'short',
            day: 'numeric',
            month: 'short',
            year: 'numeric'
        });
    }

    function sum(values) {
        return values.reduce(function (total, value) {
            return total + value;
        }, 0);
    }

    function truncate(text, length) {
        return text.length > length ? text.slice(0, length - 1) + '…' : text;
    }

    function plural(count, word) {
        return count + ' ' + word + (count === 1 ? '' : 's');
    }

    /* ---------- Bookings received per day ---------- */

    function initRequests() {
        var canvas = document.getElementById('requests-chart');

        if (!canvas) {
            return;
        }

        var hasData = sum(data.requests) > 0;
        var emptyNote = document.querySelector('[data-chart-empty]');

        if (emptyNote) {
            emptyNote.hidden = hasData;
        }

        new Chart(canvas, {
            type: 'line',
            data: {
                labels: data.labels,
                datasets: [{
                    label: 'Bookings',
                    data: data.requests,
                    borderColor: COLORS.primary,
                    backgroundColor: 'rgba(' + COLORS.primaryRgb + ', 0.12)',
                    fill: true,
                    tension: 0.3,
                    borderWidth: 2,
                    pointRadius: 0,
                    pointHoverRadius: 4
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                interaction: { mode: 'index', intersect: false },
                plugins: {
                    legend: { display: false },
                    tooltip: {
                        callbacks: {
                            title: function (items) {
                                return items.length ? longDate(data.labels[items[0].dataIndex]) : '';
                            },
                            label: function (item) {
                                return plural(item.parsed.y, 'booking');
                            }
                        }
                    }
                },
                scales: {
                    x: {
                        grid: { display: false },
                        ticks: {
                            maxRotation: 0,
                            autoSkip: true,
                            maxTicksLimit: 8,
                            callback: function (value) {
                                return shortDate(data.labels[value]);
                            }
                        }
                    },
                    y: {
                        beginAtZero: true,
                        suggestedMax: hasData ? undefined : 4,
                        grid: { color: COLORS.grid, borderDash: [3, 3] },
                        border: { display: false },
                        ticks: {
                            precision: 0,
                            callback: function (value) {
                                return numberFormat.format(value);
                            }
                        }
                    }
                }
            }
        });
    }

    /* ---------- Booking outcomes (doughnut) ---------- */

    function initStatus() {
        var canvas = document.getElementById('status-chart');

        if (!canvas) {
            return;
        }

        var total = sum(data.status.values);
        var centerLabel = {
            id: 'dashboardCenterLabel',
            afterDraw: function (chart) {
                var area = chart.chartArea;
                var context = chart.ctx;
                var x = (area.left + area.right) / 2;
                var y = (area.top + area.bottom) / 2;

                context.save();
                context.textAlign = 'center';
                context.textBaseline = 'middle';
                context.fillStyle = '#101828';
                context.font = '700 24px ' + Chart.defaults.font.family;
                context.fillText(numberFormat.format(total), x, y - 8);
                context.fillStyle = COLORS.text;
                context.font = '12px ' + Chart.defaults.font.family;
                context.fillText(total === 1 ? 'booking' : 'bookings', x, y + 14);
                context.restore();
            }
        };

        new Chart(canvas, {
            type: 'doughnut',
            data: {
                labels: data.status.labels,
                datasets: [{
                    data: data.status.values,
                    backgroundColor: data.status.labels.map(function (label) {
                        return STATUS_COLORS[label] || COLORS.neutral;
                    }),
                    borderColor: '#ffffff',
                    borderWidth: 2,
                    hoverOffset: 4
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                cutout: '68%',
                plugins: {
                    legend: { display: false },
                    tooltip: {
                        callbacks: {
                            label: function (item) {
                                var share = total > 0 ? Math.round(100 * item.parsed / total) : 0;

                                return item.label + ': ' + numberFormat.format(item.parsed) + ' (' + share + '%)';
                            }
                        }
                    }
                }
            },
            plugins: [centerLabel]
        });
    }

    /* ---------- Most booked services (horizontal bars) ---------- */

    function initTopServices() {
        var canvas = document.getElementById('top-services-chart');

        if (!canvas || !data.topServices.labels.length) {
            return;
        }

        var holder = canvas.parentElement;
        var rows = parseInt(holder.getAttribute('data-chart-bars'), 10) || data.topServices.labels.length;

        holder.style.height = (56 + rows * 44) + 'px';

        new Chart(canvas, {
            type: 'bar',
            data: {
                labels: data.topServices.labels,
                datasets: [{
                    label: 'Bookings',
                    data: data.topServices.requests,
                    backgroundColor: COLORS.primary,
                    borderRadius: 4,
                    maxBarThickness: 22
                }]
            },
            options: {
                indexAxis: 'y',
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: { display: false },
                    tooltip: {
                        callbacks: {
                            title: function (items) {
                                return items.length ? data.topServices.labels[items[0].dataIndex] : '';
                            },
                            label: function (item) {
                                return plural(item.parsed.x, 'booking');
                            }
                        }
                    }
                },
                scales: {
                    x: {
                        beginAtZero: true,
                        grid: { color: COLORS.grid, borderDash: [3, 3] },
                        border: { display: false },
                        ticks: { precision: 0 }
                    },
                    y: {
                        grid: { display: false },
                        ticks: {
                            callback: function (value) {
                                return truncate(String(this.getLabelForValue(value)), 24);
                            }
                        }
                    }
                }
            }
        });
    }

    initRequests();
    initStatus();
    initTopServices();
})();
