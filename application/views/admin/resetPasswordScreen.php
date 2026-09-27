<main class="login-container" id="admin-main-content">
    <div class="login-header">
        <a href="<?php echo ADMIN_URL; ?>" class="logo" aria-label="Return to administration sign in">
            <?php if (file_exists($logo)) { ?>
            <img src="<?php echo image_thumb_src($logo, 0, 180, 'webp'); ?>" alt="<?php echo htmlspecialchars(PROJECT_TITLE, ENT_QUOTES, 'UTF-8'); ?>">
            <?php } ?>
        </a>
        <h1 class="h4 mb-2">Choose a new password</h1>
        <p class="description">Enter and confirm a new password for your administrator account.</p>
    </div>
    <div class="login-form">
        <?php if ($flash_message !== '') { ?>
        <div class="alert alert-<?php echo $flash_type === 'success' ? 'success' : 'danger'; ?>" role="alert">
            <?php echo htmlspecialchars($flash_message, ENT_QUOTES, 'UTF-8'); ?>
        </div>
        <?php } ?>
        <form id="resetPassword" action="<?php echo ADMIN_URL; ?>login/resetpwd" method="post" class="validate" data-submit-lock data-submit-requires="#new_password,#confirm_password">
            <input type="hidden" name="form_token" value="<?php echo htmlspecialchars($form_token, ENT_QUOTES, 'UTF-8'); ?>">
            <input type="hidden" name="reset_token" value="<?php echo htmlspecialchars($reset_token, ENT_QUOTES, 'UTF-8'); ?>">
            <div class="admin-field mb-3">
                <label class="form-label" for="new_password">New password</label>
                <div class="input-group">
                    <span class="input-group-text"><i class="bi bi-key" aria-hidden="true"></i></span>
                    <input type="password" class="form-control" name="new_password" id="new_password" autocomplete="new-password" minlength="8" maxlength="72" data-validate="required,minlength[8],maxlength[72]" required autofocus>
                </div>
            </div>
            <div class="admin-field mb-3">
                <label class="form-label" for="confirm_password">Confirm new password</label>
                <div class="input-group">
                    <span class="input-group-text"><i class="bi bi-key-fill" aria-hidden="true"></i></span>
                    <input type="password" class="form-control" name="confirm_password" id="confirm_password" autocomplete="new-password" minlength="8" maxlength="72" data-validate="required,minlength[8],maxlength[72],equalTo[#new_password]" required>
                </div>
            </div>
            <button type="submit" class="btn btn-primary w-100" disabled aria-disabled="true">Change password</button>
            <a href="<?php echo ADMIN_URL; ?>" class="btn btn-link w-100 mt-2">Back to sign in</a>
        </form>
    </div>
</main>
