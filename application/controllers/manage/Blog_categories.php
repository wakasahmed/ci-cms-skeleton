<?php defined('BASEPATH') OR exit('No direct script access allowed');

class Blog_categories extends CI_Controller {

	public $tblName = 'blog_categories';
	public $colPrefix = 'cat_';
	public $pKey = 'cat_id';
	public $moduleName = 'Blog Categories';
	public $moduleNameSingular = 'Blog Category';
	public $moduleDesc = 'Organize blogs into localized categories and manage their search and banner presentation.';
	public $controller = 'blog-categories';
	public $per_page = 10;
	public $tStatus = 'cat_status';
	public $listView = 'blogCategories';
	public $addEditView = 'addBlogCategory';
	public $user_data = array();
	private $translationModule = 'blog_categories';
	private $imageDirectory = 'assets/frontend/images/blog-categories';
	private $protectedID = 1;

	public function __construct()
	{
		parent::__construct();
		$this->user_data = $this->SqlModel->authAdmin($this->session->userdata('admin_auth'), $this->session->userdata('admin_id'));
		if (empty($this->user_data)) redirect(base_url('manage/login'));
		$this->load->library('manage_translation_service');
	}

	public function index($sortby = 'cat_order', $order = 'ASC', $status = '-', $keywords = '-', $pgNo = '')
	{
		$sorts = array('cat_id', 'cat_order', 'cat_name', 'cat_status', 'cat_added', 'cat_updated');
		$sortby = in_array($sortby, $sorts, TRUE) ? $sortby : 'cat_order';
		$order = strtoupper($order) === 'DESC' ? 'DESC' : 'ASC';
		$status = in_array($status, array('Enable', 'Disable'), TRUE) ? $status : '-';

		$allowed = array_merge(array(0), range(10, 100, 10));
		$requested = $this->input->get('per_page');
		if ($requested !== NULL && ctype_digit((string) $requested) && in_array((int) $requested, $allowed, TRUE)) $this->session->set_userdata('per_page', (int) $requested);
		if (in_array((int) $this->session->userdata('per_page'), $allowed, TRUE)) $this->per_page = (int) $this->session->userdata('per_page');

		$keywords = urldecode((string) $keywords);
		$where = array('cat_id >' => $this->protectedID);
		if ($status !== '-') $where[$this->tStatus] = $status;
		$search = $keywords !== '-' ? array('cols' => 'cat_name,cat_name_ar,cat_slug,cat_slug_ar', 'value' => $keywords) : array();
		$base = base_url('manage/'.$this->controller.'/index/'.$sortby.'/'.$order.'/'.$status.'/'.urlencode($keywords));
		$total = $this->SqlModel->countRecords($this->tblName, $where, $search);
		$offset = max(0, (int) ($pgNo !== '' ? $pgNo : $this->uri->segment(8, 0)));
		$this->pagination->initialize(admin_pagination_config($base, $total, $this->per_page, 8));

		$recordSort = $sortby === 'cat_order' ? 'cat_order,cat_name' : $sortby;
		$records = $this->SqlModel->getRecords(
			'blog_categories.*, (SELECT COUNT(*) FROM blog_assigned_cat WHERE bc_cat_id=blog_categories.cat_id) AS blog_count',
			$this->tblName,
			$recordSort,
			$order,
			$where,
			$search,
			$this->per_page,
			$offset,
			FALSE
		);
		$ids = array_map('intval', array_column($records, $this->pKey));

		$this->render($this->listView, array(
			'alert' => $this->session->flashdata('alert'),
			'page_title' => PROJECT_TITLE.' | '.$this->moduleName,
			'userdata' => $this->user_data,
			'blogCategoriesActive' => 1,
			'records' => $records,
			'total_rows' => $total,
			'per_page' => $this->per_page,
			'paginate' => $this->pagination->create_links(),
			'sortby' => $sortby,
			'order' => $order === 'ASC' ? 'DESC' : 'ASC',
			'page_numb' => $offset,
			'status' => $status,
			'keywords' => $keywords,
			'translation_statuses' => empty($ids) ? array() : $this->manage_translation_service->statuses($this->translationModule, $ids),
			'useManageTranslations' => TRUE,
		));
	}

