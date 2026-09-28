<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Public website.
 *
 * Pages render through Frontend_layout (shared head, header and footer).
 * The home page is built in Phase 5 as an empty shell; the other pages of
 * the site map arrive with their CMS data in Phase 6 of PROJECT_PLAN.md,
 * until then their URLs return the 404 page.
 *
 * This controller is also the $route['404_override'] target, so unmatched
 * /manage URLs still get the admin-styled 404 page.
 */
class Frontend extends CI_Controller
{
    public function __construct()
    {
        parent::__construct();

        // $route['404_override'] also catches unmatched URLs under /manage.
        // Those belong to the admin area, so they get the admin 404 page.
        if ($this->uri->rsegment(2) === 'error_404'
            && strtolower(trim((string) $this->uri->segment(1))) === 'manage'
        ) {
            $this->renderManageNotFound();
        }

        $this->load->library('frontend_layout');

        if ($this->frontend_layout->isUnderConstruction() && !$this->isAdministrator()) {
            $this->renderUnderConstruction();
        }
    }

    /** Home page (/). */
    public function index()
    {
        $this->load->model(array(
            'Webpage_model',
            'Service_model',
            'Gallery_model',
            'Artist_model',
            'Offer_model',
            'Review_model',
        ));
        $this->load->library('content_section_service');

        $page = $this->Webpage_model->get_page('id', 1, false, true);
        $page = is_array($page) ? $page : array();
        $misc = $this->content_section_service->get_miscellaneous_contents();
        $finishes = isset($misc['nail_shapes_finishes']['finishes'])
            ? frontend_split_lines($misc['nail_shapes_finishes']['finishes'])
            : array();

        $artists = $this->Artist_model->get_all();
        $lead = !empty($artists) ? array_shift($artists) : NULL;

        $this->frontend_layout->render('frontend/home', array(
            'slides' => $this->heroSlides(isset($page['page_slider']) ? (int) $page['page_slider'] : 0),
            'sections' => $this->content_section_service->get_web_page_sections(1),
            'services' => $this->Service_model->get_featured(4),
            'finishes' => $finishes,
            'gallery' => $this->Gallery_model->get_featured(6),
            'lead' => $lead,
            'team' => $artists,
            'offers' => $this->Offer_model->get_public(TRUE, 3),
            'reviews' => $this->Review_model->get_reviews(3),
        ), array(
            'meta' => $this->frontend_layout->pageMeta($page, base_url()),
            'vendors' => array('swiper'),
            'scripts' => array('js/home.js'),
        ));
    }

    /** Services listing (/services), optionally filtered with ?category={slug}. */
    public function services()
    {
        $page = $this->listingPage('services');
        $this->load->model('Service_model');
        $this->load->library('content_section_service');

        $menu = $this->Service_model->get_menu();
        $requested = (string) $this->input->get('category');
        $category = in_array($requested, array_column($menu, 'category_slug'), TRUE) ? $requested : 'all';

        $this->frontend_layout->render('frontend/services', array(
            'hero' => $this->listingHero($page, array(
                array('label' => 'Book an appointment', 'url' => base_url('book')),
            )),
            'sections' => $this->content_section_service->get_web_page_sections((int) $page['page_id']),
            // "Nail services": the first four services of the first category.
            'featured' => !empty($menu) ? array_slice($menu[0]['services'], 0, 4) : array(),
            'menu' => $menu,
            'category' => $category,
        ), array(
            'meta' => $this->frontend_layout->pageMeta($page, base_url('services'), array(
                'images' => array(array('pages', $page['banner_background'])),
            )),
            'scripts' => array('js/services.js'),
        ));
    }

