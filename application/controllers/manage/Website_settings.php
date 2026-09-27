<?php defined('BASEPATH') OR exit('No direct script access allowed');

class Website_settings extends CI_Controller {

    public $tblName = 'site_settings';
    public $pKey = 'id';
    public $recordId = 1;
    public $moduleName = 'Website Settings';
    public $controller = 'website-settings';
    public $listView = 'webSettings';
    public $user_data = array();

    private $textColumns = array(
        'website_title', 'website_title_ar', 'website_url', 'default_language', 'under_construction',
        'address', 'address_ar', 'phone', 'email',
        'facebook', 'twitter', 'instagram', 'linkedin', 'youtube',
        'notification_emails', 'sender_name', 'sender_name_ar', 'sender_email',
        'website_intro', 'website_intro_ar', 'foot_col_1', 'foot_col_1_ar', 'foot_col_2', 'foot_col_2_ar',
        'foot_col_3', 'foot_col_3_ar', 'foot_col_4', 'foot_col_4_ar', 'copyright_text', 'copyright_text_ar',
        'contact_text', 'contact_text_ar',
        'script_after_head', 'script_before_head', 'script_after_body', 'script_before_body',
        'currency_unit', 'currency_unit_ar',
    );

    private $fieldSections = array(
        'website_title' => 'general', 'website_title_ar' => 'general', 'website_url' => 'general',
        'default_language' => 'general', 'under_construction' => 'general',
        'address' => 'contact', 'address_ar' => 'contact', 'phone' => 'contact', 'email' => 'contact',
        'facebook' => 'social', 'twitter' => 'social', 'instagram' => 'social', 'linkedin' => 'social', 'youtube' => 'social',
        'notification_emails' => 'email', 'sender_name' => 'email', 'sender_name_ar' => 'email', 'sender_email' => 'email',
        'website_intro' => 'footer', 'website_intro_ar' => 'footer', 'foot_col_1' => 'footer', 'foot_col_1_ar' => 'footer', 'foot_col_2' => 'footer', 'foot_col_2_ar' => 'footer',
        'foot_col_3' => 'footer', 'foot_col_3_ar' => 'footer', 'foot_col_4' => 'footer', 'foot_col_4_ar' => 'footer', 'copyright_text' => 'footer', 'copyright_text_ar' => 'footer',
        'contact_text' => 'footer', 'contact_text_ar' => 'footer',
        'script_after_head' => 'scripts', 'script_before_head' => 'scripts',
        'script_after_body' => 'scripts', 'script_before_body' => 'scripts',
        'currency_unit' => 'currency', 'currency_unit_ar' => 'currency',
    );

    /**
     * Columns escaped before storage. Kept minimal and matched to the
     * previous behavior: only plain identity text, never URLs/scripts/free
     * text, since htmlspecialchars() would corrupt those on save.
     */
    private $escapedColumns = array('website_title', 'website_title_ar');

    private $uploadFields = array(
        'uploadfile'  => array('column' => 'logo', 'directory' => 'assets/frontend/images/logo/', 'max_width' => 4000, 'max_height' => 4000, 'section' => 'branding', 'required' => TRUE),
        'uploadfile2' => array('column' => 'logo_sticky', 'directory' => 'assets/frontend/images/logo/', 'max_width' => 4000, 'max_height' => 4000, 'section' => 'branding', 'required' => FALSE),
        'uploadfile5' => array('column' => 'logo_white', 'directory' => 'assets/frontend/images/logo/', 'max_width' => 4000, 'max_height' => 4000, 'section' => 'branding', 'required' => TRUE),
        'uploadfile6' => array('column' => 'favicon', 'directory' => 'assets/frontend/images/logo/', 'max_width' => 4000, 'max_height' => 4000, 'section' => 'branding', 'required' => TRUE),
        'uploadfile3' => array('column' => 'default_bg', 'directory' => 'assets/frontend/images/bg/', 'max_width' => 0, 'max_height' => 0, 'section' => 'backgrounds', 'required' => FALSE),
        'uploadfile4' => array('column' => 'default_bg_ar', 'directory' => 'assets/frontend/images/bg/', 'max_width' => 0, 'max_height' => 0, 'section' => 'backgrounds', 'required' => FALSE),
    );

