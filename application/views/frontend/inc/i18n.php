<?php
/**
 * Alam Al-Munawara — localisation layer.
 *
 * The site is served from one set of CodeIgniter views in two locales. There
 * is no `ar/` copy of any page: CI routes send `/ar/<page>` to the same
 * controller action and the frontend controller selects the language before
 * rendering.
 *
 *   /tours            ->  tours.php   locale en   dir ltr
 *   /ar/tours         ->  tours.php   locale ar   dir rtl
 *   /ar/tours?x=y     ->  tours.php   locale ar   query preserved
 *
 * Four things live here:
 *
 *   Detection   current_locale() and friends. English is the fallback and
 *               only 'en' and 'ar' are ever accepted, so a hand-typed locale
 *               cannot push the site into an undefined state.
 *   Catalogues  t() / tn() read CI language files — flat arrays of
 *               stable dotted keys, never English sentences as keys.
 *   URLs        url() builds a locale-correct internal link from a script
 *               name; alt_locale_url() returns the current page in the
 *               other locale with its query string intact.
 *   Content     localize() overlays translated text fields onto a data
 *               record without duplicating the record. IDs, slugs, prices,
 *               image paths and availability stay in inc/data.php only.
 *
 * base_url() is deliberately NOT locale-aware: css/, js/, images/ and vendor/
 * must resolve at the application root in both locales, never under /ar/.
 *
 * PHP 7.4 — no named arguments, no match, no enums.
 */


/* =========================================================================
 * Supported locales
 * ====================================================================== */

/** The only locale codes this application will ever act on. */
function supported_locales(): array
{
    return ['en', 'ar'];
}

/** The locale used when nothing else is determined. */
function default_locale(): string
{
    return 'en';
}

/** Map a public locale code to its CodeIgniter language directory. */
function language_idiom(string $locale): string
{
    return $locale === 'ar' ? 'arabic' : 'english';
}

/**
 * Frontend view identifiers mapped to their public URL segments. Keep this in
 * step with application/config/routes.php.
 */
function route_map(): array
{
    return [
        'index.php'               => '',
        'about-us.php'            => 'about-us',
        'tours.php'               => 'tours',
        'tour-details.php'        => 'tour-details',
        'experiences.php'         => 'experiences',
        'tour-guides.php'         => 'tour-guides',
        'plan-your-trip.php'      => 'plan-your-trip',
        'faqs.php'                => 'faqs',
        'contact.php'             => 'contact',
        'blog.php'                => 'blog',
        'book.php'                => 'book',
        'review.php'              => 'review',
        'privacy-policy.php'      => 'privacy-policy',
        'cancellation-policy.php' => 'cancellation-policy',
        'error-404.php'           => 'error-404',
    ];
}

/* =========================================================================
 * Detection
 *
 * The controller-provided locale is authoritative. Environment and request
 * path detection remain as safe fallbacks for isolated rendering and tests:
 *
 *   1. FRONTEND_LOCALE, when provided by a trusted server rewrite. Apache
 *      exposes it as REDIRECT_FRONTEND_LOCALE (and gains another REDIRECT_ prefix
 *      per internal redirect), and a client cannot set it — an inbound
 *      `FRONTEND_LOCALE` header would arrive as HTTP_FRONTEND_LOCALE, which is never
 *      read here.
 *   2. The request path. Belt and braces for a server whose configuration
 *      swallowed the environment variable, and the reason the locale is never
 *      taken from $_GET: a query parameter would let /tours?locale=ar render
 *      Arabic copy at an English URL, which is a duplicate-content problem as
 *      well as a correctness one.
 * ====================================================================== */

/** The locale this request is being rendered in. Cached for the request. */
function current_locale(): string
{
    static $locale = null;
    if ($locale !== null) {
        return $locale;
    }

    if (
        defined('FRONTEND_LOCALE')
        && is_supported_locale((string) FRONTEND_LOCALE)
    ) {
        $locale = (string) FRONTEND_LOCALE;

        return $locale;
    }

    $locale = default_locale();

    // 1. The Apache environment variable set by the rewrite.
    foreach (['FRONTEND_LOCALE', 'REDIRECT_FRONTEND_LOCALE', 'REDIRECT_REDIRECT_FRONTEND_LOCALE'] as $key) {
        if (!empty($_SERVER[$key]) && is_supported_locale((string) $_SERVER[$key])) {
            $locale = (string) $_SERVER[$key];
            return $locale;
        }
    }

    // 2. The request path, relative to the application root.
    $prefix = request_locale_prefix();
    if ($prefix !== '') {
        $locale = $prefix;
    }

    return $locale;
}

