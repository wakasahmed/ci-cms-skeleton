<?php defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Read-only journal (blog) queries backing the public frontend. Only
 * published posts are public, newest first by publish date. Each post
 * carries its primary category: the first enabled category assigned to it,
 * in the order set in Manage > Blog Categories.
 */
class Blog_model extends SqlModel
{
    /** Columns every listing card needs. */
    const CARD_COLUMNS = 'b.blog_id, b.blog_slug, b.blog_name, b.blog_short_description, b.blog_image, '
        .'b.blog_time_to_read, b.blog_added, b.blog_pdate, b.blog_updated';

    /** Published posts, newest first, with their primary category. */
    public function get_published()
    {
        $rows = $this->db
            ->select(self::CARD_COLUMNS, FALSE)
            ->from('blogs b')
            ->where('b.blog_status', 'Published')
            ->order_by('COALESCE(b.blog_pdate, b.blog_added)', 'DESC', FALSE)
            ->order_by('b.blog_id', 'DESC')
            ->get()
            ->result_array();

        return $this->attachCategories($rows);
    }

    /** Enabled categories in their managed order. */
    public function get_categories()
    {
        return $this->db
            ->select('cat_id, cat_name, cat_slug')
            ->where('cat_status', 'Enable')
            ->order_by('cat_order', 'ASC')
            ->order_by('cat_id', 'ASC')
            ->get('blog_categories')
            ->result_array();
    }

    /**
     * One published post by slug with its primary category and related
     * service (enabled services only), or NULL.
     */
    public function get_by_slug($slug)
    {
        $row = $this->db
            ->select('b.*, s.service_slug, s.service_name, s.service_summary, s.service_price_from, '
                .'s.service_price_suffix, s.service_duration_label', FALSE)
            ->from('blogs b')
            ->join('services s', "s.service_id = b.blog_service_id AND s.service_status = 'Enable'", 'left', FALSE)
            ->where('b.blog_slug', (string) $slug)
            ->where('b.blog_status', 'Published')
            ->limit(1)
            ->get()
            ->row_array();

        if ($row === NULL) {
            return NULL;
        }

        $rows = $this->attachCategories(array($row));

        return $rows[0];
    }

    /** The newest published posts other than the one being read. */
    public function get_more($excludeId, $limit = 3)
    {
        $rows = $this->db
            ->select(self::CARD_COLUMNS, FALSE)
            ->from('blogs b')
            ->where('b.blog_status', 'Published')
            ->where('b.blog_id !=', (int) $excludeId)
            ->order_by('COALESCE(b.blog_pdate, b.blog_added)', 'DESC', FALSE)
            ->order_by('b.blog_id', 'DESC')
            ->limit((int) $limit)
            ->get()
            ->result_array();

        return $this->attachCategories($rows);
    }

    /**
     * Adds category_name and category_slug (the primary category) to each
     * post, with one query for the whole batch.
     */
    private function attachCategories(array $rows)
    {
        $primary = array();
        $blogIds = array_map('intval', array_column($rows, 'blog_id'));

        if (!empty($blogIds)) {
            $categories = $this->db
                ->select('a.bc_blog_id, c.cat_name, c.cat_slug')
                ->from('blog_assigned_cat a')
                ->join('blog_categories c', 'c.cat_id = a.bc_cat_id')
                ->where('c.cat_status', 'Enable')
                ->where_in('a.bc_blog_id', $blogIds)
                ->order_by('c.cat_order', 'ASC')
                ->order_by('c.cat_id', 'ASC')
                ->get()
                ->result_array();

            foreach ($categories as $category) {
                $blogId = (int) $category['bc_blog_id'];
                if (!isset($primary[$blogId])) {
                    $primary[$blogId] = $category;
                }
            }
        }

        foreach ($rows as &$row) {
            $blogId = (int) $row['blog_id'];
            $row['category_name'] = isset($primary[$blogId]) ? $primary[$blogId]['cat_name'] : '';
            $row['category_slug'] = isset($primary[$blogId]) ? $primary[$blogId]['cat_slug'] : '';
        }
        unset($row);

        return $rows;
    }
}
