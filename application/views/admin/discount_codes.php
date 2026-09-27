<?php
$hasActiveFilters = ($keywords !== '-' || $status !== '-' || (int) $ref_id > 0);
$addUrl = base_url('manage/'.$this->controller.'/control');
$sortUrl = function ($column) use ($order, $status, $keywords, $ref_id, $page_numb) { return base_url('manage/'.$this->controller.'/index/'.$column.'/'.$order.'/'.$status.'/'.rawurlencode($keywords).'/'.(int) $ref_id.'/'.(int) $page_numb); };
$sortIcon = function ($column) use ($sortby, $order) { if ($sortby !== $column) return 'bi-arrow-down-up'; return $order === 'DESC' ? 'bi-arrow-up' : 'bi-arrow-down'; };
$sortAria = function ($column) use ($sortby, $order) { if ($sortby !== $column) return ''; return $order === 'DESC' ? 'ascending' : 'descending'; };
$sortableHeading = function ($column, $label, $class = '') use ($sortUrl, $sortIcon, $sortAria) { $aria = $sortAria($column); ?><th scope="col"<?php if ($class !== '') { ?> class="<?php echo $class; ?>"<?php } ?><?php if ($aria !== '') { ?> aria-sort="<?php echo $aria; ?>"<?php } ?>><a class="pages-sort-link" href="<?php echo $sortUrl($column); ?>"><?php echo $label; ?> <i class="bi <?php echo $sortIcon($column); ?> pages-sort-icon" aria-hidden="true"></i></a></th><?php };
?>
<?php $this->load->view('admin/partials/breadcrumb', array('items' => array(array('label' => $this->moduleName, 'active' => TRUE)))); ?>
<?php $this->load->view('admin/partials/crud_alert', array(
    'module_name' => 'Discount code', 'status' => $alert,
    'status_messages' => array(
        'deleteblocked' => array('danger', 'Cannot delete!', 'A selected discount code has booking history. No records were deleted.'),
        'success_email_failed' => array('warning', 'Saved.', 'Discount code was added, but the email to the referral could not be sent.'),
        'editsuccess_email_failed' => array('warning', 'Saved.', 'Discount code was updated, but the email to the referral could not be sent.'),
    ),
)); ?>

