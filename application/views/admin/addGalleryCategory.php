<?php
$isEdit = ($alert === 'edit' && !empty($tbl_data[$this->pKey]));
$crumb = $isEdit ? 'Edit' : 'Add';
$action = base_url(
    'manage/'.$this->controller.'/'.($isEdit ? 'editRecord/'.(int) $tbl_data[$this->pKey] : 'addRecord')
);
$value = function ($key) use ($tbl_data) {
    return isset($tbl_data[$key])
        ? htmlspecialchars((string) $tbl_data[$key], ENT_QUOTES, 'UTF-8')
        : '';
};
$invalidClass = function ($key) use ($invalid_fields) {
    return in_array($key, $invalid_fields, TRUE) ? ' is-invalid' : '';
};
$currentStatus = isset($tbl_data[$this->tStatus]) ? $tbl_data[$this->tStatus] : 'Enable';
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
                <strong>Could not save the gallery category.</strong>
                <?php echo htmlspecialchars($form_error, ENT_QUOTES, 'UTF-8'); ?>
                <button type="button" class="btn-close alertBox crud-alert-close" data-bs-dismiss="alert" aria-label="Dismiss notification"></button>
            </div>
        </div>
    </div>
<?php } ?>

<section class="admin-records-listing" aria-labelledby="gallery-categories-title">
    <?php $this->load->view('admin/partials/module_header', array(
        'title' => $this->moduleName,
        'description' => $this->moduleDesc,
        'id' => 'gallery-categories-title',
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
                class="validate"
                data-submit-lock
            >
                <?php if ($this->config->item('csrf_protection')) { ?>
                    <input type="hidden" name="<?php echo htmlspecialchars($this->security->get_csrf_token_name(), ENT_QUOTES, 'UTF-8'); ?>" value="<?php echo htmlspecialchars($this->security->get_csrf_hash(), ENT_QUOTES, 'UTF-8'); ?>">
                <?php } ?>

                <div class="row">
                    <div class="admin-field mb-3 col-md-6">
                        <label class="form-label is-required" for="category_name">Name</label>
                        <input
                            type="text"
                            name="category_name"
                            id="category_name"
                            maxlength="100"
                            value="<?php echo $value('category_name'); ?>"
                            class="form-control<?php echo $isEdit ? '' : ' auto-slug-source'; ?><?php echo $invalidClass('category_name'); ?>"
                            data-validate="required,maxlength[100]"
                            <?php if (!$isEdit) { ?>data-slug-target="#category_slug"<?php } ?>
                            placeholder="Nail Art"
                            required
                        >
                        <div class="form-text">Shown as a filter button on the gallery page.</div>
                    </div>
                    <div class="admin-field mb-3 col-md-6">
                        <label class="form-label is-required" for="category_slug">URL Slug</label>
                        <div class="input-group">
                            <input
                                type="text"
                                name="category_slug"
                                id="category_slug"
                                maxlength="110"
                                value="<?php echo $value('category_slug'); ?>"
                                class="form-control slug<?php echo $invalidClass('category_slug'); ?>"
                                data-validate="required,maxlength[110]"
                                required
                            >
                            <button type="button" class="btn btn-outline-secondary generate-slug" data-slug-source="#category_name" data-slug-target="#category_slug">
                                <i class="bi bi-arrow-clockwise" aria-hidden="true"></i>
                                <span class="d-none d-sm-inline">Generate</span>
                            </button>
                        </div>
                        <div class="form-text">Used in gallery filter links. Made unique automatically when saved.</div>
                    </div>
                </div>


                <div class="row">
                    <div class="admin-field mb-3 col-md-6">
                        <label class="form-label" for="category_status">Status</label>
                        <select class="form-select select2" name="category_status" id="category_status" data-minimum-results-for-search="-1">
                            <option value="Enable" <?php echo $currentStatus !== 'Disable' ? 'selected' : ''; ?>>Enable</option>
                            <option value="Disable" <?php echo $currentStatus === 'Disable' ? 'selected' : ''; ?>>Disable</option>
                        </select>
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
