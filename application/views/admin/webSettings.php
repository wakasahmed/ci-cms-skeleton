<?php
$value = function ($key) use ($web) {
    return isset($web[$key]) ? htmlspecialchars((string) $web[$key], ENT_QUOTES, 'UTF-8') : '';
};
$isInvalid = function ($key) use ($invalid_fields) {
    return in_array($key, $invalid_fields, TRUE);
};
$openSections = array_unique(array_merge(array('general'), $error_sections));
$isOpen = function ($section) use ($openSections) {
    return in_array($section, $openSections, TRUE);
};
$currentImage = function ($column, $directory) use ($web) {
    return !empty($web[$column]) ? $directory.basename((string) $web[$column]) : '';
};

/**
 * Status badge shown next to each section heading, same idea as addPage.php:
 * a fixed "Required" badge for the section that always needs input, and a
 * fixed "Optional" badge for the rest. An error always takes over the badge
 * regardless of this.
 */
$sectionBadges = array(
    'general' => array('Required', 'text-bg-danger'),
    'branding' => array('Required', 'text-bg-danger'),
    'contact' => array('Required', 'text-bg-danger'),
    'currency' => array('Required', 'text-bg-danger'),
    'backgrounds' => array('Optional', 'text-bg-light'),
    'social' => array('Optional', 'text-bg-light'),
    'email' => array('Optional', 'text-bg-light'),
    'footer' => array('Optional', 'text-bg-light'),
    'scripts' => array('Optional', 'text-bg-light'),
);

/**
 * Renders one collapsible section's opening header markup, using the same
 * admin-form-accordion structure and classes as addPage.php: icon on the
 * left, heading, a status badge, then the native chevron on the right. The
 * body markup (the actual fields) is written inline after each call so this
 * stays a plain header helper rather than trying to template arbitrary field
 * sets.
 */
$sectionHeader = function ($key, $title, $description, $icon) use ($isOpen, $error_sections, $sectionBadges) {
    $panelId = 'website-settings-panel-'.$key;
    $headingId = 'website-settings-heading-'.$key;
    $open = $isOpen($key);
    $hasError = in_array($key, $error_sections, TRUE);
    echo '<article class="accordion-item admin-form-accordion-item'.($hasError ? ' has-error' : '').'" data-website-settings-section="'.htmlspecialchars($key, ENT_QUOTES, 'UTF-8').'"'.($hasError ? ' data-force-open="true"' : '').'>';
    echo '<h3 class="accordion-header" id="'.htmlspecialchars($headingId, ENT_QUOTES, 'UTF-8').'">';
    echo '<button class="accordion-button'.($open ? '' : ' collapsed').'" type="button" data-bs-toggle="collapse" data-bs-target="#'.htmlspecialchars($panelId, ENT_QUOTES, 'UTF-8').'" aria-expanded="'.($open ? 'true' : 'false').'" aria-controls="'.htmlspecialchars($panelId, ENT_QUOTES, 'UTF-8').'">';
    echo '<span class="admin-form-section-icon"><i class="bi '.htmlspecialchars($icon, ENT_QUOTES, 'UTF-8').'" aria-hidden="true"></i></span>';
    echo '<span class="admin-form-section-heading"><span class="admin-form-section-title">'.htmlspecialchars($title, ENT_QUOTES, 'UTF-8').'</span><span class="admin-form-section-description">'.htmlspecialchars($description, ENT_QUOTES, 'UTF-8').'</span></span>';
    if ($hasError) {
        echo '<span class="badge text-bg-danger admin-form-section-status">Needs attention</span>';
    } elseif (isset($sectionBadges[$key])) {
        echo '<span class="badge '.htmlspecialchars($sectionBadges[$key][1], ENT_QUOTES, 'UTF-8').' admin-form-section-status">'.htmlspecialchars($sectionBadges[$key][0], ENT_QUOTES, 'UTF-8').'</span>';
    }
    echo '</button>';
    echo '</h3>';
    echo '<div id="'.htmlspecialchars($panelId, ENT_QUOTES, 'UTF-8').'" class="accordion-collapse collapse'.($open ? ' show' : '').'" aria-labelledby="'.htmlspecialchars($headingId, ENT_QUOTES, 'UTF-8').'">';
    echo '<div class="accordion-body">';
};
$sectionFooter = function () {
    echo '</div></div></article>';
};
?>
<?php $this->load->view('admin/partials/breadcrumb', array('items' => array(array('label' => 'Website Settings', 'active' => TRUE)))); ?>

