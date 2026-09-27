<?php
$isEdit = ($alert === 'edit' && !empty($tbl_data[$this->pKey]));
$crumb = $isEdit ? 'Edit' : 'Add';
$action = base_url('manage/'.$this->controller.'/'.($isEdit ? 'editRecord/'.$tbl_data[$this->pKey] : 'addRecord'));
$value = function ($key) use ($tbl_data) { return isset($tbl_data[$key]) ? htmlspecialchars((string) $tbl_data[$key], ENT_QUOTES, 'UTF-8') : ''; };
$localizedValue = function ($key) use ($localized_values) { return isset($localized_values[$key]) ? htmlspecialchars((string) $localized_values[$key], ENT_QUOTES, 'UTF-8') : ''; };
$localeDetails = $manage_locales[$active_locale];
$translationStatus = $isEdit && !empty($translation_state['status']) ? $translation_state['status'] : 'MISSING';
$languageBaseUrl = $isEdit ? base_url('manage/'.$this->controller.'/control/edit/'.(int) $tbl_data[$this->pKey]) : '';
?>
<?php $this->load->view('admin/partials/breadcrumb', array('items' => array(array('label' => $this->moduleName, 'url' => ADMIN_URL.$this->controller), array('label' => $crumb.' '.$this->moduleNameSingular, 'active' => TRUE)))); ?>

<?php if (!empty($form_error)) { ?>
<div class="row alertrow"><div class="col-md-12"><button class="btn-close alertBox" data-bs-dismiss="alert" aria-label="Dismiss"></button><div class="alert alert-danger"><strong>Could not save the email template.</strong> <?php echo htmlspecialchars($form_error, ENT_QUOTES, 'UTF-8'); ?></div></div></div>
<?php } ?>

<section class="admin-records-listing" aria-labelledby="email-templates-title"<?php if ($isEdit) { ?> data-translation-poll data-module="email_templates" data-status-url="<?php echo base_url('manage/translations/statuses'); ?>" data-poll-seconds="4" data-locale="<?php echo htmlspecialchars($active_locale, ENT_QUOTES, 'UTF-8'); ?>"<?php } ?>>
    <?php $this->load->view('admin/partials/module_header', array('title' => $this->moduleName, 'description' => $this->moduleDesc, 'id' => 'email-templates-title')); ?>

    <div class="card admin-card">
        <div class="card-header d-flex flex-wrap align-items-center justify-content-between gap-3"><h2 class="card-title mb-0"><?php echo $crumb; ?> <?php echo htmlspecialchars($this->moduleNameSingular, ENT_QUOTES, 'UTF-8'); ?></h2><?php if ($isEdit) { $this->load->view('admin/partials/manage_language_switcher', array('language_base_url' => $languageBaseUrl, 'locales' => $manage_locales, 'locale' => $active_locale, 'translation_module' => 'email_templates', 'translation_entity_id' => $tbl_data[$this->pKey], 'translation_status' => $translationStatus, 'translation_ready' => $translationStatus === 'SUCCEEDED')); } ?></div>
        <div class="card-body">
        <form id="<?php echo $this->controller; ?>_form" name="<?php echo $this->controller; ?>_form" method="post" action="<?php echo $action; ?>" class="validate" data-manage-language-form>
            <input type="hidden" name="active_locale" value="<?php echo htmlspecialchars($active_locale, ENT_QUOTES, 'UTF-8'); ?>">
            <input type="hidden" name="redirect_lang" value="" data-redirect-locale>
            <?php if ($this->config->item('csrf_protection')) { ?><input type="hidden" name="<?php echo htmlspecialchars($this->security->get_csrf_token_name(), ENT_QUOTES, 'UTF-8'); ?>" value="<?php echo htmlspecialchars($this->security->get_csrf_hash(), ENT_QUOTES, 'UTF-8'); ?>"><?php } ?>

            <div class="admin-field mb-3"><label class="form-label is-required" for="name">Name</label><input type="text" name="name" id="name" maxlength="255" value="<?php echo $value('name'); ?>" class="form-control" data-validate="required,maxlength[255]" required placeholder="Booking Confirmation"></div>

            <div class="admin-field mb-3">
                <label class="form-label is-required" for="localized_subject">Subject</label>
                <input
                    type="text"
                    name="localized_subject"
                    id="localized_subject"
                    maxlength="255"
                    value="<?php echo $localizedValue('localized_subject'); ?>"
                    class="form-control"
                    data-translation-field="localized_subject"
                    data-validate="required,maxlength[255]"
                    data-short-tag-target
                    required
                    dir="<?php echo $localeDetails['direction']; ?>"
                    <?php echo $active_locale === 'ar' ? 'lang="ar"' : ''; ?>
                >
            </div>

            <div class="admin-field mb-3">
                <label class="form-label" for="localized_heading">Heading</label>
                <input
                    type="text"
                    name="localized_heading"
                    id="localized_heading"
                    maxlength="255"
                    value="<?php echo $localizedValue('localized_heading'); ?>"
                    class="form-control"
                    data-translation-field="localized_heading"
                    data-validate="maxlength[255]"
                    data-short-tag-target
                    dir="<?php echo $localeDetails['direction']; ?>"
                    <?php echo $active_locale === 'ar' ? 'lang="ar"' : ''; ?>
                >
            </div>

            <?php $this->load->view('admin/partials/short_tag_picker', array(
                'tags' => $short_tags,
                'entity_label' => $short_tag_entity,
                'id' => 'email-template-tags',
                'description' => 'Copy a tag, or use + to insert it into the contents, or into the subject or '
                    . 'heading if you were editing one of those. Only these values are available when this email is sent.',
            )); ?>

            <div class="admin-field mb-4">
                <label class="form-label is-required" for="localized_contents">Contents</label>
                <?php echo $this->ckeditor->editor(
                    'localized_contents',
                    isset($localized_values['localized_contents']) ? (string) $localized_values['localized_contents'] : ''
                ); ?>
            </div>

            <div class="admin-form-actions"><a class="btn btn-outline-secondary" href="<?php echo ADMIN_URL.$this->controller; ?>">Cancel</a><button type="submit" class="btn btn-primary" data-save-button><span data-save-label>Save <?php echo htmlspecialchars($this->moduleNameSingular, ENT_QUOTES, 'UTF-8'); ?></span></button></div>
        </form>
        </div>
    </div>
</section>
<?php if ($isEdit) $this->load->view('admin/partials/manage_language_switch_modal'); ?>
