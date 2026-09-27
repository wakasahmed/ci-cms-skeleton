<?php
$hasActiveFilters = ($keywords !== '-');
$addUrl = base_url('manage/'.$this->controller.'/control');
$sortUrl = function ($column) use ($order, $keywords, $page_numb) {
    return base_url('manage/'.$this->controller.'/index/'.$column.'/'.$order.'/'.rawurlencode($keywords).'/'.$page_numb);
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
<?php $this->load->view('admin/partials/crud_alert', array(
    'module_name' => 'Country',
    'status' => $alert,
    'status_messages' => array('deleteblocked' => array('danger', 'Cannot delete!', 'A selected country is used by existing bookings. No records were deleted.')),
)); ?>

<section class="admin-records-listing" aria-labelledby="countries-title" data-translation-poll data-module="countries" data-status-url="<?php echo base_url('manage/translations/statuses'); ?>" data-poll-seconds="4">
    <?php $this->load->view('admin/partials/module_header', array('title' => $this->moduleName, 'description' => $this->moduleDesc, 'id' => 'countries-title', 'action_url' => $addUrl, 'action_label' => 'Add '.$this->moduleNameSingular)); ?>

    <div class="pages-listing-toolbar">
        <form class="pages-filter-form" id="countries-filter-form" role="search">
            <div class="pages-search-control"><label class="visually-hidden" for="search_keywords">Search countries</label><span class="pages-search-icon" aria-hidden="true"><i class="bi bi-search"></i></span><input class="form-control" type="search" value="<?php echo $keywords !== '-' ? htmlspecialchars($keywords, ENT_QUOTES, 'UTF-8') : ''; ?>" id="search_keywords" placeholder="Search English or Arabic names..." autocomplete="off"></div>
            <button class="btn btn-outline-secondary pages-search-submit" type="submit"><i class="bi bi-search" aria-hidden="true"></i><span class="d-none d-sm-inline">Search</span></button>
        </form>
    </div>

    <div class="pages-bulk-actions" id="countries-bulk-actions" aria-live="polite" hidden><span class="pages-selection-count" id="countries-selection-count">0 selected</span><button id="deleteAllRecords" class="btn btn-sm btn-outline-danger" type="button" disabled><i class="bi bi-trash" aria-hidden="true"></i> Delete Selected</button></div>

    <form action="<?php echo ADMIN_URL.$this->controller; ?>/deleteall" method="post" name="multiDel" id="multiDel">
        <div class="table-responsive pages-table-responsive" tabindex="0" aria-label="Countries table">
            <table id="table-<?php echo htmlspecialchars($this->controller, ENT_QUOTES, 'UTF-8'); ?>" class="table pages-listing-table admin-records-table">
                <thead><tr>
                    <th class="pages-select-column" scope="col"><input class="form-check-input" type="checkbox" id="all-checkbox" autocomplete="off" aria-label="Select all countries"></th>
                    <?php foreach (array($this->pKey => 'ID', 'name' => 'Name', 'iso' => 'ISO', 'phonecode' => 'Phone Code') as $column => $label) { $idColClass = $column === $this->pKey ? ' d-none d-md-table-cell' : ''; ?><th scope="col"<?php if ($idColClass !== '') { ?> class="<?php echo trim($idColClass); ?>"<?php } ?><?php if ($sortAria($column) !== '') { ?> aria-sort="<?php echo $sortAria($column); ?>"<?php } ?>><a class="pages-sort-link" href="<?php echo $sortUrl($column); ?>"><?php echo $label; ?> <i class="bi <?php echo $sortIcon($column); ?> pages-sort-icon" aria-hidden="true"></i></a></th><?php } ?>
                    <th scope="col">Translation</th>
                    <?php foreach (array('added' => 'Created On', 'updated' => 'Updated On') as $column => $label) { ?><th scope="col" class="d-none d-md-table-cell"<?php if ($sortAria($column) !== '') { ?> aria-sort="<?php echo $sortAria($column); ?>"<?php } ?>><a class="pages-sort-link" href="<?php echo $sortUrl($column); ?>"><?php echo $label; ?> <i class="bi <?php echo $sortIcon($column); ?> pages-sort-icon" aria-hidden="true"></i></a></th><?php } ?>
                    <th scope="col">Actions</th>
                </tr></thead>
                <tbody>
                <?php if (!empty($records)) { foreach ($records as $record) {
                    $id = (int) $record[$this->pKey];
                    $name = trim((string) $record['name']);
                    $escapedName = htmlspecialchars($name, ENT_QUOTES, 'UTF-8');
                    $editUrl = base_url('manage/'.$this->controller.'/control/edit/'.$id);
                    $updatedAt = !empty($record['updated']) ? $record['updated'] : $record['added'];
                ?>
                    <tr id="<?php echo htmlspecialchars($this->controller, ENT_QUOTES, 'UTF-8').'-'.$id; ?>" data-record-id="<?php echo $id; ?>">
                        <td class="pages-select-column"><input name="records[]" class="form-check-input cselect" value="<?php echo $id; ?>" type="checkbox" aria-label="Select <?php echo $escapedName; ?>"></td>
                        <td class="d-none d-md-table-cell"><span class="pages-record-id"><?php echo $id; ?></span></td>
                        <td><a class="pages-name-link" href="<?php echo $editUrl; ?>"><?php echo $escapedName; ?></a></td>
                        <td><?php echo htmlspecialchars((string) $record['iso'], ENT_QUOTES, 'UTF-8'); ?></td>
                        <td><?php echo $record['phonecode'] !== NULL && $record['phonecode'] !== '' ? '+'.htmlspecialchars((string) $record['phonecode'], ENT_QUOTES, 'UTF-8') : '<span class="pages-cell-meta">&mdash;</span>'; ?></td>
                        <td><?php $this->load->view('admin/partials/translation_status_badge', array('translation_status' => isset($translation_statuses[(string) $id]) ? $translation_statuses[(string) $id] : 'MISSING', 'translation_entity_id' => $id)); ?></td>
                        <td class="d-none d-md-table-cell"><time datetime="<?php echo date('c', strtotime($record['added'])); ?>"><?php echo date(ADMIN_DATE_FORMAT, strtotime($record['added'])); ?><span class="pages-cell-meta"><?php echo date(ADMIN_TIME_FORMAT, strtotime($record['added'])); ?></span></time></td>
                        <td class="d-none d-md-table-cell"><time datetime="<?php echo date('c', strtotime($updatedAt)); ?>"><?php echo date(ADMIN_DATE_FORMAT, strtotime($updatedAt)); ?><span class="pages-cell-meta"><?php echo date(ADMIN_TIME_FORMAT, strtotime($updatedAt)); ?></span></time></td>
                        <td class="pages-actions-cell"><a class="admin-action-icon font16 dupitem" id="copyID<?php echo $id; ?>" data-rec-name="<?php echo $escapedName; ?>" data-controller="<?php echo htmlspecialchars($this->controller, ENT_QUOTES, 'UTF-8'); ?>" href="javascript:void(0);" aria-label="Duplicate <?php echo $escapedName; ?>" title="Duplicate" data-bs-toggle="tooltip"><i class="bi bi-copy" aria-hidden="true"></i></a><a class="admin-action-icon font16" href="<?php echo $editUrl; ?>" aria-label="Edit <?php echo $escapedName; ?>" title="Edit" data-bs-toggle="tooltip"><i class="bi bi-pencil" aria-hidden="true"></i></a><a class="admin-action-icon font16 delitem" href="javascript:void(0);" data-controller="<?php echo htmlspecialchars($this->controller, ENT_QUOTES, 'UTF-8'); ?>" data-record-name="<?php echo $escapedName; ?>" id="recordID<?php echo $id; ?>" aria-label="Delete <?php echo $escapedName; ?>" title="Delete" data-bs-toggle="tooltip"><i class="bi bi-trash" aria-hidden="true"></i></a></td>
                    </tr>
                <?php }} else { ?><tr class="nodrag"><td class="pages-empty-state" colspan="9"><strong><?php echo $hasActiveFilters ? 'No countries match your filters.' : 'No countries found.'; ?></strong><span><?php echo $hasActiveFilters ? 'Try changing your search.' : 'Add a country to get started.'; ?></span></td></tr><?php } ?>
                </tbody>
            </table>
        </div>
    </form>

    <?php $this->load->view('admin/partials/table_listing_footer', array('total_rows' => $total_rows, 'per_page' => $per_page, 'selected_per_page' => $this->per_page, 'page_offset' => $page_numb, 'pagination' => isset($paginate) ? $paginate : '', 'pagination_label' => 'Countries pagination')); ?>
</section>

<script>
jQuery(function($) {
    var $form = $('#countries-filter-form'), $search = $('#search_keywords');
    var $rows = $('#multiDel .cselect'), $all = $('#all-checkbox'), $bulk = $('#countries-bulk-actions'), $count = $('#countries-selection-count'), $delete = $('#deleteAllRecords');
    var filterUrl = <?php echo json_encode(base_url('manage/'.$this->controller.'/index/'.$sortby.'/'.(($order === 'ASC') ? 'DESC' : 'ASC'))); ?>;
    function applyFilters() { var keyword = $.trim($search.val()); window.location = filterUrl+'/'+(keyword === '' ? '-' : encodeURIComponent(keyword)); }
    function updateSelection() { var selected = $rows.filter(':checked').length; $count.text(selected+' selected'); $delete.prop('disabled', selected === 0); $bulk.prop('hidden', selected === 0); $all.prop('checked', $rows.length > 0 && selected === $rows.length).prop('indeterminate', selected > 0 && selected < $rows.length).prop('disabled', $rows.length === 0); }
    $form.on('submit', function(event) { event.preventDefault(); applyFilters(); });
    $rows.on('change', updateSelection); $all.on('change', function() { window.setTimeout(updateSelection, 0); }); updateSelection();
});
</script>
