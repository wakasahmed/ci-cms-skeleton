<?php
$isEdit = ($alert === 'edit' && !empty($tbl_data[$this->pKey]));
$crumb = $isEdit ? 'Edit' : 'Add';
$action = base_url('manage/'.$this->controller.'/'.($isEdit ? 'editRecord/'.(int) $tbl_data[$this->pKey] : 'addRecord'));
$value = function ($key) use ($tbl_data) { return isset($tbl_data[$key]) ? htmlspecialchars((string) $tbl_data[$key], ENT_QUOTES, 'UTF-8') : ''; };
$localizedValue = function ($key) use ($localized_values) { return isset($localized_values[$key]) ? htmlspecialchars((string) $localized_values[$key], ENT_QUOTES, 'UTF-8') : ''; };
$localeDetails = $manage_locales[$active_locale];
$translationStatus = $isEdit && !empty($translation_state['status']) ? $translation_state['status'] : 'MISSING';
$languageBaseUrl = $isEdit ? base_url('manage/'.$this->controller.'/control/edit/'.(int) $tbl_data[$this->pKey]) : '';
$vehicleImage = isset($tbl_data['vehicle_image']) ? basename((string) $tbl_data['vehicle_image']) : '';
$vehicleTitle = isset($tbl_data['vehicle_title']) ? trim((string) $tbl_data['vehicle_title']) : 'Vehicle';
$currencyUnit = htmlspecialchars((string) $currency_unit, ENT_QUOTES, 'UTF-8');
?>
<?php $this->load->view('admin/partials/breadcrumb', array('items' => array(array('label' => $this->moduleName, 'url' => ADMIN_URL.$this->controller), array('label' => $crumb.' '.$this->moduleNameSingular, 'active' => TRUE)))); ?>
<?php if (!empty($form_error)) { ?><div class="row alertrow"><div class="col-md-12"><div class="alert alert-danger alert-dismissible fade show" role="alert"><strong>Could not save the vehicle.</strong> <?php echo htmlspecialchars($form_error, ENT_QUOTES, 'UTF-8'); ?><button type="button" class="btn-close alertBox crud-alert-close" data-bs-dismiss="alert" aria-label="Dismiss notification"></button></div></div></div><?php } ?>

