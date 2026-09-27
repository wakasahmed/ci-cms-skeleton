<?php
$escape = function ($value) {
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
};

$key = $editor_section['key'];
$sectionLabel = $editor_section['label'];
$languageBaseURL = base_url(
    'manage/miscellaneous-contents/'.rawurlencode($key).'/edit'
);
$firstErrorName = '';
$statusName = $this->content_section_service->status_name($key);
$statusError = isset($errors[$statusName]) ? $errors[$statusName] : '';
$localeLabel = isset($locales[$locale]['label']) ? $locales[$locale]['label'] : strtoupper($locale);

if (!empty($errors)) {
    reset($errors);
    $firstErrorName = key($errors);
}
?>

<?php $this->load->view('admin/partials/breadcrumb', array(
    'items' => array(
        array('label' => 'Miscellaneous Contents', 'url' => base_url('manage/miscellaneous-contents')),
        array('label' => $sectionLabel, 'active' => TRUE),
    ),
)); ?>

<section
    class="admin-records-listing miscellaneous-content-editor"
    aria-labelledby="miscellaneous-content-title"
    data-translation-poll
    data-module="miscellaneous_contents"
    data-status-url="<?php echo $escape(base_url('manage/translations/statuses')); ?>"
    data-poll-seconds="4"
    data-locale="<?php echo $escape($locale); ?>"
>
    <?php $this->load->view('admin/partials/module_header', array(
        'title' => 'Miscellaneous Contents',
        'description' => 'Manage reusable global content shown across the website.',
        'id' => 'miscellaneous-content-title',
        'actions_view' => 'admin/content_sections/_language_switch',
        'actions_data' => array(
            'language_base_url' => $languageBaseURL,
            'locales' => $locales,
            'locale' => $locale,
            'translation_module' => 'miscellaneous_contents',
            'translation_entity_id' => $key,
            'translation_status' => isset($translation_state['status'])
                ? $translation_state['status']
                : 'MISSING',
            'translation_ready' => isset($translation_state['status'])
                && $translation_state['status'] === 'SUCCEEDED',
            'can_update' => $can_update,
        ),
    )); ?>

    <?php if ($success_message) { ?>
        <div class="alert alert-success" role="status">
            <?php echo $escape($success_message); ?>
        </div>
    <?php } ?>

    <?php if ($error_message) { ?>
        <div class="alert alert-danger" role="alert">
            <?php echo $escape($error_message); ?>
        </div>
    <?php } ?>

    <form
        id="content-sections-form"
        class="validate"
        method="post"
        action="<?php echo base_url('manage/miscellaneous-contents/'.rawurlencode($key).'/save'); ?>"
        enctype="multipart/form-data"
        novalidate
        data-content-sections-form
        data-manage-language-form
        data-submit-lock
        data-first-error="<?php echo $escape($firstErrorName); ?>"
    >
        <input type="hidden" name="locale" value="<?php echo $escape($locale); ?>">
        <input type="hidden" name="redirect_lang" value="<?php echo $escape($locale); ?>" data-redirect-locale>
        <?php if ($this->config->item('csrf_protection')) { ?>
            <input
                type="hidden"
                name="<?php echo $escape($this->security->get_csrf_token_name()); ?>"
                value="<?php echo $escape($this->security->get_csrf_hash()); ?>"
            >
        <?php } ?>

        <?php if (!$can_update) { ?>
            <fieldset disabled>
        <?php } ?>

        <div class="card admin-card content-locale-panel miscellaneous-content-card" dir="<?php echo $locale === 'ar' ? 'rtl' : 'ltr'; ?>" data-section-card>
            <div class="card-body miscellaneous-content-card-body">
                <div class="miscellaneous-editor-context">
                    <div>
                        <h2><?php echo $escape($sectionLabel); ?></h2>
                        <p>Edit the <?php echo $escape($localeLabel); ?> content for this global block.</p>
                    </div>
                    <span class="miscellaneous-editor-key"><?php echo $escape($key); ?></span>
                </div>

                <?php $this->load->view('admin/content_sections/_field_renderer', array(
                    'section' => $editor_section,
                    'locale' => $locale,
                    'errors' => $errors,
                )); ?>

                <div class="admin-publishing-panel content-section-publishing-panel">
                    <div>
                        <h4>Section status</h4>
                        <p>Control whether this global content block is enabled or disabled on the website.</p>
                    </div>
                    <div class="admin-field mb-0">
                        <label class="form-label" for="status-<?php echo $escape($key); ?>">Status</label>
                        <select
                            class="form-select select2<?php echo $statusError ? ' is-invalid' : ''; ?>"
                            name="<?php echo $escape($statusName); ?>"
                            id="status-<?php echo $escape($key); ?>"
                            data-minimum-results-for-search="-1"
                        >
                            <option value="Enable"<?php echo $editor_section['section_status'] === 'Enable' ? ' selected' : ''; ?>>Enable</option>
                            <option value="Disable"<?php echo $editor_section['section_status'] === 'Disable' ? ' selected' : ''; ?>>Disable</option>
                        </select>
                        <?php if ($statusError) { ?>
                            <div class="invalid-feedback d-block"><?php echo $escape($statusError); ?></div>
                        <?php } ?>
                    </div>
                </div>
            </div>
        </div>

        <?php if (!$can_update) { ?>
            </fieldset>
        <?php } ?>

        <div class="admin-form-actions content-section-actions">
            <a class="btn btn-outline-secondary" href="<?php echo base_url('manage/miscellaneous-contents'); ?>">Back to Contents</a>
            <?php if ($can_update) { ?>
                <button type="submit" class="btn btn-primary" data-save-button>
                    <span data-save-label>Save <?php echo $escape($sectionLabel); ?></span>
                </button>
            <?php } ?>
        </div>
    </form>
</section>

<?php $this->load->view('admin/content_sections/_language_modal'); ?>
