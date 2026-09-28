<?php defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Read-only blog queries backing the public frontend.
 *
 * Selects only the columns the frontend actually renders. Every
 * listing/detail/related method attaches category data through the same
 * batched aggregator (attachCategoryData()), so a post's primary category,
 * every enabled category it is assigned to, and the stable category ids used
 * for filtering are always computed the same way.
 */
class Blog_model extends SqlModel
{
    /** Published homepage posts with their first enabled category attached. */
    public function get_homepage_posts($limit = 3)
    {
        return $this->attachCategoryData($this->queryPublishedPosts($limit));
    }

    /**
     * Published blog posts, featured first, then most recent. Used by the
     * homepage, which does not need pagination or category filtering.
     */
    public function get_blog_posts($limit = 0)
    {
        return $this->queryPublishedPosts($limit);
    }

    /**
     * One page of the public Blog listing, optionally narrowed to one
     * category. `$offset`/`$limit` are applied in SQL — the caller never
     * loads the full table to slice it in PHP.
     */
    public function get_blog_listing($categoryId, $offset, $limit)
    {
        $rows = $this->queryPublishedPosts($limit, 0, $categoryId, $offset);

        return $this->attachCategoryData($rows);
    }

    /**
     * Total published posts, optionally narrowed to one category — the
     * count real pagination is built from.
     */
    public function get_published_count($categoryId = 0)
    {
        $categoryId = (int) $categoryId;

        $this->db->select('b.blog_id', false);
        $this->db->distinct();
        $this->db->from('blogs b');
        $this->db->where('b.blog_status', 'Published');
        if ($categoryId > 0) {
            $this->db->join('blog_assigned_cat bac', 'bac.bc_blog_id = b.blog_id');
            $this->db->where('bac.bc_cat_id', $categoryId);
        }

        return (int) $this->db->count_all_results();
    }

    /**
     * Enabled blog categories in their managed order for Blog navigation.
     */
    public function get_blog_category_nav()
    {
        $this->db->select(implode(',', array(
            'bc.cat_id',
            'bc.cat_slug',
            'bc.cat_name',
        )), false);
        $this->db->from('blog_categories bc');
        $this->db->where('bc.cat_status', 'Enable');
        $this->db->order_by('bc.cat_order', 'ASC');
        $this->db->order_by('bc.cat_id', 'ASC');

        return $this->db->get()->result_array();
    }

    /**
     * One enabled category by its stable English slug, with its own
     * localized SEO, robots and banner fields — or null when the slug is
     * unknown or the category is disabled, so the caller can fall back to
     * the full listing predictably.
     */
    public function get_category_by_slug($slug)
    {
        $slug = trim((string) $slug);
        if ($slug === '') {
            return null;
        }

        $this->db->select(implode(',', array(
            'cat_id',
            'cat_slug',
            'cat_cover_image',
            'show_top_banner',
            'banner_overlay',
            'banner_background_color_1',
            'banner_background_color_2',
            'banner_title_color_1',
            'banner_title_color_2',
            'banner_heading_color_1',
            'banner_heading_color_2',
            'banner_text_color_1',
            'banner_text_color_2',
            'robots_index',
            'robots_follow',
            'cat_name',
            'cat_desc',
            'cat_contents',
            'page_title',
            'meta_description',
            'meta_keywords',
            'og_title',
            'og_description',
            'og_image',
            'banner_title',
            'banner_heading',
            'banner_text',
            'banner_background',
        )), false);
        $this->db->from('blog_categories');
        $this->db->where('cat_slug', $slug);
        $this->db->where('cat_status', 'Enable');
        $row = $this->db->get()->row_array();

        return $row !== null ? $row : null;
    }