<section class="admin-records-listing" aria-labelledby="vehicles-title"<?php if ($isEdit) { ?> data-translation-poll data-module="vehicles" data-status-url="<?php echo base_url('manage/translations/statuses'); ?>" data-poll-seconds="4" data-locale="<?php echo htmlspecialchars($active_locale, ENT_QUOTES, 'UTF-8'); ?>"<?php } ?>>
    <?php $this->load->view('admin/partials/module_header', array('title' => $this->moduleName, 'description' => $this->moduleDesc, 'id' => 'vehicles-title')); ?>
    <div class="card admin-card">
        <div class="card-header d-flex flex-wrap align-items-center justify-content-between gap-3"><h2 class="card-title mb-0"><?php echo $crumb; ?> <?php echo htmlspecialchars($this->moduleNameSingular, ENT_QUOTES, 'UTF-8'); ?></h2><?php if ($isEdit) { $this->load->view('admin/partials/manage_language_switcher', array('language_base_url' => $languageBaseUrl, 'locales' => $manage_locales, 'locale' => $active_locale, 'translation_module' => 'vehicles', 'translation_entity_id' => (int) $tbl_data[$this->pKey], 'translation_status' => $translationStatus, 'translation_ready' => $translationStatus === 'SUCCEEDED')); } ?></div>
        <div class="card-body">
            <form id="<?php echo htmlspecialchars($this->controller, ENT_QUOTES, 'UTF-8'); ?>_form" name="<?php echo htmlspecialchars($this->controller, ENT_QUOTES, 'UTF-8'); ?>_form" method="post" action="<?php echo $action; ?>" enctype="multipart/form-data" class="validate" data-manage-language-form>
                <input type="hidden" name="active_locale" value="<?php echo htmlspecialchars($active_locale, ENT_QUOTES, 'UTF-8'); ?>">
                <input type="hidden" name="redirect_lang" value="" data-redirect-locale>
                <?php if ($this->config->item('csrf_protection')) { ?><input type="hidden" name="<?php echo htmlspecialchars($this->security->get_csrf_token_name(), ENT_QUOTES, 'UTF-8'); ?>" value="<?php echo htmlspecialchars($this->security->get_csrf_hash(), ENT_QUOTES, 'UTF-8'); ?>"><?php } ?>

                <div class="admin-field mb-3"><label class="form-label is-required" for="vehicle_title">Title</label><input type="text" name="vehicle_title" id="vehicle_title" maxlength="255" value="<?php echo $value('vehicle_title'); ?>" class="form-control" data-validate="required" required placeholder="Sedan (Toyota Camry, Hyundai Sonata)"></div>
                <div class="admin-field mb-4"><?php $this->load->view('admin/partials/file_upload', array(
                    'name' => 'vehicle_image', 'id' => 'vehicle_image', 'label' => 'Vehicle Image', 'allowed_types' => UPLOAD_IMAGE_MIMES, 'required' => TRUE,
                    'current_path' => $vehicleImage !== '' ? 'assets/frontend/images/vehicles/'.$vehicleImage : '', 'preview_alt' => $vehicleTitle.' image', 'preview_shape' => 'square', 'preview_size' => 132,
                    'recommended_size' => '1280 × 960px (4:3)',
                    'size_note' => 'Shown up to 640px wide on tour pages and is not cropped; its height follows the image, so use the same ratio for every vehicle.',
                )); ?></div>
                <div class="row">
                    <div class="admin-field mb-3 col-md-6"><label class="form-label is-required" for="localized_name">Name </label><input type="text" name="localized_name" id="localized_name" maxlength="255" value="<?php echo $localizedValue('localized_name'); ?>" class="form-control" data-translation-field="localized_name" data-validate="required" required dir="<?php echo htmlspecialchars($localeDetails['direction'], ENT_QUOTES, 'UTF-8'); ?>"<?php echo $active_locale === 'ar' ? ' lang="ar"' : ''; ?> placeholder="<?php echo $active_locale === 'ar' ? 'سيارة سيدان' : 'Sedan'; ?>"></div>
                    <div class="admin-field mb-3 col-md-6"><label class="form-label is-required" for="vehicle_max_capacity">Maximum Capacity (Including Driver & Guide)</label><input type="number" min="1" max="15000" step="1" name="vehicle_max_capacity" id="vehicle_max_capacity" value="<?php echo $value('vehicle_max_capacity'); ?>" class="form-control" data-validate="required,digits,min[1],max[15000]" required placeholder="4"></div>
                </div>
                <div class="admin-field mb-3"><label class="form-label" for="localized_details">Details </label><textarea rows="5" name="localized_details" id="localized_details" class="form-control" data-translation-field="localized_details" dir="<?php echo htmlspecialchars($localeDetails['direction'], ENT_QUOTES, 'UTF-8'); ?>"<?php echo $active_locale === 'ar' ? ' lang="ar"' : ''; ?> placeholder="Vehicle details"><?php echo $localizedValue('localized_details'); ?></textarea></div>

                <?php foreach (array(
                    array('label' => 'Price', 'prefix' => 'vehicle_price', 'help' => 'Enter a price for each duration option.', 'required' => TRUE),
                    array('label' => 'Meals / Refreshments Price (Per Person)', 'prefix' => 'vehicle_meals', 'help' => 'Optional. Leave empty to default to 0.', 'required' => FALSE),
                ) as $priceGroup) { ?>
                <div class="row align-items-end">
                    <div class="col-lg-4">
                        <p class="admin-form-colour-label"><?php echo htmlspecialchars($priceGroup['label'], ENT_QUOTES, 'UTF-8'); ?></p>
                        <?php if ($priceGroup['help'] !== '') { ?><p class="form-text mt-n2 mb-2"><?php echo htmlspecialchars($priceGroup['help'], ENT_QUOTES, 'UTF-8'); ?></p><?php } ?>
                    </div>
                    <?php foreach (array(2, 4, 6, 8) as $hours) { $fieldName = $priceGroup['prefix'].'_'.$hours; $minValue = $priceGroup['required'] ? 1 : 0; ?>
                    <div class="admin-field mb-3 col-6 col-md-3 col-lg-2">
                        <label class="form-label<?php echo $priceGroup['required'] ? ' is-required' : ''; ?>" for="<?php echo $fieldName; ?>"><?php echo (int) $hours; ?> Hours</label>
                        <div class="input-group"><span class="input-group-text"><?php echo $currencyUnit; ?></span><input type="number" min="<?php echo $minValue; ?>" max="15000" step="1" name="<?php echo $fieldName; ?>" id="<?php echo $fieldName; ?>" value="<?php echo $value($fieldName); ?>" class="form-control" data-validate="<?php echo $priceGroup['required'] ? 'required,' : ''; ?>digits,min[<?php echo $minValue; ?>],max[15000]"<?php echo $priceGroup['required'] ? ' required' : ''; ?> placeholder=""></div>
                    </div>
                    <?php } ?>
                </div>
                <?php } ?>
                <div class="admin-field mb-3"><label class="form-label" for="vehicle_status">Status</label><select class="form-select select2" name="vehicle_status" id="vehicle_status" data-minimum-results-for-search="-1"><option value="Enable" <?php echo (!isset($tbl_data['vehicle_status']) || $tbl_data['vehicle_status'] === 'Enable') ? 'selected' : ''; ?>>Enable</option><option value="Disable" <?php echo (isset($tbl_data['vehicle_status']) && $tbl_data['vehicle_status'] === 'Disable') ? 'selected' : ''; ?>>Disable</option></select></div>
                <div class="admin-form-actions"><a class="btn btn-outline-secondary" href="<?php echo ADMIN_URL.$this->controller; ?>">Cancel</a><button type="submit" class="btn btn-primary" data-save-button><span data-save-label>Save <?php echo htmlspecialchars($this->moduleNameSingular, ENT_QUOTES, 'UTF-8'); ?></span></button></div>
            </form>
        </div>
    </div>
</section>
<?php if ($isEdit) $this->load->view('admin/partials/manage_language_switch_modal'); ?>
