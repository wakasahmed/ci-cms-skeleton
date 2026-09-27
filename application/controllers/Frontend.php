<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Temporary public frontend.
 *
 * The Alam tour website was retired in Phase 2 of PROJECT_PLAN.md. Until the
 * Blossom frontend is built (Phase 5), every public URL shows a holding page.
 * This controller is also the $route['404_override'] target, so unmatched
 * /manage URLs still get the admin-styled 404 page.
 */
class Frontend extends CI_Controller
{
    public function __construct()
    {
        parent::__construct();

        // $route['404_override'] also catches unmatched URLs under /manage.
        // Those belong to the admin area, so they get the admin 404 page.
        if ($this->uri->rsegment(2) === 'error_404'
            && strtolower(trim((string) $this->uri->segment(1))) === 'manage'
        ) {
            $this->renderManageNotFound();
        }
    }

    public function index()
    {
        $this->renderHoldingPage(200);
    }

    /**
     * Target of $route['404_override'] for public URLs.
     */
    public function error_404()
    {
        $this->renderHoldingPage(404);
    }

    private function renderHoldingPage($statusCode)
    {
        $settings = $this->SqlModel->getSingleRecord('site_settings', array('id' => 1));
        $settings = is_array($settings) ? $settings : array();

        $this->output->set_status_header($statusCode);
        $this->output->set_header('X-Robots-Tag: noindex, nofollow');
        $this->load->view('frontend/holding', array(
            'site_name' => !empty($settings['website_title']) ? $settings['website_title'] : 'Blossom Ewa Mazur',
            'phone' => isset($settings['phone']) ? trim((string) $settings['phone']) : '',
            'address' => isset($settings['address']) ? trim((string) $settings['address']) : '',
            'is_not_found' => $statusCode === 404,
        ));
    }

    private function renderManageNotFound()
    {
        $this->controller = 'errors';
        $this->SqlModel->setTitle();
        $this->output->set_status_header(404);
        $this->load->view('admin/header', array(
            'loginSection' => 1,
            'page_title' => PROJECT_TITLE.' | Page Not Found',
        ));
        $this->load->view('admin/error404', array(
            'isLoggedIn' => (string) $this->session->userdata('admin_auth') === 'allow',
        ));
        $this->load->view('admin/footer');
        $this->output->_display();
        exit;
    }
}
