<?php defined('BASEPATH') OR exit('No direct script access allowed');

if (!function_exists('manage_translation_status_meta'))
{
    function manage_translation_status_meta($status)
    {
        $statuses = array(
            'MISSING' => array('label' => 'Missing', 'class' => 'translation-status-missing'),
            'PENDING' => array('label' => 'Pending', 'class' => 'translation-status-pending'),
            'RUNNING' => array('label' => 'Translating', 'class' => 'translation-status-running'),
            'SUCCEEDED' => array('label' => 'Ready', 'class' => 'translation-status-succeeded status-enabled'),
            'FAILED' => array('label' => 'Failed', 'class' => 'translation-status-failed'),
        );

        return isset($statuses[$status]) ? $statuses[$status] : $statuses['MISSING'];
    }
}

if (!function_exists('manage_json_response'))
{
    function manage_json_response($controller, array $data, $status_code = 200)
    {
        if ($controller->config->item('csrf_protection'))
        {
            $data['_csrf'] = array(
                'name' => $controller->security->get_csrf_token_name(),
                'hash' => $controller->security->get_csrf_hash(),
            );
        }
        return $controller->output
            ->set_status_header((int) $status_code)
            ->set_content_type('application/json', 'utf-8')
            ->set_output(json_encode($data, JSON_UNESCAPED_UNICODE));
    }
}
