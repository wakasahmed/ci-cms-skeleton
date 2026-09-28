<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Public website.
 *
 * Pages render through Frontend_layout (shared head, header and footer).
 * The home page is built in Phase 5 as an empty shell; the other pages of
 * the site map arrive with their CMS data in Phase 6 of PROJECT_PLAN.md,
 * until then their URLs return the 404 page.
 *
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

        $this->load->library('frontend_layout');

        if ($this->frontend_layout->isUnderConstruction() && !$this->isAdministrator()) {
            $this->renderUnderConstruction();
        }
    }

    public function index()
    {
        $this->load->model('Webpage_model');
        $page = $this->Webpage_model->get_page('id', 1, false, true);

        $this->frontend_layout->render('frontend/home', array(), array(
            'meta' => $this->frontend_layout->pageMeta(is_array($page) ? $page : array(), base_url()),
        ));
    }

    /**
     * Target of $route['404_override'] for public URLs, and of show_404()
     * during a public request (see MY_Exceptions).
     */
    public function error_404()
    {
        $this->output->set_status_header(404);

        $this->frontend_layout->render('frontend/error_404', array(), array(
            'meta' => array(
                'page_title' => 'Page not found | '.$this->frontend_layout->setting('website_title'),
                'robots' => 'noindex, follow',
            ),
        ));
    }

    /**
     * Signed-in administrators keep browsing the real site while it is under
     * construction, so they can review pages before launch.
     */
    private function isAdministrator()
    {
        return (string) $this->session->userdata('admin_auth') === 'allow';
    }

    /**
     * Serve the under-construction page for every public URL. 503 with
     * Retry-After tells search engines the outage is temporary. Runs from the
     * constructor, before the router dispatches, so it must flush and halt
     * here to avoid a double render.
     */
    private function renderUnderConstruction()
    {
        $this->output->set_status_header(503);
        $this->output->set_header('Retry-After: 3600');
        $this->output->set_header('Cache-Control: no-store');
        $this->output->set_header('X-Robots-Tag: noindex, nofollow');

        $this->frontend_layout->renderStandalone('frontend/under_construction', array(), array(
            'meta' => array(
                'page_title' => $this->frontend_layout->setting('website_title'),
            ),
        ));
        $this->output->_display();
        exit;
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
