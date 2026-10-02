<?php defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * The account forms of the website (Phase 10), in the public form pattern
 * (see Contact_form): a one-use token per form, server-side validation,
 * reCAPTCHA Enterprise on the forms a stranger can submit (sign up, sign in,
 * forgotten password), and emails through managed templates 5 (confirm your
 * email), 6 (reset your password) and 7 (password changed).
 *
 * Every handler returns array('status' => …, 'errors' => field => message,
 * 'values' => what to show again), like Contact_form::submit().
 */
class Customer_account
{
    const RECAPTCHA_MIN_SCORE = 0.5;
    const PASSWORD_MAX_LENGTH = 72;

    const VERIFY_TEMPLATE_ID = 5;
    const RESET_TEMPLATE_ID = 6;
    const PASSWORD_CHANGED_TEMPLATE_ID = 7;

    private $CI;

    public function __construct()
    {
        $this->CI =& get_instance();
        $this->CI->load->model(array('Customer_model', 'Customer_token_model'));
        $this->CI->load->library(array('customer_auth', 'form_token', 'EmailService'));
    }

    /** Create an account, sign it in and send the confirmation link. */
    public function signUp()
    {
        $values = $this->values(array('name', 'email', 'phone'));

        if (!$this->CI->form_token->consume('account_sign_up')) {
            return $this->result('expired', $values);
        }

        $errors = $this->detailErrors($values);
        $errors += $this->newPasswordErrors('password', 'password_confirm');
        if (empty($errors) && !empty($this->CI->Customer_model->findByEmail($values['email']))) {
            $errors['email'] = 'There is already an account with this email. Sign in, or reset your password.';
        }
        if (!empty($errors)) {
            return $this->result('invalid', $values, $errors);
        }
        if (!$this->human('SIGN_UP')) {
            return $this->result('recaptcha', $values);
        }

        $customerId = $this->CI->Customer_model->create(
            $values['name'],
            $values['email'],
            $values['phone'],
            password_hash((string) $this->CI->input->post('password'), PASSWORD_DEFAULT)
        );
        if (!$customerId) {
            return $this->result('error', $values);
        }

        $customer = $this->CI->Customer_model->find($customerId);
        $this->CI->customer_auth->signIn($customer, FALSE);
        $this->sendVerification($customer);

        return $this->result('success');
    }

    /** Check the email and password, slowing down repeated failures. */
    public function signIn()
    {
        $values = $this->values(array('email'));
        $values['remember'] = $this->CI->input->post('remember') === '1' ? '1' : '';
        $ip = $this->CI->input->ip_address();

        if (!$this->CI->form_token->consume('account_sign_in')) {
            return $this->result('expired', $values);
        }

        $password = (string) $this->CI->input->post('password');
        if (!$this->validEmail($values['email']) || $password === '' || strlen($password) > self::PASSWORD_MAX_LENGTH) {
            return $this->result('invalid', $values, array('email' => 'Enter your email address and password.'));
        }
        if ($this->CI->Customer_model->retryAfter($values['email'], $ip) > 0) {
            $this->recordAttempt($values['email'], NULL, FALSE, 'rate_limited');

            return $this->result('locked', $values);
        }
        if (!$this->human('SIGN_IN')) {
            return $this->result('recaptcha', $values);
        }

        $customer = $this->CI->Customer_model->findByEmail($values['email']);
        // Compare against a dummy hash when there is no account, so both cases take as long.
        $valid = !empty($customer)
            ? password_verify($password, $customer['customer_password'])
            : password_verify($password, '$2y$10$vVwA7.nMEoStCUPydSWp2O7pA4q7q4rq9mWwthjiu7NkqkXjU0tOa');

        if (!$valid || $customer['customer_status'] !== 'Enable') {
            $this->recordAttempt($values['email'], empty($customer) ? NULL : $customer['customer_id'], FALSE, 'invalid_credentials');

            return $this->result('credentials', $values);
        }

        if (password_needs_rehash($customer['customer_password'], PASSWORD_DEFAULT)) {
            $this->CI->Customer_model->update($customer['customer_id'], array(
                'customer_password' => password_hash($password, PASSWORD_DEFAULT),
            ));
        }

        $this->recordAttempt($values['email'], $customer['customer_id'], TRUE, NULL);
        $this->CI->customer_auth->signIn($customer, $values['remember'] === '1');

        return $this->result('success');
    }

    /** Email a reset link when the address has an account; the answer is the same either way. */
    public function forgot()
    {
        $values = $this->values(array('email'));

        if (!$this->CI->form_token->consume('account_forgot')) {
            return $this->result('expired', $values);
        }
        if (!$this->validEmail($values['email'])) {
            return $this->result('invalid', $values, array('email' => 'That email doesn’t look quite right.'));
        }
        if (!$this->human('FORGOT_PASSWORD')) {
            return $this->result('recaptcha', $values);
        }

        $customer = $this->CI->Customer_model->findByEmail($values['email']);
        if (!empty($customer) && $customer['customer_status'] === 'Enable') {
            $token = $this->CI->Customer_token_model->createLink(
                $customer['customer_id'],
                'reset',
                PASSWORD_RESET_TTL,
                $this->CI->input->ip_address()
            );
            if ($token !== FALSE && !$this->sendLink($customer, self::RESET_TEMPLATE_ID, 'account/reset-password/'.$token, PASSWORD_RESET_TTL)) {
                $this->CI->Customer_token_model->deleteLink($token);
            }
        }

        return $this->result('success');
    }

