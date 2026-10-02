<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Customer accounts on the website (PROJECT_PLAN.md, Phase 10): sign up,
 * sign in and out, forgotten password, email confirmation, the
 * "My appointments" page, details and password, and cancelling or moving an
 * appointment online until ACCOUNT_CHANGE_NOTICE_HOURS before it starts.
 *
 * Accounts are optional; guests still book. Forms follow the public form
 * pattern (Customer_account) with Post/Redirect/Get: a POST is handled, its
 * result is flashed and the page is shown again. Every page is noindex and
 * kept out of the sitemap.
 */
class Account extends CI_Controller
{
    /** Pages a sign-in may return to (?next=), as path prefixes. */
    const NEXT_ALLOWED = array('/account', '/book');

    public function __construct()
    {
        parent::__construct();

        $this->load->library('frontend_layout');
        $this->frontend_layout->guardUnderConstruction();
        $this->load->library(array('customer_auth', 'customer_account', 'form_token'));
    }

    /** My appointments (/account). */
    public function index()
    {
        $customer = $this->requireCustomer();
        $this->load->library('booking_request');

        $upcoming = array();
        $past = array();
        $now = time();
        foreach ($this->Customer_model->appointments($customer['customer_id']) as $appointment) {
            $appointment['blocker'] = $this->booking_request->changeBlocker($appointment);
            $start = strtotime($appointment['appointment_date'].' '.$appointment['appointment_time']);
            if ($start >= $now && $appointment['appointment_status'] !== 'Cancelled') {
                $upcoming[] = $appointment;
            } else {
                $past[] = $appointment;
            }
        }

        $this->render('frontend/account/appointments', 'My appointments', array(
            'customer' => $customer,
            'upcoming' => array_reverse($upcoming),
            'past' => $past,
            'notice' => $this->session->flashdata('account_notice'),
            'tokens' => array(
                'appointment' => $this->form_token->get('account_appointment'),
                'resend' => $this->form_token->get('account_resend'),
                'sign_out' => $this->form_token->get('account_sign_out'),
            ),
        ));
    }

    public function sign_in()
    {
        if ($this->customer_auth->current() !== NULL) {
            redirect($this->nextUrl());
        }

        if ($this->isPost()) {
            $result = $this->customer_account->signIn();
            if ($result['status'] === 'success') {
                redirect($this->nextUrl());
            }
            $this->flashResult($result);
            redirect($this->withNext('account/sign-in'), 'location', 303);
        }

        $this->renderForm('frontend/account/sign_in', 'Sign in', 'account_sign_in', array(
            'recaptchaAction' => 'SIGN_IN',
            'next' => $this->nextPath(),
        ));
    }

    public function sign_up()
    {
        if ($this->customer_auth->current() !== NULL) {
            redirect('account');
        }

        if ($this->isPost()) {
            $result = $this->customer_account->signUp();
            if ($result['status'] === 'success') {
                $this->notice('success', 'Welcome! Your account is ready. We’ve emailed you a link to confirm your address.');
                redirect($this->nextUrl());
            }
            $this->flashResult($result);
            redirect($this->withNext('account/sign-up'), 'location', 303);
        }

        $this->renderForm('frontend/account/sign_up', 'Create an account', 'account_sign_up', array(
            'recaptchaAction' => 'SIGN_UP',
            'next' => $this->nextPath(),
        ));
    }

    public function sign_out()
    {
        if ($this->isPost() && $this->form_token->consume('account_sign_out')) {
            $this->customer_auth->signOut();
        }

        redirect(base_url());
    }

    public function forgot_password()
    {
        if ($this->isPost()) {
            $result = $this->customer_account->forgot();
            $this->flashResult($result);
            redirect('account/forgot-password', 'location', 303);
        }

        $this->renderForm('frontend/account/forgot_password', 'Forgotten password', 'account_forgot', array(
            'recaptchaAction' => 'FORGOT_PASSWORD',
        ));
    }

    public function reset_password($token = '')
    {
        $token = (string) $token;

        if ($this->isPost()) {
            $result = $this->customer_account->resetPassword($token);
            if ($result['status'] === 'success') {
                $this->customer_auth->signOut();
                $this->notice('success', 'Your password has been changed. Please sign in with the new one.');
                redirect('account/sign-in');
            }
            if ($result['status'] === 'link') {
                $this->notice('error', 'This link has expired or was already used. Please ask for a new one.');
                redirect('account/forgot-password');
            }
            $this->flashResult($result);
            redirect('account/reset-password/'.rawurlencode($token), 'location', 303);
        }

        if (empty($this->customer_account->resetLink($token))) {
            $this->notice('error', 'This link has expired or was already used. Please ask for a new one.');
            redirect('account/forgot-password');
        }

        $this->renderForm('frontend/account/reset_password', 'Choose a new password', 'account_reset', array(
            'resetToken' => $token,
        ));
    }

