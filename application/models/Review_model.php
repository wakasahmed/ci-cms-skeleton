<?php defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Read-only customer review queries backing the public frontend.
 *
 * Selects only the columns the frontend actually renders.
 */
class Review_model extends SqlModel
{
    /** Active customer reviews, ordered by `review_order`. */
    public function get_reviews($limit = 0)
    {
        $this->db->select(implode(',', array(
            'review_caption',
            'review_name',
            'review_desc',
            'review_rating',
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
