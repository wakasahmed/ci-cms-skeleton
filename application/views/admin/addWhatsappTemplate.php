<?php
$recordId = (int) $stored_record[$this->pKey];
$action = base_url('manage/' . $this->controller . '/editRecord/' . $recordId);
$service = $this->whatsapp_template_service;
$escape = function ($value) {
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
};
$value = function ($key) use ($tbl_data, $escape) {
    return isset($tbl_data[$key]) ? $escape($tbl_data[$key]) : '';
};
$errorField = !empty($form_error_field) ? (string) $form_error_field : '';
$invalidClass = function ($field) use ($errorField) {
    return $errorField === $field ? ' is-invalid' : '';
};
$invalidAttr = function ($field) use ($errorField) {
    return $errorField === $field ? 'aria-invalid="true"' : '';
};
$fieldState = function ($field) use ($errorField) {
    return $errorField === $field ? ' validate-has-error' : '';
};
$isSubmitted = $service->isSubmitted($stored_record);
$category = isset($tbl_data['wt_category']) ? $tbl_data['wt_category'] : 'UTILITY';
$status = isset($tbl_data['wt_status']) ? $tbl_data['wt_status'] : 'Enable';
?>
<?php $this->load->view('admin/partials/breadcrumb', array(
    'items' => array(
        array('label' => $this->moduleName, 'url' => ADMIN_URL . $this->controller),
        array('label' => 'Edit ' . $this->moduleNameSingular, 'active' => true),
    ),
)); ?>

<?php $this->load->view('admin/partials/crud_alert', array(
    'module_name' => 'WhatsApp template',
    'status' => $saved_alert,
    'status_messages' => array(
        'savednotsubmitted' => array('warning', 'Saved, not submitted.', 'The template was saved, but Meta did not accept it for review. Correct the problem below, then save again or use Submit to Meta.'),
        'synced' => array('success', 'Up to date.', 'Changes were submitted to Meta and the review statuses were refreshed.'),
        'testsent' => array('success', 'Test sent.', 'Meta accepted the test message. It should arrive on the phone shortly.'),
    ),
)); ?>

<?php if (!empty($form_error)) { ?>
    <div class="row alertrow">
        <div class="col-md-12">
            <button class="btn-close alertBox" data-bs-dismiss="alert" aria-label="Dismiss"></button>
            <div class="alert alert-danger">
                <strong>Could not save the WhatsApp template.</strong>
                <?php echo $escape($form_error); ?>
            </div>
        </div>
    </div>
<?php } ?>

<?php if (!empty($meta_error)) { ?>
    <div class="row alertrow">
        <div class="col-md-12">
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <strong>Meta:</strong>
                <?php echo $escape($meta_error); ?>
                <button type="button" class="btn-close alertBox crud-alert-close" data-bs-dismiss="alert" aria-label="Dismiss notification"></button>
            </div>
        </div>
    </div>
<?php } ?>

