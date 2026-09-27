<?php defined('BASEPATH') OR exit('No direct script access allowed');

class Discount_codes extends CI_Controller {

	public $tblName = 'discount_codes';
	public $colPrefix = 'discount_';
	public $pKey = 'discount_id';
	public $moduleName = 'Discount Codes';
	public $moduleNameSingular = 'Discount Code';
	public $moduleDesc = 'Manage referral discount codes, commission terms, usage limits, and validity.';
	public $controller = 'discount-codes';
	public $per_page = 10;
	public $tStatus = 'discount_status';
	public $listView = 'discount_codes';
	public $addEditView = 'addDiscountCode';
	public $user_data = array();

	public function __construct()
	{
		parent::__construct();
		$this->user_data = $this->SqlModel->authAdmin($this->session->userdata('admin_auth'), $this->session->userdata('admin_id'));
		if (empty($this->user_data)) redirect(base_url('manage/login'));
		$this->load->library('manage_translation_service');
	}

	public function index($sortby = 'discount_added', $order = 'DESC', $status = '-', $keywords = '-', $ref_id = '0', $pg_no = '')
	{
		$allowedSorts = array(
			$this->pKey, 'ref_name', $this->colPrefix.'name', $this->colPrefix.'name_ar',
			$this->colPrefix.'code', $this->colPrefix.'type', $this->colPrefix.'value',
			'total_commission', 'total_received', 'total_remaining', 'total_use', 'completed_uses',
			$this->colPrefix.'expiry', $this->tStatus, $this->colPrefix.'added', $this->colPrefix.'updated',
		);
		$sortby = in_array($sortby, $allowedSorts, TRUE) ? $sortby : $this->colPrefix.'added';
		$order = strtoupper($order) === 'ASC' ? 'ASC' : 'DESC';
		$status = in_array($status, array('Enable', 'Disable', 'Expired'), TRUE) ? $status : '-';
		$ref_id = ctype_digit((string) $ref_id) ? max(0, (int) $ref_id) : 0;
		$allowedPerPage = array_merge(array(0), range(10, 100, 10));
		$requestedPerPage = $this->input->get('per_page');
		if ($requestedPerPage !== NULL && ctype_digit((string) $requestedPerPage) && in_array((int) $requestedPerPage, $allowedPerPage, TRUE)) $this->session->set_userdata('per_page', (int) $requestedPerPage);
		if ($this->session->userdata('per_page') !== NULL && in_array((int) $this->session->userdata('per_page'), $allowedPerPage, TRUE)) $this->per_page = (int) $this->session->userdata('per_page');

		$keywords = urldecode((string) $keywords);
		$where = array();
		if ($status !== '-') $where[$this->tStatus] = $status;
		if ($ref_id > 0) $where[$this->colPrefix.'ref_id'] = $ref_id;
		$search = $keywords !== '-' ? array('cols' => 'ref_name,discount_name,discount_name_ar,discount_code', 'value' => $keywords) : array();
		$source = $this->tblName.' p LEFT JOIN referrals r ON r.ref_id = p.discount_ref_id';
		$baseUrl = base_url('manage/'.$this->controller.'/index/'.$sortby.'/'.$order.'/'.$status.'/'.rawurlencode($keywords).'/'.$ref_id);
		$totalRows = $this->SqlModel->countRecords($source, $where, $search);
		$uriSegment = 9;
		$offset = max(0, (int) $this->uri->segment($uriSegment, 0));
		$this->pagination->initialize(admin_pagination_config($baseUrl, $totalRows, $this->per_page, $uriSegment));
		// Successful redemptions are Completed bookings, plus any legacy Consumed rows.
		$select = 'p.*, r.ref_name, r.ref_organization,'
			.' (SELECT COALESCE(SUM(b.book_ref_commission), 0) FROM tour_bookings b WHERE p.discount_id = b.book_promo_id AND b.book_status IN ("Completed", "Consumed")) total_commission,'
			.' (SELECT COALESCE(SUM(b.book_ref_commission), 0) FROM tour_bookings b WHERE p.discount_id = b.book_promo_id AND b.book_status IN ("Completed", "Consumed") AND b.book_ref_commission_received = "Yes") total_received,'
			.' (SELECT COALESCE(SUM(b.book_ref_commission), 0) FROM tour_bookings b WHERE p.discount_id = b.book_promo_id AND b.book_status IN ("Completed", "Consumed") AND b.book_ref_commission_received = "No") total_remaining,'
			.' (SELECT COUNT(*) FROM tour_bookings b WHERE p.discount_id = b.book_promo_id) total_use,'
			// total_use (any booking) guards deletion; completed_uses is the displayed usage count.
			.' (SELECT COUNT(*) FROM tour_bookings b WHERE p.discount_id = b.book_promo_id AND b.book_status = "Completed") completed_uses';
		$records = $this->SqlModel->getRecords($select, $source, $sortby, $order, $where, $search, $this->per_page, $offset, FALSE);
		$recordIds = array();
		foreach ($records as $record) $recordIds[] = (int) $record[$this->pKey];
		$translationStatuses = array();
		$statusBatchSize = max(1, (int) $this->config->item('manage_translation_max_status_ids', 'manage_translations'));
		foreach (array_chunk($recordIds, $statusBatchSize) as $batch) $translationStatuses = array_replace($translationStatuses, $this->manage_translation_service->statuses('discount_codes', $batch));
		$data = array(
			'alert' => $this->session->flashdata('alert'), 'page_title' => PROJECT_TITLE.' | '.$this->moduleName,
			'userdata' => $this->user_data, 'discountCodesActive' => 1, 'total_rows' => $totalRows,
			'per_page' => $this->per_page, 'records' => $records, 'translation_statuses' => $translationStatuses,
			'paginate' => $this->pagination->create_links(), 'sortby' => $sortby,
			'order' => $order === 'ASC' ? 'DESC' : 'ASC', 'page_numb' => $offset,
			'status' => $status, 'keywords' => $keywords, 'ref_id' => $ref_id,
			'referrals' => $this->referrals(), 'useManageTranslations' => TRUE, 'useSweetAlert' => TRUE,
		);
		$this->render($this->listView, $data);
	}

