<?php defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Read-only customer review queries backing the public frontend.
 *
 * Selects only the columns the frontend actually renders and resolves
 * bilingual columns to the requested locale in the query itself.
 */
class Review_model extends Localized_model
{
    /**
     * Active customer reviews, ordered by `review_order`. `review_caption`
     * has no Arabic column, so it is selected as-is in both locales.
     */
    public function get_reviews($limit = 0, $locale = 'en')
    {
        $locale = $this->normalizeLocale($locale);

        $this->db->select(implode(',', array(
            'review_caption',
            $this->localizedColumn('review_name', $locale),
            $this->localizedColumn('review_desc', $locale),
        )), false);
        $this->db->from('customer_reviews');
        $this->db->where('review_status', 'Enable');
        $this->db->order_by('review_order', 'ASC');
        if ($limit > 0) {
            $this->db->limit($limit);
        }

        return $this->db->get()->result_array();
    }
}
