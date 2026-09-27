<?php defined('BASEPATH') OR exit('No direct script access allowed');

class Countries extends CI_Controller {

	public $tblName = 'countries';
	public $colPrefix = '';
	public $pKey = 'id';
	public $moduleName = 'Countries';
	public $moduleNameSingular = 'Country';
	public $moduleDesc = 'Manage the countries offered on contact and booking forms.';
	public $controller = 'countries';
	public $per_page = 10;
	public $listView = 'countries';
	public $addEditView = 'addCountry';
	public $user_data = array();

	public function __construct()
	{
		parent::__construct();
		$this->user_data = $this->SqlModel->authAdmin($this->session->userdata('admin_auth'), $this->session->userdata('admin_id'));
		if (empty($this->user_data)) redirect(base_url('manage/login'));
		$this->load->library('manage_translation_service');
	}

	public function index($sortby = 'name', $order = 'ASC', $keywords = '-', $pg_no = '')
	{
		$allowedSorts = array($this->pKey, 'iso', 'name', 'name_ar', 'iso3', 'numcode', 'phonecode', 'added', 'updated');
		$sortby = in_array($sortby, $allowedSorts, TRUE) ? $sortby : 'name';
		$order = strtoupper($order) === 'DESC' ? 'DESC' : 'ASC';

		$requestedPerPage = $this->input->get('per_page');
		$allowedPerPage = array_merge(array(0), range(10, 100, 10));
		if ($requestedPerPage !== NULL && ctype_digit((string) $requestedPerPage) && in_array((int) $requestedPerPage, $allowedPerPage, TRUE)) $this->session->set_userdata('per_page', (int) $requestedPerPage);
		if ($this->session->userdata('per_page') !== NULL && in_array((int) $this->session->userdata('per_page'), $allowedPerPage, TRUE)) $this->per_page = (int) $this->session->userdata('per_page');

		$keywords = urldecode((string) $keywords);
		$search = $keywords !== '-' ? array('cols' => 'name,name_ar', 'value' => $keywords) : array();
		$baseUrl = base_url('manage/'.$this->controller.'/index/'.$sortby.'/'.$order.'/'.rawurlencode($keywords));
		$totalRows = $this->SqlModel->countRecords($this->tblName, array(), $search);
		$uriSegment = 7;
		$offset = max(0, (int) $this->uri->segment($uriSegment, 0));

		$this->pagination->initialize(admin_pagination_config($baseUrl, $totalRows, $this->per_page, $uriSegment));
		$recordSort = $sortby === 'name' ? 'name' : $sortby.',name';
		$records = $this->SqlModel->getRecords('*', $this->tblName, $recordSort, $order, array(), $search, $this->per_page, $offset, FALSE);
		$recordIds = array();
		foreach ($records as $record) $recordIds[] = (int) $record[$this->pKey];
		$translationStatuses = empty($recordIds) ? array() : $this->manage_translation_service->statuses('countries', $recordIds);
		$data = array(
			'alert' => $this->session->flashdata('alert'), 'page_title' => PROJECT_TITLE.' | '.$this->moduleName,
			'userdata' => $this->user_data, 'countriesActive' => 1,
			'total_rows' => $totalRows, 'per_page' => $this->per_page, 'records' => $records,
			'translation_statuses' => $translationStatuses, 'paginate' => $this->pagination->create_links(),
			'sortby' => $sortby, 'order' => $order === 'ASC' ? 'DESC' : 'ASC', 'page_numb' => $offset,
			'keywords' => $keywords, 'useManageTranslations' => TRUE,
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
		if (is_array($postedData))
		{
			foreach (array('iso', 'iso3', 'numcode', 'phonecode') as $sharedColumn)
			{
				if (array_key_exists($sharedColumn, $postedData)) $viewRecord[$sharedColumn] = $postedData[$sharedColumn];
			}
		}
		$localizedValues = $this->manage_translation_service->localized_values('countries', $record, $activeLocale);
		if (is_array($postedData) && array_key_exists('localized_name', $postedData)) $localizedValues['localized_name'] = $postedData['localized_name'];
		$translationState = $isEdit ? $this->manage_translation_service->state('countries', $editID) : NULL;
		$data = array(
			'countriesActive' => 1, 'alert' => $isEdit ? 'edit' : '', 'form_error' => $this->session->flashdata('form_error'),
			'tbl_data' => $viewRecord, 'page_title' => PROJECT_TITLE.' | '.($isEdit ? 'Edit' : 'Add').' '.$this->moduleNameSingular,
			'userdata' => $this->user_data, 'localized_values' => $localizedValues, 'active_locale' => $activeLocale,
			'manage_locales' => $this->manage_translation_service->locales(), 'translation_state' => $translationState,
			'useManageTranslations' => TRUE,
		);
		$this->render($this->addEditView, $data);
	}

	public function addRecord()
	{
		$activeLocale = 'en';
		if (!$this->validRequiredInput($activeLocale)) return $this->formFailure('Enter a valid country name, ISO code, and phone code.');

		$data = $this->postedCountryData($activeLocale);
		$data['name_ar'] = '';
		$data['added'] = date('Y-m-d H:i:s');
		$data['updated'] = date('Y-m-d H:i:s');

		$id = $this->SqlModel->insertRecord($this->tblName, $data);
		if (!$id) return $this->formFailure('The country could not be saved. Please try again.');

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
		if (!$this->validRequiredInput($activeLocale)) return $this->formFailure('Enter a valid country name, ISO code, and phone code.', $editID, $activeLocale);

		$data = $this->postedCountryData($activeLocale);
		$data['updated'] = date('Y-m-d H:i:s');
		if ($activeLocale === 'en' && trim((string) $current['name']) !== $data['name']) $data['name_ar'] = '';

		if (!$this->SqlModel->updateRecord($this->tblName, $data, array($this->pKey => $editID))) return $this->formFailure('The country could not be updated. Please try again.', $editID, $activeLocale);

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
		$result = empty($record) ? 'error' : $this->deleteCountryRecords(array($id));
		$this->session->set_flashdata('alert', $result === 'success' ? 'deletesuccess' : ($result === 'blocked' ? 'deleteblocked' : 'deleteerror'));
		redirect(base_url('manage/'.$this->controller));
	}

	public function deleteall()
	{
		$ids = array_values(array_unique(array_filter(array_map('intval', (array) $this->input->post('records')))));
		$result = empty($ids) ? 'error' : $this->deleteCountryRecords($ids);
		$this->session->set_flashdata('alert', $result === 'success' ? 'deletesuccess' : ($result === 'blocked' ? 'deleteblocked' : 'deleteerror'));
		redirect(base_url('manage/'.$this->controller));
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
		$data['name'] = trim((string) $data['name']).' Duplicate';
		$data['name_ar'] = '';
		$data['added'] = $data['updated'] = date('Y-m-d H:i:s');

		$newId = $this->SqlModel->insertRecord($this->tblName, $data);
		if (!$newId)
		{
			$this->session->set_flashdata('alert', 'error');
			redirect(base_url('manage/'.$this->controller));
			return;
		}
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

	private function postedCountryData($locale)
	{
		$data = array(
			'iso' => strtoupper(trim((string) $this->input->post('iso'))),
			'iso3' => strtoupper(trim((string) $this->input->post('iso3'))),
			'numcode' => $this->blankToNull($this->input->post('numcode')),
			'phonecode' => $this->blankToNull($this->input->post('phonecode')),
		);
		$post = $this->input->post(NULL, FALSE);
		return array_merge($data, $this->manage_translation_service->localized_post_data('countries', $locale, is_array($post) ? $post : array()));
	}

	private function blankToNull($value)
	{
		$value = trim((string) $value);
		return $value === '' ? NULL : (int) $value;
	}

	private function validRequiredInput($locale)
	{
		$post = $this->input->post(NULL, FALSE);
		if (!$this->manage_translation_service->required_localized_input_valid('countries', $locale, is_array($post) ? $post : array())) return FALSE;

		$iso = strtoupper(trim((string) $this->input->post('iso')));
		$iso3 = strtoupper(trim((string) $this->input->post('iso3')));
		$numcode = trim((string) $this->input->post('numcode'));
		$phonecode = trim((string) $this->input->post('phonecode'));

		if (preg_match('/^[A-Z]{2}$/', $iso) !== 1) return FALSE;
		if ($iso3 !== '' && preg_match('/^[A-Z]{3}$/', $iso3) !== 1) return FALSE;
		if ($numcode !== '' && !ctype_digit($numcode)) return FALSE;
		if ($phonecode === '' || !ctype_digit($phonecode)) return FALSE;

		return TRUE;
	}

	private function formFailure($message, $editID = 0, $locale = 'en')
	{
		$posted = $this->input->post(NULL, FALSE);
		if (is_array($posted)) $posted['active_locale'] = $this->manage_translation_service->locale($locale);
		$this->session->set_flashdata($this->controller.'_data', is_array($posted) ? $posted : array());
		$this->session->set_flashdata('form_error', $message);
		redirect(base_url('manage/'.$this->controller.'/control'.($editID ? '/edit/'.(int) $editID.'?lang='.$this->manage_translation_service->locale($locale) : '')));
	}

	private function deleteCountryRecords(array $ids)
	{
		$ids = array_values(array_unique(array_filter(array_map('intval', $ids))));
		if (empty($ids)) return 'error';

		$existing = $this->db->select($this->pKey)->where_in($this->pKey, $ids)->get($this->tblName)->result_array();
		if (count($existing) !== count($ids)) return 'error';
		if ($this->db->where_in('book_country_id', $ids)->count_all_results('tour_bookings') > 0) return 'blocked';

		$this->db->trans_begin();
		foreach ($ids as $id) $this->manage_translation_service->delete_jobs('countries', $id);
		$this->db->where_in($this->pKey, $ids)->delete($this->tblName);
		if ($this->db->trans_status() === FALSE || $this->db->affected_rows() !== count($ids))
		{
			$this->db->trans_rollback();
			return 'error';
		}
		$this->db->trans_commit();

		return 'success';
	}

	private function queueTranslationSafely($countryId)
	{
		try
		{
			$this->manage_translation_service->queue('countries', (int) $countryId);
		}
		catch (Throwable $exception)
		{
			log_message('error', 'Country translation could not be queued for record '.(int) $countryId.'.');
		}
	}
}
