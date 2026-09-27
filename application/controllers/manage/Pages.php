<?php defined('BASEPATH') OR exit('No direct script access allowed');

class Pages extends CI_Controller {

	public $tblName = 'pages';
	public $colPrefix = 'page_';
	public $pKey = 'page_id';
	public $moduleName = 'Web Pages';
	public $moduleNameSingular = 'Web Page';
	public $moduleDesc = 'Manage website pages, localized content, publishing, SEO, and banners.';
	public $controller = 'pages';
	public $per_page = 10;
	public $tStatus = 'page_status';
	public $listView = 'webPages';
	public $addEditView = 'addPage';
	public $user_data = array();

	private $translationModule = 'pages';
	private $imageDirectory = 'assets/frontend/images/pages';
	private $protectedPageMaxId = 20;

	public function __construct()
	{
		parent::__construct();
		$this->user_data = $this->SqlModel->authAdmin($this->session->userdata('admin_auth'), $this->session->userdata('admin_id'));
		if (empty($this->user_data)) redirect(base_url('manage/login'));
		$this->load->library('content_section_service');
		$this->load->library('manage_translation_service');
	}

	public function index($parentID = 0, $sortby = 'page_order', $order = 'ASC', $status = '-', $keywords = '-', $pgNo = '')
	{
		$parentID = max(0, (int) $parentID);
		$parent = $parentID > 0 ? $this->pageRecord($parentID) : array();
		if ($parentID > 0 && empty($parent)) return $this->redirectToListing(0);
		$allowedSorts = array($this->pKey, 'page_order', 'page_name', $this->tStatus, 'page_added', 'created_at', 'updated_at');
		$sortby = in_array($sortby, $allowedSorts, TRUE) ? $sortby : 'page_order';
		$order = strtoupper($order) === 'DESC' ? 'DESC' : 'ASC';
		$status = in_array($status, array('Published', 'Un-Published'), TRUE) ? $status : '-';
		$allowedPerPage = array_merge(array(0), range(10, 100, 10));
		$requestedPerPage = $this->input->get('per_page');
		if ($requestedPerPage !== NULL && ctype_digit((string) $requestedPerPage) && in_array((int) $requestedPerPage, $allowedPerPage, TRUE)) $this->session->set_userdata('per_page', (int) $requestedPerPage);
		if ($this->session->userdata('per_page') !== NULL && in_array((int) $this->session->userdata('per_page'), $allowedPerPage, TRUE)) $this->per_page = (int) $this->session->userdata('per_page');
		$keywords = urldecode((string) $keywords);
		$where = array('page_parent_id' => $parentID);
		if ($status !== '-') $where[$this->tStatus] = $status;
		$search = $keywords !== '-' ? array('cols' => 'page_name,page_title,menu_name,page_slug', 'value' => $keywords) : array();
		$baseUrl = base_url('manage/'.$this->controller.'/index/'.$parentID.'/'.$sortby.'/'.$order.'/'.$status.'/'.urlencode($keywords));
		$totalRows = $this->SqlModel->countRecords($this->tblName, $where, $search);
		$uriSegment = 9;
		$offset = max(0, (int) ($pgNo !== '' ? $pgNo : $this->uri->segment($uriSegment, 0)));
		$this->pagination->initialize(admin_pagination_config($baseUrl, $totalRows, $this->per_page, $uriSegment));
		$recordSort = $sortby === 'page_order' ? 'page_order,page_name' : $sortby;
		$records = $this->SqlModel->getRecords('pages.*', $this->tblName, $recordSort, $order, $where, $search, $this->per_page, $offset, FALSE);
		$recordIds = array();
		foreach ($records as $record) $recordIds[] = (int) $record[$this->pKey];
		$data = array(
			'alert' => $this->session->flashdata('alert'), 'page_title' => PROJECT_TITLE.' | '.$this->moduleName,
			'userdata' => $this->user_data, 'pagesActive' => 1, 'parent_id' => $parentID,
			'parent_name' => !empty($parent) ? $this->displayText($parent['page_name']) : '',
			'total_rows' => $totalRows, 'per_page' => $this->per_page, 'records' => $records,
			'translation_statuses' => empty($recordIds) ? array() : $this->manage_translation_service->statuses($this->translationModule, $recordIds),
			'paginate' => $this->pagination->create_links(), 'sortby' => $sortby,
			'order' => $order === 'ASC' ? 'DESC' : 'ASC', 'page_numb' => $offset,
			'status' => $status, 'keywords' => $keywords, 'protected_page_max_id' => $this->protectedPageMaxId,
			'sectionPageIds' => $this->content_section_service->configured_page_ids(),
			'canUpdateSections' => $this->content_section_service->can('web_pages.update', $this->user_data),
			'useManageTranslations' => TRUE,
		);
		$this->render($this->listView, $data);
	}