    /** Service detail (/services/{slug}). */
    public function service($slug = '')
    {
        $this->load->model('Service_model');
        $this->load->library('content_section_service');

        $service = $this->Service_model->get_by_slug(urldecode((string) $slug));
        if ($service === NULL) {
            return $this->error_404();
        }

        $serviceId = (int) $service['service_id'];
        $misc = $this->content_section_service->get_miscellaneous_contents();
        $labels = isset($misc['service_page']) ? $misc['service_page'] : array();
        $url = base_url('services/'.rawurlencode($service['service_slug']));
        $bookUrl = base_url('book').'?service='.rawurlencode($service['service_slug']);
        $price = frontend_service_price($service);
        $heroImage = $service['service_hero_image'] !== '' && $service['service_hero_image'] !== NULL
            ? $service['service_hero_image']
            : $service['service_card_image'];

        $facts = array();
        if ($price !== '') {
            $facts[] = array('label' => 'Price', 'value' => $price, 'icon' => '');
        }
        if (trim((string) $service['service_duration_label']) !== '') {
            $facts[] = array(
                'label' => 'Time needed',
                'value' => $service['service_duration_label'],
                'icon' => 'fa-regular fa-clock',
            );
        }

        $ctaHeading = isset($labels['cta_heading']) && trim($labels['cta_heading']) !== ''
            ? $labels['cta_heading']
            : 'Ready to book your {service}?';

        $this->frontend_layout->render('frontend/service', array(
            'service' => $service,
            'hero' => array(
                'crumbs' => array(
                    array('label' => 'Home', 'url' => base_url()),
                    array('label' => $this->listingLabel('services', 'Services'), 'url' => base_url('services')),
                    array('label' => $service['service_name']),
                ),
                'label' => $service['category_name'],
                'heading' => $service['service_name'],
                'lead' => $this->frontend_seo->plainText($service['service_description']),
                'actions' => array(
                    array('label' => 'Book '.mb_strtolower($service['service_name'], 'UTF-8'), 'url' => $bookUrl),
                    array('label' => 'See the gallery', 'url' => base_url('gallery'), 'variant' => 'outline'),
                ),
                'facts' => $facts,
                'image' => array(
                    'url' => upload_thumb('services', $heroImage, 1200, 0, 'images/no_image.jpg'),
                    'alt' => $service['service_name'],
                ),
            ),
            'addons' => $this->Service_model->get_addons($serviceId),
            'gallery' => $this->Service_model->get_gallery($serviceId),
            'artists' => $this->Service_model->get_artists($serviceId),
            'related' => $this->Service_model->get_related($serviceId),
            'labels' => $labels,
            'shapes' => (int) $service['service_show_shapes'] === 1 && isset($misc['nail_shapes_finishes'])
                ? $misc['nail_shapes_finishes']
                : NULL,
            'cta' => array(
                'heading' => str_replace('{service}', mb_strtolower($service['service_name'], 'UTF-8'), $ctaHeading),
                'text' => isset($labels['cta_text']) ? $labels['cta_text'] : '',
                'book_url' => $bookUrl,
                'book_label' => 'Book this service',
            ),
            'schema' => $this->serviceSchema($service, $url),
        ), array(
            'meta' => $this->frontend_layout->pageMeta($service, $url, array(
                'name' => $service['service_name'],
                'description' => array($service['service_summary'], $service['service_description']),
                'image_dir' => 'services',
                'images' => array(array('services', $heroImage)),
            )),
        ));
    }

    /** Gallery (/gallery), optionally filtered with ?category={slug}. */
    public function gallery()
    {
        $page = $this->listingPage('gallery');
        $this->load->model('Gallery_model');
        $this->load->library('content_section_service');

        $images = $this->Gallery_model->get_all();
        foreach ($images as &$image) {
            // The lightbox opens the original upload at its real size.
            $file = basename((string) $image['image_file']);
            $relative = 'assets/frontend/images/gallery/'.$file;
            $size = $file !== '' ? @getimagesize(FCPATH.$relative) : FALSE;
            $image['full'] = array(
                'url' => is_array($size) ? base_url($relative) : upload_thumb('gallery', $file, 1600, 0, 'images/no_image.jpg'),
                'width' => is_array($size) ? (int) $size[0] : 1600,
                'height' => is_array($size) ? (int) $size[1] : 1200,
            );
        }
        unset($image);

        $categories = $this->Gallery_model->get_categories();
        $requested = (string) $this->input->get('category');
        $category = in_array($requested, array_column($categories, 'category_slug'), TRUE) ? $requested : 'all';

        $this->frontend_layout->render('frontend/gallery', array(
            'page' => $page,
            'sections' => $this->content_section_service->get_web_page_sections((int) $page['page_id']),
            'images' => $images,
            'categories' => $categories,
            'category' => $category,
        ), array(
            'meta' => $this->frontend_layout->pageMeta($page, base_url('gallery'), array(
                'images' => array(array('pages', $page['banner_background'])),
            )),
            'vendors' => array('photoswipe'),
            'scripts' => array('js/category-filter.js', 'js/gallery.js'),
            'header' => 'overlay',
        ));
    }

