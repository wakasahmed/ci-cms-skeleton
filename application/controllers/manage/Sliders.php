<?php defined('BASEPATH') OR exit('No direct script access allowed');

class Sliders extends CI_Controller {

	public $tblName = 'sliders';
	public $colPrefix = 'sliders_';
	public $pKey = 'sliders_id';
	public $moduleName = 'Image Sliders';
	public $moduleNameSingular = 'Slider';
	public $moduleDesc = 'Manage slider collections, their visibility, and the images assigned to each collection.';
	public $controller = 'sliders';
	public $per_page = 10;
	public $tStatus = 'sliders_status';
	public $listView = 'sliders';
	public $addEditView = 'addSliders';
	public $user_data = array();

	public function __construct()
	{
		parent::__construct();
		$this->user_data = $this->SqlModel->authAdmin($this->session->userdata('admin_auth'), $this->session->userdata('admin_id'));
		if (empty($this->user_data)) redirect(base_url('manage/login'));
	}

	public function index($sortby = 'created_at', $order = 'DESC', $status = '-', $keywords = '-', $pg_no = '')
	{
		$allowedSorts = array($this->pKey, $this->colPrefix.'title', $this->tStatus, 'created_at', 'updated_at', 'images');
		$sortby = in_array($sortby, $allowedSorts, TRUE) ? $sortby : 'created_at';
		$order = strtoupper($order) === 'ASC' ? 'ASC' : 'DESC';
		$status = in_array($status, array('Enable', 'Disable'), TRUE) ? $status : '-';
		$allowedPerPage = array_merge(array(0), range(10, 100, 10));
		$requestedPerPage = $this->input->get('per_page');
		if ($requestedPerPage !== NULL && ctype_digit((string) $requestedPerPage) && in_array((int) $requestedPerPage, $allowedPerPage, TRUE)) $this->session->set_userdata('per_page', (int) $requestedPerPage);
		if ($this->session->userdata('per_page') !== NULL && in_array((int) $this->session->userdata('per_page'), $allowedPerPage, TRUE)) $this->per_page = (int) $this->session->userdata('per_page');

		$keywords = urldecode((string) $keywords);
		$where = $status === '-' ? array() : array($this->tStatus => $status);
		$search = $keywords !== '-' ? array('cols' => $this->colPrefix.'title', 'value' => $keywords) : array();
		$baseUrl = base_url('manage/'.$this->controller.'/index/'.$sortby.'/'.$order.'/'.$status.'/'.urlencode($keywords));
		$totalRows = $this->SqlModel->countRecords($this->tblName, $where, $search);
		$uriSegment = 8;
		$offset = max(0, (int) $this->uri->segment($uriSegment, 0));
		$this->pagination->initialize(admin_pagination_config($baseUrl, $totalRows, $this->per_page, $uriSegment));
		$records = $this->SqlModel->getRecords('sliders.*, (SELECT COUNT(*) FROM slider WHERE slider.slider_id = sliders.sliders_id) AS images, (SELECT image FROM slider WHERE slider.slider_id = sliders.sliders_id ORDER BY slider.`order` ASC, slider.id ASC LIMIT 1) AS preview_image', $this->tblName, $sortby, $order, $where, $search, $this->per_page, $offset, FALSE);
		$data = array(
			'alert' => $this->session->flashdata('alert'), 'page_title' => PROJECT_TITLE.' | '.$this->moduleName,
			'userdata' => $this->user_data, 'sliderActive' => 1, 'total_rows' => $totalRows,
			'per_page' => $this->per_page, 'records' => $records, 'paginate' => $this->pagination->create_links(),
			'sortby' => $sortby, 'order' => $order === 'ASC' ? 'DESC' : 'ASC', 'page_numb' => $offset,
			'status' => $status, 'keywords' => $keywords,
		);
		$this->render($this->listView, $data);
	}

