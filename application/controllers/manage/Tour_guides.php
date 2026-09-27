<?php defined('BASEPATH') OR exit('No direct script access allowed');

class Tour_guides extends CI_Controller {

	public $tblName = 'tour_guides';
	public $colPrefix = 'tour_guide_';
	public $pKey = 'tour_guide_id';
	public $moduleName = 'Tour Guides';
	public $moduleNameSingular = 'Tour Guide';
	public $moduleDesc = 'Manage guide profiles, contact details, license files, and availability status.';
	public $controller = 'tour-guides';
	public $per_page = 10;
	public $tStatus = 'tour_guide_status';
	public $listView = 'tourGuides';
	public $addEditView = 'addTourGuide';
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

	public function index($sortby = 'tour_guide_order', $order = 'ASC', $status = '-', $keywords = '-', $pg_no = '')
	{
		$allowedSorts = array($this->pKey, $this->colPrefix.'order', $this->colPrefix.'image', $this->colPrefix.'name', $this->colPrefix.'email', $this->tStatus, $this->colPrefix.'added', $this->colPrefix.'updated');
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
		$search = $keywords !== '-' ? array('cols' => $this->colPrefix.'name,'.$this->colPrefix.'email,'.$this->colPrefix.'license_number', 'value' => $keywords) : array();
		$baseUrl = base_url('manage/'.$this->controller.'/index/'.$sortby.'/'.$order.'/'.$status.'/'.rawurlencode($keywords));
		$totalRows = $this->SqlModel->countRecords($this->tblName, $where, $search);
		$uriSegment = 8;
		$offset = (int) $this->uri->segment($uriSegment, 0);

		$this->pagination->initialize(admin_pagination_config($baseUrl, $totalRows, $this->per_page, $uriSegment));
		$recordSort = $sortby === $this->colPrefix.'order' ? $sortby.','.$this->colPrefix.'name' : $sortby;
		$records = $this->SqlModel->getRecords('*', $this->tblName, $recordSort, $order, $where, $search, $this->per_page, $offset, FALSE);
		$recordIds = array();
		foreach ($records as $record) $recordIds[] = (int) $record[$this->pKey];
		$translationStatuses = empty($recordIds) ? array() : $this->manage_translation_service->statuses('tour_guides', $recordIds);
		$data = array(
			'alert' => $this->session->flashdata('alert'), 'page_title' => PROJECT_TITLE.' | '.$this->moduleName,
			'userdata' => $this->user_data, 'tourGuidesActive' => 1,
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
		if (is_array($postedData))
		{
			foreach (array($this->colPrefix.'phone', $this->colPrefix.'email', $this->colPrefix.'license_number', $this->colPrefix.'noti_lang', $this->tStatus) as $sharedColumn)
			{
				if (array_key_exists($sharedColumn, $postedData)) $viewRecord[$sharedColumn] = $postedData[$sharedColumn];
			}
		}
		$localizedValues = $this->manage_translation_service->localized_values('tour_guides', $record, $activeLocale);
		if (is_array($postedData))
		{
			foreach (array_keys($localizedValues) as $control) if (array_key_exists($control, $postedData)) $localizedValues[$control] = $postedData[$control];
		}
		$translationState = $isEdit ? $this->manage_translation_service->state('tour_guides', $editID) : NULL;
		$data = array(
			'tourGuidesActive' => 1, 'alert' => $isEdit ? 'edit' : '',
			'form_error' => $this->session->flashdata('form_error'), 'tbl_data' => $viewRecord,
			'page_title' => PROJECT_TITLE.' | '.($isEdit ? 'Edit' : 'Add').' '.$this->moduleNameSingular,
			'userdata' => $this->user_data,
			'localized_values' => $localizedValues, 'active_locale' => $activeLocale,
			'manage_locales' => $this->manage_translation_service->locales(),
			'translation_state' => $translationState, 'useManageTranslations' => TRUE, 'useSweetAlert' => TRUE,
			'lang' => $this->SqlModel->getRecords('lang_id,lang_name', 'tour_languages', 'lang_order', 'ASC', array('lang_status' => 'Enable')),
			'tours' => $this->SqlModel->getRecords('tour_id,tour_name', 'tours', 'tour_order', 'ASC', array('tour_type' => 'Tour', 'tour_status' => 'Enable')),
			'tour_guide_lang' => $isEdit ? $this->assignedIds('tour_guide_assigned_languages', 'lang_id', $editID) : array(),
			'tour_guide_tours' => $isEdit ? $this->assignedIds('tour_guide_assigned_tours', 'tour_id', $editID) : array(),
		);
		if (isset($postedData['tour_guide_lang'])) $data['tour_guide_lang'] = (array) $postedData['tour_guide_lang'];
		if (isset($postedData['tour_guide_tours'])) $data['tour_guide_tours'] = (array) $postedData['tour_guide_tours'];
		$this->render($this->addEditView, $data);
	}

	public function addRecord()
	{
		$activeLocale = 'en';
		$relations = $this->postedRelations();
		if (!$this->validPhoneInput()) return $this->formFailure('Enter a valid phone number with its country calling code.');
		if (!$this->validRequiredInput($relations, $activeLocale)) return $this->formFailure('Enter the required fields and select at least one language and tour.');

		$image = $this->saveUploadedFile('uploadfile', 'assets/frontend/images/tour-guides', UPLOAD_IMAGE_MIMES);
		if ($image['error'] !== '') return $this->formFailure('Profile image: '.$image['error']);
		if ($image['filename'] === '') return $this->formFailure('A profile image is required.');

		$license = $this->saveUploadedFile($this->colPrefix.'license_image', 'assets/frontend/images/tour-guide-licenses', UPLOAD_IMAGE_MIMES.'|'.UPLOAD_DOC_MIMES);
		if ($license['error'] !== '')
		{
			$this->deleteUploadedFile('assets/frontend/images/tour-guides', $image['filename'], $this->colPrefix.'image');
			return $this->formFailure('License file: '.$license['error']);
		}

		$data = $this->postedGuideData($activeLocale);
		$data[$this->colPrefix.'image'] = $image['filename'];
		if ($license['filename'] !== '') $data[$this->colPrefix.'license_image'] = $license['filename'];
		$this->load->library('admin_record_sorter');
		$data[$this->colPrefix.'order'] = $this->admin_record_sorter->nextOrder($this->controller);
		$data[$this->colPrefix.'added'] = date('Y-m-d H:i:s');
		$data[$this->colPrefix.'updated'] = date('Y-m-d H:i:s');

		$this->db->trans_begin();
		$id = $this->SqlModel->insertRecord($this->tblName, $data);
		if ($id) $this->syncAssignments($id, $relations);
		if (!$id || $this->db->trans_status() === FALSE)
		{
			$this->db->trans_rollback();
			$this->deleteUploadedFile('assets/frontend/images/tour-guides', $image['filename'], $this->colPrefix.'image');
			$this->deleteUploadedFile('assets/frontend/images/tour-guide-licenses', $license['filename'], $this->colPrefix.'license_image');
			return $this->formFailure('The record could not be saved. Please try again.');
		}
		$this->db->trans_commit();
		$this->queueTranslationSafely($id);
		$this->session->set_flashdata('alert', 'success');
		redirect(base_url('manage/'.$this->controller));
	}

	public function editRecord($editID = '')
	{
		$editID = (int) $editID;
		$activeLocale = $this->manage_translation_service->locale($this->input->post('active_locale', TRUE));
		$current = $this->SqlModel->getSingleRecord($this->tblName, array($this->pKey => $editID));
		$relations = $this->postedRelations();
		if (empty($current))
		{
			$this->session->set_flashdata('alert', 'error');
			redirect(base_url('manage/'.$this->controller));
			return;
		}
		if (!$this->validPhoneInput()) return $this->formFailure('Enter a valid phone number with its country calling code.', $editID, $activeLocale);
		if (!$this->validRequiredInput($relations, $activeLocale)) return $this->formFailure('Enter the required fields and select at least one language and tour.', $editID, $activeLocale);

		$image = $this->saveUploadedFile('uploadfile', 'assets/frontend/images/tour-guides', UPLOAD_IMAGE_MIMES);
		if ($image['error'] !== '') return $this->formFailure('Profile image: '.$image['error'], $editID, $activeLocale);
		if ($image['filename'] === '' && empty($current[$this->colPrefix.'image'])) return $this->formFailure('A profile image is required.', $editID, $activeLocale);
		$license = $this->saveUploadedFile($this->colPrefix.'license_image', 'assets/frontend/images/tour-guide-licenses', UPLOAD_IMAGE_MIMES.'|'.UPLOAD_DOC_MIMES);
		if ($license['error'] !== '')
		{
			$this->deleteUploadedFile('assets/frontend/images/tour-guides', $image['filename'], $this->colPrefix.'image');
			return $this->formFailure('License file: '.$license['error'], $editID, $activeLocale);
		}

		$data = $this->postedGuideData($activeLocale);
		$data[$this->colPrefix.'updated'] = date('Y-m-d H:i:s');
		if ($image['filename'] !== '') $data[$this->colPrefix.'image'] = $image['filename'];
		if ($license['filename'] !== '') $data[$this->colPrefix.'license_image'] = $license['filename'];

		$this->db->trans_begin();
		$updated = $this->SqlModel->updateRecord($this->tblName, $data, array($this->pKey => $editID));
		if ($updated) $this->syncAssignments($editID, $relations);
		if (!$updated || $this->db->trans_status() === FALSE)
		{
			$this->db->trans_rollback();
			$this->deleteUploadedFile('assets/frontend/images/tour-guides', $image['filename'], $this->colPrefix.'image');
			$this->deleteUploadedFile('assets/frontend/images/tour-guide-licenses', $license['filename'], $this->colPrefix.'license_image');
			return $this->formFailure('The record could not be updated. Please try again.', $editID, $activeLocale);
		}
		$this->db->trans_commit();
		if ($image['filename'] !== '') $this->deleteUploadedFile('assets/frontend/images/tour-guides', $current[$this->colPrefix.'image'], $this->colPrefix.'image');
		if ($license['filename'] !== '') $this->deleteUploadedFile('assets/frontend/images/tour-guide-licenses', $current[$this->colPrefix.'license_image'], $this->colPrefix.'license_image');
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
		if (empty($record) || !$this->deleteGuideRecord($record))
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
			if (!empty($record) && $this->deleteGuideRecord($record)) $deleted++;
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

	public function removefile($id = 0, $key = '')
	{
		$this->output->set_content_type('application/json');
		$id = (int) $id;
		$allowedColumns = array($this->colPrefix.'license_image' => 'assets/frontend/images/tour-guide-licenses');
		if ($id <= 0 || !isset($allowedColumns[$key]))
		{
			return $this->output->set_output(json_encode(array('success' => FALSE, 'message' => 'Invalid request.')));
		}
		$record = $this->SqlModel->getSingleRecord($this->tblName, array($this->pKey => $id));
		if (empty($record))
		{
			return $this->output->set_output(json_encode(array('success' => FALSE, 'message' => 'Record not found.')));
		}
		if (!$this->SqlModel->updateRecord($this->tblName, array($key => ''), array($this->pKey => $id)))
		{
			return $this->output->set_output(json_encode(array('success' => FALSE, 'message' => 'The record could not be updated.')));
		}
		$this->deleteUploadedFile($allowedColumns[$key], $record[$key], $key);
		return $this->output->set_output(json_encode(array('success' => TRUE, 'id' => $id, 'key' => $key)));
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
		$this->db->trans_begin();
		$newId = $this->SqlModel->insertRecord($this->tblName, $data);
		if ($newId) $this->syncAssignments($newId, array('languages' => $this->assignedIds('tour_guide_assigned_languages', 'lang_id', $sourceId), 'tours' => $this->assignedIds('tour_guide_assigned_tours', 'tour_id', $sourceId)));
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

	private function postedGuideData($locale)
	{
		$status = $this->input->post($this->tStatus);
		$notiLang = $this->input->post($this->colPrefix.'noti_lang');
		$data = array(
			$this->colPrefix.'phone' => trim((string) $this->input->post($this->colPrefix.'phone')),
			$this->colPrefix.'email' => trim((string) $this->input->post($this->colPrefix.'email')),
			$this->colPrefix.'license_number' => trim((string) $this->input->post($this->colPrefix.'license_number')),
			$this->colPrefix.'noti_lang' => in_array($notiLang, array('English', 'Arabic'), TRUE) ? $notiLang : 'Arabic',
			$this->tStatus => in_array($status, array('Enable', 'Disable'), TRUE) ? $status : 'Disable',
		);
		$post = $this->input->post(NULL, FALSE);
		return array_merge($data, $this->manage_translation_service->localized_post_data('tour_guides', $locale, is_array($post) ? $post : array()));
	}

	private function postedRelations()
	{
		return array(
			'languages' => array_unique(array_filter(array_map('intval', (array) $this->input->post($this->colPrefix.'lang')))),
			'tours' => array_unique(array_filter(array_map('intval', (array) $this->input->post($this->colPrefix.'tours')))),
		);
	}

	private function validRequiredInput($relations, $locale)
	{
		$post = $this->input->post(NULL, FALSE);
		return $this->manage_translation_service->required_localized_input_valid('tour_guides', $locale, is_array($post) ? $post : array())
			&& filter_var(trim((string) $this->input->post($this->colPrefix.'email')), FILTER_VALIDATE_EMAIL) !== FALSE
			&& trim((string) $this->input->post($this->colPrefix.'phone')) !== ''
			&& !empty($relations['languages']) && !empty($relations['tours']);
	}

	private function validPhoneInput()
	{
		$phone = trim((string) $this->input->post($this->colPrefix.'phone'));
		return preg_match('/^\+[1-9][0-9]{6,14}$/', $phone) === 1;
	}

	private function formFailure($message, $editID = 0, $locale = 'en')
	{
		$posted = $this->input->post(NULL, FALSE);
		if (is_array($posted)) $posted['active_locale'] = $this->manage_translation_service->locale($locale);
		$this->session->set_flashdata($this->controller.'_data', is_array($posted) ? $posted : array());
		$this->session->set_flashdata('form_error', $message);
		redirect(base_url('manage/'.$this->controller.'/control'.($editID ? '/edit/'.(int) $editID.'?lang='.$this->manage_translation_service->locale($locale) : '')));
	}

	private function assignedIds($table, $column, $guideId)
	{
		$records = $this->SqlModel->getRecords($column, $table, $column, 'ASC', array('tour_guide_id' => (int) $guideId));
		$ids = array();
		foreach ($records as $record) $ids[] = $record[$column];
		return $ids;
	}

	private function syncAssignments($guideId, $relations)
	{
		$this->SqlModel->deleteRecord('tour_guide_assigned_languages', array('tour_guide_id' => $guideId));
		$this->SqlModel->deleteRecord('tour_guide_assigned_tours', array('tour_guide_id' => $guideId));
		foreach ($relations['languages'] as $id) $this->SqlModel->insertRecord('tour_guide_assigned_languages', array('lang_id' => $id, 'tour_guide_id' => $guideId));
		foreach ($relations['tours'] as $id) $this->SqlModel->insertRecord('tour_guide_assigned_tours', array('tour_id' => $id, 'tour_guide_id' => $guideId));
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

	private function deleteGuideRecord($record)
	{
		$this->db->trans_begin();
		$this->SqlModel->deleteRecord('tour_guide_assigned_languages', array('tour_guide_id' => $record[$this->pKey]));
		$this->SqlModel->deleteRecord('tour_guide_assigned_tours', array('tour_guide_id' => $record[$this->pKey]));
		$this->manage_translation_service->delete_jobs('tour_guides', $record[$this->pKey]);
		$deleted = $this->SqlModel->deleteRecord($this->tblName, array($this->pKey => $record[$this->pKey]));
		if (!$deleted || $this->db->trans_status() === FALSE)
		{
			$this->db->trans_rollback();
			return FALSE;
		}
		$this->db->trans_commit();
		$this->deleteUploadedFile('assets/frontend/images/tour-guides', $record[$this->colPrefix.'image'], $this->colPrefix.'image');
		$this->deleteUploadedFile('assets/frontend/images/tour-guide-licenses', $record[$this->colPrefix.'license_image'], $this->colPrefix.'license_image');
		return TRUE;
	}

	private function queueTranslationSafely($guideId)
	{
		try
		{
			$this->manage_translation_service->queue('tour_guides', (int) $guideId);
		}
		catch (Throwable $exception)
		{
			log_message('error', 'Tour guide translation could not be queued for record '.(int) $guideId.'.');
		}
	}

	private function deleteUploadedFile($relativeDirectory, $filename, $column)
	{
		if (!is_string($filename) || $filename === '' || basename($filename) !== $filename) return;
		if ($this->SqlModel->countRecords($this->tblName, array($column => $filename)) > 0) return;
		delete_uploaded_file(FCPATH.trim($relativeDirectory, '/\\'), $filename);
	}
}
