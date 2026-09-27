<?php if (!defined('BASEPATH')) exit('No direct script access allowed');

class Bookings extends CI_Controller
{
    public $tblName = "tour_bookings";
    public $colPrefix = "book_";
    public $pKey = "";
    public $moduleName = "Bookings";
    public $controller = "";
    public $per_page = "10";
    public $tStatus = "";
    public $listView = "bookings";
    public $addEditView = "viewBooking";
    private $bookingStatuses = array(
        'Completed',
        'Cancelled',
        'Pending',
        'Refunded',
    );

    public function __construct()
    {
        // Call the Model constructor
        parent::__construct();
        $this->user_data = $this->SqlModel->authAdmin($this->session->userdata('admin_auth'), $this->session->userdata('admin_id'));
        if (empty($this->user_data)) {
            redirect(base_url('manage/login'));
        }
        $this->controller = $this->router->fetch_class();
        $this->pKey = $this->colPrefix . "id";
        $this->tStatus = $this->colPrefix . "status";
    }

    public function index($sortby = "book_id", $order = "DESC", $status = "-", $keywords = "-", $pg_no = "")
    {
        $allowedSorts = array('book_id', 'book_name', 'book_tour_name', 'book_tour_guide_name', 'book_date', 'book_status');
        $sortby = in_array($sortby, $allowedSorts, true) ? $sortby : 'book_id';
        $order = strtoupper($order) === 'ASC' ? 'ASC' : 'DESC';
        $status = in_array($status, $this->bookingStatuses, true) ? $status : '-';
        //PER_PAGE_START
        if ($this->input->get('per_page') != "" && is_numeric($this->input->get('per_page')) && (int)$this->input->get('per_page') <= 100) {
            $this->session->set_userdata('per_page', $this->input->get('per_page'));
        }
        if ($this->session->userdata('per_page') != "") {
            $this->per_page = $this->session->userdata('per_page');
        }

        //PER_PAGE_END
        $data['alert'] = $this->session->flashdata('alert');
        $where = array();
		if($status!="-")
		{
			$where['book_status'] = urldecode($status);	
		}
		
        $this->load->helper('report');
        $filters = $this->readListingFilters();
        $where = array_merge($where, $this->filterWhere($filters));
        $filterParams = $this->filterParams($filters);

        $keywords = urldecode($keywords);


        $search = ($keywords != "-") ? array('cols' => 'book_name,book_tour_name,book_id,book_tour_guide_name,book_slot_name,book_vehicle_name', 'value' => urldecode($keywords)) : array();
        $base_url = base_url() . 'manage/' . $this->controller . '/index/' . $sortby . "/" . $order . "/" . $status ."/". $keywords;
        $total_rows = $data['total_rows'] = $this->SqlModel->countRecords($this->tblName, $where, $search);
        $per_page = $data['per_page'] = $this->per_page;
        
		$uri_segment = 8;

        $data['page_title'] = PROJECT_TITLE . " | " . $this->moduleName;
        $data['userdata'] = $this->user_data;
        $data[$this->controller . 'Active'] = 1;

        //Pagination START
        $pconfig = admin_pagination_config($base_url, $total_rows, $this->per_page, $uri_segment);
        // Tour and date filters travel in the query string, so page links must keep it.
        $pconfig['reuse_query_string'] = TRUE;
        $data['per_page'] = $this->per_page;
        $offset = ($this->uri->segment($uri_segment)) ? $this->uri->segment($uri_segment) : 0;
        $this->pagination->initialize($pconfig);
        $data['records'] = $this->SqlModel->getRecords('*', $this->tblName, $sortby, $order, $where, $search, $per_page, $offset);
        $data['records'] = $this->withBookingProgress($data['records']);

        $data['paginate'] = $this->pagination->create_links();
        //Pagination END
        $data['sortby'] = $sortby;
        $data['order'] = ($order == "ASC") ? "DESC" : "ASC";
        $data['page_numb'] = $offset;
        $data['keywords'] = $keywords;
		$data['status'] = $status;
        $data['filter_controls'] = $this->filterControls($filters);
        $data['filter_query'] = empty($filterParams) ? '' : '?' . report_query_string($filterParams);
        $data['has_extra_filters'] = !empty($filterParams);
        $this->load->view('admin/header', $data);
        $this->load->view('admin/navigation');
        $this->load->view('admin/' . $this->listView);
        $this->load->view('admin/footer');
    }

