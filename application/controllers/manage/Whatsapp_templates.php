<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Manage WhatsApp message templates. Saving a template stores it locally and
 * then submits the English and Arabic bodies to Meta for review.
 *
 * Templates are system-defined: each wt_id matches the email template ID the
 * code sends for the same notification, so they can be edited but never added
 * or deleted here.
 */
class Whatsapp_templates extends CI_Controller
{
    public $tblName = 'whatsapp_templates';
    public $colPrefix = 'wt_';
    public $pKey = 'wt_id';
    public $moduleName = 'WhatsApp Templates';
    public $moduleNameSingular = 'WhatsApp Template';
    public $moduleDesc = 'Manage English and Arabic WhatsApp notification templates and their Meta approval status.';
    public $controller = 'whatsapp-templates';
    public $per_page = 10;
    public $tStatus = 'wt_status';
    public $listView = 'whatsappTemplates';
    public $addEditView = 'addWhatsappTemplate';
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

            return;
        }
        $this->load->library('Whatsapp_template_service');
    }

    public function index($sortby = 'wt_id', $order = 'ASC', $status = '-', $keywords = '-', $pg_no = '')
    {
        $allowedSorts = array(
            $this->pKey,
            $this->colPrefix . 'title',
            $this->colPrefix . 'category',
            $this->tStatus,
            $this->colPrefix . 'updated',
        );
        $sortby = in_array($sortby, $allowedSorts, true) ? $sortby : $this->pKey;
        $order = strtoupper($order) === 'DESC' ? 'DESC' : 'ASC';
        $status = in_array($status, array('Enable', 'Disable'), true) ? $status : '-';

        $requestedPerPage = $this->input->get('per_page');
        if ($requestedPerPage !== null
            && ctype_digit((string) $requestedPerPage)
            && (int) $requestedPerPage <= 100
        ) {
            $this->session->set_userdata('per_page', (int) $requestedPerPage);
        }
        if ($this->session->userdata('per_page') !== null) {
            $this->per_page = (int) $this->session->userdata('per_page');
        }

        $keywords = urldecode((string) $keywords);
        $where = $status === '-' ? array() : array($this->tStatus => $status);
        $search = $keywords !== '-'
            ? array(
                'cols' => $this->colPrefix . 'title,' . $this->colPrefix . 'name',
                'value' => $keywords,
            )
            : array();
        $baseUrl = base_url(
            'manage/' . $this->controller . '/index/' . $sortby . '/' . $order . '/'
            . $status . '/' . rawurlencode($keywords)
        );
        $totalRows = $this->SqlModel->countRecords($this->tblName, $where, $search);
        $uriSegment = 8;
        $offset = (int) $this->uri->segment($uriSegment, 0);

        $this->pagination->initialize(
            admin_pagination_config($baseUrl, $totalRows, $this->per_page, $uriSegment)
        );
        $records = $this->SqlModel->getRecords(
            '*',
            $this->tblName,
            $sortby,
            $order,
            $where,
            $search,
            $this->per_page,
            $offset,
            false
        );

        $data = array(
            'alert' => $this->session->flashdata('alert'),
            'meta_error' => $this->session->flashdata('meta_error'),
            'page_title' => PROJECT_TITLE . ' | ' . $this->moduleName,
            'userdata' => $this->user_data,
            'whatsappTemplatesActive' => 1,
            'total_rows' => $totalRows,
            'per_page' => $this->per_page,
            'records' => $records,
            'paginate' => $this->pagination->create_links(),
            'sortby' => $sortby,
            'order' => $order === 'ASC' ? 'DESC' : 'ASC',
            'page_numb' => $offset,
            'status' => $status,
            'keywords' => $keywords,
        );
        $this->render($this->listView, $data);
    }

    public function control($alert = '', $editID = '')
    {
        $record = $alert === 'edit'
            ? $this->SqlModel->getSingleRecord($this->tblName, array($this->pKey => (int) $editID))
            : array();
        if (empty($record)) {
            if ($alert === 'edit') {
                $this->session->set_flashdata('alert', 'error');
            }
            redirect(base_url('manage/' . $this->controller));

            return;
        }

        $viewRecord = $record;
        $postedData = $this->session->flashdata($this->controller . '_data');
        if (is_array($postedData)) {
            foreach ($this->editableColumns() as $column) {
                if (array_key_exists($column, $postedData) && !is_array($postedData[$column])) {
                    $viewRecord[$column] = (string) $postedData[$column];
                }
            }
        }

        $data = array(
            'whatsappTemplatesActive' => 1,
            'useWhatsappTemplates' => true,
            'useShortTagPicker' => true,
            'useSweetAlert' => true,
            'alert' => 'edit',
            'saved_alert' => $this->session->flashdata('alert'),
            'form_error' => $this->session->flashdata('form_error'),
            'form_error_field' => $this->session->flashdata('form_error_field'),
            'meta_error' => $this->session->flashdata('meta_error'),
            'test_error' => $this->session->flashdata('test_error'),
            'test_phone' => $this->session->flashdata('test_phone')
                ?: (string) $this->session->userdata('whatsapp_test_phone'),
            'tbl_data' => $viewRecord,
            'stored_record' => $record,
            'short_tags' => $this->whatsapp_template_service->tagsFor($record[$this->pKey]),
            'short_tag_entity' => $this->short_tags->entityLabel($record[$this->pKey]),
            'languages' => array_keys(Whatsapp_template_service::LANGUAGES),
            'page_title' => PROJECT_TITLE . ' | Edit ' . $this->moduleNameSingular,
            'userdata' => $this->user_data,
        );
        $this->render($this->addEditView, $data);
    }

    public function editRecord($editID = '')
    {
        $editID = (int) $editID;
        $current = $this->SqlModel->getSingleRecord($this->tblName, array($this->pKey => $editID));
        if (empty($current)) {
            $this->session->set_flashdata('alert', 'error');
            redirect(base_url('manage/' . $this->controller));

            return;
        }

        $data = $this->postedData($current);
        $error = $this->formError($data, $current);
        if ($error !== null) {
            return $this->formFailure($error['message'], $error['field'], $editID);
        }

        $data[$this->colPrefix . 'updated'] = date('Y-m-d H:i:s');
        if (!$this->SqlModel->updateRecord($this->tblName, $data, array($this->pKey => $editID))) {
            return $this->formFailure('The template could not be updated. Please try again.', '', $editID);
        }

        $this->submitToMeta($editID, 'editsuccess');
    }

    /** Submit unsent changes to Meta and read back the current review statuses. */
    public function sync($id = 0)
    {
        $id = (int) $id;
        if ($this->input->method() !== 'post'
            || empty($this->SqlModel->getSingleRecord($this->tblName, array($this->pKey => $id)))
        ) {
            $this->session->set_flashdata('alert', 'error');
            redirect(base_url('manage/' . $this->controller));

            return;
        }

        $errors = $this->whatsapp_template_service->submit($id);
        $refreshError = $this->whatsapp_template_service->refresh($id);
        if ($refreshError !== '') {
            $errors[] = 'Status refresh: ' . $refreshError;
        }

        if (!empty($errors)) {
            $this->session->set_flashdata('meta_error', implode(' ', $errors));
        } else {
            $this->session->set_flashdata('alert', 'synced');
        }
        redirect(base_url('manage/' . $this->controller . '/control/edit/' . $id));
    }

    /**
     * Send the approved template to one number with example values, for checking
     * how it arrives and for Meta review recordings.
     */
    public function sendtest($id = 0)
    {
        $id = (int) $id;
        if ($this->input->method() !== 'post'
            || empty($this->SqlModel->getSingleRecord($this->tblName, array($this->pKey => $id)))
        ) {
            $this->session->set_flashdata('alert', 'error');
            redirect(base_url('manage/' . $this->controller));

            return;
        }

        $phone = trim((string) $this->input->post('test_phone', true));
        $language = (string) $this->input->post('test_language', true);
        $result = $this->whatsapp_template_service->sendSample($phone, $id, $language);

        if (!empty($result['success'])) {
            // Remember the number so repeated tests do not need it typed again.
            $this->session->set_userdata('whatsapp_test_phone', $phone);
            $this->session->set_flashdata('alert', 'testsent');
        } else {
            $this->session->set_flashdata('test_error', $result['error']);
            $this->session->set_flashdata('test_phone', $phone);
        }
        redirect(base_url('manage/' . $this->controller . '/control/edit/' . $id . '#whatsapp-template-test'));
    }

    public function changestatus($id = 0, $status = 'Enable')
    {
        $this->output->set_content_type('application/json');
        if (!in_array($status, array('Enable', 'Disable'), true)
            || !$this->SqlModel->updateRecord(
                $this->tblName,
                array($this->tStatus => $status),
                array($this->pKey => (int) $id)
            )
        ) {
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
        $this->load->view('admin/' . $view);
        $this->load->view('admin/footer');
    }

    private function editableColumns()
    {
        return array(
            $this->colPrefix . 'title',
            $this->colPrefix . 'name',
            $this->colPrefix . 'category',
            $this->colPrefix . 'body_en',
            $this->colPrefix . 'body_ar',
            $this->tStatus,
        );
    }

    /**
     * Collect the submitted values. The Meta name and category are kept from the
     * stored record once Meta has accepted the template, and a language that is
     * in review keeps its stored body.
     */
    private function postedData(array $current)
    {
        $status = $this->input->post($this->tStatus);
        $category = $this->input->post($this->colPrefix . 'category');
        $data = array(
            $this->colPrefix . 'title' => $this->postedString('title'),
            $this->colPrefix . 'name' => strtolower($this->postedString('name')),
            $this->colPrefix . 'category' => in_array($category, array('UTILITY', 'MARKETING'), true)
                ? $category
                : 'UTILITY',
            $this->colPrefix . 'body_en' => $this->postedBody('body_en'),
            $this->colPrefix . 'body_ar' => $this->postedBody('body_ar'),
            $this->tStatus => in_array($status, array('Enable', 'Disable'), true) ? $status : 'Enable',
        );

        if ($this->whatsapp_template_service->isSubmitted($current)) {
            $data[$this->colPrefix . 'name'] = $current[$this->colPrefix . 'name'];
            $data[$this->colPrefix . 'category'] = $current[$this->colPrefix . 'category'];
        }
        foreach (array_keys(Whatsapp_template_service::LANGUAGES) as $language) {
            if ($this->whatsapp_template_service->isLocked($current, $language)) {
                $data[$this->colPrefix . 'body_' . $language] = (string) $current[$this->colPrefix . 'body_' . $language];
            }
        }

        return $data;
    }

    /** Returns array('message' => ..., 'field' => ...) for the first invalid field, or null. */
    private function formError(array $data, array $current)
    {
        $title = $data[$this->colPrefix . 'title'];
        if ($title === '') {
            return $this->fieldError('Enter a title.', 'wt_title');
        }
        if (mb_strlen($title, 'UTF-8') > 150) {
            return $this->fieldError('The title must not exceed 150 characters.', 'wt_title');
        }

        $name = $data[$this->colPrefix . 'name'];
        if (preg_match('/^[a-z][a-z0-9_]{0,99}$/D', $name) !== 1) {
            return $this->fieldError(
                'The Meta template name must start with a letter and use only lowercase letters, '
                . 'numbers and underscores (100 characters maximum).',
                'wt_name'
            );
        }
        $duplicate = $this->SqlModel->getSingleRecord($this->tblName, array($this->colPrefix . 'name' => $name));
        if (!empty($duplicate)
            && (int) $duplicate[$this->pKey] !== (int) $current[$this->pKey]
        ) {
            return $this->fieldError('Another template already uses this Meta template name.', 'wt_name');
        }

        $englishError = $this->whatsapp_template_service->bodyError(
            $data[$this->colPrefix . 'body_en'],
            'English',
            $current[$this->pKey]
        );
        if ($englishError !== '') {
            return $this->fieldError($englishError, 'wt_body_en');
        }

        $arabic = $data[$this->colPrefix . 'body_ar'];
        if (trim($arabic) === '') {
            if ((string) $current[$this->colPrefix . 'ar_meta_id'] !== '') {
                return $this->fieldError(
                    'The Arabic message has already been submitted to Meta and cannot be removed.',
                    'wt_body_ar'
                );
            }

            return null;
        }
        $arabicError = $this->whatsapp_template_service->bodyError($arabic, 'Arabic', $current[$this->pKey]);
        if ($arabicError !== '') {
            return $this->fieldError($arabicError, 'wt_body_ar');
        }

        return null;
    }

    private function fieldError($message, $field)
    {
        return array(
            'message' => $message,
            'field' => $field,
        );
    }

    /** Submit the saved record to Meta, then redirect according to the outcome. */
    private function submitToMeta($id, $successAlert)
    {
        $errors = $this->whatsapp_template_service->submit($id);
        if (empty($errors)) {
            $this->session->set_flashdata('alert', $successAlert);
            redirect(base_url('manage/' . $this->controller));

            return;
        }

        // The template itself is saved; only the Meta submission needs another attempt.
        $this->session->set_flashdata('alert', 'savednotsubmitted');
        $this->session->set_flashdata('meta_error', implode(' ', $errors));
        redirect(base_url('manage/' . $this->controller . '/control/edit/' . (int) $id));
    }

    private function postedString($key)
    {
        $value = $this->input->post($this->colPrefix . $key, false);

        return is_array($value) ? '' : trim((string) $value);
    }

    /** Keep the body's own line breaks, but normalise them and trim surrounding space. */
    private function postedBody($key)
    {
        $value = $this->input->post($this->colPrefix . $key, false);
        if (is_array($value)) {
            return '';
        }

        return trim(str_replace(array("\r\n", "\r"), "\n", (string) $value));
    }

    private function formFailure($message, $field = '', $editID = 0)
    {
        $posted = $this->input->post(null, false);
        $this->session->set_flashdata($this->controller . '_data', is_array($posted) ? $posted : array());
        $this->session->set_flashdata('form_error', $message);
        $this->session->set_flashdata('form_error_field', $field);
        redirect(base_url(
            'manage/' . $this->controller . '/control' . ($editID ? '/edit/' . (int) $editID : '')
        ));
    }
}
