<?php $this->load->view('admin/partials/breadcrumb', array('items' => array(array('label' => 'Account Settings', 'active' => TRUE)))); ?>

<?php if ($alert == "success") { ?>
<div class="row alertrow">
    <div class="col-md-12">
        <button class="btn-close alertBox" data-bs-dismiss="alert">x</button>
        <div class="alert alert-success"><strong>Success!</strong> Settings saved successfully.</div>
    </div>
</div>
<?php } if ($alert == "exist") { ?>
<div class="row alertrow">
    <div class="col-md-12">
        <button class="btn-close alertBox" data-bs-dismiss="alert">x</button>
        <div class="alert alert-danger"><strong>Error!!</strong> The email address you entered is already exist, please use different email address.</div>
    </div>
</div>
<?php } if ($alert == "error") { ?>
<div class="row alertrow">
    <div class="col-md-12">
        <button class="btn-close alertBox" data-bs-dismiss="alert">x</button>
        <div class="alert alert-danger"><strong>Error!!</strong> Error occurred while saving the record, please try again.</div>
    </div>
</div>
<?php } if ($alert == "perror") { ?>
<div class="row alertrow">
    <div class="col-md-12">
        <button class="btn-close alertBox" data-bs-dismiss="alert">x</button>
        <div class="alert alert-danger"><strong>Error!!</strong> Unable to change the password, please provide the correct current password.</div>
    </div>
</div>
<?php } ?>

<?php $this->load->view('admin/partials/module_header', array('title' => 'Account Settings')); ?>

<div class="card admin-card">
    <div class="card-body">

        <form role="form" method="post" id="adm_setting" name="adm_setting" method="post" action="<?php echo ADMIN_URL; ?>home/savesettings">

            <div class="admin-field mb-3">
                <label class="form-label">Name :</label>
                <input type="text" name="admin_name" id="admin_name" value="<?php echo $userdata['full_name']; ?>" placeholder="Enter your Full Name" class="form-control" data-validate="required" />
            </div>

            <div class="admin-field mb-3">
                <label class="form-label">Email :</label>
                <input type="text" class="form-control" name="admin_email" id="admin_email" value="<?php echo $userdata['email']; ?>" data-validate="required,email" placeholder="Enter your email address" />
            </div>

            <div class="admin-field mb-3">
                <label class="form-label">Current Password :</label>
                <input class="form-control" type="password" name="admin_current_pwd" id="admin_current_pwd" />
            </div>

            <div class="admin-field mb-3">
                <label class="form-label">New Password :</label>
                <input class="form-control" type="password" name="admin_new_pwd" id="admin_new_pwd" />
            </div>

            <div class="admin-field mb-3">
                <label class="form-label">Confirm New Password :</label>
                <input class="form-control" type="password" name="admin_new_pwd2" id="admin_new_pwd2" />
            </div>

            <div class="admin-form-actions">
                <button type="button" class="btn btn-outline-secondary" onclick="window.location='<?php echo ADMIN_URL; ?>'">Cancel</button>
                <button type="submit" class="btn btn-primary">Submit</button>
            </div>

        </form>

    </div>

</div>
