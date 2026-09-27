<?php

defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Resolve prototype paths through CodeIgniter's application base URL.
 *
 * The static frontend stores its runtime assets under assets/frontend while
 * internal page paths remain at the application root.
 */
function site_base_url(string $path = ''): string
{
    $path = ltrim($path, '/');

    if (
        $path !== ''
        && preg_match('#^(css|images|js|vendor)/#', $path) === 1
    ) {
        $path = 'assets/frontend/' . $path;
    }

    return base_url($path);
}

/**
 * Full current request URL, including its query string.
 */
function site_current_url(): string
{
    $url = current_url();
    $query = (string) ($_SERVER['QUERY_STRING'] ?? '');

    return $query === '' ? $url : $url . '?' . $query;
}

/* =========================================================================
 * Alam Al-Munawara — frontend bootstrap
 * ====================================================================== */

/* i18n first: the data accessors below call current_locale() and
   localize() as they build their records. */
require_once __DIR__ . '/inc/i18n.php';
require_once __DIR__ . '/inc/data.php';
require_once __DIR__ . '/inc/experiences.php';
require_once __DIR__ . '/inc/components.php';
require_once __DIR__ . '/inc/home.php';
require_once __DIR__ . '/inc/about.php';
require_once __DIR__ . '/inc/tours.php';
require_once __DIR__ . '/inc/experiences_listing.php';
require_once __DIR__ . '/inc/guides_listing.php';
require_once __DIR__ . '/inc/blog.php';

/**
 * Asset URL with a cache-busting stamp so CSS/JS changes show up immediately
 * during development.
 */
function asset(string $path): string
{
    $file = FCPATH . 'assets/frontend/' . ltrim($path, '/');
    $url  = site_base_url($path);
    if (is_file($file)) {
        $url .= '?v=' . filemtime($file);
    }
    return $url;
}

/**
 * Responsive `srcset` for a photograph, built from its width variants.
 *
 * Every photograph in images/alam/ ships with a smaller sibling — cards get
 * `-600`, full-bleed images get `-1280`. This returns a ready-to-print srcset
 * attribute value, or an empty string when the variant is not on disk, so a
 * missing sibling degrades to the single `src` instead of a 404.
 */
function srcset(string $path, string $suffix = '-600', int $smallW = 600, int $largeW = 1200): string
{
    $variant = preg_replace('/\.(webp|jpg|png)$/i', $suffix . '.$1', $path);
    if (
        $variant === null
        || $variant === $path
        || !is_file(FCPATH . 'assets/frontend/' . ltrim($variant, '/'))
    ) {
        return '';
    }

    return site_base_url($variant) . ' ' . $smallW . 'w, '
        . site_base_url($path) . ' ' . $largeW . 'w';
}

/** Map managed page slugs onto the public frontend route contract. */
function managed_page_script(array $page): string
{
    $pageId = (int) ($page['page_id'] ?? 0);
    $pageIds = [
        1 => 'index.php',
        2 => 'about-us.php',
        3 => 'tours.php',
        4 => 'experiences.php',
        5 => 'plan-your-trip.php',
        6 => 'faqs.php',
        7 => 'contact.php',
        8 => 'blog.php',
        9 => 'privacy-policy.php',
        10 => 'cancellation-policy.php',
        11 => 'tour-guides.php',
    ];

    return $pageIds[$pageId] ?? '';
}

/** Resolve a core page identifier to its current CMS slug. */
function managed_route_segment(string $script, string $locale): string
{
    if ($script === 'index.php') {
        return '';
    }

    $CI =& get_instance();
    $pages = $CI->config->item('frontend_pages');

    if (is_array($pages)) {
        foreach ($pages as $page) {
            if (!is_array($page) || managed_page_script($page) !== $script) {
                continue;
            }

            $field = $locale === 'ar' ? 'page_slug_ar' : 'page_slug';
            $slug = trim((string) ($page[$field] ?? ''));
            if ($slug === '') {
                $slug = trim((string) ($page['page_slug'] ?? ''));
            }

            if ($slug !== '') {
                return $slug;
            }
        }
    }

    $routes = route_map();

    return isset($routes[$script]) ? $routes[$script] : '';
}

/** Choose the configured menu label for the active frontend locale. */
function managed_menu_label(array $page): string
{
    $field = current_locale() === 'ar' ? 'menu_name_ar' : 'menu_name';
    $value = trim((string) ($page[$field] ?? ''));

    return html_entity_decode($value, ENT_QUOTES, 'UTF-8');
}

/** Return one published page's localized database-managed menu name. */
function managed_page_menu_label(int $pageId): string
{
    $CI =& get_instance();
    $pages = $CI->config->item('frontend_pages');

    return is_array($pages) && isset($pages[$pageId])
        ? managed_menu_label($pages[$pageId])
        : '';
}

