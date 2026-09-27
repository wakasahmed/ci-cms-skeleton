<?php defined('BASEPATH') OR exit('No direct script access allowed');

class Content_section_service
{
    private $CI;
    private $settings = array();
    private $read_cache = array();
    private $types = array(
        'text',
        'textarea',
        'text-editor',
        'image',
        'select',
        'icon-picker',
    );
    private $layouts = array('full', 'half', 'third');

    public function __construct()
    {
        $this->CI =& get_instance();
        $this->CI->config->load('content_sections', true);
        $this->settings = (array) $this->CI->config->item('content_sections');
        $this->CI->load->model('Web_page_section_model', 'web_page_sections');
        $this->CI->load->model('Miscellaneous_content_model', 'miscellaneous_contents');
    }

    public function locales()
    {
        return isset($this->settings['content_locales']) ? $this->settings['content_locales'] : array();
    }

    public function locale($locale)
    {
        $locale = strtolower(trim((string) $locale));
        return isset($this->locales()[$locale]) ? $locale : false;
    }

    public function configured_page_ids()
    {
        return array_map('intval', array_keys(isset($this->settings['web_page_sections']) ? $this->settings['web_page_sections'] : array()));
    }

    public function web_definitions($page_id)
    {
        $all = isset($this->settings['web_page_sections']) ? $this->settings['web_page_sections'] : array();
        return isset($all[(int) $page_id]) ? $this->normalize_definitions($all[(int) $page_id]) : array();
    }

    public function miscellaneous_definitions()
    {
        return $this->normalize_definitions(isset($this->settings['miscellaneous_content_sections']) ? $this->settings['miscellaneous_content_sections'] : array());
    }

    public function miscellaneous_definition($key)
    {
        foreach ($this->miscellaneous_definitions() as $definition) {
            if (hash_equals($definition['key'], (string) $key)) {
                return $definition;
            }
        }
        return null;
    }

    public function can($capability, array $admin)
    {
        if (isset($admin['user_role']) && $admin['user_role'] === 'Super Admin') {
            return true;
        }
        $map = array(
            'web_pages.view' => 'access_pages',
            'web_pages.update' => 'access_pages',
            'miscellaneous_contents.view' => 'access_sections',
            'miscellaneous_contents.update' => 'access_sections',
        );
        return isset($map[$capability], $admin[$map[$capability]]) && $admin[$map[$capability]] === 'Yes';
    }

    public function field_name($section_key, $field_key)
    {
        return $section_key.'__'.$field_key;
    }

    public function status_name($section_key)
    {
        return $section_key.'__section_status';
    }

    public function configure_ckeditor($locale)
    {
        $this->CI->load->library('ckeditor');
        $this->CI->load->library('ckfinder');
        $this->CI->ckeditor->basePath = base_url('assets/ckeditor/');
        $this->CI->ckeditor->config['removePlugins'] = implode(', ', array(
            'save',
            'preview',
            'newpage',
            'forms',
            'flash',
        ));
        $this->CI->ckeditor->config['height'] = '340px';
        $this->CI->ckeditor->config['contentsLangDirection'] = $locale === 'ar'
            ? 'rtl'
            : 'ltr';
        $this->CI->ckfinder->SetupCKEditor(
            $this->CI->ckeditor,
            '../../../../assets/ckfinder/'
        );
    }

    public function ensure_web_page_sections($page_id)
    {
        if (!preg_match('/^[1-9][0-9]*$/', (string) $page_id)) {
            return array('status' => 'missing', 'page' => null, 'sections' => array());
        }
        $page = $this->CI->web_page_sections->find_page((int) $page_id);
        if (!$page) {
            return array('status' => 'missing', 'page' => null, 'sections' => array());
        }
        $definitions = $this->web_definitions((int) $page_id);
        if (empty($definitions)) {
            return array('status' => 'unsupported', 'page' => $page, 'sections' => array());
        }
        $sections = $this->CI->web_page_sections->ensure_sections((int) $page_id, $definitions);
        return array(
            'status' => $sections === false ? 'error' : 'ok',
            'page' => $page,
            'sections' => $sections ?: array(),
            'definitions' => $definitions,
        );
    }

    public function ensure_miscellaneous_content_sections()
    {
        $definitions = $this->miscellaneous_definitions();
        $sections = $this->CI->miscellaneous_contents->ensure_sections($definitions);
        return array(
            'status' => $sections === false ? 'error' : 'ok',
            'sections' => $sections ?: array(),
            'definitions' => $definitions,
        );
    }

    /**
     * Every configured field (config/content_sections.php) that has no row
     * in the database for a configured locale.
     *
     * Missing section rows are created first through the same ensure_*()
     * calls the editors and frontend already run on every load.
     */
    public function missing_fields()
    {
        $result = array(
            'status' => 'ok',
            'items' => array(),
        );

        foreach ($this->configured_page_ids() as $page_id) {
            $ensured = $this->ensure_web_page_sections($page_id);
            if ($ensured['status'] === 'missing' || $ensured['status'] === 'unsupported') {
                continue;
            }
            if ($ensured['status'] !== 'ok') {
                $result['status'] = 'error';
                continue;
            }

            $page_name = html_entity_decode(
                (string) $ensured['page']['page_name'],
                ENT_QUOTES,
                'UTF-8'
            );
            $rows = $this->CI->web_page_sections->get_fields(
                array_column($ensured['sections'], 'section_id'),
                array_keys($this->locales())
            );

            $result['items'] = array_merge(
                $result['items'],
                $this->missing_field_items(
                    'web',
                    $page_name,
                    $ensured['definitions'],
                    $ensured['sections'],
                    $rows
                )
            );
        }

        $ensured = $this->ensure_miscellaneous_content_sections();
        if ($ensured['status'] !== 'ok') {
            $result['status'] = 'error';
            return $result;
        }

        $rows = $this->CI->miscellaneous_contents->get_fields(
            array_column($ensured['sections'], 'section_id'),
            array_keys($this->locales())
        );

        $result['items'] = array_merge(
            $result['items'],
            $this->missing_field_items(
                'misc',
                'Miscellaneous Contents',
                $ensured['definitions'],
                $ensured['sections'],
                $rows
            )
        );

        return $result;
    }

