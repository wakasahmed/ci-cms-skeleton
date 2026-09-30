<?php
$period = $dashboard['period'];
$kpi = $dashboard['kpi'];
$attention = $dashboard['attention'];
$catalogue = $dashboard['catalogue'];
$statusTotal = array_sum($dashboard['status']);
$rangeDays = (int) $dashboardRange;
$periodLabel = 'the last '.$rangeDays.' days';
$appointmentsUrl = ADMIN_URL.'appointments/index/';

$e = function ($value) {
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
};

$plural = function ($count, $singular, $pluralForm = NULL) {
    if ((int) $count === 1) {
        return $singular;
    }

    return $pluralForm !== NULL ? $pluralForm : $singular.'s';
};

/**
 * Trend pill against the previous period. $change is NULL when there was
 * nothing to compare with.
 */
$trend = function ($change) use ($e, $rangeDays) {
    if ($change === NULL) {
        $class = 'is-up';
        $icon = 'bi-arrow-up-right';
        $text = 'New';
    } elseif ($change === 0) {
        $class = 'is-flat';
        $icon = 'bi-dash';
        $text = '0%';
    } else {
        $isUp = $change > 0;
        $class = $isUp ? 'is-up' : 'is-down';
        $icon = $isUp ? 'bi-arrow-up-right' : 'bi-arrow-down-right';
        $text = ($isUp ? '+' : '').$change.'%';
    }

    return '<span class="admin-dashboard-trend '.$class.'" title="Compared with the previous '.(int) $rangeDays.' days">'
        .'<i class="bi '.$icon.'" aria-hidden="true"></i> '.$e($text)
        .'<span class="visually-hidden"> compared with the previous '.(int) $rangeDays.' days</span></span>';
};

$statusClass = function ($status) {
    $classes = array(
        'New' => 'status-warning',
        'Confirmed' => 'status-published',
        'Completed' => 'status-enabled',
        'Cancelled' => 'status-disabled',
    );

    return isset($classes[$status]) ? $classes[$status] : 'status-warning';
};

$appointmentMoment = function ($row) {
    $timestamp = strtotime($row['appointment_date'].' '.$row['appointment_time']);

    return $timestamp ? $timestamp : NULL;
};

$kpiCards = array(
    array(
        'label' => 'Awaiting reply',
        'value' => number_format($kpi['awaiting']),
        'icon' => 'bi-hourglass-split',
        'tone' => 'orange',
        'trend' => '',
        'note' => 'New appointments not yet confirmed or cancelled',
        'url' => $appointmentsUrl.'appointment_added/ASC/New',
    ),
    array(
        'label' => 'Bookings received',
        'value' => number_format($kpi['requests']),
        'icon' => 'bi-calendar-plus',
        'tone' => 'purple',
        'trend' => $trend($kpi['trend']['requests']),
        'note' => 'Bookings in '.$periodLabel,
        'url' => ADMIN_URL.'appointments',
    ),
    array(
        'label' => 'Confirmed this week',
        'value' => number_format($kpi['upcoming']),
        'icon' => 'bi-calendar-check',
        'tone' => 'green',
        'trend' => '',
        'note' => 'Confirmed appointments in the next '.Dashboard_model::UPCOMING_DAYS.' days',
        'url' => $appointmentsUrl.'appointment_date/ASC/Confirmed',
    ),
    array(
        'label' => 'Contact messages',
        'value' => number_format($kpi['messages']),
        'icon' => 'bi-envelope',
        'tone' => 'blue',
        'trend' => $trend($kpi['trend']['messages']),
        'note' => 'Messages in '.$periodLabel,
        'url' => ADMIN_URL.'contact-requests',
    ),
);

$attentionItems = array(
    array(
        'title' => 'New appointments waiting over '.Dashboard_model::REPLY_WITHIN_HOURS.' hours',
        'count' => $attention['waiting'],
        'icon' => 'bi-hourglass-bottom',
        'url' => $appointmentsUrl.'appointment_added/ASC/New',
        'ok' => 'Every new appointment has been answered within a day.',
        'warn' => 'Call or email these clients to confirm a time.',
    ),
    array(
        'title' => 'New appointments for dates already passed',
        'count' => $attention['new_past'],
        'icon' => 'bi-calendar-x',
        'url' => $appointmentsUrl.'appointment_date/ASC/New',
        'ok' => 'No unanswered appointments are out of date.',
        'warn' => 'Contact these clients to rebook, or cancel the appointment.',
    ),
    array(
        'title' => 'Past appointments still marked Confirmed',
        'count' => $attention['confirmed_past'],
        'icon' => 'bi-check2-square',
        'url' => $appointmentsUrl.'appointment_date/ASC/Confirmed',
        'ok' => 'Past appointments are all up to date.',
        'warn' => 'Mark them Completed or Cancelled.',
        'info' => TRUE,
    ),
    array(
        'title' => 'Offers ending within '.Dashboard_model::OFFER_ENDING_DAYS.' days',
        'count' => $attention['offers_ending'],
        'icon' => 'bi-gift',
        'url' => ADMIN_URL.'offers/index/offer_valid_to/ASC/Enable',
        'ok' => 'No running offer ends this week.',
        'warn' => 'Extend or replace them if they should continue.',
        'info' => TRUE,
    ),
    array(
        'title' => 'Placeholder artist profiles on the website',
        'count' => $attention['placeholder_artists'],
        'icon' => 'bi-person-exclamation',
        'url' => ADMIN_URL.'artists',
        'ok' => 'Every visible artist profile is complete.',
        'warn' => 'Add the real name, photo and details, or disable the profile.',
    ),
);

