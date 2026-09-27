<?php
$hasActiveFilters = ($keywords !== '-' || $website !== '-' || $country > 0 || $stepCompleted !== '-');
$addUrl = base_url('manage/'.$this->controller.'/control');
$clearUrl = base_url('manage/'.$this->controller.'/index/'.$sortby.'/'.$order);
$sortUrl = function ($column) use ($order, $website, $country, $stepCompleted, $keywords, $page_numb) {
    return base_url('manage/'.$this->controller.'/index/'.$column.'/'.$order.'/'.$website.'/'.$country.'/'.$stepCompleted.'/'.rawurlencode($keywords).'/'.$page_numb);
};
$sortIcon = function ($column) use ($sortby, $order) {
    if ($sortby !== $column) return 'bi-arrow-down-up';
    return $order === 'DESC' ? 'bi-arrow-up' : 'bi-arrow-down';
};
$sortAria = function ($column) use ($sortby, $order) {
    if ($sortby !== $column) return '';
    return $order === 'DESC' ? 'ascending' : 'descending';
};
?>
<?php $this->load->view('admin/partials/breadcrumb', array('items' => array(array('label' => $this->moduleName, 'active' => TRUE)))); ?>

<?php $this->load->view('admin/partials/crud_alert', array('module_name' => 'Plan Your Visit request', 'status' => $alert)); ?>

