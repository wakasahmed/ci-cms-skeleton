<?php defined('BASEPATH') OR exit('No direct script access allowed');

class Slider extends CI_Controller {

	public $tblName = 'slider';
	public $colPrefix = '';
	public $pKey = 'id';
	public $moduleName = 'Slider Images';
	public $moduleNameSingular = 'Slider Image';
	public $moduleDesc = 'Manage slide content, images, display order, and visibility for this slider collection.';
	public $controller = 'slider';
	public $per_page = 10;
	public $tStatus = 'status';
	public $listView = 'slider';
	public $addEditView = 'addSlider';
	public $user_data = array();

	private $imageDirectory = 'assets/frontend/images/slider';

	public function __construct()
	{
		parent::__construct();
		$this->user_data = $this->SqlModel->authAdmin($this->session->userdata('admin_auth'), $this->session->userdata('admin_id'));
		if (empty($this->user_data)) redirect(base_url('manage/login'));
		$this->load->helper('admin_input');
	}

	public function index($alert = 'page', $sliderID = 0, $sortby = 'order', $order = 'ASC', $status = '-', $keywords = '-', $pg_no = '')
	{
		$group = $this->sliderGroup($sliderID);
		if (empty($group)) return $this->redirectToGroups();
		$allowedSorts = array($this->pKey, 'order', 'heading', 'pre_heading', $this->tStatus, 'created_at', 'updated_at');
		$sortby = in_array($sortby, $allowedSorts, TRUE) ? $sortby : 'order';
		$order = strtoupper($order) === 'DESC' ? 'DESC' : 'ASC';
		$status = in_array($status, array('Enable', 'Disable'), TRUE) ? $status : '-';
		$allowedPerPage = array_merge(array(0), range(10, 100, 10));
		$requestedPerPage = $this->input->get('per_page');
		if ($requestedPerPage !== NULL && ctype_digit((string) $requestedPerPage) && in_array((int) $requestedPerPage, $allowedPerPage, TRUE)) $this->session->set_userdata('per_page', (int) $requestedPerPage);
		if ($this->session->userdata('per_page') !== NULL && in_array((int) $this->session->userdata('per_page'), $allowedPerPage, TRUE)) $this->per_page = (int) $this->session->userdata('per_page');
		$keywords = urldecode((string) $keywords);
		$where = array('slider_id' => (int) $group['sliders_id']);
		if ($status !== '-') $where[$this->tStatus] = $status;
		$search = $keywords !== '-' ? array('cols' => 'pre_heading,heading,text', 'value' => $keywords) : array();
		$baseUrl = base_url('manage/'.$this->controller.'/index/page/'.(int) $group['sliders_id'].'/'.$sortby.'/'.$order.'/'.$status.'/'.urlencode($keywords));
		$totalRows = $this->SqlModel->countRecords($this->tblName, $where, $search);
		$uriSegment = 10;
		$offset = max(0, (int) ($pg_no !== '' ? $pg_no : $this->uri->segment($uriSegment, 0)));
		$this->pagination->initialize(admin_pagination_config($baseUrl, $totalRows, $this->per_page, $uriSegment));
		$recordSort = $sortby === 'order' ? 'order,id' : $sortby;
		$records = $this->SqlModel->getRecords('*', $this->tblName, $recordSort, $order, $where, $search, $this->per_page, $offset, FALSE);
		$routeAlert = in_array($alert, array('success', 'editsuccess', 'deletesuccess', 'deleteerror', 'error'), TRUE) ? $alert : '';
		$data = array(
			'alert' => $this->session->flashdata('alert') ?: $routeAlert, 'page_title' => PROJECT_TITLE.' | '.$this->moduleName,
			'userdata' => $this->user_data, 'sliderActive' => 1, 'slider_id' => (int) $group['sliders_id'], 'slider_name' => $group['sliders_title'],
			'total_rows' => $totalRows, 'per_page' => $this->per_page, 'records' => $records,
			'paginate' => $this->pagination->create_links(), 'sortby' => $sortby, 'order' => $order === 'ASC' ? 'DESC' : 'ASC', 'page_numb' => $offset,
			'status' => $status, 'keywords' => $keywords,
		);
		$this->render($this->listView, $data);
	}

