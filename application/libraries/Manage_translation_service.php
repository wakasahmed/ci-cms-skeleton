<?php defined('BASEPATH') OR exit('No direct script access allowed');

class Manage_translation_service
{
    private $CI;
    private $modules = array();
    private $locales = array();
    private $settings = array();

    public function __construct()
    {
        $this->CI =& get_instance();
        $this->CI->config->load('manage_translations', TRUE);
        $config = $this->CI->config->item('manage_translations');
        $this->modules = isset($config['manage_translation_modules']) ? $config['manage_translation_modules'] : array();
        $this->locales = isset($config['manage_translation_locales']) ? $config['manage_translation_locales'] : array();
        $this->settings = $config;
        $this->CI->load->model('Translation_job_model', 'translation_jobs');
    }

    public function locales()
    {
        return $this->locales;
    }

    public function locale($locale)
    {
        $default = isset($this->settings['manage_translation_default_locale']) ? $this->settings['manage_translation_default_locale'] : 'en';
        return isset($this->locales[$locale]) ? $locale : $default;
    }

    public function locale_details($locale)
    {
        $locale = $this->locale($locale);
        return $this->locales[$locale];
    }

    public function module($module_key)
    {
        if (!is_string($module_key) || !isset($this->modules[$module_key]))
        {
            throw new InvalidArgumentException('This module is not configured for translation.');
        }
        return $this->modules[$module_key];
    }

    public function localized_columns($module_key, $locale)
    {
        $module = $this->module($module_key);
        $locale = $this->locale($locale);
        $columns = array();
        foreach ($module['fields'] as $field)
        {
            $columns[$field['control']] = $locale === $module['target_language'] ? $field['target'] : $field['source'];
        }
        return $columns;
    }

    public function localized_values($module_key, array $record, $locale)
    {
        $values = array();
        foreach ($this->localized_columns($module_key, $locale) as $control => $column)
        {
            $values[$control] = isset($record[$column]) ? (string) $record[$column] : '';
        }
        return $values;
    }

    public function localized_post_data($module_key, $locale, array $post)
    {
        $module = $this->module($module_key);
        $locale = $this->locale($locale);
        $data = array();
        foreach ($module['fields'] as $field)
        {
            $column = $locale === $module['target_language'] ? $field['target'] : $field['source'];
            $value = isset($post[$field['control']]) ? (string) $post[$field['control']] : '';
            $max = $locale === $module['target_language'] ? (isset($field['max_length']) ? $field['max_length'] : NULL) : (isset($field['source_max_length']) ? $field['source_max_length'] : NULL);
            $data[$column] = $this->truncate(trim($value), $max);
        }
        return $data;
    }

    public function required_localized_input_valid($module_key, $locale, array $post)
    {
        $module = $this->module($module_key);
        foreach ($module['fields'] as $field)
        {
            if (!empty($field['required']) && trim(isset($post[$field['control']]) ? (string) $post[$field['control']] : '') === '')
            {
                return FALSE;
            }
        }
        return TRUE;
    }

    public function queue($module_key, $entity_id)
    {
        $module = $this->module($module_key);
        $entity_id = $this->entity_id($module, $entity_id);
        if ($entity_id === NULL)
        {
            throw new InvalidArgumentException('The record ID is invalid.');
        }

        if ($this->is_web_page_sections_module($module))
        {
            return $this->queue_web_page_sections($module, $entity_id);
        }

        if ($this->is_miscellaneous_contents_module($module))
        {
            return $this->queue_miscellaneous_contents($module, $entity_id);
        }

        $record = $this->record($module, $entity_id);
        if (!$record)
        {
            return FALSE;
        }
        $missing = $this->missing_fields($module, $record);
        if (empty($missing))
        {
            return FALSE;
        }

        $source_hash = $this->source_hash($module, $record);
        $dedupe_key = hash('sha256', implode('|', array(
            $module['entity_type'], (string) $entity_id, $module['source_language'],
            $module['target_language'], $source_hash,
        )));
        $keys = array();
        foreach ($missing as $field)
        {
            $keys[] = $field['source'];
        }

        return $this->CI->translation_jobs->create_or_reset(array(
            'dedupe_key' => $dedupe_key,
            'module_key' => $module['module_key'],
            'entity_type' => $module['entity_type'],
            'entity_id' => (string) $entity_id,
            'source_language' => $module['source_language'],
            'target_language' => $module['target_language'],
            'field_keys' => json_encode($keys),
            'source_hash' => $source_hash,
            'status' => 'PENDING',
            'attempts' => 0,
            'max_attempts' => 5,
            'next_attempt_at' => date('Y-m-d H:i:s'),
            'last_error' => NULL,
            'completed_at' => NULL,
        ));
    }

