<?php
$isEdit = !empty($tbl_data[$this->pKey]);
$recordID = $isEdit ? (int)$tbl_data[$this->pKey] : 0;
$verb = $isEdit ? 'Edit' : 'Add';
$action = base_url('manage/blog-categories/' . ($isEdit ? 'editRecord/' . $recordID : 'addRecord'));
$lv = function ($key) use ($text_values) {
    return htmlspecialchars(isset($text_values[$key]) ? (string)$text_values[$key] : '', ENT_QUOTES, 'UTF-8');
};
$v = function ($key) use ($form_values) {
    return htmlspecialchars(isset($form_values[$key]) ? (string)$form_values[$key] : '', ENT_QUOTES, 'UTF-8');
};
$cover = basename((string)$form_values['current_cat_cover_image']);
$og = basename((string)$form_values['current_og_image']);
$banner = basename((string)$form_values['current_banner_background']);
$field = function ($name, $label, $type = 'input', $help = '') use ($lv) { ?><div class="admin-field mb-3"><label class="form-label" for="<?php echo $name; ?>"><?php echo $label; ?></label><?php if ($type === 'textarea') { ?><textarea name="<?php echo $name; ?>" id="<?php echo $name; ?>" rows="4" class="form-control"><?php echo $lv($name); ?></textarea><?php } else { ?><input type="text" name="<?php echo $name; ?>" id="<?php echo $name; ?>" maxlength="255" value="<?php echo $lv($name); ?>" class="form-control" data-validate="maxlength[255]"><?php }
                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                    if ($help !== '') { ?><div class="form-text"><?php echo $help; ?></div><?php } ?></div><?php };
                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                ?>
<?php $this->load->view('admin/partials/breadcrumb', array('items' => array(array('label' => 'Blogs', 'url' => base_url('manage/blogs')), array('label' => $this->moduleName, 'url' => base_url('manage/blog-categories')), array('label' => $verb . ' ' . $this->moduleNameSingular, 'active' => TRUE)))); ?>
<?php if ($form_error) { ?><div class="row alertrow">
        <div class="col-md-12">
            <div class="alert alert-danger alert-dismissible fade show" role="alert"><strong>Could not save the blog category.</strong> <?php echo htmlspecialchars($form_error, ENT_QUOTES, 'UTF-8'); ?><button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Dismiss notification"></button></div>
        </div>
    </div><?php } ?>
