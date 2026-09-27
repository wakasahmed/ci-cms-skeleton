<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Artists: the salon team shown on the website and offered in booking.
 *
 * This is the canonical CRUD module for Blossom (see AGENTS.md), ported from
 * the legacy Tour Guides module without its translation, language and
 * license features.
 */
class Artists extends CI_Controller
{
    public $tblName = 'artists';
    public $colPrefix = 'artist_';
    public $pKey = 'artist_id';
    public $moduleName = 'Artists';
    public $moduleNameSingular = 'Artist';
    public $moduleDesc = 'Manage the team: profiles, photos, specialties and the services each artist offers.';
    public $controller = 'artists';
    public $per_page = 10;
    public $tStatus = 'artist_status';
    public $listView = 'artists';
    public $addEditView = 'addArtist';
    public $user_data = array();

    /** Upload directory, relative to FCPATH. */
    private $imageDirectory = 'assets/frontend/images/artists';

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
        $this->load->library(array('admin_slug', 'admin_upload'));
    }

    public function index($sortby = 'artist_order', $order = 'ASC', $status = '-', $keywords = '-', $pg_no = '')
    {
        $allowedSorts = array(
            $this->pKey,
            $this->colPrefix.'order',
            $this->colPrefix.'name',
            $this->tStatus,
            $this->colPrefix.'updated',
        );
        $sortby = in_array($sortby, $allowedSorts, TRUE) ? $sortby : $this->colPrefix.'order';
        $order = strtoupper($order) === 'DESC' ? 'DESC' : 'ASC';
        $status = in_array($status, array('Enable', 'Disable'), TRUE) ? $status : '-';
        $this->applyPerPage();

        $keywords = urldecode((string) $keywords);
        $where = $status === '-' ? array() : array($this->tStatus => $status);
        $search = $keywords !== '-'
            ? array('cols' => $this->colPrefix.'name,'.$this->colPrefix.'role', 'value' => $keywords)
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

        $recordSort = $sortby === $this->colPrefix.'order'
            ? $sortby.','.$this->colPrefix.'name'
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
            'artistsActive' => 1,
            'total_rows' => $totalRows,
            'per_page' => $this->per_page,
            'records' => $records,
            'service_counts' => $this->serviceCounts($records),
            'paginate' => $this->pagination->create_links(),
            'sortby' => $sortby,
            'order' => $order === 'ASC' ? 'DESC' : 'ASC',
            'page_numb' => $offset,
            'status' => $status,
            'keywords' => $keywords,
        ));
    }

    public function control($alert = '', $editID = '')
    {
        $isEdit = ($alert === 'edit');
        $record = array($this->tStatus => 'Enable');
        $assignedServices = array();

        if ($isEdit) {
            $record = $this->SqlModel->getSingleRecord($this->tblName, array($this->pKey => (int) $editID));

            if (empty($record)) {
                $this->session->set_flashdata('alert', 'error');
                redirect(base_url('manage/'.$this->controller));

                return;
            }

            $assignedServices = $this->assignedServiceIds((int) $editID);
        }

        $postedData = $this->session->flashdata($this->controller.'_data');

        if (is_array($postedData)) {
            foreach ($this->formFields() as $field) {
                if (array_key_exists($field, $postedData)) {
                    $record[$field] = $postedData[$field];
                }
            }

            $record[$this->colPrefix.'working_days'] = implode(',', $this->validDays(
                isset($postedData['working_days']) ? $postedData['working_days'] : array()
            ));
            $assignedServices = admin_ids(isset($postedData['services']) ? $postedData['services'] : array());
        }

        $invalidFields = $this->session->flashdata($this->controller.'_invalid');

        $this->render($this->addEditView, array(
            'artistsActive' => 1,
            'alert' => $isEdit ? 'edit' : '',
            'form_error' => $this->session->flashdata('form_error'),
            'invalid_fields' => is_array($invalidFields) ? $invalidFields : array(),
            'tbl_data' => $record,
            'services' => $this->serviceOptions(),
            'assigned_services' => $assignedServices,
            'days' => $this->days(),
            'image_directory' => $this->imageDirectory,
            'page_title' => PROJECT_TITLE.' | '.($isEdit ? 'Edit' : 'Add').' '.$this->moduleNameSingular,
            'userdata' => $this->user_data,
            'useSweetAlert' => TRUE,
        ));
    }

    public function addRecord()
    {
        $posted = $this->validatedPost(0);
        $image = $this->saveImage(0);
        $data = $posted['data'];
        $now = date('Y-m-d H:i:s');

        $this->load->library('admin_record_sorter');

        if ($image !== '') {
            $data[$this->colPrefix.'image'] = $image;
        }

        $data[$this->colPrefix.'order'] = $this->admin_record_sorter->nextOrder($this->controller);
        $data[$this->colPrefix.'added'] = $now;
        $data[$this->colPrefix.'updated'] = $now;

        $this->db->trans_begin();
        $id = $this->SqlModel->insertRecord($this->tblName, $data);

        if ($id) {
            $this->syncServices($id, $posted['services']);
        }

        if (!$id || $this->db->trans_status() === FALSE) {
            $this->db->trans_rollback();
            $this->admin_upload->delete($this->imageDirectory, $image);

            return $this->formFailure('The artist could not be saved. Please try again.');
        }

        $this->db->trans_commit();
        $this->session->set_flashdata('alert', 'success');
        redirect(base_url('manage/'.$this->controller));
    }

    public function editRecord($editID = '')
    {
        $editID = (int) $editID;
        $current = $this->SqlModel->getSingleRecord($this->tblName, array($this->pKey => $editID));

        if (empty($current)) {
            $this->session->set_flashdata('alert', 'error');
            redirect(base_url('manage/'.$this->controller));

            return;
        }

        $posted = $this->validatedPost($editID);
        $image = $this->saveImage($editID);
        $data = $posted['data'];

        if ($image !== '') {
            $data[$this->colPrefix.'image'] = $image;
        }

        $data[$this->colPrefix.'updated'] = date('Y-m-d H:i:s');

        $this->db->trans_begin();
        $updated = $this->SqlModel->updateRecord($this->tblName, $data, array($this->pKey => $editID));

        if ($updated) {
            $this->syncServices($editID, $posted['services']);
        }

        if (!$updated || $this->db->trans_status() === FALSE) {
            $this->db->trans_rollback();
            $this->admin_upload->delete($this->imageDirectory, $image);

            return $this->formFailure('The artist could not be updated. Please try again.', $editID);
        }

        $this->db->trans_commit();

        if ($image !== '') {
            $this->deleteImageIfUnused($current[$this->colPrefix.'image']);
        }

        $this->session->set_flashdata('alert', 'editsuccess');
        redirect(base_url('manage/'.$this->controller));
    }

    public function delete($deleteID = '')
    {
        $this->session->set_flashdata(
            'alert',
            $this->deleteArtist((int) $deleteID) ? 'deletesuccess' : 'deleteerror'
        );
        redirect(base_url('manage/'.$this->controller));
    }

    public function deleteall()
    {
        $ids = admin_ids($this->input->post('records'));
        $deleted = 0;

        foreach ($ids as $id) {
            if ($this->deleteArtist($id)) {
                $deleted++;
            }
        }

        $this->session->set_flashdata(
            'alert',
            ($deleted > 0 && $deleted === count($ids)) ? 'deletesuccess' : 'deleteerror'
        );
        redirect(base_url('manage/'.$this->controller));
    }

    public function changestatus($id = 0, $status = 'Enable')
    {
        $this->output->set_content_type('application/json');

        $updated = in_array($status, array('Enable', 'Disable'), TRUE)
            && $this->SqlModel->updateRecord(
                $this->tblName,
                array($this->tStatus => $status),
                array($this->pKey => (int) $id)
            );

        if (!$updated) {
            return $this->output->set_output(json_encode(array('status' => 'false')));
        }

        return $this->output->set_output(json_encode(array(
            'status' => 'true',
            'id' => (int) $id,
            'currentStatus' => $status,
        )));
    }

    public function removefile($id = 0, $key = '')
    {
        $this->output->set_content_type('application/json');
        $id = (int) $id;
        $column = $this->colPrefix.'image';

        if ($id <= 0 || $key !== $column) {
            return $this->jsonFailure('Invalid request.');
        }

        $record = $this->SqlModel->getSingleRecord($this->tblName, array($this->pKey => $id));

        if (empty($record)) {
            return $this->jsonFailure('Record not found.');
        }

        if (!$this->SqlModel->updateRecord($this->tblName, array($column => NULL), array($this->pKey => $id))) {
            return $this->jsonFailure('The record could not be updated.');
        }

        $this->deleteImageIfUnused($record[$column]);

        return $this->output->set_output(json_encode(array(
            'success' => TRUE,
            'id' => $id,
            'key' => $column,
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

    /**
     * Allowed working-day keys and their labels, Monday first.
     */
    private function days()
    {
        return array(
            'mon' => 'Monday',
            'tue' => 'Tuesday',
            'wed' => 'Wednesday',
            'thu' => 'Thursday',
            'fri' => 'Friday',
            'sat' => 'Saturday',
            'sun' => 'Sunday',
        );
    }

    private function validDays($values)
    {
        $values = (array) $values;

        return array_values(array_filter(array_keys($this->days()), function ($day) use ($values) {
            return in_array($day, $values, TRUE);
        }));
    }

    private function formFields()
    {
        return array(
            $this->colPrefix.'name',
            $this->colPrefix.'slug',
            $this->colPrefix.'role',
            $this->colPrefix.'bio',
            $this->colPrefix.'specialties',
            $this->colPrefix.'is_placeholder',
            $this->tStatus,
        );
    }

    /**
     * Services grouped for the assignment select, in menu order.
     */
    private function serviceOptions()
    {
        return $this->db
            ->select('s.service_id, s.service_name, s.service_status, c.category_name')
            ->from('services s')
            ->join('service_categories c', 'c.category_id = s.service_category_id', 'left')
            ->order_by('c.category_order', 'ASC')
            ->order_by('s.service_order', 'ASC')
            ->get()
            ->result_array();
    }

    private function assignedServiceIds($artistId)
    {
        $rows = $this->SqlModel->getRecords(
            'service_id',
            'artist_services',
            'service_id',
            'ASC',
            array('artist_id' => (int) $artistId)
        );

        return array_map('intval', array_column($rows, 'service_id'));
    }

    private function syncServices($artistId, $serviceIds)
    {
        $this->SqlModel->deleteRecord('artist_services', array('artist_id' => (int) $artistId));

        foreach ($serviceIds as $serviceId) {
            $this->SqlModel->insertRecord('artist_services', array(
                'artist_id' => (int) $artistId,
                'service_id' => (int) $serviceId,
            ));
        }
    }

    /**
     * Validates the posted form and returns array('data' => row,
     * 'services' => ids). Redirects back to the form when anything is invalid.
     */
    private function validatedPost($editID)
    {
        $invalid = array();
        $messages = array();

        $name = admin_clean_text($this->input->post($this->colPrefix.'name'), 120);

        if ($name === '') {
            $invalid[] = $this->colPrefix.'name';
            $messages[] = 'Enter the artist\'s name.';
        }

        $slugInput = trim((string) $this->input->post($this->colPrefix.'slug'));
        $slug = $this->admin_slug->normalize($slugInput !== '' ? $slugInput : $name, 130);

        if ($slug === '' && $name !== '') {
            $invalid[] = $this->colPrefix.'slug';
            $messages[] = 'Enter a URL slug using letters or numbers.';
        }

        // Only IDs of services that exist are kept.
        $requestedServices = admin_ids($this->input->post('services'));
        $knownServices = array_map('intval', array_column($this->serviceOptions(), 'service_id'));
        $services = array_values(array_intersect($requestedServices, $knownServices));

        if (!empty($invalid)) {
            $this->formFailure(implode(' ', $messages), $editID, $invalid);
        }

        $status = $this->input->post($this->tStatus);

        return array(
            'data' => array(
                $this->colPrefix.'name' => $name,
                $this->colPrefix.'slug' => $this->admin_slug->unique(
                    $this->tblName,
                    $this->colPrefix.'slug',
                    $this->pKey,
                    $slug,
                    $editID,
                    130
                ),
                $this->colPrefix.'role' => admin_clean_text($this->input->post($this->colPrefix.'role'), 120),
                $this->colPrefix.'bio' => admin_clean_text($this->input->post($this->colPrefix.'bio'), 5000),
                $this->colPrefix.'specialties' => admin_clean_lines($this->input->post($this->colPrefix.'specialties'), 12, 80),
                $this->colPrefix.'working_days' => implode(',', $this->validDays($this->input->post('working_days'))),
                $this->colPrefix.'is_placeholder' => $this->input->post($this->colPrefix.'is_placeholder') === '1' ? 1 : 0,
                $this->tStatus => in_array($status, array('Enable', 'Disable'), TRUE) ? $status : 'Disable',
            ),
            'services' => $services,
        );
    }

    /**
     * Saves the optional photo upload and returns its filename ('' when no
     * file was sent). Redirects back to the form on an upload error.
     */
    private function saveImage($editID)
    {
        $upload = $this->admin_upload->save('image_upload', $this->imageDirectory, UPLOAD_IMAGE_MIMES);

        if ($upload['error'] !== '') {
            $this->formFailure('Photo: '.$upload['error'], $editID, array('image_upload'));
        }

        return $upload['filename'];
    }

    private function deleteImageIfUnused($filename)
    {
        if (!is_string($filename) || $filename === '') {
            return;
        }

        if ($this->SqlModel->countRecords($this->tblName, array($this->colPrefix.'image' => $filename)) > 0) {
            return;
        }

        $this->admin_upload->delete($this->imageDirectory, $filename);
    }

    private function deleteArtist($id)
    {
        $record = $this->SqlModel->getSingleRecord($this->tblName, array($this->pKey => (int) $id));

        if (empty($record)) {
            return FALSE;
        }

        // Service assignments cascade; appointment requests keep the artist's
        // name and lose only the link.
        if (!$this->SqlModel->deleteRecord($this->tblName, array($this->pKey => (int) $id))) {
            return FALSE;
        }

        $this->deleteImageIfUnused($record[$this->colPrefix.'image']);

        return TRUE;
    }

    private function serviceCounts($records)
    {
        $ids = array_map('intval', array_column($records, $this->pKey));

        if (empty($ids)) {
            return array();
        }

        $rows = $this->db
            ->select('artist_id, COUNT(*) AS total')
            ->where_in('artist_id', $ids)
            ->group_by('artist_id')
            ->get('artist_services')
            ->result_array();

        return array_column(array_map(function ($row) {
            return array((int) $row['artist_id'], (int) $row['total']);
        }, $rows), 1, 0);
    }

    private function formFailure($message, $editID = 0, $invalidFields = array())
    {
        $posted = $this->input->post(NULL, FALSE);

        $this->session->set_flashdata($this->controller.'_data', is_array($posted) ? $posted : array());
        $this->session->set_flashdata($this->controller.'_invalid', array_values($invalidFields));
        $this->session->set_flashdata('form_error', $message);

        redirect(base_url(
            'manage/'.$this->controller.'/control'.($editID ? '/edit/'.(int) $editID : '')
        ));
    }

    private function jsonFailure($message)
    {
        return $this->output->set_output(json_encode(array(
            'success' => FALSE,
            'message' => $message,
        )));
    }
}
