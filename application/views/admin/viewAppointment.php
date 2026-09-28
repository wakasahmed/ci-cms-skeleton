<?php
$id = (int) $record[$this->pKey];
$escape = function ($value) {
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
};
$reference = $escape($record['appointment_reference']);
$appointmentTime = strtotime($record['appointment_date'].' '.$record['appointment_time']);
$currentStatus = (string) $record['appointment_status'];
$statusBadges = array(
    'New' => 'text-bg-warning',
    'Confirmed' => 'text-bg-primary',
    'Completed' => 'text-bg-success',
    'Cancelled' => 'text-bg-secondary',
);
$duration = (int) $record['appointment_duration_minutes'];
$durationText = '';

if ($duration > 0) {
    $hours = intdiv($duration, 60);
    $minutes = $duration % 60;
    $durationText = trim(($hours > 0 ? $hours.' hr ' : '').($minutes > 0 ? $minutes.' min' : ''));
}

$details = array(
    array('Date and time', $appointmentTime ? date(ADMIN_DATE_FORMAT, $appointmentTime).', '.date(ADMIN_TIME_FORMAT, $appointmentTime) : 'Not specified', 'bi-calendar3'),
    array('Artist', !empty($record['appointment_artist_name']) ? $record['appointment_artist_name'] : 'Any available artist', 'bi-person-heart'),
    array('Estimated duration', $durationText !== '' ? $durationText : 'Not specified', 'bi-clock'),
    array('Estimated price', $record['appointment_total_price'] !== NULL ? 'from '.admin_format_price($record['appointment_total_price']) : 'Not specified', 'bi-cash'),
);

if (!empty($record['appointment_offer_title'])) {
    $details[] = array('Offer', $record['appointment_offer_title'], 'bi-gift');
}

$client = array(
    array('Name', $record['customer_name'], 'bi-person'),
    array('Email', $record['customer_email'], 'bi-envelope'),
    array('Phone', $record['customer_phone'] !== NULL && $record['customer_phone'] !== '' ? $record['customer_phone'] : 'Not provided', 'bi-telephone'),
    array('Preferred contact', $record['customer_contact_preference'], 'bi-chat-dots'),
);
?>
<?php $this->load->view('admin/partials/breadcrumb', array(
    'items' => array(
        array('label' => $this->moduleName, 'url' => ADMIN_URL.$this->controller),
        array('label' => 'Request '.$record['appointment_reference'], 'active' => TRUE),
    ),
)); ?>

<?php if (is_array($message) && isset($message['text'])) { ?>
    <div class="row alertrow">
        <div class="col-md-12">
            <div class="alert alert-<?php echo !empty($message['success']) ? 'success' : 'danger'; ?> alert-dismissible fade show" role="alert">
                <?php echo $escape($message['text']); ?>
                <button type="button" class="btn-close alertBox crud-alert-close" data-bs-dismiss="alert" aria-label="Dismiss notification"></button>
            </div>
        </div>
    </div>
<?php } ?>