	public function control($editID = 0)
	{
		$editID = (int) $editID;
		$record = $editID > 0 ? $this->record($editID) : array();
		if ($editID > 0 && empty($record))
		{
			$this->session->set_flashdata('alert', 'error');
			return redirect(base_url('manage/'.$this->controller));
		}

		$posted = $this->session->flashdata($this->controller.'_data');
		$requested = is_array($posted) && isset($posted['active_locale']) ? $posted['active_locale'] : $this->input->get('lang', TRUE);
		$locale = $editID > 0 ? $this->manage_translation_service->locale($requested) : 'en';
		$localized = $this->manage_translation_service->localized_values($this->translationModule, $record, $locale);
		if (is_array($posted)) foreach (array_keys($localized) as $key) if (array_key_exists($key, $posted)) $localized[$key] = $posted[$key];
		$this->configureEditor($locale);

		$this->render($this->addEditView, array(
			'blogCategoriesActive' => 1,
			'page_title' => PROJECT_TITLE.' | '.($editID ? 'Edit' : 'Add').' '.$this->moduleNameSingular,
			'userdata' => $this->user_data,
			'tbl_data' => $record,
			'form_values' => $this->formValues($record, $locale, $posted),
			'localized_values' => $localized,
			'active_locale' => $locale,
			'manage_locales' => $this->manage_translation_service->locales(),
			'translation_state' => $editID ? $this->manage_translation_service->state($this->translationModule, $editID) : NULL,
			'form_error' => $this->session->flashdata('form_error'),
			'next_order' => $this->nextOrder(),
			'useColorPicker' => TRUE,
			'useManageTranslations' => TRUE,
			'useSweetAlert' => TRUE,
		));
	}

	public function addRecord()
	{
		if (!$this->validPost('en')) return $this->formFailure('Enter a category name and valid URL slug, then check the publishing and banner settings.');
		if ($this->categoryExists('en', $this->clean($this->input->post('localized_name'), 255), $this->normalizeSlug($this->input->post('page_slug')))) return $this->formFailure('A blog category with this name or URL slug already exists.');

		$uploads = $this->saveUploads('en');
		if ($uploads['error']) return $this->formFailure($uploads['error']);

		$data = $this->postedData('en');
		$data['cat_slug'] = $this->uniqueSlug('cat_slug', $this->normalizeSlug($this->input->post('page_slug')));
		$data['cat_name_ar'] = $data['cat_slug_ar'] = $data['cat_desc_ar'] = '';
		$data['cat_order'] = $this->nextOrder();
		$data['cat_added'] = $data['cat_updated'] = date('Y-m-d H:i:s');
		foreach ($uploads['files'] as $column => $file) if ($file !== '') $data[$column] = $file;

		$this->db->trans_begin();
		$id = $this->SqlModel->insertRecord($this->tblName, $data);
		if (!$id || $this->db->trans_status() === FALSE)
		{
			$this->db->trans_rollback();
			$this->cleanup($uploads['files']);
			return $this->formFailure('The blog category could not be saved. Please try again.');
		}
		$this->db->trans_commit();

		$this->queue($id);
		$this->session->set_flashdata('alert', 'success');
		redirect(base_url('manage/'.$this->controller));
	}

	public function editRecord($editID = 0)
	{
		$editID = (int) $editID;
		$current = $this->record($editID);
		if (empty($current))
		{
			$this->session->set_flashdata('alert', 'error');
			return redirect(base_url('manage/'.$this->controller));
		}

		$locale = $this->manage_translation_service->locale($this->input->post('active_locale', TRUE));
		if (!$this->validPost($locale)) return $this->formFailure('Enter a category name and valid URL slug, then check the publishing and banner settings.', $editID, $locale);
		if ($this->categoryExists($locale, $this->clean($this->input->post('localized_name'), 255), $this->normalizeSlug($this->input->post('page_slug')), $editID)) return $this->formFailure('A blog category with this name or URL slug already exists.', $editID, $locale);

		$uploads = $this->saveUploads($locale);
		if ($uploads['error']) return $this->formFailure($uploads['error'], $editID, $locale);

		$data = $this->postedData($locale);
		$slugColumn = $locale === 'ar' ? 'cat_slug_ar' : 'cat_slug';
		$data[$slugColumn] = $this->uniqueSlug($slugColumn, $this->normalizeSlug($this->input->post('page_slug')), $editID);
		$data['cat_updated'] = date('Y-m-d H:i:s');
		foreach ($uploads['files'] as $column => $file) if ($file !== '') $data[$column] = $file;

		$this->db->trans_begin();
		$ok = $this->SqlModel->updateRecord($this->tblName, $data, array($this->pKey => $editID));
		if (!$ok || $this->db->trans_status() === FALSE)
		{
			$this->db->trans_rollback();
			$this->cleanup($uploads['files']);
			return $this->formFailure('The blog category could not be updated. Please try again.', $editID, $locale);
		}
		$this->db->trans_commit();

		foreach ($uploads['files'] as $column => $file) if ($file !== '') $this->deleteImage(isset($current[$column]) ? $current[$column] : '');
		if ($locale === 'en') $this->queue($editID);
		$this->session->set_flashdata('alert', 'editsuccess');

		$redirect = $this->input->post('redirect_lang', TRUE);
		if (is_string($redirect) && $redirect !== '' && $this->manage_translation_service->locale($redirect) === $redirect) return redirect(base_url('manage/'.$this->controller.'/control/'.$editID.'?lang='.$redirect));
		redirect(base_url('manage/'.$this->controller));
	}

