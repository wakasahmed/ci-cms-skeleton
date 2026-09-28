<?php defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Read-only service queries backing the public frontend.
 *
 * A service is public when it and its category are both enabled.
 */
class Service_model extends SqlModel
{
    /**
     * Featured services for the footer's first column, in menu order
     * (category order, then service order).
     */
    public function get_footer_services($limit = 6)
    {
        $this->db->select('s.service_name, s.service_slug');
        $this->db->from('services s');
        $this->db->join('service_categories c', 'c.category_id = s.service_category_id');
        $this->db->where('s.service_status', 'Enable');
        $this->db->where('c.category_status', 'Enable');
        $this->db->where('s.service_featured', 1);
        $this->db->order_by('c.category_order', 'ASC');
        $this->db->order_by('s.service_order', 'ASC');
        $this->db->order_by('s.service_id', 'ASC');
        $this->db->limit((int) $limit);

        return $this->db->get()->result_array();
    }
}
