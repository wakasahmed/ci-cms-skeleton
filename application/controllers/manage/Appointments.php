<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Appointment requests sent from the website booking form.
 *
 * Requests are created by the public site (PROJECT_PLAN.md, Phase 6), so
 * this module only lists, views, updates the status of, annotates and
 * deletes them. Service, artist and offer names are copied onto each request
 * when it is made, so later catalogue changes never alter it.
 */
class Appointments extends CI_Controller
{
    public $tblName = 'appointments';
    public $colPrefix = 'appointment_';
    public $pKey = 'appointment_id';
    public $moduleName = 'Appointments';
    public $moduleNameSingular = 'Appointment Request';
    public $moduleDesc = 'Review booking requests from the website, confirm them with the client and keep notes.';
    public $controller = 'appointments';
    public $per_page = 10;
    public $tStatus = 'appointment_status';
    public $listView = 'appointments';
    public $detailView = 'viewAppointment';
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
    }

    /**
     * Allowed statuses. Any status can be changed to any other so mistakes
     * can be corrected.
     */
    private function statuses()
    {
        return array('New', 'Confirmed', 'Completed', 'Cancelled');
    }

    public function index($sortby = 'appointment_added', $order = 'DESC', $status = '-', $keywords = '-', $pg_no = '')
    {
        $allowedSorts = array(
            $this->colPrefix.'reference',
            $this->colPrefix.'date',
            'customer_name',
            $this->tStatus,
            $this->colPrefix.'added',
        );
        $sortby = in_array($sortby, $allowedSorts, TRUE) ? $sortby : $this->colPrefix.'added';
        $order = strtoupper($order) === 'ASC' ? 'ASC' : 'DESC';
        $status = in_array($status, $this->statuses(), TRUE) ? $status : '-';
        $this->applyPerPage();

        $keywords = urldecode((string) $keywords);
        $where = $status === '-' ? array() : array($this->tStatus => $status);
        $search = $keywords !== '-'
            ? array(
                'cols' => $this->colPrefix.'reference,customer_name,customer_email,customer_phone',
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

        // Appointments on the same day are also ordered by time.
        $recordSort = $sortby === $this->colPrefix.'date'
            ? $this->colPrefix.'date,'.$this->colPrefix.'time'
            : $sortby;
        $records = $this->SqlModel->getRecords(
            '*',
            $this->tblName,
            $recordSort,
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
            'appointmentsActive' => 1,
            'total_rows' => $totalRows,
            'per_page' => $this->per_page,
            'records' => $records,
            'record_services' => $this->servicesFor(array_column($records, $this->pKey)),
            'status_counts' => $this->statusCounts(),
            'status_options' => $this->statuses(),
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
        $id = (int) $id;
        $record = $this->SqlModel->getSingleRecord($this->tblName, array($this->pKey => $id));

        if (empty($record)) {
            $this->session->set_flashdata('alert', 'error');
            redirect(base_url('manage/'.$this->controller));

            return;
        }

        $services = $this->servicesFor(array($id));
        $notes = $this->db
            ->select('n.note_id, n.author_id, n.note, n.created_at, a.full_name')
            ->from('appointment_notes n')
            ->join('admin_users a', 'a.id = n.author_id', 'left')
            ->where('n.appointment_id', $id)
            ->order_by('n.note_id', 'DESC')
            ->get()
            ->result_array();

        $this->render($this->detailView, array(
            'appointmentsActive' => 1,
            'page_title' => PROJECT_TITLE.' | '.$record[$this->colPrefix.'reference'],
            'userdata' => $this->user_data,
            'record' => $record,
            'services' => isset($services[$id]) ? $services[$id] : array(),
            'notes' => $notes,
            'status_options' => $this->statuses(),
            'message' => $this->session->flashdata('appointment_message'),
            'note_draft' => $this->session->flashdata('appointment_note_draft'),
            'current_admin_id' => (int) $this->session->userdata('admin_id'),
        ));
    }

    public function updateStatus($id = 0)
    {
        $id = (int) $id;

        if ($this->input->method(TRUE) !== 'POST') {
            show_error('Method not allowed', 405);

            return;
        }

        $status = $this->input->post('status');
        $record = $this->SqlModel->getSingleRecord($this->tblName, array($this->pKey => $id));
        $ok = !empty($record)
            && in_array($status, $this->statuses(), TRUE)
            && $this->SqlModel->updateRecord(
                $this->tblName,
                array(
                    $this->tStatus => $status,
                    $this->colPrefix.'updated' => date('Y-m-d H:i:s'),
                ),
                array($this->pKey => $id)
            );

        if (empty($record)) {
            $this->session->set_flashdata('alert', 'error');
            redirect(base_url('manage/'.$this->controller));

            return;
        }

        $this->session->set_flashdata('appointment_message', array(
            'success' => $ok,
            'text' => $ok
                ? 'Status changed to '.$status.'.'
                : 'The status could not be changed. Please try again.',
        ));
        redirect(base_url('manage/'.$this->controller.'/view/'.$id));
    }

    public function addNote($id = 0)
    {
        $id = (int) $id;

        if ($this->input->method(TRUE) !== 'POST') {
            show_error('Method not allowed', 405);

            return;
        }

        $note = trim((string) $this->input->post('note'));
        $exists = $this->SqlModel->countRecords($this->tblName, array($this->pKey => $id)) > 0;
        $ok = FALSE;

        if (!$exists) {
            $this->session->set_flashdata('alert', 'error');
            redirect(base_url('manage/'.$this->controller));

            return;
        }

        if ($note !== '' && mb_strlen($note, 'UTF-8') <= 5000) {
            $ok = (bool) $this->SqlModel->insertRecord('appointment_notes', array(
                'appointment_id' => $id,
                'author_id' => (int) $this->session->userdata('admin_id'),
                'note' => $note,
                'created_at' => date('Y-m-d H:i:s'),
            ));
        }

        if (!$ok) {
            $this->session->set_flashdata('appointment_note_draft', $note);
        }

        $this->session->set_flashdata('appointment_message', array(
            'success' => $ok,
            'text' => $ok
                ? 'Note added.'
                : 'The note could not be saved. Enter a note of up to 5,000 characters and try again.',
        ));
        redirect(base_url('manage/'.$this->controller.'/view/'.$id));
    }

    /**
     * Deletes a note. Administrators can delete only their own notes; the
     * author is checked in the delete itself.
     */
    public function deleteNote($id = 0, $noteId = 0)
    {
        $id = (int) $id;
        $noteId = (int) $noteId;

        if ($this->input->method(TRUE) !== 'POST') {
            show_error('Method not allowed', 405);

            return;
        }

        $this->db
            ->where('note_id', $noteId)
            ->where('appointment_id', $id)
            ->where('author_id', (int) $this->session->userdata('admin_id'))
            ->delete('appointment_notes');
        $ok = $this->db->affected_rows() === 1;

        $this->session->set_flashdata('appointment_message', array(
            'success' => $ok,
            'text' => $ok
                ? 'Note deleted.'
                : 'The note could not be deleted. You can only delete notes you wrote.',
        ));
        redirect(base_url('manage/'.$this->controller.'/view/'.$id));
    }

    public function delete($deleteID = '')
    {
        $deleted = $this->SqlModel->countRecords($this->tblName, array($this->pKey => (int) $deleteID)) > 0
            && $this->SqlModel->deleteRecord($this->tblName, array($this->pKey => (int) $deleteID));

        $this->session->set_flashdata('alert', $deleted ? 'deletesuccess' : 'deleteerror');
        redirect(base_url('manage/'.$this->controller));
    }

    public function deleteall()
    {
        $ids = admin_ids($this->input->post('records'));
        $deleted = 0;

        foreach ($ids as $id) {
            if ($this->SqlModel->countRecords($this->tblName, array($this->pKey => $id)) > 0
                && $this->SqlModel->deleteRecord($this->tblName, array($this->pKey => $id))
            ) {
                $deleted++;
            }
        }

        $this->session->set_flashdata(
            'alert',
            ($deleted > 0 && $deleted === count($ids)) ? 'deletesuccess' : 'deleteerror'
        );
        redirect(base_url('manage/'.$this->controller));
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

    /**
     * Requested services per appointment ID.
     */
    private function servicesFor($appointmentIds)
    {
        $appointmentIds = array_map('intval', (array) $appointmentIds);

        if (empty($appointmentIds)) {
            return array();
        }

        $rows = $this->db
            ->where_in('appointment_id', $appointmentIds)
            ->order_by('id', 'ASC')
            ->get('appointment_services')
            ->result_array();
        $grouped = array();

        foreach ($rows as $row) {
            $grouped[(int) $row['appointment_id']][] = $row;
        }

        return $grouped;
    }

    private function statusCounts()
    {
        $rows = $this->db
            ->select($this->tStatus.' AS status, COUNT(*) AS total')
            ->group_by($this->tStatus)
            ->get($this->tblName)
            ->result_array();

        return array_column($rows, 'total', 'status');
    }
}