    /**
     * Tour / experience, tour date and booked-on filters, read from the query
     * string like the bookings report. Invalid values are dropped.
     */
    private function readListingFilters()
    {
        $filters = array();

        $tourId = $this->input->get('tour_id');
        if (is_string($tourId) && ctype_digit($tourId) && strlen($tourId) <= 10 && (int) $tourId > 0) {
            $filters['tour_id'] = (int) $tourId;
        }

        foreach (array('date', 'created') as $key) {
            $raw = $this->input->get($key);
            $range = is_string($raw) ? report_parse_date_range(trim($raw)) : NULL;
            if ($range !== NULL) {
                $filters[$key . '_from'] = $range[0];
                $filters[$key . '_to'] = $range[1];
            }
        }

        return $filters;
    }

    private function filterWhere(array $filters)
    {
        $where = array();

        if (!empty($filters['tour_id'])) {
            $where['book_tour_id'] = $filters['tour_id'];
        }
        if (!empty($filters['date_from'])) {
            $where['book_date >='] = $filters['date_from'];
            $where['book_date <='] = $filters['date_to'];
        }
        if (!empty($filters['created_from'])) {
            // book_added is a datetime, so the last day covers the whole day.
            $where['book_added >='] = $filters['created_from'] . ' 00:00:00';
            $where['book_added <='] = $filters['created_to'] . ' 23:59:59';
        }

        return $where;
    }

    private function filterParams(array $filters)
    {
        $params = array();

        if (!empty($filters['tour_id'])) {
            $params['tour_id'] = $filters['tour_id'];
        }
        foreach (array('date', 'created') as $key) {
            if (!empty($filters[$key . '_from'])) {
                $params[$key] = report_format_date_range($filters[$key . '_from'], $filters[$key . '_to']);
            }
        }

        return $params;
    }

    /**
     * Control definitions for the shared report_filter_field partial.
     */
    private function filterControls(array $filters)
    {
        $this->load->model('ReportsModel');

        $groups = array();
        foreach ($this->ReportsModel->filterOptions(array('tours'))['tours'] as $tour) {
            $group = trim((string) $tour['type']) === 'Experience' ? 'Experiences' : 'Tours';
            $groups[$group][] = array((string) $tour['id'], $tour['name']);
        }
        ksort($groups);
        $groups = array_reverse($groups, TRUE);

        $dateValue = function ($key) use ($filters) {
            return !empty($filters[$key . '_from'])
                ? report_format_date_range($filters[$key . '_from'], $filters[$key . '_to'])
                : '';
        };

        return array(
            array(
                'key' => 'tour_id',
                'label' => 'Tour / experience',
                'type' => 'select',
                'search' => TRUE,
                'value' => isset($filters['tour_id']) ? (string) $filters['tour_id'] : '',
                'choices' => array(),
                'groups' => $groups,
                'extra' => array(),
            ),
            array(
                'key' => 'date',
                'label' => 'Tour date',
                'type' => 'daterange',
                'search' => FALSE,
                'value' => $dateValue('date'),
                'choices' => array(),
                'groups' => array(),
                'extra' => array(),
            ),
            array(
                'key' => 'created',
                'label' => 'Booked on',
                'type' => 'daterange',
                'search' => FALSE,
                'value' => $dateValue('created'),
                'choices' => array(),
                'groups' => array(),
                'extra' => array(),
            ),
        );
    }

