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

    public function index()
    {
        $this->load->model('Webpage_model');
        $page = $this->Webpage_model->get_page('id', 1, false, true);

        $this->frontend_layout->render('frontend/home', array(), array(
            'meta' => $this->frontend_layout->pageMeta(is_array($page) ? $page : array(), base_url()),
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
            'featured' => $this->Service_model->get_featured(4),
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