	public function delete($id = 0)
	{
		$ok = $this->deleteRecords(array((int) $id));
		$this->session->set_flashdata('alert', $ok ? 'deletesuccess' : 'deleteerror');
		redirect(base_url('manage/'.$this->controller));
	}

	public function deleteall()
	{
		$ids = array_values(array_unique(array_filter(array_map('intval', (array) $this->input->post('records')))));
		$ok = !empty($ids) && $this->deleteRecords($ids);
		$this->session->set_flashdata('alert', $ok ? 'deletesuccess' : 'deleteerror');
		redirect(base_url('manage/'.$this->controller));
	}

	public function changestatus($id = 0, $status = 'Enable')
	{
		$this->output->set_content_type('application/json');
		$id = (int) $id;
		$ok = $id > $this->protectedID && in_array($status, array('Enable', 'Disable'), TRUE) && !empty($this->record($id)) && $this->SqlModel->updateRecord($this->tblName, array($this->tStatus => $status, 'cat_updated' => date('Y-m-d H:i:s')), array($this->pKey => $id));
		return $this->output->set_output(json_encode(array('status' => $ok ? 'true' : 'false', 'id' => $id, 'currentStatus' => $ok ? $status : '')));
	}

	public function duplicate($id = 0)
	{
		$data = $this->record((int) $id);
		if (empty($data))
		{
			$this->session->set_flashdata('alert', 'error');
			return redirect(base_url('manage/'.$this->controller));
		}

		unset($data[$this->pKey]);
		$data['cat_name'] = $this->truncate($this->display($data['cat_name']).' Duplicate', 255);
		$data['cat_slug'] = $this->uniqueSlug('cat_slug', $this->normalizeSlug($data['cat_slug'].'-copy'));
		if (trim($data['cat_slug_ar']) !== '') $data['cat_slug_ar'] = $this->uniqueSlug('cat_slug_ar', $this->normalizeSlug($data['cat_slug_ar'].'-copy'));
		$data['cat_order'] = $this->nextOrder();
		$data['cat_added'] = $data['cat_updated'] = date('Y-m-d H:i:s');

		$new = $this->SqlModel->insertRecord($this->tblName, $data);
		if ($new)
		{
			$this->queue($new);
			return redirect(base_url('manage/'.$this->controller.'/control/'.$new));
		}
		$this->session->set_flashdata('alert', 'error');
		redirect(base_url('manage/'.$this->controller));
	}

	public function categoryorder()
	{
		$this->output->set_content_type('application/json');
		if ($this->input->method(TRUE) !== 'POST') return $this->json(405, FALSE, 'Sorting requires a POST request.');

		$ids = array_values(array_unique(array_filter(array_map('intval', (array) $this->input->post('records')))));
		$rows = $this->db->select($this->pKey)->where('cat_id >', $this->protectedID)->order_by('cat_order', 'ASC')->order_by('cat_name', 'ASC')->get($this->tblName)->result_array();
		$all = array_map('intval', array_column($rows, $this->pKey));
		if (empty($ids) || count(array_intersect($ids, $all)) !== count($ids)) return $this->json(422, FALSE, 'One or more categories are invalid.');

		$remaining = array_values(array_diff($all, $ids));
		array_splice($remaining, max(0, min((int) $this->input->post('offset'), count($remaining))), 0, $ids);
		$updates = array();
		foreach ($remaining as $i => $id) $updates[] = array($this->pKey => $id, 'cat_order' => $i + 1, 'cat_updated' => date('Y-m-d H:i:s'));
		$ok = $this->SqlModel->batchUpdate($this->tblName, $updates, $this->pKey);
		return $this->json($ok ? 200 : 500, (bool) $ok, $ok ? 'The category order was saved.' : 'The category order could not be saved.');
	}

