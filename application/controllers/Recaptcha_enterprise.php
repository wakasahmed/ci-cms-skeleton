<?php if (!defined('BASEPATH')) exit('No direct script access allowed');

class Recaptcha_enterprise extends CI_Controller
{
    const ACTION = 'LOGIN';

    public function index()
    {
        $this->load->view('recaptcha_enterprise', array(
            'site_key' => RECAPTCHA_ENTERPRISE_SITE_KEY,
            'recaptcha_action' => self::ACTION
        ));
    }

    public function verify()
    {
        if (strtoupper($this->input->method()) !== 'POST') {
            return $this->json_response(array(
                'success' => false,
                'message' => 'Only POST requests are allowed.'
            ), 405);
        }

        $token = trim((string) $this->input->post('recaptcha_token'));
        if ($token === '') {
            return $this->json_response(array(
                'success' => false,
                'message' => 'The reCAPTCHA token is required.'
            ), 422);
        }

        $this->load->library('google_recaptcha');
        $result = $this->google_recaptcha->create_assessment(
            $token,
            self::ACTION,
            $this->input->ip_address(),
            !empty($_SERVER['HTTP_USER_AGENT']) ? $_SERVER['HTTP_USER_AGENT'] : ''
        );
        $status_code = $result['status_code'];
        unset($result['status_code']);

        return $this->json_response($result, $status_code);
    }

    private function json_response($data, $status_code)
    {
        $this->output
            ->set_status_header($status_code)
            ->set_content_type('application/json', 'utf-8')
            ->set_output(json_encode($data));
    }
}
