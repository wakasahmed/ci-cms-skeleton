<?php
$hasActiveFilters = ($keywords !== '-');
$sortUrl = function ($column) use ($order, $keywords, $page_numb) {
    return base_url('manage/'.$this->controller.'/index/'.$column.'/'.$order.'/'.rawurlencode($keywords).'/'.$page_numb);
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
$escape = function ($value) { return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8'); };
?>
<?php $this->load->view('admin/partials/breadcrumb', array('items' => array(array('label' => $this->moduleName, 'active' => TRUE)))); ?>

<?php $this->load->view('admin/partials/crud_alert', array(
    'module_name' => 'Email template', 'status' => $alert,
    'status_messages' => array(
        'blocked' => array('danger', 'Blocked!', 'This email template is referenced elsewhere and cannot be deleted.'),
    ),
)); ?>

<section class="admin-records-listing" aria-labelledby="email-templates-title" data-translation-poll data-module="email_templates" data-status-url="<?php echo base_url('manage/translations/statuses'); ?>" data-poll-seconds="4">
    <?php $this->load->view('admin/partials/module_header', array('title' => $this->moduleName, 'description' => $this->moduleDesc, 'id' => 'email-templates-title')); ?>

    <div class="pages-listing-toolbar">
        <form class="pages-filter-form" id="email-templates-filter-form" role="search">
            <div class="pages-search-control"><label class="visually-hidden" for="search_keywords">Search email templates</label><span class="pages-search-icon" aria-hidden="true"><i class="bi bi-search"></i></span><input class="form-control" type="search" value="<?php echo $keywords !== '-' ? $escape($keywords) : ''; ?>" id="search_keywords" placeholder="Search name, subject, or heading..." autocomplete="off"></div>
            <button class="btn btn-outline-secondary pages-search-submit" type="submit"><i class="bi bi-search" aria-hidden="true"></i><span class="d-none d-sm-inline">Search</span></button>
        </form>
    </div>

    <form name="multiDel" id="multiDel">
        <div class="table-responsive pages-table-responsive" tabindex="0" aria-label="Email templates table">
            <table id="table-<?php echo $this->controller; ?>" class="table pages-listing-table admin-records-table">
                <thead>
                    <tr>
                        <th class="pages-select-column" scope="col"><span class="visually-hidden">Protected</span></th>
                        <th scope="col" class="d-none d-md-table-cell"<?php if ($sortAria($this->pKey) !== '') { ?> aria-sort="<?php echo $sortAria($this->pKey); ?>"<?php } ?>><a class="pages-sort-link" href="<?php echo $sortUrl($this->pKey); ?>">ID <i class="bi <?php echo $sortIcon($this->pKey); ?> pages-sort-icon" aria-hidden="true"></i></a></th>
                        <th scope="col"<?php if ($sortAria('name') !== '') { ?> aria-sort="<?php echo $sortAria('name'); ?>"<?php } ?>><a class="pages-sort-link" href="<?php echo $sortUrl('name'); ?>">Name <i class="bi <?php echo $sortIcon('name'); ?> pages-sort-icon" aria-hidden="true"></i></a></th>
                        <th scope="col"<?php if ($sortAria('subject') !== '') { ?> aria-sort="<?php echo $sortAria('subject'); ?>"<?php } ?>><a class="pages-sort-link" href="<?php echo $sortUrl('subject'); ?>">Subject <i class="bi <?php echo $sortIcon('subject'); ?> pages-sort-icon" aria-hidden="true"></i></a></th>
                        <th scope="col" class="d-none d-md-table-cell"<?php if ($sortAria('heading') !== '') { ?> aria-sort="<?php echo $sortAria('heading'); ?>"<?php } ?>><a class="pages-sort-link" href="<?php echo $sortUrl('heading'); ?>">Heading <i class="bi <?php echo $sortIcon('heading'); ?> pages-sort-icon" aria-hidden="true"></i></a></th>
                        <th scope="col">Translation</th>
                        <th scope="col">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (!empty($records)) {
                        foreach ($records as $record) {
                            $id = (int) $record[$this->pKey];
                            $name = trim((string) $record['name']);
                            $escapedName = $escape($name);
                            $editUrl = base_url('manage/'.$this->controller.'/control/edit/'.$id);
                    ?>
                            <tr id="<?php echo $this->controller.'-'.$id; ?>" data-record-id="<?php echo $id; ?>">
                                <td class="pages-select-column"><i class="bi bi-lock-fill text-secondary" aria-hidden="true" title="Protected email template"></i><span class="visually-hidden">Protected email template</span></td>
                                <td class="d-none d-md-table-cell"><span class="pages-record-id"><?php echo $id; ?></span></td>
                                <td><a class="pages-name-link" href="<?php echo $editUrl; ?>"><?php echo $escapedName; ?></a></td>
                                <td><?php echo $escape($record['subject']); ?></td>
                                <td class="d-none d-md-table-cell"><?php echo $escape((string) $record['heading']); ?></td>
                                <td><?php $this->load->view('admin/partials/translation_status_badge', array('translation_status' => isset($translation_statuses[(string) $id]) ? $translation_statuses[(string) $id] : 'MISSING', 'translation_entity_id' => $id)); ?></td>
                                <td class="pages-actions-cell">
                                    <a class="admin-action-icon font16" href="<?php echo $editUrl; ?>" aria-label="Edit <?php echo $escapedName; ?>" title="Edit" data-bs-toggle="tooltip"><i class="bi bi-pencil" aria-hidden="true"></i></a>
                                </td>
                            </tr>
                        <?php }
                    } else { ?><tr>
                            <td class="pages-empty-state" colspan="6"><strong><?php echo $hasActiveFilters ? 'No email templates match your search.' : 'No email templates found.'; ?></strong><span><?php echo $hasActiveFilters ? 'Try changing or clearing the search.' : 'No email templates have been created yet.'; ?></span></td>
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
        'pagination_label' => 'Email template pagination',
    )); ?>
</section>

<script>
    jQuery(function($) {
        var $form = $('#email-templates-filter-form'),
            $search = $('#search_keywords');
        var filterUrl = <?php echo json_encode(base_url('manage/'.$this->controller.'/index/'.$sortby.'/'.(($order === 'ASC') ? 'DESC' : 'ASC'))); ?>;

        function applyFilters() {
            var keyword = $.trim($search.val());
            window.location = filterUrl + '/' + (keyword === '' ? '-' : encodeURIComponent(keyword));
        }

        $form.on('submit', function(event) {
            event.preventDefault();
            applyFilters();
        });
    });
</script>