	public function control($alert = '', $editID = '')
	{
		$isEdit = ($alert === 'edit');
		$record = array();
		$postedData = $this->session->flashdata($this->controller.'_data');
		$requestedLocale = is_array($postedData) && isset($postedData['active_locale']) ? $postedData['active_locale'] : $this->input->get('lang', TRUE);
		$activeLocale = $isEdit ? $this->manage_translation_service->locale($requestedLocale) : 'en';
		if ($isEdit)
		{
			$editID = (int) $editID;
			$record = $this->SqlModel->getSingleRecord($this->tblName, array($this->pKey => $editID));
			if (empty($record))
			{
				$this->session->set_flashdata('alert', 'error');
				redirect(base_url('manage/'.$this->controller));
				return;
			}
		}
		$viewRecord = $record;
		$shared = array('ref_id', 'code', 'type', 'value', 'ref_commission_type', 'ref_commission', 'expiry', 'no_of_uses');
		if (is_array($postedData)) foreach ($shared as $suffix) if (array_key_exists($this->colPrefix.$suffix, $postedData)) $viewRecord[$this->colPrefix.$suffix] = $postedData[$this->colPrefix.$suffix];
		if (is_array($postedData) && array_key_exists($this->tStatus, $postedData)) $viewRecord[$this->tStatus] = $postedData[$this->tStatus];
		$localizedValues = $this->manage_translation_service->localized_values('discount_codes', $record, $activeLocale);
		if (is_array($postedData)) foreach (array_keys($localizedValues) as $control) if (array_key_exists($control, $postedData)) $localizedValues[$control] = $postedData[$control];
		$translationState = $isEdit ? $this->manage_translation_service->state('discount_codes', $editID) : NULL;
		$discountType = isset($viewRecord[$this->colPrefix.'type']) ? $viewRecord[$this->colPrefix.'type'] : 'Fixed Amount';
		$commissionType = isset($viewRecord[$this->colPrefix.'ref_commission_type']) ? $viewRecord[$this->colPrefix.'ref_commission_type'] : 'Fixed Amount';
		$discountRange = $this->amountRange('value', $discountType);
		$commissionRange = $this->amountRange('ref_commission', $commissionType);
		$data = array(
			'discountCodesActive' => 1, 'alert' => $isEdit ? 'edit' : '', 'form_error' => $this->session->flashdata('form_error'),
			'tbl_data' => $viewRecord, 'page_title' => PROJECT_TITLE.' | '.($isEdit ? 'Edit' : 'Add').' '.$this->moduleNameSingular,
			'userdata' => $this->user_data, 'localized_values' => $localizedValues, 'active_locale' => $activeLocale,
			'manage_locales' => $this->manage_translation_service->locales(), 'translation_state' => $translationState,
			'useManageTranslations' => TRUE, 'datePicker' => 1, 'referrals' => $this->referrals(),
			'pcount' => $isEdit ? $this->bookingCount($editID) : 0,
			'discount_code' => $isEdit ? '' : $this->getDiscountCode(DISCOUNT_CODE_LENGTH),
			'discount_min' => $discountRange['min'], 'discount_max' => $discountRange['max'],
			'ref_commission_min' => $commissionRange['min'], 'ref_commission_max' => $commissionRange['max'],
		);
		$this->render($this->addEditView, $data);
	}