<section class="admin-records-listing" aria-labelledby="whatsapp-templates-title">
    <?php $this->load->view('admin/partials/module_header', array(
        'title' => $this->moduleName,
        'description' => $this->moduleDesc,
        'id' => 'whatsapp-templates-title',
    )); ?>

    <div class="card admin-card">
        <div class="card-header d-flex flex-wrap align-items-center justify-content-between gap-3">
            <h2 class="card-title mb-0">Edit <?php echo $escape($this->moduleNameSingular); ?></h2>
            <form method="post" action="<?php echo base_url('manage/' . $this->controller . '/sync/' . $recordId); ?>">
                <?php if ($this->config->item('csrf_protection')) { ?>
                    <input type="hidden" name="<?php echo $escape($this->security->get_csrf_token_name()); ?>" value="<?php echo $escape($this->security->get_csrf_hash()); ?>">
                <?php } ?>
                <button type="submit" class="btn btn-outline-secondary btn-sm">
                    <i class="bi bi-arrow-repeat" aria-hidden="true"></i>
                    Submit to Meta &amp; refresh status
                </button>
            </form>
        </div>
        <div class="card-body">
            <form
                id="<?php echo $this->controller; ?>_form"
                name="<?php echo $this->controller; ?>_form"
                method="post"
                action="<?php echo $action; ?>"
                class="validate"
                data-whatsapp-template-form
            >
                <?php if ($this->config->item('csrf_protection')) { ?>
                    <input type="hidden" name="<?php echo $escape($this->security->get_csrf_token_name()); ?>" value="<?php echo $escape($this->security->get_csrf_hash()); ?>">
                <?php } ?>

                <div class="row">
                    <div class="admin-field mb-3 col-md-6<?php echo $fieldState('wt_title'); ?>">
                        <label class="form-label is-required" for="wt_title">Title</label>
                        <input
                            type="text"
                            name="wt_title"
                            id="wt_title"
                            maxlength="150"
                            value="<?php echo $value('wt_title'); ?>"
                            class="form-control<?php echo $invalidClass('wt_title'); ?>"
                            <?php echo $invalidAttr('wt_title'); ?>
                            data-validate="required,maxlength[150]"
                            required
                            placeholder="Tour Booking Confirmation"
                        >
                    </div>
                    <div class="admin-field mb-3 col-md-6<?php echo $fieldState('wt_name'); ?>">
                        <label class="form-label is-required" for="wt_name">Meta Template Name</label>
                        <input
                            type="text"
                            name="wt_name"
                            id="wt_name"
                            maxlength="100"
                            value="<?php echo $value('wt_name'); ?>"
                            class="form-control<?php echo $invalidClass('wt_name'); ?>"
                            <?php echo $invalidAttr('wt_name'); ?>
                            data-validate="required,maxlength[100]"
                            required
                            pattern="[a-z][a-z0-9_]*"
                            spellcheck="false"
                            autocomplete="off"
                            aria-describedby="wt_name_help"
                            <?php echo $isSubmitted ? 'readonly' : ''; ?>
                            data-name-source="#wt_title"
                            placeholder="tour_booking_confirmation"
                        >
                        <div class="form-text" id="wt_name_help">
                            <?php if ($isSubmitted) { ?>
                                Fixed because Meta has received this template.
                            <?php } else { ?>
                                Lowercase letters, numbers and underscores. It cannot be changed after the template is submitted to Meta.
                            <?php } ?>
                        </div>
                    </div>
                </div>

                <div class="row">
                    <div class="admin-field mb-3 col-md-6">
                        <label class="form-label" for="wt_category">Meta Category</label>
                        <select
                            class="form-select select2"
                            name="wt_category"
                            id="wt_category"
                            data-minimum-results-for-search="-1"
                            aria-describedby="wt_category_help"
                            <?php echo $isSubmitted ? 'disabled' : ''; ?>
                        >
                            <option value="UTILITY" <?php echo $category !== 'MARKETING' ? 'selected' : ''; ?>>Utility (booking and account updates)</option>
                            <option value="MARKETING" <?php echo $category === 'MARKETING' ? 'selected' : ''; ?>>Marketing (promotions and offers)</option>
                        </select>
                        <div class="form-text" id="wt_category_help">
                            <?php if ($isSubmitted) { ?>
                                Fixed because Meta has received this template.
                            <?php } else { ?>
                                Meta may re-classify a Utility template as Marketing if its wording is promotional.
                            <?php } ?>
                        </div>
                    </div>
                    <div class="admin-field mb-3 col-md-6">
                        <label class="form-label" for="wt_status">Status</label>
                        <select class="form-select select2" name="wt_status" id="wt_status" data-minimum-results-for-search="-1" aria-describedby="wt_status_help">
                            <option value="Enable" <?php echo $status !== 'Disable' ? 'selected' : ''; ?>>Enable</option>
                            <option value="Disable" <?php echo $status === 'Disable' ? 'selected' : ''; ?>>Disable</option>
                        </select>
                        <div class="form-text" id="wt_status_help">Disabled templates are kept on Meta but not sent.</div>
                    </div>
                </div>

                <?php $this->load->view('admin/partials/short_tag_picker', array(
                    'tags' => $short_tags,
                    'entity_label' => $short_tag_entity,
                    'id' => 'whatsapp-template-tags',
                    'description' => 'Only these values are available when this notification is sent. '
                        . 'Copy a tag, or use + to insert it into the message you are editing. Each tag may be used once '
                        . 'per message, and a message cannot start or end with a tag.',
                )); ?>

                <div class="row">
                    <?php foreach ($languages as $language) { ?>
                        <?php
                        $field = 'wt_body_' . $language;
                        $isArabic = $language === 'ar';
                        $label = $service->languageLabel($language);
                        $locked = $service->isLocked($stored_record, $language);
                        $metaStatus = $stored_record['wt_' . $language . '_meta_status'];
                        $metaReason = $stored_record['wt_' . $language . '_meta_reason'];
                        $metaId = $stored_record['wt_' . $language . '_meta_id'];
                        $statusMeta = $service->statusMeta($metaStatus);
                        $unsubmitted = $metaId !== '' && $service->hasUnsubmittedChanges($stored_record, $language);
                        ?>
                        <div class="admin-field mb-3 col-lg-6<?php echo $fieldState($field); ?>">
                            <label class="form-label<?php echo $isArabic ? '' : ' is-required'; ?>" for="<?php echo $field; ?>">
                                <?php echo $label; ?> Message<?php echo $isArabic ? ' (Optional)' : ''; ?>
                            </label>
                            <textarea
                                name="<?php echo $field; ?>"
                                id="<?php echo $field; ?>"
                                rows="10"
                                maxlength="<?php echo Whatsapp_template_service::BODY_MAX_LENGTH; ?>"
                                class="form-control whatsapp-template-body<?php echo $invalidClass($field); ?>"
                                data-short-tag-target
                                <?php echo $isArabic ? '' : 'data-short-tag-default'; ?>
                                <?php echo $invalidAttr($field); ?>
                                dir="<?php echo $isArabic ? 'rtl' : 'ltr'; ?>"
                                <?php echo $isArabic ? 'lang="ar"' : 'data-validate="required,maxlength[' . Whatsapp_template_service::BODY_MAX_LENGTH . ']" required'; ?>
                                aria-describedby="<?php echo $field; ?>_status <?php echo $field; ?>_count"
                                <?php echo $locked ? 'readonly' : ''; ?>
                            ><?php echo $value($field); ?></textarea>
                            <div class="d-flex flex-wrap justify-content-between gap-2 mt-1">
                                <div id="<?php echo $field; ?>_status" class="whatsapp-template-meta-status">
                                    <span class="translation-status-badge <?php echo $escape($statusMeta['class']); ?>">
                                        <?php echo $escape($statusMeta['label']); ?>
                                    </span>
                                    <?php if ($locked) { ?>
                                        <span class="form-text">Locked while Meta reviews it.</span>
                                    <?php } elseif ($unsubmitted) { ?>
                                        <span class="form-text">Changes not submitted yet.</span>
                                    <?php } ?>
                                    <?php if ($metaId !== '') { ?>
                                        <span class="form-text">Meta ID <?php echo $escape($metaId); ?></span>
                                    <?php } ?>
                                </div>
                                <span class="form-text" id="<?php echo $field; ?>_count" data-character-count-for="<?php echo $field; ?>" aria-live="polite"></span>
                            </div>
                            <?php if ($metaReason !== '') { ?>
                                <div class="form-text text-danger"><?php echo $escape($metaReason); ?></div>
                            <?php } ?>
                        </div>
                    <?php } ?>
                </div>
                <p class="form-text">
                    Plain text only. Use *bold*, _italic_ and line breaks for formatting.
                    Saving submits a new or changed message to Meta, which must approve it before it can be sent.
                </p>

                <div class="admin-form-actions">
                    <a class="btn btn-outline-secondary" href="<?php echo ADMIN_URL . $this->controller; ?>">Cancel</a>
                    <button type="submit" class="btn btn-primary" data-save-button>
                        <span data-save-label>Save <?php echo $escape($this->moduleNameSingular); ?></span>
                    </button>
                </div>
            </form>
        </div>
    </div>

    <?php
    $approvedLanguages = array();
    foreach ($languages as $language) {
        if ($stored_record['wt_' . $language . '_meta_status'] === 'APPROVED') {
            $approvedLanguages[] = $language;
        }
    }
    ?>
    <div class="card admin-card mt-4" id="whatsapp-template-test">
        <div class="card-header">
            <h2 class="card-title mb-0" id="whatsapp-template-test-title">Send Test Message</h2>
        </div>
        <div class="card-body">
            <p class="form-text mt-0">
                Sends the approved template with each short tag filled by its example value.
                With Meta's test number, the phone must be one of the recipients added under WhatsApp &gt; API Setup.
            </p>

            <?php if (!empty($test_error)) { ?>
                <div class="alert alert-danger" role="alert">
                    <strong>Test not sent.</strong>
                    <?php echo $escape($test_error); ?>
                </div>
            <?php } ?>

            <?php if (empty($approvedLanguages)) { ?>
                <p class="mb-0 text-secondary">
                    Available once Meta approves the English or Arabic version of this template.
                </p>
            <?php } else { ?>
                <form
                    method="post"
                    action="<?php echo base_url('manage/' . $this->controller . '/sendtest/' . $recordId); ?>"
                    class="validate"
                    data-submit-lock
                    aria-labelledby="whatsapp-template-test-title"
                >
                    <?php if ($this->config->item('csrf_protection')) { ?>
                        <input type="hidden" name="<?php echo $escape($this->security->get_csrf_token_name()); ?>" value="<?php echo $escape($this->security->get_csrf_hash()); ?>">
                    <?php } ?>
                    <div class="row align-items-end">
                        <div class="admin-field mb-3 col-md-6">
                            <label class="form-label is-required" for="test_phone">Phone Number</label>
                            <input
                                type="tel"
                                name="test_phone"
                                id="test_phone"
                                maxlength="30"
                                value="<?php echo $escape($test_phone); ?>"
                                class="form-control intl-phone"
                                data-initial-country="sa"
                                data-validate="required,intlPhone"
                                required
                                autocomplete="tel"
                                placeholder="Phone number"
                            >
                        </div>
                        <div class="admin-field mb-3 col-md-3">
                            <label class="form-label" for="test_language">Language</label>
                            <select class="form-select select2" name="test_language" id="test_language" data-minimum-results-for-search="-1">
                                <?php foreach ($languages as $language) { ?>
                                    <?php $isApproved = in_array($language, $approvedLanguages, true); ?>
                                    <option
                                        value="<?php echo $escape($language); ?>"
                                        <?php echo $isApproved ? '' : 'disabled'; ?>
                                        <?php echo $language === $approvedLanguages[0] ? 'selected' : ''; ?>
                                    ><?php echo $escape($service->languageLabel($language) . ($isApproved ? '' : ' (not approved)')); ?></option>
                                <?php } ?>
                            </select>
                        </div>
                        <div class="mb-3 col-md-3">
                            <button type="submit" class="btn btn-outline-primary w-100">
                                <i class="bi bi-send" aria-hidden="true"></i>
                                Send Test
                            </button>
                        </div>
                    </div>
                </form>
            <?php } ?>
        </div>
    </div>
</section>