	public function control($parentID = 0, $editID = 0)
	{
		$parentID = max(0, (int) $parentID);
		$parent = $parentID > 0 ? $this->pageRecord($parentID) : array();
		if ($parentID > 0 && empty($parent)) return $this->redirectToListing(0);
		$editID = (int) $editID;
		$isEdit = $editID > 0;
		$record = $isEdit ? $this->pageRecord($editID, $parentID) : array();
		if ($isEdit && empty($record))
		{
			$this->session->set_flashdata('alert', 'error');
			return $this->redirectToListing($parentID);
		}
		$posted = $this->session->flashdata($this->controller.'_data');
		$requestedLocale = is_array($posted) && isset($posted['active_locale']) ? $posted['active_locale'] : $this->input->get('lang', TRUE);
		$activeLocale = $isEdit ? $this->manage_translation_service->locale($requestedLocale) : 'en';
		$localizedValues = $this->manage_translation_service->localized_values($this->translationModule, $record, $activeLocale);
		if (is_array($posted)) foreach (array_keys($localizedValues) as $control) if (array_key_exists($control, $posted)) $localizedValues[$control] = $posted[$control];
		$this->configureEditor($activeLocale);
		$data = array(
			'pagesActive' => 1, 'alert' => $isEdit ? 'edit' : '', 'form_error' => $this->session->flashdata('form_error'),
			'page_title' => PROJECT_TITLE.' | '.($isEdit ? 'Edit' : 'Add').' '.$this->moduleNameSingular, 'userdata' => $this->user_data,
			'parent_id' => $parentID, 'parent_name' => !empty($parent) ? $this->displayText($parent['page_name']) : '',
			'tbl_data' => $record, 'form_values' => $this->formValues($record, $activeLocale, $posted),
			'localized_values' => $localizedValues, 'active_locale' => $activeLocale,
			'manage_locales' => $this->manage_translation_service->locales(),
			'translation_state' => $isEdit ? $this->manage_translation_service->state($this->translationModule, $editID) : NULL,
			'sliders' => $this->SqlModel->getRecords('sliders_id,sliders_title', 'sliders', 'sliders_title', 'ASC'),
			'next_order' => $this->nextOrder($parentID), 'useColorPicker' => TRUE, 'useManageTranslations' => TRUE, 'useSweetAlert' => TRUE,
		);
		$this->render($this->addEditView, $data);
	}

