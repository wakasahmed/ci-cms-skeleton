<?php defined('BASEPATH') OR exit('No direct script access allowed');

class Customer_reviews extends CI_Controller {

	public $tblName = 'customer_reviews';
	public $colPrefix = 'review_';
	public $pKey = 'review_id';
	public $moduleName = 'Customer Reviews';
	public $moduleNameSingular = 'Customer Review';
	public $moduleDesc = 'Manage customer feedback, ratings, profile pictures, display order, and publishing status.';
	public $controller = 'customer-reviews';
	public $per_page = 10;
	public $tStatus = 'review_status';
	public $listView = 'customerReviews';
	public $addEditView = 'addCustomerReview';
	public $user_data = array();

	public function __construct()
	{
		parent::__construct();
		$this->user_data = $this->SqlModel->authAdmin($this->session->userdata('admin_auth'), $this->session->userdata('admin_id'));
		if (empty($this->user_data)) redirect(base_url('manage/login'));
		$this->load->helper('admin_input');
	}

	public function index($sortby = 'review_order', $order = 'ASC', $status = '-', $keywords = '-', $pg_no = '')
	{
		$allowedSorts = array($this->pKey, $this->colPrefix.'order', $this->colPrefix.'name', $this->colPrefix.'rating', $this->tStatus, $this->colPrefix.'added', $this->colPrefix.'updated');
		$sortby = in_array($sortby, $allowedSorts, TRUE) ? $sortby : $this->colPrefix.'order';
		$order = strtoupper($order) === 'DESC' ? 'DESC' : 'ASC';
		$status = in_array($status, array('Enable', 'Disable'), TRUE) ? $status : '-';
		$allowedPerPage = array_merge(array(0), range(10, 100, 10));
		$requestedPerPage = $this->input->get('per_page');
		if ($requestedPerPage !== NULL && ctype_digit((string) $requestedPerPage) && in_array((int) $requestedPerPage, $allowedPerPage, TRUE)) $this->session->set_userdata('per_page', (int) $requestedPerPage);
		if ($this->session->userdata('per_page') !== NULL && in_array((int) $this->session->userdata('per_page'), $allowedPerPage, TRUE)) $this->per_page = (int) $this->session->userdata('per_page');

		$keywords = urldecode((string) $keywords);
		$where = $status === '-' ? array() : array($this->tStatus => $status);
		$search = $keywords !== '-' ? array('cols' => $this->colPrefix.'name,'.$this->colPrefix.'desc', 'value' => $keywords) : array();
		$baseUrl = base_url('manage/'.$this->controller.'/index/'.$sortby.'/'.$order.'/'.$status.'/'.urlencode($keywords));
		$totalRows = $this->SqlModel->countRecords($this->tblName, $where, $search);
		$uriSegment = 8;
		$offset = max(0, (int) $this->uri->segment($uriSegment, 0));
		$this->pagination->initialize(admin_pagination_config($baseUrl, $totalRows, $this->per_page, $uriSegment));
		$records = $this->SqlModel->getRecords('*', $this->tblName, $sortby, $order, $where, $search, $this->per_page, $offset, FALSE);
		$data = array(
			'alert' => $this->session->flashdata('alert'), 'page_title' => PROJECT_TITLE.' | '.$this->moduleName,
			'userdata' => $this->user_data, 'customerReviewsActive' => 1, 'total_rows' => $totalRows,
			'per_page' => $this->per_page, 'records' => $records,
			'paginate' => $this->pagination->create_links(), 'sortby' => $sortby,
			'order' => $order === 'ASC' ? 'DESC' : 'ASC', 'page_numb' => $offset,
			'status' => $status, 'keywords' => $keywords,
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
			$record = $editID > 0 ? $this->SqlModel->getSingleRecord($this->tblName, array($this->pKey => $editID)) : array();
			if (empty($record))
			{
				$this->session->set_flashdata('alert', 'error');
				redirect(base_url('manage/'.$this->controller));
				return;
			}
		}

		$viewRecord = $record;
		foreach (array($this->colPrefix.'name', $this->colPrefix.'desc', $this->colPrefix.'rating', $this->tStatus) as $column) if (is_array($postedData) && array_key_exists($column, $postedData)) $viewRecord[$column] = $postedData[$column];
		$data = array(
			'customerReviewsActive' => 1, 'alert' => $isEdit ? 'edit' : '', 'form_error' => $this->session->flashdata('form_error'),
			'tbl_data' => $viewRecord, 'page_title' => PROJECT_TITLE.' | '.($isEdit ? 'Edit' : 'Add').' '.$this->moduleNameSingular,
			'userdata' => $this->user_data,
		);
		$this->render($this->addEditView, $data);
	}

