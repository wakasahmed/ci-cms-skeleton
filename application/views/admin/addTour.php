<?php
$isEdit = ($alert === 'edit' && !empty($tbl_data[$this->pKey]));
$crumb = $isEdit ? 'Edit' : 'Add';
$action = base_url('manage/'.$this->controller.'/'.($isEdit ? 'editRecord/'.$tbl_data[$this->pKey] : 'addRecord'));
$value = function ($key) use ($tbl_data) { return isset($tbl_data[$key]) ? htmlspecialchars((string) $tbl_data[$key], ENT_QUOTES, 'UTF-8') : ''; };
$localizedValue = function ($key) use ($localized_values) { return isset($localized_values[$key]) ? htmlspecialchars((string) $localized_values[$key], ENT_QUOTES, 'UTF-8') : ''; };
$tourImage = isset($tbl_data[$this->colPrefix.'image']) ? basename((string) $tbl_data[$this->colPrefix.'image']) : '';
$tourImageAr = isset($tbl_data[$this->colPrefix.'image_ar']) ? basename((string) $tbl_data[$this->colPrefix.'image_ar']) : '';
$bgImage = isset($tbl_data[$this->colPrefix.'bg_image']) ? basename((string) $tbl_data[$this->colPrefix.'bg_image']) : '';
$bgImageAr = isset($tbl_data[$this->colPrefix.'bg_image_ar']) ? basename((string) $tbl_data[$this->colPrefix.'bg_image_ar']) : '';
$localeSuffix = $active_locale === 'ar' ? '_ar' : '';
$currencyUnit = htmlspecialchars((string) $currency_unit, ENT_QUOTES, 'UTF-8');
$sharingImage = isset($tbl_data['og_image'.$localeSuffix]) ? basename((string) $tbl_data['og_image'.$localeSuffix]) : '';
$tourName = isset($tbl_data[$this->colPrefix.'name']) ? trim((string) $tbl_data[$this->colPrefix.'name']) : 'Tour';
$localeDetails = $manage_locales[$active_locale];
$translationStatus = $isEdit && !empty($translation_state['status']) ? $translation_state['status'] : 'MISSING';
$languageBaseUrl = $isEdit ? base_url('manage/'.$this->controller.'/control/edit/'.(int) $tbl_data[$this->pKey]) : '';
$assignedAttractions = isset($tour_attractions) ? $tour_attractions : array();
$assignedCategories = isset($tour_categories_assigned) ? $tour_categories_assigned : array();
$assignedVehicles = isset($tour_vehicles_assigned) ? $tour_vehicles_assigned : array();
$assignedSlots = isset($tour_slots_assigned) ? $tour_slots_assigned : array();
$attractionsById = array();
if (!empty($attractions)) foreach ($attractions as $a) $attractionsById[(int) $a['attraction_id']] = $a['attraction_name'];
$tourTypeUri = function ($type) { return $type === 'Experience' ? EXPERIENCE_URI : TOUR_URI; };
$slugPrefixFor = function ($type) use ($active_locale, $tourTypeUri) {
    return '/'.($active_locale === 'ar' ? 'ar/' : 'en/').$tourTypeUri($type);
};
$slugPrefix = $slugPrefixFor($value('tour_type') !== '' ? $value('tour_type') : 'Tour');
$slugPrefixTour = $slugPrefixFor('Tour');
$slugPrefixExperience = $slugPrefixFor('Experience');
$slugPlaceholderFor = function ($type) { return $type === 'Experience' ? 'my-experience' : 'my-tour'; };
$slugPlaceholder = $slugPlaceholderFor($value('tour_type') !== '' ? $value('tour_type') : 'Tour');
$slugPlaceholderTour = $slugPlaceholderFor('Tour');
$slugPlaceholderExperience = $slugPlaceholderFor('Experience');
$isExperienceType = $value('tour_type') === 'Experience';
$textField = function ($name, $label, $type = 'input', $maxlength = 255) use ($localizedValue, $localeDetails, $active_locale) { ?>
    <div class="admin-field mb-3">
        <label class="form-label" for="<?php echo $name; ?>"><?php echo htmlspecialchars($label, ENT_QUOTES, 'UTF-8'); ?></label>
        <?php if ($type === 'textarea') { ?>
        <textarea name="<?php echo $name; ?>" id="<?php echo $name; ?>" rows="3" class="form-control" data-translation-field="<?php echo $name; ?>" dir="<?php echo $localeDetails['direction']; ?>"<?php echo $active_locale === 'ar' ? ' lang="ar"' : ''; ?>><?php echo $localizedValue($name); ?></textarea>
        <?php } else { ?>
        <input type="text" name="<?php echo $name; ?>" id="<?php echo $name; ?>" maxlength="<?php echo (int) $maxlength; ?>" value="<?php echo $localizedValue($name); ?>" class="form-control" data-translation-field="<?php echo $name; ?>" dir="<?php echo $localeDetails['direction']; ?>"<?php echo $active_locale === 'ar' ? ' lang="ar"' : ''; ?>>
        <?php } ?>
    </div>
<?php };
?>
<?php $this->load->view('admin/partials/breadcrumb', array('items' => array(array('label' => $this->moduleName, 'url' => ADMIN_URL.$this->controller), array('label' => $crumb.' '.$this->moduleNameSingular, 'active' => TRUE)))); ?>

