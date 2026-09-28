<?php
$isEdit = ($alert === 'edit' && !empty($tbl_data[$this->pKey]));
$recordId = $isEdit ? (int) $tbl_data[$this->pKey] : 0;
$crumb = $isEdit ? 'Edit' : 'Add';
$action = base_url(
    'manage/'.$this->controller.'/'.($isEdit ? 'editRecord/'.$recordId : 'addRecord')
);
$value = function ($key) use ($tbl_data) {
    return isset($tbl_data[$key])
        ? htmlspecialchars((string) $tbl_data[$key], ENT_QUOTES, 'UTF-8')
        : '';
};
$invalidClass = function ($key) use ($invalid_fields) {
    return in_array($key, $invalid_fields, TRUE) ? ' is-invalid' : '';
};
$checked = function ($key) use ($tbl_data) {
    return isset($tbl_data[$key]) && (string) $tbl_data[$key] === '1' ? 'checked' : '';
};
$imagePath = function ($column) use ($tbl_data, $image_directory) {
    $filename = isset($tbl_data[$column]) ? basename((string) $tbl_data[$column]) : '';

    return $filename !== '' ? $image_directory.'/'.$filename : '';
};
$hasImage = function ($column) use ($imagePath) {
    return image_thumb_path($imagePath($column)) !== FALSE;
};

// Accordion sections and the fields they contain. A section opens when it is
// the first one or when the server reported an invalid field inside it.
$sections = array(
    'details' => array('service_category_id', 'service_name', 'service_slug', 'service_summary', 'service_price_from', 'service_price_suffix', 'service_duration_label', 'service_duration_minutes'),
    'content' => array('service_description', 'service_included', 'service_before_visit', 'service_aftercare', 'related_services'),
    'addons' => array('addons'),
    'images' => array('card_image_upload', 'hero_image_upload'),
    'search' => array('page_title', 'meta_description'),
    'sharing' => array('og_title', 'og_description', 'og_image_upload'),
);
$sectionHasError = function ($section) use ($sections, $invalid_fields) {
    return count(array_intersect($sections[$section], $invalid_fields)) > 0;
};
$sectionOpen = function ($section) use ($sectionHasError) {
    return $section === 'details' || $sectionHasError($section);
};
$sectionHeader = function ($section, $icon, $title, $description, $required) use ($sectionOpen, $sectionHasError) {
    $open = $sectionOpen($section);
    $badge = $required
        ? '<span class="badge text-bg-danger admin-form-section-status">Required</span>'
        : '<span class="badge text-bg-light admin-form-section-status">Optional</span>';

    if ($sectionHasError($section)) {
        $badge = '<span class="badge text-bg-danger admin-form-section-status">Needs attention</span>';
    }

    echo '<h3 class="accordion-header">'
        .'<button class="accordion-button'.($open ? '' : ' collapsed').'" type="button" data-bs-toggle="collapse"'
        .' data-bs-target="#service-'.$section.'-panel" aria-expanded="'.($open ? 'true' : 'false').'"'
        .' aria-controls="service-'.$section.'-panel">'
        .'<span class="admin-form-section-icon"><i class="bi '.$icon.'" aria-hidden="true"></i></span>'
        .'<span class="admin-form-section-heading">'
        .'<span class="admin-form-section-title">'.$title.'</span>'
        .'<span class="admin-form-section-description">'.$description.'</span>'
        .'</span>'
        .$badge
        .'</button>'
        .'</h3>';
};
$panelClass = function ($section) use ($sectionOpen) {
    return 'accordion-collapse collapse'.($sectionOpen($section) ? ' show' : '');
};
$currentStatus = isset($tbl_data[$this->tStatus]) ? $tbl_data[$this->tStatus] : 'Enable';
$selectedCategory = isset($tbl_data['service_category_id']) ? (int) $tbl_data['service_category_id'] : 0;

// Other services grouped by category for the "Often booked with this" select.
$relatedGroups = array();

