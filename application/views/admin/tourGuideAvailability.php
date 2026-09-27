<?php
$hasActiveFilters = ($keywords !== '-' || (string) $tour_guide_id !== '0' || $date_start !== '-' || $date_end !== '-' || $book_status !== '-');
$manageAvailabilityUrl = base_url('manage/'.$this->controller.'/manageAvailability');
$dateRangeDisplay = ($date_start !== '-' && $date_end !== '-')
    ? date('d-M-Y', strtotime($date_start)).' to '.date('d-M-Y', strtotime($date_end))
    : '';
$sortUrl = function ($column) use ($order, $keywords, $tour_guide_id, $date_start, $date_end, $book_status, $page_numb) {
    return base_url(
        'manage/'.$this->controller.'/index/'.$column.'/'.$order.'/'.rawurlencode($keywords).'/'.$tour_guide_id.'/'.$date_start.'/'.$date_end.'/'.$book_status.'/'.$page_numb
    );
};
$sortIcon = function ($column) use ($sortby, $order) {
    if ($sortby !== $column) {
        return 'bi-arrow-down-up';
    }

    return $order === 'DESC' ? 'bi-arrow-up' : 'bi-arrow-down';
};
$sortAria = function ($column) use ($sortby, $order) {
    if ($sortby !== $column) {
        return '';
    }

    return $order === 'DESC' ? 'ascending' : 'descending';
};
?>

<?php $this->load->view('admin/partials/breadcrumb', array('class' => 'availability-breadcrumb', 'items' => array(array('label' => $this->moduleName, 'active' => TRUE)))); ?>

<?php if ($alert === 'success') { ?>
<div class="row alertrow"><div class="col-md-12"><button class="btn-close alertBox" data-bs-dismiss="alert">x</button><div class="alert alert-success"><strong>Success!</strong> Availability date added successfully.</div></div></div>
<?php } if ($alert === 'deletesuccess') { ?>
<div class="row alertrow"><div class="col-md-12"><button class="btn-close alertBox" data-bs-dismiss="alert">x</button><div class="alert alert-success"><strong>Success!</strong> Availability date deleted successfully.</div></div></div>
<?php } if ($alert === 'deleteerror') { ?>
<div class="row alertrow"><div class="col-md-12"><button class="btn-close alertBox" data-bs-dismiss="alert">x</button><div class="alert alert-danger"><strong>Error!</strong> An error occurred while deleting the record. Please try again.</div></div></div>
<?php } if ($alert === 'removesuccess') { ?>
<div class="row alertrow"><div class="col-md-12"><button class="btn-close alertBox" data-bs-dismiss="alert">x</button><div class="alert alert-success"><strong>Success!</strong> Availability removed successfully.</div></div></div>
<?php } if ($alert === 'error') { ?>
<div class="row alertrow"><div class="col-md-12"><button class="btn-close alertBox" data-bs-dismiss="alert">x</button><div class="alert alert-danger"><strong>Error!</strong> An error occurred while saving the record. Please try again.</div></div></div>
<?php } ?>

