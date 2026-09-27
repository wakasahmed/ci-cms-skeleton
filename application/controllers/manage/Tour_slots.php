<?php defined('BASEPATH') OR exit('No direct script access allowed');

class Tour_slots extends CI_Controller {

	public $tblName = 'tour_slots';
	public $colPrefix = 'slot_';
	public $pKey = 'slot_id';
	public $moduleName = 'Tour Slots';
	public $moduleNameSingular = 'Tour Slot';
	public $moduleDesc = 'Manage localized tour time slots, schedules, durations, icons, and availability status.';
	public $controller = 'tour-slots';
	public $per_page = 10;
	public $tStatus = 'slot_status';
	public $listView = 'tourSlots';
	public $addEditView = 'addTourSlot';
	public $user_data = array();

	public function __construct()
	{
		parent::__construct();
		$this->user_data = $this->SqlModel->authAdmin($this->session->userdata('admin_auth'), $this->session->userdata('admin_id'));
		if (empty($this->user_data)) redirect(base_url('manage/login'));
		$this->load->library('manage_translation_service');
	}

	public function index($sortby = 'slot_order', $order = 'ASC', $status = '-', $keywords = '-', $pg_no = '')
	{
		$allowedSorts = array(
			$this->pKey, $this->colPrefix.'order', $this->colPrefix.'title', $this->colPrefix.'name',
			$this->colPrefix.'name_ar', $this->colPrefix.'start_time', $this->colPrefix.'end_time',
			$this->colPrefix.'hours', $this->tStatus, $this->colPrefix.'added', $this->colPrefix.'updated',
		);
		$sortby = in_array($sortby, $allowedSorts, TRUE) ? $sortby : $this->colPrefix.'order';
		$order = strtoupper($order) === 'DESC' ? 'DESC' : 'ASC';
		$status = in_array($status, array('Enable', 'Disable'), TRUE) ? $status : '-';
		$allowedPerPage = array_merge(array(0), range(10, 100, 10));
		$requestedPerPage = $this->input->get('per_page');
		if ($requestedPerPage !== NULL && ctype_digit((string) $requestedPerPage) && in_array((int) $requestedPerPage, $allowedPerPage, TRUE)) $this->session->set_userdata('per_page', (int) $requestedPerPage);
		if ($this->session->userdata('per_page') !== NULL && in_array((int) $this->session->userdata('per_page'), $allowedPerPage, TRUE)) $this->per_page = (int) $this->session->userdata('per_page');

		$keywords = urldecode((string) $keywords);
		$where = $status === '-' ? array() : array($this->tStatus => $status);
		$search = $keywords !== '-' ? array('cols' => $this->colPrefix.'title,'.$this->colPrefix.'name,'.$this->colPrefix.'name_ar', 'value' => $keywords) : array();
		$baseUrl = base_url('manage/'.$this->controller.'/index/'.$sortby.'/'.$order.'/'.$status.'/'.rawurlencode($keywords));
		$totalRows = $this->SqlModel->countRecords($this->tblName, $where, $search);
		$uriSegment = 8;
		$offset = max(0, (int) $this->uri->segment($uriSegment, 0));
		$this->pagination->initialize(admin_pagination_config($baseUrl, $totalRows, $this->per_page, $uriSegment));
		$recordSort = $sortby === $this->colPrefix.'order' ? $sortby.','.$this->colPrefix.'name' : $sortby;
		$records = $this->SqlModel->getRecords('*', $this->tblName, $recordSort, $order, $where, $search, $this->per_page, $offset, FALSE);
		$recordIds = array();
		foreach ($records as $record) $recordIds[] = (int) $record[$this->pKey];
		$translationStatuses = empty($recordIds) ? array() : $this->manage_translation_service->statuses('tour_slots', $recordIds);
		$data = array(
			'alert' => $this->session->flashdata('alert'), 'page_title' => PROJECT_TITLE.' | '.$this->moduleName,
			'userdata' => $this->user_data, 'tourGuideSlotsActive' => 1, 'total_rows' => $totalRows,
			'per_page' => $this->per_page, 'records' => $records, 'translation_statuses' => $translationStatuses,
			'paginate' => $this->pagination->create_links(), 'sortby' => $sortby,
			'order' => $order === 'ASC' ? 'DESC' : 'ASC', 'page_numb' => $offset,
			'status' => $status, 'keywords' => $keywords, 'useManageTranslations' => TRUE,
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
		$sharedColumns = array($this->colPrefix.'title', $this->colPrefix.'icon', $this->colPrefix.'start_time', $this->colPrefix.'end_time', $this->tStatus);
		if (is_array($postedData)) foreach ($sharedColumns as $column) if (array_key_exists($column, $postedData)) $viewRecord[$column] = $postedData[$column];
		$localizedValues = $this->manage_translation_service->localized_values('tour_slots', $record, $activeLocale);
		if (is_array($postedData)) foreach (array_keys($localizedValues) as $control) if (array_key_exists($control, $postedData)) $localizedValues[$control] = $postedData[$control];
		$translationState = $isEdit ? $this->manage_translation_service->state('tour_slots', $editID) : NULL;
		$data = array(
			'tourGuideSlotsActive' => 1, 'alert' => $isEdit ? 'edit' : '', 'form_error' => $this->session->flashdata('form_error'),
			'tbl_data' => $viewRecord, 'page_title' => PROJECT_TITLE.' | '.($isEdit ? 'Edit' : 'Add').' '.$this->moduleNameSingular,
			'userdata' => $this->user_data, 'localized_values' => $localizedValues, 'active_locale' => $activeLocale,
			'manage_locales' => $this->manage_translation_service->locales(), 'translation_state' => $translationState,
			'useManageTranslations' => TRUE, 'useIconPicker' => TRUE,
		);
		$this->render($this->addEditView, $data);
	}

	public function addRecord()
	{
		$error = $this->validationError('en');
		if ($error !== '') return $this->formFailure($error);
		$data = $this->postedSlotData('en');
		$data[$this->colPrefix.'name_ar'] = '';
		$data[$this->colPrefix.'details_ar'] = '';
		$this->load->library('admin_record_sorter');
		$data[$this->colPrefix.'order'] = $this->admin_record_sorter->nextOrder($this->controller);
		$data[$this->colPrefix.'added'] = date('Y-m-d H:i:s');
		$data[$this->colPrefix.'updated'] = date('Y-m-d H:i:s');
		$id = $this->SqlModel->insertRecord($this->tblName, $data);
		if (!$id) return $this->formFailure('The tour slot could not be saved. Please try again.');
		$this->queueTranslationSafely($id);
		$this->session->set_flashdata('alert', 'success');
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
		$error = $this->validationError($activeLocale);
		if ($error !== '') return $this->formFailure($error, $editID, $activeLocale);
		$data = $this->postedSlotData($activeLocale);
		$data[$this->colPrefix.'updated'] = date('Y-m-d H:i:s');
		if ($activeLocale === 'en')
		{
			if (trim((string) $current[$this->colPrefix.'name']) !== $data[$this->colPrefix.'name']) $data[$this->colPrefix.'name_ar'] = '';
			if (trim((string) $current[$this->colPrefix.'details']) !== $data[$this->colPrefix.'details']) $data[$this->colPrefix.'details_ar'] = '';
		}
		if (!$this->SqlModel->updateRecord($this->tblName, $data, array($this->pKey => $editID))) return $this->formFailure('The tour slot could not be updated. Please try again.', $editID, $activeLocale);
		if ($activeLocale === 'en') $this->queueTranslationSafely($editID);
		$this->session->set_flashdata('alert', 'editsuccess');
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
		$record = $id > 0 ? $this->SqlModel->getSingleRecord($this->tblName, array($this->pKey => $id)) : array();
		$result = empty($record) ? 'error' : $this->deleteSlotRecords(array($id));
		$this->session->set_flashdata('alert', $result === 'success' ? 'deletesuccess' : ($result === 'blocked' ? 'deleteblocked' : 'deleteerror'));
		redirect(base_url('manage/'.$this->controller));
	}

	public function deleteall()
	{
		$ids = array_values(array_unique(array_filter(array_map('intval', (array) $this->input->post('records')))));
		$result = empty($ids) ? 'error' : $this->deleteSlotRecords($ids);
		$this->session->set_flashdata('alert', $result === 'success' ? 'deletesuccess' : ($result === 'blocked' ? 'deleteblocked' : 'deleteerror'));
		redirect(base_url('manage/'.$this->controller));
	}

	public function changestatus($id = 0, $status = 'Enable')
	{
		$this->output->set_content_type('application/json');
		$id = (int) $id;
		$record = $id > 0 ? $this->SqlModel->getSingleRecord($this->tblName, array($this->pKey => $id)) : array();
		if (empty($record) || !in_array($status, array('Enable', 'Disable'), TRUE) || !$this->SqlModel->updateRecord($this->tblName, array($this->tStatus => $status), array($this->pKey => $id)))
		{
			return $this->output->set_output(json_encode(array('status' => 'false')));
		}
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
		$data[$this->colPrefix.'title'] = trim((string) $data[$this->colPrefix.'title']).' Duplicate';
		$data[$this->colPrefix.'name'] = trim((string) $data[$this->colPrefix.'name']).' Duplicate';
		$data[$this->colPrefix.'name_ar'] = '';
		$data[$this->colPrefix.'details_ar'] = '';
		$this->load->library('admin_record_sorter');
		$data[$this->colPrefix.'order'] = $this->admin_record_sorter->nextOrder($this->controller);
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

	private function render($view, $data)
	{
		$this->load->view('admin/header', $data);
		$this->load->view('admin/navigation');
		$this->load->view('admin/'.$view);
		$this->load->view('admin/footer');
	}

	private function postedSlotData($locale)
	{
		$start = $this->normalizeTime($this->input->post($this->colPrefix.'start_time'));
		$end = $this->normalizeTime($this->input->post($this->colPrefix.'end_time'));
		$status = $this->input->post($this->tStatus);
		$data = array(
			$this->colPrefix.'title' => $this->truncate(trim((string) $this->input->post($this->colPrefix.'title')), 255),
			$this->colPrefix.'icon' => trim((string) $this->input->post($this->colPrefix.'icon')),
			$this->colPrefix.'start_time' => $start,
			$this->colPrefix.'end_time' => $end,
			$this->colPrefix.'hours' => (int) ceil(($this->timeToSeconds($end) - $this->timeToSeconds($start)) / 3600),
			$this->tStatus => in_array($status, array('Enable', 'Disable'), TRUE) ? $status : 'Disable',
		);
		$post = $this->input->post(NULL, FALSE);
		return array_merge($data, $this->manage_translation_service->localized_post_data('tour_slots', $locale, is_array($post) ? $post : array()));
	}

	private function validationError($locale)
	{
		$genericError = 'Enter the required fields, choose a valid icon, and provide an end time that is not earlier than the start time.';
		$post = $this->input->post(NULL, FALSE);
		if (!$this->manage_translation_service->required_localized_input_valid('tour_slots', $locale, is_array($post) ? $post : array())) return $genericError;
		if (trim((string) $this->input->post($this->colPrefix.'title')) === '') return $genericError;
		$icon = trim((string) $this->input->post($this->colPrefix.'icon'));
		if ($icon !== '' && preg_match('/^fa-(?:solid|regular|brands) fa-[a-z0-9-]+$/', $icon) !== 1) return $genericError;
		$start = $this->normalizeTime($this->input->post($this->colPrefix.'start_time'));
		$end = $this->normalizeTime($this->input->post($this->colPrefix.'end_time'));
		if ($start === '' || $end === '' || $this->timeToSeconds($end) < $this->timeToSeconds($start)) return $genericError;
		if (!$this->validDuration($start, $end)) return 'Tour slots can only be 2, 4, 6, or 8 hours long. Please adjust the start or end time so the duration matches one of these lengths.';
		return '';
	}

	private function validDuration($start, $end)
	{
		$diffSeconds = $this->timeToSeconds($end) - $this->timeToSeconds($start);
		if ($diffSeconds <= 0 || $diffSeconds % 3600 !== 0) return FALSE;
		return in_array($diffSeconds / 3600, $this->allowedDurationHours(), TRUE);
	}

	private function allowedDurationHours()
	{
		return array(2, 4, 6, 8);
	}

	private function normalizeTime($value)
	{
		$value = trim((string) $value);
		if ($value === '' || preg_match('/^([0-9]{1,2}):([0-9]{2})(?::([0-9]{2}))?$/', $value, $matches) !== 1) return '';
		if ((int) $matches[1] > 23 || (int) $matches[2] > 59 || (isset($matches[3]) && (int) $matches[3] > 59)) return '';
		return sprintf('%02d:%02d:%02d', (int) $matches[1], (int) $matches[2], isset($matches[3]) ? (int) $matches[3] : 0);
	}

	private function timeToSeconds($time)
	{
		$parts = explode(':', (string) $time);
		return ((int) $parts[0] * 3600) + ((int) $parts[1] * 60) + (int) $parts[2];
	}

	private function formFailure($message, $editID = 0, $locale = 'en')
	{
		$posted = $this->input->post(NULL, FALSE);
		if (is_array($posted)) $posted['active_locale'] = $this->manage_translation_service->locale($locale);
		$this->session->set_flashdata($this->controller.'_data', is_array($posted) ? $posted : array());
		$this->session->set_flashdata('form_error', $message);
		redirect(base_url('manage/'.$this->controller.'/control'.($editID ? '/edit/'.(int) $editID.'?lang='.$this->manage_translation_service->locale($locale) : '')));
	}

	private function deleteSlotRecords(array $ids)
	{
		$ids = array_values(array_unique(array_filter(array_map('intval', $ids))));
		if (empty($ids)) return 'error';
		$existing = $this->db->select($this->pKey)->where_in($this->pKey, $ids)->get($this->tblName)->result_array();
		if (count($existing) !== count($ids)) return 'error';
		if ($this->db->where_in('book_slot_id', $ids)->count_all_results('tour_bookings') > 0) return 'blocked';
		$this->db->where_in('avail_slot_id', $ids)->group_start()->where('avail_book_status', 'Reserved')->or_where('avail_book_id >', 0)->group_end();
		if ($this->db->count_all_results('tour_guide_availability') > 0) return 'blocked';

		$this->db->trans_begin();
		$this->db->where_in('avail_slot_id', $ids)->delete('tour_guide_availability');
		foreach ($ids as $id) $this->manage_translation_service->delete_jobs('tour_slots', $id);
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

	private function queueTranslationSafely($slotId)
	{
		try { $this->manage_translation_service->queue('tour_slots', (int) $slotId); }
		catch (Throwable $exception) { log_message('error', 'Tour slot translation could not be queued for record '.(int) $slotId.'.'); }
	}

	private function truncate($value, $maxLength)
	{
		if (function_exists('mb_substr')) return mb_substr((string) $value, 0, (int) $maxLength, 'UTF-8');
		return substr((string) $value, 0, (int) $maxLength);
	}
}