    public function state($module_key, $entity_id)
    {
        $states = $this->states($module_key, array($entity_id), TRUE);
        return isset($states[(string) $entity_id]) ? $states[(string) $entity_id] : NULL;
    }

    public function statuses($module_key, array $entity_ids)
    {
        $states = $this->states($module_key, $entity_ids, FALSE);
        $statuses = array();
        foreach ($states as $id => $state)
        {
            $statuses[$id] = $state['status'];
        }
        return $statuses;
    }

    public function states($module_key, array $entity_ids, $include_values = FALSE)
    {
        $module = $this->module($module_key);
        $ids = array();
        foreach ($entity_ids as $entity_id)
        {
            $entity_id = $this->entity_id($module, $entity_id);
            if ($entity_id === NULL)
            {
                throw new InvalidArgumentException('One or more record IDs are invalid.');
            }
            $ids[] = $entity_id;
        }
        $ids = array_values(array_unique($ids));
        $limit = isset($this->settings['manage_translation_max_status_ids']) ? (int) $this->settings['manage_translation_max_status_ids'] : 100;
        if (count($ids) > $limit)
        {
            throw new InvalidArgumentException('Too many record IDs were requested.');
        }
        if (empty($ids))
        {
            return array();
        }

        if ($this->is_web_page_sections_module($module))
        {
            return $this->web_page_section_states($module, $ids, $include_values);
        }

        if ($this->is_miscellaneous_contents_module($module))
        {
            return $this->miscellaneous_content_states($module, $ids, $include_values);
        }

        $columns = array($module['primary_key']);
        foreach ($module['fields'] as $field)
        {
            $columns[] = $field['source'];
            $columns[] = $field['target'];
        }
        $records = $this->CI->db->select(implode(',', array_unique($columns)))
            ->where_in($module['primary_key'], $ids)->get($module['table'])->result_array();
        $latest = $this->CI->translation_jobs->latest_for_entities($module['module_key'], $module['entity_type'], $ids);
        $states = array();
        foreach ($records as $record)
        {
            $id = (string) $record[$module['primary_key']];
            $missing = $this->missing_fields($module, $record);
            $status = empty($missing) ? 'SUCCEEDED' : 'MISSING';
            $currentHash = $this->source_hash($module, $record);
            $relevant = isset($latest[$id][$currentHash]) ? $latest[$id][$currentHash] : NULL;
            if (!empty($missing) && $relevant !== NULL
                && in_array($relevant['status'], array('PENDING', 'RUNNING', 'FAILED'), TRUE))
            {
                $status = $relevant['status'];
            }
            $states[$id] = array('status' => $status);
            if ($include_values)
            {
                $states[$id]['values'] = $this->localized_values($module_key, $record, $module['target_language']);
            }
        }
        return $states;
    }

