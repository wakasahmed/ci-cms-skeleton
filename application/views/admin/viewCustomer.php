<?php
$escape = function ($value) {
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
};
$id = (int) $customer['customer_id'];
$status = (string) $customer['customer_status'];
$details = array(
    array('Email', $customer['customer_email'], 'bi-envelope'),
    array('Phone', $customer['customer_phone'] !== NULL && $customer['customer_phone'] !== '' ? $customer['customer_phone'] : 'Not given', 'bi-telephone'),
    array(
        'Email confirmed',
        $customer['customer_email_verified_at'] !== NULL
            ? date(ADMIN_DATETIME_FORMAT, strtotime($customer['customer_email_verified_at']))
            : 'Not yet',
        'bi-patch-check',
    ),
    array('Joined', date(ADMIN_DATETIME_FORMAT, strtotime($customer['customer_added'])), 'bi-person-plus'),
    array(
        'Last sign-in',
        $customer['customer_last_login'] !== NULL ? date(ADMIN_DATETIME_FORMAT, strtotime($customer['customer_last_login'])) : 'Never',
        'bi-box-arrow-in-right',
    ),
);
?>
<?php $this->load->view('admin/partials/breadcrumb', array(
    'items' => array(
        array('label' => $this->moduleName, 'url' => ADMIN_URL.$this->controller),
        array('label' => $customer['customer_name'], 'active' => TRUE),
    ),
)); ?>

<section class="admin-records-listing" aria-labelledby="customer-title">
    <?php $this->load->view('admin/partials/module_header', array(
        'title' => $customer['customer_name'],
        'description' => 'Website account, '.($status === 'Enable' ? 'enabled' : 'disabled').'.',
        'id' => 'customer-title',
        'actions' => array(
            array(
                'url' => ADMIN_URL.$this->controller,
                'label' => 'All Customers',
                'icon' => 'bi-arrow-left',
                'class' => 'btn-outline-secondary',
            ),
        ),
    )); ?>

    <div class="row g-4">
        <div class="col-lg-4">
            <div class="card admin-card mb-4">
                <div class="card-header d-flex flex-wrap align-items-center justify-content-between gap-2">
                    <h2 class="card-title mb-0">Account</h2>
                    <button
                        type="button"
                        class="changestatus pages-status-button"
                        data-controller="<?php echo $this->controller; ?>"
                        id="statusID<?php echo $id; ?>"
                        aria-label="Change status for <?php echo $escape($customer['customer_name']); ?>"
                        title="Change status"
                        data-bs-toggle="tooltip"
                    ><?php echo $escape($status); ?></button>
                </div>
                <div class="card-body">
                    <div class="contact-request-details-grid">
                        <?php foreach ($details as $detail) { ?>
                            <div class="contact-request-detail contact-request-detail-wide">
                                <span class="contact-request-detail-icon" aria-hidden="true"><i class="bi <?php echo $detail[2]; ?>"></i></span>
                                <div>
                                    <span class="contact-request-detail-label"><?php echo $escape($detail[0]); ?></span>
                                    <span class="contact-request-detail-value"><?php echo $escape($detail[1]); ?></span>
                                </div>
                            </div>
                        <?php } ?>
                    </div>
                    <p class="form-text mt-3 mb-0">
                        Disabling signs the customer out and stops them signing in. Their appointments are kept.
                    </p>
                </div>
            </div>
        </div>

        <div class="col-lg-8">
            <div class="card admin-card mb-4">
                <div class="card-header">
                    <h2 class="card-title mb-0">Appointments (<?php echo count($appointments); ?>)</h2>
                </div>
                <div class="table-responsive pages-table-responsive" tabindex="0" aria-label="Appointments of <?php echo $escape($customer['customer_name']); ?>">
                    <table class="table pages-listing-table admin-records-table mb-0">
                        <thead>
                            <tr>
                                <th scope="col">Appointment</th>
                                <th scope="col">Reference</th>
                                <th scope="col" class="d-none d-md-table-cell">Services</th>
                                <th scope="col">Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (!empty($appointments)) { ?>
                                <?php foreach ($appointments as $appointment) {
                                    $start = strtotime($appointment['appointment_date'].' '.$appointment['appointment_time']);
                                    ?>
                                    <tr>
                                        <td>
                                            <time datetime="<?php echo date('c', $start); ?>">
                                                <?php echo date(ADMIN_DATE_FORMAT, $start); ?>
                                                <span class="pages-cell-meta"><?php echo date(ADMIN_TIME_FORMAT, $start); ?></span>
                                            </time>
                                        </td>
                                        <td>
                                            <a class="pages-name-link" href="<?php echo base_url('manage/appointments/view/'.(int) $appointment['appointment_id']); ?>"><?php echo $escape($appointment['appointment_reference']); ?></a>
                                        </td>
                                        <td class="d-none d-md-table-cell"><?php echo $escape(implode(', ', array_column($appointment['services'], 'service_name'))); ?></td>
                                        <td><span class="badge <?php echo admin_appointment_status_badge($appointment['appointment_status']); ?>"><?php echo $escape($appointment['appointment_status']); ?></span></td>
                                    </tr>
                                <?php } ?>
                            <?php } else { ?>
                                <tr>
                                    <td class="pages-empty-state" colspan="4"><span>No appointments linked to this account yet.</span></td>
                                </tr>
                            <?php } ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</section>
