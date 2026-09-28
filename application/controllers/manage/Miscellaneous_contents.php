<?php defined('BASEPATH') OR exit('No direct script access allowed');

class Miscellaneous_contents extends CI_Controller
{
    public $moduleName = 'Miscellaneous Contents';
    public $controller = 'miscellaneous_contents';
    public $user_data = array();

    private $listView = 'admin/content_sections/miscellaneous_list';
    private $editView = 'admin/content_sections/miscellaneous_edit';

    /**
     * Section fields are stored with a locale column; the site is English
     * only, so every read and write uses this one locale.
     */
    private $locale = 'en';

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
    }

    public function index()
    {
        $this->requirePermission('miscellaneous_contents.view');
        $listing = $this->content_section_service->miscellaneous_listing();

        if ($listing['status'] !== 'ok') {
            show_error('Miscellaneous contents could not be loaded.', 500);
            return;
        }

        $this->render($this->listView, array(
            'page_title' => PROJECT_TITLE.' | '.$this->moduleName,
            'userdata' => $this->user_data,
            'contentSectionsActive' => 1,
            'sections' => $listing['sections'],
            'can_update' => $this->content_section_service->can(
                'miscellaneous_contents.update',
                $this->user_data
            ),
            'success_message' => $this->session->flashdata('content_sections_message'),
        ));
    }

    public function edit($encodedKey = '')
    {
        $this->requirePermission('miscellaneous_contents.view');

        $key = $this->safeKey($encodedKey);
        $locale = $this->locale;
        $this->renderEditor($this->loadEditor($key, $locale), $locale);
    }

    public function save($encodedKey = '')
    {
        $this->requirePermission('miscellaneous_contents.update');

        if (strtoupper($this->input->method()) !== 'POST') {
            show_404();
            return;
        }

        $key = $this->safeKey($encodedKey);
        $locale = $this->locale;
        $editor = $this->loadEditor($key, $locale);
        $post = $this->input->post(NULL, FALSE);
        $validated = $this->content_section_service->validate_miscellaneous(
            $editor,
            $locale,
            is_array($post) ? $post : array()
        );

        if (!$validated['valid']) {
            $this->renderFailure($editor, $locale, $validated);
            return;
        }

        if (!$this->content_section_service->save_miscellaneous(
            $key,
            $locale,
            $validated,
            $this->user_data
        )) {
            log_message('error', 'Unable to save miscellaneous content section '.$key.'.');
            $this->renderFailure(
                $editor,
                $locale,
                $validated,
                array(),
                'The content section could not be saved. Please try again.'
            );
            return;
        }

        $this->session->set_flashdata(
            'content_sections_message',
            'Content section saved successfully.'
        );

        redirect(
            base_url(
                'manage/miscellaneous-contents/'.rawurlencode($key).'/edit'
            )
        );
    }

    public function changestatus($id = 0, $status = 'Enable')
    {
        $this->requirePermission('miscellaneous_contents.update');

        $id = filter_var(
            $id,
            FILTER_VALIDATE_INT,
            array('options' => array('min_range' => 1))
        );
        $status = rawurldecode((string) $status);

        if ($id === false || !in_array($status, array('Enable', 'Disable'), TRUE)) {
            return $this->jsonResponse(array('status' => 'false'));
        }

        $section = $this->content_section_service->update_miscellaneous_status(
            $id,
            $status,
            $this->user_data
        );

        if (!$section) {
            return $this->jsonResponse(array('status' => 'false'));
        }

        return $this->jsonResponse(array(
            'status' => 'true',
            'id' => (int) $section['section_id'],
            'currentStatus' => $section['section_status'],
        ));
    }

    private function loadEditor($key, $locale)
    {
        $editor = $this->content_section_service->miscellaneous_editor($key, $locale);

        if ($editor['status'] === 'missing') {
            show_404();
            return array();
        }

        if ($editor['status'] !== 'ok') {
            show_error('The content section could not be loaded.', 500);
            return array();
        }

        return $editor;
    }

    private function renderFailure(
        array $editor,
        $locale,
        array $validated,
        array $errors = array(),
        $message = 'Please correct the highlighted fields.'
    ) {
        $sections = $this->content_section_service->apply_submitted_values(
            array($editor['editor_section']),
            $validated
        );
        $editor['editor_section'] = $sections[0];

        $this->renderEditor(
            $editor,
            $locale,
            empty($errors) ? $validated['errors'] : $errors,
            $message
        );
    }

    private function renderEditor(
        array $editor,
        $locale,
        array $errors = array(),
        $errorMessage = ''
    ) {
        $this->content_section_service->configure_ckeditor();

        $this->render($this->editView, array(
            'page_title' => PROJECT_TITLE.' | Edit '.$editor['definition']['label'],
            'userdata' => $this->user_data,
            'contentSectionsActive' => 1,
            'useIconPicker' => TRUE,
            'useContentSections' => TRUE,
            'editor_section' => $editor['editor_section'],
            'locale' => $locale,
            'errors' => $errors,
            'error_message' => $errorMessage,
            'success_message' => $this->session->flashdata('content_sections_message'),
            'can_update' => $this->content_section_service->can(
                'miscellaneous_contents.update',
                $this->user_data
            ),
        ));
    }


    private function render($view, array $data)
    {
        $this->load->view('admin/header', $data);
        $this->load->view('admin/navigation');
        $this->load->view($view);
        $this->load->view('admin/footer');
    }

    private function safeKey($encodedKey)
    {
        $key = rawurldecode((string) $encodedKey);
        if (!preg_match('/^[a-z][a-z0-9_]*$/', $key)
            || !$this->content_section_service->miscellaneous_definition($key))
        {
            show_404();
            return '';
        }

        return $key;
    }

    private function requirePermission($capability)
    {
        if (!$this->content_section_service->can($capability, $this->user_data)) {
            show_error('Access denied.', 403);
        }
    }


    private function jsonResponse(array $payload)
    {
        return $this->output
            ->set_content_type('application/json')
            ->set_output(json_encode($payload));
    }
}