    /**
     * One published post by its managed slug.
     * Returns null for an empty, unknown or unpublished slug so the
     * controller can return a proper 404 rather than falling back to
     * another post.
     */
    public function get_post_by_slug($slug)
    {
        $slug = trim((string) $slug);
        if ($slug === '') {
            return null;
        }

        $this->db->select(implode(',', array(
            'blog_id',
            'blog_author',
            'blog_time_to_read',
            'blog_added',
            'blog_pdate',
            'blog_updated',
            'blog_featured',
            'blog_image',
            'blog_cover_image',
            'show_top_banner',
            'banner_overlay',
            'banner_background_color_1',
            'banner_background_color_2',
            'banner_title_color_1',
            'banner_title_color_2',
            'banner_heading_color_1',
            'banner_heading_color_2',
            'banner_text_color_1',
            'banner_text_color_2',
            'robots_index',
            'robots_follow',
            'blog_slug',
            'blog_name',
            'blog_short_description',
            'blog_text',
            'page_title',
            'meta_description',
            'meta_keywords',
            'og_title',
            'og_description',
            'og_image',
            'banner_title',
            'banner_heading',
            'banner_text',
            'banner_background',
        )), false);
        $this->db->from('blogs');
        $this->db->where('blog_slug', $slug);
        $this->db->where('blog_status', 'Published');
        $row = $this->db->get()->row_array();
        if ($row === null) {
            return null;
        }

        $rows = $this->attachCategoryData(array($row));

        return $rows[0];
    }

    /**
     * Featured-first, most-recent posts for the article side rail, excluding
     * the article being read.
     */
    public function get_rail_posts($excludeId, $limit)
    {
        $rows = $this->queryPublishedPosts($limit, $excludeId);

        return $this->attachCategoryData($rows);
    }

    /**
     * Related posts for the "Keep reading" grid: published, excluding the
     * current article and whatever the side rail is already showing —
     * throughout both passes, so a post visible in the rail never also
     * appears in this grid — preferring posts that share one of the current
     * article's enabled categories. If that leaves fewer than $limit, the
     * remainder is filled from the most recent posts overall, still
     * excluding the current article, the rail, and anything already chosen.
     * A short article list comes back with fewer cards rather than a
     * repeated one.
     */
    public function get_related_posts($currentId, array $railIds, array $categoryIds, $limit)
    {
        $currentId = (int) $currentId;
        $limit = max(0, (int) $limit);
        $categoryIds = array_values(array_unique(array_filter(array_map('intval', $categoryIds))));

        $columns = implode(',', array(
            'b.blog_id',
            'b.blog_slug',
            'b.blog_image',
            'b.blog_cover_image',
            'b.blog_time_to_read',
            'b.blog_added',
            'b.blog_pdate',
            'b.blog_featured',
            'b.blog_name',
            'b.blog_short_description',
        ));

        $railExclude = array_values(array_unique(array_merge(
            array($currentId),
            array_map('intval', $railIds)
        )));

        $related = array();
        if (!empty($categoryIds) && $limit > 0) {
            $this->db->select($columns, false);
            $this->db->distinct();
            $this->db->from('blogs b');
            $this->db->join('blog_assigned_cat bac', 'bac.bc_blog_id = b.blog_id');
            $this->db->where('b.blog_status', 'Published');
            $this->db->where_not_in('b.blog_id', $railExclude);
            $this->db->where_in('bac.bc_cat_id', $categoryIds);
            $this->db->order_by("b.blog_featured = 'Yes' DESC, b.blog_added DESC", '', false);
            $this->db->limit($limit);
            $related = $this->db->get()->result_array();
        }

        $remaining = $limit - count($related);
        if ($remaining > 0) {
            $exclude = array_values(array_unique(array_merge(
                $railExclude,
                array_column($related, 'blog_id')
            )));

            $this->db->select($columns, false);
            $this->db->from('blogs b');
            $this->db->where('b.blog_status', 'Published');
            $this->db->where_not_in('b.blog_id', $exclude);
            $this->db->order_by("b.blog_featured = 'Yes' DESC, b.blog_added DESC", '', false);
            $this->db->limit($remaining);
            $related = array_merge($related, $this->db->get()->result_array());
        }

        return $this->attachCategoryData($related);
    }

    /**
     * Managed author display names for a batch of `admin_users` ids —
     * `full_name` when set, `user_name` otherwise. Never the business name:
     * a post with no resolvable author simply has no entry in the map.
     */
    public function get_authors(array $authorIds)
    {
        $authorIds = array_values(array_unique(array_filter(array_map('intval', $authorIds))));
        if (empty($authorIds)) {
            return array();
        }

        $this->db->select('id, full_name, user_name');
        $this->db->from('admin_users');
        $this->db->where_in('id', $authorIds);

        $map = array();
        foreach ($this->db->get()->result_array() as $row) {
            $name = trim((string) $row['full_name']);
            if ($name === '') {
                $name = trim((string) $row['user_name']);
            }
            $map[(int) $row['id']] = $name;
        }

        return $map;
    }