<section class="admin-records-listing" aria-labelledby="discount-codes-title" data-translation-poll data-module="discount_codes" data-status-url="<?php echo base_url('manage/translations/statuses'); ?>" data-poll-seconds="4">
    <?php $this->load->view('admin/partials/module_header', array('title' => $this->moduleName, 'description' => $this->moduleDesc, 'id' => 'discount-codes-title', 'action_url' => $addUrl, 'action_label' => 'Add '.$this->moduleNameSingular)); ?>
    <div class="pages-listing-toolbar">
        <form class="pages-filter-form" id="discount-codes-filter-form" role="search">
            <div class="pages-search-control"><label class="visually-hidden" for="search_keywords">Search discount codes</label><span class="pages-search-icon" aria-hidden="true"><i class="bi bi-search"></i></span><input class="form-control" type="search" value="<?php echo $keywords !== '-' ? htmlspecialchars($keywords, ENT_QUOTES, 'UTF-8') : ''; ?>" id="search_keywords" placeholder="Search referral, name, or code..." autocomplete="off"></div>
            <button class="btn btn-outline-secondary pages-search-submit" type="submit"><i class="bi bi-search" aria-hidden="true"></i><span class="d-none d-sm-inline">Search</span></button>
            <div class="pages-status-control"><label class="visually-hidden" for="search_ref">Filter by referral</label><select class="form-select select2" id="search_ref"><option value="0">All Referrals</option><?php foreach ($referrals as $referral) { ?><option value="<?php echo (int) $referral['ref_id']; ?>" <?php echo (int) $ref_id === (int) $referral['ref_id'] ? 'selected' : ''; ?>><?php echo htmlspecialchars($referral['ref_name'].($referral['ref_organization'] !== '' ? ' ('.$referral['ref_organization'].')' : ''), ENT_QUOTES, 'UTF-8'); ?></option><?php } ?></select></div>
            <div class="pages-status-control"><label class="visually-hidden" for="search_status">Filter by status</label><select class="form-select select2" id="search_status" data-minimum-results-for-search="-1"><option value="-">All Statuses</option><option value="Enable" <?php echo $status === 'Enable' ? 'selected' : ''; ?>>Enabled</option><option value="Disable" <?php echo $status === 'Disable' ? 'selected' : ''; ?>>Disabled</option><option value="Expired" <?php echo $status === 'Expired' ? 'selected' : ''; ?>>Expired</option></select></div>
        </form>
    </div>
    <div class="pages-bulk-actions" id="discount-codes-bulk-actions" aria-live="polite" hidden><span class="pages-selection-count" id="discount-codes-selection-count">0 selected</span><button id="deleteAllRecords" class="btn btn-sm btn-outline-danger" type="button" disabled><i class="bi bi-trash" aria-hidden="true"></i> Delete Selected</button></div>

    <form action="<?php echo ADMIN_URL.$this->controller; ?>/deleteall" method="post" name="multiDel" id="multiDel">
        <div class="table-responsive pages-table-responsive" tabindex="0" aria-label="Discount codes table">
            <table id="table-<?php echo htmlspecialchars($this->controller, ENT_QUOTES, 'UTF-8'); ?>" class="table pages-listing-table admin-records-table">
                <thead><tr><th class="pages-select-column" scope="col"><input class="form-check-input" type="checkbox" id="all-checkbox" autocomplete="off" aria-label="Select all unused discount codes"></th><?php $sortableHeading($this->pKey, 'ID', 'd-none d-md-table-cell'); $sortableHeading($this->colPrefix.'name', 'Discount'); $sortableHeading($this->colPrefix.'expiry', 'Expiry'); $sortableHeading('completed_uses', 'Uses'); $sortableHeading($this->tStatus, 'Status'); ?><th scope="col">Translation</th><?php $sortableHeading($this->colPrefix.'updated', 'Updated On', 'd-none d-md-table-cell'); ?><th scope="col">Actions</th></tr></thead>
                <tbody>
                <?php if (!empty($records)) { foreach ($records as $record) {
                    $id = (int) $record[$this->pKey]; $uses = (int) $record['total_use'];
                    $name = trim((string) $record[$this->colPrefix.'name']); $escapedName = htmlspecialchars($name, ENT_QUOTES, 'UTF-8');
                    $code = htmlspecialchars((string) $record[$this->colPrefix.'code'], ENT_QUOTES, 'UTF-8');
                    $editUrl = base_url('manage/'.$this->controller.'/control/edit/'.$id);
                    $updatedAt = !empty($record[$this->colPrefix.'updated']) ? $record[$this->colPrefix.'updated'] : $record[$this->colPrefix.'added'];
                ?>
                    <tr id="<?php echo htmlspecialchars($this->controller, ENT_QUOTES, 'UTF-8').'-'.$id; ?>" data-record-id="<?php echo $id; ?>">
                        <td class="pages-select-column"><?php if ($uses === 0) { ?><input name="records[]" class="form-check-input cselect" value="<?php echo $id; ?>" type="checkbox" aria-label="Select <?php echo $escapedName; ?>"><?php } else { ?><span class="visually-hidden">Used codes cannot be deleted</span><?php } ?></td>
                        <td class="d-none d-md-table-cell"><span class="pages-record-id"><?php echo $id; ?></span></td>
                        <td><a class="pages-name-link" href="<?php echo $editUrl; ?>"><?php echo $escapedName; ?></a><span class="pages-cell-meta"><code class="pages-code-value"><?php echo $code; ?></code> <button type="button" class="admin-action-icon font16" data-copy-value="<?php echo $code; ?>" aria-label="Copy code <?php echo $escapedName; ?>" title="Copy code" data-bs-toggle="tooltip"><i class="bi bi-copy" aria-hidden="true"></i></button></span></td>
                        <td><time datetime="<?php echo htmlspecialchars((string) $record[$this->colPrefix.'expiry'], ENT_QUOTES, 'UTF-8'); ?>"><?php echo !empty($record[$this->colPrefix.'expiry']) ? date(ADMIN_DATE_FORMAT, strtotime($record[$this->colPrefix.'expiry'])) : '—'; ?></time></td>
                        <td><?php echo (int) $record['completed_uses']; ?><?php if ((int) $record[$this->colPrefix.'no_of_uses'] > 0) { ?><span class="pages-cell-meta">of <?php echo (int) $record[$this->colPrefix.'no_of_uses']; ?> allowed</span><?php } ?></td>
                        <td><button type="button" class="changestatus pages-status-button" data-controller="<?php echo htmlspecialchars($this->controller, ENT_QUOTES, 'UTF-8'); ?>" id="statusID<?php echo $id; ?>" aria-label="Change status for <?php echo $escapedName; ?>" title="Change status" data-bs-toggle="tooltip"><?php echo htmlspecialchars((string) $record[$this->tStatus], ENT_QUOTES, 'UTF-8'); ?></button></td>
                        <td><?php $this->load->view('admin/partials/translation_status_badge', array('translation_status' => isset($translation_statuses[(string) $id]) ? $translation_statuses[(string) $id] : 'MISSING', 'translation_entity_id' => $id)); ?></td>
                        <td class="d-none d-md-table-cell"><time datetime="<?php echo date('c', strtotime($updatedAt)); ?>"><?php echo date(ADMIN_DATE_FORMAT, strtotime($updatedAt)); ?><span class="pages-cell-meta"><?php echo date(ADMIN_TIME_FORMAT, strtotime($updatedAt)); ?></span></time></td>
                        <td class="pages-actions-cell"><a class="admin-action-icon font16 dupitem" id="copyID<?php echo $id; ?>" data-rec-name="<?php echo $escapedName; ?>" data-controller="<?php echo htmlspecialchars($this->controller, ENT_QUOTES, 'UTF-8'); ?>" href="javascript:void(0);" aria-label="Duplicate <?php echo $escapedName; ?>" title="Duplicate" data-bs-toggle="tooltip"><i class="bi bi-copy" aria-hidden="true"></i></a><a class="admin-action-icon font16" href="<?php echo $editUrl; ?>" aria-label="Edit <?php echo $escapedName; ?>" title="Edit" data-bs-toggle="tooltip"><i class="bi bi-pencil" aria-hidden="true"></i></a><?php if ($uses === 0) { ?><a class="admin-action-icon font16 delitem" href="javascript:void(0);" data-controller="<?php echo htmlspecialchars($this->controller, ENT_QUOTES, 'UTF-8'); ?>" data-record-name="<?php echo $escapedName; ?>" id="recordID<?php echo $id; ?>" aria-label="Delete <?php echo $escapedName; ?>" title="Delete" data-bs-toggle="tooltip"><i class="bi bi-trash" aria-hidden="true"></i></a><?php } ?></td>
                    </tr>
                <?php }} else { ?><tr><td class="pages-empty-state" colspan="9"><strong><?php echo $hasActiveFilters ? 'No discount codes match your filters.' : 'No discount codes found.'; ?></strong><span><?php echo $hasActiveFilters ? 'Try changing your search, referral, or status filter.' : 'Add a discount code to get started.'; ?></span></td></tr><?php } ?>
                </tbody>
            </table>
        </div>
    </form>
    <?php $this->load->view('admin/partials/table_listing_footer', array('total_rows' => $total_rows, 'per_page' => $per_page, 'selected_per_page' => $this->per_page, 'page_offset' => $page_numb, 'pagination' => isset($paginate) ? $paginate : '', 'pagination_label' => 'Discount code pagination')); ?>
