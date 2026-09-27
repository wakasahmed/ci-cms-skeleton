<?php defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Read-only tour/experience queries backing the public frontend.
 *
 * Every method respects `tour_status` and the administrator-defined
 * `tour_order`, selects only the columns the frontend actually renders, and
 * resolves bilingual columns to the requested locale in the query itself
 * (see Localized_model::localizedColumn()).
 */
class Tour_model extends Localized_model
{
    /** Keep published records selectable so missing assignments have a useful booking empty state. */
    public function get_booking_tours($locale = 'en', $type = null)
    {
        $locale = $this->normalizeLocale($locale);
        $this->db->select('tour_id');
        $this->db->select($this->localizedColumn('tour_slug', $locale), false);
        $this->db->where('tour_status', 'Enable');
        $types = in_array($type, array('Tour', 'Experience'), true)
            ? array($type)
            : array('Tour', 'Experience');
        $this->db->where_in('tour_type', $types);
        $this->db->order_by('tour_order', 'ASC');

        return $this->db->get('tours')->result_array();
    }

    /**
     * Bookable Tours for the homepage availability search, each with the
     * vehicles and guide languages its booking page offers. Relations are
     * loaded in batches; only Tours (never Experiences) are returned.
     */
    public function get_hero_search_options($locale = 'en')
    {
        $locale = $this->normalizeLocale($locale);
        $tours = $this->get_tours('Tour', 0, $locale);
        $tourIds = $this->normalizeIds(array_column($tours, 'tour_id'));
        if (empty($tourIds)) {
            return array();
        }

        $this->db->select(implode(',', array(
            'av.tour_id',
            'v.vehicle_id',
            'v.vehicle_max_capacity',
            $this->localizedColumn('v.vehicle_name', $locale, 'vehicle_name'),
        )), false);
        $this->db->from('tour_assigned_vehicles av');
        $this->db->join('vehicles v', 'v.vehicle_id = av.vehicle_id');
        $this->db->where_in('av.tour_id', $tourIds);
        $this->db->where('v.vehicle_status', 'Enable');
        $this->db->order_by('v.vehicle_order', 'ASC');

        $vehiclesByTour = array();
        foreach ($this->db->get()->result_array() as $row) {
            $vehiclesByTour[(int) $row['tour_id']][] = $row;
        }

        $languageData = $this->getTourLanguageRelations($tourIds, $locale);
        $options = array();
        foreach ($tours as $tour) {
            $tourId = (int) $tour['tour_id'];
            $options[] = array(
                'tour_id' => $tourId,
                'tour_name' => $tour['tour_name'],
                'vehicles' => isset($vehiclesByTour[$tourId])
                    ? $vehiclesByTour[$tourId]
                    : array(),
                'languages' => isset($languageData['by_tour'][$tourId])
                    ? $languageData['by_tour'][$tourId]
                    : array(),
            );
        }

        return $options;
    }

    /**
     * Whether any enabled guide assigned to the Tour is free on a date, for
     * the homepage availability check. Mirrors what the booking page offers:
     * only slots assigned to the Tour with a valid duration and a price count,
     * and only rows still 'Available' (not Reserved, On-hold or Unavailable).
     * A language ID narrows the guides to those who speak it.
     */
    public function has_available_guide($tourId, $date, $languageId = 0)
    {
        $this->db->select('a.avail_id');
        $this->db->from('tours t');
        $this->db->join('tour_guide_assigned_tours tgat', 'tgat.tour_id = t.tour_id');
        $this->db->join('tour_guides g', 'g.tour_guide_id = tgat.tour_guide_id');
        $this->db->join('tour_guide_availability a', 'a.avail_tour_guide_id = g.tour_guide_id');
        $this->db->join('tour_assigned_slots ts', 'ts.tour_id = t.tour_id AND ts.slot_id = a.avail_slot_id');
        $this->db->join('tour_slots s', 's.slot_id = a.avail_slot_id');
        if ((int) $languageId > 0) {
            $this->db->join('tour_guide_assigned_languages tgal', 'tgal.tour_guide_id = g.tour_guide_id');
            $this->db->join('tour_languages tl', 'tl.lang_id = tgal.lang_id');
            $this->db->where('tgal.lang_id', (int) $languageId);
            $this->db->where('tl.lang_status', 'Enable');
        }
        $this->db->where('t.tour_id', (int) $tourId);
        $this->db->where('t.tour_type', 'Tour');
        $this->db->where('t.tour_status', 'Enable');
        $this->db->where('g.tour_guide_status', 'Enable');
        $this->db->where('a.avail_date', $date);
        $this->db->where('a.avail_status', 'Enable');
        $this->db->where('a.avail_book_status', 'Available');
        $this->db->where('s.slot_status', 'Enable');
        $this->db->where(
            "CASE s.slot_hours
                WHEN 2 THEN t.tour_price_2
                WHEN 4 THEN t.tour_price_4
                WHEN 6 THEN t.tour_price_6
                WHEN 8 THEN t.tour_price_8
                ELSE 0
            END > 0",
            null,
            false
        );
        $this->db->limit(1);

        return $this->db->get()->num_rows() > 0;
    }

