<?php
$hasActiveFilters = ($keywords !== '-' || $status !== '-');
$addUrl = base_url('manage/'.$this->controller.'/control');
$sortUrl = function ($column) use ($order, $status, $keywords, $page_numb) { return base_url('manage/'.$this->controller.'/index/'.$column.'/'.$order.'/'.$status.'/'.rawurlencode($keywords).'/'.$page_numb); };
$sortIcon = function ($column) use ($sortby, $order) { if ($sortby !== $column) return 'bi-arrow-down-up'; return $order === 'DESC' ? 'bi-arrow-up' : 'bi-arrow-down'; };
$sortAria = function ($column) use ($sortby, $order) { if ($sortby !== $column) return ''; return $order === 'DESC' ? 'ascending' : 'descending'; };
$sortingEnabled = !$hasActiveFilters && $sortby === $this->colPrefix.'order' && $order === 'DESC';
$sortableHeading = function ($column, $label, $class = '') use ($sortUrl, $sortIcon, $sortAria) { $aria = $sortAria($column); ?><th scope="col"<?php if ($class !== '') { ?> class="<?php echo $class; ?>"<?php } ?><?php if ($aria !== '') { ?> aria-sort="<?php echo $aria; ?>"<?php } ?>><a class="pages-sort-link" href="<?php echo $sortUrl($column); ?>"><?php echo $label; ?> <i class="bi <?php echo $sortIcon($column); ?> pages-sort-icon" aria-hidden="true"></i></a></th><?php };
$displayTime = function ($value) { return !empty($value) ? date(ADMIN_TIME_FORMAT, strtotime($value)) : '—'; };
?>
<?php $this->load->view('admin/partials/breadcrumb', array('items' => array(array('label' => $this->moduleName, 'active' => TRUE)))); ?>
<?php $this->load->view('admin/partials/crud_alert', array(
    'module_name' => 'Tour slot',
    'status' => $alert,
    'status_messages' => array('deleteblocked' => array('danger', 'Cannot delete!', 'A selected tour slot is used by a booking or reserved availability. No records were deleted.')),
)); ?>

