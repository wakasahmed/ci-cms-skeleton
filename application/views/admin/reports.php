<?php
$e = function ($value) {
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
};
$hours = function ($minutes) {
    $minutes = (int) $minutes;
    $text = floor($minutes / 60).' h';

    return $minutes % 60 > 0 ? $text.' '.($minutes % 60).' min' : $text;
};
$summary = $report['summary'];
$reportUrl = base_url('manage/'.$this->controller);
$exportUrl = $reportUrl.'/export?'.http_build_query($query);
$period = date(ADMIN_DATE_FORMAT, strtotime($filters['from'])).' – '.date(ADMIN_DATE_FORMAT, strtotime($filters['to']));
$hasFilters = $filters['status'] !== '' || $filters['artist'] > 0 || $filters['service'] > 0;
$filterNames = array();

if ($filters['status'] !== '') {
    $filterNames[] = $filters['status'];
}
foreach ($artists as $artist) {
    if ((int) $artist['artist_id'] === $filters['artist']) {
        $filterNames[] = $artist['artist_name'];
    }
}
foreach ($services as $service) {
    if ((int) $service['service_id'] === $filters['service']) {
        $filterNames[] = $service['service_name'];
    }
}

$cards = array(
    array(
        'label' => 'Appointments',
        'value' => number_format($summary['appointments']),
        'icon' => 'bi-calendar-check',
        'tone' => 'purple',
        'note' => $summary['status']['Confirmed'].' confirmed · '.$summary['status']['Completed'].' completed · '.$summary['status']['New'].' new',
    ),
    array(
        'label' => 'Booked hours',
        'value' => $hours($summary['minutes']),
        'icon' => 'bi-clock-history',
        'tone' => 'blue',
        'note' => 'Service time, excluding cancelled appointments',
    ),
    array(
        'label' => 'Estimated value',
        'value' => admin_format_price($summary['value']),
        'icon' => 'bi-cash-coin',
        'tone' => 'green',
        'note' => 'From list prices, excluding cancelled appointments',
    ),
    array(
        'label' => 'Cancelled',
        'value' => number_format($summary['status']['Cancelled']),
        'icon' => 'bi-calendar-x',
        'tone' => 'red',
        'note' => admin_format_price($summary['cancelled_value']).' not taken',
    ),
);

$groupTables = array(
    array('id' => 'report-artists', 'title' => 'By artist', 'column' => 'Artist', 'rows' => $report['by_artist']),
    array('id' => 'report-services', 'title' => 'By service', 'column' => 'Service', 'rows' => $report['by_service']),
);
?>
<?php $this->load->view('admin/partials/breadcrumb', array(
    'items' => array(
        array('label' => $this->moduleName, 'active' => TRUE),
    ),
)); ?>

