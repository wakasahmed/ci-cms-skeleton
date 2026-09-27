<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="color-scheme" content="light">
    <?php /* The admin area is never indexed. It is deliberately not listed in robots.txt either:
             naming a private path there would only advertise it. The header covers the login
             screen and every other admin page that is served through this template. */ ?>
    <meta name="robots" content="noindex, nofollow, noarchive">
    <?php $this->output->set_header('X-Robots-Tag: noindex, nofollow, noarchive'); ?>
    <title><?php echo isset($page_title) ? $page_title : PROJECT_TITLE; ?></title>
    <?php $faviconTags = favicon_tags(); ?>
    <?php if ($faviconTags !== '') { ?>
    <?php echo $faviconTags; ?>
    <?php } ?>
    <link rel="stylesheet" href="<?php echo ADMIN_ASSETS; ?>vendor/bootstrap/bootstrap.min.css">
    <link rel="stylesheet" href="<?php echo ADMIN_ASSETS; ?>vendor/bootstrap-icons/bootstrap-icons.min.css">
    <link rel="stylesheet" href="<?php echo ADMIN_ASSETS; ?>vendor/select2/select2.min.css">
    <link rel="stylesheet" href="<?php echo ADMIN_ASSETS; ?>vendor/flatpickr/flatpickr.min.css">
    <link rel="stylesheet" href="<?php echo ADMIN_ASSETS; ?>vendor/intl-tel-input/css/intlTelInput.min.css?v=29.2.3">
    <?php if (!empty($useIconPicker)) { ?>
    <link rel="stylesheet" href="<?php echo ADMIN_ASSETS; ?>vendor/fontawesome-free/css/all.min.css?v=7.3.1">
    <link rel="stylesheet" href="<?php echo ADMIN_ASSETS; ?>vendor/vanilla-icon-picker/bootstrap-5.min.css?v=1.3.1">
    <?php } ?>
    <?php if (!empty($useColorPicker)) { ?>
    <link rel="stylesheet" href="<?php echo ADMIN_ASSETS; ?>vendor/coloris/coloris.min.css">
    <?php } ?>
    <?php if (!empty($useDropzone)) { ?>
    <link rel="stylesheet" href="<?php echo ADMIN_ASSETS; ?>vendor/dropzone/dropzone.min.css?v=<?php echo (int) @filemtime(FCPATH.'assets/admin/vendor/dropzone/dropzone.min.css'); ?>">
    <?php } ?>
    <?php if (in_array($this->controller, array('admins', 'customer-reviews'), TRUE)) { ?>
    <link rel="stylesheet" href="<?php echo ADMIN_ASSETS; ?>vendor/cropperjs/cropper.min.css?v=<?php echo (int) @filemtime(FCPATH.'assets/admin/vendor/cropperjs/cropper.min.css'); ?>">
    <?php } ?>
    <?php if (!empty($useMenuManager)) { ?>
    <link rel="stylesheet" href="<?php echo ADMIN_ASSETS; ?>css/menu-manager.css?v=<?php echo (int) @filemtime(FCPATH.'assets/admin/css/menu-manager.css'); ?>">
    <?php } ?>
    <link rel="stylesheet" href="<?php echo ADMIN_ASSETS; ?>css/admin.css?v=<?php echo (int) @filemtime(FCPATH.'assets/admin/css/admin.css'); ?>">
    <?php if (!empty($useIconPicker)) { ?>
    <link rel="stylesheet" href="<?php echo ADMIN_ASSETS; ?>css/icon-picker.css?v=<?php echo (int) @filemtime(FCPATH.'assets/admin/css/icon-picker.css'); ?>">
    <?php } ?>
    <script src="<?php echo ADMIN_ASSETS; ?>vendor/jquery/jquery.min.js"></script>
</head>
<body class="<?php echo isset($loginSection) ? 'admin-login-page' : 'admin-body'; ?>">
<?php if (!isset($loginSection)) { ?>
<script>
(function () {
    try {
        if (window.localStorage && window.localStorage.getItem('adminSidebarCollapsed') === '1' && window.innerWidth >= 992) {
            document.body.classList.add('admin-sidebar-collapsed');
        }
    } catch (e) {}
})();
</script>
<?php } ?>
<a class="visually-hidden-focusable admin-skip-link" href="#admin-main-content">Skip to main content</a>