/** True when $code is one of the locales this application serves. */
function is_supported_locale(string $code): bool
{
    return in_array($code, supported_locales(), true);
}

/**
 * The locale segment at the front of the current request path, or ''.
 *
 * The application may be installed in a subfolder, so the folder base_url()
 * derives from SCRIPT_NAME is stripped before the first segment is read.
 */
function request_locale_prefix(): string
{
    $uri = (string) ($_SERVER['REQUEST_URI'] ?? '/');

    // Query string is not part of the path.
    $qmark = strpos($uri, '?');
    if ($qmark !== false) {
        $uri = substr($uri, 0, $qmark);
    }

    // Strip the folder the application is installed in, if any.
    $script = (string) ($_SERVER['SCRIPT_NAME'] ?? '/');
    $dir    = rtrim(str_replace('\\', '/', dirname($script)), '/');
    if ($dir !== '' && $dir !== '.' && strpos($uri, $dir) === 0) {
        $uri = substr($uri, strlen($dir));
    }

    $first = strtolower(trim(explode('/', trim($uri, '/'))[0] ?? ''));

    return ($first !== '' && is_supported_locale($first)) ? $first : '';
}

/** 'rtl' for Arabic, 'ltr' otherwise. */
function locale_dir(?string $locale = null): string
{
    $locale = $locale !== null ? $locale : current_locale();
    return $locale === 'ar' ? 'rtl' : 'ltr';
}

/** True when the given (or current) locale reads right to left. */
function locale_is_rtl(?string $locale = null): bool
{
    return locale_dir($locale) === 'rtl';
}

/** The locale a language switch would move to — the other supported one. */
function other_locale(?string $locale = null): string
{
    $locale = $locale !== null ? $locale : current_locale();
    foreach (supported_locales() as $code) {
        if ($code !== $locale) {
            return $code;
        }
    }
    return default_locale();
}

/**
 * The BCP 47 tag for a locale, used for `lang`, hreflang and Intl.* in the
 * browser. Arabic is served to a Saudi audience, so it carries the region.
 */
function locale_tag(?string $locale = null): string
{
    $locale = $locale !== null ? $locale : current_locale();
    $tags = ['en' => 'en', 'ar' => 'ar-SA'];
    return isset($tags[$locale]) ? $tags[$locale] : $locale;
}

/** The Open Graph locale token for a locale (og:locale / og:locale:alternate). */
function og_locale(?string $locale = null): string
{
    $locale = $locale !== null ? $locale : current_locale();
    $tags = ['en' => 'en_US', 'ar' => 'ar_SA'];
    return isset($tags[$locale]) ? $tags[$locale] : $locale;
}

/* =========================================================================
 * Translation catalogues
 * ====================================================================== */

/**
 * The whole catalogue for a locale, loaded once per request.
 *
 * Catalogues are loaded through CodeIgniter's language library from
 * application/language/<idiom>/frontend_lang.php.
 */
function catalog(?string $locale = null): array
{
    static $loaded = [];

    $locale = $locale !== null ? $locale : current_locale();
    if (!is_supported_locale($locale)) {
        $locale = default_locale();
    }
    if (isset($loaded[$locale])) {
        return $loaded[$locale];
    }

    $CI =& get_instance();
    $data = $CI->lang->load(
        'frontend',
        language_idiom($locale),
        true
    );
    $loaded[$locale] = is_array($data) ? $data : [];

    return $loaded[$locale];
}

/**
 * Translate one key.
 *
 * Placeholders are written `{name}` in the catalogue and filled from $params.
 * Interpolation is a plain strtr() over a fixed set of tokens — there is no
 * eval, no callable in a catalogue and no HTML in a translated string. Where a
 * sentence genuinely needs markup (a link inside a paragraph), the markup stays
 * in the template and only the text fragments around it are translated.
 *
 * A missing key falls back to English, then to the key itself, so a gap shows
 * up as a visible identifier during review rather than as an empty element.
 *
 * The return value is NOT escaped: call sites print it through e(), the same
 * as every other dynamic value in this codebase.
 */
