<?php defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Read-only service queries backing the public frontend.
 *
 * A service is public when it and its category are both enabled. Lists are
 * in menu order: category order, then service order.
 */
class Service_model extends SqlModel
{
    /** Columns a service card or list row needs. */
    private $cardColumns = 's.service_id, s.service_name, s.service_slug, s.service_summary,'
        .' s.service_price_from, s.service_price_suffix, s.service_duration_label,'
        .' s.service_card_image, s.service_hero_image';

    /**
     * Featured services for the footer's first column.
     */
    public function get_footer_services($limit = 6)
    {
        $this->publicServices('s.service_name, s.service_slug');
        $this->db->where('s.service_featured', 1);
        $this->db->limit((int) $limit);

        return $this->db->get()->result_array();
    }

    /** Featured services as cards (services listing, home page). */
    public function get_featured($limit = 8)
    {
        $this->publicServices($this->cardColumns);
        $this->db->where('s.service_featured', 1);
        $this->db->limit((int) $limit);

        return $this->db->get()->result_array();
    }

    /**
     * Enabled categories that have public services, each with its services:
     * array of category rows with a 'services' list.
     */
    public function get_menu()
    {
        $this->publicServices(
            $this->cardColumns.', c.category_id, c.category_name, c.category_slug, c.category_description'
        );

        $categories = array();
        foreach ($this->db->get()->result_array() as $row) {
            $id = (int) $row['category_id'];
            if (!isset($categories[$id])) {
                $categories[$id] = array(
                    'category_name' => $row['category_name'],
                    'category_slug' => $row['category_slug'],
                    'category_description' => $row['category_description'],
                    'services' => array(),
                );
            }
            $categories[$id]['services'][] = $row;
        }

        return array_values($categories);
    }

    /** One public service by slug, with its category, or NULL. */
    public function get_by_slug($slug)
    {
        $this->publicServices('s.*, c.category_name, c.category_slug');
        $this->db->where('s.service_slug', (string) $slug);
        $this->db->limit(1);
        $row = $this->db->get()->row_array();

        return $row !== NULL ? $row : NULL;
    }

    public function get_addons($serviceId)
    {
        return $this->db
            ->select('addon_label, addon_price')
            ->where('addon_service_id', (int) $serviceId)
            ->order_by('addon_order', 'ASC')
            ->get('service_addons')
            ->result_array();
    }

    /** The public "Often booked with this" services. */
    public function get_related($serviceId)
    {
        $this->publicServices($this->cardColumns);
        $this->db->join('service_related r', 'r.related_service_id = s.service_id');
        $this->db->where('r.service_id', (int) $serviceId);

        return $this->db->get()->result_array();
    }

    /** Enabled gallery images linked to the service, gallery order. */
    public function get_gallery($serviceId, $limit = 4)
    {
        return $this->db
            ->select('i.image_file, i.image_caption')
            ->from('gallery_images i')
            ->join('gallery_categories c', 'c.category_id = i.image_category_id', 'left')
            ->where('i.image_service_id', (int) $serviceId)
            ->where('i.image_status', 'Enable')
            ->group_start()
                ->where('c.category_status', 'Enable')
                ->or_where('i.image_category_id IS NULL', NULL, FALSE)
            ->group_end()
            ->order_by('i.image_order', 'ASC')
            ->order_by('i.image_id', 'ASC')
            ->limit((int) $limit)
            ->get()
            ->result_array();
    }

    /** Enabled artists who take the service, artist order. */
    public function get_artists($serviceId)
    {
        return $this->db
            ->select('a.artist_name, a.artist_slug, a.artist_role, a.artist_bio, a.artist_specialties, a.artist_image')
            ->from('artists a')
            ->join('artist_services x', 'x.artist_id = a.artist_id')
            ->where('x.service_id', (int) $serviceId)
            ->where('a.artist_status', 'Enable')
            ->order_by('a.artist_order', 'ASC')
            ->order_by('a.artist_id', 'ASC')
            ->get()
            ->result_array();
    }

    /** Starts a query over public services in menu order. */
    private function publicServices($columns)
    {
        $this->db->select($columns);
        $this->db->from('services s');
        $this->db->join('service_categories c', 'c.category_id = s.service_category_id');
        $this->db->where('s.service_status', 'Enable');
        $this->db->where('c.category_status', 'Enable');
        $this->db->order_by('c.category_order', 'ASC');
        $this->db->order_by('s.service_order', 'ASC');
        $this->db->order_by('s.service_id', 'ASC');
    }
}