$catalogueCards = array(
    array('label' => 'Services', 'description' => 'Enabled services on the menu', 'value' => $catalogue['services'], 'url' => ADMIN_URL.'services', 'icon' => 'bi-stars', 'tone' => 'purple'),
    array('label' => 'Artists', 'description' => 'Team members shown on the website', 'value' => $catalogue['artists'], 'url' => ADMIN_URL.'artists', 'icon' => 'bi-person-heart', 'tone' => 'blue'),
    array('label' => 'Gallery', 'description' => 'Photos shown in the gallery', 'value' => $catalogue['gallery'], 'url' => ADMIN_URL.'gallery', 'icon' => 'bi-images', 'tone' => 'green'),
    array('label' => 'Offers', 'description' => 'Offers running today', 'value' => $catalogue['offers'], 'url' => ADMIN_URL.'offers', 'icon' => 'bi-gift', 'tone' => 'orange'),
);

$linkPanels = array(
    array(
        'id' => 'website-content-title',
        'title' => 'Website content',
        'description' => 'Keep public-facing pages and media up to date.',
        'icon' => 'bi-window-stack',
        'items' => array(
            array('label' => 'Web Pages', 'value' => $catalogue['pages'], 'url' => ADMIN_URL.'pages', 'icon' => 'bi-file-earmark-text'),
            array('label' => 'Image Sliders', 'value' => $catalogue['sliders'], 'url' => ADMIN_URL.'sliders', 'icon' => 'bi-images'),
            array('label' => 'Journal Posts', 'value' => $catalogue['blogs'], 'url' => ADMIN_URL.'blogs', 'icon' => 'bi-journal-text'),
        ),
    ),
    array(
        'id' => 'communication-title',
        'title' => 'Clients',
        'description' => 'What clients say and ask.',
        'icon' => 'bi-chat-heart',
        'items' => array(
            array('label' => 'Customer Reviews', 'value' => $catalogue['reviews'], 'url' => ADMIN_URL.'customer-reviews', 'icon' => 'bi-star'),
            array('label' => 'FAQs', 'value' => $catalogue['faqs'], 'url' => ADMIN_URL.'faqs-categories', 'icon' => 'bi-question-circle'),
            array('label' => 'Email Templates', 'url' => ADMIN_URL.'email-templates', 'icon' => 'bi-envelope-paper'),
        ),
    ),
    array(
        'id' => 'administration-title',
        'title' => 'Website administration',
        'description' => 'Navigation, users and site-wide settings.',
        'icon' => 'bi-gear',
        'items' => array(
            array('label' => 'Menu Manager', 'url' => ADMIN_URL.'menu', 'icon' => 'bi-diagram-3'),
            array('label' => 'Admin Users', 'value' => $catalogue['admins'], 'url' => ADMIN_URL.'admins', 'icon' => 'bi-people'),
            array('label' => 'Website Settings', 'url' => ADMIN_URL.'website-settings', 'icon' => 'bi-gear'),
        ),
    ),
);

$chartData = array(
    'days' => $rangeDays,
    'labels' => $dashboard['series']['labels'],
    'requests' => $dashboard['series']['requests'],
    'status' => array(
        'labels' => array_keys($dashboard['status']),
        'values' => array_values($dashboard['status']),
    ),
    'topServices' => array(
        'labels' => array_column($dashboard['topServices'], 'name'),
        'requests' => array_map('intval', array_column($dashboard['topServices'], 'requests')),
    ),
);
?>

<?php $this->load->view('admin/partials/breadcrumb', array(
    'show_home' => FALSE,
    'items' => array(
        array('label' => 'Dashboard', 'active' => TRUE),
    ),
)); ?>