function t(string $key, array $params = [], ?string $locale = null): string
{
    $locale = $locale !== null ? $locale : current_locale();
    $value = false;

    if ($locale === current_locale()) {
        $CI =& get_instance();
        $value = $CI->lang->line($key, false);
    }

    if (!is_string($value)) {
        $catalog = catalog($locale);
        $value = isset($catalog[$key]) && is_string($catalog[$key])
            ? $catalog[$key]
            : false;
    }

    if (!is_string($value)) {
        $fallback = catalog(default_locale());
        $value = (isset($fallback[$key]) && is_string($fallback[$key])) ? $fallback[$key] : $key;
    }

    return $params ? interpolate($value, $params) : $value;
}

/**
 * Translate a counted key, choosing the plural form the locale actually uses.
 *
 * The catalogue holds one entry per CLDR plural category the locale needs,
 * suffixed onto the key: `tours.count.one`, `tours.count.other` for English;
 * zero / one / two / few / many / other for Arabic. `{count}` is filled in
 * automatically and can be overridden through $params like any other token.
 */
function tn(string $key, int $count, array $params = [], ?string $locale = null): string
{
    $locale   = $locale !== null ? $locale : current_locale();
    $category = plural_category($count, $locale);
    $catalog  = catalog($locale);

    $candidates = [$key . '.' . $category, $key . '.other', $key];
    $value = null;
    foreach ($candidates as $candidate) {
        if (isset($catalog[$candidate]) && is_string($catalog[$candidate])) {
            $value = $catalog[$candidate];
            break;
        }
    }

    if ($value === null) {
        // Same walk again over English before giving up on the key itself.
        $fallback = catalog(default_locale());
        foreach ([$key . '.' . plural_category($count, default_locale()), $key . '.other', $key] as $candidate) {
            if (isset($fallback[$candidate]) && is_string($fallback[$candidate])) {
                $value = $fallback[$candidate];
                break;
            }
        }
    }
    if ($value === null) {
        $value = $key;
    }

    $params = array_merge(['count' => number($count, $locale)], $params);

    return interpolate($value, $params);
}

/**
 * A list-valued catalogue entry — the bullet lists in the policy pages.
 *
 * Most catalogue values are strings. A few are lists, because the thing being
 * translated is a list: `privacy.collect.given.items` is eight bullets, and
 * splitting it into eight numbered keys would make the Arabic harder to review,
 * not easier. Every element is still a plain string, escaped by the caller.
 */
function t_list(string $key, ?string $locale = null): array
{
    $locale = $locale !== null ? $locale : current_locale();
    $value = false;

    if ($locale === current_locale()) {
        $CI =& get_instance();
        $value = $CI->lang->line($key, false);
    }

    if (is_array($value)) {
        return $value;
    }

    $catalog = catalog($locale);

    if (isset($catalog[$key]) && is_array($catalog[$key])) {
        return $catalog[$key];
    }

    $fallback = catalog(default_locale());
    if (isset($fallback[$key]) && is_array($fallback[$key])) {
        return $fallback[$key];
    }

    return [];
}

/**
 * The CLDR plural category for $n in $locale.
 *
 * Arabic uses all six; English uses one/other. Mirrored in js/main.js so a
 * count rendered by PHP and the same count re-rendered by a filter agree.
 */
function plural_category(int $n, ?string $locale = null): string
{
    $locale = $locale !== null ? $locale : current_locale();

    if ($locale !== 'ar') {
        return $n === 1 ? 'one' : 'other';
    }

    if ($n === 0) {
        return 'zero';
    }
    if ($n === 1) {
        return 'one';
    }
    if ($n === 2) {
        return 'two';
    }

    $mod100 = $n % 100;
    if ($mod100 >= 3 && $mod100 <= 10) {
        return 'few';
    }
    if ($mod100 >= 11 && $mod100 <= 99) {
        return 'many';
    }

    return 'other';
}