    /** The customer behind a valid reset link, or NULL. */
    public function resetLink($token)
    {
        return $this->CI->Customer_token_model->findLink($token, 'reset');
    }

    /**
     * Set a new password from a reset link. The link proves the address, so
     * it also confirms it. Every remembered device is signed out.
     */
    public function resetPassword($token)
    {
        if (!$this->CI->form_token->consume('account_reset')) {
            return $this->result('expired');
        }

        $link = $this->resetLink($token);
        if (empty($link)) {
            return $this->result('link');
        }

        $errors = $this->newPasswordErrors('password', 'password_confirm');
        if (!empty($errors)) {
            return $this->result('invalid', array(), $errors);
        }
        if (!$this->CI->Customer_token_model->useLink($link, 'reset')) {
            return $this->result('link');
        }

        $this->CI->Customer_model->update($link['customer_id'], array(
            'customer_password' => password_hash((string) $this->CI->input->post('password'), PASSWORD_DEFAULT),
        ));
        $this->CI->Customer_model->markVerified($link['customer_id']);
        $this->CI->customer_auth->forgetAllDevices($link['customer_id']);
        $this->sendPasswordChanged($this->CI->Customer_model->find($link['customer_id']));

        return $this->result('success');
    }

    /** Confirm the address from a 'verify' link; guest bookings made with it join the account. */
    public function verify($token)
    {
        $link = $this->CI->Customer_token_model->findLink($token, 'verify');
        if (empty($link) || !$this->CI->Customer_token_model->useLink($link, 'verify')) {
            return $this->result('link');
        }

        return $this->CI->Customer_model->markVerified($link['customer_id'])
            ? $this->result('success')
            : $this->result('error');
    }

    /** Send the confirmation link again (signed-in customer, form 'account_resend'). */
    public function resendVerification(array $customer)
    {
        if (!$this->CI->form_token->consume('account_resend')) {
            return $this->result('expired');
        }
        if ($customer['customer_email_verified_at'] !== NULL) {
            return $this->result('verified');
        }

        return $this->sendVerification($customer)
            ? $this->result('success')
            : $this->result('limit');
    }

    /**
     * Name, phone and email of the signed-in customer. Changing the email
     * needs the current password and a new confirmation.
     */
    public function updateDetails(array $customer)
    {
        $values = $this->values(array('name', 'email', 'phone'));

        if (!$this->CI->form_token->consume('account_details')) {
            return $this->result('expired', $values);
        }

        $errors = $this->detailErrors($values);
        $emailChanged = $this->CI->Customer_model->normalizeEmail($values['email']) !== $customer['customer_email'];
        if ($emailChanged && empty($errors['email'])) {
            $other = $this->CI->Customer_model->findByEmail($values['email']);
            if (!empty($other)) {
                $errors['email'] = 'Another account already uses this email.';
            } elseif (!password_verify((string) $this->CI->input->post('current_password'), $customer['customer_password'])) {
                $errors['current_password'] = 'Enter your current password to change your email.';
            }
        }
        if (!empty($errors)) {
            return $this->result('invalid', $values, $errors);
        }

        $changes = array(
            'customer_name' => $values['name'],
            'customer_phone' => $values['phone'] !== '' ? $values['phone'] : NULL,
        );
        if ($emailChanged) {
            $changes['customer_email'] = $this->CI->Customer_model->normalizeEmail($values['email']);
            $changes['customer_email_verified_at'] = NULL;
        }
        if (!$this->CI->Customer_model->update($customer['customer_id'], $changes)) {
            return $this->result('error', $values);
        }

        $updated = $this->CI->customer_auth->refresh();
        if ($emailChanged) {
            $this->sendVerification($updated);

            return $this->result('email_changed');
        }

        return $this->result('success');
    }

    /** New password for the signed-in customer; other remembered devices are signed out. */
    public function changePassword(array $customer)
    {
        if (!$this->CI->form_token->consume('account_password')) {
            return $this->result('expired');
        }

        $errors = array();
        if (!password_verify((string) $this->CI->input->post('current_password'), $customer['customer_password'])) {
            $errors['current_password'] = 'That is not your current password.';
        }
        $errors += $this->newPasswordErrors('new_password', 'new_password_confirm');
        if (!empty($errors)) {
            return $this->result('invalid', array(), $errors);
        }

        $this->CI->Customer_model->update($customer['customer_id'], array(
            'customer_password' => password_hash((string) $this->CI->input->post('new_password'), PASSWORD_DEFAULT),
        ));
        $this->CI->customer_auth->forgetAllDevices($customer['customer_id']);
        $this->sendPasswordChanged($this->CI->customer_auth->refresh());

        return $this->result('success');
    }