	public function control($sliderID = 0, $alert = '', $editID = 0)
	{
		$group = $this->sliderGroup($sliderID);
		if (empty($group)) return $this->redirectToGroups();
		$isEdit = ($alert === 'edit');
		$postedData = $this->session->flashdata($this->controller.'_data');
		$record = array();
		if ($isEdit)
		{
			$editID = (int) $editID;
			$record = $this->slideRecord($editID, $group['sliders_id']);
			if (empty($record))
			{
				$this->session->set_flashdata('alert', 'error');
				return $this->redirectToGroup($group['sliders_id']);
			}
		}
		$textValues = array();
		foreach ($this->textFields() as $column => $maxLength) $textValues[$column] = isset($record[$column]) ? $record[$column] : '';
		if (is_array($postedData)) foreach (array_keys($textValues) as $column) if (array_key_exists($column, $postedData)) $textValues[$column] = $postedData[$column];
		$data = array(
			'sliderActive' => 1, 'alert' => $isEdit ? 'edit' : '', 'form_error' => $this->session->flashdata('form_error'),
			'tbl_data' => $record, 'form_values' => $this->formValues($record, $postedData),
			'text_values' => $textValues,
			'page_title' => PROJECT_TITLE.' | '.($isEdit ? 'Edit' : 'Add').' '.$this->moduleNameSingular, 'userdata' => $this->user_data,
			'slider_id' => (int) $group['sliders_id'], 'slider_name' => $group['sliders_title'], 'next_order' => $this->nextOrder($group['sliders_id']),
			'useIconPicker' => TRUE, 'useColorPicker' => TRUE,
		);
		$this->render($this->addEditView, $data);
	}

	public function addRecord($sliderID = 0)
	{
		$group = $this->sliderGroup($sliderID);
		if (empty($group)) return $this->redirectToGroups();
		if (!$this->validPost('en')) return $this->formFailure('Check the slide content, colours, and status.', $group['sliders_id']);
		$image = $this->saveUploadedImage('slide_image');
		if ($image['error'] !== '') return $this->formFailure('Slide image: '.$image['error'], $group['sliders_id']);
		if ($image['filename'] === '') return $this->formFailure('A slide image is required.', $group['sliders_id']);
		$data = $this->postedRecordData();
		$data['slider_id'] = (int) $group['sliders_id'];
		$data['order'] = $this->nextOrder($group['sliders_id']);
		$data['image'] = $image['filename'];
		$data['created_at'] = $data['updated_at'] = date('Y-m-d H:i:s');
		$this->db->trans_begin();
		$id = $this->SqlModel->insertRecord($this->tblName, $data);
		if (!$id || $this->db->trans_status() === FALSE)
		{
			$this->db->trans_rollback();
			$this->deleteSliderImage($image['filename']);
			return $this->formFailure('The slider image could not be saved. Please try again.', $group['sliders_id']);
		}
		$this->db->trans_commit();
		$this->session->set_flashdata('alert', 'success');
		$this->redirectToGroup($group['sliders_id']);
	}

