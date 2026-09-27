<?php defined('BASEPATH') OR exit('No direct script access allowed');

class Faqs extends CI_Controller {

	public $tblName = 'faqs';
	public $colPrefix = 'faq_';
	public $pKey = 'faq_id';
	public $moduleName = 'FAQs';
	public $moduleNameSingular = 'FAQ';
	public $moduleDesc = 'Manage frequently asked questions in this category.';
	public $controller = 'faqs';
	public $per_page = 10;
	public $tStatus = 'faq_status';
	public $listView = 'faqs';
	public $addEditView = 'addFaq';
	public $user_data = array();
	private $translationModule = 'faqs';
	private $categoriesTable = 'faqs_categories';
	private $categoriesController = 'faqs-categories';

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

	public function index($sortby = 'faq_order', $order = 'ASC', $status = '-', $keywords = '-', $pg_no = '')
	{
		$category = $this->requiredCategory($this->validCategoryFilterId($this->input->get('category_id')));
		$categoryId = (int) $category['cat_id'];
		$this->moduleName = $category['cat_name'].' FAQs';

		$allowedSorts = array($this->pKey, $this->colPrefix.'order', $this->colPrefix.'question', $this->colPrefix.'question_ar', $this->tStatus, $this->colPrefix.'added', $this->colPrefix.'updated');
		$sortby = in_array($sortby, $allowedSorts, TRUE) ? $sortby : $this->colPrefix.'order';
		$order = strtoupper($order) === 'DESC' ? 'DESC' : 'ASC';
		$status = in_array($status, array('Enable', 'Disable'), TRUE) ? $status : '-';

		$allowedPerPage = array_merge(array(0), range(10, 100, 10));
		$requestedPerPage = $this->input->get('per_page');
		if ($requestedPerPage !== NULL && ctype_digit((string) $requestedPerPage) && in_array((int) $requestedPerPage, $allowedPerPage, TRUE))
		{
			$this->session->set_userdata('per_page', (int) $requestedPerPage);
		}
		if ($this->session->userdata('per_page') !== NULL && in_array((int) $this->session->userdata('per_page'), $allowedPerPage, TRUE))
		{
			$this->per_page = (int) $this->session->userdata('per_page');
		}

		$keywords = urldecode((string) $keywords);
		$where = $status === '-' ? array() : array($this->tStatus => $status);
		$where[$this->colPrefix.'cat_id'] = $categoryId;
		$search = $keywords !== '-' ? array('cols' => $this->colPrefix.'question,'.$this->colPrefix.'question_ar,'.$this->colPrefix.'answer,'.$this->colPrefix.'answer_ar', 'value' => $keywords) : array();
		$baseUrl = base_url('manage/'.$this->controller.'/index/'.$sortby.'/'.$order.'/'.$status.'/'.rawurlencode($keywords));
		$totalRows = $this->SqlModel->countRecords($this->tblName, $where, $search);
		$uriSegment = 8;
		$offset = max(0, (int) $this->uri->segment($uriSegment, 0));

		$paginationConfig = admin_pagination_config($baseUrl, $totalRows, $this->per_page, $uriSegment);
		$paginationConfig['suffix'] = '?category_id='.$categoryId;
		$this->pagination->initialize($paginationConfig);
		$recordSort = $sortby === $this->colPrefix.'order' ? $sortby.','.$this->pKey : $sortby;
		$records = $this->SqlModel->getRecords('*', $this->tblName, $recordSort, $order, $where, $search, $this->per_page, $offset, FALSE);
		$recordIds = array();
		foreach ($records as $record) $recordIds[] = (int) $record[$this->pKey];
		$translationStatuses = empty($recordIds) ? array() : $this->manage_translation_service->statuses($this->translationModule, $recordIds);
		$data = array(
			'alert' => $this->session->flashdata('alert'), 'page_title' => PROJECT_TITLE.' | '.$this->moduleName,
			'userdata' => $this->user_data, 'faqsActive' => 1,
			'total_rows' => $totalRows, 'per_page' => $this->per_page,
			'records' => $records, 'translation_statuses' => $translationStatuses,
			'paginate' => $this->pagination->create_links(), 'sortby' => $sortby,
			'order' => $order === 'ASC' ? 'DESC' : 'ASC', 'page_numb' => $offset,
			'status' => $status, 'keywords' => $keywords,
			'category_filter_id' => $categoryId, 'category_filter' => $category,
			'categories' => $this->SqlModel->getRecords('cat_id,cat_name', $this->categoriesTable, 'cat_name', 'ASC'),
			'categories_controller' => $this->categoriesController,
			'useManageTranslations' => TRUE,
		);
		$this->render($this->listView, $data);
	}

