<?php defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Read-only `pages` lookups backing the public frontend.
 *
 * Selects only the columns the frontend actually renders and resolves
 * bilingual columns to the requested locale in the query itself.
 */
class Webpage_model extends Localized_model
{
    /**
     * One `pages` record, found either by its id or by its slug.
     *
     * $type 'id' looks up `page_id`. $type 'slug' matches $id_slug against
     * either `page_slug` or `page_slug_ar`, so a page is found by its slug
     * regardless of which locale that slug was typed in.
     */
    public function get_page(
        $locale = 'en',
        $type = 'id',
        $id_slug = 1,
        $includeContent = false,
        $publishedOnly = false
    )
    {
        $locale = $this->normalizeLocale($locale);
        $type = $type === 'slug' ? 'slug' : 'id';

        $columns = array(
            'page_id',
            'page_slug',
            'page_slug_ar',
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
            $this->localizedColumn('page_name', $locale),
            $this->localizedColumn('page_title', $locale),
            $this->localizedColumn('meta_description', $locale),
            $this->localizedColumn('meta_keywords', $locale),
            $this->localizedColumn('og_title', $locale),
            $this->localizedColumn('og_description', $locale),
            $this->localizedColumn('og_image', $locale),
            $this->localizedColumn('banner_title', $locale),
            $this->localizedColumn('banner_heading', $locale),
            $this->localizedColumn('banner_text', $locale),
            $this->localizedColumn('banner_background', $locale),
        );
        if ($includeContent) {
            $columns[] = $this->localizedColumn('page_text', $locale);
        }

        $this->db->select(implode(',', $columns), false);
        $this->db->from('pages');

        if ($type === 'slug') {
            $slug = trim((string) $id_slug);
            $this->db->group_start();
            $this->db->where('page_slug', $slug);
            $this->db->or_where('page_slug_ar', $slug);
            $this->db->group_end();
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
    public function get_hero_slides($sliderId, $locale = 'en')
    {
        $locale = $this->normalizeLocale($locale);
        $sliderId = (int) $sliderId;
        if ($sliderId <= 0) {
            return array();
        }

        $this->db->select(implode(',', array(
            $this->localizedColumn('image', $locale),
            $this->localizedColumn('pre_heading', $locale),
            $this->localizedColumn('heading', $locale),
            $this->localizedColumn('text', $locale),
            'overlay',
            'banner_pre_heading_color_1',
            'banner_pre_heading_color_2',
            'banner_heading_color_1',
            'banner_heading_color_2',
            'banner_text_color_1',
            'banner_text_color_2',
            'button_1_icon',
            $this->localizedColumn('button_1_text', $locale),
            'button_1_url',
            'button_1_target',
            'button_2_icon',
            $this->localizedColumn('button_2_text', $locale),
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
