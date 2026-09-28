<?php defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Read-only offer queries backing the public frontend.
 *
 * An offer is public when it is enabled and today falls inside its validity
 * dates (either date may be empty). Lists follow the offer order in
 * Manage > Offers.
 */
class Offer_model extends SqlModel
{
    /** Public offers, featured ones first when $featuredFirst is TRUE. */
    public function get_public($featuredFirst = TRUE, $limit = 0)
    {
        $today = date('Y-m-d');

        $this->db->from('offers');
        $this->db->where('offer_status', 'Enable');
        $this->db->group_start();
        $this->db->where('offer_valid_from IS NULL', NULL, FALSE);
        $this->db->or_where('offer_valid_from <=', $today);
        $this->db->group_end();
        $this->db->group_start();
        $this->db->where('offer_valid_to IS NULL', NULL, FALSE);
        $this->db->or_where('offer_valid_to >=', $today);
        $this->db->group_end();

        if ($featuredFirst) {
            $this->db->order_by('offer_featured', 'DESC');
        }
        $this->db->order_by('offer_order', 'ASC');
        $this->db->order_by('offer_id', 'ASC');

        if ($limit > 0) {
            $this->db->limit((int) $limit);
        }

        return $this->db->get()->result_array();
    }

    /** offer_id => slugs of the public services each offer includes, in menu order. */
    public function get_service_slugs(array $offerIds)
    {
        $offerIds = array_values(array_filter(array_map('intval', $offerIds)));
        if (empty($offerIds)) {
            return array();
        }

        $rows = $this->db
            ->select('x.offer_id, s.service_slug')
            ->from('offer_services x')
            ->join('services s', 's.service_id = x.service_id')
            ->join('service_categories c', 'c.category_id = s.service_category_id')
            ->where('s.service_status', 'Enable')
            ->where('c.category_status', 'Enable')
            ->where_in('x.offer_id', $offerIds)
            ->order_by('c.category_order', 'ASC')
            ->order_by('s.service_order', 'ASC')
            ->get()
            ->result_array();

        $slugs = array();
        foreach ($rows as $row) {
            $slugs[(int) $row['offer_id']][] = $row['service_slug'];
        }

        return $slugs;
    }
}
