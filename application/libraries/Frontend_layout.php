<?php defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Shared page layout for the public site.
 *
 * Loads Website Settings once per request, builds the data the head, header
 * and footer partials need (menus from Manage > Menu and Manage > Foot,
 * featured services, contact details, opening hours), and renders a page
 * view between them:
 *
 *   frontend/layout/head    <head> and the opening <body>
 *   frontend/layout/header  skip link and site header
 *   <page view>             the page's <main>
 *   frontend/layout/footer  site footer, scripts and closing tags
 */
class Frontend_layout
{
    /** Vendor bundles a page can ask for through $page['vendors']. */
    private $vendorAssets = array(
        'swiper' => array(
            'styles' => array('vendor/swiper/swiper-bundle-14.0.7.min.css'),
            'scripts' => array('vendor/swiper/swiper-bundle-14.0.7.min.js'),
        ),
        'photoswipe' => array(
            'styles' => array('vendor/photoswipe/photoswipe-5.4.4.css'),
            'scripts' => array('vendor/photoswipe/photoswipe-5.4.4.umd.min.js'),
        ),
    );

    private $CI;
    private $settings = array();

    public function __construct()
    {
        $this->CI =& get_instance();

        $settings = $this->CI->SqlModel->getSingleRecord('site_settings', array('id' => 1));
        $this->settings = is_array($settings) ? $settings : array();

        // Frontend_seo reads the settings from here.
        $this->CI->config->set_item('frontend_site_settings', $this->settings);

        $this->CI->load->library('frontend_seo');
        $this->CI->load->helper('frontend');
    }

    public function settings()
    {
        return $this->settings;
    }

    public function setting($key)
    {
        return isset($this->settings[$key]) ? trim((string) $this->settings[$key]) : '';
    }

    public function isUnderConstruction()
    {
        return $this->CI->frontend_seo->isUnderConstruction();
    }

    /**
     * Render a page.
     *
     * $page keys (all optional):
     *   meta      head values from pageMeta()
     *   vendors   names from $vendorAssets to load on this page only
     *   styles    extra stylesheets, relative to assets/frontend/
     *   scripts   extra deferred scripts, relative to assets/frontend/
     *   header    'default', or 'overlay' for a page that opens with a dark
     *             image hero (light header until the page scrolls)
     *   crumbs    breadcrumb for the structured data (array of label/url);
     *             defaults to $data['hero']['crumbs'] or $data['crumbs']
     *   page_type schema.org type of the page (WebPage, CollectionPage,
     *             AboutPage, ContactPage, FAQPage, SearchResultsPage, ...)
     *   webpage   extra properties of the WebPage node
     *   schema    extra JSON-LD nodes for the page's record (Service,
     *             BlogPosting, Person, ...)
     *   is_home   TRUE on the home page
     */
    public function render($view, array $data = array(), array $page = array())
    {
        if (!isset($page['crumbs'])) {
            if (isset($data['hero']['crumbs'])) {
                $page['crumbs'] = $data['hero']['crumbs'];
            } elseif (isset($data['crumbs'])) {
                $page['crumbs'] = $data['crumbs'];
            }
        }
        $layout = $this->layoutData($page);

        $this->CI->load->view('frontend/layout/head', $layout);
        $this->CI->load->view('frontend/layout/header', $layout);
        $this->CI->load->view($view, array_merge($layout, $data));
        $this->CI->load->view('frontend/layout/footer', $layout);
    }

    /** Render a page with the shared head only; the view closes the document itself. */
    public function renderStandalone($view, array $data = array(), array $page = array())
    {
        $layout = $this->layoutData($page);

        $this->CI->load->view('frontend/layout/head', $layout);
        $this->CI->load->view($view, array_merge($layout, $data));
    }

    private function layoutData(array $page)
    {
        $page = array_merge(array(
            'meta' => array(),
            'vendors' => array(),
            'styles' => array(),
            'scripts' => array(),
            'header' => 'default',
            'crumbs' => array(),
            'page_type' => 'WebPage',
            'webpage' => array(),
            'schema' => array(),
            'is_home' => FALSE,
        ), $page);
        $meta = $this->headMeta($page['meta']);

        return array(
            'site' => $this->siteData(),
            'meta' => $meta,
            'json_ld' => $this->structuredData($page, $meta),
            'styles' => array_merge(
                $this->vendorAssetUrls($page['vendors'], 'styles'),
                array_map(array($this, 'assetUrl'), $page['styles'])
            ),
            // Vendor libraries load before jQuery (as in the reference), page scripts after site.js.
            'vendor_scripts' => $this->vendorAssetUrls($page['vendors'], 'scripts'),
            'scripts' => array_map(array($this, 'assetUrl'), $page['scripts']),
            'navigation' => $this->navigation(),
            'header_overlay' => $page['header'] === 'overlay',
            'footer' => $this->footerData(),
        );
    }

