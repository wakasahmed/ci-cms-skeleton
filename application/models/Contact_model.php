<?php defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * The public Contact form's settings read and write path.
 */
class Contact_model extends SqlModel
{
    /** The Contact form settings from singleton record 1. */
    public function get_form_settings()
    {
        $this->db->select(implode(',', array(
            'id',
            'contact_subject AS subject_values',
            'contact_subject AS subject_labels',
            'contact_success AS success_message',
        )), false);
        $this->db->from('form_settings');
        $this->db->where('id', 1);
        $row = $this->db->get()->row_array();

        return $row !== null ? $row : null;
    }

    /**
     * Insert one public contact request. $data is a server-built payload —
     * every column, including `ip`, `user_agent` and both
     * timestamps, is expected to already be validated and normalized by the
     * caller. Returns the new row's id, or false on failure.
     */
    public function create_request(array $data)
    {
        if (!$this->db->insert('contact_requests', $data)) {
            return false;
        }

        return (int) $this->db->insert_id();
    }
}