    /** Offers (/offers): featured offers first, then the rest. */
    public function offers()
    {
        $page = $this->listingPage('offers');
        $this->load->model('Offer_model');
        $this->load->library('content_section_service');

        $featured = array();
        $others = array();
        foreach ($this->Offer_model->get_public(FALSE) as $offer) {
            if ((int) $offer['offer_featured'] === 1) {
                $featured[] = $offer;
            } else {
                $others[] = $offer;
            }
        }

        $this->frontend_layout->render('frontend/offers', array(
            'hero' => $this->listingHero($page),
            'sections' => $this->content_section_service->get_web_page_sections((int) $page['page_id']),
            'featured' => $featured,
            'others' => $others,
        ), array(
            'meta' => $this->frontend_layout->pageMeta($page, base_url('offers'), array(
                'images' => array(array('offers', !empty($featured) ? $featured[0]['offer_image'] : '')),
            )),
        ));
    }

    /** About (/about): studio story, team and location. */
    public function about()
    {
        $page = $this->listingPage('about');
        $this->load->model('Artist_model');
        $this->load->library('content_section_service');

        $this->frontend_layout->render('frontend/about', array(
            'hero' => $this->listingHero($page),
            'sections' => $this->content_section_service->get_web_page_sections((int) $page['page_id']),
            'artists' => $this->Artist_model->get_all(),
        ), array(
            'meta' => $this->frontend_layout->pageMeta($page, base_url('about'), array(
                'images' => array(array('pages', $page['banner_background'])),
            )),
        ));
    }

    /**
     * Journal (/blog): the latest post leads, the rest follow with category
     * chips (optionally preselected with ?category={slug}).
     */
    public function blog()
    {
        $page = $this->listingPage('blog');
        $this->load->model('Blog_model');
        $this->load->library('content_section_service');

        $posts = $this->Blog_model->get_published();
        $lead = !empty($posts) ? array_shift($posts) : NULL;

        // Chips for the categories used by the posts after the lead.
        $counts = array_count_values(array_filter(array_column($posts, 'category_slug')));
        $categories = array();
        foreach ($this->Blog_model->get_categories() as $category) {
            if (isset($counts[$category['cat_slug']])) {
                $categories[] = array(
                    'slug' => $category['cat_slug'],
                    'name' => $category['cat_name'],
                    'count' => $counts[$category['cat_slug']],
                );
            }
        }

        $requested = (string) $this->input->get('category');
        $category = in_array($requested, array_column($categories, 'slug'), TRUE) ? $requested : 'all';

        $this->frontend_layout->render('frontend/blog', array(
            'hero' => $this->listingHero($page),
            'sections' => $this->content_section_service->get_web_page_sections((int) $page['page_id']),
            'lead' => $lead,
            'posts' => $posts,
            'categories' => $categories,
            'category' => $category,
        ), array(
            'meta' => $this->frontend_layout->pageMeta($page, base_url('blog'), array(
                'images' => array(array('blogs', $lead !== NULL ? $lead['blog_image'] : '')),
            )),
            'scripts' => array('js/category-filter.js'),
        ));
    }

    /** Old category URLs (/blog/category/{slug}) open the filtered journal. */
    public function blog_category($slug = '')
    {
        redirect(base_url('blog').'?category='.rawurlencode(urldecode((string) $slug)), 'location', 301);
    }

