<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/*
 * Create an account (/account/sign-up). Libraries: Customer_account::signUp(),
 * js/form.js. Accounts are optional; the page says so.
 *
 * $formToken, $status, $errors, $values, $notice   see Account::renderForm()
 * $recaptchaSiteKey, $recaptchaAction              reCAPTCHA Enterprise
 * $next                                            local page to return to
 */
$messages = array(
    'invalid' => 'Please check the highlighted fields.',
    'expired' => 'That took a little long. Please try again.',
    'recaptcha' => 'We couldn’t confirm you’re a person. Please try again.',
    'error' => 'Your account could not be created. Please try again.',
);
$message = isset($messages[$status]) ? $messages[$status] : '';
$old = function ($field) use ($values) {
    return isset($values[$field]) ? (string) $values[$field] : '';
};
$error = function ($field) use ($errors) {
    return isset($errors[$field]) ? (string) $errors[$field] : '';
};
$query = $next !== '' ? '?next='.rawurlencode($next) : '';
?>
<main id="main">
    <?php $this->load->view('frontend/partials/page_hero', array('hero' => array(
        'crumbs' => array(
            array('label' => 'Home', 'url' => base_url()),
            array('label' => 'Create an account'),
        ),
        'label' => 'Your account',
        'heading' => 'Create an account',
        'lead' => 'An account is optional. It fills in your details when you book, keeps your appointments in one place, and lets you move or cancel them online up to '.ACCOUNT_CHANGE_NOTICE_HOURS.' hours before.',
    ))); ?>

    <section class="pb-18 md:pb-22 lg:pb-26 bg-background text-foreground" aria-label="Create an account">
        <div class="mx-auto w-full max-w-[86rem] px-5 sm:px-8 lg:px-12">
            <div class="card-soft max-w-2xl p-6 sm:p-10">
                <?php $this->load->view('frontend/partials/form_status', array('message' => $message, 'success' => FALSE)); ?>
                <form
                    method="post"
                    action="<?php echo html_escape(base_url('account/sign-up').$query); ?>"
                    novalidate
                    class="grid gap-6 sm:grid-cols-2"
                    data-public-form
                    data-recaptcha-site-key="<?php echo html_escape($recaptchaSiteKey); ?>"
                    data-recaptcha-action="<?php echo html_escape($recaptchaAction); ?>"
                >
                    <input type="hidden" name="form_token" value="<?php echo html_escape($formToken); ?>">
                    <input type="hidden" name="recaptcha_token" value="" data-recaptcha-token>
                    <div class="sm:col-span-2">
                        <?php $this->load->view('frontend/partials/form_field', array('field' => array(
                            'id' => 'sign-up-name',
                            'name' => 'name',
                            'label' => 'Name',
                            'value' => $old('name'),
                            'required' => TRUE,
                            'autocomplete' => 'name',
                            'placeholder' => 'Anna Kowalska',
                            'maxlength' => 150,
                            'error' => $error('name'),
                        ))); ?>
                    </div>
                    <?php $this->load->view('frontend/partials/form_field', array('field' => array(
                        'id' => 'sign-up-email',
                        'name' => 'email',
                        'label' => 'Email',
                        'type' => 'email',
                        'value' => $old('email'),
                        'required' => TRUE,
                        'autocomplete' => 'email',
                        'placeholder' => 'anna@example.com',
                        'maxlength' => 190,
                        'error' => $error('email'),
                    ))); ?>
                    <?php $this->load->view('frontend/partials/form_field', array('field' => array(
                        'id' => 'sign-up-phone',
                        'name' => 'phone',
                        'label' => 'Phone',
                        'type' => 'tel',
                        'value' => $old('phone'),
                        'autocomplete' => 'tel',
                        'placeholder' => '+48 500 000 000',
                        'maxlength' => 40,
                        'hint' => 'Optional — filled in when you book.',
                        'error' => $error('phone'),
                    ))); ?>
                    <?php $this->load->view('frontend/partials/form_field', array('field' => array(
                        'id' => 'sign-up-password',
                        'name' => 'password',
                        'label' => 'Password',
                        'type' => 'password',
                        'required' => TRUE,
                        'autocomplete' => 'new-password',
                        'minlength' => PASSWORD_MIN_LENGTH,
                        'maxlength' => 72,
                        'hint' => 'At least '.PASSWORD_MIN_LENGTH.' characters.',
                        'error' => $error('password'),
                    ))); ?>
                    <?php $this->load->view('frontend/partials/form_field', array('field' => array(
                        'id' => 'sign-up-password-confirm',
                        'name' => 'password_confirm',
                        'label' => 'Repeat password',
                        'type' => 'password',
                        'required' => TRUE,
                        'autocomplete' => 'new-password',
                        'maxlength' => 72,
                        'match' => 'sign-up-password',
                        'error' => $error('password_confirm'),
                    ))); ?>
                    <div class="sm:col-span-2">
                        <p class="mb-5 text-sm text-muted-foreground">
                            We use your details to manage your appointments, as described in our
                            <a class="link-underline font-semibold text-primary-ink" href="<?php echo html_escape(base_url('privacy-policy')); ?>">privacy policy</a>.
                        </p>
                        <button class="<?php echo html_escape(frontend_button_class('primary', 'h-13 w-full px-8 sm:w-auto')); ?>" type="submit" data-form-submit data-busy-label="Creating your account…">Create account</button>
                    </div>
                </form>
                <p class="mt-8 border-t border-border pt-6 text-sm text-foreground-soft">
                    Already have an account?
                    <a class="link-underline font-semibold text-primary-ink" href="<?php echo html_escape(base_url('account/sign-in').$query); ?>">Sign in</a>
                </p>
            </div>
        </div>
    </section>
</main>
<?php if ($recaptchaSiteKey !== '') { ?>
    <script src="https://www.google.com/recaptcha/enterprise.js?render=<?php echo rawurlencode($recaptchaSiteKey); ?>" defer></script>
<?php } ?>