    public function control($alert = "", $orderID = "")
    {
        $orderID = (int) $orderID;
        $data['datePicker'] = 1;
        $data['alert'] = $this->session->flashdata('alert');
        $data[$this->controller . 'Active'] = 1;
        $data['page_title'] = PROJECT_TITLE . " | View " . rtrim($this->moduleName, 's');

        $data['record'] = $this->SqlModel->getSingleRecord($this->tblName, array($this->pKey => $orderID));
        if (empty($data['record'])) {
            redirect(base_url() . 'manage/' . $this->controller, 'location');
        }
        $records = $this->withBookingProgress(array($data['record']));
        $data['record'] = $records[0];
		$data['feedback'] =array();
        $data['userdata'] = $this->user_data;
        $this->load->model('Manage_booking_model');
        $data['guides'] = $this->Manage_booking_model->guides($data['record']);
        $data['guide'] = $this->db->where('tour_guide_id', (int) $data['record']['book_tour_guide_id'])->get('tour_guides')->row_array();
        $data['notes_ready'] = $this->db->table_exists('tour_booking_internal_notes');
        $data['internal_notes'] = $data['notes_ready']
            ? $this->db->select('notes.*, author.full_name AS author_name, author.user_name AS author_username')
                ->from('tour_booking_internal_notes notes')
                ->join('admin_users author', 'author.id = notes.author_id', 'left')
                ->where('notes.booking_id', $orderID)
                ->order_by('notes.id', 'DESC')->get()->result_array()
            : array();
        $data['booking_message'] = $this->session->flashdata('booking_message');
        $data['booking_values'] = $this->session->flashdata('booking_values');
        $this->load->model('Booking_payment_model');
        $data['payment_schema_ready'] = $this->Booking_payment_model->tablesAvailable();
        $data['moyasar_payment'] = $data['payment_schema_ready']
            ? $this->Booking_payment_model->paymentForBooking($orderID)
            : null;
        $data['payment_refunds'] = $data['payment_schema_ready']
            ? $this->Booking_payment_model->refundsForBooking($orderID)
            : array();
        $data['refund_request_token'] = bin2hex(random_bytes(16));
        $this->load->model('Tour_review_model');
        $reviewBooking = $this->Tour_review_model->booking($orderID);
        $data['review_schema_ready'] = $this->db->table_exists(Tour_review_model::TABLE);
        $data['tour_review'] = $data['review_schema_ready']
            ? $this->Tour_review_model->reviewForBooking($orderID)
            : array();
        $data['review_eligible'] = $this->Tour_review_model->isEligible($reviewBooking);
        $data['review_url'] = $data['review_eligible']
            ? $this->Tour_review_model->reviewUrl(
                $reviewBooking,
                $data['record']['book_lang'] === 'Arabic' ? 'ar' : 'en'
            )
            : '';
        $data['useUserSelect'] = true;
        $this->load->view('admin/header', $data);
        $this->load->view('admin/navigation');
        $this->load->view('admin/' . $this->addEditView);
        $this->load->view('admin/footer');
    }

    private function withBookingProgress(array $records)
    {
        if (empty($records)) {
            return $records;
        }

        $tourIds = array_unique(array_map('intval', array_column($records, 'book_tour_id')));
        $tours = $this->db->select('tour_id, tour_type')
            ->where_in('tour_id', $tourIds)
            ->get('tours')->result_array();
        $types = array_column($tours, 'tour_type', 'tour_id');

        foreach ($records as &$record) {
            $tourId = (int) $record['book_tour_id'];
            $record['tour_type'] = isset($types[$tourId])
                ? trim($types[$tourId])
                : '';
            $record['total_steps'] = $record['tour_type'] === 'Experience'
                ? 5
                : 6;
        }
        unset($record);

        return $records;
    }

    public function saveDetails($id = 0)
    {
        $id = (int) $id;
        if ($this->input->method() !== 'post') {
            show_error('Method not allowed', 405);
            return;
        }
        $this->db->trans_begin();
        $booking = $this->db->query('SELECT * FROM tour_bookings WHERE book_id = ? FOR UPDATE', array($id))->row_array();
        if (!$booking) {
            $this->db->trans_rollback();
            show_404();
            return;
        }
        $this->load->library('form_validation');
        $this->form_validation->set_rules('book_name', 'Name', 'trim|required|max_length[255]');
        $this->form_validation->set_rules('book_email', 'Email', 'trim|required|valid_email|max_length[255]');
        $this->form_validation->set_rules('book_phone', 'Phone', 'trim|required|max_length[255]');
        $this->form_validation->set_rules('book_address', 'Pickup', 'trim|max_length[255]');
        $this->form_validation->set_rules('book_paid_amount', 'Total paid', 'required|numeric|greater_than_equal_to[0]|less_than_equal_to[4294967295]');
        $this->form_validation->set_rules('book_payment_method', 'Payment method', 'trim|max_length[100]');
        $this->form_validation->set_rules('book_payer_email', 'Payer email', 'trim|valid_email|max_length[255]');
        $this->form_validation->set_rules('book_transaction_id', 'Transaction ID', 'trim|max_length[255]');
        $values = array();
        foreach (array('book_name', 'book_email', 'book_phone', 'book_address', 'book_paid_amount', 'book_payment_method', 'book_payer_email', 'book_transaction_id') as $field) {
            $values[$field] = trim((string) $this->input->post($field));
        }
        $valid = $this->form_validation->run();
        $this->load->model('Booking_payment_model');
        $moyasarPayment = $this->Booking_payment_model->paymentForBooking($id);
        if ($moyasarPayment) {
            // Verified provider values are read-only; admins may still edit the
            // ordinary customer details in this form.
            foreach (array(
                'book_paid_amount',
                'book_payment_method',
                'book_payer_email',
                'book_transaction_id',
            ) as $paymentField) {
                $values[$paymentField] = $booking[$paymentField];
            }
        }
        if ($valid) {
            // The amount is edited as whole currency units; round any fractional input.
            $values['book_paid_amount'] = (string) (int) round((float) $values['book_paid_amount']);
        }
        if (!$valid || (int) $values['book_paid_amount'] > (int) $booking['book_fee']
            || (int) $values['book_paid_amount'] < (int) $booking['book_refund_amount']) {
            $this->session->set_flashdata('booking_values', $values);
            $this->session->set_flashdata('booking_message', array('success' => false, 'text' => strip_tags(validation_errors()) ?: 'Total paid must not exceed the booking total or fall below the recorded refund.'));
            $this->db->trans_rollback();
        } else {
            $values['book_updated'] = date('Y-m-d H:i:s');
            if ((int) $values['book_paid_amount'] !== (int) $booking['book_paid_amount']) {
                $values['book_payment_date'] = (int) $values['book_paid_amount'] > 0 ? $values['book_updated'] : null;
            }
            $ok = $this->db->where('book_id', $id)->update($this->tblName, $values);
            if ($ok && $this->db->trans_status()) {
                $ok = $this->db->trans_commit();
            } else {
                $this->db->trans_rollback();
                $ok = false;
            }
            $this->session->set_flashdata('booking_message', array('success' => $ok, 'text' => $ok ? 'Booking details updated successfully.' : 'Unable to update the booking. Please try again.'));
        }
        redirect(base_url('manage/bookings/control/view/' . $id));
    }

