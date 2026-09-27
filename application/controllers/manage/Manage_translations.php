<?php defined('BASEPATH') OR exit('No direct script access allowed');

class Manage_translations extends CI_Controller
{
    private $user_data = array();

    public function __construct()
    {
        parent::__construct();
        $this->user_data = $this->SqlModel->authAdmin($this->session->userdata('admin_auth'), $this->session->userdata('admin_id'));
        if (empty($this->user_data))
        {
            if ($this->input->is_ajax_request())
            {
                $this->load->helper('manage_translation');
                manage_json_response($this, array('success' => FALSE, 'message' => 'Your admin session has expired.'), 401);
                $this->output->_display();
                exit;
            }
            redirect(base_url('manage/login'));
        }
        $this->load->library('manage_translation_service');
        $this->load->helper('manage_translation');
    }

    public function statuses()
    {
        if (strtoupper($this->input->method()) !== 'POST') return manage_json_response($this, array('success' => FALSE, 'message' => 'Only POST requests are allowed.'), 405);
        $module = trim((string) $this->input->post('module', TRUE));
        $ids = array_values(array_unique(array_filter(array_map('strval', (array) $this->input->post('ids')), 'strlen')));
        if ($module === '' || empty($ids)) return manage_json_response($this, array('success' => FALSE, 'message' => 'Supply a configured module and record IDs.'), 422);
        try
        {
            $include_values = $this->input->post('include_values') === '1';
            if ($this->isWebPageSectionsModule($module))
            {
                $this->requireWebPageSectionsPermission('web_pages.view');
                $states = $this->webPageSectionStates($ids, $include_values);

                return manage_json_response($this, array('success' => TRUE, 'states' => $states), 200);
            }

            if ($this->isMiscellaneousContentsModule($module))
            {
                $this->requireMiscellaneousContentsPermission(
                    'miscellaneous_contents.view'
                );
            }

            $states = $this->manage_translation_service->states($module, $ids, $include_values);
            return manage_json_response($this, array('success' => TRUE, 'states' => $states), 200);
        }
        catch (InvalidArgumentException $exception)
        {
            return manage_json_response($this, array('success' => FALSE, 'message' => $exception->getMessage()), 422);
        }
        catch (Throwable $exception)
        {
            log_message('error', 'Manage translation status lookup failed.');
            return manage_json_response($this, array('success' => FALSE, 'message' => 'Translation statuses could not be loaded.'), 500);
        }
    }

    public function translate()
    {
        if (strtoupper($this->input->method()) !== 'POST') return manage_json_response($this, array('success' => FALSE, 'message' => 'Only POST requests are allowed.'), 405);
        $module = trim((string) $this->input->post('module', TRUE));
        $id = trim((string) $this->input->post('id', TRUE));
        if ($module === '' || $id === '') return manage_json_response($this, array('success' => FALSE, 'message' => 'Supply a configured module and record ID.'), 422);
        try
        {
            if ($this->isWebPageSectionsModule($module))
            {
                $this->requireWebPageSectionsPermission('web_pages.update');
                $result = $this->content_section_service->translate_web_from_english((int) $id);
                $status = isset($result['status_code']) ? (int) $result['status_code'] : 500;
                unset($result['status_code']);

                return manage_json_response($this, $result, $status);
            }

            if ($this->isMiscellaneousContentsModule($module))
            {
                $this->requireMiscellaneousContentsPermission(
                    'miscellaneous_contents.update'
                );
            }

            $result = $this->manage_translation_service->translate_from_source($module, $id);
            $status = isset($result['status_code']) ? (int) $result['status_code'] : 500;
            unset($result['status_code']);
            return manage_json_response($this, $result, $status);
        }
        catch (InvalidArgumentException $exception)
        {
            return manage_json_response($this, array('success' => FALSE, 'message' => $exception->getMessage()), 422);
        }
        catch (Throwable $exception)
        {
            log_message('error', 'Manage manual translation request failed.');
            return manage_json_response($this, array('success' => FALSE, 'message' => 'The Arabic draft could not be created.'), 500);
        }
    }

    private function isWebPageSectionsModule($module)
    {
        if (!hash_equals('web_page_sections', (string) $module))
        {
            return FALSE;
        }

        $configuration = $this->manage_translation_service->module($module);

        return isset($configuration['adapter'])
            && hash_equals('web_page_sections', (string) $configuration['adapter']);
    }

    private function requireWebPageSectionsPermission($capability)
    {
        $this->load->library('content_section_service');

        if (!$this->content_section_service->can($capability, $this->user_data))
        {
            throw new InvalidArgumentException('You do not have permission to access page sections.');
        }
    }

    private function isMiscellaneousContentsModule($module)
    {
        if (!hash_equals('miscellaneous_contents', (string) $module))
        {
            return FALSE;
        }

        $configuration = $this->manage_translation_service->module($module);

        return isset($configuration['adapter'])
            && hash_equals(
                'miscellaneous_contents',
                (string) $configuration['adapter']
            );
    }

    private function requireMiscellaneousContentsPermission($capability)
    {
        $this->load->library('content_section_service');

        if (!$this->content_section_service->can($capability, $this->user_data))
        {
            throw new InvalidArgumentException(
                'You do not have permission to access miscellaneous contents.'
            );
        }
    }

    private function webPageSectionStates(array $ids, $include_values)
    {
        if (count($ids) > 100)
        {
            throw new InvalidArgumentException('Too many record IDs were requested.');
        }

        foreach ($ids as $id)
        {
            if (!ctype_digit((string) $id) || (int) $id < 1)
            {
                throw new InvalidArgumentException('One or more record IDs are invalid.');
            }

        }

        return $this->manage_translation_service->states(
            'web_page_sections',
            $ids,
            $include_values
        );
    }
}
