<main class="login-container" id="admin-main-content">
    <div class="login-header">
        <a href="<?php echo ADMIN_URL; ?>" class="logo" aria-label="<?php echo htmlspecialchars(PROJECT_TITLE, ENT_QUOTES, 'UTF-8'); ?> administration">
            <?php if (file_exists($logo)) { ?>
            <img src="<?php echo image_thumb_src($logo, 0, 180, 'webp'); ?>" alt="<?php echo htmlspecialchars(PROJECT_TITLE, ENT_QUOTES, 'UTF-8'); ?>">
            <?php } else { ?>
            <span class="admin-brand-mark"><i class="bi bi-compass" aria-hidden="true"></i></span>
            <?php } ?>
        </a>
        <h1 class="h4 mb-2">Sign in to the CMS</h1>
        <p class="description">Enter your administrator credentials to continue.</p>
    </div>
    <div class="login-form">
        <?php if ($flash_message !== '') { ?>
        <div class="alert alert-<?php echo $flash_type === 'success' ? 'success' : 'danger'; ?>" role="alert">
            <?php echo htmlspecialchars($flash_message, ENT_QUOTES, 'UTF-8'); ?>
        </div>
        <?php } ?>
        <form method="post" id="form_login" class="validate" action="<?php echo ADMIN_URL; ?>login/auth" data-submit-lock data-submit-requires="#username,#password">
            <input type="hidden" name="form_token" value="<?php echo htmlspecialchars($form_token, ENT_QUOTES, 'UTF-8'); ?>">
            <div class="admin-field mb-3">
                <label class="form-label" for="username">Username</label>
                <div class="input-group">
                    <span class="input-group-text"><i class="bi bi-person" aria-hidden="true"></i></span>
                    <input type="text" class="form-control" name="username" id="username" value="<?php echo htmlspecialchars($old_username, ENT_QUOTES, 'UTF-8'); ?>" autocomplete="username" maxlength="100" data-validate="required,maxlength[100]" required autofocus>
                </div>
            </div>
            <div class="admin-field mb-3">
                <label class="form-label" for="password">Password</label>
                <div class="input-group">
                    <span class="input-group-text"><i class="bi bi-key" aria-hidden="true"></i></span>
                    <input type="password" class="form-control" name="password" id="password" autocomplete="current-password" maxlength="72" data-validate="required,maxlength[72]" required>
                </div>
            </div>
            <div class="form-check mb-3">
                <input type="checkbox" class="form-check-input" name="remember_me" id="remember_me" value="1"<?php echo $old_remember_me ? ' checked' : ''; ?>>
                <label class="form-check-label" for="remember_me">Remember me for 30 days</label>
            </div>
            <button type="submit" class="btn btn-primary w-100" disabled aria-disabled="true"><i class="bi bi-box-arrow-in-right" aria-hidden="true"></i> Sign in</button>
            <a class="btn btn-link w-100 mt-2" href="<?php echo ADMIN_URL; ?>login/forgot">Forgot your password?</a>
        </form>
    </div>
</main>
