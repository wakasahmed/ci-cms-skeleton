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
            'page_id, page_slug, page_slug_ar, page_name, page_name_ar, page_updated, og_image, banner_background',
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

    /** Enabled tours and experiences, in the order the site lists them. */
    public function get_tours($indexableOnly = true)
    {
        $this->db->select(
            'tour_id, tour_type, tour_slug, tour_slug_ar, tour_name, tour_short_description, '
            . 'tour_updated, tour_image, tour_image_ar, tour_bg_image, og_image',
            false
        );
        $this->db->from('tours');
        $this->db->where('tour_status', 'Enable');
        if ($indexableOnly) {
            $this->db->where('robots_index', 1);
        }
        $this->db->order_by('tour_type', 'ASC');
        $this->db->order_by('tour_order', 'ASC');
        $this->db->order_by('tour_id', 'ASC');

        return $this->db->get()->result_array();
    }

    /** Enabled gallery images for the given tours, keyed by tour id. */
    public function get_tour_gallery_files(array $tourIds, $perTour = 10)
    {
        $tourIds = array_values(array_filter(array_map('intval', $tourIds)));
        if (empty($tourIds)) {
            return array();
        }

        $this->db->select('image_tour_id, image_image', false);
        $this->db->from('tour_images');
        $this->db->where_in('image_tour_id', $tourIds);
        $this->db->where('image_status', 'Enable');
        $this->db->where('image_image <>', '');
        $this->db->order_by('image_tour_id', 'ASC');
        $this->db->order_by('image_order', 'ASC');
        $this->db->order_by('image_id', 'ASC');

        $files = array();
        foreach ($this->db->get()->result_array() as $row) {
            $tourId = (int) $row['image_tour_id'];
            if (!isset($files[$tourId])) {
                $files[$tourId] = array();
            }
            if (count($files[$tourId]) < (int) $perTour) {
                $files[$tourId][] = $row['image_image'];
            }
        }

        return $files;
    }

    /** Published blog posts, newest first. */
    public function get_blog_posts($indexableOnly = true)
    {
        $this->db->select(
            'blog_id, blog_slug, blog_slug_ar, blog_name, blog_short_description, blog_added, blog_pdate, '
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