	public function addRecord($parentID = 0)
	{
		$parentID = max(0, (int) $parentID);
		if ($parentID > 0 && empty($this->pageRecord($parentID))) return $this->redirectToListing(0);
		if (!$this->validPost('en')) return $this->formFailure('Enter a page name and valid slug, then check the publishing and banner settings.', $parentID);
		$uploads = $this->savePageUploads('en');
		if ($uploads['error'] !== '') return $this->formFailure($uploads['error'], $parentID);
		$data = $this->postedPageData('en');
		$data['page_parent_id'] = $parentID;
		$data['page_slug'] = $this->uniqueSlug('page_slug', $this->normalizeSlug($this->input->post('page_slug')));
		$data['page_name_ar'] = '';
		$data['page_order'] = $this->nextOrder($parentID);
		$data['page_added'] = $data['page_updated'] = date('Y-m-d H:i:s');
		$data['created_at'] = $data['updated_at'] = date('Y-m-d H:i:s');
		$data['page_year'] = (int) date('Y');
		$data['page_month'] = (int) date('m');
		$data['page_month_year'] = date('F Y');
		foreach ($uploads['files'] as $column => $filename) if ($filename !== '') $data[$column] = $filename;
		$this->db->trans_begin();
		$id = $this->SqlModel->insertRecord($this->tblName, $data);
		if (!$id || $this->db->trans_status() === FALSE)
		{
			$this->db->trans_rollback();
			$this->cleanupNewUploads($uploads['files']);
			return $this->formFailure('The web page could not be saved. Please try again.', $parentID);
		}
		$this->db->trans_commit();
		$this->queueTranslationSafely($id);
		$this->session->set_flashdata('alert', 'success');
		$this->redirectToListing($parentID);
	}

	public function editRecord($parentID = 0, $editID = 0)
	{
		$parentID = max(0, (int) $parentID);
		$editID = (int) $editID;
		$current = $this->pageRecord($editID, $parentID);
		if (empty($current))
		{
			$this->session->set_flashdata('alert', 'error');
			return $this->redirectToListing($parentID);
		}
		$activeLocale = $this->manage_translation_service->locale($this->input->post('active_locale', TRUE));
		if (!$this->validPost($activeLocale)) return $this->formFailure('Enter a page name and valid slug, then check the publishing and banner settings.', $parentID, $editID, $activeLocale);
		$uploads = $this->savePageUploads($activeLocale);
		if ($uploads['error'] !== '') return $this->formFailure($uploads['error'], $parentID, $editID, $activeLocale);
		$data = $this->postedPageData($activeLocale);
		$slugColumn = $activeLocale === 'ar' ? 'page_slug_ar' : 'page_slug';
		$data[$slugColumn] = $this->uniqueSlug($slugColumn, $this->normalizeSlug($this->input->post('page_slug')), $editID);
		$data['page_updated'] = $data['updated_at'] = date('Y-m-d H:i:s');
		foreach ($uploads['files'] as $column => $filename) if ($filename !== '') $data[$column] = $filename;
		$this->db->trans_begin();
		$updated = $this->SqlModel->updateRecord($this->tblName, $data, array($this->pKey => $editID, 'page_parent_id' => $parentID));
		if (!$updated || $this->db->trans_status() === FALSE)
		{
			$this->db->trans_rollback();
			$this->cleanupNewUploads($uploads['files']);
			return $this->formFailure('The web page could not be updated. Please try again.', $parentID, $editID, $activeLocale);
		}
		$this->db->trans_commit();
		foreach ($uploads['files'] as $column => $filename) if ($filename !== '') $this->deletePageImage(isset($current[$column]) ? $current[$column] : '');
		if ($activeLocale === 'en') $this->queueTranslationSafely($editID);
		$this->session->set_flashdata('alert', 'editsuccess');
		$redirectLocale = $this->input->post('redirect_lang', TRUE);
		if (is_string($redirectLocale) && $redirectLocale !== '' && $this->manage_translation_service->locale($redirectLocale) === $redirectLocale)
		{
			redirect(base_url('manage/'.$this->controller.'/control/'.$parentID.'/'.$editID.'?lang='.$redirectLocale));
			return;
		}
		$this->redirectToListing($parentID);
	}

	public function delete($deleteID = 0)
	{
		$record = $this->pageRecord((int) $deleteID);
		$parentID = !empty($record) ? (int) $record['page_parent_id'] : 0;
		$deleted = !empty($record) && (int) $record[$this->pKey] > $this->protectedPageMaxId && $this->deletePageTree(array((int) $record[$this->pKey]));
		$this->session->set_flashdata('alert', $deleted ? 'deletesuccess' : 'deleteerror');
		$this->redirectToListing($parentID);
	}