	public function sortrecords()
	{
		return $this->categoryorder();
	}

	public function addRecordAJAX()
	{
		$this->output->set_content_type('application/json');
		if ($this->input->method(TRUE) !== 'POST') return $this->output->set_status_header(405)->set_output(json_encode(array('status' => 'false', 'message' => 'Category creation requires a POST request.')));

		$name = $this->clean($this->input->post('cat_name'), 255);
		$slug = $this->normalizeSlug($this->input->post('cat_slug'));
		if ($name === '' || $slug === '' || $this->length($name) > 255 || $this->length($slug) > 255) return $this->output->set_status_header(422)->set_output(json_encode(array('status' => 'false', 'message' => 'Enter a category name and valid URL slug.')));
		if ($this->categoryExists('en', $name, $slug)) return $this->output->set_status_header(409)->set_output(json_encode(array('status' => 'false', 'message' => 'A blog category with this name or URL slug already exists.')));

		$now = date('Y-m-d H:i:s');
		$data = array(
			'cat_name' => $name,
			'cat_name_ar' => '',
			'cat_slug' => $slug,
			'cat_slug_ar' => '',
			'cat_desc' => '',
			'cat_desc_ar' => '',
			'cat_contents' => '',
			'cat_contents_ar' => '',
			'cat_status' => 'Enable',
			'cat_order' => $this->nextOrder(),
			'cat_added' => $now,
			'cat_updated' => $now,
		);

		$this->db->trans_begin();
		$id = $this->SqlModel->insertRecord($this->tblName, $data);
		if (!$id || $this->db->trans_status() === FALSE)
		{
			$this->db->trans_rollback();
			return $this->output->set_status_header(500)->set_output(json_encode(array('status' => 'false', 'message' => 'The blog category could not be saved. Please try again.')));
		}
		$this->db->trans_commit();

		$this->queue($id);
		return $this->output->set_output(json_encode(array(
			'status' => 'true',
			'cat_id' => (int) $id,
			'cat_name' => $name,
			'cat_slug' => $slug,
			'csrf_name' => $this->config->item('csrf_protection') ? $this->security->get_csrf_token_name() : NULL,
			'csrf_hash' => $this->config->item('csrf_protection') ? $this->security->get_csrf_hash() : NULL,
		)));
	}

	public function removefile($id = 0, $key = '')
	{
		$this->output->set_content_type('application/json');
		$id = (int) $id;
		$record = in_array($key, $this->imageColumns(), TRUE) ? $this->record($id) : array();
		if (empty($record))
		{
			return $this->output->set_output(json_encode(array('success' => FALSE, 'message' => 'Invalid request.')));
		}
		if (!$this->SqlModel->updateRecord($this->tblName, array($key => '', 'cat_updated' => date('Y-m-d H:i:s')), array($this->pKey => $id)))
		{
			return $this->output->set_output(json_encode(array('success' => FALSE, 'message' => 'The record could not be updated.')));
		}
		$this->deleteImage(isset($record[$key]) ? $record[$key] : '');
		return $this->output->set_output(json_encode(array('success' => TRUE, 'id' => $id, 'key' => $key)));
	}