<section class="admin-dashboard" aria-labelledby="dashboard-title">
    <?php $this->load->view('admin/partials/module_header', array(
        'title' => 'Dashboard',
        'description' => 'Bookings, client messages and the services, team and content behind the website.',
        'id' => 'dashboard-title',
    )); ?>

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
                    href="<?php echo $e(ADMIN_URL.'?range='.(int) $days); ?>"
                    class="<?php echo (int) $days === $rangeDays ? 'is-active' : ''; ?>"
                    <?php if ((int) $days === $rangeDays) { ?>aria-current="true"<?php } ?>
                ><?php echo (int) $days; ?> days</a>
            <?php } ?>
        </nav>
    </div>

    <section class="admin-dashboard-kpis" aria-label="Booking summary for <?php echo $e($periodLabel); ?>">
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
            <section class="admin-dashboard-panel" aria-labelledby="requests-chart-title">
                <header class="admin-dashboard-panel-header">
                    <div>
                        <h2 id="requests-chart-title">Bookings</h2>
                        <p>Bookings received each day in <?php echo $e($periodLabel); ?>.</p>
                    </div>
                    <span class="admin-dashboard-panel-icon" aria-hidden="true"><i class="bi bi-graph-up-arrow"></i></span>
                </header>

                <div class="admin-dashboard-panel-body">
                    <dl class="admin-dashboard-chart-stats">
                        <div>
                            <dt>Bookings received</dt>
                            <dd><?php echo $e(number_format($kpi['requests'])); ?></dd>
                        </div>
                    </dl>
                    <div class="admin-dashboard-chart admin-dashboard-chart-lg">
                        <canvas id="requests-chart" role="img" aria-label="Line chart of bookings received per day over <?php echo $e($periodLabel); ?>"></canvas>
                    </div>
                    <p class="admin-dashboard-chart-note" hidden data-chart-empty>No bookings to plot yet for this period.</p>
                </div>
            </section>

            <section class="admin-dashboard-panel" aria-labelledby="upcoming-title">
                <header class="admin-dashboard-panel-header">
                    <div>
                        <h2 id="upcoming-title">Coming up</h2>
                        <p>New and confirmed appointments from today, soonest first.</p>
                    </div>
                    <a class="btn btn-sm btn-primary" href="<?php echo $e($appointmentsUrl.'appointment_date/ASC'); ?>">View all</a>
                </header>

                <?php if (empty($dashboard['upcoming'])) { ?>
                    <p class="admin-dashboard-empty">No appointments are booked from today onwards.</p>
                <?php } else { ?>
                    <div class="table-responsive">
                        <table class="table table-hover mb-0">
                            <caption class="visually-hidden">New and confirmed appointments from today</caption>
                            <thead>
                                <tr>
                                    <th scope="col">Date and time</th>
                                    <th scope="col">Client</th>
                                    <th scope="col" class="d-none d-md-table-cell">Artist</th>
                                    <th scope="col">Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($dashboard['upcoming'] as $row) { ?>
                                    <?php
                                    $viewUrl = ADMIN_URL.'appointments/view/'.(int) $row['appointment_id'];
                                    $moment = $appointmentMoment($row);
                                    ?>
                                    <tr class="admin-dashboard-row-link" data-href="<?php echo $e($viewUrl); ?>">
                                        <td>
                                            <?php if ($moment) { ?>
                                                <strong><?php echo $e(date(ADMIN_DATE_FORMAT, $moment)); ?></strong>
                                                <span class="admin-dashboard-sub"><?php echo $e(date(ADMIN_TIME_FORMAT, $moment)); ?></span>
                                            <?php } ?>
                                        </td>
                                        <td>
                                            <a class="admin-dashboard-row-title" href="<?php echo $e($viewUrl); ?>"><?php echo $e($row['customer_name']); ?></a>
                                            <span class="admin-dashboard-sub"><?php echo $e($row['services']); ?></span>
                                        </td>
                                        <td class="d-none d-md-table-cell">
                                            <?php echo $row['appointment_artist_name'] !== NULL && $row['appointment_artist_name'] !== '' ? $e($row['appointment_artist_name']) : '<span class="admin-dashboard-sub">Any artist</span>'; ?>
                                        </td>
                                        <td><span class="status-badge <?php echo $statusClass($row['appointment_status']); ?>"><?php echo $e($row['appointment_status']); ?></span></td>
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
                        <h2 id="recent-title">Latest bookings</h2>
                        <p>The most recent bookings from the website.</p>
                    </div>
                    <a class="btn btn-sm btn-primary" href="<?php echo $e(ADMIN_URL.'appointments'); ?>">View all</a>
                </header>

                <?php if (empty($dashboard['recent'])) { ?>
                    <p class="admin-dashboard-empty">No bookings have been received yet.</p>
                <?php } else { ?>
                    <div class="table-responsive">
                        <table class="table table-hover mb-0">
                            <caption class="visually-hidden">Most recent bookings</caption>
                            <thead>
                                <tr>
                                    <th scope="col">Client</th>
                                    <th scope="col" class="d-none d-md-table-cell">Services</th>
                                    <th scope="col">Appointment</th>
                                    <th scope="col">Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($dashboard['recent'] as $row) { ?>
                                    <?php
                                    $viewUrl = ADMIN_URL.'appointments/view/'.(int) $row['appointment_id'];
                                    $moment = $appointmentMoment($row);
                                    ?>
                                    <tr class="admin-dashboard-row-link" data-href="<?php echo $e($viewUrl); ?>">
                                        <td>
                                            <a class="admin-dashboard-row-title" href="<?php echo $e($viewUrl); ?>"><?php echo $e($row['customer_name']); ?></a>
                                            <span class="admin-dashboard-sub"><?php echo $e($row['appointment_reference']); ?> &middot; <?php echo $e(date(ADMIN_DATETIME_FORMAT, strtotime($row['appointment_added']))); ?></span>
                                        </td>
                                        <td class="d-none d-md-table-cell"><?php echo $e($row['services']); ?></td>
                                        <td><?php echo $moment ? $e(date(ADMIN_DATE_FORMAT, $moment)) : '&mdash;'; ?></td>
                                        <td><span class="status-badge <?php echo $statusClass($row['appointment_status']); ?>"><?php echo $e($row['appointment_status']); ?></span></td>
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
                        <p>Appointments, offers and profiles to follow up.</p>
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
                        <p>Current status of bookings received in <?php echo $e($periodLabel); ?>.</p>
                    </div>
                    <span class="admin-dashboard-panel-icon" aria-hidden="true"><i class="bi bi-pie-chart"></i></span>
                </header>

                <div class="admin-dashboard-panel-body">
                    <?php if ($statusTotal === 0) { ?>
                        <p class="admin-dashboard-empty admin-dashboard-empty-inline">No bookings were received in this period.</p>
                    <?php } else { ?>
                        <div class="admin-dashboard-chart admin-dashboard-chart-sm">
                            <canvas id="status-chart" role="img" aria-label="Doughnut chart of bookings by status"></canvas>
                        </div>
                    <?php } ?>
                    <ul class="admin-dashboard-legend">
                        <?php foreach ($dashboard['status'] as $status => $count) { ?>
                            <li>
                                <span class="admin-dashboard-swatch admin-dashboard-swatch-<?php echo strtolower($e($status)); ?>" aria-hidden="true"></span>
                                <a href="<?php echo $e($appointmentsUrl.'appointment_added/DESC/'.$status); ?>"><?php echo $e($status); ?></a>
                                <strong><?php echo number_format($count); ?></strong>
                            </li>
                        <?php } ?>
                    </ul>
                </div>
            </section>

            <section class="admin-dashboard-panel" aria-labelledby="top-services-title">
                <header class="admin-dashboard-panel-header">
                    <div>
                        <h2 id="top-services-title">Most booked services</h2>
                        <p>From bookings received in <?php echo $e($periodLabel); ?>, excluding cancelled ones.</p>
                    </div>
                    <span class="admin-dashboard-panel-icon" aria-hidden="true"><i class="bi bi-trophy"></i></span>
                </header>

                <div class="admin-dashboard-panel-body">
                    <?php if (empty($dashboard['topServices'])) { ?>
                        <p class="admin-dashboard-empty admin-dashboard-empty-inline">No services have been booked in this period yet.</p>
                    <?php } else { ?>
                        <div class="admin-dashboard-chart" data-chart-bars="<?php echo count($dashboard['topServices']); ?>">
                            <canvas id="top-services-chart" role="img" aria-label="Bar chart of the most booked services"></canvas>
                        </div>
                        <ol class="visually-hidden">
                            <?php foreach ($dashboard['topServices'] as $service) { ?>
                                <li><?php echo $e($service['name']); ?>: <?php echo (int) $service['requests']; ?> <?php echo $plural($service['requests'], 'booking'); ?></li>
                            <?php } ?>
                        </ol>
                    <?php } ?>
                </div>
            </section>
        </div>
    </div>

    <header class="admin-dashboard-section-heading">
        <h2 id="catalogue-title">Salon and content</h2>
        <p>Manage the services, team, gallery and pages behind the website.</p>
    </header>

    <section class="admin-dashboard-kpis" aria-labelledby="catalogue-title">
        <?php foreach ($catalogueCards as $item) { ?>
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
