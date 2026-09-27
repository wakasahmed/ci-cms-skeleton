<?php defined('BASEPATH') OR exit('No direct script access allowed');

class Vehicles extends CI_Controller {

	public $tblName = 'vehicles';
	public $colPrefix = 'vehicle_';
	public $pKey = 'vehicle_id';
	public $moduleName = 'Vehicles';
	public $moduleNameSingular = 'Vehicle';
	public $moduleDesc = 'Manage vehicle capacities, duration-based prices, localized details, and availability status.';
	public $controller = 'vehicles';
	public $per_page = 10;
	public $tStatus = 'vehicle_status';
	public $listView = 'vehicles';
	public $addEditView = 'addVehicle';
	public $user_data = array();

	public function __construct()
	{
		parent::__construct();
		$this->user_data = $this->SqlModel->authAdmin($this->session->userdata('admin_auth'), $this->session->userdata('admin_id'));
		if (empty($this->user_data)) redirect(base_url('manage/login'));
		$this->load->library('manage_translation_service');
	}

	public function index($sortby = 'vehicle_order', $order = 'ASC', $status = '-', $keywords = '-', $pg_no = '')
	{
		$allowedSorts = array(
			$this->pKey, $this->colPrefix.'order', $this->colPrefix.'title', $this->colPrefix.'name',
			$this->colPrefix.'name_ar', $this->colPrefix.'max_capacity',
			$this->colPrefix.'price_2', $this->colPrefix.'price_4', $this->colPrefix.'price_6',
			$this->colPrefix.'price_8', $this->tStatus, $this->colPrefix.'added', $this->colPrefix.'updated',
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
		$translationStatuses = empty($recordIds) ? array() : $this->manage_translation_service->statuses('vehicles', $recordIds);
		$data = array(
			'alert' => $this->session->flashdata('alert'), 'page_title' => PROJECT_TITLE.' | '.$this->moduleName,
			'userdata' => $this->user_data, 'vehiclesActive' => 1, 'total_rows' => $totalRows,
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
		$sharedColumns = array(
			$this->colPrefix.'title', $this->colPrefix.'max_capacity',
			$this->colPrefix.'price_2', $this->colPrefix.'price_4', $this->colPrefix.'price_6',
			$this->colPrefix.'price_8', $this->colPrefix.'meals_2', $this->colPrefix.'meals_4',
			$this->colPrefix.'meals_6', $this->colPrefix.'meals_8', $this->tStatus,
		);
		if (is_array($postedData)) foreach ($sharedColumns as $column) if (array_key_exists($column, $postedData)) $viewRecord[$column] = $postedData[$column];
		$localizedValues = $this->manage_translation_service->localized_values('vehicles', $record, $activeLocale);
		if (is_array($postedData)) foreach (array_keys($localizedValues) as $control) if (array_key_exists($control, $postedData)) $localizedValues[$control] = $postedData[$control];
		$translationState = $isEdit ? $this->manage_translation_service->state('vehicles', $editID) : NULL;
		$siteSettings = $this->SqlModel->getSingleRecord('site_settings', array('id' => 1));
		$currencyColumn = $activeLocale === 'ar' ? 'currency_unit_ar' : 'currency_unit';
		$currencyUnit = !empty($siteSettings[$currencyColumn]) ? $siteSettings[$currencyColumn] : 'SAR';
		$data = array(
			'vehiclesActive' => 1, 'alert' => $isEdit ? 'edit' : '', 'form_error' => $this->session->flashdata('form_error'),
			'tbl_data' => $viewRecord, 'page_title' => PROJECT_TITLE.' | '.($isEdit ? 'Edit' : 'Add').' '.$this->moduleNameSingular,
			'userdata' => $this->user_data, 'localized_values' => $localizedValues, 'active_locale' => $activeLocale,
			'manage_locales' => $this->manage_translation_service->locales(), 'translation_state' => $translationState,
			'currency_unit' => $currencyUnit,
			'useManageTranslations' => TRUE,
		);
		$this->render($this->addEditView, $data);
	}

	public function addRecord()
	{
		if (!$this->validPost('en')) return $this->formFailure('Enter all required fields, use whole numbers from 1 to 15,000, and make maximum guests greater than minimum guests.');

		$image = $this->saveUploadedFile('vehicle_image', 'assets/frontend/images/vehicles', UPLOAD_IMAGE_MIMES);
		if ($image['error'] !== '') return $this->formFailure('Vehicle image: '.$image['error']);
		if ($image['filename'] === '') return $this->formFailure('A vehicle image is required.');

		$data = $this->postedVehicleData('en');
		$data[$this->colPrefix.'image'] = $image['filename'];
		$data[$this->colPrefix.'name_ar'] = '';
		$data[$this->colPrefix.'details_ar'] = '';
		$this->load->library('admin_record_sorter');
		$data[$this->colPrefix.'order'] = $this->admin_record_sorter->nextOrder($this->controller);
		$data[$this->colPrefix.'added'] = date('Y-m-d H:i:s');
		$data[$this->colPrefix.'updated'] = date('Y-m-d H:i:s');
		$id = $this->SqlModel->insertRecord($this->tblName, $data);
		if (!$id)
		{
			$this->deleteUploadedFile('assets/frontend/images/vehicles', $image['filename'], $this->colPrefix.'image');
			return $this->formFailure('The vehicle could not be saved. Please try again.');
		}
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
		if (!$this->validPost($activeLocale)) return $this->formFailure('Enter all required fields, use whole numbers from 1 to 15,000, and make maximum guests greater than minimum guests.', $editID, $activeLocale);

		$image = $this->saveUploadedFile('vehicle_image', 'assets/frontend/images/vehicles', UPLOAD_IMAGE_MIMES);
		if ($image['error'] !== '') return $this->formFailure('Vehicle image: '.$image['error'], $editID, $activeLocale);
		if ($image['filename'] === '' && empty($current[$this->colPrefix.'image'])) return $this->formFailure('A vehicle image is required.', $editID, $activeLocale);

		$data = $this->postedVehicleData($activeLocale);
		$data[$this->colPrefix.'updated'] = date('Y-m-d H:i:s');
		if ($image['filename'] !== '') $data[$this->colPrefix.'image'] = $image['filename'];
		if ($activeLocale === 'en')
		{
			if (trim((string) $current[$this->colPrefix.'name']) !== $data[$this->colPrefix.'name']) $data[$this->colPrefix.'name_ar'] = '';
			if (trim((string) $current[$this->colPrefix.'details']) !== $data[$this->colPrefix.'details']) $data[$this->colPrefix.'details_ar'] = '';
		}
		if (!$this->SqlModel->updateRecord($this->tblName, $data, array($this->pKey => $editID)))
		{
			$this->deleteUploadedFile('assets/frontend/images/vehicles', $image['filename'], $this->colPrefix.'image');
			return $this->formFailure('The vehicle could not be updated. Please try again.', $editID, $activeLocale);
		}
		if ($image['filename'] !== '') $this->deleteUploadedFile('assets/frontend/images/vehicles', $current[$this->colPrefix.'image'], $this->colPrefix.'image');
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
		$result = empty($record) ? 'error' : $this->deleteVehicleRecords(array($id));
		$this->session->set_flashdata('alert', $result === 'success' ? 'deletesuccess' : ($result === 'blocked' ? 'deleteblocked' : 'deleteerror'));
		redirect(base_url('manage/'.$this->controller));
	}

	public function deleteall()
	{
		$ids = array_values(array_unique(array_filter(array_map('intval', (array) $this->input->post('records')))));
		$result = empty($ids) ? 'error' : $this->deleteVehicleRecords($ids);
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

	private function postedVehicleData($locale)
	{
		$status = $this->input->post($this->tStatus);
		$data = array(
			$this->colPrefix.'title' => $this->truncate(trim((string) $this->input->post($this->colPrefix.'title')), 255),
			$this->colPrefix.'max_capacity' => (int) $this->input->post($this->colPrefix.'max_capacity'),
			$this->colPrefix.'price_2' => (int) $this->input->post($this->colPrefix.'price_2'),
			$this->colPrefix.'price_4' => (int) $this->input->post($this->colPrefix.'price_4'),
			$this->colPrefix.'price_6' => (int) $this->input->post($this->colPrefix.'price_6'),
			$this->colPrefix.'price_8' => (int) $this->input->post($this->colPrefix.'price_8'),
			$this->colPrefix.'meals_2' => $this->validatedOptionalPrice($this->input->post($this->colPrefix.'meals_2')),
			$this->colPrefix.'meals_4' => $this->validatedOptionalPrice($this->input->post($this->colPrefix.'meals_4')),
			$this->colPrefix.'meals_6' => $this->validatedOptionalPrice($this->input->post($this->colPrefix.'meals_6')),
			$this->colPrefix.'meals_8' => $this->validatedOptionalPrice($this->input->post($this->colPrefix.'meals_8')),
			$this->tStatus => in_array($status, array('Enable', 'Disable'), TRUE) ? $status : 'Disable',
		);
		$post = $this->input->post(NULL, FALSE);
		return array_merge($data, $this->manage_translation_service->localized_post_data('vehicles', $locale, is_array($post) ? $post : array()));
	}

	private function validPost($locale)
	{
		$post = $this->input->post(NULL, FALSE);
		if (!$this->manage_translation_service->required_localized_input_valid('vehicles', $locale, is_array($post) ? $post : array())) return FALSE;
		if (trim((string) $this->input->post($this->colPrefix.'title')) === '') return FALSE;
		$numericFields = array('max_capacity', 'price_2', 'price_4', 'price_6', 'price_8');
		foreach ($numericFields as $field)
		{
			$value = $this->input->post($this->colPrefix.$field);
			if ($value === NULL || !ctype_digit((string) $value) || (int) $value < 1 || (int) $value > 15000) return FALSE;
		}
		$optionalNumericFields = array('meals_2', 'meals_4', 'meals_6', 'meals_8');
		foreach ($optionalNumericFields as $field)
		{
			$value = $this->input->post($this->colPrefix.$field);
			if ($value !== NULL && trim((string) $value) !== '' && (!ctype_digit((string) $value) || (int) $value > 15000)) return FALSE;
		}
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

	private function deleteVehicleRecords(array $ids)
	{
		$ids = array_values(array_unique(array_filter(array_map('intval', $ids))));
		if (empty($ids)) return 'error';
		$existing = $this->db->select($this->pKey.','.$this->colPrefix.'image')->where_in($this->pKey, $ids)->get($this->tblName)->result_array();
		if (count($existing) !== count($ids)) return 'error';
		if ($this->db->where_in('book_vehicle_id', $ids)->count_all_results('tour_bookings') > 0) return 'blocked';

		$this->db->trans_begin();
		foreach ($ids as $id) $this->manage_translation_service->delete_jobs('vehicles', $id);
		$this->db->where_in($this->pKey, $ids)->delete($this->tblName);
		$deleted = $this->db->affected_rows();
		if ($this->db->trans_status() === FALSE || $deleted !== count($ids))
		{
			$this->db->trans_rollback();
			return 'error';
		}
		$this->db->trans_commit();
		foreach ($existing as $record) $this->deleteUploadedFile('assets/frontend/images/vehicles', $record[$this->colPrefix.'image'], $this->colPrefix.'image');
		return 'success';
	}

	private function queueTranslationSafely($vehicleId)
	{
		try { $this->manage_translation_service->queue('vehicles', (int) $vehicleId); }
		catch (Throwable $exception) { log_message('error', 'Vehicle translation could not be queued for record '.(int) $vehicleId.'.'); }
	}

	private function saveUploadedFile($field, $relativeDirectory, $allowedTypes)
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
			$result['error'] = 'The file must be '.UPLOAD_SIZE_MB.' MB or smaller.';
			return $result;
		}
		$uploadPath = FCPATH.trim($relativeDirectory, '/\\').DIRECTORY_SEPARATOR;
		if (!is_dir($uploadPath) && !mkdir($uploadPath, 0755, TRUE))
		{
			$result['error'] = 'The upload directory could not be created.';
			return $result;
		}
		$this->load->library('upload');
		$this->upload->initialize(array(
			'upload_path' => $uploadPath, 'allowed_types' => $allowedTypes,
			'max_size' => UPLOAD_SIZE_MB * 1024, 'encrypt_name' => TRUE, 'remove_spaces' => TRUE,
		));
		if (!$this->upload->do_upload($field))
		{
			$result['error'] = strip_tags($this->upload->display_errors('', ''));
			return $result;
		}
		$file = $this->upload->data();
		$result['filename'] = $file['file_name'];
		return $result;
	}

	private function deleteUploadedFile($relativeDirectory, $filename, $column)
	{
		if (!is_string($filename) || $filename === '' || basename($filename) !== $filename) return;
		if ($this->SqlModel->countRecords($this->tblName, array($column => $filename)) > 0) return;
		delete_uploaded_file(FCPATH.trim($relativeDirectory, '/\\'), $filename);
	}

	private function validatedOptionalPrice($value)
	{
		if ($value === NULL || trim((string) $value) === '' || !ctype_digit((string) $value)) return 0;
		return (int) $value;
	}

	private function truncate($value, $maxLength)
	{
		if (function_exists('mb_substr')) return mb_substr((string) $value, 0, (int) $maxLength, 'UTF-8');
		return substr((string) $value, 0, (int) $maxLength);
	}
}