	public function addRecord()
	{
		$error = $this->validateDiscountCodePost('en');
		if ($error !== '') return $this->formFailure($error);
		$data = $this->postedDiscountCodeData('en');
		$data[$this->colPrefix.'name_ar'] = '';
		$data[$this->colPrefix.'added'] = $data[$this->colPrefix.'updated'] = date('Y-m-d H:i:s');
		$id = $this->SqlModel->insertRecord($this->tblName, $data);
		if (!$id) return $this->formFailure('The discount code could not be saved. Please try again.');
		$this->queueTranslationSafely($id);
		$this->session->set_flashdata('alert', $this->sendDiscountCodeEmail($id, 'added') ? 'success' : 'success_email_failed');
		redirect(base_url('manage/'.$this->controller));
	}

	public function editRecord($editID = '')
	{
		$editID = (int) $editID;
		$activeLocale = $this->manage_translation_service->locale($this->input->post('active_locale', TRUE));
		$current = $this->SqlModel->getSingleRecord($this->tblName, array($this->pKey => $editID));
		if (empty($current))
		{
			$this->session->set_flashdata('alert', 'error');
			redirect(base_url('manage/'.$this->controller));
			return;
		}
		$error = $this->validateDiscountCodePost($activeLocale, $editID, $current);
		if ($error !== '') return $this->formFailure($error, $editID, $activeLocale);
		$data = $this->postedDiscountCodeData($activeLocale);
		if ($this->bookingCount($editID) > 0) $data[$this->colPrefix.'ref_id'] = (int) $current[$this->colPrefix.'ref_id'];
		$data[$this->colPrefix.'updated'] = date('Y-m-d H:i:s');
		if ($activeLocale === 'en' && trim((string) $current[$this->colPrefix.'name']) !== $data[$this->colPrefix.'name']) $data[$this->colPrefix.'name_ar'] = '';
		if (!$this->SqlModel->updateRecord($this->tblName, $data, array($this->pKey => $editID))) return $this->formFailure('The discount code could not be updated. Please try again.', $editID, $activeLocale);
		if ($activeLocale === 'en') $this->queueTranslationSafely($editID);
		$this->session->set_flashdata('alert', $this->sendDiscountCodeEmail($editID, 'updated') ? 'editsuccess' : 'editsuccess_email_failed');
		$redirectLocale = $this->input->post('redirect_lang', TRUE);
		if (is_string($redirectLocale) && $redirectLocale !== '' && $this->manage_translation_service->locale($redirectLocale) === $redirectLocale)
		{
			redirect(base_url('manage/'.$this->controller.'/control/edit/'.$editID.'?lang='.$redirectLocale));
			return;
		}
		redirect(base_url('manage/'.$this->controller));
	}

	public function delete($deleteID = '')
	{
		$id = (int) $deleteID;
		$result = $id > 0 ? $this->deleteDiscountCodeRecords(array($id)) : 'error';
		$this->session->set_flashdata('alert', $result === 'success' ? 'deletesuccess' : ($result === 'blocked' ? 'deleteblocked' : 'deleteerror'));
		redirect(base_url('manage/'.$this->controller));
	}