foreach ($service_options as $option) {
    if ((int) $option['service_id'] === $recordId) {
        continue;
    }

    $group = $option['category_name'] !== NULL ? $option['category_name'] : 'Uncategorised';
    $relatedGroups[$group][] = $option;
}
$addonRow = function ($label, $price) {
    ?>
    <div class="admin-repeatable-row" data-repeatable-row>
        <input type="text" name="addon_label[]" maxlength="150" value="<?php echo htmlspecialchars((string) $label, ENT_QUOTES, 'UTF-8'); ?>" class="form-control" placeholder="Gel colour instead of regular polish" data-repeatable-aria="label">
        <div class="input-group">
            <input type="text" name="addon_price[]" maxlength="12" inputmode="decimal" value="<?php echo htmlspecialchars((string) $price, ENT_QUOTES, 'UTF-8'); ?>" class="form-control" placeholder="40" data-repeatable-aria="price in złoty">
            <span class="input-group-text">zł</span>
        </div>
        <button type="button" class="btn btn-outline-danger" data-repeatable-remove>
            <i class="bi bi-trash" aria-hidden="true"></i>
            <span class="visually-hidden">Remove</span>
        </button>
    </div>
    <?php
};
?>
<?php $this->load->view('admin/partials/breadcrumb', array(
    'items' => array(
        array('label' => $this->moduleName, 'url' => ADMIN_URL.$this->controller),
        array('label' => $crumb.' '.$this->moduleNameSingular, 'active' => TRUE),
    ),
)); ?>

<?php if (!empty($form_error)) { ?>
    <div class="row alertrow">
        <div class="col-md-12">
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <strong>Could not save the service.</strong>
                <?php echo htmlspecialchars($form_error, ENT_QUOTES, 'UTF-8'); ?>
                <button type="button" class="btn-close alertBox crud-alert-close" data-bs-dismiss="alert" aria-label="Dismiss notification"></button>
            </div>
        </div>
    </div>
<?php } ?>

