<?php
$isEdit = ($alert === 'edit' && !empty($tbl_data[$this->pKey]));
$crumb = $isEdit ? 'Edit' : 'Add';
$action = base_url('manage/'.$this->controller.'/'.($isEdit ? 'editRecord/'.$tbl_data[$this->pKey] : 'addRecord'));
$value = function ($key) use ($tbl_data) { return isset($tbl_data[$key]) ? htmlspecialchars((string) $tbl_data[$key], ENT_QUOTES, 'UTF-8') : ''; };
$localizedValue = function ($key) use ($localized_values) { return isset($localized_values[$key]) ? htmlspecialchars((string) $localized_values[$key], ENT_QUOTES, 'UTF-8') : ''; };
$guideImage = isset($tbl_data['tour_guide_image']) ? basename((string) $tbl_data['tour_guide_image']) : '';
$licenseFile = isset($tbl_data['tour_guide_license_image']) ? basename((string) $tbl_data['tour_guide_license_image']) : '';
$notiLang = isset($tbl_data['tour_guide_noti_lang']) ? $tbl_data['tour_guide_noti_lang'] : 'Arabic';
$guideName = isset($tbl_data['tour_guide_name']) ? trim((string) $tbl_data['tour_guide_name']) : 'Tour guide';
$localeDetails = $manage_locales[$active_locale];
$translationStatus = $isEdit && !empty($translation_state['status']) ? $translation_state['status'] : 'MISSING';
$languageBaseUrl = $isEdit ? base_url('manage/'.$this->controller.'/control/edit/'.(int) $tbl_data[$this->pKey]) : '';
?>
<?php $this->load->view('admin/partials/breadcrumb', array('items' => array(array('label' => $this->moduleName, 'url' => ADMIN_URL.$this->controller), array('label' => $crumb.' '.$this->moduleNameSingular, 'active' => TRUE)))); ?>

<?php if (!empty($form_error)) { ?>
<div class="row alertrow"><div class="col-md-12"><button class="btn-close alertBox" data-bs-dismiss="alert" aria-label="Dismiss"></button><div class="alert alert-danger"><strong>Could not save the tour guide.</strong> <?php echo htmlspecialchars($form_error, ENT_QUOTES, 'UTF-8'); ?></div></div></div>
<?php } ?>