<?php if (!empty($form_error)) { ?>
<div class="row alertrow"><div class="col-md-12"><button class="btn-close alertBox" data-bs-dismiss="alert" aria-label="Dismiss"></button><div class="alert alert-danger"><strong>Could not save the tour.</strong> <?php echo htmlspecialchars($form_error, ENT_QUOTES, 'UTF-8'); ?></div></div></div>
<?php } ?>

<section class="admin-records-listing admin-form-screen" aria-labelledby="tours-title"<?php if ($isEdit) { ?> data-translation-poll data-module="tours" data-status-url="<?php echo base_url('manage/translations/statuses'); ?>" data-poll-seconds="4" data-locale="<?php echo htmlspecialchars($active_locale, ENT_QUOTES, 'UTF-8'); ?>"<?php } ?>>
    <?php $this->load->view('admin/partials/module_header', array('title' => $this->moduleName, 'description' => $this->moduleDesc, 'id' => 'tours-title')); ?>

    <div class="card admin-card">
        <div class="card-header d-flex flex-wrap align-items-center justify-content-between gap-3"><h2 class="card-title mb-0"><?php echo $crumb; ?> <?php echo htmlspecialchars($this->moduleNameSingular, ENT_QUOTES, 'UTF-8'); ?></h2><?php if ($isEdit) { $this->load->view('admin/partials/manage_language_switcher', array('language_base_url' => $languageBaseUrl, 'locales' => $manage_locales, 'locale' => $active_locale, 'translation_module' => 'tours', 'translation_entity_id' => $tbl_data[$this->pKey], 'translation_status' => $translationStatus, 'translation_ready' => $translationStatus === 'SUCCEEDED')); } ?></div>
        <div class="card-body">
        <form id="<?php echo $this->controller; ?>_form" name="<?php echo $this->controller; ?>_form" method="post" action="<?php echo $action; ?>" enctype="multipart/form-data" class="validate" data-manage-language-form data-submit-lock novalidate>
            <input type="hidden" name="active_locale" value="<?php echo htmlspecialchars($active_locale, ENT_QUOTES, 'UTF-8'); ?>">
            <input type="hidden" name="redirect_lang" value="" data-redirect-locale>
            <?php if ($this->config->item('csrf_protection')) { ?><input type="hidden" name="<?php echo htmlspecialchars($this->security->get_csrf_token_name(), ENT_QUOTES, 'UTF-8'); ?>" value="<?php echo htmlspecialchars($this->security->get_csrf_hash(), ENT_QUOTES, 'UTF-8'); ?>"><?php } ?>

            <div class="accordion admin-form-accordion" id="tourEditorAccordion">
                <article class="accordion-item admin-form-accordion-item">
                    <h3 class="accordion-header"><button class="accordion-button" type="button" data-bs-toggle="collapse" data-bs-target="#tour-content-panel" aria-expanded="true"><span class="admin-form-section-icon"><i class="bi bi-signpost-2"></i></span><span class="admin-form-section-heading"><span class="admin-form-section-title">Tour Content</span><span class="admin-form-section-description">Name, type, URL, attractions, and imagery</span></span><span class="badge text-bg-danger admin-form-section-status">Required</span></button></h3>
                    <div id="tour-content-panel" class="accordion-collapse collapse show">
                        <div class="accordion-body">
                            <div class="row">
                                <div class="admin-field mb-3 col-md-6"><label class="form-label is-required" for="localized_name">Name </label><input type="text" name="localized_name" id="localized_name" maxlength="255" value="<?php echo $localizedValue('localized_name'); ?>" class="form-control<?php echo $isEdit ? '' : ' auto-slug-source'; ?>" data-translation-field="localized_name" data-validate="required"<?php if (!$isEdit) { ?> data-slug-target="#tour_slug" data-slug-language="<?php echo htmlspecialchars($active_locale, ENT_QUOTES, 'UTF-8'); ?>"<?php } ?> required dir="<?php echo $localeDetails['direction']; ?>"<?php echo $active_locale === 'ar' ? ' lang="ar"' : ''; ?> placeholder="<?php echo $active_locale === 'ar' ? 'اسم الجولة' : "Treasures of Prophet's"; ?>"></div>
                                <div class="admin-field mb-3 col-md-6">
                                    <label class="form-label is-required" for="tour_type">Type</label>
                                    <select class="form-select select2" name="tour_type" id="tour_type" data-minimum-results-for-search="-1" required>
                                        <option value="Tour" <?php echo ($value('tour_type') === 'Tour' || $value('tour_type') === '') ? 'selected' : ''; ?>>Tour</option>
                                        <option value="Experience" <?php echo $value('tour_type') === 'Experience' ? 'selected' : ''; ?>>Experience</option>
                                    </select>
                                </div>
                            </div>

                            <div class="admin-field mb-4">
                                <label class="form-label is-required" for="tour_slug">Slug</label>
                                <div class="input-group" dir="ltr"><span class="input-group-text" id="tour_slug_prefix" data-prefix-tour="<?php echo htmlspecialchars($slugPrefixTour, ENT_QUOTES, 'UTF-8'); ?>" data-prefix-experience="<?php echo htmlspecialchars($slugPrefixExperience, ENT_QUOTES, 'UTF-8'); ?>"><?php echo htmlspecialchars($slugPrefix, ENT_QUOTES, 'UTF-8'); ?></span><input type="text" name="tour_slug" id="tour_slug" maxlength="255" value="<?php echo $value('tour_slug'); ?>" class="form-control slug" placeholder="<?php echo $active_locale === 'ar' ? 'اسم-الجولة' : htmlspecialchars($slugPlaceholder, ENT_QUOTES, 'UTF-8'); ?>"<?php if ($active_locale !== 'ar') { ?> data-placeholder-tour="<?php echo htmlspecialchars($slugPlaceholderTour, ENT_QUOTES, 'UTF-8'); ?>" data-placeholder-experience="<?php echo htmlspecialchars($slugPlaceholderExperience, ENT_QUOTES, 'UTF-8'); ?>"<?php } ?> data-validate="required,maxlength[255]"<?php echo $active_locale === 'ar' ? ' lang="ar" dir="rtl"' : ''; ?> required><button type="button" class="btn btn-outline-secondary generate-slug" data-slug-source="#localized_name" data-slug-target="#tour_slug" data-slug-language="<?php echo htmlspecialchars($active_locale, ENT_QUOTES, 'UTF-8'); ?>" title="Generate slug from tour name" aria-label="Generate <?php echo htmlspecialchars($localeDetails['label'], ENT_QUOTES, 'UTF-8'); ?> slug from tour name"><i class="bi bi-arrow-clockwise" aria-hidden="true"></i><span class="d-none d-sm-inline ms-1">Generate</span></button></div>
                                <div class="form-text">Language-specific and automatically made unique when saved.</div>
                            </div>

                            <div class="admin-field mb-4">
                                <div class="d-flex align-items-center justify-content-between gap-2 mb-2">
                                    <label class="form-label mb-0" for="tour_categories">Categories</label>
                                    <button class="btn btn-sm btn-outline-secondary" type="button" id="addNewTourCat">
                                        <i class="bi bi-plus-circle" aria-hidden="true"></i> Add New Category
                                    </button>
                                </div>
                                <select autocomplete="off" name="tour_categories[]" id="tour_categories" class="form-select select2" multiple>
                                    <?php if (!empty($tour_categories)) { foreach ($tour_categories as $c) { ?>
                                        <option value="<?php echo (int) $c['cat_id']; ?>" <?php echo in_array((int) $c['cat_id'], $assignedCategories, TRUE) ? 'selected' : ''; ?>><?php echo htmlspecialchars($c['cat_name'], ENT_QUOTES, 'UTF-8'); ?></option>
                                    <?php } } ?>
                                </select>
                            </div>

                            <?php $textField('localized_short_description', 'Short Description', 'textarea'); ?>

                            <div class="row">
                                <div class="col-md-6"><?php $textField('localized_group_size', 'Group Size'); ?></div>
                                <div class="col-md-6"><?php $textField('localized_walking', 'Walking'); ?></div>
                                <div class="col-md-6"><?php $textField('localized_pickup', 'Pickup'); ?></div>
                                <div class="col-md-6"><?php $textField('localized_duration', 'Duration'); ?></div>
                                <div class="col-md-6 tour-experience-only-group"<?php echo $isExperienceType ? '' : ' hidden'; ?>><?php $textField('localized_lang', 'Experience Languages'); ?></div>
                            </div>

                            <div class="admin-field mb-3 hidden">
                                <label class="form-label">Reservation Fee</label>
                                <input type="text" name="tour_fee" id="tour_fee" maxlength="100" value="<?php echo $value('tour_fee'); ?>" class="form-control">
                            </div>
                            <div class="admin-field mb-3 hidden">
                                <label class="form-label">No of Days</label>
                                <input type="text" name="tour_days" id="tour_days" maxlength="100" value="<?php echo $value('tour_days'); ?>" class="form-control">
                            </div>

                            <div class="admin-field mb-4"><?php $this->load->view('admin/partials/file_upload', array(
                                'name' => 'uploadfile', 'id' => 'uploadfile', 'label' => 'Tour Image', 'allowed_types' => UPLOAD_IMAGE_MIMES, 'required' => TRUE,
                                'current_path' => $tourImage !== '' ? 'assets/frontend/images/tours/'.$tourImage : '', 'preview_alt' => $tourName, 'preview_shape' => 'square', 'preview_size' => 140,
                                'help' => 'Upload a JPEG, PNG, or WebP image. Leave empty to keep the current image.',
                                'recommended_size' => '1440 × 960px (3:2)',
                                'size_note' => 'Shown on tour cards. Other ratios are cropped from the centre. Minimum 960 × 640px.',
                            )); ?></div>

                            <?php if ($active_locale === 'en') { ?>
                            <div class="admin-field mb-2"><?php $this->load->view('admin/partials/file_upload', array(
                                'name' => 'uploadfile2', 'id' => 'uploadfile2', 'label' => 'Background Image (Optional)', 'allowed_types' => UPLOAD_IMAGE_MIMES, 'required' => FALSE,
                                'current_path' => $bgImage !== '' ? 'assets/frontend/images/tours/'.$bgImage : '', 'preview_alt' => $tourName.' background', 'preview_shape' => 'square', 'preview_size' => 160,
                                'help' => 'Leave empty to keep the current image.',
                                'recommended_size' => '1440 × 960px (3:2)',
                                'size_note' => 'Hero background of the tour page. It is cropped from the centre to 3:2 and the hero then trims the top and bottom, so keep the subject in the middle. Minimum 960 × 640px.',
                            )); ?></div>
                            <?php if ($isEdit && image_thumb_path('assets/frontend/images/tours/' . $bgImage) !== FALSE) { ?><div class="admin-current-file-remove"><div class="admin-current-file-actions"><button type="button" class="btn btn-outline-danger btn-sm removeFile" data-controller="<?php echo $this->controller; ?>" data-file-name="tour_bg_image" data-file-id="<?php echo (int) $tbl_data[$this->pKey]; ?>"><i class="bi bi-trash" aria-hidden="true"></i> Remove Image</button></div></div><?php } ?>
                            <?php } else { ?>
                            <div class="admin-field mb-2"><?php $this->load->view('admin/partials/file_upload', array(
                                'name' => 'uploadfile4', 'id' => 'uploadfile4', 'label' => 'Tour Image (Arabic, Optional)', 'allowed_types' => UPLOAD_IMAGE_MIMES, 'required' => FALSE,
                                'current_path' => $tourImageAr !== '' ? 'assets/frontend/images/tours/'.$tourImageAr : '', 'preview_alt' => $tourName.' Arabic', 'preview_shape' => 'square', 'preview_size' => 140,
                                'help' => 'Falls back to the main Tour Image above when not uploaded.',
                                'recommended_size' => '1440 × 960px (3:2)',
                                'size_note' => 'Shown on Arabic tour cards. Other ratios are cropped from the centre. Minimum 960 × 640px.',
                            )); ?></div>
                            <?php if ($isEdit && image_thumb_path('assets/frontend/images/tours/' . $tourImageAr) !== FALSE) { ?><div class="admin-current-file-remove"><div class="admin-current-file-actions"><button type="button" class="btn btn-outline-danger btn-sm removeFile" data-controller="<?php echo $this->controller; ?>" data-file-name="tour_image_ar" data-file-id="<?php echo (int) $tbl_data[$this->pKey]; ?>"><i class="bi bi-trash" aria-hidden="true"></i> Remove Image</button></div></div><?php } ?>

                            <div class="admin-field mb-2"><?php $this->load->view('admin/partials/file_upload', array(
                                'name' => 'uploadfile3', 'id' => 'uploadfile3', 'label' => 'Background Image (Arabic, Optional)', 'allowed_types' => UPLOAD_IMAGE_MIMES, 'required' => FALSE,
                                'current_path' => $bgImageAr !== '' ? 'assets/frontend/images/tours/'.$bgImageAr : '', 'preview_alt' => $tourName.' Arabic background', 'preview_shape' => 'square', 'preview_size' => 160,
                                'help' => 'Leave empty to keep the current image.',
                                'recommended_size' => '1440 × 960px (3:2)',
                                'size_note' => 'Hero background of the Arabic tour page. It is cropped from the centre to 3:2 and the hero then trims the top and bottom, so keep the subject in the middle. Minimum 960 × 640px.',
                            )); ?></div>
                            <?php if ($isEdit && image_thumb_path('assets/frontend/images/tours/' . $bgImageAr) !== FALSE) { ?><div class="admin-current-file-remove"><div class="admin-current-file-actions"><button type="button" class="btn btn-outline-danger btn-sm removeFile" data-controller="<?php echo $this->controller; ?>" data-file-name="tour_bg_image_ar" data-file-id="<?php echo (int) $tbl_data[$this->pKey]; ?>"><i class="bi bi-trash" aria-hidden="true"></i> Remove Image</button></div></div><?php } ?>
                            <?php } ?>

                            <div class="admin-field mb-0"><label class="form-label" for="tour_status">Status</label><select class="form-select select2" name="tour_status" id="tour_status" data-minimum-results-for-search="-1"><option value="Enable" <?php echo (!isset($tbl_data['tour_status']) || $tbl_data['tour_status'] === 'Enable') ? 'selected' : ''; ?>>Enable</option><option value="Disable" <?php echo (isset($tbl_data['tour_status']) && $tbl_data['tour_status'] === 'Disable') ? 'selected' : ''; ?>>Disable</option></select></div>
                        </div>
                    </div>
                </article>

                <article class="accordion-item admin-form-accordion-item">
                    <h3 class="accordion-header"><button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#tour-pricing-panel" aria-expanded="false"><span class="admin-form-section-icon"><i class="bi bi-currency-dollar"></i></span><span class="admin-form-section-heading"><span class="admin-form-section-title">Pricing &amp; Slots</span><span class="admin-form-section-description">Starting price, hourly rates, and available time slots</span></span><span class="badge text-bg-danger admin-form-section-status">Required</span></button></h3>
                    <div id="tour-pricing-panel" class="accordion-collapse collapse">
                        <div class="accordion-body">
                            <?php foreach (array(
                                array('label' => 'Price per person (Including the entrance fee, tickets, parking or other amenities)', 'prefix' => 'tour_price', 'help' => 'Enter a price for at least one of the hourly options.', 'requireOneGroup' => TRUE, 'guideOnly' => FALSE),
                                array('label' => 'Arabic Speaking Guide', 'prefix' => 'tour_arabic_guide_price', 'help' => '', 'requireOneGroup' => FALSE, 'guideOnly' => TRUE),
                                array('label' => 'Foreign Speaking Guide', 'prefix' => 'tour_english_guide_price', 'help' => '', 'requireOneGroup' => FALSE, 'guideOnly' => TRUE),
                            ) as $priceGroup) { ?>
                            <div class="row align-items-end<?php echo $priceGroup['guideOnly'] ? ' tour-guide-price-group' : ''; ?>"<?php echo ($priceGroup['guideOnly'] && $isExperienceType) ? ' hidden' : ''; ?>>
                                <div class="col-lg-4">
                                    <p class="admin-form-colour-label"><?php echo htmlspecialchars($priceGroup['label'], ENT_QUOTES, 'UTF-8'); ?></p>
                                    <?php if ($priceGroup['help'] !== '') { ?><p class="form-text mt-n2 mb-2"><?php echo htmlspecialchars($priceGroup['help'], ENT_QUOTES, 'UTF-8'); ?></p><?php } ?>
                                </div>
                                <?php foreach (array(2, 4, 6, 8) as $hours) { $fieldName = $priceGroup['prefix'].'_'.$hours; ?>
                                <div class="admin-field mb-3 col-6 col-md-3 col-lg-2">
                                    <label class="form-label" for="<?php echo $fieldName; ?>"><?php echo (int) $hours; ?> Hours</label>
                                    <div class="input-group"><span class="input-group-text"><?php echo $currencyUnit; ?></span><input type="number" min="0" max="15000" step="1" name="<?php echo $fieldName; ?>" id="<?php echo $fieldName; ?>" value="<?php echo $value($fieldName); ?>" class="form-control<?php echo $priceGroup['requireOneGroup'] ? ' tour-hourly-price' : ''; ?>" data-validate="digits,min[0],max[15000]<?php echo $priceGroup['requireOneGroup'] ? ',onepositiveingroup[.tour-hourly-price]' : ''; ?>"></div>
                                </div>
                                <?php } ?>
                            </div>
                            <?php } ?>

                            <?php $textField('localized_price_details', 'Details', 'textarea'); ?>

                            <div class="admin-field mb-0">
                                <label class="form-label is-required" for="tour_slots">Slots</label>
                                <select autocomplete="off" name="tour_slots[]" id="tour_slots" class="form-select select2" multiple data-validate="required" required>
                                    <?php if (!empty($tour_slots)) { foreach ($tour_slots as $s) { ?>
                                        <option value="<?php echo (int) $s['slot_id']; ?>" <?php echo in_array((int) $s['slot_id'], $assignedSlots, TRUE) ? 'selected' : ''; ?>><?php echo htmlspecialchars($s['slot_title'], ENT_QUOTES, 'UTF-8'); ?></option>
                                    <?php } } ?>
                                </select>
                            </div>
                        </div>
                    </div>
                </article>

                <article class="accordion-item admin-form-accordion-item">
                    <h3 class="accordion-header"><button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#tour-vehicles-panel" aria-expanded="false"><span class="admin-form-section-icon"><i class="bi bi-car-front"></i></span><span class="admin-form-section-heading"><span class="admin-form-section-title">Vehicles</span><span class="admin-form-section-description">Available vehicles and vehicle overview content</span></span><span class="badge text-bg-danger admin-form-section-status">Required</span></button></h3>
                    <div id="tour-vehicles-panel" class="accordion-collapse collapse">
                        <div class="accordion-body">
                            <div class="admin-field mb-4">
                                <label class="form-label is-required" for="tour_vehicles">Vehicles</label>
                                <select autocomplete="off" name="tour_vehicles[]" id="tour_vehicles" class="form-select select2" multiple data-validate="required" required>
                                    <?php if (!empty($tour_vehicles)) { foreach ($tour_vehicles as $v) { ?>
                                        <option value="<?php echo (int) $v['vehicle_id']; ?>" <?php echo in_array((int) $v['vehicle_id'], $assignedVehicles, TRUE) ? 'selected' : ''; ?>><?php echo htmlspecialchars($v['vehicle_title'], ENT_QUOTES, 'UTF-8'); ?></option>
                                    <?php } } ?>
                                </select>
                            </div>
                            <div class="admin-field mb-0"><label class="form-label" for="localized_vehicle_text">Vehicle Text </label><?php echo $ckeditor_fields['localized_vehicle_text']; ?></div>
                        </div>
                    </div>
                </article>

                <article class="accordion-item admin-form-accordion-item">
                    <h3 class="accordion-header"><button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#tour-overview-panel" aria-expanded="false"><span class="admin-form-section-icon"><i class="bi bi-journal-text"></i></span><span class="admin-form-section-heading"><span class="admin-form-section-title">Overview</span><span class="admin-form-section-description">Overview content</span></span><span class="badge text-bg-light admin-form-section-status">Optional</span></button></h3>
                    <div id="tour-overview-panel" class="accordion-collapse collapse">
                        <div class="accordion-body">
                            <div class="admin-field mb-0"><label class="form-label" for="localized_overview">Overview </label><?php echo $ckeditor_fields['localized_overview']; ?></div>
                        </div>
                    </div>
                </article>

                <article class="accordion-item admin-form-accordion-item">
                    <h3 class="accordion-header"><button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#tour-highlights-panel" aria-expanded="false"><span class="admin-form-section-icon"><i class="bi bi-stars"></i></span><span class="admin-form-section-heading"><span class="admin-form-section-title">Highlights</span><span class="admin-form-section-description">Attractions and highlights content</span></span><span class="badge text-bg-light admin-form-section-status">Optional</span></button></h3>
                    <div id="tour-highlights-panel" class="accordion-collapse collapse">
                        <div class="accordion-body">
                            <div class="admin-field mb-4"><label class="form-label" for="localized_highlights">Highlights </label><?php echo $ckeditor_fields['localized_highlights']; ?></div>
                            <div class="admin-field mb-0">
                                <label class="form-label" for="tour_attractions">Attractions</label>
                                <select autocomplete="off" name="tour_attractions[]" id="tour_attractions" class="form-select select2" multiple data-sortable-list="tour_attractions_sort_list">
                                    <?php if (!empty($attractions)) { foreach ($attractions as $a) { ?>
                                        <option value="<?php echo (int) $a['attraction_id']; ?>" <?php echo in_array((int) $a['attraction_id'], $assignedAttractions, TRUE) ? 'selected' : ''; ?>><?php echo htmlspecialchars($a['attraction_name'], ENT_QUOTES, 'UTF-8'); ?></option>
                                    <?php } } ?>
                                </select>
                                <ul class="admin-selected-sort-list" id="tour_attractions_sort_list">
                                    <?php foreach ($assignedAttractions as $attractionId) { if (!isset($attractionsById[$attractionId])) continue; ?>
                                        <li class="admin-selected-sort-item" data-value="<?php echo (int) $attractionId; ?>">
                                            <span class="admin-selected-sort-handle" aria-hidden="true"><i class="bi bi-grip-vertical"></i></span>
                                            <span class="admin-selected-sort-label"><?php echo htmlspecialchars($attractionsById[$attractionId], ENT_QUOTES, 'UTF-8'); ?></span>
                                            <button type="button" class="admin-selected-sort-remove" aria-label="Remove <?php echo htmlspecialchars($attractionsById[$attractionId], ENT_QUOTES, 'UTF-8'); ?>"><i class="bi bi-x-lg" aria-hidden="true"></i></button>
                                        </li>
                                    <?php } ?>
                                </ul>
                                <?php if (!empty($assignedAttractions)) { ?><div class="form-text">Drag to reorder how attractions appear on the tour page.</div><?php } ?>
                            </div>
                            
                        </div>
                    </div>
                </article>

                <article class="accordion-item admin-form-accordion-item">
                    <h3 class="accordion-header"><button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#tour-itinerary-panel" aria-expanded="false"><span class="admin-form-section-icon"><i class="bi bi-map"></i></span><span class="admin-form-section-heading"><span class="admin-form-section-title">Itinerary</span><span class="admin-form-section-description">Day-by-day or step-by-step itinerary content</span></span><span class="badge text-bg-light admin-form-section-status">Optional</span></button></h3>
                    <div id="tour-itinerary-panel" class="accordion-collapse collapse">
                        <div class="accordion-body">
                            <div class="admin-field mb-0"><label class="form-label" for="localized_itinerary">Itinerary </label><?php echo $ckeditor_fields['localized_itinerary']; ?></div>
                        </div>
                    </div>
                </article>

                <article class="accordion-item admin-form-accordion-item">
                    <h3 class="accordion-header"><button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#tour-pinfo-panel" aria-expanded="false"><span class="admin-form-section-icon"><i class="bi bi-info-circle"></i></span><span class="admin-form-section-heading"><span class="admin-form-section-title">Practical Information</span><span class="admin-form-section-description">Logistics travellers need to know before booking</span></span><span class="badge text-bg-light admin-form-section-status">Optional</span></button></h3>
                    <div id="tour-pinfo-panel" class="accordion-collapse collapse">
                        <div class="accordion-body">
                            <div class="admin-field mb-4"><label class="form-label" for="localized_pinfo">Practical Information </label><?php echo $ckeditor_fields['localized_pinfo']; ?></div>
                            <div class="row">
                                <div class="col-md-6"><?php $textField('localized_pinfo_pickup', 'Pickup', 'textarea'); ?></div>
                                <div class="col-md-6"><?php $textField('localized_pinfo_departure_point', 'Departure point', 'textarea'); ?></div>
                                <div class="col-md-6"><?php $textField('localized_pinfo_departure_time', 'Departure time', 'textarea'); ?></div>
                                <div class="col-md-6"><?php $textField('localized_pinfo_duration', 'Duration', 'textarea'); ?></div>
                                <div class="col-md-6"><?php $textField('localized_pinfo_group_size', 'Group Size', 'textarea'); ?></div>
                                <div class="col-md-6"><?php $textField('localized_pinfo_transportation', 'Transportation', 'textarea'); ?></div>
                                <div class="col-md-6"><?php $textField('localized_pinfo_lang', 'Language', 'textarea'); ?></div>
                                <div class="col-md-6"><?php $textField('localized_pinfo_meals', 'Meals', 'textarea'); ?></div>
                                <div class="col-md-6"><?php $textField('localized_pinfo_refreshment', 'Refreshment / Snacks', 'textarea'); ?></div>
                                <div class="col-md-6"><?php $textField('localized_pinfo_weather', 'Weather', 'textarea'); ?></div>
                                <div class="col-md-6"><?php $textField('localized_pinfo_bring', 'Bring', 'textarea'); ?></div>
                                <div class="col-md-6"><?php $textField('localized_pinfo_access', 'Accessibility', 'textarea'); ?></div>
                                <div class="col-md-6"><?php $textField('localized_pinfo_family', 'Family friendly', 'textarea'); ?></div>
                                <div class="col-md-6"><?php $textField('localized_pinfo_return', 'Return', 'textarea'); ?></div>
                            </div>
                        </div>
                    </div>
                </article>

                <article class="accordion-item admin-form-accordion-item">
                    <h3 class="accordion-header"><button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#tour-gallery-panel" aria-expanded="false"><span class="admin-form-section-icon"><i class="bi bi-images"></i></span><span class="admin-form-section-heading"><span class="admin-form-section-title">Gallery & Tour Guide</span><span class="admin-form-section-description">Gallery content and tour guide details</span></span><span class="badge text-bg-light admin-form-section-status">Optional</span></button></h3>
                    <div id="tour-gallery-panel" class="accordion-collapse collapse">
                        <div class="accordion-body">
                            <div class="admin-field mb-4"><label class="form-label" for="localized_gallery">Gallery </label><?php echo $ckeditor_fields['localized_gallery']; ?></div>
                            <div class="admin-field mb-0"><label class="form-label" for="localized_guide_info">Tour Guide </label><?php echo $ckeditor_fields['localized_guide_info']; ?></div>
                        </div>
                    </div>
                </article>
                
                <article class="accordion-item admin-form-accordion-item">
                    <h3 class="accordion-header"><button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#tour-faqs-panel" aria-expanded="false"><span class="admin-form-section-icon"><i class="bi bi-question-circle"></i></span><span class="admin-form-section-heading"><span class="admin-form-section-title">FAQs</span><span class="admin-form-section-description">FAQ content and linked FAQ categories</span></span><span class="badge text-bg-light admin-form-section-status">Optional</span></button></h3>
                    <div id="tour-faqs-panel" class="accordion-collapse collapse">
                        <div class="accordion-body">
                            <div class="admin-field mb-4"><label class="form-label" for="localized_faqs">FAQs </label><?php echo $ckeditor_fields['localized_faqs']; ?></div>
                            <div class="row">
                                <div class="admin-field mb-3 col-md-6">
                                    <label class="form-label" for="tour_faqs_general">General FAQs</label>
                                    <select class="form-select select2" name="tour_faqs_general" id="tour_faqs_general">
                                        <option value="">None</option>
                                        <?php if (!empty($faqs_categories)) { foreach ($faqs_categories as $c) { ?>
                                            <option value="<?php echo (int) $c['cat_id']; ?>" <?php echo ($value('tour_faqs_general') !== '' && (int) $value('tour_faqs_general') === (int) $c['cat_id']) ? 'selected' : ''; ?>><?php echo htmlspecialchars($c['cat_name'], ENT_QUOTES, 'UTF-8'); ?><?php echo $c['cat_status'] === 'Disable' ? ' (Disabled)' : ''; ?></option>
                                        <?php } } ?>
                                    </select>
                                </div>
                                <div class="admin-field mb-3 col-md-6">
                                    <label class="form-label" for="tour_faqs_specific">Tour Specific FAQs</label>
                                    <select class="form-select select2" name="tour_faqs_specific" id="tour_faqs_specific">
                                        <option value="">None</option>
                                        <?php if (!empty($faqs_categories)) { foreach ($faqs_categories as $c) { ?>
                                            <option value="<?php echo (int) $c['cat_id']; ?>" <?php echo ($value('tour_faqs_specific') !== '' && (int) $value('tour_faqs_specific') === (int) $c['cat_id']) ? 'selected' : ''; ?>><?php echo htmlspecialchars($c['cat_name'], ENT_QUOTES, 'UTF-8'); ?><?php echo $c['cat_status'] === 'Disable' ? ' (Disabled)' : ''; ?></option>
                                        <?php } } ?>
                                    </select>
                                </div>
                            </div>
                        </div>
                    </div>
                </article>

                <article class="accordion-item admin-form-accordion-item">
                    <h3 class="accordion-header"><button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#tour-search-panel" aria-expanded="false"><span class="admin-form-section-icon"><i class="bi bi-search"></i></span><span class="admin-form-section-heading"><span class="admin-form-section-title">Search Visibility</span><span class="admin-form-section-description">Search result text and crawler controls</span></span><span class="badge text-bg-light admin-form-section-status">Optional</span></button></h3>
                    <div id="tour-search-panel" class="accordion-collapse collapse">
                        <div class="accordion-body">
                            <?php $textField('localized_page_title', 'Page Title'); ?>
                            <div class="form-text mt-n2 mb-3">Leave blank to use the tour name followed by the site name.</div>
                            <?php $textField('localized_meta_description', 'Meta Description', 'textarea'); ?>
                            <div class="form-text mt-n2 mb-3">Leave blank to use the short description. About 150-160 characters displays best in search results.</div>
                            <?php $textField('localized_meta_keywords', 'Meta Keywords', 'textarea'); ?>
                            <fieldset class="admin-form-choice-group">
                                <legend>Search Engine Access</legend>
                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="form-check">
                                            <input type="hidden" name="robots_index" value="0">
                                            <input type="checkbox" name="robots_index" id="robots_index" value="1" class="form-check-input"<?php echo (int) $value('robots_index') === 1 ? ' checked' : ''; ?>>
                                            <label class="form-check-label" for="robots_index">Allow indexing</label>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="form-check">
                                            <input type="hidden" name="robots_follow" value="0">
                                            <input type="checkbox" name="robots_follow" id="robots_follow" value="1" class="form-check-input"<?php echo (int) $value('robots_follow') === 1 ? ' checked' : ''; ?>>
                                            <label class="form-check-label" for="robots_follow">Allow link following</label>
                                        </div>
                                    </div>
                                </div>
                            </fieldset>
                        </div>
                    </div>
                </article>

                <article class="accordion-item admin-form-accordion-item">
                    <h3 class="accordion-header"><button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#tour-sharing-panel" aria-expanded="false"><span class="admin-form-section-icon"><i class="bi bi-share"></i></span><span class="admin-form-section-heading"><span class="admin-form-section-title">Social Sharing</span><span class="admin-form-section-description">Preview title, description, and image</span></span><span class="badge text-bg-light admin-form-section-status">Optional</span></button></h3>
                    <div id="tour-sharing-panel" class="accordion-collapse collapse">
                        <div class="accordion-body">
                            <?php $textField('localized_og_title', 'Sharing Title'); ?>
                            <div class="form-text mt-n2 mb-3">Leave blank to use the page title.</div>
                            <?php $textField('localized_og_description', 'Sharing Description', 'textarea'); ?>
                            <div class="form-text mt-n2 mb-3">Leave blank to use the meta description.</div>
                            <?php $this->load->view('admin/partials/file_upload', array(
                                'name' => 'og_image_upload',
                                'id' => 'og_image_upload',
                                'label' => 'Sharing Image (Optional)',
                                'allowed_types' => UPLOAD_IMAGE_MIMES,
                                'required' => FALSE,
                                'current_path' => $sharingImage !== '' ? 'assets/frontend/images/tours/'.$sharingImage : '',
                                'help' => 'Maximum '.UPLOAD_SIZE_MB.' MB.',
                                'recommended_size' => '1200 × 630px',
                                'size_note' => 'Used for link previews on social media and messaging apps. Leave empty to use the main tour image.',
                                'preview_alt' => 'Current sharing image',
                                'preview_size' => 220,
                            )); ?>
                            <?php if ($isEdit && image_thumb_path('assets/frontend/images/tours/' . $sharingImage) !== FALSE) { ?>
                                <div class="admin-current-file-remove">
                                    <div class="admin-current-file-actions">
                                        <button class="btn btn-outline-danger btn-sm mt-3 removeFile" data-controller="<?php echo $this->controller; ?>" data-file-name="og_image<?php echo $localeSuffix; ?>" data-file-id="<?php echo (int) $tbl_data[$this->pKey]; ?>" type="button"><i class="bi bi-trash" aria-hidden="true"></i> Remove Image</button>
                                    </div>
                                </div>
                            <?php } ?>
                        </div>
                    </div>
                </article>

            </div>

            <div class="admin-form-actions"><a class="btn btn-outline-secondary" href="<?php echo ADMIN_URL.$this->controller; ?>">Cancel</a><button type="submit" class="btn btn-primary" data-save-button><span data-save-label>Save <?php echo htmlspecialchars($this->moduleNameSingular, ENT_QUOTES, 'UTF-8'); ?></span></button></div>
        </form>
        </div>
    </div>
