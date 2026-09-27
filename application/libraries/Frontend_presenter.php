<?php defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Converts frontend records into stable view models.
 *
 * This class intentionally contains no request, response, session, or database
 * work, so it can be reused by any public-facing controller.
 */
class Frontend_presenter
{
    private $CI;

    public function __construct()
    {
        $this->CI =& get_instance();
    }

    public function text(array $row, $field)
    {
        $value = isset($row[$field]) ? trim((string) $row[$field]) : '';

        return $value !== ''
            ? html_entity_decode($value, ENT_QUOTES, 'UTF-8')
            : '';
    }

    public function plainText(array $row, $field)
    {
        $value = isset($row[$field]) ? (string) $row[$field] : '';
        $value = html_entity_decode(strip_tags($value), ENT_QUOTES, 'UTF-8');
        $value = preg_replace('/\s+/u', ' ', $value);

        return is_string($value) ? trim($value) : '';
    }

    /** Return the localized menu name stored for a published CMS page. */
    public function menuLabel($pageId)
    {
        $pages = $this->CI->config->item('frontend_pages');
        $pageId = (int) $pageId;
        if (!is_array($pages) || !isset($pages[$pageId])) {
            return '';
        }

        $field = defined('FRONTEND_LOCALE') && FRONTEND_LOCALE === 'ar'
            ? 'menu_name_ar'
            : 'menu_name';

        return $this->text($pages[$pageId], $field);
    }

    public function line($key, array $params = array())
    {
        $line = $this->CI->lang->line($key, false);
        if (!is_string($line)) {
            $line = $key;
        }

        foreach ($params as $name => $value) {
            if (is_scalar($value) || $value === null) {
                $line = str_replace('{' . $name . '}', (string) $value, $line);
            }
        }

        return $line;
    }

    /**
     * $heroSlides are the home page's slider rows; their images are the last
     * sharing-image fallback before the site-wide default.
     */
    public function homeConfig(array $page, array $heroSlides = array())
    {
        $config = array(
            'page_title' => $this->line('home.meta.title'),
            'meta_description' => $this->line('home.meta.description'),
            'active' => 'home',
            'select2' => true,
            'datepicker' => true,
            'sweetalert2' => true,
            'styles' => array('vendor/swiper/swiper-bundle.min.css'),
            'scripts' => array(
                'vendor/swiper/swiper-bundle.min.js',
                'js/hero-slider.js',
                'js/hero-search.js',
            ),
        );

        $images = array(
            array('pages', isset($page['banner_background']) ? $page['banner_background'] : ''),
        );
        foreach ($heroSlides as $slide) {
            $images[] = array('slider', isset($slide['image']) ? $slide['image'] : '');
        }

        return $this->pageConfig($page, $config, 'pages', array('images' => $images));
    }

    public function managedPageConfig(
        array $page,
        $locale,
        $active,
        $breadcrumbPageId,
        $bannerSize = 'default',
        array $options = array()
    ) {
        /* 'seo_images' are further sharing-image fallbacks (see pageConfig),
           tried after the page's own banner. They are not page config, so
           they are taken out before the merge. */
        $seoImages = isset($options['seo_images']) && is_array($options['seo_images'])
            ? $options['seo_images']
            : array();
        unset($options['seo_images']);

        $images = array(
            array('pages', isset($page['banner_background']) ? $page['banner_background'] : ''),
        );

        $config = $this->pageConfig($page, array_merge(array(
            'page_title' => $this->text($page, 'page_name'),
            'meta_description' => '',
            'active' => $active,
        ), $options), 'pages', array(
            'name' => $this->text($page, 'page_name'),
            'description' => array(
                isset($page['banner_text']) ? $page['banner_text'] : '',
                isset($page['page_text']) ? $page['page_text'] : '',
            ),
            'images' => array_merge($images, $seoImages),
        ));

        $crumbs = array(
            array(
                'label' => $this->menuLabel(1),
                'href' => base_url($locale === 'ar' ? 'ar' : 'en'),
            ),
            array('label' => $this->menuLabel($breadcrumbPageId)),
        );

        return $this->managedBanner($config, $page, $bannerSize, $crumbs);
    }