<section class="admin-records-listing availability-listing" aria-labelledby="availability-listing-title">
    <?php $this->load->view('admin/partials/module_header', array('title' => 'Guide Availability', 'description' => 'Manage tour guide dates, time slots, and bookings.', 'id' => 'availability-listing-title', 'actions' => array(array('url' => $manageAvailabilityUrl, 'label' => 'Add or Remove Availability', 'icon' => 'bi-calendar-plus', 'class' => 'btn-primary pages-add-button')))); ?>

    <div class="pages-listing-toolbar">
        <form class="pages-filter-form" id="availability-filter-form" role="search">
            <div class="pages-search-control">
                <label class="visually-hidden" for="search_keywords">Search availability</label>
                <span class="pages-search-icon" aria-hidden="true"><i class="bi bi-search"></i></span>
                <input class="form-control" type="search" value="<?php echo ($keywords !== '-') ? htmlspecialchars($keywords, ENT_QUOTES, 'UTF-8') : ''; ?>" name="search_keywords" id="search_keywords" placeholder="Search availability..." autocomplete="off">
            </div>
            <button class="btn btn-outline-secondary pages-search-submit" type="submit"><i class="bi bi-search" aria-hidden="true"></i><span class="d-none d-sm-inline">Search</span></button>
            <div class="pages-search-control availability-date-filter-control">
                <label class="visually-hidden" for="search_dates">Search by dates</label>
                <span class="pages-search-icon" aria-hidden="true"><i class="bi bi-calendar3"></i></span>
                <input class="form-control availability-date-range" type="text" value="<?php echo htmlspecialchars($dateRangeDisplay, ENT_QUOTES, 'UTF-8'); ?>" name="search_dates" id="search_dates" placeholder="Search by dates" autocomplete="off">
                <input type="hidden" name="search_date_start" id="search_date_start" value="<?php echo ($date_start !== '-') ? htmlspecialchars($date_start, ENT_QUOTES, 'UTF-8') : ''; ?>">
                <input type="hidden" name="search_date_end" id="search_date_end" value="<?php echo ($date_end !== '-') ? htmlspecialchars($date_end, ENT_QUOTES, 'UTF-8') : ''; ?>">
            </div>
            <div class="availability-tour-guide-control">
                <label class="visually-hidden" for="search_tour_guide">Filter by tour guide</label>
                <select autocomplete="off" class="form-select select2" name="search_tour_guide" id="search_tour_guide" aria-label="Filter by tour guide">
                    <option value="0">All Tour Guides</option>
                    <?php if (isset($tour_guides) && !empty($tour_guides)) { foreach ($tour_guides as $d) { ?>
                    <option value="<?php echo $d['tour_guide_id']; ?>" <?php echo ((string) $tour_guide_id === (string) $d['tour_guide_id']) ? 'selected="selected"' : ''; ?>><?php echo htmlspecialchars($d['tour_guide_name'], ENT_QUOTES, 'UTF-8'); ?></option>
                    <?php } } ?>
                </select>
            </div>
            <div class="pages-status-control availability-book-status-control">
                <label class="visually-hidden" for="search_book_status">Filter by booking status</label>
                <select autocomplete="off" class="form-select select2" name="search_book_status" id="search_book_status" data-minimum-results-for-search="-1" aria-label="Filter by booking status">
                    <option value="-">All Booking Statuses</option>
                    <option value="Available" <?php if ($book_status === 'Available') { echo 'selected="selected"'; } ?>>Available</option>
                    <option value="Pending" <?php if ($book_status === 'Pending') { echo 'selected="selected"'; } ?>>Pending</option>
                    <option value="On-hold" <?php if ($book_status === 'On-hold') { echo 'selected="selected"'; } ?>>On-hold</option>
                    <option value="Reserved" <?php if ($book_status === 'Reserved') { echo 'selected="selected"'; } ?>>Reserved</option>
                    <option value="Unavailable" <?php if ($book_status === 'Unavailable') { echo 'selected="selected"'; } ?>>Unavailable</option>
                </select>
            </div>
            <?php if ($hasActiveFilters) { ?>
                <a class="btn btn-link pages-clear-filters" href="<?php echo base_url('manage/'.$this->controller); ?>">Clear filters</a>
            <?php } ?>
        </form>
    </div>

    <div class="pages-bulk-actions" id="availability-bulk-actions" aria-live="polite" hidden>
        <span class="pages-selection-count" id="availability-selection-count">0 selected</span>
        <button id="deleteAllRecords" class="btn btn-sm btn-outline-danger" type="button" disabled><i class="bi bi-trash" aria-hidden="true"></i> Delete Selected</button>
    </div>

    <form action="<?php echo ADMIN_URL.$this->controller; ?>/deleteall" method="post" name="multiDel" id="multiDel">
        <div class="table-responsive pages-table-responsive" tabindex="0" aria-label="Guide Availability table">
            <table id="table-<?php echo $this->controller; ?>" class="table pages-listing-table admin-records-table availability-table">
                <thead>
                    <tr>
                        <th class="pages-select-column" scope="col"><input class="form-check-input" type="checkbox" id="all-checkbox" name="all-checkbox" autocomplete="off" aria-label="Select all unbooked availability dates"></th>
                        <th class="availability-id-column d-none d-md-table-cell" scope="col"<?php if ($sortAria($this->pKey) !== '') { ?> aria-sort="<?php echo $sortAria($this->pKey); ?>"<?php } ?>><a class="pages-sort-link" href="<?php echo $sortUrl($this->pKey); ?>">ID <i class="bi <?php echo $sortIcon($this->pKey); ?> pages-sort-icon" aria-hidden="true"></i></a></th>
                        <th class="availability-guide-column" scope="col"<?php if ($sortAria('tour_guide_name') !== '') { ?> aria-sort="<?php echo $sortAria('tour_guide_name'); ?>"<?php } ?>><a class="pages-sort-link" href="<?php echo $sortUrl('tour_guide_name'); ?>">Tour Guide <i class="bi <?php echo $sortIcon('tour_guide_name'); ?> pages-sort-icon" aria-hidden="true"></i></a></th>
                        <th class="availability-date-column" scope="col"<?php if ($sortAria('avail_date') !== '') { ?> aria-sort="<?php echo $sortAria('avail_date'); ?>"<?php } ?>><a class="pages-sort-link" href="<?php echo $sortUrl('avail_date'); ?>">Date &amp; Slot <i class="bi <?php echo $sortIcon('avail_date'); ?> pages-sort-icon" aria-hidden="true"></i></a></th>
                        <th class="availability-booking-column" scope="col"<?php if ($sortAria('book_status') !== '') { ?> aria-sort="<?php echo $sortAria('book_status'); ?>"<?php } ?>><a class="pages-sort-link" href="<?php echo $sortUrl('book_status'); ?>">Booking <i class="bi <?php echo $sortIcon('book_status'); ?> pages-sort-icon" aria-hidden="true"></i></a></th>
                        <th class="availability-added-column d-none d-md-table-cell" scope="col"<?php if ($sortAria($this->colPrefix.'added') !== '') { ?> aria-sort="<?php echo $sortAria($this->colPrefix.'added'); ?>"<?php } ?>><a class="pages-sort-link" href="<?php echo $sortUrl($this->colPrefix.'added'); ?>">Created On <i class="bi <?php echo $sortIcon($this->colPrefix.'added'); ?> pages-sort-icon" aria-hidden="true"></i></a></th>
                        <th class="availability-updated-column d-none d-md-table-cell" scope="col"<?php if ($sortAria('slot_updated') !== '') { ?> aria-sort="<?php echo $sortAria('slot_updated'); ?>"<?php } ?>><a class="pages-sort-link" href="<?php echo $sortUrl('slot_updated'); ?>">Updated On <i class="bi <?php echo $sortIcon('slot_updated'); ?> pages-sort-icon" aria-hidden="true"></i></a></th>
                        <th class="availability-actions-column" scope="col">Actions</th>
                    </tr>
                </thead>
                <tbody>
                <?php if (count($records) > 0) { foreach ($records as $c) {
                    $isBooked = !empty($c['avail_book_id']) || !empty($c['book_status']);
                    $availabilityStatus = $c['avail_book_status'] === 'On-hold'
                        ? 'On-hold'
                        : (!empty($c['book_status'])
                        ? $c['book_status']
                        : (!empty($c['avail_book_status']) ? $c['avail_book_status'] : 'Available'));
                    // Pending bookings are linked via tour_bookings.book_avail_id, so
                    // avail_book_id can still be 0 for them.
                    $bookingId = !empty($c['linked_book_id'])
                        ? (int) $c['linked_book_id']
                        : (int) $c['avail_book_id'];
                    $recordId = $c[$this->pKey];
                    $guideName = htmlspecialchars($c['tour_guide_name'], ENT_QUOTES, 'UTF-8');
                    $slotName = htmlspecialchars($c['slot_title'], ENT_QUOTES, 'UTF-8');
                ?>
                    <tr id="<?php echo $this->controller.'-'.$recordId; ?>">
                        <td class="pages-select-column"><?php if (!$isBooked) { ?><input name="records[]" autocomplete="off" class="form-check-input cselect" value="<?php echo $recordId; ?>" type="checkbox" aria-label="Select <?php echo $guideName; ?> on <?php echo date(ADMIN_DATE_FORMAT, strtotime($c['avail_date'])); ?>"><?php } ?></td>
                        <td class="availability-id-column d-none d-md-table-cell"><span class="pages-record-id"><?php echo $recordId; ?></span></td>
                        <td class="availability-guide-column"><?php echo $guideName; ?></td>
                        <td class="availability-date-column"><time datetime="<?php echo date('Y-m-d', strtotime($c['avail_date'])); ?>"><?php echo date(ADMIN_DATE_FORMAT, strtotime($c['avail_date'])); ?></time><span class="pages-cell-meta"><?php echo $slotName; ?></span></td>
                        <td class="availability-booking-column"><span class="availability-booking-badge <?php echo $isBooked || $availabilityStatus !== 'Available' ? 'is-booked' : 'is-available'; ?>"><?php echo htmlspecialchars($availabilityStatus, ENT_QUOTES, 'UTF-8'); ?></span></td>
                        <td class="availability-added-column d-none d-md-table-cell"><time datetime="<?php echo date('c', strtotime($c[$this->colPrefix.'added'])); ?>"><?php echo date(ADMIN_DATETIME_FORMAT, strtotime($c[$this->colPrefix.'added'])); ?></time></td>
                        <td class="availability-updated-column d-none d-md-table-cell"><?php if (!empty($c['slot_updated']) && strtotime($c['slot_updated'])) { ?><time datetime="<?php echo date('c', strtotime($c['slot_updated'])); ?>"><?php echo date(ADMIN_DATETIME_FORMAT, strtotime($c['slot_updated'])); ?></time><?php } else { ?>&mdash;<?php } ?></td>
                        <td class="availability-actions-column pages-actions-cell">
                            <?php if (!$isBooked) { ?>
                            <a class="admin-action-icon font16 availability-action delitem" href="javascript:void(0);" data-controller="<?php echo $this->controller; ?>" id="recordID<?php echo $recordId; ?>" aria-label="Delete availability for <?php echo $guideName; ?>" title="Delete" data-bs-toggle="tooltip" data-bs-placement="top"><i class="bi bi-trash" aria-hidden="true"></i></a>
                            <?php } elseif ($bookingId > 0) { ?>
                            <a class="admin-action-icon font16 availability-action" href="<?php echo base_url('manage/bookings/control/view/'.$bookingId); ?>" aria-label="View booking for <?php echo $guideName; ?>" title="View Booking" data-bs-toggle="tooltip" data-bs-placement="top"><i class="bi bi-file-text" aria-hidden="true"></i></a>
                            <?php } ?>
                        </td>
                    </tr>
                <?php } } else { ?>
                    <tr><td class="pages-empty-state" colspan="8"><strong><?php echo $hasActiveFilters ? 'No availability dates match your filters.' : 'No availability dates found.'; ?></strong><span><?php echo $hasActiveFilters ? 'Try changing your search, tour guide, or date range filter.' : 'Add availability to get started.'; ?></span></td></tr>
                <?php } ?>
                </tbody>
            </table>
        </div>
    </form>

    <?php
    $total_pages = ((int) $per_page === 0) ? 1 : (int) ceil($total_rows / $per_page);
    $current_page = ((int) $per_page === 0) ? 1 : (int) ceil($page_numb / $per_page) + 1;
    if ($total_pages === 1) {
        $showing_from = $total_rows > 0 ? 1 : 0;
        $showing_to = $total_rows;
    } elseif ($total_pages === $current_page) {
        $showing_from = ($per_page * ($current_page - 1)) + 1;
        $showing_to = $total_rows;
    } else {
        $showing_from = ($per_page * ($current_page - 1)) + 1;
        $showing_to = $per_page * $current_page;
    }
    ?>
    <?php if ($total_rows > 0) { ?>
    <footer class="pages-listing-footer">
        <div class="pages-footer-summary">
            <div class="pages-per-page-control">
                <label for="per_page">Rows per page</label>
                <select class="form-select form-select-sm" name="per_page" id="per_page" aria-label="Rows per page">
                    <option value="0" <?php echo ((int) $this->per_page === 0) ? 'selected="selected"' : ''; ?>>All</option>
                    <?php for ($i = 10; $i <= 100; $i += 10) { ?>
                    <option value="<?php echo $i; ?>" <?php echo ((int) $this->per_page === $i) ? 'selected="selected"' : ''; ?>><?php echo $i; ?></option>
                    <?php } ?>
                </select>
            </div>
            <p>Showing <?php echo $showing_from; ?>&ndash;<?php echo $showing_to; ?> of <?php echo $total_rows; ?> record<?php echo ((int) $total_rows === 1) ? '' : 's'; ?></p>
        </div>
        <?php if (isset($paginate) && $paginate !== '') { ?><nav class="pages-pagination" aria-label="Guide Availability pagination"><?php echo $paginate; ?></nav><?php } ?>
    </footer>
    <?php } ?>