/** Convert MenuModel/FootModel nodes to the small shape the views consume. */
function map_managed_menu(array $nodes): array
{
    $menu = [];

    foreach ($nodes as $node) {
        if (!is_array($node)) {
            continue;
        }

        $slugField = current_locale() === 'ar' ? 'page_slug_ar' : 'page_slug';
        $slug = trim((string) ($node[$slugField] ?? ''));
        $label = managed_menu_label($node);
        if ($slug === '' || $label === '') {
            continue;
        }

        $children = isset($node['children']) && is_array($node['children'])
            ? map_managed_menu($node['children'])
            : [];
        $pageId = (int) ($node['page_id'] ?? 0);
        /* Page 1 is the home page: link to the site root, the same as the
           logo/branding link, rather than its own managed slug. */
        $href = $pageId === 1
            ? base_url(current_locale())
            : base_url(current_locale() . '/' . ltrim($slug, '/'));
        $menu[] = [
            'page_id' => $pageId,
            'key' => trim((string) ($node['page_slug'] ?? '')),
            'label' => $label,
            'href' => $href,
            'children' => $children,
        ];
    }

    return $menu;
}

/** Primary navigation managed under Manage > Menu. */
function nav(): array
{
    $CI =& get_instance();
    $managed = $CI->config->item('frontend_navigation');

    return is_array($managed) ? map_managed_menu($managed) : [];
}

/** Flat footer navigation managed under the two Footer Navigation screens. */
function footer_nav(string $type): array
{
    $CI =& get_instance();
    $managed = $CI->config->item('frontend_footer_navigation');

    return is_array($managed)
        && isset($managed[$type])
        && is_array($managed[$type])
        ? map_managed_menu($managed[$type])
        : [];
}

/** Whether a menu node or one of its children represents the current page. */
function nav_item_is_active(array $item, string $activeKey): bool
{
    if ($activeKey !== '' && ($item['key'] ?? '') === $activeKey) {
        return true;
    }

    $itemPath = parse_url((string) ($item['href'] ?? ''), PHP_URL_PATH);
    $currentPath = parse_url(site_current_url(), PHP_URL_PATH);
    if (
        is_string($itemPath)
        && is_string($currentPath)
        && rtrim($itemPath, '/') === rtrim($currentPath, '/')
    ) {
        return true;
    }

    $pageId = (int) ($item['page_id'] ?? 0);
    if (
        $pageId > 0
        && managed_page_script(['page_id' => $pageId]) === current_script()
    ) {
        return true;
    }

    foreach (($item['children'] ?? []) as $child) {
        if (is_array($child) && nav_item_is_active($child, $activeKey)) {
            return true;
        }
    }

    return false;
}

/* -------------------------------------------------------------------------
 * Partial page navigation (js/htmx-init.js)
 *
 * Pages whose content can be swapped into <main> without a full page load,
 * keyed by managed page id. A page's own plugin files (the home page's
 * slider and date picker, the tour page's lightbox) are loaded on demand by
 * js/htmx-init.js the first time a swap needs them, and every widget on these
 * pages can be initialised again on swapped content. Every other CMS page
 * (page.php) takes part as well. Pages with forms,
 * reCAPTCHA, booking or payment (contact, plan your visit, book, review)
 * stay a normal page load. Add a page here only after checking that its
 * widgets are covered by initRegion() in js/htmx-init.js.
 * ---------------------------------------------------------------------- */
function htmx_navigation_pages(): array
{
    return [
        1 => 'index.php',
        2 => 'about-us.php',
        3 => 'tours.php',
        4 => 'experiences.php',
        6 => 'faqs.php',
        8 => 'blog.php',
        9 => 'privacy-policy.php',
        10 => 'cancellation-policy.php',
        11 => 'tour-guides.php',
    ];
}

/** Whether the page being rendered takes part in partial navigation. */
function htmx_navigation_enabled(): bool
{
    // Blog posts and tour/experience pages are records, not managed pages,
    // so they have no page id above. page.php renders every other CMS page
    // created in Manage > Web Pages (see htmx_navigation_page_id()).
    $scripts = array_merge(
        array_values(htmx_navigation_pages()),
        ['blog-post.php', 'tour-details.php', 'page.php']
    );

    return in_array(current_script(), $scripts, true);
}

/**
 * ` hx-boost="true"` for a link to a page that always takes part in partial
 * navigation (a tour, an experience, the home page), when this page does too.
 */
function htmx_boost_attr(): string
{
    return htmx_navigation_enabled() ? ' hx-boost="true"' : '';
}

/**
 * Whether a managed page id takes part in partial navigation: a core page on
 * the list above, or any CMS page that is not a core page at all (those are
 * rendered by page.php). Core pages left off the list — plan your visit,
 * contact — never do.
 */