    /**
     * Insert the rows reported by missing_fields(). Existing rows are never
     * changed. Returns the number of inserted rows, or false on failure.
     */
    public function add_missing_fields(array $admin)
    {
        $missing = $this->missing_fields();
        if ($missing['status'] !== 'ok') {
            return false;
        }

        $web_rows = array();
        $misc_rows = array();
        $page_ids = array();
        foreach ($missing['items'] as $item) {
            if ($item['scope'] === 'web') {
                $web_rows[] = $item;
                $page_ids[$item['page_id']] = true;
            } else {
                $misc_rows[] = $item;
            }
        }

        if (empty($web_rows) && empty($misc_rows)) {
            return 0;
        }

        $inserted = 0;
        if (!empty($web_rows)) {
            $count = $this->CI->web_page_sections->insert_missing_fields($web_rows);
            if ($count === false) {
                return false;
            }
            $inserted += $count;

            foreach (array_keys($page_ids) as $page_id) {
                $this->invalidate_web_cache($page_id);
            }
        }

        if (!empty($misc_rows)) {
            $count = $this->CI->miscellaneous_contents->insert_missing_fields($misc_rows);
            if ($count === false) {
                return false;
            }
            $inserted += $count;
            $this->invalidate_miscellaneous_cache();
        }

        $this->audit('content_section_fields', 'add_missing', $admin, array(
            'inserted' => $inserted,
        ));

        return $inserted;
    }

    public function web_editor($page_id, $locale)
    {
        $ensured = $this->ensure_web_page_sections($page_id);
        if ($ensured['status'] !== 'ok') {
            return $ensured;
        }
        $ensured['editor_sections'] = $this->map_editor_sections($ensured['definitions'], $ensured['sections'], $locale, 'web');
        return $ensured;
    }

    public function web_translation_state($page_id, $include_values = false)
    {
        $page_id = (int) $page_id;
        $english = $this->web_editor($page_id, 'en');
        $arabic = $this->web_editor($page_id, 'ar');

        if ($english['status'] !== 'ok' || $arabic['status'] !== 'ok') {
            return null;
        }

        $arabic_sections = $this->sections_by_key($arabic['editor_sections']);
        $missing = false;
        $values = array();

        foreach ($english['editor_sections'] as $section) {
            $arabic_values = isset($arabic_sections[$section['key']]['values'])
                ? $arabic_sections[$section['key']]['values']
                : array();

            foreach ($section['fields'] as $field) {
                if (!$this->is_translatable_field($field)) {
                    continue;
                }

                $source = isset($section['values'][$field['key']])
                    ? $section['values'][$field['key']]
                    : '';
                $source = $this->translation_value($source, $field);

                if ($source === null) {
                    continue;
                }

                $control = $this->field_name($section['key'], $field['key']);
                $target = isset($arabic_values[$field['key']])
                    ? $arabic_values[$field['key']]
                    : '';

                if ($this->plain_text($target, $this->is_multiline_field($field)) === null) {
                    $missing = true;
                }

                if ($include_values) {
                    $values[$control] = (string) $target;
                }
            }
        }

        $state = array('status' => $missing ? 'MISSING' : 'SUCCEEDED');
        if ($include_values) {
            $state['values'] = $values;
        }

        return $state;
    }

    public function web_translation_payload($page_id)
    {
        $page_id = (int) $page_id;
        $english = $this->web_editor($page_id, 'en');
        $arabic = $this->web_editor($page_id, 'ar');

        if ($english['status'] !== 'ok' || $arabic['status'] !== 'ok') {
            return null;
        }

        $arabic_sections = $this->sections_by_key($arabic['editor_sections']);
        $items = array();
        $missing = array();
        $source = array();

        foreach ($english['editor_sections'] as $section) {
            $arabic_values = isset($arabic_sections[$section['key']]['values'])
                ? $arabic_sections[$section['key']]['values']
                : array();

            foreach ($section['fields'] as $field) {
                if (!$this->is_translatable_field($field)) {
                    continue;
                }

                $value = isset($section['values'][$field['key']])
                    ? $section['values'][$field['key']]
                    : '';
                $value = $this->translation_value($value, $field);
                if ($value === null) {
                    continue;
                }

                $control = $this->field_name($section['key'], $field['key']);
                $target = isset($arabic_values[$field['key']])
                    ? $arabic_values[$field['key']]
                    : '';
                $items[$control] = array(
                    'control' => $control,
                    'section_id' => (int) $section['section_id'],
                    'field_key' => $field['key'],
                    'value' => $value,
                    'target' => (string) $target,
                    'max_length' => $this->is_multiline_field($field) ? 65535 : 255,
                    'mime_type' => $this->is_rich_text_field($field) ? 'text/html' : 'text/plain',
                );
                $source[$control] = $value;

                if ($this->plain_text($target, $this->is_multiline_field($field)) === null) {
                    $missing[] = $control;
                }
            }
        }

        return array(
            'page_id' => $page_id,
            'items' => $items,
            'missing' => $missing,
            'source_hash' => hash(
                'sha256',
                json_encode($source, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)
            ),
        );
    }

    public function translate_web_items(array $items)
    {
        if (empty($items)) {
            return array(
                'success' => false,
                'status_code' => 422,
                'message' => 'There is no English content to translate.',
            );
        }

        $this->CI->load->library('google_translation_service');
        $values = array();
        $batch = array();
        $batch_length = 0;
        $batch_mime_type = null;

        foreach ($items as $item) {
            $length = $this->text_length($item['value']);
            $mime_type = isset($item['mime_type']) ? $item['mime_type'] : 'text/plain';
            if ($length > 30000) {
                return array(
                    'success' => false,
                    'status_code' => 422,
                    'message' => 'A section field exceeds the 30,000 character translation limit.',
                );
            }

            if (!empty($batch) && (
                count($batch) === 100
                || $batch_length + $length > 30000
                || $batch_mime_type !== $mime_type
            )) {
                $result = $this->translate_web_batch($batch, $values);
                if (!$result['success']) {
                    return $result;
                }

                $batch = array();
                $batch_length = 0;
            }

            $batch_mime_type = $mime_type;
            $batch[] = $item;
            $batch_length += $length;
        }

        $result = $this->translate_web_batch($batch, $values);
        if (!$result['success']) {
            return $result;
        }

        return array(
            'success' => true,
            'status_code' => 200,
            'values' => $values,
        );
    }