<section class="admin-records-listing" aria-labelledby="tour-slots-title" data-translation-poll data-module="tour_slots" data-status-url="<?php echo base_url('manage/translations/statuses'); ?>" data-poll-seconds="4">
    <?php $this->load->view('admin/partials/module_header', array('title' => $this->moduleName, 'description' => $this->moduleDesc, 'id' => 'tour-slots-title', 'action_url' => $addUrl, 'action_label' => 'Add '.$this->moduleNameSingular)); ?>
    <div class="pages-listing-toolbar"><form class="pages-filter-form" id="tour-slots-filter-form" role="search"><div class="pages-search-control"><label class="visually-hidden" for="search_keywords">Search tour slots</label><span class="pages-search-icon" aria-hidden="true"><i class="bi bi-search"></i></span><input class="form-control" type="search" value="<?php echo $keywords !== '-' ? htmlspecialchars($keywords, ENT_QUOTES, 'UTF-8') : ''; ?>" id="search_keywords" placeholder="Search title, English, or Arabic name..." autocomplete="off"></div><button class="btn btn-outline-secondary pages-search-submit" type="submit"><i class="bi bi-search" aria-hidden="true"></i><span class="d-none d-sm-inline">Search</span></button><div class="pages-status-control"><label class="visually-hidden" for="search_status">Filter by status</label><select class="form-select select2" id="search_status" data-minimum-results-for-search="-1"><option value="-">All Statuses</option><option value="Enable" <?php echo $status === 'Enable' ? 'selected' : ''; ?>>Enabled</option><option value="Disable" <?php echo $status === 'Disable' ? 'selected' : ''; ?>>Disabled</option></select></div></form></div>
    <div class="pages-bulk-actions" id="tour-slots-bulk-actions" aria-live="polite" hidden><span class="pages-selection-count" id="tour-slots-selection-count">0 selected</span><button id="deleteAllRecords" class="btn btn-sm btn-outline-danger" type="button" disabled><i class="bi bi-trash" aria-hidden="true"></i> Delete Selected</button></div>

    <form action="<?php echo ADMIN_URL.$this->controller; ?>/deleteall" method="post" name="multiDel" id="multiDel">
        <div class="table-responsive pages-table-responsive" tabindex="0" aria-label="Tour slots table">
            <table id="table-<?php echo htmlspecialchars($this->controller, ENT_QUOTES, 'UTF-8'); ?>" class="table pages-listing-table admin-records-table"<?php if ($sortingEnabled) { ?> data-sortable-records data-sort-handle=".pages-drag-handle" data-sort-url="<?php echo base_url('manage/record-sorting/sort/'.$this->controller); ?>" data-sort-offset="<?php echo (int) $page_numb; ?>"<?php } ?>>
                <thead><tr><?php if ($sortingEnabled) { ?><th scope="col"><span class="visually-hidden">Reorder</span></th><?php } ?><th class="pages-select-column" scope="col"><input class="form-check-input" type="checkbox" id="all-checkbox" autocomplete="off" aria-label="Select all tour slots"></th><?php $sortableHeading($this->pKey, 'ID', 'd-none d-md-table-cell'); $sortableHeading($this->colPrefix.'title', 'Title'); ?><th scope="col">Time</th><?php $sortableHeading($this->tStatus, 'Status'); ?><th scope="col">Translation</th><?php $sortableHeading($this->colPrefix.'added', 'Created On', 'd-none d-md-table-cell'); $sortableHeading($this->colPrefix.'updated', 'Updated On', 'd-none d-md-table-cell'); ?><th scope="col">Actions</th></tr></thead>
                <tbody>
                <?php if (!empty($records)) { foreach ($records as $record) {
                    $id = (int) $record[$this->pKey];
                    $name = trim((string) $record[$this->colPrefix.'name']);
                    $escapedName = htmlspecialchars($name, ENT_QUOTES, 'UTF-8');
                    $title = htmlspecialchars((string) $record[$this->colPrefix.'title'], ENT_QUOTES, 'UTF-8');
                    $editUrl = base_url('manage/'.$this->controller.'/control/edit/'.$id);
                    $updatedAt = !empty($record[$this->colPrefix.'updated']) ? $record[$this->colPrefix.'updated'] : '';
                ?>
                    <tr id="<?php echo htmlspecialchars($this->controller, ENT_QUOTES, 'UTF-8').'-'.$id; ?>" data-record-id="<?php echo $id; ?>">
                        <?php if ($sortingEnabled) { ?><td class="pages-drag-handle" title="Drag to reorder <?php echo $escapedName; ?>" aria-label="Drag to reorder <?php echo $escapedName; ?>"><i class="bi bi-grip-vertical" aria-hidden="true"></i></td><?php } ?>
                        <td class="pages-select-column"><input name="records[]" class="form-check-input cselect" value="<?php echo $id; ?>" type="checkbox" aria-label="Select <?php echo $escapedName; ?>"></td>
                        <td class="d-none d-md-table-cell"><span class="pages-record-id"><?php echo $id; ?></span></td>
                        <td><div class="d-flex align-items-center gap-2"><a class="pages-name-link" href="<?php echo $editUrl; ?>"><?php echo $title; ?></a></div></td>
                        <td><span class="pages-record-id"><?php echo $displayTime($record[$this->colPrefix.'start_time']); ?> - <?php echo $displayTime($record[$this->colPrefix.'end_time']); ?></span><br><span class="pages-cell-meta">(<?php echo (int) $record[$this->colPrefix.'hours']; ?> hour<?php echo (int) $record[$this->colPrefix.'hours'] === 1 ? '' : 's'; ?>)</span></td>
                        <td><button type="button" class="changestatus pages-status-button" data-controller="<?php echo htmlspecialchars($this->controller, ENT_QUOTES, 'UTF-8'); ?>" id="statusID<?php echo $id; ?>" aria-label="Change status for <?php echo $escapedName; ?>" title="Change status" data-bs-toggle="tooltip"><?php echo htmlspecialchars((string) $record[$this->tStatus], ENT_QUOTES, 'UTF-8'); ?></button></td>
                        <td><?php $this->load->view('admin/partials/translation_status_badge', array('translation_status' => isset($translation_statuses[(string) $id]) ? $translation_statuses[(string) $id] : 'MISSING', 'translation_entity_id' => $id)); ?></td>
                        <td class="d-none d-md-table-cell"><time datetime="<?php echo date('c', strtotime($record[$this->colPrefix.'added'])); ?>"><?php echo date(ADMIN_DATE_FORMAT, strtotime($record[$this->colPrefix.'added'])); ?><span class="pages-cell-meta"><?php echo date(ADMIN_TIME_FORMAT, strtotime($record[$this->colPrefix.'added'])); ?></span></time></td>
                        <td class="d-none d-md-table-cell"><?php if ($updatedAt !== '') { ?><time datetime="<?php echo date('c', strtotime($updatedAt)); ?>"><?php echo date(ADMIN_DATE_FORMAT, strtotime($updatedAt)); ?><span class="pages-cell-meta"><?php echo date(ADMIN_TIME_FORMAT, strtotime($updatedAt)); ?></span></time><?php } else { ?>—<?php } ?></td>
                        <td class="pages-actions-cell"><a class="admin-action-icon font16 dupitem" id="copyID<?php echo $id; ?>" data-rec-name="<?php echo $escapedName; ?>" data-controller="<?php echo htmlspecialchars($this->controller, ENT_QUOTES, 'UTF-8'); ?>" href="javascript:void(0);" aria-label="Duplicate <?php echo $escapedName; ?>" title="Duplicate" data-bs-toggle="tooltip"><i class="bi bi-copy" aria-hidden="true"></i></a><a class="admin-action-icon font16" href="<?php echo $editUrl; ?>" aria-label="Edit <?php echo $escapedName; ?>" title="Edit" data-bs-toggle="tooltip"><i class="bi bi-pencil" aria-hidden="true"></i></a><a class="admin-action-icon font16 delitem" href="javascript:void(0);" data-controller="<?php echo htmlspecialchars($this->controller, ENT_QUOTES, 'UTF-8'); ?>" data-record-name="<?php echo $escapedName; ?>" id="recordID<?php echo $id; ?>" aria-label="Delete <?php echo $escapedName; ?>" title="Delete" data-bs-toggle="tooltip"><i class="bi bi-trash" aria-hidden="true"></i></a></td>
                    </tr>
                <?php }} else { ?><tr class="nodrag"><td class="pages-empty-state" colspan="<?php echo $sortingEnabled ? 10 : 9; ?>"><strong><?php echo $hasActiveFilters ? 'No tour slots match your filters.' : 'No tour slots found.'; ?></strong><span><?php echo $hasActiveFilters ? 'Try changing your search or status filter.' : 'Add a tour slot to get started.'; ?></span></td></tr><?php } ?>
                </tbody>
            </table>
        </div>
    </form>
    <?php $this->load->view('admin/partials/table_listing_footer', array('total_rows' => $total_rows, 'per_page' => $per_page, 'selected_per_page' => $this->per_page, 'page_offset' => $page_numb, 'pagination' => isset($paginate) ? $paginate : '', 'pagination_label' => 'Tour slot pagination')); ?>
