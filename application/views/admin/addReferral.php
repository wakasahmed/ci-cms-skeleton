<?php
$isEdit = $alert === 'edit' && !empty($tbl_data[$this->pKey]);
$crumb = $isEdit ? 'Edit' : 'Add';
$id = $isEdit ? (int) $tbl_data[$this->pKey] : 0;
$action = base_url('manage/'.$this->controller.'/'.($isEdit ? 'editRecord/'.$id : 'addRecord'));
$localeDetails = $manage_locales[$active_locale];
$translationStatus = $isEdit && !empty($translation_state['status']) ? $translation_state['status'] : 'MISSING';
$languageBaseUrl = $isEdit ? base_url('manage/'.$this->controller.'/control/edit/'.$id) : '';
$value = function ($key, $default = '') use ($tbl_data) {
    return htmlspecialchars(isset($tbl_data[$key]) ? (string) $tbl_data[$key] : $default, ENT_QUOTES, 'UTF-8');
};
$localizedValue = function ($key) use ($localized_values) {
    return htmlspecialchars(isset($localized_values[$key]) ? (string) $localized_values[$key] : '', ENT_QUOTES, 'UTF-8');
};
?>
<?php $this->load->view('admin/partials/breadcrumb', array(
    'items' => array(
        array('label' => $this->moduleName, 'url' => ADMIN_URL.$this->controller),
        array('label' => $crumb.' '.$this->moduleNameSingular, 'active' => TRUE),
    ),
)); ?>

<?php if (!empty($form_error)) { ?>
    <div class="alert alert-danger" role="alert"><strong>Could not save the referral.</strong> <?php echo htmlspecialchars($form_error, ENT_QUOTES, 'UTF-8'); ?></div>
<?php } ?>

