<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Gallery categories drive the filter chips on the gallery page
 * ("Manicure", "Nail Art", "Hair" ...).
 *
 * Structure follows the canonical CRUD module (see AGENTS.md). Deleting a
 * category keeps its images; they become uncategorised.
 */
class Gallery_categories extends CI_Controller
{
    public $tblName = 'gallery_categories';
    public $colPrefix = 'category_';
    public $pKey = 'category_id';
    public $moduleName = 'Gallery Categories';
    public $moduleNameSingular = 'Gallery Category';
    public $moduleDesc = 'Manage the filters visitors use to browse the gallery.';
    public $controller = 'gallery-categories';
    public $per_page = 10;
    public $tStatus = 'category_status';
    public $listView = 'galleryCategories';
    public $addEditView = 'addGalleryCategory';
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
        $this->load->library('admin_slug');
    }

    public function index($sortby = 'category_order', $order = 'ASC', $status = '-', $keywords = '-', $pg_no = '')
    {
        $allowedSorts = array(
            $this->pKey,
            $this->colPrefix.'order',
            $this->colPrefix.'name',
            $this->tStatus,
            $this->colPrefix.'added',
            $this->colPrefix.'updated',
        );
        $sortby = in_array($sortby, $allowedSorts, TRUE) ? $sortby : $this->colPrefix.'order';
        $order = strtoupper($order) === 'DESC' ? 'DESC' : 'ASC';
        $status = in_array($status, array('Enable', 'Disable'), TRUE) ? $status : '-';
        $this->applyPerPage();

        $keywords = urldecode((string) $keywords);
        $where = $status === '-' ? array() : array($this->tStatus => $status);
        $search = $keywords !== '-'
            ? array('cols' => $this->colPrefix.'name,'.$this->colPrefix.'slug', 'value' => $keywords)
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
            'galleryCategoriesActive' => 1,
            'total_rows' => $totalRows,
            'per_page' => $this->per_page,
            'records' => $records,
            'image_counts' => $this->imageCounts($records),
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
        $record = array();

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
            'galleryCategoriesActive' => 1,
            'alert' => $isEdit ? 'edit' : '',
            'form_error' => $this->session->flashdata('form_error'),
            'invalid_fields' => is_array($invalidFields) ? $invalidFields : array(),
            'tbl_data' => $record,
            'page_title' => PROJECT_TITLE.' | '.($isEdit ? 'Edit' : 'Add').' '.$this->moduleNameSingular,
            'userdata' => $this->user_data,
        ));
    }

    public function addRecord()
    {
        $data = $this->postedData(0);

        if (is_string($data)) {
            return;
        }

        $this->load->library('admin_record_sorter');
        $now = date('Y-m-d H:i:s');
        $data[$this->colPrefix.'order'] = $this->admin_record_sorter->nextOrder($this->controller);
        $data[$this->colPrefix.'added'] = $now;
        $data[$this->colPrefix.'updated'] = $now;

        if (!$this->SqlModel->insertRecord($this->tblName, $data)) {
            return $this->formFailure('The record could not be saved. Please try again.');
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

        $data = $this->postedData($editID);

        if (is_string($data)) {
            return;
        }

        $data[$this->colPrefix.'updated'] = date('Y-m-d H:i:s');

        if (!$this->SqlModel->updateRecord($this->tblName, $data, array($this->pKey => $editID))) {
            return $this->formFailure('The record could not be updated. Please try again.', $editID);
        }

        $this->session->set_flashdata('alert', 'editsuccess');
        redirect(base_url('manage/'.$this->controller));
    }

    public function delete($deleteID = '')
    {
        $this->session->set_flashdata(
            'alert',
            $this->deleteCategory((int) $deleteID) ? 'deletesuccess' : 'deleteerror'
        );
        redirect(base_url('manage/'.$this->controller));
    }

    public function deleteall()
    {
        $ids = admin_ids($this->input->post('records'));
        $deleted = 0;

        foreach ($ids as $id) {
            if ($this->deleteCategory($id)) {
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

    private function formFields()
    {
        return array(
            $this->colPrefix.'name',
            $this->colPrefix.'slug',
            $this->tStatus,
        );
    }

    /**
     * Validates the posted form. Returns the row to save, or a string after
     * redirecting back to the form with the submitted values.
     */
    private function postedData($editID)
    {
        $name = admin_clean_text($this->input->post($this->colPrefix.'name'), 100);
        $slugInput = $this->input->post($this->colPrefix.'slug');
        $slug = $this->admin_slug->normalize($slugInput !== NULL && trim($slugInput) !== '' ? $slugInput : $name, 110);
        $status = $this->input->post($this->tStatus);
        $invalid = array();

        if ($name === '') {
            $invalid[] = $this->colPrefix.'name';
        }

        if ($slug === '') {
            $invalid[] = $this->colPrefix.'slug';
        }

        if (!empty($invalid)) {
            $this->formFailure('Enter the category name.', $editID, $invalid);

            return 'invalid';
        }

        return array(
            $this->colPrefix.'name' => $name,
            $this->colPrefix.'slug' => $this->admin_slug->unique(
                $this->tblName,
                $this->colPrefix.'slug',
                $this->pKey,
                $slug,
                $editID,
                110
            ),
            $this->tStatus => in_array($status, array('Enable', 'Disable'), TRUE) ? $status : 'Disable',
        );
    }

    private function formFailure($message, $editID = 0, $invalidFields = array())
    {
        $posted = $this->input->post(NULL, FALSE);

        $this->session->set_flashdata($this->controller.'_data', is_array($posted) ? $posted : array());
        $this->session->set_flashdata($this->controller.'_invalid', $invalidFields);
        $this->session->set_flashdata('form_error', $message);

        redirect(base_url(
            'manage/'.$this->controller.'/control'.($editID ? '/edit/'.(int) $editID : '')
        ));
    }

    /**
     * Images in the category keep their rows; the database clears their
     * category link (ON DELETE SET NULL).
     */
    private function deleteCategory($id)
    {
        $record = $this->SqlModel->getSingleRecord($this->tblName, array($this->pKey => (int) $id));

        if (empty($record)) {
            return FALSE;
        }

        return (bool) $this->SqlModel->deleteRecord($this->tblName, array($this->pKey => (int) $id));
    }

    private function imageCounts($records)
    {
        $ids = array_map('intval', array_column($records, $this->pKey));

        if (empty($ids)) {
            return array();
        }

        $rows = $this->db
            ->select('image_category_id, COUNT(*) AS total')
            ->where_in('image_category_id', $ids)
            ->group_by('image_category_id')
            ->get('gallery_images')
            ->result_array();
        $counts = array();

        foreach ($rows as $row) {
            $counts[(int) $row['image_category_id']] = (int) $row['total'];
        }

        return $counts;
    }
}
