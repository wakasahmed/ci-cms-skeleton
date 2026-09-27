<?php
$hasActiveFilters = $keywords !== '-' || $status !== '-';
$addUrl = base_url('manage/'.$this->controller.'/control');
$sortUrl = function ($column) use ($order, $status, $keywords, $page_numb) {
    return base_url('manage/'.$this->controller.'/index/'.$column.'/'.$order.'/'.$status.'/'.rawurlencode($keywords).'/'.$page_numb);
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
<?php $this->load->view('admin/partials/breadcrumb', array(
    'items' => array(array('label' => $this->moduleName, 'active' => TRUE)),
)); ?>
<?php $this->load->view('admin/partials/crud_alert', array(
    'module_name' => 'Referral',
    'status' => $alert,
)); ?>

<section
    class="admin-records-listing"
    aria-labelledby="referrals-title"
    data-translation-poll
    data-module="referrals"
    data-status-url="<?php echo base_url('manage/translations/statuses'); ?>"
    data-poll-seconds="4"
>
    <?php $this->load->view('admin/partials/module_header', array(
        'title' => $this->moduleName,
        'description' => $this->moduleDesc,
        'id' => 'referrals-title',
        'action_url' => $addUrl,
        'action_label' => 'Add '.$this->moduleNameSingular,
    )); ?>

    <div class="pages-listing-toolbar">
        <form class="pages-filter-form" id="referrals-filter-form" role="search">
            <div class="pages-search-control">
                <label class="visually-hidden" for="search_keywords">Search referrals</label>
                <span class="pages-search-icon" aria-hidden="true"><i class="bi bi-search"></i></span>
                <input class="form-control" type="search" value="<?php echo $keywords !== '-' ? htmlspecialchars($keywords, ENT_QUOTES, 'UTF-8') : ''; ?>" id="search_keywords" placeholder="Search name, organisation, or email..." autocomplete="off">
            </div>
            <button class="btn btn-outline-secondary pages-search-submit" type="submit"><i class="bi bi-search" aria-hidden="true"></i><span class="d-none d-sm-inline">Search</span></button>
            <div class="pages-status-control">
                <label class="visually-hidden" for="search_status">Filter by status</label>
                <select class="form-select select2" id="search_status" data-minimum-results-for-search="-1">
                    <option value="-">All Statuses</option>
                    <option value="Enable"<?php echo $status === 'Enable' ? ' selected' : ''; ?>>Enabled</option>
                    <option value="Disable"<?php echo $status === 'Disable' ? ' selected' : ''; ?>>Disabled</option>
                </select>
            </div>
        </form>
    </div>

    <div class="pages-bulk-actions" id="referrals-bulk-actions" aria-live="polite" hidden>
        <span class="pages-selection-count" id="referrals-selection-count">0 selected</span>
        <button id="deleteAllRecords" class="btn btn-sm btn-outline-danger" type="button" disabled><i class="bi bi-trash" aria-hidden="true"></i> Delete Selected</button>
    </div>

    <form action="<?php echo ADMIN_URL.$this->controller; ?>/deleteall" method="post" name="multiDel" id="multiDel">
        <div class="table-responsive pages-table-responsive" tabindex="0" aria-label="Referrals table">
            <table class="table pages-listing-table admin-records-table" id="table-<?php echo $this->controller; ?>">
                <thead>
                    <tr>
                        <th class="pages-select-column" scope="col"><input class="form-check-input" type="checkbox" id="all-checkbox" autocomplete="off" aria-label="Select all referrals"></th>
                        <?php foreach (array(
                            $this->pKey => 'ID',
                            'ref_name' => 'Referral',
                            'total_commission' => 'Commission',
                            'total_received' => 'Paid',
                            'total_remaining' => 'Unpaid',
                            $this->tStatus => 'Status',
                        ) as $column => $label) { ?>
                            <th scope="col"<?php echo $column === $this->pKey ? ' class="d-none d-md-table-cell"' : ''; ?><?php echo $sortAria($column) !== '' ? ' aria-sort="'.$sortAria($column).'"' : ''; ?>><a class="pages-sort-link" href="<?php echo $sortUrl($column); ?>"><?php echo $label; ?> <i class="bi <?php echo $sortIcon($column); ?> pages-sort-icon" aria-hidden="true"></i></a></th>
                        <?php } ?>
                        <th scope="col">Translation</th>
                        <th scope="col" class="d-none d-md-table-cell"<?php echo $sortAria('ref_added') !== '' ? ' aria-sort="'.$sortAria('ref_added').'"' : ''; ?>><a class="pages-sort-link" href="<?php echo $sortUrl('ref_added'); ?>">Created On <i class="bi <?php echo $sortIcon('ref_added'); ?> pages-sort-icon" aria-hidden="true"></i></a></th>
                        <th scope="col"><span class="visually-hidden">Actions</span></th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (!empty($records)) { ?>
                        <?php foreach ($records as $record) { ?>
                            <?php
                            $id = (int) $record[$this->pKey];
                            $name = trim((string) $record['ref_name']);
                            $escapedName = htmlspecialchars($name, ENT_QUOTES, 'UTF-8');
                            $email = htmlspecialchars((string) $record['ref_email'], ENT_QUOTES, 'UTF-8');
                            $editUrl = base_url('manage/'.$this->controller.'/control/edit/'.$id);
                            $canDelete = (int) $record['total_use'] === 0;
                            $amount = function ($value) {
                                return 'SR'.($value === NULL || $value === '' ? '0.000' : number_format((float) $value, 3));
                            };
                            ?>
                            <tr data-record-id="<?php echo $id; ?>">
                                <td class="pages-select-column">
                                    <?php if ($canDelete) { ?><input name="records[]" class="form-check-input cselect" value="<?php echo $id; ?>" type="checkbox" aria-label="Select <?php echo $escapedName; ?>"><?php } ?>
                                </td>
                                <td class="d-none d-md-table-cell"><span class="pages-record-id"><?php echo $id; ?></span></td>
                                <td><div class="user-card"><span class="admin-table-avatar" aria-hidden="true"><?php echo htmlspecialchars(strtoupper(substr($name, 0, 1)) ?: 'R', ENT_QUOTES, 'UTF-8'); ?></span><div class="user-card-details"><a class="pages-name-link" href="<?php echo $editUrl; ?>"><?php echo $escapedName; ?></a><a class="user-card-email" href="mailto:<?php echo $email; ?>"><?php echo $email; ?></a></div></div></td>
                                <td><?php echo $amount($record['total_commission']); ?></td>
                                <td><?php echo $amount($record['total_received']); ?></td>
                                <td><?php echo $amount($record['total_remaining']); ?></td>
                                <td><button type="button" class="changestatus pages-status-button" data-controller="<?php echo $this->controller; ?>" id="statusID<?php echo $id; ?>" aria-label="Change status for <?php echo $escapedName; ?>" title="Change status"><?php echo htmlspecialchars($record[$this->tStatus], ENT_QUOTES, 'UTF-8'); ?></button></td>
                                <td><?php $this->load->view('admin/partials/translation_status_badge', array('translation_status' => isset($translation_statuses[(string) $id]) ? $translation_statuses[(string) $id] : 'MISSING', 'translation_entity_id' => $id)); ?></td>
                                <td class="d-none d-md-table-cell"><time datetime="<?php echo date('c', strtotime($record['ref_added'])); ?>"><?php echo date(ADMIN_DATE_FORMAT, strtotime($record['ref_added'])); ?><span class="pages-cell-meta"><?php echo date(ADMIN_TIME_FORMAT, strtotime($record['ref_added'])); ?></span></time></td>
                                <td class="pages-actions-cell"><a class="admin-action-icon font16 dupitem" id="copyID<?php echo $id; ?>" data-rec-name="<?php echo $escapedName; ?>" data-controller="<?php echo $this->controller; ?>" href="javascript:void(0);" aria-label="Duplicate <?php echo $escapedName; ?>" title="Duplicate"><i class="bi bi-copy" aria-hidden="true"></i></a><a class="admin-action-icon font16" href="<?php echo $editUrl; ?>" aria-label="Edit <?php echo $escapedName; ?>" title="Edit"><i class="bi bi-pencil" aria-hidden="true"></i></a><?php if ($canDelete) { ?><a class="admin-action-icon font16 delitem" href="javascript:void(0);" data-controller="<?php echo $this->controller; ?>" data-record-name="<?php echo $escapedName; ?>" id="recordID<?php echo $id; ?>" aria-label="Delete <?php echo $escapedName; ?>" title="Delete"><i class="bi bi-trash" aria-hidden="true"></i></a><?php } ?></td>
                            </tr>
                        <?php } ?>
                    <?php } else { ?>
                        <tr><td class="pages-empty-state" colspan="11"><strong><?php echo $hasActiveFilters ? 'No referrals match your filters.' : 'No referrals found.'; ?></strong><span><?php echo $hasActiveFilters ? 'Try changing your search or status filter.' : 'Add a referral to get started.'; ?></span></td></tr>
                    <?php } ?>
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
        'pagination_label' => 'Referral pagination',
    )); ?>
