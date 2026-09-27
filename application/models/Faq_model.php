<?php defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Read-only FAQ queries backing the public frontend.
 *
 * Selects only the columns the frontend actually renders and resolves
 * bilingual columns to the requested locale in the query itself.
 */
class Faq_model extends Localized_model
{
    /** The FAQ category (`faqs_categories`) dedicated to the Home page. */
    const HOME_CATEGORY_ID = 1;

    /**
     * Enabled FAQs in the Home page's FAQ category, in the administrator's
     * `faq_order`.
     */
    public function get_home_faqs($locale = 'en', $catId = self::HOME_CATEGORY_ID)
    {
        $locale = $this->normalizeLocale($locale);

        $this->db->select(implode(',', array(
            $this->localizedColumn('faq_question', $locale),
            $this->localizedColumn('faq_answer', $locale),
        )), false);
        $this->db->from('faqs');
        $this->db->where('faq_cat_id', (int) $catId);
        $this->db->where('faq_status', 'Enable');
        $this->db->order_by('faq_order', 'ASC');

        return $this->db->get()->result_array();
    }

    /**
     * Every public FAQ category with its enabled FAQs, in administrator
     * order — the complete, already-grouped data set the public FAQs page
     * renders. `cat_hidden` (not just `cat_id`) is the visibility authority,
     * so the Home page's protected category is excluded the same way any
     * other hidden category would be, without a special case for its id.
     * Empty categories (enabled, visible, but with no enabled FAQ left) are
     * dropped, so the caller never has to check for that itself.
     *
     * One category query plus one batched FAQ query — never one FAQ query
     * per category.
     */
    public function get_faq_groups($locale = 'en')
    {
        $locale = $this->normalizeLocale($locale);

        $this->db->select(implode(',', array(
            'cat_id',
            $this->localizedColumn('cat_name', $locale),
            $this->localizedColumn('cat_short_description', $locale),
        )), false);
        $this->db->from('faqs_categories');
        $this->db->where('cat_status', 'Enable');
        $this->db->where('cat_hidden', 'No');
        $this->db->order_by('cat_order', 'ASC');
        $this->db->order_by('cat_id', 'ASC');
        $categories = $this->db->get()->result_array();
        if (empty($categories)) {
            return array();
        }

        $catIds = array_map('intval', array_column($categories, 'cat_id'));

        $this->db->select(implode(',', array(
            'faq_id',
            'faq_cat_id',
            $this->localizedColumn('faq_question', $locale),
            $this->localizedColumn('faq_answer', $locale),
        )), false);
        $this->db->from('faqs');
        $this->db->where('faq_status', 'Enable');
        $this->db->where_in('faq_cat_id', $catIds);
        $this->db->order_by('faq_cat_id', 'ASC');
        $this->db->order_by('faq_order', 'ASC');
        $this->db->order_by('faq_id', 'ASC');

        $byCategory = array();
        foreach ($this->db->get()->result_array() as $row) {
            $byCategory[(int) $row['faq_cat_id']][] = array(
                'id' => (int) $row['faq_id'],
                'question' => $row['faq_question'],
                'answer' => $row['faq_answer'],
            );
        }

        $groups = array();
        foreach ($categories as $category) {
            $catId = (int) $category['cat_id'];
            if (empty($byCategory[$catId])) {
                continue;
            }

            $groups[] = array(
                'id' => $catId,
                'title' => $category['cat_name'],
                'description' => $category['cat_short_description'],
                'items' => $byCategory[$catId],
            );
        }

        return $groups;
    }
}