	public function control($alert = '', $editID = '')
	{
		$isEdit = ($alert === 'edit');
		$record = array();
		$categoryId = 0;
		if ($isEdit)
		{
			$editID = (int) $editID;
			$record = $this->SqlModel->getSingleRecord($this->tblName, array($this->pKey => $editID));
			if (empty($record))
			{
				redirect(base_url('manage/'.$this->categoriesController));
				return;
			}
			$categoryId = (int) $record[$this->colPrefix.'cat_id'];
		}
		else
		{
			$categoryId = $this->validCategoryFilterId($this->input->get('category_id'));
		}
		$category = $this->requiredCategory($categoryId);
		$categoryId = (int) $category['cat_id'];
		$this->moduleName = $category['cat_name'].' FAQs';

		$postedData = $this->session->flashdata($this->controller.'_data');
		$requestedLocale = is_array($postedData) && isset($postedData['active_locale']) ? $postedData['active_locale'] : $this->input->get('lang', TRUE);
		$activeLocale = $isEdit ? $this->manage_translation_service->locale($requestedLocale) : 'en';

		$viewRecord = $record;
		$viewRecord[$this->colPrefix.'cat_id'] = $categoryId;
		if (is_array($postedData) && array_key_exists($this->tStatus, $postedData)) $viewRecord[$this->tStatus] = $postedData[$this->tStatus];
		$localizedValues = $this->manage_translation_service->localized_values($this->translationModule, $record, $activeLocale);
		if (is_array($postedData))
		{
			foreach (array_keys($localizedValues) as $control) if (array_key_exists($control, $postedData)) $localizedValues[$control] = $postedData[$control];
		}
		$translationState = $isEdit ? $this->manage_translation_service->state($this->translationModule, $editID) : NULL;
		$data = array(
			'faqsActive' => 1, 'alert' => $isEdit ? 'edit' : '',
			'form_error' => $this->session->flashdata('form_error'), 'tbl_data' => $viewRecord,
			'page_title' => PROJECT_TITLE.' | '.($isEdit ? 'Edit' : 'Add').' '.$this->moduleNameSingular,
			'userdata' => $this->user_data,
			'localized_values' => $localizedValues, 'active_locale' => $activeLocale,
			'manage_locales' => $this->manage_translation_service->locales(),
			'translation_state' => $translationState, 'useManageTranslations' => TRUE,
			'category_filter_id' => $categoryId, 'category_filter' => $category,
		);
		$this->render($this->addEditView, $data);
	}

	public function addRecord()
	{
		$activeLocale = 'en';
		if (!$this->validRequiredInput($activeLocale)) return $this->formFailure('Enter the required question and answer in English.');
		if (!$this->validCategoryInput()) return $this->formFailure('Select a valid FAQ category.');

		$categoryId = (int) $this->input->post($this->colPrefix.'cat_id');
		$data = $this->postedFaqData($activeLocale);
		$data[$this->colPrefix.'question_ar'] = '';
		$data[$this->colPrefix.'answer_ar'] = '';
		$this->load->library('admin_record_sorter');
		$data[$this->colPrefix.'order'] = $this->admin_record_sorter->nextOrder($this->controller);
		$data[$this->colPrefix.'added'] = date('Y-m-d H:i:s');
		$data[$this->colPrefix.'updated'] = date('Y-m-d H:i:s');

		$id = $this->SqlModel->insertRecord($this->tblName, $data);
		if (!$id) return $this->formFailure('The FAQ could not be saved. Please try again.');

		$this->queueTranslationSafely($id);
		$this->session->set_flashdata('alert', 'success');
		redirect(base_url('manage/'.$this->controller.'?category_id='.$categoryId));
	}