    public function save_web_translation_values($page_id, $source_hash, array $values)
    {
        $page_id = (int) $page_id;
        $this->CI->db->trans_begin();
        $this->CI->db->query(
            'SELECT id FROM web_page_sections WHERE page_id = ? FOR UPDATE',
            array($page_id)
        );

        $payload = $this->web_translation_payload($page_id);
        if ($payload === null || !hash_equals($source_hash, $payload['source_hash'])) {
            $this->CI->db->trans_rollback();

            return array('success' => false, 'source_changed' => true);
        }

        $now = date('Y-m-d H:i:s');
        foreach ($values as $control => $value) {
            if (!isset($payload['items'][$control])) {
                continue;
            }

            $item = $payload['items'][$control];
            if ($this->plain_text($item['target'], false) !== null) {
                continue;
            }

            $this->CI->db->query(
                'INSERT INTO web_page_section_fields (section_id, locale, field_key, field_value, created_at, updated_at) '
                .'VALUES (?, ?, ?, ?, ?, ?) ON DUPLICATE KEY UPDATE field_value=VALUES(field_value), updated_at=VALUES(updated_at)',
                array($item['section_id'], 'ar', $item['field_key'], $value, $now, $now)
            );
        }

        if ($this->CI->db->trans_status() === false) {
            $this->CI->db->trans_rollback();

            return array('success' => false, 'source_changed' => false);
        }

        $this->CI->db->trans_commit();
        $this->invalidate_web_cache($page_id);

        return array('success' => true, 'source_changed' => false);
    }

    public function translate_web_from_english($page_id)
    {
        $page_id = (int) $page_id;
        $editor = $this->web_editor($page_id, 'en');

        if ($editor['status'] !== 'ok') {
            return array(
                'success' => false,
                'status_code' => 404,
                'message' => 'The page sections were not found.',
            );
        }

        $items = array();
        foreach ($editor['editor_sections'] as $section) {
            foreach ($section['fields'] as $field) {
                if (!$this->is_translatable_field($field)) {
                    continue;
                }

                $value = isset($section['values'][$field['key']])
                    ? $section['values'][$field['key']]
                    : '';
                $value = $this->translation_value($value, $field);

                if ($value === null) {
                    continue;
                }

                $items[] = array(
                    'control' => $this->field_name($section['key'], $field['key']),
                    'value' => $value,
                    'max_length' => $this->is_multiline_field($field) ? 65535 : 255,
                    'mime_type' => $this->is_rich_text_field($field) ? 'text/html' : 'text/plain',
                );
            }
        }

        if (empty($items)) {
            return array(
                'success' => false,
                'status_code' => 422,
                'message' => 'There is no English content to translate.',
            );
        }

        $this->CI->load->library('google_translation_service');
        $values = array();
        $batch = array();
        $batch_length = 0;

        foreach ($items as $item) {
            $length = $this->text_length($item['value']);
            if ($length > 30000) {
                return array(
                    'success' => false,
                    'status_code' => 422,
                    'message' => 'A section field exceeds the 30,000 character translation limit.',
                );
            }

            if (count($batch) === 100 || $batch_length + $length > 30000) {
                $result = $this->translate_web_batch($batch, $values);
                if (!$result['success']) {
                    return $result;
                }

                $batch = array();
                $batch_length = 0;
            }

            $batch[] = $item;
            $batch_length += $length;
        }

        $result = $this->translate_web_batch($batch, $values);
        if (!$result['success']) {
            return $result;
        }

        return array(
            'success' => true,
            'status_code' => 200,
            'message' => 'Arabic draft created. Save the form to keep it.',
            'values' => $values,
        );
    }

    public function miscellaneous_editor($key, $locale)
    {
        $definition = $this->miscellaneous_definition($key);
        if (!$definition) {
            return array('status' => 'missing');
        }
        $ensured = $this->ensure_miscellaneous_content_sections();
        if ($ensured['status'] !== 'ok') {
            return $ensured;
        }
        $row = null;
        foreach ($ensured['sections'] as $section) {
            if ($section['section_key'] === $key) {
                $row = $section;
            }
        }
        if (!$row) {
            return array('status' => 'error');
        }
        $mapped = $this->map_editor_sections(array($definition), array($row), $locale, 'misc');
        return array('status' => 'ok', 'definition' => $definition, 'section' => $row, 'editor_section' => $mapped[0]);
    }

    public function miscellaneous_translation_payload($key)
    {
        $english = $this->miscellaneous_editor($key, 'en');
        $arabic = $this->miscellaneous_editor($key, 'ar');

        if ($english['status'] !== 'ok' || $arabic['status'] !== 'ok') {
            return null;
        }

        $items = array();
        $missing = array();
        $source = array();
        foreach ($english['editor_section']['fields'] as $field) {
            if (!$this->is_translatable_field($field)) {
                continue;
            }

            $value = isset($english['editor_section']['values'][$field['key']])
                ? $english['editor_section']['values'][$field['key']]
                : '';
            $value = $this->translation_value($value, $field);
            if ($value === null) {
                continue;
            }

            $control = $this->field_name($key, $field['key']);
            $target = isset($arabic['editor_section']['values'][$field['key']])
                ? $arabic['editor_section']['values'][$field['key']]
                : '';
            $items[$control] = array(
                'control' => $control,
                'section_id' => (int) $english['section']['section_id'],
                'field_key' => $field['key'],
                'value' => $value,
                'target' => (string) $target,
                'max_length' => $this->is_multiline_field($field) ? 65535 : 255,
                'mime_type' => $this->is_rich_text_field($field) ? 'text/html' : 'text/plain',
            );
            $source[$control] = $value;

            if ($this->plain_text($target, $this->is_multiline_field($field)) === null) {
                $missing[] = $control;
            }
        }

        return array(
            'key' => $key,
            'items' => $items,
            'missing' => $missing,
            'source_hash' => hash(
                'sha256',
                json_encode($source, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)
            ),
        );
    }