<section class="admin-records-listing admin-form-screen" aria-labelledby="category-form-title">
    <?php $this->load->view('admin/partials/module_header', array('title' => $this->moduleName, 'description' => 'Create the category content first, then configure presentation, search visibility, and sharing.', 'id' => 'category-form-title')); ?>
    <div class="card admin-card">
        <div class="card-header d-flex flex-wrap align-items-center justify-content-between gap-3">
            <div>
                <h2 class="card-title mb-1"><?php echo $verb; ?> <?php echo htmlspecialchars($this->moduleNameSingular, ENT_QUOTES, 'UTF-8'); ?></h2>
            </div>
        </div>
        <div class="card-body">
            <form id="blog_categories_form" method="post" action="<?php echo $action; ?>" enctype="multipart/form-data" class="validate" novalidate data-submit-lock><?php if ($this->config->item('csrf_protection')) { ?><input type="hidden" name="<?php echo htmlspecialchars($this->security->get_csrf_token_name(), ENT_QUOTES, 'UTF-8'); ?>" value="<?php echo htmlspecialchars($this->security->get_csrf_hash(), ENT_QUOTES, 'UTF-8'); ?>"><?php } ?>
                <div class="accordion admin-form-accordion" id="categoryEditorAccordion">
                    <article class="accordion-item admin-form-accordion-item">
                        <h3 class="accordion-header" id="category-content-heading"><button class="accordion-button" type="button" data-bs-toggle="collapse" data-bs-target="#category-content-panel" aria-expanded="true"><span class="admin-form-section-icon"><i class="bi bi-folder2-open"></i></span><span class="admin-form-section-heading"><span class="admin-form-section-title">Category Content</span><span class="admin-form-section-description">Name, URL, description, and main contents</span></span><span class="badge text-bg-danger admin-form-section-status">Required</span></button></h3>
                        <div id="category-content-panel" class="accordion-collapse collapse show">
                            <div class="accordion-body">
                                <div class="admin-field mb-3"><label class="form-label is-required" for="cat_name">Name</label><input type="text" name="cat_name" id="cat_name" maxlength="255" value="<?php echo $lv('cat_name'); ?>" class="form-control<?php echo $isEdit ? '' : ' auto-slug-source'; ?>" data-validate="required,maxlength[255]"<?php if (!$isEdit) { ?> data-slug-target="#page_slug"<?php } ?> required></div>
                                <div class="admin-field mb-4"><label class="form-label is-required" for="page_slug">Slug</label>
                                    <div class="input-group" dir="ltr"><span class="input-group-text"><?php echo '/'.BLOG_CATEGORY_URI; ?></span><input type="text" name="page_slug" id="page_slug" maxlength="255" value="<?php echo $v('page_slug'); ?>" class="form-control slug" data-validate="required,maxlength[255]" required><button type="button" class="btn btn-outline-secondary generate-slug" data-slug-source="#cat_name" data-slug-target="#page_slug"><i class="bi bi-arrow-clockwise"></i> <span class="d-none d-sm-inline">Generate</span></button></div>
                                    <div class="form-text">Made unique automatically when saved.</div>
                                </div><?php $field('cat_desc', 'Short Description', 'textarea'); ?> <div class="admin-field mb-4"><label class="form-label" for="cat_contents">Contents</label><?php echo $this->ckeditor->editor('cat_contents', isset($text_values['cat_contents']) ? (string)$text_values['cat_contents'] : ''); ?></div>
                                    <div class="admin-field mb-4">
                                        <?php $this->load->view('admin/partials/file_upload', array(
                                            'name' => 'cat_cover_image_upload',
                                            'id' => 'cat_cover_image_upload',
                                            'label' => 'Cover Image (Optional)',
                                            'allowed_types' => UPLOAD_IMAGE_MIMES,
                                            'required' => FALSE,
                                            'current_path' => $cover !== '' ? 'assets/frontend/images/blog-categories/' . $cover : '',
                                            'help' => 'Main hero image displayed on the individual blog category page. Shared by both languages. Maximum ' . UPLOAD_SIZE_MB . ' MB.',
                                            'recommended_size' => '1920 × 720px (8:3)',
                                            'size_note' => 'Wide hero behind the category title. It is cropped from the centre to 8:3, so keep the subject in the middle.',
                                            'preview_alt' => 'Current category cover image',
                                            'preview_size' => 220,
                                        )); ?>
                                        <?php if ($isEdit && image_thumb_path('assets/frontend/images/blog-categories/' . $cover) !== FALSE) { ?><div class="admin-current-file-remove"><div class="admin-current-file-actions"><button class="btn btn-outline-danger btn-sm mt-3 removeFile" data-controller="blog-categories" data-file-name="cat_cover_image" data-file-id="<?php echo $recordID; ?>" type="button"><i class="bi bi-trash" aria-hidden="true"></i> Remove Image</button></div></div><?php } ?>
                                    </div>
                                <div class="admin-publishing-panel">
                                    <div>
                                        <h4>Category Status</h4>
                                        <p>Enabled categories can be used on the website. Disable a category while it is unavailable.</p>
                                    </div>
                                    <div class="admin-field mb-0"><label class="form-label" for="cat_status">Status</label><select class="form-select select2" name="cat_status" id="cat_status" data-minimum-results-for-search="-1">
                                            <option value="Enable" <?php echo $form_values['cat_status'] === 'Enable' ? ' selected' : ''; ?>>Enabled</option>
                                            <option value="Disable" <?php echo $form_values['cat_status'] === 'Disable' ? ' selected' : ''; ?>>Disabled</option>
                                        </select></div>
                                </div>
                            </div>
                        </div>
                    </article>
                    <article class="accordion-item admin-form-accordion-item">
                        <h3 class="accordion-header" id="category-presentation-heading"><button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#category-presentation-panel" aria-expanded="false"><span class="admin-form-section-icon"><i class="bi bi-image"></i></span><span class="admin-form-section-heading"><span class="admin-form-section-title">Category Presentation</span><span class="admin-form-section-description">Top banner, imagery, and shared colours</span></span><span class="badge text-bg-light admin-form-section-status">Optional</span></button></h3>
                        <div id="category-presentation-panel" class="accordion-collapse collapse">
                            <div class="accordion-body">
                                <div class="form-check banner-check mb-4"><input type="hidden" name="show_top_banner" value="0"><input type="checkbox" name="show_top_banner" id="show_top_banner" value="1" class="form-check-input" <?php echo (int)$form_values['show_top_banner'] === 1 ? ' checked' : ''; ?>><label class="form-check-label fw-semibold" for="show_top_banner">Show banner</label>
                                    <div class="form-text">Display the top banner above this category's content.</div>
                                </div>
                                <div class="row">
                                    <div class="col-lg-6"><?php $field('banner_title', 'Eyebrow / Title'); ?></div>
                                    <div class="col-lg-6"><?php $field('banner_heading', 'Main Heading'); ?></div>
                                </div><?php $field('banner_text', 'Supporting Text', 'textarea'); ?><?php $this->load->view('admin/partials/file_upload', array('name' => 'banner_background_upload', 'id' => 'banner_background_upload', 'label' => 'Banner Background (Optional)', 'allowed_types' => UPLOAD_IMAGE_MIMES, 'required' => FALSE, 'current_path' => $banner !== '' ? 'assets/frontend/images/blog-categories/' . $banner : '', 'help' => 'Maximum ' . UPLOAD_SIZE_MB . ' MB.', 'recommended_size' => '1920 × 720px (8:3)', 'size_note' => 'Used for the category header banner instead of the cover image. It is cropped from the centre to 8:3.', 'preview_alt' => 'Current banner background', 'preview_size' => 220)); ?><?php if ($isEdit && image_thumb_path('assets/frontend/images/blog-categories/' . $banner) !== FALSE) { ?><div class="admin-current-file-remove">
                                    <div class="admin-current-file-actions"><button class="btn btn-outline-danger btn-sm mt-3 removeFile" data-controller="blog-categories" data-file-name="banner_background" data-file-id="<?php echo $recordID; ?>" type="button"><i class="bi bi-trash"></i> Remove Image</button></div>
                                </div><?php } ?><div class="admin-form-subsection mb-0 mt-4">
                                <div class="admin-form-subsection-heading">
                                    <div>
                                        <h4>Banner Colours</h4>
                                        <p>Select one colour for a solid appearance, or select two colours to create a gradient.</p>
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
                        <h3 class="accordion-header" id="category-search-heading"><button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#category-search-panel" aria-expanded="false"><span class="admin-form-section-icon"><i class="bi bi-search"></i></span><span class="admin-form-section-heading"><span class="admin-form-section-title">Search Visibility</span><span class="admin-form-section-description">Search result text and crawler controls</span></span><span class="badge text-bg-light admin-form-section-status">Optional</span></button></h3>
                        <div id="category-search-panel" class="accordion-collapse collapse">
                            <div class="accordion-body"><?php $field('page_title', 'Page Title', 'input', 'Leave blank to use the category name followed by the site name.');
                                                        $field('meta_description', 'Meta Description', 'textarea', 'Leave blank to use the category description, then its latest posts. About 150-160 characters displays best in search results.');
                                                        $field('meta_keywords', 'Meta Keywords', 'textarea'); ?><fieldset class="admin-form-choice-group">
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
                        <h3 class="accordion-header" id="category-sharing-heading"><button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#category-sharing-panel" aria-expanded="false"><span class="admin-form-section-icon"><i class="bi bi-share"></i></span><span class="admin-form-section-heading"><span class="admin-form-section-title">Social Sharing</span><span class="admin-form-section-description">Preview title, description, and image</span></span><span class="badge text-bg-light admin-form-section-status">Optional</span></button></h3>
                        <div id="category-sharing-panel" class="accordion-collapse collapse">
                            <div class="accordion-body"><?php $field('og_title', 'Sharing Title', 'input', 'Leave blank to use the page title.');
                                                        $field('og_description', 'Sharing Description', 'textarea', 'Leave blank to use the meta description.'); ?><?php $this->load->view('admin/partials/file_upload', array('name' => 'og_image_upload', 'id' => 'og_image_upload', 'label' => 'Sharing Image (Optional)', 'allowed_types' => UPLOAD_IMAGE_MIMES, 'required' => FALSE, 'current_path' => $og !== '' ? 'assets/frontend/images/blog-categories/' . $og : '', 'help' => 'Maximum ' . UPLOAD_SIZE_MB . ' MB.', 'recommended_size' => '1200 × 630px', 'size_note' => 'Used for link previews on social media and messaging apps. Leave empty to use the cover image.', 'preview_alt' => 'Current sharing image', 'preview_size' => 220)); ?><?php if ($isEdit && image_thumb_path('assets/frontend/images/blog-categories/' . $og) !== FALSE) { ?><div class="admin-current-file-remove">
                                    <div class="admin-current-file-actions"><button class="btn btn-outline-danger btn-sm mt-3 removeFile" data-controller="blog-categories" data-file-name="og_image" data-file-id="<?php echo $recordID; ?>" type="button"><i class="bi bi-trash"></i> Remove Image</button></div>
                                </div><?php } ?></div>
                        </div>
                    </article>
                </div>
                <div class="admin-form-actions"><a class="btn btn-outline-secondary" href="<?php echo base_url('manage/blog-categories'); ?>">Cancel</a><button type="submit" class="btn btn-primary">Save <?php echo htmlspecialchars($this->moduleNameSingular, ENT_QUOTES, 'UTF-8'); ?></button></div>
            </form>
        </div>
    </div>
</section>
<script>
    jQuery(function($) {
        var form = document.getElementById('blog_categories_form');
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
                panel.addEventListener('shown.bs.collapse', focusAndValidate, { once: true });
                bootstrap.Collapse.getOrCreateInstance(panel, { toggle: false }).show();
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