    public function changeGuide($id = 0)
    {
        if ($this->input->method() !== 'post') {
            show_error('Method not allowed', 405);
            return;
        }
        $this->load->model('Manage_booking_model');
        $this->load->library('Booking_email_service');
        $guideId = (int) $this->input->post('guide_id');
        // The previous guide's name must be read before the guide is replaced.
        $previousGuide = $this->booking_email_service->assignedGuide((int) $id);
        $ok = $this->Manage_booking_model->changeGuide((int) $id, $guideId);
        $successText = 'Tour guide updated successfully.';
        if ($ok && !empty($previousGuide) && $previousGuide['guide_id'] !== $guideId) {
            // The customer is told about a replaced guide; a first assignment has no previous guide.
            if ($previousGuide['guide_id'] > 0
                && !$this->sendGuideChangeEmail((int) $id, $previousGuide['name'])
            ) {
                $successText .= ' The guide change email could not be sent to the customer.';
            }
            if (!$this->sendNewGuideAssignmentEmail((int) $id)) {
                $successText .= ' The assignment email could not be sent to the new tour guide.';
            }
            if ($previousGuide['guide_id'] > 0
                && !$this->sendPreviousGuideUnassignmentEmail((int) $id, $previousGuide)
            ) {
                $successText .= ' The unassignment email could not be sent to the previous tour guide.';
            }
        }
        $this->session->set_flashdata('booking_message', array(
            'success' => $ok,
            'text' => $ok ? $successText : 'That guide is no longer available for this booking\'s date, time, tour, and language. Please choose another guide.',
        ));
        redirect(base_url('manage/bookings/control/view/' . (int) $id));
    }

    /**
     * Email the customer about the new guide (template 9). Only confirmed (Completed) bookings
     * are emailed. The guide is already changed, so a failure is logged and reported, not rolled back.
     */
    private function sendGuideChangeEmail($bookingId, $previousGuideName)
    {
        try {
            if (!$this->bookingIsCompleted($bookingId)) {
                return true;
            }

            return (bool) $this->booking_email_service->sendCustomerGuideChange($bookingId, $previousGuideName);
        } catch (Throwable $exception) {
            log_message(
                'error',
                'Booking guide change email failed for booking ID ' . (int) $bookingId . ': '
                . $exception->getMessage()
            );

            return false;
        }
    }

    /**
     * Tell the newly assigned tour guide about the tour (template 10, the same email sent when a
     * booking is completed). Only confirmed (Completed) bookings are emailed; a failure is logged
     * and reported, not rolled back.
     */
    private function sendNewGuideAssignmentEmail($bookingId)
    {
        try {
            if (!$this->bookingIsCompleted($bookingId)) {
                return true;
            }

            return (bool) $this->booking_email_service->sendTourGuideAssignment($bookingId);
        } catch (Throwable $exception) {
            log_message(
                'error',
                'Booking guide assignment email failed for booking ID ' . (int) $bookingId . ': '
                . $exception->getMessage()
            );

            return false;
        }
    }

