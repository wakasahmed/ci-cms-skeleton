<?php
$escape = function ($value) {
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
};
$localizedValue = function ($key) use ($localized_values, $escape) {
    return isset($localized_values[$key]) ? $escape($localized_values[$key]) : '';
};
$localeDetails = $manage_locales[$active_locale];
$translationStatus = !empty($translation_state['status'])
    ? $translation_state['status']
    : 'MISSING';
$languageBaseUrl = base_url('manage/'.$this->controller);
?>
<?php $this->load->view('admin/partials/breadcrumb', array(
    'items' => array(array('label' => 'Form Settings', 'active' => TRUE)),
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

<section
    class="admin-records-listing"
    aria-labelledby="form-settings-title"
    data-translation-poll
    data-module="form_settings"
    data-status-url="<?php echo base_url('manage/translations/statuses'); ?>"
    data-poll-seconds="4"
    data-locale="<?php echo $escape($active_locale); ?>"
>
    <?php $this->load->view('admin/partials/module_header', array(
        'title' => 'Form Settings',
        'description' => 'Manage customer-facing form labels and the confirmation messages shown after submissions.',
        'id' => 'form-settings-title',
    )); ?>

    <div class="card admin-card">
        <div class="card-header d-flex flex-wrap align-items-center justify-content-between gap-3">
            <h2 class="card-title mb-0">Edit Form Settings</h2>
            <?php $this->load->view('admin/partials/manage_language_switcher', array(
                'language_base_url' => $languageBaseUrl,
                'locales' => $manage_locales,
                'locale' => $active_locale,
                'translation_module' => 'form_settings',
                'translation_entity_id' => $this->recordId,
                'translation_status' => $translationStatus,
                'translation_ready' => $translationStatus === 'SUCCEEDED',
            )); ?>
        </div>
        <div class="card-body">
            <form
                id="contact_settings_form"
                name="contact_settings_form"
                method="post"
                action="<?php echo base_url('manage/'.$this->controller.'/save'); ?>"
                class="validate"
                data-manage-language-form
            >
                <input type="hidden" name="active_locale" value="<?php echo $escape($active_locale); ?>">
                <input type="hidden" name="redirect_lang" value="" data-redirect-locale>
                <?php if ($this->config->item('csrf_protection')) { ?>
                    <input type="hidden" name="<?php echo $escape($this->security->get_csrf_token_name()); ?>" value="<?php echo $escape($this->security->get_csrf_hash()); ?>">
                <?php } ?>

                <div class="accordion admin-form-accordion" id="formSettingsAccordion">
                    <article class="accordion-item admin-form-accordion-item">
                        <h3 class="accordion-header" id="submission-confirmations-heading">
                            <button class="accordion-button" type="button" data-bs-toggle="collapse" data-bs-target="#submission-confirmations-panel" aria-expanded="true" aria-controls="submission-confirmations-panel">
                                <span class="admin-form-section-icon"><i class="bi bi-check2-circle" aria-hidden="true"></i></span>
                                <span class="admin-form-section-heading">
                                    <span class="admin-form-section-title">Submission Confirmation Messages</span>
                                    <span class="admin-form-section-description">Messages customers see after successfully submitting each form.</span>
                                </span>
                                <span class="badge text-bg-danger admin-form-section-status">Required</span>
                            </button>
                        </h3>
                        <div id="submission-confirmations-panel" class="accordion-collapse collapse show" aria-labelledby="submission-confirmations-heading">
                            <div class="accordion-body">
                                <div class="admin-field mb-3">
                                    <label class="form-label is-required" for="localized_contact_success">Contact Success Message</label>
                                    <textarea name="localized_contact_success" id="localized_contact_success" rows="4" class="form-control" data-translation-field="localized_contact_success" data-validate="required" required dir="<?php echo $escape($localeDetails['direction']); ?>"<?php echo $active_locale === 'ar' ? ' lang="ar"' : ''; ?>><?php echo $localizedValue('localized_contact_success'); ?></textarea>
                                </div>

                                <div class="admin-field mb-3">
                                    <label class="form-label is-required" for="localized_plan_success">Plan Your Visit Success Message</label>
                                    <textarea name="localized_plan_success" id="localized_plan_success" rows="4" class="form-control" data-translation-field="localized_plan_success" data-validate="required" required dir="<?php echo $escape($localeDetails['direction']); ?>"<?php echo $active_locale === 'ar' ? ' lang="ar"' : ''; ?>><?php echo $localizedValue('localized_plan_success'); ?></textarea>
                                </div>

                                <div class="admin-field mb-3">
                                    <label class="form-label is-required" for="localized_tour_success">Tour Success Message</label>
                                    <textarea name="localized_tour_success" id="localized_tour_success" rows="4" class="form-control" data-translation-field="localized_tour_success" data-validate="required" required dir="<?php echo $escape($localeDetails['direction']); ?>"<?php echo $active_locale === 'ar' ? ' lang="ar"' : ''; ?>><?php echo $localizedValue('localized_tour_success'); ?></textarea>
                                </div>

                                <div class="admin-field mb-0">
                                    <label class="form-label is-required" for="localized_experience_success">Experience Success Message</label>
                                    <textarea name="localized_experience_success" id="localized_experience_success" rows="4" class="form-control" data-translation-field="localized_experience_success" data-validate="required" required dir="<?php echo $escape($localeDetails['direction']); ?>"<?php echo $active_locale === 'ar' ? ' lang="ar"' : ''; ?>><?php echo $localizedValue('localized_experience_success'); ?></textarea>
                                </div>
                            </div>
                        </div>
                    </article>

                    <article class="accordion-item admin-form-accordion-item">
                        <h3 class="accordion-header" id="customer-form-labels-heading">
                            <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#customer-form-labels-panel" aria-expanded="false" aria-controls="customer-form-labels-panel">
                                <span class="admin-form-section-icon"><i class="bi bi-input-cursor-text" aria-hidden="true"></i></span>
                                <span class="admin-form-section-heading">
                                    <span class="admin-form-section-title">Customer Form Field Labels</span>
                                    <span class="admin-form-section-description">Labels displayed for fields on the Contact and Plan Your Visit forms.</span>
                                </span>
                                <span class="badge text-bg-danger admin-form-section-status">Required</span>
                            </button>
                        </h3>
                        <div id="customer-form-labels-panel" class="accordion-collapse collapse" aria-labelledby="customer-form-labels-heading">
                            <div class="accordion-body">
                                <div class="admin-field mb-3">
                                    <label class="form-label is-required" for="localized_contact_subject">Subject (Contact)</label>
                                    <textarea name="localized_contact_subject" id="localized_contact_subject" rows="3" class="form-control" data-translation-field="localized_contact_subject" data-validate="required" required dir="<?php echo $escape($localeDetails['direction']); ?>"<?php echo $active_locale === 'ar' ? ' lang="ar"' : ''; ?>><?php echo $localizedValue('localized_contact_subject'); ?></textarea>
                                </div>

                                <div class="admin-field mb-3">
                                    <label class="form-label is-required" for="localized_pyt_interests">Interests (Plan Your Visit)</label>
                                    <textarea name="localized_pyt_interests" id="localized_pyt_interests" rows="3" class="form-control" data-translation-field="localized_pyt_interests" data-validate="required" required dir="<?php echo $escape($localeDetails['direction']); ?>"<?php echo $active_locale === 'ar' ? ' lang="ar"' : ''; ?>><?php echo $localizedValue('localized_pyt_interests'); ?></textarea>
                                </div>

                                <div class="admin-field mb-0">
                                    <label class="form-label is-required" for="localized_pyt_visit_time">Visiting Time (Plan Your Visit)</label>
                                    <textarea name="localized_pyt_visit_time" id="localized_pyt_visit_time" rows="3" class="form-control" data-translation-field="localized_pyt_visit_time" data-validate="required" required dir="<?php echo $escape($localeDetails['direction']); ?>"<?php echo $active_locale === 'ar' ? ' lang="ar"' : ''; ?>><?php echo $localizedValue('localized_pyt_visit_time'); ?></textarea>
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

<?php $this->load->view('admin/partials/manage_language_switch_modal'); ?>

<script>
jQuery(function ($) {
    var accordion = document.getElementById('formSettingsAccordion');
    var form = document.getElementById('contact_settings_form');

    if (!accordion || !form || !window.bootstrap || !bootstrap.Collapse) {
        return;
    }

    function firstMissingRequiredField() {
        return Array.prototype.slice.call(form.querySelectorAll('[required]')).find(function (field) {
            if (field.disabled) {
                return false;
            }

            if (field.type === 'checkbox' || field.type === 'radio') {
                return !field.checked;
            }

            return !String(field.value || '').trim();
        });
    }

    function revealRequiredField(field) {
        var panel = field.closest('.accordion-collapse');
        var focusAndValidate = function () {
            if ($.fn.validate) {
                $(field).valid();
            }

            field.focus();
        };

        if (panel && !panel.classList.contains('show')) {
            panel.addEventListener('shown.bs.collapse', focusAndValidate, { once: true });
            bootstrap.Collapse.getOrCreateInstance(panel, { toggle: false }).show();
        } else {
            focusAndValidate();
        }
    }

    form.addEventListener('submit', function (event) {
        var firstMissingField = firstMissingRequiredField();

        if (!firstMissingField) {
            return;
        }

        event.preventDefault();
        event.stopImmediatePropagation();
        revealRequiredField(firstMissingField);
    }, true);

    form.addEventListener('invalid', function (event) {
        event.preventDefault();
        revealRequiredField(event.target);
    }, true);
});
</script>