<section class="admin-records-listing" aria-labelledby="services-title">
    <?php $this->load->view('admin/partials/module_header', array(
        'title' => $this->moduleName,
        'description' => $this->moduleDesc,
        'id' => 'services-title',
    )); ?>

    <div class="card admin-card">
        <div class="card-header">
            <h2 class="card-title mb-0"><?php echo $crumb; ?> <?php echo htmlspecialchars($this->moduleNameSingular, ENT_QUOTES, 'UTF-8'); ?></h2>
        </div>
        <div class="card-body">
            <form
                id="<?php echo $this->controller; ?>_form"
                name="<?php echo $this->controller; ?>_form"
                method="post"
                action="<?php echo $action; ?>"
                enctype="multipart/form-data"
                class="validate"
                novalidate
                data-submit-lock
                data-accordion-validation
            >
                <?php if ($this->config->item('csrf_protection')) { ?>
                    <input type="hidden" name="<?php echo htmlspecialchars($this->security->get_csrf_token_name(), ENT_QUOTES, 'UTF-8'); ?>" value="<?php echo htmlspecialchars($this->security->get_csrf_hash(), ENT_QUOTES, 'UTF-8'); ?>">
                <?php } ?>

                <div class="accordion admin-form-accordion" id="serviceEditorAccordion">

                    <article class="accordion-item admin-form-accordion-item">
                        <?php $sectionHeader('details', 'bi-stars', 'Service Details', 'Category, name, URL, price and duration', TRUE); ?>
                        <div id="service-details-panel" class="<?php echo $panelClass('details'); ?>">
                            <div class="accordion-body">
                                <div class="row">
                                    <div class="admin-field mb-3 col-md-6">
                                        <label class="form-label is-required" for="service_category_id">Category</label>
                                        <select
                                            class="form-select select2<?php echo $invalidClass('service_category_id'); ?>"
                                            name="service_category_id"
                                            id="service_category_id"
                                            data-minimum-results-for-search="-1"
                                            data-validate="required"
                                            required
                                        >
                                            <option value="">Select a category</option>
                                            <?php foreach ($categories as $categoryId => $category) { ?>
                                                <option value="<?php echo (int) $categoryId; ?>" <?php echo $selectedCategory === (int) $categoryId ? 'selected' : ''; ?>>
                                                    <?php echo htmlspecialchars($category['category_name'], ENT_QUOTES, 'UTF-8'); ?><?php echo $category['category_status'] === 'Disable' ? ' (disabled)' : ''; ?>
                                                </option>
                                            <?php } ?>
                                        </select>
                                    </div>
                                    <div class="admin-field mb-3 col-md-6">
                                        <label class="form-label is-required" for="service_name">Name</label>
                                        <input
                                            type="text"
                                            name="service_name"
                                            id="service_name"
                                            maxlength="150"
                                            value="<?php echo $value('service_name'); ?>"
                                            class="form-control<?php echo $isEdit ? '' : ' auto-slug-source'; ?><?php echo $invalidClass('service_name'); ?>"
                                            data-validate="required,maxlength[150]"
                                            <?php if (!$isEdit) { ?>data-slug-target="#service_slug"<?php } ?>
                                            placeholder="Gel Manicure"
                                            required
                                        >
                                    </div>
                                </div>

                                <div class="admin-field mb-3">
                                    <label class="form-label is-required" for="service_slug">URL Slug</label>
                                    <div class="input-group">
                                        <span class="input-group-text">/services/</span>
                                        <input
                                            type="text"
                                            name="service_slug"
                                            id="service_slug"
                                            maxlength="160"
                                            value="<?php echo $value('service_slug'); ?>"
                                            class="form-control slug<?php echo $invalidClass('service_slug'); ?>"
                                            data-validate="required,maxlength[160]"
                                            required
                                        >
                                        <button type="button" class="btn btn-outline-secondary generate-slug" data-slug-source="#service_name" data-slug-target="#service_slug">
                                            <i class="bi bi-arrow-clockwise" aria-hidden="true"></i>
                                            <span class="d-none d-sm-inline">Generate</span>
                                        </button>
                                    </div>
                                    <div class="form-text">Made unique automatically when saved.</div>
                                </div>

                                <div class="admin-field mb-3">
                                    <label class="form-label" for="service_summary">Summary</label>
                                    <textarea
                                        rows="3"
                                        name="service_summary"
                                        id="service_summary"
                                        maxlength="500"
                                        class="form-control<?php echo $invalidClass('service_summary'); ?>"
                                        placeholder="A full manicure finished in gel colour — long-wearing and glossy."
                                    ><?php echo $value('service_summary'); ?></textarea>
                                    <div class="form-text">Shown on service cards and the booking form.</div>
                                </div>

                                <div class="row">
                                    <div class="admin-field mb-3 col-md-6 col-lg-3">
                                        <label class="form-label" for="service_price_from">Price From</label>
                                        <div class="input-group">
                                            <input
                                                type="text"
                                                name="service_price_from"
                                                id="service_price_from"
                                                maxlength="12"
                                                inputmode="decimal"
                                                value="<?php echo $value('service_price_from'); ?>"
                                                class="form-control<?php echo $invalidClass('service_price_from'); ?>"
                                                placeholder="120"
                                            >
                                            <span class="input-group-text">zł</span>
                                        </div>
                                    </div>
                                    <div class="admin-field mb-3 col-md-6 col-lg-3">
                                        <label class="form-label" for="service_price_suffix">Price Suffix</label>
                                        <input
                                            type="text"
                                            name="service_price_suffix"
                                            id="service_price_suffix"
                                            maxlength="40"
                                            value="<?php echo $value('service_price_suffix'); ?>"
                                            class="form-control"
                                            placeholder="/ nail"
                                        >
                                    </div>
                                    <div class="admin-field mb-3 col-md-6 col-lg-3">
                                        <label class="form-label" for="service_duration_label">Duration Shown</label>
                                        <input
                                            type="text"
                                            name="service_duration_label"
                                            id="service_duration_label"
                                            maxlength="60"
                                            value="<?php echo $value('service_duration_label'); ?>"
                                            class="form-control"
                                            placeholder="90–120 min"
                                        >
                                    </div>
                                    <div class="admin-field mb-3 col-md-6 col-lg-3">
                                        <label class="form-label" for="service_duration_minutes">Booking Duration</label>
                                        <div class="input-group">
                                            <input
                                                type="number"
                                                name="service_duration_minutes"
                                                id="service_duration_minutes"
                                                min="1"
                                                max="720"
                                                step="1"
                                                value="<?php echo $value('service_duration_minutes'); ?>"
                                                class="form-control<?php echo $invalidClass('service_duration_minutes'); ?>"
                                                placeholder="75"
                                            >
                                            <span class="input-group-text">min</span>
                                        </div>
                                    </div>
                                </div>
                                <p class="form-text mt-n2 mb-3">
                                    Leave the price empty to hide it. "Duration Shown" is the text visitors see; "Booking Duration" is used to plan appointment times.
                                </p>

                                <div class="row">
                                    <div class="admin-field mb-3 col-md-6">
                                        <label class="form-label" for="service_status">Status</label>
                                        <select class="form-select select2" name="service_status" id="service_status" data-minimum-results-for-search="-1">
                                            <option value="Enable" <?php echo $currentStatus !== 'Disable' ? 'selected' : ''; ?>>Enable</option>
                                            <option value="Disable" <?php echo $currentStatus === 'Disable' ? 'selected' : ''; ?>>Disable</option>
                                        </select>
                                    </div>
                                    <div class="admin-field mb-3 col-md-6 d-flex align-items-end">
                                        <div class="form-check">
                                            <input type="hidden" name="service_featured" value="0">
                                            <input type="checkbox" name="service_featured" id="service_featured" value="1" class="form-check-input" <?php echo $checked('service_featured'); ?>>
                                            <label class="form-check-label" for="service_featured">Feature on the home page</label>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </article>

                    <article class="accordion-item admin-form-accordion-item">
                        <?php $sectionHeader('content', 'bi-card-text', 'Description & Details', 'Full description, what is included, before and after the visit', FALSE); ?>
                        <div id="service-content-panel" class="<?php echo $panelClass('content'); ?>">
                            <div class="accordion-body">
                                <div class="admin-field mb-4">
                                    <label class="form-label" for="service_description">Description</label>
                                    <?php echo $description_editor; ?>
                                </div>
                                <div class="admin-field mb-3">
                                    <label class="form-label" for="service_included">What's Included</label>
                                    <textarea rows="5" name="service_included" id="service_included" class="form-control" placeholder="Cuticle care and nail shaping"><?php echo $value('service_included'); ?></textarea>
                                    <div class="form-text">One item per line.</div>
                                </div>
                                <div class="row">
                                    <div class="admin-field mb-3 col-md-6">
                                        <label class="form-label" for="service_before_visit">Before Your Visit</label>
                                        <textarea rows="4" name="service_before_visit" id="service_before_visit" class="form-control" placeholder="Come with clean, product-free nails if you can"><?php echo $value('service_before_visit'); ?></textarea>
                                        <div class="form-text">One tip per line.</div>
                                    </div>
                                    <div class="admin-field mb-3 col-md-6">
                                        <label class="form-label" for="service_aftercare">Looking After It</label>
                                        <textarea rows="4" name="service_aftercare" id="service_aftercare" class="form-control" placeholder="Cuticle oil daily keeps the finish fresh"><?php echo $value('service_aftercare'); ?></textarea>
                                        <div class="form-text">One tip per line.</div>
                                    </div>
                                </div>
                                <div class="row">
                                    <div class="admin-field mb-3 col-md-6">
                                        <label class="form-label" for="related_services">Often Booked With This</label>
                                        <select
                                            class="form-select select2<?php echo $invalidClass('related_services'); ?>"
                                            name="related_services[]"
                                            id="related_services"
                                            multiple
                                            data-placeholder="Select services"
                                        >
                                            <?php foreach ($relatedGroups as $groupName => $groupServices) { ?>
                                                <optgroup label="<?php echo htmlspecialchars($groupName, ENT_QUOTES, 'UTF-8'); ?>">
                                                    <?php foreach ($groupServices as $option) { ?>
                                                        <option value="<?php echo (int) $option['service_id']; ?>" <?php echo in_array((int) $option['service_id'], $related_services, TRUE) ? 'selected' : ''; ?>>
                                                            <?php echo htmlspecialchars($option['service_name'], ENT_QUOTES, 'UTF-8'); ?><?php echo $option['service_status'] === 'Disable' ? ' (disabled)' : ''; ?>
                                                        </option>
                                                    <?php } ?>
                                                </optgroup>
                                            <?php } ?>
                                        </select>
                                        <div class="form-text">Up to 6. Shown at the end of the service page, in menu order.</div>
                                    </div>
                                    <div class="admin-field mb-3 col-md-6 d-flex align-items-end">
                                        <div class="form-check">
                                            <input type="hidden" name="service_show_shapes" value="0">
                                            <input type="checkbox" name="service_show_shapes" id="service_show_shapes" value="1" class="form-check-input" <?php echo $checked('service_show_shapes'); ?>>
                                            <label class="form-check-label" for="service_show_shapes">Show "Shapes and finishes"</label>
                                            <div class="form-text">Edited under Miscellaneous Contents &gt; Nail Shapes &amp; Finishes.</div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </article>

                    <article class="accordion-item admin-form-accordion-item">
                        <?php $sectionHeader('addons', 'bi-plus-circle', 'Add-ons', 'Extras clients can add to this appointment', FALSE); ?>
                        <div id="service-addons-panel" class="<?php echo $panelClass('addons'); ?>">
                            <div class="accordion-body">
                                <fieldset
                                    class="<?php echo in_array('addons', $invalid_fields, TRUE) ? 'validate-has-error' : ''; ?>"
                                    data-repeatable
                                    data-repeatable-max="20"
                                    data-repeatable-label="Add-on"
                                >
                                    <legend class="form-label">Add-ons</legend>
                                    <div class="admin-repeatable-list" data-repeatable-list><?php foreach ($addons as $addon) {
                                        $addonPrice = isset($addon['addon_price_input'])
                                            ? $addon['addon_price_input']
                                            : ($addon['addon_price'] !== NULL ? rtrim(rtrim((string) $addon['addon_price'], '0'), '.') : '');
                                        $addonRow($addon['addon_label'], $addonPrice);
                                    } ?></div>
                                    <p class="admin-repeatable-empty" data-repeatable-empty>No add-ons yet.</p>
                                    <template data-repeatable-template><?php $addonRow('', ''); ?></template>
                                    <button type="button" class="btn btn-outline-primary btn-sm" data-repeatable-add>
                                        <i class="bi bi-plus-lg" aria-hidden="true"></i> Add an add-on
                                    </button>
                                    <div class="form-text">Shown on the service page as "Add to this appointment". Leave the price empty for free extras.</div>
                                </fieldset>
                            </div>
                        </div>
                    </article>

                    <article class="accordion-item admin-form-accordion-item">
                        <?php $sectionHeader('images', 'bi-image', 'Images', 'Card image and page header image', FALSE); ?>
                        <div id="service-images-panel" class="<?php echo $panelClass('images'); ?>">
                            <div class="accordion-body">
                                <div class="admin-field mb-4">
                                    <?php $this->load->view('admin/partials/file_upload', array(
                                        'name' => 'card_image_upload',
                                        'id' => 'card_image_upload',
                                        'label' => 'Card Image (Optional)',
                                        'allowed_types' => UPLOAD_IMAGE_MIMES,
                                        'required' => FALSE,
                                        'current_path' => $imagePath('service_card_image'),
                                        'recommended_size' => '800 × 800px (1:1)',
                                        'size_note' => 'Used on service cards, the services menu and the booking form.',
                                        'preview_alt' => 'Current card image',
                                        'preview_size' => 160,
                                    )); ?>
                                </div>
                                <?php if ($isEdit && $hasImage('service_card_image')) { ?>
                                    <div class="admin-current-file-remove">
                                        <div class="admin-current-file-actions">
                                            <button type="button" class="btn btn-outline-danger btn-sm removeFile" data-controller="<?php echo $this->controller; ?>" data-file-name="service_card_image" data-file-id="<?php echo $recordId; ?>">
                                                <i class="bi bi-trash" aria-hidden="true"></i> Remove Card Image
                                            </button>
                                        </div>
                                    </div>
                                <?php } ?>

                                <div class="admin-field mb-4">
                                    <?php $this->load->view('admin/partials/file_upload', array(
                                        'name' => 'hero_image_upload',
                                        'id' => 'hero_image_upload',
                                        'label' => 'Page Image (Optional)',
                                        'allowed_types' => UPLOAD_IMAGE_MIMES,
                                        'required' => FALSE,
                                        'current_path' => $imagePath('service_hero_image'),
                                        'recommended_size' => '1200 × 1500px (4:5)',
                                        'size_note' => 'Shown beside the introduction on the service page. Leave empty to use the card image.',
                                        'preview_alt' => 'Current page image',
                                        'preview_size' => 160,
                                    )); ?>
                                </div>
                                <?php if ($isEdit && $hasImage('service_hero_image')) { ?>
                                    <div class="admin-current-file-remove">
                                        <div class="admin-current-file-actions">
                                            <button type="button" class="btn btn-outline-danger btn-sm removeFile" data-controller="<?php echo $this->controller; ?>" data-file-name="service_hero_image" data-file-id="<?php echo $recordId; ?>">
                                                <i class="bi bi-trash" aria-hidden="true"></i> Remove Page Image
                                            </button>
                                        </div>
                                    </div>
                                <?php } ?>
                            </div>
                        </div>
                    </article>

                    <article class="accordion-item admin-form-accordion-item">
                        <?php $sectionHeader('search', 'bi-search', 'Search Visibility', 'Search result text and crawler controls', FALSE); ?>
                        <div id="service-search-panel" class="<?php echo $panelClass('search'); ?>">
                            <div class="accordion-body">
                                <div class="admin-field mb-3">
                                    <label class="form-label" for="page_title">Page Title</label>
                                    <input type="text" name="page_title" id="page_title" maxlength="255" value="<?php echo $value('page_title'); ?>" class="form-control">
                                    <div class="form-text">Leave blank to use the service name followed by the site name.</div>
                                </div>
                                <div class="admin-field mb-3">
                                    <label class="form-label" for="meta_description">Meta Description</label>
                                    <textarea rows="3" name="meta_description" id="meta_description" maxlength="500" class="form-control"><?php echo $value('meta_description'); ?></textarea>
                                    <div class="form-text">Leave blank to use the summary. About 150–160 characters displays best in search results.</div>
                                </div>
                                <fieldset class="admin-form-choice-group">
                                    <legend>Search Engine Access</legend>
                                    <div class="row">
                                        <div class="col-md-6">
                                            <div class="form-check">
                                                <input type="hidden" name="robots_index" value="0">
                                                <input type="checkbox" name="robots_index" id="robots_index" value="1" class="form-check-input" <?php echo $checked('robots_index'); ?>>
                                                <label class="form-check-label" for="robots_index">Allow indexing</label>
                                            </div>
                                        </div>
                                        <div class="col-md-6">
                                            <div class="form-check">
                                                <input type="hidden" name="robots_follow" value="0">
                                                <input type="checkbox" name="robots_follow" id="robots_follow" value="1" class="form-check-input" <?php echo $checked('robots_follow'); ?>>
                                                <label class="form-check-label" for="robots_follow">Allow link following</label>
                                            </div>
                                        </div>
                                    </div>
                                </fieldset>
                            </div>
                        </div>
                    </article>

                    <article class="accordion-item admin-form-accordion-item">
                        <?php $sectionHeader('sharing', 'bi-share', 'Social Sharing', 'Preview title, description and image', FALSE); ?>
                        <div id="service-sharing-panel" class="<?php echo $panelClass('sharing'); ?>">
                            <div class="accordion-body">
                                <div class="admin-field mb-3">
                                    <label class="form-label" for="og_title">Sharing Title</label>
                                    <input type="text" name="og_title" id="og_title" maxlength="255" value="<?php echo $value('og_title'); ?>" class="form-control">
                                    <div class="form-text">Leave blank to use the page title.</div>
                                </div>
                                <div class="admin-field mb-3">
                                    <label class="form-label" for="og_description">Sharing Description</label>
                                    <textarea rows="3" name="og_description" id="og_description" maxlength="500" class="form-control"><?php echo $value('og_description'); ?></textarea>
                                    <div class="form-text">Leave blank to use the meta description.</div>
                                </div>
                                <div class="admin-field mb-4">
                                    <?php $this->load->view('admin/partials/file_upload', array(
                                        'name' => 'og_image_upload',
                                        'id' => 'og_image_upload',
                                        'label' => 'Sharing Image (Optional)',
                                        'allowed_types' => UPLOAD_IMAGE_MIMES,
                                        'required' => FALSE,
                                        'current_path' => $imagePath('og_image'),
                                        'recommended_size' => '1200 × 630px',
                                        'size_note' => 'Used for link previews. Leave empty to use the page image, then the card image.',
                                        'preview_alt' => 'Current sharing image',
                                        'preview_size' => 220,
                                    )); ?>
                                </div>
                                <?php if ($isEdit && $hasImage('og_image')) { ?>
                                    <div class="admin-current-file-remove">
                                        <div class="admin-current-file-actions">
                                            <button type="button" class="btn btn-outline-danger btn-sm removeFile" data-controller="<?php echo $this->controller; ?>" data-file-name="og_image" data-file-id="<?php echo $recordId; ?>">
                                                <i class="bi bi-trash" aria-hidden="true"></i> Remove Sharing Image
                                            </button>
                                        </div>
                                    </div>
                                <?php } ?>
                            </div>
                        </div>
                    </article>
                </div>

                <div class="admin-form-actions">
                    <a class="btn btn-outline-secondary" href="<?php echo ADMIN_URL.$this->controller; ?>">Cancel</a>
                    <button type="submit" class="btn btn-primary" data-save-button>
                        <span data-save-label>Save <?php echo htmlspecialchars($this->moduleNameSingular, ENT_QUOTES, 'UTF-8'); ?></span>
                    </button>
                </div>
            </form>
        </div>
    </div>
</section>
