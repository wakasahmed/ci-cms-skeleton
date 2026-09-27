<?php
$isEdit = ($alert === 'edit' && !empty($tbl_data[$this->pKey]));
$crumb = $isEdit ? 'Edit' : 'Add';
$recordID = $isEdit ? (int) $tbl_data[$this->pKey] : 0;
$parentID = (int) $parent_id;
$action = base_url('manage/'.$this->controller.'/'.($isEdit ? 'editRecord/'.$parentID.'/'.$recordID : 'addRecord/'.$parentID));
$localizedValue = function ($key) use ($localized_values) { return isset($localized_values[$key]) ? htmlspecialchars((string) $localized_values[$key], ENT_QUOTES, 'UTF-8') : ''; };
$value = function ($key) use ($form_values) { return isset($form_values[$key]) ? htmlspecialchars((string) $form_values[$key], ENT_QUOTES, 'UTF-8') : ''; };
$localeDetails = $manage_locales[$active_locale];
$translationStatus = $isEdit && !empty($translation_state['status']) ? $translation_state['status'] : 'MISSING';
$languageBaseUrl = $isEdit ? base_url('manage/'.$this->controller.'/control/'.$parentID.'/'.$recordID) : '';
$listingUrl = ADMIN_URL.'pages'.($parentID > 0 ? '/index/'.$parentID : '');
$ogImage = !empty($form_values['current_og_image']) ? basename((string) $form_values['current_og_image']) : '';
$bannerImage = !empty($form_values['current_banner_background']) ? basename((string) $form_values['current_banner_background']) : '';
$imageColumnSuffix = $active_locale === 'ar' ? '_ar' : '';
$breadcrumbItems = array(array('label' => $this->moduleName, 'url' => ADMIN_URL.'pages'));
if ($parentID > 0) $breadcrumbItems[] = array('label' => $parent_name, 'url' => $listingUrl);
$breadcrumbItems[] = array('label' => $crumb.' '.$this->moduleNameSingular, 'active' => TRUE);
$hasSeoContent = FALSE;
foreach (array('localized_page_title', 'localized_meta_description', 'localized_meta_keywords') as $seoField) {
    if (!empty($localized_values[$seoField])) $hasSeoContent = TRUE;
}
$hasSharingContent = $ogImage !== '' || !empty($localized_values['localized_og_title']) || !empty($localized_values['localized_og_description']);
?>
<?php $this->load->view('admin/partials/breadcrumb', array('items' => $breadcrumbItems)); ?>
<?php if (!empty($form_error)) { ?><div class="row alertrow"><div class="col-md-12"><div class="alert alert-danger alert-dismissible fade show" role="alert"><strong>Could not save the web page.</strong> <?php echo htmlspecialchars($form_error, ENT_QUOTES, 'UTF-8'); ?><button type="button" class="btn-close alertBox crud-alert-close" data-bs-dismiss="alert" aria-label="Dismiss notification"></button></div></div></div><?php } ?>