	public function deleteall()
	{
		$ids = array_values(array_unique(array_filter(array_map('intval', (array) $this->input->post('records')))));
		$result = empty($ids) ? 'error' : $this->deleteDiscountCodeRecords($ids);
		$this->session->set_flashdata('alert', $result === 'success' ? 'deletesuccess' : ($result === 'blocked' ? 'deleteblocked' : 'deleteerror'));
		redirect(base_url('manage/'.$this->controller));
	}

	public function changestatus($id = 0, $status = 'Enable')
	{
		$this->output->set_content_type('application/json');
		$id = (int) $id;
		$record = $id > 0 ? $this->SqlModel->getSingleRecord($this->tblName, array($this->pKey => $id)) : array();
		if (empty($record) || !in_array($status, array('Enable', 'Disable'), TRUE) || !$this->SqlModel->updateRecord($this->tblName, array($this->tStatus => $status), array($this->pKey => $id))) return $this->output->set_output(json_encode(array('status' => 'false')));
		return $this->output->set_output(json_encode(array('status' => 'true', 'id' => $id, 'currentStatus' => $status)));
	}

	public function duplicate($id = 0)
	{
		$sourceId = (int) $id;
		$data = $sourceId > 0 ? $this->SqlModel->getSingleRecord($this->tblName, array($this->pKey => $sourceId)) : array();
		if (empty($data))
		{
			$this->session->set_flashdata('alert', 'error');
			redirect(base_url('manage/'.$this->controller));
			return;
		}
		unset($data[$this->pKey]);
		$data[$this->colPrefix.'name'] = $this->truncate(trim((string) $data[$this->colPrefix.'name']).' Duplicate', 255);
		$data[$this->colPrefix.'name_ar'] = '';
		$data[$this->colPrefix.'code'] = $this->getDiscountCode(DISCOUNT_CODE_LENGTH);
		$data[$this->tStatus] = 'Disable';
		$data[$this->colPrefix.'added'] = $data[$this->colPrefix.'updated'] = date('Y-m-d H:i:s');
		$this->db->trans_begin();
		$newId = $this->SqlModel->insertRecord($this->tblName, $data);
		if (!$newId || $this->db->trans_status() === FALSE)
		{
			$this->db->trans_rollback();
			$this->session->set_flashdata('alert', 'error');
			redirect(base_url('manage/'.$this->controller));
			return;
		}
		$this->db->trans_commit();
		$this->queueTranslationSafely($newId);
		redirect(base_url('manage/'.$this->controller.'/control/edit/'.$newId));
	}

    /**
     * Email the referral about the discount code (template 11 when it is added, 12 when it is
     * updated). Only Enabled codes are emailed, since a Disabled or Expired code cannot be shared
     * yet; skipping is not a failure. The code is already saved, so a failure is logged and shown
     * in the alert, never rolled back.
     */
    private function sendDiscountCodeEmail($discountId, $event)
    {
        try {
            $discount = $this->SqlModel->getSingleRecord($this->tblName, array($this->pKey => (int) $discountId));
            if (empty($discount) || $discount[$this->tStatus] !== 'Enable') {
                return true;
            }

            $this->load->library('Discount_email_service');

            return $event === 'added'
                ? (bool) $this->discount_email_service->sendCodeAdded($discountId)
                : (bool) $this->discount_email_service->sendCodeUpdated($discountId);
        } catch (Throwable $exception) {
            log_message(
                'error',
                'Discount code ' . $event . ' email failed for discount ID ' . (int) $discountId . ': '
                . $exception->getMessage()
            );

            return false;
        }
    }

	private function render($view, $data)
	{
		$this->load->view('admin/header', $data);
		$this->load->view('admin/navigation');
		$this->load->view('admin/'.$view);
		$this->load->view('admin/footer');
	}