    private $uploadAllowedTypes = 'jpg|jpeg|png';
    private $uploadMaxSizeKb = 10240;

    public function __construct()
    {
        parent::__construct();
        $this->user_data = $this->SqlModel->authAdmin($this->session->userdata('admin_auth'), $this->session->userdata('admin_id'));
        if (empty($this->user_data))
        {
            redirect(base_url('manage/login'));
            return;
        }
        if ($this->SqlModel->checkAccess('access_settings', $this->user_data) === FALSE)
        {
            redirect(ADMIN_URL);
        }
    }

    public function index()
    {
        $record = $this->SqlModel->getSingleRecord($this->tblName, array($this->pKey => $this->recordId));
        $record = is_array($record) ? $record : array();

        $posted = $this->session->flashdata($this->controller.'_data');
        if (is_array($posted))
        {
            $record = array_merge($record, $posted);
        }

        $data = array(
            'wsettingActive' => 1,
            'page_title' => PROJECT_TITLE.' | '.$this->moduleName,
            'alert' => $this->session->flashdata('alert'),
            'invalid_fields' => (array) $this->session->flashdata('invalid_fields'),
            'error_sections' => (array) $this->session->flashdata('error_sections'),
            'userdata' => $this->user_data,
            'web' => $record,
            'useWebsiteSettings' => TRUE,
        );
        $this->load->view('admin/header', $data);
        $this->load->view('admin/navigation');
        $this->load->view('admin/'.$this->listView);
        $this->load->view('admin/footer');
    }

    public function save()
    {
        $current = $this->SqlModel->getSingleRecord($this->tblName, array($this->pKey => $this->recordId));
        if (empty($current))
        {
            $this->session->set_flashdata('alert', 'database_error');
            redirect(base_url('manage/'.$this->controller));
            return;
        }

        list($values, $invalidFields, $errorSections) = $this->validatedTextValues();

        $this->load->library('upload');
        $uploads = array();
        $uploadFailed = FALSE;
        foreach ($this->uploadFields as $field => $config)
        {
            $result = $this->uploadImage($field, $config);
            if ($result['error'] !== '')
            {
                $uploadFailed = TRUE;
                $invalidFields[] = $field;
                $errorSections[$config['section']] = TRUE;
                continue;
            }
            if ($result['filename'] !== '')
            {
                $uploads[$config['column']] = array('filename' => $result['filename'], 'directory' => $config['directory'], 'previous' => $current[$config['column']]);
            }
            elseif (!empty($config['required']) && empty($current[$config['column']]))
            {
                $invalidFields[] = $field;
                $errorSections[$config['section']] = TRUE;
            }
        }

        if ($uploadFailed || !empty($invalidFields))
        {
            $this->deleteUploadedFiles($uploads, TRUE);
            $status = $uploadFailed ? 'upload_error' : 'validation_error';
            return $this->formFailure($values, $invalidFields, $errorSections, $status);
        }

        $data = $values;
        foreach ($uploads as $column => $upload)
        {
            $data[$column] = $upload['filename'];
        }

        $this->db->trans_begin();
        $updated = $this->SqlModel->updateRecord($this->tblName, $data, array($this->pKey => $this->recordId));
        if (!$updated || $this->db->trans_status() === FALSE)
        {
            $this->db->trans_rollback();
            $this->deleteUploadedFiles($uploads, TRUE);
            return $this->formFailure($values, array(), array(), 'database_error');
        }
        $this->db->trans_commit();

        $this->deleteUploadedFiles($uploads, FALSE);

        $this->session->set_flashdata('alert', 'success');
        redirect(base_url('manage/'.$this->controller));
    }

