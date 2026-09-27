/**
 * Manage dashboard charts (Chart.js 4).
 *
 * All figures are calculated by Dashboard_model and embedded in the page as
 * JSON (#dashboard-chart-data); this file only draws them.
 */
(function () {
    'use strict';

    /*
     * Whole-row links for the Upcoming tours and Recent bookings tables. Each row
     * also holds a real link for keyboard and screen-reader users, so clicks on
     * links and controls inside the row are left alone.
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
        primary: styles.getPropertyValue('--admin-primary').trim() || '#63569b',
        primaryRgb: styles.getPropertyValue('--admin-primary-rgb').trim() || '99, 86, 155',
        success: '#157347',
        warning: '#d98a1f',
        neutral: '#98a2b3',
        info: '#2563eb',
        text: styles.getPropertyValue('--admin-muted').trim() || '#6b7280',
        grid: '#eef0f3'
    };
    var STATUS_COLORS = {
        Completed: COLORS.success,
        Cancelled: COLORS.neutral,
        Refunded: COLORS.info
    };

    var reduceMotion = window.matchMedia('(prefers-reduced-motion: reduce)').matches;
    var numberFormat = new Intl.NumberFormat('en-US', { maximumFractionDigits: 0 });
    var compactFormat = new Intl.NumberFormat('en-US', { notation: 'compact', maximumFractionDigits: 1 });

    Chart.defaults.font.family = getComputedStyle(document.body).fontFamily;
    Chart.defaults.font.size = 12;
    Chart.defaults.color = COLORS.text;
    Chart.defaults.animation = reduceMotion ? false : { duration: 400 };

    function money(value) {
        var prefix = data.currency ? data.currency + ' ' : '';

        return prefix + numberFormat.format(value);
    }

    function parseDay(iso) {
        return new Date(iso + 'T00:00:00');
    }

    function shortDate(iso) {
        return parseDay(iso).toLocaleDateString('en-US', { month: 'short', day: 'numeric' });
    }

    function longDate(iso) {
        return parseDay(iso).toLocaleDateString('en-US', {
            weekday: 'short',
            month: 'short',
            day: 'numeric',
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

    /* ---------- Business performance (Revenue / Bookings tabs) ---------- */

    var performanceCanvas = document.getElementById('performance-chart');
    var performanceChart = null;

    function performanceOptions(isRevenue, hasData) {
        return {
            responsive: true,
            maintainAspectRatio: false,
            interaction: { mode: 'index', intersect: false },
            plugins: {
                legend: {
                    display: !isRevenue,
                    position: 'bottom',
                    labels: { usePointStyle: true, boxWidth: 8, boxHeight: 8 }
                },
                tooltip: {
                    callbacks: {
                        title: function (items) {
                            return items.length ? longDate(data.labels[items[0].dataIndex]) : '';
                        },
                        label: function (item) {
                            var value = isRevenue ? money(item.parsed.y) : numberFormat.format(item.parsed.y);

                            return item.dataset.label + ': ' + value;
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
                    suggestedMax: hasData ? undefined : (isRevenue ? 100 : 4),
                    grid: { color: COLORS.grid, borderDash: [3, 3] },
                    border: { display: false },
                    ticks: {
                        precision: 0,
                        callback: function (value) {
                            return isRevenue ? compactFormat.format(value) : numberFormat.format(value);
                        }
                    }
                }
            }
        };
    }

    function performanceConfig(tab) {
        var isRevenue = tab === 'revenue';
        var datasets;

        if (isRevenue) {
            datasets = [{
                label: 'Net revenue',
                data: data.revenue,
                borderColor: COLORS.primary,
                backgroundColor: 'rgba(' + COLORS.primaryRgb + ', 0.12)',
                fill: true,
                tension: 0.3,
                borderWidth: 2,
                pointRadius: 0,
                pointHoverRadius: 4
            }];
        } else {
            datasets = [
                {
                    label: 'Bookings started',
                    data: data.started,
                    borderColor: COLORS.primary,
                    backgroundColor: COLORS.primary,
                    tension: 0.3,
                    borderWidth: 2,
                    pointRadius: 0,
                    pointHoverRadius: 4
                },
                {
                    label: 'Payment completed',
                    data: data.paid,
                    borderColor: COLORS.success,
                    backgroundColor: COLORS.success,
                    tension: 0.3,
                    borderWidth: 2,
                    pointRadius: 0,
                    pointHoverRadius: 4
                }
            ];
        }

        var hasData = sum(datasets[0].data) > 0 || (datasets[1] ? sum(datasets[1].data) > 0 : false);

        return {
            hasData: hasData,
            config: {
                type: 'line',
                data: { labels: data.labels, datasets: datasets },
                options: performanceOptions(isRevenue, hasData)
            }
        };
    }

    function showPerformance(tab) {
        var built = performanceConfig(tab);
        var emptyNote = document.querySelector('[data-chart-empty]');
        var panel = document.getElementById('performance-chart-panel');

        if (performanceChart) {
            performanceChart.destroy();
        }

        performanceChart = new Chart(performanceCanvas, built.config);
        performanceCanvas.setAttribute(
            'aria-label',
            tab === 'revenue'
                ? 'Line chart of daily net revenue over the last ' + data.days + ' days'
                : 'Line chart of bookings started and paid per day over the last ' + data.days + ' days'
        );

        document.querySelectorAll('[data-chart-stat]').forEach(function (stat) {
            stat.hidden = stat.getAttribute('data-chart-stat') !== tab;
        });

        if (emptyNote) {
            emptyNote.hidden = built.hasData;
        }
        if (panel) {
            panel.setAttribute('aria-labelledby', 'performance-tab-' + tab);
        }
    }

    function initPerformance() {
        var tablist = document.querySelector('[data-dashboard-tabs]');

        if (!performanceCanvas || !tablist) {
            return;
        }

        var tabs = Array.prototype.slice.call(tablist.querySelectorAll('[data-chart-tab]'));

        function select(tab, moveFocus) {
            tabs.forEach(function (button) {
                var active = button === tab;

                button.setAttribute('aria-selected', active ? 'true' : 'false');
                button.tabIndex = active ? 0 : -1;
            });

            if (moveFocus) {
                tab.focus();
            }
            showPerformance(tab.getAttribute('data-chart-tab'));
        }

        tabs.forEach(function (button, index) {
            button.addEventListener('click', function () {
                select(button, false);
            });

            button.addEventListener('keydown', function (event) {
                var target = null;

                if (event.key === 'ArrowRight') {
                    target = tabs[(index + 1) % tabs.length];
                } else if (event.key === 'ArrowLeft') {
                    target = tabs[(index - 1 + tabs.length) % tabs.length];
                } else if (event.key === 'Home') {
                    target = tabs[0];
                } else if (event.key === 'End') {
                    target = tabs[tabs.length - 1];
                }

                if (target) {
                    event.preventDefault();
                    select(target, true);
                }
            });
        });

        showPerformance('revenue');
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
                context.fillText('bookings', x, y + 14);
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

    /* ---------- Top tours (horizontal bars) ---------- */

    function initTopTours() {
        var canvas = document.getElementById('top-tours-chart');

        if (!canvas || !data.topTours.labels.length) {
            return;
        }

        var holder = canvas.parentElement;
        var rows = parseInt(holder.getAttribute('data-chart-bars'), 10) || data.topTours.labels.length;

        holder.style.height = (56 + rows * 44) + 'px';

        new Chart(canvas, {
            type: 'bar',
            data: {
                labels: data.topTours.labels,
                datasets: [{
                    label: 'Net revenue',
                    data: data.topTours.revenue,
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
                                return items.length ? data.topTours.labels[items[0].dataIndex] : '';
                            },
                            label: function (item) {
                                return 'Net revenue: ' + money(item.parsed.x);
                            },
                            afterLabel: function (item) {
                                var count = data.topTours.bookings[item.dataIndex];

                                return count + (count === 1 ? ' booking' : ' bookings');
                            }
                        }
                    }
                },
                scales: {
                    x: {
                        beginAtZero: true,
                        grid: { color: COLORS.grid, borderDash: [3, 3] },
                        border: { display: false },
                        ticks: {
                            maxTicksLimit: 5,
                            callback: function (value) {
                                return compactFormat.format(value);
                            }
                        }
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

    initPerformance();
    initStatus();
    initTopTours();
})();
