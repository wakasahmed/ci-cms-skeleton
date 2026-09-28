<?php
$listingTitle = $this->moduleName;
$hasActiveFilters = ($keywords !== '-' || $status !== '-');
$addUrl = base_url('manage/'.$this->controller.'/control/'.(int) $parent_id);
$sortUrl = function ($column) use ($order, $status, $keywords, $page_numb, $parent_id) { return base_url('manage/'.$this->controller.'/index/'.(int) $parent_id.'/'.$column.'/'.$order.'/'.$status.'/'.urlencode($keywords).'/'.(int) $page_numb); };
$sortIcon = function ($column) use ($sortby, $order) { if ($sortby !== $column) return 'bi-arrow-down-up'; return $order === 'DESC' ? 'bi-arrow-up' : 'bi-arrow-down'; };
$sortAria = function ($column) use ($sortby, $order) { if ($sortby !== $column) return ''; return $order === 'DESC' ? 'ascending' : 'descending'; };
$sortableHeading = function ($column, $label, $class = '') use ($sortUrl, $sortIcon, $sortAria) { $aria = $sortAria($column); ?><th scope="col"<?php if ($class !== '') { ?> class="<?php echo $class; ?>"<?php } ?><?php if ($aria !== '') { ?> aria-sort="<?php echo $aria; ?>"<?php } ?>><a class="pages-sort-link" href="<?php echo $sortUrl($column); ?>"><?php echo $label; ?> <i class="bi <?php echo $sortIcon($column); ?> pages-sort-icon" aria-hidden="true"></i></a></th><?php };
$sortingEnabled = !$hasActiveFilters && $sortby === 'page_order' && $order === 'DESC';
$currentOrder = $order === 'ASC' ? 'DESC' : 'ASC';
$breadcrumbItems = array(array('label' => $this->moduleName, 'active' => TRUE));
?>
<?php $this->load->view('admin/partials/breadcrumb', array('items' => $breadcrumbItems)); ?>
<?php $this->load->view('admin/partials/crud_alert', array('module_name' => 'Web page', 'status' => $alert)); ?>