    /**
     * Tell the replaced guide they were unassigned (template 15). Only confirmed (Completed)
     * bookings are emailed. $previousGuide is the snapshot taken before the change; a failure is
     * logged and reported, not rolled back.
     */
    private function sendPreviousGuideUnassignmentEmail($bookingId, array $previousGuide)
    {
        try {
            if (!$this->bookingIsCompleted($bookingId)) {
                return true;
            }

            return (bool) $this->booking_email_service->sendPreviousGuideUnassignment($bookingId, $previousGuide);
        } catch (Throwable $exception) {
            log_message(
                'error',
                'Booking guide unassignment email failed for booking ID ' . (int) $bookingId . ': '
                . $exception->getMessage()
            );

            return false;
        }
    }

    private function bookingIsCompleted($bookingId)
    {
        $booking = $this->db->select('book_status')->where('book_id', (int) $bookingId)->get($this->tblName)->row_array();

        return !empty($booking) && $booking['book_status'] === 'Completed';
    }

    public function addNote($id = 0)
    {
        $id = (int) $id;
        if ($this->input->method() !== 'post') {
            show_error('Method not allowed', 405);
            return;
        }
        $note = trim((string) $this->input->post('note'));
        $ok = false;
        if ($note !== '' && strlen($note) <= 10000
            && $this->db->table_exists('tour_booking_internal_notes')
            && $this->db->where('book_id', $id)->get($this->tblName)->row_array()) {
            $ok = $this->db->insert('tour_booking_internal_notes', array(
                'booking_id' => $id, 'note' => $note,
                'author_id' => (int) $this->session->userdata('admin_id'),
                'created_at' => date('Y-m-d H:i:s'),
            ));
        }
        if (!$ok) {
            $this->session->set_flashdata('booking_values', array('note' => $note));
        }
        $this->session->set_flashdata('booking_message', array('success' => $ok, 'text' => $ok ? 'Internal note added successfully.' : 'Unable to save the note. Please enter a note and try again.'));
        redirect(base_url('manage/bookings/control/view/' . $id));
    }

    public function deleteNote($id = 0, $noteId = 0)
    {
        $id = (int) $id;
        $noteId = (int) $noteId;
        if ($this->input->method() !== 'post') {
            show_error('Method not allowed', 405);
            return;
        }

        $ok = false;
        $authorId = (int) $this->session->userdata('admin_id');
        if ($id > 0 && $noteId > 0 && $authorId > 0
            && $this->db->table_exists('tour_booking_internal_notes')) {
            // Ownership is checked in the delete itself, even for forged requests.
            $deleted = $this->db->where('id', $noteId)
                ->where('booking_id', $id)
                ->where('author_id', $authorId)
                ->delete('tour_booking_internal_notes');
            $ok = $deleted && $this->db->affected_rows() === 1;
        }

        $this->session->set_flashdata('booking_message', array(
            'success' => $ok,
            'text' => $ok ? 'Internal note deleted successfully.' : 'Unable to delete this note. You can only delete notes you created.',
        ));
        redirect(base_url('manage/bookings/control/view/' . $id));
    }

