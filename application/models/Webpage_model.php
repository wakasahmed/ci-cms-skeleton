<?php defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Read-only `pages` lookups backing the public frontend.
 *
 * Selects only the columns the frontend actually renders.
 */
class Webpage_model extends SqlModel
{
    /**
     * One `pages` record, found either by its id or by its slug.
     *
     * $type 'id' looks up `page_id`. $type 'slug' matches $id_slug against
     * `page_slug`.
     */
    public function get_page(
        $type = 'id',
        $id_slug = 1,
        $includeContent = false,
        $publishedOnly = false
    )
    {
        $type = $type === 'slug' ? 'slug' : 'id';

        $columns = array(
            'page_id',
            'page_slug',
            'page_slider',
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
            'page_name',
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
        );
        if ($includeContent) {
            $columns[] = 'page_text';
        }

        $this->db->select(implode(',', $columns), false);
        $this->db->from('pages');

        if ($type === 'slug') {
            $slug = trim((string) $id_slug);
            $this->db->where('page_slug', $slug);
        } else {
            $this->db->where('page_id', (int) $id_slug);
        }
        if ($publishedOnly) {
            $this->db->where('page_status', 'Published');
        }

        return $this->db->get()->row_array();
    }

    /**
     * Hero slides for one slider group — a page's is found on its own
     * `pages` record, `page_slider` (see get_page()). Returns an empty
     * array when $sliderId is not configured or the group has no enabled
     * slides, so the caller can fall back to static content.
     */
    public function get_hero_slides($sliderId)
    {
        $sliderId = (int) $sliderId;
        if ($sliderId <= 0) {
            return array();
        }

        $this->db->select(implode(',', array(
            'image',
            'pre_heading',
            'heading',
            'text',
            'overlay',
            'banner_pre_heading_color_1',
            'banner_pre_heading_color_2',
            'banner_heading_color_1',
            'banner_heading_color_2',
            'banner_text_color_1',
            'banner_text_color_2',
            'button_1_icon',
            'button_1_text',
            'button_1_url',
            'button_1_target',
            'button_2_icon',
            'button_2_text',
            'button_2_url',
            'button_2_target',
        )), false);
        $this->db->from('slider');
        $this->db->where('slider_id', $sliderId);
        $this->db->where('status', 'Enable');
        $this->db->order_by('`order`', 'ASC');
        $this->db->order_by('id', 'ASC');

        return $this->db->get()->result_array();
    }
}
