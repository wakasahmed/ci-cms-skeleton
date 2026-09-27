<?php
$escape = function ($value) {
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
};
$hasActiveFilters = ($keywords !== '-' || $status !== '-' || !empty($has_extra_filters));
$sortUrl = function ($column) use ($order, $status, $keywords, $page_numb, $filter_query) {
    return base_url('manage/bookings/index/' . $column . '/' . $order . '/' . $status . '/' . rawurlencode($keywords) . '/' . $page_numb) . $filter_query;
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
$statusOptions = array('Completed', 'Cancelled', 'Pending', 'Refunded');
?>
<?php $this->load->view('admin/partials/breadcrumb', array('items' => array(array('label' => $this->moduleName, 'active' => TRUE)))); ?>

<?php $this->load->view('admin/partials/crud_alert', array('module_name' => 'Booking', 'status' => $alert)); ?>

<section class="admin-records-listing" aria-labelledby="bookings-title">
    <?php $this->load->view('admin/partials/module_header', array('title' => $this->moduleName, 'id' => 'bookings-title')); ?>

    <div class="pages-listing-toolbar">
        <form class="pages-filter-form bookings-filter-form" id="bookings-filter-form" role="search">
            <div class="pages-search-control"><label class="visually-hidden" for="search_keywords">Search bookings</label><span class="pages-search-icon" aria-hidden="true"><i class="bi bi-search"></i></span><input class="form-control" type="search" value="<?php echo $keywords !== '-' ? $escape($keywords) : ''; ?>" id="search_keywords" placeholder="Search name, tour, or guide..." autocomplete="off"></div>
            <button class="btn btn-outline-secondary pages-search-submit" type="submit"><i class="bi bi-search" aria-hidden="true"></i><span class="d-none d-sm-inline">Search</span></button>
            <div class="pages-status-control"><label class="visually-hidden" for="search_status">Filter by status</label><select class="form-select select2" id="search_status" data-minimum-results-for-search="-1">
                    <option value="-">All Statuses</option>
                    <?php foreach ($statusOptions as $statusOption) { ?>
                    <option value="<?php echo $escape($statusOption); ?>" <?php echo $status === $statusOption ? 'selected' : ''; ?>><?php echo $escape($statusOption); ?></option>
                    <?php } ?>
                </select></div>
            <?php foreach ($filter_controls as $control) { ?>
                <?php $this->load->view('admin/partials/report_filter_field', array('control' => $control, 'label_as_placeholder' => TRUE)); ?>
            <?php } ?>
            <?php if ($hasActiveFilters) { ?>
                <a class="btn btn-link pages-clear-filters" href="<?php echo base_url('manage/' . $this->controller); ?>">
                    <i class="bi bi-x-circle" aria-hidden="true"></i> Clear all
                </a>
            <?php } ?>
        </form>
    </div>

    <div class="table-responsive pages-table-responsive" tabindex="0" aria-label="Bookings table">
        <table id="table-<?php echo $this->controller; ?>" class="table pages-listing-table admin-records-table">
            <thead>
                <tr>
                    <th scope="col" class="d-none d-md-table-cell"<?php if ($sortAria($this->pKey) !== '') { ?> aria-sort="<?php echo $sortAria($this->pKey); ?>"<?php } ?>><a class="pages-sort-link" href="<?php echo $sortUrl($this->pKey); ?>">ID <i class="bi <?php echo $sortIcon($this->pKey); ?> pages-sort-icon" aria-hidden="true"></i></a></th>
                    <th scope="col"<?php if ($sortAria('book_name') !== '') { ?> aria-sort="<?php echo $sortAria('book_name'); ?>"<?php } ?>><a class="pages-sort-link" href="<?php echo $sortUrl('book_name'); ?>">Customer <i class="bi <?php echo $sortIcon('book_name'); ?> pages-sort-icon" aria-hidden="true"></i></a></th>
                    <th scope="col"
                        <?php if ($sortAria('book_tour_name') !== '') { ?>
                            aria-sort="<?php echo $sortAria('book_tour_name'); ?>"
                        <?php } ?>
                    >
                        <a class="pages-sort-link" href="<?php echo $sortUrl('book_tour_name'); ?>">
                            Tour / Experience
                            <i class="bi <?php echo $sortIcon('book_tour_name'); ?> pages-sort-icon" aria-hidden="true"></i>
                        </a>
                    </th>
                    <th scope="col" class="d-none d-md-table-cell"<?php if ($sortAria('book_tour_guide_name') !== '') { ?> aria-sort="<?php echo $sortAria('book_tour_guide_name'); ?>"<?php } ?>><a class="pages-sort-link" href="<?php echo $sortUrl('book_tour_guide_name'); ?>">Tour Guide <i class="bi <?php echo $sortIcon('book_tour_guide_name'); ?> pages-sort-icon" aria-hidden="true"></i></a></th>
                    <th scope="col" class="d-none d-md-table-cell"<?php if ($sortAria('book_date') !== '') { ?> aria-sort="<?php echo $sortAria('book_date'); ?>"<?php } ?>><a class="pages-sort-link" href="<?php echo $sortUrl('book_date'); ?>">Tour Date / Time <i class="bi <?php echo $sortIcon('book_date'); ?> pages-sort-icon" aria-hidden="true"></i></a></th>
                    <th scope="col"<?php if ($sortAria('book_status') !== '') { ?> aria-sort="<?php echo $sortAria('book_status'); ?>"<?php } ?>><a class="pages-sort-link" href="<?php echo $sortUrl('book_status'); ?>">Status <i class="bi <?php echo $sortIcon('book_status'); ?> pages-sort-icon" aria-hidden="true"></i></a></th>
                    <th scope="col">Steps completed</th>
                    <th scope="col">Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php if (!empty($records)) {
                    foreach ($records as $record) {
                        $id = (int) $record[$this->pKey];
                        $viewUrl = base_url('manage/' . $this->controller . '/control/view/' . $id);
                        $tourDateText = !empty($record['book_date'])
                            ? date(ADMIN_DATE_FORMAT, strtotime($record['book_date'])) . ', ' . $record['book_slot_name']
                            : '—';
                        $statusBadgeClass = 'status-warning';
                        if ($record['book_status'] === 'Completed') {
                            $statusBadgeClass = 'status-enabled';
                        } elseif ($record['book_status'] === 'Cancelled') {
                            $statusBadgeClass = 'status-disabled';
                        } elseif ($record['book_status'] === 'Refunded') {
                            $statusBadgeClass = 'status-published';
                        }
                ?>
                    <tr id="<?php echo $this->controller . '-' . $id; ?>">
                        <td class="d-none d-md-table-cell"><span class="pages-record-id"><?php echo $id; ?></span></td>
                        <td><a class="pages-name-link" href="<?php echo $viewUrl; ?>"><?php echo $escape($record['book_name']); ?></a></td>
                        <td><a class="pages-name-link" href="<?php echo $viewUrl; ?>"><?php echo $escape($record['book_tour_name']); ?></a></td>
                        <td class="d-none d-md-table-cell"><?php echo $escape(!empty($record['book_tour_guide_name']) ? $record['book_tour_guide_name'] : '—'); ?></td>
                        <td class="d-none d-md-table-cell"><?php echo $escape($tourDateText); ?></td>
                        <td><span class="pages-status-static status-badge <?php echo $statusBadgeClass; ?>"><?php echo $escape($record['book_status']); ?></span></td>
                        <td><?php echo report_progress($record['steps_completed'], $record['total_steps'], $record['book_status']); ?></td>
                        <td class="pages-actions-cell"><a class="btn btn-sm btn-outline-secondary" href="<?php echo $viewUrl; ?>"><i class="bi bi-file-text" aria-hidden="true"></i> View Details</a></td>
                    </tr>
                <?php }
                } else { ?><tr>
                        <td class="pages-empty-state" colspan="8"><strong><?php echo $hasActiveFilters ? 'No bookings match your filters.' : 'No bookings found.'; ?></strong><span><?php echo $hasActiveFilters ? 'Try changing your search or filters.' : 'Bookings will appear here once customers reserve a tour.'; ?></span></td>
                    </tr><?php } ?>
            </tbody>
        </table>
    </div>

    <?php $this->load->view('admin/partials/table_listing_footer', array(
        'total_rows' => $total_rows,
        'per_page' => $per_page,
        'selected_per_page' => $this->per_page,
        'page_offset' => $page_numb,
        'pagination' => isset($paginate) ? $paginate : '',
        'pagination_label' => 'Bookings pagination',
    )); ?>
</section>

<script>
    jQuery(function ($) {
        var $form = $('#bookings-filter-form'),
            $search = $('#search_keywords'),
            $status = $('#search_status'),
            $extraFilters = $form.find('.report-filter-field select, .report-filter-field input');
        var filterUrl = <?php echo json_encode(base_url('manage/' . $this->controller . '/index/' . $sortby . '/' . (($order === 'ASC') ? 'DESC' : 'ASC'))); ?>;

        function applyFilters() {
            var keyword = $.trim($search.val());
            var params = [];

            // Tour and date filters travel in the query string, like the bookings report.
            $extraFilters.each(function () {
                var value = $.trim($(this).val());
                if (this.name && value !== '') {
                    params.push(encodeURIComponent(this.name) + '=' + encodeURIComponent(value));
                }
            });

            window.location = filterUrl + '/' + $status.val() + '/' + (keyword === '' ? '-' : encodeURIComponent(keyword))
                + (params.length ? '?' + params.join('&') : '');
        }

        $form.on('submit', function (event) {
            event.preventDefault();
            applyFilters();
        });
        $status.on('change', applyFilters);
        // Date ranges are applied with the Search button so a range can be picked in two clicks.
        $extraFilters.filter('select').on('change', applyFilters);
    });
</script>