	public function addRecord()
	{
		if (!$this->validPost()) return $this->formFailure('Enter the customer name and review, and choose a rating from 1 to 5.');
		$image = $this->saveReviewImage();
		if ($image['error'] !== '') return $this->formFailure('Customer picture: '.$image['error']);
		$data = $this->postedReviewData();
		$data[$this->colPrefix.'caption'] = '';
		$data[$this->colPrefix.'image'] = $image['filename'];
		$this->load->library('admin_record_sorter');
		$data[$this->colPrefix.'order'] = $this->admin_record_sorter->nextOrder($this->controller);
		$data[$this->colPrefix.'added'] = $data[$this->colPrefix.'updated'] = date('Y-m-d H:i:s');
		$this->db->trans_begin();
		$id = $this->SqlModel->insertRecord($this->tblName, $data);
		if (!$id || $this->db->trans_status() === FALSE)
		{
			$this->db->trans_rollback();
			$this->deleteReviewImage($image['filename']);
			return $this->formFailure('The customer review could not be saved. Please try again.');
		}
		$this->db->trans_commit();
		$this->session->set_flashdata('alert', 'success');
		redirect(base_url('manage/'.$this->controller));
	}

	public function editRecord($editID = '')
	{
		$editID = (int) $editID;
		$current = $editID > 0 ? $this->SqlModel->getSingleRecord($this->tblName, array($this->pKey => $editID)) : array();
		if (empty($current))
		{
			$this->session->set_flashdata('alert', 'error');
			redirect(base_url('manage/'.$this->controller));
			return;
		}
		if (!$this->validPost()) return $this->formFailure('Enter the customer name and review, and choose a rating from 1 to 5.', $editID);
		$image = $this->saveReviewImage();
		if ($image['error'] !== '') return $this->formFailure('Customer picture: '.$image['error'], $editID);
		$data = $this->postedReviewData();
		$data[$this->colPrefix.'updated'] = date('Y-m-d H:i:s');
		if ($image['filename'] !== '') $data[$this->colPrefix.'image'] = $image['filename'];
		else if ($this->input->post('remove_review_image') === '1') $data[$this->colPrefix.'image'] = '';
		$this->db->trans_begin();
		$updated = $this->SqlModel->updateRecord($this->tblName, $data, array($this->pKey => $editID));
		if (!$updated || $this->db->trans_status() === FALSE)
		{
			$this->db->trans_rollback();
			$this->deleteReviewImage($image['filename']);
			return $this->formFailure('The customer review could not be updated. Please try again.', $editID);
		}
		$this->db->trans_commit();
		if (array_key_exists($this->colPrefix.'image', $data) && $current[$this->colPrefix.'image'] !== $data[$this->colPrefix.'image']) $this->deleteReviewImage($current[$this->colPrefix.'image']);
		$this->session->set_flashdata('alert', 'editsuccess');
		redirect(base_url('manage/'.$this->controller));
	}

	public function delete($deleteID = '')
	{
		$result = $this->deleteReviewRecords(array((int) $deleteID));
		$this->session->set_flashdata('alert', $result ? 'deletesuccess' : 'deleteerror');
		redirect(base_url('manage/'.$this->controller));
	}

	public function deleteall()
	{
		$ids = array_values(array_unique(array_filter(array_map('intval', (array) $this->input->post('records')))));
		$result = $this->deleteReviewRecords($ids);
		$this->session->set_flashdata('alert', $result ? 'deletesuccess' : 'deleteerror');
		redirect(base_url('manage/'.$this->controller));
	}

	public function changestatus($id = 0, $status = 'Enable')
	{
		$this->output->set_content_type('application/json');
		$id = (int) $id;
		$record = $id > 0 ? $this->SqlModel->getSingleRecord($this->tblName, array($this->pKey => $id)) : array();
		if (empty($record) || !in_array($status, array('Enable', 'Disable'), TRUE) || !$this->SqlModel->updateRecord($this->tblName, array($this->tStatus => $status, $this->colPrefix.'updated' => date('Y-m-d H:i:s')), array($this->pKey => $id))) return $this->output->set_output(json_encode(array('status' => 'false')));
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
		$data[$this->colPrefix.'name'] = $this->truncate(trim((string) $data[$this->colPrefix.'name']).' Duplicate', 100);
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
		redirect(base_url('manage/'.$this->controller.'/control/edit/'.$newId));
	}

