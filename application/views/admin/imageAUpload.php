<?php
$imageAccept = upload_accept_types('jpg|jpeg|png');
$this->load->view('admin/partials/breadcrumb', array('items' => array(array('label' => 'Architects', 'url' => ADMIN_URL.'architects'), array('label' => $catData['archi_name'].' '. $this->moduleName, 'url' => ADMIN_URL.$this->controller.'/index/'.$archi_id), array('label' => 'Upload '. $this->moduleName, 'active' => TRUE))));
?>






<?php $this->load->view('admin/partials/module_header', array('title' => 'Upload '.$this->moduleName)); ?>


<div class="card admin-card">
    <form id="upload" method="post" action="<?php echo ADMIN_URL.$this->controller.'/do_upload';?>" enctype="multipart/form-data">
        <div id="drop">
            Drop Here (Only JPG/JPEG/PNG)

            <a>Browse</a>
            <input type="file" name="upl" multiple accept="<?php echo htmlspecialchars($imageAccept, ENT_QUOTES, 'UTF-8'); ?>"/>
        </div>

        <ul>
            <!-- The file uploads will be shown here -->
        </ul>

    </form>

    <div class="card-body">
        <form  id="<?php echo $this->controller;?>_form" class="admin-form-horizontal admin-form-bordered validate" name="<?php echo $this->controller;?>_form" method="post" action="<?php echo ADMIN_URL.$this->controller.'/saveimages/'.$archi_id;?>" enctype="multipart/form-data" >
            <input type="hidden" autocomplete="off" name="totalImages" id="totalImages" value="0" />

            <br clear="all" />

            <div class="admin-form-actions">
                <button type="button" class="btn btn-outline-secondary" onclick="window.location='<?php echo ADMIN_URL.$this->controller.'/index/'.$archi_id;?>'">Cancel</button>
                <button type="submit"   class="btn btn-primary">Submit</button>
            </div>
        </form>
    </div>
</div>
