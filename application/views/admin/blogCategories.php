<?php
$hasFilters = $keywords !== '-' || $status !== '-';
$currentOrder = $order === 'ASC' ? 'DESC' : 'ASC';
$sortUrl = function ($column) use ($order, $status, $keywords, $page_numb) { return base_url('manage/blog-categories/index/'.$column.'/'.$order.'/'.$status.'/'.urlencode($keywords).'/'.(int) $page_numb); };
$heading = function ($column, $label, $class = '') use ($sortUrl, $sortby, $order) { $active = $sortby === $column; ?><th scope="col"<?php if ($class !== '') { ?> class="<?php echo $class; ?>"<?php } ?><?php if ($active) { ?> aria-sort="<?php echo $order === 'DESC' ? 'ascending' : 'descending'; ?>"<?php } ?>><a class="pages-sort-link" href="<?php echo $sortUrl($column); ?>"><?php echo $label; ?> <i class="bi <?php echo $active ? ($order === 'DESC' ? 'bi-arrow-up' : 'bi-arrow-down') : 'bi-arrow-down-up'; ?> pages-sort-icon" aria-hidden="true"></i></a></th><?php };
$sortingEnabled = !$hasFilters && $sortby === 'cat_order' && $order === 'DESC';
?>
<?php $this->load->view('admin/partials/breadcrumb', array('items' => array(array('label' => 'Blogs', 'url' => base_url('manage/blogs')), array('label' => $this->moduleName, 'active' => TRUE)))); ?>
<?php $this->load->view('admin/partials/crud_alert', array('module_name' => 'Blog category', 'status' => $alert)); ?>
<section class="admin-records-listing" aria-labelledby="blog-categories-title" data-translation-poll data-module="blog_categories" data-status-url="<?php echo base_url('manage/translations/statuses'); ?>" data-poll-seconds="4">
    <?php $this->load->view('admin/partials/module_header', array('title' => $this->moduleName, 'description' => $this->moduleDesc, 'id' => 'blog-categories-title', 'action_url' => base_url('manage/blog-categories/control'), 'action_label' => 'Add '.$this->moduleNameSingular)); ?>
    <div class="pages-listing-toolbar">
        <form class="pages-filter-form" id="categories-filter-form" role="search">
            <div class="pages-search-control"><label class="visually-hidden" for="search_keywords">Search blog categories</label><span class="pages-search-icon"><i class="bi bi-search"></i></span><input class="form-control" type="search" id="search_keywords" value="<?php echo $keywords !== '-' ? htmlspecialchars($keywords, ENT_QUOTES, 'UTF-8') : ''; ?>" placeholder="Search names or slugs..."></div>
            <button class="btn btn-outline-secondary pages-search-submit" type="submit"><i class="bi bi-search"></i><span class="d-none d-sm-inline">Search</span></button>
            <div class="pages-status-control"><label class="visually-hidden" for="search_status">Filter by status</label><select class="form-select select2" id="search_status" data-minimum-results-for-search="-1"><option value="-">All Statuses</option><option value="Enable"<?php echo $status === 'Enable' ? ' selected' : ''; ?>>Enabled</option><option value="Disable"<?php echo $status === 'Disable' ? ' selected' : ''; ?>>Disabled</option></select></div>
            <a class="btn btn-outline-secondary" href="<?php echo base_url('manage/blogs'); ?>">Back to Blogs</a>
        </form>
    </div>
    <div class="pages-bulk-actions" id="category-bulk-actions" aria-live="polite" hidden><span class="pages-selection-count" id="category-selection-count">0 selected</span><button id="deleteAllRecords" class="btn btn-sm btn-outline-danger" type="button" disabled><i class="bi bi-trash"></i> Delete Selected</button></div>
    <form action="<?php echo ADMIN_URL; ?>blog-categories/deleteall" method="post" id="multiDel">
        <?php if ($this->config->item('csrf_protection')) { ?><input type="hidden" name="<?php echo htmlspecialchars($this->security->get_csrf_token_name(), ENT_QUOTES, 'UTF-8'); ?>" value="<?php echo htmlspecialchars($this->security->get_csrf_hash(), ENT_QUOTES, 'UTF-8'); ?>"><?php } ?>
        <div class="table-responsive pages-table-responsive" tabindex="0" aria-label="Blog categories table">
            <table id="table-blog-categories" class="table pages-listing-table admin-records-table"<?php if ($sortingEnabled) { ?> data-sortable-records data-sort-handle=".pages-drag-handle" data-sort-url="<?php echo base_url('manage/blog-categories/categoryorder'); ?>" data-sort-offset="<?php echo (int) $page_numb; ?>"<?php } ?>>
                <thead><tr><?php if ($sortingEnabled) { ?><th scope="col"><span class="visually-hidden">Reorder</span></th><?php } ?><th class="pages-select-column" scope="col"><input class="form-check-input" type="checkbox" id="all-checkbox" aria-label="Select all categories"></th><?php $heading('cat_id', 'ID', 'd-none d-md-table-cell'); $heading('cat_name', 'Name'); ?><th scope="col">Translation</th><?php $heading('cat_status', 'Status'); $heading('cat_added', 'Created On', 'd-none d-md-table-cell'); $heading('cat_updated', 'Updated On', 'd-none d-md-table-cell'); ?><th scope="col">Actions</th></tr></thead>
                <tbody>
                <?php if ($records) foreach ($records as $r) {
                    $id = (int) $r['cat_id'];
                    $name = html_entity_decode((string) $r['cat_name'], ENT_QUOTES, 'UTF-8');
                    $esc = htmlspecialchars($name, ENT_QUOTES, 'UTF-8');
                    $edit = base_url('manage/blog-categories/control/'.$id);
                    $viewUrl = base_url('en/'.BLOG_CATEGORY_URI.$r['cat_slug']);
                ?>
                    <tr id="<?php echo $this->controller . '-' . $id; ?>" data-record-id="<?php echo $id; ?>">
                        <?php if ($sortingEnabled) { ?><td class="pages-drag-handle" aria-label="Drag to reorder <?php echo $esc; ?>"><i class="bi bi-grip-vertical"></i></td><?php } ?>
                        <td class="pages-select-column"><input name="records[]" class="form-check-input cselect" value="<?php echo $id; ?>" type="checkbox" aria-label="Select <?php echo $esc; ?>"></td>
                        <td class="d-none d-md-table-cell"><span class="pages-record-id"><?php echo $id; ?></span></td>
                        <td><div class="user-card-details"><a class="pages-name-link" href="<?php echo $edit; ?>"><?php echo $esc; ?></a></div></td>
                        <td><?php $this->load->view('admin/partials/translation_status_badge', array('translation_status' => isset($translation_statuses[(string) $id]) ? $translation_statuses[(string) $id] : 'MISSING', 'translation_entity_id' => $id)); ?></td>
                        <td><button type="button" class="changestatus pages-status-button" data-controller="blog-categories" id="statusID<?php echo $id; ?>" aria-label="Change status for <?php echo $esc; ?>"><?php echo htmlspecialchars($r['cat_status'], ENT_QUOTES, 'UTF-8'); ?></button></td>
                        <td class="d-none d-md-table-cell"><?php if (strtotime($r['cat_added'])) { ?><time datetime="<?php echo date('c', strtotime($r['cat_added'])); ?>"><?php echo date(ADMIN_DATE_FORMAT, strtotime($r['cat_added'])); ?><span class="pages-cell-meta"><?php echo date(ADMIN_TIME_FORMAT, strtotime($r['cat_added'])); ?></span></time><?php } else { ?>&mdash;<?php } ?></td>
                        <td class="d-none d-md-table-cell"><?php if (strtotime($r['cat_updated'])) { ?><time datetime="<?php echo date('c', strtotime($r['cat_updated'])); ?>"><?php echo date(ADMIN_DATE_FORMAT, strtotime($r['cat_updated'])); ?><span class="pages-cell-meta"><?php echo date(ADMIN_TIME_FORMAT, strtotime($r['cat_updated'])); ?></span></time><?php } else { ?>&mdash;<?php } ?></td>
                        <td class="pages-actions-cell"><a class="admin-action-icon font16" target="_blank" rel="noopener" href="<?php echo htmlspecialchars($viewUrl, ENT_QUOTES, 'UTF-8'); ?>" aria-label="View <?php echo $esc; ?>" title="View"><i class="bi bi-eye"></i></a><a class="admin-action-icon font16 dupitem" id="copyID<?php echo $id; ?>" data-rec-name="<?php echo $esc; ?>" data-controller="blog-categories" href="javascript:void(0)" aria-label="Duplicate <?php echo $esc; ?>" title="Duplicate"><i class="bi bi-copy"></i></a><a class="admin-action-icon font16" href="<?php echo $edit; ?>" aria-label="Edit <?php echo $esc; ?>" title="Edit"><i class="bi bi-pencil"></i></a><a class="admin-action-icon font16 delitem" href="javascript:void(0)" data-controller="blog-categories" data-record-name="<?php echo $esc; ?>" id="recordID<?php echo $id; ?>" aria-label="Delete <?php echo $esc; ?>" title="Delete"><i class="bi bi-trash"></i></a></td>
                    </tr>
                <?php } else { ?><tr><td class="pages-empty-state" colspan="<?php echo $sortingEnabled ? 9 : 8; ?>"><strong><?php echo $hasFilters ? 'No blog categories match your filters.' : 'No blog categories found.'; ?></strong><span><?php echo $hasFilters ? 'Try changing your search or status.' : 'Add a category to get started.'; ?></span></td></tr><?php } ?>
                </tbody>
            </table>
        </div>
    </form>
    <?php $this->load->view('admin/partials/table_listing_footer', array('total_rows' => $total_rows, 'per_page' => $per_page, 'selected_per_page' => $this->per_page, 'page_offset' => $page_numb, 'pagination' => $paginate, 'pagination_label' => 'Blog category pagination')); ?>
</section>
<script>jQuery(function($){var f=$('#categories-filter-form'),s=$('#search_keywords'),st=$('#search_status'),rows=$('#multiDel .cselect'),all=$('#all-checkbox'),bulk=$('#category-bulk-actions'),count=$('#category-selection-count'),del=$('#deleteAllRecords'),url=<?php echo json_encode(base_url('manage/blog-categories/index/'.$sortby.'/'.$currentOrder)); ?>;function filter(){var q=$.trim(s.val());window.location=url+'/'+st.val()+'/'+(q===''?'-':encodeURIComponent(q).replace(/%20/g,'+'));}function selection(){var n=rows.filter(':checked').length;count.text(n+' selected');del.prop('disabled',!n);bulk.prop('hidden',!n);all.prop('checked',rows.length>0&&n===rows.length).prop('indeterminate',n>0&&n<rows.length).prop('disabled',!rows.length);}f.on('submit',function(e){e.preventDefault();filter();});st.on('change',filter);rows.on('change',selection);all.on('change',function(){setTimeout(selection,0);});selection();});</script>
