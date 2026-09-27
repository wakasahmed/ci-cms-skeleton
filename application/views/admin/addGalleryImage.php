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
$imageFile = isset($tbl_data['image_file']) ? basename((string) $tbl_data['image_file']) : '';
$selectedCategory = isset($tbl_data['image_category_id']) ? (int) $tbl_data['image_category_id'] : 0;
$selectedService = isset($tbl_data['image_service_id']) ? (int) $tbl_data['image_service_id'] : 0;
$currentStatus = isset($tbl_data[$this->tStatus]) ? $tbl_data[$this->tStatus] : 'Enable';
$isFeatured = isset($tbl_data['image_featured']) && (string) $tbl_data['image_featured'] === '1';
?>
<?php $this->load->view('admin/partials/breadcrumb', array(
    'items' => array(
        array('label' => $this->moduleName, 'url' => ADMIN_URL.$this->controller),
        array('label' => $crumb.' Image', 'active' => TRUE),
    ),
)); ?>

<?php if (!empty($form_error)) { ?>
    <div class="row alertrow">
        <div class="col-md-12">
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <strong>Could not save the image.</strong>
                <?php echo htmlspecialchars($form_error, ENT_QUOTES, 'UTF-8'); ?>
                <button type="button" class="btn-close alertBox crud-alert-close" data-bs-dismiss="alert" aria-label="Dismiss notification"></button>
            </div>
        </div>
    </div>
<?php } ?>

<section class="admin-records-listing" aria-labelledby="gallery-title">
    <?php $this->load->view('admin/partials/module_header', array(
        'title' => $this->moduleName,
        'description' => $this->moduleDesc,
        'id' => 'gallery-title',
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

                <div class="admin-field mb-4<?php echo in_array('image_upload', $invalid_fields, TRUE) ? ' validate-has-error' : ''; ?>">
                    <?php $this->load->view('admin/partials/file_upload', array(
                        'name' => 'image_upload',
                        'id' => 'image_upload',
                        'label' => $isEdit ? 'Replace Image (Optional)' : 'Image',
                        'allowed_types' => UPLOAD_IMAGE_MIMES,
                        'required' => !$isEdit,
                        'current_path' => $imageFile !== '' ? $image_directory.'/'.$imageFile : '',
                        'recommended_size' => '1600px on the longest side',
                        'size_note' => 'Portrait and square photos both work; the gallery keeps each photo\'s shape.',
                        'preview_alt' => 'Current image',
                        'preview_size' => 220,
                    )); ?>
                </div>

                <div class="admin-field mb-3">
                    <label class="form-label is-required" for="image_caption">Caption</label>
                    <input
                        type="text"
                        name="image_caption"
                        id="image_caption"
                        maxlength="255"
                        value="<?php echo $value('image_caption'); ?>"
                        class="form-control<?php echo $invalidClass('image_caption'); ?>"
                        data-validate="required,maxlength[255]"
                        placeholder="Almond shape in deep plum"
                        required
                    >
                    <div class="form-text">Shown under the photo and read out by screen readers, so describe what the photo shows.</div>
                </div>

                <div class="row">
                    <div class="admin-field mb-3 col-md-6">
                        <label class="form-label" for="image_category_id">Category</label>
                        <select class="form-select select2" name="image_category_id" id="image_category_id" data-minimum-results-for-search="-1">
                            <option value="">Uncategorised</option>
                            <?php foreach ($categories as $categoryId => $category) { ?>
                                <option value="<?php echo (int) $categoryId; ?>" <?php echo $selectedCategory === (int) $categoryId ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($category['category_name'], ENT_QUOTES, 'UTF-8'); ?>
                                </option>
                            <?php } ?>
                        </select>
                    </div>
                    <div class="admin-field mb-3 col-md-6">
                        <label class="form-label" for="image_service_id">Related Service</label>
                        <select class="form-select select2" name="image_service_id" id="image_service_id">
                            <option value="">None</option>
                            <?php foreach ($services as $serviceId => $service) { ?>
                                <option value="<?php echo (int) $serviceId; ?>" <?php echo $selectedService === (int) $serviceId ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($service['service_name'], ENT_QUOTES, 'UTF-8'); ?>
                                </option>
                            <?php } ?>
                        </select>
                        <div class="form-text">Shows this photo on that service's page.</div>
                    </div>
                </div>

                <div class="row">
                    <div class="admin-field mb-3 col-md-6">
                        <label class="form-label" for="image_status">Status</label>
                        <select class="form-select select2" name="image_status" id="image_status" data-minimum-results-for-search="-1">
                            <option value="Enable" <?php echo $currentStatus !== 'Disable' ? 'selected' : ''; ?>>Enable</option>
                            <option value="Disable" <?php echo $currentStatus === 'Disable' ? 'selected' : ''; ?>>Disable</option>
                        </select>
                    </div>
                    <div class="admin-field mb-3 col-md-6 d-flex align-items-end">
                        <div class="form-check">
                            <input type="hidden" name="image_featured" value="0">
                            <input type="checkbox" name="image_featured" id="image_featured" value="1" class="form-check-input" <?php echo $isFeatured ? 'checked' : ''; ?>>
                            <label class="form-check-label" for="image_featured">Show on the home page</label>
                        </div>
                    </div>
                </div>

                <div class="admin-form-actions">
                    <a class="btn btn-outline-secondary" href="<?php echo ADMIN_URL.$this->controller; ?>">Cancel</a>
                    <button type="submit" class="btn btn-primary" data-save-button>
                        <span data-save-label>Save Image</span>
                    </button>
                </div>
            </form>
        </div>
    </div>
</section>
