<?php defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Read-only artist queries backing the public frontend. Only enabled
 * artists are public; lists follow the artist order in Manage > Artists.
 */
class Artist_model extends SqlModel
{
    /** Enabled artists with the number of public services each takes. */
    public function get_all()
    {
        return $this->db
            ->select('a.*, COUNT(s.service_id) AS service_count')
            ->from('artists a')
            ->join('artist_services x', 'x.artist_id = a.artist_id', 'left')
            ->join('services s', "s.service_id = x.service_id AND s.service_status = 'Enable'", 'left', FALSE)
            ->where('a.artist_status', 'Enable')
            ->group_by('a.artist_id')
            ->order_by('a.artist_order', 'ASC')
            ->order_by('a.artist_id', 'ASC')
            ->get()
            ->result_array();
    }

    /** One enabled artist by slug, or NULL. */
    public function get_by_slug($slug)
    {
        $row = $this->db
            ->where('artist_slug', (string) $slug)
            ->where('artist_status', 'Enable')
            ->limit(1)
            ->get('artists')
            ->row_array();

        return $row !== NULL ? $row : NULL;
    }

    /** The public services an artist takes, in menu order. */
    public function get_services($artistId)
    {
        return $this->db
            ->select('s.service_id, s.service_name, s.service_slug, s.service_summary, s.service_price_from,'
                .' s.service_price_suffix, s.service_duration_label, s.service_card_image, s.service_hero_image')
            ->from('services s')
            ->join('service_categories c', 'c.category_id = s.service_category_id')
            ->join('artist_services x', 'x.service_id = s.service_id')
            ->where('x.artist_id', (int) $artistId)
            ->where('s.service_status', 'Enable')
            ->where('c.category_status', 'Enable')
            ->order_by('c.category_order', 'ASC')
            ->order_by('s.service_order', 'ASC')
            ->get()
            ->result_array();
    }
}
