<?php
$isEdit = ($alert === 'edit' && !empty($tbl_data[$this->pKey]));
$recordId = $isEdit ? (int) $tbl_data[$this->pKey] : 0;
$crumb = $isEdit ? 'Edit' : 'Add';
$action = base_url(
    'manage/'.$this->controller.'/'.($isEdit ? 'editRecord/'.$recordId : 'addRecord')
);
$value = function ($key) use ($tbl_data) {
    return isset($tbl_data[$key])
        ? htmlspecialchars((string) $tbl_data[$key], ENT_QUOTES, 'UTF-8')
        : '';
};
$invalidClass = function ($key) use ($invalid_fields) {
    return in_array($key, $invalid_fields, TRUE) ? ' is-invalid' : '';
};
$imageFile = isset($tbl_data['artist_image']) ? basename((string) $tbl_data['artist_image']) : '';
$imagePath = $imageFile !== '' ? $image_directory.'/'.$imageFile : '';
$artistName = isset($tbl_data['artist_name']) && trim((string) $tbl_data['artist_name']) !== ''
    ? trim((string) $tbl_data['artist_name'])
    : 'Artist';
$currentStatus = isset($tbl_data[$this->tStatus]) ? $tbl_data[$this->tStatus] : 'Enable';
$isPlaceholder = isset($tbl_data['artist_is_placeholder']) && (string) $tbl_data['artist_is_placeholder'] === '1';

// Group services by category for the select's <optgroup>s.
$serviceGroups = array();

foreach ($services as $service) {
    $group = $service['category_name'] !== NULL ? $service['category_name'] : 'Uncategorised';
    $serviceGroups[$group][] = $service;
}

$escape = function ($text) {
    return htmlspecialchars((string) $text, ENT_QUOTES, 'UTF-8');
};

// One time-off row; also rendered empty inside the repeatable <template>.
$timeOffRow = function (array $row) use ($escape) {
    $field = function ($key) use ($row, $escape) {
        return isset($row[$key]) ? $escape($row[$key]) : '';
    };
    ?>
    <div class="admin-repeatable-row admin-time-off-row" data-repeatable-row>
        <input type="text" name="time_off_start_date[]" value="<?php echo $field('time_off_start_date'); ?>" class="form-control datepicker" placeholder="First day" autocomplete="off" data-repeatable-aria="first day">
        <input type="text" name="time_off_end_date[]" value="<?php echo $field('time_off_end_date'); ?>" class="form-control datepicker" placeholder="Last day (if longer)" autocomplete="off" data-repeatable-aria="last day, optional">
        <input type="text" name="time_off_start_time[]" value="<?php echo $field('time_off_start_time'); ?>" class="form-control timepicker" data-minute-step="15" placeholder="From (part of day)" autocomplete="off" data-repeatable-aria="from time, optional">
        <input type="text" name="time_off_end_time[]" value="<?php echo $field('time_off_end_time'); ?>" class="form-control timepicker" data-minute-step="15" placeholder="Until" autocomplete="off" data-repeatable-aria="until time, optional">
        <input type="text" name="time_off_note[]" value="<?php echo $field('time_off_note'); ?>" maxlength="160" class="form-control" placeholder="Note (staff only)" data-repeatable-aria="note">
        <button type="button" class="btn btn-outline-danger" data-repeatable-remove>
            <i class="bi bi-trash" aria-hidden="true"></i>
            <span class="visually-hidden">Remove</span>
        </button>
    </div>
    <?php
};
?>
<?php $this->load->view('admin/partials/breadcrumb', array(
    'items' => array(
        array('label' => $this->moduleName, 'url' => ADMIN_URL.$this->controller),
        array('label' => $crumb.' '.$this->moduleNameSingular, 'active' => TRUE),
    ),
)); ?>

