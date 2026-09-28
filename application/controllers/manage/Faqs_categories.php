<?php defined('BASEPATH') OR exit('No direct script access allowed');

class Faqs_categories extends CI_Controller {

	public $tblName = 'faqs_categories';
	public $colPrefix = 'cat_';
	public $pKey = 'cat_id';
	public $moduleName = 'FAQs';
	public $moduleNameSingular = 'FAQ Category';
	public $moduleDesc = 'Manage the categories used to group frequently asked questions.';
	public $controller = 'faqs-categories';
	public $per_page = 10;
	public $tStatus = 'cat_status';
	public $listView = 'faqsCategories';
	public $addEditView = 'addFaqsCategory';
	public $user_data = array();
	private $faqsTable = 'faqs';
	private $protectedCategoryId = 1;

	public function __construct()
	{
		parent::__construct();
		$this->user_data = $this->SqlModel->authAdmin($this->session->userdata('admin_auth'), $this->session->userdata('admin_id'));
		if (empty($this->user_data))
		{
			redirect(base_url('manage/login'));
		}
		$this->load->helper('admin_input');
	}

	public function index($sortby = 'cat_order', $order = 'ASC', $status = '-', $hidden = '-', $keywords = '-', $pg_no = '')
	{
		$allowedSorts = array($this->pKey, $this->colPrefix.'order', $this->colPrefix.'name', $this->tStatus, $this->colPrefix.'added', $this->colPrefix.'updated');
		$sortby = in_array($sortby, $allowedSorts, TRUE) ? $sortby : $this->colPrefix.'order';
		$order = strtoupper($order) === 'DESC' ? 'DESC' : 'ASC';
		$status = in_array($status, array('Enable', 'Disable'), TRUE) ? $status : '-';
		$hidden = in_array($hidden, array('Yes', 'No'), TRUE) ? $hidden : '-';

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
		$where = array();
		if ($status !== '-') $where[$this->tStatus] = $status;
		if ($hidden !== '-') $where[$this->colPrefix.'hidden'] = $hidden;
		$search = $keywords !== '-' ? array('cols' => $this->colPrefix.'name', 'value' => $keywords) : array();
		$baseUrl = base_url('manage/'.$this->controller.'/index/'.$sortby.'/'.$order.'/'.$status.'/'.$hidden.'/'.rawurlencode($keywords));
		$totalRows = $this->SqlModel->countRecords($this->tblName, $where, $search);
		$uriSegment = 9;
		$offset = (int) $this->uri->segment($uriSegment, 0);

		$this->pagination->initialize(admin_pagination_config($baseUrl, $totalRows, $this->per_page, $uriSegment));
		$recordSort = $sortby === $this->colPrefix.'order' ? $sortby.','.$this->colPrefix.'name' : $sortby;
		$listingFields = $this->tblName.'.*, (SELECT COUNT(*) FROM '.$this->faqsTable.' WHERE '.$this->faqsTable.'.faq_cat_id = '.$this->tblName.'.'.$this->pKey.') AS faq_count';
		$records = $this->SqlModel->getRecords($listingFields, $this->tblName, $recordSort, $order, $where, $search, $this->per_page, $offset, FALSE);
		$data = array(
			'alert' => $this->session->flashdata('alert'), 'page_title' => PROJECT_TITLE.' | '.$this->moduleName,
			'userdata' => $this->user_data, 'faqsCategoriesActive' => 1,
			'total_rows' => $totalRows, 'per_page' => $this->per_page,
			'records' => $records,
			'paginate' => $this->pagination->create_links(), 'sortby' => $sortby,
			'order' => $order === 'ASC' ? 'DESC' : 'ASC', 'page_numb' => $offset,
			'status' => $status, 'hidden' => $hidden, 'keywords' => $keywords,
			'protected_category_id' => $this->protectedCategoryId,
		);
		$this->render($this->listView, $data);
	}

