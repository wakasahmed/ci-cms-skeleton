<?php defined('BASEPATH') OR exit('No direct script access allowed');

class Web_page_section_model extends CI_Model
{
    public function find_page($page_id)
    {
        return $this->db->get_where('pages', array('page_id' => (int) $page_id), 1)->row_array();
    }

    public function ensure_sections($page_id, array $definitions)
    {
        $page_id = (int) $page_id;
        $existing = $this->db->where('page_id', $page_id)->get('web_page_sections')->result_array();
        $by_key = array();
        $max_order = 0;
        foreach ($existing as $row) {
            $by_key[$row['section_key']] = $row;
            $max_order = max($max_order, (int) $row['sort_order']);
        }

        $now = date('Y-m-d H:i:s');
        $this->db->trans_begin();
        foreach ($definitions as $definition) {
            $key = $definition['key'];
            if (isset($by_key[$key])) {
                if ($by_key[$key]['section_label'] !== $definition['label']) {
                    $this->db->where('id', $by_key[$key]['id'])->update('web_page_sections', array(
                        'section_label' => $definition['label'], 'updated_at' => $now,
                    ));
                }
                continue;
            }
            $max_order = ((int) floor($max_order / 10) * 10) + 10;
            $this->db->insert('web_page_sections', array(
                'page_id' => $page_id, 'section_key' => $key, 'section_label' => $definition['label'],
                'status' => 'Enable', 'sort_order' => $max_order,
                'created_at' => $now, 'updated_at' => $now,
            ));
        }
        if ($this->db->trans_status() === false) {
            $this->db->trans_rollback();
            return false;
        }
        $this->db->trans_commit();
        return $this->get_sections($page_id);
    }

    public function get_sections($page_id, $active_only = false)
    {
        $this->db->where('page_id', (int) $page_id);
        if ($active_only) {
            $this->db->where('status', 'Enable');
        }
        $rows = $this->db->order_by('sort_order', 'ASC')->order_by('id', 'ASC')->get('web_page_sections')->result_array();
        foreach ($rows as &$row) {
            $row['section_id'] = $row['id'];
            $row['section_status'] = $row['status'];
        }
        unset($row);
        return $rows;
    }

    public function get_fields(array $section_ids, array $locales)
    {
        if (empty($section_ids) || empty($locales)) {
            return array();
        }
        return $this->db->where_in('section_id', array_map('intval', $section_ids))
            ->where_in('locale', $locales)->get('web_page_section_fields')->result_array();
    }

    /**
     * Insert field rows that do not exist yet. An existing row is never
     * overwritten, even when its value is NULL or empty.
     */
    public function insert_missing_fields(array $rows)
    {
        $now = date('Y-m-d H:i:s');
        $inserted = 0;

        $this->db->trans_begin();
        foreach ($rows as $row) {
            $this->db->query(
                'INSERT INTO web_page_section_fields '
                .'(section_id, locale, field_key, field_value, created_at, updated_at) '
                .'VALUES (?, ?, ?, ?, ?, ?) ON DUPLICATE KEY UPDATE id = id',
                array(
                    (int) $row['section_id'],
                    $row['locale'],
                    $row['field_key'],
                    $row['field_value'],
                    $now,
                    $now,
                )
            );
            $inserted += $this->db->affected_rows() === 1 ? 1 : 0;
        }

        if ($this->db->trans_status() === false) {
            $this->db->trans_rollback();
            return false;
        }
        $this->db->trans_commit();

        return $inserted;
    }

    public function save(
        $page_id,
        array $sections_by_key,
        $locale,
        array $values,
        array $statuses,
        array $order
    )
    {
        $now = date('Y-m-d H:i:s');
        $this->db->trans_begin();
        foreach ($order as $position => $key) {
            $this->db->where('id', $sections_by_key[$key]['section_id'])->update('web_page_sections', array(
                'sort_order' => ($position + 1) * 10,
                'status' => $statuses[$key],
                'updated_at' => $now,
            ));
        }
        foreach ($values as $section_key => $fields) {
            $section_id = (int) $sections_by_key[$section_key]['section_id'];
            foreach ($fields as $field_key => $value) {
                $this->db->query(
                    'INSERT INTO web_page_section_fields (section_id, locale, field_key, field_value, created_at, updated_at) '
                    .'VALUES (?, ?, ?, ?, ?, ?) ON DUPLICATE KEY UPDATE field_value=VALUES(field_value), updated_at=VALUES(updated_at)',
                    array($section_id, $locale, $field_key, $value, $now, $now)
                );
            }
        }
        $this->db
            ->where('page_id', (int) $page_id)
            ->update('pages', array('updated_at' => $now));

        if ($this->db->trans_status() === false) {
            $this->db->trans_rollback();
            return false;
        }
        $this->db->trans_commit();
        return true;
    }
}