	public function editRecord($sliderID = 0, $editID = 0)
	{
		$group = $this->sliderGroup($sliderID);
		if (empty($group)) return $this->redirectToGroups();
		$editID = (int) $editID;
		$current = $this->slideRecord($editID, $group['sliders_id']);
		if (empty($current))
		{
			$this->session->set_flashdata('alert', 'error');
			return $this->redirectToGroup($group['sliders_id']);
		}
		if (!$this->validPost()) return $this->formFailure('Check the slide content, colours, and status.', $group['sliders_id'], $editID);
		$image = $this->saveUploadedImage('slide_image');
		if ($image['error'] !== '') return $this->formFailure('Slide image: '.$image['error'], $group['sliders_id'], $editID);
		$data = $this->postedRecordData();
		$data['order'] = (int) $current['order'];
		$data['updated_at'] = date('Y-m-d H:i:s');
		$imageColumn = 'image';
		if ($image['filename'] !== '') $data[$imageColumn] = $image['filename'];
		$this->db->trans_begin();
		$updated = $this->SqlModel->updateRecord($this->tblName, $data, array($this->pKey => $editID, 'slider_id' => (int) $group['sliders_id']));
		if (!$updated || $this->db->trans_status() === FALSE)
		{
			$this->db->trans_rollback();
			$this->deleteSliderImage($image['filename']);
			return $this->formFailure('The slider image could not be updated. Please try again.', $group['sliders_id'], $editID);
		}
		$this->db->trans_commit();
		if ($image['filename'] !== '') $this->deleteSliderImage(isset($current[$imageColumn]) ? $current[$imageColumn] : '');
		$this->session->set_flashdata('alert', 'editsuccess');
		$this->redirectToGroup($group['sliders_id']);
	}

	public function delete($deleteID = 0)
	{
		$record = $this->slideRecord((int) $deleteID);
		$sliderID = !empty($record) ? (int) $record['slider_id'] : 0;
		$this->session->set_flashdata('alert', !empty($record) && $this->deleteSlideRecords(array($record)) ? 'deletesuccess' : 'deleteerror');
		if ($sliderID > 0) return $this->redirectToGroup($sliderID);
		$this->redirectToGroups();
	}

	public function deleteall()
	{
		$sliderID = (int) $this->input->post('slider_id');
		$group = $this->sliderGroup($sliderID);
		$ids = array_values(array_unique(array_filter(array_map('intval', (array) $this->input->post('records')))));
		$records = array();
		if (!empty($group) && !empty($ids)) $records = $this->db->where('slider_id', $sliderID)->where_in($this->pKey, $ids)->get($this->tblName)->result_array();
		$deleted = count($records) === count($ids) && $this->deleteSlideRecords($records);
		$this->session->set_flashdata('alert', $deleted ? 'deletesuccess' : 'deleteerror');
		if (!empty($group)) return $this->redirectToGroup($sliderID);
		$this->redirectToGroups();
	}

	public function changestatus($id = 0, $status = 'Enable')
	{
		$this->output->set_content_type('application/json');
		$id = (int) $id;
		$record = $this->slideRecord($id);
		if (empty($record) || !in_array($status, array('Enable', 'Disable'), TRUE)
			|| !$this->SqlModel->updateRecord($this->tblName, array($this->tStatus => $status, 'updated_at' => date('Y-m-d H:i:s')), array($this->pKey => $id)))
		{
			return $this->output->set_output(json_encode(array('status' => 'false')));
		}
		return $this->output->set_output(json_encode(array('status' => 'true', 'id' => $id, 'currentStatus' => $status)));
	}

	public function duplicate($id = 0)
	{
		$data = $this->slideRecord((int) $id);
		if (empty($data)) return $this->redirectToGroups();
		unset($data[$this->pKey]);
		if (!empty($data['pre_heading'])) $data['pre_heading'] = $this->truncate($data['pre_heading'].' Duplicate', 255);
		elseif (!empty($data['heading'])) $data['heading'] = $this->truncate($data['heading'].' Duplicate', 255);
		$data['order'] = $this->nextOrder($data['slider_id']);
		$data['created_at'] = $data['updated_at'] = date('Y-m-d H:i:s');
		$this->db->trans_begin();
		$newId = $this->SqlModel->insertRecord($this->tblName, $data);
		if (!$newId || $this->db->trans_status() === FALSE)
		{
			$this->db->trans_rollback();
			$this->session->set_flashdata('alert', 'error');
			return $this->redirectToGroup($data['slider_id']);
		}
		$this->db->trans_commit();
		redirect(base_url('manage/'.$this->controller.'/control/'.(int) $data['slider_id'].'/edit/'.$newId));
	}

