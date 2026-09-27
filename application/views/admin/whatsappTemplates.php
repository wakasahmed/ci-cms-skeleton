<?php
$hasActiveFilters = ($keywords !== '-' || $status !== '-');
$escape = function ($value) {
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
};
$sortUrl = function ($column) use ($order, $status, $keywords, $page_numb) {
    return base_url(
        'manage/' . $this->controller . '/index/' . $column . '/' . $order . '/' . $status . '/'
        . rawurlencode($keywords) . '/' . $page_numb
    );
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
$sortableHeader = function ($column, $label, $class = '') use ($sortUrl, $sortIcon, $sortAria) {
    $aria = $sortAria($column);
    ?>
    <th scope="col"<?php echo $class !== '' ? ' class="' . $class . '"' : ''; ?><?php echo $aria !== '' ? ' aria-sort="' . $aria . '"' : ''; ?>>
        <a class="pages-sort-link" href="<?php echo $sortUrl($column); ?>">
            <?php echo $label; ?>
            <i class="bi <?php echo $sortIcon($column); ?> pages-sort-icon" aria-hidden="true"></i>
        </a>
    </th>
    <?php
};
$service = $this->whatsapp_template_service;
?>
<?php $this->load->view('admin/partials/breadcrumb', array(
    'items' => array(
        array('label' => $this->moduleName, 'active' => true),
    ),
)); ?>

<?php $this->load->view('admin/partials/crud_alert', array(
    'module_name' => 'WhatsApp template',
    'status' => $alert,
    'status_messages' => array(
        'editsuccess' => array('success', 'Success!', 'WhatsApp template was updated. Any changed message was submitted to Meta for review.'),
    ),
)); ?>

<?php if (!empty($meta_error)) { ?>
    <div class="row alertrow">
        <div class="col-md-12">
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <?php echo $escape($meta_error); ?>
                <button type="button" class="btn-close alertBox crud-alert-close" data-bs-dismiss="alert" aria-label="Dismiss notification"></button>
            </div>
        </div>
    </div>
<?php } ?>

<section class="admin-records-listing" aria-labelledby="whatsapp-templates-title">
    <?php $this->load->view('admin/partials/module_header', array(
        'title' => $this->moduleName,
        'description' => $this->moduleDesc,
        'id' => 'whatsapp-templates-title',
    )); ?>

    <div class="pages-listing-toolbar">
        <form class="pages-filter-form" id="whatsapp-templates-filter-form" role="search">
            <div class="pages-search-control">
                <label class="visually-hidden" for="search_keywords">Search WhatsApp templates</label>
                <span class="pages-search-icon" aria-hidden="true"><i class="bi bi-search"></i></span>
                <input
                    class="form-control"
                    type="search"
                    value="<?php echo $keywords !== '-' ? $escape($keywords) : ''; ?>"
                    id="search_keywords"
                    placeholder="Search title or Meta name..."
                    autocomplete="off"
                >
            </div>
            <button class="btn btn-outline-secondary pages-search-submit" type="submit">
                <i class="bi bi-search" aria-hidden="true"></i>
                <span class="d-none d-sm-inline">Search</span>
            </button>
            <div class="pages-status-control">
                <label class="visually-hidden" for="search_status">Filter by status</label>
                <select class="form-select select2" id="search_status" data-minimum-results-for-search="-1">
                    <option value="-">All Statuses</option>
                    <option value="Enable" <?php echo $status === 'Enable' ? 'selected' : ''; ?>>Enabled</option>
                    <option value="Disable" <?php echo $status === 'Disable' ? 'selected' : ''; ?>>Disabled</option>
                </select>
            </div>
            <?php if ($hasActiveFilters) { ?>
                <a class="btn btn-link" href="<?php echo base_url('manage/' . $this->controller); ?>">Clear filters</a>
            <?php } ?>
        </form>
    </div>

    <div class="table-responsive pages-table-responsive" tabindex="0" aria-label="WhatsApp templates table">
        <table id="table-<?php echo $this->controller; ?>" class="table pages-listing-table admin-records-table">
            <thead>
                <tr>
                    <th class="pages-select-column" scope="col"><span class="visually-hidden">Protected</span></th>
                    <?php $sortableHeader($this->pKey, 'ID', 'd-none d-md-table-cell'); ?>
                    <?php $sortableHeader($this->colPrefix . 'title', 'Template'); ?>
                    <?php $sortableHeader($this->colPrefix . 'category', 'Category', 'd-none d-lg-table-cell'); ?>
                    <th scope="col">English</th>
                    <th scope="col">Arabic</th>
                    <?php $sortableHeader($this->tStatus, 'Status'); ?>
                    <?php $sortableHeader($this->colPrefix . 'updated', 'Updated On', 'd-none d-md-table-cell'); ?>
                    <th scope="col">Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php if (!empty($records)) { ?>
                    <?php foreach ($records as $record) { ?>
                        <?php
                        $id = (int) $record[$this->pKey];
                        $escapedTitle = $escape($record[$this->colPrefix . 'title']);
                        $editUrl = base_url('manage/' . $this->controller . '/control/edit/' . $id);
                        $updatedAt = $record[$this->colPrefix . 'updated'];
                        ?>
                        <tr id="<?php echo $this->controller . '-' . $id; ?>" data-record-id="<?php echo $id; ?>">
                            <td class="pages-select-column">
                                <i class="bi bi-lock-fill text-secondary" aria-hidden="true" title="Protected WhatsApp template"></i>
                                <span class="visually-hidden">Protected WhatsApp template</span>
                            </td>
                            <td class="d-none d-md-table-cell"><span class="pages-record-id"><?php echo $id; ?></span></td>
                            <td>
                                <a class="pages-name-link" href="<?php echo $editUrl; ?>"><?php echo $escapedTitle; ?></a>
                                <span class="pages-cell-meta"><code><?php echo $escape($record[$this->colPrefix . 'name']); ?></code></span>
                            </td>
                            <td class="d-none d-lg-table-cell"><?php echo $escape(ucfirst(strtolower($record[$this->colPrefix . 'category']))); ?></td>
                            <?php foreach (array('en', 'ar') as $language) { ?>
                                <td>
                                    <?php if ($language === 'ar' && trim((string) $record[$this->colPrefix . 'body_ar']) === '') { ?>
                                        <span class="text-secondary">Not used</span>
                                    <?php } else { ?>
                                        <?php $statusMeta = $service->statusMeta($record[$this->colPrefix . $language . '_meta_status']); ?>
                                        <span class="translation-status-badge <?php echo $escape($statusMeta['class']); ?>">
                                            <?php echo $escape($statusMeta['label']); ?>
                                        </span>
                                        <?php if ($service->hasUnsubmittedChanges($record, $language) && $record[$this->colPrefix . $language . '_meta_id'] !== '') { ?>
                                            <span class="pages-cell-meta">Changes not submitted</span>
                                        <?php } ?>
                                    <?php } ?>
                                </td>
                            <?php } ?>
                            <td>
                                <button
                                    type="button"
                                    class="changestatus pages-status-button"
                                    data-controller="<?php echo $this->controller; ?>"
                                    id="statusID<?php echo $id; ?>"
                                    aria-label="Change status for <?php echo $escapedTitle; ?>"
                                    title="Change status"
                                    data-bs-toggle="tooltip"
                                ><?php echo $escape($record[$this->tStatus]); ?></button>
                            </td>
                            <td class="d-none d-md-table-cell">
                                <time datetime="<?php echo date('c', strtotime($updatedAt)); ?>">
                                    <?php echo date(ADMIN_DATE_FORMAT, strtotime($updatedAt)); ?>
                                    <span class="pages-cell-meta"><?php echo date(ADMIN_TIME_FORMAT, strtotime($updatedAt)); ?></span>
                                </time>
                            </td>
                            <td class="pages-actions-cell">
                                <a
                                    class="admin-action-icon font16"
                                    href="<?php echo $editUrl; ?>"
                                    aria-label="Edit <?php echo $escapedTitle; ?>"
                                    title="Edit"
                                    data-bs-toggle="tooltip"
                                ><i class="bi bi-pencil" aria-hidden="true"></i></a>
                            </td>
                        </tr>
                    <?php } ?>
                <?php } else { ?>
                    <tr>
                        <td class="pages-empty-state" colspan="9">
                            <strong><?php echo $hasActiveFilters ? 'No WhatsApp templates match your filters.' : 'No WhatsApp templates found.'; ?></strong>
                            <span><?php echo $hasActiveFilters ? 'Try changing your search or status filter.' : 'No WhatsApp templates have been created yet.'; ?></span>
                        </td>
                    </tr>
                <?php } ?>
            </tbody>
        </table>
    </div>

    <?php $this->load->view('admin/partials/table_listing_footer', array(
        'total_rows' => $total_rows,
        'per_page' => $per_page,
        'selected_per_page' => $this->per_page,
        'page_offset' => $page_numb,
        'pagination' => isset($paginate) ? $paginate : '',
        'pagination_label' => 'WhatsApp template pagination',
    )); ?>
</section>

<script>
    jQuery(function($) {
        var $form = $('#whatsapp-templates-filter-form'),
            $search = $('#search_keywords'),
            $status = $('#search_status');
        var filterUrl = <?php echo json_encode(base_url('manage/' . $this->controller . '/index/' . $sortby . '/' . (($order === 'ASC') ? 'DESC' : 'ASC'))); ?>;

        function applyFilters() {
            var keyword = $.trim($search.val());
            window.location = filterUrl + '/' + $status.val() + '/' + (keyword === '' ? '-' : encodeURIComponent(keyword));
        }

        $form.on('submit', function(event) {
            event.preventDefault();
            applyFilters();
        });
        $status.on('change', applyFilters);
    });
</script>
