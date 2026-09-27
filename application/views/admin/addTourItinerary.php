<?php
$isEdit = !empty($is_edit);
$id = $isEdit ? (int) $tbl_data[$this->pKey] : 0;
$crumb = $isEdit ? 'Edit' : 'Add';
$action = base_url('manage/'.$this->controller.'/'.($isEdit ? 'editRecord/'.$itinerary_tour_id.'/'.$id : 'addRecord/'.$itinerary_tour_id));
$locale = $manage_locales[$active_locale];
$translationStatus = $isEdit && !empty($translation_state['status']) ? $translation_state['status'] : 'MISSING';
$currentImage = !empty($tbl_data['image']) ? basename((string) $tbl_data['image']) : '';
$currentAttractionId = !empty($tbl_data['attraction_id']) ? (int) $tbl_data['attraction_id'] : 0;
$value = function ($key) use ($localized_values) { return isset($localized_values[$key]) ? htmlspecialchars((string) $localized_values[$key], ENT_QUOTES, 'UTF-8') : ''; };
?>
<?php $this->load->view('admin/partials/breadcrumb', array('items' => array(array('label' => 'Tours', 'url' => ADMIN_URL.'tours'), array('label' => $tourData['tour_name'].' Itineraries', 'url' => ADMIN_URL.$this->controller.'/index/'.$itinerary_tour_id), array('label' => $crumb.' '.$this->moduleNameSingular, 'active' => TRUE)))); ?>
<?php if (!empty($form_error)) { ?><div class="alert alert-danger" role="alert"><strong>Could not save the itinerary.</strong> <?php echo htmlspecialchars($form_error, ENT_QUOTES, 'UTF-8'); ?></div><?php } ?>
<section class="admin-records-listing" aria-labelledby="tour-itinerary-form-title"<?php if ($isEdit) { ?> data-translation-poll data-module="tour_itineraries" data-status-url="<?php echo base_url('manage/translations/statuses'); ?>" data-poll-seconds="4" data-locale="<?php echo htmlspecialchars($active_locale, ENT_QUOTES, 'UTF-8'); ?>"<?php } ?>>
    <?php $this->load->view('admin/partials/module_header', array('title' => $tourData['tour_name'].' Itineraries', 'description' => 'Add or update a day-by-day itinerary item.', 'id' => 'tour-itinerary-form-title')); ?>
    <div class="card admin-card">
        <div class="card-header d-flex flex-wrap align-items-center justify-content-between gap-3">
            <h2 class="card-title mb-0"><?php echo $crumb; ?> <?php echo htmlspecialchars($this->moduleNameSingular, ENT_QUOTES, 'UTF-8'); ?></h2>
            <?php if ($isEdit) { $this->load->view('admin/partials/manage_language_switcher', array('language_base_url' => base_url('manage/'.$this->controller.'/control/'.$itinerary_tour_id.'/'.$id), 'locales' => $manage_locales, 'locale' => $active_locale, 'translation_module' => 'tour_itineraries', 'translation_entity_id' => $id, 'translation_status' => $translationStatus, 'translation_ready' => $translationStatus === 'SUCCEEDED')); } ?>
        </div>
        <div class="card-body">
            <form id="tour-itinerary-form" method="post" action="<?php echo $action; ?>" enctype="multipart/form-data" class="validate" novalidate data-manage-language-form data-submit-lock>
                <input type="hidden" name="active_locale" value="<?php echo htmlspecialchars($active_locale, ENT_QUOTES, 'UTF-8'); ?>">
                <input type="hidden" name="redirect_lang" value="" data-redirect-locale>
                <?php if ($this->config->item('csrf_protection')) { ?><input type="hidden" name="<?php echo htmlspecialchars($this->security->get_csrf_token_name(), ENT_QUOTES, 'UTF-8'); ?>" value="<?php echo htmlspecialchars($this->security->get_csrf_hash(), ENT_QUOTES, 'UTF-8'); ?>"><?php } ?>

                <div class="row">
                    <div class="admin-field mb-3 col-md-12">
                        <label class="form-label" for="attraction_id">Attraction</label>
                        <select autocomplete="off" name="attraction_id" id="attraction_id" class="select2" data-placeholder="No attraction">
                            <option value="" data-short-desc="">No attraction</option>
                            <?php foreach ($attractions as $attraction) {
                                $attractionShortDesc = $active_locale === 'ar' ? $attraction['attraction_short_desc_ar'] : $attraction['attraction_short_desc'];
                            ?>
                                <option value="<?php echo (int) $attraction['attraction_id']; ?>"<?php echo $currentAttractionId === (int) $attraction['attraction_id'] ? ' selected' : ''; ?> data-short-desc="<?php echo htmlspecialchars((string) $attractionShortDesc, ENT_QUOTES, 'UTF-8'); ?>"><?php echo htmlspecialchars((string) $attraction['attraction_name'], ENT_QUOTES, 'UTF-8'); ?></option>
                            <?php } ?>
                        </select>
                    </div>
                    
                </div>

                <div class="row">
                    <div class="admin-field mb-3 col-md-6">
                        <label class="form-label is-required" for="localized_title">Title </label>
                        <input type="text" class="form-control" name="localized_title" id="localized_title" maxlength="255" value="<?php echo $value('localized_title'); ?>" data-translation-field="localized_title" data-validate="required" required dir="<?php echo $locale['direction']; ?>"<?php echo $active_locale === 'ar' ? ' lang="ar"' : ''; ?>>
                    </div>
                    <div class="admin-field mb-3 col-md-6">
                        <label class="form-label is-required" for="localized_day_hour">Day / Hour / Time / Step </label>
                        <input type="text" class="form-control" name="localized_day_hour" id="localized_day_hour" maxlength="255" value="<?php echo $value('localized_day_hour'); ?>" data-translation-field="localized_day_hour" data-validate="required" required placeholder="Day 1, 09:00 AM, START, or Stop 3" dir="<?php echo $locale['direction']; ?>"<?php echo $active_locale === 'ar' ? ' lang="ar"' : ''; ?>>
                    </div>
                </div>

                <div class="admin-field mb-4">
                    <label class="form-label is-required" for="localized_details">Details </label>
                    <textarea class="form-control" name="localized_details" id="localized_details" rows="3" data-translation-field="localized_details" data-validate="required" required dir="<?php echo $locale['direction']; ?>"<?php echo $active_locale === 'ar' ? ' lang="ar"' : ''; ?>><?php echo $value('localized_details'); ?></textarea>
                </div>

                <?php $this->load->view('admin/partials/file_upload', array('name' => 'uploadfile', 'id' => 'uploadfile', 'label' => 'Image', 'allowed_types' => UPLOAD_IMAGE_MIMES, 'required' => FALSE, 'current_path' => $currentImage !== '' ? 'assets/frontend/images/tour-itineraries/'.$currentImage : '', 'help' => 'JPG, JPEG, PNG, or WebP. Maximum size: '.UPLOAD_SIZE_MB.' MB.', 'recommended_size' => '1440 × 960px (3:2)', 'size_note' => 'Shown as a small thumbnail beside the itinerary step. Other ratios are cropped from the centre, so keep the subject in the middle. Minimum 960 × 640px.', 'preview_alt' => 'Current itinerary image', 'preview_shape' => 'square', 'preview_size' => 180)); ?>

                <div class="row">
                    <div class="admin-field mb-3 col-md-12">
                        <label class="form-label" for="status">Status</label>
                        <select class="form-select select2" name="status" id="status" data-minimum-results-for-search="-1">
                            <option value="Enable"<?php echo !isset($tbl_data['status']) || $tbl_data['status'] === 'Enable' ? ' selected' : ''; ?>>Enabled</option>
                            <option value="Disable"<?php echo isset($tbl_data['status']) && $tbl_data['status'] === 'Disable' ? ' selected' : ''; ?>>Disabled</option>
                        </select>
                    </div>
                </div>

                <div class="admin-form-actions">
                    <a class="btn btn-outline-secondary" href="<?php echo ADMIN_URL.$this->controller.'/index/'.$itinerary_tour_id; ?>">Cancel</a>
                    <button type="submit" class="btn btn-primary" data-save-button><span data-save-label>Save <?php echo htmlspecialchars($this->moduleNameSingular, ENT_QUOTES, 'UTF-8'); ?></span></button>
                </div>
            </form>
        </div>
    </div>
</section>
<script>
    (function ($) {
        var attractionSelect = document.getElementById('attraction_id');
        var details = document.getElementById('localized_details');
        if (!attractionSelect || !details || !$) return;
        // Select2 fires select2:select / select2:clear through jQuery's own event
        // system (not a native DOM event), so these must be bound with jQuery's .on()
        // rather than addEventListener. Using the plain "change" event instead would
        // also fire once when Select2 initializes on a select that already has a
        // value, wiping this field on every page load.
        $(attractionSelect).on('select2:select', function () {
            var selectedOption = attractionSelect.options[attractionSelect.selectedIndex];
            details.value = selectedOption ? (selectedOption.getAttribute('data-short-desc') || '') : '';
        });
        $(attractionSelect).on('select2:clear', function () {
            details.value = '';
        });
    }(window.jQuery));
</script>
<?php if ($isEdit) { $this->load->view('admin/partials/manage_language_switch_modal'); } ?>