    public function translate_from_source($module_key, $entity_id)
    {
        $module = $this->module($module_key);

        if ($this->is_miscellaneous_contents_module($module))
        {
            $entity_id = $this->entity_id($module, $entity_id);
            if ($entity_id === NULL)
            {
                return array(
                    'success' => FALSE,
                    'status_code' => 404,
                    'message' => 'The record was not found.',
                );
            }

            return $this->content_section_service()
                ->translate_miscellaneous_from_english($entity_id);
        }

        $record = $this->record($module, $entity_id);
        if (!$record)
        {
            return array('success' => FALSE, 'status_code' => 404, 'message' => 'The record was not found.');
        }

        $fields = array();
        $values = array();
        foreach ($module['fields'] as $field)
        {
            if ($this->normalize(isset($record[$field['source']]) ? $record[$field['source']] : '') !== '')
            {
                $fields[] = $field;
            }
            elseif (!empty($field['clear_target_when_source_empty']))
            {
                $values[$field['control']] = '';
            }
        }
        if (empty($fields) && empty($values))
        {
            return array('success' => FALSE, 'status_code' => 422, 'message' => 'There is no English content to translate.');
        }

        if (!empty($fields))
        {
            $translated = $this->translate_fields($module, $record, $fields);
            if (!$translated['success'])
            {
                return $translated;
            }
            foreach ($fields as $field)
            {
                $values[$field['control']] = $translated['values'][$field['target']];
            }
        }
        return array('success' => TRUE, 'status_code' => 200, 'message' => 'Arabic draft created. Save the form to keep it.', 'values' => $values);
    }

    public function delete_jobs($module_key, $entity_id)
    {
        $module = $this->module($module_key);
        return $this->CI->translation_jobs->delete_entity($module['module_key'], $module['entity_type'], $entity_id);
    }

    public function process_pending($limit = 25)
    {
        if (!$this->CI->translation_jobs->available())
        {
            throw new RuntimeException('The translation jobs table is not installed.');
        }
        $limit = max(1, min(100, (int) $limit));
        $summary = array('claimed' => 0, 'succeeded' => 0, 'failed' => 0, 'skipped' => 0, 'released' => 0);
        $lease = isset($this->settings['manage_translation_lease_seconds']) ? (int) $this->settings['manage_translation_lease_seconds'] : 300;
        $summary['released'] = $this->CI->translation_jobs->release_stale($lease);

        for ($i = 0; $i < $limit; $i++)
        {
            $job = $this->CI->translation_jobs->claim_next();
            if (!$job)
            {
                break;
            }
            $summary['claimed']++;
            $result = $this->process_job($job);
            $summary[strtolower($result)]++;
        }
        return $summary;
    }