<section class="admin-records-listing admin-report" aria-labelledby="reports-title">
    <?php $this->load->view('admin/partials/module_header', array(
        'title' => $this->moduleName,
        'description' => $this->moduleDesc,
        'id' => 'reports-title',
        'actions' => array(
            array(
                'url' => $exportUrl,
                'label' => 'Export CSV',
                'icon' => 'bi-download',
                'class' => 'btn-outline-secondary',
            ),
        ),
        'actions_view' => 'admin/partials/print_button',
    )); ?>

    <form class="admin-report-filters admin-no-print" method="get" action="<?php echo $reportUrl; ?>">
        <div class="row g-2 align-items-end">
            <div class="col-md-6 col-xl-4">
                <label class="form-label" for="report_range">Appointment dates</label>
                <input
                    type="text"
                    class="form-control daterange"
                    id="report_range"
                    name="range"
                    value="<?php echo $e($query['range']); ?>"
                    autocomplete="off"
                >
            </div>
            <div class="col-sm-6 col-md-3 col-xl-2">
                <label class="form-label" for="report_status">Status</label>
                <select class="form-select select2" id="report_status" name="status" data-minimum-results-for-search="-1">
                    <option value="">All statuses</option>
                    <?php foreach ($statuses as $status) { ?>
                        <option value="<?php echo $e($status); ?>" <?php echo $filters['status'] === $status ? 'selected' : ''; ?>><?php echo $e($status); ?></option>
                    <?php } ?>
                </select>
            </div>
            <div class="col-sm-6 col-md-3 col-xl-2">
                <label class="form-label" for="report_artist">Artist</label>
                <select class="form-select select2" id="report_artist" name="artist">
                    <option value="0">All artists</option>
                    <?php foreach ($artists as $artist) { ?>
                        <option value="<?php echo (int) $artist['artist_id']; ?>" <?php echo (int) $artist['artist_id'] === $filters['artist'] ? 'selected' : ''; ?>>
                            <?php echo $e($artist['artist_name']); ?><?php echo $artist['artist_status'] === 'Disable' ? ' (disabled)' : ''; ?>
                        </option>
                    <?php } ?>
                </select>
            </div>
            <div class="col-sm-6 col-md-6 col-xl-2">
                <label class="form-label" for="report_service">Service</label>
                <select class="form-select select2" id="report_service" name="service">
                    <option value="0">All services</option>
                    <?php foreach ($services as $service) { ?>
                        <option value="<?php echo (int) $service['service_id']; ?>" <?php echo (int) $service['service_id'] === $filters['service'] ? 'selected' : ''; ?>>
                            <?php echo $e($service['service_name']); ?>
                        </option>
                    <?php } ?>
                </select>
            </div>
            <div class="col-sm-6 col-md-6 col-xl-2 d-flex gap-2">
                <button type="submit" class="btn btn-primary flex-grow-1">
                    <i class="bi bi-funnel" aria-hidden="true"></i> Show
                </button>
                <?php if ($hasFilters) { ?>
                    <a class="btn btn-outline-secondary" href="<?php echo $reportUrl.'?'.http_build_query(array('range' => $query['range'])); ?>">Clear</a>
                <?php } ?>
            </div>
        </div>
        <nav class="admin-report-presets" aria-label="Quick date ranges">
            <?php foreach ($presets as $label => $range) {
                $presetQuery = array_merge($query, array('range' => $range));
                ?>
                <a
                    class="btn btn-sm <?php echo $range === $query['range'] ? 'btn-secondary' : 'btn-outline-secondary'; ?>"
                    href="<?php echo $reportUrl.'?'.$e(http_build_query($presetQuery)); ?>"
                    <?php echo $range === $query['range'] ? 'aria-current="true"' : ''; ?>
                ><?php echo $e($label); ?></a>
            <?php } ?>
        </nav>
    </form>

    <p class="admin-report-period">
        <strong><?php echo $e($period); ?></strong>
        <?php if (!empty($filterNames)) { ?>
            · <?php echo $e(implode(' · ', $filterNames)); ?>
        <?php } ?>
    </p>

    <section class="admin-dashboard-kpis" aria-label="Summary for <?php echo $e($period); ?>">
        <?php foreach ($cards as $card) { ?>
            <article class="admin-dashboard-kpi admin-dashboard-kpi-<?php echo $e($card['tone']); ?>">
                <header>
                    <h2><?php echo $e($card['label']); ?></h2>
                    <span class="admin-dashboard-kpi-icon" aria-hidden="true"><i class="bi <?php echo $e($card['icon']); ?>"></i></span>
                </header>
                <p class="admin-dashboard-kpi-value"><?php echo $e($card['value']); ?></p>
                <p class="admin-dashboard-kpi-note"><span><?php echo $e($card['note']); ?></span></p>
            </article>
        <?php } ?>
    </section>

    <div class="row">
        <?php foreach ($groupTables as $table) { ?>
            <div class="col-xl-6">
                <section class="card admin-card" aria-labelledby="<?php echo $e($table['id']); ?>-title">
                    <div class="card-header">
                        <h2 class="card-title mb-0" id="<?php echo $e($table['id']); ?>-title"><?php echo $e($table['title']); ?></h2>
                    </div>
                    <div class="table-responsive pages-table-responsive" tabindex="0" aria-labelledby="<?php echo $e($table['id']); ?>-title">
                        <table class="table pages-listing-table admin-records-table admin-report-table mb-0">
                            <thead>
                                <tr>
                                    <th scope="col"><?php echo $e($table['column']); ?></th>
                                    <th scope="col" class="text-end">Appointments</th>
                                    <th scope="col" class="text-end">Hours</th>
                                    <th scope="col" class="text-end">Value</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (!empty($table['rows'])) { ?>
                                    <?php foreach ($table['rows'] as $row) { ?>
                                        <tr>
                                            <th scope="row"><?php echo $e($row['label']); ?></th>
                                            <td class="text-end"><?php echo (int) $row['appointments']; ?></td>
                                            <td class="text-end"><?php echo $e($hours($row['minutes'])); ?></td>
                                            <td class="text-end"><?php echo $e(admin_format_price($row['value'])); ?></td>
                                        </tr>
                                    <?php } ?>
                                <?php } else { ?>
                                    <tr>
                                        <td class="pages-empty-state" colspan="4"><span>Nothing booked for these filters.</span></td>
                                    </tr>
                                <?php } ?>
                            </tbody>
                        </table>
                    </div>
                </section>
            </div>
        <?php } ?>
    </div>

    <section class="card admin-card" aria-labelledby="report-appointments-title">
        <div class="card-header">
            <h2 class="card-title mb-0" id="report-appointments-title">Appointments (<?php echo (int) $summary['appointments']; ?>)</h2>
        </div>
        <div class="table-responsive pages-table-responsive" tabindex="0" aria-labelledby="report-appointments-title">
            <table class="table pages-listing-table admin-records-table admin-report-table mb-0">
                <thead>
                    <tr>
                        <th scope="col">Appointment</th>
                        <th scope="col">Reference</th>
                        <th scope="col">Client</th>
                        <th scope="col">Services</th>
                        <th scope="col">Status</th>
                        <th scope="col" class="text-end">Value</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (!empty($report['appointments'])) { ?>
                        <?php foreach ($report['appointments'] as $appointment) {
                            $start = strtotime($appointment['date'].' '.$appointment['time']);
                            ?>
                            <tr>
                                <td>
                                    <time datetime="<?php echo date('c', $start); ?>">
                                        <?php echo date(ADMIN_DATE_FORMAT, $start); ?>
                                        <span class="pages-cell-meta"><?php echo date(ADMIN_TIME_FORMAT, $start); ?></span>
                                    </time>
                                </td>
                                <td>
                                    <a class="pages-name-link" href="<?php echo base_url('manage/appointments/view/'.(int) $appointment['id']); ?>"><?php echo $e($appointment['reference']); ?></a>
                                </td>
                                <td>
                                    <?php echo $e($appointment['client']); ?>
                                    <span class="pages-cell-meta"><?php echo $e($appointment['email']); ?></span>
                                </td>
                                <td>
                                    <?php foreach ($appointment['services'] as $service) { ?>
                                        <span class="d-block">
                                            <?php echo $e($service['name']); ?>
                                            <?php if ($service['artist'] !== '') { ?>
                                                <span class="pages-cell-meta d-inline">with <?php echo $e($service['artist']); ?></span>
                                            <?php } ?>
                                        </span>
                                    <?php } ?>
                                </td>
                                <td><span class="badge <?php echo admin_appointment_status_badge($appointment['status']); ?>"><?php echo $e($appointment['status']); ?></span></td>
                                <td class="text-end"><?php echo $e(admin_format_price($appointment['value'])); ?></td>
                            </tr>
                        <?php } ?>
                    <?php } else { ?>
                        <tr>
                            <td class="pages-empty-state" colspan="6">
                                <strong>No appointments in this period<?php echo $hasFilters ? ' for these filters' : ''; ?>.</strong>
                                <span>Choose other dates<?php echo $hasFilters ? ' or clear the filters' : ''; ?> above.</span>
                            </td>
                        </tr>
                    <?php } ?>
                </tbody>
            </table>
        </div>
    </section>
</section>
