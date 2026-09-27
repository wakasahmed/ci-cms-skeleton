<?php defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Read-only reference data and the tokenized draft/completion write path for
 * the public Plan Your Trip wizard.
 *
 * Country lookups are NOT duplicated here — the controller reuses
 * Contact_model::get_countries()/country_exists() directly, since that
 * implementation is already generic and correct.
 *
 * Every write method is scoped to the caller's own draft by binding on the
 * 64-character `session_token` the controller resolves from the CI session
 * (see Frontend::planDraftToken()) — never by numeric id, and never by a
 * token read back from the request.
 */
class Plan_your_visit_model extends Localized_model
{
    /** Enabled tour languages, ordered for display, with both the localized label and the stable English name used for storage/restore matching. */
    public function get_languages($locale = 'en')
    {
        $locale = $this->normalizeLocale($locale);

        $this->db->select(implode(',', array(
            'lang_id',
            'lang_name AS name_en',
            $this->localizedColumn('lang_name', $locale, 'label'),
        )), false);
        $this->db->from('tour_languages');
        $this->db->where('lang_status', 'Enable');
        $this->db->order_by('lang_order', 'ASC');
        $this->db->order_by('lang_id', 'ASC');

        return $this->db->get()->result_array();
    }

    /** One enabled language by id, or null. */
    public function get_language($id)
    {
        $id = (int) $id;
        if ($id <= 0) {
            return null;
        }

        $this->db->select('lang_id, lang_name AS name_en');
        $this->db->from('tour_languages');
        $this->db->where('lang_id', $id);
        $this->db->where('lang_status', 'Enable');
        $row = $this->db->get()->row_array();

        return $row !== null ? $row : null;
    }

    /** Enabled visiting-time slots, ordered for display, with the raw start/end times for formatting. */
    public function get_time_options($locale = 'en')
    {
        $locale = $this->normalizeLocale($locale);

        $this->db->select(implode(',', array(
            'slot_id',
            'slot_name AS name_en',
            'slot_start_time',
            'slot_end_time',
            $this->localizedColumn('slot_name', $locale, 'label'),
        )), false);
        $this->db->from('tour_slots');
        $this->db->where('slot_status', 'Enable');
        $this->db->order_by('slot_order', 'ASC');
        $this->db->order_by('slot_id', 'ASC');

        return $this->db->get()->result_array();
    }

    /** One enabled time slot by id, or null. */
    public function get_time_option($id)
    {
        $id = (int) $id;
        if ($id <= 0) {
            return null;
        }

        $this->db->select('slot_id, slot_name AS name_en, slot_start_time, slot_end_time');
        $this->db->from('tour_slots');
        $this->db->where('slot_id', $id);
        $this->db->where('slot_status', 'Enable');
        $row = $this->db->get()->row_array();

        return $row !== null ? $row : null;
    }

    /** The singleton `form_settings` record (id 1), or null. */
    public function get_form_settings()
    {
        $this->db->select('id, plan_success, plan_success_ar, pyt_interests, pyt_interests_ar, pyt_visit_time, pyt_visit_time_ar');
        $this->db->from('form_settings');
        $this->db->where('id', 1);
        $row = $this->db->get()->row_array();

        return $row !== null ? $row : null;
    }

    /** The draft/completed row owned by this token, or null. */
    public function find_by_session_token($token)
    {
        $token = trim((string) $token);
        if ($token === '') {
            return null;
        }

        $row = $this->db->where('session_token', $token)->get('plan_your_visit')->row_array();

        return $row !== null ? $row : null;
    }

    /** Whether a (non-empty) token is already in use. */
    public function session_token_exists($token)
    {
        $token = trim((string) $token);
        if ($token === '') {
            return false;
        }

        return $this->db->where('session_token', $token)->count_all_results('plan_your_visit') > 0;
    }

    /**
     * Insert the step-1 draft. $data must already contain every column this
     * writes — including `session_token`, `step_completed` (1), `website`,
     * `ip`, `user_agent`, `created_at` and `updated_at` — all server-owned.
     * Returns the new row's id, or false on failure (the caller retries with
     * a fresh token on the — practically impossible — chance of a
     * token collision).
     */
    public function create_draft(array $data)
    {
        if (!$this->db->insert('plan_your_visit', $data)) {
            return false;
        }

        return (int) $this->db->insert_id();
    }

    /**
     * Update only the columns for one step on the draft owned by $token.
     * Refuses (returns false) when the token has no row, or the row is
     * already fully completed — a completed row is never mutated by a
     * draft-step update. `step_completed` only ever moves forward: it is set
     * to max(existing, $step) here, never trusted from the caller directly
     * beyond the validated $step number.
     */
    public function update_draft($token, $step, array $data)
    {
        $token = trim((string) $token);
        $step = (int) $step;
        if ($token === '' || $step < 1) {
            return false;
        }

        $current = $this->find_by_session_token($token);
        if ($current === null || (int) $current['step_completed'] >= 4) {
            return false;
        }

        $data['step_completed'] = max((int) $current['step_completed'], $step);
        $data['updated_at'] = date('Y-m-d H:i:s');

        return (bool) $this->db
            ->where('session_token', $token)
            ->where('step_completed <', 4)
            ->update('plan_your_visit', $data);
    }

    /**
     * The final, transactional, all-fields completion. $data carries every
     * form-owned column (steps 1-4); `step_completed` is forced to 4 and
     * `updated_at` refreshed here — `created_at`, the token and the original
     * request metadata are left untouched. Refuses an already-completed row
     * so a replayed final submission cannot re-run it.
     */
    public function complete_request($token, array $data)
    {
        $token = trim((string) $token);
        if ($token === '') {
            return false;
        }

        $current = $this->find_by_session_token($token);
        if ($current === null || (int) $current['step_completed'] >= 4) {
            return false;
        }

        $data['step_completed'] = 4;
        $data['updated_at'] = date('Y-m-d H:i:s');

        $this->db->trans_begin();
        $this->db
            ->where('session_token', $token)
            ->where('step_completed <', 4)
            ->update('plan_your_visit', $data);

        if ($this->db->trans_status() === false || $this->db->affected_rows() !== 1) {
            $this->db->trans_rollback();

            return false;
        }

        $this->db->trans_commit();

        return true;
    }
}
