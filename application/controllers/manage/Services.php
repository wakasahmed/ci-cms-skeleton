<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Salon services (the price menu) with add-ons, images and SEO fields.
 *
 * Structure follows the canonical CRUD module (see AGENTS.md). Prices are
 * "from" prices in złoty; add-ons are stored in service_addons and replaced
 * as a set on every save.
 */
class Services extends CI_Controller
{
    public $tblName = 'services';
    public $colPrefix = 'service_';
    public $pKey = 'service_id';
    public $moduleName = 'Services';
    public $moduleNameSingular = 'Service';
    public $moduleDesc = 'Manage the service menu: prices, durations, descriptions, add-ons and images.';
    public $controller = 'services';
    public $per_page = 10;
    public $tStatus = 'service_status';
    public $listView = 'services';
    public $addEditView = 'addService';
    public $user_data = array();

    /** Upload directory, relative to FCPATH. */
    private $imageDirectory = 'assets/frontend/images/services';

    /** Maximum add-on rows per service. */
    private $maxAddons = 20;

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

    public function index($sortby = 'service_order', $order = 'ASC', $status = '-', $category = '-', $keywords = '-', $pg_no = '')
    {
        $allowedSorts = array(
            $this->pKey,
            $this->colPrefix.'order',
            $this->colPrefix.'name',
            $this->colPrefix.'price_from',
            $this->tStatus,
            $this->colPrefix.'updated',
        );
        $sortby = in_array($sortby, $allowedSorts, TRUE) ? $sortby : $this->colPrefix.'order';
        $order = strtoupper($order) === 'DESC' ? 'DESC' : 'ASC';
        $status = in_array($status, array('Enable', 'Disable'), TRUE) ? $status : '-';
        $categories = $this->categories();
        $category = (ctype_digit((string) $category) && isset($categories[(int) $category]))
            ? (int) $category
            : '-';
        $this->applyPerPage();

        $keywords = urldecode((string) $keywords);
        $where = array();

        if ($status !== '-') {
            $where[$this->tStatus] = $status;
        }

        if ($category !== '-') {
            $where[$this->colPrefix.'category_id'] = $category;
        }

        $search = $keywords !== '-'
            ? array('cols' => $this->colPrefix.'name,'.$this->colPrefix.'slug', 'value' => $keywords)
            : array();
        $baseUrl = base_url(
            'manage/'.$this->controller.'/index/'.$sortby.'/'.$order.'/'.$status.'/'.$category.'/'.rawurlencode($keywords)
        );
        $totalRows = $this->SqlModel->countRecords($this->tblName, $where, $search);
        $uriSegment = 9;
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
            'servicesActive' => 1,
            'total_rows' => $totalRows,
            'per_page' => $this->per_page,
            'records' => $records,
            'categories' => $categories,
            'paginate' => $this->pagination->create_links(),
            'sortby' => $sortby,
            'order' => $order === 'ASC' ? 'DESC' : 'ASC',
            'page_numb' => $offset,
            'status' => $status,
            'category' => $category,
            'keywords' => $keywords,
        ));
    }

    public function control($alert = '', $editID = '')
    {
        $isEdit = ($alert === 'edit');
        $record = array(
            'robots_index' => 1,
            'robots_follow' => 1,
            $this->tStatus => 'Enable',
        );
        $addons = array();

        if ($isEdit) {
            $record = $this->SqlModel->getSingleRecord($this->tblName, array($this->pKey => (int) $editID));

            if (empty($record)) {
                $this->session->set_flashdata('alert', 'error');
                redirect(base_url('manage/'.$this->controller));

                return;
            }

            $addons = $this->addons((int) $editID);
        }

        $postedData = $this->session->flashdata($this->controller.'_data');

        if (is_array($postedData)) {
            foreach ($this->formFields() as $field) {
                if (array_key_exists($field, $postedData)) {
                    $record[$field] = $postedData[$field];
                }
            }

            $addons = $this->postedAddonRows($postedData);
        }

        $invalidFields = $this->session->flashdata($this->controller.'_invalid');

        $this->render($this->addEditView, array(
            'servicesActive' => 1,
            'alert' => $isEdit ? 'edit' : '',
            'form_error' => $this->session->flashdata('form_error'),
            'invalid_fields' => is_array($invalidFields) ? $invalidFields : array(),
            'tbl_data' => $record,
            'addons' => $addons,
            'categories' => $this->categories(),
            'description_editor' => $this->descriptionEditor(
                isset($record[$this->colPrefix.'description']) ? (string) $record[$this->colPrefix.'description'] : ''
            ),
            'image_directory' => $this->imageDirectory,
            'page_title' => PROJECT_TITLE.' | '.($isEdit ? 'Edit' : 'Add').' '.$this->moduleNameSingular,
            'userdata' => $this->user_data,
            'useRepeatableRows' => TRUE,
            'useAccordionValidation' => TRUE,
            'useSweetAlert' => TRUE,
        ));
    }

    public function addRecord()
    {
        $posted = $this->validatedPost(0);
        $uploads = $this->saveUploads(0);
        $data = $posted['data'];
        $now = date('Y-m-d H:i:s');

        $this->load->library('admin_record_sorter');

        foreach ($uploads as $column => $filename) {
            $data[$column] = $filename;
        }

        $data[$this->colPrefix.'order'] = $this->admin_record_sorter->nextOrder($this->controller);
        $data[$this->colPrefix.'added'] = $now;
        $data[$this->colPrefix.'updated'] = $now;

        $this->db->trans_begin();
        $id = $this->SqlModel->insertRecord($this->tblName, $data);

        if ($id) {
            $this->syncAddons($id, $posted['addons']);
        }

        if (!$id || $this->db->trans_status() === FALSE) {
            $this->db->trans_rollback();
            $this->discardUploads($uploads);

            return $this->formFailure('The service could not be saved. Please try again.');
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
        $uploads = $this->saveUploads($editID);
        $data = $posted['data'];

        foreach ($uploads as $column => $filename) {
            $data[$column] = $filename;
        }

        $data[$this->colPrefix.'updated'] = date('Y-m-d H:i:s');

        $this->db->trans_begin();
        $updated = $this->SqlModel->updateRecord($this->tblName, $data, array($this->pKey => $editID));

        if ($updated) {
            $this->syncAddons($editID, $posted['addons']);
        }

        if (!$updated || $this->db->trans_status() === FALSE) {
            $this->db->trans_rollback();
            $this->discardUploads($uploads);

            return $this->formFailure('The service could not be updated. Please try again.', $editID);
        }

        $this->db->trans_commit();

        foreach (array_keys($uploads) as $column) {
            $this->deleteImageIfUnused($current[$column]);
        }

        $this->session->set_flashdata('alert', 'editsuccess');
        redirect(base_url('manage/'.$this->controller));
    }

    public function delete($deleteID = '')
    {
        $this->session->set_flashdata(
            'alert',
            $this->deleteService((int) $deleteID) ? 'deletesuccess' : 'deleteerror'
        );
        redirect(base_url('manage/'.$this->controller));
    }

    public function deleteall()
    {
        $ids = admin_ids($this->input->post('records'));
        $deleted = 0;

        foreach ($ids as $id) {
            if ($this->deleteService($id)) {
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

        if ($id <= 0 || !in_array($key, $this->imageColumns(), TRUE)) {
            return $this->jsonFailure('Invalid request.');
        }

        $record = $this->SqlModel->getSingleRecord($this->tblName, array($this->pKey => $id));

        if (empty($record)) {
            return $this->jsonFailure('Record not found.');
        }

        if (!$this->SqlModel->updateRecord($this->tblName, array($key => NULL), array($this->pKey => $id))) {
            return $this->jsonFailure('The record could not be updated.');
        }

        $this->deleteImageIfUnused($record[$key]);

        return $this->output->set_output(json_encode(array(
            'success' => TRUE,
            'id' => $id,
            'key' => $key,
        )));
    }

    /**
     * Copies a service (including images and add-ons) as a disabled draft
     * and opens it for editing.
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

        unset($data[$this->pKey]);
        $data[$this->colPrefix.'name'] = admin_clean_text($data[$this->colPrefix.'name'].' (copy)', 150);
        $data[$this->colPrefix.'slug'] = $this->admin_slug->unique(
            $this->tblName,
            $this->colPrefix.'slug',
            $this->pKey,
            $data[$this->colPrefix.'slug'].'-copy'
        );
        $data[$this->tStatus] = 'Disable';
        $data[$this->colPrefix.'order'] = $this->admin_record_sorter->nextOrder($this->controller);
        $data[$this->colPrefix.'added'] = $now;
        $data[$this->colPrefix.'updated'] = $now;

        $copies = array();

        foreach ($this->imageColumns() as $column) {
            $copy = $this->admin_upload->copy($this->imageDirectory, $data[$column]);
            $data[$column] = $copy !== '' ? $copy : NULL;

            if ($copy !== '') {
                $copies[$column] = $copy;
            }
        }

        $this->db->trans_begin();
        $newId = $this->SqlModel->insertRecord($this->tblName, $data);

        if ($newId) {
            $this->syncAddons($newId, $this->addons($sourceId));
        }

        if (!$newId || $this->db->trans_status() === FALSE) {
            $this->db->trans_rollback();
            $this->discardUploads($copies);
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

    /**
     * Categories keyed by ID, in menu order.
     */
    private function categories()
    {
        $rows = $this->SqlModel->getRecords(
            'category_id, category_name, category_status',
            'service_categories',
            'category_order',
            'ASC'
        );
        $categories = array();

        foreach ($rows as $row) {
            $categories[(int) $row['category_id']] = $row;
        }

        return $categories;
    }

    private function addons($serviceId)
    {
        return $this->SqlModel->getRecords(
            'addon_label, addon_price',
            'service_addons',
            'addon_order',
            'ASC',
            array('addon_service_id' => (int) $serviceId)
        );
    }

    private function imageColumns()
    {
        return array(
            $this->colPrefix.'card_image',
            $this->colPrefix.'hero_image',
            'og_image',
        );
    }

    /**
     * Maps each image column to its file input name.
     */
    private function uploadFields()
    {
        return array(
            $this->colPrefix.'card_image' => 'card_image_upload',
            $this->colPrefix.'hero_image' => 'hero_image_upload',
            'og_image' => 'og_image_upload',
        );
    }

    /**
     * Fields restored into the form after a validation failure.
     */
    private function formFields()
    {
        return array(
            $this->colPrefix.'category_id',
            $this->colPrefix.'name',
            $this->colPrefix.'slug',
            $this->colPrefix.'summary',
            $this->colPrefix.'description',
            $this->colPrefix.'price_from',
            $this->colPrefix.'price_suffix',
            $this->colPrefix.'duration_label',
            $this->colPrefix.'duration_minutes',
            $this->colPrefix.'included',
            $this->colPrefix.'before_visit',
            $this->colPrefix.'aftercare',
            $this->colPrefix.'featured',
            $this->tStatus,
            'page_title',
            'meta_description',
            'robots_index',
            'robots_follow',
            'og_title',
            'og_description',
        );
    }

    /**
     * Validates the posted form and returns array('data' => row, 'addons' =>
     * rows). Redirects back to the form when anything is invalid.
     */
    private function validatedPost($editID)
    {
        $post = function ($field) {
            return $this->input->post($field);
        };
        $invalid = array();
        $messages = array();

        $categoryId = (int) $post($this->colPrefix.'category_id');
        $categories = $this->categories();

        if (!isset($categories[$categoryId])) {
            $invalid[] = $this->colPrefix.'category_id';
            $messages[] = 'Select a category.';
        }

        $name = admin_clean_text($post($this->colPrefix.'name'), 150);

        if ($name === '') {
            $invalid[] = $this->colPrefix.'name';
            $messages[] = 'Enter the service name.';
        }

        $slugInput = trim((string) $post($this->colPrefix.'slug'));
        $slug = $this->admin_slug->normalize($slugInput !== '' ? $slugInput : $name);

        if ($slug === '' && $name !== '') {
            $invalid[] = $this->colPrefix.'slug';
            $messages[] = 'Enter a URL slug using letters or numbers.';
        }

        $price = admin_price_value($post($this->colPrefix.'price_from'));

        if ($price === FALSE) {
            $invalid[] = $this->colPrefix.'price_from';
            $messages[] = 'Enter the price as a number, for example 80 or 80.50.';
        }

        $minutesInput = trim((string) $post($this->colPrefix.'duration_minutes'));
        $minutes = NULL;

        if ($minutesInput !== '') {
            if (!ctype_digit($minutesInput) || (int) $minutesInput < 1 || (int) $minutesInput > 720) {
                $invalid[] = $this->colPrefix.'duration_minutes';
                $messages[] = 'Enter the booking duration in whole minutes between 1 and 720.';
            } else {
                $minutes = (int) $minutesInput;
            }
        }

        $addons = $this->postedAddonRows($this->input->post(NULL, FALSE));

        foreach ($addons as $addon) {
            if ($addon['addon_label'] === '' || $addon['addon_price'] === FALSE) {
                $invalid[] = 'addons';
                $messages[] = 'Each add-on needs a label and a valid price (or an empty price).';
                break;
            }
        }

        if (count($addons) > $this->maxAddons) {
            $invalid[] = 'addons';
            $messages[] = 'A service can have at most '.$this->maxAddons.' add-ons.';
        }

        if (!empty($invalid)) {
            $this->formFailure(implode(' ', array_unique($messages)), $editID, array_unique($invalid));
        }

        $status = $post($this->tStatus);

        $data = array(
            $this->colPrefix.'category_id' => $categoryId,
            $this->colPrefix.'name' => $name,
            $this->colPrefix.'slug' => $this->admin_slug->unique(
                $this->tblName,
                $this->colPrefix.'slug',
                $this->pKey,
                $slug,
                $editID
            ),
            $this->colPrefix.'summary' => admin_clean_text($post($this->colPrefix.'summary'), 500),
            // Rich text from CKEditor, authored by administrators only.
            $this->colPrefix.'description' => trim((string) $this->input->post($this->colPrefix.'description', FALSE)),
            $this->colPrefix.'price_from' => $price,
            $this->colPrefix.'price_suffix' => admin_clean_text($post($this->colPrefix.'price_suffix'), 40),
            $this->colPrefix.'duration_label' => admin_clean_text($post($this->colPrefix.'duration_label'), 60),
            $this->colPrefix.'duration_minutes' => $minutes,
            $this->colPrefix.'included' => admin_clean_lines($post($this->colPrefix.'included')),
            $this->colPrefix.'before_visit' => admin_clean_lines($post($this->colPrefix.'before_visit')),
            $this->colPrefix.'aftercare' => admin_clean_lines($post($this->colPrefix.'aftercare')),
            $this->colPrefix.'featured' => $post($this->colPrefix.'featured') === '1' ? 1 : 0,
            $this->tStatus => in_array($status, array('Enable', 'Disable'), TRUE) ? $status : 'Disable',
            'page_title' => admin_clean_text($post('page_title'), 255),
            'meta_description' => admin_clean_text($post('meta_description'), 500),
            'robots_index' => $post('robots_index') === '1' ? 1 : 0,
            'robots_follow' => $post('robots_follow') === '1' ? 1 : 0,
            'og_title' => admin_clean_text($post('og_title'), 255),
            'og_description' => admin_clean_text($post('og_description'), 500),
        );

        return array(
            'data' => $data,
            'addons' => $addons,
        );
    }

    /**
     * Builds add-on rows from posted addon_label[] / addon_price[] arrays.
     * Fully empty rows are skipped; an invalid price is kept as FALSE so the
     * caller can report it.
     */
    private function postedAddonRows($post)
    {
        $labels = isset($post['addon_label']) ? (array) $post['addon_label'] : array();
        $prices = isset($post['addon_price']) ? (array) $post['addon_price'] : array();
        $rows = array();

        foreach (array_values($labels) as $index => $label) {
            $label = admin_clean_text($label, 150);
            $rawPrice = isset($prices[$index]) ? (string) $prices[$index] : '';

            if ($label === '' && trim($rawPrice) === '') {
                continue;
            }

            $rows[] = array(
                'addon_label' => $label,
                'addon_price' => admin_price_value($rawPrice),
                'addon_price_input' => $rawPrice,
            );
        }

        return $rows;
    }

    private function syncAddons($serviceId, $addons)
    {
        $this->SqlModel->deleteRecord('service_addons', array('addon_service_id' => (int) $serviceId));

        foreach (array_values($addons) as $index => $addon) {
            $this->SqlModel->insertRecord('service_addons', array(
                'addon_service_id' => (int) $serviceId,
                'addon_label' => $addon['addon_label'],
                'addon_price' => $addon['addon_price'],
                'addon_order' => $index + 1,
            ));
        }
    }

    /**
     * Saves the optional image uploads. Returns column => new filename for
     * each file received. On an upload error, removes anything already saved
     * and redirects back to the form.
     */
    private function saveUploads($editID)
    {
        $saved = array();

        foreach ($this->uploadFields() as $column => $field) {
            $upload = $this->admin_upload->save($field, $this->imageDirectory, UPLOAD_IMAGE_MIMES);

            if ($upload['error'] !== '') {
                $this->discardUploads($saved);
                $this->formFailure('Image upload: '.$upload['error'], $editID, array($field));
            }

            if ($upload['filename'] !== '') {
                $saved[$column] = $upload['filename'];
            }
        }

        return $saved;
    }

    private function discardUploads($uploads)
    {
        foreach ($uploads as $filename) {
            $this->admin_upload->delete($this->imageDirectory, $filename);
        }
    }

    /**
     * Deletes an image unless another service row still references it.
     */
    private function deleteImageIfUnused($filename)
    {
        if (!is_string($filename) || $filename === '') {
            return;
        }

        foreach ($this->imageColumns() as $column) {
            if ($this->SqlModel->countRecords($this->tblName, array($column => $filename)) > 0) {
                return;
            }
        }

        $this->admin_upload->delete($this->imageDirectory, $filename);
    }

    private function deleteService($id)
    {
        $record = $this->SqlModel->getSingleRecord($this->tblName, array($this->pKey => (int) $id));

        if (empty($record)) {
            return FALSE;
        }

        // Add-ons and artist/offer links cascade; gallery images and
        // appointment requests keep their rows with the link cleared.
        if (!$this->SqlModel->deleteRecord($this->tblName, array($this->pKey => (int) $id))) {
            return FALSE;
        }

        foreach ($this->imageColumns() as $column) {
            $this->deleteImageIfUnused($record[$column]);
        }

        return TRUE;
    }

    private function descriptionEditor($value)
    {
        $this->load->library('ckeditor');
        $this->load->library('ckfinder');
        $this->ckeditor->basePath = base_url('assets/ckeditor/');
        $this->ckeditor->config['removePlugins'] = 'save, preview, newpage, forms, flash';
        $this->ckeditor->config['height'] = '260px';
        $this->ckfinder->SetupCKEditor($this->ckeditor, '../../../../assets/ckfinder/');
        $this->ckeditor->textareaAttributes = array(
            'id' => $this->colPrefix.'description',
            'rows' => 8,
            'cols' => 60,
        );

        ob_start();
        $this->ckeditor->editor($this->colPrefix.'description', $value);

        return ob_get_clean();
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
