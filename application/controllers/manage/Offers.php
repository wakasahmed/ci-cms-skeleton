<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Offers: bundles of services sold at a combined price for a period.
 *
 * Structure follows the canonical CRUD module (see AGENTS.md). Validity
 * dates are optional; the website hides an offer outside its dates.
 */
class Offers extends CI_Controller
{
    public $tblName = 'offers';
    public $colPrefix = 'offer_';
    public $pKey = 'offer_id';
    public $moduleName = 'Offers';
    public $moduleNameSingular = 'Offer';
    public $moduleDesc = 'Manage bundled offers: what is included, the offer price and when it runs.';
    public $controller = 'offers';
    public $per_page = 10;
    public $tStatus = 'offer_status';
    public $listView = 'offers';
    public $addEditView = 'addOffer';
    public $user_data = array();

    /** Upload directory, relative to FCPATH. */
    private $imageDirectory = 'assets/frontend/images/offers';

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

    public function index($sortby = 'offer_order', $order = 'ASC', $status = '-', $keywords = '-', $pg_no = '')
    {
        $allowedSorts = array(
            $this->pKey,
            $this->colPrefix.'order',
            $this->colPrefix.'title',
            $this->colPrefix.'price',
            $this->colPrefix.'valid_to',
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
            ? array('cols' => $this->colPrefix.'title,'.$this->colPrefix.'label', 'value' => $keywords)
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
            ? $sortby.','.$this->colPrefix.'title'
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
            'offersActive' => 1,
            'total_rows' => $totalRows,
            'per_page' => $this->per_page,
            'records' => $records,
            'image_directory' => $this->imageDirectory,
            'today' => date('Y-m-d'),
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
        $linkedServices = array();

        if ($isEdit) {
            $record = $this->SqlModel->getSingleRecord($this->tblName, array($this->pKey => (int) $editID));

            if (empty($record)) {
                $this->session->set_flashdata('alert', 'error');
                redirect(base_url('manage/'.$this->controller));

                return;
            }

            $linkedServices = $this->linkedServiceIds((int) $editID);
            $record[$this->colPrefix.'valid_from'] = admin_datepicker_value($record[$this->colPrefix.'valid_from']);
            $record[$this->colPrefix.'valid_to'] = admin_datepicker_value($record[$this->colPrefix.'valid_to']);
        }

        $postedData = $this->session->flashdata($this->controller.'_data');

        if (is_array($postedData)) {
            foreach ($this->formFields() as $field) {
                if (array_key_exists($field, $postedData)) {
                    $record[$field] = $postedData[$field];
                }
            }

            $linkedServices = admin_ids(isset($postedData['services']) ? $postedData['services'] : array());
        }

        $invalidFields = $this->session->flashdata($this->controller.'_invalid');

        $this->render($this->addEditView, array(
            'offersActive' => 1,
            'alert' => $isEdit ? 'edit' : '',
            'form_error' => $this->session->flashdata('form_error'),
            'invalid_fields' => is_array($invalidFields) ? $invalidFields : array(),
            'tbl_data' => $record,
            'services' => $this->serviceOptions(),
            'linked_services' => $linkedServices,
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

            return $this->formFailure('The offer could not be saved. Please try again.');
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

            return $this->formFailure('The offer could not be updated. Please try again.', $editID);
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
            $this->deleteOffer((int) $deleteID) ? 'deletesuccess' : 'deleteerror'
        );
        redirect(base_url('manage/'.$this->controller));
    }

    public function deleteall()
    {
        $ids = admin_ids($this->input->post('records'));
        $deleted = 0;

        foreach ($ids as $id) {
            if ($this->deleteOffer($id)) {
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

    /**
     * Copies an offer (image and linked services included) as a disabled
     * draft, e.g. to run a seasonal offer again, and opens it for editing.
     */
    public function duplicate($id = 0)
    {
        $sourceId = (int) $id;
        $data = $this->SqlModel->getSingleRecord($this->tblName, array($this->pKey => $sourceId));

        if (empty($data)) {
            $this->session->set_flashdata('alert', 'error');
            redirect(base_url('manage/'.$this->controller));

            return;
        }

        $this->load->library('admin_record_sorter');
        $now = date('Y-m-d H:i:s');
        $imageCopy = $this->admin_upload->copy($this->imageDirectory, $data[$this->colPrefix.'image']);

        unset($data[$this->pKey]);
        $data[$this->colPrefix.'title'] = admin_clean_text($data[$this->colPrefix.'title'].' (copy)', 150);
        $data[$this->colPrefix.'slug'] = $this->admin_slug->unique(
            $this->tblName,
            $this->colPrefix.'slug',
            $this->pKey,
            $data[$this->colPrefix.'slug'].'-copy'
        );
        $data[$this->colPrefix.'image'] = $imageCopy !== '' ? $imageCopy : NULL;
        $data[$this->tStatus] = 'Disable';
        $data[$this->colPrefix.'order'] = $this->admin_record_sorter->nextOrder($this->controller);
        $data[$this->colPrefix.'added'] = $now;
        $data[$this->colPrefix.'updated'] = $now;

        $this->db->trans_begin();
        $newId = $this->SqlModel->insertRecord($this->tblName, $data);

        if ($newId) {
            $this->syncServices($newId, $this->linkedServiceIds($sourceId));
        }

        if (!$newId || $this->db->trans_status() === FALSE) {
            $this->db->trans_rollback();
            $this->admin_upload->delete($this->imageDirectory, $imageCopy);
            $this->session->set_flashdata('alert', 'error');
            redirect(base_url('manage/'.$this->controller));

            return;
        }

        $this->db->trans_commit();
        redirect(base_url('manage/'.$this->controller.'/control/edit/'.(int) $newId));
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

    private function formFields()
    {
        return array(
            $this->colPrefix.'title',
            $this->colPrefix.'slug',
            $this->colPrefix.'label',
            $this->colPrefix.'summary',
            $this->colPrefix.'inclusions',
            $this->colPrefix.'price',
            $this->colPrefix.'old_price',
            $this->colPrefix.'duration_label',
            $this->colPrefix.'valid_from',
            $this->colPrefix.'valid_to',
            $this->colPrefix.'validity_note',
            $this->colPrefix.'featured',
            $this->tStatus,
        );
    }

    /**
     * Services for the linked-services select, in menu order.
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

    private function linkedServiceIds($offerId)
    {
        $rows = $this->SqlModel->getRecords(
            'service_id',
            'offer_services',
            'service_id',
            'ASC',
            array('offer_id' => (int) $offerId)
        );

        return array_map('intval', array_column($rows, 'service_id'));
    }

    private function syncServices($offerId, $serviceIds)
    {
        $this->SqlModel->deleteRecord('offer_services', array('offer_id' => (int) $offerId));

        foreach ($serviceIds as $serviceId) {
            $this->SqlModel->insertRecord('offer_services', array(
                'offer_id' => (int) $offerId,
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

        $title = admin_clean_text($this->input->post($this->colPrefix.'title'), 150);

        if ($title === '') {
            $invalid[] = $this->colPrefix.'title';
            $messages[] = 'Enter the offer title.';
        }

        $slugInput = trim((string) $this->input->post($this->colPrefix.'slug'));
        $slug = $this->admin_slug->normalize($slugInput !== '' ? $slugInput : $title);

        if ($slug === '' && $title !== '') {
            $invalid[] = $this->colPrefix.'slug';
            $messages[] = 'Enter a URL slug using letters or numbers.';
        }

        $price = admin_price_value($this->input->post($this->colPrefix.'price'));

        if ($price === NULL || $price === FALSE) {
            $invalid[] = $this->colPrefix.'price';
            $messages[] = 'Enter the offer price as a number, for example 160.';
        }

        $oldPrice = admin_price_value($this->input->post($this->colPrefix.'old_price'));

        if ($oldPrice === FALSE) {
            $invalid[] = $this->colPrefix.'old_price';
            $messages[] = 'Enter the regular price as a number, or leave it empty.';
        } elseif ($oldPrice !== NULL && is_string($price) && (float) $oldPrice <= (float) $price) {
            $invalid[] = $this->colPrefix.'old_price';
            $messages[] = 'The regular price must be higher than the offer price.';
        }

        $validFrom = admin_date_value($this->input->post($this->colPrefix.'valid_from'));
        $validTo = admin_date_value($this->input->post($this->colPrefix.'valid_to'));

        if ($validFrom === FALSE) {
            $invalid[] = $this->colPrefix.'valid_from';
            $messages[] = 'Enter a valid start date.';
        }

        if ($validTo === FALSE) {
            $invalid[] = $this->colPrefix.'valid_to';
            $messages[] = 'Enter a valid end date.';
        }

        if (is_string($validFrom) && is_string($validTo) && $validTo < $validFrom) {
            $invalid[] = $this->colPrefix.'valid_to';
            $messages[] = 'The end date cannot be before the start date.';
        }

        // Only IDs of services that exist are kept.
        $requestedServices = admin_ids($this->input->post('services'));
        $knownServices = array_map('intval', array_column($this->serviceOptions(), 'service_id'));
        $services = array_values(array_intersect($requestedServices, $knownServices));

        if (!empty($invalid)) {
            $this->formFailure(implode(' ', array_unique($messages)), $editID, array_unique($invalid));
        }

        $status = $this->input->post($this->tStatus);

        return array(
            'data' => array(
                $this->colPrefix.'title' => $title,
                $this->colPrefix.'slug' => $this->admin_slug->unique(
                    $this->tblName,
                    $this->colPrefix.'slug',
                    $this->pKey,
                    $slug,
                    $editID
                ),
                $this->colPrefix.'label' => admin_clean_text($this->input->post($this->colPrefix.'label'), 80),
                $this->colPrefix.'summary' => admin_clean_text($this->input->post($this->colPrefix.'summary'), 500),
                $this->colPrefix.'inclusions' => admin_clean_lines($this->input->post($this->colPrefix.'inclusions')),
                $this->colPrefix.'price' => $price,
                $this->colPrefix.'old_price' => $oldPrice,
                $this->colPrefix.'duration_label' => admin_clean_text($this->input->post($this->colPrefix.'duration_label'), 60),
                $this->colPrefix.'valid_from' => $validFrom,
                $this->colPrefix.'valid_to' => $validTo,
                $this->colPrefix.'validity_note' => admin_clean_text($this->input->post($this->colPrefix.'validity_note'), 255),
                $this->colPrefix.'featured' => $this->input->post($this->colPrefix.'featured') === '1' ? 1 : 0,
                $this->tStatus => in_array($status, array('Enable', 'Disable'), TRUE) ? $status : 'Disable',
            ),
            'services' => $services,
        );
    }

    /**
     * Saves the optional image upload and returns its filename ('' when no
     * file was sent). Redirects back to the form on an upload error.
     */
    private function saveImage($editID)
    {
        $upload = $this->admin_upload->save('image_upload', $this->imageDirectory, UPLOAD_IMAGE_MIMES);

        if ($upload['error'] !== '') {
            $this->formFailure('Image: '.$upload['error'], $editID, array('image_upload'));
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

    private function deleteOffer($id)
    {
        $record = $this->SqlModel->getSingleRecord($this->tblName, array($this->pKey => (int) $id));

        if (empty($record)) {
            return FALSE;
        }

        // Linked services cascade; appointment requests keep the offer title
        // and lose only the link.
        if (!$this->SqlModel->deleteRecord($this->tblName, array($this->pKey => (int) $id))) {
            return FALSE;
        }

        $this->deleteImageIfUnused($record[$this->colPrefix.'image']);

        return TRUE;
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
