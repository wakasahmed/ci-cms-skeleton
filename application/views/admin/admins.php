<?php
$hasActiveFilters = ($keywords !== '-' || $status !== '-');
$addUrl = base_url('manage/'.$this->controller.'/control');
$sortUrl = function ($column) use ($order, $status, $keywords, $page_numb) { return base_url('manage/'.$this->controller.'/index/'.$column.'/'.$order.'/'.$status.'/'.rawurlencode($keywords).'/'.(int) $page_numb); };
$sortIcon = function ($column) use ($sortby, $order) { if ($sortby !== $column) return 'bi-arrow-down-up'; return $order === 'DESC' ? 'bi-arrow-up' : 'bi-arrow-down'; };
$sortAria = function ($column) use ($sortby, $order) { if ($sortby !== $column) return ''; return $order === 'DESC' ? 'ascending' : 'descending'; };
$sortableHeading = function ($column, $label, $class = '') use ($sortUrl, $sortIcon, $sortAria) { $aria = $sortAria($column); ?><th scope="col"<?php if ($class !== '') { ?> class="<?php echo $class; ?>"<?php } ?><?php if ($aria !== '') { ?> aria-sort="<?php echo $aria; ?>"<?php } ?>><a class="pages-sort-link" href="<?php echo $sortUrl($column); ?>"><?php echo $label; ?> <i class="bi <?php echo $sortIcon($column); ?> pages-sort-icon" aria-hidden="true"></i></a></th><?php };
?>
<?php $this->load->view('admin/partials/breadcrumb', array('items' => array(array('label' => $this->moduleName, 'active' => TRUE)))); ?>
<?php $this->load->view('admin/partials/crud_alert', array(
    'module_name' => 'Administrator', 'status' => $alert,
    'status_messages' => array('protected' => array('danger', 'Action blocked!', 'The signed-in account and the final enabled administrator are protected. No accounts were deleted.')),
)); ?>