<section class="admin-records-listing" aria-labelledby="referral-form-title"<?php if ($isEdit) { ?> data-translation-poll data-module="referrals" data-status-url="<?php echo base_url('manage/translations/statuses'); ?>" data-poll-seconds="4" data-locale="<?php echo htmlspecialchars($active_locale, ENT_QUOTES, 'UTF-8'); ?>"<?php } ?>>
    <?php $this->load->view('admin/partials/module_header', array(
        'title' => $this->moduleName,
        'description' => 'Manage referral contacts, payment details, and commission status.',
        'id' => 'referral-form-title',
    )); ?>

    <div class="card admin-card">
        <div class="card-header d-flex flex-wrap align-items-center justify-content-between gap-3">
            <h2 class="card-title mb-0"><?php echo $crumb; ?> <?php echo htmlspecialchars($this->moduleNameSingular, ENT_QUOTES, 'UTF-8'); ?></h2>
            <?php if ($isEdit) { ?>
                <?php $this->load->view('admin/partials/manage_language_switcher', array(
                    'language_base_url' => $languageBaseUrl,
                    'locales' => $manage_locales,
                    'locale' => $active_locale,
                    'translation_module' => 'referrals',
                    'translation_entity_id' => $id,
                    'translation_status' => $translationStatus,
                    'translation_ready' => $translationStatus === 'SUCCEEDED',
                )); ?>
            <?php } ?>
        </div>
        <div class="card-body">
            <form id="referral_form" method="post" action="<?php echo $action; ?>" class="validate" novalidate data-manage-language-form data-submit-lock>
                <input type="hidden" name="active_locale" value="<?php echo htmlspecialchars($active_locale, ENT_QUOTES, 'UTF-8'); ?>">
                <input type="hidden" name="redirect_lang" value="" data-redirect-locale>
                <?php if ($this->config->item('csrf_protection')) { ?><input type="hidden" name="<?php echo htmlspecialchars($this->security->get_csrf_token_name(), ENT_QUOTES, 'UTF-8'); ?>" value="<?php echo htmlspecialchars($this->security->get_csrf_hash(), ENT_QUOTES, 'UTF-8'); ?>"><?php } ?>

                <div class="row" dir="<?php echo $localeDetails['direction']; ?>">
                    <div class="admin-field mb-3 col-md-6">
                        <label class="form-label is-required" for="localized_name">Referral Name </label>
                        <input type="text" class="form-control" name="localized_name" id="localized_name" maxlength="255" value="<?php echo $localizedValue('localized_name'); ?>" data-translation-field="localized_name" data-validate="required" required dir="<?php echo $localeDetails['direction']; ?>"<?php echo $active_locale === 'ar' ? ' lang="ar"' : ''; ?> placeholder="<?php echo $active_locale === 'ar' ? 'اسم جهة الإحالة' : 'Referral name'; ?>">
                    </div>
                    <div class="admin-field mb-3 col-md-6">
                        <label class="form-label is-required" for="ref_email">Email</label>
                        <input type="email" class="form-control" name="ref_email" id="ref_email" maxlength="255" value="<?php echo $value('ref_email'); ?>" data-validate="required" required placeholder="referral@example.com">
                    </div>
                    <div class="admin-field mb-3 col-md-6">
                        <label class="form-label is-required" for="ref_phone">Phone</label>
                        <input type="tel" class="form-control intl-phone" name="ref_phone" id="ref_phone" maxlength="255" value="<?php echo $value('ref_phone'); ?>" data-initial-country="sa" data-validate="required,intlPhone" required autocomplete="tel" placeholder="Phone number">
                    </div>
                    <div class="admin-field mb-3 col-md-6">
                        <label class="form-label" for="ref_type">Type</label>
                        <select class="form-select select2" name="ref_type" id="ref_type" data-minimum-results-for-search="-1">
                            <?php foreach (array('Government', 'Company', 'Individual') as $type) { ?><option value="<?php echo $type; ?>"<?php echo $value('ref_type', 'Individual') === $type ? ' selected' : ''; ?>><?php echo $type; ?></option><?php } ?>
                        </select>
                    </div>
                    <div class="admin-field mb-3 col-md-6">
                        <label class="form-label" for="localized_organization">Organisation</label>
                        <input type="text" class="form-control" name="localized_organization" id="localized_organization" maxlength="255" value="<?php echo $localizedValue('localized_organization'); ?>" data-translation-field="localized_organization" dir="<?php echo $localeDetails['direction']; ?>"<?php echo $active_locale === 'ar' ? ' lang="ar"' : ''; ?> placeholder="<?php echo $active_locale === 'ar' ? 'اسم الجهة' : 'Organisation name'; ?>">
                    </div>
                    <div class="admin-field mb-3 col-md-6">
                        <label class="form-label" for="localized_designation">Designation</label>
                        <input type="text" class="form-control" name="localized_designation" id="localized_designation" maxlength="255" value="<?php echo $localizedValue('localized_designation'); ?>" data-translation-field="localized_designation" dir="<?php echo $localeDetails['direction']; ?>"<?php echo $active_locale === 'ar' ? ' lang="ar"' : ''; ?> placeholder="<?php echo $active_locale === 'ar' ? 'المسمى الوظيفي' : 'Job title or role'; ?>">
                    </div>
                    <div class="admin-field mb-3 col-md-6"><label class="form-label" for="ref_lang">Notification Language</label><select class="form-select select2" name="ref_lang" id="ref_lang" data-minimum-results-for-search="-1"><option value="Arabic"<?php echo $value('ref_lang', 'Arabic') === 'Arabic' ? ' selected' : ''; ?>>Arabic</option><option value="English"<?php echo $value('ref_lang') === 'English' ? ' selected' : ''; ?>>English</option></select></div>
                    <div class="admin-field mb-3 col-md-6"><label class="form-label" for="ref_country">Country</label><select name="ref_country" id="ref_country" class="form-select select2"><option value="">Select country</option><?php foreach ($countries as $country) { ?><option value="<?php echo htmlspecialchars($country['name'], ENT_QUOTES, 'UTF-8'); ?>"<?php echo isset($tbl_data['ref_country']) && (string) $tbl_data['ref_country'] === (string) $country['name'] ? ' selected' : ''; ?>><?php echo htmlspecialchars($country['name'], ENT_QUOTES, 'UTF-8'); ?></option><?php } ?></select></div>
                </div>

                <hr>
                <h3 class="h6 mb-3">Payment Details</h3>
                <div class="row">
                    <div class="admin-field mb-3 col-md-6"><label class="form-label" for="ref_acc_title">Account Title</label><input type="text" class="form-control" name="ref_acc_title" id="ref_acc_title" maxlength="255" value="<?php echo $value('ref_acc_title'); ?>"></div>
                    <div class="admin-field mb-3 col-md-6"><label class="form-label" for="ref_bank_name">Bank Name</label><input type="text" class="form-control" name="ref_bank_name" id="ref_bank_name" maxlength="255" value="<?php echo $value('ref_bank_name'); ?>"></div>
                    <div class="admin-field mb-3 col-md-6"><label class="form-label" for="ref_acc_no">Account Number / IBAN</label><input type="text" class="form-control" name="ref_acc_no" id="ref_acc_no" maxlength="255" value="<?php echo $value('ref_acc_no'); ?>"></div>
                    <div class="admin-field mb-3 col-md-6"><label class="form-label" for="ref_swift_code">SWIFT Code</label><input type="text" class="form-control" name="ref_swift_code" id="ref_swift_code" maxlength="255" value="<?php echo $value('ref_swift_code'); ?>"></div>
                    <div class="admin-field mb-3 col-md-6"><label class="form-label" for="ref_bank_code">Bank / Branch Code</label><input type="text" class="form-control" name="ref_bank_code" id="ref_bank_code" maxlength="255" value="<?php echo $value('ref_bank_code'); ?>"></div>
                    <div class="admin-field mb-3 col-md-6"><label class="form-label" for="ref_bank_address">Bank / Branch Address</label><input type="text" class="form-control" name="ref_bank_address" id="ref_bank_address" maxlength="255" value="<?php echo $value('ref_bank_address'); ?>"></div>
                </div>

                <div class="admin-publishing-panel">
                    <div><h4>Availability</h4><p>Control whether this referral can be used for discount codes.</p></div>
                    <div class="admin-field mb-0"><label class="form-label" for="ref_status">Status</label><select class="form-select select2" name="ref_status" id="ref_status" data-minimum-results-for-search="-1"><option value="Enable"<?php echo $value('ref_status', 'Enable') === 'Enable' ? ' selected' : ''; ?>>Enabled</option><option value="Disable"<?php echo $value('ref_status') === 'Disable' ? ' selected' : ''; ?>>Disabled</option></select></div>
                </div>
                <div class="admin-form-actions"><a class="btn btn-outline-secondary" href="<?php echo ADMIN_URL.$this->controller; ?>">Cancel</a><button type="submit" class="btn btn-primary" data-save-button><span data-save-label>Save <?php echo htmlspecialchars($this->moduleNameSingular, ENT_QUOTES, 'UTF-8'); ?></span></button></div>
            </form>
        </div>
    </div>
</section>
<?php if ($isEdit) { $this->load->view('admin/partials/manage_language_switch_modal'); } ?>