    public function save_miscellaneous_translation_values($key, $source_hash, array $values)
    {
        $payload = $this->miscellaneous_translation_payload($key);
        if ($payload === null || !hash_equals($source_hash, $payload['source_hash'])) {
            return array('success' => false, 'source_changed' => true);
        }

        $items = array_values($payload['items']);
        if (empty($items)) {
            return array('success' => true, 'source_changed' => false);
        }

        $this->CI->db->trans_begin();
        $this->CI->db->query(
            'SELECT id FROM miscellaneous_content_sections WHERE id = ? FOR UPDATE',
            array((int) $items[0]['section_id'])
        );
        $payload = $this->miscellaneous_translation_payload($key);
        if ($payload === null || !hash_equals($source_hash, $payload['source_hash'])) {
            $this->CI->db->trans_rollback();

            return array('success' => false, 'source_changed' => true);
        }

        $now = date('Y-m-d H:i:s');
        foreach ($values as $control => $value) {
            if (!isset($payload['items'][$control])
                || $this->plain_text($payload['items'][$control]['target'], false) !== null)
            {
                continue;
            }

            $item = $payload['items'][$control];
            $this->CI->db->query(
                'INSERT INTO miscellaneous_content_section_fields (section_id, locale, field_key, field_value, created_at, updated_at) '
                .'VALUES (?, ?, ?, ?, ?, ?) ON DUPLICATE KEY UPDATE field_value=VALUES(field_value), updated_at=VALUES(updated_at)',
                array($item['section_id'], 'ar', $item['field_key'], $value, $now, $now)
            );
        }

        if ($this->CI->db->trans_status() === false) {
            $this->CI->db->trans_rollback();

            return array('success' => false, 'source_changed' => false);
        }

        $this->CI->db->trans_commit();
        $this->invalidate_miscellaneous_cache();

        return array('success' => true, 'source_changed' => false);
    }

    public function translate_miscellaneous_from_english($key)
    {
        $payload = $this->miscellaneous_translation_payload($key);
        if ($payload === null) {
            return array('success' => false, 'status_code' => 404, 'message' => 'The content section was not found.');
        }

        $result = $this->translate_web_items(array_values($payload['items']));
        if (empty($result['success'])) {
            return $result;
        }

        $result['message'] = 'Arabic draft created. Save the form to keep it.';

        return $result;
    }

    public function miscellaneous_listing()
    {
        $ensured = $this->ensure_miscellaneous_content_sections();
        if ($ensured['status'] !== 'ok') {
            return $ensured;
        }
        $ids = array_column($ensured['sections'], 'section_id');
        $fields = $this->CI->miscellaneous_contents->get_fields($ids, array('ar'));
        $values = $this->field_map($fields);
        $defs = array();
        foreach ($ensured['definitions'] as $definition) {
            $defs[$definition['key']] = $definition;
        }
        $rows = array();
        foreach ($ensured['sections'] as $section) {
            if (!isset($defs[$section['section_key']])) {
                continue;
            }

            $section_values = isset($values[$section['section_id']]['ar']) ? $values[$section['section_id']]['ar'] : array();
            $section['arabic_status'] = $this->arabic_status($defs[$section['section_key']], $section_values);
            $rows[] = $section;
        }
        return array('status' => 'ok', 'sections' => $rows);
    }

    public function validate_web(array $editor, $locale, array $post)
    {
        $definitions_by_key = array();
        foreach ($editor['definitions'] as $definition) {
            $definitions_by_key[$definition['key']] = $definition;
        }
        $definitions = array();
        foreach ($editor['editor_sections'] as $item) {
            $definitions[] = $definitions_by_key[$item['key']];
        }
        $sections = $this->rows_by_key($editor['sections']);
        $current = array();
        foreach ($editor['editor_sections'] as $item) {
            $current[$item['key']] = $item['values'];
        }
        $result = $this->validate($definitions, $locale, $post, $current, true);
        $result['sections'] = $sections;
        $result['translation_invalidations'] = array();

        if ($locale === 'en') {
            foreach ($definitions as $definition) {
                foreach ($definition['fields'] as $field) {
                    if (!$this->is_translatable_field($field)) {
                        continue;
                    }

                    $previous = isset($current[$definition['key']][$field['key']])
                        ? $current[$definition['key']][$field['key']]
                        : '';
                    $submitted = isset($result['values'][$definition['key']][$field['key']])
                        ? $result['values'][$definition['key']][$field['key']]
                        : '';

                    if ($this->translation_value($previous, $field)
                        !== $this->translation_value($submitted, $field))
                    {
                        $result['translation_invalidations'][$definition['key']][] = $field['key'];
                    }
                }
            }
        }

        return $result;
    }

    public function validate_miscellaneous(array $editor, $locale, array $post)
    {
        $definition = $editor['definition'];
        $current = array($definition['key'] => $editor['editor_section']['values']);
        $result = $this->validate(array($definition), $locale, $post, $current, false);
        $result['section'] = $editor['section'];
        $result['translation_invalidations'] = array();

        if ($locale === 'en') {
            foreach ($definition['fields'] as $field) {
                if (!$this->is_translatable_field($field)) {
                    continue;
                }

                $previous = isset($current[$definition['key']][$field['key']])
                    ? $current[$definition['key']][$field['key']]
                    : '';
                $submitted = isset($result['values'][$definition['key']][$field['key']])
                    ? $result['values'][$definition['key']][$field['key']]
                    : '';

                if ($this->translation_value($previous, $field)
                    !== $this->translation_value($submitted, $field))
                {
                    $result['translation_invalidations'][] = $field['key'];
                }
            }
        }

        return $result;
    }