	private function postedData($locale)
	{
		$post = $this->input->post(NULL, FALSE);
		$data = $this->manage_translation_service->localized_post_data($this->translationModule, $locale, is_array($post) ? $post : array());
		foreach (array_keys($data) as $column)
		{
			if (!in_array($column, array('cat_contents', 'cat_contents_ar'), TRUE)) $data[$column] = $this->clean($data[$column], in_array($column, array('cat_name', 'cat_name_ar', 'page_title', 'page_title_ar', 'og_title', 'og_title_ar', 'banner_title', 'banner_title_ar', 'banner_heading', 'banner_heading_ar'), TRUE) ? 255 : NULL);
		}
		foreach ($this->colorFields() as $field)
		{
			$value = $this->normalizeRgba($this->input->post($field));
			$data[$field] = $value === '' ? NULL : $value;
		}
		$data['robots_index'] = $this->input->post('robots_index') === '1' ? 1 : 0;
		$data['robots_follow'] = $this->input->post('robots_follow') === '1' ? 1 : 0;
		$data['show_top_banner'] = $this->input->post('show_top_banner') === '1' ? 1 : 0;
		$data['banner_overlay'] = $this->input->post('banner_overlay') === 'No' ? 'No' : 'Yes';
		$data[$this->tStatus] = $this->input->post($this->tStatus) === 'Disable' ? 'Disable' : 'Enable';

		return $data;
	}

	private function validPost($locale)
	{
		$post = $this->input->post(NULL, FALSE);
		if (!$this->manage_translation_service->required_localized_input_valid($this->translationModule, $locale, is_array($post) ? $post : array())) return FALSE;

		$slug = $this->normalizeSlug($this->input->post('page_slug'));
		if ($slug === '' || $this->length($slug) > 255) return FALSE;
		if (!in_array($this->input->post($this->tStatus), array('Enable', 'Disable'), TRUE) || !in_array($this->input->post('banner_overlay'), array('Yes', 'No'), TRUE)) return FALSE;

		foreach ($this->colorFields() as $field)
		{
			$v = trim((string) $this->input->post($field));
			if ($v !== '' && $this->normalizeRgba($v) === '') return FALSE;
		}

		return TRUE;
	}

	private function formValues($record, $locale, $posted)
	{
		$v = array(
			'page_slug' => '',
			'cat_status' => 'Enable',
			'robots_index' => 1,
			'robots_follow' => 1,
			'show_top_banner' => 0,
			'banner_overlay' => 'Yes',
			'current_cat_cover_image' => '',
			'current_og_image' => '',
			'current_banner_background' => '',
		);
		foreach ($this->colorFields() as $f) $v[$f] = isset($record[$f]) ? $record[$f] : '';
		foreach (array('cat_status', 'robots_index', 'robots_follow', 'show_top_banner', 'banner_overlay') as $f) if (isset($record[$f])) $v[$f] = $record[$f];

		$suffix = $locale === 'ar' ? '_ar' : '';
		if (isset($record['cat_slug'.$suffix])) $v['page_slug'] = $record['cat_slug'.$suffix];
		$v['current_cat_cover_image'] = isset($record['cat_cover_image']) ? basename($record['cat_cover_image']) : '';
		$v['current_og_image'] = isset($record['og_image'.$suffix]) ? basename($record['og_image'.$suffix]) : '';
		$v['current_banner_background'] = isset($record['banner_background'.$suffix]) ? basename($record['banner_background'.$suffix]) : '';

		if (is_array($posted)) foreach (array_keys($v) as $f) if (strpos($f, 'current_') !== 0 && array_key_exists($f, $posted)) $v[$f] = $posted[$f];

		return $v;
	}

	private function formFailure($message, $id = 0, $locale = 'en')
	{
		$posted = $this->input->post(NULL, FALSE);
		$posted = is_array($posted) ? $posted : array();
		$posted['active_locale'] = $this->manage_translation_service->locale($locale);
		$this->session->set_flashdata($this->controller.'_data', $posted);
		$this->session->set_flashdata('form_error', $message);
		redirect(base_url('manage/'.$this->controller.'/control'.($id ? '/'.(int) $id.'?lang='.$this->manage_translation_service->locale($locale) : '')));
	}

	private function configureEditor($locale)
	{
		$this->load->library('ckeditor');
		$this->load->library('ckfinder');
		$this->ckeditor->basePath = base_url().'assets/ckeditor/';
		$this->ckeditor->config['removePlugins'] = 'save, preview, newpage, forms, flash';
		$this->ckeditor->config['height'] = '340px';
		$this->ckeditor->config['contentsLangDirection'] = $locale === 'ar' ? 'rtl' : 'ltr';
		$this->ckeditor->textareaAttributes = array('id' => 'localized_contents', 'data-translation-field' => 'localized_contents', 'dir' => $locale === 'ar' ? 'rtl' : 'ltr');
		$this->ckfinder->SetupCKEditor($this->ckeditor, '../../../../assets/ckfinder/');
	}

