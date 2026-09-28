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
$priceValue = function ($key) use ($tbl_data) {
    if (!isset($tbl_data[$key]) || $tbl_data[$key] === NULL) {
        return '';
    }

    $price = (string) $tbl_data[$key];

    // Show stored "160.00" as "160", but keep typed values as entered.
    return htmlspecialchars(
        preg_match('/^\d+\.\d{2}$/', $price) ? rtrim(rtrim($price, '0'), '.') : $price,
        ENT_QUOTES,
        'UTF-8'
    );
};
$invalidClass = function ($key) use ($invalid_fields) {
    return in_array($key, $invalid_fields, TRUE) ? ' is-invalid' : '';
};
$imageFile = isset($tbl_data['offer_image']) ? basename((string) $tbl_data['offer_image']) : '';
$imagePath = $imageFile !== '' ? $image_directory.'/'.$imageFile : '';
$currentStatus = isset($tbl_data[$this->tStatus]) ? $tbl_data[$this->tStatus] : 'Enable';
$isFeatured = isset($tbl_data['offer_featured']) && (string) $tbl_data['offer_featured'] === '1';

$serviceGroups = array();

foreach ($services as $service) {
    $group = $service['category_name'] !== NULL ? $service['category_name'] : 'Uncategorised';
    $serviceGroups[$group][] = $service;
}
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
                <strong>Could not save the offer.</strong>
                <?php echo htmlspecialchars($form_error, ENT_QUOTES, 'UTF-8'); ?>
                <button type="button" class="btn-close alertBox crud-alert-close" data-bs-dismiss="alert" aria-label="Dismiss notification"></button>
            </div>
        </div>
    </div>
<?php } ?>