	public function sliderorder($sliderID = 0)
	{
		$this->output->set_content_type('application/json');
		if ($this->input->method(TRUE) !== 'POST') return $this->jsonResponse(405, FALSE, 'Slide sorting requires a POST request.');
		$group = $this->sliderGroup($sliderID);
		if (empty($group)) return $this->jsonResponse(404, FALSE, 'The slider collection was not found.');
		$ids = array_values(array_unique(array_filter(array_map('intval', (array) $this->input->post('records')))));
		if (empty($ids)) return $this->jsonResponse(422, FALSE, 'No slides were supplied.');
		$rows = $this->db->select($this->pKey)->where('slider_id', (int) $group['sliders_id'])->order_by('order', 'ASC')->order_by($this->pKey, 'ASC')->get($this->tblName)->result_array();
		$allIds = array_map('intval', array_column($rows, $this->pKey));
		if (count(array_intersect($ids, $allIds)) !== count($ids)) return $this->jsonResponse(422, FALSE, 'One or more slides do not belong to this collection.');
		$remaining = array_values(array_diff($allIds, $ids));
		$offset = max(0, min((int) $this->input->post('offset'), count($remaining)));
		array_splice($remaining, $offset, 0, $ids);
		$updates = array();
		foreach ($remaining as $index => $recordId) $updates[] = array($this->pKey => $recordId, 'order' => $index + 1, 'updated_at' => date('Y-m-d H:i:s'));
		$this->db->trans_begin();
		$updated = empty($updates) || $this->SqlModel->batchUpdate($this->tblName, $updates, $this->pKey);
		if (!$updated || $this->db->trans_status() === FALSE)
		{
			$this->db->trans_rollback();
			return $this->jsonResponse(500, FALSE, 'The slide order could not be saved.');
		}
		$this->db->trans_commit();
		return $this->jsonResponse(200, TRUE, 'The slide order was saved.');
	}

	private function render($view, $data)
	{
		$this->load->view('admin/header', $data);
		$this->load->view('admin/navigation');
		$this->load->view('admin/'.$view);
		$this->load->view('admin/footer');
	}

	private function postedRecordData()
	{
		$data = array();
		foreach ($this->textFields() as $column => $maxLength)
		{
			$value = trim((string) $this->input->post($column));
			$data[$column] = $maxLength !== NULL && $this->stringLength($value) > $maxLength ? mb_substr($value, 0, $maxLength, 'UTF-8') : $value;
		}
		foreach (array('button_1_icon', 'button_2_icon') as $field) $data[$field] = $this->normalizeFontAwesomeIconClass($this->input->post($field));
		foreach (array('button_1_url', 'button_2_url') as $field) $data[$field] = $this->cleanString($this->input->post($field));
		foreach (array('button_1_target', 'button_2_target') as $field) $data[$field] = $this->input->post($field) === '_blank' ? '_blank' : '_self';
		foreach ($this->colorFields() as $field)
		{
			$color = $this->normalizeRgba($this->input->post($field));
			$data[$field] = $color === '' ? NULL : $color;
		}
		$data['overlay'] = $this->input->post('overlay') === 'No' ? 'No' : 'Yes';
		$data[$this->tStatus] = $this->input->post($this->tStatus) === 'Disable' ? 'Disable' : 'Enable';
		return $data;
	}

	private function validPost()
	{
		if (!in_array($this->input->post('overlay'), array('Yes', 'No'), TRUE)) return FALSE;
		if (!in_array($this->input->post($this->tStatus), array('Enable', 'Disable'), TRUE)) return FALSE;
		foreach (array('pre_heading', 'heading', 'button_1_text', 'button_2_text', 'button_1_url', 'button_2_url') as $field) if ($this->stringLength(trim((string) $this->input->post($field))) > 255) return FALSE;
		foreach (array('button_1_icon', 'button_2_icon') as $field)
		{
			$value = trim((string) $this->input->post($field));
			if ($value !== '' && $this->normalizeFontAwesomeIconClass($value) === NULL) return FALSE;
		}
		foreach ($this->colorFields() as $field)
		{
			$value = trim((string) $this->input->post($field));
			if ($value !== '' && $this->normalizeRgba($value) === '') return FALSE;
		}
		return TRUE;
	}

