<?php
$isEdit = !empty($tbl_data[$this->pKey]);
$id = $isEdit ? (int)$tbl_data[$this->pKey] : 0;
$verb = $isEdit ? 'Edit' : 'Add';
$action = base_url('manage/blogs/' . ($isEdit ? 'editRecord/' . $id : 'addRecord'));
$locale = $manage_locales[$active_locale];
$suffix = $active_locale === 'ar' ? '_ar' : '';
$lv = function ($k) use ($localized_values) {
    return htmlspecialchars(isset($localized_values[$k]) ? (string)$localized_values[$k] : '', ENT_QUOTES, 'UTF-8');
};
$v = function ($k) use ($form_values) {
    return htmlspecialchars(isset($form_values[$k]) ? (string)$form_values[$k] : '', ENT_QUOTES, 'UTF-8');
};
$status = $isEdit && !empty($translation_state['status']) ? $translation_state['status'] : 'MISSING';
$thumbnail = basename((string)$form_values['current_blog_image']);
$cover = basename((string)$form_values['current_blog_cover_image']);
$og = basename((string)$form_values['current_og_image']);
$banner = basename((string)$form_values['current_banner_background']);
$textField = function ($name, $label, $type = 'input', $help = '', $required = FALSE) use ($lv, $locale) { ?><div class="admin-field mb-3"><label class="form-label<?php echo $required ? ' is-required' : ''; ?>" for="<?php echo $name; ?>"><?php echo $label; ?></label><?php if ($type === 'textarea') { ?><textarea name="<?php echo $name; ?>" id="<?php echo $name; ?>" rows="4" class="form-control" data-validate="<?php echo $required ? 'required' : ''; ?>" data-translation-field="<?php echo $name; ?>" dir="<?php echo $locale['direction']; ?>"<?php echo $required ? ' required' : ''; ?>><?php echo $lv($name); ?></textarea><?php } else { ?><input type="text" name="<?php echo $name; ?>" id="<?php echo $name; ?>" maxlength="255" value="<?php echo $lv($name); ?>" class="form-control" data-validate="<?php echo $required ? 'required,maxlength[255]' : 'maxlength[255]'; ?>" data-translation-field="<?php echo $name; ?>" dir="<?php echo $locale['direction']; ?>"<?php echo $required ? ' required' : ''; ?>><?php }
                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                            if ($help) { ?><div class="form-text"><?php echo $help; ?></div><?php } ?></div><?php };
                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                ?>
<?php $this->load->view('admin/partials/breadcrumb', array('items' => array(array('label' => 'Blogs', 'url' => base_url('manage/blogs')), array('label' => $verb . ' ' . $this->moduleNameSingular, 'active' => TRUE)))); ?>
<?php if ($form_error) { ?><div class="row alertrow">
        <div class="col-md-12">
            <div class="alert alert-danger alert-dismissible fade show"><strong>Could not save the blog.</strong> <?php echo htmlspecialchars($form_error, ENT_QUOTES, 'UTF-8'); ?><button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Dismiss notification"></button></div>
        </div>
    </div><?php } ?>
