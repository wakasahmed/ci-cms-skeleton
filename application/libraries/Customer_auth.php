<?php defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Who is signed in on the website (Phase 10). Customers are kept apart from
 * administrators: their own session key and their own remember-me cookie.
 *
 * current() is the one place that decides; it re-reads the account on every
 * request, so disabling a customer in Manage > Customers signs them out.
 */
class Customer_auth
{
    const SESSION_KEY = 'customer_id';

    private $CI;
    private $customer = FALSE;

    public function __construct()
    {
        $this->CI =& get_instance();
        $this->CI->load->model(array('Customer_model', 'Customer_token_model'));
    }

    /** The signed-in, enabled customer, or NULL. */
    public function current()
    {
        if ($this->customer !== FALSE) {
            return $this->customer;
        }

        $this->customer = NULL;
        $customerId = (int) $this->CI->session->userdata(self::SESSION_KEY);

        if ($customerId > 0) {
            $customer = $this->CI->Customer_model->find($customerId);
            if (!empty($customer) && $customer['customer_status'] === 'Enable') {
                $this->customer = $customer;
            } else {
                $this->CI->session->unset_userdata(self::SESSION_KEY);
            }
        } else {
            $this->customer = $this->fromRememberCookie();
        }

        return $this->customer;
    }

    /** Start a session for $customer; with $remember, also a 30-day cookie. */
    public function signIn(array $customer, $remember)
    {
        $this->CI->session->sess_regenerate(TRUE);
        $this->CI->session->set_userdata(self::SESSION_KEY, (int) $customer['customer_id']);
        $this->CI->Customer_model->recordSignIn($customer['customer_id'], $this->CI->input->ip_address());

        $this->forgetCookie();
        if ($remember) {
            $value = $this->CI->Customer_token_model->createRemember(
                $customer['customer_id'],
                $this->CI->input->ip_address(),
                $this->CI->input->user_agent()
            );
            if ($value !== FALSE) {
                $this->setCookie($value, Customer_token_model::REMEMBER_LIFETIME);
            }
        }

        $this->customer = $this->CI->Customer_model->find($customer['customer_id']);
    }

    public function signOut()
    {
        $this->forgetCookie();
        $this->CI->session->unset_userdata(self::SESSION_KEY);
        $this->CI->session->sess_regenerate(TRUE);
        $this->customer = NULL;
    }

    /** Sign out every remembered device of $customerId (after a password change). */
    public function forgetAllDevices($customerId)
    {
        $this->CI->Customer_token_model->revokeAllRemember($customerId);
    }

    /** Drop the cached customer after their record changed in this request. */
    public function refresh()
    {
        $this->customer = FALSE;

        return $this->current();
    }

    private function fromRememberCookie()
    {
        $cookie = (string) $this->CI->input->cookie(Customer_token_model::COOKIE_NAME);
        if ($cookie === '') {
            return NULL;
        }

        $customer = $this->CI->Customer_token_model->useRemember($cookie);
        if (empty($customer)) {
            $this->clearCookie();

            return NULL;
        }

        $this->CI->session->sess_regenerate(TRUE);
        $this->CI->session->set_userdata(self::SESSION_KEY, (int) $customer['customer_id']);
        $this->CI->Customer_model->recordSignIn($customer['customer_id'], $this->CI->input->ip_address());
        $this->setCookie($customer['cookie_value'], $customer['cookie_lifetime']);
        unset($customer['cookie_value'], $customer['cookie_lifetime']);

        return $customer;
    }

    private function forgetCookie()
    {
        $cookie = (string) $this->CI->input->cookie(Customer_token_model::COOKIE_NAME);
        if ($cookie !== '') {
            $this->CI->Customer_token_model->revokeRemember($cookie);
        }
        $this->clearCookie();
    }

    private function setCookie($value, $lifetime)
    {
        $this->CI->input->set_cookie(array(
            'name' => Customer_token_model::COOKIE_NAME,
            'value' => (string) $value,
            'expire' => max(1, (int) $lifetime),
            'path' => '/',
            'secure' => $this->isSecureRequest(),
            'httponly' => TRUE,
            'samesite' => 'Lax',
        ));
    }

    private function clearCookie()
    {
        $this->CI->input->set_cookie(array(
            'name' => Customer_token_model::COOKIE_NAME,
            'value' => '',
            'expire' => '',
            'path' => '/',
            'secure' => $this->isSecureRequest(),
            'httponly' => TRUE,
            'samesite' => 'Lax',
        ));
    }

    private function isSecureRequest()
    {
        $https = strtolower((string) $this->CI->input->server('HTTPS'));

        return $https !== '' && $https !== 'off';
    }
}