function htmx_navigation_page_id(int $pageId): bool
{
    if ($pageId <= 0) {
        return false;
    }

    return isset(htmx_navigation_pages()[$pageId])
        || managed_page_script(['page_id' => $pageId]) === '';
}

/**
 * ` hx-boost="true"` for a managed menu link when both this page and the
 * link's page take part in partial navigation; otherwise nothing, and the
 * link is an ordinary page load.
 */
function htmx_nav_attr(array $item): string
{
    $pageId = (int) ($item['page_id'] ?? 0);

    return htmx_navigation_enabled() && htmx_navigation_page_id($pageId)
        ? ' hx-boost="true"'
        : '';
}

/* -------------------------------------------------------------------------
 * Shared vendor bundles
 *
 * A page opts into a bundle with a flag in $config; header.php and footer.php
 * expand it into the real asset paths. Keeping the paths here means a bundle
 * is declared once and can never be half-loaded or loaded twice on a page.
 * ---------------------------------------------------------------------- */

/** Vendor stylesheets a page has opted into, ahead of its own $config['styles']. */
function page_styles(array $config): array
{
    $styles = [];
    if (!empty($config['select2'])) {
        $styles[] = 'vendor/select2/select2.min.css';
    }
    if (!empty($config['datepicker'])) {
        $styles[] = 'vendor/flatpickr/flatpickr.min.css';
    }
    if (!empty($config['phone'])) {
        $styles[] = 'vendor/intl-tel-input/css/intlTelInput.min.css';
    }
    foreach (($config['styles'] ?? []) as $style) {
        $styles[] = $style;
    }
    return array_values(array_unique($styles));
}

/**
 * Page scripts, vendor bundles first. Every tag is emitted with `defer`, so the
 * array order is also the execution order: jQuery, then Select2, then the shared
 * initialiser, then whatever the page asked for.
 */
function page_scripts(array $config): array
{
    /* Locale bundles for the plugins that need one. Each is the vendor's own
       unmodified file and must load AFTER the plugin core and BEFORE our
       initialiser — Select2's registers an AMD module on the plugin's bundled
       loader, Flatpickr's writes into window.flatpickr.l10ns. English needs
       neither: it is what both plugins ship with. intl-tel-input takes its
       interface strings through an option instead, so it has no bundle here
       (see js/phone-input.js and vendor/intl-tel-input/VERSION.txt). */
    $rtl = locale_is_rtl();

    $scripts = [];
    if (!empty($config['select2'])) {
        $scripts[] = 'vendor/jquery/jquery.min.js';
        $scripts[] = 'vendor/select2/select2.min.js';
        if ($rtl) {
            $scripts[] = 'vendor/select2/i18n/ar.js';
        }
        $scripts[] = 'js/select2-init.js';
    }
    if (!empty($config['datepicker'])) {
        $scripts[] = 'vendor/flatpickr/flatpickr.min.js';
        if ($rtl) {
            $scripts[] = 'vendor/flatpickr/l10n/ar.js';
        }
        $scripts[] = 'js/datepicker.js';
    }
    if (!empty($config['phone'])) {
        $scripts[] = 'vendor/intl-tel-input/js/intlTelInput.min.js';
        $scripts[] = 'js/phone-input.js';
    }
    if (!empty($config['validate'])) {
        /* Same jQuery Validation Plugin build already vetted and used by the
           manage/admin forms (assets/admin/vendor/jquery-validation) — not a
           new dependency, just made available to the public frontend too. */
        $scripts[] = 'vendor/jquery/jquery.min.js';
        $scripts[] = 'vendor/jquery-validation/jquery.validate.min.js';
        $scripts[] = 'js/form-validate.js';
    }
    if (!empty($config['htmx'])) {
        /* Partial page navigation (progressive enhancement). The initialiser
           configures HTMX, so it follows it; Alpine loads last so the
           components htmx-init.js registers on `alpine:init` exist before
           Alpine starts. */
        $scripts[] = 'vendor/htmx/htmx.min.js';
        $scripts[] = 'js/htmx-init.js';
        $scripts[] = 'vendor/alpinejs/alpine.min.js';
    }
    foreach (($config['scripts'] ?? []) as $s) {
        $scripts[] = $s;
    }
    return array_values(array_unique($scripts));
}

/** Page metadata with sensible Alam Al-Munawara defaults. */
function site_meta(array $config, string $key, string $fallback = ''): string
{
    $v = $config[$key] ?? '';
    return $v !== '' ? (string) $v : $fallback;
}

/**
 * URL-safe slug from a human label ("Visiting Madinah" -> "visiting-madinah").
 * Used for blog category links so the query string stays readable.
 */
function slug(string $text): string
{
    $slug = strtolower(trim($text));
    $slug = preg_replace('/[^a-z0-9]+/', '-', $slug) ?? '';
    return trim($slug, '-');
}