<section class="admin-records-listing" aria-labelledby="plan-your-trip-title">
    <?php $this->load->view('admin/partials/module_header', array('title' => $this->moduleName, 'description' => $this->moduleDesc, 'id' => 'plan-your-trip-title', 'action_url' => $addUrl, 'action_label' => 'Add '.$this->moduleNameSingular)); ?>

    <div class="pages-listing-toolbar">
        <form class="pages-filter-form" id="plan-your-trip-filter-form" role="search">
            <div class="pages-search-control"><label class="visually-hidden" for="search_keywords">Search Plan Your Visit requests</label><span class="pages-search-icon" aria-hidden="true"><i class="bi bi-search"></i></span><input class="form-control" type="search" value="<?php echo $keywords !== '-' ? htmlspecialchars($keywords, ENT_QUOTES, 'UTF-8') : ''; ?>" id="search_keywords" placeholder="Search name, email, phone, or message..." autocomplete="off"></div>
            <button class="btn btn-outline-secondary pages-search-submit" type="submit"><i class="bi bi-search" aria-hidden="true"></i><span class="d-none d-sm-inline">Search</span></button>
            <div class="pages-status-control"><label class="visually-hidden" for="search_website">Filter by website</label><select class="form-select select2" id="search_website" data-minimum-results-for-search="-1">
                    <option value="-">All Websites</option>
                    <option value="English" <?php echo $website === 'English' ? 'selected' : ''; ?>>English</option>
                    <option value="Arabic" <?php echo $website === 'Arabic' ? 'selected' : ''; ?>>Arabic</option>
                </select></div>
            <div class="pages-status-control"><label class="visually-hidden" for="search_country">Filter by country</label><select class="form-select select2" id="search_country">
                    <option value="0">All Countries</option>
                    <?php foreach ($countries as $countryOption) { ?>
                    <option value="<?php echo (int) $countryOption['id']; ?>" <?php echo $country === (int) $countryOption['id'] ? 'selected' : ''; ?>><?php echo htmlspecialchars($countryOption['name'], ENT_QUOTES, 'UTF-8'); ?></option>
                    <?php } ?>
                </select></div>
            <div class="pages-status-control"><label class="visually-hidden" for="search_step">Filter by step completed</label><select class="form-select select2" id="search_step" data-minimum-results-for-search="-1">
                    <option value="-">All Steps</option>
                    <?php for ($step = 0; $step <= 5; $step++) { ?>
                    <option value="<?php echo $step; ?>" <?php echo $stepCompleted === (string) $step ? 'selected' : ''; ?>>Step <?php echo $step; ?></option>
                    <?php } ?>
                </select></div>
            <?php if ($hasActiveFilters) { ?><a class="btn btn-outline-secondary pages-clear-filters" href="<?php echo $clearUrl; ?>">Clear filters</a><?php } ?>
        </form>
    </div>

    <div class="pages-bulk-actions" id="plan-your-trip-bulk-actions" aria-live="polite" hidden><span class="pages-selection-count" id="plan-your-trip-selection-count">0 selected</span><button id="deleteAllRecords" class="btn btn-sm btn-outline-danger" type="button" disabled><i class="bi bi-trash" aria-hidden="true"></i> Delete Selected</button></div>

    <form action="<?php echo ADMIN_URL.$this->controller; ?>/deleteall" method="post" name="multiDel" id="multiDel">
        <?php if ($this->config->item('csrf_protection')) { ?><input type="hidden" name="<?php echo htmlspecialchars($this->security->get_csrf_token_name(), ENT_QUOTES, 'UTF-8'); ?>" value="<?php echo htmlspecialchars($this->security->get_csrf_hash(), ENT_QUOTES, 'UTF-8'); ?>"><?php } ?>
        <div class="table-responsive pages-table-responsive" tabindex="0" aria-label="Plan Your Visit requests table">
            <table id="table-<?php echo $this->controller; ?>" class="table pages-listing-table admin-records-table">
                <thead>
                    <tr>
                        <th class="pages-select-column" scope="col"><input class="form-check-input" type="checkbox" id="all-checkbox" autocomplete="off" aria-label="Select all Plan Your Visit requests"></th>
                        <th scope="col" class="d-none d-md-table-cell"<?php if ($sortAria('id') !== '') { ?> aria-sort="<?php echo $sortAria('id'); ?>"<?php } ?>><a class="pages-sort-link" href="<?php echo $sortUrl('id'); ?>">ID <i class="bi <?php echo $sortIcon('id'); ?> pages-sort-icon" aria-hidden="true"></i></a></th>
                        <th scope="col"<?php if ($sortAria('name') !== '') { ?> aria-sort="<?php echo $sortAria('name'); ?>"<?php } ?>><a class="pages-sort-link" href="<?php echo $sortUrl('name'); ?>">Name <i class="bi <?php echo $sortIcon('name'); ?> pages-sort-icon" aria-hidden="true"></i></a></th>
                        <th scope="col"<?php if ($sortAria('email') !== '') { ?> aria-sort="<?php echo $sortAria('email'); ?>"<?php } ?>><a class="pages-sort-link" href="<?php echo $sortUrl('email'); ?>">Email <i class="bi <?php echo $sortIcon('email'); ?> pages-sort-icon" aria-hidden="true"></i></a></th>
                        <th scope="col"<?php if ($sortAria('country') !== '') { ?> aria-sort="<?php echo $sortAria('country'); ?>"<?php } ?>><a class="pages-sort-link" href="<?php echo $sortUrl('country'); ?>">Country <i class="bi <?php echo $sortIcon('country'); ?> pages-sort-icon" aria-hidden="true"></i></a></th>
                        <th scope="col"<?php if ($sortAria('step_completed') !== '') { ?> aria-sort="<?php echo $sortAria('step_completed'); ?>"<?php } ?>><a class="pages-sort-link" href="<?php echo $sortUrl('step_completed'); ?>">Step Completed <i class="bi <?php echo $sortIcon('step_completed'); ?> pages-sort-icon" aria-hidden="true"></i></a></th>
                        <th scope="col"<?php if ($sortAria('website') !== '') { ?> aria-sort="<?php echo $sortAria('website'); ?>"<?php } ?>><a class="pages-sort-link" href="<?php echo $sortUrl('website'); ?>">Website <i class="bi <?php echo $sortIcon('website'); ?> pages-sort-icon" aria-hidden="true"></i></a></th>
                        <th scope="col" class="d-none d-md-table-cell"<?php if ($sortAria('created_at') !== '') { ?> aria-sort="<?php echo $sortAria('created_at'); ?>"<?php } ?>><a class="pages-sort-link" href="<?php echo $sortUrl('created_at'); ?>">Created On <i class="bi <?php echo $sortIcon('created_at'); ?> pages-sort-icon" aria-hidden="true"></i></a></th>
                        <th scope="col" class="d-none d-md-table-cell"<?php if ($sortAria('updated_at') !== '') { ?> aria-sort="<?php echo $sortAria('updated_at'); ?>"<?php } ?>><a class="pages-sort-link" href="<?php echo $sortUrl('updated_at'); ?>">Updated On <i class="bi <?php echo $sortIcon('updated_at'); ?> pages-sort-icon" aria-hidden="true"></i></a></th>
                        <th scope="col">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (!empty($records)) {
                        foreach ($records as $record) {
                            $id = (int) $record[$this->pKey];
                            $escapedName = htmlspecialchars((string) $record['name'], ENT_QUOTES, 'UTF-8');
                            $editUrl = base_url('manage/'.$this->controller.'/control/edit/'.$id);
                            $email = htmlspecialchars((string) $record['email'], ENT_QUOTES, 'UTF-8');
                            $countryName = trim((string) $record['country_name']);
                    ?>
                            <tr id="<?php echo $this->controller.'-'.$id; ?>" data-record-id="<?php echo $id; ?>">
                                <td class="pages-select-column"><input name="records[]" class="form-check-input cselect" value="<?php echo $id; ?>" type="checkbox" aria-label="Select <?php echo $escapedName; ?>"></td>
                                <td class="d-none d-md-table-cell"><span class="pages-record-id"><?php echo $id; ?></span></td>
                                <td><a class="pages-name-link" href="<?php echo $editUrl; ?>"><?php echo $escapedName !== '' ? $escapedName : '<span class="pages-cell-meta">Unnamed</span>'; ?></a></td>
                                <td><a href="mailto:<?php echo $email; ?>"><?php echo $email; ?></a></td>
                                <td><?php echo $countryName !== '' ? htmlspecialchars($countryName, ENT_QUOTES, 'UTF-8') : '<span class="pages-cell-meta">&mdash;</span>'; ?></td>
                                <td><?php echo report_progress($record['step_completed'], 4, ''); ?></td>
                                <td><?php echo htmlspecialchars((string) $record['website'], ENT_QUOTES, 'UTF-8'); ?></td>
                                <td class="d-none d-md-table-cell"><time datetime="<?php echo date('c', strtotime($record['created_at'])); ?>"><?php echo date(ADMIN_DATE_FORMAT, strtotime($record['created_at'])); ?><span class="pages-cell-meta"><?php echo date(ADMIN_TIME_FORMAT, strtotime($record['created_at'])); ?></span></time></td>
                                <td class="d-none d-md-table-cell"><time datetime="<?php echo date('c', strtotime($record['updated_at'])); ?>"><?php echo date(ADMIN_DATE_FORMAT, strtotime($record['updated_at'])); ?><span class="pages-cell-meta"><?php echo date(ADMIN_TIME_FORMAT, strtotime($record['updated_at'])); ?></span></time></td>
                                <td class="pages-actions-cell">
                                    <button type="button" class="admin-action-icon font16 plan-your-trip-view" data-details-url="<?php echo base_url('manage/'.$this->controller.'/details/'.$id); ?>" data-bs-toggle="modal" data-bs-target="#planYourTripDetailsModal" aria-label="View <?php echo $escapedName; ?>" title="View"><i class="bi bi-eye" aria-hidden="true"></i></button>
                                    <a class="admin-action-icon font16" href="<?php echo $editUrl; ?>" aria-label="Edit <?php echo $escapedName; ?>" title="Edit" data-bs-toggle="tooltip"><i class="bi bi-pencil" aria-hidden="true"></i></a>
                                    <a class="admin-action-icon font16 delitem" href="javascript:void(0);" data-controller="<?php echo $this->controller; ?>" data-record-name="<?php echo $escapedName; ?>" id="recordID<?php echo $id; ?>" aria-label="Delete <?php echo $escapedName; ?>" title="Delete" data-bs-toggle="tooltip"><i class="bi bi-trash" aria-hidden="true"></i></a>
                                </td>
                            </tr>
                        <?php }
                    } else { ?><tr class="nodrag">
                            <td class="pages-empty-state" colspan="10"><strong><?php echo $hasActiveFilters ? 'No Plan Your Visit requests match your filters.' : 'No Plan Your Visit requests found.'; ?></strong><span><?php echo $hasActiveFilters ? 'Try changing your search or filters.' : 'Trip-planning requests submitted through the website will appear here.'; ?></span></td>
                        </tr><?php } ?>
                </tbody>
            </table>
        </div>
    </form>

    <?php $this->load->view('admin/partials/table_listing_footer', array(
        'total_rows' => $total_rows,
        'per_page' => $per_page,
        'selected_per_page' => $this->per_page,
        'page_offset' => $page_numb,
        'pagination' => isset($paginate) ? $paginate : '',
        'pagination_label' => 'Plan Your Visit request pagination',
    )); ?>