    public function managedBanner(
        array $config,
        array $row,
        $bannerSize,
        array $crumbs,
        $imageDir = 'pages'
    ) {
        if (!isset($row['show_top_banner']) || (int) $row['show_top_banner'] !== 1) {
            return $config;
        }

        $banner = array(
            'eyebrow' => $this->text($row, 'banner_title'),
            'title' => $this->text($row, 'banner_heading'),
            'text' => $this->text($row, 'banner_text'),
            'image' => upload_thumb(
                $imageDir,
                isset($row['banner_background']) ? $row['banner_background'] : null,
                1920,
                720,
                ''
            ),
            'overlay' => isset($row['banner_overlay']) && $row['banner_overlay'] === 'Yes',
            'background_colors' => array(
                isset($row['banner_background_color_1']) ? $row['banner_background_color_1'] : '',
                isset($row['banner_background_color_2']) ? $row['banner_background_color_2'] : '',
            ),
            'eyebrow_colors' => array(
                isset($row['banner_title_color_1']) ? $row['banner_title_color_1'] : '',
                isset($row['banner_title_color_2']) ? $row['banner_title_color_2'] : '',
            ),
            'heading_colors' => array(
                isset($row['banner_heading_color_1']) ? $row['banner_heading_color_1'] : '',
                isset($row['banner_heading_color_2']) ? $row['banner_heading_color_2'] : '',
            ),
            'text_colors' => array(
                isset($row['banner_text_color_1']) ? $row['banner_text_color_1'] : '',
                isset($row['banner_text_color_2']) ? $row['banner_text_color_2'] : '',
            ),
            'size' => $bannerSize,
            'crumbs' => $crumbs,
        );

        if ($banner['eyebrow'] !== '' || $banner['title'] !== ''
            || $banner['text'] !== '' || $banner['image'] !== ''
        ) {
            $config['banner'] = $banner;
        }

        return $config;
    }

    /**
     * Applies a record's managed search and sharing fields to a page config.
     *
     * $seo takes the fallback options documented on Frontend_seo::entityConfig():
     * the record name, body content for a blank meta description, and further
     * images to try when the record has no sharing image of its own.
     */
    public function pageConfig(array $page, array $config, $ogImageDir = 'pages', array $seo = array())
    {
        return $this->CI->frontend_seo->entityConfig(
            $page,
            $config,
            array_merge(array('image_dir' => $ogImageDir), $seo)
        );
    }

    public function tourCards(array $rows, $fallbackImage = '', $useFallbackContent = true)
    {
        $items = array();
        foreach ($rows as $row) {
            $imageCandidates = defined('FRONTEND_LOCALE') && FRONTEND_LOCALE === 'ar'
                ? array(
                    isset($row['tour_image_ar']) ? $row['tour_image_ar'] : null,
                    isset($row['tour_image']) ? $row['tour_image'] : null,
                )
                : array(isset($row['tour_image']) ? $row['tour_image'] : null);
            $category = $this->text($row, 'category_name');
            if ($category === '' && $useFallbackContent) {
                $category = $this->line('tour.category.uncategorized');
            }

            $categoryIds = isset($row['category_ids']) && is_array($row['category_ids'])
                ? array_values(array_map('strval', $row['category_ids']))
                : array();
            $categoryNames = isset($row['category_names']) && is_array($row['category_names'])
                ? array_values(array_filter(array_map('strval', $row['category_names'])))
                : array();
            if (empty($categoryNames) && $category !== '') {
                $categoryNames[] = $category;
            }
            $languageIds = isset($row['language_ids']) && is_array($row['language_ids'])
                ? array_values(array_map('strval', $row['language_ids']))
                : array();
            $languageNames = isset($row['language_names']) && is_array($row['language_names'])
                ? array_values(array_filter(array_map('strval', $row['language_names'])))
                : array();

            $items[] = array(
                'slug' => isset($row['tour_slug']) ? (string) $row['tour_slug'] : '',
                'title' => $this->text($row, 'tour_name'),
                'category' => $category,
                'category_key' => isset($categoryIds[0]) ? $categoryIds[0] : $category,
                'category_keys' => $categoryIds,
                'categories' => $categoryNames,
                'image' => upload_thumb_candidates(
                    'tours',
                    $imageCandidates,
                    960,
                    640,
                    $fallbackImage
                ),
                'summary' => $this->text($row, 'tour_short_description'),
                'duration' => $this->text($row, 'tour_duration'),
                'capacity' => $this->text($row, 'tour_group_size'),
                'filter_capacity' => isset($row['max_capacity']) ? (int) $row['max_capacity'] : 0,
                'language_ids' => $languageIds,
                'language_label' => !empty($languageNames)
                    ? implode($this->line('format.listSeparator'), $languageNames)
                    : '',
                'price' => isset($row['tour_price']) ? (int) $row['tour_price'] : 0,
            );
        }

        return $items;
    }

