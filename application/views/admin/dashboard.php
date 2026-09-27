<?php
$primaryItems = array(
    array(
        'label' => 'Tours',
        'description' => 'Tour content and pricing',
        'value' => $totalTours,
        'url' => ADMIN_URL . 'tours',
        'icon' => 'bi-map',
        'tone' => 'purple',
    ),
    array(
        'label' => 'Tour Guides',
        'description' => 'Guide profiles and details',
        'value' => $totalTourGuides,
        'url' => ADMIN_URL . 'tour-guides',
        'icon' => 'bi-person-badge',
        'tone' => 'blue',
    ),
    array(
        'label' => 'Attractions',
        'description' => 'Destinations and highlights',
        'value' => $totalAttractions,
        'url' => ADMIN_URL . 'attractions',
        'icon' => 'bi-geo-alt',
        'tone' => 'green',
    ),
    array(
        'label' => 'Vehicles',
        'description' => 'Fleet and transport options',
        'value' => $totalVehicles,
        'url' => ADMIN_URL . 'vehicles',
        'icon' => 'bi-car-front',
        'tone' => 'orange',
    ),
);

$linkPanels = array(
    array(
        'id' => 'website-content-title',
        'title' => 'Website content',
        'description' => 'Keep public-facing pages and media up to date.',
        'icon' => 'bi-window-stack',
        'items' => array(
            array('label' => 'Web Pages', 'value' => $totalPages, 'url' => ADMIN_URL . 'pages', 'icon' => 'bi-file-earmark-text'),
            array('label' => 'Image Sliders', 'value' => $totalSliders, 'url' => ADMIN_URL . 'sliders', 'icon' => 'bi-images'),
            array('label' => 'Customer Reviews', 'value' => $totalReviews, 'url' => ADMIN_URL . 'customer-reviews', 'icon' => 'bi-star'),
        ),
    ),
    array(
        'id' => 'configuration-title',
        'title' => 'Configuration',
        'description' => 'Manage the supporting options used across tours.',
        'icon' => 'bi-sliders',
        'items' => array(
            array('label' => 'Tour Slots', 'value' => $totalSlots, 'url' => ADMIN_URL . 'tour-slots', 'icon' => 'bi-clock'),
            array('label' => 'Tour Languages', 'value' => $totalLang, 'url' => ADMIN_URL . 'tour-languages', 'icon' => 'bi-translate'),
            array('label' => 'Admin Users', 'value' => $totalAdmins, 'url' => ADMIN_URL . 'admins', 'icon' => 'bi-people'),
        ),
    ),
    array(
        'id' => 'administration-title',
        'title' => 'Website administration',
        'description' => 'Navigation and site-wide settings.',
        'icon' => 'bi-gear',
        'items' => array(
            array('label' => 'Menu Manager', 'url' => ADMIN_URL . 'menu', 'icon' => 'bi-diagram-3'),
            array('label' => 'Website Settings', 'url' => ADMIN_URL . 'website-settings', 'icon' => 'bi-gear'),
        ),
    ),
);

$period = $dashboard['period'];
$kpi = $dashboard['kpi'];
$attention = $dashboard['attention'];
$statusTotal = array_sum($dashboard['status']);
$rangeDays = (int) $dashboardRange;
$periodLabel = 'the last ' . $rangeDays . ' days';
$bookingsBaseUrl = ADMIN_URL . 'bookings/index/book_id/DESC/';

$e = function ($value) {
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
};

$money = function ($amount) use ($dashboardCurrency) {
    $amount = (int) $amount;
    $formatted = number_format(abs($amount));
    $prefix = $dashboardCurrency !== '' ? $dashboardCurrency . ' ' : '';

    return ($amount < 0 ? '-' : '') . $prefix . $formatted;
};

$plural = function ($count, $singular, $pluralForm = null) {
    if ((int) $count === 1) {
        return $singular;
    }

    return $pluralForm !== null ? $pluralForm : $singular . 's';
};

/**
 * Trend pill against the previous period. $change is NULL when there was
 * nothing to compare with, and $invert flips the colours when "up" is bad.
 */
