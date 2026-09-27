<main class="login-container" id="admin-main-content">
    <div class="login-header">
        <a href="<?php echo ADMIN_URL; ?>" class="logo" aria-label="Return to administration sign in">
            <?php if (file_exists($logo)) { ?>
            <img src="<?php echo image_thumb_src($logo, 0, 180, 'webp'); ?>" alt="<?php echo htmlspecialchars(PROJECT_TITLE, ENT_QUOTES, 'UTF-8'); ?>">
            <?php } ?>
        </a>
        <h1 class="h4 mb-2">Reset your password</h1>
        <p class="description">Enter your administrator username or email address and we will send a secure reset link.</p>
    </div>
    <div class="login-form">
        <?php if ($flash_message !== '') { ?>
        <div class="alert alert-<?php echo $flash_type === 'success' ? 'success' : 'danger'; ?>" role="alert">
            <?php echo htmlspecialchars($flash_message, ENT_QUOTES, 'UTF-8'); ?>
        </div>
        <?php } ?>
        <form id="forgotPass" name="forgotPass" action="<?php echo ADMIN_URL; ?>login/forgotpwd" method="post" class="validate" data-submit-lock data-submit-requires="#identifier">
            <input type="hidden" name="form_token" value="<?php echo htmlspecialchars($form_token, ENT_QUOTES, 'UTF-8'); ?>">
            <div class="admin-field mb-3">
                <label class="form-label" for="identifier">Username or email address</label>
                <div class="input-group">
                    <span class="input-group-text"><i class="bi bi-person" aria-hidden="true"></i></span>
                    <input type="text" data-validate="required,maxlength[100]" class="form-control" name="identifier" id="identifier" value="<?php echo htmlspecialchars($old_reset_identifier, ENT_QUOTES, 'UTF-8'); ?>" autocomplete="username" maxlength="100" required autofocus>
                </div>
            </div>
            <button type="submit" name="forgot_submit" id="forgot_submit" class="btn btn-primary w-100" disabled aria-disabled="true">Send reset link</button>
            <a href="<?php echo ADMIN_URL; ?>" class="btn btn-outline-secondary w-100 mt-2">Back to sign in</a>
        </form>
    </div>
</main>
