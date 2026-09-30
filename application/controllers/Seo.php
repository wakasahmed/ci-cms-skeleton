<?php defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Crawler-facing files: robots.txt, sitemap.xml and llms.txt.
 *
 * All three are generated from the same records the public site serves, so they
 * never drift from what is actually published. The admin area is deliberately
 * absent from every file: naming a private path in a public file would only
 * advertise it. It is kept out of search results by its own noindex headers
 * instead (see admin/header.php).
 */
class Seo extends CI_Controller
{
    /** Sitemap protocol allows 50,000 URLs per file. */
    const SITEMAP_URL_LIMIT = 50000;

    public function __construct()
    {
        parent::__construct();

        $settings = $this->SqlModel->getSingleRecord('site_settings', array('id' => 1));
        $this->config->set_item(
            'frontend_site_settings',
            is_array($settings) ? $settings : array()
        );

        $this->load->library('frontend_seo');
        $this->load->helper('frontend');
        $this->load->model('Seo_model');
    }

    public function robots()
    {
        $lines = array('User-agent: *');

        if ($this->frontend_seo->isUnderConstruction()) {
            // The site is not launched: keep every crawler out until the switch is turned off.
            $lines[] = 'Disallow: /';
        } else {
            $lines[] = 'Allow: /';
            // Shows the visitor's own booking request; there is nothing to index.
            $lines[] = 'Disallow: /book/confirmed';
            $lines[] = '';
            $lines[] = 'Sitemap: ' . base_url('sitemap.xml');
        }

        $this->respond('text/plain', implode("\n", $lines) . "\n");
    }

    public function sitemap()
    {
        if ($this->frontend_seo->isUnderConstruction()) {
            $this->output->set_status_header(404);

            return;
        }

        $entries = array_merge(
            $this->homeEntries(),
            $this->pageEntries(),
            $this->serviceEntries(),
            $this->artistEntries(),
            $this->blogPostEntries()
        );

        $this->respond(
            'application/xml',
            $this->renderSitemap(array_slice($entries, 0, self::SITEMAP_URL_LIMIT))
        );
    }

    public function llms()
    {
        if ($this->frontend_seo->isUnderConstruction()) {
            $this->output->set_status_header(404);

            return;
        }

        $seo = $this->frontend_seo;
        $intro = $seo->siteDescription();
        $lines = array(
            '# ' . $seo->siteName(),
            '',
            '> ' . ($intro !== '' ? $intro : 'Manicure, nail art and beauty treatments in Gorlice, Poland.'),
            '',
        );

        $contact = array();
        $settings = $this->config->item('frontend_site_settings');
        $address = implode(', ', frontend_lines(isset($settings['address']) ? $settings['address'] : ''));
        if ($address !== '') {
            $contact[] = '- Address: ' . $address;
        }
        if ($seo->setting('phone') !== '') {
            $contact[] = '- Phone: ' . frontend_phone_display($seo->setting('phone'));
        }
        // The raw setting keeps its line breaks (Frontend_seo::setting() flattens them).
        $openingHours = isset($settings['opening_hours']) ? $settings['opening_hours'] : '';
        foreach (frontend_opening_hours($openingHours) as $row) {
            $contact[] = '- ' . $row['days'] . ': ' . $row['hours'];
        }
        $contact[] = '- Book online: ' . $seo->encodeUrl(base_url('book'))
            . ' (appointment requests are confirmed by the salon)';

        $services = array();
        foreach ($this->Seo_model->get_services() as $row) {
            $details = array_filter(array(frontend_service_price($row), $row['service_duration_label']));
            $services[] = $this->llmsItem(
                $row['service_name'],
                base_url('services/' . rawurlencode($row['service_slug'])),
                trim(implode(' · ', $details) . '. ' . $seo->plainText($row['service_summary']), '. ')
            );
        }

        $offers = array();
        $this->load->model('Offer_model');
        foreach ($this->Offer_model->get_public(FALSE) as $row) {
            $offers[] = $this->llmsItem(
                $row['offer_title'],
                base_url('offers') . '#offer-' . rawurlencode($row['offer_slug']),
                trim(frontend_price($row['offer_price']) . '. ' . $seo->plainText($row['offer_summary']), '. ')
            );
        }

        $team = array();
        foreach ($this->Seo_model->get_artists() as $row) {
            $team[] = $this->llmsItem(
                $row['artist_name'],
                base_url('artists/' . rawurlencode($row['artist_slug'])),
                implode(' · ', array_filter(array_merge(
                    array($row['artist_role']),
                    frontend_lines($row['artist_specialties'])
                )))
            );
        }

        $posts = array();
        foreach ($this->Seo_model->get_blog_posts() as $row) {
            $posts[] = $this->llmsItem(
                $row['blog_name'],
                $seo->blogPostUrl($row['blog_slug']),
                $row['blog_short_description']
            );
        }

        $pages = array();
        foreach ($this->Seo_model->get_pages() as $row) {
            if ((int) $row['page_id'] === 1 || !$this->isRoutedPage(trim((string) $row['page_slug']))) {
                continue;
            }
            $pages[] = $this->llmsItem(
                $row['page_name'],
                $seo->pageUrl($row['page_slug']),
                ''
            );
        }

        $sections = array(
            'Visit and contact' => $contact,
            'Services' => $services,
            'Offers' => $offers,
            'Team' => $team,
            'Journal' => $posts,
            'Pages' => $pages,
        );
        foreach ($sections as $heading => $items) {
            if (empty($items)) {
                continue;
            }
            $lines[] = '## ' . $heading;
            $lines[] = '';
            foreach ($items as $item) {
                $lines[] = $item;
            }
            $lines[] = '';
        }

        $this->respond('text/plain', implode("\n", $lines));
    }