<section class="admin-records-listing admin-form-screen" aria-labelledby="web-page-form-title"<?php if ($isEdit) { ?> data-translation-poll data-module="pages" data-status-url="<?php echo base_url('manage/translations/statuses'); ?>" data-poll-seconds="4" data-locale="<?php echo htmlspecialchars($active_locale, ENT_QUOTES, 'UTF-8'); ?>"<?php } ?>>
    <?php $this->load->view('admin/partials/module_header', array(
        'title' => $this->moduleName,
        'description' => 'Build the page content first, then configure presentation, search, sharing, and publishing.',
        'id' => 'web-page-form-title',
    )); ?>

    <div class="card admin-card">
        <div class="card-header d-flex flex-wrap align-items-center justify-content-between gap-3">
            <div>
                <h2 class="card-title mb-1"><?php echo $crumb; ?> <?php echo htmlspecialchars($this->moduleNameSingular, ENT_QUOTES, 'UTF-8'); ?></h2>
                <p class="text-muted small mb-0"><?php echo htmlspecialchars($localeDetails['label'], ENT_QUOTES, 'UTF-8'); ?> content and locale-specific media</p>
            </div>
            <?php if ($isEdit) { $this->load->view('admin/partials/manage_language_switcher', array(
                'language_base_url' => $languageBaseUrl, 'locales' => $manage_locales, 'locale' => $active_locale,
                'translation_module' => 'pages', 'translation_entity_id' => $recordID,
                'translation_status' => $translationStatus, 'translation_ready' => $translationStatus === 'SUCCEEDED',
            )); } ?>
        </div>
        <div class="card-body">
            <form id="pages_form" name="pages_form" method="post" action="<?php echo $action; ?>" enctype="multipart/form-data" class="validate" data-submit-lock data-manage-language-form novalidate>
                <input type="hidden" name="active_locale" value="<?php echo htmlspecialchars($active_locale, ENT_QUOTES, 'UTF-8'); ?>">
                <input type="hidden" name="redirect_lang" value="" data-redirect-locale>
                <?php if ($this->config->item('csrf_protection')) { ?><input type="hidden" name="<?php echo htmlspecialchars($this->security->get_csrf_token_name(), ENT_QUOTES, 'UTF-8'); ?>" value="<?php echo htmlspecialchars($this->security->get_csrf_hash(), ENT_QUOTES, 'UTF-8'); ?>"><?php } ?>

                <div class="accordion admin-form-accordion" id="pageEditorAccordion">
                    <article class="accordion-item admin-form-accordion-item">
                        <h3 class="accordion-header" id="page-content-heading">
                            <button class="accordion-button" type="button" data-bs-toggle="collapse" data-bs-target="#page-content-panel" aria-expanded="true" aria-controls="page-content-panel">
                                <span class="admin-form-section-icon"><i class="bi bi-file-earmark-text" aria-hidden="true"></i></span>
                                <span class="admin-form-section-heading"><span class="admin-form-section-title">Page Content</span><span class="admin-form-section-description">Name, URL, navigation label, and main body</span></span>
                                <span class="badge text-bg-danger admin-form-section-status">Required</span>
                            </button>
                        </h3>
                        <div id="page-content-panel" class="accordion-collapse collapse show" aria-labelledby="page-content-heading">
                            <div class="accordion-body">
                                <div class="row">
                                    <div class="admin-field mb-3 col-lg-7"><label class="form-label is-required" for="localized_name">Page Name</label><input type="text" name="localized_name" id="localized_name" maxlength="255" value="<?php echo $localizedValue('localized_name'); ?>" class="form-control<?php echo $isEdit ? '' : ' auto-slug-source'; ?>" data-validate="required,maxlength[255]" data-translation-field="localized_name"<?php if (!$isEdit) { ?> data-slug-target="#page_slug" data-slug-language="<?php echo htmlspecialchars($active_locale, ENT_QUOTES, 'UTF-8'); ?>"<?php } ?> dir="<?php echo $localeDetails['direction']; ?>"<?php echo $active_locale === 'ar' ? ' lang="ar"' : ''; ?> required><div class="form-text">Used as the main internal name for this page.</div></div>
                                    <div class="admin-field mb-3 col-lg-5"><label class="form-label" for="localized_menu_name">Menu Name</label><input type="text" name="localized_menu_name" id="localized_menu_name" maxlength="100" value="<?php echo $localizedValue('localized_menu_name'); ?>" class="form-control" data-validate="maxlength[100]" data-translation-field="localized_menu_name" dir="<?php echo $localeDetails['direction']; ?>"<?php echo $active_locale === 'ar' ? ' lang="ar"' : ''; ?>><div class="form-text">Optional shorter label for navigation menus.</div></div>
                                </div>
                                <div class="admin-field mb-4">
                                    <label class="form-label is-required" for="page_slug">Page URL Slug</label>
                                    <div class="input-group" dir="ltr"><span class="input-group-text"><?php echo $active_locale === 'ar' ? '/ar/' : '/en/'; ?></span><input type="text" name="page_slug" id="page_slug" maxlength="255" value="<?php echo $value('page_slug'); ?>" class="form-control slug" placeholder="<?php echo $active_locale === 'ar' ? 'من-نحن' : 'about-us'; ?>" data-validate="required,maxlength[255]"<?php echo $active_locale === 'ar' ? ' lang="ar" dir="rtl"' : ''; ?> required><button type="button" class="btn btn-outline-secondary generate-slug" data-slug-source="#localized_name" data-slug-target="#page_slug" data-slug-language="<?php echo htmlspecialchars($active_locale, ENT_QUOTES, 'UTF-8'); ?>" title="Generate slug from page name" aria-label="Generate <?php echo htmlspecialchars($localeDetails['label'], ENT_QUOTES, 'UTF-8'); ?> slug from page name"><i class="bi bi-arrow-clockwise" aria-hidden="true"></i><span class="d-none d-sm-inline ms-1">Generate</span></button></div>
                                    <div class="form-text">Language-specific and automatically made unique when saved.</div>
                                </div>
                                <div class="admin-field mb-4"><label class="form-label" for="localized_page_text">Page Contents</label><?php echo $this->ckeditor->editor('localized_page_text', isset($localized_values['localized_page_text']) ? (string) $localized_values['localized_page_text'] : ''); ?></div>
                                <div class="admin-publishing-panel">
                                    <div><h4>Publication Status</h4><p>Published pages are available to website visitors. Keep a page unpublished while it is still being prepared.</p></div>
                                    <div class="admin-field mb-0"><label class="form-label" for="page_status">Status</label><select class="form-select select2" name="page_status" id="page_status" data-minimum-results-for-search="-1"><option value="Published"<?php echo $form_values['page_status'] === 'Published' ? ' selected' : ''; ?>>Published</option><option value="Un-Published"<?php echo $form_values['page_status'] === 'Un-Published' ? ' selected' : ''; ?>>Unpublished</option></select></div>
                                </div>
                            </div>
                        </div>
                    </article>

                    <article class="accordion-item admin-form-accordion-item">
                        <h3 class="accordion-header" id="page-presentation-heading">
                            <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#page-presentation-panel" aria-expanded="false" aria-controls="page-presentation-panel">
                                <span class="admin-form-section-icon"><i class="bi bi-image" aria-hidden="true"></i></span>
                                <span class="admin-form-section-heading"><span class="admin-form-section-title">Page Presentation</span><span class="admin-form-section-description">Slider, top banner, imagery, and colours</span></span>
                                <span class="badge <?php echo (int) $form_values['show_top_banner'] === 1 ? 'text-bg-success' : 'text-bg-light'; ?> admin-form-section-status"><?php echo (int) $form_values['show_top_banner'] === 1 ? 'Banner on' : 'Optional'; ?></span>
                            </button>
                        </h3>
                        <div id="page-presentation-panel" class="accordion-collapse collapse" aria-labelledby="page-presentation-heading">
                            <div class="accordion-body">
                                <div class="admin-form-subsection">
                                    <div class="admin-form-subsection-heading"><div><h4>Page Slider</h4><p>Choose an existing image slider to display with this page.</p></div></div>
                                    <div class="admin-field mb-0"><label class="form-label" for="page_slider">Image Slider</label><select class="form-select select2" name="page_slider" id="page_slider"><option value="">No image slider</option><?php foreach ((array) $sliders as $slider) { ?><option value="<?php echo (int) $slider['sliders_id']; ?>"<?php echo (string) $slider['sliders_id'] === (string) $form_values['page_slider'] ? ' selected' : ''; ?>><?php echo htmlspecialchars((string) $slider['sliders_title'], ENT_QUOTES, 'UTF-8'); ?></option><?php } ?></select></div>
                                </div>

                                <div class="admin-form-subsection">
                                    <div class="admin-form-subsection-heading"><div><h4>Top Banner</h4><p>Configure the page hero shown above the main content.</p></div></div>
                                    <div class="admin-form-choice-group mb-4">
                                        <div class="form-check banner-check"><input type="hidden" name="show_top_banner" value="0"><input type="checkbox" name="show_top_banner" id="show_top_banner" value="1" class="form-check-input"<?php echo (int) $form_values['show_top_banner'] === 1 ? ' checked' : ''; ?>><label class="form-check-label fw-semibold" for="show_top_banner">Show banner</label><div class="form-text">Display the top banner above this page's main content.</div></div>
                                    </div>
                                    <div class="row">
                                        <div class="admin-field mb-3 col-lg-6"><label class="form-label" for="localized_banner_title">Eyebrow / Title</label><input type="text" name="localized_banner_title" id="localized_banner_title" maxlength="255" value="<?php echo $localizedValue('localized_banner_title'); ?>" class="form-control" data-validate="maxlength[255]" data-translation-field="localized_banner_title" dir="<?php echo $localeDetails['direction']; ?>"<?php echo $active_locale === 'ar' ? ' lang="ar"' : ''; ?>></div>
                                        <div class="admin-field mb-3 col-lg-6"><label class="form-label" for="localized_banner_heading">Main Heading</label><input type="text" name="localized_banner_heading" id="localized_banner_heading" maxlength="255" value="<?php echo $localizedValue('localized_banner_heading'); ?>" class="form-control" data-validate="maxlength[255]" data-translation-field="localized_banner_heading" dir="<?php echo $localeDetails['direction']; ?>"<?php echo $active_locale === 'ar' ? ' lang="ar"' : ''; ?>></div>
                                    </div>
                                    <div class="admin-field mb-4"><label class="form-label" for="localized_banner_text">Supporting Text</label><textarea name="localized_banner_text" id="localized_banner_text" rows="3" class="form-control" data-translation-field="localized_banner_text" dir="<?php echo $localeDetails['direction']; ?>"<?php echo $active_locale === 'ar' ? ' lang="ar"' : ''; ?>><?php echo $localizedValue('localized_banner_text'); ?></textarea></div>
                                    <div class="admin-field mb-4">
                                        <?php $this->load->view('admin/partials/file_upload', array(
                                            'name' => 'banner_background_upload',
                                            'id' => 'banner_background_upload',
                                            'label' => $localeDetails['label'].' Banner Background (Optional)',
                                            'allowed_types' => UPLOAD_IMAGE_MIMES,
                                            'required' => FALSE,
                                            'current_path' => $bannerImage !== '' ? 'assets/frontend/images/pages/'.$bannerImage : '',
                                            'help' => 'Maximum size: '.UPLOAD_SIZE_MB.' MB.',
                                            'recommended_size' => '1920 × 720px (8:3)',
                                            'size_note' => 'Wide banner behind the page title. It is cropped from the centre to 8:3, so keep the subject in the middle.',
                                            'preview_alt' => 'Current '.$localeDetails['label'].' banner background',
                                            'preview_shape' => 'square',
                                            'preview_size' => 220,
                                        )); ?>
                                        <?php if ($isEdit && image_thumb_path('assets/frontend/images/pages/' . $bannerImage) !== FALSE) { ?><div class="admin-current-file-remove"><div class="admin-current-file-actions"><button class="btn btn-outline-danger btn-sm mt-3 removeFile" data-controller="<?php echo htmlspecialchars($this->controller, ENT_QUOTES, 'UTF-8'); ?>" data-file-name="banner_background<?php echo $imageColumnSuffix; ?>" data-file-id="<?php echo $recordID; ?>" type="button"><i class="bi bi-trash" aria-hidden="true"></i> Remove Image</button></div></div><?php } ?>
                                    </div>
                                </div>

                                <div class="admin-form-subsection mb-0">
                                    <div class="admin-form-subsection-heading"><div><h4>Banner Colours</h4><p>Select one colour for a solid appearance, or select two colours to create a gradient. These colour settings are shared across both the English and Arabic versions of the page.</p></div></div>
                                    <div class="admin-field mb-4"><label class="form-label" for="banner_overlay">Image Overlay</label><select class="form-select select2" name="banner_overlay" id="banner_overlay" data-minimum-results-for-search="-1"><option value="Yes"<?php echo $form_values['banner_overlay'] === 'Yes' ? ' selected' : ''; ?>>Yes</option><option value="No"<?php echo $form_values['banner_overlay'] === 'No' ? ' selected' : ''; ?>>No</option></select><div class="form-text">Adds an overlay to improve text contrast over the background image.</div></div>
                                    <?php foreach (array(
                                        array('label' => 'Background', 'first' => 'banner_background_color_1', 'second' => 'banner_background_color_2'),
                                        array('label' => 'Eyebrow / Title', 'first' => 'banner_title_color_1', 'second' => 'banner_title_color_2'),
                                        array('label' => 'Main Heading', 'first' => 'banner_heading_color_1', 'second' => 'banner_heading_color_2'),
                                        array('label' => 'Supporting Text', 'first' => 'banner_text_color_1', 'second' => 'banner_text_color_2'),
                                    ) as $colourGroup) { ?>
                                    <div class="row align-items-end">
                                        <div class="col-lg-3"><p class="admin-form-colour-label"><?php echo $colourGroup['label']; ?></p></div>
                                        <div class="admin-field mb-3 col-md-6 col-lg-4"><label class="visually-hidden" for="<?php echo $colourGroup['first']; ?>"><?php echo $colourGroup['label']; ?> colour one</label><input type="text" name="<?php echo $colourGroup['first']; ?>" id="<?php echo $colourGroup['first']; ?>" value="<?php echo $value($colourGroup['first']); ?>" class="form-control color-picker" placeholder="Colour 1" maxlength="50" dir="ltr" autocomplete="off" aria-label="<?php echo $colourGroup['label']; ?> colour one"></div>
                                        <div class="admin-field mb-3 col-md-6 col-lg-4"><label class="visually-hidden" for="<?php echo $colourGroup['second']; ?>"><?php echo $colourGroup['label']; ?> colour two</label><input type="text" name="<?php echo $colourGroup['second']; ?>" id="<?php echo $colourGroup['second']; ?>" value="<?php echo $value($colourGroup['second']); ?>" class="form-control color-picker" placeholder="Colour 2" maxlength="50" dir="ltr" autocomplete="off" aria-label="<?php echo $colourGroup['label']; ?> colour two"></div>
                                    </div>
                                    <?php } ?>
                                </div>
                            </div>
                        </div>
                    </article>

                    <article class="accordion-item admin-form-accordion-item">
                        <h3 class="accordion-header" id="page-search-heading">
                            <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#page-search-panel" aria-expanded="false" aria-controls="page-search-panel">
                                <span class="admin-form-section-icon"><i class="bi bi-search" aria-hidden="true"></i></span>
                                <span class="admin-form-section-heading"><span class="admin-form-section-title">Search Visibility</span><span class="admin-form-section-description">Search result text, keywords, and crawler controls</span></span>
                                <span class="badge <?php echo $hasSeoContent ? 'text-bg-success' : 'text-bg-light'; ?> admin-form-section-status"><?php echo $hasSeoContent ? 'Configured' : 'Optional'; ?></span>
                            </button>
                        </h3>
                        <div id="page-search-panel" class="accordion-collapse collapse" aria-labelledby="page-search-heading">
                            <div class="accordion-body">
                                <div class="admin-field mb-3"><label class="form-label" for="localized_page_title">Page Title</label><input type="text" name="localized_page_title" id="localized_page_title" maxlength="255" value="<?php echo $localizedValue('localized_page_title'); ?>" class="form-control" data-validate="maxlength[255]" data-translation-field="localized_page_title" dir="<?php echo $localeDetails['direction']; ?>"<?php echo $active_locale === 'ar' ? ' lang="ar"' : ''; ?>><div class="form-text">Leave blank to use the page name.</div></div>
                                <div class="row">
                                    <div class="admin-field mb-3 col-lg-7"><label class="form-label" for="localized_meta_description">Meta Description</label><textarea name="localized_meta_description" id="localized_meta_description" rows="4" class="form-control" data-translation-field="localized_meta_description" dir="<?php echo $localeDetails['direction']; ?>"<?php echo $active_locale === 'ar' ? ' lang="ar"' : ''; ?>><?php echo $localizedValue('localized_meta_description'); ?></textarea><div class="form-text">A concise summary that may appear in search results. Leave blank to use the banner text, then the page content.</div></div>
                                    <div class="admin-field mb-3 col-lg-5"><label class="form-label" for="localized_meta_keywords">Meta Keywords</label><textarea name="localized_meta_keywords" id="localized_meta_keywords" rows="4" class="form-control" data-translation-field="localized_meta_keywords" dir="<?php echo $localeDetails['direction']; ?>"<?php echo $active_locale === 'ar' ? ' lang="ar"' : ''; ?>><?php echo $localizedValue('localized_meta_keywords'); ?></textarea><div class="form-text">Optional comma-separated keywords.</div></div>
                                </div>
                                <fieldset class="admin-form-choice-group">
                                    <legend>Search Engine Access</legend>
                                    <div class="row">
                                        <div class="admin-field col-md-6"><div class="form-check"><input type="hidden" name="robots_index" value="0"><input type="checkbox" name="robots_index" id="robots_index" value="1" class="form-check-input"<?php echo (int) $form_values['robots_index'] === 1 ? ' checked' : ''; ?>><label class="form-check-label" for="robots_index">Allow indexing</label><div class="form-text">Permit this page to appear in search results.</div></div></div>
                                        <div class="admin-field col-md-6"><div class="form-check"><input type="hidden" name="robots_follow" value="0"><input type="checkbox" name="robots_follow" id="robots_follow" value="1" class="form-check-input"<?php echo (int) $form_values['robots_follow'] === 1 ? ' checked' : ''; ?>><label class="form-check-label" for="robots_follow">Allow link following</label><div class="form-text">Permit crawlers to follow links from this page.</div></div></div>
                                    </div>
                                </fieldset>
                            </div>
                        </div>
                    </article>

                    <article class="accordion-item admin-form-accordion-item">
                        <h3 class="accordion-header" id="page-sharing-heading">
                            <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#page-sharing-panel" aria-expanded="false" aria-controls="page-sharing-panel">
                                <span class="admin-form-section-icon"><i class="bi bi-share" aria-hidden="true"></i></span>
                                <span class="admin-form-section-heading"><span class="admin-form-section-title">Social Sharing</span><span class="admin-form-section-description">Preview title, description, and image for shared links</span></span>
                                <span class="badge <?php echo $hasSharingContent ? 'text-bg-success' : 'text-bg-light'; ?> admin-form-section-status"><?php echo $hasSharingContent ? 'Configured' : 'Optional'; ?></span>
                            </button>
                        </h3>
                        <div id="page-sharing-panel" class="accordion-collapse collapse" aria-labelledby="page-sharing-heading">
                            <div class="accordion-body">
                                <div class="admin-field mb-3"><label class="form-label" for="localized_og_title">Sharing Title</label><input type="text" name="localized_og_title" id="localized_og_title" maxlength="255" value="<?php echo $localizedValue('localized_og_title'); ?>" class="form-control" data-validate="maxlength[255]" data-translation-field="localized_og_title" dir="<?php echo $localeDetails['direction']; ?>"<?php echo $active_locale === 'ar' ? ' lang="ar"' : ''; ?>><div class="form-text">Leave blank to use the page title, or the page name followed by the site name.</div></div>
                                <div class="admin-field mb-4"><label class="form-label" for="localized_og_description">Sharing Description</label><textarea name="localized_og_description" id="localized_og_description" rows="3" class="form-control" data-translation-field="localized_og_description" dir="<?php echo $localeDetails['direction']; ?>"<?php echo $active_locale === 'ar' ? ' lang="ar"' : ''; ?>><?php echo $localizedValue('localized_og_description'); ?></textarea></div>
                                <div class="admin-field mb-0">
                                    <?php $this->load->view('admin/partials/file_upload', array(
                                        'name' => 'og_image_upload',
                                        'id' => 'og_image_upload',
                                        'label' => $localeDetails['label'].' Sharing Image (Optional)',
                                        'allowed_types' => UPLOAD_IMAGE_MIMES,
                                        'required' => FALSE,
                                        'current_path' => $ogImage !== '' ? 'assets/frontend/images/pages/'.$ogImage : '',
                                        'help' => 'Maximum size: '.UPLOAD_SIZE_MB.' MB.',
                                        'recommended_size' => '1200 × 630px',
                                        'size_note' => 'Used for link previews on social media and messaging apps. Leave empty to use the banner background, then the site default.',
                                        'preview_alt' => 'Current '.$localeDetails['label'].' sharing image',
                                        'preview_shape' => 'square',
                                        'preview_size' => 220,
                                    )); ?>
                                    <?php if ($isEdit && image_thumb_path('assets/frontend/images/pages/' . $ogImage) !== FALSE) { ?><div class="admin-current-file-remove"><div class="admin-current-file-actions"><button class="btn btn-outline-danger btn-sm mt-3 removeFile" data-controller="<?php echo htmlspecialchars($this->controller, ENT_QUOTES, 'UTF-8'); ?>" data-file-name="og_image<?php echo $imageColumnSuffix; ?>" data-file-id="<?php echo $recordID; ?>" type="button"><i class="bi bi-trash" aria-hidden="true"></i> Remove Image</button></div></div><?php } ?>
                                </div>
                            </div>
                        </div>
                    </article>

                </div>

                <div class="admin-form-actions"><a class="btn btn-outline-secondary" href="<?php echo $listingUrl; ?>">Cancel</a><button type="submit" class="btn btn-primary" data-save-button><span data-save-label>Save <?php echo htmlspecialchars($this->moduleNameSingular, ENT_QUOTES, 'UTF-8'); ?></span></button></div>
            </form>
        </div>
    </div>
</section>
<?php if ($isEdit) $this->load->view('admin/partials/manage_language_switch_modal'); ?>

<script>
jQuery(function ($) {
    var accordion = document.getElementById('pageEditorAccordion');
    var form = document.getElementById('pages_form');
    if (!accordion || !form || !window.bootstrap || !bootstrap.Collapse) return;
    function firstMissingRequiredField() {
        return Array.prototype.slice.call(form.querySelectorAll('[required]')).find(function (field) {
            if (field.disabled) return false;
            if (field.type === 'checkbox' || field.type === 'radio') return !field.checked;
            return !String(field.value || '').trim();
        });
    }
    function revealRequiredField(field) {
        var panel = field.closest('.accordion-collapse');
        var focusAndValidate = function () {
            if ($.fn.validate) $(field).valid();
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
        if (!firstMissingField) return;
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
