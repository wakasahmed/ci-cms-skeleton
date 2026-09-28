<?php defined('BASEPATH') OR exit('No direct script access allowed');

class Miscellaneous_content_model extends CI_Model
{
    public function ensure_sections(array $definitions)
    {
        $existing = $this->db->get('miscellaneous_content_sections')->result_array();
        $by_key = array();
        foreach ($existing as $row) {
            $by_key[$row['section_key']] = $row;
        }
        $now = date('Y-m-d H:i:s');
        $this->db->trans_begin();
        foreach ($definitions as $definition) {
            $key = $definition['key'];
            if (isset($by_key[$key])) {
                if ($by_key[$key]['section_label'] !== $definition['label']) {
                    $this->db->where('id', $by_key[$key]['id'])->update('miscellaneous_content_sections', array(
                        'section_label' => $definition['label'], 'updated_at' => $now,
                    ));
                }
                continue;
            }
            $this->db->insert('miscellaneous_content_sections', array(
                'section_key' => $key, 'section_label' => $definition['label'],
                'status' => 'Enable',
                'created_at' => $now, 'updated_at' => $now,
            ));
        }
        if ($this->db->trans_status() === false) {
            $this->db->trans_rollback();
            return false;
        }
        $this->db->trans_commit();
        return $this->get_sections();
    }

    public function get_sections($active_only = false)
    {
        if ($active_only) {
            $this->db->where('status', 'Enable');
        }
        $rows = $this->db->order_by('id', 'ASC')->get('miscellaneous_content_sections')->result_array();
        foreach ($rows as &$row) {
            $row['section_id'] = $row['id'];
            $row['section_status'] = $row['status'];
        }
        unset($row);
        return $rows;
    }

    public function get_section($section_id)
    {
        $row = $this->db->where('id', (int) $section_id)->get('miscellaneous_content_sections')->row_array();
        if (!$row) {
            return false;
        }
        $row['section_id'] = $row['id'];
        $row['section_status'] = $row['status'];
        return $row;
    }

    public function update_status($section_id, $status)
    {
        if (!in_array($status, array('Enable', 'Disable'), true)) {
            return false;
        }
        $updated = $this->db->where('id', (int) $section_id)->update('miscellaneous_content_sections', array(
            'status' => $status,
            'updated_at' => date('Y-m-d H:i:s'),
        ));
        if (!$updated) {
            return false;
        }
        return $this->get_section($section_id);
    }

    public function get_fields(array $section_ids, array $locales)
    {
        if (empty($section_ids) || empty($locales)) {
            return array();
        }
        return $this->db->where_in('section_id', array_map('intval', $section_ids))
            ->where_in('locale', $locales)->get('miscellaneous_content_section_fields')->result_array();
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
                'INSERT INTO miscellaneous_content_section_fields '
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
        array $section,
        $locale,
        array $values,
        $status
    )
    {
        $now = date('Y-m-d H:i:s');
        $section_id = (int) $section['section_id'];
        $this->db->trans_begin();
        $this->db->where('id', $section_id)->update('miscellaneous_content_sections', array(
            'status' => $status, 'updated_at' => $now,
        ));
        foreach ($values as $field_key => $value) {
            $this->db->query(
                'INSERT INTO miscellaneous_content_section_fields (section_id, locale, field_key, field_value, created_at, updated_at) '
                .'VALUES (?, ?, ?, ?, ?, ?) ON DUPLICATE KEY UPDATE field_value=VALUES(field_value), updated_at=VALUES(updated_at)',
                array($section_id, $locale, $field_key, $value, $now, $now)
            );
        }
        if ($this->db->trans_status() === false) {
            $this->db->trans_rollback();
            return false;
        }
        $this->db->trans_commit();
        return true;
    }
}
