<?php
$isEdit = $alert === 'edit' && !empty($tbl_data[$this->pKey]);
$crumb = $isEdit ? 'Edit' : 'Add';
$record = is_array($tbl_data) ? $tbl_data : array();
$action = base_url('manage/'.$this->controller.'/'.($isEdit ? 'editRecord/'.(int) $record[$this->pKey] : 'addRecord'));
$value = function ($key) use ($record) {
    return isset($record[$key]) ? htmlspecialchars((string) $record[$key], ENT_QUOTES, 'UTF-8') : '';
};
$localizedValue = function ($key) use ($localized_values) {
    return isset($localized_values[$key]) ? htmlspecialchars((string) $localized_values[$key], ENT_QUOTES, 'UTF-8') : '';
};
$localeDetails = $manage_locales[$active_locale];
$translationStatus = $isEdit && !empty($translation_state['status']) ? $translation_state['status'] : 'MISSING';
$languageBaseUrl = $isEdit ? base_url('manage/'.$this->controller.'/control/edit/'.(int) $record[$this->pKey]) : '';
$codeValue = !empty($record['discount_code']) ? (string) $record['discount_code'] : $discount_code;
$expiryValue = isset($record['discount_expiry']) ? trim((string) $record['discount_expiry']) : '';
if ($expiryValue !== '' && strpos($expiryValue, '/') === FALSE) {
    $expiryValue = date('m/d/Y', strtotime($expiryValue));
}
?>

<?php $this->load->view('admin/partials/breadcrumb', array(
    'items' => array(
        array('label' => $this->moduleName, 'url' => ADMIN_URL.$this->controller),
        array('label' => $crumb.' '.$this->moduleNameSingular, 'active' => TRUE),
    ),
)); ?>

<?php if (!empty($form_error)) { ?>
    <div class="alert alert-danger alert-dismissible fade show" role="alert">
        <strong>Could not save the discount code.</strong>
        <?php echo htmlspecialchars($form_error, ENT_QUOTES, 'UTF-8'); ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Dismiss"></button>
    </div>
<?php } ?>

<section
    class="admin-records-listing"
    aria-labelledby="discount-codes-title"
    <?php if ($isEdit) { ?>data-translation-poll data-module="discount_codes" data-status-url="<?php echo base_url('manage/translations/statuses'); ?>" data-poll-seconds="4" data-locale="<?php echo htmlspecialchars($active_locale, ENT_QUOTES, 'UTF-8'); ?>"<?php } ?>