    private function formFailure($values, $invalidFields, $errorSections, $status)
    {
        $this->session->set_flashdata($this->controller.'_data', $values);
        $this->session->set_flashdata('invalid_fields', $invalidFields);
        $this->session->set_flashdata('error_sections', array_keys($errorSections));
        $this->session->set_flashdata('alert', $status);
        redirect(base_url('manage/'.$this->controller));
    }

    /**
     * Reads, sanitizes, and validates the plain text/select fields. Returns
     * [values, invalidFields, errorSections] so the caller can merge in
     * upload errors before deciding whether the record can be saved.
     */
    private function validatedTextValues()
    {
        $values = array();
        $invalidFields = array();
        $errorSections = array();

        foreach ($this->textColumns as $column)
        {
            $raw = $this->input->post($column);
            if (is_array($raw))
            {
                $invalidFields[] = $column;
                $errorSections[$this->fieldSections[$column]] = TRUE;
                continue;
            }
            $values[$column] = trim((string) $raw);
        }

        foreach (array('website_title', 'website_title_ar') as $requiredColumn)
        {
            if (isset($values[$requiredColumn]) && ($values[$requiredColumn] === '' || strlen($values[$requiredColumn]) > 255))
            {
                $invalidFields[] = $requiredColumn;
                $errorSections['general'] = TRUE;
            }
        }

        if (isset($values['website_url']) && ($values['website_url'] === '' || !$this->isValidLooseUrl($values['website_url'])))
        {
            $invalidFields[] = 'website_url';
            $errorSections['general'] = TRUE;
        }

        if (isset($values['default_language']) && !in_array($values['default_language'], array('English', 'Arabic'), TRUE))
        {
            $invalidFields[] = 'default_language';
            $errorSections['general'] = TRUE;
        }

        if (isset($values['under_construction']) && !in_array($values['under_construction'], array('Yes', 'No'), TRUE))
        {
            $invalidFields[] = 'under_construction';
            $errorSections['general'] = TRUE;
        }

        if (isset($values['email']) && ($values['email'] === '' || filter_var($values['email'], FILTER_VALIDATE_EMAIL) === FALSE))
        {
            $invalidFields[] = 'email';
            $errorSections['contact'] = TRUE;
        }

        if (isset($values['phone']) && !$this->isValidPhone($values['phone']))
        {
            $invalidFields[] = 'phone';
            $errorSections['contact'] = TRUE;
        }

        foreach (array('facebook', 'twitter', 'instagram', 'linkedin', 'youtube') as $social)
        {
            if (isset($values[$social]) && !$this->isValidLooseUrl($values[$social]))
            {
                $invalidFields[] = $social;
                $errorSections['social'] = TRUE;
            }
        }

        if (isset($values['notification_emails']) && !$this->isValidEmailList($values['notification_emails']))
        {
            $invalidFields[] = 'notification_emails';
            $errorSections['email'] = TRUE;
        }

        foreach (array('sender_name', 'sender_name_ar') as $senderColumn)
        {
            if (isset($values[$senderColumn]) && strlen($values[$senderColumn]) > 255)
            {
                $invalidFields[] = $senderColumn;
                $errorSections['email'] = TRUE;
            }
        }

        if (isset($values['sender_email']) && $values['sender_email'] !== '' && filter_var($values['sender_email'], FILTER_VALIDATE_EMAIL) === FALSE)
        {
            $invalidFields[] = 'sender_email';
            $errorSections['email'] = TRUE;
        }

        foreach (array('foot_col_1', 'foot_col_1_ar', 'foot_col_2', 'foot_col_2_ar', 'foot_col_3', 'foot_col_3_ar', 'foot_col_4', 'foot_col_4_ar', 'copyright_text', 'copyright_text_ar') as $footerColumn)
        {
            if (isset($values[$footerColumn]) && strlen($values[$footerColumn]) > 255)
            {
                $invalidFields[] = $footerColumn;
                $errorSections['footer'] = TRUE;
            }
        }

        foreach (array('currency_unit', 'currency_unit_ar') as $requiredColumn)
        {
            if (isset($values[$requiredColumn]) && ($values[$requiredColumn] === '' || strlen($values[$requiredColumn]) > 255))
            {
                $invalidFields[] = $requiredColumn;
                $errorSections['currency'] = TRUE;
            }
        }


        foreach ($this->escapedColumns as $escapedColumn)
        {
            if (isset($values[$escapedColumn]))
            {
                $values[$escapedColumn] = htmlspecialchars($values[$escapedColumn], ENT_QUOTES, 'UTF-8');
            }
        }

        return array($values, $invalidFields, $errorSections);
    }

