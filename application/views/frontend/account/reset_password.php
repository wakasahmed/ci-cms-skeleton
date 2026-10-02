<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/*
 * Choose a new password (/account/reset-password/{token}), reached from the
 * emailed link. Customer_account::resetPassword().
 *
 * $formToken, $status, $errors     see Account::renderForm()
 * $resetToken                      the link's token (posted back in the URL)
 */
$messages = array(
    'invalid' => 'Please check the highlighted fields.',
    'expired' => 'That took a little long. Please try again.',
);
$message = isset($messages[$status]) ? $messages[$status] : '';
?>
<main id="main">
    <?php $this->load->view('frontend/partials/page_hero', array('hero' => array(
        'crumbs' => array(
            array('label' => 'Home', 'url' => base_url()),
            array('label' => 'Choose a new password'),
        ),
        'label' => 'Your account',
        'heading' => 'Choose a new password',
        'lead' => 'You’ll then sign in with it. Devices where you chose “Remember me” are signed out.',
    ))); ?>

    <section class="pb-18 md:pb-22 lg:pb-26 bg-background text-foreground" aria-label="Choose a new password">
        <div class="mx-auto w-full max-w-[86rem] px-5 sm:px-8 lg:px-12">
            <div class="card-soft max-w-xl p-6 sm:p-10">
                <?php $this->load->view('frontend/partials/form_status', array('message' => $message, 'success' => FALSE)); ?>
                <form
                    method="post"
                    action="<?php echo html_escape(base_url('account/reset-password/'.rawurlencode($resetToken))); ?>"
                    novalidate
                    class="grid gap-6"
                    data-public-form
                >
                    <input type="hidden" name="form_token" value="<?php echo html_escape($formToken); ?>">
                    <?php $this->load->view('frontend/partials/form_field', array('field' => array(
                        'id' => 'reset-password',
                        'name' => 'password',
                        'label' => 'New password',
                        'type' => 'password',
                        'required' => TRUE,
                        'autocomplete' => 'new-password',
                        'minlength' => PASSWORD_MIN_LENGTH,
                        'maxlength' => 72,
                        'hint' => 'At least '.PASSWORD_MIN_LENGTH.' characters.',
                        'error' => isset($errors['password']) ? $errors['password'] : '',
                    ))); ?>
                    <?php $this->load->view('frontend/partials/form_field', array('field' => array(
                        'id' => 'reset-password-confirm',
                        'name' => 'password_confirm',
                        'label' => 'Repeat new password',
                        'type' => 'password',
                        'required' => TRUE,
                        'autocomplete' => 'new-password',
                        'maxlength' => 72,
                        'match' => 'reset-password',
                        'error' => isset($errors['password_confirm']) ? $errors['password_confirm'] : '',
                    ))); ?>
                    <div>
                        <button class="<?php echo html_escape(frontend_button_class('primary', 'h-13 w-full px-8 sm:w-auto')); ?>" type="submit" data-form-submit data-busy-label="Saving…">Save new password</button>
                    </div>
                </form>
            </div>
        </div>
    </section>
</main>