</section>

<script>
jQuery(function($) {
    var $form = $('#tour-slots-filter-form'), $search = $('#search_keywords'), $status = $('#search_status');
    var $rows = $('#multiDel .cselect'), $all = $('#all-checkbox'), $bulk = $('#tour-slots-bulk-actions'), $count = $('#tour-slots-selection-count'), $delete = $('#deleteAllRecords');
    var filterUrl = <?php echo json_encode(base_url('manage/'.$this->controller.'/index/'.$sortby.'/'.(($order === 'ASC') ? 'DESC' : 'ASC'))); ?>;
    function applyFilters() { var keyword = $.trim($search.val()); window.location = filterUrl+'/'+$status.val()+'/'+(keyword === '' ? '-' : encodeURIComponent(keyword)); }
    function updateSelection() { var selected = $rows.filter(':checked').length; $count.text(selected+' selected'); $delete.prop('disabled', selected === 0); $bulk.prop('hidden', selected === 0); $all.prop('checked', $rows.length > 0 && selected === $rows.length).prop('indeterminate', selected > 0 && selected < $rows.length).prop('disabled', $rows.length === 0); }
    $form.on('submit', function(event) { event.preventDefault(); applyFilters(); }); $status.on('change', applyFilters); $rows.on('change', updateSelection); $all.on('change', function() { window.setTimeout(updateSelection, 0); }); updateSelection();
});
</script>