    /** Confirm the email address from the emailed link. */
    public function verify($token = '')
    {
        $result = $this->customer_account->verify((string) $token);
        $signedIn = $this->customer_auth->refresh() !== NULL;

        if ($result['status'] === 'success') {
            $this->notice('success', 'Thank you — your email address is confirmed.');
        } else {
            $this->notice('error', 'This confirmation link has expired or was already used.'
                .($signedIn ? ' You can ask for a new one below.' : ' Sign in to ask for a new one.'));
        }

        redirect($signedIn ? 'account' : 'account/sign-in');
    }

    public function resend_verification()
    {
        $customer = $this->requireCustomer();

        if ($this->isPost()) {
            $messages = array(
                'success' => array('success', 'We’ve sent a new confirmation link to '.$customer['customer_email'].'.'),
                'verified' => array('success', 'Your email address is already confirmed.'),
                'limit' => array('error', 'We’ve already sent several links. Please check your inbox, or try again later.'),
                'expired' => array('error', 'That took a little long. Please try again.'),
            );
            $result = $this->customer_account->resendVerification($customer);
            $message = isset($messages[$result['status']]) ? $messages[$result['status']] : $messages['expired'];
            $this->notice($message[0], $message[1]);
        }

        redirect('account');
    }

    /** Name, email, phone and password (/account/details). */
    public function details()
    {
        $customer = $this->requireCustomer();

        if ($this->isPost()) {
            $result = $this->customer_account->updateDetails($customer);
            if ($result['status'] === 'success') {
                $this->notice('success', 'Your details are saved.');
                redirect('account/details');
            }
            if ($result['status'] === 'email_changed') {
                $this->notice('success', 'Your details are saved. Please confirm your new email address with the link we sent.');
                redirect('account/details');
            }
            $this->flashResult($result);
            redirect('account/details', 'location', 303);
        }

        $this->renderForm('frontend/account/details', 'Your details', 'account_details', array(
            'customer' => $customer,
            'passwordToken' => $this->form_token->get('account_password'),
            'passwordStatus' => (string) $this->session->flashdata('account_password_status'),
            'passwordErrors' => (array) $this->session->flashdata('account_password_errors'),
            'signOutToken' => $this->form_token->get('account_sign_out'),
        ));
    }

    public function password()
    {
        $customer = $this->requireCustomer();

        if ($this->isPost()) {
            $result = $this->customer_account->changePassword($customer);
            if ($result['status'] === 'success') {
                $this->notice('success', 'Your password has been changed. Other devices where you chose “Remember me” are signed out.');
            } else {
                $this->session->set_flashdata('account_password_status', $result['status']);
                $this->session->set_flashdata('account_password_errors', $result['errors']);
            }
        }

        redirect('account/details#password');
    }

    /** Cancel one of the customer's appointments (POST from My appointments). */
    public function cancel($reference = '')
    {
        $customer = $this->requireCustomer();
        $appointment = $this->Customer_model->appointment($customer['customer_id'], (string) $reference);

        if ($appointment !== NULL && $this->isPost() && $this->form_token->consume('account_appointment')) {
            $this->load->library('booking_request');
            $status = $this->booking_request->cancelForCustomer($appointment);
            $messages = array(
                'success' => array('success', 'Appointment '.$appointment['appointment_reference'].' is cancelled. We’ve emailed you a confirmation.'),
                'blocked' => array('error', $this->blockedMessage()),
            );
            $message = isset($messages[$status])
                ? $messages[$status]
                : array('error', 'The appointment could not be cancelled. Please call the salon.');
            $this->notice($message[0], $message[1]);
        } elseif ($appointment === NULL) {
            $this->notice('error', 'We couldn’t find that appointment in your account.');
        } else {
            $this->notice('error', 'That took a little long. Please try again.');
        }

        redirect('account');
    }

