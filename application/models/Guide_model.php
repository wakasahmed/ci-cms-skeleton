<?php defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Read-only tour guide queries backing the public frontend.
 *
 * Every method respects `tour_guide_status` and the administrator-defined
 * `tour_guide_order`, selects only the columns the frontend actually
 * renders, and batches per-guide lookups (languages, tour counts) into a
 * single query instead of looping.
 */
class Guide_model extends Localized_model
{
    /** Active homepage guides with batched language and tour-count relations. */
    public function get_homepage_guides($limit = 3, $locale = 'en')
    {
        $guides = $this->get_guides($limit, $locale);
        $guideIds = array_column($guides, 'tour_guide_id');
        $languages = $this->get_guide_languages($guideIds, $locale);
        $tourCounts = $this->get_guide_tour_counts($guideIds);

        foreach ($guides as &$guide) {
            $guideId = (int) $guide['tour_guide_id'];
            $guide['languages'] = isset($languages[$guideId])
                ? array_column($languages[$guideId], 'lang_name')
                : array();
            $guide['tour_count'] = isset($tourCounts[$guideId])
                ? (int) $tourCounts[$guideId]
                : 0;
        }
        unset($guide);

        return $guides;
    }

    /**
     * Every active guide for the public Tour Guides listing, plus the
     * languages those guides speak for its filter. Mirrors the shape of
     * Tour_model::get_experience_listing(): ['guides' => ..., 'languages' =>
     * [['id' => ..., 'label' => ...], ...]].
     */
    public function get_guide_listing($locale = 'en')
    {
        $locale = $this->normalizeLocale($locale);
        $guides = $this->get_guides(0, $locale);
        $guideIds = array_column($guides, 'tour_guide_id');
        $languages = $this->get_guide_languages($guideIds, $locale);
        $tourCounts = $this->get_guide_tour_counts($guideIds);

        $filterLanguageIds = array();
        foreach ($guides as &$guide) {
            $guideId = (int) $guide['tour_guide_id'];
            $guideLanguages = isset($languages[$guideId])
                ? $languages[$guideId]
                : array();

            $guide['languages'] = array_column($guideLanguages, 'lang_name');
            $guide['language_ids'] = array_column($guideLanguages, 'lang_id');
            $guide['tour_count'] = isset($tourCounts[$guideId])
                ? (int) $tourCounts[$guideId]
                : 0;

            foreach ($guide['language_ids'] as $languageId) {
                $filterLanguageIds[$languageId] = true;
            }
        }
        unset($guide);

        return array(
            'guides' => $guides,
            'languages' => $this->get_listing_languages(
                array_keys($filterLanguageIds),
                $locale
            ),
        );
    }

    /**
     * The given language ids as filter options, in `lang_order`.
     */
    private function get_listing_languages(array $languageIds, $locale)
    {
        $languageIds = array_values(array_unique(array_map('intval', $languageIds)));
        if (empty($languageIds)) {
            return array();
        }

        $this->db->select(implode(',', array(
            'lang_id',
            $this->localizedColumn('lang_name', $locale),
        )), false);
        $this->db->from('tour_languages');
        $this->db->where_in('lang_id', $languageIds);
        $this->db->order_by('lang_order', 'ASC');

        $options = array();
        foreach ($this->db->get()->result_array() as $row) {
            $options[] = array(
                'id' => (string) (int) $row['lang_id'],
                'label' => $row['lang_name'],
            );
        }

        return $options;
    }

    /**
     * Active tour guides, ordered by `tour_guide_order`.
     */
    public function get_guides($limit = 0, $locale = 'en')
    {
        $locale = $this->normalizeLocale($locale);

        $this->db->select(implode(',', array(
            'tour_guide_id',
            'tour_guide_image',
            $this->localizedColumn('tour_guide_name', $locale),
            $this->localizedColumn('tour_guide_title', $locale),
            $this->localizedColumn('tour_guide_desc', $locale),
        )), false);
        $this->db->from('tour_guides');
        $this->db->where('tour_guide_status', 'Enable');
        $this->db->order_by('tour_guide_order', 'ASC');
        if ($limit > 0) {
            $this->db->limit($limit);
        }

        return $this->db->get()->result_array();
    }

    /**
     * The enabled languages assigned to each guide id, batched into one
     * query. Returns [tour_guide_id => [['lang_id' => ..., 'lang_name' => ...], ...]]
     * with `lang_name` already resolved to the requested locale.
     */
    public function get_guide_languages(array $guideIds, $locale = 'en')
    {
        $locale = $this->normalizeLocale($locale);
        $guideIds = array_values(array_unique(array_map('intval', $guideIds)));
        if (empty($guideIds)) {
            return array();
        }

        $this->db->select(implode(',', array(
            'tgal.tour_guide_id',
            'tl.lang_id',
            $this->localizedColumn('tl.lang_name', $locale, 'lang_name'),
        )), false);
        $this->db->from('tour_guide_assigned_languages tgal');
        $this->db->join('tour_languages tl', 'tl.lang_id = tgal.lang_id');
        $this->db->where('tl.lang_status', 'Enable');
        $this->db->where_in('tgal.tour_guide_id', $guideIds);
        $this->db->order_by('tl.lang_order', 'ASC');
        $rows = $this->db->get()->result_array();

        $map = array();
        foreach ($rows as $row) {
            $guideId = (int) $row['tour_guide_id'];
            $map[$guideId][] = array(
                'lang_id' => (int) $row['lang_id'],
                'lang_name' => $row['lang_name'],
            );
        }

        return $map;
    }

    /**
     * The number of tours assigned to each guide id, batched into one query.
     * Returns [tour_guide_id => count].
     */
    public function get_guide_tour_counts(array $guideIds)
    {
        $guideIds = array_values(array_unique(array_map('intval', $guideIds)));
        if (empty($guideIds)) {
            return array();
        }

        $this->db->select('tour_guide_id, COUNT(*) AS tour_count');
        $this->db->from('tour_guide_assigned_tours');
        $this->db->where_in('tour_guide_id', $guideIds);
        $this->db->group_by('tour_guide_id');
        $rows = $this->db->get()->result_array();

        $map = array();
        foreach ($rows as $row) {
            $map[(int) $row['tour_guide_id']] = (int) $row['tour_count'];
        }

        return $map;
    }
}
