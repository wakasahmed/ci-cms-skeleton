<?php
$hasActiveFilters = ($keywords !== '-' || $status !== '-' || $category !== '-');
$sortUrl = function ($column) use ($order, $status, $category, $keywords, $page_numb) {
    return base_url(
        'manage/'.$this->controller.'/index/'.$column.'/'.$order.'/'.$status.'/'.$category.'/'.rawurlencode($keywords).'/'.$page_numb
    );
};
$sortingEnabled = !$hasActiveFilters
    && $sortby === $this->colPrefix.'order'
    && $order === 'DESC';
$columnCount = $sortingEnabled ? 9 : 8;
?>
<?php $this->load->view('admin/partials/breadcrumb', array(
    'items' => array(
        array('label' => $this->moduleName, 'active' => TRUE),
    ),
)); ?>

<?php $this->load->view('admin/partials/crud_alert', array(
    'module_name' => 'Gallery image',
    'status' => $alert,
)); ?>

<section class="admin-records-listing" aria-labelledby="gallery-title" data-records-listing>
    <?php $this->load->view('admin/partials/module_header', array(
        'title' => $this->moduleName,
        'description' => $this->moduleDesc,
        'id' => 'gallery-title',
        'actions' => array(
            array(
                'url' => base_url('manage/'.$this->controller.'/upload'),
                'label' => 'Upload Several',
                'icon' => 'bi-cloud-arrow-up',
                'class' => 'btn-outline-primary',
            ),
            array(
                'url' => base_url('manage/'.$this->controller.'/control'),
                'label' => 'Add Image',
                'icon' => 'bi-plus-lg',
                'class' => 'btn-primary pages-add-button',
            ),
        ),
    )); ?>

    <div class="pages-listing-toolbar">
        <form
            class="pages-filter-form"
            role="search"
            data-records-filter
            data-filter-base-url="<?php echo htmlspecialchars(base_url('manage/'.$this->controller.'/index/'.$sortby.'/'.($order === 'ASC' ? 'DESC' : 'ASC')), ENT_QUOTES, 'UTF-8'); ?>"
        >
            <div class="pages-search-control">
                <label class="visually-hidden" for="search_keywords">Search gallery captions</label>
                <span class="pages-search-icon" aria-hidden="true"><i class="bi bi-search"></i></span>
                <input
                    class="form-control"
                    type="search"
                    id="search_keywords"
                    value="<?php echo $keywords !== '-' ? htmlspecialchars($keywords, ENT_QUOTES, 'UTF-8') : ''; ?>"
                    placeholder="Search captions..."
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
        <div class="table-responsive pages-table-responsive" tabindex="0" aria-label="Gallery images table">
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
                            <input class="form-check-input" type="checkbox" id="all-checkbox" autocomplete="off" aria-label="Select all gallery images">
                        </th>
                        <?php echo admin_sort_heading('ID', $this->pKey, $sortby, $order, $sortUrl($this->pKey), 'd-none d-md-table-cell'); ?>
                        <th scope="col">Image</th>
                        <?php echo admin_sort_heading('Caption', $this->colPrefix.'caption', $sortby, $order, $sortUrl($this->colPrefix.'caption')); ?>
                        <th scope="col" class="d-none d-lg-table-cell">Category</th>
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
                            $caption = (string) $record[$this->colPrefix.'caption'];
                            $escapedCaption = htmlspecialchars($caption, ENT_QUOTES, 'UTF-8');
                            $editUrl = base_url('manage/'.$this->controller.'/control/edit/'.$id);
                            $categoryId = (int) $record[$this->colPrefix.'category_id'];
                            $serviceId = (int) $record[$this->colPrefix.'service_id'];
                            ?>
                            <tr id="<?php echo $this->controller.'-'.$id; ?>" data-record-id="<?php echo $id; ?>">
                                <?php if ($sortingEnabled) { ?>
                                    <td class="pages-drag-handle" title="Drag to reorder <?php echo $escapedCaption; ?>" aria-label="Drag to reorder <?php echo $escapedCaption; ?>">
                                        <i class="bi bi-grip-vertical" aria-hidden="true"></i>
                                    </td>
                                <?php } ?>
                                <td class="pages-select-column">
                                    <input name="records[]" class="form-check-input cselect" value="<?php echo $id; ?>" type="checkbox" aria-label="Select <?php echo $escapedCaption; ?>">
                                </td>
                                <td class="d-none d-md-table-cell"><span class="pages-record-id"><?php echo $id; ?></span></td>
                                <td>
                                    <?php echo image_thumb(
                                        $image_directory.'/'.basename((string) $record[$this->colPrefix.'file']),
                                        120,
                                        120,
                                        'webp',
                                        'square',
                                        TRUE,
                                        array(
                                            'alt' => $caption,
                                            'size' => 64,
                                            'group' => 'gallery',
                                            'class' => 'admin-table-image',
                                        )
                                    ); ?>
                                </td>
                                <td>
                                    <a class="pages-name-link" href="<?php echo $editUrl; ?>"><?php echo $escapedCaption; ?></a>
                                    <?php if ((int) $record[$this->colPrefix.'featured'] === 1) { ?>
                                        <span class="badge text-bg-light ms-1">Home page</span>
                                    <?php } ?>
                                    <?php if (isset($services[$serviceId])) { ?>
                                        <span class="pages-cell-meta"><?php echo htmlspecialchars($services[$serviceId]['service_name'], ENT_QUOTES, 'UTF-8'); ?></span>
                                    <?php } ?>
                                </td>
                                <td class="d-none d-lg-table-cell">
                                    <?php if (isset($categories[$categoryId])) { ?>
                                        <?php echo htmlspecialchars($categories[$categoryId]['category_name'], ENT_QUOTES, 'UTF-8'); ?>
                                    <?php } else { ?>
                                        <span class="text-muted">Uncategorised</span>
                                    <?php } ?>
                                </td>
                                <td>
                                    <button
                                        type="button"
                                        class="changestatus pages-status-button"
                                        data-controller="<?php echo $this->controller; ?>"
                                        id="statusID<?php echo $id; ?>"
                                        aria-label="Change status for <?php echo $escapedCaption; ?>"
                                        title="Change status"
                                        data-bs-toggle="tooltip"
                                    ><?php echo htmlspecialchars($record[$this->tStatus], ENT_QUOTES, 'UTF-8'); ?></button>
                                </td>
                                <td class="d-none d-md-table-cell"><?php echo admin_datetime_cell($record[$this->colPrefix.'updated']); ?></td>
                                <td class="pages-actions-cell">
                                    <a class="admin-action-icon font16" href="<?php echo $editUrl; ?>" aria-label="Edit <?php echo $escapedCaption; ?>" title="Edit" data-bs-toggle="tooltip">
                                        <i class="bi bi-pencil" aria-hidden="true"></i>
                                    </a>
                                    <a
                                        class="admin-action-icon font16 delitem"
                                        href="javascript:void(0);"
                                        data-controller="<?php echo $this->controller; ?>"
                                        data-record-name="<?php echo $escapedCaption; ?>"
                                        id="recordID<?php echo $id; ?>"
                                        aria-label="Delete <?php echo $escapedCaption; ?>"
                                        title="Delete"
                                        data-bs-toggle="tooltip"
                                    ><i class="bi bi-trash" aria-hidden="true"></i></a>
                                </td>
                            </tr>
                        <?php } ?>
                    <?php } else { ?>
                        <tr class="nodrag">
                            <td class="pages-empty-state" colspan="<?php echo $columnCount; ?>">
                                <strong><?php echo $hasActiveFilters ? 'No images match your filters.' : 'The gallery is empty.'; ?></strong>
                                <span><?php echo $hasActiveFilters ? 'Try changing your search, status or category filter.' : 'Add an image, or upload several at once.'; ?></span>
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
        'pagination_label' => 'Gallery pagination',
    )); ?>
</section>
