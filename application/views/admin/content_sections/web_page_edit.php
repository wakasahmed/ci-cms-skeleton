<?php
$escape = function ($value) {
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
};

$pageID = (int) $page['page_id'];
$pageName = html_entity_decode((string) $page['page_name'], ENT_QUOTES, 'UTF-8');
$firstErrorName = '';

if (!empty($errors)) {
    reset($errors);
    $firstErrorName = key($errors);
}
?>
<?php $this->load->view('admin/partials/breadcrumb', array(
    'items' => array(
        array('label' => 'Web Pages', 'url' => ADMIN_URL.'pages'),
        array('label' => $pageName.' Sections', 'active' => TRUE),
    ),
)); ?>

<section
    class="admin-records-listing web-page-sections-editor"
    aria-labelledby="web-page-sections-title"
>
    <?php $this->load->view('admin/partials/module_header', array(
        'title' => $pageName.' Sections',
        'description' => 'Manage the reusable content blocks for '.$pageName.'.',
        'id' => 'web-page-sections-title',
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
        action="<?php echo base_url('manage/web-pages/'.$pageID.'/sections/save'); ?>"
        enctype="multipart/form-data"
        novalidate
        data-content-sections-form
        data-submit-lock
        data-first-error="<?php echo $escape($firstErrorName); ?>"
    >
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

        <div class="card admin-card web-page-sections-card">
            <div class="card-header web-page-sections-card-header">
                <div>
                    <h2 class="card-title mb-1">Content blocks</h2>
                    <p class="text-muted small mb-0">Open a block to edit its fields. Drag blocks by their handle to change the page order.</p>
                </div>
                <span class="web-page-sections-count"><?php echo count($editor_sections); ?> section<?php echo count($editor_sections) === 1 ? '' : 's'; ?></span>
            </div>

            <div class="card-body">
                <div class="accordion admin-form-accordion content-sections-accordion" id="webPageSections" data-section-sortable>
                    <?php foreach ($editor_sections as $index => $section) { ?>
                        <?php
                        $statusName = $this->content_section_service->status_name($section['key']);
                        $statusError = isset($errors[$statusName]) ? $errors[$statusName] : '';
                        $sectionHasError = $statusError !== '';

                        foreach ($section['fields'] as $field) {
                            $fieldName = $this->content_section_service->field_name($section['key'], $field['key']);
                            if (isset($errors[$fieldName])) {
                                $sectionHasError = TRUE;
                                break;
                            }
                        }

                        $panelID = 'section-panel-'.$section['key'];
                        $headingID = 'section-heading-'.$section['key'];
                        ?>
                        <article
                            class="accordion-item admin-form-accordion-item content-section-card<?php echo $sectionHasError ? ' has-error' : ''; ?>"
                            data-section-card
                            data-section-key="<?php echo $escape($section['key']); ?>"
                            draggable="<?php echo $can_update ? 'true' : 'false'; ?>"
                        >
                            <input type="hidden" name="section_order[]" value="<?php echo $escape($section['key']); ?>">
                            <h3 class="accordion-header" id="<?php echo $escape($headingID); ?>">
                                <?php if ($can_update) { ?>
                                    <span
                                        class="content-section-drag-handle"
                                        draggable="true"
                                        title="Reorder section"
                                        aria-label="Reorder <?php echo $escape($section['label']); ?>"
                                    >
                                        <i class="bi bi-grip-vertical" aria-hidden="true"></i>
                                    </span>
                                <?php } ?>
                                <button
                                    class="accordion-button<?php echo $index === 0 ? '' : ' collapsed'; ?>"
                                    type="button"
                                    data-bs-toggle="collapse"
                                    data-bs-target="#<?php echo $escape($panelID); ?>"
                                    aria-expanded="<?php echo $index === 0 ? 'true' : 'false'; ?>"
                                    aria-controls="<?php echo $escape($panelID); ?>"
                                >
                                    <span class="admin-form-section-heading">
                                        <span class="admin-form-section-title"><?php echo $escape($section['label']); ?></span>
                                    </span>
                                    <?php if ($sectionHasError) { ?>
                                        <span class="badge text-bg-danger admin-form-section-status">Needs attention</span>
                                    <?php } elseif ($section['section_status'] === 'Disable') { ?>
                                        <span class="badge text-bg-secondary admin-form-section-status">Hidden</span>
                                    <?php } ?>
                                </button>
                            </h3>

                            <div
                                id="<?php echo $escape($panelID); ?>"
                                class="accordion-collapse collapse<?php echo $index === 0 ? ' show' : ''; ?>"
                                data-bs-parent="#webPageSections"
                                aria-labelledby="<?php echo $escape($headingID); ?>"
                            >
                                <div class="accordion-body">
                                    <?php $this->load->view('admin/content_sections/_field_renderer', array(
                                        'section' => $section,
                                        'locale' => $locale,
                                        'errors' => $errors,
                                    )); ?>

                                    <div class="admin-publishing-panel content-section-publishing-panel">
                                        <div>
                                            <h4>Section status</h4>
                                            <p>Control whether this content block is enabled or disabled on the published page.</p>
                                        </div>
                                        <div class="admin-field mb-0">
                                            <label class="form-label" for="status-<?php echo $escape($section['key']); ?>">Status</label>
                                            <select
                                                class="form-select select2<?php echo $statusError ? ' is-invalid' : ''; ?>"
                                                name="<?php echo $escape($statusName); ?>"
                                                id="status-<?php echo $escape($section['key']); ?>"
                                                data-minimum-results-for-search="-1"
                                            >
                                                <option value="Enable"<?php echo $section['section_status'] === 'Enable' ? ' selected' : ''; ?>>Enable</option>
                                                <option value="Disable"<?php echo $section['section_status'] === 'Disable' ? ' selected' : ''; ?>>Disable</option>
                                            </select>
                                            <?php if ($statusError) { ?>
                                                <div class="invalid-feedback d-block"><?php echo $escape($statusError); ?></div>
                                            <?php } ?>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </article>
                    <?php } ?>
                </div>
            </div>
        </div>

        <?php if (!$can_update) { ?>
            </fieldset>
        <?php } ?>

        <div class="admin-form-actions content-section-actions">
            <a class="btn btn-outline-secondary" href="<?php echo ADMIN_URL; ?>pages">Back to Web Pages</a>
            <?php if ($can_update) { ?>
                <button type="submit" class="btn btn-primary" data-save-button>
                    <span data-save-label>Save Sections</span>
                </button>
            <?php } ?>
        </div>
    </form>
</section>