</section>

<script>
jQuery(function ($) {
    var $filterForm = $('#availability-filter-form');
    var $search = $('#search_keywords');
    var $tourGuide = $('#search_tour_guide');
    var $bookStatus = $('#search_book_status');
    var $dateInput = $('#search_dates');
    var $dateStart = $('#search_date_start');
    var $dateEnd = $('#search_date_end');
    var $selectAll = $('#all-checkbox');
    var $rowCheckboxes = $('#multiDel .cselect');
    var $bulkActions = $('#availability-bulk-actions');
    var $bulkCount = $('#availability-selection-count');
    var $bulkDelete = $('#deleteAllRecords');
    var filterBaseUrl = <?php echo json_encode(base_url('manage/'.$this->controller.'/index/'.$sortby.'/'.(($order === 'ASC') ? 'DESC' : 'ASC'))); ?>;

    function applyFilters() {
        var keywords = $.trim($search.val());
        keywords = keywords === '' ? '-' : encodeURI(keywords);
        var dateStart = $.trim($dateStart.val()) || '-';
        var dateEnd = $.trim($dateEnd.val()) || '-';
        window.location = filterBaseUrl + '/' + keywords + '/' + $tourGuide.val() + '/' + dateStart + '/' + dateEnd + '/' + $bookStatus.val();
    }

    flatpickr('#search_dates', {
        allowInput: true,
        mode: 'range',
        conjunction: ' to ',
        dateFormat: 'd-M-Y',
        disableMobile: true,
        onChange: function (selectedDates) {
            $dateStart.val(selectedDates[0] ? flatpickr.formatDate(selectedDates[0], 'Y-m-d') : '');
            $dateEnd.val(selectedDates[1] ? flatpickr.formatDate(selectedDates[1], 'Y-m-d') : (selectedDates[0] ? flatpickr.formatDate(selectedDates[0], 'Y-m-d') : ''));
        },
        onClose: function (selectedDates) {
            if (selectedDates.length === 2) {
                $dateStart.val(flatpickr.formatDate(selectedDates[0], 'Y-m-d'));
                $dateEnd.val(flatpickr.formatDate(selectedDates[1], 'Y-m-d'));
                applyFilters();
            }
        }
    });

    function updateBulkState() {
        var selectedCount = $rowCheckboxes.filter(':checked').length;
        var selectableCount = $rowCheckboxes.length;
        $bulkCount.text(selectedCount + ' selected');
        $bulkDelete.prop('disabled', selectedCount === 0);
        $bulkActions.prop('hidden', selectedCount === 0);
        $selectAll.prop('checked', selectableCount > 0 && selectedCount === selectableCount);
        $selectAll.prop('indeterminate', selectedCount > 0 && selectedCount < selectableCount);
        $selectAll.prop('disabled', selectableCount === 0);
    }

    $filterForm.on('submit', function (event) { event.preventDefault(); applyFilters(); });
    $search.on('keydown', function (event) { if (event.key === 'Enter') { event.preventDefault(); applyFilters(); } });
    $tourGuide.add($bookStatus).on('change', applyFilters);
    $rowCheckboxes.on('change', updateBulkState);
    $selectAll.on('change', function () { window.setTimeout(updateBulkState, 0); });
    updateBulkState();
});
</script>