    /**
     * The published-posts query shared by every listing method here.
     * `$categoryId` joins to `blog_assigned_cat` when set; `$excludeId`
     * drops one post (the article currently being read); `$offset` is only
     * meaningful together with `$limit`.
     */
    private function queryPublishedPosts($limit = 0, $excludeId = 0, $categoryId = 0, $offset = 0)
    {
        $this->db->select(implode(',', array(
            'b.blog_id',
            'b.blog_slug',
            'b.blog_image',
            'b.blog_cover_image',
            'b.blog_time_to_read',
            'b.blog_added',
            'b.blog_pdate',
            'b.blog_featured',
            'b.blog_name',
            'b.blog_short_description',
        )), false);
        $this->db->from('blogs b');

        $categoryId = (int) $categoryId;
        if ($categoryId > 0) {
            $this->db->join('blog_assigned_cat bac', 'bac.bc_blog_id = b.blog_id');
            $this->db->where('bac.bc_cat_id', $categoryId);
            $this->db->distinct();
        }

        $this->db->where('b.blog_status', 'Published');

        $excludeId = (int) $excludeId;
        if ($excludeId > 0) {
            $this->db->where('b.blog_id !=', $excludeId);
        }

        $this->db->order_by("b.blog_featured = 'Yes' DESC, b.blog_added DESC", '', false);
        if ($limit > 0) {
            $this->db->limit($limit, max(0, (int) $offset));
        }

        return $this->db->get()->result_array();
    }

    /**
     * Attach `category_name`/`category_slug` (the primary, lowest
     * `cat_order` enabled category), `category_ids` (every enabled category
     * assigned, for filtering) and `categories` (the full label/slug list)
     * to a batch of post rows in one query — the aggregation every listing,
     * detail, related-post and homepage method shares.
     */
    private function attachCategoryData(array $rows)
    {
        $relations = $this->getBlogCategoryRelations(array_column($rows, 'blog_id'));

        foreach ($rows as &$row) {
            $blogId = (int) $row['blog_id'];
            $categories = isset($relations[$blogId]) ? $relations[$blogId] : array();
            $primary = isset($categories[0]) ? $categories[0] : array();

            $row['category_name'] = isset($primary['label']) ? $primary['label'] : '';
            $row['category_slug'] = isset($primary['slug']) ? $primary['slug'] : '';
            $row['category_ids'] = array_column($categories, 'id');
            $row['categories'] = $categories;
        }
        unset($row);

        return $rows;
    }

    /**
     * Every enabled category assigned to each blog id, ordered by
     * `cat_order`, batched into one query. Returns
     * [blog_id => [['id' => ..., 'slug' => ..., 'label' => ...], ...]].
     */
    private function getBlogCategoryRelations(array $blogIds)
    {
        $blogIds = array_values(array_unique(array_filter(array_map('intval', $blogIds))));
        if (empty($blogIds)) {
            return array();
        }

        $this->db->select(implode(',', array(
            'bac.bc_blog_id AS blog_id',
            'bc.cat_id',
            'bc.cat_slug',
            'bc.cat_name',
        )), false);
        $this->db->from('blog_assigned_cat bac');
        $this->db->join('blog_categories bc', 'bc.cat_id = bac.bc_cat_id');
        $this->db->where('bc.cat_status', 'Enable');
        $this->db->where_in('bac.bc_blog_id', $blogIds);
        $this->db->order_by('bac.bc_blog_id', 'ASC');
        $this->db->order_by('bc.cat_order', 'ASC');
        $this->db->order_by('bc.cat_id', 'ASC');

        $map = array();
        foreach ($this->db->get()->result_array() as $row) {
            $blogId = (int) $row['blog_id'];
            $map[$blogId][] = array(
                'id' => (string) (int) $row['cat_id'],
                'slug' => (string) $row['cat_slug'],
                'label' => $row['cat_name'],
            );
        }

        return $map;
    }
}