    /**
     * Same format the intl-tel-input widget submits: it rewrites the field
     * to the full E.164 number (leading "+", country code, no separators)
     * right before the form submits, once it recognizes a valid number.
     */
    private function isValidPhone($value)
    {
        return preg_match('/^\+[1-9][0-9]{6,14}$/', $value) === 1;
    }

    /**
     * One email address per line. Blank lines are ignored so the field can
     * be reformatted freely; every non-blank line must be a valid address.
     */
    private function isValidEmailList($value)
    {
        if ($value === '')
        {
            return TRUE;
        }
        foreach (preg_split('/\r\n|\r|\n/', $value) as $line)
        {
            $line = trim($line);
            if ($line !== '' && filter_var($line, FILTER_VALIDATE_EMAIL) === FALSE)
            {
                return FALSE;
            }
        }
        return TRUE;
    }

    /**
     * Loose URL validator: existing stored values such as "www.example.com"
     * have no scheme, so a bare FILTER_VALIDATE_URL check would reject data
     * this module has always accepted.
     */
    private function isValidLooseUrl($value)
    {
        if ($value === '')
        {
            return TRUE;
        }
        if (strlen($value) > 255)
        {
            return FALSE;
        }
        $candidate = preg_match('#^[a-z][a-z0-9+.-]*://#i', $value) ? $value : 'http://'.$value;
        return filter_var($candidate, FILTER_VALIDATE_URL) !== FALSE;
    }

    private function uploadImage($field, $config)
    {
        $result = array('filename' => '', 'error' => '');
        if (empty($_FILES[$field]['name']))
        {
            return $result;
        }
        if (!isset($_FILES[$field]['error']) || $_FILES[$field]['error'] !== UPLOAD_ERR_OK)
        {
            $result['error'] = 'The upload did not complete successfully.';
            return $result;
        }

        $uploadPath = FCPATH.str_replace('/', DIRECTORY_SEPARATOR, trim($config['directory'], '/\\')).DIRECTORY_SEPARATOR;
        if (!is_dir($uploadPath) && !mkdir($uploadPath, 0755, TRUE))
        {
            $result['error'] = 'The upload directory could not be created.';
            return $result;
        }

        $settings = array(
            'upload_path' => $uploadPath,
            'allowed_types' => $this->uploadAllowedTypes,
            'max_size' => $this->uploadMaxSizeKb,
            'encrypt_name' => TRUE,
            'remove_spaces' => TRUE,
        );
        if ((int) $config['max_width'] > 0)
        {
            $settings['max_width'] = $config['max_width'];
        }
        if ((int) $config['max_height'] > 0)
        {
            $settings['max_height'] = $config['max_height'];
        }

        $this->upload->initialize($settings, TRUE);
        if (!$this->upload->do_upload($field))
        {
            $result['error'] = strip_tags($this->upload->display_errors('', ''));
            return $result;
        }

        $file = $this->upload->data();
        $result['filename'] = $file['file_name'];
        return $result;
    }

    private function deleteUploadedFiles($uploads, $deleteNew)
    {
        foreach ($uploads as $upload)
        {
            $filename = $deleteNew ? $upload['filename'] : $upload['previous'];
            delete_uploaded_file(FCPATH.str_replace('/', DIRECTORY_SEPARATOR, trim($upload['directory'], '/\\')), $filename);
        }
    }
}
