<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Tour_guide_availability extends CI_Controller
{
    public $tblName = 'tour_guide_availability';
    public $colPrefix = 'avail_';
    public $pKey = 'avail_id';
    public $moduleName = 'Tour Guide(s) Availability';
    public $controller = 'tour-guide-availability';
    public $per_page = 10;
    public $listView = 'tourGuideAvailability';

    public function __construct()
    {
        parent::__construct();
        $this->load->helper('admin_pagination');
        $this->user_data = $this->SqlModel->authAdmin(
            $this->session->userdata('admin_auth'),
            $this->session->userdata('admin_id')
        );

        if (empty($this->user_data)) {
            redirect(base_url('manage/login'));
        }
    }

    public function index($sortby = 'avail_date', $order = 'DESC', $keywords = '-', $tour_guide_id = '0', $date_start = '-', $date_end = '-', $book_status = '-', $pg_no = '')
    {
        $allowedSorts = array($this->pKey, 'tour_guide_name', 'avail_date', 'slot_name', 'book_status', 'avail_added', 'slot_updated');
        $sortby = in_array($sortby, $allowedSorts, TRUE) ? $sortby : 'avail_date';
        $order = strtoupper($order) === 'ASC' ? 'ASC' : 'DESC';
        $requestedPerPage = $this->input->get('per_page');
        if ($requestedPerPage !== NULL && ctype_digit((string) $requestedPerPage) && (int) $requestedPerPage <= 100) {
            $this->session->set_userdata('per_page', (int) $requestedPerPage);
        }
        if ($this->session->userdata('per_page') !== '') {
            $this->per_page = (int) $this->session->userdata('per_page');
        }

        $keywords = urldecode($keywords);
        $date_start = urldecode($date_start);
        $date_end = urldecode($date_end);
        $tour_guide_id = ctype_digit((string) $tour_guide_id) ? (int) $tour_guide_id : 0;
        $book_status = in_array($book_status, array('Available', 'Pending', 'Reserved', 'Unavailable', 'On-hold'), TRUE) ? $book_status : '-';
        $where = array();
        if ($tour_guide_id > 0) {
            $where['avail_tour_guide_id'] = $tour_guide_id;
        }
        if ($this->isValidDate($date_start) && $this->isValidDate($date_end)) {
            $where['avail_date >='] = $date_start;
            $where['avail_date <='] = $date_end;
        } else {
            $date_start = '-';
            $date_end = '-';
        }
        if ($book_status === 'Pending') {
            // Pending comes from the linked booking, not the availability row.
            // On-hold takes precedence in the listing, so exclude it here.
            $where['b.book_status'] = 'Pending';
            $where['a.avail_book_status !='] = 'On-hold';
        } elseif ($book_status !== '-') {
            $where['avail_book_status'] = $book_status;
        }

        $search = $keywords !== '-'
            ? array('cols' => 'tour_guide_name, slot_name, avail_book_status', 'value' => $keywords)
            : array();
        $listingSource = $this->tblName.' a'
            .' INNER JOIN tour_guides d ON a.avail_tour_guide_id = d.tour_guide_id'
            .' INNER JOIN tour_slots s ON s.slot_id = a.avail_slot_id'
            .' LEFT JOIN tour_bookings b ON b.book_avail_id = a.avail_id';
        $baseUrl = base_url('manage/'.$this->controller.'/index/'
            .$sortby.'/'.$order.'/'.$keywords.'/'.$tour_guide_id.'/'.$date_start.'/'.$date_end.'/'.$book_status);
        $totalRows = $this->SqlModel->countRecords($listingSource, $where, $search);
        $offset = ctype_digit((string) $pg_no) ? (int) $pg_no : 0;
        $data = array(
            'alert' => $this->session->flashdata('alert'),
            'total_rows' => $totalRows,
            'per_page' => $this->per_page,
            'page_title' => PROJECT_TITLE.' | '.$this->moduleName,
            'userdata' => $this->user_data,
            'tourGuideAvailabilityActive' => 1,
            'records' => $this->SqlModel->getRecords(
                'a.*, d.*, s.*, b.book_status, b.book_id AS linked_book_id',
                $listingSource,
                $sortby,
                $order,
                $where,
                $search,
                $this->per_page,
                $offset
            ),
            'sortby' => $sortby,
            'order' => $order === 'ASC' ? 'DESC' : 'ASC',
            'page_numb' => $offset,
            'keywords' => $keywords,
            'tour_guide_id' => $tour_guide_id,
            'date_start' => $date_start,
            'date_end' => $date_end,
            'book_status' => $book_status,
            'tour_guides' => $this->SqlModel->getRecords('tour_guide_id, tour_guide_name', 'tour_guides', 'tour_guide_name', 'ASC'),
        );
        $this->pagination->initialize(admin_pagination_config($baseUrl, $totalRows, $this->per_page, 11));
        $data['paginate'] = $this->pagination->create_links();
        $this->render($this->listView, $data);
    }

    public function manageAvailability()
    {
        $data = array(
            'tourGuideAvailabilityActive' => 1,
            'page_title' => PROJECT_TITLE.' | Add or Remove Availability',
            'alert' => $this->session->flashdata('alert'),
            'tour_guides' => $this->SqlModel->getRecords('tour_guide_id, tour_guide_name, tour_guide_image, tour_guide_phone', 'tour_guides', 'tour_guide_name', 'ASC', array('tour_guide_status' => 'Enable')),
            'slots' => $this->enabledSlots('slot_title'),
            'userdata' => $this->user_data,
            'useUserSelect' => TRUE,
        );
        $this->render('manageTourGuideAvailability', $data);
    }

    public function saveManageAvailability()
    {
        $tourGuideIds = $this->validIds($this->input->post('avail_tour_guide_id'));
        $slotIds = $this->validIds($this->input->post('avail_slot_id'));
        $dateStart = $this->normaliseDate($this->input->post('avail_date_start'));
        $dateEnd = $this->normaliseDate($this->input->post('avail_date_end'));
        $action = $this->input->post('avail_action');
        if (empty($tourGuideIds) || empty($slotIds) || !$dateStart || !$dateEnd || $dateEnd < $dateStart || !in_array($action, array('Add', 'Remove'), TRUE)) {
            $this->session->set_flashdata('alert', 'error');
            redirect(base_url('manage/'.$this->controller.'/manageAvailability'));

            return;
        }

        $dates = $this->dateRange($dateStart, $dateEnd);
        $now = date('Y-m-d H:i:s');
        $this->db->trans_start();
        foreach ($tourGuideIds as $guideId) {
            foreach ($slotIds as $slotId) {
                foreach ($dates as $date) {
                    $where = array('avail_tour_guide_id' => $guideId, 'avail_slot_id' => $slotId, 'avail_date' => $date);
                    if ($action === 'Add') {
                        if ($this->SqlModel->countRecords($this->tblName, $where) === 0) {
                            $this->SqlModel->insertRecord($this->tblName, $where + array('avail_added' => $now, 'avail_updated' => $now));
                        }
                    } else {
                        $where['avail_book_status !='] = 'Reserved';
                        $where['avail_book_id'] = 0;
                        $this->SqlModel->deleteRecord($this->tblName, $where);
                    }
                }
            }
        }
        $this->db->trans_complete();
        $this->session->set_flashdata('alert', $this->db->trans_status() ? ($action === 'Add' ? 'success' : 'removesuccess') : 'error');
        redirect(base_url('manage/'.$this->controller));
    }

    public function delete($deleteID = '')
    {
        $deleteID = ctype_digit((string) $deleteID) ? (int) $deleteID : 0;
        $deleted = $deleteID > 0 && $this->SqlModel->deleteRecord($this->tblName, array(
            $this->pKey => $deleteID,
            'avail_book_status !=' => 'Reserved',
            'avail_book_id' => 0,
        ));
        $this->session->set_flashdata('alert', $deleted ? 'deletesuccess' : 'deleteerror');
        redirect(base_url('manage/'.$this->controller));
    }

    public function deleteall()
    {
        $ids = $this->validIds($this->input->post('records'));
        if (empty($ids)) {
            $this->session->set_flashdata('alert', 'deleteerror');
            redirect(base_url('manage/'.$this->controller));

            return;
        }
        $this->db->where_in($this->pKey, $ids);
        $this->db->where('avail_book_status !=', 'Reserved');
        $this->db->where('avail_book_id', 0);
        $deleted = $this->db->delete($this->tblName);
        $this->session->set_flashdata('alert', $deleted ? 'deletesuccess' : 'deleteerror');
        redirect(base_url('manage/'.$this->controller));
    }

    private function enabledSlots($nameColumn)
    {
        return $this->SqlModel->getRecords('slot_id, '.$nameColumn, 'tour_slots', 'slot_order', 'ASC', array('slot_status' => 'Enable'));
    }

    private function validIds($values)
    {
        $values = is_array($values) ? $values : array($values);
        $ids = array();
        foreach ($values as $value) {
            if (ctype_digit((string) $value) && (int) $value > 0) {
                $ids[] = (int) $value;
            }
        }
        return array_values(array_unique($ids));
    }

    private function normaliseDate($date)
    {
        $timestamp = is_string($date) ? strtotime($date) : FALSE;
        return $timestamp === FALSE ? FALSE : date('Y-m-d', $timestamp);
    }

    private function isValidDate($date)
    {
        return is_string($date) && preg_match('/^\\d{4}-\\d{2}-\\d{2}$/', $date) && strtotime($date) !== FALSE;
    }

    private function dateRange($dateStart, $dateEnd)
    {
        $dates = array();
        for ($timestamp = strtotime($dateStart); $timestamp <= strtotime($dateEnd); $timestamp = strtotime('+1 day', $timestamp)) {
            $dates[] = date('Y-m-d', $timestamp);
        }
        return $dates;
    }

    private function render($view, $data)
    {
        $this->load->view('admin/header', $data);
        $this->load->view('admin/navigation');
        $this->load->view('admin/'.$view);
        $this->load->view('admin/footer');
    }
}