    public function save_web($page_id, $locale, array $validated, array $admin)
    {
        $ok = $this->CI->web_page_sections->save(
            (int) $page_id,
            $validated['sections'],
            $locale,
            $validated['values'],
            $validated['statuses'],
            $validated['order'],
            isset($validated['translation_invalidations'])
                ? $validated['translation_invalidations']
                : array()
        );
        if (!$ok) {
            $this->cleanup_files($validated['new_files']);
            return false;
        }
        $this->cleanup_files($validated['old_files']);
        $this->invalidate_web_cache($page_id);
        $this->audit('web_page_sections', 'update', $admin, array(
            'page_id' => (int) $page_id,
            'locale' => $locale,
            'statuses' => $validated['statuses'],
            'ordering' => $validated['order'],
        ));
        return true;
    }

    public function save_miscellaneous($key, $locale, array $validated, array $admin)
    {
        $values = $validated['values'][$key];
        $status = $validated['statuses'][$key];
        $ok = $this->CI->miscellaneous_contents->save(
            $validated['section'],
            $locale,
            $values,
            $status,
            isset($validated['translation_invalidations'])
                ? $validated['translation_invalidations']
                : array()
        );
        if (!$ok) {
            $this->cleanup_files($validated['new_files']);
            return false;
        }
        $this->cleanup_files($validated['old_files']);
        $this->invalidate_miscellaneous_cache();
        $this->audit('miscellaneous_contents', 'update', $admin, array(
            'section_key' => $key,
            'locale' => $locale,
            'status' => $status,
        ));
        return true;
    }

    public function update_miscellaneous_status($section_id, $status, array $admin)
    {
        if (!in_array($status, array('Enable', 'Disable'), true)) {
            return false;
        }
        $section = $this->CI->miscellaneous_contents->get_section($section_id);
        if (!$section || !$this->miscellaneous_definition($section['section_key'])) {
            return false;
        }
        $updated = $this->CI->miscellaneous_contents->update_status($section_id, $status);
        if (!$updated) {
            return false;
        }
        $this->invalidate_miscellaneous_cache();
        $this->audit('miscellaneous_contents', 'status_update', $admin, array(
            'section_key' => $updated['section_key'],
            'status' => $updated['section_status'],
        ));
        return $updated;
    }

    public function apply_submitted_values(array $editor_sections, array $validated)
    {
        foreach ($editor_sections as &$section) {
            if (isset($validated['values'][$section['key']])) {
                $section['values'] = $validated['values'][$section['key']];
            }
            if (isset($validated['statuses'][$section['key']])) {
                $section['section_status'] = $validated['statuses'][$section['key']];
            }
        }
        unset($section);
        return $editor_sections;
    }

    public function cleanup_validation_uploads(array $validated)
    {
        $this->cleanup_files(isset($validated['new_files']) ? $validated['new_files'] : array());
    }

    public function get_web_page_sections($page_id, $locale = 'en')
    {
        $locale = $this->locale($locale);
        if ($locale === false) {
            return array();
        }
        $cache_key = 'web:'.(int) $page_id.':'.$locale;
        if (isset($this->read_cache[$cache_key])) {
            return $this->read_cache[$cache_key];
        }
        $ensured = $this->ensure_web_page_sections($page_id);
        if ($ensured['status'] !== 'ok') {
            return array();
        }
        $configured = array();
        foreach ($ensured['definitions'] as $definition) {
            $configured[$definition['key']] = $definition;
        }
        $sections = array_values(array_filter($ensured['sections'], function ($row) use ($configured) {
            return $row['section_status'] === 'Enable' && isset($configured[$row['section_key']]);
        }));
        return $this->read_cache[$cache_key] = $this->frontend_map($sections, $configured, $locale, 'web');
    }

    public function get_miscellaneous_contents($locale = 'en')
    {
        $locale = $this->locale($locale);
        if ($locale === false) {
            return array();
        }
        $cache_key = 'misc:'.$locale;
        if (isset($this->read_cache[$cache_key])) {
            return $this->read_cache[$cache_key];
        }
        $ensured = $this->ensure_miscellaneous_content_sections();
        if ($ensured['status'] !== 'ok') {
            return array();
        }
        $configured = array();
        foreach ($ensured['definitions'] as $definition) {
            $configured[$definition['key']] = $definition;
        }
        $sections = array_values(array_filter($ensured['sections'], function ($row) use ($configured) {
            return $row['section_status'] === 'Enable' && isset($configured[$row['section_key']]);
        }));
        return $this->read_cache[$cache_key] = $this->frontend_map($sections, $configured, $locale, 'misc');
    }

    public function image_url($value)
    {
        $value = $this->normalize_image($value);
        if ($value === null) {
            return null;
        }
        return base_url($this->image_path().rawurlencode($value));
    }

    private function normalize_definitions(array $definitions)
    {
        $normalized = array();
        $section_keys = array();
        foreach ($definitions as $section) {
            if (!is_array($section) || empty($section['key']) || !preg_match('/^[a-z][a-z0-9_]*$/', $section['key']) || isset($section_keys[$section['key']])) {
                continue;
            }
            $section_keys[$section['key']] = true;
            $fields = array();
            $field_keys = array();
            foreach (isset($section['fields']) && is_array($section['fields']) ? $section['fields'] : array() as $field) {
                if (!is_array($field) || empty($field['key']) || !preg_match('/^[a-z][a-z0-9_]*$/', $field['key']) || isset($field_keys[$field['key']])) {
                    continue;
                }
                $type = isset($field['type']) && in_array($field['type'], $this->types, true) ? $field['type'] : 'text';
                $defaults = array(
                    'text' => 'half',
                    'icon-picker' => 'half',
                    'textarea' => 'full',
                    'text-editor' => 'full',
                    'image' => 'full',
                    'select' => 'full',
                );
                $layout = isset($field['layout']) && in_array($field['layout'], $this->layouts, true) ? $field['layout'] : $defaults[$type];
                $field_keys[$field['key']] = true;
                $fields[] = array(
                    'key' => $field['key'],
                    'type' => $type,
                    'label' => isset($field['label']) ? (string) $field['label'] : ucwords(str_replace('_', ' ', $field['key'])),
                    'group' => isset($field['group']) && $field['group'] !== '' ? (string) $field['group'] : null,
                    'required' => !empty($field['required']),
                    'layout' => $layout,
                    'options' => isset($field['options']) && is_array($field['options']) ? $field['options'] : array(),
                    'recommended_size' => isset($field['recommended_size']) ? (string) $field['recommended_size'] : '',
                    'size_note' => isset($field['size_note']) ? (string) $field['size_note'] : '',
                );
            }
            $normalized[] = array(
                'key' => $section['key'],
                'label' => isset($section['label']) ? (string) $section['label'] : $section['key'],
                'fields' => $fields,
            );
        }
        return $normalized;
    }