	private function postedDiscountCodeData($locale)
	{
		$status = $this->input->post($this->tStatus);
		$post = $this->input->post(NULL, FALSE);
		return array_merge(array(
			$this->colPrefix.'ref_id' => (int) $this->input->post($this->colPrefix.'ref_id'),
			$this->colPrefix.'code' => strtoupper(trim((string) $this->input->post($this->colPrefix.'code'))),
			$this->colPrefix.'type' => $this->input->post($this->colPrefix.'type') === 'Percentage' ? 'Percentage' : 'Fixed Amount',
			$this->colPrefix.'value' => (int) $this->input->post($this->colPrefix.'value'),
			$this->colPrefix.'ref_commission_type' => $this->input->post($this->colPrefix.'ref_commission_type') === 'Percentage' ? 'Percentage' : 'Fixed Amount',
			$this->colPrefix.'ref_commission' => (int) $this->input->post($this->colPrefix.'ref_commission'),
			$this->colPrefix.'expiry' => $this->normalizeDate($this->input->post($this->colPrefix.'expiry')),
			$this->colPrefix.'no_of_uses' => (int) $this->input->post($this->colPrefix.'no_of_uses'),
			$this->tStatus => in_array($status, array('Enable', 'Disable', 'Expired'), TRUE) ? $status : 'Disable',
		), $this->manage_translation_service->localized_post_data('discount_codes', $locale, is_array($post) ? $post : array()));
	}

	private function validateDiscountCodePost($locale, $editID = 0, $current = array())
	{
		$post = $this->input->post(NULL, FALSE);
		if (!$this->manage_translation_service->required_localized_input_valid('discount_codes', $locale, is_array($post) ? $post : array())) return 'Enter the discount name.';
		$name = trim(isset($post['localized_name']) ? (string) $post['localized_name'] : '');
		$nameLength = function_exists('mb_strlen') ? mb_strlen($name, 'UTF-8') : strlen($name);
		if ($nameLength > 255) return 'The discount name must be 255 characters or fewer.';
		$refId = (int) $this->input->post($this->colPrefix.'ref_id');
		if ($refId < 1 || $this->SqlModel->countRecords('referrals', array('ref_id' => $refId)) !== 1) return 'Select a valid referral.';
		if ($editID > 0 && !empty($current) && $this->bookingCount($editID) > 0 && $refId !== (int) $current[$this->colPrefix.'ref_id']) return 'The referral cannot be changed after the discount code has been used.';
		$code = strtoupper(trim((string) $this->input->post($this->colPrefix.'code')));
		if (preg_match('/^[A-Z0-9]{3,40}$/', $code) !== 1) return 'Enter a discount code containing 3 to 40 letters or numbers.';
		$codeWhere = array($this->colPrefix.'code' => $code);
		if ($editID > 0) $codeWhere[$this->pKey.' !='] = $editID;
		if ($this->SqlModel->countRecords($this->tblName, $codeWhere) > 0) return 'That discount code is already in use.';
		$type = $this->input->post($this->colPrefix.'type');
		$commissionType = $this->input->post($this->colPrefix.'ref_commission_type');
		if (!in_array($type, array('Fixed Amount', 'Percentage'), TRUE) || !in_array($commissionType, array('Fixed Amount', 'Percentage'), TRUE)) return 'Select valid discount and commission types.';
		if ($this->validateAmount('value', $type, $this->input->post($this->colPrefix.'value')) === FALSE) return 'Enter a discount within the allowed range for its type.';
		if ($this->validateAmount('ref_commission', $commissionType, $this->input->post($this->colPrefix.'ref_commission')) === FALSE) return 'Enter a referral commission within the allowed range for its type.';
		$expiry = $this->normalizeDate($this->input->post($this->colPrefix.'expiry'));
		if ($expiry === '') return 'Enter a valid expiry date.';
		$status = $this->input->post($this->tStatus);
		if (!in_array($status, array('Enable', 'Disable', 'Expired'), TRUE)) return 'Select a valid status.';
		if ($status !== 'Expired' && $expiry < date('Y-m-d')) return 'The expiry date cannot be in the past for an enabled or disabled code.';
		$uses = trim((string) $this->input->post($this->colPrefix.'no_of_uses'));
		if ($uses !== '' && (!ctype_digit($uses) || (float) $uses > 2147483647)) return 'Maximum uses must be zero, blank, or a whole number.';
		return '';
	}

