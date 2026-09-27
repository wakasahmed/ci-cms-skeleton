<?php
$isEdit = ($alert === 'edit' && !empty($tbl_data[$this->pKey]));
$crumb = $isEdit ? 'Edit' : 'Add';
$recordID = $isEdit ? (int) $tbl_data[$this->pKey] : 0;
$action = base_url('manage/'.$this->controller.'/'.($isEdit ? 'editRecord/'.$recordID : 'addRecord'));
$value = function ($key) use ($tbl_data) { return isset($tbl_data[$key]) ? htmlspecialchars((string) $tbl_data[$key], ENT_QUOTES, 'UTF-8') : ''; };
$status = isset($tbl_data[$this->tStatus]) && $tbl_data[$this->tStatus] === 'Disable' ? 'Disable' : 'Enable';
?>
<?php $this->load->view('admin/partials/breadcrumb', array('items' => array(array('label' => $this->moduleName, 'url' => ADMIN_URL.$this->controller), array('label' => $crumb.' '.$this->moduleNameSingular, 'active' => TRUE)))); ?>

<?php if (!empty($form_error)) { ?><div class="row alertrow"><div class="col-md-12"><div class="alert alert-danger alert-dismissible fade show" role="alert"><strong>Could not save the image slider.</strong> <?php echo htmlspecialchars($form_error, ENT_QUOTES, 'UTF-8'); ?><button type="button" class="btn-close alertBox crud-alert-close" data-bs-dismiss="alert" aria-label="Dismiss notification"></button></div></div></div><?php } ?>

<section class="admin-records-listing" aria-labelledby="slider-form-title">
    <?php $this->load->view('admin/partials/module_header', array('title' => $this->moduleName, 'description' => 'Create a slider collection, then manage its images and localized slide content from the listing.', 'id' => 'slider-form-title')); ?>
    <div class="card admin-card">
        <div class="card-header"><h2 class="card-title mb-0"><?php echo $crumb; ?> <?php echo htmlspecialchars($this->moduleNameSingular, ENT_QUOTES, 'UTF-8'); ?></h2></div>
        <div class="card-body">
            <form id="sliders_form" name="sliders_form" method="post" action="<?php echo $action; ?>" class="validate" data-submit-lock>
                <?php if ($this->config->item('csrf_protection')) { ?><input type="hidden" name="<?php echo htmlspecialchars($this->security->get_csrf_token_name(), ENT_QUOTES, 'UTF-8'); ?>" value="<?php echo htmlspecialchars($this->security->get_csrf_hash(), ENT_QUOTES, 'UTF-8'); ?>"><?php } ?>
                <div class="admin-field mb-3"><label class="form-label is-required" for="sliders_title">Title</label><input type="text" name="sliders_title" id="sliders_title" maxlength="255" value="<?php echo $value($this->colPrefix.'title'); ?>" class="form-control" data-validate="required,maxlength[255]" required placeholder="Home Page Slider"><div class="form-text">Use a descriptive internal name that identifies where this slider appears.</div></div>
                <div class="admin-field mb-3"><label class="form-label" for="sliders_status">Status</label><select class="form-select select2" name="sliders_status" id="sliders_status" data-minimum-results-for-search="-1"><option value="Enable" <?php echo $status === 'Enable' ? 'selected' : ''; ?>>Enable</option><option value="Disable" <?php echo $status === 'Disable' ? 'selected' : ''; ?>>Disable</option></select></div>
                <div class="admin-form-actions"><a class="btn btn-outline-secondary" href="<?php echo ADMIN_URL.$this->controller; ?>">Cancel</a><button type="submit" class="btn btn-primary" data-save-button><span data-save-label>Save <?php echo htmlspecialchars($this->moduleNameSingular, ENT_QUOTES, 'UTF-8'); ?></span></button></div>
            </form>
        </div>
    </div>
</section>
