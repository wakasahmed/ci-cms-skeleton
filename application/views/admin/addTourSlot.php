<?php
$isEdit = ($alert === 'edit' && !empty($tbl_data[$this->pKey]));
$crumb = $isEdit ? 'Edit' : 'Add';
$action = base_url('manage/'.$this->controller.'/'.($isEdit ? 'editRecord/'.(int) $tbl_data[$this->pKey] : 'addRecord'));
$value = function ($key) use ($tbl_data) { return isset($tbl_data[$key]) ? htmlspecialchars((string) $tbl_data[$key], ENT_QUOTES, 'UTF-8') : ''; };
$timeValue = function ($key) use ($tbl_data) { return !empty($tbl_data[$key]) ? htmlspecialchars(substr((string) $tbl_data[$key], 0, 5), ENT_QUOTES, 'UTF-8') : ''; };
$localizedValue = function ($key) use ($localized_values) { return isset($localized_values[$key]) ? htmlspecialchars((string) $localized_values[$key], ENT_QUOTES, 'UTF-8') : ''; };
$localeDetails = $manage_locales[$active_locale];
$translationStatus = $isEdit && !empty($translation_state['status']) ? $translation_state['status'] : 'MISSING';
$languageBaseUrl = $isEdit ? base_url('manage/'.$this->controller.'/control/edit/'.(int) $tbl_data[$this->pKey]) : '';
?>
<?php $this->load->view('admin/partials/breadcrumb', array('items' => array(array('label' => $this->moduleName, 'url' => ADMIN_URL.$this->controller), array('label' => $crumb.' '.$this->moduleNameSingular, 'active' => TRUE)))); ?>
<?php if (!empty($form_error)) { ?><div class="row alertrow"><div class="col-md-12"><div class="alert alert-danger alert-dismissible fade show" role="alert"><strong>Could not save the tour slot.</strong> <?php echo htmlspecialchars($form_error, ENT_QUOTES, 'UTF-8'); ?><button type="button" class="btn-close alertBox crud-alert-close" data-bs-dismiss="alert" aria-label="Dismiss notification"></button></div></div></div><?php } ?>

