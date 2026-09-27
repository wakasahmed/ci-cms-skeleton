<?php
$isEdit = ($alert === 'edit' && !empty($tbl_data[$this->pKey]));
$crumb = $isEdit ? 'Edit' : 'Add';
$recordID = $isEdit ? (int) $tbl_data[$this->pKey] : 0;
$action = base_url('manage/'.$this->controller.'/'.($isEdit ? 'editRecord/'.(int) $slider_id.'/'.$recordID : 'addRecord/'.(int) $slider_id));
$localizedValue = function ($key) use ($localized_values) { return isset($localized_values[$key]) ? htmlspecialchars((string) $localized_values[$key], ENT_QUOTES, 'UTF-8') : ''; };
$value = function ($key) use ($form_values) { return isset($form_values[$key]) ? htmlspecialchars((string) $form_values[$key], ENT_QUOTES, 'UTF-8') : ''; };
$localeDetails = $manage_locales[$active_locale];
$translationStatus = $isEdit && !empty($translation_state['status']) ? $translation_state['status'] : 'MISSING';
$languageBaseUrl = $isEdit ? base_url('manage/'.$this->controller.'/control/'.(int) $slider_id.'/edit/'.$recordID) : '';
$currentImage = !empty($form_values['current_image']) ? basename((string) $form_values['current_image']) : '';
$targetOptions = array('_self' => 'Open in the same tab', '_blank' => 'Open in a new tab');
?>
<?php $this->load->view('admin/partials/breadcrumb', array('items' => array(array('label' => 'Image Sliders', 'url' => ADMIN_URL.'sliders'), array('label' => $slider_name, 'url' => ADMIN_URL.'slider/index/page/'.(int) $slider_id), array('label' => $crumb.' '.$this->moduleNameSingular, 'active' => TRUE)))); ?>

<?php if (!empty($form_error)) { ?><div class="row alertrow"><div class="col-md-12"><div class="alert alert-danger alert-dismissible fade show" role="alert"><strong>Could not save the slider image.</strong> <?php echo htmlspecialchars($form_error, ENT_QUOTES, 'UTF-8'); ?><button type="button" class="btn-close alertBox crud-alert-close" data-bs-dismiss="alert" aria-label="Dismiss notification"></button></div></div></div><?php } ?>