    private function map_editor_sections(array $definitions, array $rows, $locale, $type)
    {
        $by_key = $this->rows_by_key($rows);
        $ids = array_column($rows, 'section_id');
        $field_rows = $type === 'web'
            ? $this->CI->web_page_sections->get_fields($ids, array($locale))
            : $this->CI->miscellaneous_contents->get_fields($ids, array($locale));
        $field_map = $this->field_map($field_rows);
        $result = array();
        foreach ($definitions as $definition) {
            if (!isset($by_key[$definition['key']])) {
                continue;
            }
            $row = $by_key[$definition['key']];
            $values = array();
            foreach ($definition['fields'] as $field) {
                $values[$field['key']] = isset($field_map[$row['section_id']][$locale][$field['key']]) ? $field_map[$row['section_id']][$locale][$field['key']] : null;
            }
            $result[] = array_merge($definition, $row, array('values' => $values));
        }
        usort($result, function ($a, $b) {
            $a_order = isset($a['sort_order']) ? (int) $a['sort_order'] : 0;
            $b_order = isset($b['sort_order']) ? (int) $b['sort_order'] : 0;
            return $a_order <=> $b_order;
        });
        return $result;
    }

    private function validate(array $definitions, $locale, array $post, array $current, $with_order)
    {
        $result = array(
            'valid' => true,
            'values' => array(),
            'statuses' => array(),
            'order' => array(),
            'errors' => array(),
            'new_files' => array(),
            'old_files' => array(),
        );
        $configured_keys = array();
        foreach ($definitions as $definition) {
            $section_key = $definition['key'];
            $configured_keys[] = $section_key;
            $status_name = $this->status_name($section_key);
            $status = isset($post[$status_name]) ? $post[$status_name] : '';
            $allowed_statuses = array('Enable', 'Disable');
            if (!in_array($status, $allowed_statuses, true)) {
                $result['errors'][$status_name] = 'Select a valid section status.';
                $status = 'Enable';
            }
            $result['statuses'][$section_key] = $status;
            $result['values'][$section_key] = array();
            foreach ($definition['fields'] as $field) {
                $name = $this->field_name($section_key, $field['key']);
                $existing = isset($current[$section_key][$field['key']]) ? $current[$section_key][$field['key']] : null;
                if ($field['type'] === 'image') {
                    $processed = $this->validate_image_field($name, $field, $locale, $post, $existing);
                    $value = $processed['value'];
                    if ($processed['error']) {
                        $result['errors'][$name] = $processed['error'];
                    }
                    if ($processed['new_file']) {
                        $result['new_files'][] = $processed['new_file'];
                    }
                    if ($processed['old_file']) {
                        $result['old_files'][] = $processed['old_file'];
                    }
                } else {
                    $value = isset($post[$name]) && !is_array($post[$name]) ? $post[$name] : '';
                    $validated = $this->validate_value($field, $value, $locale);
                    $value = $validated['value'];
                    if ($validated['error']) {
                        $result['errors'][$name] = $validated['error'];
                    }
                }
                $result['values'][$section_key][$field['key']] = $value;
            }
        }
        if ($with_order) {
            $submitted = isset($post['section_order']) && is_array($post['section_order']) ? $post['section_order'] : array();
            $seen = array();
            foreach ($submitted as $key) {
                if (!is_string($key) || !in_array($key, $configured_keys, true) || isset($seen[$key])) {
                    continue;
                }
                $seen[$key] = true;
                $result['order'][] = $key;
            }
            foreach ($configured_keys as $key) {
                if (!isset($seen[$key])) {
                    $result['order'][] = $key;
                }
            }
        } else {
            $result['order'] = $configured_keys;
        }
        $result['valid'] = empty($result['errors']);
        if (!$result['valid']) {
            $this->cleanup_files($result['new_files']);
        }
        return $result;
    }

    private function validate_value(array $field, $value, $locale)
    {
        $value = $this->is_rich_text_field($field)
            ? $this->rich_text($value)
            : $this->plain_text($value, $this->is_multiline_field($field));
        $error = null;
        if ($field['type'] === 'icon-picker' && $value !== null && !preg_match('/^fa-(solid|regular|brands) fa-[a-z0-9-]+$/', $value)) {
            $error = 'Select a valid Font Awesome icon.';
        } elseif ($field['type'] === 'select' && $value !== null && !array_key_exists($value, $field['options'])) {
            $error = 'Select a valid option.';
        } elseif (!$this->is_multiline_field($field) && $value !== null && $this->text_length($value) > 255) {
            $error = 'Use 255 characters or fewer.';
        } elseif ($this->is_multiline_field($field) && $value !== null && strlen($value) > 65535) {
            $error = 'This content is too long.';
        }
        if ($locale === 'en' && $field['required'] && $value === null) {
            $error = 'This field is required.';
        }
        return array('value' => $value, 'error' => $error);
    }

    private function validate_image_field($name, array $field, $locale, array $post, $existing)
    {
        $existing = $this->normalize_image($existing);
        $remove = isset($post[$name.'_remove']) && $post[$name.'_remove'] === '1';
        $has_upload = isset($_FILES[$name]) && isset($_FILES[$name]['error']) && (int) $_FILES[$name]['error'] !== UPLOAD_ERR_NO_FILE;
        $value = $existing;
        $new_file = null;
        $old_file = null;
        $error = null;
        if ($has_upload) {
            $upload = $this->upload_image($name);
            if (!$upload['ok']) {
                $error = $upload['error'];
            } else {
                $value = $new_file = $upload['file'];
                if ($existing && $existing !== $new_file) {
                    $old_file = $existing;
                }
            }
        } elseif ($remove) {
            $value = null;
            $old_file = $existing;
        }
        if ($locale === 'en' && $field['required'] && $value === null) {
            $error = 'Upload an image before removing the current image.';
        }
        return array('value' => $value, 'error' => $error, 'new_file' => $new_file, 'old_file' => $error ? null : $old_file);
    }

