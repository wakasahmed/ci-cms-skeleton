<?php defined('BASEPATH') OR exit('No direct script access allowed');

class Menu extends CI_Controller
{
    public $tblName = 'pages';
    public $pKey = 'page_id';
    public $moduleName = 'Menu';
    public $controller = 'menu';
    public $user_data = array();

    public function __construct()
    {
        parent::__construct();
        $this->user_data = $this->SqlModel->authAdmin($this->session->userdata('admin_auth'), $this->session->userdata('admin_id'));
        if (empty($this->user_data)) {
            redirect(base_url('manage/login'));
        }
        $this->load->model('MenuModel');
        $this->load->library('page_menu_hierarchy');
    }

    public function index()
    {
        $menu = $this->MenuModel->getMenuData();
        $data = array(
            'page_title' => PROJECT_TITLE.' | '.$this->moduleName,
            'alert' => $this->session->flashdata('alert'),
            'userdata' => $this->user_data,
            'menuActive' => 1,
            'menuMainActive' => 1,
            'useMenuManager' => TRUE,
            'active' => $menu['active'],
            'available' => $menu['available'],
            'maxDepth' => $this->MenuModel->maxDepth(),
            'menuLabel' => 'Main Menu',
            'menuDescription' => "Arrange pages in the website's primary navigation.",
            'saveUrl' => base_url('manage/menu/save'),
        );

        $this->load->view('admin/header', $data);
        $this->load->view('admin/navigation');
        $this->load->view('admin/menuManager');
        $this->load->view('admin/footer');
    }

    public function save()
    {
        $decoded = $this->decodePayload($this->input->post('menu_payload'));

        if ($decoded === NULL) {
            $this->session->set_flashdata('alert', 'invalid');
            redirect(base_url('manage/menu'));
            return;
        }

        $allowedPages = $this->MenuModel->getAllowedPages();
        $rows = $this->page_menu_hierarchy->flattenPayload($decoded, $allowedPages, $this->MenuModel->maxDepth());

        if ($rows === NULL) {
            $this->session->set_flashdata('alert', 'invalid');
            redirect(base_url('manage/menu'));
            return;
        }

        if (!$this->persistRows($rows)) {
            $this->session->set_flashdata('alert', 'error');
            redirect(base_url('manage/menu'));
            return;
        }

        $this->session->set_flashdata('alert', 'success');
        redirect(base_url('manage/menu'));
    }

    /**
     * Decodes and shallow-validates the submitted JSON envelope.
     * Returns array('active' => [...], 'available' => [...]) or NULL.
     */
    private function decodePayload($raw)
    {
        if (!is_string($raw) || trim($raw) === '') {
            return NULL;
        }

        $decoded = json_decode($raw, TRUE);

        if (json_last_error() !== JSON_ERROR_NONE || !is_array($decoded)) {
            return NULL;
        }

        if (!isset($decoded['active']) || !isset($decoded['available']) || !is_array($decoded['active']) || !is_array($decoded['available'])) {
            return NULL;
        }

        return array('active' => $decoded['active'], 'available' => $decoded['available']);
    }

    private function persistRows($rows)
    {
        if (empty($rows)) {
            return TRUE;
        }

        $this->db->trans_begin();

        foreach ($rows as $row) {
            $this->db->where('page_id', $row['page_id'])->update('pages', array(
                'menu_parent_id' => $row['menu_parent_id'],
                'menu_order' => $row['menu_order'],
                'menu_active' => $row['menu_active'],
            ));
        }

        if ($this->db->trans_status() === FALSE) {
            $this->db->trans_rollback();
            return FALSE;
        }

        $this->db->trans_commit();
        return TRUE;
    }
}
