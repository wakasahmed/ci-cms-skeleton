<?php defined('BASEPATH') OR exit('No direct script access allowed');

class Form_settings extends CI_Controller
{
    public $tblName = 'form_settings';
    public $pKey = 'id';
    public $recordId = 1;
    public $moduleName = 'Form Settings';
    public $controller = 'form-settings';
    public $listView = 'formSettings';
    public $translationModule = 'form_settings';
    public $user_data = array();

    public function __construct()
    {
        parent::__construct();
        $this->user_data = $this->SqlModel->authAdmin(
            $this->session->userdata('admin_auth'),
            $this->session->userdata('admin_id')
        );

        if (empty($this->user_data))
        {
            redirect(base_url('manage/login'));
            return;
        }

        if ($this->SqlModel->checkAccess('access_settings', $this->user_data) === FALSE)
        {
            redirect(ADMIN_URL);
            return;
        }

        $this->load->library('manage_translation_service');
    }

    public function index()
    {
        $record = $this->SqlModel->getSingleRecord(
            $this->tblName,
            array($this->pKey => $this->recordId)
        );
        $record = is_array($record) ? $record : array();

        $posted = $this->session->flashdata($this->controller.'_data');
        $requestedLocale = is_array($posted) && isset($posted['active_locale'])
            ? $posted['active_locale']
            : $this->input->get('lang', TRUE);
        $activeLocale = $this->manage_translation_service->locale($requestedLocale);
        $localizedValues = $this->manage_translation_service->localized_values(
            $this->translationModule,
            $record,
            $activeLocale
        );

        if (is_array($posted))
        {
            foreach ($localizedValues as $control => $value)
            {
                if (isset($posted[$control]) && !is_array($posted[$control]))
                {
                    $localizedValues[$control] = (string) $posted[$control];
                }
            }
        }

        $translationState = empty($record)
            ? NULL
            : $this->manage_translation_service->state($this->translationModule, $this->recordId);

        $data = array(
            'formSettingsActive' => 1,
            'page_title' => PROJECT_TITLE.' | '.$this->moduleName,
            'alert' => $this->session->flashdata('alert'),
            'form_error' => $this->session->flashdata('form_error'),
            'userdata' => $this->user_data,
            'tbl_data' => $record,
            'localized_values' => $localizedValues,
            'manage_locales' => $this->manage_translation_service->locales(),
            'active_locale' => $activeLocale,
            'translation_state' => $translationState,
            'useManageTranslations' => TRUE,
        );

        $this->load->view('admin/header', $data);
        $this->load->view('admin/navigation');
        $this->load->view('admin/'.$this->listView);
        $this->load->view('admin/footer');
    }

    public function save()
    {
        $current = $this->SqlModel->getSingleRecord(
            $this->tblName,
            array($this->pKey => $this->recordId)
        );
        $activeLocale = $this->manage_translation_service->locale(
            $this->input->post('active_locale', TRUE)
        );

        if (empty($current))
        {
            return $this->formFailure(
                'The Form Settings record is missing. Run the provided database query first.',
                $activeLocale
            );
        }

        $post = $this->input->post(NULL, FALSE);
        $post = is_array($post) ? $post : array();

        if (!$this->manage_translation_service->required_localized_input_valid(
            $this->translationModule,
            $activeLocale,
            $post
        ))
        {
            return $this->formFailure(
                'Complete all required form setting fields.',
                $activeLocale
            );
        }

        $data = $this->manage_translation_service->localized_post_data(
            $this->translationModule,
            $activeLocale,
            $post
        );

        $this->db->trans_begin();
        $updated = $this->SqlModel->updateRecord(
            $this->tblName,
            $data,
            array($this->pKey => $this->recordId)
        );

        if (!$updated || $this->db->trans_status() === FALSE)
        {
            $this->db->trans_rollback();

            return $this->formFailure(
                'The Form Settings could not be saved. No changes were applied.',
                $activeLocale
            );
        }

        $this->db->trans_commit();

        if ($activeLocale === 'en')
        {
            $this->queueTranslationSafely();
        }

        $this->session->set_flashdata('alert', 'success');
        $redirectLocale = $this->input->post('redirect_lang', TRUE);
        if (is_string($redirectLocale)
            && $redirectLocale !== ''
            && $this->manage_translation_service->locale($redirectLocale) === $redirectLocale)
        {
            redirect(base_url('manage/'.$this->controller.'?lang='.$redirectLocale));
            return;
        }

        redirect(base_url('manage/'.$this->controller.'?lang='.$activeLocale));
    }

    private function formFailure($message, $locale)
    {
        $posted = $this->input->post(NULL, FALSE);
        $posted = is_array($posted) ? $posted : array();
        $posted['active_locale'] = $this->manage_translation_service->locale($locale);

        $this->session->set_flashdata($this->controller.'_data', $posted);
        $this->session->set_flashdata('form_error', $message);
        redirect(
            base_url(
                'manage/'.$this->controller.'?lang='.
                $this->manage_translation_service->locale($locale)
            )
        );
    }

    private function queueTranslationSafely()
    {
        try
        {
            $this->manage_translation_service->queue(
                $this->translationModule,
                $this->recordId
            );
        }
        catch (Throwable $exception)
        {
            log_message(
                'error',
                'Form Settings translation could not be queued for record '.
                $this->recordId.'.'
            );
        }
    }
}
