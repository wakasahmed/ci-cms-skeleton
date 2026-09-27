<?php defined('BASEPATH') OR exit('No direct script access allowed');

class Tour_reviews extends CI_Controller
{
    public $tblName = 'tour_reviews';
    public $colPrefix = 'tour_review_';
    public $pKey = 'tour_review_id';
    public $moduleName = 'Tour Reviews';
    public $moduleNameSingular = 'Tour Review';
    public $controller = 'tour-reviews';
    public $per_page = 10;
    public $tStatus = 'tour_review_status';
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
    }

    public function index(
        $sortby = 'tour_review_added',
        $order = 'DESC',
        $status = '-',
        $keywords = '-',
        $pg_no = ''
    ) {
        $allowedSorts = array(
            'tour_review_id',
            'tour_review_customer_name',
            'tour_review_tour_name',
            'tour_review_overall_rating',
            'tour_review_status',
            'tour_review_added',
        );
        $sortby = in_array($sortby, $allowedSorts, true)
            ? $sortby
            : 'tour_review_added';
        $order = strtoupper($order) === 'ASC' ? 'ASC' : 'DESC';
        $status = in_array($status, array('New', 'Approved', 'Hidden'), true)
            ? $status
            : '-';
        $allowedPerPage = array_merge(array(0), range(10, 100, 10));
        $requestedPerPage = $this->input->get('per_page');
        if ($requestedPerPage !== null
            && ctype_digit((string) $requestedPerPage)
            && in_array((int) $requestedPerPage, $allowedPerPage, true)) {
            $this->session->set_userdata('per_page', (int) $requestedPerPage);
        }
        if ($this->session->userdata('per_page') !== null
            && in_array((int) $this->session->userdata('per_page'), $allowedPerPage, true)) {
            $this->per_page = (int) $this->session->userdata('per_page');
        }

        $keywords = urldecode((string) $keywords);
        $baseUrl = base_url(
            'manage/' . $this->controller . '/index/' . $sortby . '/' . $order . '/'
            . $status . '/' . urlencode($keywords)
        );
        $uriSegment = 8;
        $offset = max(0, (int) $this->uri->segment($uriSegment, 0));
        $schemaReady = $this->db->table_exists($this->tblName);
        $totalRows = $schemaReady ? $this->reviewQuery($status, $keywords)->count_all_results() : 0;
        $records = array();

        if ($schemaReady) {
            $query = $this->reviewQuery($status, $keywords)
                ->order_by($sortby, $order);
            if ($this->per_page > 0) {
                $query->limit($this->per_page, $offset);
            }
            $records = $query->get()->result_array();
        }

        $this->pagination->initialize(
            admin_pagination_config($baseUrl, $totalRows, $this->per_page, $uriSegment)
        );
        $this->render('tourReviews', array(
            'alert' => $this->session->flashdata('alert'),
            'page_title' => PROJECT_TITLE . ' | ' . $this->moduleName,
            'userdata' => $this->user_data,
            'tourReviewsActive' => 1,
            'schema_ready' => $schemaReady,
            'total_rows' => $totalRows,
            'per_page' => $this->per_page,
            'records' => $records,
            'paginate' => $this->pagination->create_links(),
            'sortby' => $sortby,
            'order' => $order === 'ASC' ? 'DESC' : 'ASC',
            'page_numb' => $offset,
            'status' => $status,
            'keywords' => $keywords,
        ));
    }

    public function control($action = 'view', $id = 0)
    {
        $id = (int) $id;
        if ($action !== 'view' || !$this->db->table_exists($this->tblName)) {
            redirect(base_url('manage/' . $this->controller));
            return;
        }

        $record = $this->db
            ->select('reviews.*, bookings.book_date, bookings.book_slot_name, bookings.book_vehicle_name')
            ->from($this->tblName . ' reviews')
            ->join(
                'tour_bookings bookings',
                'bookings.book_id = reviews.tour_review_booking_id',
                'left'
            )
            ->where('reviews.' . $this->pKey, $id)
            ->get()
            ->row_array();
        if (!$record) {
            redirect(base_url('manage/' . $this->controller));
            return;
        }

        $this->render('viewTourReview', array(
            'page_title' => PROJECT_TITLE . ' | Review #' . $id,
            'userdata' => $this->user_data,
            'tourReviewsActive' => 1,
            'record' => $record,
            'review_message' => $this->session->flashdata('review_message'),
        ));
    }

    public function updateStatus($id = 0)
    {
        $id = (int) $id;
        if (strtoupper($this->input->method()) !== 'POST') {
            show_error('Method not allowed', 405);
            return;
        }
        $status = $this->input->post('tour_review_status');
        $valid = in_array($status, array('New', 'Approved', 'Hidden'), true);
        $updated = $valid && $id > 0 && $this->db->table_exists($this->tblName)
            ? $this->db->where($this->pKey, $id)->update($this->tblName, array(
                $this->tStatus => $status,
                'tour_review_updated' => date('Y-m-d H:i:s'),
            ))
            : false;
        $this->session->set_flashdata('review_message', array(
            'success' => (bool) $updated,
            'text' => $updated
                ? 'Review status updated successfully.'
                : 'Unable to update the review status.',
        ));
        redirect(base_url('manage/' . $this->controller . '/control/view/' . $id));
    }

    private function reviewQuery($status, $keywords)
    {
        $query = $this->db->from($this->tblName);
        if ($status !== '-') {
            $query->where($this->tStatus, $status);
        }
        if ($keywords !== '-') {
            $query->group_start()
                ->like('tour_review_customer_name', $keywords)
                ->or_like('tour_review_customer_email', $keywords)
                ->or_like('tour_review_tour_name', $keywords)
                ->or_like('tour_review_comments', $keywords)
                ->group_end();
        }

        return $query;
    }

    private function render($view, array $data)
    {
        $this->load->view('admin/header', $data);
        $this->load->view('admin/navigation');
        $this->load->view('admin/' . $view);
        $this->load->view('admin/footer');
    }
}
