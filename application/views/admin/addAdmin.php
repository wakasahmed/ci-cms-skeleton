<?php
$isEdit = ($alert === 'edit' && !empty($tbl_data[$this->pKey]));
$crumb = $isEdit ? 'Edit' : 'Add';
$recordID = $isEdit ? (int) $tbl_data[$this->pKey] : 0;
$action = base_url('manage/'.$this->controller.'/'.($isEdit ? 'editRecord/'.$recordID : 'addRecord'));
$value = function ($key) use ($tbl_data) { return isset($tbl_data[$key]) ? htmlspecialchars((string) $tbl_data[$key], ENT_QUOTES, 'UTF-8') : ''; };
$fullName = isset($tbl_data['full_name']) ? trim((string) $tbl_data['full_name']) : '';
$avatar = isset($tbl_data['avatar']) ? basename((string) $tbl_data['avatar']) : '';
$status = isset($tbl_data['status']) && $tbl_data['status'] === 'Disable' ? 'Disable' : 'Enable';
$isCurrent = $isEdit && $recordID === (int) $current_admin_id;
?>
<?php $this->load->view('admin/partials/breadcrumb', array('items' => array(array('label' => $this->moduleName, 'url' => ADMIN_URL.$this->controller), array('label' => $crumb.' '.$this->moduleNameSingular, 'active' => TRUE)))); ?>

<?php if (!empty($form_error)) { ?>
<div class="row alertrow"><div class="col-md-12"><div class="alert alert-danger alert-dismissible fade show" role="alert"><strong>Could not save the administrator.</strong> <?php echo htmlspecialchars($form_error, ENT_QUOTES, 'UTF-8'); ?><button type="button" class="btn-close alertBox crud-alert-close" data-bs-dismiss="alert" aria-label="Dismiss notification"></button></div></div></div>
<?php } ?>