	public function deleteall()
	{
		$parentID = max(0, (int) $this->input->post('parent_id'));
		$ids = array_values(array_unique(array_filter(array_map('intval', (array) $this->input->post('records')))));
		$records = empty($ids) ? array() : $this->db->where('page_parent_id', $parentID)->where_in($this->pKey, $ids)->get($this->tblName)->result_array();
		$allowed = !empty($ids) && count($records) === count($ids);
		foreach ($ids as $id) if ($id <= $this->protectedPageMaxId) $allowed = FALSE;
		$this->session->set_flashdata('alert', $allowed && $this->deletePageTree($ids) ? 'deletesuccess' : 'deleteerror');
		$this->redirectToListing($parentID);
	}

	public function changestatus($id = 0, $status = 'Published')
	{
		$this->output->set_content_type('application/json');
		$id = (int) $id;
		$record = $this->pageRecord($id);
		if ($id <= $this->protectedPageMaxId || empty($record) || !in_array($status, array('Published', 'Un-Published'), TRUE)
			|| !$this->SqlModel->updateRecord($this->tblName, array($this->tStatus => $status, 'page_updated' => date('Y-m-d H:i:s'), 'updated_at' => date('Y-m-d H:i:s')), array($this->pKey => $id)))
		{
			return $this->output->set_output(json_encode(array('status' => 'false')));
		}
		return $this->output->set_output(json_encode(array('status' => 'true', 'id' => $id, 'currentStatus' => $status)));
	}

	public function duplicate($id = 0)
	{
		$data = $this->pageRecord((int) $id);
		if (empty($data))
		{
			$this->session->set_flashdata('alert', 'error');
			return $this->redirectToListing(0);
		}
		unset($data[$this->pKey]);
		$data['page_name'] = $this->truncate($this->displayText($data['page_name']).' Duplicate', 255);
		$data['page_slug'] = $this->uniqueSlug('page_slug', $this->normalizeSlug($data['page_slug'].'-copy'));
		if (trim((string) $data['page_slug_ar']) !== '') $data['page_slug_ar'] = $this->uniqueSlug('page_slug_ar', $this->normalizeSlug($data['page_slug_ar'].'-copy'));
		$data['page_order'] = $this->nextOrder($data['page_parent_id']);
		$data['page_added'] = $data['page_updated'] = date('Y-m-d H:i:s');
		$data['created_at'] = $data['updated_at'] = date('Y-m-d H:i:s');
		$this->db->trans_begin();
		$newID = $this->SqlModel->insertRecord($this->tblName, $data);
		if (!$newID || $this->db->trans_status() === FALSE)
		{
			$this->db->trans_rollback();
			$this->session->set_flashdata('alert', 'error');
			return $this->redirectToListing($data['page_parent_id']);
		}
		$this->db->trans_commit();
		$this->queueTranslationSafely($newID);
		redirect(base_url('manage/'.$this->controller.'/control/'.(int) $data['page_parent_id'].'/'.$newID));
	}

	public function pageorder($parentID = 0)
	{
		$this->output->set_content_type('application/json');
		if ($this->input->method(TRUE) !== 'POST') return $this->jsonResponse(405, FALSE, 'Page sorting requires a POST request.');
		$parentID = max(0, (int) $parentID);
		if ($parentID > 0 && empty($this->pageRecord($parentID))) return $this->jsonResponse(404, FALSE, 'The parent page was not found.');
		$ids = array_values(array_unique(array_filter(array_map('intval', (array) $this->input->post('records')))));
		if (empty($ids)) return $this->jsonResponse(422, FALSE, 'No pages were supplied.');
		$rows = $this->db->select($this->pKey)->where('page_parent_id', $parentID)->order_by('page_order', 'ASC')->order_by('page_name', 'ASC')->order_by($this->pKey, 'ASC')->get($this->tblName)->result_array();
		$allIDs = array_map('intval', array_column($rows, $this->pKey));
		if (count(array_intersect($ids, $allIDs)) !== count($ids)) return $this->jsonResponse(422, FALSE, 'One or more pages do not belong to this listing.');
		$remaining = array_values(array_diff($allIDs, $ids));
		$offset = max(0, min((int) $this->input->post('offset'), count($remaining)));
		array_splice($remaining, $offset, 0, $ids);
		$updates = array();
		foreach ($remaining as $index => $pageID) $updates[] = array($this->pKey => $pageID, 'page_order' => $index + 1, 'page_updated' => date('Y-m-d H:i:s'), 'updated_at' => date('Y-m-d H:i:s'));
		$this->db->trans_begin();
		$updated = empty($updates) || $this->SqlModel->batchUpdate($this->tblName, $updates, $this->pKey);
		if (!$updated || $this->db->trans_status() === FALSE)
		{
			$this->db->trans_rollback();
			return $this->jsonResponse(500, FALSE, 'The page order could not be saved.');
		}
		$this->db->trans_commit();
		return $this->jsonResponse(200, TRUE, 'The page order was saved.');
	}