    /** Journal article (/blog/{slug}). */
    public function article($slug = '')
    {
        $this->load->model('Blog_model');
        $this->load->library('content_section_service');

        $post = $this->Blog_model->get_by_slug(urldecode((string) $slug));
        if ($post === NULL) {
            return $this->error_404();
        }

        $url = base_url('blog/'.rawurlencode($post['blog_slug']));
        $image = trim((string) $post['blog_cover_image']) !== '' ? $post['blog_cover_image'] : $post['blog_image'];
        $published = $post['blog_pdate'] !== NULL && $post['blog_pdate'] !== '' ? $post['blog_pdate'] : $post['blog_added'];
        $journalSections = $this->content_section_service->get_web_page_sections(8);
        $cta = isset($journalSections['cta']) ? $journalSections['cta'] : array();

        $this->frontend_layout->render('frontend/article', array(
            'post' => $post,
            'crumbs' => array(
                array('label' => 'Home', 'url' => base_url()),
                array('label' => $this->listingLabel('blog', 'Journal'), 'url' => base_url('blog')),
                array('label' => $post['blog_name']),
            ),
            'image' => upload_thumb('blogs', $image, 1200, 0, 'images/no_image.jpg'),
            'published' => $published,
            'more' => $this->Blog_model->get_more((int) $post['blog_id'], 3),
            'cta' => $cta,
            'schema' => array(
                '@context' => 'https://schema.org',
                '@type' => 'Article',
                'headline' => $post['blog_name'],
                'description' => $this->frontend_seo->plainText($post['blog_short_description']),
                'datePublished' => date('Y-m-d', strtotime($published)),
                'image' => upload_thumb('blogs', $image, 1200, 0, 'images/no_image.jpg'),
                'author' => array('@type' => 'Organization', 'name' => $this->frontend_seo->siteName()),
                'publisher' => array('@type' => 'Organization', 'name' => $this->frontend_seo->siteName()),
            ),
        ), array(
            'meta' => $this->frontend_layout->pageMeta($post, $url, array(
                'name' => $post['blog_name'],
                'description' => array($post['blog_short_description']),
                'image_dir' => 'blogs',
                'images' => array(array('blogs', $image)),
            )),
        ));
    }

    /** FAQ (/faq): the visible FAQ categories with their questions. */
    public function faq()
    {
        $page = $this->listingPage('faq');
        $this->load->model('Faq_model');
        $this->load->library('content_section_service');

        $groups = $this->Faq_model->get_faq_groups();
        $questions = array();
        foreach ($groups as $group) {
            foreach ($group['items'] as $item) {
                $questions[] = array(
                    '@type' => 'Question',
                    'name' => $item['question'],
                    'acceptedAnswer' => array('@type' => 'Answer', 'text' => $item['answer']),
                );
            }
        }

        $this->frontend_layout->render('frontend/faq', array(
            'hero' => $this->listingHero($page),
            'sections' => $this->content_section_service->get_web_page_sections((int) $page['page_id']),
            'groups' => $groups,
            'schema' => !empty($questions)
                ? array(
                    '@context' => 'https://schema.org',
                    '@type' => 'FAQPage',
                    'mainEntity' => $questions,
                )
                : NULL,
        ), array(
            'meta' => $this->frontend_layout->pageMeta($page, base_url('faq')),
        ));
    }

    /** Artists listing (/artists): the first artist leads, the rest follow. */
    public function artists()
    {
        $page = $this->listingPage('artists');
        $this->load->model('Artist_model');
        $this->load->library('content_section_service');

        $artists = $this->Artist_model->get_all();
        $lead = !empty($artists) ? array_shift($artists) : NULL;

        $this->frontend_layout->render('frontend/artists', array(
            'page' => $page,
            'sections' => $this->content_section_service->get_web_page_sections((int) $page['page_id']),
            'lead' => $lead,
            'team' => $artists,
        ), array(
            'meta' => $this->frontend_layout->pageMeta($page, base_url('artists'), array(
                'images' => array(array('artists', $lead !== NULL ? $lead['artist_image'] : '')),
            )),
        ));
    }

