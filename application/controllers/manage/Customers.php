<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Customers: the website accounts (PROJECT_PLAN.md, Phase 10).
 *
 * Customers create and edit their own accounts, so this module only lists
 * them, shows one with its appointments, and enables or disables it.
 * Disabling signs the customer out everywhere (Customer_auth re-reads the
 * account on every request; remembered devices are revoked here).
 */
class Customers extends CI_Controller
{
    public $tblName = 'customers';
    public $colPrefix = 'customer_';
    public $pKey = 'customer_id';
    public $moduleName = 'Customers';
    public $moduleNameSingular = 'Customer';
    public $moduleDesc = 'Website accounts: who signed up, whether their email is confirmed, and their appointments.';
    public $controller = 'customers';
    public $per_page = 10;
    public $tStatus = 'customer_status';
    public $listView = 'customers';
    public $detailView = 'viewCustomer';
    public $user_data = array();

    public function __construct()
    {
        parent::__construct();

        $this->user_data = $this->SqlModel->authAdmin(
            $this->session->userdata('admin_auth'),
            $this->session->userdata('admin_id')
        );

        if (empty($this->user_data)) {
            redirect(base_url('manage/login'));
        }

        $this->load->helper(array('admin_input', 'admin_listing'));
        $this->load->model(array('Customer_model', 'Customer_token_model'));
    }

    public function index($sortby = 'customer_added', $order = 'DESC', $status = '-', $keywords = '-', $pg_no = '')
    {
        $allowedSorts = array(
            $this->pKey,
            $this->colPrefix.'name',
            $this->colPrefix.'email',
            $this->tStatus,
            $this->colPrefix.'added',
            $this->colPrefix.'last_login',
        );
        $sortby = in_array($sortby, $allowedSorts, TRUE) ? $sortby : $this->colPrefix.'added';
        $order = strtoupper($order) === 'ASC' ? 'ASC' : 'DESC';
        $status = in_array($status, array('Enable', 'Disable'), TRUE) ? $status : '-';
        $this->applyPerPage();

        $keywords = urldecode((string) $keywords);
        $where = $status === '-' ? array() : array($this->tStatus => $status);
        $search = $keywords !== '-'
            ? array(
                'cols' => $this->colPrefix.'name,'.$this->colPrefix.'email,'.$this->colPrefix.'phone',
                'value' => $keywords,
            )
            : array();
        $baseUrl = base_url(
            'manage/'.$this->controller.'/index/'.$sortby.'/'.$order.'/'.$status.'/'.rawurlencode($keywords)
        );
        $totalRows = $this->SqlModel->countRecords($this->tblName, $where, $search);
        $uriSegment = 8;
        $offset = (int) $this->uri->segment($uriSegment, 0);

        $this->pagination->initialize(
            admin_pagination_config($baseUrl, $totalRows, $this->per_page, $uriSegment)
        );

        $records = $this->SqlModel->getRecords(
            '*',
            $this->tblName,
            $sortby,
            $order,
            $where,
            $search,
            $this->per_page,
            $offset,
            FALSE
        );

        $this->render($this->listView, array(
            'alert' => $this->session->flashdata('alert'),
            'page_title' => PROJECT_TITLE.' | '.$this->moduleName,
            'userdata' => $this->user_data,
            'customersActive' => 1,
            'total_rows' => $totalRows,
            'per_page' => $this->per_page,
            'records' => $records,
            'appointment_counts' => $this->appointmentCounts($records),
            'paginate' => $this->pagination->create_links(),
            'sortby' => $sortby,
            'order' => $order === 'ASC' ? 'DESC' : 'ASC',
            'page_numb' => $offset,
            'status' => $status,
            'keywords' => $keywords,
        ));
    }

    public function view($id = 0)
    {
        $customer = $this->Customer_model->find((int) $id);

        if (empty($customer)) {
            $this->session->set_flashdata('alert', 'error');
            redirect(base_url('manage/'.$this->controller));

            return;
        }

        $this->render($this->detailView, array(
            'page_title' => PROJECT_TITLE.' | '.$customer['customer_name'],
            'userdata' => $this->user_data,
            'customersActive' => 1,
            'customer' => $customer,
            'appointments' => $this->Customer_model->appointments($customer['customer_id']),
            'message' => $this->session->flashdata('customer_message'),
        ));
    }

    /** Enable or disable an account (AJAX, as on the other listings). */
    public function changestatus($id = 0, $status = 'Enable')
    {
        $this->output->set_content_type('application/json');
        $id = (int) $id;

        $updated = in_array($status, array('Enable', 'Disable'), TRUE)
            && $this->Customer_model->update($id, array($this->tStatus => $status))
            && $this->db->affected_rows() === 1;

        if (!$updated) {
            return $this->output->set_output(json_encode(array('status' => 'false')));
        }

        if ($status === 'Disable') {
            $this->Customer_token_model->revokeAllRemember($id);
        }

        return $this->output->set_output(json_encode(array(
            'status' => 'true',
            'id' => $id,
            'currentStatus' => $status,
        )));
    }

    private function render($view, $data)
    {
        $this->load->view('admin/header', $data);
        $this->load->view('admin/navigation');
        $this->load->view('admin/'.$view);
        $this->load->view('admin/footer');
    }

    private function applyPerPage()
    {
        $requestedPerPage = $this->input->get('per_page');

        if ($requestedPerPage !== NULL
            && ctype_digit((string) $requestedPerPage)
            && (int) $requestedPerPage <= 100
        ) {
            $this->session->set_userdata('per_page', (int) $requestedPerPage);
        }

        if ($this->session->userdata('per_page') !== NULL) {
            $this->per_page = (int) $this->session->userdata('per_page');
        }
    }

    /** customer id => number of appointments, for the listed customers. */
    private function appointmentCounts(array $records)
    {
        $ids = array_map('intval', array_column($records, $this->pKey));

        if (empty($ids)) {
            return array();
        }

        $rows = $this->db
            ->select('customer_id, COUNT(*) AS total')
            ->where_in('customer_id', $ids)
            ->group_by('customer_id')
            ->get('appointments')
            ->result_array();

        return array_column(array_map(function ($row) {
            return array((int) $row['customer_id'], (int) $row['total']);
        }, $rows), 1, 0);
    }
}
