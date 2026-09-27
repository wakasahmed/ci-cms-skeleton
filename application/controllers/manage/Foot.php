<?php defined('BASEPATH') OR exit('No direct script access allowed');

class Foot extends CI_Controller
{
    public $tblName = 'foot';
    public $pKey = 'foot_id';
    public $moduleName = 'Footer Navigation';
    public $controller = 'foot';
    public $user_data = array();

    /** Allowlisted footer types mapped to the human label used on screen. */
    private $typeLabels = array(
        'one' => 'Footer Menu',
        'two' => 'Footer Menu Two',
        'three' => 'Company',
    );

    public function __construct()
    {
        parent::__construct();
        $this->user_data = $this->SqlModel->authAdmin($this->session->userdata('admin_auth'), $this->session->userdata('admin_id'));
        if (empty($this->user_data)) {
            redirect(base_url('manage/login'));
        }

        $settings = $this->SqlModel->getSingleRecord('site_settings', array('id' => 1));
        if (!empty($settings['foot_col_2'])) {
            $this->typeLabels['one'] = $settings['foot_col_2'];
        }
        if (!empty($settings['foot_col_3'])) {
            $this->typeLabels['two'] = $settings['foot_col_3'];
        }
        $this->typeLabels['three'] = 'Email';

        $this->load->model('FootModel');
        $this->load->library('page_menu_hierarchy');
    }

    public function index($type = 'one')
    {
        if (!isset($this->typeLabels[$type])) {
            redirect(base_url('manage'));
            return;
        }

        $menu = $this->FootModel->getFooterMenuData($type);
        $data = array(
            'page_title' => PROJECT_TITLE.' | '.$this->typeLabels[$type],
            'alert' => $this->session->flashdata('alert'),
            'userdata' => $this->user_data,
            'menuActive' => 1,
            'menuFootActive' => $type,
            'useMenuManager' => TRUE,
            'active' => $menu['active'],
            'available' => $menu['available'],
            'maxDepth' => $this->FootModel->maxDepth(),
            'menuLabel' => $this->typeLabels[$type],
            'menuDescription' => 'Arrange pages shown in the '.$this->typeLabels[$type].' footer navigation.',
            'saveUrl' => base_url('manage/foot/save/'.$type),
        );

        $this->load->view('admin/header', $data);
        $this->load->view('admin/navigation');
        $this->load->view('admin/menuManager');
        $this->load->view('admin/footer');
    }

    public function save($type = '')
    {
        if (!isset($this->typeLabels[$type])) {
            redirect(base_url('manage/foot'));
            return;
        }

        $decoded = $this->decodePayload($this->input->post('menu_payload'));

        if ($decoded === NULL) {
            $this->session->set_flashdata('alert', 'invalid');
            redirect(base_url('manage/foot/index/'.$type));
            return;
        }

        $allowedPages = $this->FootModel->getAllowedPages($type);
        $rows = $this->page_menu_hierarchy->flattenPayload($decoded, $allowedPages, $this->FootModel->maxDepth());

        if ($rows === NULL) {
            $this->session->set_flashdata('alert', 'invalid');
            redirect(base_url('manage/foot/index/'.$type));
            return;
        }

        if (!$this->persistRows($rows, $type)) {
            $this->session->set_flashdata('alert', 'error');
            redirect(base_url('manage/foot/index/'.$type));
            return;
        }

        $this->session->set_flashdata('alert', 'success');
        redirect(base_url('manage/foot/index/'.$type));
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

    private function persistRows($rows, $type)
    {
        if (empty($rows)) {
            return TRUE;
        }

        $suffix = $type === 'one' || $type === 'two' || $type === 'three' ? $type : '';
        if ($suffix === '') {
            return FALSE;
        }

        $this->db->trans_begin();

        foreach ($rows as $row) {
            $this->db->where('page_id', $row['page_id'])->update('pages', array(
                'menu_parent_id_'.$suffix => $row['menu_parent_id'],
                'menu_order_'.$suffix => $row['menu_order'],
                'menu_active_'.$suffix => $row['menu_active'],
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