/**
 * Fill `{token}` placeholders from a flat array of scalars.
 *
 * Values are cast to string and substituted literally. Nothing is executed and
 * no value is treated as markup — the result is escaped by the caller.
 */
function interpolate(string $text, array $params): string
{
    if (!$params) {
        return $text;
    }

    $replace = [];
    foreach ($params as $name => $value) {
        if (is_array($value) || is_object($value)) {
            continue;
        }
        if (is_bool($value)) {
            $value = $value ? '1' : '0';
        }
        $replace['{' . $name . '}'] = (string) $value;
    }

    return strtr($text, $replace);
}

/* =========================================================================
 * Locale-aware formatting
 *
 * Numerals stay Western (0-9) in both locales. Saudi sites overwhelmingly use
 * them, prices and telephone numbers are read alongside Latin text, and the
 * value a form posts must not change shape with the display locale.
 * ====================================================================== */

/** A grouped integer, e.g. 1,250. */
function number($amount, ?string $locale = null): string
{
    return number_format((float) $amount, 0, '.', ',');
}

/** A frontend-formatted date from a date string or Unix timestamp. */
function site_date(string $ymd, ?string $locale = null): string
{
    $locale = $locale !== null ? $locale : current_locale();
    $time = strtotime($ymd);
    if ($time === false) {
        return $ymd;
    }

    $format = FRONTEND_DATE_FORMAT;
    $month = t('month.' . date('n', $time), [], $locale);
    $output = '';
    $escaped = false;

    for ($index = 0, $length = strlen($format); $index < $length; $index++) {
        $token = $format[$index];

        if ($escaped) {
            $output .= $token;
            $escaped = false;
            continue;
        }

        if ($token === '\\') {
            $escaped = true;
            continue;
        }

        if ($token === 'F') {
            $output .= $month;
            continue;
        }

        if ($token === 'M' && $locale === 'ar') {
            $output .= $month;
            continue;
        }

        $output .= date($token, $time);
    }

    return $output;
}

/* =========================================================================
 * URLs
 * ====================================================================== */

/**
 * A locale-correct internal URL.
 *
 * $script is the page's real filename ('tours.php'); the locale decides the
 * shape of the link:
 *
 *   url('tours.php')                       en -> /en/{CMS slug}
 *   url('tours.php', [], 'ar')             ar -> /ar/{CMS Arabic slug}
 *   url('tour-details.php', ['tour' => x]) en -> /en/tour-details?tour=x
 *
 * A script that is not in route_map() (there are none today) falls back to
 * base_url() with the filename, which keeps an unrouted page reachable rather
 * than emitting a broken link.
 */
function url(string $script = 'index.php', array $params = [], ?string $locale = null): string
{
    $locale = $locale !== null ? $locale : current_locale();
    if (!is_supported_locale($locale)) {
        $locale = default_locale();
    }

    $map = route_map();
    if (!isset($map[$script])) {
        $path = ltrim($script, '/');
    } else {
        $segment = managed_route_segment($script, $locale);
        $path = rtrim($locale . '/' . $segment, '/') . ($segment === '' ? '/' : '');
    }

    $url = site_base_url($path);

    if ($params) {
        $query = http_build_query($params);
        if ($query !== '') {
            $url .= (strpos($url, '?') === false ? '?' : '&') . $query;
        }
    }

    return $url;
}

/** A locale-correct Blog post URL using the configured public URI prefix. */
function blog_post_url(string $slug, ?string $locale = null): string
{
    $locale = $locale !== null ? $locale : current_locale();
    if (!is_supported_locale($locale)) {
        $locale = default_locale();
    }

    return site_base_url($locale . '/' . BLOG_URI . rawurlencode(trim($slug)));
}

/** A locale-correct Tour detail URL using the configured public URI prefix. */
function tour_url(string $slug, ?string $locale = null): string
{
    $locale = $locale !== null ? $locale : current_locale();
    if (!is_supported_locale($locale)) {
        $locale = default_locale();
    }

    return site_base_url($locale . '/' . TOUR_URI . rawurlencode(trim($slug)));
}

