<?php defined('BASEPATH') OR exit('No direct script access allowed');

class Contact_requests extends CI_Controller {

    public $tblName = 'contact_requests';
    public $pKey = 'id';
    public $moduleName = 'Contact Requests';
    public $moduleNameSingular = 'Contact Request';
    public $moduleDesc = 'Manage contact requests received through the website.';
    public $controller = 'contact-requests';
    public $per_page = 10;
    public $listView = 'contactRequests';
    public $addEditView = 'addContactRequest';
    public $user_data = array();

    public function __construct()
    {
        parent::__construct();
        $this->user_data = $this->SqlModel->authAdmin($this->session->userdata('admin_auth'), $this->session->userdata('admin_id'));
        if (empty($this->user_data))
        {
            redirect(base_url('manage/login'));
        }
    }

    public function index($sortby = 'created_at', $order = 'DESC', $website = '-', $country = 0, $keywords = '-', $pgNo = '')
    {
        $sortColumns = array(
            'id' => 'contact_requests.id',
            'first_name' => 'contact_requests.first_name',
            'last_name' => 'contact_requests.last_name',
            'email' => 'contact_requests.email',
            'subject' => 'contact_requests.subject',
            'phone' => 'contact_requests.phone',
            'country' => 'countries.name',
            'website' => 'contact_requests.website',
            'created_at' => 'contact_requests.created_at',
            'updated_at' => 'contact_requests.updated_at',
        );
        $sortby = array_key_exists($sortby, $sortColumns) ? $sortby : 'created_at';
        $order = strtoupper($order) === 'ASC' ? 'ASC' : 'DESC';
        $website = in_array($website, array('English', 'Arabic'), TRUE) ? $website : '-';
        $country = (int) $country;
        if ($country > 0 && $this->SqlModel->countRecords('countries', array('id' => $country)) === 0)
        {
            $country = 0;
        }

        $allowedPerPage = array_merge(array(0), range(10, 100, 10));
        $requestedPerPage = $this->input->get('per_page');
        if ($requestedPerPage !== NULL && ctype_digit((string) $requestedPerPage) && in_array((int) $requestedPerPage, $allowedPerPage, TRUE))
        {
            $this->session->set_userdata('per_page', (int) $requestedPerPage);
        }
        if ($this->session->userdata('per_page') !== NULL && in_array((int) $this->session->userdata('per_page'), $allowedPerPage, TRUE))
        {
            $this->per_page = (int) $this->session->userdata('per_page');
        }

        $keywords = urldecode((string) $keywords);
        $where = array();
        if ($website !== '-') $where['contact_requests.website'] = $website;
        if ($country > 0) $where['contact_requests.country'] = $country;

        $search = $keywords !== '-' ? array(
            'cols' => 'contact_requests.first_name,contact_requests.last_name,contact_requests.email,contact_requests.subject,contact_requests.phone,contact_requests.message,contact_requests.ip,contact_requests.user_agent',
            'value' => $keywords,
        ) : array();

        $listingTable = 'contact_requests LEFT JOIN countries ON countries.id = contact_requests.country';
        $baseUrl = base_url('manage/'.$this->controller.'/index/'.$sortby.'/'.$order.'/'.$website.'/'.$country.'/'.rawurlencode($keywords));
        $totalRows = $this->SqlModel->countRecords($listingTable, $where, $search);
        $uriSegment = 9;
        $offset = max(0, (int) $this->uri->segment($uriSegment, 0));

        $this->pagination->initialize(admin_pagination_config($baseUrl, $totalRows, $this->per_page, $uriSegment));

        $fields = 'contact_requests.id, contact_requests.first_name, contact_requests.last_name, contact_requests.email, '
            .'contact_requests.subject, contact_requests.phone, contact_requests.message, contact_requests.ip, '
            .'contact_requests.user_agent, contact_requests.created_at, contact_requests.updated_at, contact_requests.country, '
            .'contact_requests.website, countries.name AS country_name';
        $records = $this->SqlModel->getRecords($fields, $listingTable, $sortColumns[$sortby], $order, $where, $search, $this->per_page, $offset, FALSE);

        $data = array(
            'alert' => $this->session->flashdata('alert'), 'page_title' => PROJECT_TITLE.' | '.$this->moduleName,
            'userdata' => $this->user_data, 'contactRequestsActive' => 1,
            'total_rows' => $totalRows, 'per_page' => $this->per_page,
            'records' => $records, 'paginate' => $this->pagination->create_links(),
            'sortby' => $sortby, 'order' => $order === 'ASC' ? 'DESC' : 'ASC', 'page_numb' => $offset,
            'website' => $website, 'country' => $country, 'keywords' => $keywords,
            'countries' => $this->SqlModel->getRecords('id,name', 'countries', 'name', 'ASC'),
        );
        $this->render($this->listView, $data);
    }