	public function removefile($id = 0, $key = '')
	{
		$this->output->set_content_type('application/json');
		$id = (int) $id;
		$allowed = array('og_image', 'og_image_ar', 'banner_background', 'banner_background_ar');
		$record = in_array($key, $allowed, TRUE) ? $this->pageRecord($id) : array();
		if (empty($record))
		{
			return $this->output->set_output(json_encode(array('success' => FALSE, 'message' => 'Invalid request.')));
		}
		if (!$this->SqlModel->updateRecord($this->tblName, array($key => '', 'page_updated' => date('Y-m-d H:i:s'), 'updated_at' => date('Y-m-d H:i:s')), array($this->pKey => $id)))
		{
			return $this->output->set_output(json_encode(array('success' => FALSE, 'message' => 'The record could not be updated.')));
		}
		$this->deletePageImage(isset($record[$key]) ? $record[$key] : '');
		return $this->output->set_output(json_encode(array('success' => TRUE, 'id' => $id, 'key' => $key)));
	}

	private function render($view, $data)
	{
		$this->load->view('admin/header', $data);
		$this->load->view('admin/navigation');
		$this->load->view('admin/'.$view);
		$this->load->view('admin/footer');
	}

	private function configureEditor($locale)
	{
		$this->load->library('ckeditor');
		$this->load->library('ckfinder');
		$this->ckeditor->basePath = base_url().'assets/ckeditor/';
		$this->ckeditor->config['removePlugins'] = 'save, preview, newpage, forms, flash';
		$this->ckeditor->config['height'] = '340px';
		$this->ckeditor->config['contentsLangDirection'] = $locale === 'ar' ? 'rtl' : 'ltr';
		$this->ckeditor->textareaAttributes = array('id' => 'localized_page_text', 'rows' => 8, 'cols' => 60, 'data-translation-field' => 'localized_page_text', 'dir' => $locale === 'ar' ? 'rtl' : 'ltr');
		$this->ckfinder->SetupCKEditor($this->ckeditor, '../../../../assets/ckfinder/');
	}

	private function postedPageData($locale)
	{
		$post = $this->input->post(NULL, FALSE);
		$post = is_array($post) ? $post : array();
		$data = $this->manage_translation_service->localized_post_data($this->translationModule, $locale, $post);
		foreach (array_keys($data) as $column) if ($column !== 'page_text' && $column !== 'page_text_ar') $data[$column] = $this->cleanPlainText($data[$column], $this->plainTextLimit($column));
		foreach ($this->colorFields() as $field)
		{
			$color = $this->normalizeRgba($this->input->post($field));
			$data[$field] = $color === '' ? NULL : $color;
		}
		$data['robots_index'] = $this->input->post('robots_index') === '1' ? 1 : 0;
		$data['robots_follow'] = $this->input->post('robots_follow') === '1' ? 1 : 0;
		$data['show_top_banner'] = $this->input->post('show_top_banner') === '1' ? 1 : 0;
		$data['banner_overlay'] = $this->input->post('banner_overlay') === 'No' ? 'No' : 'Yes';
		$data[$this->tStatus] = $this->input->post($this->tStatus) === 'Un-Published' ? 'Un-Published' : 'Published';
		$sliderID = ctype_digit((string) $this->input->post('page_slider')) ? (int) $this->input->post('page_slider') : 0;
		$data['page_slider'] = $sliderID > 0 && $this->SqlModel->countRecords('sliders', array('sliders_id' => $sliderID)) > 0 ? $sliderID : 0;
		return $data;
	}

