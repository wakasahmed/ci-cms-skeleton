<?php defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Search-engine and AI-search metadata for the public site.
 *
 * Turns the SEO fields the administrator manages (page title, meta description,
 * keywords, robots, sharing title/description/image) into the values the shared
 * frontend header prints, and supplies the fallbacks used when a field is left
 * blank: the record's own content and images come first, the site-wide defaults
 * last. It also builds the absolute URLs and JSON-LD nodes shared by the page
 * head, the XML sitemap and llms.txt, so all three describe a page the same way.
 *
 * The class holds no request or response handling and no database queries.
 */
class Frontend_seo
{
    const DESCRIPTION_LENGTH = 160;

    /** Longest title search engines show without truncating it. */
    const TITLE_LENGTH = 65;

    const IMAGE_WIDTH = 1200;

    const IMAGE_HEIGHT = 630;

    /** The site is published in English only (BCP 47 tag). */
    const LANGUAGE = 'en';

    /** Site-wide sharing image, used only when a record has no usable image. */
    const DEFAULT_IMAGE = 'assets/frontend/images/brand/og-default.jpg';

    /** Lets search engines show large image previews and full snippets. */
    const ROBOTS_SNIPPET_DIRECTIVES = 'max-image-preview:large, max-snippet:-1, max-video-preview:-1';

    private $CI;

    public function __construct()
    {
        $this->CI =& get_instance();
    }

    /* ---------------------------------------------------------------------
     * Text
     * ------------------------------------------------------------------ */

    /** Editor HTML or stored text reduced to one line of plain text. */
    public function plainText($value)
    {
        $value = (string) $value;
        // Block-level closings become spaces so adjacent paragraphs do not run together.
        $value = preg_replace('#<(br|/p|/div|/li|/h[1-6]|/tr)[^>]*>#i', ' ', $value);
        $value = strip_tags((string) $value);
        $value = html_entity_decode($value, ENT_QUOTES, 'UTF-8');

        // Content saved entity-encoded ("&lt;p&gt;") decodes to markup on the first pass.
        if (strpos($value, '<') !== false) {
            $value = strip_tags($value);
        }

        $value = preg_replace('/\s+/u', ' ', $value);

        return is_string($value) ? trim($value) : '';
    }

    /** Plain text cut at a word boundary, for descriptions built from body content. */
    public function excerpt($value, $limit = self::DESCRIPTION_LENGTH)
    {
        $text = $this->plainText($value);
        $limit = (int) $limit;

        if ($text === '' || mb_strlen($text, 'UTF-8') <= $limit) {
            return $text;
        }

        $cut = mb_substr($text, 0, $limit - 1, 'UTF-8');
        $space = mb_strrpos($cut, ' ', 0, 'UTF-8');
        if ($space !== false && $space > $limit * 0.6) {
            $cut = mb_substr($cut, 0, $space, 'UTF-8');
        }

        return rtrim($cut, " \t\n\r,.;:-") . '…';
    }

    /** The administrator's meta description as written, else the first non-empty fallback. */
    public function description($custom, array $fallbacks = array())
    {
        $custom = $this->plainText($custom);
        if ($custom !== '') {
            return $custom;
        }

        foreach ($fallbacks as $fallback) {
            $text = $this->excerpt($fallback);
            if ($text !== '') {
                return $text;
            }
        }

        return '';
    }

    /**
     * "Record name | Site name". The site name is left off when it is already
     * in the name or would push the title past what search results display.
     */
    public function brandedTitle($name)
    {
        $name = $this->plainText($name);
        $brand = $this->siteName();

        if ($name === '' || $brand === '' || mb_stripos($name, $brand, 0, 'UTF-8') !== false) {
            return $name !== '' ? $name : $brand;
        }

        $branded = $name . ' | ' . $brand;

        return mb_strlen($branded, 'UTF-8') <= self::TITLE_LENGTH ? $branded : $name;
    }

    /** The site name, from Website Settings. */
    public function siteName()
    {
        return $this->setting('website_title');
    }

    /** Site-wide description used when a page has nothing more specific. */
    public function siteDescription()
    {
        return $this->excerpt($this->setting('website_intro'));
    }

    /** Value of a Website Settings column as plain text. */
    public function setting($key)
    {
        $settings = $this->CI->config->item('frontend_site_settings');
        $settings = is_array($settings) ? $settings : array();

        return isset($settings[$key]) ? $this->plainText($settings[$key]) : '';
    }

    /** Website Settings "Website Under Construction" switch. */
    public function isUnderConstruction()
    {
        $settings = $this->CI->config->item('frontend_site_settings');

        return is_array($settings)
            && isset($settings['under_construction'])
            && $settings['under_construction'] === 'Yes';
    }