    public function control($alert = '', $editID = '')
    {
        $isEdit = ($alert === 'edit');
        $record = array();
        $postedData = $this->session->flashdata($this->controller.'_data');
        if ($isEdit)
        {
            $editID = (int) $editID;
            $record = $this->SqlModel->getSingleRecord($this->tblName, array($this->pKey => $editID));
            if (empty($record))
            {
                $this->session->set_flashdata('alert', 'error');
                redirect(base_url('manage/'.$this->controller));
                return;
            }
        }

        $viewRecord = $record;
        if (is_array($postedData))
        {
            foreach (array('first_name', 'last_name', 'email', 'subject', 'phone', 'message', 'ip', 'user_agent', 'country', 'website') as $sharedColumn)
            {
                if (array_key_exists($sharedColumn, $postedData)) $viewRecord[$sharedColumn] = $postedData[$sharedColumn];
            }
        }

        $data = array(
            'contactRequestsActive' => 1, 'alert' => $isEdit ? 'edit' : '',
            'form_error' => $this->session->flashdata('form_error'), 'tbl_data' => $viewRecord,
            'page_title' => PROJECT_TITLE.' | '.($isEdit ? 'Edit' : 'Add').' '.$this->moduleNameSingular,
            'userdata' => $this->user_data,
            'countries' => $this->SqlModel->getRecords('id,name', 'countries', 'name', 'ASC'),
        );
        $this->render($this->addEditView, $data);
    }

    public function addRecord()
    {
        if (!$this->validRequiredInput()) return $this->formFailure('Enter the required fields with valid values.');

        $data = $this->postedContactData();
        $data['created_at'] = date('Y-m-d H:i:s');
        $data['updated_at'] = date('Y-m-d H:i:s');

        $id = $this->SqlModel->insertRecord($this->tblName, $data);
        if (!$id) return $this->formFailure('The contact request could not be saved. Please try again.');

        $this->session->set_flashdata('alert', 'success');
        redirect(base_url('manage/'.$this->controller));
    }

    public function editRecord($editID = '')
    {
        $editID = (int) $editID;
        $current = $this->SqlModel->getSingleRecord($this->tblName, array($this->pKey => $editID));
        if (empty($current))
        {
            $this->session->set_flashdata('alert', 'error');
            redirect(base_url('manage/'.$this->controller));
            return;
        }
        if (!$this->validRequiredInput()) return $this->formFailure('Enter the required fields with valid values.', $editID);

        $data = $this->postedContactData();
        $data['updated_at'] = date('Y-m-d H:i:s');

        if (!$this->SqlModel->updateRecord($this->tblName, $data, array($this->pKey => $editID))) return $this->formFailure('The contact request could not be updated. Please try again.', $editID);

        $this->session->set_flashdata('alert', 'editsuccess');
        redirect(base_url('manage/'.$this->controller));
    }

    public function details($requestID = 0)
    {
        $this->output->set_content_type('application/json');
        $requestID = (int) $requestID;
        if ($requestID <= 0)
        {
            return $this->output
                ->set_status_header(404)
                ->set_output(json_encode(array('status' => 'false', 'message' => 'The contact request was not found.')));
        }

        $record = $this->db
            ->select('contact_requests.*, countries.name AS country_name')
            ->from($this->tblName)
            ->join('countries', 'countries.id = contact_requests.country', 'left')
            ->where('contact_requests.'.$this->pKey, $requestID)
            ->get()
            ->row_array();

        if (empty($record))
        {
            return $this->output
                ->set_status_header(404)
                ->set_output(json_encode(array('status' => 'false', 'message' => 'The contact request was not found.')));
        }

        $response = array(
            'id' => (int) $record['id'],
            'first_name' => (string) $record['first_name'],
            'last_name' => (string) $record['last_name'],
            'email' => (string) $record['email'],
            'subject' => (string) $record['subject'],
            'phone' => (string) $record['phone'],
            'country' => (string) $record['country_name'],
            'website' => (string) $record['website'],
            'message' => (string) $record['message'],
            'created_at' => date('M d, Y, h:i A', strtotime($record['created_at'])),
            'updated_at' => date('M d, Y, h:i A', strtotime($record['updated_at'])),
            'ip' => (string) $record['ip'],
            'user_agent' => (string) $record['user_agent'],
        );

        return $this->output->set_output(json_encode(array('status' => 'true', 'record' => $response)));
    }