<section class="admin-records-listing" aria-labelledby="web-pages-title">
    <?php $this->load->view('admin/partials/module_header', array('title' => $listingTitle, 'description' => $this->moduleDesc, 'id' => 'web-pages-title', 'action_url' => $addUrl, 'action_label' => 'Add '.$this->moduleNameSingular)); ?>

    <div class="pages-listing-toolbar">
        <form class="pages-filter-form" id="pages-filter-form" role="search">
            <div class="pages-search-control"><label class="visually-hidden" for="search_keywords">Search web pages</label><span class="pages-search-icon" aria-hidden="true"><i class="bi bi-search"></i></span><input class="form-control" type="search" value="<?php echo $keywords !== '-' ? htmlspecialchars($keywords, ENT_QUOTES, 'UTF-8') : ''; ?>" id="search_keywords" placeholder="Search names, titles, or slugs..." autocomplete="off"></div>
            <button class="btn btn-outline-secondary pages-search-submit" type="submit"><i class="bi bi-search" aria-hidden="true"></i><span class="d-none d-sm-inline">Search</span></button>
            <div class="pages-status-control"><label class="visually-hidden" for="search_status">Filter by publishing status</label><select class="form-select select2" id="search_status" data-minimum-results-for-search="-1"><option value="-">All Statuses</option><option value="Published" <?php echo $status === 'Published' ? 'selected' : ''; ?>>Published</option><option value="Un-Published" <?php echo $status === 'Un-Published' ? 'selected' : ''; ?>>Unpublished</option></select></div>
        </form>
    </div>

    <div class="pages-bulk-actions" id="pages-bulk-actions" aria-live="polite" hidden><span class="pages-selection-count" id="pages-selection-count">0 selected</span><button id="deleteAllRecords" class="btn btn-sm btn-outline-danger" type="button" disabled><i class="bi bi-trash" aria-hidden="true"></i> Delete Selected</button></div>

    <form action="<?php echo ADMIN_URL.$this->controller; ?>/deleteall" method="post" name="multiDel" id="multiDel">
        <input type="hidden" name="parent_id" value="<?php echo (int) $parent_id; ?>">
        <?php if ($this->config->item('csrf_protection')) { ?><input type="hidden" name="<?php echo htmlspecialchars($this->security->get_csrf_token_name(), ENT_QUOTES, 'UTF-8'); ?>" value="<?php echo htmlspecialchars($this->security->get_csrf_hash(), ENT_QUOTES, 'UTF-8'); ?>"><?php } ?>
        <div class="table-responsive pages-table-responsive" tabindex="0" aria-label="Web pages table">
            <table id="table-page" class="table pages-listing-table admin-records-table"<?php if ($sortingEnabled) { ?> data-sortable-records data-sort-handle=".pages-drag-handle" data-sort-url="<?php echo base_url('manage/'.$this->controller.'/pageorder/'.(int) $parent_id); ?>" data-sort-offset="<?php echo (int) $page_numb; ?>"<?php } ?>>
                <thead><tr><?php if ($sortingEnabled) { ?><th scope="col"><span class="visually-hidden">Reorder</span></th><?php } ?><th class="pages-select-column" scope="col"><input class="form-check-input" type="checkbox" id="all-checkbox" autocomplete="off" aria-label="Select all deletable web pages"></th><?php $sortableHeading($this->pKey, 'ID', 'd-none d-md-table-cell'); $sortableHeading('page_name', 'Page'); ?><?php $sortableHeading($this->tStatus, 'Status'); $sortableHeading('page_added', 'Created On', 'd-none d-md-table-cell'); $sortableHeading('updated_at', 'Updated On', 'd-none d-md-table-cell'); ?><th scope="col">Actions</th></tr></thead>
                <tbody>
                <?php if (!empty($records)) { foreach ($records as $record) {
                    $id = (int) $record[$this->pKey];
                    $canDelete = $id > (int) $protected_page_max_id;
                    $pageName = html_entity_decode((string) $record['page_name'], ENT_QUOTES, 'UTF-8');
                    $escapedName = htmlspecialchars($pageName, ENT_QUOTES, 'UTF-8');
                    $slug = trim((string) $record['page_slug']);
                    $editUrl = base_url('manage/'.$this->controller.'/control/'.(int) $parent_id.'/'.$id);
                    $previewUrl = $id === 1 ? base_url() : base_url($slug);
                    $updatedAt = !empty($record['updated_at']) && $record['updated_at'] !== '0000-00-00 00:00:00' ? $record['updated_at'] : $record['page_updated'];
                ?>
                    <tr id="page-<?php echo $id; ?>" data-record-id="<?php echo $id; ?>">
                        <?php if ($sortingEnabled) { ?><td class="pages-drag-handle" title="Drag to reorder <?php echo $escapedName; ?>" aria-label="Drag to reorder <?php echo $escapedName; ?>"><i class="bi bi-grip-vertical" aria-hidden="true"></i></td><?php } ?>
                        <td class="pages-select-column"><?php if ($canDelete) { ?><input name="records[]" class="form-check-input cselect" value="<?php echo $id; ?>" type="checkbox" aria-label="Select <?php echo $escapedName; ?>"><?php } else { ?><i class="bi bi-lock-fill text-secondary" aria-hidden="true" title="Protected core page"></i><span class="visually-hidden">Protected core page</span><?php } ?></td>
                        <td class="d-none d-md-table-cell"><span class="pages-record-id"><?php echo $id; ?></span></td>
                        <td><div class="user-card-details"><a class="pages-name-link" href="<?php echo $editUrl; ?>"><?php echo $escapedName; ?></a></div></td>
                        <td><?php if ($canDelete) { ?><button type="button" class="changestatus pages-status-button" data-controller="<?php echo htmlspecialchars($this->controller, ENT_QUOTES, 'UTF-8'); ?>" id="statusID<?php echo $id; ?>" aria-label="Change status for <?php echo $escapedName; ?>" title="Change status" data-bs-toggle="tooltip"><?php echo htmlspecialchars((string) $record[$this->tStatus], ENT_QUOTES, 'UTF-8'); ?></button><?php } else { ?><span class="pages-status-static"><?php echo htmlspecialchars((string) $record[$this->tStatus], ENT_QUOTES, 'UTF-8'); ?></span><?php } ?></td>
                        <td class="d-none d-md-table-cell"><?php if (!empty($record['page_added']) && strtotime($record['page_added'])) { ?><time datetime="<?php echo date('c', strtotime($record['page_added'])); ?>"><?php echo date(ADMIN_DATE_FORMAT, strtotime($record['page_added'])); ?><span class="pages-cell-meta"><?php echo date(ADMIN_TIME_FORMAT, strtotime($record['page_added'])); ?></span></time><?php } else { ?>&mdash;<?php } ?></td>
                        <td class="d-none d-md-table-cell"><?php if (!empty($updatedAt) && strtotime($updatedAt)) { ?><time datetime="<?php echo date('c', strtotime($updatedAt)); ?>"><?php echo date(ADMIN_DATE_FORMAT, strtotime($updatedAt)); ?><span class="pages-cell-meta"><?php echo date(ADMIN_TIME_FORMAT, strtotime($updatedAt)); ?></span></time><?php } else { ?>&mdash;<?php } ?></td>
                        <td class="pages-actions-cell"><?php if (!empty($canUpdateSections) && in_array($id, $sectionPageIds, TRUE)) { ?><a class="admin-action-icon font16" href="<?php echo base_url('manage/web-pages/'.$id.'/sections'); ?>" aria-label="Edit sections for <?php echo $escapedName; ?>" title="Edit Sections" data-bs-toggle="tooltip"><i class="bi bi-layout-text-window-reverse" aria-hidden="true"></i></a><?php } ?><a target="_blank" rel="noopener" class="admin-action-icon font16" href="<?php echo htmlspecialchars($previewUrl, ENT_QUOTES, 'UTF-8'); ?>" aria-label="Preview <?php echo $escapedName; ?>" title="Preview" data-bs-toggle="tooltip"><i class="bi bi-eye" aria-hidden="true"></i></a><a class="admin-action-icon font16 dupitem" id="copyID<?php echo $id; ?>" data-rec-name="<?php echo $escapedName; ?>" data-controller="<?php echo htmlspecialchars($this->controller, ENT_QUOTES, 'UTF-8'); ?>" href="javascript:void(0);" aria-label="Duplicate <?php echo $escapedName; ?>" title="Duplicate" data-bs-toggle="tooltip"><i class="bi bi-copy" aria-hidden="true"></i></a><a class="admin-action-icon font16" href="<?php echo $editUrl; ?>" aria-label="Edit <?php echo $escapedName; ?>" title="Edit" data-bs-toggle="tooltip"><i class="bi bi-pencil" aria-hidden="true"></i></a><?php if ($canDelete) { ?><a class="admin-action-icon font16 delitem" href="javascript:void(0);" data-controller="<?php echo htmlspecialchars($this->controller, ENT_QUOTES, 'UTF-8'); ?>" data-record-name="<?php echo $escapedName; ?>" id="recordID<?php echo $id; ?>" aria-label="Delete <?php echo $escapedName; ?>" title="Delete" data-bs-toggle="tooltip"><i class="bi bi-trash" aria-hidden="true"></i></a><?php } ?></td>
                    </tr>
                <?php }} else { ?><tr><td class="pages-empty-state" colspan="<?php echo $sortingEnabled ? 8 : 7; ?>"><strong><?php echo $hasActiveFilters ? 'No web pages match your filters.' : 'No web pages found.'; ?></strong><span><?php echo $hasActiveFilters ? 'Try changing your search or publishing status.' : 'Add a web page to get started.'; ?></span></td></tr><?php } ?>
                </tbody>
            </table>
        </div>
    </form>
    <?php $this->load->view('admin/partials/table_listing_footer', array('total_rows' => $total_rows, 'per_page' => $per_page, 'selected_per_page' => $this->per_page, 'page_offset' => $page_numb, 'pagination' => isset($paginate) ? $paginate : '', 'pagination_label' => 'Web page pagination')); ?>