<section class="admin-records-listing admin-form-screen" aria-labelledby="blog-form-title" <?php if ($isEdit) { ?> data-translation-poll data-module="blogs" data-status-url="<?php echo base_url('manage/translations/statuses'); ?>" data-poll-seconds="4" data-locale="<?php echo $active_locale; ?>" <?php } ?>><?php $this->load->view('admin/partials/module_header', array('title' => 'Blogs', 'description' => 'Write the article first, then configure presentation, search visibility, and sharing.', 'id' => 'blog-form-title')); ?><div class="card admin-card">
        <div class="card-header d-flex flex-wrap align-items-center justify-content-between gap-3">
            <div>
                <h2 class="card-title mb-1"><?php echo $verb; ?> <?php echo htmlspecialchars($this->moduleNameSingular, ENT_QUOTES, 'UTF-8'); ?></h2>
                <p class="text-muted small mb-0"><?php echo htmlspecialchars($locale['label'], ENT_QUOTES, 'UTF-8'); ?> content and locale-specific media</p>
            </div><?php if ($isEdit) $this->load->view('admin/partials/manage_language_switcher', array('language_base_url' => base_url('manage/blogs/control/' . $id), 'locales' => $manage_locales, 'locale' => $active_locale, 'translation_module' => 'blogs', 'translation_entity_id' => $id, 'translation_status' => $status, 'translation_ready' => $status === 'SUCCEEDED')); ?>
        </div>
        <div class="card-body">
            <form id="blogs_form" method="post" action="<?php echo $action; ?>" enctype="multipart/form-data" class="validate" novalidate data-submit-lock data-manage-language-form><input type="hidden" name="active_locale" value="<?php echo $active_locale; ?>"><input type="hidden" name="redirect_lang" value="" data-redirect-locale><?php if ($this->config->item('csrf_protection')) { ?><input type="hidden" name="<?php echo htmlspecialchars($this->security->get_csrf_token_name(), ENT_QUOTES, 'UTF-8'); ?>" value="<?php echo htmlspecialchars($this->security->get_csrf_hash(), ENT_QUOTES, 'UTF-8'); ?>"><?php } ?>
                <div class="accordion admin-form-accordion" id="blogEditorAccordion">
                    <article class="accordion-item admin-form-accordion-item">
                        <h3 class="accordion-header"><button class="accordion-button" type="button" data-bs-toggle="collapse" data-bs-target="#blog-content-panel" aria-expanded="true"><span class="admin-form-section-icon"><i class="bi bi-journal-text"></i></span><span class="admin-form-section-heading"><span class="admin-form-section-title">Blog Content</span><span class="admin-form-section-description">Title, URL, article details, categories, thumbnail, and cover images</span></span><span class="badge text-bg-danger admin-form-section-status">Required</span></button></h3>
                        <div id="blog-content-panel" class="accordion-collapse collapse show">
                            <div class="accordion-body">
                                <div class="admin-field mb-3"><label class="form-label is-required" for="localized_name">Blog Title</label><input type="text" name="localized_name" id="localized_name" maxlength="255" value="<?php echo $lv('localized_name'); ?>" class="form-control<?php echo $isEdit ? '' : ' auto-slug-source'; ?>" data-validate="required,maxlength[255]" data-translation-field="localized_name"<?php if (!$isEdit) { ?> data-slug-target="#page_slug" data-slug-language="<?php echo htmlspecialchars($active_locale, ENT_QUOTES, 'UTF-8'); ?>"<?php } ?> dir="<?php echo $locale['direction']; ?>" required></div>
                                <div class="admin-field mb-4"><label class="form-label is-required" for="page_slug">Blog URL Slug</label>
                                    <div class="input-group" dir="ltr"><span class="input-group-text"><?php echo '/'.($active_locale === 'ar' ? 'ar/' : 'en/').BLOG_URI; ?></span><input type="text" name="page_slug" id="page_slug" maxlength="255" value="<?php echo $v('page_slug'); ?>" class="form-control slug" data-validate="required,maxlength[255]" required<?php echo $active_locale === 'ar' ? ' lang="ar" dir="rtl"' : ''; ?>><button type="button" class="btn btn-outline-secondary generate-slug" data-slug-source="#localized_name" data-slug-target="#page_slug" data-slug-language="<?php echo $active_locale; ?>"><i class="bi bi-arrow-clockwise"></i> <span class="d-none d-sm-inline">Generate</span></button></div>
                                    <div class="form-text">Language-specific and automatically made unique when saved.</div>
                                </div>
                                <div class="row">
                                    <div class="admin-field mb-3 col-md-6">
                                        <label class="form-label is-required" for="blog_author">Author</label>
                                        <?php $this->load->view('admin/partials/user_select', array(
                                            'name' => 'blog_author',
                                            'id' => 'blog_author',
                                            'users' => $admins,
                                            'selected_id' => $form_values['blog_author'],
                                            'required' => TRUE,
                                            'empty_label' => 'Select an author',
                                        )); ?>
                                    </div>
                                    <div class="admin-field mb-3 col-md-6">
                                        <label class="form-label is-required" for="blog_time_to_read">Time to Read</label>
                                        <select class="form-select select2" name="blog_time_to_read" id="blog_time_to_read" data-validate="required" required>
                                            <?php for ($minutes = 1; $minutes <= 60; $minutes++) { ?>
                                                <option value="<?php echo $minutes; ?>"<?php echo (int) $form_values['blog_time_to_read'] === $minutes ? ' selected' : ''; ?>><?php echo $minutes; ?> minute<?php echo $minutes === 1 ? '' : 's'; ?></option>
                                            <?php } ?>
                                        </select>
                                    </div>
                                    <div class="admin-field mb-3 col-12">
                                        <div class="d-flex align-items-center justify-content-between gap-2 mb-2">
                                            <label class="form-label is-required mb-0" for="blog_category">Categories</label>
                                            <button class="btn btn-sm btn-outline-secondary" type="button" id="addNewCat">
                                                <i class="bi bi-plus-circle" aria-hidden="true"></i> Add New Category
                                            </button>
                                        </div>
                                        <select class="form-select select2 required" name="blog_category[]" id="blog_category" multiple required>
                                            <?php foreach ($cats as $cat) { ?>
                                                <option value="<?php echo (int) $cat['cat_id']; ?>"<?php echo in_array((int) $cat['cat_id'], $selected_categories, TRUE) ? ' selected' : ''; ?>><?php echo htmlspecialchars(html_entity_decode((string) $cat['cat_name'], ENT_QUOTES, 'UTF-8'), ENT_QUOTES, 'UTF-8'); ?><?php echo $cat['cat_status'] === 'Disable' ? ' (Disabled)' : ''; ?></option>
                                            <?php } ?>
                                        </select>
                                        <div class="form-text">Categories are shared by both language versions.</div>
                                    </div>
                                </div><?php $textField('localized_short_description', 'Short Description', 'textarea', '', TRUE); ?><div class="admin-field mb-4"><label class="form-label" for="localized_text">Blog Contents</label><?php echo $this->ckeditor->editor('localized_text', isset($localized_values['localized_text']) ? (string)$localized_values['localized_text'] : ''); ?></div><?php if ($active_locale === 'en') {
                                $this->load->view('admin/partials/file_upload', array(
                                    'name' => 'blog_image_upload',
                                    'id' => 'blog_image_upload',
                                    'label' => 'Thumbnail Image',
                                    'allowed_types' => UPLOAD_IMAGE_MIMES,
                                    'required' => TRUE,
                                    'current_path' => $thumbnail !== '' ? 'assets/frontend/images/blogs/' . $thumbnail : '',
                                    'help' => 'Required image used for this post on the main blog listing page. Shared by both languages. Maximum ' . UPLOAD_SIZE_MB . ' MB.',
                                    'recommended_size' => '1600 × 900px (16:9)',
                                    'size_note' => 'Shown on blog cards and cropped from the centre to 16:9. Minimum 1200 × 675px.',
                                    'preview_alt' => 'Current thumbnail image',
                                    'preview_size' => 220,
                                ));
                                                                                                                                                                                                                                                                                                                                                                                    echo '<br clear="all">';
                                $this->load->view('admin/partials/file_upload', array(
                                    'name' => 'blog_cover_image_upload',
                                    'id' => 'blog_cover_image_upload',
                                    'label' => 'Cover Image (Optional)',
                                    'allowed_types' => UPLOAD_IMAGE_MIMES,
                                    'required' => FALSE,
                                    'current_path' => $cover !== '' ? 'assets/frontend/images/blogs/' . $cover : '',
                                    'help' => 'Main hero image displayed on the individual blog post page. Shared by both languages. Maximum ' . UPLOAD_SIZE_MB . ' MB.',
                                    'recommended_size' => '1920 × 640px (3:1)',
                                    'size_note' => 'Wide hero behind the post title. It is cropped from the centre to 3:1, so keep the subject in the middle.',
                                    'preview_alt' => 'Current cover image',
                                    'preview_size' => 220,
                                ));
                                                                                                                                                                                                                                                                                                                                                                                    if ($isEdit && image_thumb_path('assets/frontend/images/blogs/' . $cover) !== FALSE) { ?><div class="admin-current-file-remove">
                                            <div class="admin-current-file-actions"><button class="btn btn-outline-danger btn-sm mt-3 removeFile" data-controller="blogs" data-file-name="blog_cover_image" data-file-id="<?php echo $id; ?>" type="button"><i class="bi bi-trash"></i> Remove Image</button></div>
                                        </div><?php }
                                                                                                                                                                                                                                                                                                                                                                                } ?><div class="admin-publishing-panel mt-4">
                                    <div>
                                        <h4>Publishing</h4>
                                        <p>Control whether this article is visible and highlighted as featured.</p>
                                    </div>
                                    <div class="row flex-grow-1">
                                        <div class="admin-field col-md-6"><label class="form-label" for="blog_status">Status</label><select class="form-select select2" name="blog_status" id="blog_status">
                                                <option value="Published" <?php echo $form_values['blog_status'] === 'Published' ? ' selected' : ''; ?>>Published</option>
                                                <option value="Un-Published" <?php echo $form_values['blog_status'] === 'Un-Published' ? ' selected' : ''; ?>>Unpublished</option>
                                            </select></div>
                                        <div class="admin-field col-md-6"><label class="form-label" for="blog_featured">Featured</label><select class="form-select select2" name="blog_featured" id="blog_featured">
                                                <option value="No" <?php echo $form_values['blog_featured'] === 'No' ? ' selected' : ''; ?>>No</option>
                                                <option value="Yes" <?php echo $form_values['blog_featured'] === 'Yes' ? ' selected' : ''; ?>>Yes</option>
                                            </select></div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </article>
                    <article class="accordion-item admin-form-accordion-item">
                        <h3 class="accordion-header"><button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#blog-presentation-panel" aria-expanded="false"><span class="admin-form-section-icon"><i class="bi bi-image"></i></span><span class="admin-form-section-heading"><span class="admin-form-section-title">Blog Presentation</span><span class="admin-form-section-description">Top banner, imagery, and shared colours</span></span><span class="badge text-bg-light admin-form-section-status">Optional</span></button></h3>
                        <div id="blog-presentation-panel" class="accordion-collapse collapse">
                            <div class="accordion-body">
                                <div class="form-check banner-check mb-4"><input type="hidden" name="show_top_banner" value="0"><input type="checkbox" name="show_top_banner" id="show_top_banner" value="1" class="form-check-input" <?php echo (int)$form_values['show_top_banner'] === 1 ? ' checked' : ''; ?>><label class="form-check-label fw-semibold" for="show_top_banner">Show banner</label></div>
                                <div class="row">
                                    <div class="col-lg-6"><?php $textField('localized_banner_title', 'Eyebrow / Title'); ?></div>
                                    <div class="col-lg-6"><?php $textField('localized_banner_heading', 'Main Heading'); ?></div>
                                </div><?php $textField('localized_banner_text', 'Supporting Text', 'textarea');
                                        $this->load->view('admin/partials/file_upload', array(
                                            'name' => 'banner_background_upload',
                                            'id' => 'banner_background_upload',
                                            'label' => $locale['label'] . ' Banner Background (Optional)',
                                            'allowed_types' => UPLOAD_IMAGE_MIMES,
                                            'required' => FALSE,
                                            'current_path' => $banner !== '' ? 'assets/frontend/images/blogs/' . $banner : '',
                                            'help' => 'Maximum ' . UPLOAD_SIZE_MB . ' MB.',
                                            'recommended_size' => '1920 × 640px (3:1)',
                                            'size_note' => 'Used for the post header banner instead of the cover image. It is cropped from the centre to 3:1.',
                                            'preview_alt' => 'Current banner',
                                            'preview_size' => 220,
                                        ));
                                        if ($isEdit && image_thumb_path('assets/frontend/images/blogs/' . $banner) !== FALSE) { ?><div class="admin-current-file-remove">
                                        <div class="admin-current-file-actions"><button class="btn btn-outline-danger btn-sm mt-3 removeFile" data-controller="blogs" data-file-name="banner_background<?php echo $suffix; ?>" data-file-id="<?php echo $id; ?>" type="button"><i class="bi bi-trash"></i> Remove Image</button></div>
                                    </div><?php } ?><div class="admin-form-subsection mb-0 mt-4">
                                    <div class="admin-form-subsection-heading">
                                        <div>
                                            <h4>Banner Colours</h4>
                                            <p>Select one colour for a solid appearance, or select two colours to create a gradient. These colour settings are shared across both the English and Arabic versions of the blog.</p>
                                        </div>
                                    </div>
                                    <div class="admin-field mb-4"><label class="form-label" for="banner_overlay">Image Overlay</label><select class="form-select select2" name="banner_overlay" id="banner_overlay">
                                            <option value="Yes" <?php echo $form_values['banner_overlay'] === 'Yes' ? ' selected' : ''; ?>>Yes</option>
                                            <option value="No" <?php echo $form_values['banner_overlay'] === 'No' ? ' selected' : ''; ?>>No</option>
                                        </select></div><?php foreach (array('Background' => 'banner_background', 'Eyebrow / Title' => 'banner_title', 'Main Heading' => 'banner_heading', 'Supporting Text' => 'banner_text') as $label => $prefix) { ?><div class="row align-items-end">
                                            <div class="col-lg-3">
                                                <p class="admin-form-colour-label"><?php echo $label; ?></p>
                                            </div><?php foreach (array(1, 2) as $n) { ?><div class="admin-field mb-3 col-md-6 col-lg-4"><label class="visually-hidden" for="<?php echo $prefix . '_color_' . $n; ?>"><?php echo $label . ' colour ' . $n; ?></label><input type="text" name="<?php echo $prefix . '_color_' . $n; ?>" id="<?php echo $prefix . '_color_' . $n; ?>" value="<?php echo $v($prefix . '_color_' . $n); ?>" class="form-control color-picker" placeholder="Colour <?php echo $n; ?>" maxlength="50" dir="ltr"></div><?php } ?>
                                        </div><?php } ?>
                                </div>
                            </div>
                        </div>
                    </article>
                    <article class="accordion-item admin-form-accordion-item">
                        <h3 class="accordion-header"><button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#blog-search-panel" aria-expanded="false"><span class="admin-form-section-icon"><i class="bi bi-search"></i></span><span class="admin-form-section-heading"><span class="admin-form-section-title">Search Visibility</span><span class="admin-form-section-description">Search result text and crawler controls</span></span><span class="badge text-bg-light admin-form-section-status">Optional</span></button></h3>
                        <div id="blog-search-panel" class="accordion-collapse collapse">
                            <div class="accordion-body"><?php $textField('localized_page_title', 'Page Title', 'input', 'Leave blank to use the blog title followed by the site name.');
                                                        $textField('localized_meta_description', 'Meta Description', 'textarea', 'Leave blank to use the short description. About 150-160 characters displays best in search results.');
                                                        $textField('localized_meta_keywords', 'Meta Keywords', 'textarea'); ?><fieldset class="admin-form-choice-group">
                                    <legend>Search Engine Access</legend>
                                    <div class="row">
                                        <div class="col-md-6">
                                            <div class="form-check"><input type="hidden" name="robots_index" value="0"><input type="checkbox" name="robots_index" id="robots_index" value="1" class="form-check-input" <?php echo (int)$form_values['robots_index'] === 1 ? ' checked' : ''; ?>><label class="form-check-label" for="robots_index">Allow indexing</label></div>
                                        </div>
                                        <div class="col-md-6">
                                            <div class="form-check"><input type="hidden" name="robots_follow" value="0"><input type="checkbox" name="robots_follow" id="robots_follow" value="1" class="form-check-input" <?php echo (int)$form_values['robots_follow'] === 1 ? ' checked' : ''; ?>><label class="form-check-label" for="robots_follow">Allow link following</label></div>
                                        </div>
                                    </div>
                                </fieldset>
                            </div>
                        </div>
                    </article>
                    <article class="accordion-item admin-form-accordion-item">
                        <h3 class="accordion-header"><button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#blog-sharing-panel" aria-expanded="false"><span class="admin-form-section-icon"><i class="bi bi-share"></i></span><span class="admin-form-section-heading"><span class="admin-form-section-title">Social Sharing</span><span class="admin-form-section-description">Preview title, description, and image</span></span><span class="badge text-bg-light admin-form-section-status">Optional</span></button></h3>
                        <div id="blog-sharing-panel" class="accordion-collapse collapse">
                            <div class="accordion-body"><?php $textField('localized_og_title', 'Sharing Title', 'input', 'Leave blank to use the page title.');
                                                        $textField('localized_og_description', 'Sharing Description', 'textarea', 'Leave blank to use the meta description.');
                                                        $this->load->view('admin/partials/file_upload', array(
                                                            'name' => 'og_image_upload',
                                                            'id' => 'og_image_upload',
                                                            'label' => $locale['label'] . ' Sharing Image (Optional)',
                                                            'allowed_types' => UPLOAD_IMAGE_MIMES,
                                                            'required' => FALSE,
                                                            'current_path' => $og !== '' ? 'assets/frontend/images/blogs/' . $og : '',
                                                            'help' => 'Maximum ' . UPLOAD_SIZE_MB . ' MB.',
                                                            'recommended_size' => '1200 × 630px',
                                                            'size_note' => 'Used for link previews on social media and messaging apps. Leave empty to use the thumbnail, then the cover image.',
                                                            'preview_alt' => 'Current sharing image',
                                                            'preview_size' => 220,
                                                        ));
                                                        if ($isEdit && image_thumb_path('assets/frontend/images/blogs/' . $og) !== FALSE) { ?><div class="admin-current-file-remove">
                                        <div class="admin-current-file-actions"><button class="btn btn-outline-danger btn-sm mt-3 removeFile" data-controller="blogs" data-file-name="og_image<?php echo $suffix; ?>" data-file-id="<?php echo $id; ?>" type="button"><i class="bi bi-trash"></i> Remove Image</button></div>
                                    </div><?php } ?></div>
                        </div>
                    </article>
                </div>
                <div class="admin-form-actions"><a class="btn btn-outline-secondary" href="<?php echo base_url('manage/blogs'); ?>">Cancel</a><button type="submit" class="btn btn-primary">Save <?php echo htmlspecialchars($this->moduleNameSingular, ENT_QUOTES, 'UTF-8'); ?></button></div>
            </form>
        </div>
    </div>