</section>

<div class="modal fade contact-request-modal" id="planYourTripDetailsModal" tabindex="-1" aria-labelledby="planYourTripDetailsTitle" aria-hidden="true">
    <div class="modal-dialog modal-xl modal-dialog-centered modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header">
                <div class="contact-request-modal-heading">
                    <span class="contact-request-modal-heading-icon" aria-hidden="true"><i class="bi bi-signpost-split"></i></span>
                    <div>
                        <h2 class="modal-title" id="planYourTripDetailsTitle">Plan Your Visit Details</h2>
                        <p data-plan-your-trip-summary>Loading request details...</p>
                    </div>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="contact-request-modal-loader" data-plan-your-trip-loader role="status" aria-live="polite">
                    <span class="spinner-border" aria-hidden="true"></span>
                    <span>Loading Plan Your Visit request...</span>
                </div>
                <div class="alert alert-danger mb-0" data-plan-your-trip-error role="alert" hidden>The Plan Your Visit request details could not be loaded. Please try again.</div>
                <div class="contact-request-details-grid" data-plan-your-trip-details hidden>
                    <?php foreach (array(
                        array('id', 'ID', 'bi-hash'),
                        array('name', 'Name', 'bi-person'),
                        array('email', 'Email Address', 'bi-envelope'),
                        array('phone', 'Phone', 'bi-telephone'),
                        array('country', 'Country', 'bi-globe2'),
                        array('arrival_date', 'Arrival Date', 'bi-calendar2-check'),
                        array('departure_date', 'Departure Date', 'bi-calendar2-x'),
                        array('guests', 'Guests', 'bi-people'),
                        array('step_completed', 'Step Completed', 'bi-list-ol'),
                        array('preferred_language', 'Preferred Language', 'bi-translate'),
                        array('preferred_time', 'Preferred Time', 'bi-clock',  'contact-request-detail-wide'),
                        array('interests', 'Interests', 'bi-heart', 'contact-request-detail-wide'),
                        array('message', 'Message', 'bi-chat-square-text', 'contact-request-detail-wide'),
                        array('created_at', 'Created', 'bi-calendar2-check'),
                        array('updated_at', 'Updated', 'bi-calendar2-check'),
                        array('ip', 'IP Address', 'bi-pc-display', 'contact-request-detail-wide'),
                        array('user_agent', 'User Agent', 'bi-pc-display', 'contact-request-detail-wide'),
                        array('website', 'Website', 'bi-window', 'contact-request-detail-wide'),
                        // array('session_token', 'Session Token', 'bi-key', 'contact-request-detail-wide'),
                    ) as $detail) { ?>
                    <div class="contact-request-detail <?php echo isset($detail[3]) ? $detail[3] : ''; ?>">
                        <span class="contact-request-detail-icon" aria-hidden="true"><i class="bi <?php echo $detail[2]; ?>"></i></span>
                        <div>
                            <span class="contact-request-detail-label"><?php echo $detail[1]; ?></span>
                            <span class="contact-request-detail-value" data-plan-your-trip="<?php echo $detail[0]; ?>">&mdash;</span>
                        </div>
                    </div>
                    <?php } ?>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>

