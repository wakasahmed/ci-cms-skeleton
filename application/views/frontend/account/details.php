<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/*
 * Your details (/account/details): name, email and phone
 * (Customer_account::updateDetails(); a new email needs the current password
 * and a new confirmation), and the password (changePassword(), posted to
 * /account/password).
 *
 * $customer                                         the signed-in customer
 * $formToken, $status, $errors, $values, $notice    the details form (Account::renderForm())
 * $passwordToken, $passwordStatus, $passwordErrors  the password form
 * $signOutToken                                     the account navigation's sign-out form
 */
$messages = array(
    'invalid' => 'Please check the highlighted fields.',
    'expired' => 'That took a little long. Please try again.',
    'error' => 'Your details could not be saved. Please try again.',
);
$message = isset($messages[$status]) ? $messages[$status] : '';
$success = FALSE;
if ($message === '' && is_array($notice)) {
    $message = $notice['message'];
    $success = $notice['type'] === 'success';
}
$passwordMessage = isset($messages[$passwordStatus]) ? $messages[$passwordStatus] : '';
$old = function ($field, $stored) use ($values) {
    return isset($values[$field]) ? (string) $values[$field] : (string) $stored;
};
$error = function ($field) use ($errors) {
    return isset($errors[$field]) ? (string) $errors[$field] : '';
};
$passwordError = function ($field) use ($passwordErrors) {
    return isset($passwordErrors[$field]) ? (string) $passwordErrors[$field] : '';
};
?>
<main id="main">
    <?php $this->load->view('frontend/partials/page_hero', array('hero' => array(
        'crumbs' => array(
            array('label' => 'Home', 'url' => base_url()),
            array('label' => 'My appointments', 'url' => base_url('account')),
            array('label' => 'Your details'),
        ),
        'label' => 'Your account',
        'heading' => 'Your details',
        'lead' => 'These are filled in when you book while signed in.',
    ))); ?>

    <section class="pb-18 md:pb-22 lg:pb-26 bg-background text-foreground" aria-labelledby="details-heading">
        <div class="mx-auto w-full max-w-[86rem] px-5 sm:px-8 lg:px-12">
            <?php $this->load->view('frontend/account/nav', array('current' => 'details', 'signOutToken' => $signOutToken)); ?>

            <div class="grid gap-10 lg:grid-cols-2">
                <div class="card-soft p-6 sm:p-10">
                    <h2 id="details-heading" class="text-2xl">Contact details</h2>
                    <div class="mt-6">
                        <?php $this->load->view('frontend/partials/form_status', array('message' => $message, 'success' => $success)); ?>
                    </div>
                    <form method="post" action="<?php echo html_escape(base_url('account/details')); ?>" novalidate class="grid gap-6" data-public-form>
                        <input type="hidden" name="form_token" value="<?php echo html_escape($formToken); ?>">
                        <?php $this->load->view('frontend/partials/form_field', array('field' => array(
                            'id' => 'details-name',
                            'name' => 'name',
                            'label' => 'Name',
                            'value' => $old('name', $customer['customer_name']),
                            'required' => TRUE,
                            'autocomplete' => 'name',
                            'maxlength' => 150,
                            'error' => $error('name'),
                        ))); ?>
                        <?php $this->load->view('frontend/partials/form_field', array('field' => array(
                            'id' => 'details-email',
                            'name' => 'email',
                            'label' => 'Email',
                            'type' => 'email',
                            'value' => $old('email', $customer['customer_email']),
                            'required' => TRUE,
                            'autocomplete' => 'email',
                            'maxlength' => 190,
                            'hint' => $customer['customer_email_verified_at'] !== NULL ? 'Confirmed.' : 'Not confirmed yet — see My appointments.',
                            'error' => $error('email'),
                        ))); ?>
                        <?php $this->load->view('frontend/partials/form_field', array('field' => array(
                            'id' => 'details-phone',
                            'name' => 'phone',
                            'label' => 'Phone',
                            'type' => 'tel',
                            'value' => $old('phone', $customer['customer_phone']),
                            'autocomplete' => 'tel',
                            'maxlength' => 40,
                            'error' => $error('phone'),
                        ))); ?>
                        <?php $this->load->view('frontend/partials/form_field', array('field' => array(
                            'id' => 'details-current-password',
                            'name' => 'current_password',
                            'label' => 'Current password',
                            'type' => 'password',
                            'autocomplete' => 'current-password',
                            'maxlength' => 72,
                            'hint' => 'Only needed to change your email.',
                            'error' => $error('current_password'),
                        ))); ?>
                        <div>
                            <button class="<?php echo html_escape(frontend_button_class('primary', 'h-13 w-full px-8 sm:w-auto')); ?>" type="submit" data-form-submit data-busy-label="Saving…">Save details</button>
                        </div>
                    </form>
                </div>

                <div class="card-soft p-6 sm:p-10" id="password">
                    <h2 class="text-2xl">Password</h2>
                    <div class="mt-6">
                        <?php $this->load->view('frontend/partials/form_status', array('message' => $passwordMessage, 'success' => FALSE)); ?>
                    </div>
                    <form method="post" action="<?php echo html_escape(base_url('account/password')); ?>" novalidate class="grid gap-6" data-public-form>
                        <input type="hidden" name="form_token" value="<?php echo html_escape($passwordToken); ?>">
                        <?php $this->load->view('frontend/partials/form_field', array('field' => array(
                            'id' => 'password-current',
                            'name' => 'current_password',
                            'label' => 'Current password',
                            'type' => 'password',
                            'required' => TRUE,
                            'autocomplete' => 'current-password',
                            'maxlength' => 72,
                            'error' => $passwordError('current_password'),
                        ))); ?>
                        <?php $this->load->view('frontend/partials/form_field', array('field' => array(
                            'id' => 'password-new',
                            'name' => 'new_password',
                            'label' => 'New password',
                            'type' => 'password',
                            'required' => TRUE,
                            'autocomplete' => 'new-password',
                            'minlength' => PASSWORD_MIN_LENGTH,
                            'maxlength' => 72,
                            'hint' => 'At least '.PASSWORD_MIN_LENGTH.' characters.',
                            'error' => $passwordError('new_password'),
                        ))); ?>
                        <?php $this->load->view('frontend/partials/form_field', array('field' => array(
                            'id' => 'password-new-confirm',
                            'name' => 'new_password_confirm',
                            'label' => 'Repeat new password',
                            'type' => 'password',
                            'required' => TRUE,
                            'autocomplete' => 'new-password',
                            'maxlength' => 72,
                            'match' => 'password-new',
                            'error' => $passwordError('new_password_confirm'),
                        ))); ?>
                        <div>
                            <button class="<?php echo html_escape(frontend_button_class('outline', 'h-13 w-full px-8 sm:w-auto')); ?>" type="submit" data-form-submit data-busy-label="Saving…">Change password</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </section>
</main>