	private function saveUploads($locale)
	{
		$suffix = $locale === 'ar' ? '_ar' : '';
		$fields = array('og_image_upload' => 'og_image'.$suffix, 'banner_background_upload' => 'banner_background'.$suffix);
		if ($locale === 'en') $fields = array('cat_cover_image_upload' => 'cat_cover_image') + $fields;

		$result = array('files' => array(), 'error' => '');
		foreach ($fields as $field => $column)
		{
			$u = $this->upload($field);
			$result['files'][$column] = $u['filename'];
			if ($u['error'])
			{
				$label = $field === 'og_image_upload' ? 'Sharing image: ' : ($field === 'cat_cover_image_upload' ? 'Cover image: ' : 'Banner background: ');
				$result['error'] = $label.$u['error'];
				$this->cleanup($result['files']);
				break;
			}
		}

		return $result;
	}

	private function upload($field)
	{
		$r = array('filename' => '', 'error' => '');
		if (empty($_FILES[$field]['name'])) return $r;
		if (!isset($_FILES[$field]['error']) || $_FILES[$field]['error'] !== UPLOAD_ERR_OK)
		{
			$r['error'] = 'The upload did not complete successfully.';
			return $r;
		}
		if ((int) $_FILES[$field]['size'] > (int) UPLOAD_SIZE)
		{
			$r['error'] = 'The image must be '.UPLOAD_SIZE_MB.' MB or smaller.';
			return $r;
		}

		$path = FCPATH.$this->imageDirectory.DIRECTORY_SEPARATOR;
		if (!is_dir($path) && !mkdir($path, 0755, TRUE))
		{
			$r['error'] = 'The image storage directory could not be created.';
			return $r;
		}

		$this->load->library('upload');
		$this->upload->initialize(array(
			'upload_path' => $path,
			'allowed_types' => UPLOAD_IMAGE_MIMES,
			'max_size' => UPLOAD_SIZE_MB * 1024,
			'encrypt_name' => TRUE,
			'remove_spaces' => TRUE,
		));
		if (!$this->upload->do_upload($field))
		{
			$r['error'] = strip_tags($this->upload->display_errors('', ''));
			return $r;
		}

		$file = $this->upload->data();
		$r['filename'] = $file['file_name'];

		return $r;
	}

	private function deleteRecords($ids)
	{
		$ids = array_values(array_unique(array_filter(array_map('intval', $ids))));
		foreach ($ids as $id) if ($id <= $this->protectedID) return FALSE;

		$records = empty($ids) ? array() : $this->db->where_in($this->pKey, $ids)->get($this->tblName)->result_array();
		if (count($records) !== count($ids)) return FALSE;

		$this->db->trans_begin();
		foreach ($ids as $id) $this->manage_translation_service->delete_jobs($this->translationModule, $id);
		$this->db->where_in('bc_cat_id', $ids)->delete('blog_assigned_cat');
		$this->db->where_in($this->pKey, $ids)->delete($this->tblName);
		if ($this->db->trans_status() === FALSE)
		{
			$this->db->trans_rollback();
			return FALSE;
		}
		$this->db->trans_commit();

		foreach ($records as $r) foreach ($this->imageColumns() as $c) $this->deleteImage(isset($r[$c]) ? $r[$c] : '');

		return TRUE;
	}

	private function cleanup($files)
	{
		foreach ($files as $f) if ($f !== '') $this->deleteImage($f);
	}

	private function deleteImage($file)
	{
		if (!is_string($file) || $file === '' || basename($file) !== $file) return;

		$this->db->from($this->tblName)->group_start();
		foreach ($this->imageColumns() as $i => $c) $i ? $this->db->or_where($c, $file) : $this->db->where($c, $file);
		$this->db->group_end();
		if ($this->db->count_all_results() > 0) return;

		delete_uploaded_file(FCPATH.$this->imageDirectory, $file);
	}

	private function categoryExists($locale, $name, $slug, $exclude = 0)
	{
		$nameColumn = $locale === 'ar' ? 'cat_name_ar' : 'cat_name';
		$slugColumn = $locale === 'ar' ? 'cat_slug_ar' : 'cat_slug';
		$this->db->from($this->tblName)->group_start()->where($nameColumn, $name)->or_where($slugColumn, $slug)->group_end();
		if ((int) $exclude > 0) $this->db->where($this->pKey.' !=', (int) $exclude);

		return $this->db->count_all_results() > 0;
	}