	public function control($alert = '', $editID = '')
	{
		$isEdit = ($alert === 'edit');
		$record = array();
		$postedData = $this->session->flashdata($this->controller.'_data');
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
			foreach (array($this->colPrefix.'name', $this->colPrefix.'short_description', $this->tStatus, $this->colPrefix.'hidden') as $field)
			{
				if (array_key_exists($field, $postedData)) $viewRecord[$field] = $postedData[$field];
			}
		}
		$data = array(
			'faqsCategoriesActive' => 1, 'alert' => $isEdit ? 'edit' : '',
			'form_error' => $this->session->flashdata('form_error'), 'tbl_data' => $viewRecord,
			'page_title' => PROJECT_TITLE.' | '.($isEdit ? 'Edit' : 'Add').' '.$this->moduleNameSingular,
			'userdata' => $this->user_data,
		);
		$this->render($this->addEditView, $data);
	}

	public function addRecord()
	{
		if (!$this->validRequiredInput()) return $this->formFailure('Enter the category name.');

		$data = $this->postedCategoryData();
		$this->load->library('admin_record_sorter');
		$data[$this->colPrefix.'order'] = $this->admin_record_sorter->nextOrder($this->controller);
		$data[$this->colPrefix.'added'] = date('Y-m-d H:i:s');
		$data[$this->colPrefix.'updated'] = date('Y-m-d H:i:s');

		$id = $this->SqlModel->insertRecord($this->tblName, $data);
		if (!$id) return $this->formFailure('The record could not be saved. Please try again.');

		$this->session->set_flashdata('alert', 'success');
		redirect(base_url('manage/'.$this->controller));
	}

	public function editRecord($editID = '')
	{
		$editID = (int) $editID;
		$current = $this->SqlModel->getSingleRecord($this->tblName, array($this->pKey => $editID));
		if (empty($current))
		{
			$this->session->set_flashdata('alert', 'error');
			redirect(base_url('manage/'.$this->controller));
			return;
		}
		if (!$this->validRequiredInput()) return $this->formFailure('Enter the category name.', $editID);

		$data = $this->postedCategoryData();
		$data[$this->colPrefix.'updated'] = date('Y-m-d H:i:s');

		$updated = $this->SqlModel->updateRecord($this->tblName, $data, array($this->pKey => $editID));
		if (!$updated) return $this->formFailure('The record could not be updated. Please try again.', $editID);

		$this->session->set_flashdata('alert', 'editsuccess');
		redirect(base_url('manage/'.$this->controller));
	}

	public function delete($deleteID = '')
	{
		$result = $this->deleteCategoryRecords(array((int) $deleteID));
		$this->session->set_flashdata('alert', $result === 'success' ? 'deletesuccess' : ($result === 'blocked' ? 'deleteblocked' : 'deleteerror'));
		redirect(base_url('manage/'.$this->controller));
	}

	public function deleteall()
	{
		$ids = array_values(array_unique(array_filter(array_map('intval', (array) $this->input->post('records')))));
		$result = $this->deleteCategoryRecords($ids);
		$this->session->set_flashdata('alert', $result === 'success' ? 'deletesuccess' : ($result === 'blocked' ? 'deleteblocked' : 'deleteerror'));
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
		redirect(base_url('manage/'.$this->controller.'/control/edit/'.$newId));
	}

	private function render($view, $data)
	{
		$this->load->view('admin/header', $data);
		$this->load->view('admin/navigation');
		$this->load->view('admin/'.$view);
		$this->load->view('admin/footer');
	}

	private function postedCategoryData()
	{
		$status = $this->input->post($this->tStatus);
		$hidden = $this->input->post($this->colPrefix.'hidden');

		return array(
			$this->colPrefix.'name' => admin_clean_text($this->input->post($this->colPrefix.'name'), 255),
			$this->colPrefix.'short_description' => admin_clean_text($this->input->post($this->colPrefix.'short_description')),
			$this->tStatus => in_array($status, array('Enable', 'Disable'), TRUE) ? $status : 'Disable',
			$this->colPrefix.'hidden' => in_array($hidden, array('Yes', 'No'), TRUE) ? $hidden : 'No',
		);
	}

	private function validRequiredInput()
	{
		return admin_clean_text($this->input->post($this->colPrefix.'name')) !== '';
	}

	private function formFailure($message, $editID = 0)
	{
		$posted = $this->input->post(NULL, FALSE);
		$this->session->set_flashdata($this->controller.'_data', is_array($posted) ? $posted : array());
		$this->session->set_flashdata('form_error', $message);
		redirect(base_url('manage/'.$this->controller.'/control'.($editID ? '/edit/'.(int) $editID : '')));
	}

	private function deleteCategoryRecords(array $ids)
	{
		$ids = array_values(array_unique(array_filter(array_map('intval', $ids))));
		if (empty($ids)) return 'error';
		if (in_array($this->protectedCategoryId, $ids, TRUE)) return 'error';

		$existing = $this->db->select($this->pKey)->where_in($this->pKey, $ids)->get($this->tblName)->result_array();
		if (count($existing) !== count($ids)) return 'error';
		if ($this->db->where_in('faq_cat_id', $ids)->count_all_results($this->faqsTable) > 0) return 'blocked';

		$this->db->trans_begin();
		$this->db->where_in($this->pKey, $ids)->delete($this->tblName);
		if ($this->db->trans_status() === FALSE || $this->db->affected_rows() !== count($ids))
		{
			$this->db->trans_rollback();
			return 'error';
		}
		$this->db->trans_commit();

		return 'success';
	}

}
