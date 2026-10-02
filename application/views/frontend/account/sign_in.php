<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/*
 * Sign in (/account/sign-in). Libraries: Customer_account::signIn(), js/form.js.
 *
 * $formToken, $status, $errors, $values, $notice   see Account::renderForm()
 * $recaptchaSiteKey, $recaptchaAction              reCAPTCHA Enterprise
 * $next                                            local page to return to ('' for the account)
 */
$messages = array(
    'invalid' => 'Please enter your email address and password.',
    'credentials' => 'That email and password don’t match an account.',
    'locked' => 'Too many attempts. Please wait 15 minutes, or reset your password.',
    'expired' => 'That took a little long. Please try again.',
    'recaptcha' => 'We couldn’t confirm you’re a person. Please try again.',
    'error' => 'Something went wrong. Please try again.',
);
$message = isset($messages[$status]) ? $messages[$status] : '';
$success = FALSE;
if ($message === '' && is_array($notice)) {
    $message = $notice['message'];
    $success = $notice['type'] === 'success';
}
$query = $next !== '' ? '?next='.rawurlencode($next) : '';
?>
<main id="main">
    <?php $this->load->view('frontend/partials/page_hero', array('hero' => array(
        'crumbs' => array(
            array('label' => 'Home', 'url' => base_url()),
            array('label' => 'Sign in'),
        ),
        'label' => 'Your account',
        'heading' => 'Welcome back',
        'lead' => 'Sign in to see your appointments, and to move or cancel them online.',
    ))); ?>

    <section class="pb-18 md:pb-22 lg:pb-26 bg-background text-foreground" aria-label="Sign in">
        <div class="mx-auto w-full max-w-[86rem] px-5 sm:px-8 lg:px-12">
            <div class="card-soft max-w-xl p-6 sm:p-10">
                <?php $this->load->view('frontend/partials/form_status', array('message' => $message, 'success' => $success)); ?>
                <form
                    method="post"
                    action="<?php echo html_escape(base_url('account/sign-in').$query); ?>"
                    novalidate
                    class="grid gap-6"
                    data-public-form
                    data-recaptcha-site-key="<?php echo html_escape($recaptchaSiteKey); ?>"
                    data-recaptcha-action="<?php echo html_escape($recaptchaAction); ?>"
                >
                    <input type="hidden" name="form_token" value="<?php echo html_escape($formToken); ?>">
                    <input type="hidden" name="recaptcha_token" value="" data-recaptcha-token>
                    <?php $this->load->view('frontend/partials/form_field', array('field' => array(
                        'id' => 'sign-in-email',
                        'name' => 'email',
                        'label' => 'Email',
                        'type' => 'email',
                        'value' => isset($values['email']) ? $values['email'] : '',
                        'required' => TRUE,
                        'autocomplete' => 'email',
                        'maxlength' => 190,
                        'error' => '',
                    ))); ?>
                    <?php $this->load->view('frontend/partials/form_field', array('field' => array(
                        'id' => 'sign-in-password',
                        'name' => 'password',
                        'label' => 'Password',
                        'type' => 'password',
                        'value' => '',
                        'required' => TRUE,
                        'autocomplete' => 'current-password',
                        'maxlength' => 72,
                        'error' => '',
                    ))); ?>
                    <div class="flex flex-wrap items-center justify-between gap-3">
                        <label class="inline-flex min-h-11 cursor-pointer items-center gap-3 text-sm text-foreground-soft" for="sign-in-remember">
                            <input
                                class="size-5 rounded border-border-strong accent-[var(--color-primary)]"
                                type="checkbox"
                                id="sign-in-remember"
                                name="remember"
                                value="1"
                                <?php echo !empty($values['remember']) ? 'checked' : ''; ?>
                            >
                            Remember me on this device
                        </label>
                        <a class="link-underline text-sm font-semibold text-primary-ink" href="<?php echo html_escape(base_url('account/forgot-password')); ?>">Forgotten your password?</a>
                    </div>
                    <div>
                        <button class="<?php echo html_escape(frontend_button_class('primary', 'h-13 w-full px-8 sm:w-auto')); ?>" type="submit" data-form-submit data-busy-label="Signing in…">Sign in</button>
                    </div>
                </form>
                <p class="mt-8 border-t border-border pt-6 text-sm text-foreground-soft">
                    New here?
                    <a class="link-underline font-semibold text-primary-ink" href="<?php echo html_escape(base_url('account/sign-up').$query); ?>">Create an account</a>
                    — or simply <a class="link-underline font-semibold text-primary-ink" href="<?php echo html_escape(base_url('book')); ?>">book as a guest</a>.
                </p>
            </div>
        </div>
    </section>
</main>
<?php if ($recaptchaSiteKey !== '') { ?>
    <script src="https://www.google.com/recaptcha/enterprise.js?render=<?php echo rawurlencode($recaptchaSiteKey); ?>" defer></script>
<?php } ?>