	private function formValues(array $record, $postedData)
	{
		$values = array('overlay' => 'Yes', 'status' => 'Enable', 'order' => 0, 'button_1_target' => '_self', 'button_2_target' => '_self');
		foreach ($this->colorFields() as $field) $values[$field] = isset($record[$field]) ? $record[$field] : '';
		foreach (array('overlay', 'status', 'order') as $field) if (isset($record[$field])) $values[$field] = $record[$field];
		foreach (array('button_1_icon', 'button_1_url', 'button_1_target', 'button_2_icon', 'button_2_url', 'button_2_target') as $field)
		{
			if (isset($record[$field])) $values[$field] = $record[$field];
		}
		$values['current_image'] = isset($record['image']) ? basename((string) $record['image']) : '';
		if (is_array($postedData)) foreach (array_keys($values) as $field) if ($field !== 'current_image' && array_key_exists($field, $postedData)) $values[$field] = $postedData[$field];
		return $values;
	}

	private function formFailure($message, $sliderID, $editID = 0)
	{
		$posted = $this->input->post(NULL, FALSE);
		if (!is_array($posted)) $posted = array();
		$this->session->set_flashdata($this->controller.'_data', $posted);
		$this->session->set_flashdata('form_error', $message);
		redirect(base_url('manage/'.$this->controller.'/control/'.(int) $sliderID.($editID > 0 ? '/edit/'.(int) $editID.'' : '')));
	}

	private function sliderGroup($sliderID)
	{
		$sliderID = (int) $sliderID;
		return $sliderID > 0 ? $this->SqlModel->getSingleRecord('sliders', array('sliders_id' => $sliderID)) : array();
	}

	private function slideRecord($id, $sliderID = 0)
	{
		$where = array($this->pKey => (int) $id);
		if ((int) $sliderID > 0) $where['slider_id'] = (int) $sliderID;
		return (int) $id > 0 ? $this->SqlModel->getSingleRecord($this->tblName, $where) : array();
	}

	private function nextOrder($sliderID)
	{
		$this->db->select_max('order', 'max_order')->where('slider_id', (int) $sliderID);
		$row = $this->db->get($this->tblName)->row_array();
		return isset($row['max_order']) ? (int) $row['max_order'] + 1 : 1;
	}

	private function saveUploadedImage($field)
	{
		$result = array('filename' => '', 'error' => '');
		if (empty($_FILES[$field]['name'])) return $result;
		if (!isset($_FILES[$field]['error']) || $_FILES[$field]['error'] !== UPLOAD_ERR_OK)
		{
			$result['error'] = 'The upload did not complete successfully.';
			return $result;
		}
		if ((int) $_FILES[$field]['size'] > (int) UPLOAD_SIZE)
		{
			$result['error'] = 'The image must be '.UPLOAD_SIZE_MB.' MB or smaller.';
			return $result;
		}
		$uploadPath = FCPATH.$this->imageDirectory.DIRECTORY_SEPARATOR;
		if (!is_dir($uploadPath) && !mkdir($uploadPath, 0755, TRUE))
		{
			$result['error'] = 'The image storage directory could not be created.';
			return $result;
		}
		$this->load->library('upload');
		$this->upload->initialize(array('upload_path' => $uploadPath, 'allowed_types' => UPLOAD_IMAGE_MIMES, 'max_size' => UPLOAD_SIZE_MB * 1024, 'encrypt_name' => TRUE, 'remove_spaces' => TRUE));
		if (!$this->upload->do_upload($field))
		{
			$result['error'] = strip_tags($this->upload->display_errors('', ''));
			return $result;
		}
		$file = $this->upload->data();
		$result['filename'] = $file['file_name'];
		return $result;
	}