    /* ---------------------------------------------------------------------
     * Sitemap entries. Each entry is one public URL.
     * ------------------------------------------------------------------ */

    private function homeEntries()
    {
        // get_pages() returns only published pages that allow indexing, so a home page set to
        // "noindex" in Manage > Web Pages is left out like any other page.
        $homePage = null;
        foreach ($this->Seo_model->get_pages() as $row) {
            if ((int) $row['page_id'] === 1) {
                $homePage = $row;
            }
        }
        if ($homePage === null) {
            return array();
        }

        return array(
            $this->entry(
                $this->frontend_seo->homeUrl(),
                $this->frontend_seo->isoDate($homePage['page_updated']),
                array()
            ),
        );
    }

    private function pageEntries()
    {
        $entries = array();

        foreach ($this->Seo_model->get_pages() as $row) {
            // The home page is listed by homeEntries() at the site root.
            if ((int) $row['page_id'] === 1) {
                continue;
            }

            $slug = trim((string) $row['page_slug']);
            if (!$this->isRoutedPage($slug)) {
                continue;
            }

            $images = $this->imageUrls(
                array(
                    array('pages', $row['og_image']),
                    array('pages', $row['banner_background']),
                ),
                1
            );

            $entries[] = $this->entry(
                $this->frontend_seo->pageUrl($slug),
                $this->frontend_seo->isoDate($row['page_updated']),
                $images
            );
        }

        return $entries;
    }

    private function serviceEntries()
    {
        $entries = array();

        foreach ($this->Seo_model->get_services() as $row) {
            $entries[] = $this->entry(
                base_url('services/' . rawurlencode($row['service_slug'])),
                $this->frontend_seo->isoDate($row['service_updated']),
                $this->imageUrls(
                    array(
                        array('services', $row['og_image']),
                        array('services', $row['service_hero_image']),
                        array('services', $row['service_card_image']),
                    ),
                    2
                )
            );
        }

        return $entries;
    }