<?php $this->load->view('admin/partials/crud_alert', array(
    'status' => $alert,
    'module_name' => 'Website Settings',
    'status_messages' => array(
        'success' => array('success', 'Success!', 'Website Settings saved successfully.'),
        'validation_error' => array('danger', 'Error!', 'The Website Settings could not be saved. Review the highlighted fields.'),
        'upload_error' => array('danger', 'Error!', 'The Website Settings could not be saved because one or more images were invalid.'),
        'database_error' => array('danger', 'Error!', 'The Website Settings could not be saved. No changes were applied.'),
    ),
)); ?>

<section class="admin-records-listing website-settings" aria-labelledby="website-settings-title">
    <?php $this->load->view('admin/partials/module_header', array(
        'title' => 'Website Settings',
        'description' => 'Manage the website identity, branding, contact details, notifications, default imagery, footer content, custom scripts, and social profiles.',
        'id' => 'website-settings-title',
    )); ?>

    <div class="card admin-card">
        <div class="card-body">
            <form id="website_settings_form" name="website_settings_form" method="post" action="<?php echo base_url('manage/website-settings/save'); ?>" enctype="multipart/form-data" class="validate" data-website-settings-form>
                <?php if ($this->config->item('csrf_protection')) { ?>
                    <input type="hidden" name="<?php echo htmlspecialchars($this->security->get_csrf_token_name(), ENT_QUOTES, 'UTF-8'); ?>" value="<?php echo htmlspecialchars($this->security->get_csrf_hash(), ENT_QUOTES, 'UTF-8'); ?>">
                <?php } ?>

                <div class="accordion admin-form-accordion" id="websiteSettingsAccordion">

                <?php $sectionHeader('general', 'General Settings', 'Website identity, public URL, and availability.', 'bi-info-circle'); ?>
                    <div class="row">
                        <div class="admin-field mb-3 col-md-6">
                            <label class="form-label is-required" for="website_title">Website Name</label>
                            <input type="text" name="website_title" id="website_title" maxlength="255" value="<?php echo $value('website_title'); ?>" class="form-control<?php echo $isInvalid('website_title') ? ' is-invalid' : ''; ?>" data-validate="required" required placeholder="My Website">
                        </div>
                        <div class="admin-field mb-3 col-md-6">
                            <label class="form-label is-required" for="website_title_ar">Website Name (Arabic)</label>
                            <input type="text" name="website_title_ar" id="website_title_ar" maxlength="255" value="<?php echo $value('website_title_ar'); ?>" class="form-control<?php echo $isInvalid('website_title_ar') ? ' is-invalid' : ''; ?>" data-validate="required" required dir="rtl" lang="ar" placeholder="اسم الموقع">
                        </div>
                    </div>
                    <div class="admin-field mb-3">
                        <label class="form-label is-required" for="website_url">Website URL</label>
                        <input type="text" name="website_url" id="website_url" maxlength="255" value="<?php echo $value('website_url'); ?>" class="form-control<?php echo $isInvalid('website_url') ? ' is-invalid' : ''; ?>" data-validate="required" required placeholder="www.mywebsite.com">
                        <div class="form-text">The public web address shown to customers. A scheme (https://) is not required.</div>
                    </div>
                    <div class="row">
                        <div class="admin-field mb-0 col-md-6">
                            <label class="form-label" for="default_language">Default Language</label>
                            <select autocomplete="off" class="form-select select2<?php echo $isInvalid('default_language') ? ' is-invalid' : ''; ?>" name="default_language" id="default_language" data-minimum-results-for-search="-1">
                                <option value="English" <?php echo (!isset($web['default_language']) || $web['default_language'] === 'English') ? 'selected' : ''; ?>>English</option>
                                <option value="Arabic" <?php echo (isset($web['default_language']) && $web['default_language'] === 'Arabic') ? 'selected' : ''; ?>>Arabic</option>
                            </select>
                            <div class="form-text">The language shown to visitors by default.</div>
                        </div>
                        <div class="admin-field mb-0 col-md-6">
                            <label class="form-label" for="under_construction">Website Under Construction</label>
                            <select autocomplete="off" class="form-select select2<?php echo $isInvalid('under_construction') ? ' is-invalid' : ''; ?>" name="under_construction" id="under_construction" data-minimum-results-for-search="-1">
                                <option value="Yes" <?php echo (isset($web['under_construction']) && $web['under_construction'] === 'Yes') ? 'selected' : ''; ?>>Yes</option>
                                <option value="No" <?php echo (!isset($web['under_construction']) || $web['under_construction'] !== 'Yes') ? 'selected' : ''; ?>>No</option>
                            </select>
                            <div class="form-text">Yes shows visitors a "coming soon" page (HTTP 503) and keeps search engines out. Signed-in administrators still see the full website. No keeps the public website available normally.</div>
                        </div>
                    </div>
                <?php $sectionFooter(); ?>

                <?php $sectionHeader('branding', 'Branding and Logos', 'The logos and favicon used across the website.', 'bi-badge-tm'); ?>
                    <div class="admin-field mb-4">
                        <?php $this->load->view('admin/partials/file_upload', array(
                            'name' => 'uploadfile', 'id' => 'uploadfile', 'label' => 'Main Logo',
                            'allowed_types' => 'jpg|jpeg|png', 'required' => TRUE,
                            'current_path' => $currentImage('logo', 'assets/frontend/images/logo/'),
                            'preview_alt' => 'Current main logo', 'preview_shape' => 'square', 'preview_size' => 132,
                            'help' => 'Allowed: JPG, JPEG, PNG. Max 4000 x 4000px, 10 MB.',
                            'recommended_size' => 'at least 300px tall, PNG with a transparent background',
                            'size_note' => 'Shown in the website header at 44–56px tall; the width follows the logo.',
                        )); ?>
                    </div>
                    <div class="admin-field mb-4 d-none">
                        <?php $this->load->view('admin/partials/file_upload', array(
                            'name' => 'uploadfile2', 'id' => 'uploadfile2', 'label' => 'Header Logo',
                            'allowed_types' => 'jpg|jpeg|png', 'required' => FALSE,
                            'current_path' => $currentImage('logo_sticky', 'assets/frontend/images/logo/'),
                            'preview_alt' => 'Current header logo', 'preview_shape' => 'square', 'preview_size' => 132,
                            'help' => 'Shown once the header becomes sticky. Allowed: JPG, JPEG, PNG. Max 4000 x 4000px, 10 MB.',
                        )); ?>
                    </div>
                    <div class="admin-field mb-4">
                        <?php $this->load->view('admin/partials/file_upload', array(
                            'name' => 'uploadfile5', 'id' => 'uploadfile5', 'label' => 'White Logo',
                            'allowed_types' => 'jpg|jpeg|png', 'required' => TRUE,
                            'current_path' => $currentImage('logo_white', 'assets/frontend/images/logo/'),
                            'preview_alt' => 'Current white logo', 'preview_shape' => 'square', 'preview_size' => 132,
                            'help' => 'Allowed: JPG, JPEG, PNG. Max 4000 x 4000px, 10 MB.',
                            'recommended_size' => 'at least 300px tall, white PNG with a transparent background',
                            'size_note' => 'Shown in the website footer at 80px tall; the width follows the logo.',
                        )); ?>
                    </div>
                    <div class="admin-field mb-0">
                        <?php $this->load->view('admin/partials/file_upload', array(
                            'name' => 'uploadfile6', 'id' => 'uploadfile6', 'label' => 'Favicon',
                            'allowed_types' => 'jpg|jpeg|png', 'required' => TRUE,
                            'current_path' => $currentImage('favicon', 'assets/frontend/images/logo/'),
                            'preview_alt' => 'Current favicon', 'preview_shape' => 'square', 'preview_size' => 132,
                            'help' => 'Allowed: JPG, JPEG, PNG. Max 4000 x 4000px, 10 MB.',
                            'recommended_size' => '512 × 512px (1:1)',
                            'size_note' => 'Use a square PNG. It is used as-is for the browser tab icon and the home-screen icon.',
                        )); ?>
                    </div>
                <?php $sectionFooter(); ?>

                <?php $sectionHeader('contact', 'Contact Information', 'Address, phone, and email shown to customers.', 'bi-geo-alt'); ?>
                    <div class="row">
                        <div class="admin-field mb-3 col-md-6">
                            <label class="form-label" for="address">Address (English)</label>
                            <textarea name="address" id="address" rows="3" class="form-control<?php echo $isInvalid('address') ? ' is-invalid' : ''; ?>" placeholder="Address"><?php echo $value('address'); ?></textarea>
                        </div>
                        <div class="admin-field mb-3 col-md-6">
                            <label class="form-label" for="address_ar">Address (Arabic)</label>
                            <textarea name="address_ar" id="address_ar" rows="3" class="form-control<?php echo $isInvalid('address_ar') ? ' is-invalid' : ''; ?>" dir="rtl" lang="ar" placeholder="العنوان"><?php echo $value('address_ar'); ?></textarea>
                        </div>
                    </div>
                    <div class="row">
                        <div class="admin-field mb-0 col-md-6">
                            <label class="form-label is-required" for="phone">Phone</label>
                            <input type="tel" name="phone" id="phone" value="<?php echo $value('phone'); ?>" class="form-control intl-phone<?php echo $isInvalid('phone') ? ' is-invalid' : ''; ?>" data-initial-country="sa" data-validate="required,intlPhone" required autocomplete="tel" placeholder="Phone number">
                        </div>
                        <div class="admin-field mb-0 col-md-6">
                            <label class="form-label is-required" for="email">Email</label>
                            <input type="email" name="email" id="email" value="<?php echo $value('email'); ?>" class="form-control<?php echo $isInvalid('email') ? ' is-invalid' : ''; ?>" data-validate="required,email" required placeholder="info@example.com">
                        </div>
                    </div>
                <?php $sectionFooter(); ?>

                <?php $sectionHeader('currency', 'Currency, Profit & Tax', 'The currency shown with prices, and the profit and tax percentages applied to every tour and experience price.', 'bi-cash-coin'); ?>
                    <div class="row">
                        <div class="admin-field mb-0 col-md-6">
                            <label class="form-label is-required" for="currency_unit">Currency Unit</label>
                            <input type="text" name="currency_unit" id="currency_unit" maxlength="255" value="<?php echo $value('currency_unit'); ?>" class="form-control<?php echo $isInvalid('currency_unit') ? ' is-invalid' : ''; ?>" data-validate="required" required placeholder="SAR">
                        </div>
                        <div class="admin-field mb-0 col-md-6">
                            <label class="form-label is-required" for="currency_unit_ar">Currency Unit (Arabic)</label>
                            <input type="text" name="currency_unit_ar" id="currency_unit_ar" maxlength="255" value="<?php echo $value('currency_unit_ar'); ?>" class="form-control<?php echo $isInvalid('currency_unit_ar') ? ' is-invalid' : ''; ?>" data-validate="required" required dir="rtl" lang="ar" placeholder="ريال سعودي">
                        </div>
                        <div class="admin-field mb-0 mt-3 col-md-6">
                            <label class="form-label is-required" for="profit">Profit (%)</label>
                            <input
                                type="number"
                                name="profit"
                                id="profit"
                                min="0"
                                max="999.99"
                                step="0.01"
                                inputmode="decimal"
                                value="<?php echo $value('profit'); ?>"
                                class="form-control<?php echo $isInvalid('profit') ? ' is-invalid' : ''; ?>"
                                data-validate="required,number,min[0],max[999.99]"
                                required
                                aria-describedby="profit-help"
                                placeholder="0.00"
                            >
                            <div id="profit-help" class="form-text">Added to the booking subtotal before any discount. Enter 0 for none.</div>
                        </div>
                        <div class="admin-field mb-0 mt-3 col-md-6">
                            <label class="form-label is-required" for="tax">Tax (%)</label>
                            <input
                                type="number"
                                name="tax"
                                id="tax"
                                min="0"
                                max="100"
                                step="0.01"
                                inputmode="decimal"
                                value="<?php echo $value('tax'); ?>"
                                class="form-control<?php echo $isInvalid('tax') ? ' is-invalid' : ''; ?>"
                                data-validate="required,number,min[0],max[100]"
                                required
                                aria-describedby="tax-help"
                                placeholder="0.00"
                            >
                            <div id="tax-help" class="form-text">Applied after profit and discount. Customer prices include it. Enter 0 for none.</div>
                        </div>
                    </div>
                <?php $sectionFooter(); ?>

                <?php $sectionHeader('email', 'Email & Notifications', 'Outgoing email identity and who gets notified.', 'bi-envelope-at'); ?>
                    <div class="admin-field mb-3">
                        <label class="form-label" for="notification_emails">Notification Emails</label>
                        <textarea name="notification_emails" id="notification_emails" rows="4" class="form-control<?php echo $isInvalid('notification_emails') ? ' is-invalid' : ''; ?>" placeholder="one@example.com&#10;two@example.com"><?php echo $value('notification_emails'); ?></textarea>
                        <div class="form-text">Enter one email address per line.</div>
                    </div>
                    <div class="row">
                        <div class="admin-field mb-3 col-md-6">
                            <label class="form-label" for="sender_name">Sender Name</label>
                            <input type="text" name="sender_name" id="sender_name" maxlength="255" value="<?php echo $value('sender_name'); ?>" class="form-control<?php echo $isInvalid('sender_name') ? ' is-invalid' : ''; ?>" placeholder="Sender Name">
                            <div class="form-text">Leave blank to use the website name.</div>
                        </div>
                        <div class="admin-field mb-3 col-md-6">
                            <label class="form-label" for="sender_name_ar">Sender Name (Arabic)</label>
                            <input type="text" name="sender_name_ar" id="sender_name_ar" maxlength="255" value="<?php echo $value('sender_name_ar'); ?>" class="form-control<?php echo $isInvalid('sender_name_ar') ? ' is-invalid' : ''; ?>" dir="rtl" lang="ar" placeholder="اسم المرسل">
                            <div class="form-text">Leave blank to use the website name.</div>
                        </div>
                    </div>
                    <div class="admin-field mb-0">
                        <label class="form-label" for="sender_email">Sender Email</label>
                        <input type="email" name="sender_email" id="sender_email" maxlength="255" value="<?php echo $value('sender_email'); ?>" class="form-control<?php echo $isInvalid('sender_email') ? ' is-invalid' : ''; ?>" placeholder="no-reply@example.com">
                        <div class="form-text">Leave blank to use the website email address.</div>
                    </div>
                <?php $sectionFooter(); ?>

                <?php $sectionHeader('backgrounds', 'Default Backgrounds', 'Fallback background imagery for English and Arabic pages.', 'bi-image'); ?>
                    <div class="admin-field mb-4">
                        <?php $this->load->view('admin/partials/file_upload', array(
                            'name' => 'uploadfile3', 'id' => 'uploadfile3', 'label' => 'Default Background Image (English)',
                            'allowed_types' => 'jpg|jpeg|png', 'required' => FALSE,
                            'current_path' => $currentImage('default_bg', 'assets/frontend/images/bg/'),
                            'preview_alt' => 'Current default background', 'preview_shape' => 'square', 'preview_size' => 132,
                            'help' => 'Recommended size: 1000 x 470px. Allowed: JPG, JPEG, PNG. Max 10 MB.',
                        )); ?>
                    </div>
                    <div class="admin-field mb-0">
                        <?php $this->load->view('admin/partials/file_upload', array(
                            'name' => 'uploadfile4', 'id' => 'uploadfile4', 'label' => 'Default Background Image (Arabic)',
                            'allowed_types' => 'jpg|jpeg|png', 'required' => FALSE,
                            'current_path' => $currentImage('default_bg_ar', 'assets/frontend/images/bg/'),
                            'preview_alt' => 'Current Arabic default background', 'preview_shape' => 'square', 'preview_size' => 132,
                            'help' => 'Recommended size: 1000 x 470px. Allowed: JPG, JPEG, PNG. Max 10 MB.',
                        )); ?>
                    </div>
                <?php $sectionFooter(); ?>

                <?php $sectionHeader('social', 'Social Media Links', 'Public profile links shown across the website.', 'bi-share'); ?>
                    <div class="row">
                        <div class="admin-field mb-3 col-md-6">
                            <label class="form-label" for="facebook">Facebook</label>
                            <input type="url" name="facebook" id="facebook" maxlength="255" value="<?php echo $value('facebook'); ?>" class="form-control<?php echo $isInvalid('facebook') ? ' is-invalid' : ''; ?>" placeholder="https://facebook.com/yourpage">
                        </div>
                        <div class="admin-field mb-3 col-md-6">
                            <label class="form-label" for="twitter">Twitter</label>
                            <input type="url" name="twitter" id="twitter" maxlength="255" value="<?php echo $value('twitter'); ?>" class="form-control<?php echo $isInvalid('twitter') ? ' is-invalid' : ''; ?>" placeholder="https://twitter.com/yourhandle">
                        </div>
                        <div class="admin-field mb-3 col-md-6">
                            <label class="form-label" for="instagram">Instagram</label>
                            <input type="url" name="instagram" id="instagram" maxlength="255" value="<?php echo $value('instagram'); ?>" class="form-control<?php echo $isInvalid('instagram') ? ' is-invalid' : ''; ?>" placeholder="https://instagram.com/yourprofile">
                        </div>
                        <div class="admin-field mb-3 col-md-6">
                            <label class="form-label" for="linkedin">LinkedIn</label>
                            <input type="url" name="linkedin" id="linkedin" maxlength="255" value="<?php echo $value('linkedin'); ?>" class="form-control<?php echo $isInvalid('linkedin') ? ' is-invalid' : ''; ?>" placeholder="https://linkedin.com/company/yourcompany">
                        </div>
                        <div class="admin-field mb-0 col-md-6">
                            <label class="form-label" for="youtube">YouTube</label>
                            <input type="url" name="youtube" id="youtube" maxlength="255" value="<?php echo $value('youtube'); ?>" class="form-control<?php echo $isInvalid('youtube') ? ' is-invalid' : ''; ?>" placeholder="https://youtube.com/yourchannel">
                        </div>
                    </div>
                <?php $sectionFooter(); ?>

                <?php $sectionHeader('footer', 'Footer', 'Footer columns, contact copy, and copyright text.', 'bi-layout-text-window-reverse'); ?>
                    <div class="row">
                        <div class="admin-field mb-3 col-md-6">
                            <label class="form-label" for="website_intro">About Website / Organization</label>
                            <textarea name="website_intro" id="website_intro" rows="3" class="form-control<?php echo $isInvalid('website_intro') ? ' is-invalid' : ''; ?>"><?php echo $value('website_intro'); ?></textarea>
                        </div>
                        <div class="admin-field mb-3 col-md-6">
                            <label class="form-label" for="website_intro_ar">About Website / Organization (Arabic)</label>
                            <textarea name="website_intro_ar" id="website_intro_ar" rows="3" class="form-control<?php echo $isInvalid('website_intro_ar') ? ' is-invalid' : ''; ?>" dir="rtl" lang="ar"><?php echo $value('website_intro_ar'); ?></textarea>
                        </div>
                    </div>
                    <div class="row">
                        <div class="admin-field mb-3 col-md-6">
                            <label class="form-label" for="foot_col_1">Heading 1</label>
                            <input type="text" name="foot_col_1" id="foot_col_1" maxlength="255" value="<?php echo $value('foot_col_1'); ?>" class="form-control<?php echo $isInvalid('foot_col_1') ? ' is-invalid' : ''; ?>">
                        </div>
                        <div class="admin-field mb-3 col-md-6">
                            <label class="form-label" for="foot_col_1_ar">Heading 1 (Arabic)</label>
                            <input type="text" name="foot_col_1_ar" id="foot_col_1_ar" maxlength="255" value="<?php echo $value('foot_col_1_ar'); ?>" class="form-control<?php echo $isInvalid('foot_col_1_ar') ? ' is-invalid' : ''; ?>" dir="rtl" lang="ar">
                        </div>
                    </div>
                    <div class="row">
                        <div class="admin-field mb-3 col-md-6">
                            <label class="form-label" for="foot_col_2">Heading 2</label>
                            <input type="text" name="foot_col_2" id="foot_col_2" maxlength="255" value="<?php echo $value('foot_col_2'); ?>" class="form-control<?php echo $isInvalid('foot_col_2') ? ' is-invalid' : ''; ?>">
                        </div>
                        <div class="admin-field mb-3 col-md-6">
                            <label class="form-label" for="foot_col_2_ar">Heading 2 (Arabic)</label>
                            <input type="text" name="foot_col_2_ar" id="foot_col_2_ar" maxlength="255" value="<?php echo $value('foot_col_2_ar'); ?>" class="form-control<?php echo $isInvalid('foot_col_2_ar') ? ' is-invalid' : ''; ?>" dir="rtl" lang="ar">
                        </div>
                    </div>
                    <div class="row">
                        <div class="admin-field mb-3 col-md-6">
                            <label class="form-label" for="foot_col_3">Heading 3</label>
                            <input type="text" name="foot_col_3" id="foot_col_3" maxlength="255" value="<?php echo $value('foot_col_3'); ?>" class="form-control<?php echo $isInvalid('foot_col_3') ? ' is-invalid' : ''; ?>">
                        </div>
                        <div class="admin-field mb-3 col-md-6">
                            <label class="form-label" for="foot_col_3_ar">Heading 3 (Arabic)</label>
                            <input type="text" name="foot_col_3_ar" id="foot_col_3_ar" maxlength="255" value="<?php echo $value('foot_col_3_ar'); ?>" class="form-control<?php echo $isInvalid('foot_col_3_ar') ? ' is-invalid' : ''; ?>" dir="rtl" lang="ar">
                        </div>
                    </div>
                    <div class="row">
                        <div class="admin-field mb-3 col-md-6">
                            <label class="form-label" for="foot_col_4">Heading 4</label>
                            <input type="text" name="foot_col_4" id="foot_col_4" maxlength="255" value="<?php echo $value('foot_col_4'); ?>" class="form-control<?php echo $isInvalid('foot_col_4') ? ' is-invalid' : ''; ?>">
                        </div>
                        <div class="admin-field mb-3 col-md-6">
                            <label class="form-label" for="foot_col_4_ar">Heading 4 (Arabic)</label>
                            <input type="text" name="foot_col_4_ar" id="foot_col_4_ar" maxlength="255" value="<?php echo $value('foot_col_4_ar'); ?>" class="form-control<?php echo $isInvalid('foot_col_4_ar') ? ' is-invalid' : ''; ?>" dir="rtl" lang="ar">
                        </div>
                    </div>
                    <div class="row">
                        <div class="admin-field mb-3 col-md-6">
                            <label class="form-label" for="copyright_text">Copyright Text</label>
                            <input type="text" name="copyright_text" id="copyright_text" maxlength="255" value="<?php echo $value('copyright_text'); ?>" class="form-control<?php echo $isInvalid('copyright_text') ? ' is-invalid' : ''; ?>">
                        </div>
                        <div class="admin-field mb-3 col-md-6">
                            <label class="form-label" for="copyright_text_ar">Copyright Text (Arabic)</label>
                            <input type="text" name="copyright_text_ar" id="copyright_text_ar" maxlength="255" value="<?php echo $value('copyright_text_ar'); ?>" class="form-control<?php echo $isInvalid('copyright_text_ar') ? ' is-invalid' : ''; ?>" dir="rtl" lang="ar">
                        </div>
                    </div>
                    <div class="row">
                        <div class="admin-field mb-3 col-md-6">
                            <label class="form-label" for="license_number">Tourism License</label>
                            <input type="text" name="license_number" id="license_number" maxlength="255" value="<?php echo $value('license_number'); ?>" class="form-control<?php echo $isInvalid('license_number') ? ' is-invalid' : ''; ?>">
                        </div>
                        <div class="admin-field mb-3 col-md-6">
                            <label class="form-label" for="license_number_ar">Tourism License (Arabic)</label>
                            <input type="text" name="license_number_ar" id="license_number_ar" maxlength="255" value="<?php echo $value('license_number_ar'); ?>" class="form-control<?php echo $isInvalid('license_number_ar') ? ' is-invalid' : ''; ?>" dir="rtl" lang="ar">
                        </div>
                    </div>
                    <div class="row">
                        <div class="admin-field mb-0 col-md-6">
                            <label class="form-label" for="contact_text">Contact Text</label>
                            <textarea name="contact_text" id="contact_text" rows="3" class="form-control<?php echo $isInvalid('contact_text') ? ' is-invalid' : ''; ?>"><?php echo $value('contact_text'); ?></textarea>
                        </div>
                        <div class="admin-field mb-0 col-md-6">
                            <label class="form-label" for="contact_text_ar">Contact Text (Arabic)</label>
                            <textarea name="contact_text_ar" id="contact_text_ar" rows="3" class="form-control<?php echo $isInvalid('contact_text_ar') ? ' is-invalid' : ''; ?>" dir="rtl" lang="ar"><?php echo $value('contact_text_ar'); ?></textarea>
                        </div>
                    </div>
                    <div class="row mt-4">
                        <div class="admin-field mb-4 col-md-6">
                            <label class="form-label" for="payment_title">Payment Title</label>
                            <input type="text" name="payment_title" id="payment_title" maxlength="255" value="<?php echo $value('payment_title'); ?>" class="form-control<?php echo $isInvalid('payment_title') ? ' is-invalid' : ''; ?>">
                        </div>
                        <div class="admin-field mb-4 col-md-6">
                            <label class="form-label" for="payment_title_ar">Payment Title (Arabic)</label>
                            <input type="text" name="payment_title_ar" id="payment_title_ar" maxlength="255" value="<?php echo $value('payment_title_ar'); ?>" class="form-control<?php echo $isInvalid('payment_title_ar') ? ' is-invalid' : ''; ?>" dir="rtl" lang="ar">
                        </div>
                    </div>
                    <div class="admin-field mb-0">
                        <?php $this->load->view('admin/partials/file_upload', array(
                            'name' => 'uploadfile7',
                            'id' => 'uploadfile7',
                            'label' => 'Payment Accepted',
                            'allowed_types' => 'jpg|jpeg|png',
                            'required' => FALSE,
                            'current_path' => $currentImage('payment_icons', 'assets/frontend/images/logo/'),
                            'preview_alt' => 'Current payment accepted icons',
                            'preview_shape' => 'square',
                            'preview_size' => 132,
                            'help' => 'Allowed: JPG, JPEG, PNG. Max 4000 x 4000px, 10 MB.',
                            'recommended_size' => 'PNG with a transparent background',
                            'size_note' => 'The accepted payment method icons shown in the website footer.',
                        )); ?>
                    </div>
                <?php $sectionFooter(); ?>

                <?php $sectionHeader('scripts', 'Custom Scripts', 'Raw HTML or JavaScript injected into the site templates. For advanced use only.', 'bi-code-slash'); ?>
                    <div class="row">
                        <div class="admin-field mb-3 col-md-6">
                            <label class="form-label" for="script_after_head">After &lt;head&gt;</label>
                            <textarea name="script_after_head" id="script_after_head" rows="5" class="form-control font-monospace<?php echo $isInvalid('script_after_head') ? ' is-invalid' : ''; ?>" spellcheck="false"><?php echo $value('script_after_head'); ?></textarea>
                        </div>
                        <div class="admin-field mb-3 col-md-6">
                            <label class="form-label" for="script_before_head">Before &lt;/head&gt;</label>
                            <textarea name="script_before_head" id="script_before_head" rows="5" class="form-control font-monospace<?php echo $isInvalid('script_before_head') ? ' is-invalid' : ''; ?>" spellcheck="false"><?php echo $value('script_before_head'); ?></textarea>
                        </div>
                    </div>
                    <div class="row">
                        <div class="admin-field mb-0 col-md-6">
                            <label class="form-label" for="script_after_body">After &lt;body&gt;</label>
                            <textarea name="script_after_body" id="script_after_body" rows="5" class="form-control font-monospace<?php echo $isInvalid('script_after_body') ? ' is-invalid' : ''; ?>" spellcheck="false"><?php echo $value('script_after_body'); ?></textarea>
                        </div>
                        <div class="admin-field mb-0 col-md-6">
                            <label class="form-label" for="script_before_body">Before &lt;/body&gt;</label>
                            <textarea name="script_before_body" id="script_before_body" rows="5" class="form-control font-monospace<?php echo $isInvalid('script_before_body') ? ' is-invalid' : ''; ?>" spellcheck="false"><?php echo $value('script_before_body'); ?></textarea>
                        </div>
                    </div>
                <?php $sectionFooter(); ?>

                </div>

                <div class="admin-form-actions">
                    <a class="btn btn-outline-secondary" href="<?php echo ADMIN_URL; ?>">Cancel</a>
                    <button type="submit" class="btn btn-primary" data-save-button><span data-save-label>Save Website Settings</span></button>
                </div>
            </form>
        </div>
    </div>
</section>
