<?php defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Read-only lookups behind the XML sitemap and llms.txt.
 *
 * Every method returns only records the public site actually serves: a
 * published/enabled status, and — for the sitemap — "Allow indexing" switched
 * on, so a page the administrator hid from search engines is never advertised.
 */
class Seo_model extends SqlModel
{
    /** Published CMS pages open to indexing. Page 1 is the home page. */
    public function get_pages($indexableOnly = true)
    {
        $this->db->select(
            'page_id, page_slug, page_name, page_updated, og_image, banner_background',
            false
        );
        $this->db->from('pages');
        $this->db->where('page_status', 'Published');
        if ($indexableOnly) {
            $this->db->where('robots_index', 1);
        }
        $this->db->order_by('page_id', 'ASC');

        return $this->db->get()->result_array();
    }

    /** Published blog posts, newest first. */
    public function get_blog_posts($indexableOnly = true)
    {
        $this->db->select(
            'blog_id, blog_slug, blog_name, blog_short_description, blog_added, blog_pdate, '
            . 'blog_updated, blog_image, blog_cover_image, og_image',
            false
        );
        $this->db->from('blogs');
        $this->db->where('blog_status', 'Published');
        if ($indexableOnly) {
            $this->db->where('robots_index', 1);
        }
        $this->db->order_by('blog_added', 'DESC');
        $this->db->order_by('blog_id', 'DESC');

        return $this->db->get()->result_array();
    }

    /** Public services (service and category enabled) open to indexing, in menu order. */
    public function get_services($indexableOnly = true)
    {
        $this->db->select(
            's.service_name, s.service_slug, s.service_summary, s.service_price_from, s.service_price_suffix, '
            . 's.service_duration_label, s.service_updated, s.service_card_image, s.service_hero_image, s.og_image, '
            . 'c.category_name',
            false
        );
        $this->db->from('services s');
        $this->db->join('service_categories c', 'c.category_id = s.service_category_id');
        $this->db->where('s.service_status', 'Enable');
        $this->db->where('c.category_status', 'Enable');
        if ($indexableOnly) {
            $this->db->where('s.robots_index', 1);
        }
        $this->db->order_by('c.category_order', 'ASC');
        $this->db->order_by('s.service_order', 'ASC');

        return $this->db->get()->result_array();
    }

    /** Enabled artists, in team order. */
    public function get_artists()
    {
        $this->db->select('artist_name, artist_slug, artist_role, artist_specialties, artist_image, artist_updated', false);
        $this->db->from('artists');
        $this->db->where('artist_status', 'Enable');
        $this->db->order_by('artist_order', 'ASC');
        $this->db->order_by('artist_id', 'ASC');

        return $this->db->get()->result_array();
    }
}