	public function editRecord($editID = '')
	{
		$editID = (int) $editID;
		$activeLocale = $this->manage_translation_service->locale($this->input->post('active_locale', TRUE));
		$current = $this->SqlModel->getSingleRecord($this->tblName, array($this->pKey => $editID));
		if (empty($current))
		{
			redirect(base_url('manage/'.$this->categoriesController));
			return;
		}
		if (!$this->validRequiredInput($activeLocale)) return $this->formFailure($activeLocale === 'ar' ? 'Enter the required question and answer in Arabic.' : 'Enter the required question and answer.', $editID, $activeLocale);
		if (!$this->validCategoryInput()) return $this->formFailure('Select a valid FAQ category.', $editID, $activeLocale);

		$categoryId = (int) $this->input->post($this->colPrefix.'cat_id');
		$data = $this->postedFaqData($activeLocale);
		$data[$this->colPrefix.'updated'] = date('Y-m-d H:i:s');
		if ($activeLocale === 'en')
		{
			if (trim((string) $current[$this->colPrefix.'question']) !== $data[$this->colPrefix.'question']) $data[$this->colPrefix.'question_ar'] = '';
			if (trim((string) $current[$this->colPrefix.'answer']) !== $data[$this->colPrefix.'answer']) $data[$this->colPrefix.'answer_ar'] = '';
		}

		if (!$this->SqlModel->updateRecord($this->tblName, $data, array($this->pKey => $editID))) return $this->formFailure('The FAQ could not be updated. Please try again.', $editID, $activeLocale);

		if ($activeLocale === 'en') $this->queueTranslationSafely($editID);
		$this->session->set_flashdata('alert', 'editsuccess');
		$redirectLocale = $this->input->post('redirect_lang', TRUE);
		if (is_string($redirectLocale) && $redirectLocale !== '' && $this->manage_translation_service->locale($redirectLocale) === $redirectLocale)
		{
			redirect(base_url('manage/'.$this->controller.'/control/edit/'.$editID.'?lang='.$redirectLocale));
			return;
		}
		redirect(base_url('manage/'.$this->controller.'?category_id='.$categoryId));
	}

	public function delete($deleteID = '')
	{
		$record = $this->SqlModel->getSingleRecord($this->tblName, array($this->pKey => (int) $deleteID));
		$categoryId = !empty($record) ? (int) $record[$this->colPrefix.'cat_id'] : 0;
		if (empty($record) || !$this->deleteFaqRecord($record))
		{
			$this->session->set_flashdata('alert', 'deleteerror');
		}
		else
		{
			$this->session->set_flashdata('alert', 'deletesuccess');
		}
		redirect($categoryId > 0 ? base_url('manage/'.$this->controller.'?category_id='.$categoryId) : base_url('manage/'.$this->categoriesController));
	}