    /* ---------------------------------------------------------------------
     * Robots and keywords
     * ------------------------------------------------------------------ */

    /** "index, follow" style directive from the two Search Engine Access checkboxes. */
    public function robots($index, $follow)
    {
        return ((int) $index === 1 ? 'index' : 'noindex')
            . ', '
            . ((int) $follow === 1 ? 'follow' : 'nofollow');
    }

    /** Adds the snippet/image-preview directives to an indexable directive only. */
    public function withSnippetDirectives($robots)
    {
        $robots = trim((string) $robots);

        if (strpos($robots, 'noindex') !== false || strpos($robots, self::ROBOTS_SNIPPET_DIRECTIVES) !== false) {
            return $robots;
        }

        return $robots . ', ' . self::ROBOTS_SNIPPET_DIRECTIVES;
    }

    /** Comma-separated keywords, whitespace tidied. */
    public function keywords($value)
    {
        $parts = preg_split('/[,\x{060C}\r\n]+/u', $this->plainText($value));
        $parts = array_filter(array_map('trim', is_array($parts) ? $parts : array()));

        return implode(', ', array_unique($parts));
    }

    /* ---------------------------------------------------------------------
     * Records
     * ------------------------------------------------------------------ */

    /**
     * Search and sharing values for one record, merged over the caller's page config.
     *
     * Options:
     *   name         Record name; the title falls back to "name | site".
     *   description  Raw content, most specific first, for a blank meta description.
     *   image_dir    Upload directory holding the record's own sharing image.
     *   images       array(array(directory, file)) tried after the sharing image.
     *   image_alt    Alt text for the sharing image; defaults to the title.
     */
    public function entityConfig(array $row, array $config, array $options = array())
    {
        $options = array_merge(array(
            'name' => '',
            'description' => array(),
            'image_dir' => 'pages',
            'images' => array(),
            'image_alt' => '',
        ), $options);

        $title = $this->plainText(isset($row['page_title']) ? $row['page_title'] : '');
        if ($title === '' && $options['name'] !== '') {
            $title = $this->brandedTitle($options['name']);
        }
        if ($title !== '') {
            $config['page_title'] = $title;
        }

        $description = $this->description(
            isset($row['meta_description']) ? $row['meta_description'] : '',
            $options['description']
        );
        if ($description !== '') {
            $config['meta_description'] = $description;
        }

        $ogTitle = $this->plainText(isset($row['og_title']) ? $row['og_title'] : '');
        if ($ogTitle !== '') {
            $config['og_title'] = $ogTitle;
        }

        $ogDescription = $this->plainText(isset($row['og_description']) ? $row['og_description'] : '');
        if ($ogDescription !== '') {
            $config['og_description'] = $ogDescription;
        }

        $candidates = array(array($options['image_dir'], isset($row['og_image']) ? $row['og_image'] : ''));
        foreach ($options['images'] as $candidate) {
            $candidates[] = $candidate;
        }
        $image = $this->image($candidates, false);
        if (!empty($image)) {
            $config['og_image'] = $image['url'];
            $config['og_image_width'] = $image['width'];
            $config['og_image_height'] = $image['height'];
            $config['og_image_alt'] = $options['image_alt'] !== ''
                ? $this->plainText($options['image_alt'])
                : (isset($config['page_title']) ? $config['page_title'] : '');
        }

        if (isset($row['robots_index'], $row['robots_follow'])) {
            $config['robots'] = $this->robots($row['robots_index'], $row['robots_follow']);
        }

        $keywords = $this->keywords(isset($row['meta_keywords']) ? $row['meta_keywords'] : '');
        if ($keywords !== '') {
            $config['keywords'] = $keywords;
        }

        return $config;
    }

    /* ---------------------------------------------------------------------
     * Images
     * ------------------------------------------------------------------ */

    /**
     * The first usable image as an absolute, sharing-sized URL.
     *
     * Each candidate is array(upload directory, stored value). The stored value
     * is normally a bare filename; older records hold a path such as
     * "images/alam/tours/x.webp", which is resolved against the frontend assets.
     * With $useDefault the site-wide sharing image is returned when nothing
     * matches; otherwise an empty array.
     */
    public function image(array $candidates, $useDefault = true, $width = self::IMAGE_WIDTH, $height = self::IMAGE_HEIGHT)
    {
        foreach ($candidates as $candidate) {
            if (!is_array($candidate) || count($candidate) < 2) {
                continue;
            }

            $relative = $this->imagePath($candidate[0], $candidate[1]);
            if ($relative === '') {
                continue;
            }

            // JPEG is the most widely accepted format for link previews.
            $source = image_thumb_src($relative, (int) $height, (int) $width, 'jpg');
            if ($source !== '') {
                return array('url' => $source, 'width' => (int) $width, 'height' => (int) $height);
            }

            return $this->originalImage($relative);
        }

        return $useDefault ? $this->originalImage(self::DEFAULT_IMAGE) : array();
    }