    /** Artist detail (/artists/{slug}). */
    public function artist($slug = '')
    {
        $this->load->model(array('Artist_model', 'Gallery_model', 'Review_model'));
        $this->load->library('content_section_service');

        $artist = $this->Artist_model->get_by_slug(urldecode((string) $slug));
        if ($artist === NULL) {
            return $this->error_404();
        }

        $firstName = strtok($artist['artist_name'], ' ');
        $misc = $this->content_section_service->get_miscellaneous_contents();
        $labels = array();
        foreach ((isset($misc['artist_page']) ? $misc['artist_page'] : array()) as $key => $value) {
            $labels[$key] = str_replace('{name}', $firstName, (string) $value);
        }

        $url = base_url('artists/'.rawurlencode($artist['artist_slug']));
        $bookUrl = base_url('book').'?artist='.rawurlencode($artist['artist_slug']);
        $dayNames = array(
            'mon' => 'Monday',
            'tue' => 'Tuesday',
            'wed' => 'Wednesday',
            'thu' => 'Thursday',
            'fri' => 'Friday',
            'sat' => 'Saturday',
            'sun' => 'Sunday',
        );
        $workingDays = array_filter(explode(',', (string) $artist['artist_working_days']));
        $days = array();
        foreach ($dayNames as $code => $name) {
            if (in_array($code, $workingDays, TRUE)) {
                $days[] = $name;
            }
        }

        $this->frontend_layout->render('frontend/artist', array(
            'artist' => $artist,
            'hero' => array(
                'crumbs' => array(
                    array('label' => 'Home', 'url' => base_url()),
                    array('label' => $this->listingLabel('artists', 'Artists'), 'url' => base_url('artists')),
                    array('label' => $artist['artist_name']),
                ),
                'label' => $artist['artist_role'],
                'heading' => $artist['artist_name'],
                'lead' => $this->frontend_seo->plainText($artist['artist_bio']),
                'actions' => array(
                    array('label' => 'Book with '.$firstName, 'url' => $bookUrl),
                    array('label' => 'All artists', 'url' => base_url('artists'), 'variant' => 'outline'),
                ),
                'chips' => frontend_lines($artist['artist_specialties']),
                'image' => array(
                    'url' => upload_thumb('artists', $artist['artist_image'], 1200, 0, 'images/no_image.jpg'),
                    'alt' => 'Portrait of '.$artist['artist_name'],
                ),
            ),
            'services' => $this->Artist_model->get_services((int) $artist['artist_id']),
            'days' => $days,
            'work' => $this->Gallery_model->get_recent(6),
            'reviews' => $this->Review_model->get_reviews(3),
            'labels' => $labels,
            'book_url' => $bookUrl,
            'cta' => array(
                'heading' => isset($labels['cta_heading']) && $labels['cta_heading'] !== ''
                    ? $labels['cta_heading']
                    : 'Book with '.$firstName,
                'text' => isset($labels['cta_text']) ? $labels['cta_text'] : '',
                'book_url' => $bookUrl,
                'book_label' => 'Book an appointment',
            ),
        ), array(
            'meta' => $this->frontend_layout->pageMeta(array(), $url, array(
                'name' => $artist['artist_name'],
                'description' => array($artist['artist_bio']),
                'image_dir' => 'artists',
                'images' => array(array('artists', $artist['artist_image'])),
            )),
        ));
    }

    /**
     * Target of $route['404_override'] for public URLs, and of show_404()
     * during a public request (see MY_Exceptions).
     */
    public function error_404()
    {
        $this->output->set_status_header(404);

        $this->frontend_layout->render('frontend/error_404', array(), array(
            'meta' => array(
                'page_title' => 'Page not found | '.$this->frontend_layout->setting('website_title'),
                'robots' => 'noindex, follow',
            ),
        ));
    }

    /**
     * The published Web Pages record behind a module listing (its URL is the
     * slug), or the 404 page when it is missing or unpublished.
     */
    private function listingPage($slug)
    {
        $this->load->model('Webpage_model');
        $page = $this->Webpage_model->get_page('slug', $slug, false, true);

        if (empty($page)) {
            $this->error_404();
            $this->output->_display();
            exit;
        }

        return $page;
    }

    /** The menu name of a listing page, for breadcrumbs. */
    private function listingLabel($slug, $default)
    {
        $this->load->model('Webpage_model');
        $page = $this->Webpage_model->get_page('slug', $slug, false, true);

        return !empty($page['page_name']) ? html_entity_decode($page['page_name'], ENT_QUOTES, 'UTF-8') : $default;
    }

    /**
     * Hero of a module listing from its Web Pages banner fields: banner title
     * (label), banner heading (h1, falls back to the page name), banner text
     * (lead) and banner background (image).
     */
    private function listingHero(array $page, array $actions = array())
    {
        $name = html_entity_decode((string) $page['page_name'], ENT_QUOTES, 'UTF-8');
        $heading = trim((string) $page['banner_heading']);
        $image = trim((string) $page['banner_background']);

        return array(
            'crumbs' => array(
                array('label' => 'Home', 'url' => base_url()),
                array('label' => $name),
            ),
            'label' => trim((string) $page['banner_title']),
            'heading' => $heading !== '' ? $heading : $name,
            'lead' => $this->frontend_seo->plainText($page['banner_text']),
            'actions' => $actions,
            'image' => $image !== ''
                ? array(
                    'url' => upload_thumb('pages', $image, 1200, 0, 'images/no_image.jpg'),
                    'alt' => $heading !== '' ? $heading : $name,
                )
                : array(),
        );
    }

