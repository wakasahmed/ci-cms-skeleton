<?php
$hasActiveFilters = ($keywords !== '-');
$addUrl = base_url('manage/'.$this->controller.'/control');
$clearUrl = base_url('manage/'.$this->controller.'/index/'.$sortby.'/'.$order);
$sortUrl = function ($column) use ($order, $keywords, $page_numb) {
    return base_url(
        'manage/'.$this->controller.'/index/'.$column.'/'.$order.'/'.rawurlencode($keywords).'/'.$page_numb
    );
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

<?php $this->load->view('admin/partials/crud_alert', array('module_name' => 'Contact request', 'status' => $alert)); ?>

<section class="admin-records-listing" aria-labelledby="contact-requests-title">
    <?php $this->load->view('admin/partials/module_header', array('title' => $this->moduleName, 'description' => $this->moduleDesc, 'id' => 'contact-requests-title', 'action_url' => $addUrl, 'action_label' => 'Add '.$this->moduleNameSingular)); ?>

    <div class="pages-listing-toolbar">
        <form class="pages-filter-form" id="contact-requests-filter-form" role="search">
            <div class="pages-search-control"><label class="visually-hidden" for="search_keywords">Search contact requests</label><span class="pages-search-icon" aria-hidden="true"><i class="bi bi-search"></i></span><input class="form-control" type="search" value="<?php echo $keywords !== '-' ? htmlspecialchars($keywords, ENT_QUOTES, 'UTF-8') : ''; ?>" id="search_keywords" placeholder="Search name, email, subject, or message..." autocomplete="off"></div>
            <button class="btn btn-outline-secondary pages-search-submit" type="submit"><i class="bi bi-search" aria-hidden="true"></i><span class="d-none d-sm-inline">Search</span></button>
            <?php if ($hasActiveFilters) { ?><a class="btn btn-outline-secondary pages-clear-filters" href="<?php echo $clearUrl; ?>">Clear filters</a><?php } ?>
        </form>
    </div>

    <div class="pages-bulk-actions" id="contact-requests-bulk-actions" aria-live="polite" hidden><span class="pages-selection-count" id="contact-requests-selection-count">0 selected</span><button id="deleteAllRecords" class="btn btn-sm btn-outline-danger" type="button" disabled><i class="bi bi-trash" aria-hidden="true"></i> Delete Selected</button></div>

    <form action="<?php echo ADMIN_URL.$this->controller; ?>/deleteall" method="post" name="multiDel" id="multiDel">
        <?php if ($this->config->item('csrf_protection')) { ?><input type="hidden" name="<?php echo htmlspecialchars($this->security->get_csrf_token_name(), ENT_QUOTES, 'UTF-8'); ?>" value="<?php echo htmlspecialchars($this->security->get_csrf_hash(), ENT_QUOTES, 'UTF-8'); ?>"><?php } ?>
        <div class="table-responsive pages-table-responsive" tabindex="0" aria-label="Contact requests table">
            <table id="table-<?php echo $this->controller; ?>" class="table pages-listing-table admin-records-table">
                <thead>
                    <tr>
                        <th class="pages-select-column" scope="col"><input class="form-check-input" type="checkbox" id="all-checkbox" autocomplete="off" aria-label="Select all contact requests"></th>
                        <th scope="col" class="d-none d-md-table-cell"<?php if ($sortAria('id') !== '') { ?> aria-sort="<?php echo $sortAria('id'); ?>"<?php } ?>><a class="pages-sort-link" href="<?php echo $sortUrl('id'); ?>">ID <i class="bi <?php echo $sortIcon('id'); ?> pages-sort-icon" aria-hidden="true"></i></a></th>
                        <th scope="col"<?php if ($sortAria('first_name') !== '') { ?> aria-sort="<?php echo $sortAria('first_name'); ?>"<?php } ?>><a class="pages-sort-link" href="<?php echo $sortUrl('first_name'); ?>">Name <i class="bi <?php echo $sortIcon('first_name'); ?> pages-sort-icon" aria-hidden="true"></i></a></th>
                        <th scope="col"<?php if ($sortAria('email') !== '') { ?> aria-sort="<?php echo $sortAria('email'); ?>"<?php } ?>><a class="pages-sort-link" href="<?php echo $sortUrl('email'); ?>">Email <i class="bi <?php echo $sortIcon('email'); ?> pages-sort-icon" aria-hidden="true"></i></a></th>
                        <th scope="col"<?php if ($sortAria('subject') !== '') { ?> aria-sort="<?php echo $sortAria('subject'); ?>"<?php } ?>><a class="pages-sort-link" href="<?php echo $sortUrl('subject'); ?>">Subject <i class="bi <?php echo $sortIcon('subject'); ?> pages-sort-icon" aria-hidden="true"></i></a></th>
                        <th scope="col"<?php if ($sortAria('phone') !== '') { ?> aria-sort="<?php echo $sortAria('phone'); ?>"<?php } ?>><a class="pages-sort-link" href="<?php echo $sortUrl('phone'); ?>">Phone <i class="bi <?php echo $sortIcon('phone'); ?> pages-sort-icon" aria-hidden="true"></i></a></th>
                        <th scope="col" class="d-none d-md-table-cell"<?php if ($sortAria('created_at') !== '') { ?> aria-sort="<?php echo $sortAria('created_at'); ?>"<?php } ?>><a class="pages-sort-link" href="<?php echo $sortUrl('created_at'); ?>">Created On <i class="bi <?php echo $sortIcon('created_at'); ?> pages-sort-icon" aria-hidden="true"></i></a></th>
                        <th scope="col" class="d-none d-md-table-cell"<?php if ($sortAria('updated_at') !== '') { ?> aria-sort="<?php echo $sortAria('updated_at'); ?>"<?php } ?>><a class="pages-sort-link" href="<?php echo $sortUrl('updated_at'); ?>">Updated On <i class="bi <?php echo $sortIcon('updated_at'); ?> pages-sort-icon" aria-hidden="true"></i></a></th>
                        <th scope="col">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (!empty($records)) {
                        foreach ($records as $record) {
                            $id = (int) $record[$this->pKey];
                            $name = trim($record['first_name'].' '.$record['last_name']);
                            $escapedName = htmlspecialchars($name, ENT_QUOTES, 'UTF-8');
                            $editUrl = base_url('manage/'.$this->controller.'/control/edit/'.$id);
                            $email = htmlspecialchars((string) $record['email'], ENT_QUOTES, 'UTF-8');
                            $subject = htmlspecialchars((string) $record['subject'], ENT_QUOTES, 'UTF-8');
                            $phone = trim((string) $record['phone']);
                    ?>
                            <tr id="<?php echo $this->controller.'-'.$id; ?>" data-record-id="<?php echo $id; ?>">
                                <td class="pages-select-column"><input name="records[]" class="form-check-input cselect" value="<?php echo $id; ?>" type="checkbox" aria-label="Select <?php echo $escapedName; ?>"></td>
                                <td class="d-none d-md-table-cell"><span class="pages-record-id"><?php echo $id; ?></span></td>
                                <td><a class="pages-name-link" href="<?php echo $editUrl; ?>"><?php echo $escapedName !== '' ? $escapedName : '<span class="pages-cell-meta">Unnamed</span>'; ?></a></td>
                                <td><a href="mailto:<?php echo $email; ?>"><?php echo $email; ?></a></td>
                                <td><?php echo $subject; ?></td>
                                <td><?php echo $phone !== '' ? htmlspecialchars($phone, ENT_QUOTES, 'UTF-8') : '<span class="pages-cell-meta">&mdash;</span>'; ?></td>
                                <td class="d-none d-md-table-cell"><time datetime="<?php echo date('c', strtotime($record['created_at'])); ?>"><?php echo date(ADMIN_DATE_FORMAT, strtotime($record['created_at'])); ?><span class="pages-cell-meta"><?php echo date(ADMIN_TIME_FORMAT, strtotime($record['created_at'])); ?></span></time></td>
                                <td class="d-none d-md-table-cell"><time datetime="<?php echo date('c', strtotime($record['updated_at'])); ?>"><?php echo date(ADMIN_DATE_FORMAT, strtotime($record['updated_at'])); ?><span class="pages-cell-meta"><?php echo date(ADMIN_TIME_FORMAT, strtotime($record['updated_at'])); ?></span></time></td>
                                <td class="pages-actions-cell">
                                    <button type="button" class="admin-action-icon font16 contact-request-view" data-details-url="<?php echo base_url('manage/'.$this->controller.'/details/'.$id); ?>" data-bs-toggle="modal" data-bs-target="#contactRequestDetailsModal" aria-label="View <?php echo $escapedName; ?>" title="View"><i class="bi bi-eye" aria-hidden="true"></i></button>
                                    <a class="admin-action-icon font16" href="<?php echo $editUrl; ?>" aria-label="Edit <?php echo $escapedName; ?>" title="Edit" data-bs-toggle="tooltip"><i class="bi bi-pencil" aria-hidden="true"></i></a>
                                    <a class="admin-action-icon font16 delitem" href="javascript:void(0);" data-controller="<?php echo $this->controller; ?>" data-record-name="<?php echo $escapedName; ?>" id="recordID<?php echo $id; ?>" aria-label="Delete <?php echo $escapedName; ?>" title="Delete" data-bs-toggle="tooltip"><i class="bi bi-trash" aria-hidden="true"></i></a>
                                </td>
                            </tr>
                        <?php }
                    } else { ?><tr class="nodrag">
                            <td class="pages-empty-state" colspan="9"><strong><?php echo $hasActiveFilters ? 'No contact requests match your filters.' : 'No contact requests found.'; ?></strong><span><?php echo $hasActiveFilters ? 'Try changing your search or filters.' : 'Contact requests submitted through the website will appear here.'; ?></span></td>
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
        'pagination_label' => 'Contact request pagination',
    )); ?>