	public function deleteall()
	{
		$categoryId = $this->validCategoryFilterId($this->input->get('category_id'));
		$ids = array_unique(array_filter(array_map('intval', (array) $this->input->post('records'))));
		$deleted = 0;
		foreach ($ids as $id)
		{
			$record = $this->SqlModel->getSingleRecord($this->tblName, array($this->pKey => $id));
			if (!empty($record) && $this->deleteFaqRecord($record)) $deleted++;
		}
		$this->session->set_flashdata('alert', ($deleted > 0 && $deleted === count($ids)) ? 'deletesuccess' : 'deleteerror');
		redirect($categoryId > 0 ? base_url('manage/'.$this->controller.'?category_id='.$categoryId) : base_url('manage/'.$this->categoriesController));
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
			redirect(base_url('manage/'.$this->categoriesController));
			return;
		}
		unset($data[$this->pKey]);
		$data[$this->colPrefix.'question'] = trim((string) $data[$this->colPrefix.'question']).' Duplicate';
		$data[$this->colPrefix.'question_ar'] = '';
		$data[$this->colPrefix.'answer_ar'] = '';
		if (!empty($data[$this->colPrefix.'cat_id']) && $this->SqlModel->countRecords($this->categoriesTable, array('cat_id' => (int) $data[$this->colPrefix.'cat_id'])) < 1)
		{
			$data[$this->colPrefix.'cat_id'] = NULL;
		}
		$this->load->library('admin_record_sorter');
		$data[$this->colPrefix.'order'] = $this->admin_record_sorter->nextOrder($this->controller);
		$data[$this->colPrefix.'added'] = $data[$this->colPrefix.'updated'] = date('Y-m-d H:i:s');
		$newId = $this->SqlModel->insertRecord($this->tblName, $data);
		if (!$newId)
		{
			$this->session->set_flashdata('alert', 'error');
			redirect(base_url('manage/'.$this->categoriesController));
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

	private function requiredCategory($categoryId)
	{
		$category = $categoryId > 0 ? $this->SqlModel->getSingleRecord($this->categoriesTable, array('cat_id' => $categoryId)) : array();
		if (empty($category))
		{
			redirect(base_url('manage/'.$this->categoriesController));
			exit;
		}
		return $category;
	}

	private function postedFaqData($locale)
	{
		$status = $this->input->post($this->tStatus);
		$data = array(
			$this->tStatus => in_array($status, array('Enable', 'Disable'), TRUE) ? $status : 'Disable',
			$this->colPrefix.'cat_id' => $this->postedCategoryId(),
		);
		$post = $this->input->post(NULL, FALSE);
		return array_merge($data, $this->manage_translation_service->localized_post_data($this->translationModule, $locale, is_array($post) ? $post : array()));
	}

	private function postedCategoryId()
	{
		$catId = (int) $this->input->post($this->colPrefix.'cat_id');
		return $catId > 0 ? $catId : NULL;
	}

	private function validCategoryInput()
	{
		$catId = (int) $this->input->post($this->colPrefix.'cat_id');
		if ($catId <= 0) return FALSE;
		return (int) $this->SqlModel->countRecords($this->categoriesTable, array('cat_id' => $catId)) > 0;
	}

	private function validCategoryFilterId($value)
	{
		return $value !== NULL && ctype_digit((string) $value) && (int) $value > 0 ? (int) $value : 0;
	}

	private function validRequiredInput($locale)
	{
		$post = $this->input->post(NULL, FALSE);
		return $this->manage_translation_service->required_localized_input_valid($this->translationModule, $locale, is_array($post) ? $post : array());
	}

	private function formFailure($message, $editID = 0, $locale = 'en')
	{
		$posted = $this->input->post(NULL, FALSE);
		if (is_array($posted)) $posted['active_locale'] = $this->manage_translation_service->locale($locale);
		$this->session->set_flashdata($this->controller.'_data', is_array($posted) ? $posted : array());
		$this->session->set_flashdata('form_error', $message);
		$categoryId = (int) $this->input->post($this->colPrefix.'cat_id');
		$suffix = $editID ? '/edit/'.(int) $editID.'?lang='.$this->manage_translation_service->locale($locale) : ($categoryId > 0 ? '?category_id='.$categoryId : '');
		redirect(base_url('manage/'.$this->controller.'/control'.$suffix));
	}

	private function deleteFaqRecord($record)
	{
		$this->manage_translation_service->delete_jobs($this->translationModule, $record[$this->pKey]);
		return $this->SqlModel->deleteRecord($this->tblName, array($this->pKey => $record[$this->pKey]));
	}

	private function queueTranslationSafely($faqId)
	{
		try
		{
			$this->manage_translation_service->queue($this->translationModule, (int) $faqId);
		}
		catch (Throwable $exception)
		{
			log_message('error', 'FAQ translation could not be queued for record '.(int) $faqId.'.');
		}
	}
}