>
    <?php $this->load->view('admin/partials/module_header', array(
        'title' => $crumb.' '.$this->moduleNameSingular,
        'description' => 'Configure the referral, discount terms, and usage limit.',
        'id' => 'discount-codes-title',
    )); ?>

    <div class="card admin-card">
        <div class="card-header d-flex flex-wrap align-items-center justify-content-between gap-3">
            <h2 class="card-title mb-0"><?php echo $crumb; ?> <?php echo htmlspecialchars($this->moduleNameSingular, ENT_QUOTES, 'UTF-8'); ?></h2>
            <?php if ($isEdit) { ?>
                <?php $this->load->view('admin/partials/manage_language_switcher', array(
                    'language_base_url' => $languageBaseUrl,
                    'locales' => $manage_locales,
                    'locale' => $active_locale,
                    'translation_module' => 'discount_codes',
                    'translation_entity_id' => $record[$this->pKey],
                    'translation_status' => $translationStatus,
                    'translation_ready' => $translationStatus === 'SUCCEEDED',
                )); ?>
            <?php } ?>
        </div>

        <div class="card-body">
            <form
                id="discount_codes_form"
                method="post"
                action="<?php echo $action; ?>"
                class="validate"
                novalidate
                data-submit-lock
                data-manage-language-form
            >
                <input type="hidden" name="active_locale" value="<?php echo htmlspecialchars($active_locale, ENT_QUOTES, 'UTF-8'); ?>">
                <input type="hidden" name="redirect_lang" value="" data-redirect-locale>
                <?php if ($this->config->item('csrf_protection')) { ?>
                    <input type="hidden" name="<?php echo htmlspecialchars($this->security->get_csrf_token_name(), ENT_QUOTES, 'UTF-8'); ?>" value="<?php echo htmlspecialchars($this->security->get_csrf_hash(), ENT_QUOTES, 'UTF-8'); ?>">
                <?php } ?>

                <div class="accordion admin-form-accordion" id="discount-code-form-sections">
                    <article class="accordion-item admin-form-accordion-item">
                        <h3 class="accordion-header">
                            <button class="accordion-button" type="button" data-bs-toggle="collapse" data-bs-target="#discount-code-details" aria-expanded="true" aria-controls="discount-code-details">
                                <span class="admin-form-section-icon"><i class="bi bi-ticket-perforated" aria-hidden="true"></i></span>
                                <span class="admin-form-section-heading">
                                    <span class="admin-form-section-title">Discount Code Details</span>
                                    <span class="admin-form-section-description">Name, referral, and the customer-facing code</span>
                                </span>
                                <span class="badge text-bg-danger admin-form-section-status">Required</span>
                            </button>
                        </h3>
                        <div id="discount-code-details" class="accordion-collapse collapse show" data-bs-parent="#discount-code-form-sections">
                            <div class="accordion-body">
                                <div class="row g-3">
                                    <div class="admin-field col-md-6 mb-0">
                                        <label class="form-label is-required" for="localized_name">Name</label>
                                        <input type="text" name="localized_name" id="localized_name" maxlength="255" value="<?php echo $localizedValue('localized_name'); ?>" class="form-control" data-translation-field="localized_name" data-validate="required,maxlength[255]" required dir="<?php echo $localeDetails['direction']; ?>"<?php echo $active_locale === 'ar' ? ' lang="ar"' : ''; ?>>
                                    </div>
                                    <div class="admin-field col-md-6 mb-0">
                                        <label class="form-label is-required" for="discount_ref_id">Referral</label>
                                        <?php if ($isEdit && (int) $pcount > 0) { ?>
                                            <?php
                                            $selectedReferral = '';
                                            foreach ($referrals as $referral) {
                                                if ((int) $referral['ref_id'] === (int) $record['discount_ref_id']) {
                                                    $selectedReferral = $referral['ref_name'].($referral['ref_organization'] !== '' ? ' ('.$referral['ref_organization'].')' : '');
                                                    break;
                                                }
                                            }
                                            ?>
                                            <input type="text" class="form-control" value="<?php echo htmlspecialchars($selectedReferral, ENT_QUOTES, 'UTF-8'); ?>" disabled>
                                            <input type="hidden" name="discount_ref_id" id="discount_ref_id" value="<?php echo (int) $record['discount_ref_id']; ?>">
                                            <div class="form-text">The referral cannot be changed after the code has been used.</div>
                                        <?php } else { ?>
                                            <select name="discount_ref_id" id="discount_ref_id" class="form-select select2 required" data-placeholder="Select a referral" data-validate="required" required>
                                                <option value=""></option>
                                                <?php foreach ($referrals as $referral) { ?>
                                                    <option value="<?php echo (int) $referral['ref_id']; ?>"<?php echo isset($record['discount_ref_id']) && (int) $record['discount_ref_id'] === (int) $referral['ref_id'] ? ' selected' : ''; ?>>
                                                        <?php echo htmlspecialchars($referral['ref_name'].($referral['ref_organization'] !== '' ? ' ('.$referral['ref_organization'].')' : ''), ENT_QUOTES, 'UTF-8'); ?>
                                                    </option>
                                                <?php } ?>
                                            </select>
                                        <?php } ?>
                                    </div>
                                    <div class="admin-field col-12 mb-0">
                                        <label class="form-label is-required" for="discount_code">Discount Code</label>
                                        <div class="input-group">
                                            <input type="text" name="discount_code" id="discount_code" maxlength="40" value="<?php echo htmlspecialchars($codeValue, ENT_QUOTES, 'UTF-8'); ?>" class="form-control" data-validate="required,minlength[3],maxlength[40]" required autocomplete="off" placeholder="ALAM25">
                                            <button type="button" class="btn btn-outline-secondary" id="discount_regenerate_code" data-code-length="<?php echo (int) DISCOUNT_CODE_LENGTH; ?>"><i class="bi bi-arrow-counterclockwise" aria-hidden="true"></i> Regenerate</button>
                                        </div>
                                        <div class="form-text">Use 3–40 letters or numbers. The code must be unique.</div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </article>

                    <article class="accordion-item admin-form-accordion-item">
                        <h3 class="accordion-header">
                            <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#discount-code-pricing" aria-expanded="false" aria-controls="discount-code-pricing">
                                <span class="admin-form-section-icon"><i class="bi bi-cash-coin" aria-hidden="true"></i></span>
                                <span class="admin-form-section-heading">
                                    <span class="admin-form-section-title">Discount and Commission</span>
                                    <span class="admin-form-section-description">Customer discount and the referral commission amount</span>
                                </span>
                                <span class="badge text-bg-danger admin-form-section-status">Required</span>
                            </button>
                        </h3>
                        <div id="discount-code-pricing" class="accordion-collapse collapse" data-bs-parent="#discount-code-form-sections">
                            <div class="accordion-body">
                                <div class="row g-3">
                                    <div class="admin-field col-md-6 mb-0">
                                        <label class="form-label is-required" for="discount_type">Discount Type</label>
                                        <select class="form-select select2 required" name="discount_type" id="discount_type" data-minimum-results-for-search="-1" required>
                                            <option value="Fixed Amount"<?php echo !isset($record['discount_type']) || $record['discount_type'] === 'Fixed Amount' ? ' selected' : ''; ?>>Fixed Amount</option>
                                            <option value="Percentage"<?php echo isset($record['discount_type']) && $record['discount_type'] === 'Percentage' ? ' selected' : ''; ?>>Percentage</option>
                                        </select>
                                    </div>
                                    <div class="admin-field col-md-6 mb-0">
                                        <label class="form-label is-required" for="discount_value">Discount Value</label>
                                        <input type="number" name="discount_value" id="discount_value" min="<?php echo (int) $discount_min; ?>" max="<?php echo (int) $discount_max; ?>" step="1" value="<?php echo $value('discount_value'); ?>" data-validate="required,digits,min[<?php echo (int) $discount_min; ?>],max[<?php echo (int) $discount_max; ?>]" data-min-percentage="<?php echo DISCOUNT_VALUE_MIN_PERCENTAGE; ?>" data-max-percentage="<?php echo DISCOUNT_VALUE_MAX_PERCENTAGE; ?>" data-min-fixed="<?php echo DISCOUNT_VALUE_MIN_FIXED; ?>" data-max-fixed="<?php echo DISCOUNT_VALUE_MAX_FIXED; ?>" data-type-field="#discount_type" class="form-control" required>
                                        <div class="form-text" id="discount_value_help"></div>
                                    </div>
                                    <div class="admin-field col-md-6 mb-0">
                                        <label class="form-label is-required" for="discount_ref_commission_type">Referral Commission Type</label>
                                        <select class="form-select select2 required" name="discount_ref_commission_type" id="discount_ref_commission_type" data-minimum-results-for-search="-1" required>
                                            <option value="Fixed Amount"<?php echo !isset($record['discount_ref_commission_type']) || $record['discount_ref_commission_type'] === 'Fixed Amount' ? ' selected' : ''; ?>>Fixed Amount</option>
                                            <option value="Percentage"<?php echo isset($record['discount_ref_commission_type']) && $record['discount_ref_commission_type'] === 'Percentage' ? ' selected' : ''; ?>>Percentage</option>
                                        </select>
                                    </div>
                                    <div class="admin-field col-md-6 mb-0">
                                        <label class="form-label is-required" for="discount_ref_commission">Referral Commission</label>
                                        <input type="number" name="discount_ref_commission" id="discount_ref_commission" min="<?php echo (int) $ref_commission_min; ?>" max="<?php echo (int) $ref_commission_max; ?>" step="1" value="<?php echo $value('discount_ref_commission'); ?>" data-validate="required,digits,min[<?php echo (int) $ref_commission_min; ?>],max[<?php echo (int) $ref_commission_max; ?>]" data-min-percentage="<?php echo DISCOUNT_REF_COMMISSION_MIN_PERCENTAGE; ?>" data-max-percentage="<?php echo DISCOUNT_REF_COMMISSION_MAX_PERCENTAGE; ?>" data-min-fixed="<?php echo DISCOUNT_REF_COMMISSION_MIN_FIXED; ?>" data-max-fixed="<?php echo DISCOUNT_REF_COMMISSION_MAX_FIXED; ?>" data-type-field="#discount_ref_commission_type" class="form-control" required>
                                        <div class="form-text" id="discount_ref_commission_help"></div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </article>

                    <article class="accordion-item admin-form-accordion-item">
                        <h3 class="accordion-header">
                            <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#discount-code-availability" aria-expanded="false" aria-controls="discount-code-availability">
                                <span class="admin-form-section-icon"><i class="bi bi-calendar-check" aria-hidden="true"></i></span>
                                <span class="admin-form-section-heading">
                                    <span class="admin-form-section-title">Availability</span>
                                    <span class="admin-form-section-description">Expiry, usage limit, and status</span>
                                </span>
                                <span class="badge text-bg-danger admin-form-section-status">Required</span>
                            </button>
                        </h3>
                        <div id="discount-code-availability" class="accordion-collapse collapse" data-bs-parent="#discount-code-form-sections">
                            <div class="accordion-body">
                                <div class="row g-3">
                                    <div class="admin-field col-md-6 mb-0">
                                        <label class="form-label is-required" for="discount_expiry">Expiry Date</label>
                                        <input type="text" name="discount_expiry" id="discount_expiry" maxlength="20" value="<?php echo htmlspecialchars($expiryValue, ENT_QUOTES, 'UTF-8'); ?>" class="form-control datepicker" data-validate="required" required autocomplete="off" placeholder="MM/DD/YYYY">
                                    </div>
                                    <div class="admin-field col-md-6 mb-0">
                                        <label class="form-label" for="discount_no_of_uses">Maximum Uses</label>
                                        <input type="number" name="discount_no_of_uses" id="discount_no_of_uses" min="0" max="2147483647" step="1" value="<?php echo $value('discount_no_of_uses'); ?>" class="form-control" placeholder="0">
                                        <div class="form-text">Leave blank or enter 0 for unlimited uses.</div>
                                    </div>
                                    <div class="admin-field col-md-6 mb-0">
                                        <label class="form-label is-required" for="discount_status">Status</label>
                                        <select class="form-select select2 required" name="discount_status" id="discount_status" data-minimum-results-for-search="-1" required>
                                            <option value="Enable"<?php echo !isset($record['discount_status']) || $record['discount_status'] === 'Enable' ? ' selected' : ''; ?>>Enabled</option>
                                            <option value="Disable"<?php echo isset($record['discount_status']) && $record['discount_status'] === 'Disable' ? ' selected' : ''; ?>>Disabled</option>
                                            <option value="Expired"<?php echo isset($record['discount_status']) && $record['discount_status'] === 'Expired' ? ' selected' : ''; ?>>Expired</option>
                                        </select>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </article>
                </div>

                <div class="admin-form-actions">
                    <a class="btn btn-outline-secondary" href="<?php echo ADMIN_URL.$this->controller; ?>">Cancel</a>
                    <button type="submit" class="btn btn-primary" data-save-button><span data-save-label>Save <?php echo htmlspecialchars($this->moduleNameSingular, ENT_QUOTES, 'UTF-8'); ?></span></button>
                </div>
            </form>
        </div>
    </div>
</section>

<?php if ($isEdit) { ?>
    <?php $this->load->view('admin/partials/manage_language_switch_modal'); ?>
<?php } ?>

<script>
jQuery(function ($) {
    var accordion = document.getElementById('discount-code-form-sections');
    var form = document.getElementById('discount_codes_form');
    if (!accordion || !form || !window.bootstrap || !bootstrap.Collapse) return;

    function firstMissingRequiredField() {
        return Array.prototype.slice.call(form.querySelectorAll('[required]')).find(function (field) {
            if (field.disabled) return false;
            if (field.type === 'checkbox' || field.type === 'radio') return !field.checked;
            return !String(field.value || '').trim();
        });
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
        var firstMissingField = firstMissingRequiredField();
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