    /** Email a fresh confirmation link. FALSE when rate limited or not sent. */
    public function sendVerification(array $customer)
    {
        $token = $this->CI->Customer_token_model->createLink(
            $customer['customer_id'],
            'verify',
            CUSTOMER_VERIFY_TTL,
            $this->CI->input->ip_address()
        );
        if ($token === FALSE) {
            return FALSE;
        }

        if (!$this->sendLink($customer, self::VERIFY_TEMPLATE_ID, 'account/verify/'.$token, CUSTOMER_VERIFY_TTL)) {
            $this->CI->Customer_token_model->deleteLink($token);

            return FALSE;
        }

        return TRUE;
    }

    private function sendLink(array $customer, $templateId, $path, $ttl)
    {
        $values = $this->emailValues($customer);
        $values['link'] = base_url($path);
        $values['expires'] = $this->readableDuration($ttl);

        return $this->CI->emailservice->sendManagedTemplate(array(
            'template_id' => $templateId,
            'to' => $customer['customer_email'],
            'values' => $values,
            'parser' => 'parseCustomerShortTags',
            'label' => 'Customer account email '.$templateId,
        ));
    }

    private function sendPasswordChanged(array $customer)
    {
        return $this->CI->emailservice->sendManagedTemplate(array(
            'template_id' => self::PASSWORD_CHANGED_TEMPLATE_ID,
            'to' => $customer['customer_email'],
            'values' => $this->emailValues($customer),
            'parser' => 'parseCustomerShortTags',
            'label' => 'Customer password changed email',
        ));
    }

    private function emailValues(array $customer)
    {
        $nameParts = preg_split('/\s+/u', trim((string) $customer['customer_name']), 2);

        return array(
            'first_name' => $nameParts[0],
            'customer_name' => $customer['customer_name'],
            'customer_email' => $customer['customer_email'],
            'link' => '',
            'expires' => '',
        );
    }

    /** "60 minutes", "48 hours", "2 days". */
    private function readableDuration($seconds)
    {
        $minutes = (int) round($seconds / 60);
        if ($minutes < 120) {
            return $minutes.' minutes';
        }

        return (int) round($minutes / 60).' hours';
    }

    /** Name, email and optional phone, checked as on the booking form. */
    private function detailErrors(array $values)
    {
        $errors = array();

        if ($values['name'] === '' || mb_strlen($values['name']) > 150) {
            $errors['name'] = 'Please tell us your name.';
        }
        if (!$this->validEmail($values['email'])) {
            $errors['email'] = 'That email doesn’t look quite right.';
        }
        if ($values['phone'] !== ''
            && (mb_strlen($values['phone']) > 40
                || !preg_match('/^\+?[0-9 ()\-]+$/', $values['phone'])
                || preg_match_all('/[0-9]/', $values['phone']) < 6)
        ) {
            $errors['phone'] = 'Please enter a phone number we can call, or leave it empty.';
        }

        return $errors;
    }

    private function newPasswordErrors($field, $confirmField)
    {
        $password = (string) $this->CI->input->post($field);

        if (mb_strlen($password) < PASSWORD_MIN_LENGTH) {
            return array($field => 'Use at least '.PASSWORD_MIN_LENGTH.' characters.');
        }
        if (strlen($password) > self::PASSWORD_MAX_LENGTH) {
            return array($field => 'Use at most '.self::PASSWORD_MAX_LENGTH.' characters.');
        }
        if ($password !== (string) $this->CI->input->post($confirmField)) {
            return array($confirmField => 'The two passwords do not match.');
        }

        return array();
    }

    private function human($action)
    {
        $this->CI->load->library('google_recaptcha');

        return $this->CI->google_recaptcha->verify(
            $this->CI->input->post('recaptcha_token'),
            $action,
            self::RECAPTCHA_MIN_SCORE,
            $this->CI->input->ip_address(),
            (string) $this->CI->input->user_agent()
        );
    }

    private function recordAttempt($email, $customerId, $successful, $reason)
    {
        $this->CI->Customer_model->recordAttempt(
            $email,
            $customerId,
            $successful,
            $reason,
            $this->CI->input->ip_address(),
            $this->CI->input->user_agent()
        );
    }

    /** Untrusted POST values as trimmed strings. */
    private function values(array $fields)
    {
        $values = array();
        foreach ($fields as $field) {
            $value = $this->CI->input->post($field);
            $values[$field] = is_string($value) ? trim($value) : '';
        }

        return $values;
    }

    /** A valid address with a dot-separated domain (as on the booking form). */
    private function validEmail($email)
    {
        if ($email === '' || mb_strlen($email) > 190 || filter_var($email, FILTER_VALIDATE_EMAIL) === FALSE) {
            return FALSE;
        }

        $domain = substr($email, strrpos($email, '@') + 1);

        return strpos($domain, '.') > 0 && substr($domain, -1) !== '.';
    }

    private function result($status, array $values = array(), array $errors = array())
    {
        return array(
            'status' => $status,
            'errors' => $errors,
            'values' => $values,
        );
    }
}
