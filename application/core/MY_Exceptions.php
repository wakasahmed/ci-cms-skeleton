<?php

defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Renders a branded 404 page instead of CodeIgniter's generic English-only
 * template whenever show_404() runs mid-request (for example, an expired
 * booking reference on the public site, or a missing record in an admin
 * controller).
 *
 * - A public (Frontend controller) request reuses the site's own 404 page.
 * - Any /manage/* request reuses the admin area's branded, sidebar-less
 *   shell (the same one the login screen uses) since the admin may or may
 *   not still be signed in.
 * - CLI calls and anything else fall through to the parent implementation
 *   unchanged.
 */
class MY_Exceptions extends CI_Exceptions
{
    public function show_404($page = '', $log_error = true)
    {
        $CI = &get_instance();

        if (is_cli()) {
            parent::show_404($page, $log_error);
            return;
        }

        if ($log_error) {
            log_message('error', '404 Page Not Found: ' . $page);
        }

        if ($CI instanceof Frontend) {
            $CI->error_404();
            $CI->output->_display();
            exit(4); // EXIT_UNKNOWN_FILE
        }

        if (strtolower(trim((string) $CI->uri->segment(1))) === 'manage') {
            $CI->output->set_status_header(404);
            $CI->load->view('admin/header', array(
                'loginSection' => 1,
                'page_title' => PROJECT_TITLE . ' | Page Not Found',
            ));
            $CI->load->view('admin/error404', array(
                'isLoggedIn' => (string) $CI->session->userdata('admin_auth') === 'allow',
            ));
            $CI->load->view('admin/footer');
            $CI->output->_display();
            exit(4); // EXIT_UNKNOWN_FILE
        }

        parent::show_404('', false);
    }
}