</section>
<?php if ($isEdit) $this->load->view('admin/partials/manage_language_switch_modal'); ?>
<script>
    jQuery(function($) {
        var form = document.getElementById('blogs_form');
        if (!form) return;

        function firstMissingRequiredField() {
            return Array.prototype.slice.call(form.querySelectorAll('[required]')).find(function(field) {
                if (field.disabled) return false;
                if (field.type === 'checkbox' || field.type === 'radio') return !field.checked;
                return !String(field.value || '').trim();
            });
        }

        function revealRequiredField(field) {
            var panel = field.closest('.accordion-collapse');
            var focusAndValidate = function() {
                if ($.fn.validate) $(field).valid();
                field.focus();
            };
            if (panel && window.bootstrap && bootstrap.Collapse && !panel.classList.contains('show')) {
                panel.addEventListener('shown.bs.collapse', focusAndValidate, {
                    once: true
                });
                bootstrap.Collapse.getOrCreateInstance(panel, {
                    toggle: false
                }).show();
            } else {
                focusAndValidate();
            }
        }
        form.addEventListener('submit', function(event) {
            var missing = firstMissingRequiredField();
            if (!missing) return;
            event.preventDefault();
            event.stopImmediatePropagation();
            revealRequiredField(missing);
        }, true);
        form.addEventListener('invalid', function(event) {
            event.preventDefault();
            revealRequiredField(event.target);
        }, true);
    });
</script>
