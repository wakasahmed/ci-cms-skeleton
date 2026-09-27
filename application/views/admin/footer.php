<?php if (!isset($loginSection)) { ?>
        </main>
        <footer class="admin-footer">
            <span></span>
            <span><?php echo htmlspecialchars(PROJECT_TITLE, ENT_QUOTES, 'UTF-8'); ?></span>
        </footer>
    </div>
</div>
<?php $this->load->view('admin/partials/confirm_modals'); ?>
<?php } ?>

<?php
if ($this->controller === 'pages') {
    $perpageURL = base_url('manage/'.$this->controller).'/index/'.(isset($parent_id) ? $parent_id : 0);
} elseif ($this->controller === 'pimages') {
    $perpageURL = base_url('manage/'.$this->controller).'/index/'.(isset($cat_id) ? $cat_id : 0);
} else {
    $perpageURL = base_url('manage/'.$this->controller).'/';
}
$hasMultiUpload = isset($suploadscript);
?>
<script>
window.AdminConfig = {
    adminUrl: <?php echo json_encode(ADMIN_URL); ?>,
    baseUrl: <?php echo json_encode(base_url()); ?>,
    controllerUrl: <?php echo json_encode($perpageURL); ?>,
    controllerName: <?php echo json_encode($this->controller); ?>,
    uploadVariant: <?php echo json_encode(isset($suploadscript) ? 'slider' : ($hasMultiUpload ? 'gallery' : null)); ?>,
    uploadMaxSizeMb: <?php echo (int) UPLOAD_SIZE_MB; ?>,
    intlTelUtilsUrl: <?php echo json_encode(ADMIN_ASSETS.'vendor/intl-tel-input/js/utils.js?v=29.2.3'); ?>,
    csrfName: <?php echo json_encode($this->config->item('csrf_protection') ? $this->security->get_csrf_token_name() : null); ?>,
    csrfHash: <?php echo json_encode($this->config->item('csrf_protection') ? $this->security->get_csrf_hash() : null); ?>
};
window.admin_url = window.AdminConfig.adminUrl;
window.base_url = window.AdminConfig.baseUrl;
window.controller = window.AdminConfig.controllerUrl;
window.contName = window.AdminConfig.controllerName;
</script>
<script src="<?php echo ADMIN_ASSETS; ?>vendor/bootstrap/bootstrap.bundle.min.js"></script>
<script src="<?php echo ADMIN_ASSETS; ?>vendor/select2/select2.min.js"></script>
<script src="<?php echo ADMIN_ASSETS; ?>vendor/jquery-validation/jquery.validate.min.js"></script>
<script src="<?php echo ADMIN_ASSETS; ?>vendor/jquery-validation/additional-methods.min.js"></script>
<script src="<?php echo ADMIN_ASSETS; ?>vendor/flatpickr/flatpickr.min.js"></script>
<script src="<?php echo ADMIN_ASSETS; ?>vendor/intl-tel-input/js/intlTelInput.min.js?v=29.2.3"></script>

<?php if (!empty($useIconPicker)) { ?>
<script src="<?php echo ADMIN_ASSETS; ?>vendor/vanilla-icon-picker/icon-picker.min.js?v=1.3.1"></script>
<script src="<?php echo ADMIN_ASSETS; ?>js/icon-picker.js?v=<?php echo (int) @filemtime(FCPATH.'assets/admin/js/icon-picker.js'); ?>"></script>
<?php } ?>

<?php if (!empty($useColorPicker)) { ?>
<script src="<?php echo ADMIN_ASSETS; ?>vendor/coloris/coloris.min.js"></script>
<script src="<?php echo ADMIN_ASSETS; ?>js/color-picker.js?v=<?php echo (int) @filemtime(FCPATH.'assets/admin/js/color-picker.js'); ?>"></script>
<?php } ?>

<?php if (!empty($useSweetAlert)) { ?>
<script src="<?php echo ADMIN_ASSETS; ?>vendor/sweetalert2/sweetalert2.all.min.js?v=<?php echo (int) @filemtime(FCPATH.'assets/admin/vendor/sweetalert2/sweetalert2.all.min.js'); ?>"></script>
<?php } ?>

<?php if (!empty($useContentSections) || !empty($useManageTranslations)) { ?>
<script src="<?php echo ADMIN_ASSETS; ?>js/manage-translations.js?v=<?php echo (int) @filemtime(FCPATH.'assets/admin/js/manage-translations.js'); ?>"></script>
<?php } ?>

<?php if (!empty($useContentSections)) { ?>
<script src="<?php echo ADMIN_ASSETS; ?>js/content-sections.js?v=<?php echo (int) @filemtime(FCPATH.'assets/admin/js/content-sections.js'); ?>"></script>
<?php } ?>

<?php if (!empty($useDropzone)) { ?>
<script src="<?php echo ADMIN_ASSETS; ?>vendor/dropzone/dropzone.min.js?v=<?php echo (int) @filemtime(FCPATH.'assets/admin/vendor/dropzone/dropzone.min.js'); ?>"></script>
<?php } ?>

<?php if (in_array($this->controller, array('admins', 'customer-reviews'), TRUE)) { ?>
<script src="<?php echo ADMIN_ASSETS; ?>vendor/cropperjs/cropper.min.js?v=<?php echo (int) @filemtime(FCPATH.'assets/admin/vendor/cropperjs/cropper.min.js'); ?>"></script>
<script src="<?php echo ADMIN_ASSETS; ?>js/avatar-editor.js?v=<?php echo (int) @filemtime(FCPATH.'assets/admin/js/avatar-editor.js'); ?>"></script>
<?php } ?>

