<?php defined('BASEPATH') OR exit('No direct script access allowed');

class Plan_your_visit extends CI_Controller {

    public $tblName = 'plan_your_visit';
    public $pKey = 'id';
    public $moduleName = 'Plan Your Visit';
    public $moduleNameSingular = 'Plan Your Visit Request';
    public $moduleDesc = 'Manage trip-planning requests received through the website.';
    public $controller = 'plan-your-visit';
    public $per_page = 10;
    public $listView = 'planYourVisit';
    public $addEditView = 'addPlanYourVisit';
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

    public function index($sortby = 'created_at', $order = 'DESC', $website = '-', $country = 0, $stepCompleted = '-', $keywords = '-', $pgNo = '')
    {
        $this->load->helper('report');
        $sortColumns = array(
            'id' => 'plan_your_visit.id',
            'name' => 'plan_your_visit.name',
            'email' => 'plan_your_visit.email',
            'phone' => 'plan_your_visit.phone',
            'country' => 'countries.name',
            'arrival_date' => 'plan_your_visit.arrival_date',
            'departure_date' => 'plan_your_visit.departure_date',
            'guests' => 'plan_your_visit.guests',
            'step_completed' => 'plan_your_visit.step_completed',
            'preferred_language' => 'plan_your_visit.preferred_language',
            'preferred_time' => 'plan_your_visit.preferred_time',
            'website' => 'plan_your_visit.website',
            'created_at' => 'plan_your_visit.created_at',
            'updated_at' => 'plan_your_visit.updated_at',
        );
        $sortby = array_key_exists($sortby, $sortColumns) ? $sortby : 'created_at';
        $order = strtoupper($order) === 'ASC' ? 'ASC' : 'DESC';
        $website = in_array($website, array('English', 'Arabic'), TRUE) ? $website : '-';
        $country = (int) $country;
        if ($country > 0 && $this->SqlModel->countRecords('countries', array('id' => $country)) === 0)
        {
            $country = 0;
        }
        $stepCompleted = ($stepCompleted === '-' || $stepCompleted === NULL) ? '-' : (string) $stepCompleted;
        if ($stepCompleted !== '-' && (!ctype_digit($stepCompleted) || (int) $stepCompleted < 0 || (int) $stepCompleted > 255))
        {
            $stepCompleted = '-';
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
        if ($website !== '-') $where['plan_your_visit.website'] = $website;
        if ($country > 0) $where['plan_your_visit.country'] = $country;
        if ($stepCompleted !== '-') $where['plan_your_visit.step_completed'] = (int) $stepCompleted;

        $search = $keywords !== '-' ? array(
            'cols' => 'plan_your_visit.name,plan_your_visit.email,plan_your_visit.phone,plan_your_visit.preferred_language,'
                .'plan_your_visit.preferred_time,plan_your_visit.interests,plan_your_visit.message,plan_your_visit.ip,'
                .'plan_your_visit.user_agent,plan_your_visit.session_token',
            'value' => $keywords,
        ) : array();

        $listingTable = 'plan_your_visit LEFT JOIN countries ON countries.id = plan_your_visit.country';
        $baseUrl = base_url('manage/'.$this->controller.'/index/'.$sortby.'/'.$order.'/'.$website.'/'.$country.'/'.$stepCompleted.'/'.rawurlencode($keywords));
        $totalRows = $this->SqlModel->countRecords($listingTable, $where, $search);
        $uriSegment = 10;
        $offset = max(0, (int) $this->uri->segment($uriSegment, 0));

        $this->pagination->initialize(admin_pagination_config($baseUrl, $totalRows, $this->per_page, $uriSegment));

        $fields = 'plan_your_visit.id, plan_your_visit.name, plan_your_visit.email, plan_your_visit.phone, '
            .'plan_your_visit.country, plan_your_visit.arrival_date, plan_your_visit.departure_date, plan_your_visit.guests, '
            .'plan_your_visit.step_completed, plan_your_visit.preferred_language, plan_your_visit.preferred_time, '
            .'plan_your_visit.interests, plan_your_visit.message, plan_your_visit.created_at, plan_your_visit.updated_at, '
            .'plan_your_visit.ip, plan_your_visit.user_agent, plan_your_visit.website, plan_your_visit.session_token, '
            .'countries.name AS country_name';
        $records = $this->SqlModel->getRecords($fields, $listingTable, $sortColumns[$sortby], $order, $where, $search, $this->per_page, $offset, FALSE);

        $data = array(
            'alert' => $this->session->flashdata('alert'), 'page_title' => PROJECT_TITLE.' | '.$this->moduleName,
            'userdata' => $this->user_data, 'palanYourVisitActive' => 1,
            'total_rows' => $totalRows, 'per_page' => $this->per_page,
            'records' => $records, 'paginate' => $this->pagination->create_links(),
            'sortby' => $sortby, 'order' => $order === 'ASC' ? 'DESC' : 'ASC', 'page_numb' => $offset,
            'website' => $website, 'country' => $country, 'stepCompleted' => $stepCompleted, 'keywords' => $keywords,
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
            foreach (array(
                'name', 'email', 'phone', 'country', 'arrival_date', 'departure_date', 'guests', 'step_completed',
                'preferred_language', 'preferred_time', 'interests', 'message', 'ip', 'user_agent', 'website', 'session_token',
            ) as $sharedColumn)
            {
                if (array_key_exists($sharedColumn, $postedData)) $viewRecord[$sharedColumn] = $postedData[$sharedColumn];
            }
        }

        $data = array(
            'palanYourVisitActive' => 1, 'alert' => $isEdit ? 'edit' : '',
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

        $data = $this->postedTripData();
        $data['created_at'] = date('Y-m-d H:i:s');
        $data['updated_at'] = date('Y-m-d H:i:s');

        $id = $this->SqlModel->insertRecord($this->tblName, $data);
        if (!$id) return $this->formFailure('The Plan Your Visit request could not be saved. Please try again.');

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

        $data = $this->postedTripData();
        $data['updated_at'] = date('Y-m-d H:i:s');

        if (!$this->SqlModel->updateRecord($this->tblName, $data, array($this->pKey => $editID))) return $this->formFailure('The Plan Your Visit request could not be updated. Please try again.', $editID);

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
                ->set_output(json_encode(array('status' => 'false', 'message' => 'The Plan Your Visit request was not found.')));
        }

        $record = $this->db
            ->select('plan_your_visit.*, countries.name AS country_name')
            ->from($this->tblName)
            ->join('countries', 'countries.id = plan_your_visit.country', 'left')
            ->where('plan_your_visit.'.$this->pKey, $requestID)
            ->get()
            ->row_array();

        if (empty($record))
        {
            return $this->output
                ->set_status_header(404)
                ->set_output(json_encode(array('status' => 'false', 'message' => 'The Plan Your Visit request was not found.')));
        }

        $response = array(
            'id' => (int) $record['id'],
            'name' => (string) $record['name'],
            'email' => (string) $record['email'],
            'phone' => (string) $record['phone'],
            'country' => (string) $record['country_name'],
            'arrival_date' => $record['arrival_date'] ? date(ADMIN_DATE_FORMAT, strtotime($record['arrival_date'])) : '',
            'departure_date' => $record['departure_date'] ? date(ADMIN_DATE_FORMAT, strtotime($record['departure_date'])) : '',
            'guests' => $record['guests'] !== NULL ? (int) $record['guests'] : NULL,
            'step_completed' => (int) $record['step_completed'],
            'preferred_language' => (string) $record['preferred_language'],
            'preferred_time' => (string) $record['preferred_time'],
            'interests' => (string) $record['interests'],
            'message' => (string) $record['message'],
            'created_at' => date('M d, Y, h:i A', strtotime($record['created_at'])),
            'updated_at' => date('M d, Y, h:i A', strtotime($record['updated_at'])),
            'ip' => (string) $record['ip'],
            'user_agent' => (string) $record['user_agent'],
            'website' => (string) $record['website'],
            'session_token' => (string) $record['session_token'],
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

    private function postedTripData()
    {
        $phone = trim((string) $this->input->post('phone'));
        $ip = trim((string) $this->input->post('ip'));
        $userAgent = trim((string) $this->input->post('user_agent'));
        $country = trim((string) $this->input->post('country'));
        $website = $this->input->post('website');
        $arrivalDate = trim((string) $this->input->post('arrival_date'));
        $departureDate = trim((string) $this->input->post('departure_date'));
        $guests = trim((string) $this->input->post('guests'));
        $stepCompleted = trim((string) $this->input->post('step_completed'));
        $preferredLanguage = trim((string) $this->input->post('preferred_language'));
        $preferredTime = trim((string) $this->input->post('preferred_time'));
        $interests = trim((string) $this->input->post('interests'));
        $message = trim((string) $this->input->post('message'));
        $sessionToken = trim((string) $this->input->post('session_token'));

        $countryId = NULL;
        if ($country !== '' && (int) $country > 0 && $this->SqlModel->countRecords('countries', array('id' => (int) $country)) > 0)
        {
            $countryId = (int) $country;
        }

        return array(
            'name' => trim((string) $this->input->post('name')),
            'email' => trim((string) $this->input->post('email')),
            'phone' => $phone !== '' ? $phone : NULL,
            'country' => $countryId,
            'arrival_date' => $arrivalDate !== '' ? $this->toStorageDate($arrivalDate) : NULL,
            'departure_date' => $departureDate !== '' ? $this->toStorageDate($departureDate) : NULL,
            'guests' => $guests !== '' ? (int) $guests : NULL,
            'step_completed' => $stepCompleted !== '' ? (int) $stepCompleted : 0,
            'preferred_language' => $preferredLanguage !== '' ? $preferredLanguage : NULL,
            'preferred_time' => $preferredTime !== '' ? $preferredTime : NULL,
            'interests' => $interests !== '' ? $interests : NULL,
            'message' => $message !== '' ? $message : NULL,
            'ip' => $ip !== '' ? $ip : NULL,
            'user_agent' => $userAgent !== '' ? $userAgent : NULL,
            'website' => in_array($website, array('English', 'Arabic'), TRUE) ? $website : 'English',
            'session_token' => $sessionToken !== '' ? $sessionToken : NULL,
        );
    }

    private function validRequiredInput()
    {
        $name = trim((string) $this->input->post('name'));
        $email = trim((string) $this->input->post('email'));
        $phone = trim((string) $this->input->post('phone'));
        $country = trim((string) $this->input->post('country'));
        $website = $this->input->post('website');
        $arrivalDate = trim((string) $this->input->post('arrival_date'));
        $departureDate = trim((string) $this->input->post('departure_date'));
        $guests = trim((string) $this->input->post('guests'));
        $stepCompleted = trim((string) $this->input->post('step_completed'));
        $preferredLanguage = trim((string) $this->input->post('preferred_language'));
        $preferredTime = trim((string) $this->input->post('preferred_time'));
        $ip = trim((string) $this->input->post('ip'));
        $sessionToken = trim((string) $this->input->post('session_token'));

        if ($name === '' || mb_strlen($name) > 255) return FALSE;
        if ($email === '' || mb_strlen($email) > 255 || filter_var($email, FILTER_VALIDATE_EMAIL) === FALSE) return FALSE;
        if ($phone !== '' && mb_strlen($phone) > 50) return FALSE;
        if ($country === '' || (int) $country <= 0 || $this->SqlModel->countRecords('countries', array('id' => (int) $country)) === 0) return FALSE;

        if ($arrivalDate !== '' && !$this->isValidDate($arrivalDate)) return FALSE;
        if ($departureDate !== '' && !$this->isValidDate($departureDate)) return FALSE;
        if ($arrivalDate !== '' && $departureDate !== '' && strtotime($this->toStorageDate($departureDate)) < strtotime($this->toStorageDate($arrivalDate))) return FALSE;

        if ($guests !== '' && (!ctype_digit($guests) || (int) $guests < 1 || (int) $guests > 255)) return FALSE;
        if ($stepCompleted === '' || !ctype_digit($stepCompleted) || (int) $stepCompleted < 0 || (int) $stepCompleted > 255) return FALSE;

        if ($preferredLanguage !== '' && mb_strlen($preferredLanguage) > 255) return FALSE;
        if ($preferredTime !== '' && mb_strlen($preferredTime) > 255) return FALSE;
        if ($ip !== '' && mb_strlen($ip) > 50) return FALSE;
        if ($sessionToken !== '' && mb_strlen($sessionToken) > 64) return FALSE;
        if (!in_array($website, array('English', 'Arabic'), TRUE)) return FALSE;

        return TRUE;
    }

    private function isValidDate($date)
    {
        $parsed = DateTime::createFromFormat('m/d/Y', $date);
        return $parsed !== FALSE && $parsed->format('m/d/Y') === $date;
    }

    private function toStorageDate($date)
    {
        $parsed = DateTime::createFromFormat('m/d/Y', $date);
        return $parsed !== FALSE ? $parsed->format('Y-m-d') : NULL;
    }

    private function formFailure($message, $editID = 0)
    {
        $posted = $this->input->post(NULL, FALSE);
        $this->session->set_flashdata($this->controller.'_data', is_array($posted) ? $posted : array());
        $this->session->set_flashdata('form_error', $message);
        redirect(base_url('manage/'.$this->controller.'/control'.($editID ? '/edit/'.(int) $editID : '')));
    }
}