/** A locale-correct Experience detail URL using the configured public URI prefix. */
function experience_url(string $slug, ?string $locale = null): string
{
    $locale = $locale !== null ? $locale : current_locale();
    if (!is_supported_locale($locale)) {
        $locale = default_locale();
    }

    return site_base_url($locale . '/' . EXPERIENCE_URI . rawurlencode(trim($slug)));
}

/** A locale-correct Blog category URL using the configured public URI prefix. */
function blog_category_url(string $slug, ?string $locale = null): string
{
    $locale = $locale !== null ? $locale : current_locale();
    if (!is_supported_locale($locale)) {
        $locale = default_locale();
    }

    $slug = trim($slug);
    if ($slug === '') {
        return site_base_url($locale . '/' . rtrim(BLOG_URI, '/'));
    }

    return site_base_url($locale . '/' . BLOG_CATEGORY_URI . rawurlencode($slug));
}

/** The page script currently executing, e.g. 'tours.php'. */
function current_script(): string
{
    if (defined('FRONTEND_SCRIPT')) {
        return (string) FRONTEND_SCRIPT;
    }

    $script = basename((string) ($_SERVER['SCRIPT_NAME'] ?? 'index.php'));
    return $script !== '' ? $script : 'index.php';
}

/** The current request's query parameters, as they arrived. */
function current_query(): array
{
    $query = (string) ($_SERVER['QUERY_STRING'] ?? '');
    if ($query === '') {
        return [];
    }
    $out = [];
    parse_str($query, $out);
    return is_array($out) ? $out : [];
}

/**
 * The slug a record has in $locale. A record's English slug is defined as
 * FRONTEND_*_SLUG and its Arabic one as FRONTEND_*_SLUG_AR; a record with no
 * Arabic slug is served at its English one in both locales.
 */
function localized_slug_constant(string $constant, string $locale): string
{
    $slug = trim((string) constant($constant));

    if ($locale === 'ar' && defined($constant . '_AR')) {
        $arabic = trim((string) constant($constant . '_AR'));
        if ($arabic !== '') {
            return $arabic;
        }
    }

    return $slug;
}

/**
 * The canonical URL of the page being rendered, in $locale.
 *
 * Built from the route map rather than from REQUEST_URI, so /tours.php and
 * /tours both canonicalise to the same address, and every query parameter the
 * page actually varies on (tour, vehicle, language, page …) survives the
 * switch. Blog post, category and tour slugs are preserved in their URI paths,
 * each in the slug that belongs to $locale.
 *
 * $query overrides the request's query string; canonical_url_in() uses it to
 * keep tracking parameters out of the address search engines index.
 */
function current_url_in(?string $locale = null, ?array $query = null): string
{
    if (defined('FRONTEND_REVIEW_BOOKING_ID') && defined('FRONTEND_REVIEW_TOKEN')) {
        $locale = $locale !== null ? $locale : current_locale();
        if (!is_supported_locale($locale)) {
            $locale = default_locale();
        }

        return site_base_url(
            $locale . '/review/' . (int) FRONTEND_REVIEW_BOOKING_ID . '/'
            . rawurlencode((string) FRONTEND_REVIEW_TOKEN)
        );
    }

    $slugLocale = $locale !== null ? $locale : current_locale();
    if (!is_supported_locale($slugLocale)) {
        $slugLocale = default_locale();
    }

    if (defined('FRONTEND_BLOG_POST_SLUG')) {
        return blog_post_url(
            localized_slug_constant('FRONTEND_BLOG_POST_SLUG', $slugLocale),
            $locale
        );
    }

    if (defined('FRONTEND_BLOG_CATEGORY_SLUG')) {
        return blog_category_url((string) FRONTEND_BLOG_CATEGORY_SLUG, $locale);
    }

    if (defined('FRONTEND_TOUR_SLUG')) {
        $tourSlug = localized_slug_constant('FRONTEND_TOUR_SLUG', $slugLocale);

        return defined('FRONTEND_TOUR_TYPE') && FRONTEND_TOUR_TYPE === 'Experience'
            ? experience_url($tourSlug, $locale)
            : tour_url($tourSlug, $locale);
    }

    if (defined('FRONTEND_PAGE_SLUG')) {
        return site_base_url(
            $slugLocale . '/' . localized_slug_constant('FRONTEND_PAGE_SLUG', $slugLocale)
        );
    }

    return url(current_script(), $query !== null ? $query : current_query(), $locale);
}