<section class="admin-records-listing" aria-labelledby="administrators-title">
    <?php $this->load->view('admin/partials/module_header', array('title' => $this->moduleName, 'description' => $this->moduleDesc, 'id' => 'administrators-title', 'action_url' => $addUrl, 'action_label' => 'Add '.$this->moduleNameSingular)); ?>
    <div class="pages-listing-toolbar">
        <form class="pages-filter-form" id="administrators-filter-form" role="search">
            <div class="pages-search-control"><label class="visually-hidden" for="search_keywords">Search administrators</label><span class="pages-search-icon" aria-hidden="true"><i class="bi bi-search"></i></span><input class="form-control" type="search" value="<?php echo $keywords !== '-' ? htmlspecialchars($keywords, ENT_QUOTES, 'UTF-8') : ''; ?>" id="search_keywords" placeholder="Search name, username, email, or phone..." autocomplete="off"></div>
            <button class="btn btn-outline-secondary pages-search-submit" type="submit"><i class="bi bi-search" aria-hidden="true"></i><span class="d-none d-sm-inline">Search</span></button>
            <div class="pages-status-control"><label class="visually-hidden" for="search_status">Filter by status</label><select class="form-select select2" id="search_status" data-minimum-results-for-search="-1"><option value="-">All Statuses</option><option value="Enable" <?php echo $status === 'Enable' ? 'selected' : ''; ?>>Enabled</option><option value="Disable" <?php echo $status === 'Disable' ? 'selected' : ''; ?>>Disabled</option></select></div>
        </form>
    </div>
    <div class="pages-bulk-actions" id="administrators-bulk-actions" aria-live="polite" hidden><span class="pages-selection-count" id="administrators-selection-count">0 selected</span><button id="deleteAllRecords" class="btn btn-sm btn-outline-danger" type="button" disabled><i class="bi bi-trash" aria-hidden="true"></i> Delete Selected</button></div>

    <form action="<?php echo ADMIN_URL.$this->controller; ?>/deleteall" method="post" name="multiDel" id="multiDel">
        <?php if ($this->config->item('csrf_protection')) { ?><input type="hidden" name="<?php echo htmlspecialchars($this->security->get_csrf_token_name(), ENT_QUOTES, 'UTF-8'); ?>" value="<?php echo htmlspecialchars($this->security->get_csrf_hash(), ENT_QUOTES, 'UTF-8'); ?>"><?php } ?>
        <div class="table-responsive pages-table-responsive" tabindex="0" aria-label="Administrators table">
            <table id="table-<?php echo htmlspecialchars($this->controller, ENT_QUOTES, 'UTF-8'); ?>" class="table pages-listing-table admin-records-table">
                <thead><tr><th class="pages-select-column" scope="col"><input class="form-check-input" type="checkbox" id="all-checkbox" autocomplete="off" aria-label="Select all deletable administrators"></th><?php $sortableHeading($this->pKey, 'ID', 'd-none d-md-table-cell'); $sortableHeading('full_name', 'Administrator'); $sortableHeading('user_name', 'Username'); $sortableHeading($this->tStatus, 'Status'); $sortableHeading('date_created', 'Created On', 'd-none d-md-table-cell'); $sortableHeading('last_modified', 'Updated On', 'd-none d-md-table-cell'); ?><th scope="col">Actions</th></tr></thead>
                <tbody>
                <?php if (!empty($records)) { foreach ($records as $record) {
                    $id = (int) $record[$this->pKey];
                    $isCurrent = $id === (int) $current_admin_id;
                    $name = trim((string) $record['full_name']);
                    $displayName = $name !== '' ? $name : (string) $record['user_name'];
                    $escapedName = htmlspecialchars($displayName, ENT_QUOTES, 'UTF-8');
                    $username = htmlspecialchars((string) $record['user_name'], ENT_QUOTES, 'UTF-8');
                    $email = htmlspecialchars((string) $record['email'], ENT_QUOTES, 'UTF-8');
                    $editUrl = base_url('manage/'.$this->controller.'/control/edit/'.$id);
                    $avatar = isset($record['avatar']) ? basename((string) $record['avatar']) : '';
                    $initial = $displayName !== '' ? strtoupper(function_exists('mb_substr') ? mb_substr($displayName, 0, 1, 'UTF-8') : substr($displayName, 0, 1)) : 'A';
                    $addedAt = !empty($record['date_created']) ? $record['date_created'] : NULL;
                    $updatedAt = !empty($record['last_modified']) ? $record['last_modified'] : $addedAt;
                ?>
                    <tr id="<?php echo htmlspecialchars($this->controller, ENT_QUOTES, 'UTF-8').'-'.$id; ?>" data-record-id="<?php echo $id; ?>">
                        <td class="pages-select-column"><?php if (!$isCurrent) { ?><input name="records[]" class="form-check-input cselect" value="<?php echo $id; ?>" type="checkbox" aria-label="Select <?php echo $escapedName; ?>"><?php } else { ?><span class="visually-hidden">The signed-in account cannot be deleted</span><?php } ?></td>
                        <td class="d-none d-md-table-cell"><span class="pages-record-id"><?php echo $id; ?></span></td>
                        <td>
                            <div class="user-card">
                                <?php echo image_thumb('assets/frontend/images/admins/'.$avatar, 96, 96, 'webp', 'circle', TRUE, array('alt' => $displayName, 'fallback' => $initial, 'href' => $editUrl, 'aria_label' => 'Preview '.$displayName, 'size' => 48, 'group' => 'admins', 'class' => 'user-card-image')); ?>
                                <div class="user-card-details">
                                    <a class="pages-name-link" href="<?php echo $editUrl; ?>"><?php echo $escapedName; ?></a>
                                    <?php if ($email !== '') { ?><a class="user-card-email" href="mailto:<?php echo $email; ?>"><?php echo $email; ?></a><?php } ?>
                                    <span class="pages-cell-meta"><?php echo htmlspecialchars(isset($record['user_role']) ? (string) $record['user_role'] : 'Administrator', ENT_QUOTES, 'UTF-8'); ?><?php if ($isCurrent) { ?> · You<?php } ?></span>
                                </div>
                            </div>
                        </td>
                        <td><code><?php echo $username; ?></code></td>
                        <td><?php if ($isCurrent) { ?><button type="button" class="pages-status-button status-enabled" disabled aria-label="Signed-in account is enabled" title="Your signed-in account must remain enabled"><?php echo htmlspecialchars((string) $record[$this->tStatus], ENT_QUOTES, 'UTF-8'); ?></button><?php } else { ?><button type="button" class="changestatus pages-status-button" data-controller="<?php echo htmlspecialchars($this->controller, ENT_QUOTES, 'UTF-8'); ?>" id="statusID<?php echo $id; ?>" aria-label="Change status for <?php echo $escapedName; ?>" title="Change status" data-bs-toggle="tooltip"><?php echo htmlspecialchars((string) $record[$this->tStatus], ENT_QUOTES, 'UTF-8'); ?></button><?php } ?></td>
                        <td class="d-none d-md-table-cell"><?php if ($addedAt) { ?><time datetime="<?php echo date('c', strtotime($addedAt)); ?>"><?php echo date(ADMIN_DATE_FORMAT, strtotime($addedAt)); ?><span class="pages-cell-meta"><?php echo date(ADMIN_TIME_FORMAT, strtotime($addedAt)); ?></span></time><?php } else { ?>—<?php } ?></td>
                        <td class="d-none d-md-table-cell"><?php if ($updatedAt) { ?><time datetime="<?php echo date('c', strtotime($updatedAt)); ?>"><?php echo date(ADMIN_DATE_FORMAT, strtotime($updatedAt)); ?><span class="pages-cell-meta"><?php echo date(ADMIN_TIME_FORMAT, strtotime($updatedAt)); ?></span></time><?php } else { ?>—<?php } ?></td>
                        <td class="pages-actions-cell"><a class="admin-action-icon font16" href="<?php echo $editUrl; ?>" aria-label="Edit <?php echo $escapedName; ?>" title="Edit" data-bs-toggle="tooltip"><i class="bi bi-pencil" aria-hidden="true"></i></a><?php if (!$isCurrent) { ?><a class="admin-action-icon font16 delitem" href="javascript:void(0);" data-controller="<?php echo htmlspecialchars($this->controller, ENT_QUOTES, 'UTF-8'); ?>" data-record-name="<?php echo $escapedName; ?>" id="recordID<?php echo $id; ?>" aria-label="Delete <?php echo $escapedName; ?>" title="Delete" data-bs-toggle="tooltip"><i class="bi bi-trash" aria-hidden="true"></i></a><?php } ?></td>
                    </tr>
                <?php }} else { ?><tr><td class="pages-empty-state" colspan="8"><strong><?php echo $hasActiveFilters ? 'No administrators match your filters.' : 'No administrators found.'; ?></strong><span><?php echo $hasActiveFilters ? 'Try changing your search or status filter.' : 'Add an administrator to get started.'; ?></span></td></tr><?php } ?>
                </tbody>
            </table>
        </div>
    </form>
    <?php $this->load->view('admin/partials/table_listing_footer', array('total_rows' => $total_rows, 'per_page' => $per_page, 'selected_per_page' => $this->per_page, 'page_offset' => $page_numb, 'pagination' => isset($paginate) ? $paginate : '', 'pagination_label' => 'Administrator pagination')); ?>
