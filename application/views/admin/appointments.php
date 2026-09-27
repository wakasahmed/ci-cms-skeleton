<?php
$hasActiveFilters = ($keywords !== '-' || $status !== '-');
$sortUrl = function ($column) use ($order, $status, $keywords, $page_numb) {
    return base_url(
        'manage/'.$this->controller.'/index/'.$column.'/'.$order.'/'.$status.'/'.rawurlencode($keywords).'/'.$page_numb
    );
};
$statusBadges = array(
    'New' => 'text-bg-warning',
    'Confirmed' => 'text-bg-primary',
    'Completed' => 'text-bg-success',
    'Cancelled' => 'text-bg-secondary',
);
?>
<?php $this->load->view('admin/partials/breadcrumb', array(
    'items' => array(
        array('label' => $this->moduleName, 'active' => TRUE),
    ),
)); ?>

<?php $this->load->view('admin/partials/crud_alert', array(
    'module_name' => 'Appointment request',
    'status' => $alert,
)); ?>

<section class="admin-records-listing" aria-labelledby="appointments-title" data-records-listing>
    <?php $this->load->view('admin/partials/module_header', array(
        'title' => $this->moduleName,
        'description' => $this->moduleDesc,
        'id' => 'appointments-title',
    )); ?>

    <div class="pages-listing-toolbar">
        <form
            class="pages-filter-form"
            role="search"
            data-records-filter
            data-filter-base-url="<?php echo htmlspecialchars(base_url('manage/'.$this->controller.'/index/'.$sortby.'/'.($order === 'ASC' ? 'DESC' : 'ASC')), ENT_QUOTES, 'UTF-8'); ?>"
        >
            <div class="pages-search-control">
                <label class="visually-hidden" for="search_keywords">Search appointment requests</label>
                <span class="pages-search-icon" aria-hidden="true"><i class="bi bi-search"></i></span>
                <input
                    class="form-control"
                    type="search"
                    id="search_keywords"
                    value="<?php echo $keywords !== '-' ? htmlspecialchars($keywords, ENT_QUOTES, 'UTF-8') : ''; ?>"
                    placeholder="Search reference, name, email or phone..."
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
                    <?php foreach ($status_options as $statusOption) { ?>
                        <option value="<?php echo $statusOption; ?>" <?php echo $status === $statusOption ? 'selected' : ''; ?>>
                            <?php echo $statusOption; ?> (<?php echo isset($status_counts[$statusOption]) ? (int) $status_counts[$statusOption] : 0; ?>)
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
        <div class="table-responsive pages-table-responsive" tabindex="0" aria-label="Appointment requests table">
            <table id="table-<?php echo $this->controller; ?>" class="table pages-listing-table admin-records-table">
                <thead>
                    <tr>
                        <th class="pages-select-column" scope="col">
                            <input class="form-check-input" type="checkbox" id="all-checkbox" autocomplete="off" aria-label="Select all appointment requests">
                        </th>
                        <?php echo admin_sort_heading('Reference', $this->colPrefix.'reference', $sortby, $order, $sortUrl($this->colPrefix.'reference')); ?>
                        <?php echo admin_sort_heading('Appointment', $this->colPrefix.'date', $sortby, $order, $sortUrl($this->colPrefix.'date')); ?>
                        <?php echo admin_sort_heading('Client', 'customer_name', $sortby, $order, $sortUrl('customer_name')); ?>
                        <th scope="col" class="d-none d-lg-table-cell">Services</th>
                        <?php echo admin_sort_heading('Status', $this->tStatus, $sortby, $order, $sortUrl($this->tStatus)); ?>
                        <?php echo admin_sort_heading('Requested On', $this->colPrefix.'added', $sortby, $order, $sortUrl($this->colPrefix.'added'), 'd-none d-md-table-cell'); ?>
                        <th scope="col">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (!empty($records)) { ?>
                        <?php foreach ($records as $record) { ?>
                            <?php
                            $id = (int) $record[$this->pKey];
                            $reference = htmlspecialchars((string) $record[$this->colPrefix.'reference'], ENT_QUOTES, 'UTF-8');
                            $viewUrl = base_url('manage/'.$this->controller.'/view/'.$id);
                            $appointmentTime = strtotime($record[$this->colPrefix.'date'].' '.$record[$this->colPrefix.'time']);
                            $serviceNames = array();

                            foreach (isset($record_services[$id]) ? $record_services[$id] : array() as $service) {
                                $serviceNames[] = $service['service_name'];
                            }

                            $recordStatus = (string) $record[$this->tStatus];
                            ?>
                            <tr id="<?php echo $this->controller.'-'.$id; ?>" data-record-id="<?php echo $id; ?>">
                                <td class="pages-select-column">
                                    <input name="records[]" class="form-check-input cselect" value="<?php echo $id; ?>" type="checkbox" aria-label="Select request <?php echo $reference; ?>">
                                </td>
                                <td><a class="pages-name-link" href="<?php echo $viewUrl; ?>"><?php echo $reference; ?></a></td>
                                <td>
                                    <?php if ($appointmentTime) { ?>
                                        <time datetime="<?php echo date('c', $appointmentTime); ?>">
                                            <?php echo date(ADMIN_DATE_FORMAT, $appointmentTime); ?>
                                            <span class="pages-cell-meta"><?php echo date(ADMIN_TIME_FORMAT, $appointmentTime); ?></span>
                                        </time>
                                    <?php } ?>
                                </td>
                                <td>
                                    <?php echo htmlspecialchars((string) $record['customer_name'], ENT_QUOTES, 'UTF-8'); ?>
                                    <span class="pages-cell-meta"><?php echo htmlspecialchars((string) $record['customer_email'], ENT_QUOTES, 'UTF-8'); ?></span>
                                </td>
                                <td class="d-none d-lg-table-cell">
                                    <?php echo htmlspecialchars(implode(', ', $serviceNames), ENT_QUOTES, 'UTF-8'); ?>
                                    <?php if (!empty($record[$this->colPrefix.'artist_name'])) { ?>
                                        <span class="pages-cell-meta">with <?php echo htmlspecialchars($record[$this->colPrefix.'artist_name'], ENT_QUOTES, 'UTF-8'); ?></span>
                                    <?php } ?>
                                </td>
                                <td>
                                    <span class="badge <?php echo isset($statusBadges[$recordStatus]) ? $statusBadges[$recordStatus] : 'text-bg-light'; ?>">
                                        <?php echo htmlspecialchars($recordStatus, ENT_QUOTES, 'UTF-8'); ?>
                                    </span>
                                </td>
                                <td class="d-none d-md-table-cell"><?php echo admin_datetime_cell($record[$this->colPrefix.'added']); ?></td>
                                <td class="pages-actions-cell">
                                    <a class="admin-action-icon font16" href="<?php echo $viewUrl; ?>" aria-label="View request <?php echo $reference; ?>" title="View" data-bs-toggle="tooltip">
                                        <i class="bi bi-eye" aria-hidden="true"></i>
                                    </a>
                                    <a
                                        class="admin-action-icon font16 delitem"
                                        href="javascript:void(0);"
                                        data-controller="<?php echo $this->controller; ?>"
                                        data-record-name="request <?php echo $reference; ?>"
                                        id="recordID<?php echo $id; ?>"
                                        aria-label="Delete request <?php echo $reference; ?>"
                                        title="Delete"
                                        data-bs-toggle="tooltip"
                                    ><i class="bi bi-trash" aria-hidden="true"></i></a>
                                </td>
                            </tr>
                        <?php } ?>
                    <?php } else { ?>
                        <tr>
                            <td class="pages-empty-state" colspan="8">
                                <strong><?php echo $hasActiveFilters ? 'No appointment requests match your filters.' : 'No appointment requests yet.'; ?></strong>
                                <span><?php echo $hasActiveFilters ? 'Try changing your search or status filter.' : 'Requests made through the website booking form appear here.'; ?></span>
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
        'pagination_label' => 'Appointment request pagination',
    )); ?>
</section>