$trend = function ($change, $invert = false) use ($e, $rangeDays) {
    if ($change === null) {
        $class = 'is-up';
        $icon = 'bi-arrow-up-right';
        $text = 'New';
    } elseif ($change === 0) {
        $class = 'is-flat';
        $icon = 'bi-dash';
        $text = '0%';
    } else {
        $isUp = $change > 0;
        $class = ($isUp xor $invert) ? 'is-up' : 'is-down';
        $icon = $isUp ? 'bi-arrow-up-right' : 'bi-arrow-down-right';
        $text = ($isUp ? '+' : '') . $change . '%';
    }

    return '<span class="admin-dashboard-trend ' . $class . '" title="Compared with the previous ' . (int) $rangeDays . ' days">'
        . '<i class="bi ' . $icon . '" aria-hidden="true"></i> ' . $e($text)
        . '<span class="visually-hidden"> compared with the previous ' . (int) $rangeDays . ' days</span></span>';
};

$bookingStatusClass = function ($status) {
    if ($status === 'Completed') {
        return 'status-enabled';
    }
    if ($status === 'Cancelled') {
        return 'status-disabled';
    }
    if ($status === 'Refunded') {
        return 'status-published';
    }

    return 'status-warning';
};

$createdRange = report_format_date_range($period['from'], $period['to']);
$today = $period['to'];
$upcomingRange = report_format_date_range($today, date('Y-m-d', strtotime($today . ' +' . Dashboard_model::UPCOMING_DAYS . ' days')));

$kpiCards = array(
    array(
        'label' => 'Net revenue',
        'value' => $money($kpi['net_revenue']),
        'icon' => 'bi-cash-coin',
        'tone' => 'green',
        'trend' => $trend($kpi['trend']['net_revenue']),
        'note' => $money($kpi['collected']) . ' collected · ' . $money($kpi['refunded']) . ' refunded',
        'url' => ADMIN_URL . 'reports/payments',
    ),
    array(
        'label' => 'Bookings started',
        'value' => number_format($kpi['started']),
        'icon' => 'bi-journal-check',
        'tone' => 'purple',
        'trend' => $trend($kpi['trend']['started']),
        'note' => $kpi['started_paid'] . ' paid · ' . $kpi['abandoned_count'] . ' abandoned · ' . $kpi['conversion'] . '% converted',
        'url' => ADMIN_URL . 'bookings?created=' . rawurlencode($createdRange),
    ),
    array(
        'label' => 'Upcoming tours',
        'value' => number_format($kpi['upcoming_count']),
        'icon' => 'bi-calendar-event',
        'tone' => 'blue',
        'trend' => '',
        'note' => 'Paid, next ' . Dashboard_model::UPCOMING_DAYS . ' days · ' . number_format($kpi['upcoming_guests']) . ' ' . $plural($kpi['upcoming_guests'], 'guest'),
        'url' => ADMIN_URL . 'bookings/index/book_date/ASC/Completed?date=' . rawurlencode($upcomingRange),
    ),
    array(
        'label' => 'Abandoned checkouts',
        'value' => number_format($kpi['abandoned_count']),
        'icon' => 'bi-cart-x',
        'tone' => 'orange',
        'trend' => $trend($kpi['trend']['abandoned'], true),
        'note' => $money($kpi['abandoned_value']) . ' in bookings not completed',
        'url' => $bookingsBaseUrl . 'Cancelled?created=' . rawurlencode($createdRange),
    ),
    array(
        'label' => 'Refunds',
        'value' => $money($kpi['refunded']),
        'icon' => 'bi-arrow-counterclockwise',
        'tone' => 'red',
        'trend' => '',
        'note' => $kpi['refund_count'] . ' ' . $plural($kpi['refund_count'], 'refund') . ' in ' . $periodLabel,
        'url' => $bookingsBaseUrl . 'Refunded',
    ),
    array(
        'label' => 'Average booking value',
        'value' => $money($kpi['average_value']),
        'icon' => 'bi-receipt',
        'tone' => 'green',
        'trend' => $trend($kpi['trend']['average_value']),
        'note' => $kpi['paid_count'] . ' paid ' . $plural($kpi['paid_count'], 'booking') . ' in ' . $periodLabel,
        'url' => ADMIN_URL . 'reports/payments',
    ),
    array(
        'label' => 'Contact requests',
        'value' => number_format($kpi['contact_requests']),
        'icon' => 'bi-envelope',
        'tone' => 'blue',
        'trend' => $trend($kpi['trend']['contact_requests']),
        'note' => 'New enquiries in ' . $periodLabel,
        'url' => ADMIN_URL . 'contact-requests',
    ),
    array(
        'label' => 'Trip plans',
        'value' => number_format($kpi['trip_plans']),
        'icon' => 'bi-map',
        'tone' => 'purple',
        'trend' => $trend($kpi['trend']['trip_plans']),
        'note' => 'Completed plan-your-visit forms in ' . $periodLabel,
        'url' => ADMIN_URL . 'plan-your-visit',
    ),
);