</section>

<script>
jQuery(function($) {
    var $form = $('#administrators-filter-form'), $search = $('#search_keywords'), $status = $('#search_status');
    var $rows = $('#multiDel .cselect'), $all = $('#all-checkbox'), $bulk = $('#administrators-bulk-actions'), $count = $('#administrators-selection-count'), $delete = $('#deleteAllRecords');
    var filterUrl = <?php echo json_encode(base_url('manage/'.$this->controller.'/index/'.$sortby.'/'.(($order === 'ASC') ? 'DESC' : 'ASC'))); ?>;
    function applyFilters() { var keyword = $.trim($search.val()); window.location = filterUrl+'/'+$status.val()+'/'+(keyword === '' ? '-' : encodeURIComponent(keyword)); }
    function updateSelection() { var selected = $rows.filter(':checked').length; $count.text(selected+' selected'); $delete.prop('disabled', selected === 0); $bulk.prop('hidden', selected === 0); $all.prop('checked', $rows.length > 0 && selected === $rows.length).prop('indeterminate', selected > 0 && selected < $rows.length).prop('disabled', $rows.length === 0); }
    $form.on('submit', function(event) { event.preventDefault(); applyFilters(); }); $status.on('change', applyFilters); $rows.on('change', updateSelection); $all.on('change', function() { window.setTimeout(updateSelection, 0); }); updateSelection();
});
</script>
