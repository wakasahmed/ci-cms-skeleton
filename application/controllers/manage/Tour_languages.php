<?php defined('BASEPATH') OR exit('No direct script access allowed');

class Tour_languages extends CI_Controller {

	public $tblName = 'tour_languages';
	public $colPrefix = 'lang_';
	public $pKey = 'lang_id';
	public $moduleName = 'Tour Languages';
	public $moduleNameSingular = 'Tour Language';
	public $moduleDesc = 'Manage the languages available for tours and guides.';
	public $controller = 'tour-languages';
	public $per_page = 10;
	public $tStatus = 'lang_status';
	public $listView = 'tourLanguages';
	public $addEditView = 'addTourLanguage';
	public $user_data = array();

	private $protectedLanguageId = 1;

	public function __construct()
	{
		parent::__construct();
		$this->user_data = $this->SqlModel->authAdmin($this->session->userdata('admin_auth'), $this->session->userdata('admin_id'));
		if (empty($this->user_data)) redirect(base_url('manage/login'));
		$this->load->library('manage_translation_service');
	}

	public function index($sortby = 'lang_order', $order = 'ASC', $status = '-', $keywords = '-', $pg_no = '')
	{
		$allowedSorts = array($this->pKey, $this->colPrefix.'order', $this->colPrefix.'name', $this->colPrefix.'name_ar', $this->tStatus, $this->colPrefix.'added', $this->colPrefix.'updated');
		$sortby = in_array($sortby, $allowedSorts, TRUE) ? $sortby : $this->colPrefix.'order';
		$order = strtoupper($order) === 'DESC' ? 'DESC' : 'ASC';
		$status = in_array($status, array('Enable', 'Disable'), TRUE) ? $status : '-';
		$requestedPerPage = $this->input->get('per_page');
		$allowedPerPage = array_merge(array(0), range(10, 100, 10));
		if ($requestedPerPage !== NULL && ctype_digit((string) $requestedPerPage) && in_array((int) $requestedPerPage, $allowedPerPage, TRUE)) $this->session->set_userdata('per_page', (int) $requestedPerPage);
		if ($this->session->userdata('per_page') !== NULL && in_array((int) $this->session->userdata('per_page'), $allowedPerPage, TRUE)) $this->per_page = (int) $this->session->userdata('per_page');

		$keywords = urldecode((string) $keywords);
		$where = $status === '-' ? array() : array($this->tStatus => $status);
		$search = $keywords !== '-' ? array('cols' => $this->colPrefix.'name,'.$this->colPrefix.'name_ar', 'value' => $keywords) : array();
		$baseUrl = base_url('manage/'.$this->controller.'/index/'.$sortby.'/'.$order.'/'.$status.'/'.rawurlencode($keywords));
		$totalRows = $this->SqlModel->countRecords($this->tblName, $where, $search);
		$uriSegment = 8;
		$offset = max(0, (int) $this->uri->segment($uriSegment, 0));
		$this->pagination->initialize(admin_pagination_config($baseUrl, $totalRows, $this->per_page, $uriSegment));
		$recordSort = $sortby === $this->colPrefix.'order' ? $sortby.','.$this->pKey : $sortby;
		$records = $this->SqlModel->getRecords('*', $this->tblName, $recordSort, $order, $where, $search, $this->per_page, $offset, FALSE);
		$recordIds = array();
		foreach ($records as $record) $recordIds[] = (int) $record[$this->pKey];
		$translationStatuses = empty($recordIds) ? array() : $this->manage_translation_service->statuses('tour_languages', $recordIds);
		$data = array(
			'alert' => $this->session->flashdata('alert'), 'page_title' => PROJECT_TITLE.' | '.$this->moduleName,
			'userdata' => $this->user_data, 'tourLanguagesActive' => 1,
			'total_rows' => $totalRows, 'per_page' => $this->per_page, 'records' => $records,
			'translation_statuses' => $translationStatuses, 'paginate' => $this->pagination->create_links(),
			'sortby' => $sortby, 'order' => $order === 'ASC' ? 'DESC' : 'ASC', 'page_numb' => $offset,
			'status' => $status, 'keywords' => $keywords, 'useManageTranslations' => TRUE,
			'protected_language_id' => $this->protectedLanguageId,
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
		if (is_array($postedData) && array_key_exists($this->tStatus, $postedData)) $viewRecord[$this->tStatus] = $postedData[$this->tStatus];
		$localizedValues = $this->manage_translation_service->localized_values('tour_languages', $record, $activeLocale);
		if (is_array($postedData) && array_key_exists('localized_name', $postedData)) $localizedValues['localized_name'] = $postedData['localized_name'];
		$translationState = $isEdit ? $this->manage_translation_service->state('tour_languages', $editID) : NULL;
		$data = array(
			'tourLanguagesActive' => 1, 'alert' => $isEdit ? 'edit' : '', 'form_error' => $this->session->flashdata('form_error'),
			'tbl_data' => $viewRecord, 'page_title' => PROJECT_TITLE.' | '.($isEdit ? 'Edit' : 'Add').' '.$this->moduleNameSingular,
			'userdata' => $this->user_data, 'localized_values' => $localizedValues, 'active_locale' => $activeLocale,
			'manage_locales' => $this->manage_translation_service->locales(), 'translation_state' => $translationState,
			'useManageTranslations' => TRUE,
		);
		$this->render($this->addEditView, $data);
	}

	public function addRecord()
	{
		if (!$this->validLocalizedInput('en')) return $this->formFailure('Enter a language name in English.');
		$data = $this->postedLanguageData('en');
		$data[$this->colPrefix.'name_ar'] = '';
		$this->load->library('admin_record_sorter');
		$data[$this->colPrefix.'order'] = $this->admin_record_sorter->nextOrder($this->controller);
		$data[$this->colPrefix.'added'] = date('Y-m-d H:i:s');
		$data[$this->colPrefix.'updated'] = date('Y-m-d H:i:s');
		$id = $this->SqlModel->insertRecord($this->tblName, $data);
		if (!$id) return $this->formFailure('The tour language could not be saved. Please try again.');
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
		if (!$this->validLocalizedInput($activeLocale)) return $this->formFailure('Enter the required language name.', $editID, $activeLocale);
		$data = $this->postedLanguageData($activeLocale);
		$data[$this->colPrefix.'updated'] = date('Y-m-d H:i:s');
		if ($activeLocale === 'en' && trim((string) $current[$this->colPrefix.'name']) !== $data[$this->colPrefix.'name']) $data[$this->colPrefix.'name_ar'] = '';
		if (!$this->SqlModel->updateRecord($this->tblName, $data, array($this->pKey => $editID))) return $this->formFailure('The tour language could not be updated. Please try again.', $editID, $activeLocale);
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
		$result = empty($record) ? 'error' : $this->deleteLanguageRecords(array($id));
		$this->session->set_flashdata('alert', $result === 'success' ? 'deletesuccess' : ($result === 'blocked' ? 'deleteblocked' : 'deleteerror'));
		redirect(base_url('manage/'.$this->controller));
	}

	public function deleteall()
	{
		$ids = array_values(array_unique(array_filter(array_map('intval', (array) $this->input->post('records')))));
		$result = empty($ids) ? 'error' : $this->deleteLanguageRecords($ids);
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
		$data[$this->colPrefix.'name'] = trim((string) $data[$this->colPrefix.'name']).' Duplicate';
		$data[$this->colPrefix.'name_ar'] = '';
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

	private function postedLanguageData($locale)
	{
		$status = $this->input->post($this->tStatus);
		$post = $this->input->post(NULL, FALSE);
		$data = array($this->tStatus => in_array($status, array('Enable', 'Disable'), TRUE) ? $status : 'Disable');
		return array_merge($data, $this->manage_translation_service->localized_post_data('tour_languages', $locale, is_array($post) ? $post : array()));
	}

	private function validLocalizedInput($locale)
	{
		$post = $this->input->post(NULL, FALSE);
		return $this->manage_translation_service->required_localized_input_valid('tour_languages', $locale, is_array($post) ? $post : array());
	}

	private function formFailure($message, $editID = 0, $locale = 'en')
	{
		$posted = $this->input->post(NULL, FALSE);
		if (is_array($posted)) $posted['active_locale'] = $this->manage_translation_service->locale($locale);
		$this->session->set_flashdata($this->controller.'_data', is_array($posted) ? $posted : array());
		$this->session->set_flashdata('form_error', $message);
		redirect(base_url('manage/'.$this->controller.'/control'.($editID ? '/edit/'.(int) $editID.'?lang='.$this->manage_translation_service->locale($locale) : '')));
	}

	private function deleteLanguageRecords(array $ids)
	{
		$ids = array_values(array_unique(array_filter(array_map('intval', $ids))));
		if (empty($ids)) return 'error';
		if (in_array($this->protectedLanguageId, $ids, TRUE)) return 'error';
		$existing = $this->db->select($this->pKey)->where_in($this->pKey, $ids)->get($this->tblName)->result_array();
		if (count($existing) !== count($ids)) return 'error';
		if ($this->db->where_in('book_lang_id', $ids)->count_all_results('tour_bookings') > 0) return 'blocked';
		$this->db->trans_begin();
		$this->db->where_in('lang_id', $ids)->delete('tour_guide_assigned_languages');
		foreach ($ids as $id) $this->manage_translation_service->delete_jobs('tour_languages', $id);
		$this->db->where_in($this->pKey, $ids)->delete($this->tblName);
		if ($this->db->trans_status() === FALSE || $this->db->affected_rows() !== count($ids))
		{
			$this->db->trans_rollback();
			return 'error';
		}
		$this->db->trans_commit();
		return 'success';
	}

	private function queueTranslationSafely($languageId)
	{
		try { $this->manage_translation_service->queue('tour_languages', (int) $languageId); }
		catch (Throwable $exception) { log_message('error', 'Tour language translation could not be queued for record '.(int) $languageId.'.'); }
	}
}