/**
 * The query parameters that change what a page shows, and so belong in its
 * canonical URL. Everything else (utm_*, gclid, fbclid, client-side filters)
 * is dropped so one page has one indexed address.
 */
function canonical_query(): array
{
    $allowed = [
        'blog.php' => ['page'],
        'book.php' => ['i', 'vehicle', 'language', 'date'],
    ];
    $requested = current_query();
    $query = [];

    foreach (($allowed[current_script()] ?? []) as $key) {
        if (isset($requested[$key]) && is_string($requested[$key]) && $requested[$key] !== '') {
            $query[$key] = $requested[$key];
        }
    }

    // A listing reports the page it actually rendered, which can differ from an out-of-range request.
    if (defined('FRONTEND_LISTING_PAGE')) {
        $query['page'] = (string) FRONTEND_LISTING_PAGE;
    }

    // The first page of a listing is the listing itself.
    if (isset($query['page']) && (int) $query['page'] <= 1) {
        unset($query['page']);
    }

    return $query;
}

/** The address of the current page in $locale that search engines should index. */
function canonical_url_in(?string $locale = null): string
{
    return current_url_in($locale, canonical_query());
}

/** The current page in the other locale — what the language switcher links to. */
function alt_locale_url(?string $locale = null): string
{
    $locale = $locale !== null ? $locale : other_locale();
    return current_url_in($locale);
}

/* =========================================================================
 * Locale-aware content records
 *
 * Operational data — ids, slugs, prices, capacities, image paths, availability
 * — lives once, in inc/data.php / inc/experiences.php, in
 * English. A translation supplies ONLY the text fields, keyed by the record's
 * stable id, and is merged over the top. Nothing is duplicated: an Arabic tour
 * record is the English one with its title, summary and prose replaced.
 * ====================================================================== */

/**
 * Temporary static editorial-content overlay used until the page phases move
 * these fields to the existing database English and Arabic columns.
 */
function content_map(?string $locale = null): array
{
    static $loaded = [];

    $locale = $locale !== null ? $locale : current_locale();
    if ($locale === default_locale() || !is_supported_locale($locale)) {
        return [];
    }
    if (isset($loaded[$locale])) {
        return $loaded[$locale];
    }

    $file = __DIR__ . '/lang/content-' . $locale . '.php';
    $data = is_file($file) ? require $file : [];
    $loaded[$locale] = is_array($data) ? $data : [];

    return $loaded[$locale];
}

/**
 * Overlay one record's translated text fields.
 *
 * $group is a top-level key of the content map ('tours', 'guides', …) and $id
 * the record's stable identifier (its slug or id — never translated). Keys the
 * overlay does not mention keep their source values, so a partially translated
 * record degrades field by field rather than losing its price or its image.
 */
function localize(array $record, string $group, string $id, ?string $locale = null): array
{
    $map = content_map($locale);
    if (!isset($map[$group][$id]) || !is_array($map[$group][$id])) {
        return $record;
    }
    return merge_localized($record, $map[$group][$id]);
}

/**
 * Merge a translation over a source record, recursing into nested lists.
 *
 * A tour's `highlights` is a list of `['attraction' => slug, 'title' => …,
 * 'text' => …]`. Only the title and the text are translated, so the overlay
 * lists the two text fields per position and the `attraction` slug — the link
 * into the attractions catalogue — survives from the source. The same applies
 * to `journey`, `prepare` and `faqs`.
 *
 * Positional, not keyed: the overlay's element 3 translates the source's
 * element 3. Reordering a tour's highlights in inc/data.php therefore means
 * reordering them in inc/lang/content-ar.php too. An overlay shorter than its
 * source leaves the surplus entries in the source language rather than
 * dropping them.
 */
function merge_localized($source, $overlay)
{
    if (!is_array($source) || !is_array($overlay)) {
        return $overlay;
    }

    $out = $source;
    foreach ($overlay as $key => $value) {
        $out[$key] = (isset($source[$key]) && is_array($source[$key]) && is_array($value))
            ? merge_localized($source[$key], $value)
            : $value;
    }

    return $out;
}