    public function delete($deleteID = '')
    {
        $id = (int) $deleteID;
        $record = $id > 0 ? $this->SqlModel->getSingleRecord($this->tblName, array($this->pKey => $id)) : array();
        if (empty($record) || !$this->SqlModel->deleteRecord($this->tblName, array($this->pKey => $id)))
        {
            $this->session->set_flashdata('alert', 'deleteerror');
        }
        else
        {
            $this->session->set_flashdata('alert', 'deletesuccess');
        }
        redirect(base_url('manage/'.$this->controller));
    }

    public function deleteall()
    {
        $ids = array_values(array_unique(array_filter(array_map('intval', (array) $this->input->post('records')))));
        if (empty($ids))
        {
            $this->session->set_flashdata('alert', 'deleteerror');
            redirect(base_url('manage/'.$this->controller));
            return;
        }

        $existing = $this->db->select($this->pKey)->where_in($this->pKey, $ids)->get($this->tblName)->result_array();
        if (count($existing) !== count($ids))
        {
            $this->session->set_flashdata('alert', 'deleteerror');
            redirect(base_url('manage/'.$this->controller));
            return;
        }

        $this->db->trans_begin();
        $this->db->where_in($this->pKey, $ids)->delete($this->tblName);
        if ($this->db->trans_status() === FALSE || $this->db->affected_rows() !== count($ids))
        {
            $this->db->trans_rollback();
            $this->session->set_flashdata('alert', 'deleteerror');
        }
        else
        {
            $this->db->trans_commit();
            $this->session->set_flashdata('alert', 'deletesuccess');
        }
        redirect(base_url('manage/'.$this->controller));
    }

    private function render($view, $data)
    {
        $this->load->view('admin/header', $data);
        $this->load->view('admin/navigation');
        $this->load->view('admin/'.$view);
        $this->load->view('admin/footer');
    }

    private function postedContactData()
    {
        $phone = trim((string) $this->input->post('phone'));
        $ip = trim((string) $this->input->post('ip'));
        $userAgent = trim((string) $this->input->post('user_agent'));
        $country = trim((string) $this->input->post('country'));
        $website = $this->input->post('website');

        $countryId = NULL;
        if ($country !== '' && (int) $country > 0 && $this->SqlModel->countRecords('countries', array('id' => (int) $country)) > 0)
        {
            $countryId = (int) $country;
        }

        return array(
            'first_name' => trim((string) $this->input->post('first_name')),
            'last_name' => trim((string) $this->input->post('last_name')),
            'email' => trim((string) $this->input->post('email')),
            'subject' => trim((string) $this->input->post('subject')),
            'phone' => $phone !== '' ? $phone : NULL,
            'message' => trim((string) $this->input->post('message')),
            'ip' => $ip !== '' ? $ip : NULL,
            'user_agent' => $userAgent !== '' ? $userAgent : NULL,
            'country' => $countryId,
            'website' => in_array($website, array('English', 'Arabic'), TRUE) ? $website : 'English',
        );
    }

    private function validRequiredInput()
    {
        $firstName = trim((string) $this->input->post('first_name'));
        $lastName = trim((string) $this->input->post('last_name'));
        $email = trim((string) $this->input->post('email'));
        $subject = trim((string) $this->input->post('subject'));
        $message = trim((string) $this->input->post('message'));
        $phone = trim((string) $this->input->post('phone'));
        $ip = trim((string) $this->input->post('ip'));
        $country = trim((string) $this->input->post('country'));
        $website = $this->input->post('website');

        if ($firstName === '' || mb_strlen($firstName) > 255) return FALSE;
        if ($lastName === '' || mb_strlen($lastName) > 255) return FALSE;
        if ($email === '' || mb_strlen($email) > 100 || filter_var($email, FILTER_VALIDATE_EMAIL) === FALSE) return FALSE;
        if ($subject === '' || mb_strlen($subject) > 255) return FALSE;
        if ($message === '') return FALSE;
        if ($phone !== '' && mb_strlen($phone) > 50) return FALSE;
        if ($ip !== '' && mb_strlen($ip) > 50) return FALSE;
        if ($country !== '' && ((int) $country <= 0 || $this->SqlModel->countRecords('countries', array('id' => (int) $country)) === 0)) return FALSE;
        if (!in_array($website, array('English', 'Arabic'), TRUE)) return FALSE;

        return TRUE;
    }

    private function formFailure($message, $editID = 0)
    {
        $posted = $this->input->post(NULL, FALSE);
        $this->session->set_flashdata($this->controller.'_data', is_array($posted) ? $posted : array());
        $this->session->set_flashdata('form_error', $message);
        redirect(base_url('manage/'.$this->controller.'/control'.($editID ? '/edit/'.(int) $editID : '')));
    }
}
