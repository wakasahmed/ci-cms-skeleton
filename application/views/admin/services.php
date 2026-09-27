<?php
$hasActiveFilters = ($keywords !== '-' || $status !== '-' || $category !== '-');
$addUrl = base_url('manage/'.$this->controller.'/control');
$sortUrl = function ($column) use ($order, $status, $category, $keywords, $page_numb) {
    return base_url(
        'manage/'.$this->controller.'/index/'.$column.'/'.$order.'/'.$status.'/'.$category.'/'.rawurlencode($keywords).'/'.$page_numb
    );
};
$sortingEnabled = !$hasActiveFilters
    && $sortby === $this->colPrefix.'order'
    && $order === 'DESC';
$columnCount = $sortingEnabled ? 10 : 9;
?>
<?php $this->load->view('admin/partials/breadcrumb', array(
    'items' => array(
        array('label' => $this->moduleName, 'active' => TRUE),
    ),
)); ?>

<?php $this->load->view('admin/partials/crud_alert', array(
    'module_name' => 'Service',
    'status' => $alert,
)); ?>

<section class="admin-records-listing" aria-labelledby="services-title" data-records-listing>
    <?php $this->load->view('admin/partials/module_header', array(
        'title' => $this->moduleName,
        'description' => $this->moduleDesc,
        'id' => 'services-title',
        'action_url' => $addUrl,
        'action_label' => 'Add '.$this->moduleNameSingular,
    )); ?>

    <?php if (empty($categories)) { ?>
        <div class="alert alert-warning" role="status">
            Add a <a href="<?php echo base_url('manage/service-categories/control'); ?>">service category</a> before adding services.
        </div>
    <?php } ?>

    <div class="pages-listing-toolbar">
        <form
            class="pages-filter-form"
            role="search"
            data-records-filter
            data-filter-base-url="<?php echo htmlspecialchars(base_url('manage/'.$this->controller.'/index/'.$sortby.'/'.($order === 'ASC' ? 'DESC' : 'ASC')), ENT_QUOTES, 'UTF-8'); ?>"
        >
            <div class="pages-search-control">
                <label class="visually-hidden" for="search_keywords">Search services</label>
                <span class="pages-search-icon" aria-hidden="true"><i class="bi bi-search"></i></span>
                <input
                    class="form-control"
                    type="search"
                    id="search_keywords"
                    value="<?php echo $keywords !== '-' ? htmlspecialchars($keywords, ENT_QUOTES, 'UTF-8') : ''; ?>"
                    placeholder="Search service name..."
                    autocomplete="off"
                    data-filter-keyword
                >
            </div>
            <button class="btn btn-outline-secondary pages-search-submit" type="submit">
                <i class="bi bi-search" aria-hidden="true"></i>
                <span class="d-none d-sm-inline">Search</span>
            </button>
            <div class="pages-status-control">
                <label class="visually-hidden" for="search_status">Filter by status</label>
                <select class="form-select select2" id="search_status" data-minimum-results-for-search="-1" data-filter-segment>
                    <option value="-">All Statuses</option>
                    <option value="Enable" <?php echo $status === 'Enable' ? 'selected' : ''; ?>>Enabled</option>
                    <option value="Disable" <?php echo $status === 'Disable' ? 'selected' : ''; ?>>Disabled</option>
                </select>
            </div>
            <div class="pages-status-control">
                <label class="visually-hidden" for="search_category">Filter by category</label>
                <select class="form-select select2" id="search_category" data-minimum-results-for-search="-1" data-filter-segment>
                    <option value="-">All Categories</option>
                    <?php foreach ($categories as $categoryId => $categoryRow) { ?>
                        <option value="<?php echo (int) $categoryId; ?>" <?php echo $category === (int) $categoryId ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($categoryRow['category_name'], ENT_QUOTES, 'UTF-8'); ?>
                        </option>
                    <?php } ?>
                </select>
            </div>
            <?php if ($hasActiveFilters) { ?>
                <a class="btn btn-outline-secondary pages-clear-filters" href="<?php echo base_url('manage/'.$this->controller); ?>">Clear filters</a>
            <?php } ?>
        </form>
    </div>

    <div class="pages-bulk-actions" aria-live="polite" data-bulk-actions hidden>
        <span class="pages-selection-count" data-selection-count>0 selected</span>
        <button id="deleteAllRecords" class="btn btn-sm btn-outline-danger" type="button" disabled>
            <i class="bi bi-trash" aria-hidden="true"></i> Delete Selected
        </button>
    </div>

    <form action="<?php echo ADMIN_URL.$this->controller; ?>/deleteall" method="post" name="multiDel" id="multiDel">
        <div class="table-responsive pages-table-responsive" tabindex="0" aria-label="Services table">
            <table
                id="table-<?php echo $this->controller; ?>"
                class="table pages-listing-table admin-records-table"
                <?php if ($sortingEnabled) { ?>
                    data-sortable-records
                    data-sort-handle=".pages-drag-handle"
                    data-sort-url="<?php echo base_url('manage/record-sorting/sort/'.$this->controller); ?>"
                    data-sort-offset="<?php echo (int) $page_numb; ?>"
                <?php } ?>
            >
                <thead>
                    <tr>
                        <?php if ($sortingEnabled) { ?>
                            <th scope="col"><span class="visually-hidden">Reorder</span></th>
                        <?php } ?>
                        <th class="pages-select-column" scope="col">
                            <input class="form-check-input" type="checkbox" id="all-checkbox" autocomplete="off" aria-label="Select all services">
                        </th>
                        <?php echo admin_sort_heading('ID', $this->pKey, $sortby, $order, $sortUrl($this->pKey), 'd-none d-md-table-cell'); ?>
                        <th scope="col" class="d-none d-sm-table-cell">Image</th>
                        <?php echo admin_sort_heading('Name', $this->colPrefix.'name', $sortby, $order, $sortUrl($this->colPrefix.'name')); ?>
                        <?php echo admin_sort_heading('Price', $this->colPrefix.'price_from', $sortby, $order, $sortUrl($this->colPrefix.'price_from')); ?>
                        <th scope="col" class="d-none d-lg-table-cell">Duration</th>
                        <?php echo admin_sort_heading('Status', $this->tStatus, $sortby, $order, $sortUrl($this->tStatus)); ?>
                        <?php echo admin_sort_heading('Updated On', $this->colPrefix.'updated', $sortby, $order, $sortUrl($this->colPrefix.'updated'), 'd-none d-md-table-cell'); ?>
                        <th scope="col">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (!empty($records)) { ?>
                        <?php foreach ($records as $record) { ?>
                            <?php
                            $id = (int) $record[$this->pKey];
                            $name = (string) $record[$this->colPrefix.'name'];
                            $escapedName = htmlspecialchars($name, ENT_QUOTES, 'UTF-8');
                            $editUrl = base_url('manage/'.$this->controller.'/control/edit/'.$id);
                            $categoryId = (int) $record[$this->colPrefix.'category_id'];
                            $categoryName = isset($categories[$categoryId]) ? $categories[$categoryId]['category_name'] : '';
                            $price = admin_format_price($record[$this->colPrefix.'price_from'], $record[$this->colPrefix.'price_suffix']);
                            ?>
                            <tr id="<?php echo $this->controller.'-'.$id; ?>" data-record-id="<?php echo $id; ?>">
                                <?php if ($sortingEnabled) { ?>
                                    <td class="pages-drag-handle" title="Drag to reorder <?php echo $escapedName; ?>" aria-label="Drag to reorder <?php echo $escapedName; ?>">
                                        <i class="bi bi-grip-vertical" aria-hidden="true"></i>
                                    </td>
                                <?php } ?>
                                <td class="pages-select-column">
                                    <input name="records[]" class="form-check-input cselect" value="<?php echo $id; ?>" type="checkbox" aria-label="Select <?php echo $escapedName; ?>">
                                </td>
                                <td class="d-none d-md-table-cell"><span class="pages-record-id"><?php echo $id; ?></span></td>
                                <td class="d-none d-sm-table-cell">
                                    <?php echo image_thumb(
                                        'assets/frontend/images/services/'.basename((string) $record[$this->colPrefix.'card_image']),
                                        96,
                                        96,
                                        'webp',
                                        'square',
                                        TRUE,
                                        array(
                                            'alt' => $name,
                                            'size' => 56,
                                            'group' => 'services',
                                            'class' => 'admin-table-image',
                                        )
                                    ); ?>
                                </td>
                                <td>
                                    <a class="pages-name-link" href="<?php echo $editUrl; ?>"><?php echo $escapedName; ?></a>
                                    <?php if ((int) $record[$this->colPrefix.'featured'] === 1) { ?>
                                        <span class="badge text-bg-light ms-1">Featured</span>
                                    <?php } ?>
                                    <span class="pages-cell-meta"><?php echo htmlspecialchars($categoryName, ENT_QUOTES, 'UTF-8'); ?></span>
                                </td>
                                <td><?php echo $price !== '' ? 'from '.htmlspecialchars($price, ENT_QUOTES, 'UTF-8') : '<span class="text-muted">&mdash;</span>'; ?></td>
                                <td class="d-none d-lg-table-cell"><?php echo htmlspecialchars((string) $record[$this->colPrefix.'duration_label'], ENT_QUOTES, 'UTF-8'); ?></td>
                                <td>
                                    <button
                                        type="button"
                                        class="changestatus pages-status-button"
                                        data-controller="<?php echo $this->controller; ?>"
                                        id="statusID<?php echo $id; ?>"
                                        aria-label="Change status for <?php echo $escapedName; ?>"
                                        title="Change status"
                                        data-bs-toggle="tooltip"
                                    ><?php echo htmlspecialchars($record[$this->tStatus], ENT_QUOTES, 'UTF-8'); ?></button>
                                </td>
                                <td class="d-none d-md-table-cell"><?php echo admin_datetime_cell($record[$this->colPrefix.'updated']); ?></td>
                                <td class="pages-actions-cell">
                                    <a
                                        class="admin-action-icon font16 dupitem"
                                        id="copyID<?php echo $id; ?>"
                                        data-rec-name="<?php echo $escapedName; ?>"
                                        data-controller="<?php echo $this->controller; ?>"
                                        href="javascript:void(0);"
                                        aria-label="Duplicate <?php echo $escapedName; ?>"
                                        title="Duplicate"
                                        data-bs-toggle="tooltip"
                                    ><i class="bi bi-copy" aria-hidden="true"></i></a>
                                    <a class="admin-action-icon font16" href="<?php echo $editUrl; ?>" aria-label="Edit <?php echo $escapedName; ?>" title="Edit" data-bs-toggle="tooltip">
                                        <i class="bi bi-pencil" aria-hidden="true"></i>
                                    </a>
                                    <a
                                        class="admin-action-icon font16 delitem"
                                        href="javascript:void(0);"
                                        data-controller="<?php echo $this->controller; ?>"
                                        data-record-name="<?php echo $escapedName; ?>"
                                        id="recordID<?php echo $id; ?>"
                                        aria-label="Delete <?php echo $escapedName; ?>"
                                        title="Delete"
                                        data-bs-toggle="tooltip"
                                    ><i class="bi bi-trash" aria-hidden="true"></i></a>
                                </td>
                            </tr>
                        <?php } ?>
                    <?php } else { ?>
                        <tr class="nodrag">
                            <td class="pages-empty-state" colspan="<?php echo $columnCount; ?>">
                                <strong><?php echo $hasActiveFilters ? 'No services match your filters.' : 'No services yet.'; ?></strong>
                                <span><?php echo $hasActiveFilters ? 'Try changing your search, status or category filter.' : 'Add a service to start building the price menu.'; ?></span>
                            </td>
                        </tr>
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
        'pagination_label' => 'Service pagination',
    )); ?>
</section>