    private function artistEntries()
    {
        $entries = array();

        foreach ($this->Seo_model->get_artists() as $row) {
            $entries[] = $this->entry(
                base_url('artists/' . rawurlencode($row['artist_slug'])),
                $this->frontend_seo->isoDate($row['artist_updated']),
                $this->imageUrls(array(array('artists', $row['artist_image'])), 1)
            );
        }

        return $entries;
    }

    /**
     * TRUE when a Web Pages slug is a public route (listing, legal or
     * booking page). Pages without a route would only lead to the 404 page.
     */
    private function isRoutedPage($slug)
    {
        return $slug !== ''
            && isset($this->router->routes[$slug])
            && strpos($this->router->routes[$slug], 'frontend/') === 0;
    }

    private function blogPostEntries()
    {
        $entries = array();

        foreach ($this->Seo_model->get_blog_posts() as $row) {
            $slug = trim((string) $row['blog_slug']);
            if ($slug === '') {
                continue;
            }

            $images = $this->imageUrls(
                array(
                    array('blogs', $row['og_image']),
                    array('blogs', $row['blog_image']),
                    array('blogs', $row['blog_cover_image']),
                ),
                3
            );
            $lastModified = $this->frontend_seo->isoDate($row['blog_updated']);
            if ($lastModified === '') {
                $lastModified = $this->frontend_seo->isoDate($row['blog_added']);
            }

            $entries[] = $this->entry(
                $this->frontend_seo->blogPostUrl($slug),
                $lastModified,
                $images
            );
        }

        return $entries;
    }

    private function entry($url, $lastModified, array $images)
    {
        return array(
            'loc' => $this->frontend_seo->encodeUrl($url),
            'lastmod' => $lastModified,
            'images' => $images,
        );
    }

    /** Distinct public URLs of the first $limit candidates that exist on disk. */
    private function imageUrls(array $candidates, $limit)
    {
        $urls = array();

        foreach ($candidates as $candidate) {
            $relative = $this->frontend_seo->imagePath($candidate[0], $candidate[1]);
            if ($relative === '') {
                continue;
            }

            $url = $this->frontend_seo->encodeUrl(image_thumb_url($relative));
            if (!in_array($url, $urls, true)) {
                $urls[] = $url;
            }
            if (count($urls) >= $limit) {
                break;
            }
        }

        return $urls;
    }

    private function renderSitemap(array $entries)
    {
        $xml = array(
            '<?xml version="1.0" encoding="UTF-8"?>',
            // Browsers show the sitemap as a styled table; crawlers ignore the stylesheet.
            '<?xml-stylesheet type="text/xsl" href="' . $this->xml(base_url('assets/frontend/xsl/sitemap.xsl')) . '"?>',
            '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9"'
                . ' xmlns:image="http://www.google.com/schemas/sitemap-image/1.1">',
        );

        foreach ($entries as $entry) {
            $xml[] = '  <url>';
            $xml[] = '    <loc>' . $this->xml($entry['loc']) . '</loc>';
            if ($entry['lastmod'] !== '') {
                $xml[] = '    <lastmod>' . $this->xml($entry['lastmod']) . '</lastmod>';
            }
            foreach ($entry['images'] as $image) {
                $xml[] = '    <image:image><image:loc>' . $this->xml($image) . '</image:loc></image:image>';
            }
            $xml[] = '  </url>';
        }

        $xml[] = '</urlset>';

        return implode("\n", $xml) . "\n";
    }

    private function llmsItem($name, $url, $summary)
    {
        $name = $this->frontend_seo->plainText($name);
        $summary = $this->frontend_seo->excerpt($summary);

        return '- [' . $name . '](' . $this->frontend_seo->encodeUrl($url) . ')'
            . ($summary !== '' ? ': ' . $summary : '');
    }

    private function xml($value)
    {
        return htmlspecialchars((string) $value, ENT_QUOTES | ENT_XML1, 'UTF-8');
    }

    private function respond($contentType, $body)
    {
        $this->output
            ->set_content_type($contentType, 'UTF-8')
            ->set_header('Cache-Control: public, max-age=3600')
            ->set_output($body);
    }
}
