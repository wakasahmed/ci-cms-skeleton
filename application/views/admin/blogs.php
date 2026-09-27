<?php
$hasFilters = $keywords !== '-' || $status !== '-';
$currentOrder = $order === 'ASC' ? 'DESC' : 'ASC';
$sortUrl = function ($c) use ($order, $status, $keywords, $page_numb) { return base_url('manage/blogs/index/'.$c.'/'.$order.'/'.$status.'/'.urlencode($keywords).'/'.(int) $page_numb); };
$heading = function ($c, $label, $class = '') use ($sortUrl, $sortby, $order) { $active = $sortby === $c; ?><th scope="col"<?php if ($class !== '') { ?> class="<?php echo $class; ?>"<?php } ?><?php if ($active) { ?> aria-sort="<?php echo $order === 'DESC' ? 'ascending' : 'descending'; ?>"<?php } ?>><a class="pages-sort-link" href="<?php echo $sortUrl($c); ?>"><?php echo $label; ?> <i class="bi <?php echo $active ? ($order === 'DESC' ? 'bi-arrow-up' : 'bi-arrow-down') : 'bi-arrow-down-up'; ?> pages-sort-icon"></i></a></th><?php };
?>
<?php $this->load->view('admin/partials/breadcrumb', array('items' => array(array('label' => $this->moduleName, 'active' => TRUE)))); $this->load->view('admin/partials/crud_alert', array('module_name' => 'Blog', 'status' => $alert)); ?>
<section class="admin-records-listing" aria-labelledby="blogs-title" data-translation-poll data-module="blogs" data-status-url="<?php echo base_url('manage/translations/statuses'); ?>" data-poll-seconds="4">
    <?php $this->load->view('admin/partials/module_header', array('title' => $this->moduleName, 'description' => $this->moduleDesc, 'id' => 'blogs-title', 'action_url' => base_url('manage/blogs/control'), 'action_label' => 'Add '.$this->moduleNameSingular)); ?>
    <div class="pages-listing-toolbar">
        <form class="pages-filter-form" id="blogs-filter-form" role="search">
            <div class="pages-search-control"><label class="visually-hidden" for="search_keywords">Search blogs</label><span class="pages-search-icon"><i class="bi bi-search"></i></span><input class="form-control" type="search" id="search_keywords" value="<?php echo $keywords !== '-' ? htmlspecialchars($keywords, ENT_QUOTES, 'UTF-8') : ''; ?>" placeholder="Search titles, authors, or slugs..."></div>
            <button class="btn btn-outline-secondary pages-search-submit" type="submit"><i class="bi bi-search"></i><span class="d-none d-sm-inline">Search</span></button>
            <div class="pages-status-control"><label class="visually-hidden" for="search_status">Filter by status</label><select class="form-select select2" id="search_status" data-minimum-results-for-search="-1"><option value="-">All Statuses</option><option value="Published"<?php echo $status === 'Published' ? ' selected' : ''; ?>>Published</option><option value="Un-Published"<?php echo $status === 'Un-Published' ? ' selected' : ''; ?>>Unpublished</option></select></div>
            <a class="btn btn-outline-secondary" href="<?php echo base_url('manage/blog-categories'); ?>">Blog Categories</a>
        </form>
    </div>
    <div class="pages-bulk-actions" id="blogs-bulk-actions" hidden><span class="pages-selection-count" id="blogs-selection-count">0 selected</span><button id="deleteAllRecords" class="btn btn-sm btn-outline-danger" type="button" disabled><i class="bi bi-trash"></i> Delete Selected</button></div>
    <form action="<?php echo ADMIN_URL; ?>blogs/deleteall" method="post" id="multiDel">
        <?php if ($this->config->item('csrf_protection')) { ?><input type="hidden" name="<?php echo htmlspecialchars($this->security->get_csrf_token_name(), ENT_QUOTES, 'UTF-8'); ?>" value="<?php echo htmlspecialchars($this->security->get_csrf_hash(), ENT_QUOTES, 'UTF-8'); ?>"><?php } ?>
        <div class="table-responsive pages-table-responsive" tabindex="0" aria-label="Blogs table">
            <table class="table pages-listing-table admin-records-table">
                <thead><tr><th class="pages-select-column"><input class="form-check-input" type="checkbox" id="all-checkbox" aria-label="Select all blogs"></th><?php $heading('blog_id', 'ID', 'd-none d-md-table-cell'); ?><th scope="col">Image</th><?php $heading('blog_name', 'Blog'); ?><th scope="col">Translation</th><?php $heading('blog_status', 'Status'); $heading('blog_added', 'Created On', 'd-none d-md-table-cell'); $heading('blog_updated', 'Updated On', 'd-none d-md-table-cell'); ?><th scope="col">Actions</th></tr></thead>
                <tbody>
                <?php if ($records) foreach ($records as $r) {
                    $id = (int) $r['blog_id'];
                    $name = html_entity_decode((string) $r['blog_name'], ENT_QUOTES, 'UTF-8');
                    $esc = htmlspecialchars($name, ENT_QUOTES, 'UTF-8');
                    $edit = base_url('manage/blogs/control/'.$id);
                    $viewUrl = base_url('en/'.BLOG_URI.$r['blog_slug']);
                ?>
                    <tr>
                        <td class="pages-select-column"><input name="records[]" class="form-check-input cselect" value="<?php echo $id; ?>" type="checkbox" aria-label="Select <?php echo $esc; ?>"></td>
                        <td class="d-none d-md-table-cell"><span class="pages-record-id"><?php echo $id; ?></span></td>
                        <td><?php echo image_thumb('./assets/frontend/images/blogs/'.(string) $r['blog_image'], 0, 64, 'webp', 'square', TRUE, array('alt' => $name, 'placeholder' => TRUE)); ?></td>
                        <td><div class="user-card-details"><a class="pages-name-link" href="<?php echo $edit; ?>"><?php echo $esc; ?></a><span class="pages-cell-meta"><?php echo $r['category_names'] ? htmlspecialchars($r['category_names'], ENT_QUOTES, 'UTF-8') : '<span class="text-muted">Uncategorized</span>'; ?><?php echo $r['blog_featured'] === 'Yes' ? ' · Featured' : ''; ?></span></div></td>
                        <td><?php $this->load->view('admin/partials/translation_status_badge', array('translation_status' => isset($translation_statuses[(string) $id]) ? $translation_statuses[(string) $id] : 'MISSING', 'translation_entity_id' => $id)); ?></td>
                        <td><button type="button" class="changestatus pages-status-button" data-controller="blogs" id="statusID<?php echo $id; ?>" aria-label="Change status for <?php echo $esc; ?>"><?php echo htmlspecialchars($r['blog_status'], ENT_QUOTES, 'UTF-8'); ?></button></td>
                        <td class="d-none d-md-table-cell"><?php if (strtotime($r['blog_added'])) { ?><time datetime="<?php echo date('c', strtotime($r['blog_added'])); ?>"><?php echo date(ADMIN_DATE_FORMAT, strtotime($r['blog_added'])); ?><span class="pages-cell-meta"><?php echo date(ADMIN_TIME_FORMAT, strtotime($r['blog_added'])); ?></span></time><?php } else { ?>&mdash;<?php } ?></td>
                        <td class="d-none d-md-table-cell"><?php if (strtotime($r['blog_updated'])) { ?><time datetime="<?php echo date('c', strtotime($r['blog_updated'])); ?>"><?php echo date(ADMIN_DATE_FORMAT, strtotime($r['blog_updated'])); ?><span class="pages-cell-meta"><?php echo date(ADMIN_TIME_FORMAT, strtotime($r['blog_updated'])); ?></span></time><?php } else { ?>&mdash;<?php } ?></td>
                        <td class="pages-actions-cell"><a class="admin-action-icon font16" target="_blank" rel="noopener" href="<?php echo htmlspecialchars($viewUrl, ENT_QUOTES, 'UTF-8'); ?>" aria-label="Preview <?php echo $esc; ?>" title="Preview"><i class="bi bi-eye"></i></a><a class="admin-action-icon font16 dupitem" id="copyID<?php echo $id; ?>" data-rec-name="<?php echo $esc; ?>" data-controller="blogs" href="javascript:void(0)" aria-label="Duplicate <?php echo $esc; ?>" title="Duplicate"><i class="bi bi-copy"></i></a><a class="admin-action-icon font16" href="<?php echo $edit; ?>" aria-label="Edit <?php echo $esc; ?>" title="Edit"><i class="bi bi-pencil"></i></a><a class="admin-action-icon font16 delitem" href="javascript:void(0)" data-controller="blogs" data-record-name="<?php echo $esc; ?>" id="recordID<?php echo $id; ?>" aria-label="Delete <?php echo $esc; ?>" title="Delete"><i class="bi bi-trash"></i></a></td>
                    </tr>
                <?php } else { ?><tr><td class="pages-empty-state" colspan="9"><strong><?php echo $hasFilters ? 'No blogs match your filters.' : 'No blogs found.'; ?></strong><span><?php echo $hasFilters ? 'Try changing your search or status.' : 'Add a blog to get started.'; ?></span></td></tr><?php } ?>
                </tbody>
            </table>
        </div>
    </form>
    <?php $this->load->view('admin/partials/table_listing_footer', array('total_rows' => $total_rows, 'per_page' => $per_page, 'selected_per_page' => $this->per_page, 'page_offset' => $page_numb, 'pagination' => $paginate, 'pagination_label' => 'Blog pagination')); ?>
</section>
<script>jQuery(function($){var f=$('#blogs-filter-form'),s=$('#search_keywords'),st=$('#search_status'),rows=$('#multiDel .cselect'),all=$('#all-checkbox'),bulk=$('#blogs-bulk-actions'),count=$('#blogs-selection-count'),del=$('#deleteAllRecords'),url=<?php echo json_encode(base_url('manage/blogs/index/'.$sortby.'/'.$currentOrder)); ?>;function filter(){var q=$.trim(s.val());window.location=url+'/'+st.val()+'/'+(q===''?'-':encodeURIComponent(q).replace(/%20/g,'+'));}function selection(){var n=rows.filter(':checked').length;count.text(n+' selected');del.prop('disabled',!n);bulk.prop('hidden',!n);all.prop('checked',rows.length>0&&n===rows.length).prop('indeterminate',n>0&&n<rows.length).prop('disabled',!rows.length);}f.on('submit',function(e){e.preventDefault();filter();});st.on('change',filter);rows.on('change',selection);all.on('change',function(){setTimeout(selection,0);});selection();});</script>