<section class="admin-records-listing" aria-labelledby="appointment-title">
    <?php $this->load->view('admin/partials/module_header', array(
        'title' => 'Request '.$record['appointment_reference'],
        'description' => 'Received '.date(ADMIN_DATETIME_FORMAT, strtotime($record['appointment_added'])).'.',
        'id' => 'appointment-title',
        'actions' => array(
            array(
                'url' => ADMIN_URL.$this->controller,
                'label' => 'All Requests',
                'icon' => 'bi-arrow-left',
                'class' => 'btn-outline-secondary',
            ),
        ),
    )); ?>

    <div class="row g-4">
        <div class="col-lg-8">
            <div class="card admin-card mb-4">
                <div class="card-header d-flex flex-wrap align-items-center justify-content-between gap-2">
                    <h2 class="card-title mb-0">Appointment</h2>
                    <span class="badge <?php echo isset($statusBadges[$currentStatus]) ? $statusBadges[$currentStatus] : 'text-bg-light'; ?>"><?php echo $escape($currentStatus); ?></span>
                </div>
                <div class="card-body">
                    <div class="contact-request-details-grid mb-4">
                        <?php foreach ($details as $detail) { ?>
                            <div class="contact-request-detail">
                                <span class="contact-request-detail-icon" aria-hidden="true"><i class="bi <?php echo $detail[2]; ?>"></i></span>
                                <div>
                                    <span class="contact-request-detail-label"><?php echo $escape($detail[0]); ?></span>
                                    <span class="contact-request-detail-value"><?php echo $escape($detail[1]); ?></span>
                                </div>
                            </div>
                        <?php } ?>
                    </div>

                    <h3 class="h6 mb-2">Services</h3>
                    <?php if (!empty($services)) { ?>
                        <div class="table-responsive">
                            <table class="table table-sm align-middle mb-0">
                                <thead>
                                    <tr>
                                        <th scope="col">Start</th>
                                        <th scope="col">Service</th>
                                        <th scope="col">Artist</th>
                                        <th scope="col">Duration</th>
                                        <th scope="col" class="text-end">Price</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($services as $service) { ?>
                                        <tr>
                                            <td class="text-nowrap"><?php echo $service['service_start_time'] !== NULL ? $escape(date('g:i A', strtotime($service['service_start_time']))) : '&mdash;'; ?></td>
                                            <td>
                                                <?php echo $escape($service['service_name']); ?>
                                                <?php if ($service['service_id'] === NULL) { ?>
                                                    <span class="text-muted small">(no longer on the menu)</span>
                                                <?php } ?>
                                            </td>
                                            <td><?php echo $service['service_artist_name'] !== NULL ? $escape($service['service_artist_name']) : '&mdash;'; ?></td>
                                            <td><?php echo $service['service_duration_minutes'] !== NULL ? (int) $service['service_duration_minutes'].' min' : '&mdash;'; ?></td>
                                            <td class="text-end"><?php echo $service['service_price'] !== NULL ? $escape('from '.admin_format_price($service['service_price'])) : '&mdash;'; ?></td>
                                        </tr>
                                    <?php } ?>
                                </tbody>
                            </table>
                        </div>
                    <?php } else { ?>
                        <p class="text-muted mb-0">No services were recorded with this appointment.</p>
                    <?php } ?>
                </div>
            </div>

            <div class="card admin-card mb-4">
                <div class="card-header">
                    <h2 class="card-title mb-0">Client</h2>
                </div>
                <div class="card-body">
                    <div class="contact-request-details-grid">
                        <?php foreach ($client as $detail) { ?>
                            <div class="contact-request-detail">
                                <span class="contact-request-detail-icon" aria-hidden="true"><i class="bi <?php echo $detail[2]; ?>"></i></span>
                                <div>
                                    <span class="contact-request-detail-label"><?php echo $escape($detail[0]); ?></span>
                                    <span class="contact-request-detail-value">
                                        <?php if ($detail[0] === 'Email') { ?>
                                            <a href="mailto:<?php echo $escape($detail[1]); ?>"><?php echo $escape($detail[1]); ?></a>
                                        <?php } elseif ($detail[0] === 'Phone' && $record['customer_phone']) { ?>
                                            <a href="tel:<?php echo $escape(preg_replace('/[^\d+]/', '', $detail[1])); ?>"><?php echo $escape($detail[1]); ?></a>
                                        <?php } else { ?>
                                            <?php echo $escape($detail[1]); ?>
                                        <?php } ?>
                                    </span>
                                </div>
                            </div>
                        <?php } ?>
                        <?php if (trim((string) $record['customer_notes']) !== '') { ?>
                            <div class="contact-request-detail contact-request-detail-wide">
                                <span class="contact-request-detail-icon" aria-hidden="true"><i class="bi bi-chat-left-text"></i></span>
                                <div>
                                    <span class="contact-request-detail-label">Client's note</span>
                                    <span class="contact-request-detail-value"><?php echo $escape($record['customer_notes']); ?></span>
                                </div>
                            </div>
                        <?php } ?>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-lg-4">
            <div class="card admin-card mb-4">
                <div class="card-header">
                    <h2 class="card-title mb-0">Status</h2>
                </div>
                <div class="card-body">
                    <form method="post" action="<?php echo base_url('manage/'.$this->controller.'/updateStatus/'.$id); ?>">
                        <?php if ($this->config->item('csrf_protection')) { ?>
                            <input type="hidden" name="<?php echo $escape($this->security->get_csrf_token_name()); ?>" value="<?php echo $escape($this->security->get_csrf_hash()); ?>">
                        <?php } ?>
                        <div class="admin-field mb-3">
                            <label class="form-label" for="status">Request status</label>
                            <select class="form-select select2" name="status" id="status" data-minimum-results-for-search="-1">
                                <?php foreach ($status_options as $statusOption) { ?>
                                    <option value="<?php echo $statusOption; ?>" <?php echo $currentStatus === $statusOption ? 'selected' : ''; ?>><?php echo $statusOption; ?></option>
                                <?php } ?>
                            </select>
                            <div class="form-text">Online bookings arrive Confirmed. Changing the status to Confirmed or Cancelled emails the client, and Cancelled frees the artists' time for online booking.</div>
                        </div>
                        <button type="submit" class="btn btn-primary w-100">Update Status</button>
                    </form>
                </div>
            </div>

            <div class="card admin-card mb-4">
                <div class="card-header">
                    <h2 class="card-title mb-0">Internal notes</h2>
                </div>
                <div class="card-body">
                    <form method="post" action="<?php echo base_url('manage/'.$this->controller.'/addNote/'.$id); ?>" class="mb-3">
                        <?php if ($this->config->item('csrf_protection')) { ?>
                            <input type="hidden" name="<?php echo $escape($this->security->get_csrf_token_name()); ?>" value="<?php echo $escape($this->security->get_csrf_hash()); ?>">
                        <?php } ?>
                        <div class="admin-field mb-2">
                            <label class="form-label" for="note">Add a note</label>
                            <textarea class="form-control" name="note" id="note" rows="3" maxlength="5000" required><?php echo $escape($note_draft); ?></textarea>
                            <div class="form-text">Only administrators can see notes.</div>
                        </div>
                        <button type="submit" class="btn btn-outline-primary w-100">Add Note</button>
                    </form>

                    <?php if (!empty($notes)) { ?>
                        <ul class="list-unstyled mb-0">
                            <?php foreach ($notes as $note) { ?>
                                <li class="border-top pt-2 mt-2">
                                    <div class="d-flex justify-content-between align-items-start gap-2">
                                        <small class="text-muted">
                                            <?php echo $escape($note['full_name'] !== NULL ? $note['full_name'] : 'Former administrator'); ?>
                                            &middot;
                                            <time datetime="<?php echo date('c', strtotime($note['created_at'])); ?>"><?php echo date(ADMIN_DATETIME_FORMAT, strtotime($note['created_at'])); ?></time>
                                        </small>
                                        <?php if ((int) $note['author_id'] === $current_admin_id) { ?>
                                            <form method="post" action="<?php echo base_url('manage/'.$this->controller.'/deleteNote/'.$id.'/'.(int) $note['note_id']); ?>">
                                                <?php if ($this->config->item('csrf_protection')) { ?>
                                                    <input type="hidden" name="<?php echo $escape($this->security->get_csrf_token_name()); ?>" value="<?php echo $escape($this->security->get_csrf_hash()); ?>">
                                                <?php } ?>
                                                <button type="submit" class="btn btn-link btn-sm text-danger p-0" aria-label="Delete this note">
                                                    <i class="bi bi-trash" aria-hidden="true"></i>
                                                </button>
                                            </form>
                                        <?php } ?>
                                    </div>
                                    <p class="admin-note-text mb-0 mt-1"><?php echo $escape($note['note']); ?></p>
                                </li>
                            <?php } ?>
                        </ul>
                    <?php } else { ?>
                        <p class="text-muted small mb-0">No notes yet.</p>
                    <?php } ?>
                </div>
            </div>
        </div>
    </div>
</section>