</section>

<script>
jQuery(function ($) {
    var $form = $('#referrals-filter-form');
    var $rows = $('#multiDel .cselect');
    var $all = $('#all-checkbox');
    var $bulk = $('#referrals-bulk-actions');
    var $count = $('#referrals-selection-count');
    var $delete = $('#deleteAllRecords');
    var filterUrl = <?php echo json_encode(base_url('manage/'.$this->controller.'/index/'.$sortby.'/'.$order)); ?>;
    function applyFilters() { var keyword = $.trim($('#search_keywords').val()); window.location = filterUrl+'/'+$('#search_status').val()+'/'+(keyword === '' ? '-' : encodeURIComponent(keyword)); }
    function updateSelection() { var selected = $rows.filter(':checked').length; $count.text(selected+' selected'); $delete.prop('disabled', selected === 0); $bulk.prop('hidden', selected === 0); $all.prop('checked', $rows.length > 0 && selected === $rows.length).prop('indeterminate', selected > 0 && selected < $rows.length).prop('disabled', $rows.length === 0); }
    $form.on('submit', function (event) { event.preventDefault(); applyFilters(); });
    $('#search_status').on('change', applyFilters);
    $rows.on('change', updateSelection);
    $all.on('change', function () { window.setTimeout(updateSelection, 0); });
    updateSelection();
});
</script>