    /**
     * Head values for a Web Pages record (or any row with the same SEO
     * columns), with the page name as the title fallback.
     */
    public function pageMeta(array $row, $canonical, array $options = array())
    {
        $meta = $this->CI->frontend_seo->entityConfig($row, array(), array_merge(array(
            'name' => isset($row['page_name']) ? $row['page_name'] : '',
        ), $options));
        $meta['canonical'] = $canonical;

        return $meta;
    }

    /** Contact and branding values shared by every partial. */
    private function siteData()
    {
        $name = $this->CI->frontend_seo->siteName();

        return array(
            'name' => $name !== '' ? $name : 'Blossom Ewa Mazur',
            'intro' => $this->CI->frontend_seo->plainText($this->setting('website_intro')),
            'phone' => $this->setting('phone'),
            'phone_href' => frontend_phone_href($this->setting('phone')),
            'email' => $this->setting('email'),
            'address_lines' => frontend_lines($this->setting('address')),
            'address_note' => $this->setting('address_note'),
            'opening_hours' => frontend_opening_hours($this->setting('opening_hours')),
            'map_url' => $this->setting('map_url'),
            'logo' => $this->logo('logo', 'blossom-logo.png'),
            'logo_light' => $this->logo('logo_white', 'blossom-logo-light.png'),
            'socials' => $this->socials(),
            'copyright' => str_replace('[YEAR]', date('Y'), $this->setting('copyright_text')),
            'book_url' => base_url('book'),
            'search_url' => base_url('search'),
        );
    }

    /** An uploaded Website Settings logo, or the bundled Blossom logo when none is set. */
    private function logo($column, $fallback)
    {
        $file = basename($this->setting($column));
        $relative = $file !== '' && is_file(FCPATH.'assets/frontend/images/logo/'.$file)
            ? 'assets/frontend/images/logo/'.$file
            : 'assets/frontend/images/brand/'.$fallback;

        $size = @getimagesize(FCPATH.$relative);

        return array(
            'url' => base_url($relative),
            'width' => is_array($size) ? (int) $size[0] : 250,
            'height' => is_array($size) ? (int) $size[1] : 102,
        );
    }

    private function socials()
    {
        $networks = array(
            'instagram' => array('label' => 'Instagram', 'icon' => 'fa-instagram'),
            'facebook' => array('label' => 'Facebook', 'icon' => 'fa-facebook-f'),
            'twitter' => array('label' => 'X', 'icon' => 'fa-x-twitter'),
            'youtube' => array('label' => 'YouTube', 'icon' => 'fa-youtube'),
            'linkedin' => array('label' => 'LinkedIn', 'icon' => 'fa-linkedin-in'),
        );

        $socials = array();
        foreach ($networks as $column => $network) {
            $url = $this->setting($column);
            if ($url !== '' && preg_match('#^https?://#i', $url) === 1) {
                $socials[] = array_merge($network, array('url' => $url));
            }
        }

        return $socials;
    }

    /** Title, description, robots and sharing tags for the page head. */
    private function headMeta(array $meta)
    {
        $seo = $this->CI->frontend_seo;
        $siteName = $seo->siteName();

        $title = isset($meta['page_title']) && $meta['page_title'] !== ''
            ? $meta['page_title']
            : $siteName;
        $description = isset($meta['meta_description']) && $meta['meta_description'] !== ''
            ? $meta['meta_description']
            : $seo->siteDescription();

        // A site still under construction is kept out of search results entirely.
        $robots = $this->isUnderConstruction()
            ? 'noindex, nofollow'
            : $seo->withSnippetDirectives(isset($meta['robots']) ? $meta['robots'] : 'index, follow');

        $image = isset($meta['og_image'])
            ? array(
                'url' => $meta['og_image'],
                'width' => isset($meta['og_image_width']) ? $meta['og_image_width'] : 0,
                'height' => isset($meta['og_image_height']) ? $meta['og_image_height'] : 0,
            )
            : $seo->image(array());

        return array(
            'title' => $title,
            'description' => $description,
            'keywords' => isset($meta['keywords']) ? $meta['keywords'] : '',
            'robots' => $robots,
            'canonical' => isset($meta['canonical']) ? $seo->encodeUrl($meta['canonical']) : '',
            'site_name' => $siteName,
            'og_title' => isset($meta['og_title']) ? $meta['og_title'] : $title,
            'og_description' => isset($meta['og_description']) ? $meta['og_description'] : $description,
            'og_image' => $image,
            'og_image_alt' => isset($meta['og_image_alt']) ? $meta['og_image_alt'] : $title,
            'og_type' => isset($meta['og_type']) ? $meta['og_type'] : 'website',
            // article:published_time and similar, for og_type "article".
            'article' => isset($meta['article']) && is_array($meta['article']) ? $meta['article'] : array(),
        );
    }