</section>
<?php if ($isEdit) $this->load->view('admin/partials/manage_language_switch_modal'); ?>
<script>
jQuery(function ($) {
    var accordion = document.getElementById('tourEditorAccordion');
    var form = document.getElementById('<?php echo $this->controller; ?>_form');
    if (!accordion || !form || !window.bootstrap || !bootstrap.Collapse) return;

    function hourlyPriceGroupInvalid() {
        var group = form.querySelectorAll('.tour-hourly-price');
        if (!group.length) return null;
        var hasPositive = Array.prototype.some.call(group, function (field) {
            var raw = String(field.value || '').trim();
            return /^\d+$/.test(raw) && parseInt(raw, 10) > 0;
        });
        return hasPositive ? null : group[0];
    }

    function firstMissingRequiredField() {
        return Array.prototype.slice.call(form.querySelectorAll('[required]')).find(function (field) {
            if (field.disabled) return false;
            if (field.tagName === 'SELECT' && field.multiple) return field.selectedOptions.length === 0;
            if (field.type === 'checkbox' || field.type === 'radio') return !field.checked;
            return !String(field.value || '').trim();
        });
    }

    function firstPatternInvalidField() {
        var controls = form.querySelectorAll('input[pattern]');
        for (var i = 0; i < controls.length; i++) {
            var field = controls[i];
            if (field.disabled || !String(field.value || '').trim()) continue;
            if (typeof field.checkValidity === 'function' && !field.checkValidity()) return field;
        }
        return null;
    }

    function firstInvalidField() {
        return firstMissingRequiredField() || firstPatternInvalidField() || hourlyPriceGroupInvalid();
    }

    function focusField(field) {
        var $field = $(field);
        if ($field.hasClass('select2') && $.fn.select2) {
            $field.select2('open');
        } else if (typeof field.focus === 'function') {
            field.focus();
        }
    }

    function revealField(field) {
        var panel = field.closest('.accordion-collapse');
        var reveal = function () {
            if (typeof field.scrollIntoView === 'function') {
                field.scrollIntoView({ behavior: 'smooth', block: 'center' });
            }
            if ($.fn.valid) $(field).valid();
            focusField(field);
        };
        if (panel && !panel.classList.contains('show')) {
            panel.addEventListener('shown.bs.collapse', reveal, { once: true });
            bootstrap.Collapse.getOrCreateInstance(panel, { toggle: false }).show();
        } else {
            reveal();
        }
    }

    form.addEventListener('submit', function (event) {
        var firstMissingField = firstInvalidField();
        if (!firstMissingField) return;
        event.preventDefault();
        event.stopImmediatePropagation();
        revealField(firstMissingField);
    }, true);

    form.addEventListener('invalid', function (event) {
        event.preventDefault();
        revealField(event.target);
    }, true);
});
</script>
