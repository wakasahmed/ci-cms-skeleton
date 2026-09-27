<?php defined('BASEPATH') OR exit('No direct script access allowed');

class Web_page_sections extends CI_Controller
{
    public $moduleName = 'Page Sections';
    public $controller = 'web_page_sections';
    public $user_data = array();

    private $editView = 'admin/content_sections/web_page_edit';

    public function __construct()
    {
        parent::__construct();

        $this->user_data = $this->SqlModel->authAdmin(
            $this->session->userdata('admin_auth'),
            $this->session->userdata('admin_id')
        );

        if (empty($this->user_data)) {
            redirect(base_url('manage/login'));
            return;
        }

        $this->load->library('content_section_service');
        $this->load->library('manage_translation_service');
    }

    public function edit($pageID = 0)
    {
        $this->requirePermission('web_pages.view');

        $pageID = (int) $pageID;
        $locale = $this->requestedLocale($this->input->get('lang', TRUE));
        $editor = $this->loadEditor($pageID, $locale);

        $this->renderEditor($editor, $locale);
    }

    public function save($pageID = 0)
    {
        $this->requirePermission('web_pages.update');

        if (strtoupper($this->input->method()) !== 'POST') {
            show_404();
            return;
        }

        $pageID = (int) $pageID;
        $locale = $this->requestedLocale($this->input->post('locale', TRUE));
        $editor = $this->loadEditor($pageID, $locale);
        $post = $this->input->post(NULL, FALSE);
        $validated = $this->content_section_service->validate_web(
            $editor,
            $locale,
            is_array($post) ? $post : array()
        );

        if (!$validated['valid']) {
            $editor['editor_sections'] = $this->content_section_service->apply_submitted_values(
                $editor['editor_sections'],
                $validated
            );

            $this->renderEditor(
                $editor,
                $locale,
                $validated['errors'],
                'Please correct the highlighted fields.'
            );
            return;
        }

        if (!$this->content_section_service->save_web(
            $pageID,
            $locale,
            $validated,
            $this->user_data
        )) {
            log_message(
                'error',
                'Unable to save web page sections for page ID '.(int) $pageID.'.'
            );

            $editor['editor_sections'] = $this->content_section_service->apply_submitted_values(
                $editor['editor_sections'],
                $validated
            );

            $this->renderEditor(
                $editor,
                $locale,
                array(),
                'The page sections could not be saved. Please try again.'
            );
            return;
        }

        if ($locale === 'en') {
            $this->queueTranslationSafely($pageID);
        }

        $targetLocale = $this->safeRedirectLocale(
            $this->input->post('redirect_lang', TRUE),
            $locale
        );

        $this->session->set_flashdata(
            'content_sections_message',
            'Page sections saved successfully.'
        );

        redirect(
            base_url(
                'manage/web-pages/'.(int) $pageID.'/sections?lang='.$targetLocale
            )
        );
    }

    private function loadEditor($pageID, $locale)
    {
        $editor = $this->content_section_service->web_editor(
            (int) $pageID,
            $locale
        );

        if ($editor['status'] === 'missing' || $editor['status'] === 'unsupported') {
            show_404();
            return array();
        }

        if ($editor['status'] !== 'ok') {
            show_error('The page sections could not be loaded.', 500);
            return array();
        }

        return $editor;
    }

    private function renderEditor(
        array $editor,
        $locale,
        array $errors = array(),
        $errorMessage = ''
    ) {
        $this->content_section_service->configure_ckeditor($locale);

        $pageName = isset($editor['page']['page_name'])
            ? html_entity_decode(
                (string) $editor['page']['page_name'],
                ENT_QUOTES,
                'UTF-8'
            )
            : 'Web Page';
        $translationState = $this->manage_translation_service->state(
            'web_page_sections',
            (int) $editor['page']['page_id']
        );

        $data = array(
            'page_title' => PROJECT_TITLE.' | Edit '.$pageName.' Sections',
            'userdata' => $this->user_data,
            'pagesActive' => 1,
            'useIconPicker' => TRUE,
            'useContentSections' => TRUE,
            'useManageTranslations' => TRUE,
            'page' => $editor['page'],
            'editor_sections' => $editor['editor_sections'],
            'locale' => $locale,
            'locales' => $this->content_section_service->locales(),
            'errors' => $errors,
            'error_message' => $errorMessage,
            'success_message' => $this->session->flashdata('content_sections_message'),
            'translation_state' => $translationState,
            'can_update' => $this->content_section_service->can(
                'web_pages.update',
                $this->user_data
            ),
        );

        $this->load->view('admin/header', $data);
        $this->load->view('admin/navigation');
        $this->load->view($this->editView);
        $this->load->view('admin/footer');
    }

    private function requirePermission($capability)
    {
        if (!$this->content_section_service->can($capability, $this->user_data)) {
            show_error('Access denied.', 403);
        }
    }

    private function requestedLocale($locale)
    {
        if ($locale === NULL || $locale === '') {
            $locale = 'en';
        }

        $locale = $this->content_section_service->locale($locale);

        if ($locale === FALSE) {
            show_404();
            return 'en';
        }

        return $locale;
    }

    private function safeRedirectLocale($locale, $fallback)
    {
        $locale = $this->content_section_service->locale($locale);

        return $locale === FALSE ? $fallback : $locale;
    }

    private function queueTranslationSafely($pageID)
    {
        try {
            $this->manage_translation_service->queue(
                'web_page_sections',
                (int) $pageID
            );
        } catch (Throwable $exception) {
            log_message(
                'error',
                'Page sections translation could not be queued for page ID '
                .(int) $pageID.'.'
            );
        }
    }
}