$attentionItems = array(
    array(
        'title' => 'Paid bookings without a guide',
        'count' => $attention['unassigned_count'],
        'icon' => 'bi-person-exclamation',
        'url' => $bookingsBaseUrl . 'Completed',
        'ok' => 'Every upcoming paid tour has a guide assigned.',
        'warn' => 'Assign a guide before the tour date.',
    ),
    array(
        'title' => 'Abandoned bookings not cleaned up',
        'count' => $attention['stale_pending'],
        'icon' => 'bi-exclamation-triangle',
        'url' => $bookingsBaseUrl . 'Pending',
        'ok' => 'Old pending bookings are being released.',
        'warn' => 'Pending for over ' . (int) TOUR_BOOKING_CUTOFF_HOURS . ' ' . $plural(TOUR_BOOKING_CUTOFF_HOURS, 'hour') . '. Check that the booking_cron release_holds job is scheduled.',
    ),
    array(
        'title' => 'Guides held by open checkouts',
        'count' => $attention['holds'],
        'icon' => 'bi-clock-history',
        'url' => $bookingsBaseUrl . 'Pending',
        'ok' => 'No customer is holding a guide right now.',
        'warn' => 'Customers at review or payment are holding a guide for the day.',
        'info' => true,
    ),
    array(
        'title' => 'Referral commission unpaid',
        'count' => $attention['commission_bookings'],
        'icon' => 'bi-wallet2',
        'url' => ADMIN_URL . 'reports/referrals',
        'ok' => 'No referral commission is outstanding.',
        'warn' => $money($attention['commission_amount']) . ' owed on completed bookings.',
    ),
    array(
        'title' => 'Promo codes expiring within ' . (int) $attention['promo_days'] . ' days',
        'count' => $attention['promos_expiring'],
        'icon' => 'bi-ticket-perforated',
        'url' => ADMIN_URL . 'discount-codes',
        'ok' => 'No active promo codes expire this week.',
        'warn' => 'Extend or replace them if the campaign continues.',
    ),
    array(
        'title' => 'Guides with no open availability',
        'count' => $attention['guides_without_availability'],
        'icon' => 'bi-calendar-x',
        'url' => ADMIN_URL . 'tour-guide-availability',
        'ok' => 'Every guide has open slots in the next ' . (int) $attention['availability_days'] . ' days.',
        'warn' => 'No open slots in the next ' . (int) $attention['availability_days'] . ' days, so customers cannot book them.',
    ),
);

$chartData = array(
    'currency' => $dashboardCurrency,
    'days' => $rangeDays,
    'labels' => $dashboard['series']['labels'],
    'revenue' => $dashboard['series']['revenue'],
    'started' => $dashboard['series']['started'],
    'paid' => $dashboard['series']['paid'],
    'status' => array(
        'labels' => array_keys($dashboard['status']),
        'values' => array_values($dashboard['status']),
    ),
    'topTours' => array(
        'labels' => array_column($dashboard['topTours'], 'name'),
        'revenue' => array_column($dashboard['topTours'], 'revenue'),
        'bookings' => array_column($dashboard['topTours'], 'bookings'),
    ),
);
?>

<?php
$this->load->view(
    'admin/partials/breadcrumb',
    array(
        'show_home' => FALSE,
        'items' => array(array('label' => 'Dashboard', 'active' => TRUE)),
    )
);
?>

