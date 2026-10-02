<?php defined('BASEPATH') OR exit('No direct script access allowed');

class Admins extends CI_Controller {

	public $tblName = 'admin_users';
	public $colPrefix = '';
	public $pKey = 'id';
	public $moduleName = 'Administrators';
	public $moduleNameSingular = 'Administrator';
	public $moduleDesc = 'Manage administrator identities, contact details, account access, and profile avatars.';
	public $controller = 'admins';
	public $per_page = 10;
	public $tStatus = 'status';
	public $listView = 'admins';
	public $addEditView = 'addAdmin';
	public $user_data = array();

	public function __construct()
	{
		parent::__construct();
		$this->user_data = $this->SqlModel->authAdmin($this->session->userdata('admin_auth'), $this->session->userdata('admin_id'));
		if (empty($this->user_data)) redirect(base_url('manage/login'));
		if (!isset($this->user_data['user_role']) || $this->user_data['user_role'] !== 'Super Admin') redirect(base_url('manage'));
	}

	public function index($sortby = 'full_name', $order = 'ASC', $status = '-', $keywords = '-', $pg_no = '')
	{
		$allowedSorts = array($this->pKey, 'user_name', 'full_name', 'email', 'phone', $this->tStatus, 'date_created', 'last_modified');
		$sortby = in_array($sortby, $allowedSorts, TRUE) ? $sortby : 'full_name';
		$order = strtoupper($order) === 'DESC' ? 'DESC' : 'ASC';
		$status = in_array($status, array('Enable', 'Disable'), TRUE) ? $status : '-';
		$allowedPerPage = array_merge(array(0), range(10, 100, 10));
		$requestedPerPage = $this->input->get('per_page');
		if ($requestedPerPage !== NULL && ctype_digit((string) $requestedPerPage) && in_array((int) $requestedPerPage, $allowedPerPage, TRUE)) $this->session->set_userdata('per_page', (int) $requestedPerPage);
		if ($this->session->userdata('per_page') !== NULL && in_array((int) $this->session->userdata('per_page'), $allowedPerPage, TRUE)) $this->per_page = (int) $this->session->userdata('per_page');

		$keywords = urldecode((string) $keywords);
		$where = $status === '-' ? array() : array($this->tStatus => $status);
		$search = $keywords !== '-' ? array('cols' => 'user_name,full_name,email,phone', 'value' => $keywords) : array();
		$baseUrl = base_url('manage/'.$this->controller.'/index/'.$sortby.'/'.$order.'/'.$status.'/'.rawurlencode($keywords));
		$totalRows = $this->SqlModel->countRecords($this->tblName, $where, $search);
		$uriSegment = 8;
		$offset = max(0, (int) $this->uri->segment($uriSegment, 0));
		$this->pagination->initialize(admin_pagination_config($baseUrl, $totalRows, $this->per_page, $uriSegment));
		$records = $this->SqlModel->getRecords('*', $this->tblName, $sortby, $order, $where, $search, $this->per_page, $offset, FALSE);
		$data = array(
			'alert' => $this->session->flashdata('alert'), 'page_title' => PROJECT_TITLE.' | '.$this->moduleName,
			'userdata' => $this->user_data, 'adminsActive' => 1, 'total_rows' => $totalRows,
			'per_page' => $this->per_page, 'records' => $records, 'paginate' => $this->pagination->create_links(),
			'sortby' => $sortby, 'order' => $order === 'ASC' ? 'DESC' : 'ASC', 'page_numb' => $offset,
			'status' => $status, 'keywords' => $keywords, 'current_admin_id' => $this->currentAdminId(),
			'active_admin_count' => $this->activeAdminCount(),
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
		if (is_array($posted)) foreach (array('full_name', 'email', 'phone', 'user_name', 'status') as $field) if (array_key_exists($field, $posted)) $record[$field] = $posted[$field];
		$data = array(
			'adminsActive' => 1, 'alert' => $isEdit ? 'edit' : '', 'form_error' => $this->session->flashdata('form_error'),
			'tbl_data' => $record, 'page_title' => PROJECT_TITLE.' | '.($isEdit ? 'Edit' : 'Add').' '.$this->moduleNameSingular,
			'userdata' => $this->user_data, 'current_admin_id' => $this->currentAdminId(),
			'active_admin_count' => $this->activeAdminCount(),
		);
		$this->render($this->addEditView, $data);
	}

	public function addRecord()
	{
		$error = $this->validationError(FALSE);
		if ($error !== '') return $this->formFailure($error);
		$avatar = $this->saveAvatarUpload();
		if ($avatar['error'] !== '') return $this->formFailure($avatar['error']);
		$data = $this->postedAdminData();
		$password = (string) $this->input->post('pwd');
		$data['pwd'] = password_hash($password, PASSWORD_DEFAULT);
		$data['user_role'] = 'Super Admin';
		$data['date_created'] = date('Y-m-d H:i:s');
		if ($avatar['filename'] !== '') $data['avatar'] = $avatar['filename'];
		$this->db->trans_begin();
		$id = $this->SqlModel->insertRecord($this->tblName, $data);
		if (!$id || $this->db->trans_status() === FALSE)
		{
			$this->db->trans_rollback();
			$this->deleteAvatarFile($avatar['filename']);
			return $this->formFailure('The administrator could not be saved. Please try again.');
		}
		$this->db->trans_commit();
		if ($this->input->post('notify_account_email') === '1')
		{
			$data['id'] = $id;
			if (!$this->sendAccountInfoEmail($data, $password, FALSE))
			{
				log_message('error', 'Account information email failed for administrator ID '.$id.'.');
			}
		}
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
		$error = $this->validationError(TRUE, $editID, $current);
		if ($error !== '') return $this->formFailure($error, $editID);
		$avatar = $this->saveAvatarUpload();
		if ($avatar['error'] !== '') return $this->formFailure($avatar['error'], $editID);
		$data = $this->postedAdminData();
		$data['user_role'] = isset($current['user_role']) && in_array($current['user_role'], array('Admin', 'Super Admin'), TRUE) ? $current['user_role'] : 'Super Admin';
		$data['last_modified'] = date('Y-m-d H:i:s');
		$password = (string) $this->input->post('pwd');
		if ($password !== '') $data['pwd'] = password_hash($password, PASSWORD_DEFAULT);
		if ($avatar['filename'] !== '') $data['avatar'] = $avatar['filename'];
		else if ($this->input->post('remove_avatar') === '1') $data['avatar'] = NULL;
		$this->db->trans_begin();
		$updated = $this->SqlModel->updateRecord($this->tblName, $data, array($this->pKey => $editID));
		if (!$updated || $this->db->trans_status() === FALSE)
		{
			$this->db->trans_rollback();
			$this->deleteAvatarFile($avatar['filename']);
			return $this->formFailure('The administrator could not be updated. Please try again.', $editID);
		}
		$this->db->trans_commit();
		if (($avatar['filename'] !== '' || $this->input->post('remove_avatar') === '1') && isset($current['avatar']) && $current['avatar'] !== $avatar['filename']) $this->deleteAvatarFile($current['avatar']);
		if ($password !== '' && $this->input->post('notify_account_email') === '1')
		{
			$data['id'] = $editID;
			if (!$this->sendAccountInfoEmail($data, $password, TRUE))
			{
				log_message('error', 'Account information email failed for administrator ID '.$editID.'.');
			}
		}
		$this->session->set_flashdata('alert', 'editsuccess');
		redirect(base_url('manage/'.$this->controller));
	}

	public function delete($deleteID = '')
	{
		$result = $this->deleteAdminRecords(array((int) $deleteID));
		$this->session->set_flashdata('alert', $result === 'success' ? 'deletesuccess' : ($result === 'protected' ? 'protected' : 'deleteerror'));
		redirect(base_url('manage/'.$this->controller));
	}

	public function deleteall()
	{
		$ids = array_values(array_unique(array_filter(array_map('intval', (array) $this->input->post('records')))));
		$result = $this->deleteAdminRecords($ids);
		$this->session->set_flashdata('alert', $result === 'success' ? 'deletesuccess' : ($result === 'protected' ? 'protected' : 'deleteerror'));
		redirect(base_url('manage/'.$this->controller));
	}

	public function changestatus($id = 0, $status = 'Enable')
	{
		$this->output->set_content_type('application/json');
		$id = (int) $id;
		$record = $id > 0 ? $this->SqlModel->getSingleRecord($this->tblName, array($this->pKey => $id)) : array();
		if (empty($record) || !in_array($status, array('Enable', 'Disable'), TRUE)) return $this->jsonStatus(FALSE);
		if ($status === 'Disable' && ($id === $this->currentAdminId() || ($record[$this->tStatus] === 'Enable' && $this->activeAdminCount() <= 1))) return $this->jsonStatus(FALSE, $id, $record[$this->tStatus], 'This administrator account must remain enabled.');
		if (!$this->SqlModel->updateRecord($this->tblName, array($this->tStatus => $status, 'last_modified' => date('Y-m-d H:i:s')), array($this->pKey => $id))) return $this->jsonStatus(FALSE);
		return $this->jsonStatus(TRUE, $id, $status);
	}

	private function sendAccountInfoEmail(array $admin, $password, $isPasswordChange)
	{
		$email = trim((string) (isset($admin['email']) ? $admin['email'] : ''));
		if ($email === '') return FALSE;
		$settings = $this->SqlModel->getSingleRecord('site_settings', array('id' => 1));
		if (empty($settings))
		{
			log_message('error', 'Unable to send account information email: site settings are missing.');
			return FALSE;
		}

		$safeName = htmlspecialchars((string) $admin['full_name'], ENT_QUOTES, 'UTF-8');
		$safeUsername = htmlspecialchars((string) $admin['user_name'], ENT_QUOTES, 'UTF-8');
		$safePassword = htmlspecialchars((string) $password, ENT_QUOTES, 'UTF-8');
		$safeProject = htmlspecialchars(PROJECT_TITLE, ENT_QUOTES, 'UTF-8');
		$loginUrl = ADMIN_URL.'login';
		$safeLoginUrl = htmlspecialchars($loginUrl, ENT_QUOTES, 'UTF-8');

		$intro = $isPasswordChange
			? 'The password for your administration account has been changed. Your updated sign-in details are below.'
			: 'An administration account has been created for you. Your sign-in details are below.';

		$body =
			'Hello '.$safeName.',<br><br>'.
			$intro.'<br>'.
			'<table cellpadding="0" cellspacing="0" style="margin:16px 0"><tr><td style="padding:4px 12px 4px 0"><strong>Username:</strong></td><td>'.$safeUsername.'</td></tr>'.
			'<tr><td style="padding:4px 12px 4px 0"><strong>Password:</strong></td><td>'.$safePassword.'</td></tr></table>'.
			'For security, please sign in and change your password as soon as possible.'.
			'<div style="margin:24px 0;text-align:center">'.
			'<a href="'.$safeLoginUrl.'" style="display:inline-block;padding:12px 20px;border-radius:6px;'.
			'background:#a73a9b;color:#fff;text-decoration:none;font-weight:700">Sign in</a>'.
			'</div>'.
			'If the button does not work, copy this link into your browser:<br>'.
			'<a href="'.$safeLoginUrl.'">'.$safeLoginUrl.'</a><br><br>'.
			'If you did not expect this email, contact the site administrator immediately.<br><br>'.
			'Admin Team,<br>'.$safeProject;

		$subject = $isPasswordChange
			? 'Your '.$settings['website_title'].' CMS password was changed'
			: 'Your '.$settings['website_title'].' CMS account details';

		$this->load->library('EmailService');
		$message = $this->emailservice->renderTemplate(array(
			'site_settings' => $settings,
			'heading' => $subject,
			'body' => $body,
		));

		if ($message === FALSE)
		{
			log_message('error', 'Account information email template could not be rendered.');
			return FALSE;
		}

		$altMessage = html_entity_decode(strip_tags(preg_replace('/<br\s*\/?>/i', "\n", $body)), ENT_QUOTES, 'UTF-8');
		$sent = $this->emailservice->send(array(
			'to' => $email,
			'subject' => $subject,
			'message' => $message,
			'alt_message' => $altMessage,
			'config' => array('useragent' => trim($settings['website_title'])),
		));

		if (!$sent) log_message('error', 'Account information email failed: '.$this->emailservice->getLastError());
		return $sent;
	}

	private function render($view, $data)
	{
		$this->load->view('admin/header', $data);
		$this->load->view('admin/navigation');
		$this->load->view('admin/'.$view);
		$this->load->view('admin/footer');
	}

	private function postedAdminData()
	{
		$status = $this->input->post($this->tStatus);
		return array(
			'full_name' => $this->truncate(trim((string) $this->input->post('full_name')), 100),
			'email' => $this->truncate(trim((string) $this->input->post('email')), 100),
			'phone' => $this->truncate(trim((string) $this->input->post('phone')), 50),
			'user_name' => $this->truncate(trim((string) $this->input->post('user_name')), 100),
			$this->tStatus => in_array($status, array('Enable', 'Disable'), TRUE) ? $status : 'Disable',
		);
	}

	private function validationError($isEdit, $editID = 0, $current = array())
	{
		$fullName = trim((string) $this->input->post('full_name'));
		$email = trim((string) $this->input->post('email'));
		$phone = trim((string) $this->input->post('phone'));
		$username = trim((string) $this->input->post('user_name'));
		$password = (string) $this->input->post('pwd');
		$confirmation = (string) $this->input->post('pwd2');
		$status = (string) $this->input->post($this->tStatus);
		if ($fullName === '' || $email === '' || $username === '') return 'Enter the administrator name, email address, and username.';
		if ($this->stringLength($fullName) > 100 || $this->stringLength($email) > 100 || $this->stringLength($phone) > 50 || $this->stringLength($username) > 100) return 'One or more values exceed the allowed length.';
		if (!filter_var($email, FILTER_VALIDATE_EMAIL)) return 'Enter a valid email address.';
		if (!preg_match('/^[A-Za-z0-9._-]{3,100}$/', $username)) return 'Use 3 to 100 letters, numbers, dots, underscores, or hyphens for the username.';
		if (!in_array($status, array('Enable', 'Disable'), TRUE)) return 'Choose a valid account status.';
		if ((!$isEdit || $password !== '') && (strlen($password) < 8 || strlen($password) > 72)) return 'Use a password between 8 and 72 characters.';
		if ((!$isEdit || $password !== '') && !hash_equals($password, $confirmation)) return 'The password confirmation does not match.';
		if ($this->duplicateIdentityExists($username, $email, (int) $editID)) return 'That username or email address is already in use.';
		if ($isEdit && (int) $editID === $this->currentAdminId() && $status !== 'Enable') return 'Your signed-in administrator account must remain enabled.';
		if ($isEdit && !empty($current) && $current[$this->tStatus] === 'Enable' && $status === 'Disable' && $this->activeAdminCount() <= 1) return 'At least one administrator account must remain enabled.';
		return '';
	}

	private function duplicateIdentityExists($username, $email, $excludeID = 0)
	{
		$this->db->from($this->tblName)->group_start()->where('user_name', $username)->or_where('email', $email)->group_end();
		if ($excludeID > 0) $this->db->where($this->pKey.' !=', $excludeID);
		return $this->db->count_all_results() > 0;
	}

	private function formFailure($message, $editID = 0)
	{
		$posted = $this->input->post(NULL, FALSE);
		$safe = array();
		foreach (array('full_name', 'email', 'phone', 'user_name', 'status') as $field) if (is_array($posted) && array_key_exists($field, $posted)) $safe[$field] = $posted[$field];
		$this->session->set_flashdata($this->controller.'_data', $safe);
		$this->session->set_flashdata('form_error', $message);
		redirect(base_url('manage/'.$this->controller.'/control'.($editID > 0 ? '/edit/'.(int) $editID : '')));
	}

	private function deleteAdminRecords(array $ids)
	{
		$ids = array_values(array_unique(array_filter(array_map('intval', $ids))));
		if (empty($ids) || in_array($this->currentAdminId(), $ids, TRUE)) return 'protected';
		$records = $this->db->select($this->pKey.', '.$this->tStatus.', avatar')->where_in($this->pKey, $ids)->get($this->tblName)->result_array();
		if (count($records) !== count($ids)) return 'error';
		$activeSelected = 0;
		foreach ($records as $record) if ($record[$this->tStatus] === 'Enable') $activeSelected++;
		if ($this->activeAdminCount() - $activeSelected < 1) return 'protected';
		$this->db->trans_begin();
		$this->db->where_in($this->pKey, $ids)->delete($this->tblName);
		$deleted = $this->db->affected_rows();
		if ($this->db->trans_status() === FALSE || $deleted !== count($ids))
		{
			$this->db->trans_rollback();
			return 'error';
		}
		$this->db->trans_commit();
		foreach ($records as $record) $this->deleteAvatarFile(isset($record['avatar']) ? $record['avatar'] : '');
		return 'success';
	}

	private function saveAvatarUpload()
	{
		$result = array('filename' => '', 'error' => '');
		if (empty($_FILES['avatar']['name'])) return $result;
		$uploadPath = FCPATH.'assets/frontend/images/admins/';
		if (!is_dir($uploadPath) && !mkdir($uploadPath, 0755, TRUE))
		{
			$result['error'] = 'The avatar storage directory could not be created.';
			return $result;
		}
		$config = array('upload_path' => $uploadPath, 'allowed_types' => UPLOAD_IMAGE_MIMES, 'max_size' => AVATAR_UPLOAD_MAX_MB * 1024, 'max_width' => 1024, 'max_height' => 1024, 'encrypt_name' => TRUE, 'remove_spaces' => TRUE);
		$this->load->library('upload');
		$this->upload->initialize($config);
		if (!$this->upload->do_upload('avatar'))
		{
			$result['error'] = strip_tags($this->upload->display_errors('', ''));
			return $result;
		}
		$file = $this->upload->data();
		$result['filename'] = $file['file_name'];
		return $result;
	}

	private function deleteAvatarFile($filename)
	{
		if (!is_string($filename) || $filename === '' || basename($filename) !== $filename) return;
		delete_uploaded_file(FCPATH.'assets/frontend/images/admins', $filename);
	}

	private function activeAdminCount()
	{
		return (int) $this->db->where($this->tStatus, 'Enable')->count_all_results($this->tblName);
	}

	private function currentAdminId()
	{
		return (int) $this->session->userdata('admin_id');
	}

	private function jsonStatus($success, $id = 0, $currentStatus = '', $message = '')
	{
		$payload = array('status' => $success ? 'true' : 'false');
		if ($id > 0) $payload['id'] = (int) $id;
		if ($currentStatus !== '') $payload['currentStatus'] = $currentStatus;
		if ($message !== '') $payload['message'] = $message;
		return $this->output->set_output(json_encode($payload));
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
