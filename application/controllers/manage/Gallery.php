<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Gallery images for the website gallery page and "fresh from the studio".
 *
 * Structure follows the canonical CRUD module (see AGENTS.md). Images can be
 * added one at a time (with full details) or uploaded in bulk; a bulk upload
 * creates one record per file, captioned from the filename, ready to edit.
 */
class Gallery extends CI_Controller
{
    public $tblName = 'gallery_images';
    public $colPrefix = 'image_';
    public $pKey = 'image_id';
    public $moduleName = 'Gallery';
    public $moduleNameSingular = 'Gallery Image';
    public $moduleDesc = 'Manage the photos shown in the website gallery.';
    public $controller = 'gallery';
    public $per_page = 10;
    public $tStatus = 'image_status';
    public $listView = 'gallery';
    public $addEditView = 'addGalleryImage';
    public $user_data = array();

    /** Upload directory, relative to FCPATH. */
    private $imageDirectory = 'assets/frontend/images/gallery';

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
        $this->load->library('admin_upload');
    }

    public function index($sortby = 'image_order', $order = 'ASC', $status = '-', $category = '-', $keywords = '-', $pg_no = '')
    {
        $allowedSorts = array(
            $this->pKey,
            $this->colPrefix.'order',
            $this->colPrefix.'caption',
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
            ? array('cols' => $this->colPrefix.'caption', 'value' => $keywords)
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
            ? $sortby.','.$this->pKey
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
            'galleryActive' => 1,
            'total_rows' => $totalRows,
            'per_page' => $this->per_page,
            'records' => $records,
            'categories' => $categories,
            'services' => $this->services(),
            'image_directory' => $this->imageDirectory,
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
        $record = array($this->tStatus => 'Enable');

        if ($isEdit) {
            $record = $this->SqlModel->getSingleRecord($this->tblName, array($this->pKey => (int) $editID));

            if (empty($record)) {
                $this->session->set_flashdata('alert', 'error');
                redirect(base_url('manage/'.$this->controller));

                return;
            }
        }

        $postedData = $this->session->flashdata($this->controller.'_data');

        if (is_array($postedData)) {
            foreach ($this->formFields() as $field) {
                if (array_key_exists($field, $postedData)) {
                    $record[$field] = $postedData[$field];
                }
            }
        }

        $invalidFields = $this->session->flashdata($this->controller.'_invalid');

        $this->render($this->addEditView, array(
            'galleryActive' => 1,
            'alert' => $isEdit ? 'edit' : '',
            'form_error' => $this->session->flashdata('form_error'),
            'invalid_fields' => is_array($invalidFields) ? $invalidFields : array(),
            'tbl_data' => $record,
            'categories' => $this->categories(),
            'services' => $this->services(),
            'image_directory' => $this->imageDirectory,
            'page_title' => PROJECT_TITLE.' | '.($isEdit ? 'Edit' : 'Add').' '.$this->moduleNameSingular,
            'userdata' => $this->user_data,
        ));
    }

    public function addRecord()
    {
        $data = $this->validatedPost(0);
        $upload = $this->admin_upload->save('image_upload', $this->imageDirectory, UPLOAD_IMAGE_MIMES);

        if ($upload['error'] !== '') {
            return $this->formFailure('Image: '.$upload['error'], 0, array('image_upload'));
        }

        if ($upload['filename'] === '') {
            return $this->formFailure('Choose an image to upload.', 0, array('image_upload'));
        }

        $this->load->library('admin_record_sorter');
        $now = date('Y-m-d H:i:s');
        $data[$this->colPrefix.'file'] = $upload['filename'];
        $data[$this->colPrefix.'order'] = $this->admin_record_sorter->nextOrder($this->controller);
        $data[$this->colPrefix.'added'] = $now;
        $data[$this->colPrefix.'updated'] = $now;

        if (!$this->SqlModel->insertRecord($this->tblName, $data)) {
            $this->admin_upload->delete($this->imageDirectory, $upload['filename']);

            return $this->formFailure('The image could not be saved. Please try again.');
        }

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

        $data = $this->validatedPost($editID);
        $upload = $this->admin_upload->save('image_upload', $this->imageDirectory, UPLOAD_IMAGE_MIMES);

        if ($upload['error'] !== '') {
            return $this->formFailure('Image: '.$upload['error'], $editID, array('image_upload'));
        }

        if ($upload['filename'] !== '') {
            $data[$this->colPrefix.'file'] = $upload['filename'];
        }

        $data[$this->colPrefix.'updated'] = date('Y-m-d H:i:s');

        if (!$this->SqlModel->updateRecord($this->tblName, $data, array($this->pKey => $editID))) {
            $this->admin_upload->delete($this->imageDirectory, $upload['filename']);

            return $this->formFailure('The image could not be updated. Please try again.', $editID);
        }

        if ($upload['filename'] !== '') {
            $this->deleteImageIfUnused($current[$this->colPrefix.'file']);
        }

        $this->session->set_flashdata('alert', 'editsuccess');
        redirect(base_url('manage/'.$this->controller));
    }

    /**
     * Bulk upload page.
     */
    public function upload()
    {
        $this->render('galleryUpload', array(
            'galleryActive' => 1,
            'categories' => $this->categories(),
            'services' => $this->services(),
            'page_title' => PROJECT_TITLE.' | Upload Gallery Images',
            'userdata' => $this->user_data,
            'useDropzone' => TRUE,
            'useGalleryUpload' => TRUE,
        ));
    }

    /**
     * AJAX endpoint for the bulk upload page: stores one file and creates its
     * gallery record. Responds with JSON.
     */
    public function do_upload()
    {
        $this->output->set_content_type('application/json');

        if ($this->input->method(TRUE) !== 'POST') {
            return $this->jsonResponse(405, array('status' => 'error', 'message' => 'Uploads require a POST request.'));
        }

        $originalName = isset($_FILES['file']['name']) ? (string) $_FILES['file']['name'] : '';
        $upload = $this->admin_upload->save('file', $this->imageDirectory, UPLOAD_IMAGE_MIMES);

        if ($upload['error'] !== '' || $upload['filename'] === '') {
            return $this->jsonResponse(422, array(
                'status' => 'error',
                'message' => $upload['error'] !== '' ? $upload['error'] : 'No image was received.',
            ));
        }

        $this->load->library('admin_record_sorter');
        $now = date('Y-m-d H:i:s');
        $status = $this->input->post($this->tStatus);
        $caption = $this->captionFromFilename($originalName);
        $data = array(
            $this->colPrefix.'category_id' => $this->validCategoryId($this->input->post($this->colPrefix.'category_id')),
            $this->colPrefix.'service_id' => $this->validServiceId($this->input->post($this->colPrefix.'service_id')),
            $this->colPrefix.'file' => $upload['filename'],
            $this->colPrefix.'caption' => $caption,
            $this->colPrefix.'featured' => 0,
            $this->colPrefix.'order' => $this->admin_record_sorter->nextOrder($this->controller),
            $this->tStatus => in_array($status, array('Enable', 'Disable'), TRUE) ? $status : 'Enable',
            $this->colPrefix.'added' => $now,
            $this->colPrefix.'updated' => $now,
        );
        $id = $this->SqlModel->insertRecord($this->tblName, $data);

        if (!$id) {
            $this->admin_upload->delete($this->imageDirectory, $upload['filename']);

            return $this->jsonResponse(500, array(
                'status' => 'error',
                'message' => 'The image could not be saved. Please try again.',
            ));
        }

        return $this->jsonResponse(200, array(
            'status' => 'success',
            'id' => (int) $id,
            'caption' => $caption,
            'edit_url' => base_url('manage/'.$this->controller.'/control/edit/'.(int) $id),
        ));
    }

    public function delete($deleteID = '')
    {
        $this->session->set_flashdata(
            'alert',
            $this->deleteImage((int) $deleteID) ? 'deletesuccess' : 'deleteerror'
        );
        redirect(base_url('manage/'.$this->controller));
    }

    public function deleteall()
    {
        $ids = admin_ids($this->input->post('records'));
        $deleted = 0;

        foreach ($ids as $id) {
            if ($this->deleteImage($id)) {
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
     * Gallery categories keyed by ID, in display order.
     */
    private function categories()
    {
        $rows = $this->SqlModel->getRecords(
            'category_id, category_name, category_status',
            'gallery_categories',
            'category_order',
            'ASC'
        );

        return array_column($rows, NULL, 'category_id');
    }

    /**
     * Services keyed by ID, in menu order.
     */
    private function services()
    {
        $rows = $this->db
            ->select('s.service_id, s.service_name, s.service_status')
            ->from('services s')
            ->join('service_categories c', 'c.category_id = s.service_category_id', 'left')
            ->order_by('c.category_order', 'ASC')
            ->order_by('s.service_order', 'ASC')
            ->get()
            ->result_array();

        return array_column($rows, NULL, 'service_id');
    }

    private function validCategoryId($value)
    {
        $id = (int) $value;

        return ($id > 0 && array_key_exists($id, $this->categories())) ? $id : NULL;
    }

    private function validServiceId($value)
    {
        $id = (int) $value;

        return ($id > 0 && array_key_exists($id, $this->services())) ? $id : NULL;
    }

    private function formFields()
    {
        return array(
            $this->colPrefix.'caption',
            $this->colPrefix.'category_id',
            $this->colPrefix.'service_id',
            $this->colPrefix.'featured',
            $this->tStatus,
        );
    }

    /**
     * Validates the posted details and returns the row to save. Redirects
     * back to the form when anything is invalid.
     */
    private function validatedPost($editID)
    {
        $caption = admin_clean_text($this->input->post($this->colPrefix.'caption'), 255);

        if ($caption === '') {
            $this->formFailure('Enter a caption. It is also used as the image\'s alternative text.', $editID, array($this->colPrefix.'caption'));
        }

        $status = $this->input->post($this->tStatus);

        return array(
            $this->colPrefix.'caption' => $caption,
            $this->colPrefix.'category_id' => $this->validCategoryId($this->input->post($this->colPrefix.'category_id')),
            $this->colPrefix.'service_id' => $this->validServiceId($this->input->post($this->colPrefix.'service_id')),
            $this->colPrefix.'featured' => $this->input->post($this->colPrefix.'featured') === '1' ? 1 : 0,
            $this->tStatus => in_array($status, array('Enable', 'Disable'), TRUE) ? $status : 'Disable',
        );
    }

    /**
     * "almond-shape_deep_plum.JPG" becomes "Almond shape deep plum".
     */
    private function captionFromFilename($filename)
    {
        $caption = pathinfo((string) $filename, PATHINFO_FILENAME);
        $caption = preg_replace('/[\s_\-]+/u', ' ', $caption);
        $caption = admin_clean_text($caption, 255);

        if ($caption === '') {
            return 'Gallery image';
        }

        return function_exists('mb_strtoupper')
            ? mb_strtoupper(mb_substr($caption, 0, 1, 'UTF-8'), 'UTF-8').mb_substr($caption, 1, NULL, 'UTF-8')
            : ucfirst($caption);
    }

    private function deleteImageIfUnused($filename)
    {
        if (!is_string($filename) || $filename === '') {
            return;
        }

        if ($this->SqlModel->countRecords($this->tblName, array($this->colPrefix.'file' => $filename)) > 0) {
            return;
        }

        $this->admin_upload->delete($this->imageDirectory, $filename);
    }

    private function deleteImage($id)
    {
        $record = $this->SqlModel->getSingleRecord($this->tblName, array($this->pKey => (int) $id));

        if (empty($record)) {
            return FALSE;
        }

        if (!$this->SqlModel->deleteRecord($this->tblName, array($this->pKey => (int) $id))) {
            return FALSE;
        }

        $this->deleteImageIfUnused($record[$this->colPrefix.'file']);

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

    private function jsonResponse($statusCode, $payload)
    {
        return $this->output
            ->set_status_header($statusCode)
            ->set_output(json_encode($payload));
    }
}