    public function experienceCards(array $rows, $fallbackImage = '')
    {
        $items = array();
        foreach ($rows as $row) {
            $imageCandidates = defined('FRONTEND_LOCALE') && FRONTEND_LOCALE === 'ar'
                ? array(
                    isset($row['tour_image_ar']) ? $row['tour_image_ar'] : null,
                    isset($row['tour_image']) ? $row['tour_image'] : null,
                )
                : array(isset($row['tour_image']) ? $row['tour_image'] : null);
            $category = $this->text($row, 'category_name');
            if ($category === '') {
                $category = $this->line('tour.category.uncategorized');
            }
            $categoryIds = isset($row['category_ids']) && is_array($row['category_ids'])
                ? array_values(array_map('strval', $row['category_ids']))
                : array();
            $categoryNames = isset($row['category_names']) && is_array($row['category_names'])
                ? array_values(array_filter(array_map('strval', $row['category_names'])))
                : array();
            if (empty($categoryNames) && $category !== '') {
                $categoryNames[] = $category;
            }

            $items[] = array(
                'slug' => isset($row['tour_slug']) ? (string) $row['tour_slug'] : '',
                'name' => $this->text($row, 'tour_name'),
                'category' => $category,
                'category_ids' => $categoryIds,
                'categories' => $categoryNames,
                'short_description' => $this->text($row, 'tour_short_description'),
                'image' => upload_thumb_candidates(
                    'tours',
                    $imageCandidates,
                    960,
                    640,
                    $fallbackImage
                ),
                'duration' => $this->text($row, 'tour_duration'),
                'language' => $this->text($row, 'tour_lang'),
                'group' => $this->text($row, 'tour_group_size'),
                'price' => isset($row['tour_price']) ? (int) $row['tour_price'] : 0,
            );
        }

        return $items;
    }

    public function guideCards(array $rows, $fallbackImage = '')
    {
        $items = array();
        foreach ($rows as $row) {
            $name = $this->text($row, 'tour_guide_name');
            $items[] = array(
                'image' => upload_thumb(
                    'tour-guides',
                    isset($row['tour_guide_image']) ? $row['tour_guide_image'] : null,
                    0,
                    0,
                    $fallbackImage
                ),
                'image_alt' => $name,
                'title' => $this->text($row, 'tour_guide_title'),
                'name' => $name,
                'bio' => $this->text($row, 'tour_guide_desc'),
                'expertise' => array(),
                'languages' => isset($row['languages']) && is_array($row['languages'])
                    ? $row['languages']
                    : array(),
                'language_ids' => isset($row['language_ids']) && is_array($row['language_ids'])
                    ? array_values(array_map('strval', $row['language_ids']))
                    : array(),
                'tours' => array_fill(
                    0,
                    isset($row['tour_count']) ? (int) $row['tour_count'] : 0,
                    true
                ),
            );
        }

        return $items;
    }

    public function testimonials(array $rows)
    {
        $items = array();
        foreach ($rows as $row) {
            $items[] = array(
                'quote' => $this->text($row, 'review_desc'),
                'name' => $this->text($row, 'review_name'),
                'meta' => trim(isset($row['review_caption']) ? (string) $row['review_caption'] : ''),
            );
        }

        return $items;
    }

    public function faqs(array $rows)
    {
        $items = array();
        foreach ($rows as $row) {
            $items[] = array(
                'q' => $this->text($row, 'faq_question'),
                'a' => $this->text($row, 'faq_answer'),
            );
        }

        return $items;
    }