<section class="admin-records-listing" aria-labelledby="tour-guides-title"<?php if ($isEdit) { ?> data-translation-poll data-module="tour_guides" data-status-url="<?php echo base_url('manage/translations/statuses'); ?>" data-poll-seconds="4" data-locale="<?php echo htmlspecialchars($active_locale, ENT_QUOTES, 'UTF-8'); ?>"<?php } ?>>
    <?php $this->load->view('admin/partials/module_header', array('title' => $this->moduleName, 'description' => $this->moduleDesc, 'id' => 'tour-guides-title')); ?>

    <div class="card admin-card">
        <div class="card-header d-flex flex-wrap align-items-center justify-content-between gap-3"><h2 class="card-title mb-0"><?php echo $crumb; ?> <?php echo htmlspecialchars($this->moduleNameSingular, ENT_QUOTES, 'UTF-8'); ?></h2><?php if ($isEdit) { $this->load->view('admin/partials/manage_language_switcher', array('language_base_url' => $languageBaseUrl, 'locales' => $manage_locales, 'locale' => $active_locale, 'translation_module' => 'tour_guides', 'translation_entity_id' => $tbl_data[$this->pKey], 'translation_status' => $translationStatus, 'translation_ready' => $translationStatus === 'SUCCEEDED')); } ?></div>
        <div class="card-body">
        <form id="<?php echo $this->controller; ?>_form" name="<?php echo $this->controller; ?>_form" method="post" action="<?php echo $action; ?>" enctype="multipart/form-data" class="validate" data-manage-language-form>
            <input type="hidden" name="active_locale" value="<?php echo htmlspecialchars($active_locale, ENT_QUOTES, 'UTF-8'); ?>">
            <input type="hidden" name="redirect_lang" value="" data-redirect-locale>
            <?php if ($this->config->item('csrf_protection')) { ?><input type="hidden" name="<?php echo htmlspecialchars($this->security->get_csrf_token_name(), ENT_QUOTES, 'UTF-8'); ?>" value="<?php echo htmlspecialchars($this->security->get_csrf_hash(), ENT_QUOTES, 'UTF-8'); ?>"><?php } ?>
            <div class="row">
                <div class="admin-field mb-3 col-md-6"><label class="form-label is-required" for="localized_name">Name </label><input type="text" name="localized_name" id="localized_name" maxlength="<?php echo $active_locale === 'ar' ? 255 : 100; ?>" value="<?php echo $localizedValue('localized_name'); ?>" class="form-control" data-translation-field="localized_name" data-validate="required" required dir="<?php echo $localeDetails['direction']; ?>"<?php echo $active_locale === 'ar' ? ' lang="ar"' : ''; ?> placeholder=""></div>
                <div class="admin-field mb-3 col-md-6"><label class="form-label" for="localized_title">Title</label><input type="text" name="localized_title" id="localized_title" maxlength="255" value="<?php echo $localizedValue('localized_title'); ?>" class="form-control" data-translation-field="localized_title" dir="<?php echo $localeDetails['direction']; ?>"<?php echo $active_locale === 'ar' ? ' lang="ar"' : ''; ?> placeholder=""></div>
            </div>
            <div class="row">
                <div class="admin-field mb-3 col-md-6"><label class="form-label is-required" for="tour_guide_email">Email</label><input type="email" name="tour_guide_email" id="tour_guide_email" maxlength="255" value="<?php echo $value('tour_guide_email'); ?>" class="form-control" data-validate="required" required placeholder=""></div>
                <div class="admin-field mb-3 col-md-6"><label class="form-label is-required" for="tour_guide_phone">Phone</label><input type="tel" name="tour_guide_phone" id="tour_guide_phone" maxlength="255" value="<?php echo $value('tour_guide_phone'); ?>" class="form-control intl-phone" data-initial-country="sa" data-validate="required,intlPhone" required autocomplete="tel" placeholder="Phone number"></div>
            </div>
            <div class="row">
                <div class="admin-field mb-3 col-md-6"><label class="form-label" for="tour_guide_license_number">License Number</label><input type="text" name="tour_guide_license_number" id="tour_guide_license_number" maxlength="255" value="<?php echo $value('tour_guide_license_number'); ?>" class="form-control" placeholder=""></div>
                <div class="admin-field mb-3 col-md-6">
                    <label class="form-label" for="tour_guide_noti_lang">Email Notification Language</label>
                    <select class="form-select select2" name="tour_guide_noti_lang" id="tour_guide_noti_lang" data-minimum-results-for-search="-1">
                        <option value="English" <?php echo $notiLang === 'English' ? 'selected' : ''; ?>>English</option>
                        <option value="Arabic" <?php echo $notiLang !== 'English' ? 'selected' : ''; ?>>Arabic</option>
                    </select>
                </div>
            </div>

            <div class="admin-field mb-3">
                <label class="form-label is-required" for="tour_guide_lang">Languages</label>
                <select autocomplete="off" name="tour_guide_lang[]" id="tour_guide_lang" class="form-select select2 required" multiple required><?php foreach ($lang as $item) { ?><option value="<?php echo (int) $item['lang_id']; ?>" <?php echo (!empty($tour_guide_lang) && in_array($item['lang_id'], $tour_guide_lang)) ? 'selected' : ''; ?>><?php echo htmlspecialchars($item['lang_name'], ENT_QUOTES, 'UTF-8'); ?></option><?php } ?></select>
            </div>
            <div class="admin-field mb-3">
                <label class="form-label is-required" for="tour_guide_tours">Tours</label>
                <select autocomplete="off" name="tour_guide_tours[]" id="tour_guide_tours" class="form-select select2 required" multiple required><?php foreach ($tours as $item) { ?><option value="<?php echo (int) $item['tour_id']; ?>" <?php echo (!empty($tour_guide_tours) && in_array($item['tour_id'], $tour_guide_tours)) ? 'selected' : ''; ?>><?php echo htmlspecialchars($item['tour_name'], ENT_QUOTES, 'UTF-8'); ?></option><?php } ?></select>
            </div>
            <div class="admin-field mb-3"><label class="form-label" for="localized_description">Details</label><textarea rows="5" name="localized_description" id="localized_description" class="form-control" data-translation-field="localized_description" dir="<?php echo $localeDetails['direction']; ?>"<?php echo $active_locale === 'ar' ? ' lang="ar"' : ''; ?>><?php echo $localizedValue('localized_description'); ?></textarea></div>
            <hr>
            <?php $this->load->view('admin/partials/avatar_upload', array(
                'field_name' => 'uploadfile',
                'label' => 'Guide Picture',
                'current_path' => $guideImage !== '' ? 'assets/frontend/images/tour-guides/'.$guideImage : '',
                'fallback' => $guideName,
                'required' => TRUE,
                'crop_title' => 'Adjust guide picture',
                'apply_label' => 'Use this picture',
            )); ?>
            <div class="admin-field mb-4"><?php $this->load->view('admin/partials/file_upload', array(
                'name' => 'tour_guide_license_image', 'id' => 'tour_guide_license_image', 'label' => 'License File (Optional)', 'allowed_types' => UPLOAD_IMAGE_MIMES.'|'.UPLOAD_DOC_MIMES, 'required' => FALSE,
                'current_path' => $licenseFile !== '' ? 'assets/frontend/images/tour-guide-licenses/'.$licenseFile : '', 'preview_alt' => $guideName.' license', 'preview_shape' => 'square', 'preview_size' => 132,
            )); ?></div>
            <?php if ($isEdit && image_thumb_path('assets/frontend/images/tour-guide-licenses/' . $licenseFile) !== FALSE) { ?><div class="admin-current-file-remove"><div class="admin-current-file-actions"><button type="button" class="btn btn-outline-danger btn-sm removeFile" data-controller="<?php echo $this->controller; ?>" data-file-name="tour_guide_license_image" data-file-id="<?php echo (int) $tbl_data[$this->pKey]; ?>"><i class="bi bi-trash" aria-hidden="true"></i> Remove License File</button></div></div><?php } ?>

               <div class="row">
                <div class="admin-field mb-3 col-md-12"><label class="form-label" for="tour_guide_status">Status</label><select class="form-select select2" name="tour_guide_status" id="tour_guide_status" data-minimum-results-for-search="-1"><option value="Enable" <?php echo (!isset($tbl_data['tour_guide_status']) || $tbl_data['tour_guide_status'] === 'Enable') ? 'selected' : ''; ?>>Enable</option><option value="Disable" <?php echo (isset($tbl_data['tour_guide_status']) && $tbl_data['tour_guide_status'] === 'Disable') ? 'selected' : ''; ?>>Disable</option></select></div>
            </div>

            <div class="admin-form-actions"><a class="btn btn-outline-secondary" href="<?php echo ADMIN_URL.$this->controller; ?>">Cancel</a><button type="submit" class="btn btn-primary" data-save-button><span data-save-label>Save <?php echo htmlspecialchars($this->moduleNameSingular, ENT_QUOTES, 'UTF-8'); ?></span></button></div>
        </form>
        </div>
    </div>
</section>
<?php if ($isEdit) $this->load->view('admin/partials/manage_language_switch_modal'); ?>