<section class="admin-records-listing" aria-labelledby="tour-slots-title"<?php if ($isEdit) { ?> data-translation-poll data-module="tour_slots" data-status-url="<?php echo base_url('manage/translations/statuses'); ?>" data-poll-seconds="4" data-locale="<?php echo htmlspecialchars($active_locale, ENT_QUOTES, 'UTF-8'); ?>"<?php } ?>>
    <?php $this->load->view('admin/partials/module_header', array('title' => $this->moduleName, 'description' => $this->moduleDesc, 'id' => 'tour-slots-title')); ?>
    <div class="card admin-card">
        <div class="card-header d-flex flex-wrap align-items-center justify-content-between gap-3"><h2 class="card-title mb-0"><?php echo $crumb; ?> <?php echo htmlspecialchars($this->moduleNameSingular, ENT_QUOTES, 'UTF-8'); ?></h2><?php if ($isEdit) { $this->load->view('admin/partials/manage_language_switcher', array('language_base_url' => $languageBaseUrl, 'locales' => $manage_locales, 'locale' => $active_locale, 'translation_module' => 'tour_slots', 'translation_entity_id' => (int) $tbl_data[$this->pKey], 'translation_status' => $translationStatus, 'translation_ready' => $translationStatus === 'SUCCEEDED')); } ?></div>
        <div class="card-body">
            <form id="<?php echo htmlspecialchars($this->controller, ENT_QUOTES, 'UTF-8'); ?>_form" name="<?php echo htmlspecialchars($this->controller, ENT_QUOTES, 'UTF-8'); ?>_form" method="post" action="<?php echo $action; ?>" class="validate" data-manage-language-form>
                <input type="hidden" name="active_locale" value="<?php echo htmlspecialchars($active_locale, ENT_QUOTES, 'UTF-8'); ?>">
                <input type="hidden" name="redirect_lang" value="" data-redirect-locale>
                <?php if ($this->config->item('csrf_protection')) { ?><input type="hidden" name="<?php echo htmlspecialchars($this->security->get_csrf_token_name(), ENT_QUOTES, 'UTF-8'); ?>" value="<?php echo htmlspecialchars($this->security->get_csrf_hash(), ENT_QUOTES, 'UTF-8'); ?>"><?php } ?>

                <div class="row">
                    <div class="admin-field mb-3 col-md-6"><label class="form-label is-required" for="slot_title">Title</label><input type="text" name="slot_title" id="slot_title" maxlength="255" value="<?php echo $value('slot_title'); ?>" class="form-control" data-validate="required" required placeholder="Morning tour slot"></div>
                    <div class="col-md-6"><?php $this->load->view('admin/partials/icon_picker_field', array('iconPickerName' => 'slot_icon', 'iconPickerId' => 'slot_icon', 'iconPickerLabel' => 'Icon (Optional)', 'iconPickerValue' => isset($tbl_data['slot_icon']) ? $tbl_data['slot_icon'] : '', 'iconPickerHelp' => 'Choose an icon that represents this time slot.')); ?></div>
                </div>
                <div class="admin-field mb-3"><label class="form-label is-required" for="localized_name">Name </label><input type="text" name="localized_name" id="localized_name" maxlength="255" value="<?php echo $localizedValue('localized_name'); ?>" class="form-control" data-translation-field="localized_name" data-validate="required" required dir="<?php echo htmlspecialchars($localeDetails['direction'], ENT_QUOTES, 'UTF-8'); ?>"<?php echo $active_locale === 'ar' ? ' lang="ar"' : ''; ?> placeholder="<?php echo $active_locale === 'ar' ? 'الفترة الصباحية' : 'Morning slot'; ?>"></div>
                <div class="admin-field mb-3"><label class="form-label" for="localized_details">Details </label><textarea rows="5" name="localized_details" id="localized_details" class="form-control" data-translation-field="localized_details" dir="<?php echo htmlspecialchars($localeDetails['direction'], ENT_QUOTES, 'UTF-8'); ?>"<?php echo $active_locale === 'ar' ? ' lang="ar"' : ''; ?> placeholder="Slot details"><?php echo $localizedValue('localized_details'); ?></textarea></div>
                <div class="row">
                    <div class="admin-field mb-3 col-md-6"><label class="form-label is-required" for="slot_start_time">Start Time</label><input type="text" name="slot_start_time" id="slot_start_time" maxlength="8" value="<?php echo $timeValue('slot_start_time'); ?>" class="form-control timepicker" data-validate="required" required data-show-meridian="false" data-show-seconds="false" data-minute-step="15" autocomplete="off"></div>
                    <div class="admin-field mb-3 col-md-6"><label class="form-label is-required" for="slot_end_time">End Time</label><input type="text" name="slot_end_time" id="slot_end_time" maxlength="8" value="<?php echo $timeValue('slot_end_time'); ?>" class="form-control timepicker" data-validate="required,notlessthanfield[#slot_start_time],slotdurationhours[#slot_start_time]" data-msg-notlessthanfield="End time cannot be earlier than the start time." data-msg-slotdurationhours="Tour slots can only be 2, 4, 6, or 8 hours long. Please adjust the start or end time so the duration matches one of these lengths." required  data-show-meridian="false" data-show-seconds="false" data-minute-step="15" autocomplete="off"></div>
                </div>
                <div class="admin-field mb-3"><label class="form-label" for="slot_status">Status</label><select class="form-select select2" name="slot_status" id="slot_status" data-minimum-results-for-search="-1"><option value="Enable" <?php echo (!isset($tbl_data['slot_status']) || $tbl_data['slot_status'] === 'Enable') ? 'selected' : ''; ?>>Enable</option><option value="Disable" <?php echo (isset($tbl_data['slot_status']) && $tbl_data['slot_status'] === 'Disable') ? 'selected' : ''; ?>>Disable</option></select></div>
                <div class="admin-form-actions"><a class="btn btn-outline-secondary" href="<?php echo ADMIN_URL.$this->controller; ?>">Cancel</a><button type="submit" class="btn btn-primary" data-save-button><span data-save-label>Save <?php echo htmlspecialchars($this->moduleNameSingular, ENT_QUOTES, 'UTF-8'); ?></span></button></div>
            </form>
        </div>
    </div>
</section>
<?php if ($isEdit) $this->load->view('admin/partials/manage_language_switch_modal'); ?>
<script>
jQuery(function($) {
    $('#slot_start_time').on('keyup change blur', function() {
        var $end = $('#slot_end_time'), form = $end.closest('form')[0];
        if (form && $.data(form, 'validator') && $.trim($end.val()) !== '') $.data(form, 'validator').element($end[0]);
    });
});
</script>
