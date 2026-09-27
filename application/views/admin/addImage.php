<?php
$imageAccept = upload_accept_types('jpg|jpeg|png');
$action = base_url('manage/'.$this->controller).'/';
if (isset($tbl_data) && count($tbl_data) > 0) {
    $crumb = "Edit";
    $action .= "editRecord/".$cat_id.'/'.$tbl_data[$this->pKey];
} else {
    $crumb = "Add";
    $action .= "addRecord/".$cat_id;
}
?>

<?php $this->load->view('admin/partials/breadcrumb', array('items' => array(array('label' => 'Rooms', 'url' => ADMIN_URL.'portfolio'), array('label' => $catData['cat_name'].' '.$this->moduleName, 'url' => ADMIN_URL.$this->controller.'/index/'.$cat_id), array('label' => $crumb.' '.rtrim($this->moduleName, 's'), 'active' => TRUE)))); ?>

<?php $this->load->view('admin/partials/module_header', array('title' => $crumb.' '.rtrim($this->moduleName, 's'))); ?>

<div class="card admin-card">
    <div class="card-body">
        <form id="<?php echo $this->controller; ?>_form" name="<?php echo $this->controller; ?>_form" method="post" action="<?php echo $action; ?>" enctype="multipart/form-data" class="validate">
            <div class="admin-field mb-3">
                <label class="form-label">Name :</label>
                <input type="text" name="image_name" id="image_name" maxlength="100" value="<?php echo (isset($tbl_data['image_name'])) ? $tbl_data['image_name'] : ''; ?>" class="form-control" data-validate="required" placeholder="" />
            </div>

            <div class="admin-field mb-3 hidden">
                <label class="form-label">Description :</label>

                <textarea rows="3" style="resize:none" name="image_desc" id="image_desc" class="form-control" placeholder=""><?php echo (isset($tbl_data['image_desc'])) ? $tbl_data['image_desc'] : ''; ?></textarea>
            </div>
            <div class="admin-field mb-3">
                <label class="form-label">Select Image :</label>

                <input accept="<?php echo htmlspecialchars($imageAccept, ENT_QUOTES, 'UTF-8'); ?>" <?php echo (isset($tbl_data) && $tbl_data['image_image'] != "" && file_exists('./assets/frontend/images/'.$this->controller.'/'.$tbl_data['image_image'])) ? '' : 'class="required"'; ?> type="file" name="uploadfile" id="uploadfile" />
            </div>

            <div class="admin-field mb-3">
                <label class="form-label"><?php if (isset($tbl_data)) { ?>
                    <?php echo image_thumb(
                        './assets/frontend/images/'.$this->controller.'/'.$tbl_data['image_image'],
                        0, 100, 'webp', 'square', TRUE,
                        array('alt' => 'Current image', 'placeholder' => TRUE)
                    ); ?><br/><br/>
                <?php } ?></label><br/><strong>Max Size:</strong> 50 Mb. <strong>Image Type:</strong> JPEG / PNG <br/>
            </div>

            <div class="admin-field mb-3">
                <label class="form-label">Status :</label>

                <select class="form-control" name="image_status" id="image_status">
                    <option value="Enable" <?php echo (isset($tbl_data['image_status']) && $tbl_data['image_status'] == "Enable") ? ' selected="selected"' : ''; ?>>Enable</option>
                    <option value="Disable" <?php echo (isset($tbl_data['image_status']) && $tbl_data['image_status'] == "Disable") ? ' selected="selected"' : ''; ?>>Disable</option>
                </select>
            </div>
            <div class="admin-form-actions">
                <button type="button" class="btn btn-outline-secondary" onclick="window.location='<?php echo ADMIN_URL.$this->controller.'/index/'.$cat_id; ?>'">Cancel</button>
                <button type="submit" class="btn btn-primary">Submit</button>
            </div>
        </form>
    </div>
</div>