    private function process_job(array $job)
    {
        $transactionStarted = FALSE;
        try
        {
            $module = $this->module($job['module_key']);
            if ($module['entity_type'] !== $job['entity_type'])
            {
                return $this->skip($job, 'The job configuration no longer matches.');
            }

            if ($this->is_web_page_sections_module($module))
            {
                return $this->process_web_page_sections_job($job, $module);
            }

            if ($this->is_miscellaneous_contents_module($module))
            {
                return $this->process_miscellaneous_contents_job($job, $module);
            }

            $record = $this->record($module, $job['entity_id']);
            if (!$record)
            {
                return $this->skip($job, 'The source record no longer exists.');
            }
            if (!hash_equals($job['source_hash'], $this->source_hash($module, $record)))
            {
                return $this->skip($job, 'Newer English content exists.');
            }

            $approved = json_decode($job['field_keys'], TRUE);
            $approved = is_array($approved) ? $approved : array();
            $fields = array();
            foreach ($module['fields'] as $field)
            {
                if (in_array($field['source'], $approved, TRUE)
                    && $this->normalize($record[$field['source']]) !== ''
                    && $this->normalize($record[$field['target']]) === '')
                {
                    $fields[] = $field;
                }
            }
            if (empty($fields))
            {
                $status = empty($this->missing_fields($module, $record)) ? 'SUCCEEDED' : 'SKIPPED';
                $this->CI->translation_jobs->finish($job['id'], $status);
                return strtolower($status);
            }

            $translated = $this->translate_fields($module, $record, $fields);
            if (!$translated['success'])
            {
                return $this->fail($job, $translated['message']);
            }

            $this->CI->db->trans_begin();
            $transactionStarted = TRUE;
            $locked = $this->CI->db->query('SELECT * FROM `'.$module['table'].'` WHERE `'.$module['primary_key'].'` = ? FOR UPDATE', array($job['entity_id']))->row_array();
            if (!$locked || !hash_equals($job['source_hash'], $this->source_hash($module, $locked)))
            {
                $this->CI->db->trans_rollback();
                $transactionStarted = FALSE;
                return $this->skip($job, 'The English content changed while translating.');
            }
            $updates = array();
            foreach ($fields as $field)
            {
                if ($this->normalize($locked[$field['target']]) === '')
                {
                    $updates[$field['target']] = $translated['values'][$field['target']];
                }
            }
            if (!empty($updates))
            {
                $this->CI->db->where($module['primary_key'], $job['entity_id'])->update($module['table'], $updates);
            }
            if ($this->CI->db->trans_status() === FALSE)
            {
                $this->CI->db->trans_rollback();
                $transactionStarted = FALSE;
                return $this->fail($job, 'The translated values could not be saved.');
            }
            $this->CI->db->trans_commit();
            $transactionStarted = FALSE;
            $current = array_merge($locked, $updates);
            $status = empty($this->missing_fields($module, $current)) ? 'SUCCEEDED' : 'SKIPPED';
            $this->CI->translation_jobs->finish($job['id'], $status);
            return strtolower($status);
        }
        catch (Throwable $exception)
        {
            if ($transactionStarted)
            {
                $this->CI->db->trans_rollback();
            }
            log_message('error', 'Translation worker failed for job '.(int) $job['id'].': '.$this->safe_error($exception->getMessage()));
            return $this->fail($job, 'The translation job could not be processed.');
        }
    }

    private function is_web_page_sections_module(array $module)
    {
        return isset($module['adapter'])
            && $module['adapter'] === 'web_page_sections';
    }

    private function is_miscellaneous_contents_module(array $module)
    {
        return isset($module['adapter'])
            && $module['adapter'] === 'miscellaneous_contents';
    }

    private function content_section_service()
    {
        $this->CI->load->library('content_section_service');

        return $this->CI->content_section_service;
    }

    private function queue_web_page_sections(array $module, $entity_id)
    {
        $payload = $this->content_section_service()->web_translation_payload(
            (int) $entity_id
        );

        if ($payload === null || empty($payload['missing']))
        {
            return FALSE;
        }

        $dedupe_key = hash('sha256', implode('|', array(
            $module['entity_type'],
            (string) $entity_id,
            $module['source_language'],
            $module['target_language'],
            $payload['source_hash'],
        )));

        return $this->CI->translation_jobs->create_or_reset(array(
            'dedupe_key' => $dedupe_key,
            'module_key' => $module['module_key'],
            'entity_type' => $module['entity_type'],
            'entity_id' => (string) $entity_id,
            'source_language' => $module['source_language'],
            'target_language' => $module['target_language'],
            'field_keys' => json_encode(array_values($payload['missing'])),
            'source_hash' => $payload['source_hash'],
            'status' => 'PENDING',
            'attempts' => 0,
            'max_attempts' => 5,
            'next_attempt_at' => date('Y-m-d H:i:s'),
            'last_error' => NULL,
            'completed_at' => NULL,
        ));
    }

    private function queue_miscellaneous_contents(array $module, $entity_id)
    {
        $payload = $this->content_section_service()
            ->miscellaneous_translation_payload($entity_id);

        return $this->queue_content_section_payload($module, $entity_id, $payload);
    }

