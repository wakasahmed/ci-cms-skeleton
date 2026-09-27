<?php
$isEdit = ($alert === 'edit' && !empty($tbl_data[$this->pKey]));
$crumb = $isEdit ? 'Edit' : 'Add';
$action = base_url('manage/'.$this->controller.'/'.($isEdit ? 'editRecord/'.(int) $tbl_data[$this->pKey] : 'addRecord'));
$value = function ($key) use ($tbl_data) { return isset($tbl_data[$key]) ? htmlspecialchars((string) $tbl_data[$key], ENT_QUOTES, 'UTF-8') : ''; };
$localizedValue = function ($key) use ($localized_values) { return isset($localized_values[$key]) ? htmlspecialchars((string) $localized_values[$key], ENT_QUOTES, 'UTF-8') : ''; };
$localeDetails = $manage_locales[$active_locale];
$translationStatus = $isEdit && !empty($translation_state['status']) ? $translation_state['status'] : 'MISSING';
$languageBaseUrl = $isEdit ? base_url('manage/'.$this->controller.'/control/edit/'.(int) $tbl_data[$this->pKey]) : '';
?>
<?php $this->load->view('admin/partials/breadcrumb', array('items' => array(array('label' => $this->moduleName, 'url' => ADMIN_URL.$this->controller), array('label' => $crumb.' '.$this->moduleNameSingular, 'active' => TRUE)))); ?>

<?php if (!empty($form_error)) { ?><div class="row alertrow"><div class="col-md-12"><div class="alert alert-danger alert-dismissible fade show" role="alert"><strong>Could not save the country.</strong> <?php echo htmlspecialchars($form_error, ENT_QUOTES, 'UTF-8'); ?><button type="button" class="btn-close alertBox crud-alert-close" data-bs-dismiss="alert" aria-label="Dismiss notification"></button></div></div></div><?php } ?>

<section class="admin-records-listing" aria-labelledby="countries-title"<?php if ($isEdit) { ?> data-translation-poll data-module="countries" data-status-url="<?php echo base_url('manage/translations/statuses'); ?>" data-poll-seconds="4" data-locale="<?php echo htmlspecialchars($active_locale, ENT_QUOTES, 'UTF-8'); ?>"<?php } ?>>
    <?php $this->load->view('admin/partials/module_header', array('title' => $this->moduleName, 'description' => $this->moduleDesc, 'id' => 'countries-title')); ?>
    <div class="card admin-card">
        <div class="card-header d-flex flex-wrap align-items-center justify-content-between gap-3"><h2 class="card-title mb-0"><?php echo $crumb; ?> <?php echo htmlspecialchars($this->moduleNameSingular, ENT_QUOTES, 'UTF-8'); ?></h2><?php if ($isEdit) { $this->load->view('admin/partials/manage_language_switcher', array('language_base_url' => $languageBaseUrl, 'locales' => $manage_locales, 'locale' => $active_locale, 'translation_module' => 'countries', 'translation_entity_id' => (int) $tbl_data[$this->pKey], 'translation_status' => $translationStatus, 'translation_ready' => $translationStatus === 'SUCCEEDED')); } ?></div>
        <div class="card-body">
            <form id="<?php echo htmlspecialchars($this->controller, ENT_QUOTES, 'UTF-8'); ?>_form" name="<?php echo htmlspecialchars($this->controller, ENT_QUOTES, 'UTF-8'); ?>_form" method="post" action="<?php echo $action; ?>" class="validate" data-manage-language-form>
                <input type="hidden" name="active_locale" value="<?php echo htmlspecialchars($active_locale, ENT_QUOTES, 'UTF-8'); ?>">
                <input type="hidden" name="redirect_lang" value="" data-redirect-locale>
                <?php if ($this->config->item('csrf_protection')) { ?><input type="hidden" name="<?php echo htmlspecialchars($this->security->get_csrf_token_name(), ENT_QUOTES, 'UTF-8'); ?>" value="<?php echo htmlspecialchars($this->security->get_csrf_hash(), ENT_QUOTES, 'UTF-8'); ?>"><?php } ?>
                <div class="row">
                    <div class="admin-field mb-3 col-md-8"><label class="form-label is-required" for="localized_name"> Name </label><input type="text" name="localized_name" id="localized_name" maxlength="<?php echo $active_locale === 'ar' ? 255 : 80; ?>" value="<?php echo $localizedValue('localized_name'); ?>" class="form-control" data-translation-field="localized_name" data-validate="required" required dir="<?php echo htmlspecialchars($localeDetails['direction'], ENT_QUOTES, 'UTF-8'); ?>"<?php echo $active_locale === 'ar' ? ' lang="ar"' : ''; ?> placeholder="<?php echo $active_locale === 'ar' ? 'العربية' : 'English'; ?>"></div>
                    <div class="admin-field mb-3 col-md-4"><label class="form-label is-required" for="iso">ISO Code</label><input type="text" name="iso" id="iso" maxlength="2" minlength="2" value="<?php echo $value('iso'); ?>" class="form-control text-uppercase" data-validate="required" required placeholder="AE"></div>
                </div>
                <div class="row">
                    <div class="admin-field mb-3 col-md-4"><label class="form-label" for="iso3">ISO3 Code</label><input type="text" name="iso3" id="iso3" maxlength="3" minlength="3" value="<?php echo $value('iso3'); ?>" class="form-control text-uppercase" placeholder="ARE"></div>
                    <div class="admin-field mb-3 col-md-4"><label class="form-label" for="numcode">Numeric Code</label><input type="text" inputmode="numeric" name="numcode" id="numcode" maxlength="3" value="<?php echo $value('numcode'); ?>" class="form-control" data-validate="digits" placeholder="784"></div>
                    <div class="admin-field mb-3 col-md-4"><label class="form-label is-required" for="phonecode">Phone Code</label><input type="text" inputmode="numeric" name="phonecode" id="phonecode" maxlength="4" value="<?php echo $value('phonecode'); ?>" class="form-control" data-validate="required,digits" required placeholder="971"></div>
                </div>
                <div class="admin-form-actions"><a class="btn btn-outline-secondary" href="<?php echo ADMIN_URL.$this->controller; ?>">Cancel</a><button type="submit" class="btn btn-primary" data-save-button><span data-save-label>Save <?php echo htmlspecialchars($this->moduleNameSingular, ENT_QUOTES, 'UTF-8'); ?></span></button></div>
            </form>
        </div>
    </div>
</section>
<?php if ($isEdit) $this->load->view('admin/partials/manage_language_switch_modal'); ?>
