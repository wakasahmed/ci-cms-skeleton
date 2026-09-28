<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Form Settings: text shown by the public forms (singleton record).
 *
 * Holds the contact form's confirmation message and its subject options.
 * Settings for the booking-request form are added in Phase 6 of
 * PROJECT_PLAN.md.
 */
class Form_settings extends CI_Controller
{
    public $tblName = 'form_settings';
    public $pKey = 'id';
    public $recordId = 1;
    public $moduleName = 'Form Settings';
    public $controller = 'form-settings';
    public $listView = 'formSettings';
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

        if ($this->SqlModel->checkAccess('access_settings', $this->user_data) === FALSE) {
            redirect(ADMIN_URL);

            return;
        }

        $this->load->helper('admin_input');
    }

    public function index()
    {
        $record = $this->SqlModel->getSingleRecord(
            $this->tblName,
            array($this->pKey => $this->recordId)
        );
        $record = is_array($record) ? $record : array();

        $posted = $this->session->flashdata($this->controller.'_data');

        if (is_array($posted)) {
            foreach ($this->fields() as $field) {
                if (isset($posted[$field]) && !is_array($posted[$field])) {
                    $record[$field] = (string) $posted[$field];
                }
            }
        }

        $invalidFields = $this->session->flashdata($this->controller.'_invalid');

        $data = array(
            'formSettingsActive' => 1,
            'page_title' => PROJECT_TITLE.' | '.$this->moduleName,
            'alert' => $this->session->flashdata('alert'),
            'form_error' => $this->session->flashdata('form_error'),
            'invalid_fields' => is_array($invalidFields) ? $invalidFields : array(),
            'userdata' => $this->user_data,
            'tbl_data' => $record,
            'useAccordionValidation' => TRUE,
        );

        $this->load->view('admin/header', $data);
        $this->load->view('admin/navigation');
        $this->load->view('admin/'.$this->listView);
        $this->load->view('admin/footer');
    }

    public function save()
    {
        $current = $this->SqlModel->getSingleRecord(
            $this->tblName,
            array($this->pKey => $this->recordId)
        );

        if (empty($current)) {
            return $this->formFailure('The Form Settings record is missing.');
        }

        $data = array(
            'contact_success' => admin_clean_text($this->input->post('contact_success')),
            'contact_subject' => admin_clean_lines($this->input->post('contact_subject'), 30, 150),
        );
        $invalid = array();

        foreach ($data as $field => $value) {
            if ($value === '') {
                $invalid[] = $field;
            }
        }

        if (!empty($invalid)) {
            return $this->formFailure('Complete all required form setting fields.', $invalid);
        }

        if (!$this->SqlModel->updateRecord($this->tblName, $data, array($this->pKey => $this->recordId))) {
            return $this->formFailure('The Form Settings could not be saved. No changes were applied.');
        }

        $this->session->set_flashdata('alert', 'success');
        redirect(base_url('manage/'.$this->controller));
    }

    /**
     * Columns edited on the form.
     */
    private function fields()
    {
        return array(
            'contact_success',
            'contact_subject',
        );
    }

    private function formFailure($message, $invalidFields = array())
    {
        $posted = $this->input->post(NULL, FALSE);

        $this->session->set_flashdata($this->controller.'_data', is_array($posted) ? $posted : array());
        $this->session->set_flashdata($this->controller.'_invalid', $invalidFields);
        $this->session->set_flashdata('form_error', $message);

        redirect(base_url('manage/'.$this->controller));
    }
}
