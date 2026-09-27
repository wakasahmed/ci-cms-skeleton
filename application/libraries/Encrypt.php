<?php defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Compatibility adapter for the CI2 Encrypt API used by the application.
 *
 * CI3's legacy CI_Encrypt class requires Mcrypt, which is unavailable on
 * supported PHP runtimes. Delegate to CI3's authenticated Encryption library
 * while retaining the application's existing encode()/decode() calls.
 */
class CI_Encrypt
{
    /** @var CI_Encryption */
    private $encryption;

    /** @var string */
    private $key = '';

    public function __construct()
    {
        $CI =& get_instance();
        $CI->load->library('encryption');
        $this->encryption = $CI->encryption;
    }

    public function set_key($key = '')
    {
        $this->key = $key;
    }

    public function encode($string, $key = '')
    {
        return $this->encryption->encrypt($string, $this->params($key));
    }

    public function decode($string, $key = '')
    {
        return $this->encryption->decrypt($string, $this->params($key));
    }

    private function params($key)
    {
        $key = ($key !== '') ? $key : $this->key;
        return ($key === '') ? NULL : array('key' => $key);
    }
}