<section class="admin-records-listing" aria-labelledby="administrator-form-title">
    <?php $this->load->view('admin/partials/module_header', array('title' => $this->moduleName, 'description' => 'Create an administrator account or update its identity, contact details, password, and access status.', 'id' => 'administrator-form-title')); ?>
    <div class="card admin-card">
        <div class="card-header"><h2 class="card-title mb-0"><?php echo $crumb; ?> <?php echo htmlspecialchars($this->moduleNameSingular, ENT_QUOTES, 'UTF-8'); ?></h2></div>
        <div class="card-body">
            <form id="administrators_form" name="administrators_form" method="post" action="<?php echo $action; ?>" enctype="multipart/form-data" class="validate" data-submit-lock>
                <?php if ($this->config->item('csrf_protection')) { ?><input type="hidden" name="<?php echo htmlspecialchars($this->security->get_csrf_token_name(), ENT_QUOTES, 'UTF-8'); ?>" value="<?php echo htmlspecialchars($this->security->get_csrf_hash(), ENT_QUOTES, 'UTF-8'); ?>"><?php } ?>
                <div class="row">
                    <div class="admin-field mb-3 col-md-6"><label class="form-label is-required" for="full_name">Full Name</label><input type="text" class="form-control" name="full_name" id="full_name" maxlength="100" value="<?php echo $value('full_name'); ?>" data-validate="required,maxlength[100]" required autocomplete="name" placeholder="John Smith"></div>
                    <div class="admin-field mb-3 col-md-6"><label class="form-label is-required" for="user_name">Username</label><input type="text" class="form-control" name="user_name" id="user_name" minlength="3" maxlength="100" pattern="[A-Za-z0-9._-]{3,100}" value="<?php echo $value('user_name'); ?>" data-validate="required,minlength[3],maxlength[100]" required autocomplete="username" placeholder="john.smith"><div class="form-text">Use letters, numbers, dots, underscores, or hyphens.</div></div>
                </div>
                <div class="row">
                    <div class="admin-field mb-3 col-md-6"><label class="form-label is-required" for="email">Email</label><input type="email" class="form-control" name="email" id="email" maxlength="100" value="<?php echo $value('email'); ?>" data-validate="required,email,maxlength[100]" required autocomplete="email" placeholder="john@example.com"></div>
                    <div class="admin-field mb-3 col-md-6"><label class="form-label" for="phone">Phone <span class="text-muted">(Optional)</span></label><input type="tel" class="form-control intl-phone" name="phone" id="phone" maxlength="50" value="<?php echo $value('phone'); ?>" data-initial-country="sa" data-validate="intlPhone,maxlength[50]" autocomplete="tel" placeholder="Phone number"></div>
                </div>

                <?php $this->load->view('admin/partials/avatar_upload', array(
                    'field_name' => 'avatar', 'label' => 'Avatar (Optional)',
                    'current_path' => $avatar !== '' ? 'assets/frontend/images/admins/'.$avatar : '',
                    'fallback' => $fullName, 'allow_remove' => TRUE, 'remove_name' => 'remove_avatar',
                    'crop_title' => 'Adjust administrator avatar', 'apply_label' => 'Use this avatar',
                )); ?>

                <div class="row">
                    <div class="admin-field mb-3 col-md-6"><label class="form-label<?php echo $isEdit ? '' : ' is-required'; ?>" for="pwd">Password<?php if ($isEdit) { ?> <span class="text-muted">(Optional)</span><?php } ?></label><input type="password" class="form-control" name="pwd" id="pwd" minlength="8" maxlength="72" data-validate="<?php echo $isEdit ? 'minlength[8],maxlength[72]' : 'required,minlength[8],maxlength[72]'; ?>" <?php echo $isEdit ? '' : 'required '; ?>autocomplete="new-password" placeholder="<?php echo $isEdit ? 'Leave blank to keep the current password' : 'At least 8 characters'; ?>"><div class="form-text"><?php echo $isEdit ? 'Enter a new password only when it should be changed.' : 'Use 8 to 72 characters.'; ?></div></div>
                    <div class="admin-field mb-3 col-md-6"><label class="form-label<?php echo $isEdit ? '' : ' is-required'; ?>" for="pwd2">Confirm Password</label><input type="password" class="form-control" name="pwd2" id="pwd2" minlength="8" maxlength="72" data-validate="<?php echo $isEdit ? 'equalTo[#pwd],minlength[8],maxlength[72]' : 'required,equalTo[#pwd],minlength[8],maxlength[72]'; ?>" <?php echo $isEdit ? '' : 'required '; ?>autocomplete="new-password" placeholder="Repeat the new password"></div>
                </div>

                <div class="admin-field mb-3">
                    <label class="form-label" for="status">Status</label>
                    <?php if ($isCurrent) { ?><input type="hidden" name="status" value="Enable"><select class="form-select" id="status" disabled aria-describedby="status-help"><option selected>Enable</option></select><div class="form-text" id="status-help">Your signed-in administrator account must remain enabled.</div><?php } else { ?><select class="form-select select2" name="status" id="status" data-minimum-results-for-search="-1"><option value="Enable" <?php echo $status === 'Enable' ? 'selected' : ''; ?>>Enable</option><option value="Disable" <?php echo $status === 'Disable' ? 'selected' : ''; ?>>Disable</option></select><?php } ?>
                </div>

                <div class="admin-field mb-3"<?php echo $isEdit ? ' id="notify_account_email_wrap" hidden' : ''; ?>>
                    <div class="form-check"><input type="hidden" name="notify_account_email" value="0"><input type="checkbox" name="notify_account_email" id="notify_account_email" value="1" class="form-check-input"><label class="form-check-label" for="notify_account_email">Email the account information with login details to this administrator</label></div>
                    <div class="form-text"><?php echo $isEdit ? 'Sends the username and new password by email.' : 'Sends the username and password by email so the administrator can sign in.'; ?></div>
                </div>

                <div class="admin-form-actions"><a class="btn btn-outline-secondary" href="<?php echo ADMIN_URL.$this->controller; ?>">Cancel</a><button type="submit" class="btn btn-primary" data-save-button><span data-save-label>Save <?php echo htmlspecialchars($this->moduleNameSingular, ENT_QUOTES, 'UTF-8'); ?></span></button></div>
            </form>
        </div>
    </div>
</section>

<?php if ($isEdit) { ?>
<script>
    (function () {
        var pwd = document.getElementById('pwd');
        var wrap = document.getElementById('notify_account_email_wrap');
        var notify = document.getElementById('notify_account_email');
        if (!pwd || !wrap || !notify) { return; }
        var sync = function () {
            var hasPassword = pwd.value !== '';
            wrap.hidden = !hasPassword;
            if (!hasPassword) { notify.checked = false; }
        };
        pwd.addEventListener('input', sync);
        sync();
    })();
</script>
<?php } ?>