	private function record($id)
	{
		return (int) $id > 0 ? $this->SqlModel->getSingleRecord($this->tblName, array($this->pKey => (int) $id)) : array();
	}

	private function nextOrder()
	{
		$row = $this->db->select_max('cat_order', 'max_order')->get($this->tblName)->row_array();

		return isset($row['max_order']) ? (int) $row['max_order'] + 1 : 1;
	}

	private function uniqueSlug($column, $slug, $exclude = 0)
	{
		$base = $slug ?: 'category';
		$candidate = $base;
		for ($i = 2; ; $i++)
		{
			$this->db->from($this->tblName)->where($column, $candidate);
			if ($exclude) $this->db->where($this->pKey.' !=', (int) $exclude);
			if ($this->db->count_all_results() === 0) return $candidate;
			$candidate = $this->truncate($base.'-'.$i, 255);
		}
	}

	private function normalizeSlug($v)
	{
		$v = html_entity_decode(trim((string) $v), ENT_QUOTES, 'UTF-8');
		$v = function_exists('mb_strtolower') ? mb_strtolower($v, 'UTF-8') : strtolower($v);
		$v = preg_replace('/[\s_]+/u', '-', $v);
		$v = preg_replace('/[^\p{L}\p{N}-]+/u', '', $v);

		return trim(preg_replace('/-+/', '-', $this->truncate($v, 255)), '-');
	}

	private function colorFields()
	{
		return array(
			'banner_background_color_1',
			'banner_background_color_2',
			'banner_title_color_1',
			'banner_title_color_2',
			'banner_heading_color_1',
			'banner_heading_color_2',
			'banner_text_color_1',
			'banner_text_color_2',
		);
	}

	private function imageColumns()
	{
		return array('cat_cover_image', 'og_image', 'og_image_ar', 'banner_background', 'banner_background_ar');
	}

	private function normalizeRgba($v)
	{
		$v = trim((string) $v);
		if ($v === '') return '';
		if (preg_match('/^#([0-9a-f]{6})$/i', $v, $m)) return sprintf('rgba(%d, %d, %d, 1)', hexdec(substr($m[1], 0, 2)), hexdec(substr($m[1], 2, 2)), hexdec(substr($m[1], 4, 2)));
		if (!preg_match('/^rgba?\(([^)]+)\)$/i', $v, $m)) return '';

		$p = preg_split('/[,\s\/]+/', trim($m[1]), -1, PREG_SPLIT_NO_EMPTY);
		if (count($p) < 3 || count($p) > 4) return '';
		foreach ($p as $n) if (!is_numeric($n)) return '';

		$a = isset($p[3]) ? max(0, min(1, (float) $p[3])) : 1;

		return sprintf('rgba(%d, %d, %d, %s)', max(0, min(255, (int) $p[0])), max(0, min(255, (int) $p[1])), max(0, min(255, (int) $p[2])), round($a, 2));
	}

	private function clean($v, $max = NULL)
	{
		$v = trim(strip_tags(html_entity_decode((string) $v, ENT_QUOTES, 'UTF-8')));

		return $max ? $this->truncate($v, $max) : $v;
	}

	private function display($v)
	{
		return html_entity_decode((string) $v, ENT_QUOTES, 'UTF-8');
	}

	private function truncate($v, $max)
	{
		return function_exists('mb_substr') ? mb_substr((string) $v, 0, (int) $max, 'UTF-8') : substr((string) $v, 0, (int) $max);
	}

	private function length($v)
	{
		return function_exists('mb_strlen') ? mb_strlen((string) $v, 'UTF-8') : strlen((string) $v);
	}

	private function queue($id)
	{
		try
		{
			$this->manage_translation_service->queue($this->translationModule, (int) $id);
		}
		catch (Throwable $e)
		{
			log_message('error', 'Blog category translation could not be queued for record '.(int) $id.'.');
		}
	}

	private function render($view, $data)
	{
		$this->load->view('admin/header', $data);
		$this->load->view('admin/navigation');
		$this->load->view('admin/'.$view);
		$this->load->view('admin/footer');
	}

	private function json($code, $success, $message)
	{
		return $this->output->set_status_header($code)->set_output(json_encode(array('success' => $success, 'message' => $message)));
	}
}
