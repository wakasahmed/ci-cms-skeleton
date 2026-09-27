<div class="modal fade" id="addBlogCatModal" tabindex="-1" aria-labelledby="addBlogCatModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h2 class="modal-title fs-5" id="addBlogCatModalLabel">Add New Category</h2>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="admin-field mb-3">
                    <label class="form-label is-required" for="blog_cat_ajax">Category Name</label>
                    <input type="text" name="blog_cat_ajax" id="blog_cat_ajax" class="form-control" maxlength="255" autocomplete="off" placeholder="Travel tips">
                </div>
                <div class="admin-field mb-3">
                    <label class="form-label is-required" for="blog_cat_slug_ajax">Category URL Slug</label>
                    <div class="input-group" dir="ltr"><span class="input-group-text">/blog/category/</span><input type="text" name="blog_cat_slug_ajax" id="blog_cat_slug_ajax" class="form-control slug" maxlength="255" autocomplete="off" placeholder="travel-tips"></div>
                </div>
                <div id="catErrorMsg" class="alert alert-danger py-2 mb-0 d-none" role="alert" aria-live="assertive"></div>
            </div>
            <div class="modal-footer">
                <span class="spinner-border spinner-border-sm text-primary d-none" id="ajax_loader_cat" role="status"><span class="visually-hidden">Saving...</span></span>
                <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-primary" id="bCatSubmit">Add Category</button>
            </div>
        </div>
    </div>
</div>

<?php
$confirmationModals = array(
    array('id' => 'dupModal', 'label' => 'dupModalLabel', 'title' => 'Duplicate record', 'body' => 'Create a copy of <strong id="recname"></strong>?', 'confirmId' => 'duplicateLink', 'button' => 'Duplicate', 'class' => 'btn-primary'),
    array('id' => 'statusModal', 'label' => 'statusModalLabel', 'title' => 'Change status', 'body' => 'Change this record status to <strong id="modStatus"></strong>?', 'confirmId' => 'statusLink', 'button' => 'Change status', 'class' => 'btn-primary'),
    array('id' => 'deleteModal', 'label' => 'deleteModalLabel', 'title' => 'Delete record', 'body' => 'This record will be permanently deleted. This action cannot be undone.', 'confirmId' => 'deleteLink', 'button' => 'Delete record', 'class' => 'btn-danger'),
    array('id' => 'delAllModal', 'label' => 'delAllModalLabel', 'title' => 'Delete selected records', 'body' => 'All selected records will be permanently deleted. This action cannot be undone.', 'confirmId' => 'delAllSubmit', 'button' => 'Delete selected', 'class' => 'btn-danger'),
    array('id' => 'removeImageModal', 'label' => 'removeImageModalLabel', 'title' => 'Remove file', 'body' => 'This file will be permanently removed. This action cannot be undone.', 'confirmId' => 'removeImageLink', 'button' => 'Remove File', 'class' => 'btn-danger'),
);
foreach ($confirmationModals as $modal) {
?>
<div class="modal fade" id="<?php echo $modal['id']; ?>" tabindex="-1" aria-labelledby="<?php echo $modal['label']; ?>" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h2 class="modal-title fs-5" id="<?php echo $modal['label']; ?>"><?php echo $modal['title']; ?></h2>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body"><p class="mb-0"><?php echo $modal['body']; ?></p></div>
            <div class="modal-footer">
                <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="button" id="<?php echo $modal['confirmId']; ?>" class="btn <?php echo $modal['class']; ?>"><?php echo $modal['button']; ?></button>
            </div>
        </div>
    </div>
</div>
<?php } ?>

<div class="modal fade" id="adminImageModal" tabindex="-1" aria-labelledby="adminImageModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-xl modal-dialog-centered">
        <div class="modal-content admin-lightbox-content">
            <div class="modal-header">
                <h2 class="modal-title fs-6" id="adminImageModalLabel">Image preview</h2>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body text-center"><img src="" alt="" id="adminImagePreview" class="admin-lightbox-image"></div>
        </div>
    </div>
</div>
