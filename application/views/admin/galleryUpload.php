<?php $this->load->view('admin/partials/breadcrumb', array(
    'items' => array(
        array('label' => $this->moduleName, 'url' => ADMIN_URL.$this->controller),
        array('label' => 'Upload Several', 'active' => TRUE),
    ),
)); ?>

<section class="admin-records-listing" aria-labelledby="gallery-upload-title">
    <?php $this->load->view('admin/partials/module_header', array(
        'title' => 'Upload Gallery Images',
        'description' => 'Each photo becomes a gallery image, captioned from its file name. Edit the captions afterwards so they describe each photo.',
        'id' => 'gallery-upload-title',
    )); ?>

    <div class="card admin-card">
        <div class="card-header">
            <h2 class="card-title mb-0">1. Choose the details shared by these photos</h2>
        </div>
        <div class="card-body">
            <div class="row" id="gallery-upload-options">
                <div class="admin-field mb-3 col-md-4">
                    <label class="form-label" for="upload_category_id">Category</label>
                    <select class="form-select select2" name="image_category_id" id="upload_category_id" data-minimum-results-for-search="-1">
                        <option value="">Uncategorised</option>
                        <?php foreach ($categories as $categoryId => $category) { ?>
                            <option value="<?php echo (int) $categoryId; ?>"><?php echo htmlspecialchars($category['category_name'], ENT_QUOTES, 'UTF-8'); ?></option>
                        <?php } ?>
                    </select>
                </div>
                <div class="admin-field mb-3 col-md-4">
                    <label class="form-label" for="upload_service_id">Related Service</label>
                    <select class="form-select select2" name="image_service_id" id="upload_service_id">
                        <option value="">None</option>
                        <?php foreach ($services as $serviceId => $service) { ?>
                            <option value="<?php echo (int) $serviceId; ?>"><?php echo htmlspecialchars($service['service_name'], ENT_QUOTES, 'UTF-8'); ?></option>
                        <?php } ?>
                    </select>
                </div>
                <div class="admin-field mb-3 col-md-4">
                    <label class="form-label" for="upload_status">Status</label>
                    <select class="form-select select2" name="image_status" id="upload_status" data-minimum-results-for-search="-1">
                        <option value="Enable">Enable</option>
                        <option value="Disable">Disable</option>
                    </select>
                </div>
            </div>
        </div>
    </div>

    <div class="card admin-card mt-4">
        <div class="card-header">
            <h2 class="card-title mb-0">2. Add the photos</h2>
        </div>
        <div class="card-body">
            <form
                id="gallery-dropzone"
                class="dropzone"
                action="<?php echo base_url('manage/'.$this->controller.'/do_upload'); ?>"
                method="post"
                enctype="multipart/form-data"
                data-options-container="#gallery-upload-options"
                novalidate
            >
                <?php if ($this->config->item('csrf_protection')) { ?>
                    <input type="hidden" name="<?php echo htmlspecialchars($this->security->get_csrf_token_name(), ENT_QUOTES, 'UTF-8'); ?>" value="<?php echo htmlspecialchars($this->security->get_csrf_hash(), ENT_QUOTES, 'UTF-8'); ?>">
                <?php } ?>
                <div class="dz-message">
                    <i class="bi bi-cloud-arrow-up fs-2 d-block mb-2" aria-hidden="true"></i>
                    Drop photos here or click to choose them.
                    <span class="d-block form-text">JPG, PNG or WebP, up to <?php echo (int) UPLOAD_SIZE_MB; ?> MB each.</span>
                </div>
            </form>
            <p class="form-text mt-3 mb-0" id="gallery-upload-summary" aria-live="polite"></p>
        </div>
    </div>

    <div class="admin-form-actions">
        <a class="btn btn-primary" href="<?php echo ADMIN_URL.$this->controller; ?>">Done</a>
    </div>
</section>