	public function control($alert = '', $editID = '')
	{
		$isEdit = ($alert === 'edit');
		$record = array();
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
		$posted = $this->session->flashdata($this->controller.'_data');
		if (is_array($posted)) foreach (array($this->colPrefix.'title', $this->tStatus) as $field) if (array_key_exists($field, $posted)) $record[$field] = $posted[$field];
		$data = array(
			'sliderActive' => 1, 'alert' => $isEdit ? 'edit' : '', 'form_error' => $this->session->flashdata('form_error'),
			'tbl_data' => $record, 'page_title' => PROJECT_TITLE.' | '.($isEdit ? 'Edit' : 'Add').' Image Slider',
			'userdata' => $this->user_data,
		);
		$this->render($this->addEditView, $data);
	}

	public function addRecord()
	{
		if (!$this->validPost()) return $this->formFailure('Enter a slider title and choose a valid status.');
		$data = $this->postedSliderData();
		$data['created_at'] = $data['updated_at'] = date('Y-m-d H:i:s');
		$id = $this->SqlModel->insertRecord($this->tblName, $data);
		if (!$id) return $this->formFailure('The image slider could not be saved. Please try again.');
		$this->session->set_flashdata('alert', 'success');
		redirect(base_url('manage/'.$this->controller));
	}

	public function editRecord($editID = '')
	{
		$editID = (int) $editID;
		$record = $editID > 0 ? $this->SqlModel->getSingleRecord($this->tblName, array($this->pKey => $editID)) : array();
		if (empty($record))
		{
			$this->session->set_flashdata('alert', 'error');
			redirect(base_url('manage/'.$this->controller));
			return;
		}
		if (!$this->validPost()) return $this->formFailure('Enter a slider title and choose a valid status.', $editID);
		$data = $this->postedSliderData();
		$data['updated_at'] = date('Y-m-d H:i:s');
		if (!$this->SqlModel->updateRecord($this->tblName, $data, array($this->pKey => $editID))) return $this->formFailure('The image slider could not be updated. Please try again.', $editID);
		$this->session->set_flashdata('alert', 'editsuccess');
		redirect(base_url('manage/'.$this->controller));
	}

	public function delete($deleteID = '')
	{
		$result = $this->deleteSliderGroups(array((int) $deleteID));
		$this->session->set_flashdata('alert', $result === 'success' ? 'deletesuccess' : ($result === 'blocked' ? 'deleteblocked' : 'deleteerror'));
		redirect(base_url('manage/'.$this->controller));
	}

	public function deleteall()
	{
		$ids = array_values(array_unique(array_filter(array_map('intval', (array) $this->input->post('records')))));
		$result = $this->deleteSliderGroups($ids);
		$this->session->set_flashdata('alert', $result === 'success' ? 'deletesuccess' : ($result === 'blocked' ? 'deleteblocked' : 'deleteerror'));
		redirect(base_url('manage/'.$this->controller));
	}

	public function changestatus($id = 0, $status = 'Enable')
	{
		$this->output->set_content_type('application/json');
		$id = (int) $id;
		$record = $id > 0 ? $this->SqlModel->getSingleRecord($this->tblName, array($this->pKey => $id)) : array();
		if (empty($record) || !in_array($status, array('Enable', 'Disable'), TRUE) || !$this->SqlModel->updateRecord($this->tblName, array($this->tStatus => $status, 'updated_at' => date('Y-m-d H:i:s')), array($this->pKey => $id))) return $this->output->set_output(json_encode(array('status' => 'false')));
		return $this->output->set_output(json_encode(array('status' => 'true', 'id' => $id, 'currentStatus' => $status)));
	}

