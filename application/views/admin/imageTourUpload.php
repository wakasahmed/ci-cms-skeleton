<?php $this->load->view('admin/partials/breadcrumb', array('items' => array(array('label' => 'Tours', 'url' => ADMIN_URL.'tours'), array('label' => $tourData['tour_name'].' Images', 'url' => ADMIN_URL.$this->controller.'/index/'.$image_tour_id), array('label' => 'Upload Multiple Images', 'active' => TRUE)))); ?>
<section class="admin-records-listing tour-images-upload" aria-labelledby="tour-images-upload-title">
    <?php $this->load->view('admin/partials/module_header', array('title' => 'Upload Multiple Images', 'description' => 'Drag images here or browse to upload several images at once.', 'id' => 'tour-images-upload-title')); ?>
    <div class="card admin-card"><div class="card-body">
        <form id="tour-images-dropzone" class="dropzone" action="<?php echo ADMIN_URL.$this->controller.'/do_upload'; ?>" method="post" enctype="multipart/form-data" novalidate>
            <?php if ($this->config->item('csrf_protection')) { ?><input type="hidden" name="<?php echo htmlspecialchars($this->security->get_csrf_token_name(), ENT_QUOTES, 'UTF-8'); ?>" value="<?php echo htmlspecialchars($this->security->get_csrf_hash(), ENT_QUOTES, 'UTF-8'); ?>"><?php } ?>
            <div class="dz-message"><i class="bi bi-cloud-arrow-up" aria-hidden="true"></i><strong>Drop images here or click to browse</strong><span>JPG, JPEG, PNG, GIF, or WebP. Maximum size: <?php echo (int) UPLOAD_SIZE_MB; ?> MB per image.</span></div>
        </form>
        <form id="tour-images-save-form" method="post" action="<?php echo ADMIN_URL.$this->controller.'/saveimages/'.$image_tour_id; ?>" class="validate" novalidate data-submit-lock>
            <input type="hidden" name="totalImages" id="totalImages" value="0">
            <?php if ($this->config->item('csrf_protection')) { ?><input type="hidden" name="<?php echo htmlspecialchars($this->security->get_csrf_token_name(), ENT_QUOTES, 'UTF-8'); ?>" value="<?php echo htmlspecialchars($this->security->get_csrf_hash(), ENT_QUOTES, 'UTF-8'); ?>"><?php } ?>
            <div id="tour-images-details-heading" class="admin-section-heading mt-4" hidden>
                <div>
                    <h2 class="h5 mb-1">Image Details</h2>
                    <p class="mb-0">Enter the English and Arabic name for each uploaded image before saving.</p>
                </div>
            </div>
            <div id="tour-images-uploaded-list" class="tour-images-uploaded-list" aria-live="polite"></div>
            <div class="admin-form-actions"><a class="btn btn-outline-secondary" href="<?php echo ADMIN_URL.$this->controller.'/index/'.$image_tour_id; ?>">Cancel</a><button type="submit" class="btn btn-primary" id="save-uploaded-images" disabled><span data-save-label>Save Images</span></button></div>
        </form>
    </div></div>
</section>
