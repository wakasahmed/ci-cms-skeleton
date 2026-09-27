<?php defined('BASEPATH') OR exit('No direct script access allowed');

class Email_templates extends CI_Controller
{
    public $tblName = 'email_templates';
    public $pKey = 'id';
    public $moduleName = 'Email Templates';
    public $moduleNameSingular = 'Email Template';
    public $moduleDesc = 'Manage reusable English and Arabic email subjects and content.';
    public $controller = 'email-templates';
    public $per_page = 10;
    public $listView = 'emailTemplates';
    public $addEditView = 'addEmailTemplate';
    public $user_data = array();
    private $translationModule = 'email_templates';

    public function __construct()
    {
        parent::__construct();
        $this->user_data = $this->SqlModel->authAdmin($this->session->userdata('admin_auth'), $this->session->userdata('admin_id'));
        if (empty($this->user_data))
        {
            redirect(base_url('manage/login'));
            return;
        }
        $this->load->library('manage_translation_service');
    }

    public function index($sortby = 'id', $order = 'ASC', $keywords = '-', $pgNo = '')
    {
        $allowedSorts = array($this->pKey, 'name', 'subject', 'heading');
        $sortby = in_array($sortby, $allowedSorts, TRUE) ? $sortby : 'name';
        $order = strtoupper($order) === 'DESC' ? 'DESC' : 'ASC';

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
        $search = $keywords !== '-' ? array('cols' => 'name,subject,subject_ar,heading,heading_ar', 'value' => $keywords) : array();
        $baseUrl = base_url('manage/'.$this->controller.'/index/'.$sortby.'/'.$order.'/'.rawurlencode($keywords));
        $totalRows = $this->SqlModel->countRecords($this->tblName, array(), $search);
        $uriSegment = 7;
        $offset = (int) $this->uri->segment($uriSegment, 0);

        $this->pagination->initialize(admin_pagination_config($baseUrl, $totalRows, $this->per_page, $uriSegment));
        $recordSort = $sortby === 'name' ? 'name,'.$this->pKey : $sortby;
        $records = $this->SqlModel->getRecords('*', $this->tblName, $recordSort, $order, array(), $search, $this->per_page, $offset, FALSE);
        $recordIds = array();
        foreach ($records as $record) $recordIds[] = (int) $record[$this->pKey];
        $translationStatuses = empty($recordIds) ? array() : $this->manage_translation_service->statuses($this->translationModule, $recordIds);

        $data = array(
            'alert' => $this->session->flashdata('alert'), 'page_title' => PROJECT_TITLE.' | '.$this->moduleName,
            'userdata' => $this->user_data, 'emailTemplatesActive' => 1,
            'total_rows' => $totalRows, 'per_page' => $this->per_page,
            'records' => $records, 'translation_statuses' => $translationStatuses,
            'paginate' => $this->pagination->create_links(), 'sortby' => $sortby,
            'order' => $order === 'ASC' ? 'DESC' : 'ASC', 'page_numb' => $offset,
            'keywords' => $keywords, 'useManageTranslations' => TRUE,
        );
        $this->render($this->listView, $data);
    }