</section>

<script>
jQuery(function($) {
    var $form = $('#pages-filter-form'), $search = $('#search_keywords'), $status = $('#search_status');
    var $rows = $('#multiDel .cselect'), $all = $('#all-checkbox'), $bulk = $('#pages-bulk-actions'), $count = $('#pages-selection-count'), $delete = $('#deleteAllRecords');
    var filterUrl = <?php echo json_encode(base_url('manage/'.$this->controller.'/index/'.(int) $parent_id.'/'.$sortby.'/'.$currentOrder)); ?>;
    function applyFilters() { var keyword = $.trim($search.val()); var encoded = keyword === '' ? '-' : encodeURIComponent(keyword).replace(/%20/g, '+'); window.location = filterUrl+'/'+$status.val()+'/'+encoded; }
    function updateSelection() { var selected = $rows.filter(':checked').length; $count.text(selected+' selected'); $delete.prop('disabled', selected === 0); $bulk.prop('hidden', selected === 0); $all.prop('checked', $rows.length > 0 && selected === $rows.length).prop('indeterminate', selected > 0 && selected < $rows.length).prop('disabled', $rows.length === 0); }
    $form.on('submit', function(event) { event.preventDefault(); applyFilters(); }); $status.on('change', applyFilters); $rows.on('change', updateSelection); $all.on('change', function() { window.setTimeout(updateSelection, 0); }); updateSelection();
});
</script>
