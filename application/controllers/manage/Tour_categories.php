<?php defined('BASEPATH') OR exit('No direct script access allowed');

class Tour_categories extends CI_Controller {

	public $tblName = 'tour_categories';
	public $colPrefix = 'cat_';
	public $pKey = 'cat_id';
	public $moduleName = 'Tour Categories';
	public $moduleNameSingular = 'Tour Category';
	public $moduleDesc = 'Manage the categories used to group tours.';
	public $controller = 'tour-categories';
	public $per_page = 10;
	public $tStatus = 'cat_status';
	public $listView = 'tourCategories';
	public $addEditView = 'addTourCategory';
	public $user_data = array();

	public function __construct()
	{
		parent::__construct();
		$this->user_data = $this->SqlModel->authAdmin($this->session->userdata('admin_auth'), $this->session->userdata('admin_id'));
		if (empty($this->user_data))
		{
			redirect(base_url('manage/login'));
		}
		$this->load->library('manage_translation_service');
	}

	public function index($sortby = 'cat_order', $order = 'ASC', $status = '-', $keywords = '-', $pg_no = '')
	{
		$allowedSorts = array($this->pKey, $this->colPrefix.'order', $this->colPrefix.'name', $this->tStatus, $this->colPrefix.'added', $this->colPrefix.'updated');
		$sortby = in_array($sortby, $allowedSorts, TRUE) ? $sortby : $this->colPrefix.'order';
		$order = strtoupper($order) === 'DESC' ? 'DESC' : 'ASC';
		$status = in_array($status, array('Enable', 'Disable'), TRUE) ? $status : '-';

		$requestedPerPage = $this->input->get('per_page');
		if ($requestedPerPage !== NULL && ctype_digit((string) $requestedPerPage) && (int) $requestedPerPage <= 100)
		{
			$this->session->set_userdata('per_page', (int) $requestedPerPage);
		}
		if ($this->session->userdata('per_page') !== NULL)
		{
			$this->per_page = (int) $this->session->userdata('per_page');
		}

		$keywords = urldecode((string) $keywords);
		$where = $status === '-' ? array() : array($this->tStatus => $status);
		$search = $keywords !== '-' ? array('cols' => $this->colPrefix.'name,'.$this->colPrefix.'name_ar', 'value' => $keywords) : array();
		$baseUrl = base_url('manage/'.$this->controller.'/index/'.$sortby.'/'.$order.'/'.$status.'/'.rawurlencode($keywords));
		$totalRows = $this->SqlModel->countRecords($this->tblName, $where, $search);
		$uriSegment = 8;
		$offset = (int) $this->uri->segment($uriSegment, 0);

		$this->pagination->initialize(admin_pagination_config($baseUrl, $totalRows, $this->per_page, $uriSegment));
		$recordSort = $sortby === $this->colPrefix.'order' ? $sortby.','.$this->colPrefix.'name' : $sortby;
		$records = $this->SqlModel->getRecords('*', $this->tblName, $recordSort, $order, $where, $search, $this->per_page, $offset, FALSE);
		$recordIds = array();
		foreach ($records as $record) $recordIds[] = (int) $record[$this->pKey];
		$translationStatuses = empty($recordIds) ? array() : $this->manage_translation_service->statuses('tour_categories', $recordIds);
		$data = array(
			'alert' => $this->session->flashdata('alert'), 'page_title' => PROJECT_TITLE.' | '.$this->moduleName,
			'userdata' => $this->user_data, 'tourCategoriesActive' => 1,
			'total_rows' => $totalRows, 'per_page' => $this->per_page,
			'records' => $records, 'translation_statuses' => $translationStatuses,
			'paginate' => $this->pagination->create_links(), 'sortby' => $sortby,
			'order' => $order === 'ASC' ? 'DESC' : 'ASC', 'page_numb' => $offset,
			'status' => $status, 'keywords' => $keywords,
			'useManageTranslations' => TRUE,
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
		if (is_array($postedData) && array_key_exists($this->tStatus, $postedData))
		{
			$viewRecord[$this->tStatus] = $postedData[$this->tStatus];
		}
		$localizedValues = $this->manage_translation_service->localized_values('tour_categories', $record, $activeLocale);
		if (is_array($postedData))
		{
			foreach (array_keys($localizedValues) as $control) if (array_key_exists($control, $postedData)) $localizedValues[$control] = $postedData[$control];
		}
		$translationState = $isEdit ? $this->manage_translation_service->state('tour_categories', $editID) : NULL;
		$data = array(
			'tourCategoriesActive' => 1, 'alert' => $isEdit ? 'edit' : '',
			'form_error' => $this->session->flashdata('form_error'), 'tbl_data' => $viewRecord,
			'page_title' => PROJECT_TITLE.' | '.($isEdit ? 'Edit' : 'Add').' '.$this->moduleNameSingular,
			'userdata' => $this->user_data,
			'localized_values' => $localizedValues, 'active_locale' => $activeLocale,
			'manage_locales' => $this->manage_translation_service->locales(),
			'translation_state' => $translationState, 'useManageTranslations' => TRUE,
		);
		$this->render($this->addEditView, $data);
	}

	public function addRecord()
	{
		$activeLocale = 'en';
		if (!$this->validRequiredInput($activeLocale)) return $this->formFailure('Enter the required category name fields.');

		$data = $this->postedCategoryData($activeLocale);
		$this->load->library('admin_record_sorter');
		$data[$this->colPrefix.'order'] = $this->admin_record_sorter->nextOrder($this->controller);
		$data[$this->colPrefix.'added'] = date('Y-m-d H:i:s');
		$data[$this->colPrefix.'updated'] = date('Y-m-d H:i:s');

		$id = $this->SqlModel->insertRecord($this->tblName, $data);
		if (!$id) return $this->formFailure('The record could not be saved. Please try again.');

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
		if (!$this->validRequiredInput($activeLocale)) return $this->formFailure('Enter the required category name fields.', $editID, $activeLocale);

		$data = $this->postedCategoryData($activeLocale);
		$data[$this->colPrefix.'updated'] = date('Y-m-d H:i:s');

		$updated = $this->SqlModel->updateRecord($this->tblName, $data, array($this->pKey => $editID));
		if (!$updated) return $this->formFailure('The record could not be updated. Please try again.', $editID, $activeLocale);

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
		$record = $this->SqlModel->getSingleRecord($this->tblName, array($this->pKey => (int) $deleteID));
		if (empty($record) || !$this->deleteCategoryRecord($record))
		{
			$this->session->set_flashdata('alert', 'deleteerror');
		}
		else
		{
			$this->session->set_flashdata('alert', 'deletesuccess');
		}
		redirect(base_url('manage/'.$this->controller));
	}

	public function deleteall()
	{
		$ids = array_unique(array_filter(array_map('intval', (array) $this->input->post('records'))));
		$deleted = 0;
		foreach ($ids as $id)
		{
			$record = $this->SqlModel->getSingleRecord($this->tblName, array($this->pKey => $id));
			if (!empty($record) && $this->deleteCategoryRecord($record)) $deleted++;
		}
		$this->session->set_flashdata('alert', ($deleted > 0 && $deleted === count($ids)) ? 'deletesuccess' : 'deleteerror');
		redirect(base_url('manage/'.$this->controller));
	}

	public function changestatus($id = 0, $status = 'Enable')
	{
		$this->output->set_content_type('application/json');
		if (!in_array($status, array('Enable', 'Disable'), TRUE) || !$this->SqlModel->updateRecord($this->tblName, array($this->tStatus => $status), array($this->pKey => (int) $id)))
		{
			return $this->output->set_output(json_encode(array('status' => 'false')));
		}
		return $this->output->set_output(json_encode(array('status' => 'true', 'id' => (int) $id, 'currentStatus' => $status)));
	}

	public function duplicate($id = 0)
	{
		$sourceId = (int) $id;
		$data = $this->SqlModel->getSingleRecord($this->tblName, array($this->pKey => $sourceId));
		if (empty($data))
		{
			$this->session->set_flashdata('alert', 'error');
			redirect(base_url('manage/'.$this->controller));
			return;
		}
		unset($data[$this->pKey]);
		$data[$this->colPrefix.'name'] .= ' Duplicate';
		$this->load->library('admin_record_sorter');
		$data[$this->colPrefix.'order'] = $this->admin_record_sorter->nextOrder($this->controller);
		$data[$this->colPrefix.'added'] = $data[$this->colPrefix.'updated'] = date('Y-m-d H:i:s');
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

	public function addRecordAJAX()
	{
		$this->output->set_content_type('application/json');
		if ($this->input->method(TRUE) !== 'POST') return $this->output->set_status_header(405)->set_output(json_encode(array('status' => 'false', 'message' => 'Category creation requires a POST request.')));

		$name = $this->clean($this->input->post($this->colPrefix.'name_ajax'), 255);
		if ($name === '') return $this->output->set_status_header(422)->set_output(json_encode(array('status' => 'false', 'message' => 'Enter a category name.')));
		if ($this->SqlModel->countRecords($this->tblName, array($this->colPrefix.'name' => $name)) > 0) return $this->output->set_status_header(409)->set_output(json_encode(array('status' => 'false', 'message' => 'A tour category with this name already exists.')));

		$this->load->library('admin_record_sorter');
		$now = date('Y-m-d H:i:s');
		$data = array(
			$this->colPrefix.'name' => $name,
			$this->colPrefix.'name_ar' => '',
			$this->colPrefix.'short_description' => '',
			$this->colPrefix.'short_description_ar' => '',
			$this->tStatus => 'Enable',
			$this->colPrefix.'order' => $this->admin_record_sorter->nextOrder($this->controller),
			$this->colPrefix.'added' => $now,
			$this->colPrefix.'updated' => $now,
		);

		$id = $this->SqlModel->insertRecord($this->tblName, $data);
		if (!$id) return $this->output->set_status_header(500)->set_output(json_encode(array('status' => 'false', 'message' => 'The tour category could not be saved. Please try again.')));

		$this->queueTranslationSafely($id);
		return $this->output->set_output(json_encode(array(
			'status' => 'true',
			'cat_id' => (int) $id,
			'cat_name' => $name,
			'csrf_name' => $this->config->item('csrf_protection') ? $this->security->get_csrf_token_name() : NULL,
			'csrf_hash' => $this->config->item('csrf_protection') ? $this->security->get_csrf_hash() : NULL,
		)));
	}

	private function clean($value, $maxLength = NULL)
	{
		$value = trim((string) $value);
		if ($maxLength === NULL) return $value;
		return function_exists('mb_substr') ? mb_substr($value, 0, (int) $maxLength, 'UTF-8') : substr($value, 0, (int) $maxLength);
	}

	private function render($view, $data)
	{
		$this->load->view('admin/header', $data);
		$this->load->view('admin/navigation');
		$this->load->view('admin/'.$view);
		$this->load->view('admin/footer');
	}

	private function postedCategoryData($locale)
	{
		$status = $this->input->post($this->tStatus);
		$data = array(
			$this->tStatus => in_array($status, array('Enable', 'Disable'), TRUE) ? $status : 'Disable',
		);
		$post = $this->input->post(NULL, FALSE);
		return array_merge($data, $this->manage_translation_service->localized_post_data('tour_categories', $locale, is_array($post) ? $post : array()));
	}

	private function validRequiredInput($locale)
	{
		$post = $this->input->post(NULL, FALSE);
		return $this->manage_translation_service->required_localized_input_valid('tour_categories', $locale, is_array($post) ? $post : array());
	}

	private function formFailure($message, $editID = 0, $locale = 'en')
	{
		$posted = $this->input->post(NULL, FALSE);
		if (is_array($posted)) $posted['active_locale'] = $this->manage_translation_service->locale($locale);
		$this->session->set_flashdata($this->controller.'_data', is_array($posted) ? $posted : array());
		$this->session->set_flashdata('form_error', $message);
		redirect(base_url('manage/'.$this->controller.'/control'.($editID ? '/edit/'.(int) $editID.'?lang='.$this->manage_translation_service->locale($locale) : '')));
	}

	private function deleteCategoryRecord($record)
	{
		$this->manage_translation_service->delete_jobs('tour_categories', $record[$this->pKey]);
		return $this->SqlModel->deleteRecord($this->tblName, array($this->pKey => $record[$this->pKey]));
	}

	private function queueTranslationSafely($categoryId)
	{
		try
		{
			$this->manage_translation_service->queue('tour_categories', (int) $categoryId);
		}
		catch (Throwable $exception)
		{
			log_message('error', 'Tour category translation could not be queued for record '.(int) $categoryId.'.');
		}
	}
}