    /** Project-relative path of an uploaded image, or '' when it is missing or unsafe. */
    public function imagePath($directory, $stored)
    {
        $stored = trim((string) $stored);
        if ($stored === '' || preg_match('#^[a-z][a-z0-9+.-]*://#i', $stored) === 1) {
            return '';
        }

        $stored = str_replace('\\', '/', $stored);
        if (strpos($stored, '..') !== false) {
            return '';
        }

        // Legacy records store a path relative to assets/frontend.
        $candidate = strpos($stored, 'images/') === 0
            ? 'assets/frontend/' . $stored
            : 'assets/frontend/images/' . trim((string) $directory, '/') . '/' . basename($stored);

        return is_file(FCPATH . $candidate) ? $candidate : '';
    }

    /** The unresized file with its real dimensions, for social crawlers. */
    private function originalImage($relative)
    {
        $size = @getimagesize(FCPATH . $relative);

        return array(
            'url' => base_url($relative),
            'width' => is_array($size) ? (int) $size[0] : self::IMAGE_WIDTH,
            'height' => is_array($size) ? (int) $size[1] : self::IMAGE_HEIGHT,
        );
    }

    /* ---------------------------------------------------------------------
     * URLs
     * ------------------------------------------------------------------ */

    public function homeUrl()
    {
        return base_url();
    }

    /** A CMS page URL from its managed slug. */
    public function pageUrl($slug)
    {
        return base_url($this->encodePath($slug));
    }

    public function blogPostUrl($slug)
    {
        return base_url(BLOG_URI . rawurlencode(trim((string) $slug)));
    }

    public function blogCategoryUrl($slug)
    {
        return base_url(BLOG_CATEGORY_URI . rawurlencode(trim((string) $slug)));
    }

    /** Percent-encodes non-ASCII characters so canonical and sitemap URLs agree. */
    public function encodeUrl($url)
    {
        $encoded = preg_replace_callback(
            '/[^\x21-\x7E]+/',
            static function ($match) {
                return rawurlencode($match[0]);
            },
            (string) $url
        );

        return is_string($encoded) ? $encoded : (string) $url;
    }

    private function encodePath($path)
    {
        $segments = explode('/', trim((string) $path, '/'));

        return implode('/', array_map('rawurlencode', $segments));
    }

    /* ---------------------------------------------------------------------
     * Structured data
     * ------------------------------------------------------------------ */

    /**
     * The JSON-LD @graph printed in every page head.
     *
     * $page keys: canonical, title, description, page_type, image (url/width/height), crumbs (label/href), nodes
     * (record-specific nodes), is_home, organization (see organizationNode()).
     */
    public function graph(array $page)
    {
        $organization = $this->organizationNode($page['organization']);
        $organizationId = $organization['@id'];
        $websiteId = $this->siteRootId() . '#website';

        $graph = array(
            $organization,
            array(
                '@type' => 'WebSite',
                '@id' => $websiteId,
                'url' => $this->siteRootUrl(),
                'name' => $page['organization']['name'],
                'inLanguage' => self::LANGUAGE,
                'publisher' => array('@id' => $organizationId),
            ),
        );

        $webPage = array(
            '@type' => $page['page_type'],
            '@id' => $page['canonical'] . '#webpage',
            'url' => $page['canonical'],
            'name' => $page['title'],
            'description' => $page['description'],
            'inLanguage' => self::LANGUAGE,
            'isPartOf' => array('@id' => $websiteId),
        );

        if (!empty($page['image'])) {
            $webPage['primaryImageOfPage'] = array(
                '@type' => 'ImageObject',
                'url' => $page['image']['url'],
                'width' => $page['image']['width'],
                'height' => $page['image']['height'],
            );
        }
        if (!empty($page['is_home'])) {
            $webPage['about'] = array('@id' => $organizationId);
        }

        $breadcrumb = $this->breadcrumbNode($page['crumbs'], $page['canonical']);
        if ($breadcrumb !== null) {
            $webPage['breadcrumb'] = array('@id' => $breadcrumb['@id']);
        }

        $graph[] = $webPage;
        if ($breadcrumb !== null) {
            $graph[] = $breadcrumb;
        }

        foreach ($page['nodes'] as $node) {
            if (is_array($node) && !empty($node)) {
                $graph[] = $node;
            }
        }

        return array('@context' => 'https://schema.org', '@graph' => $graph);
    }