    /** Choose a new time for one of the customer's appointments. */
    public function reschedule($reference = '')
    {
        $customer = $this->requireCustomer();
        $appointment = $this->Customer_model->appointment($customer['customer_id'], (string) $reference);
        if ($appointment === NULL) {
            $this->notice('error', 'We couldn’t find that appointment in your account.');
            redirect('account');
        }

        $this->load->library('booking_request');
        if ($this->booking_request->changeBlocker($appointment) !== '') {
            $this->notice('error', $this->blockedMessage());
            redirect('account');
        }

        $path = 'account/appointments/'.rawurlencode($appointment['appointment_reference']).'/reschedule';

        if ($this->isPost()) {
            if (!$this->form_token->consume('account_reschedule')) {
                $this->session->set_flashdata('account_form_status', 'expired');
                redirect($path, 'location', 303);
            }

            $slot = explode(' ', trim((string) $this->input->post('slot')), 2);
            $status = $this->booking_request->rescheduleForCustomer(
                $appointment,
                isset($slot[0]) ? $slot[0] : '',
                isset($slot[1]) ? $slot[1] : ''
            );
            if ($status === 'success') {
                $this->notice('success', 'Appointment '.$appointment['appointment_reference'].' is moved. We’ve emailed you the new time.');
                redirect('account');
            }
            $this->session->set_flashdata('account_form_status', $status);
            redirect($path, 'location', 303);
        }

        $this->render('frontend/account/reschedule', 'Move appointment', array(
            'appointment' => $appointment,
            'days' => $this->booking_request->rescheduleDays($appointment),
            'formToken' => $this->form_token->get('account_reschedule'),
            'status' => (string) $this->session->flashdata('account_form_status'),
            'action' => base_url($path),
        ));
    }

    /** The signed-in customer, or a redirect to sign in (returning here afterwards). */
    private function requireCustomer()
    {
        $customer = $this->customer_auth->current();
        if ($customer === NULL) {
            redirect('account/sign-in?next='.rawurlencode('/'.$this->uri->uri_string()));
        }

        return $customer;
    }

    private function blockedMessage()
    {
        return 'Appointments can be changed online until '.ACCOUNT_CHANGE_NOTICE_HOURS
            .' hours before they start. Please call the salon instead.';
    }

    private function isPost()
    {
        return $this->input->method(TRUE) === 'POST';
    }

    private function notice($type, $message)
    {
        $this->session->set_flashdata('account_notice', array('type' => $type, 'message' => $message));
    }

    private function flashResult(array $result)
    {
        $this->session->set_flashdata('account_form_status', $result['status']);
        $this->session->set_flashdata('account_form_errors', $result['errors']);
        $this->session->set_flashdata('account_form_values', $result['values']);
    }

    /** Where to go after signing in: an allowed ?next= path, or My appointments. */
    private function nextUrl()
    {
        $next = $this->nextPath();

        return $next !== '' ? base_url(ltrim($next, '/')) : base_url('account');
    }

    /** The ?next= path when it is a local page we allow, otherwise ''. */
    private function nextPath()
    {
        $next = (string) $this->input->get('next');
        if ($next === '' || $next[0] !== '/' || strpos($next, '//') === 0 || preg_match('/[\\\\\s]/', $next) === 1) {
            return '';
        }

        foreach (self::NEXT_ALLOWED as $prefix) {
            if ($next === $prefix || strpos($next, $prefix.'/') === 0 || strpos($next, $prefix.'?') === 0) {
                return $next;
            }
        }

        return '';
    }

    private function withNext($path)
    {
        $next = $this->nextPath();

        return $next !== '' ? $path.'?next='.rawurlencode($next) : $path;
    }

    /** A form page: its one-use token, the last result and the reCAPTCHA settings. */
    private function renderForm($view, $title, $tokenName, array $data)
    {
        $this->render($view, $title, $data + array(
            'formToken' => $this->form_token->get($tokenName),
            'status' => (string) $this->session->flashdata('account_form_status'),
            'errors' => (array) $this->session->flashdata('account_form_errors'),
            'values' => (array) $this->session->flashdata('account_form_values'),
            'notice' => $this->session->flashdata('account_notice'),
            'recaptchaSiteKey' => RECAPTCHA_ENTERPRISE_SITE_KEY,
        ));
    }

    private function render($view, $title, array $data)
    {
        $this->output->set_header('Cache-Control: no-store');
        $this->output->set_header('X-Robots-Tag: noindex, nofollow');

        $data['title'] = $title;
        $this->frontend_layout->render($view, $data, array(
            'meta' => array(
                'page_title' => $title.' | '.$this->frontend_layout->setting('website_title'),
                'robots' => 'noindex, nofollow',
            ),
            'crumbs' => array(
                array('label' => 'Home', 'url' => base_url()),
                array('label' => $title),
            ),
            'scripts' => array('js/form.js'),
        ));
    }
}
