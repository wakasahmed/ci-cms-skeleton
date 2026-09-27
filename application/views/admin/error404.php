<main class="login-container" id="admin-main-content">
    <div class="login-header">
        <span class="logo">
            <span class="admin-brand-mark"><i class="bi bi-signpost-2 text-white" aria-hidden="true"></i></span>
        </span>
        <h1 class="h4 mb-2">404 &mdash; Page Not Found</h1>
        <p class="description">The admin page you requested does not exist or may have been moved.</p>
    </div>
    <div class="login-form">
        <?php if (!empty($isLoggedIn)) { ?>
        <a href="<?php echo ADMIN_URL; ?>" class="btn btn-primary w-100">
            <i class="bi bi-speedometer2" aria-hidden="true"></i> Go to Dashboard
        </a>
        <?php } else { ?>
        <a href="<?php echo ADMIN_URL; ?>login" class="btn btn-primary w-100">
            <i class="bi bi-box-arrow-in-right" aria-hidden="true"></i> Go to Login
        </a>
        <?php } ?>
    </div>
</main>
