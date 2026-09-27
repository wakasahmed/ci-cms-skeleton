<?php defined('BASEPATH') OR exit('No direct script access allowed');

class Referrals extends CI_Controller
{
    public $tblName = 'referrals';
    public $colPrefix = 'ref_';
    public $pKey = 'ref_id';
    public $moduleName = 'Referrals';
    public $moduleNameSingular = 'Referral';
    public $moduleDesc = 'Manage referral contacts, payment details, and commission performance.';
    public $controller = 'referrals';
    public $per_page = 10;
    public $tStatus = 'ref_status';
    public $listView = 'referrals';
    public $addEditView = 'addReferral';
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
        $this->load->library('manage_translation_service');
    }

    public function index($sortby = 'ref_name', $order = 'ASC', $status = '-', $keywords = '-', $pg_no = '')
    {
        $allowedSorts = array($this->pKey, 'ref_name', 'ref_type', 'ref_organization', 'ref_status', 'ref_added', 'total_commission', 'total_received', 'total_remaining', 'total_use');
        $sortby = in_array($sortby, $allowedSorts, TRUE) ? $sortby : 'ref_name';
        $order = strtoupper($order) === 'DESC' ? 'DESC' : 'ASC';
        $status = in_array($status, array('Enable', 'Disable'), TRUE) ? $status : '-';
        $requestedPerPage = $this->input->get('per_page');
        if ($requestedPerPage !== NULL && ctype_digit((string) $requestedPerPage) && (int) $requestedPerPage <= 100) {
            $this->session->set_userdata('per_page', (int) $requestedPerPage);
        }
        if ($this->session->userdata('per_page') !== NULL) {
            $this->per_page = (int) $this->session->userdata('per_page');
        }
        $keywords = urldecode((string) $keywords);
        $where = $status === '-' ? array() : array($this->tStatus => $status);
        $search = $keywords === '-' ? array() : array('cols' => 'ref_name,ref_organization,ref_email', 'value' => $keywords);
        $baseUrl = base_url('manage/'.$this->controller.'/index/'.$sortby.'/'.$order.'/'.$status.'/'.rawurlencode($keywords));
        $totalRows = $this->SqlModel->countRecords($this->tblName, $where, $search);
        $uriSegment = 8;
        $offset = (int) $this->uri->segment($uriSegment, 0);
        $this->pagination->initialize(admin_pagination_config($baseUrl, $totalRows, $this->per_page, $uriSegment));
        $records = $this->SqlModel->getRecords($this->listingFields(), $this->tblName.' r', $sortby, $order, $where, $search, $this->per_page, $offset, FALSE);
        $ids = array();
        foreach ($records as $record) {
            $ids[] = (int) $record[$this->pKey];
        }
        $this->render($this->listView, array(
            'alert' => $this->session->flashdata('alert'),
            'page_title' => PROJECT_TITLE.' | '.$this->moduleName,
            'userdata' => $this->user_data,
            'referralsActive' => 1,
            'total_rows' => $totalRows,
            'per_page' => $this->per_page,
            'records' => $records,
            'translation_statuses' => empty($ids) ? array() : $this->manage_translation_service->statuses('referrals', $ids),
            'paginate' => $this->pagination->create_links(),
            'sortby' => $sortby,
            'order' => $order === 'ASC' ? 'DESC' : 'ASC',
            'page_numb' => $offset,
            'status' => $status,
            'keywords' => $keywords,
            'useManageTranslations' => TRUE,
        ));
    }

    public function control($alert = '', $editID = '')
    {
        $isEdit = $alert === 'edit';
        $record = array();
        $posted = $this->session->flashdata($this->controller.'_data');
        $locale = $isEdit ? $this->manage_translation_service->locale(is_array($posted) && isset($posted['active_locale']) ? $posted['active_locale'] : $this->input->get('lang', TRUE)) : 'en';
        if ($isEdit) {
            $editID = (int) $editID;
            $record = $this->SqlModel->getSingleRecord($this->tblName, array($this->pKey => $editID));
            if (empty($record)) {
                $this->session->set_flashdata('alert', 'error');
                redirect(base_url('manage/'.$this->controller));
                return;
            }
        }
        $viewRecord = $record;
        if (is_array($posted)) {
            foreach ($this->sharedFields() as $field) {
                if (array_key_exists($field, $posted)) {
                    $viewRecord[$field] = $posted[$field];
                }
            }
        }
        $localized = $this->manage_translation_service->localized_values('referrals', $record, $locale);
        if (is_array($posted)) {
            foreach (array_keys($localized) as $control) {
                if (array_key_exists($control, $posted)) {
                    $localized[$control] = $posted[$control];
                }
            }
        }
        $this->render($this->addEditView, array(
            'referralsActive' => 1,
            'alert' => $isEdit ? 'edit' : '',
            'form_error' => $this->session->flashdata('form_error'),
            'tbl_data' => $viewRecord,
            'page_title' => PROJECT_TITLE.' | '.($isEdit ? 'Edit' : 'Add').' '.$this->moduleNameSingular,
            'userdata' => $this->user_data,
            'localized_values' => $localized,
            'active_locale' => $locale,
            'manage_locales' => $this->manage_translation_service->locales(),
            'translation_state' => $isEdit ? $this->manage_translation_service->state('referrals', $editID) : NULL,
            'countries' => $this->SqlModel->getRecords('id,name', 'countries', 'name', 'ASC'),
            'useManageTranslations' => TRUE,
        ));
    }

    public function addRecord()
    {
        if (!$this->validInput('en')) {
            return $this->formFailure('Enter a referral name, valid email address, and phone number.');
        }
        $data = $this->postedReferralData('en');
        $data['ref_name_ar'] = '';
        $data['ref_added'] = date('Y-m-d H:i:s');
        $data['ref_updated'] = date('Y-m-d H:i:s');
        $id = $this->SqlModel->insertRecord($this->tblName, $data);
        if (!$id) {
            return $this->formFailure('The referral could not be saved. Please try again.');
        }
        $this->queueTranslationSafely($id);
        $this->session->set_flashdata('alert', 'success');
        redirect(base_url('manage/'.$this->controller));
    }

    public function editRecord($editID = '')
    {
        $id = (int) $editID;
        $locale = $this->manage_translation_service->locale($this->input->post('active_locale', TRUE));
        if (empty($this->SqlModel->getSingleRecord($this->tblName, array($this->pKey => $id)))) {
            $this->session->set_flashdata('alert', 'error');
            redirect(base_url('manage/'.$this->controller));
            return;
        }
        if (!$this->validInput($locale)) {
            return $this->formFailure('Enter a referral name, valid email address, and phone number.', $id, $locale);
        }
        $data = $this->postedReferralData($locale);
        $data['ref_updated'] = date('Y-m-d H:i:s');
        if (!$this->SqlModel->updateRecord($this->tblName, $data, array($this->pKey => $id))) {
            return $this->formFailure('The referral could not be updated. Please try again.', $id, $locale);
        }
        if ($locale === 'en') {
            $this->queueTranslationSafely($id);
        }
        $this->session->set_flashdata('alert', 'editsuccess');
        $redirectLocale = $this->input->post('redirect_lang', TRUE);
        if ($redirectLocale !== '' && $this->manage_translation_service->locale($redirectLocale) === $redirectLocale) {
            redirect(base_url('manage/'.$this->controller.'/control/edit/'.$id.'?lang='.$redirectLocale));
            return;
        }
        redirect(base_url('manage/'.$this->controller));
    }

    public function delete($deleteID = '')
    {
        $deleted = $this->deleteReferral((int) $deleteID);
        $this->session->set_flashdata('alert', $deleted ? 'deletesuccess' : 'deleteerror');
        redirect(base_url('manage/'.$this->controller));
    }

    public function deleteall()
    {
        $ids = array_unique(array_filter(array_map('intval', (array) $this->input->post('records'))));
        $deleted = 0;
        foreach ($ids as $id) {
            if ($this->deleteReferral($id)) {
                $deleted++;
            }
        }
        $this->session->set_flashdata('alert', $deleted > 0 && $deleted === count($ids) ? 'deletesuccess' : 'deleteerror');
        redirect(base_url('manage/'.$this->controller));
    }

    public function changestatus($id = 0, $status = 'Enable')
    {
        $id = (int) $id;
        $this->output->set_content_type('application/json');
        if ($id < 1 || !in_array($status, array('Enable', 'Disable'), TRUE) || !$this->SqlModel->updateRecord($this->tblName, array($this->tStatus => $status), array($this->pKey => $id))) {
            return $this->output->set_output(json_encode(array('status' => 'false')));
        }
        return $this->output->set_output(json_encode(array('status' => 'true', 'id' => $id, 'currentStatus' => $status)));
    }

    public function duplicate($id = 0)
    {
        $data = $this->SqlModel->getSingleRecord($this->tblName, array($this->pKey => (int) $id));
        if (empty($data)) {
            $this->session->set_flashdata('alert', 'error');
            redirect(base_url('manage/'.$this->controller));
            return;
        }
        unset($data[$this->pKey]);
        $data['ref_name'] .= ' Duplicate';
        $data['ref_added'] = $data['ref_updated'] = date('Y-m-d H:i:s');
        $newId = $this->SqlModel->insertRecord($this->tblName, $data);
        if (!$newId) {
            $this->session->set_flashdata('alert', 'error');
            redirect(base_url('manage/'.$this->controller));
            return;
        }
        $this->queueTranslationSafely($newId);
        redirect(base_url('manage/'.$this->controller.'/control/edit/'.$newId));
    }

    private function render($view, array $data)
    {
        $this->load->view('admin/header', $data);
        $this->load->view('admin/navigation');
        $this->load->view('admin/'.$view);
        $this->load->view('admin/footer');
    }

    private function listingFields()
    {
        // Successful redemptions are Completed bookings, plus any legacy Consumed rows.
        return 'r.*, (SELECT SUM(book_ref_commission) FROM tour_bookings b INNER JOIN discount_codes p ON b.book_promo_id = p.discount_id WHERE p.discount_ref_id = r.ref_id AND b.book_status IN ("Completed", "Consumed")) total_commission, (SELECT SUM(book_ref_commission) FROM tour_bookings b INNER JOIN discount_codes p ON b.book_promo_id = p.discount_id WHERE p.discount_ref_id = r.ref_id AND b.book_status IN ("Completed", "Consumed") AND b.book_ref_commission_received = "Yes") total_received, (SELECT SUM(book_ref_commission) FROM tour_bookings b INNER JOIN discount_codes p ON b.book_promo_id = p.discount_id WHERE p.discount_ref_id = r.ref_id AND b.book_status IN ("Completed", "Consumed") AND b.book_ref_commission_received = "No") total_remaining, (SELECT COUNT(*) FROM tour_bookings b INNER JOIN discount_codes p ON b.book_promo_id = p.discount_id WHERE p.discount_ref_id = r.ref_id) total_use';
    }

    private function postedReferralData($locale)
    {
        $post = $this->input->post(NULL, FALSE);
        $type = $this->input->post('ref_type', TRUE);
        $language = $this->input->post('ref_lang', TRUE);
        $status = $this->input->post('ref_status', TRUE);
        $data = array(
            'ref_type' => in_array($type, array('Government', 'Company', 'Individual'), TRUE) ? $type : 'Individual',
            'ref_email' => trim((string) $this->input->post('ref_email', TRUE)),
            'ref_phone' => $this->postedText('ref_phone'),
            'ref_acc_title' => $this->postedText('ref_acc_title'),
            'ref_bank_name' => $this->postedText('ref_bank_name'),
            'ref_acc_no' => $this->postedText('ref_acc_no'),
            'ref_swift_code' => $this->postedText('ref_swift_code'),
            'ref_bank_code' => $this->postedText('ref_bank_code'),
            'ref_bank_address' => $this->postedText('ref_bank_address'),
            'ref_country' => $this->postedText('ref_country'),
            'ref_lang' => in_array($language, array('Arabic', 'English'), TRUE) ? $language : 'Arabic',
            'ref_status' => in_array($status, array('Enable', 'Disable'), TRUE) ? $status : 'Enable',
        );
        return array_merge($data, $this->manage_translation_service->localized_post_data('referrals', $locale, is_array($post) ? $post : array()));
    }

    private function validInput($locale)
    {
        $post = $this->input->post(NULL, FALSE);
        return $this->manage_translation_service->required_localized_input_valid('referrals', $locale, is_array($post) ? $post : array())
            && filter_var(trim((string) $this->input->post('ref_email', TRUE)), FILTER_VALIDATE_EMAIL) !== FALSE
            && trim((string) $this->input->post('ref_phone', TRUE)) !== '';
    }

    private function formFailure($message, $editID = 0, $locale = 'en')
    {
        $posted = $this->input->post(NULL, FALSE);
        if (is_array($posted)) {
            $posted['active_locale'] = $this->manage_translation_service->locale($locale);
        }
        $this->session->set_flashdata($this->controller.'_data', is_array($posted) ? $posted : array());
        $this->session->set_flashdata('form_error', $message);
        redirect(base_url('manage/'.$this->controller.'/control'.($editID ? '/edit/'.(int) $editID.'?lang='.$this->manage_translation_service->locale($locale) : '')));
    }

    private function deleteReferral($id)
    {
        if ((int) $id < 1) {
            return FALSE;
        }
        $this->db->trans_begin();
        $this->manage_translation_service->delete_jobs('referrals', (int) $id);
        $deleted = $this->SqlModel->deleteRecord($this->tblName, array($this->pKey => (int) $id));
        if (!$deleted || $this->db->trans_status() === FALSE) {
            $this->db->trans_rollback();
            return FALSE;
        }
        $this->db->trans_commit();
        return TRUE;
    }

    private function queueTranslationSafely($id)
    {
        try {
            $this->manage_translation_service->queue('referrals', (int) $id);
        } catch (Throwable $exception) {
            log_message('error', 'Referral translation could not be queued for record '.(int) $id.'.');
        }
    }

    private function postedText($field)
    {
        return trim((string) $this->input->post($field, FALSE));
    }

    private function sharedFields()
    {
        return array('ref_type', 'ref_email', 'ref_phone', 'ref_acc_title', 'ref_bank_name', 'ref_acc_no', 'ref_swift_code', 'ref_bank_code', 'ref_bank_address', 'ref_country', 'ref_lang', 'ref_status');
    }
}