</section>

<div class="modal fade contact-request-modal" id="contactRequestDetailsModal" tabindex="-1" aria-labelledby="contactRequestDetailsTitle" aria-hidden="true">
    <div class="modal-dialog modal-xl modal-dialog-centered modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header">
                <div class="contact-request-modal-heading">
                    <span class="contact-request-modal-heading-icon" aria-hidden="true"><i class="bi bi-chat-left-text"></i></span>
                    <div>
                        <h2 class="modal-title" id="contactRequestDetailsTitle">Contact request details</h2>
                        <p data-contact-request-summary>Loading request details...</p>
                    </div>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="contact-request-modal-loader" data-contact-request-loader role="status" aria-live="polite">
                    <span class="spinner-border" aria-hidden="true"></span>
                    <span>Loading contact request...</span>
                </div>
                <div class="alert alert-danger mb-0" data-contact-request-error role="alert" hidden>The contact request details could not be loaded. Please try again.</div>
                <div class="contact-request-details-grid" data-contact-request-details hidden>
                    <?php foreach (array(
                        array('id', 'ID', 'bi-hash'),
                        array('first_name', 'First Name', 'bi-person'),
                        array('last_name', 'Last Name', 'bi-person'),
                        array('email', 'Email Address', 'bi-envelope'),
                        array('subject', 'Subject', 'bi-chat-left-text'),
                        array('phone', 'Phone', 'bi-telephone'),
                        array('message', 'Message', 'bi-chat-square-text', 'contact-request-detail-wide'),
                        array('created_at', 'Created', 'bi-calendar2-check'),
                        array('updated_at', 'Updated', 'bi-calendar2-check'),
                        array('ip', 'IP Address', 'bi-pc-display', 'contact-request-detail-wide'),
                        array('user_agent', 'User Agent', 'bi-pc-display', 'contact-request-detail-wide'),
                    ) as $detail) { ?>
                    <div class="contact-request-detail <?php echo isset($detail[3]) ? $detail[3] : ''; ?>">
                        <span class="contact-request-detail-icon" aria-hidden="true"><i class="bi <?php echo $detail[2]; ?>"></i></span>
                        <div>
                            <span class="contact-request-detail-label"><?php echo $detail[1]; ?></span>
                            <span class="contact-request-detail-value" data-contact-request="<?php echo $detail[0]; ?>">&mdash;</span>
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
        var $form = $('#contact-requests-filter-form'),
            $search = $('#search_keywords');
        var $rows = $('#multiDel .cselect'),
            $all = $('#all-checkbox'),
            $bulk = $('#contact-requests-bulk-actions'),
            $count = $('#contact-requests-selection-count'),
            $delete = $('#deleteAllRecords');
        var detailsRequest = null,
            $detailsModal = $('#contactRequestDetailsModal');
        var filterUrl = <?php echo json_encode(base_url('manage/'.$this->controller.'/index/'.$sortby.'/'.(($order === 'ASC') ? 'DESC' : 'ASC'))); ?>;

        function applyFilters() {
            var keyword = $.trim($search.val());
            window.location = filterUrl + '/' + (keyword === '' ? '-' : encodeURIComponent(keyword));
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
        $rows.on('change', updateSelection);
        $all.on('change', function() {
            window.setTimeout(updateSelection, 0);
        });
        $detailsModal.on('show.bs.modal', function(event) {
            var $modal = $(this),
                detailsUrl = $(event.relatedTarget).data('details-url');

            if (detailsRequest) detailsRequest.abort();
            $modal.find('[data-contact-request-summary]').text('Loading request details...');
            $modal.find('[data-contact-request-loader]').prop('hidden', false);
            $modal.find('[data-contact-request-error], [data-contact-request-details]').prop('hidden', true);
            $modal.find('[data-contact-request]').text('\u2014');

            detailsRequest = $.ajax({
                url: detailsUrl,
                method: 'GET',
                dataType: 'json'
            }).done(function(response) {
                if (!response || response.status !== 'true' || !response.record) {
                    $modal.find('[data-contact-request-summary]').text('Unable to load request details.');
                    $modal.find('[data-contact-request-error]').prop('hidden', false);
                    return;
                }

                var request = response.record,
                    requester = $.trim((request.first_name || '') + ' ' + (request.last_name || '')) || 'This contact';

                $modal.find('[data-contact-request-summary]').text(requester + ' submitted this request.');
                $modal.find('[data-contact-request]').each(function() {
                    var value = request[$(this).data('contact-request')];
                    $(this).text(value === null || value === undefined || value === '' ? '\u2014' : value);
                });
                $modal.find('[data-contact-request-details]').prop('hidden', false);
            }).fail(function(xhr, status) {
                if (status !== 'abort') {
                    $modal.find('[data-contact-request-summary]').text('Unable to load request details.');
                    $modal.find('[data-contact-request-error]').prop('hidden', false);
                }
            }).always(function() {
                $modal.find('[data-contact-request-loader]').prop('hidden', true);
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