<section class="admin-dashboard" aria-labelledby="dashboard-title">
    <?php
    $this->load->view(
        'admin/partials/module_header',
        array(
            'title' => 'Dashboard',
            'description' => 'Bookings, revenue and tour operations, with the tours, people and content that power your website.',
            'id' => 'dashboard-title',
        )
    );
    ?>

    <div class="admin-dashboard-toolbar">
        <p class="admin-dashboard-period">
            <i class="bi bi-calendar3" aria-hidden="true"></i>
            <?php echo $e(date(ADMIN_DATE_FORMAT, strtotime($period['from']))); ?>
            &ndash;
            <?php echo $e(date(ADMIN_DATE_FORMAT, strtotime($period['to']))); ?>
        </p>
        <nav class="admin-dashboard-range" aria-label="Reporting period">
            <?php foreach ($dashboardRanges as $days) { ?>
                <a
                    href="<?php echo $e(ADMIN_URL . '?range=' . (int) $days); ?>"
                    class="<?php echo (int) $days === $rangeDays ? 'is-active' : ''; ?>"
                    <?php if ((int) $days === $rangeDays) { ?>aria-current="true"<?php } ?>
                ><?php echo (int) $days; ?> days</a>
            <?php } ?>
        </nav>
    </div>

    <section class="admin-dashboard-kpis" aria-label="Booking and revenue summary for <?php echo $e($periodLabel); ?>">
        <?php foreach ($kpiCards as $card) { ?>
            <article class="admin-dashboard-kpi admin-dashboard-kpi-<?php echo $e($card['tone']); ?>">
                <header>
                    <h2><a href="<?php echo $e($card['url']); ?>"><?php echo $e($card['label']); ?></a></h2>
                    <span class="admin-dashboard-kpi-icon" aria-hidden="true"><i class="bi <?php echo $e($card['icon']); ?>"></i></span>
                </header>
                <p class="admin-dashboard-kpi-value"><?php echo $e($card['value']); ?></p>
                <p class="admin-dashboard-kpi-note">
                    <?php echo $card['trend']; ?>
                    <span><?php echo $e($card['note']); ?></span>
                </p>
            </article>
        <?php } ?>
    </section>

    <div class="admin-dashboard-grid">
        <div class="admin-dashboard-column">
            <section class="admin-dashboard-panel" aria-labelledby="performance-title">
                <header class="admin-dashboard-panel-header">
                    <div>
                        <h2 id="performance-title">Business performance</h2>
                        <p>Revenue and bookings in <?php echo $e($periodLabel); ?>.</p>
                    </div>
                    <span class="admin-dashboard-panel-icon" aria-hidden="true"><i class="bi bi-graph-up-arrow"></i></span>
                </header>

                <div class="admin-dashboard-panel-body">
                    <div class="admin-dashboard-tabs" role="tablist" aria-label="Chart type" data-dashboard-tabs>
                        <button type="button" role="tab" id="performance-tab-revenue" aria-selected="true" aria-controls="performance-chart-panel" data-chart-tab="revenue">Revenue</button>
                        <button type="button" role="tab" id="performance-tab-bookings" aria-selected="false" aria-controls="performance-chart-panel" tabindex="-1" data-chart-tab="bookings">Bookings</button>
                    </div>

                    <div id="performance-chart-panel" role="tabpanel" aria-labelledby="performance-tab-revenue">
                        <dl class="admin-dashboard-chart-stats">
                            <div data-chart-stat="revenue">
                                <dt>Net revenue</dt>
                                <dd><?php echo $e($money($kpi['net_revenue'])); ?></dd>
                            </div>
                            <div data-chart-stat="bookings" hidden>
                                <dt>Bookings started</dt>
                                <dd><?php echo $e(number_format($kpi['started'])); ?></dd>
                            </div>
                            <div data-chart-stat="bookings" hidden>
                                <dt>Payment completed</dt>
                                <dd><?php echo $e(number_format($kpi['started_paid'])); ?></dd>
                            </div>
                        </dl>
                        <div class="admin-dashboard-chart admin-dashboard-chart-lg">
                            <canvas id="performance-chart" role="img" aria-label="Line chart of daily net revenue over <?php echo $e($periodLabel); ?>"></canvas>
                        </div>
                        <p class="admin-dashboard-chart-note" hidden data-chart-empty>Nothing to plot yet for this period.</p>
                    </div>
                </div>
            </section>

            <section class="admin-dashboard-panel" aria-labelledby="upcoming-title">
                <header class="admin-dashboard-panel-header">
                    <div>
                        <h2 id="upcoming-title">Upcoming tours</h2>
                        <p>Paid bookings in the next <?php echo (int) Dashboard_model::UPCOMING_DAYS; ?> days, soonest first.</p>
                    </div>
                    <a class="btn btn-sm btn-primary" href="<?php echo $e(ADMIN_URL . 'bookings/index/book_date/ASC/Completed?date=' . rawurlencode($upcomingRange)); ?>">View all</a>
                </header>

                <?php if (empty($dashboard['upcoming'])) { ?>
                    <p class="admin-dashboard-empty">No paid tours are scheduled in the next <?php echo (int) Dashboard_model::UPCOMING_DAYS; ?> days.</p>
                <?php } else { ?>
                    <div class="table-responsive">
                        <table class="table table-hover mb-0">
                            <caption class="visually-hidden">Paid bookings with a tour date in the next <?php echo (int) Dashboard_model::UPCOMING_DAYS; ?> days</caption>
                            <thead>
                                <tr>
                                    <th scope="col">Date and time</th>
                                    <th scope="col">Tour</th>
                                    <th scope="col">Guide</th>
                                    <th scope="col" class="text-end">Guests</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($dashboard['upcoming'] as $booking) { ?>
                                    <?php
                                    $isTour = $booking['tour_type'] === 'Tour';
                                    $hasGuide = (int) $booking['book_tour_guide_id'] > 0;
                                    $viewUrl = ADMIN_URL . 'bookings/control/view/' . (int) $booking['book_id'];
                                    ?>
                                    <tr class="admin-dashboard-row-link" data-href="<?php echo $e($viewUrl); ?>">
                                        <td>
                                            <strong><?php echo $e(date(ADMIN_DATE_FORMAT, strtotime($booking['book_date']))); ?></strong>
                                            <span class="admin-dashboard-sub">
                                                <?php echo $e($booking['slot_name']); ?>
                                                <?php if (!empty($booking['book_slot_start_time'])) { ?>
                                                    &middot; <?php echo $e(date(ADMIN_TIME_FORMAT, strtotime($booking['book_slot_start_time']))); ?>
                                                <?php } ?>
                                            </span>
                                        </td>
                                        <td>
                                            <a class="admin-dashboard-row-title" href="<?php echo $e($viewUrl); ?>"><?php echo $e($booking['tour_name']); ?></a>
                                            <span class="admin-dashboard-sub"><?php echo $e($booking['book_name']); ?> &middot; <?php echo $e($booking['book_res_code']); ?></span>
                                        </td>
                                        <td>
                                            <?php if ($hasGuide) { ?>
                                                <?php echo $e($booking['guide_name']); ?>
                                            <?php } elseif ($isTour) { ?>
                                                <span class="status-badge status-warning">Unassigned</span>
                                            <?php } elseif ($booking['tour_type'] === 'Experience') { ?>
                                                <span class="admin-dashboard-sub">Experience, no guide</span>
                                            <?php } else { ?>
                                                &mdash;
                                            <?php } ?>
                                        </td>
                                        <td class="text-end"><?php echo (int) $booking['book_guests']; ?></td>
                                    </tr>
                                <?php } ?>
                            </tbody>
                        </table>
                    </div>
                <?php } ?>
            </section>

            <section class="admin-dashboard-panel" aria-labelledby="recent-title">
                <header class="admin-dashboard-panel-header">
                    <div>
                        <h2 id="recent-title">Recent bookings</h2>
                        <p>The latest bookings, including unfinished checkouts.</p>
                    </div>
                    <a class="btn btn-sm btn-primary" href="<?php echo $e(ADMIN_URL . 'bookings'); ?>">View all</a>
                </header>

                <?php if (empty($dashboard['recent'])) { ?>
                    <p class="admin-dashboard-empty">No bookings have been made yet.</p>
                <?php } else { ?>
                    <div class="table-responsive">
                        <table class="table table-hover mb-0">
                            <caption class="visually-hidden">Most recent bookings</caption>
                            <thead>
                                <tr>
                                    <th scope="col">Customer</th>
                                    <th scope="col">Tour</th>
                                    <th scope="col" class="d-none d-md-table-cell">Tour date</th>
                                    <th scope="col">Status</th>
                                    <th scope="col" class="text-end">Total</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($dashboard['recent'] as $booking) { ?>
                                    <?php $viewUrl = ADMIN_URL . 'bookings/control/view/' . (int) $booking['book_id']; ?>
                                    <tr class="admin-dashboard-row-link" data-href="<?php echo $e($viewUrl); ?>">
                                        <td>
                                            <a class="admin-dashboard-row-title" href="<?php echo $e($viewUrl); ?>"><?php echo $e($booking['book_name']); ?></a>
                                            <span class="admin-dashboard-sub"><?php echo $e($booking['book_res_code']); ?> &middot; <?php echo $e(date(ADMIN_DATETIME_FORMAT, strtotime($booking['book_added']))); ?></span>
                                        </td>
                                        <td><?php echo $e($booking['tour_name']); ?></td>
                                        <td class="d-none d-md-table-cell">
                                            <?php echo !empty($booking['book_date']) ? $e(date(ADMIN_DATE_FORMAT, strtotime($booking['book_date']))) : '&mdash;'; ?>
                                        </td>
                                        <td><span class="status-badge <?php echo $e($bookingStatusClass($booking['book_status'])); ?>"><?php echo $e($booking['book_status']); ?></span></td>
                                        <td class="text-end"><?php echo (int) $booking['book_fee'] > 0 ? $e($money($booking['book_fee'])) : '&mdash;'; ?></td>
                                    </tr>
                                <?php } ?>
                            </tbody>
                        </table>
                    </div>
                <?php } ?>
            </section>
        </div>

        <div class="admin-dashboard-column">
            <section class="admin-dashboard-panel" aria-labelledby="attention-title">
                <header class="admin-dashboard-panel-header">
                    <div>
                        <h2 id="attention-title">Needs attention</h2>
                        <p>Booking, guide and promotion items to follow up.</p>
                    </div>
                    <span class="admin-dashboard-panel-icon" aria-hidden="true"><i class="bi bi-bell"></i></span>
                </header>

                <ul class="admin-dashboard-attention">
                    <?php foreach ($attentionItems as $item) { ?>
                        <?php
                        $count = (int) $item['count'];
                        $state = $count === 0 ? 'is-ok' : (!empty($item['info']) ? 'is-info' : 'is-warn');
                        ?>
                        <li class="<?php echo $state; ?>">
                            <a href="<?php echo $e($item['url']); ?>">
                                <span class="admin-dashboard-attention-icon" aria-hidden="true"><i class="bi <?php echo $e($item['icon']); ?>"></i></span>
                                <span class="admin-dashboard-attention-text">
                                    <strong><?php echo $e($item['title']); ?></strong>
                                    <span><?php echo $e($count === 0 ? $item['ok'] : $item['warn']); ?></span>
                                    <?php if ($item['title'] === 'Paid bookings without a guide' && $count > 0) { ?>
                                        <?php foreach ($attention['unassigned_rows'] as $row) { ?>
                                            <span class="admin-dashboard-attention-row">
                                                <?php echo $e($row['tour_name']); ?> &middot; <?php echo $e(date(ADMIN_DATE_FORMAT, strtotime($row['book_date']))); ?>
                                            </span>
                                        <?php } ?>
                                    <?php } ?>
                                </span>
                                <span class="admin-dashboard-attention-count"><?php echo number_format($count); ?></span>
                            </a>
                        </li>
                    <?php } ?>
                </ul>
            </section>

            <section class="admin-dashboard-panel" aria-labelledby="status-title">
                <header class="admin-dashboard-panel-header">
                    <div>
                        <h2 id="status-title">Booking outcomes</h2>
                        <p>Completed, cancelled and refunded bookings started in <?php echo $e($periodLabel); ?>.</p>
                    </div>
                    <span class="admin-dashboard-panel-icon" aria-hidden="true"><i class="bi bi-pie-chart"></i></span>
                </header>

                <div class="admin-dashboard-panel-body">
                    <?php if ($statusTotal === 0) { ?>
                        <p class="admin-dashboard-empty admin-dashboard-empty-inline">No completed, cancelled or refunded bookings in this period.</p>
                    <?php } else { ?>
                        <div class="admin-dashboard-chart admin-dashboard-chart-sm">
                            <canvas id="status-chart" role="img" aria-label="Doughnut chart of bookings by status"></canvas>
                        </div>
                    <?php } ?>
                    <ul class="admin-dashboard-legend">
                        <?php foreach ($dashboard['status'] as $status => $count) { ?>
                            <li>
                                <span class="admin-dashboard-swatch admin-dashboard-swatch-<?php echo strtolower($e($status)); ?>" aria-hidden="true"></span>
                                <a href="<?php echo $e($bookingsBaseUrl . $status . '?created=' . rawurlencode($createdRange)); ?>"><?php echo $e($status); ?></a>
                                <strong><?php echo number_format($count); ?></strong>
                            </li>
                        <?php } ?>
                    </ul>
                </div>
            </section>

            <section class="admin-dashboard-panel" aria-labelledby="top-tours-title">
                <header class="admin-dashboard-panel-header">
                    <div>
                        <h2 id="top-tours-title">Top tours</h2>
                        <p>By net revenue from bookings paid in <?php echo $e($periodLabel); ?>.</p>
                    </div>
                    <span class="admin-dashboard-panel-icon" aria-hidden="true"><i class="bi bi-trophy"></i></span>
                </header>

                <div class="admin-dashboard-panel-body">
                    <?php if (empty($dashboard['topTours'])) { ?>
                        <p class="admin-dashboard-empty admin-dashboard-empty-inline">No paid bookings in this period yet.</p>
                    <?php } else { ?>
                        <div class="admin-dashboard-chart" data-chart-bars="<?php echo count($dashboard['topTours']); ?>">
                            <canvas id="top-tours-chart" role="img" aria-label="Bar chart of the top tours by net revenue"></canvas>
                        </div>
                        <ol class="visually-hidden">
                            <?php foreach ($dashboard['topTours'] as $tour) { ?>
                                <li><?php echo $e($tour['name']); ?>: <?php echo $e($money($tour['revenue'])); ?> from <?php echo (int) $tour['bookings']; ?> <?php echo $plural($tour['bookings'], 'booking'); ?></li>
                            <?php } ?>
                        </ol>
                    <?php } ?>
                </div>
            </section>
        </div>
    </div>

    <header class="admin-dashboard-section-heading">
        <h2 id="catalogue-title">Catalogue and content</h2>
        <p>Manage the tours, people, pages and settings behind the website.</p>
    </header>

    <section class="admin-dashboard-kpis" aria-labelledby="catalogue-title">
        <?php foreach ($primaryItems as $item) { ?>
            <article class="admin-dashboard-kpi admin-dashboard-kpi-<?php echo $e($item['tone']); ?>">
                <header>
                    <h3><a href="<?php echo $e($item['url']); ?>"><?php echo $e($item['label']); ?></a></h3>
                    <span class="admin-dashboard-kpi-icon" aria-hidden="true"><i class="bi <?php echo $e($item['icon']); ?>"></i></span>
                </header>
                <p class="admin-dashboard-kpi-value"><?php echo number_format((int) $item['value']); ?></p>
                <p class="admin-dashboard-kpi-note"><span><?php echo $e($item['description']); ?></span></p>
            </article>
        <?php } ?>
    </section>

    <div class="admin-dashboard-sections">
        <?php foreach ($linkPanels as $panel) { ?>
            <section class="admin-dashboard-panel" aria-labelledby="<?php echo $e($panel['id']); ?>">
                <header class="admin-dashboard-panel-header">
                    <div>
                        <h2 id="<?php echo $e($panel['id']); ?>"><?php echo $e($panel['title']); ?></h2>
                        <p><?php echo $e($panel['description']); ?></p>
                    </div>
                    <span class="admin-dashboard-panel-icon" aria-hidden="true"><i class="bi <?php echo $e($panel['icon']); ?>"></i></span>
                </header>

                <div class="admin-dashboard-links">
                    <?php foreach ($panel['items'] as $item) { ?>
                        <a class="admin-dashboard-link" href="<?php echo $e($item['url']); ?>">
                            <span class="admin-dashboard-link-icon"><i class="bi <?php echo $e($item['icon']); ?>" aria-hidden="true"></i></span>
                            <span class="admin-dashboard-link-label"><?php echo $e($item['label']); ?></span>
                            <strong class="admin-dashboard-link-value"><?php echo isset($item['value']) ? number_format((int) $item['value']) : ''; ?></strong>
                            <i class="bi bi-chevron-right admin-dashboard-link-arrow" aria-hidden="true"></i>
                        </a>
                    <?php } ?>
                </div>
            </section>
        <?php } ?>
    </div>
</section>

<script type="application/json" id="dashboard-chart-data"><?php echo json_encode($chartData, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT); ?></script>