/**
 * Overlay a list of records in one call, taking the id from $idKey.
 *
 * Used by the accessors in inc/data.php, which pass their whole result set
 * through on the way out.
 */
function localize_list(array $records, string $group, string $idKey = 'slug', ?string $locale = null): array
{
    $map = content_map($locale);
    if (empty($map[$group])) {
        return $records;
    }

    foreach ($records as $i => $record) {
        if (!is_array($record) || !isset($record[$idKey])) {
            continue;
        }
        $records[$i] = localize($record, $group, (string) $record[$idKey], $locale);
    }

    return $records;
}

/**
 * A single translated value from the content map, or $fallback.
 *
 * For the handful of places that need one field rather than a whole record —
 * a photo caption, a tour category label.
 */
function content(string $group, string $id, string $field = '', $fallback = null, ?string $locale = null)
{
    $map = content_map($locale);
    if (!isset($map[$group][$id])) {
        return $fallback;
    }
    $entry = $map[$group][$id];
    if ($field === '') {
        return $entry;
    }
    return isset($entry[$field]) ? $entry[$field] : $fallback;
}

/* =========================================================================
 * The payload shared with JavaScript
 *
 * js/main.js, js/booking.js, js/datepicker.js, js/phone-input.js and
 * js/select2-init.js are ONE copy each, serving both locales. Everything that
 * differs between them arrives here, as JSON, and never as a second script.
 * ====================================================================== */

/**
 * Locale, direction, plugin configuration and every string the shared scripts
 * generate at runtime.
 *
 * Keys under `messages` carry `{token}` placeholders and, where a count is
 * involved, one entry per plural category — the JavaScript side runs the same
 * category rules as plural_category().
 */
