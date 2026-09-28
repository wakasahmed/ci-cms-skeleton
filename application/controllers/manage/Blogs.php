<?php defined('BASEPATH') OR exit('No direct script access allowed');

class Blogs extends CI_Controller {

	public $tblName = 'blogs';
	public $colPrefix = 'blog_';
	public $pKey = 'blog_id';
	public $moduleName = 'Blog Posts';
	public $moduleNameSingular = 'Blog';
	public $moduleDesc = 'Manage articles, categories, publishing, search, and social sharing.';
	public $controller = 'blogs';
	public $per_page = 10;
	public $tStatus = 'blog_status';
	public $listView = 'blogs';
	public $addEditView = 'addBlog';
	public $user_data = array();
	private $imageDirectory = 'assets/frontend/images/blogs';

	public function __construct()
	{
		parent::__construct();
		$this->user_data = $this->SqlModel->authAdmin($this->session->userdata('admin_auth'), $this->session->userdata('admin_id'));
		if (empty($this->user_data)) redirect(base_url('manage/login'));
		$this->load->helper('admin_input');
	}

	public function index($sortby = 'blog_added', $order = 'DESC', $status = '-', $keywords = '-', $pgNo = '')
	{
		$sorts = array('blog_id', 'blog_name', 'blog_status', 'blog_featured', 'blog_added', 'blog_updated');
		$sortby = in_array($sortby, $sorts, TRUE) ? $sortby : 'blog_added';
		$order = strtoupper($order) === 'ASC' ? 'ASC' : 'DESC';
		$status = in_array($status, array('Published', 'Un-Published'), TRUE) ? $status : '-';

		$allowed = array_merge(array(0), range(10, 100, 10));
		$requested = $this->input->get('per_page');
		if ($requested !== NULL && ctype_digit((string) $requested) && in_array((int) $requested, $allowed, TRUE)) $this->session->set_userdata('per_page', (int) $requested);
		if (in_array((int) $this->session->userdata('per_page'), $allowed, TRUE)) $this->per_page = (int) $this->session->userdata('per_page');

		$keywords = urldecode((string) $keywords);
		$where = array();
		if ($status !== '-') $where[$this->tStatus] = $status;
		$listingTable = 'blogs LEFT JOIN admin_users ON admin_users.id = blogs.blog_author';
		$search = $keywords !== '-' ? array(
			'cols' => 'blogs.blog_name,blogs.blog_slug,admin_users.full_name,admin_users.user_name',
			'value' => $keywords,
		) : array();
		$base = base_url('manage/blogs/index/'.$sortby.'/'.$order.'/'.$status.'/'.urlencode($keywords));
		$total = $this->SqlModel->countRecords($listingTable, $where, $search);
		$offset = max(0, (int) ($pgNo !== '' ? $pgNo : $this->uri->segment(8, 0)));
		$this->pagination->initialize(admin_pagination_config($base, $total, $this->per_page, 8));

		$records = $this->SqlModel->getRecords(
			'blogs.*, admin_users.full_name AS author_name, admin_users.user_name AS author_username, (SELECT GROUP_CONCAT(c.cat_name ORDER BY c.cat_name SEPARATOR ", ") FROM blog_assigned_cat a JOIN blog_categories c ON c.cat_id=a.bc_cat_id WHERE a.bc_blog_id=blogs.blog_id) AS category_names',
			$listingTable,
			$sortby,
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
			'blogsActive' => 1,
			'records' => $records,
			'listing' => $records,
			'total_rows' => $total,
			'per_page' => $this->per_page,
			'paginate' => $this->pagination->create_links(),
			'sortby' => $sortby,
			'order' => $order === 'ASC' ? 'DESC' : 'ASC',
			'page_numb' => $offset,
			'status' => $status,
			'keywords' => $keywords,
		));
	}

	public function control($editID = 0)
	{
		$editID = (int) $editID;
		$record = $editID ? $this->record($editID) : array();
		if ($editID && empty($record))
		{
			$this->session->set_flashdata('alert', 'error');
			return redirect(base_url('manage/blogs'));
		}

		$posted = $this->session->flashdata('blogs_data');
		$textValues = array();
		foreach ($this->textFields() as $column => $maxLength) $textValues[$column] = isset($record[$column]) ? $record[$column] : '';
		if (is_array($posted)) foreach (array_keys($textValues) as $key) if (array_key_exists($key, $posted)) $textValues[$key] = $posted[$key];

		$assigned = $editID ? array_map('intval', array_column($this->SqlModel->getRecords('bc_cat_id', 'blog_assigned_cat', 'bc_id', 'ASC', array('bc_blog_id' => $editID)), 'bc_cat_id')) : array();
		if (is_array($posted) && isset($posted['blog_category'])) $assigned = array_map('intval', (array) $posted['blog_category']);
		$this->configureEditor();

		$this->render($this->addEditView, array(
			'blogsActive' => 1,
			'page_title' => PROJECT_TITLE.' | '.($editID ? 'Edit' : 'Add').' '.$this->moduleNameSingular,
			'userdata' => $this->user_data,
			'tbl_data' => $record,
			'form_values' => $this->formValues($record, $posted),
			'text_values' => $textValues,
			'form_error' => $this->session->flashdata('form_error'),
			'admins' => $this->SqlModel->getRecords(
				'id,user_name,full_name,email,status',
				'admin_users',
				'full_name',
				'ASC'
			),
			'selected_categories' => $assigned,
			'services' => $this->SqlModel->getRecords('service_id,service_name,service_status', 'services', 'service_name', 'ASC'),
			'cats' => $this->SqlModel->getRecords('cat_id,cat_name,cat_status', 'blog_categories', 'cat_name', 'ASC', array('cat_id >' => 1)),
			'useColorPicker' => TRUE,
			'useUserSelect' => TRUE,
			'useSweetAlert' => TRUE,
		));
	}

	public function addRecord()
	{
		if (!$this->validPost()) return $this->formFailure('Enter a blog title and valid URL slug, select an author, select at least one category, add a thumbnail image, then check the publishing and banner settings.');

		$uploads = $this->saveUploads();
		if ($uploads['error']) return $this->formFailure($uploads['error']);

		$data = $this->postedData();
		$data['blog_slug'] = $this->uniqueSlug('blog_slug', $this->normalizeSlug($this->input->post('page_slug')));
		$data['blog_added'] = $data['blog_updated'] = date('Y-m-d H:i:s');
		$data['blog_year'] = (int) date('Y');
		$data['blog_month'] = (int) date('n');
		$data['blog_month_year'] = date('F Y');
		foreach ($uploads['files'] as $c => $f) if ($f !== '') $data[$c] = $f;

		$categories = $this->validCategoryIDs();
		$this->db->trans_begin();
		$id = $this->SqlModel->insertRecord($this->tblName, $data);
		if ($id) $this->insertCategories($id, $categories);
		if (!$id || $this->db->trans_status() === FALSE)
		{
			$this->db->trans_rollback();
			$this->cleanup($uploads['files']);
			return $this->formFailure('The blog could not be saved. Please try again.');
		}
		$this->db->trans_commit();

		$this->session->set_flashdata('alert', 'success');
		redirect(base_url('manage/blogs'));
	}

	public function editRecord($editID = 0)
	{
		$editID = (int) $editID;
		$current = $this->record($editID);
		if (empty($current))
		{
			$this->session->set_flashdata('alert', 'error');
			return redirect(base_url('manage/blogs'));
		}

		if (!$this->validPost($current)) return $this->formFailure('Enter a blog title and valid URL slug, select an author, select at least one category, add a thumbnail image, then check the publishing and banner settings.', $editID);

		$uploads = $this->saveUploads();
		if ($uploads['error']) return $this->formFailure($uploads['error'], $editID);

		$data = $this->postedData();
		$data['blog_slug'] = $this->uniqueSlug('blog_slug', $this->normalizeSlug($this->input->post('page_slug')), $editID);
		$data['blog_updated'] = date('Y-m-d H:i:s');
		foreach ($uploads['files'] as $c => $f) if ($f !== '') $data[$c] = $f;

		$categories = $this->validCategoryIDs();
		$this->db->trans_begin();
		$ok = $this->SqlModel->updateRecord($this->tblName, $data, array($this->pKey => $editID));
		$this->db->delete('blog_assigned_cat', array('bc_blog_id' => $editID));
		$this->insertCategories($editID, $categories);
		if (!$ok || $this->db->trans_status() === FALSE)
		{
			$this->db->trans_rollback();
			$this->cleanup($uploads['files']);
			return $this->formFailure('The blog could not be updated. Please try again.', $editID);
		}
		$this->db->trans_commit();

		foreach ($uploads['files'] as $c => $f) if ($f !== '') $this->deleteImage(isset($current[$c]) ? $current[$c] : '');
		$this->session->set_flashdata('alert', 'editsuccess');
		redirect(base_url('manage/blogs'));
	}

	public function delete($id = 0)
	{
		$ok = $this->deleteRecords(array((int) $id));
		$this->session->set_flashdata('alert', $ok ? 'deletesuccess' : 'deleteerror');
		redirect(base_url('manage/blogs'));
	}

	public function deleteall()
	{
		$ids = array_values(array_unique(array_filter(array_map('intval', (array) $this->input->post('records')))));
		$ok = !empty($ids) && $this->deleteRecords($ids);
		$this->session->set_flashdata('alert', $ok ? 'deletesuccess' : 'deleteerror');
		redirect(base_url('manage/blogs'));
	}

	public function changestatus($id = 0, $status = 'Published')
	{
		$this->output->set_content_type('application/json');
		$id = (int) $id;
		$ok = $id > 0 && in_array($status, array('Published', 'Un-Published'), TRUE) && !empty($this->record($id)) && $this->SqlModel->updateRecord($this->tblName, array($this->tStatus => $status, 'blog_updated' => date('Y-m-d H:i:s')), array($this->pKey => $id));
		return $this->output->set_output(json_encode(array('status' => $ok ? 'true' : 'false', 'id' => $id, 'currentStatus' => $ok ? $status : '')));
	}

	public function duplicate($id = 0)
	{
		$source = (int) $id;
		$data = $this->record($source);
		if (empty($data))
		{
			$this->session->set_flashdata('alert', 'error');
			return redirect(base_url('manage/blogs'));
		}

		unset($data[$this->pKey]);
		$data['blog_name'] = $this->truncate($this->display($data['blog_name']).' Duplicate', 255);
		$data['blog_slug'] = $this->uniqueSlug('blog_slug', $this->normalizeSlug($data['blog_slug'].'-copy'));
		$data['blog_added'] = $data['blog_updated'] = date('Y-m-d H:i:s');
		$cats = array_map('intval', array_column($this->SqlModel->getRecords('bc_cat_id', 'blog_assigned_cat', 'bc_id', 'ASC', array('bc_blog_id' => $source)), 'bc_cat_id'));

		$this->db->trans_begin();
		$new = $this->SqlModel->insertRecord($this->tblName, $data);
		if ($new) $this->insertCategories($new, $cats);
		if (!$new || $this->db->trans_status() === FALSE)
		{
			$this->db->trans_rollback();
			$this->session->set_flashdata('alert', 'error');
			return redirect(base_url('manage/blogs'));
		}
		$this->db->trans_commit();

		redirect(base_url('manage/blogs/control/'.$new));
	}

	public function removefile($id = 0, $key = '')
	{
		$this->output->set_content_type('application/json');
		$id = (int) $id;
		$record = $key !== 'blog_image' && in_array($key, $this->imageColumns(), TRUE) ? $this->record($id) : array();
		if (empty($record))
		{
			return $this->output->set_output(json_encode(array('success' => FALSE, 'message' => 'Invalid request.')));
		}
		if (!$this->SqlModel->updateRecord($this->tblName, array($key => '', 'blog_updated' => date('Y-m-d H:i:s')), array($this->pKey => $id)))
		{
			return $this->output->set_output(json_encode(array('success' => FALSE, 'message' => 'The record could not be updated.')));
		}
		$this->deleteImage(isset($record[$key]) ? $record[$key] : '');
		return $this->output->set_output(json_encode(array('success' => TRUE, 'id' => $id, 'key' => $key)));
	}

	private function postedData()
	{
		$data = array();
		foreach ($this->textFields() as $c => $maxLength)
		{
			// Rich text from CKEditor is kept as HTML; everything else is plain text.
			$data[$c] = $c === 'blog_text'
				? trim((string) $this->input->post($c, FALSE))
				: $this->clean($this->input->post($c), $maxLength);
		}
		foreach ($this->colorFields() as $f)
		{
			$v = $this->normalizeRgba($this->input->post($f));
			$data[$f] = $v === '' ? NULL : $v;
		}
		$data['blog_author'] = (int) $this->input->post('blog_author');
		$data['blog_service_id'] = $this->validServiceID($this->input->post('blog_service_id')) ? (int) $this->input->post('blog_service_id') : NULL;
		$data['blog_time_to_read'] = $this->validTimeToRead($this->input->post('blog_time_to_read')) ? (int) $this->input->post('blog_time_to_read') : 5;
		$data['blog_status'] = $this->input->post('blog_status') === 'Un-Published' ? 'Un-Published' : 'Published';
		$data['blog_featured'] = $this->input->post('blog_featured') === 'Yes' ? 'Yes' : 'No';
		$data['robots_index'] = $this->input->post('robots_index') === '1' ? 1 : 0;
		$data['robots_follow'] = $this->input->post('robots_follow') === '1' ? 1 : 0;
		$data['show_top_banner'] = $this->input->post('show_top_banner') === '1' ? 1 : 0;
		$data['banner_overlay'] = $this->input->post('banner_overlay') === 'No' ? 'No' : 'Yes';

		return $data;
	}

	private function validPost($current = array())
	{
		if ($this->clean($this->input->post('blog_name')) === '' || $this->clean($this->input->post('blog_short_description')) === '') return FALSE;
		if (empty($current['blog_image']) && empty($_FILES['blog_image_upload']['name'])) return FALSE;
		if (!$this->validAuthorID($this->input->post('blog_author'))) return FALSE;
		if (!$this->validTimeToRead($this->input->post('blog_time_to_read'))) return FALSE;
		if (empty($this->validCategoryIDs())) return FALSE;

		$slug = $this->normalizeSlug($this->input->post('page_slug'));
		if ($slug === '' || $this->length($slug) > 255) return FALSE;
		if (!in_array($this->input->post('blog_status'), array('Published', 'Un-Published'), TRUE) || !in_array($this->input->post('blog_featured'), array('Yes', 'No'), TRUE) || !in_array($this->input->post('banner_overlay'), array('Yes', 'No'), TRUE)) return FALSE;

		foreach ($this->colorFields() as $f)
		{
			$v = trim((string) $this->input->post($f));
			if ($v !== '' && $this->normalizeRgba($v) === '') return FALSE;
		}

		return TRUE;
	}

	private function formValues($r, $posted)
	{
		$v = array(
			'page_slug' => '',
			'blog_author' => isset($this->user_data['id']) ? (int) $this->user_data['id'] : 0,
			'blog_service_id' => 0,
			'blog_time_to_read' => 5,
			'blog_status' => 'Published',
			'blog_featured' => 'No',
			'robots_index' => 1,
			'robots_follow' => 1,
			'show_top_banner' => 0,
			'banner_overlay' => 'Yes',
			'current_blog_image' => '',
			'current_blog_cover_image' => '',
			'current_og_image' => '',
			'current_banner_background' => '',
		);
		foreach ($this->colorFields() as $f) $v[$f] = isset($r[$f]) ? $r[$f] : '';
		foreach (array('blog_status', 'blog_featured', 'robots_index', 'robots_follow', 'show_top_banner', 'banner_overlay') as $f) if (isset($r[$f])) $v[$f] = $r[$f];
		if (isset($r['blog_author']) && $this->validAuthorID($r['blog_author'])) $v['blog_author'] = (int) $r['blog_author'];
		if (isset($r['blog_time_to_read']) && $this->validTimeToRead($r['blog_time_to_read'])) $v['blog_time_to_read'] = (int) $r['blog_time_to_read'];
		if (!empty($r['blog_service_id'])) $v['blog_service_id'] = (int) $r['blog_service_id'];

		if (isset($r['blog_slug'])) $v['page_slug'] = $r['blog_slug'];
		$v['current_blog_image'] = isset($r['blog_image']) ? basename($r['blog_image']) : '';
		$v['current_blog_cover_image'] = isset($r['blog_cover_image']) ? basename($r['blog_cover_image']) : '';
		$v['current_og_image'] = isset($r['og_image']) ? basename($r['og_image']) : '';
		$v['current_banner_background'] = isset($r['banner_background']) ? basename($r['banner_background']) : '';

		if (is_array($posted)) foreach (array_keys($v) as $f) if (strpos($f, 'current_') !== 0 && array_key_exists($f, $posted)) $v[$f] = $posted[$f];

		return $v;
	}

	private function validAuthorID($authorID)
	{
		if (!ctype_digit((string) $authorID) || (int) $authorID < 1)
		{
			return FALSE;
		}

		return $this->SqlModel->countRecords(
			'admin_users',
			array('id' => (int) $authorID)
		) === 1;
	}

	/** An existing service for the optional "Related service" card. */
	private function validServiceID($serviceID)
	{
		if (!ctype_digit((string) $serviceID) || (int) $serviceID < 1)
		{
			return FALSE;
		}

		return $this->SqlModel->countRecords(
			'services',
			array('service_id' => (int) $serviceID)
		) === 1;
	}

	private function validTimeToRead($minutes)
	{
		return ctype_digit((string) $minutes) && (int) $minutes >= 1 && (int) $minutes <= 60;
	}

	private function formFailure($m, $id = 0)
	{
		$p = $this->input->post(NULL, FALSE);
		$p = is_array($p) ? $p : array();
		$this->session->set_flashdata('blogs_data', $p);
		$this->session->set_flashdata('form_error', $m);
		redirect(base_url('manage/blogs/control'.($id ? '/'.(int) $id.'' : '')));
	}

	private function configureEditor()
	{
		$this->load->library('ckeditor');
		$this->load->library('ckfinder');
		$this->ckeditor->basePath = base_url().'assets/ckeditor/';
		$this->ckeditor->config['removePlugins'] = 'save, preview, newpage, forms, flash';
		$this->ckeditor->config['height'] = '340px';
		$this->ckeditor->textareaAttributes = array('id' => 'blog_text');
		$this->ckfinder->SetupCKEditor($this->ckeditor, '../../../../assets/ckfinder/');
	}

	private function saveUploads()
	{
		$fields = array(
			'blog_image_upload' => 'blog_image',
			'blog_cover_image_upload' => 'blog_cover_image',
			'og_image_upload' => 'og_image',
			'banner_background_upload' => 'banner_background',
		);

		$r = array('files' => array(), 'error' => '');
		foreach ($fields as $f => $c)
		{
			$u = $this->upload($f);
			$r['files'][$c] = $u['filename'];
			if ($u['error'])
			{
				$r['error'] = $u['error'];
				$this->cleanup($r['files']);
				break;
			}
		}

		return $r;
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

	private function validCategoryIDs()
	{
		$ids = array_values(array_unique(array_filter(array_map('intval', (array) $this->input->post('blog_category')))));
		if (empty($ids)) return array();

		$rows = $this->db->select('cat_id')->where_in('cat_id', $ids)->get('blog_categories')->result_array();

		return array_map('intval', array_column($rows, 'cat_id'));
	}

	private function insertCategories($id, $cats)
	{
		foreach ($cats as $c) $this->db->insert('blog_assigned_cat', array('bc_cat_id' => (int) $c, 'bc_blog_id' => (int) $id));
	}

	private function deleteRecords($ids)
	{
		$ids = array_values(array_unique(array_filter(array_map('intval', $ids))));
		$rows = empty($ids) ? array() : $this->db->where_in($this->pKey, $ids)->get($this->tblName)->result_array();
		if (count($rows) !== count($ids)) return FALSE;

		$this->db->trans_begin();
		$this->db->where_in('bc_blog_id', $ids)->delete('blog_assigned_cat');
		$this->db->where_in($this->pKey, $ids)->delete($this->tblName);
		if ($this->db->trans_status() === FALSE)
		{
			$this->db->trans_rollback();
			return FALSE;
		}
		$this->db->trans_commit();

		foreach ($rows as $r) foreach ($this->imageColumns() as $c) $this->deleteImage(isset($r[$c]) ? $r[$c] : '');

		return TRUE;
	}

	private function cleanup($files)
	{
		foreach ($files as $f) if ($f !== '') $this->deleteImage($f);
	}

	private function deleteImage($f)
	{
		if (!is_string($f) || $f === '' || basename($f) !== $f) return;

		$this->db->from($this->tblName)->group_start();
		foreach ($this->imageColumns() as $i => $c) $i ? $this->db->or_where($c, $f) : $this->db->where($c, $f);
		$this->db->group_end();
		if ($this->db->count_all_results() > 0) return;

		delete_uploaded_file(FCPATH.$this->imageDirectory, $f);
	}

	private function record($id)
	{
		return (int) $id > 0 ? $this->SqlModel->getSingleRecord($this->tblName, array($this->pKey => (int) $id)) : array();
	}

	private function uniqueSlug($c, $slug, $exclude = 0)
	{
		$base = $slug ?: 'blog';
		$v = $base;
		for ($i = 2; ; $i++)
		{
			$this->db->from($this->tblName)->where('blog_slug', $v);
			if ($exclude) $this->db->where($this->pKey.' !=', (int) $exclude);
			if ($this->db->count_all_results() === 0) return $v;
			$v = $this->truncate($base.'-'.$i, 255);
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
		return array('blog_image', 'blog_cover_image', 'og_image', 'banner_background');
	}

	/**
	 * Text columns edited on the form, with their maximum length (NULL for none).
	 */
	private function textFields()
	{
		return array(
			'blog_name' => 255,
			'blog_short_description' => NULL,
			'blog_text' => NULL,
			'page_title' => 255,
			'meta_description' => NULL,
			'meta_keywords' => NULL,
			'og_title' => 255,
			'og_description' => NULL,
			'banner_title' => 255,
			'banner_heading' => 255,
			'banner_text' => NULL,
		);
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

		return sprintf('rgba(%d, %d, %d, %s)', max(0, min(255, (int) $p[0])), max(0, min(255, (int) $p[1])), max(0, min(255, (int) $p[2])), round(isset($p[3]) ? max(0, min(1, (float) $p[3])) : 1, 2));
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

	private function truncate($v, $n)
	{
		return function_exists('mb_substr') ? mb_substr((string) $v, 0, (int) $n, 'UTF-8') : substr((string) $v, 0, (int) $n);
	}

	private function length($v)
	{
		return function_exists('mb_strlen') ? mb_strlen((string) $v, 'UTF-8') : strlen((string) $v);
	}


	private function render($view, $data)
	{
		$this->load->view('admin/header', $data);
		$this->load->view('admin/navigation');
		$this->load->view('admin/'.$view);
		$this->load->view('admin/footer');
	}
}
