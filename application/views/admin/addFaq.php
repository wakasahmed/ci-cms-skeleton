<?php
$isEdit = ($alert === 'edit' && !empty($tbl_data[$this->pKey]));
$crumb = $isEdit ? 'Edit' : 'Add';
$action = base_url('manage/'.$this->controller.'/'.($isEdit ? 'editRecord/'.(int) $tbl_data[$this->pKey] : 'addRecord'));
$localizedValue = function ($key) use ($localized_values) { return isset($localized_values[$key]) ? htmlspecialchars((string) $localized_values[$key], ENT_QUOTES, 'UTF-8') : ''; };
$localeDetails = $manage_locales[$active_locale];
$translationStatus = $isEdit && !empty($translation_state['status']) ? $translation_state['status'] : 'MISSING';
$languageBaseUrl = $isEdit ? base_url('manage/'.$this->controller.'/control/edit/'.(int) $tbl_data[$this->pKey]) : '';
$listUrl = base_url('manage/'.$this->controller.'?category_id='.$category_filter_id);
?>
<?php $this->load->view('admin/partials/breadcrumb', array('items' => array(
    array('label' => 'FAQs', 'url' => ADMIN_URL.'faqs-categories'),
    array('label' => $this->moduleName, 'url' => $listUrl),
    array('label' => $crumb.' FAQ', 'active' => TRUE),
))); ?>

<?php if (!empty($form_error)) { ?><div class="row alertrow"><div class="col-md-12"><div class="alert alert-danger alert-dismissible fade show" role="alert"><strong>Could not save the FAQ.</strong> <?php echo htmlspecialchars($form_error, ENT_QUOTES, 'UTF-8'); ?><button type="button" class="btn-close alertBox crud-alert-close" data-bs-dismiss="alert" aria-label="Dismiss notification"></button></div></div></div><?php } ?>

<section class="admin-records-listing" aria-labelledby="faqs-title"<?php if ($isEdit) { ?> data-translation-poll data-module="faqs" data-status-url="<?php echo base_url('manage/translations/statuses'); ?>" data-poll-seconds="4" data-locale="<?php echo htmlspecialchars($active_locale, ENT_QUOTES, 'UTF-8'); ?>"<?php } ?>>
    <?php $this->load->view('admin/partials/module_header', array('title' => $this->moduleName, 'description' => $this->moduleDesc, 'id' => 'faqs-title')); ?>
    <div class="card admin-card">
        <div class="card-header d-flex flex-wrap align-items-center justify-content-between gap-3"><h2 class="card-title mb-0"><?php echo $crumb; ?> <?php echo htmlspecialchars($this->moduleNameSingular, ENT_QUOTES, 'UTF-8'); ?></h2><?php if ($isEdit) { $this->load->view('admin/partials/manage_language_switcher', array('language_base_url' => $languageBaseUrl, 'locales' => $manage_locales, 'locale' => $active_locale, 'translation_module' => 'faqs', 'translation_entity_id' => (int) $tbl_data[$this->pKey], 'translation_status' => $translationStatus, 'translation_ready' => $translationStatus === 'SUCCEEDED')); } ?></div>
        <div class="card-body">
            <form id="<?php echo htmlspecialchars($this->controller, ENT_QUOTES, 'UTF-8'); ?>_form" name="<?php echo htmlspecialchars($this->controller, ENT_QUOTES, 'UTF-8'); ?>_form" method="post" action="<?php echo $action; ?>" class="validate" data-manage-language-form>
                <input type="hidden" name="active_locale" value="<?php echo htmlspecialchars($active_locale, ENT_QUOTES, 'UTF-8'); ?>">
                <input type="hidden" name="redirect_lang" value="" data-redirect-locale>
                <input type="hidden" name="faq_cat_id" value="<?php echo (int) $category_filter_id; ?>">
                <?php if ($this->config->item('csrf_protection')) { ?><input type="hidden" name="<?php echo htmlspecialchars($this->security->get_csrf_token_name(), ENT_QUOTES, 'UTF-8'); ?>" value="<?php echo htmlspecialchars($this->security->get_csrf_hash(), ENT_QUOTES, 'UTF-8'); ?>"><?php } ?>
                <div class="admin-field mb-3"><label class="form-label is-required" for="localized_name">Question </label><input type="text" name="localized_name" id="localized_name" maxlength="255" value="<?php echo $localizedValue('localized_name'); ?>" class="form-control" data-translation-field="localized_name" data-validate="required" required dir="<?php echo htmlspecialchars($localeDetails['direction'], ENT_QUOTES, 'UTF-8'); ?>"<?php echo $active_locale === 'ar' ? ' lang="ar"' : ''; ?> placeholder="<?php echo $active_locale === 'ar' ? 'السؤال' : 'Question'; ?>"></div>
                <div class="admin-field mb-3"><label class="form-label is-required" for="localized_short_description">Answer </label><textarea rows="5" name="localized_short_description" id="localized_short_description" class="form-control" data-translation-field="localized_short_description" data-validate="required" required dir="<?php echo htmlspecialchars($localeDetails['direction'], ENT_QUOTES, 'UTF-8'); ?>"<?php echo $active_locale === 'ar' ? ' lang="ar"' : ''; ?>><?php echo $localizedValue('localized_short_description'); ?></textarea></div>
                <div class="row">
                    <div class="admin-field mb-3 col-md-12"><label class="form-label" for="faq_status">Status</label><select class="form-select select2" name="faq_status" id="faq_status" data-minimum-results-for-search="-1"><option value="Enable" <?php echo (!isset($tbl_data['faq_status']) || $tbl_data['faq_status'] === 'Enable') ? 'selected' : ''; ?>>Enable</option><option value="Disable" <?php echo (isset($tbl_data['faq_status']) && $tbl_data['faq_status'] === 'Disable') ? 'selected' : ''; ?>>Disable</option></select></div>
                </div>
                <div class="admin-form-actions"><a class="btn btn-outline-secondary" href="<?php echo $listUrl; ?>">Cancel</a><button type="submit" class="btn btn-primary" data-save-button><span data-save-label>Save <?php echo htmlspecialchars($this->moduleNameSingular, ENT_QUOTES, 'UTF-8'); ?></span></button></div>
            </form>
        </div>
    </div>
</section>
<?php if ($isEdit) $this->load->view('admin/partials/manage_language_switch_modal'); ?>
