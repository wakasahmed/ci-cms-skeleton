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
    /** Locales served under /en and /ar. */
    private $locales = array('en', 'ar');

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
            $lines[] = 'Disallow: /recaptcha-enterprise';
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
            $this->blogCategoryEntries(),
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
            'This site is published in English (' . $seo->homeUrl('en') . ') and Arabic ('
                . $seo->homeUrl('ar') . '). Each page below links to its English version.',
            '',
        );

        $posts = array();
        foreach ($this->Seo_model->get_blog_posts() as $row) {
            $posts[] = $this->llmsItem(
                $row['blog_name'],
                $seo->blogPostUrl($row['blog_slug'], 'en'),
                $row['blog_short_description']
            );
        }

        $pages = array();
        foreach ($this->Seo_model->get_pages() as $row) {
            if ((int) $row['page_id'] === 1) {
                continue;
            }
            $pages[] = $this->llmsItem(
                $row['page_name'],
                $seo->pageUrl($row['page_slug'], 'en'),
                ''
            );
        }

        $sections = array(
            'Articles' => $posts,
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
     * Sitemap entries. Each entry is one URL in one locale and lists every
     * language version of the same page as an hreflang alternate.
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

        $urls = array();
        foreach ($this->locales as $locale) {
            $urls[$locale] = $this->frontend_seo->homeUrl($locale);
        }

        return $this->localizedEntries(
            $urls,
            $this->frontend_seo->isoDate($homePage['page_updated']),
            array()
        );
    }

    private function pageEntries()
    {
        $entries = array();

        foreach ($this->Seo_model->get_pages() as $row) {
            // The home page is listed by homeEntries() at its locale root.
            if ((int) $row['page_id'] === 1) {
                continue;
            }

            $slugEn = trim((string) $row['page_slug']);
            $slugAr = trim((string) $row['page_slug_ar']);
            if ($slugEn === '') {
                continue;
            }

            $urls = array(
                'en' => $this->frontend_seo->pageUrl($slugEn, 'en'),
                'ar' => $this->frontend_seo->pageUrl($slugAr !== '' ? $slugAr : $slugEn, 'ar'),
            );
            $images = $this->imageUrls(array(array('pages', $row['og_image']), array('pages', $row['banner_background'])), 1);

            $entries = array_merge(
                $entries,
                $this->localizedEntries($urls, $this->frontend_seo->isoDate($row['page_updated']), $images)
            );
        }

        return $entries;
    }

    private function blogCategoryEntries()
    {
        $entries = array();

        foreach ($this->Seo_model->get_blog_categories() as $row) {
            $slug = trim((string) $row['cat_slug']);
            if ($slug === '') {
                continue;
            }

            // Category pages are looked up by their English slug in both locales.
            $urls = array(
                'en' => $this->frontend_seo->blogCategoryUrl($slug, 'en'),
                'ar' => $this->frontend_seo->blogCategoryUrl($slug, 'ar'),
            );
            $images = $this->imageUrls(
                array(array('blog-categories', $row['og_image']), array('blog-categories', $row['cat_cover_image'])),
                1
            );

            $entries = array_merge(
                $entries,
                $this->localizedEntries($urls, $this->frontend_seo->isoDate($row['cat_updated']), $images)
            );
        }

        return $entries;
    }

    private function blogPostEntries()
    {
        $entries = array();

        foreach ($this->Seo_model->get_blog_posts() as $row) {
            $slugEn = trim((string) $row['blog_slug']);
            $slugAr = trim((string) $row['blog_slug_ar']);
            if ($slugEn === '') {
                continue;
            }

            $urls = array(
                'en' => $this->frontend_seo->blogPostUrl($slugEn, 'en'),
                'ar' => $this->frontend_seo->blogPostUrl($slugAr !== '' ? $slugAr : $slugEn, 'ar'),
            );
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

            $entries = array_merge($entries, $this->localizedEntries($urls, $lastModified, $images));
        }

        return $entries;
    }

    /** One entry per locale URL, each carrying the full hreflang set. */
    private function localizedEntries(array $urls, $lastModified, array $images)
    {
        $alternates = array();
        foreach ($urls as $locale => $url) {
            $alternates[$locale === 'ar' ? 'ar-SA' : $locale] = $this->frontend_seo->encodeUrl($url);
        }
        $alternates['x-default'] = $this->frontend_seo->encodeUrl($urls[$this->frontend_seo->defaultLocale()]);

        $entries = array();
        foreach ($urls as $url) {
            $entries[] = array(
                'loc' => $this->frontend_seo->encodeUrl($url),
                'lastmod' => $lastModified,
                'alternates' => $alternates,
                'images' => $images,
            );
        }

        return $entries;
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
                . ' xmlns:xhtml="http://www.w3.org/1999/xhtml"'
                . ' xmlns:image="http://www.google.com/schemas/sitemap-image/1.1">',
        );

        foreach ($entries as $entry) {
            $xml[] = '  <url>';
            $xml[] = '    <loc>' . $this->xml($entry['loc']) . '</loc>';
            if ($entry['lastmod'] !== '') {
                $xml[] = '    <lastmod>' . $this->xml($entry['lastmod']) . '</lastmod>';
            }
            foreach ($entry['alternates'] as $hreflang => $href) {
                $xml[] = '    <xhtml:link rel="alternate" hreflang="' . $this->xml($hreflang)
                    . '" href="' . $this->xml($href) . '"/>';
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