    /** The page's JSON-LD graph: the salon, the website, the page and its own nodes. */
    private function structuredData(array $page, array $meta)
    {
        $seo = $this->CI->frontend_seo;
        $crumbs = array();
        foreach ($page['crumbs'] as $crumb) {
            $crumbs[] = array(
                'label' => isset($crumb['label']) ? $crumb['label'] : '',
                'href' => isset($crumb['url']) ? $crumb['url'] : '',
            );
        }

        $sameAs = array();
        foreach ($this->socials() as $social) {
            $sameAs[] = $social['url'];
        }
        $logo = $this->logo('logo', 'blossom-logo.png');
        $defaultImage = $seo->image(array());

        return $seo->graph(array(
            'canonical' => $meta['canonical'] !== '' ? $meta['canonical'] : $seo->homeUrl(),
            'title' => $meta['title'],
            'description' => $meta['description'],
            'page_type' => $page['page_type'],
            'image' => !empty($meta['og_image']['url']) ? $meta['og_image'] : array(),
            'crumbs' => $crumbs,
            'nodes' => $page['schema'],
            'is_home' => $page['is_home'],
            'webpage' => $page['webpage'],
            'organization' => array(
                'name' => $seo->siteName() !== '' ? $seo->siteName() : 'Blossom Ewa Mazur',
                'url' => $seo->homeUrl(),
                'telephone' => $this->setting('phone'),
                'email' => $this->setting('email'),
                'description' => $seo->siteDescription(),
                'logo' => $logo['url'],
                'image' => !empty($defaultImage['url']) ? $defaultImage['url'] : '',
                'address' => $seo->postalAddress(frontend_lines($this->setting('address'))),
                'opening_hours' => $seo->openingHoursSpecification(
                    frontend_parse_opening_hours($this->setting('opening_hours'))
                ),
                'same_as' => $sameAs,
                'map_url' => $this->setting('map_url'),
            ),
        ));
    }

    /** Versioned URLs of the requested vendor bundles' stylesheets or scripts. */
    private function vendorAssetUrls(array $vendors, $type)
    {
        $files = array();
        foreach (array_unique($vendors) as $vendor) {
            if (isset($this->vendorAssets[$vendor])) {
                $files = array_merge($files, $this->vendorAssets[$vendor][$type]);
            }
        }

        return array_map(array($this, 'assetUrl'), $files);
    }

    /** URL of a file under assets/frontend/, versioned by its modification time. */
    public function assetUrl($file)
    {
        $relative = 'assets/frontend/'.ltrim($file, '/');
        $version = @filemtime(FCPATH.$relative);

        return base_url($relative).($version ? '?v='.$version : '');
    }

    /** Top-level items of Manage > Menu, in order. */
    private function navigation()
    {
        $this->CI->load->model('MenuModel');
        $menu = $this->CI->MenuModel->getMenuData();

        return $this->menuLinks(isset($menu['active']) ? $menu['active'] : array());
    }

    private function footerData()
    {
        $this->CI->load->model('FootModel');
        $this->CI->load->model('Service_model');

        $salon = $this->CI->FootModel->getFooterMenuData('one');
        $legal = $this->CI->FootModel->getFooterMenuData('two');

        $services = array();
        foreach ($this->CI->Service_model->get_footer_services() as $service) {
            $services[] = array(
                'label' => $service['service_name'],
                'url' => base_url('services/'.rawurlencode($service['service_slug'])),
            );
        }

        return array(
            'services_heading' => $this->setting('foot_col_1'),
            'services' => $services,
            'salon_heading' => $this->setting('foot_col_2'),
            'salon' => $this->menuLinks(isset($salon['active']) ? $salon['active'] : array()),
            'contact_heading' => $this->setting('foot_col_4'),
            'legal' => $this->menuLinks(isset($legal['active']) ? $legal['active'] : array()),
        );
    }

    /** Menu tree nodes (top level only) as label/url/current links. */
    private function menuLinks(array $nodes)
    {
        $current = trim((string) $this->CI->uri->segment(1));
        $links = array();

        foreach ($nodes as $node) {
            $isHome = (int) $node['page_id'] === 1;
            $slug = trim((string) $node['page_slug'], '/');
            $label = $node['menu_name'] !== '' ? $node['menu_name'] : $node['page_name'];

            $links[] = array(
                'label' => html_entity_decode($label, ENT_QUOTES, 'UTF-8'),
                'url' => $isHome ? base_url() : base_url($slug),
                'current' => $isHome ? $current === '' : $current !== '' && $current === $slug,
            );
        }

        return $links;
    }
}