	private function amountRange($field = 'value', $type = '')
	{
		$suffix = $type === 'Percentage' ? 'PERCENTAGE' : 'FIXED';
		$prefix = $field === 'ref_commission' ? 'DISCOUNT_REF_COMMISSION' : 'DISCOUNT_VALUE';
		return array('min' => constant($prefix.'_MIN_'.$suffix), 'max' => constant($prefix.'_MAX_'.$suffix));
	}

	private function validateAmount($field, $type, $amount)
	{
		$range = $this->amountRange($field, $type);
		$amount = trim((string) $amount);
		if ($amount === '' || !ctype_digit($amount)) return FALSE;
		$amount = (int) $amount;
		return $amount >= $range['min'] && $amount <= $range['max'] ? $amount : FALSE;
	}

	private function normalizeDate($value)
	{
		$value = trim((string) $value);
		if (preg_match('/^(\d{4})-(\d{2})-(\d{2})$/', $value, $parts) === 1 && checkdate((int) $parts[2], (int) $parts[3], (int) $parts[1])) return $value;
		if (preg_match('/^(\d{2})\/(\d{2})\/(\d{4})$/', $value, $parts) === 1 && checkdate((int) $parts[1], (int) $parts[2], (int) $parts[3])) return $parts[3].'-'.$parts[1].'-'.$parts[2];
		return '';
	}

	private function formFailure($message, $editID = 0, $locale = 'en')
	{
		$posted = $this->input->post(NULL, FALSE);
		if (is_array($posted)) $posted['active_locale'] = $this->manage_translation_service->locale($locale);
		$this->session->set_flashdata($this->controller.'_data', is_array($posted) ? $posted : array());
		$this->session->set_flashdata('form_error', $message);
		redirect(base_url('manage/'.$this->controller.'/control'.($editID ? '/edit/'.(int) $editID.'?lang='.$this->manage_translation_service->locale($locale) : '')));
	}

	private function deleteDiscountCodeRecords(array $ids)
	{
		$ids = array_values(array_unique(array_filter(array_map('intval', $ids))));
		if (empty($ids)) return 'error';
		$existing = $this->db->select($this->pKey)->where_in($this->pKey, $ids)->get($this->tblName)->result_array();
		if (count($existing) !== count($ids)) return 'error';
		if ($this->db->where_in('book_promo_id', $ids)->count_all_results('tour_bookings') > 0) return 'blocked';
		$this->db->trans_begin();
		foreach ($ids as $id) $this->manage_translation_service->delete_jobs('discount_codes', $id);
		$this->db->where_in($this->pKey, $ids)->delete($this->tblName);
		$deleted = $this->db->affected_rows();
		if ($this->db->trans_status() === FALSE || $deleted !== count($ids))
		{
			$this->db->trans_rollback();
			return 'error';
		}
		$this->db->trans_commit();
		return 'success';
	}

	private function referrals()
	{
		return $this->SqlModel->getRecords('ref_id,ref_name,ref_organization', 'referrals', 'ref_name', 'ASC');
	}

	private function bookingCount($discountId)
	{
		return $this->db->where('book_promo_id', (int) $discountId)->count_all_results('tour_bookings');
	}

	private function getDiscountCode($length = 10)
	{
		$length = max(4, min(40, (int) $length));
		$characters = '0123456789ABCDEFGHIJKLMNOPQRSTUVWXYZ';
		for ($attempt = 0; $attempt < 100; $attempt++)
		{
			$code = '';
			for ($i = 0; $i < $length; $i++) $code .= $characters[random_int(0, strlen($characters) - 1)];
			if ($this->SqlModel->countRecords($this->tblName, array($this->colPrefix.'code' => $code)) === 0) return $code;
		}
		return '';
	}

	private function queueTranslationSafely($discountId)
	{
		try { $this->manage_translation_service->queue('discount_codes', (int) $discountId); }
		catch (Throwable $exception) { log_message('error', 'Discount code translation could not be queued for record '.(int) $discountId.'.'); }
	}

	private function truncate($value, $maxLength)
	{
		if (function_exists('mb_substr')) return mb_substr((string) $value, 0, (int) $maxLength, 'UTF-8');
		return substr((string) $value, 0, (int) $maxLength);
	}

}