<section class="admin-records-listing" aria-labelledby="slider-image-form-title"<?php if ($isEdit) { ?> data-translation-poll data-module="slider" data-status-url="<?php echo base_url('manage/translations/statuses'); ?>" data-poll-seconds="4" data-locale="<?php echo htmlspecialchars($active_locale, ENT_QUOTES, 'UTF-8'); ?>"<?php } ?>>
    <?php $this->load->view('admin/partials/module_header', array('title' => $slider_name.' '.$this->moduleName, 'description' => 'Manage slide text, imagery, calls to action, colours, and display settings.', 'id' => 'slider-image-form-title')); ?>

    <div class="card admin-card">
        <div class="card-header d-flex flex-wrap align-items-center justify-content-between gap-3"><h2 class="card-title mb-0"><?php echo $crumb; ?> <?php echo htmlspecialchars($this->moduleNameSingular, ENT_QUOTES, 'UTF-8'); ?></h2><?php if ($isEdit) { $this->load->view('admin/partials/manage_language_switcher', array('language_base_url' => $languageBaseUrl, 'locales' => $manage_locales, 'locale' => $active_locale, 'translation_module' => 'slider', 'translation_entity_id' => $recordID, 'translation_status' => $translationStatus, 'translation_ready' => $translationStatus === 'SUCCEEDED')); } ?></div>
        <div class="card-body">
            <form id="slider_form" name="slider_form" method="post" action="<?php echo $action; ?>" enctype="multipart/form-data" class="validate" data-submit-lock data-manage-language-form>
                <input type="hidden" name="active_locale" value="<?php echo htmlspecialchars($active_locale, ENT_QUOTES, 'UTF-8'); ?>">
                <input type="hidden" name="redirect_lang" value="" data-redirect-locale>
                <?php if ($this->config->item('csrf_protection')) { ?><input type="hidden" name="<?php echo htmlspecialchars($this->security->get_csrf_token_name(), ENT_QUOTES, 'UTF-8'); ?>" value="<?php echo htmlspecialchars($this->security->get_csrf_hash(), ENT_QUOTES, 'UTF-8'); ?>"><?php } ?>

                <div class="admin-form-subsection">
                <div class="admin-form-subsection-heading"><div><h4>Slide Content</h4><p>Add the message visitors see on this slide.</p></div></div>
                <div class="row">
                    <div class="admin-field mb-3 col-md-6"><label class="form-label" for="localized_pre_heading">Pre-heading</label><input type="text" name="localized_pre_heading" id="localized_pre_heading" maxlength="255" value="<?php echo $localizedValue('localized_pre_heading'); ?>" class="form-control" data-translation-field="localized_pre_heading" dir="<?php echo $localeDetails['direction']; ?>"<?php echo $active_locale === 'ar' ? ' lang="ar"' : ''; ?>></div>
                    <div class="admin-field mb-3 col-md-6"><label class="form-label" for="localized_heading">Heading</label><input type="text" name="localized_heading" id="localized_heading" maxlength="255" value="<?php echo $localizedValue('localized_heading'); ?>" class="form-control" data-translation-field="localized_heading" dir="<?php echo $localeDetails['direction']; ?>"<?php echo $active_locale === 'ar' ? ' lang="ar"' : ''; ?>></div>
                </div>
                <div class="admin-field mb-4"><label class="form-label" for="localized_text">Text</label><textarea name="localized_text" id="localized_text" rows="4" class="form-control" data-translation-field="localized_text" dir="<?php echo $localeDetails['direction']; ?>"<?php echo $active_locale === 'ar' ? ' lang="ar"' : ''; ?>><?php echo $localizedValue('localized_text'); ?></textarea></div>
                </div>

                <div class="admin-form-subsection">
                <div class="admin-form-subsection-heading"><div><h4>Calls to Action</h4><p>Optional buttons that direct visitors to another page or resource.</p></div></div>
                <div class="row">
                    <div class="col-lg-6">
                        <h4 class="h6 mb-3">Primary Button</h4>
                        <?php $iconPickerName = 'button_1_icon'; $iconPickerId = 'button_1_icon'; $iconPickerLabel = 'Icon'; $iconPickerValue = isset($form_values['button_1_icon']) ? $form_values['button_1_icon'] : ''; $iconPickerHelp = 'Optional Font Awesome icon.'; $this->load->view('admin/partials/icon_picker_field', compact('iconPickerName', 'iconPickerId', 'iconPickerLabel', 'iconPickerValue', 'iconPickerHelp')); ?>
                        <div class="admin-field mb-3"><label class="form-label" for="localized_button_1_text">Text</label><input type="text" name="localized_button_1_text" id="localized_button_1_text" maxlength="255" value="<?php echo $localizedValue('localized_button_1_text'); ?>" class="form-control" data-translation-field="localized_button_1_text" dir="<?php echo $localeDetails['direction']; ?>"<?php echo $active_locale === 'ar' ? ' lang="ar"' : ''; ?>></div>
                        <div class="admin-field mb-3"><label class="form-label" for="button_1_url">URL</label><input type="text" name="button_1_url" id="button_1_url" maxlength="255" value="<?php echo $value('button_1_url'); ?>" class="form-control" placeholder="https://example.com/page" dir="ltr"></div>
                        <div class="admin-field mb-3"><label class="form-label" for="button_1_target">URL Target</label><select class="form-select" name="button_1_target" id="button_1_target"><?php foreach ($targetOptions as $targetValue => $targetLabel) { ?><option value="<?php echo $targetValue; ?>"<?php echo $form_values['button_1_target'] === $targetValue ? ' selected' : ''; ?>><?php echo $targetLabel; ?></option><?php } ?></select></div>
                    </div>
                    <div class="col-lg-6">
                        <h4 class="h6 mb-3">Secondary Button</h4>
                        <?php $iconPickerName = 'button_2_icon'; $iconPickerId = 'button_2_icon'; $iconPickerLabel = 'Icon'; $iconPickerValue = isset($form_values['button_2_icon']) ? $form_values['button_2_icon'] : ''; $iconPickerHelp = 'Optional Font Awesome icon.'; $this->load->view('admin/partials/icon_picker_field', compact('iconPickerName', 'iconPickerId', 'iconPickerLabel', 'iconPickerValue', 'iconPickerHelp')); ?>
                        <div class="admin-field mb-3"><label class="form-label" for="localized_button_2_text">Text</label><input type="text" name="localized_button_2_text" id="localized_button_2_text" maxlength="255" value="<?php echo $localizedValue('localized_button_2_text'); ?>" class="form-control" data-translation-field="localized_button_2_text" dir="<?php echo $localeDetails['direction']; ?>"<?php echo $active_locale === 'ar' ? ' lang="ar"' : ''; ?>></div>
                        <div class="admin-field mb-3"><label class="form-label" for="button_2_url">URL</label><input type="text" name="button_2_url" id="button_2_url" maxlength="255" value="<?php echo $value('button_2_url'); ?>" class="form-control" placeholder="https://example.com/page" dir="ltr"></div>
                        <div class="admin-field mb-3"><label class="form-label" for="button_2_target">URL Target</label><select class="form-select" name="button_2_target" id="button_2_target"><?php foreach ($targetOptions as $targetValue => $targetLabel) { ?><option value="<?php echo $targetValue; ?>"<?php echo $form_values['button_2_target'] === $targetValue ? ' selected' : ''; ?>><?php echo $targetLabel; ?></option><?php } ?></select></div>
                    </div>
                </div>
                </div>

                <div class="admin-form-subsection">
                <div class="admin-form-subsection-heading"><div><h4>Slide Image</h4><p>Use an image that supports the slide message and remains clear across screen sizes.</p></div></div>
                <div class="admin-field mb-0"><?php $this->load->view('admin/partials/file_upload', array(
                    'name' => 'slide_image', 'id' => 'slide_image', 'label' => 'Slide Image',
                    'allowed_types' => UPLOAD_IMAGE_MIMES, 'required' => !$isEdit,
                    'current_path' => $currentImage !== '' ? 'assets/frontend/images/slider/'.$currentImage : '',
                    'help' => ($active_locale === 'ar' ? 'Optional. When this is left empty, the default slide image is used. ' : '').'Maximum size: '.UPLOAD_SIZE_MB.' MB.',
                    'recommended_size' => '1920 × 1080px (16:9)',
                    'size_note' => 'Full-width hero background. It is cropped from the centre to 16:9, so keep the main subject near the middle.',
                    'preview_alt' => 'Current slide image', 'preview_shape' => 'square', 'preview_size' => 220,
                )); ?></div>
                </div>

                <div class="admin-form-subsection mb-0">
                <div class="admin-form-subsection-heading"><div><h4>Appearance and Visibility</h4><p>Select one colour for a solid appearance, or add a second colour to create a gradient.</p></div></div>
                <div class="row">
                    <?php foreach (array(
                        'banner_pre_heading_color_1' => 'Pre-heading Colour 1', 'banner_pre_heading_color_2' => 'Pre-heading Colour 2',
                        'banner_heading_color_1' => 'Heading Colour 1', 'banner_heading_color_2' => 'Heading Colour 2',
                        'banner_text_color_1' => 'Text Colour 1', 'banner_text_color_2' => 'Text Colour 2',
                    ) as $colorField => $colorLabel) { ?>
                    <div class="admin-field mb-3 col-md-6"><label class="form-label" for="<?php echo $colorField; ?>"><?php echo $colorLabel; ?></label><input type="text" name="<?php echo $colorField; ?>" id="<?php echo $colorField; ?>" value="<?php echo $value($colorField); ?>" class="form-control color-picker" placeholder="rgba(0, 0, 0, 0.5)" maxlength="50" dir="ltr" autocomplete="off"></div>
                    <?php } ?>
                </div>
                <div class="row">
                    <div class="admin-field mb-3 col-md-6"><label class="form-label" for="overlay">Overlay</label><select class="form-select select2" name="overlay" id="overlay" data-minimum-results-for-search="-1"><option value="Yes"<?php echo $form_values['overlay'] === 'Yes' ? ' selected' : ''; ?>>Yes</option><option value="No"<?php echo $form_values['overlay'] === 'No' ? ' selected' : ''; ?>>No</option></select></div>
                    <div class="admin-field mb-3 col-md-6"><label class="form-label" for="status">Status</label><select class="form-select select2" name="status" id="status" data-minimum-results-for-search="-1"><option value="Enable"<?php echo $form_values['status'] === 'Enable' ? ' selected' : ''; ?>>Enable</option><option value="Disable"<?php echo $form_values['status'] === 'Disable' ? ' selected' : ''; ?>>Disable</option></select></div>
                </div>
                </div>

                <div class="admin-form-actions"><a class="btn btn-outline-secondary" href="<?php echo ADMIN_URL.'slider/index/page/'.(int) $slider_id; ?>">Cancel</a><button type="submit" class="btn btn-primary" data-save-button><span data-save-label>Save <?php echo htmlspecialchars($this->moduleNameSingular, ENT_QUOTES, 'UTF-8'); ?></span></button></div>
            </form>
        </div>
    </div>
</section>
<?php if ($isEdit) $this->load->view('admin/partials/manage_language_switch_modal'); ?>
