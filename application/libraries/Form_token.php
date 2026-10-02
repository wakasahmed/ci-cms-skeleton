<?php defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * One-use tokens for public forms. Global CSRF protection is off, so each
 * form carries a token bound to the visitor's session; a POST is accepted
 * only with the current token, which is then rotated.
 *
 * Each form has its own token (session key "<name>_form_token"), so two
 * forms open in different tabs do not invalidate each other.
 */
class Form_token
{
    private $CI;

    public function __construct()
    {
        $this->CI =& get_instance();
    }

    /** The current token for form $name, created when missing. */
    public function get($name)
    {
        $key = $this->key($name);
        $token = (string) $this->CI->session->userdata($key);

        if (strlen($token) !== 64) {
            $token = bin2hex(random_bytes(32));
            $this->CI->session->set_userdata($key, $token);
        }

        return $token;
    }

    /** Check the posted 'form_token' for form $name and rotate it either way. */
    public function consume($name)
    {
        $key = $this->key($name);
        $expected = (string) $this->CI->session->userdata($key);
        $submitted = (string) $this->CI->input->post('form_token');
        $this->CI->session->unset_userdata($key);

        return strlen($expected) === 64
            && strlen($submitted) === 64
            && hash_equals($expected, $submitted);
    }

    private function key($name)
    {
        return preg_replace('/[^a-z0-9_]/', '', strtolower((string) $name)).'_form_token';
    }
}