	private function render($view, $data)
	{
		$this->load->view('admin/header', $data);
		$this->load->view('admin/navigation');
		$this->load->view('admin/'.$view);
		$this->load->view('admin/footer');
	}

	private function postedReviewData()
	{
		$status = $this->input->post($this->tStatus);
		return array(
			$this->colPrefix.'name' => admin_clean_text($this->input->post($this->colPrefix.'name'), 100),
			$this->colPrefix.'desc' => admin_clean_text($this->input->post($this->colPrefix.'desc')),
			$this->colPrefix.'rating' => (int) $this->input->post($this->colPrefix.'rating'),
			$this->tStatus => in_array($status, array('Enable', 'Disable'), TRUE) ? $status : 'Disable',
		);
	}

	private function validPost()
	{
		$rating = $this->input->post($this->colPrefix.'rating');
		$status = $this->input->post($this->tStatus);
		return admin_clean_text($this->input->post($this->colPrefix.'name')) !== ''
			&& admin_clean_text($this->input->post($this->colPrefix.'desc')) !== ''
			&& ctype_digit((string) $rating) && (int) $rating >= 1 && (int) $rating <= 5
			&& in_array($status, array('Enable', 'Disable'), TRUE);
	}

	private function formFailure($message, $editID = 0)
	{
		$posted = $this->input->post(NULL, FALSE);
		$this->session->set_flashdata($this->controller.'_data', is_array($posted) ? $posted : array());
		$this->session->set_flashdata('form_error', $message);
		redirect(base_url('manage/'.$this->controller.'/control'.($editID ? '/edit/'.(int) $editID.'' : '')));
	}

	private function saveReviewImage()
	{
		$result = array('filename' => '', 'error' => '');
		if (empty($_FILES['uploadfile']['name'])) return $result;
		if (!isset($_FILES['uploadfile']['error']) || $_FILES['uploadfile']['error'] !== UPLOAD_ERR_OK)
		{
			$result['error'] = 'The upload did not complete successfully.';
			return $result;
		}
		$uploadPath = FCPATH.'assets/frontend/images/customer-reviews'.DIRECTORY_SEPARATOR;
		if (!is_dir($uploadPath) && !mkdir($uploadPath, 0755, TRUE))
		{
			$result['error'] = 'The image storage directory could not be created.';
			return $result;
		}
		$this->load->library('upload');
		$this->upload->initialize(array('upload_path' => $uploadPath, 'allowed_types' => UPLOAD_IMAGE_MIMES, 'max_size' => AVATAR_UPLOAD_MAX_MB * 1024, 'max_width' => 1024, 'max_height' => 1024, 'encrypt_name' => TRUE, 'remove_spaces' => TRUE));
		if (!$this->upload->do_upload('uploadfile'))
		{
			$result['error'] = strip_tags($this->upload->display_errors('', ''));
			return $result;
		}
		$file = $this->upload->data();
		$result['filename'] = $file['file_name'];
		return $result;
	}

	private function deleteReviewRecords(array $ids)
	{
		$ids = array_values(array_unique(array_filter(array_map('intval', $ids))));
		if (empty($ids)) return FALSE;
		$records = $this->db->where_in($this->pKey, $ids)->get($this->tblName)->result_array();
		if (count($records) !== count($ids)) return FALSE;
		$this->db->trans_begin();
		$this->db->where_in($this->pKey, $ids)->delete($this->tblName);
		if ($this->db->trans_status() === FALSE || $this->db->affected_rows() !== count($ids))
		{
			$this->db->trans_rollback();
			return FALSE;
		}
		$this->db->trans_commit();
		foreach ($records as $record) $this->deleteReviewImage($record[$this->colPrefix.'image']);
		return TRUE;
	}

	private function deleteReviewImage($filename)
	{
		if (!is_string($filename) || $filename === '' || basename($filename) !== $filename) return;
		if ($this->SqlModel->countRecords($this->tblName, array($this->colPrefix.'image' => $filename)) > 0) return;
		delete_uploaded_file(FCPATH.'assets/frontend/images/customer-reviews', $filename);
	}

	private function truncate($value, $maxLength)
	{
		if (function_exists('mb_substr')) return mb_substr((string) $value, 0, (int) $maxLength, 'UTF-8');
		return substr((string) $value, 0, (int) $maxLength);
	}
}
