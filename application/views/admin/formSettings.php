<?php
$escape = function ($value) {
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
};
$value = function ($key) use ($tbl_data, $escape) {
    return isset($tbl_data[$key]) ? $escape($tbl_data[$key]) : '';
};
$invalidClass = function ($key) use ($invalid_fields) {
    return in_array($key, $invalid_fields, TRUE) ? ' is-invalid' : '';
};
?>
<?php $this->load->view('admin/partials/breadcrumb', array(
    'items' => array(
        array('label' => 'Form Settings', 'active' => TRUE),
    ),
)); ?>

<?php $this->load->view('admin/partials/crud_alert', array(
    'status' => $alert,
    'module_name' => 'Form Settings',
    'status_messages' => array(
        'success' => array('success', 'Success!', 'Form Settings saved successfully.'),
    ),
)); ?>

<?php if (!empty($form_error)) { ?>
    <div class="row alertrow">
        <div class="col-md-12">
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <strong>Could not save Form Settings.</strong>
                <?php echo $escape($form_error); ?>
                <button type="button" class="btn-close alertBox" data-bs-dismiss="alert" aria-label="Dismiss notification"></button>
            </div>
        </div>
    </div>
<?php } ?>

<section class="admin-records-listing" aria-labelledby="form-settings-title">
    <?php $this->load->view('admin/partials/module_header', array(
        'title' => 'Form Settings',
        'description' => 'Manage the options and confirmation messages used by the website forms.',
        'id' => 'form-settings-title',
    )); ?>

    <div class="card admin-card">
        <div class="card-header">
            <h2 class="card-title mb-0">Edit Form Settings</h2>
        </div>
        <div class="card-body">
            <form
                id="form_settings_form"
                name="form_settings_form"
                method="post"
                action="<?php echo base_url('manage/'.$this->controller.'/save'); ?>"
                class="validate"
                novalidate
                data-submit-lock
                data-accordion-validation
            >
                <?php if ($this->config->item('csrf_protection')) { ?>
                    <input type="hidden" name="<?php echo $escape($this->security->get_csrf_token_name()); ?>" value="<?php echo $escape($this->security->get_csrf_hash()); ?>">
                <?php } ?>

                <div class="accordion admin-form-accordion" id="formSettingsAccordion">
                    <article class="accordion-item admin-form-accordion-item">
                        <h3 class="accordion-header" id="contact-form-heading">
                            <button
                                class="accordion-button"
                                type="button"
                                data-bs-toggle="collapse"
                                data-bs-target="#contact-form-panel"
                                aria-expanded="true"
                                aria-controls="contact-form-panel"
                            >
                                <span class="admin-form-section-icon"><i class="bi bi-envelope" aria-hidden="true"></i></span>
                                <span class="admin-form-section-heading">
                                    <span class="admin-form-section-title">Contact Form</span>
                                    <span class="admin-form-section-description">Subject options and the message shown after sending.</span>
                                </span>
                                <span class="badge text-bg-danger admin-form-section-status">Required</span>
                            </button>
                        </h3>
                        <div id="contact-form-panel" class="accordion-collapse collapse show" aria-labelledby="contact-form-heading">
                            <div class="accordion-body">
                                <div class="admin-field mb-3">
                                    <label class="form-label is-required" for="contact_subject">Subject Options</label>
                                    <textarea
                                        name="contact_subject"
                                        id="contact_subject"
                                        rows="6"
                                        class="form-control<?php echo $invalidClass('contact_subject'); ?>"
                                        data-validate="required"
                                        required
                                    ><?php echo $value('contact_subject'); ?></textarea>
                                    <div class="form-text">One option per line, in the order they should appear.</div>
                                </div>

                                <div class="admin-field mb-0">
                                    <label class="form-label is-required" for="contact_success">Success Message</label>
                                    <textarea
                                        name="contact_success"
                                        id="contact_success"
                                        rows="4"
                                        class="form-control<?php echo $invalidClass('contact_success'); ?>"
                                        data-validate="required"
                                        required
                                    ><?php echo $value('contact_success'); ?></textarea>
                                    <div class="form-text">Shown after a visitor sends the contact form.</div>
                                </div>
                            </div>
                        </div>
                    </article>
                </div>

                <div class="admin-form-actions">
                    <a class="btn btn-outline-secondary" href="<?php echo ADMIN_URL; ?>">Cancel</a>
                    <button type="submit" class="btn btn-primary" data-save-button>
                        <span data-save-label>Save Form Settings</span>
                    </button>
                </div>
            </form>
        </div>
    </div>
</section>