	private function deleteSlideRecords(array $records)
	{
		if (empty($records)) return FALSE;
		$ids = array();
		foreach ($records as $record) $ids[] = (int) $record[$this->pKey];
		$this->db->trans_begin();
		$this->db->where_in($this->pKey, $ids)->delete($this->tblName);
		if ($this->db->trans_status() === FALSE || $this->db->affected_rows() !== count($ids))
		{
			$this->db->trans_rollback();
			return FALSE;
		}
		$this->db->trans_commit();
		foreach ($records as $record)
		{
			$this->deleteSliderImage(isset($record['image']) ? $record['image'] : '');
		}
		return TRUE;
	}

	private function deleteSliderImage($filename)
	{
		if (!is_string($filename) || $filename === '' || basename($filename) !== $filename) return;
		$this->db->from($this->tblName)->where('image', $filename);
		if ($this->db->count_all_results() > 0) return;
		delete_uploaded_file(FCPATH.$this->imageDirectory, $filename);
	}

	/**
	 * Text columns edited on the form, with their maximum length (NULL for none).
	 */
	private function textFields()
	{
		return array(
			'pre_heading' => 255,
			'heading' => 255,
			'text' => NULL,
			'button_1_text' => 255,
			'button_2_text' => 255,
		);
	}

	private function colorFields()
	{
		return array('banner_pre_heading_color_1', 'banner_pre_heading_color_2', 'banner_heading_color_1', 'banner_heading_color_2', 'banner_text_color_1', 'banner_text_color_2');
	}

	private function cleanString($value)
	{
		$value = trim((string) $value);
		return $value === '' ? NULL : $this->truncate($value, 255);
	}

	private function normalizeFontAwesomeIconClass($value)
	{
		$value = trim((string) $value);
		if ($value === '') return NULL;
		return $this->stringLength($value) <= 255 && preg_match('/^fa-(solid|regular|brands) fa-[a-z0-9-]+$/', $value) ? $value : NULL;
	}

	private function normalizeRgba($value)
	{
		$value = trim((string) $value);
		if ($value === '') return '';
		if (preg_match('/^#([0-9a-f]{3,8})$/i', $value, $match))
		{
			$hex = $match[1];
			if (strlen($hex) === 3 || strlen($hex) === 4)
			{
				$expanded = '';
				foreach (str_split($hex) as $character) $expanded .= $character.$character;
				$hex = $expanded;
			}
			if (strlen($hex) !== 6 && strlen($hex) !== 8) return '';
			$alpha = strlen($hex) === 8 ? hexdec(substr($hex, 6, 2)) / 255 : 1;
			return sprintf('rgba(%d, %d, %d, %s)', hexdec(substr($hex, 0, 2)), hexdec(substr($hex, 2, 2)), hexdec(substr($hex, 4, 2)), round($alpha, 2));
		}
		if (!preg_match('/^rgba?\(([^)]+)\)$/i', $value, $match)) return '';
		$parts = preg_split('/[,\s\/]+/', trim($match[1]), -1, PREG_SPLIT_NO_EMPTY);
		if (count($parts) < 3 || count($parts) > 4) return '';
		$channels = array();
		for ($i = 0; $i < 3; $i++)
		{
			if (!is_numeric($parts[$i])) return '';
			$channels[] = max(0, min(255, (int) round((float) $parts[$i])));
		}
		if (isset($parts[3]) && !is_numeric($parts[3])) return '';
		$alpha = isset($parts[3]) ? max(0, min(1, (float) $parts[3])) : 1;
		return sprintf('rgba(%d, %d, %d, %s)', $channels[0], $channels[1], $channels[2], round($alpha, 2));
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

	private function jsonResponse($statusCode, $success, $message)
	{
		return $this->output->set_status_header($statusCode)->set_output(json_encode(array('success' => $success, 'message' => $message)));
	}

	private function redirectToGroup($sliderID)
	{
		redirect(base_url('manage/'.$this->controller.'/index/page/'.(int) $sliderID));
	}

	private function redirectToGroups()
	{
		redirect(base_url('manage/sliders'));
	}
}