	private function validPost($locale)
	{
		$post = $this->input->post(NULL, FALSE);
		$post = is_array($post) ? $post : array();
		if (!$this->manage_translation_service->required_localized_input_valid($this->translationModule, $locale, $post)) return FALSE;
		if ($this->normalizeSlug($this->input->post('page_slug')) === '' || $this->stringLength($this->normalizeSlug($this->input->post('page_slug'))) > 255) return FALSE;
		if (!in_array($this->input->post($this->tStatus), array('Published', 'Un-Published'), TRUE)) return FALSE;
		if (!in_array($this->input->post('banner_overlay'), array('Yes', 'No'), TRUE)) return FALSE;
		foreach (array('localized_name', 'localized_page_title', 'localized_og_title', 'localized_banner_title', 'localized_banner_heading') as $field) if ($this->stringLength(trim((string) $this->input->post($field))) > 255) return FALSE;
		if ($this->stringLength(trim((string) $this->input->post('localized_menu_name'))) > 100) return FALSE;
		$slider = $this->input->post('page_slider');
		if ($slider !== NULL && $slider !== '' && (!ctype_digit((string) $slider) || $this->SqlModel->countRecords('sliders', array('sliders_id' => (int) $slider)) < 1)) return FALSE;
		foreach ($this->colorFields() as $field)
		{
			$value = trim((string) $this->input->post($field));
			if ($value !== '' && $this->normalizeRgba($value) === '') return FALSE;
		}
		return TRUE;
	}

	private function formValues(array $record, $locale, $posted)
	{
		$values = array('page_slug' => '', 'page_status' => 'Published', 'page_slider' => 0, 'robots_index' => 1, 'robots_follow' => 1, 'show_top_banner' => 0, 'banner_overlay' => 'Yes', 'page_order' => 0, 'current_og_image' => '', 'current_banner_background' => '');
		foreach ($this->colorFields() as $field) $values[$field] = isset($record[$field]) ? $record[$field] : '';
		foreach (array('page_status', 'page_slider', 'robots_index', 'robots_follow', 'show_top_banner', 'banner_overlay', 'page_order') as $field) if (isset($record[$field])) $values[$field] = $record[$field];
		$suffix = $locale === 'ar' ? '_ar' : '';
		if (isset($record['page_slug'.$suffix])) $values['page_slug'] = $record['page_slug'.$suffix];
		$values['current_og_image'] = isset($record['og_image'.$suffix]) ? basename((string) $record['og_image'.$suffix]) : '';
		$values['current_banner_background'] = isset($record['banner_background'.$suffix]) ? basename((string) $record['banner_background'.$suffix]) : '';
		if (is_array($posted)) foreach (array_keys($values) as $field) if (strpos($field, 'current_') !== 0 && array_key_exists($field, $posted)) $values[$field] = $posted[$field];
		return $values;
	}

	private function formFailure($message, $parentID, $editID = 0, $locale = 'en')
	{
		$posted = $this->input->post(NULL, FALSE);
		if (!is_array($posted)) $posted = array();
		$posted['active_locale'] = $this->manage_translation_service->locale($locale);
		$this->session->set_flashdata($this->controller.'_data', $posted);
		$this->session->set_flashdata('form_error', $message);
		redirect(base_url('manage/'.$this->controller.'/control/'.(int) $parentID.($editID > 0 ? '/'.(int) $editID.'?lang='.$this->manage_translation_service->locale($locale) : '')));
	}