    private function queue_content_section_payload(array $module, $entity_id, $payload)
    {
        if ($payload === null || empty($payload['missing'])) {
            return FALSE;
        }

        $dedupe_key = hash('sha256', implode('|', array(
            $module['entity_type'],
            (string) $entity_id,
            $module['source_language'],
            $module['target_language'],
            $payload['source_hash'],
        )));

        return $this->CI->translation_jobs->create_or_reset(array(
            'dedupe_key' => $dedupe_key,
            'module_key' => $module['module_key'],
            'entity_type' => $module['entity_type'],
            'entity_id' => (string) $entity_id,
            'source_language' => $module['source_language'],
            'target_language' => $module['target_language'],
            'field_keys' => json_encode(array_values($payload['missing'])),
            'source_hash' => $payload['source_hash'],
            'status' => 'PENDING',
            'attempts' => 0,
            'max_attempts' => 5,
            'next_attempt_at' => date('Y-m-d H:i:s'),
            'last_error' => NULL,
            'completed_at' => NULL,
        ));
    }

    private function web_page_section_states(
        array $module,
        array $entity_ids,
        $include_values
    ) {
        $latest = $this->CI->translation_jobs->latest_for_entities(
            $module['module_key'],
            $module['entity_type'],
            $entity_ids
        );
        $states = array();

        foreach ($entity_ids as $entity_id)
        {
            $payload = $this->content_section_service()->web_translation_payload(
                (int) $entity_id
            );
            if ($payload === null)
            {
                continue;
            }

            $status = empty($payload['missing']) ? 'SUCCEEDED' : 'MISSING';
            $id = (string) $entity_id;
            $relevant = isset($latest[$id][$payload['source_hash']])
                ? $latest[$id][$payload['source_hash']]
                : NULL;

            if (!empty($payload['missing']) && $relevant !== NULL
                && in_array($relevant['status'], array('PENDING', 'RUNNING', 'FAILED'), TRUE))
            {
                $status = $relevant['status'];
            }

            $states[$id] = array('status' => $status);
            if ($include_values)
            {
                $values = array();
                foreach ($payload['items'] as $control => $item)
                {
                    $values[$control] = $item['target'];
                }
                $states[$id]['values'] = $values;
            }
        }

        return $states;
    }

    private function miscellaneous_content_states(
        array $module,
        array $entity_ids,
        $include_values
    ) {
        $latest = $this->CI->translation_jobs->latest_for_entities(
            $module['module_key'],
            $module['entity_type'],
            $entity_ids
        );
        $states = array();

        foreach ($entity_ids as $entity_id)
        {
            $payload = $this->content_section_service()
                ->miscellaneous_translation_payload($entity_id);
            if ($payload === null)
            {
                continue;
            }

            $status = empty($payload['missing']) ? 'SUCCEEDED' : 'MISSING';
            $id = (string) $entity_id;
            $relevant = isset($latest[$id][$payload['source_hash']])
                ? $latest[$id][$payload['source_hash']]
                : NULL;

            if (!empty($payload['missing']) && $relevant !== NULL
                && in_array($relevant['status'], array('PENDING', 'RUNNING', 'FAILED'), TRUE))
            {
                $status = $relevant['status'];
            }

            $states[$id] = array('status' => $status);
            if ($include_values)
            {
                $values = array();
                foreach ($payload['items'] as $control => $item)
                {
                    $values[$control] = $item['target'];
                }
                $states[$id]['values'] = $values;
            }
        }

        return $states;
    }

