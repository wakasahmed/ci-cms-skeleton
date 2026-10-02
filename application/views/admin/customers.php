<?php
$hasActiveFilters = ($keywords !== '-' || $status !== '-');
$sortUrl = function ($column) use ($order, $status, $keywords, $page_numb) {
    return base_url(
        'manage/'.$this->controller.'/index/'.$column.'/'.$order.'/'.$status.'/'.rawurlencode($keywords).'/'.$page_numb
    );
};
$escape = function ($value) {
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
};
?>
<?php $this->load->view('admin/partials/breadcrumb', array(
    'items' => array(
        array('label' => $this->moduleName, 'active' => TRUE),
    ),
)); ?>

<?php $this->load->view('admin/partials/crud_alert', array(
    'module_name' => 'Customer',
    'status' => $alert,
)); ?>

<section class="admin-records-listing" aria-labelledby="customers-title" data-records-listing>
    <?php $this->load->view('admin/partials/module_header', array(
        'title' => $this->moduleName,
        'description' => $this->moduleDesc,
        'id' => 'customers-title',
    )); ?>

    <div class="pages-listing-toolbar">
        <form
            class="pages-filter-form"
            role="search"
            data-records-filter
            data-filter-base-url="<?php echo $escape(base_url('manage/'.$this->controller.'/index/'.$sortby.'/'.($order === 'ASC' ? 'DESC' : 'ASC'))); ?>"
        >
            <div class="pages-search-control">
                <label class="visually-hidden" for="search_keywords">Search customers</label>
                <span class="pages-search-icon" aria-hidden="true"><i class="bi bi-search"></i></span>
                <input
                    class="form-control"
                    type="search"
                    id="search_keywords"
                    value="<?php echo $keywords !== '-' ? $escape($keywords) : ''; ?>"
                    placeholder="Search name, email or phone..."
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

    <div class="table-responsive pages-table-responsive" tabindex="0" aria-label="Customers table">
        <table id="table-<?php echo $this->controller; ?>" class="table pages-listing-table admin-records-table">
            <thead>
                <tr>
                    <?php echo admin_sort_heading('ID', $this->pKey, $sortby, $order, $sortUrl($this->pKey), 'd-none d-md-table-cell'); ?>
                    <?php echo admin_sort_heading('Customer', $this->colPrefix.'name', $sortby, $order, $sortUrl($this->colPrefix.'name')); ?>
                    <th scope="col" class="d-none d-lg-table-cell">Email</th>
                    <th scope="col" class="d-none d-md-table-cell">Appointments</th>
                    <?php echo admin_sort_heading('Status', $this->tStatus, $sortby, $order, $sortUrl($this->tStatus)); ?>
                    <?php echo admin_sort_heading('Joined', $this->colPrefix.'added', $sortby, $order, $sortUrl($this->colPrefix.'added'), 'd-none d-md-table-cell'); ?>
                    <?php echo admin_sort_heading('Last Sign-in', $this->colPrefix.'last_login', $sortby, $order, $sortUrl($this->colPrefix.'last_login'), 'd-none d-xl-table-cell'); ?>
                    <th scope="col">Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php if (!empty($records)) { ?>
                    <?php foreach ($records as $record) { ?>
                        <?php
                        $id = (int) $record[$this->pKey];
                        $name = $escape($record[$this->colPrefix.'name']);
                        $viewUrl = base_url('manage/'.$this->controller.'/view/'.$id);
                        $count = isset($appointment_counts[$id]) ? $appointment_counts[$id] : 0;
                        ?>
                        <tr id="<?php echo $this->controller.'-'.$id; ?>" data-record-id="<?php echo $id; ?>">
                            <td class="d-none d-md-table-cell"><span class="pages-record-id"><?php echo $id; ?></span></td>
                            <td>
                                <a class="pages-name-link" href="<?php echo $viewUrl; ?>"><?php echo $name; ?></a>
                                <span class="pages-cell-meta"><?php echo $escape($record[$this->colPrefix.'phone']); ?></span>
                            </td>
                            <td class="d-none d-lg-table-cell">
                                <?php echo $escape($record[$this->colPrefix.'email']); ?>
                                <span class="pages-cell-meta">
                                    <?php echo $record[$this->colPrefix.'email_verified_at'] !== NULL ? 'Confirmed' : 'Not confirmed'; ?>
                                </span>
                            </td>
                            <td class="d-none d-md-table-cell"><?php echo $count; ?></td>
                            <td>
                                <button
                                    type="button"
                                    class="changestatus pages-status-button"
                                    data-controller="<?php echo $this->controller; ?>"
                                    id="statusID<?php echo $id; ?>"
                                    aria-label="Change status for <?php echo $name; ?>"
                                    title="Change status"
                                    data-bs-toggle="tooltip"
                                ><?php echo $escape($record[$this->tStatus]); ?></button>
                            </td>
                            <td class="d-none d-md-table-cell"><?php echo admin_datetime_cell($record[$this->colPrefix.'added']); ?></td>
                            <td class="d-none d-xl-table-cell">
                                <?php echo $record[$this->colPrefix.'last_login'] !== NULL ? admin_datetime_cell($record[$this->colPrefix.'last_login']) : '<span class="pages-cell-meta">Never</span>'; ?>
                            </td>
                            <td class="pages-actions-cell">
                                <a class="admin-action-icon font16" href="<?php echo $viewUrl; ?>" aria-label="View <?php echo $name; ?>" title="View" data-bs-toggle="tooltip">
                                    <i class="bi bi-eye" aria-hidden="true"></i>
                                </a>
                            </td>
                        </tr>
                    <?php } ?>
                <?php } else { ?>
                    <tr>
                        <td class="pages-empty-state" colspan="8">
                            <strong><?php echo $hasActiveFilters ? 'No customers match your filters.' : 'No customer accounts yet.'; ?></strong>
                            <span><?php echo $hasActiveFilters ? 'Try changing your search or status filter.' : 'Accounts created on the website appear here.'; ?></span>
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
        'pagination_label' => 'Customer pagination',
    )); ?>
</section>
