<?php
$isEdit = ($alert === 'edit' && !empty($tbl_data[$this->pKey]));
$crumb = $isEdit ? 'Edit' : 'Add';
$recordID = $isEdit ? (int) $tbl_data[$this->pKey] : 0;
$action = base_url('manage/'.$this->controller.'/'.($isEdit ? 'editRecord/'.$recordID : 'addRecord'));
$fieldValue = function ($key) use ($tbl_data) { return isset($tbl_data[$key]) ? htmlspecialchars((string) $tbl_data[$key], ENT_QUOTES, 'UTF-8') : ''; };
$image = isset($tbl_data[$this->colPrefix.'image']) ? basename((string) $tbl_data[$this->colPrefix.'image']) : '';
$name = isset($tbl_data[$this->colPrefix.'name']) ? trim((string) $tbl_data[$this->colPrefix.'name']) : 'Customer';
$rating = isset($tbl_data[$this->colPrefix.'rating']) ? max(1, min(5, (int) $tbl_data[$this->colPrefix.'rating'])) : 5;
?>
<?php $this->load->view('admin/partials/breadcrumb', array('items' => array(array('label' => $this->moduleName, 'url' => ADMIN_URL.$this->controller), array('label' => $crumb.' '.$this->moduleNameSingular, 'active' => TRUE)))); ?>

<?php if (!empty($form_error)) { ?><div class="row alertrow"><div class="col-md-12"><div class="alert alert-danger alert-dismissible fade show" role="alert"><strong>Could not save the customer review.</strong> <?php echo htmlspecialchars($form_error, ENT_QUOTES, 'UTF-8'); ?><button type="button" class="btn-close alertBox crud-alert-close" data-bs-dismiss="alert" aria-label="Dismiss notification"></button></div></div></div><?php } ?>

<section class="admin-records-listing" aria-labelledby="customer-reviews-title">
    <?php $this->load->view('admin/partials/module_header', array('title' => $this->moduleName, 'description' => $this->moduleDesc, 'id' => 'customer-reviews-title')); ?>
    <div class="card admin-card">
        <div class="card-header d-flex flex-wrap align-items-center justify-content-between gap-3"><h2 class="card-title mb-0"><?php echo $crumb; ?> <?php echo htmlspecialchars($this->moduleNameSingular, ENT_QUOTES, 'UTF-8'); ?></h2></div>
        <div class="card-body">
            <form id="customer_reviews_form" name="customer_reviews_form" method="post" action="<?php echo $action; ?>" enctype="multipart/form-data" class="validate" data-submit-lock>
                <?php if ($this->config->item('csrf_protection')) { ?><input type="hidden" name="<?php echo htmlspecialchars($this->security->get_csrf_token_name(), ENT_QUOTES, 'UTF-8'); ?>" value="<?php echo htmlspecialchars($this->security->get_csrf_hash(), ENT_QUOTES, 'UTF-8'); ?>"><?php } ?>

                <div class="admin-field mb-3"><label class="form-label is-required" for="review_name">Customer Name </label><input type="text" name="review_name" id="review_name" maxlength="100" value="<?php echo $fieldValue('review_name'); ?>" class="form-control" data-validate="required,maxlength[100]" required placeholder="Omer Bin Khalid"></div>

                <div class="admin-field mb-3"><label class="form-label is-required" for="review_desc">Review </label><textarea rows="6" name="review_desc" id="review_desc" class="form-control" data-validate="required" required placeholder="Write the customer feedback shown on the website"><?php echo $fieldValue('review_desc'); ?></textarea></div>

                <?php $this->load->view('admin/partials/star_rating', array(
                    'name' => $this->colPrefix.'rating', 'label' => 'Rating', 'max' => 5, 'value' => $rating,
                    'required' => TRUE, 'hint' => 'Choose the star rating displayed with this review.', 'empty_text' => 'No rating selected',
                )); ?>

                <?php $this->load->view('admin/partials/avatar_upload', array(
                    'field_name' => 'uploadfile', 'label' => 'Customer Picture (Optional)',
                    'current_path' => $image !== '' ? 'assets/frontend/images/customer-reviews/'.$image : '',
                    'fallback' => $name, 'required' => FALSE, 'allow_remove' => TRUE,
                    'remove_name' => 'remove_review_image', 'crop_title' => 'Adjust customer picture',
                    'apply_label' => 'Use this picture',
                )); ?>

                <div class="admin-field mb-3"><label class="form-label" for="review_status">Status</label><select class="form-select select2" name="review_status" id="review_status" data-minimum-results-for-search="-1"><option value="Enable" <?php echo (!isset($tbl_data['review_status']) || $tbl_data['review_status'] === 'Enable') ? 'selected' : ''; ?>>Enable</option><option value="Disable" <?php echo (isset($tbl_data['review_status']) && $tbl_data['review_status'] === 'Disable') ? 'selected' : ''; ?>>Disable</option></select></div>

                <div class="admin-form-actions"><a class="btn btn-outline-secondary" href="<?php echo ADMIN_URL.$this->controller; ?>">Cancel</a><button type="submit" class="btn btn-primary" data-save-button><span data-save-label>Save <?php echo htmlspecialchars($this->moduleNameSingular, ENT_QUOTES, 'UTF-8'); ?></span></button></div>
            </form>
        </div>
    </div>
</section>
