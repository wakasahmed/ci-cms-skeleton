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

    /**
     * Enabled blog categories that hold at least one published post. An empty
     * category page is thin content, so it is left out of the sitemap.
     */
    public function get_blog_categories($indexableOnly = true)
    {
        $this->db->select('c.cat_id, c.cat_slug, c.cat_name, c.cat_updated, c.cat_cover_image, c.og_image', false);
        $this->db->from('blog_categories c');
        $this->db->join('blog_assigned_cat bac', 'bac.bc_cat_id = c.cat_id');
        $this->db->join('blogs b', "b.blog_id = bac.bc_blog_id AND b.blog_status = 'Published'", 'inner', false);
        $this->db->where('c.cat_status', 'Enable');
        if ($indexableOnly) {
            $this->db->where('c.robots_index', 1);
        }
        $this->db->group_by('c.cat_id');
        $this->db->order_by('c.cat_order', 'ASC');
        $this->db->order_by('c.cat_id', 'ASC');

        return $this->db->get()->result_array();
    }
}