    private function process_web_page_sections_job(array $job, array $module)
    {
        $payload = $this->content_section_service()->web_translation_payload(
            (int) $job['entity_id']
        );
        if ($payload === null)
        {
            return $this->skip($job, 'The source record no longer exists.');
        }
        if (!hash_equals($job['source_hash'], $payload['source_hash']))
        {
            return $this->skip($job, 'Newer English content exists.');
        }

        $approved = json_decode($job['field_keys'], TRUE);
        $approved = is_array($approved) ? $approved : array();
        $items = array();
        foreach ($approved as $control)
        {
            if (isset($payload['items'][$control])
                && in_array($control, $payload['missing'], TRUE))
            {
                $items[] = $payload['items'][$control];
            }
        }

        if (empty($items))
        {
            $status = empty($payload['missing']) ? 'SUCCEEDED' : 'SKIPPED';
            $this->CI->translation_jobs->finish($job['id'], $status);

            return strtolower($status);
        }

        $translated = $this->content_section_service()->translate_web_items($items);
        if (empty($translated['success']))
        {
            return $this->fail($job, $translated['message']);
        }

        $saved = $this->content_section_service()->save_web_translation_values(
            (int) $job['entity_id'],
            $job['source_hash'],
            $translated['values']
        );
        if (empty($saved['success']))
        {
            if (!empty($saved['source_changed']))
            {
                return $this->skip($job, 'The English content changed while translating.');
            }

            return $this->fail($job, 'The translated values could not be saved.');
        }

        $current = $this->content_section_service()->web_translation_payload(
            (int) $job['entity_id']
        );
        $status = $current !== null && empty($current['missing'])
            ? 'SUCCEEDED'
            : 'SKIPPED';
        $this->CI->translation_jobs->finish($job['id'], $status);

        return strtolower($status);
    }

    private function process_miscellaneous_contents_job(array $job, array $module)
    {
        $payload = $this->content_section_service()
            ->miscellaneous_translation_payload($job['entity_id']);
        if ($payload === null)
        {
            return $this->skip($job, 'The source record no longer exists.');
        }
        if (!hash_equals($job['source_hash'], $payload['source_hash']))
        {
            return $this->skip($job, 'Newer English content exists.');
        }

        $approved = json_decode($job['field_keys'], TRUE);
        $approved = is_array($approved) ? $approved : array();
        $items = array();
        foreach ($approved as $control)
        {
            if (isset($payload['items'][$control])
                && in_array($control, $payload['missing'], TRUE))
            {
                $items[] = $payload['items'][$control];
            }
        }

        if (empty($items))
        {
            $status = empty($payload['missing']) ? 'SUCCEEDED' : 'SKIPPED';
            $this->CI->translation_jobs->finish($job['id'], $status);

            return strtolower($status);
        }

        $translated = $this->content_section_service()->translate_web_items($items);
        if (empty($translated['success']))
        {
            return $this->fail($job, $translated['message']);
        }

        $saved = $this->content_section_service()
            ->save_miscellaneous_translation_values(
                $job['entity_id'],
                $job['source_hash'],
                $translated['values']
            );
        if (empty($saved['success']))
        {
            if (!empty($saved['source_changed']))
            {
                return $this->skip($job, 'The English content changed while translating.');
            }

            return $this->fail($job, 'The translated values could not be saved.');
        }

        $current = $this->content_section_service()
            ->miscellaneous_translation_payload($job['entity_id']);
        $status = $current !== null && empty($current['missing'])
            ? 'SUCCEEDED'
            : 'SKIPPED';
        $this->CI->translation_jobs->finish($job['id'], $status);

        return strtolower($status);
    }

    private function translate_fields(array $module, array $record, array $fields)
    {
        $this->CI->load->library('google_translation_service');
        $groups = array();
        foreach ($fields as $field)
        {
            $groups[$field['mime_type']][] = $field;
        }
        $values = array();
        foreach ($groups as $mime_type => $group)
        {
            $contents = array();
            $masks = array();
            foreach ($group as $index => $field)
            {
                $masked = $this->mask_placeholders((string) $record[$field['source']], isset($field['placeholder_patterns']) ? $field['placeholder_patterns'] : array());
                $contents[] = $masked['text'];
                $masks[$index] = $masked['values'];
            }
            $result = $this->CI->google_translation_service->translate_batch($contents, $module['source_language'], $module['target_language'], $mime_type);
            if (empty($result['success'])) return $result;
            foreach ($group as $index => $field)
            {
                $restored = $this->restore_placeholders($result['translated_texts'][$index], $masks[$index]);
                if ($restored === FALSE)
                {
                    return array('success' => FALSE, 'status_code' => 502, 'message' => 'A protected placeholder was changed during translation.');
                }
                $values[$field['target']] = $this->truncate($restored, isset($field['max_length']) ? $field['max_length'] : NULL);
            }
        }
        return array('success' => TRUE, 'status_code' => 200, 'values' => $values);
    }