	public function duplicate($id = 0)
	{
		$sourceId = (int) $id;
		$group = $sourceId > 0 ? $this->SqlModel->getSingleRecord($this->tblName, array($this->pKey => $sourceId)) : array();
		if (empty($group))
		{
			$this->session->set_flashdata('alert', 'error');
			redirect(base_url('manage/'.$this->controller));
			return;
		}
		$slides = $this->SqlModel->getRecords('*', 'slider', 'order', 'ASC', array('slider_id' => $sourceId));
		unset($group[$this->pKey]);
		$group[$this->colPrefix.'title'] = $this->truncate(trim((string) $group[$this->colPrefix.'title']).' Duplicate', 255);
		$group['created_at'] = $group['updated_at'] = date('Y-m-d H:i:s');
		$this->db->trans_begin();
		$newId = $this->SqlModel->insertRecord($this->tblName, $group);
		foreach ($slides as $slide)
		{
			if (!$newId) break;
			unset($slide['id']);
			$slide['slider_id'] = $newId;
			$slide['created_at'] = $slide['updated_at'] = date('Y-m-d H:i:s');
			if (!$this->SqlModel->insertRecord('slider', $slide)) $newId = FALSE;
		}
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

	private function postedSliderData()
	{
		$status = $this->input->post($this->tStatus);
		return array(
			$this->colPrefix.'title' => $this->truncate(trim((string) $this->input->post($this->colPrefix.'title')), 255),
			$this->tStatus => in_array($status, array('Enable', 'Disable'), TRUE) ? $status : 'Disable',
		);
	}

	private function validPost()
	{
		$title = trim((string) $this->input->post($this->colPrefix.'title'));
		return $title !== '' && $this->stringLength($title) <= 255 && in_array($this->input->post($this->tStatus), array('Enable', 'Disable'), TRUE);
	}

	private function formFailure($message, $editID = 0)
	{
		$posted = $this->input->post(NULL, FALSE);
		$this->session->set_flashdata($this->controller.'_data', is_array($posted) ? $posted : array());
		$this->session->set_flashdata('form_error', $message);
		redirect(base_url('manage/'.$this->controller.'/control'.($editID > 0 ? '/edit/'.(int) $editID : '')));
	}

	private function deleteSliderGroups(array $ids)
	{
		$ids = array_values(array_unique(array_filter(array_map('intval', $ids))));
		if (empty($ids)) return 'error';
		$groups = $this->db->where_in($this->pKey, $ids)->get($this->tblName)->result_array();
		if (count($groups) !== count($ids)) return 'error';
		if ($this->sliderGroupsAreAssigned($ids)) return 'blocked';
		$slides = $this->db->select('image,image_ar')->where_in('slider_id', $ids)->get('slider')->result_array();
		$this->db->trans_begin();
		$this->db->where_in('slider_id', $ids)->delete('slider');
		$this->db->where_in($this->pKey, $ids)->delete($this->tblName);
		$deleted = $this->db->affected_rows();
		if ($this->db->trans_status() === FALSE || $deleted !== count($ids))
		{
			$this->db->trans_rollback();
			return 'error';
		}
		$this->db->trans_commit();
		foreach ($slides as $slide)
		{
			$this->deleteSliderImage(isset($slide['image']) ? $slide['image'] : '');
			$this->deleteSliderImage(isset($slide['image_ar']) ? $slide['image_ar'] : '');
		}
		return 'success';
	}

	private function sliderGroupsAreAssigned(array $ids)
	{
		return $this->db->where_in('page_slider', $ids)->count_all_results('pages') > 0;
	}

	private function deleteSliderImage($filename)
	{
		if (!is_string($filename) || $filename === '' || basename($filename) !== $filename) return;
		$this->db->from('slider')->group_start()->where('image', $filename)->or_where('image_ar', $filename)->group_end();
		if ($this->db->count_all_results() > 0) return;
		delete_uploaded_file(FCPATH.'assets/frontend/images/slider', $filename);
	}

	private function truncate($value, $maxLength)
	{
		if (function_exists('mb_substr')) return mb_substr((string) $value, 0, (int) $maxLength, 'UTF-8');
		return substr((string) $value, 0, (int) $maxLength);
	}

	private function stringLength($value)
	{
		if (function_exists('mb_strlen')) return mb_strlen((string) $value, 'UTF-8');
		return strlen((string) $value);
	}
}