    /**
     * Complete data set for the public Tours listing and its filters.
     * Relationships are loaded in batches so the view never performs queries
     * or derives category, guest-capacity, or guide-language metadata.
     */
    public function get_tour_listing($locale = 'en')
    {
        $locale = $this->normalizeLocale($locale);
        $tours = $this->get_tours('Tour', 0, $locale);
        $tourIds = array_column($tours, 'tour_id');
        $categoryData = $this->getTourCategoryRelations($tourIds, $locale);
        $languageData = $this->getTourLanguageRelations($tourIds, $locale);
        $capacities = array();

        foreach ($tours as &$tour) {
            $tourId = (int) $tour['tour_id'];
            $tourCategories = isset($categoryData['by_tour'][$tourId])
                ? $categoryData['by_tour'][$tourId]
                : array();

            $tour['category_name'] = isset($tourCategories[0]['label'])
                ? $tourCategories[0]['label']
                : '';
            $tour['category_ids'] = array_column($tourCategories, 'id');
            $tour['category_names'] = array_column($tourCategories, 'label');
            $tour['max_capacity'] = $this->extractCapacity(
                isset($tour['tour_group_size_filter'])
                    ? $tour['tour_group_size_filter']
                    : ''
            );
            if ($tour['max_capacity'] > 0) {
                $capacities[$tour['max_capacity']] = $tour['max_capacity'];
            }
            $tour['language_ids'] = isset($languageData['by_tour'][$tourId])
                ? array_column($languageData['by_tour'][$tourId], 'id')
                : array();
            $tour['language_names'] = isset($languageData['by_tour'][$tourId])
                ? array_column($languageData['by_tour'][$tourId], 'label')
                : array();
        }
        unset($tour);
        ksort($capacities, SORT_NUMERIC);

        return array(
            'tours' => $tours,
            'categories' => array_values($categoryData['options']),
            'capacities' => array_values($capacities),
            'languages' => array_values($languageData['options']),
        );
    }

    /**
     * Complete data set for the public Experiences listing and its category
     * filter. Mirrors get_tour_listing() — categories are loaded in one
     * batched query and every experience carries the stable category ids it
     * is assigned to, never a translated label, so client-side filtering
     * never breaks when the label is translated.
     */
    public function get_experience_listing($locale = 'en')
    {
        $locale = $this->normalizeLocale($locale);
        $experiences = $this->get_tours('Experience', 0, $locale);
        $experienceIds = array_column($experiences, 'tour_id');
        $categoryData = $this->getTourCategoryRelations($experienceIds, $locale);

        foreach ($experiences as &$experience) {
            $experienceId = (int) $experience['tour_id'];
            $experienceCategories = isset($categoryData['by_tour'][$experienceId])
                ? $categoryData['by_tour'][$experienceId]
                : array();

            $experience['category_name'] = isset($experienceCategories[0]['label'])
                ? $experienceCategories[0]['label']
                : '';
            $experience['category_ids'] = array_column($experienceCategories, 'id');
            $experience['category_names'] = array_column($experienceCategories, 'label');
        }
        unset($experience);

        return array(
            'experiences' => $experiences,
            'categories' => array_values($categoryData['options']),
        );
    }