    public function refund($id = 0)
    {
        $id = (int) $id;
        if ($this->input->method() !== 'post') {
            show_error('Method not allowed', 405);
            return;
        }
        $amount = (string) $this->input->post('refund_amount');
        $reply = trim((string) $this->input->post('refund_reply'));
        $requestToken = trim((string) $this->input->post('refund_request_token'));
        $this->load->model('Booking_payment_model');
        $moyasarPayment = $this->Booking_payment_model->paymentForBooking($id);
        if ($moyasarPayment) {
            $result = $this->Booking_payment_model->refundMoyasarBooking(
                $id,
                $amount,
                $reply,
                $requestToken,
                (int) $this->session->userdata('admin_id')
            );
            $ok = !empty($result['success']);
            if (!$ok) {
                $this->session->set_flashdata('booking_values', array(
                    'refund_amount' => $amount,
                    'refund_reply' => $reply,
                ));
            }
            $successText = !empty($result['full'])
                ? 'Moyasar refund completed and the booking has been marked Refunded.'
                : 'Moyasar partial refund completed successfully.';
            if ($ok && !$this->sendRefundEmail($id)) {
                $successText .= ' The refund email could not be sent to the customer.';
            }
            $this->session->set_flashdata('booking_message', array(
                'success' => $ok,
                'text' => $ok
                    ? $successText
                    : 'Moyasar could not complete the refund. No refund was recorded as successful; review the application log and try again.',
            ));
            redirect(base_url('manage/bookings/control/view/' . $id));

            return;
        }

        $this->db->trans_begin();
        $booking = $this->db->query('SELECT * FROM tour_bookings WHERE book_id = ? FOR UPDATE', array($id))->row_array();
        $ok = $booking && $booking['book_status'] === 'Completed'
            && preg_match('/^[0-9]+$/D', $amount)
            && (int) $amount > 0 && (int) $amount <= (int) $booking['book_paid_amount']
            && strlen($reply) <= 10000;
        if ($ok) {
            $this->db->query('SELECT tour_guide_id FROM tour_guides WHERE tour_guide_id = ? FOR UPDATE', array((int) $booking['book_tour_guide_id']));
            $now = date('Y-m-d H:i:s');
            $this->db->where('book_id', $id)->update($this->tblName, array(
                'book_status' => 'Refunded', 'book_refund_amount' => (int) $amount,
                'book_refund_reply' => $reply, 'book_refunded_at' => $now,
                'book_updated' => $now, 'book_avail_id' => 0,
            ));
            $this->db->where('avail_book_id', $id)->update('tour_guide_availability', array(
                'avail_book_id' => 0, 'avail_book_status' => 'Available', 'avail_updated' => $now,
            ));
            $ok = $this->db->trans_status();
        }
        if ($ok) {
            $ok = $this->db->trans_commit();
        } else {
            $this->db->trans_rollback();
        }
        if (!$ok) {
            $this->session->set_flashdata('booking_values', array('refund_amount' => $amount, 'refund_reply' => $reply));
        }
        $successText = 'Offline refund recorded and the booking has been marked Refunded.';
        if ($ok && !$this->sendRefundEmail($id)) {
            $successText .= ' The refund email could not be sent to the customer.';
        }
        $this->session->set_flashdata('booking_message', array('success' => (bool) $ok, 'text' => $ok ? $successText : 'The refund amount must be greater than zero and cannot exceed the recorded payment on a completed booking.'));
        redirect(base_url('manage/bookings/control/view/' . $id));
    }

    /**
     * Email the customer once the refund is committed (template 7 for tours, 8 for experiences).
     * The refund is already recorded, so a failed email is logged and reported but never rolled back.
     */
    private function sendRefundEmail($bookingId)
    {
        try {
            $this->load->library('Booking_email_service');

            return (bool) $this->booking_email_service->sendCustomerRefund($bookingId);
        } catch (Throwable $exception) {
            log_message(
                'error',
                'Booking refund email failed for booking ID ' . (int) $bookingId . ': '
                . $exception->getMessage()
            );

            return false;
        }
    }

    public function cancelBooking($id = 0)
    {
        $id = (int) $id;
        if ($this->input->method() !== 'post') {
            show_error('Method not allowed', 405);
            return;
        }
        $reason = trim((string) $this->input->post('cancel_reason'));
        $this->db->trans_begin();
        $booking = $this->db->query('SELECT * FROM tour_bookings WHERE book_id = ? FOR UPDATE', array($id))->row_array();
        $ok = $booking && $booking['book_status'] === 'Pending' && strlen($reason) <= 10000;
        if ($ok) {
            $now = date('Y-m-d H:i:s');
            $this->db->where('book_id', $id)->update($this->tblName, array(
                'book_status' => 'Cancelled', 'book_cancel_reason' => $reason !== '' ? $reason : null,
                'book_updated' => $now, 'book_avail_id' => 0,
            ));
            $this->db->where('avail_book_id', $id)->update('tour_guide_availability', array(
                'avail_book_id' => 0, 'avail_book_status' => 'Available', 'avail_updated' => $now,
            ));
            $ok = $this->db->trans_status();
        }
        if ($ok) {
            $ok = $this->db->trans_commit();
        } else {
            $this->db->trans_rollback();
        }
        if (!$ok) {
            $this->session->set_flashdata('booking_values', array('cancel_reason' => $reason));
        }
        $this->session->set_flashdata('booking_message', array('success' => (bool) $ok, 'text' => $ok ? 'Booking cancelled successfully.' : 'Only a pending booking can be cancelled this way.'));
        redirect(base_url('manage/bookings/control/view/' . $id));
    }
}
