<?php
$isEdit = ($alert === 'edit' && !empty($tbl_data[$this->pKey]));
$crumb = $isEdit ? 'Edit' : 'Add';
$action = base_url('manage/'.$this->controller.'/'.($isEdit ? 'editRecord/'.$tbl_data[$this->pKey] : 'addRecord'));
$localizedValue = function ($key) use ($localized_values) { return isset($localized_values[$key]) ? htmlspecialchars((string) $localized_values[$key], ENT_QUOTES, 'UTF-8') : ''; };
$localeDetails = $manage_locales[$active_locale];
$translationStatus = $isEdit && !empty($translation_state['status']) ? $translation_state['status'] : 'MISSING';
$languageBaseUrl = $isEdit ? base_url('manage/'.$this->controller.'/control/edit/'.(int) $tbl_data[$this->pKey]) : '';
?>
<?php $this->load->view('admin/partials/breadcrumb', array('items' => array(array('label' => $this->moduleName, 'url' => ADMIN_URL.$this->controller), array('label' => $crumb.' '.$this->moduleNameSingular, 'active' => TRUE)))); ?>

<?php if (!empty($form_error)) { ?>
<div class="row alertrow"><div class="col-md-12"><button class="btn-close alertBox" data-bs-dismiss="alert" aria-label="Dismiss"></button><div class="alert alert-danger"><strong>Could not save the FAQ category.</strong> <?php echo htmlspecialchars($form_error, ENT_QUOTES, 'UTF-8'); ?></div></div></div>
<?php } ?>

<section class="admin-records-listing" aria-labelledby="faqs-categories-title"<?php if ($isEdit) { ?> data-translation-poll data-module="faqs_categories" data-status-url="<?php echo base_url('manage/translations/statuses'); ?>" data-poll-seconds="4" data-locale="<?php echo htmlspecialchars($active_locale, ENT_QUOTES, 'UTF-8'); ?>"<?php } ?>>
    <?php $this->load->view('admin/partials/module_header', array('title' => $this->moduleName, 'description' => $this->moduleDesc, 'id' => 'faqs-categories-title')); ?>

    <div class="card admin-card">
        <div class="card-header d-flex flex-wrap align-items-center justify-content-between gap-3"><h2 class="card-title mb-0"><?php echo $crumb; ?> <?php echo htmlspecialchars($this->moduleNameSingular, ENT_QUOTES, 'UTF-8'); ?></h2><?php if ($isEdit) { $this->load->view('admin/partials/manage_language_switcher', array('language_base_url' => $languageBaseUrl, 'locales' => $manage_locales, 'locale' => $active_locale, 'translation_module' => 'faqs_categories', 'translation_entity_id' => $tbl_data[$this->pKey], 'translation_status' => $translationStatus, 'translation_ready' => $translationStatus === 'SUCCEEDED')); } ?></div>
        <div class="card-body">
        <form id="<?php echo $this->controller; ?>_form" name="<?php echo $this->controller; ?>_form" method="post" action="<?php echo $action; ?>" class="validate" data-manage-language-form>
            <input type="hidden" name="active_locale" value="<?php echo htmlspecialchars($active_locale, ENT_QUOTES, 'UTF-8'); ?>">
            <input type="hidden" name="redirect_lang" value="" data-redirect-locale>
            <?php if ($this->config->item('csrf_protection')) { ?><input type="hidden" name="<?php echo htmlspecialchars($this->security->get_csrf_token_name(), ENT_QUOTES, 'UTF-8'); ?>" value="<?php echo htmlspecialchars($this->security->get_csrf_hash(), ENT_QUOTES, 'UTF-8'); ?>"><?php } ?>
            <div class="admin-field mb-3"><label class="form-label is-required" for="localized_name">Name </label><input type="text" name="localized_name" id="localized_name" maxlength="255" value="<?php echo $localizedValue('localized_name'); ?>" class="form-control" data-translation-field="localized_name" data-validate="required" required dir="<?php echo $localeDetails['direction']; ?>"<?php echo $active_locale === 'ar' ? ' lang="ar"' : ''; ?> placeholder="<?php echo $active_locale === 'ar' ? 'اسم الفئة' : 'Category name'; ?>"></div>
            <div class="admin-field mb-3"><label class="form-label" for="localized_description">Short Description </label><textarea rows="5" name="localized_description" id="localized_description" class="form-control" data-translation-field="localized_description" dir="<?php echo $localeDetails['direction']; ?>"<?php echo $active_locale === 'ar' ? ' lang="ar"' : ''; ?>><?php echo $localizedValue('localized_description'); ?></textarea></div>

            <div class="row">
                <div class="admin-field mb-3 col-md-6"><label class="form-label" for="cat_status">Status</label><select class="form-select select2" name="cat_status" id="cat_status" data-minimum-results-for-search="-1"><option value="Enable" <?php echo (!isset($tbl_data['cat_status']) || $tbl_data['cat_status'] === 'Enable') ? 'selected' : ''; ?>>Enable</option><option value="Disable" <?php echo (isset($tbl_data['cat_status']) && $tbl_data['cat_status'] === 'Disable') ? 'selected' : ''; ?>>Disable</option></select></div>
                <div class="admin-field mb-3 col-md-6"><label class="form-label" for="cat_hidden">Hide it from FAQs page</label><select class="form-select select2" name="cat_hidden" id="cat_hidden" data-minimum-results-for-search="-1"><option value="No" <?php echo (!isset($tbl_data['cat_hidden']) || $tbl_data['cat_hidden'] === 'No') ? 'selected' : ''; ?>>No</option><option value="Yes" <?php echo (isset($tbl_data['cat_hidden']) && $tbl_data['cat_hidden'] === 'Yes') ? 'selected' : ''; ?>>Yes</option></select></div>
            </div>

            <div class="admin-form-actions"><a class="btn btn-outline-secondary" href="<?php echo ADMIN_URL.$this->controller; ?>">Cancel</a><button type="submit" class="btn btn-primary" data-save-button><span data-save-label>Save <?php echo htmlspecialchars($this->moduleNameSingular, ENT_QUOTES, 'UTF-8'); ?></span></button></div>
        </form>
        </div>
    </div>
</section>
<?php if ($isEdit) $this->load->view('admin/partials/manage_language_switch_modal'); ?>