function js_locale_config(): array
{
    $locale = current_locale();
    $rtl    = locale_is_rtl($locale);

    $keys = [
        // Formatting templates the scripts render values through
        'format.price', 'format.listSeparator',
        // Mobile navigation
        'menu.open', 'menu.close',
        // Tour and experience listings
        'filter.reset', 'filter.tours.none', 'filter.experiences.none',
        // Booking wizard
        'book.notSelected', 'book.anyGuide', 'book.from',
        'book.slot.unavailable', 'book.slot.chooseDate', 'book.slot.noneOpen',
        'book.guide.none', 'book.alert.confirm',
        // Booking wizard — promo code (step 5 Review)
        'book.promo.label', 'book.promo.placeholder', 'book.promo.apply', 'book.promo.remove',
        'book.promo.applying', 'book.promo.removing',
        'book.promo.applySuccessTitle', 'book.promo.applySuccessText',
        'book.promo.removeSuccessTitle', 'book.promo.removeSuccessText',
        'book.promo.applyErrorTitle',
        'book.promo.error.invalid', 'book.promo.error.expired', 'book.promo.error.unavailable',
        'book.promo.error.limitGlobal', 'book.promo.error.limitCustomer',
        'book.promo.updating', 'book.promo.incomplete', 'book.promo.refreshFailed',
        'book.promo.removeErrorTitle', 'book.promo.removeErrorText',
        'book.promo.eligibilityTitle', 'book.promo.eligibilityText', 'book.promo.ineligibleBadge',
        'book.summary.total', 'book.summary.originalTotal', 'book.summary.discount',
        'book.save.promo',
        // Booking wizard — pickup location autocomplete (Google Places)
        'book.place.listLabel', 'book.place.available', 'book.place.empty',
        'book.place.error', 'book.place.selected',
        // Booking wizard — test card payment (step 6) CVC hint, swapped by card brand
        'book.payment.cvcHint', 'book.payment.cvcHintAmex',
        // Plan Your Trip wizard — Continue save-step behaviour
        'plan.step.saving', 'plan.error.saveFailed', 'plan.error.expired', 'plan.error.summary',
        'plan.error.dateOrder', 'plan.error.recaptcha', 'plan.completed.fallbackLabel',
        'cta.continue',
        // Contact form — real-time client validation (jQuery Validate) and AJAX submit
        'contact.error.firstName', 'contact.error.lastName', 'contact.error.email',
        'contact.error.subject', 'contact.error.message', 'contact.error.required',
        'contact.error.maxlength', 'contact.error.summary',
        'contact.error.general', 'contact.error.recaptcha', 'contact.sending', 'contact.submit',
        // Phone field
        'phone.error.empty', 'phone.error.invalid', 'phone.error.tooShort',
        'phone.error.tooLong', 'phone.error.countryCode',
        // Datepicker
        'date.nextMonth', 'date.prevMonth',
        // intl-tel-input interface
        'iti.selectedCountryAriaLabel', 'iti.noCountrySelected', 'iti.countryListAriaLabel',
        'iti.searchPlaceholder', 'iti.clearSearchAriaLabel', 'iti.searchEmptyState',
        // Hero slider
        'hero.slider.label', 'hero.slide.label', 'hero.bullet.label',
    ];

    $messages = [];
    foreach ($keys as $key) {
        $messages[$key] = t($key);
    }

    /* Counted messages ship as a map of plural category -> template. */
    $plurals = [];
    foreach ([
        'count.tours', 'count.experiences', 'count.guides', 'count.filters', 'count.filtersShort',
        'count.guideMatches', 'count.slotsOpen', 'count.searchResults', 'count.upTo',
        'count.guidesLed',
        'book.step.announce', 'plan.step.announce', 'plan.step.label',
    ] as $key) {
        $plurals[$key] = plural_forms($key, $locale);
    }

    return [
        'locale'    => $locale,
        'tag'       => locale_tag($locale),
        'dir'       => locale_dir($locale),
        'rtl'       => $rtl,
        'currency'  => localized_setting(
            'currency_unit',
            $locale === 'ar' ? 'ريال' : 'SAR'
        ),
        'messages'  => $messages,
        'plurals'   => $plurals,
        'stepLabels' => [
            t('book.step.1'), t('book.step.2'), t('book.step.3'),
            t('book.step.4'), t('book.step.5'), t('book.step.6'),
        ],
        'date' => [
            /* Flatpickr's own locale bundle supplies the month and day names;
               only the pieces the bundle cannot know are sent. First day of the
               week is Sunday in both locales — it is the Saudi working week,
               and it matches the weekday numbers guide availability uses. */
            'flatpickrLocale' => $locale === 'ar' ? 'ar' : 'default',
            'firstDayOfWeek'  => 0,
            'altFormat'       => t('format.flatpickr.long'),
            'altFormatShort'  => t('format.flatpickr.short'),
        ],
        'select2' => [
            'language' => $locale === 'ar' ? 'ar' : 'en',
            'dir'      => locale_dir($locale),
        ],
        'phone' => [
            /* intl-tel-input 29 resolves its country names through
               Intl.DisplayNames(countryNameLocale, {type:'region'}), so the
               whole list comes out in Arabic from this one value — no country
               bundle to vendor. The interface strings go through
               `uiTranslations`, built in js/phone-input.js from the iti.* keys
               in the catalogue. */
            'countryNameLocale' => $locale === 'ar' ? 'ar' : 'en',
        ],
    ];
}

/**
 * Every plural form the catalogue holds for a counted key.
 *
 * Only the categories the locale actually uses are emitted, so the English
 * payload carries two entries and the Arabic one up to six.
 */
function plural_forms(string $key, ?string $locale = null): array
{
    $locale     = $locale !== null ? $locale : current_locale();
    $catalog    = catalog($locale);
    $categories = $locale === 'ar'
        ? ['zero', 'one', 'two', 'few', 'many', 'other']
        : ['one', 'other'];

    $out = [];
    foreach ($categories as $category) {
        $full = $key . '.' . $category;
        if (isset($catalog[$full]) && is_string($catalog[$full])) {
            $out[$category] = $catalog[$full];
        }
    }

    if (!isset($out['other'])) {
        $out['other'] = t($key . '.other', [], $locale);
    }

    return $out;
}

/**
 * The JSON island header.php prints, read by js/main.js on both locales.
 *
 * JSON_HEX_* keeps the payload safe inside <script>; JSON_UNESCAPED_UNICODE
 * keeps the Arabic readable in view-source instead of a wall of \u escapes.
 */
function locale_script(): string
{
    $json = json_encode(
        js_locale_config(),
        JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE
        | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT
    );

    return '<script type="application/json" id="alam-locale-data">' . $json . '</script>';
}