	private function savePageUploads($locale)
	{
		$suffix = $locale === 'ar' ? '_ar' : '';
		$fields = array('og_image_upload' => 'og_image'.$suffix, 'banner_background_upload' => 'banner_background'.$suffix);
		$result = array('files' => array(), 'error' => '');
		foreach ($fields as $field => $column)
		{
			$upload = $this->saveUploadedImage($field);
			$result['files'][$column] = $upload['filename'];
			if ($upload['error'] !== '')
			{
				$result['error'] = ($field === 'og_image_upload' ? 'Open Graph image: ' : 'Banner background: ').$upload['error'];
				$this->cleanupNewUploads($result['files']);
				break;
			}
		}
		return $result;
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

	private function cleanupNewUploads(array $files)
	{
		foreach ($files as $filename) if ($filename !== '') $this->deletePageImage($filename);
	}

	private function deletePageTree(array $rootIDs)
	{
		$ids = $this->pageTreeIDs($rootIDs);
		if (empty($ids)) return FALSE;
		foreach ($ids as $id) if ($id <= $this->protectedPageMaxId) return FALSE;
		$records = $this->db->where_in($this->pKey, $ids)->get($this->tblName)->result_array();
		if (count($records) !== count($ids)) return FALSE;
		$this->db->trans_begin();
		foreach ($ids as $id) $this->manage_translation_service->delete_jobs($this->translationModule, $id);
		$this->db->where_in($this->pKey, $ids)->delete($this->tblName);
		if ($this->db->trans_status() === FALSE || $this->db->affected_rows() !== count($ids))
		{
			$this->db->trans_rollback();
			return FALSE;
		}
		$this->db->trans_commit();
		foreach ($records as $record) foreach ($this->imageColumns() as $column) $this->deletePageImage(isset($record[$column]) ? $record[$column] : '');
		return TRUE;
	}

	private function pageTreeIDs(array $rootIDs)
	{
		$ids = array_values(array_unique(array_filter(array_map('intval', $rootIDs))));
		$frontier = $ids;
		while (!empty($frontier))
		{
			$rows = $this->db->select($this->pKey)->where_in('page_parent_id', $frontier)->get($this->tblName)->result_array();
			$frontier = array();
			foreach ($rows as $row)
			{
				$id = (int) $row[$this->pKey];
				if (!in_array($id, $ids, TRUE)) { $ids[] = $id; $frontier[] = $id; }
			}
		}
		return $ids;
	}

	private function deletePageImage($filename)
	{
		if (!is_string($filename) || $filename === '' || basename($filename) !== $filename) return;
		$this->db->from($this->tblName)->group_start();
		foreach ($this->imageColumns() as $index => $column)
		{
			if ($index === 0) $this->db->where($column, $filename);
			else $this->db->or_where($column, $filename);
		}
		$this->db->group_end();
		if ($this->db->count_all_results() > 0) return;
		delete_uploaded_file(FCPATH.$this->imageDirectory, $filename);
	}

	private function pageRecord($id, $parentID = NULL)
	{
		$id = (int) $id;
		if ($id < 1) return array();
		$where = array($this->pKey => $id);
		if ($parentID !== NULL) $where['page_parent_id'] = (int) $parentID;
		return $this->SqlModel->getSingleRecord($this->tblName, $where);
	}

	private function nextOrder($parentID)
	{
		$this->db->select_max('page_order', 'max_order')->where('page_parent_id', (int) $parentID);
		$row = $this->db->get($this->tblName)->row_array();
		return isset($row['max_order']) ? (int) $row['max_order'] + 1 : 1;
	}

	private function uniqueSlug($column, $slug, $excludeID = 0)
	{
		$base = $slug === '' ? 'page' : $slug;
		$candidate = $base;
		$suffix = 2;
		while (TRUE)
		{
			// The public site finds a page by either slug column, so a slug another page already uses in
			// English or Arabic would make two addresses ambiguous.
			$this->db->from($this->tblName)->group_start()->where('page_slug', $candidate)->or_where('page_slug_ar', $candidate)->group_end();
			if ((int) $excludeID > 0) $this->db->where($this->pKey.' !=', (int) $excludeID);
			if ($this->db->count_all_results() === 0) return $candidate;
			$candidate = $this->truncate($base.'-'.$suffix++, 255);
		}
	}

	private function normalizeSlug($value)
	{
		$value = html_entity_decode(trim((string) $value), ENT_QUOTES, 'UTF-8');
		if (function_exists('mb_strtolower')) $value = mb_strtolower($value, 'UTF-8'); else $value = strtolower($value);
		$value = preg_replace('/[\s_]+/u', '-', $value);
		$value = preg_replace('/[^\p{L}\p{N}-]+/u', '', $value);
		$value = preg_replace('/-+/', '-', $value);
		return trim($this->truncate($value, 255), '-');
	}

	private function cleanPlainText($value, $maxLength = NULL)
	{
		$value = trim(strip_tags(html_entity_decode((string) $value, ENT_QUOTES, 'UTF-8')));
		return $maxLength === NULL ? $value : $this->truncate($value, $maxLength);
	}

	private function displayText($value)
	{
		return html_entity_decode((string) $value, ENT_QUOTES, 'UTF-8');
	}

	private function plainTextLimit($column)
	{
		if (in_array($column, array('page_name', 'page_name_ar', 'page_title', 'page_title_ar', 'og_title', 'og_title_ar', 'banner_title', 'banner_title_ar', 'banner_heading', 'banner_heading_ar'), TRUE)) return 255;
		if (in_array($column, array('menu_name', 'menu_name_ar'), TRUE)) return 100;
		return NULL;
	}

	private function colorFields()
	{
		return array('banner_background_color_1', 'banner_background_color_2', 'banner_title_color_1', 'banner_title_color_2', 'banner_heading_color_1', 'banner_heading_color_2', 'banner_text_color_1', 'banner_text_color_2');
	}

	private function imageColumns()
	{
		return array('og_image', 'og_image_ar', 'banner_background', 'banner_background_ar');
	}

	private function normalizeRgba($value)
	{
		$value = trim((string) $value);
		if ($value === '') return '';
		if (preg_match('/^#([0-9a-f]{3,8})$/i', $value, $match))
		{
			$hex = $match[1];
			if (strlen($hex) === 3 || strlen($hex) === 4) { $expanded = ''; foreach (str_split($hex) as $character) $expanded .= $character.$character; $hex = $expanded; }
			if (strlen($hex) !== 6 && strlen($hex) !== 8) return '';
			$alpha = strlen($hex) === 8 ? hexdec(substr($hex, 6, 2)) / 255 : 1;
			return sprintf('rgba(%d, %d, %d, %s)', hexdec(substr($hex, 0, 2)), hexdec(substr($hex, 2, 2)), hexdec(substr($hex, 4, 2)), round($alpha, 2));
		}
		if (!preg_match('/^rgba?\(([^)]+)\)$/i', $value, $match)) return '';
		$parts = preg_split('/[,\s\/]+/', trim($match[1]), -1, PREG_SPLIT_NO_EMPTY);
		if (count($parts) < 3 || count($parts) > 4) return '';
		$channels = array();
		for ($i = 0; $i < 3; $i++) { if (!is_numeric($parts[$i])) return ''; $channels[] = max(0, min(255, (int) round((float) $parts[$i]))); }
		if (isset($parts[3]) && !is_numeric($parts[3])) return '';
		$alpha = isset($parts[3]) ? max(0, min(1, (float) $parts[3])) : 1;
		return sprintf('rgba(%d, %d, %d, %s)', $channels[0], $channels[1], $channels[2], round($alpha, 2));
	}

	private function queueTranslationSafely($pageID)
	{
		try { $this->manage_translation_service->queue($this->translationModule, (int) $pageID); }
		catch (Throwable $exception) { log_message('error', 'Page translation could not be queued for record '.(int) $pageID.'.'); }
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

	private function redirectToListing($parentID)
	{
		redirect(base_url('manage/'.$this->controller.'/index/'.max(0, (int) $parentID)));
	}
}