    /**
     * Enabled slides of a slider group as display data. A heading written as
     * "Beautiful nails.|A little time for yourself." shows the part after "|"
     * as the highlighted second line.
     */
    private function heroSlides($sliderId)
    {
        $slides = array();

        foreach ($this->Webpage_model->get_hero_slides($sliderId) as $row) {
            $heading = explode('|', (string) $row['heading'], 2);
            $slide = array(
                'label' => trim((string) $row['pre_heading']),
                'title' => trim($heading[0]),
                'highlight' => isset($heading[1]) ? trim($heading[1]) : '',
                'text' => trim(strip_tags((string) $row['text'])),
                'image' => upload_thumb('slider', $row['image'], 1400, 0, 'images/no_image.jpg'),
                'primary' => array(),
                'secondary' => array(),
            );

            foreach (array('primary' => 'button_1', 'secondary' => 'button_2') as $key => $prefix) {
                $label = trim((string) $row[$prefix.'_text']);
                if ($label !== '' && trim((string) $row[$prefix.'_url']) !== '') {
                    $slide[$key] = array(
                        'text' => $label,
                        'url' => frontend_url($row[$prefix.'_url']),
                        'target' => $row[$prefix.'_target'] === '_blank' ? '_blank' : '_self',
                        'icon' => frontend_icon_class($row[$prefix.'_icon']),
                    );
                }
            }

            $slides[] = $slide;
        }

        return $slides;
    }

    /** schema.org Service description for a service page. */
    private function serviceSchema(array $service, $url)
    {
        $site = $this->frontend_layout->settings();
        $schema = array(
            '@context' => 'https://schema.org',
            '@type' => 'Service',
            'name' => $service['service_name'],
            'description' => $this->frontend_seo->plainText($service['service_summary']),
            'serviceType' => $service['category_name'],
            'url' => $url,
            'provider' => array(
                '@type' => 'NailSalon',
                'name' => $this->frontend_seo->siteName(),
                'telephone' => isset($site['phone']) ? $site['phone'] : '',
            ),
        );

        if ($service['service_price_from'] !== NULL && $service['service_price_from'] !== '') {
            $schema['offers'] = array(
                '@type' => 'Offer',
                'price' => (float) $service['service_price_from'],
                'priceCurrency' => 'PLN',
            );
        }

        return $schema;
    }

    /**
     * Signed-in administrators keep browsing the real site while it is under
     * construction, so they can review pages before launch.
     */
    private function isAdministrator()
    {
        return (string) $this->session->userdata('admin_auth') === 'allow';
    }

    /**
     * Serve the under-construction page for every public URL. 503 with
     * Retry-After tells search engines the outage is temporary. Runs from the
     * constructor, before the router dispatches, so it must flush and halt
     * here to avoid a double render.
     */
    private function renderUnderConstruction()
    {
        $this->output->set_status_header(503);
        $this->output->set_header('Retry-After: 3600');
        $this->output->set_header('Cache-Control: no-store');
        $this->output->set_header('X-Robots-Tag: noindex, nofollow');

        $this->frontend_layout->renderStandalone('frontend/under_construction', array(), array(
            'meta' => array(
                'page_title' => $this->frontend_layout->setting('website_title'),
            ),
        ));
        $this->output->_display();
        exit;
    }

    private function renderManageNotFound()
    {
        $this->controller = 'errors';
        $this->SqlModel->setTitle();
        $this->output->set_status_header(404);
        $this->load->view('admin/header', array(
            'loginSection' => 1,
            'page_title' => PROJECT_TITLE.' | Page Not Found',
        ));
        $this->load->view('admin/error404', array(
            'isLoggedIn' => (string) $this->session->userdata('admin_auth') === 'allow',
        ));
        $this->load->view('admin/footer');
        $this->output->_display();
        exit;
    }
}