    /**
     * Homepage tours and experiences with their enabled categories.
     * Both product types are loaded before the category query so the
     * relationship lookup remains batched across the complete result set.
     */
    public function get_tours_experiences($limit = 6, $locale = 'en')
    {
        $limit = max(0, (int) $limit);
        $locale = $this->normalizeLocale($locale);

        $tourRows = $this->get_tours('Tour', $limit, $locale);
        $experienceRows = $this->get_tours('Experience', $limit, $locale);
        $categoryData = $this->getTourCategoryRelations(array_merge(
            array_column($tourRows, 'tour_id'),
            array_column($experienceRows, 'tour_id')
        ), $locale);

        return array(
            'tours' => $this->attachCategories($tourRows, $categoryData),
            'experiences' => $this->attachCategories(
                $experienceRows,
                $categoryData
            ),
        );
    }

    /**
     * Active tours of one type ('Tour' or 'Experience'), ordered by
     * `tour_order`. There is no dedicated "featured" flag, so the top
     * $limit by that order is the selection used on the Home page.
     */
    public function get_tours($type, $limit = 0, $locale = 'en')
    {
        $locale = $this->normalizeLocale($locale);

        $this->db->select(implode(',', array_merge(
            array(
                'tour_id',
                'tour_slug',
                'tour_image',
                'tour_image_ar',
            ),
            $this->startingPriceColumns(),
            $this->guidePriceColumns(),
            array(
                $this->localizedColumn('tour_name', $locale),
                $this->localizedColumn('tour_short_description', $locale),
                $this->localizedColumn('tour_duration', $locale),
                $this->localizedColumn('tour_lang', $locale),
                $this->localizedColumn('tour_group_size', $locale),
                '`tour_group_size` AS `tour_group_size_filter`',
            )
        )), false);
        $this->db->from('tours');
        $this->db->where('tour_type', $type);
        $this->db->where('tour_status', 'Enable');
        $this->db->where(
            "EXISTS (
                SELECT 1
                FROM tour_assigned_vehicles av
                INNER JOIN vehicles v ON v.vehicle_id = av.vehicle_id
                WHERE av.tour_id = tours.tour_id
                AND v.vehicle_status = 'Enable'
            )",
            null,
            false
        );
        /* A Tour is a personally guided experience and is only bookable —
           and only listed — once at least one enabled guide can lead it.
           An Experience carries no guide concept, so it skips this check
           entirely (see tour_details.php's `hasGuides`, which is likewise
           forced off for experiences). */
        if ($type === 'Tour') {
            $this->db->where(
                "EXISTS (
                    SELECT 1
                    FROM tour_guide_assigned_tours tgat
                    INNER JOIN tour_guides tg
                        ON tg.tour_guide_id = tgat.tour_guide_id
                    WHERE tgat.tour_id = tours.tour_id
                    AND tg.tour_guide_status = 'Enable'
                )",
                null,
                false
            );
        }
        $this->db->order_by('tour_order', 'ASC');
        if ($limit > 0) {
            $this->db->limit($limit);
        }

        return $this->withStartingPrice(
            $this->db->get()->result_array(),
            $type
        );
    }

    /**
     * The first enabled category (by `cat_order`) for each tour id, batched
     * into one query. Returns [tour_id => ['cat_name' => ...]] with
     * `cat_name` already resolved to the requested locale.
     */
    public function get_tour_categories(array $tourIds, $locale = 'en')
    {
        $relations = $this->getTourCategoryRelations($tourIds, $locale);
        $map = array();
        foreach ($relations['by_tour'] as $tourId => $categories) {
            $map[$tourId] = array(
                'cat_name' => isset($categories[0]['label'])
                    ? $categories[0]['label']
                    : '',
            );
        }

        return $map;
    }

    /**
     * One tour/experience by slug for the public detail page. Matches either
     * locale's slug column (same pattern as Blog_model::get_post_by_slug()),
     * and always resolves text/pinfo columns to the requested locale
     * regardless of which slug column matched.
     */
    public function get_by_slug($slug, $locale = 'en')
    {
        $locale = $this->normalizeLocale($locale);
        $slug = trim((string) $slug);
        if ($slug === '') {
            return null;
        }

        $this->db->select(implode(',', array_merge(
            array(
                'tour_id',
                'tour_type',
                'tour_image',
                'tour_image_ar',
                'tour_bg_image',
                'tour_bg_image_ar',
                'tour_updated',
                'robots_index',
                'robots_follow',
                /* Both managed slugs, unresolved, so canonical and hreflang URLs
                   can name each locale's own slug. */
                'tour_slug AS seo_slug_en',
                'tour_slug_ar AS seo_slug_ar',
            ),
            $this->startingPriceColumns(),
            $this->guidePriceColumns(),
            array(
                $this->localizedColumn('tour_slug', $locale),
                $this->localizedColumn('tour_name', $locale),
                $this->localizedColumn('page_title', $locale),
                $this->localizedColumn('meta_description', $locale),
                $this->localizedColumn('meta_keywords', $locale),
                $this->localizedColumn('og_title', $locale),
                $this->localizedColumn('og_description', $locale),
                $this->localizedColumn('og_image', $locale),
                $this->localizedColumn('tour_short_description', $locale),
                $this->localizedColumn('tour_overview', $locale),
                $this->localizedColumn('tour_highlights', $locale),
                $this->localizedColumn('tour_itinerary', $locale),
                $this->localizedColumn('tour_pinfo', $locale),
                $this->localizedColumn('tour_gallery', $locale),
                $this->localizedColumn('tour_guide_info', $locale),
                $this->localizedColumn('tour_vehicle_text', $locale),
                $this->localizedColumn('tour_faqs', $locale),
                $this->localizedColumn('tour_duration', $locale),
                $this->localizedColumn('tour_lang', $locale),
                $this->localizedColumn('tour_group_size', $locale),
                $this->localizedColumn('tour_walking', $locale),
                $this->localizedColumn('tour_pickup', $locale),
                $this->localizedColumn('tour_price_details', $locale),
                $this->localizedColumn('tour_pinfo_duration', $locale),
                $this->localizedColumn('tour_pinfo_group_size', $locale),
                $this->localizedColumn('tour_pinfo_pickup', $locale),
                $this->localizedColumn('tour_pinfo_lang', $locale),
                $this->localizedColumn('tour_pinfo_weather', $locale),
                $this->localizedColumn('tour_pinfo_bring', $locale),
                $this->localizedColumn('tour_pinfo_access', $locale),
                $this->localizedColumn('tour_pinfo_departure_point', $locale),
                $this->localizedColumn('tour_pinfo_departure_time', $locale),
                $this->localizedColumn('tour_pinfo_transportation', $locale),
                $this->localizedColumn('tour_pinfo_meals', $locale),
                $this->localizedColumn('tour_pinfo_refreshment', $locale),
                $this->localizedColumn('tour_pinfo_return', $locale),
                $this->localizedColumn('tour_pinfo_family', $locale),
                'tour_faqs_general',
                'tour_faqs_specific',
            )
        )), false);
        $this->db->from('tours');
        $this->db->group_start();
        $this->db->where('tour_slug', $slug);
        $this->db->or_where('tour_slug_ar', $slug);
        $this->db->group_end();
        $this->db->where('tour_status', 'Enable');

        $row = $this->db->get()->row_array();

        return !empty($row)
            ? current($this->withStartingPrice(array($row), $row['tour_type']))
            : $row;
    }

    /** The category label for one tour, reusing the batched relation lookup. */
    public function get_tour_categories_for($tourId, $locale = 'en')
    {
        $relations = $this->getTourCategoryRelations(array($tourId), $locale);
        $categories = isset($relations['by_tour'][(int) $tourId]) ? $relations['by_tour'][(int) $tourId] : array();

        return isset($categories[0]['label']) ? $categories[0]['label'] : '';
    }

    /**
     * The language names offered by the guides assigned to one tour, reusing
     * the same batched relation lookup the Tours listing filters use — so a
     * detail page's language list always matches what its own card would
     * show.
     */
    public function get_tour_languages_for($tourId, $locale = 'en')
    {
        $relations = $this->getTourLanguageRelations(array($tourId), $locale);
        $languages = isset($relations['by_tour'][(int) $tourId]) ? $relations['by_tour'][(int) $tourId] : array();

        return array_column($languages, 'label');
    }

    /** Raw duration prices for the booking calculation, without starting-price aggregation. */
    public function get_booking_prices($tourId)
    {
        $columns = array();
        foreach (array(2, 4, 6, 8) as $hours) {
            foreach (array('tour_price_', 'tour_arabic_guide_price_', 'tour_english_guide_price_') as $prefix) {
                $columns[] = $prefix . $hours;
            }
        }
        return $this->db->select(implode(',', $columns))
            ->where('tour_id', (int) $tourId)
            ->get('tours')
            ->row_array();
    }

    /** Enabled visiting times assigned to a tour, in managed display order. */
    public function get_tour_slots($tourId, $locale = 'en')
    {
        $locale = $this->normalizeLocale($locale);
        $this->db->distinct();
        $this->db->select(implode(',', array(
            's.slot_id',
            's.slot_icon',
            's.slot_start_time',
            's.slot_end_time',
            's.slot_hours',
            's.slot_order',
            $this->localizedColumn('s.slot_name', $locale, 'slot_name'),
            $this->localizedColumn('s.slot_details', $locale, 'slot_details'),
        )), false);
        $this->db->from('tour_assigned_slots assigned');
        $this->db->join('tour_slots s', 's.slot_id = assigned.slot_id');
        $this->db->where('assigned.tour_id', (int) $tourId);
        $this->db->where('s.slot_status', 'Enable');
        $this->db->order_by('s.slot_order', 'ASC');
        $this->db->order_by('s.slot_id', 'ASC');

        return $this->db->get()->result_array();
    }

    /** Gallery photographs for a tour's detail page, in admin-defined order. */
    public function get_tour_images($tourId, $locale = 'en')
    {
        $locale = $this->normalizeLocale($locale);

        $this->db->select(implode(',', array(
            'image_id',
            'image_image',
            'image_desc',
            $this->localizedColumn('image_name', $locale),
        )), false);
        $this->db->from('tour_images');
        $this->db->where('image_tour_id', (int) $tourId);
        $this->db->where('image_status', 'Enable');
        $this->db->order_by('image_order', 'ASC');

        return $this->db->get()->result_array();
    }

    /**
     * Itinerary steps for the journey timeline, in admin-defined order, with
     * a fallback thumbnail/alt from the linked attraction when the step has
     * no image of its own.
     */
    public function get_tour_itinerary($tourId, $locale = 'en')
    {
        $locale = $this->normalizeLocale($locale);

        $this->db->select(implode(',', array(
            'ti.image',
            $this->localizedColumn('ti.day_hour', $locale, 'day_hour'),
            $this->localizedColumn('ti.title', $locale, 'title'),
            $this->localizedColumn('ti.details', $locale, 'details'),
            'a.attraction_image',
            $this->localizedColumn('a.attraction_name', $locale, 'attraction_name'),
        )), false);
        $this->db->from('tour_itineraries ti');
        $this->db->join('attractions a', 'a.attraction_id = ti.attraction_id', 'left');
        $this->db->where('ti.tour_id', (int) $tourId);
        $this->db->where('ti.status', 'Enable');
        $this->db->order_by('ti.itinerary_order', 'ASC');

        return $this->db->get()->result_array();
    }

    /** Highlighted attractions assigned to a tour, in admin-defined order. */
    public function get_tour_attractions($tourId, $locale = 'en')
    {
        $locale = $this->normalizeLocale($locale);

        $this->db->select(implode(',', array(
            'a.attraction_id',
            $this->localizedColumn('a.attraction_name', $locale, 'attraction_name'),
            $this->localizedColumn('a.attraction_short_desc', $locale, 'attraction_short_desc'),
            $this->localizedColumn('a.attraction_desc', $locale, 'attraction_desc'),
            'a.attraction_image',
        )), false);
        $this->db->from('tour_assigned_attractions taa');
        $this->db->join('attractions a', 'a.attraction_id = taa.attraction_id');
        $this->db->where('taa.tour_id', (int) $tourId);
        $this->db->where('a.attraction_status', 'Enable');
        $this->db->order_by('taa.order', 'ASC');

        return $this->db->get()->result_array();
    }

    /** Guides assigned to a tour, each with their spoken languages. */
    public function get_tour_guides($tourId, $locale = 'en')
    {
        $locale = $this->normalizeLocale($locale);
        $tourId = (int) $tourId;

        $this->db->select(implode(',', array(
            'tg.tour_guide_id',
            'tg.tour_guide_image',
            $this->localizedColumn('tg.tour_guide_name', $locale, 'tour_guide_name'),
            $this->localizedColumn('tg.tour_guide_title', $locale, 'tour_guide_title'),
            $this->localizedColumn('tg.tour_guide_desc', $locale, 'tour_guide_desc'),
        )), false);
        $this->db->from('tour_guide_assigned_tours tgat');
        $this->db->join('tour_guides tg', 'tg.tour_guide_id = tgat.tour_guide_id');
        $this->db->where('tgat.tour_id', $tourId);
        $this->db->where('tg.tour_guide_status', 'Enable');
        $this->db->order_by('tg.tour_guide_order', 'ASC');
        $guides = $this->db->get()->result_array();
        if (empty($guides)) {
            return array();
        }

        $guideIds = array_map('intval', array_column($guides, 'tour_guide_id'));
        $this->db->select(implode(',', array(
            'tgal.tour_guide_id',
            'tl.lang_id',
            'tl.lang_name AS source_name',
            $this->localizedColumn('tl.lang_name', $locale, 'lang_name'),
        )), false);
        $this->db->from('tour_guide_assigned_languages tgal');
        $this->db->join('tour_languages tl', 'tl.lang_id = tgal.lang_id');
        $this->db->where_in('tgal.tour_guide_id', $guideIds);
        $this->db->where('tl.lang_status', 'Enable');
        $this->db->order_by('tl.lang_order', 'ASC');
        $languageRows = $this->db->get()->result_array();

        $languagesByGuide = array();
        $bookingLanguagesByGuide = array();
        foreach ($languageRows as $row) {
            $languagesByGuide[(int) $row['tour_guide_id']][] = $row['lang_name'];
            $bookingLanguagesByGuide[(int) $row['tour_guide_id']][] = $row;
        }

        foreach ($guides as &$guide) {
            $guideId = (int) $guide['tour_guide_id'];
            $guide['booking_languages'] = isset($bookingLanguagesByGuide[$guideId])
                ? $bookingLanguagesByGuide[$guideId]
                : array();
            $guide['languages'] = isset($languagesByGuide[$guideId]) ? $languagesByGuide[$guideId] : array();
        }
        unset($guide);

        return $guides;
    }

    /** Available date/slot pairs for the tour's enabled assigned guides. */
    public function get_booking_availability(array $guideIds, $minimumDate, $maximumDate)
    {
        if (empty($guideIds)) {
            return array();
        }
        $this->db->distinct();
        $this->db->select('a.avail_date, a.avail_slot_id, a.avail_tour_guide_id');
        $this->db->from('tour_guide_availability a');
        $this->db->join('tour_guides g', 'g.tour_guide_id = a.avail_tour_guide_id');
        $this->db->where_in('g.tour_guide_id', array_map('intval', $guideIds));
        $this->db->where('g.tour_guide_status', 'Enable');
        $this->db->where('a.avail_status', 'Enable');
        $this->db->where('a.avail_book_status', 'Available');
        $this->db->where('a.avail_date >=', $minimumDate);
        $this->db->where('a.avail_date <=', $maximumDate);
        $this->db->order_by('a.avail_date', 'ASC');

        return $this->db->get()->result_array();
    }

    /** Enabled vehicles assigned to a tour, in vehicle-catalogue order. */
    public function get_tour_vehicles($tourId, $locale = 'en')
    {
        $locale = $this->normalizeLocale($locale);

        $this->db->select(implode(',', array(
            'v.vehicle_id',
            'v.vehicle_image',
            'v.vehicle_max_capacity',
            'v.vehicle_price_2',
            'v.vehicle_price_4',
            'v.vehicle_price_6',
            'v.vehicle_price_8',
            'v.vehicle_meals_2',
            'v.vehicle_meals_4',
            'v.vehicle_meals_6',
            'v.vehicle_meals_8',
            $this->localizedColumn('v.vehicle_name', $locale, 'vehicle_name'),
            $this->localizedColumn('v.vehicle_details', $locale, 'vehicle_details'),
        )), false);
        $this->db->from('tour_assigned_vehicles av');
        $this->db->join('vehicles v', 'v.vehicle_id = av.vehicle_id');
        $this->db->where('av.tour_id', (int) $tourId);
        $this->db->where('v.vehicle_status', 'Enable');
        $this->db->order_by('v.vehicle_order', 'ASC');

        return $this->db->get()->result_array();
    }

    /** Enabled FAQs from the general and/or tour-specific FAQ categories. */
    public function get_faqs_for_tour($generalCatId, $specificCatId, $locale = 'en')
    {
        $locale = $this->normalizeLocale($locale);
        $catIds = array_values(array_unique(array_filter(array_map('intval', array($generalCatId, $specificCatId)))));
        if (empty($catIds)) {
            return array();
        }

        $this->db->select(implode(',', array(
            $this->localizedColumn('faq_question', $locale),
            $this->localizedColumn('faq_answer', $locale),
        )), false);
        $this->db->from('faqs');
        $this->db->where_in('faq_cat_id', $catIds);
        $this->db->where('faq_status', 'Enable');
        $this->db->order_by('faq_order', 'ASC');

        return $this->db->get()->result_array();
    }

    private function getTourCategoryRelations(array $tourIds, $locale)
    {
        $locale = $this->normalizeLocale($locale);
        $tourIds = $this->normalizeIds($tourIds);
        $result = array('by_tour' => array(), 'options' => array());
        if (empty($tourIds)) {
            return $result;
        }

        $this->db->select(implode(',', array(
            'tac.tour_id',
            'tc.cat_id',
            $this->localizedColumn('tc.cat_name', $locale, 'cat_name'),
        )), false);
        $this->db->from('tour_assigned_categories tac');
        $this->db->join('tour_categories tc', 'tc.cat_id = tac.cat_id');
        $this->db->where('tc.cat_status', 'Enable');
        $this->db->where_in('tac.tour_id', $tourIds);
        $this->db->order_by('tc.cat_order', 'ASC');
        $this->db->order_by('tc.cat_id', 'ASC');

        foreach ($this->db->get()->result_array() as $row) {
            $tourId = (int) $row['tour_id'];
            $categoryId = (string) (int) $row['cat_id'];
            $category = array(
                'id' => $categoryId,
                'label' => $row['cat_name'],
            );
            $result['by_tour'][$tourId][] = $category;
            $result['options'][$categoryId] = $category;
        }

        return $result;
    }

    private function getTourLanguageRelations(array $tourIds, $locale)
    {
        $locale = $this->normalizeLocale($locale);
        $tourIds = $this->normalizeIds($tourIds);
        $result = array('by_tour' => array(), 'options' => array());
        if (empty($tourIds)) {
            return $result;
        }

        $this->db->distinct();
        $this->db->select(implode(',', array(
            'tgat.tour_id',
            'tl.lang_id',
            $this->localizedColumn('tl.lang_name', $locale, 'lang_name'),
        )), false);
        $this->db->from('tour_guide_assigned_tours tgat');
        $this->db->join(
            'tour_guides tg',
            'tg.tour_guide_id = tgat.tour_guide_id'
        );
        $this->db->join(
            'tour_guide_assigned_languages tgal',
            'tgal.tour_guide_id = tg.tour_guide_id'
        );
        $this->db->join('tour_languages tl', 'tl.lang_id = tgal.lang_id');
        $this->db->where('tg.tour_guide_status', 'Enable');
        $this->db->where('tl.lang_status', 'Enable');
        $this->db->where_in('tgat.tour_id', $tourIds);
        $this->db->order_by('tl.lang_order', 'ASC');
        $this->db->order_by('tl.lang_id', 'ASC');

        foreach ($this->db->get()->result_array() as $row) {
            $tourId = (int) $row['tour_id'];
            $languageId = (string) (int) $row['lang_id'];
            $language = array(
                'id' => $languageId,
                'label' => $row['lang_name'],
            );
            $result['by_tour'][$tourId][] = $language;
            $result['options'][$languageId] = $language;
        }

        return $result;
    }

    private function normalizeIds(array $ids)
    {
        return array_values(array_unique(array_filter(array_map(
            'intval',
            $ids
        ))));
    }

    /** Extract the public guest count from a managed value such as "Up to 15 guests". */
    private function extractCapacity($groupSize)
    {
        if (preg_match('/\d+/', (string) $groupSize, $matches) !== 1) {
            return 0;
        }

        return (int) $matches[0];
    }

    private function attachCategories(array $rows, array $categoryData)
    {
        foreach ($rows as &$row) {
            $tourId = isset($row['tour_id']) ? (int) $row['tour_id'] : 0;
            $categories = isset($categoryData['by_tour'][$tourId])
                ? $categoryData['by_tour'][$tourId]
                : array();

            $row['category_name'] = isset($categories[0]['label'])
                ? $categories[0]['label']
                : '';
            $row['category_ids'] = array_column($categories, 'id');
            $row['category_names'] = array_column($categories, 'label');
        }
        unset($row);

        return $rows;
    }

    /** The duration-based tour price columns used by the public frontend. */
    private function startingPriceColumns()
    {
        return array(
            'tour_price_2',
            'tour_price_4',
            'tour_price_6',
            'tour_price_8',
        );
    }

    /** The guide price columns added to Tours but excluded from Experiences. */
    private function guidePriceColumns()
    {
        return array(
            'tour_arabic_guide_price_2',
            'tour_arabic_guide_price_4',
            'tour_arabic_guide_price_6',
            'tour_arabic_guide_price_8',
        );
    }

    /**
     * Calculate the lowest positive duration price and add the matching
     * enabled vehicle price and its meals charge once. The tour price and
     * meals are per person, so this is the price for one guest; profit and
     * tax from Website Settings are then added the same way as a booking.
     */
    private function withStartingPrice(array $rows, $type)
    {
        $this->load->library('tour_pricing');
        $durations = array(2, 4, 6, 8);
        $vehiclePrices = $this->getAssignedVehiclePrices(array_column(
            $rows,
            'tour_id'
        ));

        foreach ($rows as &$row) {
            $tourId = (int) $row['tour_id'];
            $lowestPrice = 0;
            $priceKey = 0;

            foreach ($durations as $duration) {
                $tourPrice = (int) $row['tour_price_' . $duration];
                if ($tourPrice <= 0) {
                    continue;
                }

                $guidePrice = $type === 'Experience'
                    ? 0
                    : (int) $row['tour_arabic_guide_price_' . $duration];
                $totalPrice = $tourPrice + $guidePrice;

                if ($lowestPrice === 0 || $totalPrice < $lowestPrice) {
                    $lowestPrice = $totalPrice;
                    $priceKey = $duration;
                }
            }

            if (
                $priceKey > 0
                && isset($vehiclePrices[$tourId][$priceKey])
            ) {
                $lowestPrice += $vehiclePrices[$tourId][$priceKey]['price'];
                $lowestPrice += $vehiclePrices[$tourId][$priceKey]['meals'];
            }

            $row['tour_price'] = $lowestPrice > 0
                ? $this->tour_pricing->grossPrice($lowestPrice)
                : 0;
            foreach ($durations as $duration) {
                unset($row['tour_price_' . $duration]);
                unset($row['tour_arabic_guide_price_' . $duration]);
            }
        }
        unset($row);

        return $rows;
    }

    /**
     * Find the lowest positive price per duration among enabled vehicles.
     * The meals charge remains tied to the vehicle that supplied that price.
     */
    private function getAssignedVehiclePrices(array $tourIds)
    {
        $tourIds = $this->normalizeIds($tourIds);
        $prices = array();
        if (empty($tourIds)) {
            return $prices;
        }

        $this->db->select(implode(',', array(
            'av.tour_id',
            'v.vehicle_id',
            'v.vehicle_price_2',
            'v.vehicle_price_4',
            'v.vehicle_price_6',
            'v.vehicle_price_8',
            'v.vehicle_meals_2',
            'v.vehicle_meals_4',
            'v.vehicle_meals_6',
            'v.vehicle_meals_8',
        )));
        $this->db->from('tour_assigned_vehicles av');
        $this->db->join('vehicles v', 'v.vehicle_id = av.vehicle_id');
        $this->db->where('v.vehicle_status', 'Enable');
        $this->db->where_in('av.tour_id', $tourIds);
        $this->db->order_by('av.tour_id', 'ASC');
        $this->db->order_by('v.vehicle_id', 'ASC');

        foreach ($this->db->get()->result_array() as $vehicle) {
            $tourId = (int) $vehicle['tour_id'];

            foreach (array(2, 4, 6, 8) as $duration) {
                $price = (int) $vehicle['vehicle_price_' . $duration];
                if ($price <= 0) {
                    continue;
                }

                if (
                    !isset($prices[$tourId][$duration])
                    || $price < $prices[$tourId][$duration]['price']
                ) {
                    $prices[$tourId][$duration] = array(
                        'price' => $price,
                        'meals' => max(
                            0,
                            (int) $vehicle['vehicle_meals_' . $duration]
                        ),
                    );
                }
            }
        }

        return $prices;
    }
}