    public function control($alert = '', $editID = '')
    {
        $isEdit = ($alert === 'edit');

        // Email templates are system-defined: they can only be edited, never added.
        if (!$isEdit)
        {
            redirect(base_url('manage/'.$this->controller));
            return;
        }

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
        if (is_array($postedData) && array_key_exists('name', $postedData) && !is_array($postedData['name']))
        {
            $viewRecord['name'] = $postedData['name'];
        }

        $localizedValues = $this->manage_translation_service->localized_values($this->translationModule, $record, $activeLocale);
        if (is_array($postedData))
        {
            foreach (array_keys($localizedValues) as $control)
            {
                if (array_key_exists($control, $postedData) && !is_array($postedData[$control])) $localizedValues[$control] = $postedData[$control];
            }
        }

        $translationState = $isEdit ? $this->manage_translation_service->state($this->translationModule, $editID) : NULL;
        $this->configureEditor($activeLocale);
        $this->load->library('Short_tags');

        $data = array(
            'emailTemplatesActive' => 1, 'alert' => $isEdit ? 'edit' : '',
            'form_error' => $this->session->flashdata('form_error'), 'tbl_data' => $viewRecord,
            'page_title' => PROJECT_TITLE.' | '.($isEdit ? 'Edit' : 'Add').' '.$this->moduleNameSingular,
            'userdata' => $this->user_data,
            'localized_values' => $localizedValues, 'active_locale' => $activeLocale,
            'manage_locales' => $this->manage_translation_service->locales(),
            'translation_state' => $translationState, 'useManageTranslations' => TRUE,
            'short_tags' => $this->short_tags->forTemplate($editID, Short_tags::CHANNEL_EMAIL),
            'short_tag_entity' => $this->short_tags->entityLabel($editID),
            'useShortTagPicker' => TRUE,
            'useSweetAlert' => TRUE,
        );
        $this->render($this->addEditView, $data);
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

        $activeLocale = $this->manage_translation_service->locale($this->input->post('active_locale', TRUE));
        $error = $this->formErrorMessage();
        if ($error !== '') return $this->formFailure($error, $editID, $activeLocale);

        $data = $this->postedData($activeLocale);
        $data['name'] = $this->truncate($this->scalarString('name'), 255);

        $this->db->trans_begin();
        $updated = $this->SqlModel->updateRecord($this->tblName, $data, array($this->pKey => $editID));
        if (!$updated || $this->db->trans_status() === FALSE)
        {
            $this->db->trans_rollback();
            return $this->formFailure('The email template could not be updated. Please try again.', $editID, $activeLocale);
        }
        $this->db->trans_commit();

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

    private function configureEditor($locale)
    {
        $this->load->library('ckeditor');
        $this->load->library('ckfinder');
        $this->ckeditor->basePath = base_url().'assets/ckeditor/';
        $this->ckeditor->config['removePlugins'] = 'save, preview, newpage, forms, flash';
        $this->ckeditor->config['height'] = '340px';
        $this->ckeditor->config['contentsLangDirection'] = $locale === 'ar' ? 'rtl' : 'ltr';
        $this->ckeditor->textareaAttributes = array(
            'id' => 'localized_contents',
            'data-translation-field' => 'localized_contents',
            'data-validate' => 'htmlrequired',
            'data-msg-htmlrequired' => 'Enter the email contents.',
            'dir' => $locale === 'ar' ? 'rtl' : 'ltr',
            // Short tags go into the contents unless the subject or heading was focused last.
            'data-short-tag-target' => 'true',
            'data-short-tag-default' => 'true',
        );
        $this->ckfinder->SetupCKEditor($this->ckeditor, '../../../../assets/ckfinder/');
    }

    private function postedData($locale)
    {
        $post = $this->input->post(NULL, FALSE);
        return $this->manage_translation_service->localized_post_data($this->translationModule, $locale, is_array($post) ? $post : array());
    }

    private function formErrorMessage()
    {
        $name = $this->scalarString('name');
        if ($name === NULL || $name === '') return 'Enter a template name.';
        if ($this->length($name) > 255) return 'The name must not exceed 255 characters.';

        $subject = $this->scalarString('localized_subject');
        if ($subject === NULL || $subject === '') return 'Enter an email subject.';
        if ($this->length($subject) > 255) return 'The subject must not exceed 255 characters.';

        $heading = $this->scalarString('localized_heading');
        if ($heading === NULL) return 'Enter the required fields correctly.';
        if ($this->length($heading) > 255) return 'The heading must not exceed 255 characters.';

        $contents = $this->input->post('localized_contents', FALSE);
        if (is_array($contents)) return 'Enter the required fields correctly.';
        if ($this->isContentEmpty((string) $contents)) return 'Enter the email contents.';

        return '';
    }

    private function scalarString($key)
    {
        $value = $this->input->post($key, FALSE);
        return is_array($value) ? NULL : trim((string) $value);
    }

    private function isContentEmpty($html)
    {
        $decoded = html_entity_decode((string) $html, ENT_QUOTES, 'UTF-8');
        $decoded = str_replace("\xc2\xa0", ' ', $decoded);
        return trim(strip_tags($decoded)) === '';
    }

    private function formFailure($message, $editID = 0, $locale = 'en')
    {
        $posted = $this->input->post(NULL, FALSE);
        $posted = is_array($posted) ? $posted : array();
        $posted['active_locale'] = $this->manage_translation_service->locale($locale);
        $this->session->set_flashdata($this->controller.'_data', $posted);
        $this->session->set_flashdata('form_error', $message);
        redirect(base_url('manage/'.$this->controller.'/control'.($editID ? '/edit/'.(int) $editID.'?lang='.$this->manage_translation_service->locale($locale) : '')));
    }

    private function queueTranslationSafely($id)
    {
        try
        {
            $this->manage_translation_service->queue($this->translationModule, (int) $id);
        }
        catch (Throwable $exception)
        {
            log_message('error', 'Email template translation could not be queued for record '.(int) $id.'.');
        }
    }

    private function truncate($value, $max)
    {
        return function_exists('mb_substr') ? mb_substr((string) $value, 0, (int) $max, 'UTF-8') : substr((string) $value, 0, (int) $max);
    }

    private function length($value)
    {
        return function_exists('mb_strlen') ? mb_strlen((string) $value, 'UTF-8') : strlen((string) $value);
    }

    private function render($view, $data)
    {
        $this->load->view('admin/header', $data);
        $this->load->view('admin/navigation');
        $this->load->view('admin/'.$view);
        $this->load->view('admin/footer');
    }
}
