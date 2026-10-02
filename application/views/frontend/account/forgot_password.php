<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/*
 * Forgotten password (/account/forgot-password). The answer is the same
 * whether or not the address has an account. Customer_account::forgot().
 *
 * $formToken, $status, $errors, $values, $notice   see Account::renderForm()
 * $recaptchaSiteKey, $recaptchaAction              reCAPTCHA Enterprise
 */
$minutes = (int) (PASSWORD_RESET_TTL / 60);
$messages = array(
    'success' => 'If an account uses that email, we’ve sent it a link to choose a new password. The link works for '.$minutes.' minutes.',
    'invalid' => 'That email doesn’t look quite right.',
    'expired' => 'That took a little long. Please try again.',
    'recaptcha' => 'We couldn’t confirm you’re a person. Please try again.',
);
$message = isset($messages[$status]) ? $messages[$status] : '';
$success = $status === 'success';
if ($message === '' && is_array($notice)) {
    $message = $notice['message'];
    $success = $notice['type'] === 'success';
}
?>
<main id="main">
    <?php $this->load->view('frontend/partials/page_hero', array('hero' => array(
        'crumbs' => array(
            array('label' => 'Home', 'url' => base_url()),
            array('label' => 'Sign in', 'url' => base_url('account/sign-in')),
            array('label' => 'Forgotten password'),
        ),
        'label' => 'Your account',
        'heading' => 'Forgotten your password?',
        'lead' => 'Enter the email address of your account and we’ll send you a link to choose a new password.',
    ))); ?>

    <section class="pb-18 md:pb-22 lg:pb-26 bg-background text-foreground" aria-label="Reset your password">
        <div class="mx-auto w-full max-w-[86rem] px-5 sm:px-8 lg:px-12">
            <div class="card-soft max-w-xl p-6 sm:p-10">
                <?php $this->load->view('frontend/partials/form_status', array('message' => $message, 'success' => $success)); ?>
                <form
                    method="post"
                    action="<?php echo html_escape(base_url('account/forgot-password')); ?>"
                    novalidate
                    class="grid gap-6"
                    data-public-form
                    data-recaptcha-site-key="<?php echo html_escape($recaptchaSiteKey); ?>"
                    data-recaptcha-action="<?php echo html_escape($recaptchaAction); ?>"
                >
                    <input type="hidden" name="form_token" value="<?php echo html_escape($formToken); ?>">
                    <input type="hidden" name="recaptcha_token" value="" data-recaptcha-token>
                    <?php $this->load->view('frontend/partials/form_field', array('field' => array(
                        'id' => 'forgot-email',
                        'name' => 'email',
                        'label' => 'Email',
                        'type' => 'email',
                        'value' => $success ? '' : (isset($values['email']) ? $values['email'] : ''),
                        'required' => TRUE,
                        'autocomplete' => 'email',
                        'maxlength' => 190,
                        'error' => isset($errors['email']) ? $errors['email'] : '',
                    ))); ?>
                    <div>
                        <button class="<?php echo html_escape(frontend_button_class('primary', 'h-13 w-full px-8 sm:w-auto')); ?>" type="submit" data-form-submit data-busy-label="Sending…">Send the link</button>
                    </div>
                </form>
                <p class="mt-8 border-t border-border pt-6 text-sm text-foreground-soft">
                    Remembered it?
                    <a class="link-underline font-semibold text-primary-ink" href="<?php echo html_escape(base_url('account/sign-in')); ?>">Sign in</a>
                </p>
            </div>
        </div>
    </section>
</main>
<?php if ($recaptchaSiteKey !== '') { ?>
    <script src="https://www.google.com/recaptcha/enterprise.js?render=<?php echo rawurlencode($recaptchaSiteKey); ?>" defer></script>
<?php } ?>