    private function record(array $module, $entity_id)
    {
        $entity_id = $this->entity_id($module, $entity_id);
        if ($entity_id === NULL)
        {
            return NULL;
        }
        $value = isset($module['id_type']) && $module['id_type'] === 'integer' ? (int) $entity_id : $entity_id;
        return $this->CI->db->where($module['primary_key'], $value)->get($module['table'])->row_array();
    }

    private function entity_id(array $module, $entity_id)
    {
        $entity_id = trim((string) $entity_id);
        if (isset($module['id_type']) && $module['id_type'] === 'integer')
        {
            return ctype_digit($entity_id) && (int) $entity_id > 0 ? (string) (int) $entity_id : NULL;
        }
        return preg_match('/^[A-Za-z0-9_-]{1,64}$/', $entity_id) ? $entity_id : NULL;
    }

    private function missing_fields(array $module, array $record)
    {
        $missing = array();
        foreach ($module['fields'] as $field)
        {
            if ($this->normalize(isset($record[$field['source']]) ? $record[$field['source']] : '') !== ''
                && $this->normalize(isset($record[$field['target']]) ? $record[$field['target']] : '') === '')
            {
                $missing[] = $field;
            }
        }
        return $missing;
    }

    private function source_hash(array $module, array $record)
    {
        $payload = array();
        foreach ($module['fields'] as $field)
        {
            $payload[$field['source']] = $this->normalize(isset($record[$field['source']]) ? $record[$field['source']] : '');
        }
        return hash('sha256', json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
    }

    private function normalize($value)
    {
        $value = str_replace(array("\r\n", "\r"), "\n", (string) $value);
        return trim($value);
    }

    private function truncate($value, $max_length)
    {
        if ($max_length === NULL || (int) $max_length < 1)
        {
            return (string) $value;
        }
        if (!function_exists('mb_strlen'))
        {
            return strlen($value) > (int) $max_length ? substr($value, 0, (int) $max_length) : (string) $value;
        }
        return mb_strlen($value, 'UTF-8') > (int) $max_length ? mb_substr($value, 0, (int) $max_length, 'UTF-8') : (string) $value;
    }

    private function mask_placeholders($text, array $patterns)
    {
        $values = array();
        foreach ($patterns as $pattern)
        {
            $text = preg_replace_callback($pattern, function ($match) use (&$values) {
                $token = 'ZXQPH'.count($values).'QXZ';
                $values[$token] = $match[0];
                return $token;
            }, $text);
        }
        return array('text' => $text, 'values' => $values);
    }

    private function restore_placeholders($text, array $values)
    {
        foreach ($values as $token => $original)
        {
            if (strpos($text, $token) === FALSE) return FALSE;
            $text = str_replace($token, $original, $text);
        }
        return $text;
    }

    private function fail(array $job, $message)
    {
        $attempts = (int) $job['attempts'];
        $delays = array(1, 5, 15, 60);
        $next = NULL;
        if ($attempts < (int) $job['max_attempts'])
        {
            $minutes = isset($delays[$attempts - 1]) ? $delays[$attempts - 1] : 60;
            $next = date('Y-m-d H:i:s', time() + ($minutes * 60));
        }
        $this->CI->translation_jobs->finish($job['id'], 'FAILED', $this->safe_error($message), $next);
        return 'failed';
    }

    private function skip(array $job, $message)
    {
        $this->CI->translation_jobs->finish($job['id'], 'SKIPPED', $this->safe_error($message));
        return 'skipped';
    }

    private function safe_error($message)
    {
        $message = trim(strip_tags((string) $message));
        $message = preg_replace('/\s+/', ' ', $message);
        return $this->truncate($message === '' ? 'Translation failed.' : $message, 500);
    }
}
