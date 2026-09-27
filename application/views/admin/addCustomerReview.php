<?php
$isEdit = ($alert === 'edit' && !empty($tbl_data[$this->pKey]));
$crumb = $isEdit ? 'Edit' : 'Add';
$recordID = $isEdit ? (int) $tbl_data[$this->pKey] : 0;
$action = base_url('manage/'.$this->controller.'/'.($isEdit ? 'editRecord/'.$recordID : 'addRecord'));
$localizedValue = function ($key) use ($localized_values) { return isset($localized_values[$key]) ? htmlspecialchars((string) $localized_values[$key], ENT_QUOTES, 'UTF-8') : ''; };
$image = isset($tbl_data[$this->colPrefix.'image']) ? basename((string) $tbl_data[$this->colPrefix.'image']) : '';
$name = isset($tbl_data[$this->colPrefix.'name']) ? trim((string) $tbl_data[$this->colPrefix.'name']) : 'Customer';
$rating = isset($tbl_data[$this->colPrefix.'rating']) ? max(1, min(5, (int) $tbl_data[$this->colPrefix.'rating'])) : 5;
$localeDetails = $manage_locales[$active_locale];
$translationStatus = $isEdit && !empty($translation_state['status']) ? $translation_state['status'] : 'MISSING';
$languageBaseUrl = $isEdit ? base_url('manage/'.$this->controller.'/control/edit/'.$recordID) : '';
?>
<?php $this->load->view('admin/partials/breadcrumb', array('items' => array(array('label' => $this->moduleName, 'url' => ADMIN_URL.$this->controller), array('label' => $crumb.' '.$this->moduleNameSingular, 'active' => TRUE)))); ?>

<?php if (!empty($form_error)) { ?><div class="row alertrow"><div class="col-md-12"><div class="alert alert-danger alert-dismissible fade show" role="alert"><strong>Could not save the customer review.</strong> <?php echo htmlspecialchars($form_error, ENT_QUOTES, 'UTF-8'); ?><button type="button" class="btn-close alertBox crud-alert-close" data-bs-dismiss="alert" aria-label="Dismiss notification"></button></div></div></div><?php } ?>

<section class="admin-records-listing" aria-labelledby="customer-reviews-title"<?php if ($isEdit) { ?> data-translation-poll data-module="customer_reviews" data-status-url="<?php echo base_url('manage/translations/statuses'); ?>" data-poll-seconds="4" data-locale="<?php echo htmlspecialchars($active_locale, ENT_QUOTES, 'UTF-8'); ?>"<?php } ?>>
    <?php $this->load->view('admin/partials/module_header', array('title' => $this->moduleName, 'description' => $this->moduleDesc, 'id' => 'customer-reviews-title')); ?>
    <div class="card admin-card">
        <div class="card-header d-flex flex-wrap align-items-center justify-content-between gap-3"><h2 class="card-title mb-0"><?php echo $crumb; ?> <?php echo htmlspecialchars($this->moduleNameSingular, ENT_QUOTES, 'UTF-8'); ?></h2><?php if ($isEdit) { $this->load->view('admin/partials/manage_language_switcher', array('language_base_url' => $languageBaseUrl, 'locales' => $manage_locales, 'locale' => $active_locale, 'translation_module' => 'customer_reviews', 'translation_entity_id' => $recordID, 'translation_status' => $translationStatus, 'translation_ready' => $translationStatus === 'SUCCEEDED')); } ?></div>
        <div class="card-body">
            <form id="customer_reviews_form" name="customer_reviews_form" method="post" action="<?php echo $action; ?>" enctype="multipart/form-data" class="validate" data-manage-language-form data-submit-lock>
                <input type="hidden" name="active_locale" value="<?php echo htmlspecialchars($active_locale, ENT_QUOTES, 'UTF-8'); ?>">
                <input type="hidden" name="redirect_lang" value="" data-redirect-locale>
                <?php if ($this->config->item('csrf_protection')) { ?><input type="hidden" name="<?php echo htmlspecialchars($this->security->get_csrf_token_name(), ENT_QUOTES, 'UTF-8'); ?>" value="<?php echo htmlspecialchars($this->security->get_csrf_hash(), ENT_QUOTES, 'UTF-8'); ?>"><?php } ?>

                <div class="admin-field mb-3"><label class="form-label is-required" for="localized_name">Customer Name </label><input type="text" name="localized_name" id="localized_name" maxlength="<?php echo $active_locale === 'ar' ? 255 : 100; ?>" value="<?php echo $localizedValue('localized_name'); ?>" class="form-control" data-translation-field="localized_name" data-validate="required,maxlength[<?php echo $active_locale === 'ar' ? 255 : 100; ?>]" required dir="<?php echo htmlspecialchars($localeDetails['direction'], ENT_QUOTES, 'UTF-8'); ?>"<?php echo $active_locale === 'ar' ? ' lang="ar"' : ''; ?> placeholder="<?php echo $active_locale === 'ar' ? 'اسم العميل' : 'Omer Bin Khalid'; ?>"></div>

                <div class="admin-field mb-3"><label class="form-label is-required" for="localized_review">Review </label><textarea rows="6" name="localized_review" id="localized_review" class="form-control" data-translation-field="localized_review" data-validate="required" required dir="<?php echo htmlspecialchars($localeDetails['direction'], ENT_QUOTES, 'UTF-8'); ?>"<?php echo $active_locale === 'ar' ? ' lang="ar"' : ''; ?> placeholder="<?php echo $active_locale === 'ar' ? 'اكتب تقييم العميل' : 'Write the customer feedback shown on the website'; ?>"><?php echo $localizedValue('localized_review'); ?></textarea></div>

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
<?php if ($isEdit) $this->load->view('admin/partials/manage_language_switch_modal'); ?>