    private function upload_image($field_name)
    {
        $path = FCPATH.str_replace('/', DIRECTORY_SEPARATOR, $this->image_path());
        if (!is_dir($path) && !@mkdir($path, 0755, true)) {
            return array('ok' => false, 'error' => 'The image directory is not writable.');
        }
        $this->CI->load->library('upload');
        $config = array(
            'upload_path' => $path,
            'allowed_types' => $this->settings['content_section_image_types'],
            'max_size' => (int) $this->settings['content_section_image_max_kb'],
            'encrypt_name' => true,
            'remove_spaces' => true,
            'detect_mime' => true,
            'mod_mime_fix' => true,
        );
        $this->CI->upload->initialize($config, true);
        if (!$this->CI->upload->do_upload($field_name)) {
            return array('ok' => false, 'error' => strip_tags($this->CI->upload->display_errors('', '')));
        }
        $data = $this->CI->upload->data();
        $file = basename($data['file_name']);
        $absolute = $path.$file;
        if (!is_file($absolute) || @getimagesize($absolute) === false) {
            if (is_file($absolute)) {
                @unlink($absolute);
            }
            return array('ok' => false, 'error' => 'The uploaded file is not a valid image.');
        }
        return array('ok' => true, 'file' => $file);
    }

    private function frontend_map(array $sections, array $definitions, $locale, $type)
    {
        if (empty($sections)) {
            return array();
        }
        $ids = array_column($sections, 'section_id');
        $locales = $locale === 'ar' ? array('en', 'ar') : array('en');
        $rows = $type === 'web'
            ? $this->CI->web_page_sections->get_fields($ids, $locales)
            : $this->CI->miscellaneous_contents->get_fields($ids, $locales);
        $map = $this->field_map($rows);
        $result = array();
        foreach ($sections as $section) {
            $definition = $definitions[$section['section_key']];
            $fields = array();
            foreach ($definition['fields'] as $field) {
                $value = isset($map[$section['section_id']][$locale][$field['key']]) ? $map[$section['section_id']][$locale][$field['key']] : null;
                if ($locale === 'ar' && ($field['type'] === 'image' || $field['type'] === 'icon-picker' || $this->is_url_field($field)) && $this->plain_text($value, false) === null) {
                    $value = isset($map[$section['section_id']]['en'][$field['key']]) ? $map[$section['section_id']]['en'][$field['key']] : null;
                }
                if ($field['type'] === 'image' && $value !== null) {
                    $value = $this->image_url($value);
                }
                $fields[$field['key']] = $value;
            }
            $result[$section['section_key']] = $fields;
        }
        return $result;
    }

    private function field_map(array $rows)
    {
        $map = array();
        foreach ($rows as $row) {
            $map[$row['section_id']][$row['locale']][$row['field_key']] = $row['field_value'];
        }
        return $map;
    }

    private function missing_field_items(
        $scope,
        $group_label,
        array $definitions,
        array $sections,
        array $rows
    ) {
        $map = $this->field_map($rows);
        $sections = $this->rows_by_key($sections);
        $items = array();

        foreach ($definitions as $definition) {
            if (!isset($sections[$definition['key']])) {
                continue;
            }

            $section = $sections[$definition['key']];
            $section_id = (int) $section['section_id'];

            foreach (array_keys($this->locales()) as $locale) {
                foreach ($definition['fields'] as $field) {
                    if (isset($map[$section_id][$locale])
                        && array_key_exists($field['key'], $map[$section_id][$locale]))
                    {
                        continue;
                    }

                    $items[] = array(
                        'scope' => $scope,
                        'group_label' => $group_label,
                        'page_id' => $scope === 'web' ? (int) $section['page_id'] : 0,
                        'section_id' => $section_id,
                        'section_label' => $definition['label'],
                        'locale' => $locale,
                        'field_key' => $field['key'],
                        'field_label' => $field['label'],
                        'field_group' => $field['group'],
                        'field_value' => $this->missing_field_value($field, $locale, $map, $section_id),
                    );
                }
            }
        }

        return $items;
    }

    /**
     * A missing field starts empty (NULL), which renders exactly like an
     * absent row. The exception is an Arabic select such as an icon
     * position: selects are never auto-translated and have no read-time
     * fallback, so it starts from the English choice when one is saved.
     */
    private function missing_field_value(array $field, $locale, array $map, $section_id)
    {
        if ($locale === $this->default_locale() || $field['type'] !== 'select') {
            return null;
        }

        $english = isset($map[$section_id][$this->default_locale()][$field['key']])
            ? (string) $map[$section_id][$this->default_locale()][$field['key']]
            : '';

        return array_key_exists($english, $field['options']) ? $english : null;
    }

    private function default_locale()
    {
        return isset($this->settings['default_content_locale'])
            ? (string) $this->settings['default_content_locale']
            : 'en';
    }

    private function rows_by_key(array $rows)
    {
        $result = array();
        foreach ($rows as $row) {
            $result[$row['section_key']] = $row;
        }
        return $result;
    }

    private function sections_by_key(array $sections)
    {
        $result = array();
        foreach ($sections as $section) {
            $result[$section['key']] = $section;
        }

        return $result;
    }

    private function is_translatable_field(array $field)
    {
        return in_array($field['type'], array('text', 'textarea', 'text-editor'), true)
            && !$this->is_url_field($field);
    }