<script>
    jQuery(function($) {
        var $form = $('#plan-your-trip-filter-form'),
            $search = $('#search_keywords'),
            $website = $('#search_website'),
            $country = $('#search_country'),
            $step = $('#search_step');
        var $rows = $('#multiDel .cselect'),
            $all = $('#all-checkbox'),
            $bulk = $('#plan-your-trip-bulk-actions'),
            $count = $('#plan-your-trip-selection-count'),
            $delete = $('#deleteAllRecords');
        var detailsRequest = null,
            $detailsModal = $('#planYourTripDetailsModal');
        var filterUrl = <?php echo json_encode(base_url('manage/'.$this->controller.'/index/'.$sortby.'/'.(($order === 'ASC') ? 'DESC' : 'ASC'))); ?>;

        function applyFilters() {
            var keyword = $.trim($search.val());
            window.location = filterUrl + '/' + $website.val() + '/' + $country.val() + '/' + $step.val() + '/' + (keyword === '' ? '-' : encodeURIComponent(keyword));
        }

        function updateSelection() {
            var selected = $rows.filter(':checked').length;
            $count.text(selected + ' selected');
            $delete.prop('disabled', selected === 0);
            $bulk.prop('hidden', selected === 0);
            $all.prop('checked', $rows.length > 0 && selected === $rows.length).prop('indeterminate', selected > 0 && selected < $rows.length).prop('disabled', $rows.length === 0);
        }
        $form.on('submit', function(event) {
            event.preventDefault();
            applyFilters();
        });
        $website.on('change', applyFilters);
        $country.on('change', applyFilters);
        $step.on('change', applyFilters);
        $rows.on('change', updateSelection);
        $all.on('change', function() {
            window.setTimeout(updateSelection, 0);
        });
        $detailsModal.on('show.bs.modal', function(event) {
            var $modal = $(this),
                detailsUrl = $(event.relatedTarget).data('details-url');

            if (detailsRequest) detailsRequest.abort();
            $modal.find('[data-plan-your-trip-summary]').text('Loading request details...');
            $modal.find('[data-plan-your-trip-loader]').prop('hidden', false);
            $modal.find('[data-plan-your-trip-error], [data-plan-your-trip-details]').prop('hidden', true);
            $modal.find('[data-plan-your-trip]').text('—');

            detailsRequest = $.ajax({
                url: detailsUrl,
                method: 'GET',
                dataType: 'json'
            }).done(function(response) {
                if (!response || response.status !== 'true' || !response.record) {
                    $modal.find('[data-plan-your-trip-summary]').text('Unable to load request details.');
                    $modal.find('[data-plan-your-trip-error]').prop('hidden', false);
                    return;
                }

                var request = response.record,
                    requester = $.trim(request.name || '') || 'This visitor';

                $modal.find('[data-plan-your-trip-summary]').text(requester + ' submitted this request.');
                $modal.find('[data-plan-your-trip]').each(function() {
                    var value = request[$(this).data('plan-your-trip')];
                    $(this).text(value === null || value === undefined || value === '' ? '—' : value);
                });
                $modal.find('[data-plan-your-trip-details]').prop('hidden', false);
            }).fail(function(xhr, status) {
                if (status !== 'abort') {
                    $modal.find('[data-plan-your-trip-summary]').text('Unable to load request details.');
                    $modal.find('[data-plan-your-trip-error]').prop('hidden', false);
                }
            }).always(function() {
                $modal.find('[data-plan-your-trip-loader]').prop('hidden', true);
                detailsRequest = null;
            });
        });
        $detailsModal.on('hidden.bs.modal', function() {
            if (detailsRequest) detailsRequest.abort();
            detailsRequest = null;
        });
        updateSelection();
    });
</script>
