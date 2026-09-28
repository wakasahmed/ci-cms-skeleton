<?php
$isEdit = ($alert === 'edit' && !empty($tbl_data[$this->pKey]));
$crumb = $isEdit ? 'Edit' : 'Add';
$action = base_url('manage/'.$this->controller.'/'.($isEdit ? 'editRecord/'.(int) $tbl_data[$this->pKey] : 'addRecord'));
$fieldValue = function ($key) use ($tbl_data) { return isset($tbl_data[$key]) ? htmlspecialchars((string) $tbl_data[$key], ENT_QUOTES, 'UTF-8') : ''; };
$listUrl = base_url('manage/'.$this->controller.'?category_id='.$category_filter_id);
?>
<?php $this->load->view('admin/partials/breadcrumb', array('items' => array(
    array('label' => 'FAQs', 'url' => ADMIN_URL.'faqs-categories'),
    array('label' => $this->moduleName, 'url' => $listUrl),
    array('label' => $crumb.' FAQ', 'active' => TRUE),
))); ?>

<?php if (!empty($form_error)) { ?><div class="row alertrow"><div class="col-md-12"><div class="alert alert-danger alert-dismissible fade show" role="alert"><strong>Could not save the FAQ.</strong> <?php echo htmlspecialchars($form_error, ENT_QUOTES, 'UTF-8'); ?><button type="button" class="btn-close alertBox crud-alert-close" data-bs-dismiss="alert" aria-label="Dismiss notification"></button></div></div></div><?php } ?>

<section class="admin-records-listing" aria-labelledby="faqs-title">
    <?php $this->load->view('admin/partials/module_header', array('title' => $this->moduleName, 'description' => $this->moduleDesc, 'id' => 'faqs-title')); ?>
    <div class="card admin-card">
        <div class="card-header d-flex flex-wrap align-items-center justify-content-between gap-3"><h2 class="card-title mb-0"><?php echo $crumb; ?> <?php echo htmlspecialchars($this->moduleNameSingular, ENT_QUOTES, 'UTF-8'); ?></h2></div>
        <div class="card-body">
            <form id="<?php echo htmlspecialchars($this->controller, ENT_QUOTES, 'UTF-8'); ?>_form" name="<?php echo htmlspecialchars($this->controller, ENT_QUOTES, 'UTF-8'); ?>_form" method="post" action="<?php echo $action; ?>" class="validate">
                <input type="hidden" name="faq_cat_id" value="<?php echo (int) $category_filter_id; ?>">
                <?php if ($this->config->item('csrf_protection')) { ?><input type="hidden" name="<?php echo htmlspecialchars($this->security->get_csrf_token_name(), ENT_QUOTES, 'UTF-8'); ?>" value="<?php echo htmlspecialchars($this->security->get_csrf_hash(), ENT_QUOTES, 'UTF-8'); ?>"><?php } ?>
                <div class="admin-field mb-3"><label class="form-label is-required" for="faq_question">Question </label><input type="text" name="faq_question" id="faq_question" maxlength="255" value="<?php echo $fieldValue('faq_question'); ?>" class="form-control" data-validate="required" required placeholder="Question"></div>
                <div class="admin-field mb-3"><label class="form-label is-required" for="faq_answer">Answer </label><textarea rows="5" name="faq_answer" id="faq_answer" class="form-control" data-validate="required" required><?php echo $fieldValue('faq_answer'); ?></textarea></div>
                <div class="row">
                    <div class="admin-field mb-3 col-md-12"><label class="form-label" for="faq_status">Status</label><select class="form-select select2" name="faq_status" id="faq_status" data-minimum-results-for-search="-1"><option value="Enable" <?php echo (!isset($tbl_data['faq_status']) || $tbl_data['faq_status'] === 'Enable') ? 'selected' : ''; ?>>Enable</option><option value="Disable" <?php echo (isset($tbl_data['faq_status']) && $tbl_data['faq_status'] === 'Disable') ? 'selected' : ''; ?>>Disable</option></select></div>
                </div>
                <div class="admin-form-actions"><a class="btn btn-outline-secondary" href="<?php echo $listUrl; ?>">Cancel</a><button type="submit" class="btn btn-primary" data-save-button><span data-save-label>Save <?php echo htmlspecialchars($this->moduleNameSingular, ENT_QUOTES, 'UTF-8'); ?></span></button></div>
            </form>
        </div>
    </div>
</section>
