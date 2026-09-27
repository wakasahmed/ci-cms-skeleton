<?php defined('BASEPATH') OR exit('No direct script access allowed');

class Translation_job_model extends CI_Model
{
    private $table = 'translation_jobs';

    public function available()
    {
        return $this->db->table_exists($this->table);
    }

    public function create_or_reset(array $job)
    {
        if (!$this->available())
        {
            return FALSE;
        }

        $existing = $this->db->where('dedupe_key', $job['dedupe_key'])->get($this->table)->row_array();
        $now = date('Y-m-d H:i:s');
        if ($existing)
        {
            if (in_array($existing['status'], array('FAILED', 'SKIPPED', 'SUCCEEDED'), TRUE))
            {
                $this->db->where('id', $existing['id'])->update($this->table, array(
                    'field_keys' => $job['field_keys'], 'status' => 'PENDING', 'attempts' => 0,
                    'next_attempt_at' => $now, 'last_error' => NULL, 'completed_at' => NULL,
                    'updated_at' => $now,
                ));
            }
            return (int) $existing['id'];
        }

        $this->db->where('module_key', $job['module_key'])
            ->where('entity_type', $job['entity_type'])
            ->where('entity_id', $job['entity_id'])
            ->where('status', 'PENDING')
            ->where('dedupe_key !=', $job['dedupe_key'])
            ->delete($this->table);

        $job['created_at'] = $now;
        $job['updated_at'] = $now;
        $this->db->insert($this->table, $job);
        if ($this->db->affected_rows() > 0)
        {
            return (int) $this->db->insert_id();
        }
        $existing = $this->db->select('id')->where('dedupe_key', $job['dedupe_key'])->get($this->table)->row_array();
        return $existing ? (int) $existing['id'] : FALSE;
    }

    public function latest_for_entities($module_key, $entity_type, array $entity_ids)
    {
        if (!$this->available() || empty($entity_ids))
        {
            return array();
        }
        $rows = $this->db->where('module_key', $module_key)->where('entity_type', $entity_type)
            ->where_in('entity_id', array_map('strval', $entity_ids))
            ->order_by('created_at', 'DESC')->order_by('id', 'DESC')
            ->get($this->table)->result_array();
        $latest = array();
        foreach ($rows as $row)
        {
            $key = (string) $row['entity_id'];
            $hash = (string) $row['source_hash'];
            if (!isset($latest[$key]))
            {
                $latest[$key] = array();
            }
            if (!isset($latest[$key][$hash]))
            {
                $latest[$key][$hash] = $row;
            }
        }
        return $latest;
    }

    public function delete_entity($module_key, $entity_type, $entity_id)
    {
        if (!$this->available())
        {
            return TRUE;
        }
        return $this->db->where('module_key', $module_key)
            ->where('entity_type', $entity_type)
            ->where('entity_id', (string) $entity_id)
            ->delete($this->table);
    }

    public function release_stale($lease_seconds)
    {
        if (!$this->available())
        {
            return 0;
        }
        $cutoff = date('Y-m-d H:i:s', time() - max(60, (int) $lease_seconds));
        $now = date('Y-m-d H:i:s');
        $this->db->where('status', 'RUNNING')->where('updated_at <', $cutoff)
            ->where('attempts < max_attempts', NULL, FALSE)
            ->update($this->table, array('status' => 'FAILED', 'next_attempt_at' => $now,
                'last_error' => 'The previous worker lease expired.', 'updated_at' => $now));
        $released = $this->db->affected_rows();
        $this->db->where('status', 'RUNNING')->where('updated_at <', $cutoff)
            ->where('attempts >= max_attempts', NULL, FALSE)
            ->update($this->table, array('status' => 'FAILED', 'next_attempt_at' => NULL,
                'last_error' => 'The final worker lease expired.', 'updated_at' => $now));
        return $released + $this->db->affected_rows();
    }

    public function claim_next()
    {
        if (!$this->available())
        {
            return NULL;
        }
        $now = date('Y-m-d H:i:s');
        $this->db->trans_begin();
        $query = $this->db->query(
            "SELECT * FROM `translation_jobs` WHERE ((`status` = 'PENDING') OR (`status` = 'FAILED' AND `attempts` < `max_attempts`)) AND (`next_attempt_at` IS NULL OR `next_attempt_at` <= ?) ORDER BY `created_at` ASC, `id` ASC LIMIT 1 FOR UPDATE",
            array($now)
        );
        $job = $query->row_array();
        if (!$job)
        {
            $this->db->trans_commit();
            return NULL;
        }
        $this->db->where('id', $job['id'])->where_in('status', array('PENDING', 'FAILED'))
            ->update($this->table, array('status' => 'RUNNING', 'attempts' => (int) $job['attempts'] + 1,
                'last_error' => NULL, 'updated_at' => $now));
        if ($this->db->affected_rows() !== 1 || $this->db->trans_status() === FALSE)
        {
            $this->db->trans_rollback();
            return NULL;
        }
        $this->db->trans_commit();
        $job['status'] = 'RUNNING';
        $job['attempts'] = (int) $job['attempts'] + 1;
        return $job;
    }

    public function finish($id, $status, $error = NULL, $next_attempt_at = NULL)
    {
        if (!$this->available())
        {
            return FALSE;
        }
        $allowed = array('SUCCEEDED', 'FAILED', 'SKIPPED');
        if (!in_array($status, $allowed, TRUE))
        {
            return FALSE;
        }
        $now = date('Y-m-d H:i:s');
        return $this->db->where('id', (int) $id)->update($this->table, array(
            'status' => $status,
            'last_error' => $error,
            'next_attempt_at' => $next_attempt_at,
            'completed_at' => in_array($status, array('SUCCEEDED', 'SKIPPED'), TRUE) ? $now : NULL,
            'updated_at' => $now,
        ));
    }
}