<script src="<?php echo ADMIN_ASSETS; ?>js/file-upload.js?v=<?php echo (int) @filemtime(FCPATH.'assets/admin/js/file-upload.js'); ?>"></script>
<script src="<?php echo ADMIN_ASSETS; ?>js/star-rating.js?v=<?php echo (int) @filemtime(FCPATH.'assets/admin/js/star-rating.js'); ?>"></script>

<?php if (!empty($useGalleryUpload)) { ?>
<script src="<?php echo ADMIN_ASSETS; ?>js/gallery-upload.js?v=<?php echo (int) @filemtime(FCPATH.'assets/admin/js/gallery-upload.js'); ?>"></script>
<?php } ?>

<?php if (!empty($useRepeatableRows)) { ?>
<script src="<?php echo ADMIN_ASSETS; ?>js/repeatable-rows.js?v=<?php echo (int) @filemtime(FCPATH.'assets/admin/js/repeatable-rows.js'); ?>"></script>
<?php } ?>

<?php if (!empty($useAccordionValidation)) { ?>
<script src="<?php echo ADMIN_ASSETS; ?>js/accordion-validation.js?v=<?php echo (int) @filemtime(FCPATH.'assets/admin/js/accordion-validation.js'); ?>"></script>
<?php } ?>

<?php if (!empty($useWebsiteSettings)) { ?>
<script src="<?php echo ADMIN_ASSETS; ?>js/website-settings.js?v=<?php echo (int) @filemtime(FCPATH.'assets/admin/js/website-settings.js'); ?>"></script>
<?php } ?>

<?php if (!empty($useShortTagPicker)) { ?>
<script src="<?php echo ADMIN_ASSETS; ?>js/short-tag-picker.js?v=<?php echo (int) @filemtime(FCPATH.'assets/admin/js/short-tag-picker.js'); ?>"></script>
<?php } ?>

<?php if (!empty($useWhatsappTemplates)) { ?>
<script src="<?php echo ADMIN_ASSETS; ?>js/whatsapp-templates.js?v=<?php echo (int) @filemtime(FCPATH.'assets/admin/js/whatsapp-templates.js'); ?>"></script>
<?php } ?>

<?php if ($hasMultiUpload) { ?>
<script src="<?php echo ADMIN_ASSETS; ?>vendor/blueimp-file-upload/jquery.ui.widget.js"></script>
<script src="<?php echo ADMIN_ASSETS; ?>vendor/blueimp-file-upload/jquery.iframe-transport.js"></script>
<script src="<?php echo ADMIN_ASSETS; ?>vendor/blueimp-file-upload/jquery.fileupload.js"></script>
<script src="<?php echo ADMIN_ASSETS; ?>js/uploads.js"></script>
<?php } ?>

<script src="<?php echo ADMIN_ASSETS; ?>vendor/tablednd/jquery.tablednd.min.js"></script>
<script src="<?php echo ADMIN_ASSETS; ?>js/sortable-records.js?v=<?php echo (int) @filemtime(FCPATH.'assets/admin/js/sortable-records.js'); ?>"></script>

<?php if (!empty($useMenuManager) || !empty($useSortableJs)) { ?>
<script src="<?php echo ADMIN_ASSETS; ?>vendor/sortablejs/Sortable.min.js"></script>
<?php } ?>
<?php if (!empty($useMenuManager)) { ?>
<script src="<?php echo ADMIN_ASSETS; ?>js/menu-manager.js?v=<?php echo (int) @filemtime(FCPATH.'assets/admin/js/menu-manager.js'); ?>"></script>
<?php } ?>
<?php if (!empty($useSortableJs)) { ?>
<script src="<?php echo ADMIN_ASSETS; ?>js/selected-items-sort.js?v=<?php echo (int) @filemtime(FCPATH.'assets/admin/js/selected-items-sort.js'); ?>"></script>
<?php } ?>
<script src="<?php echo ADMIN_ASSETS; ?>vendor/jquery-mask/jquery.mask.min.js"></script>
<script src="<?php echo ADMIN_ASSETS; ?>js/admin.js?v=<?php echo (int) @filemtime(FCPATH.'assets/admin/js/admin.js'); ?>"></script>
<?php if (!empty($useUserSelect)) { ?>
<script src="<?php echo ADMIN_ASSETS; ?>js/user-select.js?v=<?php echo (int) @filemtime(FCPATH.'assets/admin/js/user-select.js'); ?>"></script>
<?php } ?>
<script src="<?php echo ADMIN_ASSETS; ?>js/custom.js?v=<?php echo (int) @filemtime(FCPATH.'assets/admin/js/custom.js'); ?>"></script>
<script src="<?php echo ADMIN_ASSETS; ?>js/records-listing.js?v=<?php echo (int) @filemtime(FCPATH.'assets/admin/js/records-listing.js'); ?>"></script>
<?php if (!empty($useDashboardCharts)) { ?>
<script src="<?php echo ADMIN_ASSETS; ?>vendor/chartjs/chart.umd.min.js?v=4.5.1"></script>
<script src="<?php echo ADMIN_ASSETS; ?>js/dashboard.js?v=<?php echo (int) @filemtime(FCPATH.'assets/admin/js/dashboard.js'); ?>"></script>
<?php } ?>
</body>
</html>