    /** The business itself. Its @id is shared by every node that refers to the publisher. */
    public function organizationNode(array $facts)
    {
        $node = array(
            '@type' => 'TravelAgency',
            '@id' => $this->siteRootId() . '#organization',
            'name' => $facts['name'],
            'url' => $facts['url'],
            'telephone' => $facts['telephone'],
            'email' => $facts['email'],
            'areaServed' => $facts['area_served'],
            'address' => $facts['address'],
            'sameAs' => $facts['same_as'],
            'knowsLanguage' => array(self::LANGUAGE),
        );

        if ($facts['description'] !== '') {
            $node['description'] = $facts['description'];
        }
        if ($facts['logo'] !== '') {
            $node['logo'] = array('@type' => 'ImageObject', 'url' => $facts['logo']);
            $node['image'] = $facts['logo'];
        }
        if ($facts['license_value'] !== '') {
            $node['identifier'] = array(
                '@type' => 'PropertyValue',
                'name' => $facts['license_label'],
                'value' => $facts['license_value'],
            );
        }

        return $this->withoutEmpty($node);
    }

    /** BreadcrumbList from array(label, href); the last crumb is the current page. */
    public function breadcrumbNode(array $crumbs, $canonical)
    {
        $items = array();
        $count = count($crumbs);

        foreach (array_values($crumbs) as $index => $crumb) {
            $label = $this->plainText(isset($crumb['label']) ? $crumb['label'] : '');
            if ($label === '') {
                continue;
            }

            $href = isset($crumb['href']) ? trim((string) $crumb['href']) : '';
            if ($href === '' && $index === $count - 1) {
                $href = $canonical;
            }
            if ($href === '') {
                continue;
            }

            $items[] = array(
                '@type' => 'ListItem',
                'position' => count($items) + 1,
                'name' => $label,
                'item' => $this->encodeUrl($href),
            );
        }

        if (count($items) < 2) {
            return null;
        }

        return array(
            '@type' => 'BreadcrumbList',
            '@id' => $canonical . '#breadcrumb',
            'itemListElement' => $items,
        );
    }

    /** A blog post as an Article published by the agency. */
    public function articleNode(array $article)
    {
        $node = array(
            '@type' => 'Article',
            '@id' => $article['url'] . '#article',
            'headline' => mb_substr($article['headline'], 0, 110, 'UTF-8'),
            'description' => $article['description'],
            'url' => $article['url'],
            'mainEntityOfPage' => array('@id' => $article['url'] . '#webpage'),
            'inLanguage' => self::LANGUAGE,
            'datePublished' => $article['published'],
            'dateModified' => $article['modified'] !== '' ? $article['modified'] : $article['published'],
            'publisher' => array('@id' => $this->siteRootId() . '#organization'),
            'author' => $article['author'] !== ''
                ? array('@type' => 'Person', 'name' => $article['author'])
                : array('@id' => $this->siteRootId() . '#organization'),
        );

        if (!empty($article['image'])) {
            $node['image'] = array($article['image']);
        }
        if ($article['section'] !== '') {
            $node['articleSection'] = $article['section'];
        }
        if ($article['keywords'] !== '') {
            $node['keywords'] = $article['keywords'];
        }

        return $this->withoutEmpty($node);
    }

    /** A listing page's items as an ItemList; each item is array(url, name). */
    public function itemListNode($canonical, array $items)
    {
        $elements = array();

        foreach ($items as $item) {
            if (empty($item['url']) || empty($item['name'])) {
                continue;
            }

            $elements[] = array(
                '@type' => 'ListItem',
                'position' => count($elements) + 1,
                'url' => $item['url'],
                'name' => $this->plainText($item['name']),
            );
        }

        if (empty($elements)) {
            return array();
        }

        return array(
            '@type' => 'ItemList',
            '@id' => $canonical . '#list',
            'numberOfItems' => count($elements),
            'itemListElement' => $elements,
        );
    }

    /** ISO 8601 timestamp for a database DATE/DATETIME value, or ''. */
    public function isoDate($value)
    {
        $value = trim((string) $value);
        if ($value === '' || strpos($value, '0000-00-00') === 0) {
            return '';
        }

        $time = strtotime($value);

        return $time !== false ? date('c', $time) : '';
    }

    /** Identifies the organization and website. */
    private function siteRootUrl()
    {
        return $this->homeUrl();
    }

    private function siteRootId()
    {
        return $this->siteRootUrl();
    }

    /** Drops null and empty-string values; keeps 0 and non-empty arrays. */
    private function withoutEmpty(array $node)
    {
        return array_filter($node, static function ($value) {
            return $value !== null && $value !== '' && $value !== array();
        });
    }
}