    private function translate_web_batch(array $batch, array &$values)
    {
        if (empty($batch)) {
            return array('success' => true);
        }

        $contents = array();
        foreach ($batch as $item) {
            $contents[] = $item['value'];
        }

        $result = $this->CI->google_translation_service->translate_batch(
            $contents,
            'en',
            'ar',
            isset($batch[0]['mime_type']) ? $batch[0]['mime_type'] : 'text/plain'
        );

        if (empty($result['success'])) {
            return $result;
        }

        foreach ($batch as $index => $item) {
            $values[$item['control']] = $this->truncate_content_value(
                $result['translated_texts'][$index],
                $item['max_length']
            );
        }

        return array('success' => true);
    }

    private function truncate_content_value($value, $max_length)
    {
        $value = (string) $value;
        if ($max_length === null || (int) $max_length < 1) {
            return $value;
        }

        if (!function_exists('mb_strlen')) {
            return strlen($value) > (int) $max_length
                ? substr($value, 0, (int) $max_length)
                : $value;
        }

        return mb_strlen($value, 'UTF-8') > (int) $max_length
            ? mb_substr($value, 0, (int) $max_length, 'UTF-8')
            : $value;
    }

    private function arabic_status(array $definition, array $values)
    {
        foreach ($definition['fields'] as $field) {
            if (!$this->is_translatable_field($field)) {
                continue;
            }
            if (isset($values[$field['key']])
                && $this->plain_text(
                    $values[$field['key']],
                    $this->is_multiline_field($field)
                ) !== null)
            {
                return 'AVAILABLE';
            }
        }
        return 'MISSING';
    }

    private function plain_text($value, $multiline)
    {
        if (is_array($value) || is_object($value)) return null;
        $value = html_entity_decode(strip_tags((string) $value), ENT_QUOTES | ENT_HTML5, 'UTF-8');
        $value = str_replace(array("\r\n", "\r"), "\n", $value);
        if (!$multiline) $value = preg_replace('/[\r\n\t]+/u', ' ', $value);
        $value = preg_replace('/^[\p{Z}\s]+|[\p{Z}\s]+$/u', '', $value);
        return $value === '' ? null : $value;
    }

    private function rich_text($value)
    {
        if (is_array($value) || is_object($value)) {
            return null;
        }

        $value = str_replace(array("\r\n", "\r"), "\n", (string) $value);

        return $this->plain_text($value, true) === null ? null : $value;
    }

    private function translation_value($value, array $field)
    {
        return $this->is_rich_text_field($field)
            ? $this->rich_text($value)
            : $this->plain_text($value, $this->is_multiline_field($field));
    }

    private function is_multiline_field(array $field)
    {
        return in_array($field['type'], array('textarea', 'text-editor'), true);
    }

    private function is_rich_text_field(array $field)
    {
        return $field['type'] === 'text-editor';
    }

    private function normalize_image($value)
    {
        $value = trim((string) $value);
        if ($value === '') return null;
        $value = basename(str_replace('\\', '/', $value));
        return strlen($value) <= 255 && preg_match('/^[a-zA-Z0-9._-]+$/', $value) ? $value : null;
    }

    private function image_path()
    {
        return trim($this->settings['content_section_image_path'], '/').'/';
    }

    private function cleanup_files(array $files)
    {
        $base = realpath(FCPATH.str_replace('/', DIRECTORY_SEPARATOR, $this->image_path()));
        if ($base === false) {
            return;
        }
        foreach (array_unique(array_filter($files)) as $file) {
            $file = $this->normalize_image($file);
            if ($file === null) {
                continue;
            }
            if ($this->is_file_referenced($file)) {
                continue;
            }
            $target = $base.DIRECTORY_SEPARATOR.$file;
            if (is_file($target) && dirname(realpath($target)) === $base) {
                delete_uploaded_file($base, $file);
            }
        }
    }

    private function is_file_referenced($file)
    {
        return $this->CI->db->where('field_value', $file)->count_all_results('web_page_section_fields') > 0
            || $this->CI->db->where('field_value', $file)->count_all_results('miscellaneous_content_section_fields') > 0;
    }

    private function text_length($value)
    {
        return function_exists('mb_strlen') ? mb_strlen($value, 'UTF-8') : strlen($value);
    }

    private function is_url_field(array $field)
    {
        return (bool) preg_match('/(^|_)url$/', $field['key']);
    }

    private function invalidate_web_cache($page_id)
    {
        unset($this->read_cache['web:'.(int) $page_id.':en'], $this->read_cache['web:'.(int) $page_id.':ar']);
        $page = $this->CI->web_page_sections->find_page($page_id);
        if ((int) $page_id === 1) {
            $this->CI->output->delete_cache('/');
        }
        if ($page && !empty($page['page_slug'])) {
            $this->CI->output->delete_cache($page['page_slug'].'.html');
        }
        if ($page && !empty($page['page_slug_ar'])) {
            $this->CI->output->delete_cache($page['page_slug_ar'].'.html');
        }
    }

    private function invalidate_miscellaneous_cache()
    {
        unset($this->read_cache['misc:en'], $this->read_cache['misc:ar']);
        $this->CI->output->delete_cache('/');
        $pages = $this->CI->db->select('page_slug,page_slug_ar')->where('page_status', 'Published')->get('pages')->result_array();
        foreach ($pages as $page) {
            if (!empty($page['page_slug'])) {
                $this->CI->output->delete_cache($page['page_slug'].'.html');
            }
            if (!empty($page['page_slug_ar'])) {
                $this->CI->output->delete_cache($page['page_slug_ar'].'.html');
            }
        }
    }

    private function audit($module, $action, array $admin, array $context)
    {
        $entry = array_merge(array(
            'admin_id' => isset($admin['id']) ? (int) $admin['id'] : null,
            'module' => $module,
            'action' => $action,
            'timestamp' => date('c'),
        ), $context);
        $line = 'content_sections_audit '.json_encode($entry);
        log_message('info', $line);
        $path = isset($this->settings['content_section_audit_path']) ? $this->settings['content_section_audit_path'] : APPPATH.'logs/content_sections_audit.log';
        if (is_dir(dirname($path)) && is_writable(dirname($path))) {
            @file_put_contents($path, date('Y-m-d H:i:s').' '.$line.PHP_EOL, FILE_APPEND | LOCK_EX);
        }
    }
}