<section class="admin-records-listing" aria-labelledby="offers-title">
    <?php $this->load->view('admin/partials/module_header', array(
        'title' => $this->moduleName,
        'description' => $this->moduleDesc,
        'id' => 'offers-title',
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
                    <div class="admin-field mb-3 col-md-8">
                        <label class="form-label is-required" for="offer_title">Title</label>
                        <input
                            type="text"
                            name="offer_title"
                            id="offer_title"
                            maxlength="150"
                            value="<?php echo $value('offer_title'); ?>"
                            class="form-control<?php echo $isEdit ? '' : ' auto-slug-source'; ?><?php echo $invalidClass('offer_title'); ?>"
                            data-validate="required,maxlength[150]"
                            <?php if (!$isEdit) { ?>data-slug-target="#offer_slug"<?php } ?>
                            placeholder="Manicure + nail art"
                            required
                        >
                    </div>
                    <div class="admin-field mb-3 col-md-4">
                        <label class="form-label" for="offer_label">Label</label>
                        <input
                            type="text"
                            name="offer_label"
                            id="offer_label"
                            maxlength="80"
                            value="<?php echo $value('offer_label'); ?>"
                            class="form-control"
                            placeholder="Weekday mornings"
                        >
                        <div class="form-text">Short tag shown above the title.</div>
                    </div>
                </div>

                <div class="admin-field mb-3">
                    <label class="form-label is-required" for="offer_slug">URL Slug</label>
                    <div class="input-group">
                        <input
                            type="text"
                            name="offer_slug"
                            id="offer_slug"
                            maxlength="160"
                            value="<?php echo $value('offer_slug'); ?>"
                            class="form-control slug<?php echo $invalidClass('offer_slug'); ?>"
                            data-validate="required,maxlength[160]"
                            required
                        >
                        <button type="button" class="btn btn-outline-secondary generate-slug" data-slug-source="#offer_title" data-slug-target="#offer_slug">
                            <i class="bi bi-arrow-clockwise" aria-hidden="true"></i>
                            <span class="d-none d-sm-inline">Generate</span>
                        </button>
                    </div>
                    <div class="form-text">Used in "Book this offer" links. Made unique automatically when saved.</div>
                </div>

                <div class="admin-field mb-3">
                    <label class="form-label" for="offer_summary">Summary</label>
                    <textarea rows="2" name="offer_summary" id="offer_summary" maxlength="500" class="form-control" placeholder="Manicure, gel colour and hand-painted art on two nails"><?php echo $value('offer_summary'); ?></textarea>
                </div>

                <div class="admin-field mb-3">
                    <label class="form-label" for="offer_inclusions">What's Included</label>
                    <textarea rows="5" name="offer_inclusions" id="offer_inclusions" class="form-control" placeholder="Full manicure with cuticle work and shaping"><?php echo $value('offer_inclusions'); ?></textarea>
                    <div class="form-text">One item per line.</div>
                </div>

                <div class="row">
                    <div class="admin-field mb-3 col-md-4">
                        <label class="form-label is-required" for="offer_price">Offer Price</label>
                        <div class="input-group">
                            <input
                                type="text"
                                name="offer_price"
                                id="offer_price"
                                maxlength="12"
                                inputmode="decimal"
                                value="<?php echo $priceValue('offer_price'); ?>"
                                class="form-control<?php echo $invalidClass('offer_price'); ?>"
                                data-validate="required"
                                placeholder="160"
                                required
                            >
                            <span class="input-group-text">zł</span>
                        </div>
                    </div>
                    <div class="admin-field mb-3 col-md-4">
                        <label class="form-label" for="offer_old_price">Regular Price</label>
                        <div class="input-group">
                            <input
                                type="text"
                                name="offer_old_price"
                                id="offer_old_price"
                                maxlength="12"
                                inputmode="decimal"
                                value="<?php echo $priceValue('offer_old_price'); ?>"
                                class="form-control<?php echo $invalidClass('offer_old_price'); ?>"
                                placeholder="190"
                            >
                            <span class="input-group-text">zł</span>
                        </div>
                        <div class="form-text">Shown crossed out. Leave empty if there is no saving to show.</div>
                    </div>
                    <div class="admin-field mb-3 col-md-4">
                        <label class="form-label" for="offer_duration_label">Duration Shown</label>
                        <input
                            type="text"
                            name="offer_duration_label"
                            id="offer_duration_label"
                            maxlength="60"
                            value="<?php echo $value('offer_duration_label'); ?>"
                            class="form-control"
                            placeholder="about 90 min"
                        >
                    </div>
                </div>

                <div class="row">
                    <div class="admin-field mb-3 col-md-4">
                        <label class="form-label" for="offer_valid_from">Starts</label>
                        <input
                            type="text"
                            name="offer_valid_from"
                            id="offer_valid_from"
                            maxlength="10"
                            value="<?php echo $value('offer_valid_from'); ?>"
                            class="form-control datepicker<?php echo $invalidClass('offer_valid_from'); ?>"
                            placeholder="mm/dd/yyyy"
                            autocomplete="off"
                        >
                    </div>
                    <div class="admin-field mb-3 col-md-4">
                        <label class="form-label" for="offer_valid_to">Ends</label>
                        <input
                            type="text"
                            name="offer_valid_to"
                            id="offer_valid_to"
                            maxlength="10"
                            value="<?php echo $value('offer_valid_to'); ?>"
                            class="form-control datepicker<?php echo $invalidClass('offer_valid_to'); ?>"
                            placeholder="mm/dd/yyyy"
                            autocomplete="off"
                        >
                    </div>
                    <div class="admin-field mb-3 col-md-4">
                        <label class="form-label" for="offer_validity_note">Validity Note</label>
                        <input
                            type="text"
                            name="offer_validity_note"
                            id="offer_validity_note"
                            maxlength="255"
                            value="<?php echo $value('offer_validity_note'); ?>"
                            class="form-control"
                            placeholder="Monday to Friday, before noon"
                        >
                    </div>
                </div>
                <p class="form-text mt-n2 mb-3">Leave the dates empty for an offer that runs until you disable it. The website hides the offer outside its dates.</p>

                <div class="admin-field mb-3">
                    <label class="form-label" for="services">Services in This Offer</label>
                    <select class="form-select select2" name="services[]" id="services" multiple data-placeholder="Select services">
                        <?php foreach ($serviceGroups as $groupName => $groupServices) { ?>
                            <optgroup label="<?php echo htmlspecialchars($groupName, ENT_QUOTES, 'UTF-8'); ?>">
                                <?php foreach ($groupServices as $service) { ?>
                                    <option value="<?php echo (int) $service['service_id']; ?>" <?php echo in_array((int) $service['service_id'], $linked_services, TRUE) ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($service['service_name'], ENT_QUOTES, 'UTF-8'); ?><?php echo $service['service_status'] === 'Disable' ? ' (disabled)' : ''; ?>
                                    </option>
                                <?php } ?>
                            </optgroup>
                        <?php } ?>
                    </select>
                    <div class="form-text">Pre-selected on the booking form when a visitor books this offer.</div>
                </div>

                <hr>

                <div class="admin-field mb-4">
                    <?php $this->load->view('admin/partials/file_upload', array(
                        'name' => 'image_upload',
                        'id' => 'image_upload',
                        'label' => 'Image (Optional)',
                        'allowed_types' => UPLOAD_IMAGE_MIMES,
                        'required' => FALSE,
                        'current_path' => $imagePath,
                        'recommended_size' => '1200 × 900px (4:3)',
                        'size_note' => 'Shown on the offer card.',
                        'preview_alt' => 'Current offer image',
                        'preview_size' => 180,
                    )); ?>
                </div>
                <?php if ($isEdit && image_thumb_path($imagePath) !== FALSE) { ?>
                    <div class="admin-current-file-remove">
                        <div class="admin-current-file-actions">
                            <button type="button" class="btn btn-outline-danger btn-sm removeFile" data-controller="<?php echo $this->controller; ?>" data-file-name="offer_image" data-file-id="<?php echo $recordId; ?>">
                                <i class="bi bi-trash" aria-hidden="true"></i> Remove Image
                            </button>
                        </div>
                    </div>
                <?php } ?>

                <div class="row">
                    <div class="admin-field mb-3 col-md-6">
                        <label class="form-label" for="offer_status">Status</label>
                        <select class="form-select select2" name="offer_status" id="offer_status" data-minimum-results-for-search="-1">
                            <option value="Enable" <?php echo $currentStatus !== 'Disable' ? 'selected' : ''; ?>>Enable</option>
                            <option value="Disable" <?php echo $currentStatus === 'Disable' ? 'selected' : ''; ?>>Disable</option>
                        </select>
                    </div>
                    <div class="admin-field mb-3 col-md-6 d-flex align-items-end">
                        <div class="form-check">
                            <input type="hidden" name="offer_featured" value="0">
                            <input type="checkbox" name="offer_featured" id="offer_featured" value="1" class="form-check-input" <?php echo $isFeatured ? 'checked' : ''; ?>>
                            <label class="form-check-label" for="offer_featured">Feature this offer (home page and top of the offers page)</label>
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