    public function homePosts(array $rows)
    {
        $items = array();
        foreach ($rows as $row) {
            $date = $this->blogDate($row);
            $category = $this->text($row, 'category_name');
            if ($category === '') {
                $category = $this->line('blog.category.uncategorized');
            }

            $items[] = array(
                'slug' => isset($row['blog_slug']) ? (string) $row['blog_slug'] : '',
                'title' => $this->text($row, 'blog_name'),
                'category' => $category,
                'read' => $this->line(
                    'post.readTime',
                    array('minutes' => isset($row['blog_time_to_read']) ? (int) $row['blog_time_to_read'] : 0)
                ),
                'image' => upload_thumb_candidates(
                    'blogs',
                    array(
                        isset($row['blog_image']) ? $row['blog_image'] : null,
                        isset($row['blog_cover_image']) ? $row['blog_cover_image'] : null,
                    ),
                    1200,
                    675,
                    ''
                ),
                'excerpt' => $this->text($row, 'blog_short_description'),
                'date' => substr($date, 0, 10),
                'categories' => isset($row['categories']) && is_array($row['categories'])
                    ? $row['categories']
                    : array(),
            );
        }

        return $items;
    }

    public function sections(
        array $rows,
        array $groupPrefixes = array(),
        array $groupLimits = array(),
        array $darkSections = array()
    ) {
        $sections = array();
        $backgroundIndex = 0;

        foreach ($rows as $key => $row) {
            if (!is_array($row)) {
                continue;
            }

            if (in_array($key, $darkSections, true)) {
                $background = 'bg-alam-800';
                $backgroundIndex = 0;
            } else {
                $background = $backgroundIndex % 2 === 0 ? 'bg-white' : 'bg-surface-tint';
                $backgroundIndex++;
            }

            $sections[$key] = array(
                'key' => $key,
                'head' => array(
                    'eyebrow' => $this->text($row, 'pre_heading'),
                    'title' => $this->text($row, 'heading'),
                    'text' => $this->plainText($row, 'contents'),
                ),
                'contents_html' => isset($row['contents']) ? trim((string) $row['contents']) : '',
                'contents_text' => $this->plainText($row, 'contents'),
                'image' => isset($row['image']) ? trim((string) $row['image']) : '',
                'cards' => $this->sectionGroups(
                    $row,
                    isset($groupLimits[$key]) ? (int) $groupLimits[$key] : 6,
                    isset($groupPrefixes[$key]) ? $groupPrefixes[$key] : 'card'
                ),
                'card' => $this->singleSectionCard($row),
                'steps' => $this->sectionGroups($row, 6, 'step'),
                'buttons' => array(
                    'button' => $this->sectionButton($row, 'button'),
                    'button_1' => $this->sectionButton($row, 'button_1'),
                    'button_2' => $this->sectionButton($row, 'button_2'),
                ),
                'background' => $background,
            );
        }

        return $sections;
    }

    private function sectionGroups(array $row, $max, $prefix)
    {
        $groups = array();
        for ($index = 1; $index <= (int) $max; $index++) {
            $heading = $this->text($row, $prefix . '_' . $index . '_heading');
            $text = $this->text($row, $prefix . '_' . $index . '_text');
            if ($heading === '' && $text === '') {
                continue;
            }

            $groups[] = array(
                'n' => count($groups) + 1,
                'icon' => trim(isset($row[$prefix . '_' . $index . '_icon'])
                    ? (string) $row[$prefix . '_' . $index . '_icon']
                    : ''),
                'title' => $heading,
                'text' => $text,
            );
        }

        return $groups;
    }

    private function singleSectionCard(array $row)
    {
        $heading = $this->text($row, 'card_heading');
        $text = $this->text($row, 'card_text');
        if ($heading === '' && $text === '') {
            return array();
        }

        return array(
            'icon' => trim(isset($row['card_icon']) ? (string) $row['card_icon'] : ''),
            'title' => $heading,
            'text' => $text,
        );
    }

    private function sectionButton(array $row, $prefix)
    {
        return array(
            'text' => $this->text($row, $prefix . '_text'),
            'url' => trim(isset($row[$prefix . '_url']) ? (string) $row[$prefix . '_url'] : ''),
            'icon' => trim(isset($row[$prefix . '_icon']) ? (string) $row[$prefix . '_icon'] : ''),
            'icon_pos' => trim(isset($row[$prefix . '_icon_pos']) ? (string) $row[$prefix . '_icon_pos'] : ''),
        );
    }

    private function blogDate(array $row)
    {
        $date = trim(isset($row['blog_pdate']) ? (string) $row['blog_pdate'] : '');
        if ($date === '' || strpos($date, '0000-00-00') === 0) {
            $date = isset($row['blog_added']) ? (string) $row['blog_added'] : '';
        }

        return substr($date, 0, 10);
    }
}
