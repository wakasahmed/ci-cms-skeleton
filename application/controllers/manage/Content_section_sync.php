<?php defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Compares config/content_sections.php with the database and adds any
 * configured section field that has no row yet, for every content locale.
 *
 * Covers both Web Page Sections and Miscellaneous Contents. Existing rows
 * are never changed or removed.
 */
class Content_section_sync extends CI_Controller
{
    public $moduleName = 'Content Section Fields';
    public $controller = 'content_section_sync';
    public $user_data = array();

    private $view = 'admin/content_sections/sync';

    public function __construct()
    {
        parent::__construct();

        $this->user_data = $this->SqlModel->authAdmin(
            $this->session->userdata('admin_auth'),
            $this->session->userdata('admin_id')
        );

        if (empty($this->user_data)) {
            redirect(base_url('manage/login'));
            return;
        }

        $this->load->library('content_section_service');
    }

    /** Report the missing fields without changing field data. */
    public function index()
    {
        $this->requirePermission();

        $missing = $this->content_section_service->missing_fields();

        $data = array(
            'page_title' => PROJECT_TITLE.' | '.$this->moduleName,
            'userdata' => $this->user_data,
            'pagesActive' => 1,
            'controller' => $this->controller,
            'module_name' => $this->moduleName,
            'status' => $this->session->flashdata('status'),
            'inserted' => (int) $this->session->flashdata('inserted'),
            'load_error' => $missing['status'] !== 'ok',
            'items' => $missing['items'],
            'locales' => $this->content_section_service->locales(),
        );

        $this->load->view('admin/header', $data);
        $this->load->view('admin/navigation');
        $this->load->view($this->view);
        $this->load->view('admin/footer');
    }

    /** Add every missing field, then redirect back to the report. */
    public function run()
    {
        $this->requirePermission();

        if (strtoupper($this->input->method()) !== 'POST') {
            show_404();
            return;
        }

        $inserted = $this->content_section_service->add_missing_fields($this->user_data);

        if ($inserted === false) {
            log_message('error', 'Unable to add missing content section fields.');
            $this->session->set_flashdata('status', 'error');
        } else {
            $this->session->set_flashdata('status', 'editsuccess');
            $this->session->set_flashdata('inserted', (int) $inserted);
        }

        redirect(base_url('manage/'.$this->controller));
    }

    /** Both content modules are written, so both update permissions are required. */
    private function requirePermission()
    {
        $canUpdatePages = $this->content_section_service->can(
            'web_pages.update',
            $this->user_data
        );
        $canUpdateSections = $this->content_section_service->can(
            'miscellaneous_contents.update',
            $this->user_data
        );

        if (!$canUpdatePages || !$canUpdateSections) {
            show_error('Access denied.', 403);
        }
    }
}
