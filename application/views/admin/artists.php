<?php
$hasActiveFilters = ($keywords !== '-' || $status !== '-');
$addUrl = base_url('manage/'.$this->controller.'/control');
$sortUrl = function ($column) use ($order, $status, $keywords, $page_numb) {
    return base_url(
        'manage/'.$this->controller.'/index/'.$column.'/'.$order.'/'.$status.'/'.rawurlencode($keywords).'/'.$page_numb
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
    'module_name' => 'Artist',
    'status' => $alert,
)); ?>

<section class="admin-records-listing" aria-labelledby="artists-title" data-records-listing>
    <?php $this->load->view('admin/partials/module_header', array(
        'title' => $this->moduleName,
        'description' => $this->moduleDesc,
        'id' => 'artists-title',
        'action_url' => $addUrl,
        'action_label' => 'Add '.$this->moduleNameSingular,
    )); ?>

    <div class="pages-listing-toolbar">
        <form
            class="pages-filter-form"
            role="search"
            data-records-filter
            data-filter-base-url="<?php echo htmlspecialchars(base_url('manage/'.$this->controller.'/index/'.$sortby.'/'.($order === 'ASC' ? 'DESC' : 'ASC')), ENT_QUOTES, 'UTF-8'); ?>"
        >
            <div class="pages-search-control">
                <label class="visually-hidden" for="search_keywords">Search artists</label>
                <span class="pages-search-icon" aria-hidden="true"><i class="bi bi-search"></i></span>
                <input
                    class="form-control"
                    type="search"
                    id="search_keywords"
                    value="<?php echo $keywords !== '-' ? htmlspecialchars($keywords, ENT_QUOTES, 'UTF-8') : ''; ?>"
                    placeholder="Search name or role..."
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
        <div class="table-responsive pages-table-responsive" tabindex="0" aria-label="Artists table">
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
                            <input class="form-check-input" type="checkbox" id="all-checkbox" autocomplete="off" aria-label="Select all artists">
                        </th>
                        <?php echo admin_sort_heading('ID', $this->pKey, $sortby, $order, $sortUrl($this->pKey), 'd-none d-md-table-cell'); ?>
                        <th scope="col" class="d-none d-sm-table-cell">Photo</th>
                        <?php echo admin_sort_heading('Name', $this->colPrefix.'name', $sortby, $order, $sortUrl($this->colPrefix.'name')); ?>
                        <th scope="col" class="d-none d-lg-table-cell">Services</th>
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
                            $serviceCount = isset($service_counts[$id]) ? $service_counts[$id] : 0;
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
                                        'assets/frontend/images/artists/'.basename((string) $record[$this->colPrefix.'image']),
                                        96,
                                        96,
                                        'webp',
                                        'circle',
                                        TRUE,
                                        array(
                                            'alt' => $name,
                                            'size' => 48,
                                            'group' => 'artists',
                                            'class' => 'admin-table-image',
                                        )
                                    ); ?>
                                </td>
                                <td>
                                    <a class="pages-name-link" href="<?php echo $editUrl; ?>"><?php echo $escapedName; ?></a>
                                    <?php if ((int) $record[$this->colPrefix.'is_placeholder'] === 1) { ?>
                                        <span class="badge text-bg-warning ms-1">Placeholder</span>
                                    <?php } ?>
                                    <span class="pages-cell-meta"><?php echo htmlspecialchars((string) $record[$this->colPrefix.'role'], ENT_QUOTES, 'UTF-8'); ?></span>
                                </td>
                                <td class="d-none d-lg-table-cell"><?php echo $serviceCount; ?> <?php echo $serviceCount === 1 ? 'service' : 'services'; ?></td>
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
                                <strong><?php echo $hasActiveFilters ? 'No artists match your filters.' : 'No artists yet.'; ?></strong>
                                <span><?php echo $hasActiveFilters ? 'Try changing your search or status filter.' : 'Add the first member of the team.'; ?></span>
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
        'pagination_label' => 'Artist pagination',
    )); ?>
</section>