</section>

<script>
jQuery(function($) {
    var $form = $('#discount-codes-filter-form'), $search = $('#search_keywords'), $status = $('#search_status'), $referral = $('#search_ref');
    var $rows = $('#multiDel .cselect'), $all = $('#all-checkbox'), $bulk = $('#discount-codes-bulk-actions'), $count = $('#discount-codes-selection-count'), $delete = $('#deleteAllRecords');
    var filterUrl = <?php echo json_encode(base_url('manage/'.$this->controller.'/index/'.$sortby.'/'.(($order === 'ASC') ? 'DESC' : 'ASC'))); ?>;
    function applyFilters() { var keyword = $.trim($search.val()); window.location = filterUrl+'/'+$status.val()+'/'+(keyword === '' ? '-' : encodeURIComponent(keyword))+'/'+$referral.val(); }
    function updateSelection() { var selected = $rows.filter(':checked').length; $count.text(selected+' selected'); $delete.prop('disabled', selected === 0); $bulk.prop('hidden', selected === 0); $all.prop('checked', $rows.length > 0 && selected === $rows.length).prop('indeterminate', selected > 0 && selected < $rows.length).prop('disabled', $rows.length === 0); }
    $form.on('submit', function(event) { event.preventDefault(); applyFilters(); }); $status.add($referral).on('change', applyFilters); $rows.on('change', updateSelection); $all.on('change', function() { window.setTimeout(updateSelection, 0); }); updateSelection();
});
</script>
