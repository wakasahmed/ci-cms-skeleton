<?php defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Read-only country lookups and the public Contact form's write path.
 *
 * Selects only the columns the frontend actually renders and resolves the
 * bilingual `name`/`name_ar` pair to the requested locale in the query
 * itself, using the same fallback-to-English convention every other
 * bilingual column in the project already uses.
 */
class Contact_model extends Localized_model
{
    /** The locale-resolved Contact form settings from singleton record 1. */
    public function get_form_settings($locale = 'en')
    {
        $locale = $this->normalizeLocale($locale);

        $this->db->select(implode(',', array(
            'id',
            'contact_subject AS subject_values',
            $this->localizedColumn('contact_subject', $locale, 'subject_labels'),
            $this->localizedColumn('contact_success', $locale, 'success_message'),
        )), false);
        $this->db->from('form_settings');
        $this->db->where('id', 1);
        $row = $this->db->get()->row_array();

        return $row !== null ? $row : null;
    }

    /** Every country, with a locale-resolved display label. */
    public function get_countries($locale = 'en')
    {
        $locale = $this->normalizeLocale($locale);

        $this->db->select(implode(',', array(
            'id',
            'iso',
            $this->localizedColumn('name', $locale, 'label'),
        )), false);
        $this->db->from('countries');
        $this->db->order_by('label', 'ASC');

        return $this->db->get()->result_array();
    }

    /** Whether a positive country id exists — the server-side authority for country validation. */
    public function country_exists($id)
    {
        $id = (int) $id;
        if ($id <= 0) {
            return false;
        }

        return $this->db->where('id', $id)->count_all_results('countries') > 0;
    }

    /**
     * Insert one public contact request. $data is a server-built payload —
     * every column, including `website`, `ip`, `user_agent` and both
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