<?php if (!empty($form_error)) { ?>
    <div class="row alertrow">
        <div class="col-md-12">
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <strong>Could not save the artist.</strong>
                <?php echo htmlspecialchars($form_error, ENT_QUOTES, 'UTF-8'); ?>
                <button type="button" class="btn-close alertBox crud-alert-close" data-bs-dismiss="alert" aria-label="Dismiss notification"></button>
            </div>
        </div>
    </div>
<?php } ?>

<section class="admin-records-listing" aria-labelledby="artists-title">
    <?php $this->load->view('admin/partials/module_header', array(
        'title' => $this->moduleName,
        'description' => $this->moduleDesc,
        'id' => 'artists-title',
    )); ?>

    <div class="card admin-card">
        <div class="card-header">
            <h2 class="card-title mb-0"><?php echo $crumb; ?> <?php echo htmlspecialchars($this->moduleNameSingular, ENT_QUOTES, 'UTF-8'); ?></h2>
        </div>
        <div class="card-body">
            <form
                id="<?php echo $this->controller; ?>_form"
                name="<?php echo $this->controller; ?>_form"
                method="post"
                action="<?php echo $action; ?>"
                enctype="multipart/form-data"
                class="validate"
                data-submit-lock
            >
                <?php if ($this->config->item('csrf_protection')) { ?>
                    <input type="hidden" name="<?php echo htmlspecialchars($this->security->get_csrf_token_name(), ENT_QUOTES, 'UTF-8'); ?>" value="<?php echo htmlspecialchars($this->security->get_csrf_hash(), ENT_QUOTES, 'UTF-8'); ?>">
                <?php } ?>

                <div class="row">
                    <div class="admin-field mb-3 col-md-6">
                        <label class="form-label is-required" for="artist_name">Name</label>
                        <input
                            type="text"
                            name="artist_name"
                            id="artist_name"
                            maxlength="120"
                            value="<?php echo $value('artist_name'); ?>"
                            class="form-control<?php echo $isEdit ? '' : ' auto-slug-source'; ?><?php echo $invalidClass('artist_name'); ?>"
                            data-validate="required,maxlength[120]"
                            <?php if (!$isEdit) { ?>data-slug-target="#artist_slug"<?php } ?>
                            placeholder="Ewa Mazur"
                            required
                        >
                    </div>
                    <div class="admin-field mb-3 col-md-6">
                        <label class="form-label" for="artist_role">Role</label>
                        <input
                            type="text"
                            name="artist_role"
                            id="artist_role"
                            maxlength="120"
                            value="<?php echo $value('artist_role'); ?>"
                            class="form-control"
                            placeholder="Owner · Nail stylist"
                        >
                    </div>
                </div>

                <div class="admin-field mb-3">
                    <label class="form-label is-required" for="artist_slug">URL Slug</label>
                    <div class="input-group">
                        <span class="input-group-text">/artists/</span>
                        <input
                            type="text"
                            name="artist_slug"
                            id="artist_slug"
                            maxlength="130"
                            value="<?php echo $value('artist_slug'); ?>"
                            class="form-control slug<?php echo $invalidClass('artist_slug'); ?>"
                            data-validate="required,maxlength[130]"
                            required
                        >
                        <button type="button" class="btn btn-outline-secondary generate-slug" data-slug-source="#artist_name" data-slug-target="#artist_slug">
                            <i class="bi bi-arrow-clockwise" aria-hidden="true"></i>
                            <span class="d-none d-sm-inline">Generate</span>
                        </button>
                    </div>
                    <div class="form-text">Also used to pre-select this artist on the booking form. Made unique automatically when saved.</div>
                </div>

                <div class="admin-field mb-3">
                    <label class="form-label" for="artist_bio">Biography</label>
                    <textarea rows="5" name="artist_bio" id="artist_bio" maxlength="5000" class="form-control"><?php echo $value('artist_bio'); ?></textarea>
                </div>

                <div class="row">
                    <div class="admin-field mb-3 col-md-6">
                        <label class="form-label" for="artist_specialties">Specialties</label>
                        <textarea rows="4" name="artist_specialties" id="artist_specialties" class="form-control" placeholder="Manicure &amp; Nail Art"><?php echo $value('artist_specialties'); ?></textarea>
                        <div class="form-text">One per line. Shown as tags on the artist's card.</div>
                    </div>
                    <div class="admin-field mb-3 col-md-6">
                        <label class="form-label" for="services">Services Offered</label>
                        <select class="form-select select2" name="services[]" id="services" multiple data-placeholder="Select services">
                            <?php foreach ($serviceGroups as $groupName => $groupServices) { ?>
                                <optgroup label="<?php echo htmlspecialchars($groupName, ENT_QUOTES, 'UTF-8'); ?>">
                                    <?php foreach ($groupServices as $service) { ?>
                                        <option value="<?php echo (int) $service['service_id']; ?>" <?php echo in_array((int) $service['service_id'], $assigned_services, TRUE) ? 'selected' : ''; ?>>
                                            <?php echo htmlspecialchars($service['service_name'], ENT_QUOTES, 'UTF-8'); ?><?php echo $service['service_status'] === 'Disable' ? ' (disabled)' : ''; ?>
                                        </option>
                                    <?php } ?>
                                </optgroup>
                            <?php } ?>
                        </select>
                        <div class="form-text">Only these services can be booked with this artist.</div>
                    </div>
                </div>

                <fieldset class="admin-form-choice-group mb-3">
                    <legend>Working Hours</legend>
                    <div class="admin-hours-grid">
                        <span class="admin-hours-head" aria-hidden="true">Day</span>
                        <span class="admin-hours-head" aria-hidden="true">From</span>
                        <span class="admin-hours-head" aria-hidden="true">Until</span>
                        <span class="admin-hours-head" aria-hidden="true">Salon hours</span>
                        <?php foreach ($days as $dayKey => $dayLabel) {
                            $dayHours = isset($hours[$dayKey]) ? $hours[$dayKey] : NULL;
                            $salonDay = isset($salon_hours[$dayKey]) ? $salon_hours[$dayKey] : NULL;
                            ?>
                            <div class="form-check">
                                <input
                                    type="checkbox"
                                    name="working_days[]"
                                    id="working_day_<?php echo $dayKey; ?>"
                                    value="<?php echo $dayKey; ?>"
                                    class="form-check-input"
                                    <?php echo $dayHours !== NULL ? 'checked' : ''; ?>
                                >
                                <label class="form-check-label" for="working_day_<?php echo $dayKey; ?>"><?php echo $dayLabel; ?></label>
                            </div>
                            <input
                                type="text"
                                name="hours_start[<?php echo $dayKey; ?>]"
                                id="hours_start_<?php echo $dayKey; ?>"
                                value="<?php echo $dayHours !== NULL ? $escape($dayHours['start']) : ''; ?>"
                                class="form-control form-control-sm timepicker<?php echo $invalidClass('hours_start_'.$dayKey); ?>"
                                data-minute-step="15"
                                placeholder="Opening"
                                autocomplete="off"
                                aria-label="<?php echo $dayLabel; ?> from, optional"
                            >
                            <input
                                type="text"
                                name="hours_end[<?php echo $dayKey; ?>]"
                                id="hours_end_<?php echo $dayKey; ?>"
                                value="<?php echo $dayHours !== NULL ? $escape($dayHours['end']) : ''; ?>"
                                class="form-control form-control-sm timepicker<?php echo $invalidClass('hours_end_'.$dayKey); ?>"
                                data-minute-step="15"
                                placeholder="Closing"
                                autocomplete="off"
                                aria-label="<?php echo $dayLabel; ?> until, optional"
                            >
                            <span class="admin-hours-salon"><?php echo $salonDay !== NULL ? $escape($salonDay) : 'Closed'; ?></span>
                        <?php } ?>
                    </div>
                    <div class="form-text">
                        Tick the days this artist works. Leave the times empty to follow the salon's hours, or narrow the day
                        (for example from 12:00 PM). Online booking offers only these times; leave every day unticked to take
                        bookings whenever the salon is open. The ticked days are also listed on the artist's profile.
                    </div>
                </fieldset>

                <fieldset
                    class="admin-form-choice-group mb-3<?php echo in_array('time_off', $invalid_fields, TRUE) ? ' is-invalid' : ''; ?>"
                    data-repeatable
                    data-repeatable-max="60"
                    data-repeatable-label="Time off"
                >
                    <legend>Time Off</legend>
                    <div class="admin-repeatable-list" data-repeatable-list><?php foreach ($time_off as $row) {
                        $timeOffRow($row);
                    } ?></div>
                    <p class="admin-repeatable-empty" data-repeatable-empty>No time off planned.</p>
                    <template data-repeatable-template><?php $timeOffRow(array()); ?></template>
                    <button type="button" class="btn btn-outline-primary btn-sm" data-repeatable-add>
                        <i class="bi bi-plus-lg" aria-hidden="true"></i> Add time off
                    </button>
                    <div class="form-text">
                        Holidays, training days or appointments. Leave the times empty for whole days; with times, the same
                        hours are blocked on each day of the range. Online booking skips this time; existing appointments
                        are not changed.
                    </div>
                </fieldset>

                <hr>

                <div class="admin-field mb-4">
                    <?php $this->load->view('admin/partials/file_upload', array(
                        'name' => 'image_upload',
                        'id' => 'image_upload',
                        'label' => 'Photo (Optional)',
                        'allowed_types' => UPLOAD_IMAGE_MIMES,
                        'required' => FALSE,
                        'current_path' => $imagePath,
                        'recommended_size' => '1200 × 1500px (4:5)',
                        'size_note' => 'Shown as a portrait on the team pages. Keep the face near the top-centre.',
                        'preview_alt' => $artistName.' photo',
                        'preview_size' => 160,
                    )); ?>
                </div>
                <?php if ($isEdit && image_thumb_path($imagePath) !== FALSE) { ?>
                    <div class="admin-current-file-remove">
                        <div class="admin-current-file-actions">
                            <button type="button" class="btn btn-outline-danger btn-sm removeFile" data-controller="<?php echo $this->controller; ?>" data-file-name="artist_image" data-file-id="<?php echo $recordId; ?>">
                                <i class="bi bi-trash" aria-hidden="true"></i> Remove Photo
                            </button>
                        </div>
                    </div>
                <?php } ?>

                <div class="row">
                    <div class="admin-field mb-3 col-md-6">
                        <label class="form-label" for="artist_status">Status</label>
                        <select class="form-select select2" name="artist_status" id="artist_status" data-minimum-results-for-search="-1">
                            <option value="Enable" <?php echo $currentStatus !== 'Disable' ? 'selected' : ''; ?>>Enable</option>
                            <option value="Disable" <?php echo $currentStatus === 'Disable' ? 'selected' : ''; ?>>Disable</option>
                        </select>
                    </div>
                    <div class="admin-field mb-3 col-md-6 d-flex align-items-end">
                        <div class="form-check">
                            <input type="hidden" name="artist_is_placeholder" value="0">
                            <input type="checkbox" name="artist_is_placeholder" id="artist_is_placeholder" value="1" class="form-check-input" <?php echo $isPlaceholder ? 'checked' : ''; ?>>
                            <label class="form-check-label" for="artist_is_placeholder">Placeholder profile (real details still to come)</label>
                        </div>
                    </div>
                </div>

                <div class="admin-form-actions">
                    <a class="btn btn-outline-secondary" href="<?php echo ADMIN_URL.$this->controller; ?>">Cancel</a>
                    <button type="submit" class="btn btn-primary" data-save-button>
                        <span data-save-label>Save <?php echo htmlspecialchars($this->moduleNameSingular, ENT_QUOTES, 'UTF-8'); ?></span>
                    </button>
                </div>
            </form>
        </div>
    </div>
</section>
