<?php
/**
 * Alam Al-Munawara — reusable view partials.
 *
 * These are deliberately plain functions that echo markup. When the frontend
 * moves into CodeIgniter 3 each one maps to a view under
 * application/views/partials/ and is loaded with $this->load->view().
 */


/* =========================================================================
 * Escaping helpers
 * ====================================================================== */
function e(?string $v): string
{
    return htmlspecialchars((string) $v, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

/**
 * True when the string contains HTML tags, false for plain text.
 */
function isHTML(?string $string): bool
{
    return $string !== strip_tags((string) $string);
}

/**
 * True for an already-absolute URL (e.g. an upload_thumb() result), false
 * for a project-relative sample-data path (e.g. 'images/alam/tours/x.webp').
 * Card components use this to tell a pre-sized DB thumbnail apart from a
 * relative path that still needs site_base_url()/srcset() treatment.
 */
function is_absolute_url(string $path): bool
{
    return preg_match('#^[a-z][a-z0-9+.-]*://#i', $path) === 1;
}

/**
 * An inline style built from an admin-managed color pair (values are
 * `rgba(...)` strings, e.g. the slider's `banner_*_color_1`/`_2` columns).
 *
 * Neither set -> ''. One set -> a flat `color: $color !important`. Both set
 * -> a gradient *background* clipped to the text shape, since `color` itself
 * cannot hold a gradient (the browser drops the whole declaration as an
 * invalid value — `!important` cannot fix that, it only wins on specificity,
 * not on syntax validity). Every declaration is `!important` because the
 * caller's existing Tailwind text-color utility class (e.g. `text-white`)
 * stays on the element and would otherwise win. The caller only prints a
 * `style` attribute when this returns something.
 */
function text_gradient_style(?string $color1, ?string $color2): string
{
    $color1 = trim((string) $color1);
    $color2 = trim((string) $color2);

    if ($color1 === '' && $color2 === '') {
        return '';
    }

    if ($color1 === '' || $color2 === '') {
        return 'color:' . ($color1 !== '' ? $color1 : $color2) . ' !important';
    }

    $gradient = 'linear-gradient(to right, ' . $color1 . ', ' . $color2 . ')';

    return 'background:' . $gradient . ' !important;'
        . '-webkit-background-clip:text !important;'
        . 'background-clip:text !important;'
        . 'color:transparent !important;'
        . '-webkit-text-fill-color:transparent !important;';
}

/**
 * An inline style built from an admin-managed background color pair (the
 * same `rgba(...)` shape as text_gradient_style(), e.g. `banner_background_color_1`/`_2`).
 *
 * Neither set -> ''. One set -> a flat `background-color`. Both set -> a
 * linear gradient `background`. Unlike text_gradient_style(), this paints
 * the element's own background rather than clipping to text, so no
 * transparent-text declarations are needed.
 */
function background_gradient_style(?string $color1, ?string $color2): string
{
    $color1 = trim((string) $color1);
    $color2 = trim((string) $color2);

    if ($color1 === '' && $color2 === '') {
        return '';
    }

    if ($color1 === '' || $color2 === '') {
        return 'background-color:' . ($color1 !== '' ? $color1 : $color2) . ' !important';
    }

    return 'background:linear-gradient(to right, ' . $color1 . ', ' . $color2 . ') !important';
}

/**
 * A CTA <a> link built from admin-managed button fields — the shape every
 * Manage > Web Page Sections button and the slider's button_1/button_2
 * already store: an icon-picker value, a label, a URL, an icon position and
 * (sliders only) a link target. Reusable anywhere in the frontend a
 * DB-configured button needs to be rendered, not just the Home page.
 *
 * Renders '' unless both $text and $url are set — a button with no
 * destination or no label is not rendered rather than shown broken.
 * $iconPos: 'Right' puts the icon after the text; anything else (including
 * null/empty) defaults to 'Left', icon before the text.
 * $icon is a raw Font Awesome class from the DB (e.g. "fa-solid fa-arrow-right"),
 * passed straight through to icon(), which already renders that form as-is.
 * $target: '_blank' adds target="_blank" with rel="noopener noreferrer";
 * anything else (including null/empty) renders no target attribute.
 * $ltrNumbers: pass true for CTA card buttons so a label holding a phone
 * number keeps its left-to-right order on Arabic pages (see cta_label_html()).
 */
function button_link(?string $text, ?string $url, ?string $icon, ?string $iconPos, string $class, string $iconClass = 'icon-sm icon-flip', ?string $target = null, bool $ltrNumbers = false): string
{
    $text = trim((string) $text);
    $url = trim((string) $url);
    if ($text === '' || $url === '') {
        return '';
    }

    $icon = trim((string) $icon);
    $iconHtml = $icon !== '' ? icon($icon, $iconClass) : '';
    $iconAfter = strtolower(trim((string) $iconPos)) === 'right';

    $textHtml = $ltrNumbers ? cta_label_html($text) : e($text);
    $label = $iconAfter ? ($textHtml . $iconHtml) : ($iconHtml . $textHtml);

    $targetAttr = strtolower(trim((string) $target)) === '_blank' ? ' target="_blank" rel="noopener noreferrer"' : '';

    return '<a href="' . e($url) . '" class="' . e($class) . '"' . $targetAttr . '>' . $label . '</a>';
}

/**
 * Escaped CTA card button label. On Arabic pages a label containing a digit
 * (a phone number such as "+966 55 123 4567") is isolated as left-to-right,
 * matching how the footer renders phone numbers, so the bidi algorithm does
 * not reorder the "+" and the digit groups.
 */
function cta_label_html(string $text): string
{
    if (
        current_locale() === 'ar'
        && preg_match('/[0-9\x{0660}-\x{0669}\x{06F0}-\x{06F9}]/u', $text)
    ) {
        return '<bdi dir="ltr">' . e($text) . '</bdi>';
    }

    return e($text);
}

/** Render one button from a controller-prepared section view model. */
function prepared_section_button(
    array $section,
    string $class,
    string $name = 'button',
    string $iconClass = 'icon-sm icon-flip'
): string
{
    $button = $section['buttons'][$name] ?? [];

    return button_link(
        $button['text'] ?? '',
        $button['url'] ?? '',
        $button['icon'] ?? '',
        $button['icon_pos'] ?? '',
        $class,
        $iconClass
    );
}

/* =========================================================================
 * Manage > Web Page Sections / generic row helpers
 *
 * A "row" here is any associative array of DB-column-name => value — a
 * `web_page_sections` entry (as returned by
 * Content_section_service::get_web_page_sections()), a plain query row from
 * one of the Localized_model-based models, or any future page's own record.
 * None of these know or care which page/table the row came from, so any
 * page — not just Home — can reuse them once it has its own row/section in
 * hand.
 * ====================================================================== */

/** A field's value, trimmed and entity-decoded, or $fallback when unset/blank. */
function row_text(array $row, string $field, string $fallback = ''): string
{
    $value = isset($row[$field]) ? trim((string) $row[$field]) : '';

    return $value !== '' ? html_entity_decode($value, ENT_QUOTES, 'UTF-8') : $fallback;
}

/**
 * A rich-text field (e.g. a CKEditor `contents` column) reduced to a single
 * plain-text line — HTML tags stripped, entities decoded, whitespace
 * collapsed — for anywhere the surrounding markup prints the value as plain
 * text rather than as HTML.
 */
function row_plain(array $row, string $field): string
{
    $raw = isset($row[$field]) ? (string) $row[$field] : '';

    return trim(preg_replace('/\s+/u', ' ', html_entity_decode(strip_tags($raw), ENT_QUOTES, 'UTF-8')) ?? '');
}

/** One entry from a `get_web_page_sections()` result, keyed by section_key. */
function page_section(array $sections, string $key): array
{
    return isset($sections[$key]) && is_array($sections[$key]) ? $sections[$key] : [];
}

/**
 * Eyebrow/title/text for section_head(), sourced from a web_page_sections
 * row's `pre_heading`/`heading`/`contents` fields. `contents` is rich text
 * so it is reduced to plain text here (see row_plain()) — use
 * section_html() where the section's HTML should be printed as-is. A field
 * the administrator has left blank comes back as '' so section_head() hides
 * that line entirely rather than printing an empty tag.
 */
function section_head_fields(array $section): array
{
    return [
        'eyebrow' => row_text($section, 'pre_heading'),
        'title'   => row_text($section, 'heading'),
        'text'    => row_plain($section, 'contents'),
    ];
}

/** A section's rich-text `contents` field (or any field) as trusted, pre-approved admin HTML. */
function section_html(array $section, string $field = 'contents'): string
{
    return isset($section[$field]) ? trim((string) $section[$field]) : '';
}

/**
 * A section's admin-configured CTA, rendered as a full <a> link via
 * button_link() — reads that section's `{$prefix}_text`, `{$prefix}_url`,
 * `{$prefix}_icon` and `{$prefix}_icon_pos` fields. $prefix lets a
 * two-button section (e.g. `button_1_*`/`button_2_*`) pick which button to
 * render. Returns '' when the administrator hasn't set both a label and a URL.
 */
function section_button(array $section, string $class, string $prefix = 'button', string $iconClass = 'icon-sm icon-flip'): string
{
    return button_link(
        row_text($section, $prefix . '_text'),
        $section[$prefix . '_url'] ?? null,
        $section[$prefix . '_icon'] ?? null,
        $section[$prefix . '_icon_pos'] ?? null,
        $class,
        $iconClass
    );
}

/**
 * Up to $max numbered `{prefix}_N_icon` / `{prefix}_N_heading` /
 * `{prefix}_N_text` groups from a section, skipping any entry with no
 * heading and no text. Covers both the "card" and "step" field groups
 * (`card_1_icon`/`card_1_heading`/`card_1_text`, `step_1_heading`/
 * `step_1_text`, ...) since they share the same numbered-group shape.
 */
function section_groups(array $section, int $max, string $prefix = 'card'): array
{
    $groups = [];

    for ($i = 1; $i <= $max; $i++) {
        $heading = isset($section[$prefix . '_' . $i . '_heading']) ? trim((string) $section[$prefix . '_' . $i . '_heading']) : '';
        $text = isset($section[$prefix . '_' . $i . '_text']) ? trim((string) $section[$prefix . '_' . $i . '_text']) : '';
        if ($heading === '' && $text === '') {
            continue;
        }

        $groups[] = [
            'n'     => count($groups) + 1,
            'icon'  => isset($section[$prefix . '_' . $i . '_icon']) ? trim((string) $section[$prefix . '_' . $i . '_icon']) : '',
            'title' => html_entity_decode($heading, ENT_QUOTES, 'UTF-8'),
            'text'  => html_entity_decode($text, ENT_QUOTES, 'UTF-8'),
        ];
    }

    return $groups;
}

/**
 * A price for display.
 *
 * The AMOUNT is a business fact and identical in both locales; only the way the
 * currency is named changes — "SAR 450" in English, "450 ريال" in Arabic. The
 * digits stay Western in both, which is what Saudi sites use and what a visitor
 * types into a form.
 */
function price($amount): string
{
    $currency = localized_setting(
        'currency_unit',
        current_locale() === 'ar' ? 'ريال' : 'SAR'
    );

    return t('format.price', [
        'amount' => number($amount),
        'currency' => $currency,
    ]);
}

/* =========================================================================
 * Icons — Font Awesome Free 7 webfont (vendor/fontawesome/)
 *
 * Call sites name an icon by *meaning* ('map-pin', 'guide', 'suv'), not by its
 * Font Awesome class, so a glyph can be swapped here without touching a single
 * template. Only the Solid and Brands families are self-hosted, so every entry
 * below must resolve inside Font Awesome **Free** — no Pro icons.
 *
 * $class carries the size (icon-xs … icon-2xl, see css/src/tailwind.css) plus
 * any colour/layout utilities the caller needs.
 * ====================================================================== */
function icon(string $name, string $class = 'icon-lg', array $attrs = []): string
{
    /* Admin-managed content (icon-picker fields under Manage > Web Page
       Sections) stores the full Font Awesome class string directly, e.g.
       "fa-solid fa-compass" — not one of the short keys below. Render it as
       given rather than falling through to the generic 'info' glyph. */
    if (preg_match('/^fa-(solid|regular|brands|light|thin|duotone)\s+fa-[a-z0-9-]+$/', $name) === 1) {
        $extra = '';
        foreach ($attrs as $k => $v) {
            $extra .= ' ' . e((string) $k) . '="' . e((string) $v) . '"';
        }

        return '<i class="' . e('icon ' . $name . ' ' . $class) . '" aria-hidden="true"' . $extra . '></i>';
    }

    static $icons = [
        /* Navigation and generic UI */
        'menu'          => 'fa-solid fa-bars',
        'close'         => 'fa-solid fa-xmark',
        'arrow-right'   => 'fa-solid fa-arrow-right',
        'arrow-left'    => 'fa-solid fa-arrow-left',
        'chevron-down'  => 'fa-solid fa-chevron-down',
        'chevron-right' => 'fa-solid fa-chevron-right',
        'chevron-left'  => 'fa-solid fa-chevron-left',
        'plus'          => 'fa-solid fa-plus',
        'check'         => 'fa-solid fa-check',
        'alert-triangle' => 'fa-solid fa-triangle-exclamation',
        'search'        => 'fa-solid fa-magnifying-glass',
        'filter'        => 'fa-solid fa-sliders',
        'reset'         => 'fa-solid fa-rotate-left',
        'edit'          => 'fa-solid fa-pen',
        'info'          => 'fa-solid fa-circle-info',
        'question'      => 'fa-solid fa-circle-question',
        'accessibility' => 'fa-solid fa-universal-access',
        'shield'        => 'fa-solid fa-shield-halved',
        'star'          => 'fa-solid fa-star',
        'quote'         => 'fa-solid fa-quote-left',
        'sparkle'       => 'fa-solid fa-wand-magic-sparkles',
        'journal'       => 'fa-solid fa-newspaper',

        /* Contact */
        'phone'         => 'fa-solid fa-phone',
        'mail'          => 'fa-solid fa-envelope',
        'map-pin'       => 'fa-solid fa-location-dot',
        'globe'         => 'fa-solid fa-globe',
        'support'       => 'fa-solid fa-headset',

        /* Booking and form fields */
        'calendar'      => 'fa-solid fa-calendar-days',
        'clock'         => 'fa-solid fa-clock',
        /* Pace, as distinct from `clock`, which is the duration glyph on
           every tour card. */
        'hourglass'     => 'fa-solid fa-hourglass-half',
        'users'         => 'fa-solid fa-users',
        'group'         => 'fa-solid fa-user-group',
        'guide'         => 'fa-solid fa-user-tie',
        'language'      => 'fa-solid fa-language',
        'ticket'        => 'fa-solid fa-ticket',
        'tag'           => 'fa-solid fa-tag',
        /* Generic placeholder in the mock payment card-number field before
           js/booking.js detects a brand from the entered digits. */
        'credit-card'   => 'fa-solid fa-credit-card',

        /* Vehicles. Font Awesome Free has no distinct SUV glyph; the shuttle
           van is the closest large-family vehicle it ships. */
        'car'           => 'fa-solid fa-car',
        'suv'           => 'fa-solid fa-van-shuttle',
        'bus'           => 'fa-solid fa-bus',

        /* Tours, places and time of day */
        'route'         => 'fa-solid fa-route',
        'compass'       => 'fa-solid fa-compass',
        'walk'          => 'fa-solid fa-person-walking',
        'camera'        => 'fa-solid fa-camera',
        'building'      => 'fa-solid fa-building-columns',
        'arch'          => 'fa-solid fa-archway',
        'sun'           => 'fa-solid fa-sun',
        'moon'          => 'fa-solid fa-moon',
        'sunset'        => 'fa-solid fa-cloud-moon',

        /* Experience categories */
        'hiking'        => 'fa-solid fa-person-hiking',
        'mountain'      => 'fa-solid fa-mountain',
        'camp'          => 'fa-solid fa-campground',
        'horse'         => 'fa-solid fa-horse',
        'farm'          => 'fa-solid fa-seedling',
        'craft'         => 'fa-solid fa-palette',

        /* Brands — the only reason vendor/fontawesome/css/brands.min.css and
           its webfont are loaded. Keep this list to icons actually rendered. */
        'facebook'      => 'fa-brands fa-facebook-f',
        'instagram'     => 'fa-brands fa-instagram',
        'x-social'      => 'fa-brands fa-x-twitter',
        'linkedin'      => 'fa-brands fa-linkedin-in',
        'youtube'       => 'fa-brands fa-youtube',
        'whatsapp'      => 'fa-brands fa-whatsapp',

        /* Card brands — the initial, undetected state of the mock payment
           card-number field. js/booking.js swaps these same "fa-brands
           fa-cc-*" classes on live detection, so the glyphs must already be
           in this build. */
        'visa'          => 'fa-brands fa-cc-visa',
        'mastercard'    => 'fa-brands fa-cc-mastercard',
        'amex'          => 'fa-brands fa-cc-amex',
        'discover'      => 'fa-brands fa-cc-discover',
        'diners'        => 'fa-brands fa-cc-diners-club',
        'jcb'           => 'fa-brands fa-cc-jcb',
    ];

    $fa = $icons[$name] ?? $icons['info'];

    $extra = '';
    foreach ($attrs as $k => $v) {
        $extra .= ' ' . $k . '="' . e((string) $v) . '"';
    }

    return '<i class="' . e('icon ' . $fa . ' ' . $class) . '" aria-hidden="true"' . $extra . '></i>';
}

/* =========================================================================
 * Section heading
 * ====================================================================== */
function section_head(array $o): string
{
    $align  = $o['align'] ?? 'left';
    $center = $align === 'center';
    $tag    = $o['tag'] ?? 'h2';
    $tone   = $o['tone'] ?? 'dark';   // dark text on light bg, or 'light'

    $wrap  = 'max-w-2xl' . ($center ? ' mx-auto text-center' : '');
    $title = $tone === 'light' ? 'text-white' : 'text-ink';
    $body  = $tone === 'light' ? 'text-white/75' : 'text-ink-muted';
    $eye   = $tone === 'light' ? 'text-white/70' : '';

    $html = '<div class="' . $wrap . ' ' . e($o['class'] ?? '') . '">';
    if (!empty($o['eyebrow'])) {
        $html .= '<p class="eyebrow ' . $eye . ' ' . ($center ? 'justify-center' : '') . '">' . e($o['eyebrow']) . '</p>';
    }
    if (!empty($o['title_html']) || !empty($o['title'])) {
        $html .= '<' . $tag . ' class="mt-3.5 text-h2 ' . $title . '">' . ($o['title_html'] ?? e($o['title'] ?? '')) . '</' . $tag . '>';
    }
    if (!empty($o['text'])) {
        $isHTML = isHTML($o['text']);
        $html .= '<div class="mt-4 text-body-lg '.($isHTML ? 'page-contents ' : ''). $body . '">'
            . ($isHTML ? e($o['text']) : nl2br(e($o['text'])))
            . '</div>';
    }
    $html .= '</div>';

    return $html;
}

/**
 * Render up to two category pills and summarize any remaining categories.
 */
function category_badges(array $categoryLabels): string
{
    $categoryLabels = array_values(array_filter(array_map('strval', $categoryLabels)));
    if (empty($categoryLabels)) {
        return '';
    }

    $visibleCategories = array_slice($categoryLabels, 0, 2);
    $remainingCategories = array_slice($categoryLabels, 2);
    $badgeClass = 'flex items-center whitespace-nowrap rounded-full bg-white px-2.5 py-1 text-caption font-bold tracking-[0.1em] text-alam-700';
    $html = '<span class="absolute start-3 top-3 flex flex-wrap gap-1.5">';

    foreach ($visibleCategories as $categoryLabel) {
        $html .= '<span class="' . $badgeClass . '">'
            . e($categoryLabel)
            . '</span>';
    }

    if (!empty($remainingCategories)) {
        $html .= '<span class="' . $badgeClass . '" title="'
            . e(implode(', ', $remainingCategories))
            . '">' . count($remainingCategories) . '+</span>';
    }

    return $html . '</span>';
}

/* =========================================================================
 * Tour card
 * ====================================================================== */
function tour_card(array $t, array $o = []): string
{
    $href    = tour_url($t['slug']);
    $bookUrl = url('book.php', ['i' => $t['slug']]);
    $lazy    = ($o['eager'] ?? false) ? 'eager' : 'lazy';
    $cta     = $o['cta'] ?? t('cta.viewTour');
    /* The badge shows the translated category while the listing filter uses
       stable database category IDs from `category_keys`. Prototype records
       retain their original text key for pages that still use those records. */
    $catKey  = $t['category_key'] ?? $t['category'];
    $categoryKeys = isset($t['category_keys']) && is_array($t['category_keys'])
        ? $t['category_keys']
        : array($catKey);
    $categoryLabels = isset($t['categories']) && is_array($t['categories'])
        ? array_values(array_filter(array_map('strval', $t['categories'])))
        : array();
    if (empty($categoryLabels) && !empty($t['category'])) {
        $categoryLabels[] = (string) $t['category'];
    }
    $filterCapacity = isset($t['filter_capacity']) && (int) $t['filter_capacity'] > 0
        ? (int) $t['filter_capacity']
        : (int) $t['capacity'];
    $languageIds = isset($t['language_ids']) && is_array($t['language_ids'])
        ? $t['language_ids']
        : array('ar', 'en');
    $languageLabel = array_key_exists('language_label', $t)
        ? trim((string) $t['language_label'])
        : t('card.languagesValue');

    $tourImage = trim((string) ($t['image'] ?? ''));
    $tourImageAbsolute = $tourImage !== '' && is_absolute_url($tourImage);

    $html = '<article class="card card-hover group relative flex h-full flex-col overflow-hidden"'
        . ' data-tour-card'
        . ' data-category="' . e($catKey) . '"'
        . ' data-categories="' . e(implode(',', $categoryKeys)) . '"'
        . ' data-capacity="' . $filterCapacity . '"'
        . ' data-slug="' . e($t['slug']) . '"'
        . ' data-languages="' . e(implode(',', $languageIds)) . '">';

    if ($tourImage !== '') {
        $html .= '<a href="' . e($href) . '" class="relative block aspect-[3/2] overflow-hidden bg-alam-100" tabindex="-1" aria-hidden="true"' . htmx_boost_attr() . '>'
            . '<img src="' . e($tourImageAbsolute ? $tourImage : site_base_url($tourImage)) . '"'
            . (!$tourImageAbsolute
                ? ' srcset="' . e(srcset($tourImage)) . '" sizes="(min-width: 1024px) 33vw, (min-width: 640px) 50vw, 100vw"'
                : '')
            . ' alt="" width="960" height="640" loading="' . $lazy . '" decoding="async"'
            . ' class="h-full w-full object-cover transition-transform duration-500 group-hover:scale-[1.04]">';

        $html .= category_badges($categoryLabels);

        $html .= '</a>';
    }

    $html .= '<div class="flex flex-1 flex-col p-5">'
        . '<h3 class="text-card-title font-bold text-ink">'
        . '<a href="' . e($href) . '" class="after:absolute after:inset-0 focus-visible:outline-none"' . htmx_boost_attr() . '>'
        . e($t['title'])
        . '</a></h3>'
        . '<p class="mt-2 line-clamp-3 text-card-body text-ink-muted">' . e($t['summary']) . '</p>';

    /* Sample tours carry a numeric `capacity` and print it through the
       "Up to {count}" template; DB-backed tours store the group size as an
       already-formatted string (e.g. "Up to 15 guests") and print as-is. */
    $capacityLabel = is_numeric($t['capacity'])
        ? t('card.upTo', ['count' => (int) $t['capacity']])
        : (string) $t['capacity'];
    $hasPrimaryMetadata = !empty($t['duration']) || $capacityLabel !== '';

    if ($hasPrimaryMetadata || $languageLabel !== '') {
        $html .= '<div class="mt-4 border-t border-line pt-4 text-meta">';

        if ($hasPrimaryMetadata) {
            $html .= '<dl class="grid grid-cols-2 gap-x-3 gap-y-2">';
        }

        if (!empty($t['duration'])) {
            $html .= '<div class="flex items-center gap-1.5 text-ink-muted">'
                . icon('clock', 'icon-sm shrink-0 text-alam-500')
                . '<dt class="sr-only">' . e(t('meta.duration')) . '</dt><dd>' . e($t['duration']) . '</dd>'
                . '</div>';
        }

        if ($capacityLabel !== '') {
            $html .= '<div class="flex items-center gap-1.5 text-ink-muted">'
                . icon('users', 'icon-sm shrink-0 text-alam-500')
                . '<dt class="sr-only">' . e(t('meta.maxGroup')) . '</dt><dd>&nbsp; ' . e($capacityLabel) . '</dd>'
                . '</div>';
        }

        if ($hasPrimaryMetadata) {
            $html .= '</dl>';
        }

        if ($languageLabel !== '') {
            $html .= '<dl class="' . ($hasPrimaryMetadata ? 'mt-4 ' : '') . 'w-full">'
                . '<div class="flex w-full items-baseline gap-1.5 text-ink-muted">'
                . icon('language', 'icon-sm shrink-0 text-alam-500')
                . '<dt class="sr-only">' . e(t('meta.languages')) . '</dt><dd>' . e($languageLabel) . '</dd>'
                . '</div></dl>';
        }

        $html .= '</div>';
    }

    $html .= '<div class="mt-auto flex items-end justify-between gap-3 pt-5">'
        . '<p class="text-meta text-ink-muted">'
        . e(t('card.from')) . ' <span class="block text-xl font-extrabold leading-tight text-ink">' . e(price($t['price'])) . '</span>'
        . '</p>'
        . '<span class="btn-secondary btn-sm relative z-10 pointer-events-none">' . e($cta) . '</span>'
        . '</div>'
        . '</div>'
        . '<a href="' . e($bookUrl) . '" class="sr-only focus:not-sr-only focus:m-3 focus:block focus:text-button focus:font-semibold focus:text-alam-700">'
        . e(t('card.bookNamed', ['title' => $t['title']]))
        . '</a>'
        . '</article>';

    return $html;
}

/* =========================================================================
 * Help card — sidebar "ask us" panel
 *
 * Icon tile, eyebrow, title, one line of copy and a single full-width CTA,
 * with two soft shapes bleeding off opposite corners. Used in the FAQ
 * category rail and in the booking sidebars.
 *
 * Options:
 *   icon     glyph for the tile
 *   eyebrow  small uppercase line above the title
 *   title    styled as a heading; rendered as <p> because this card sits
 *            inside a <nav> on the FAQ page, where a real heading would
 *            land in that landmark's outline
 *   text     one sentence of supporting copy
 *   cta      ['label' => …, 'href' => …, 'icon' => …, 'icon_pos' => 'Left'|'Right', 'style' => 'btn-primary'|'btn-outline']
 *            or false for a card that only states something and has no action
 *   class    extra classes on the wrapper (margins, responsive visibility)
 *
 * Every field is caller-supplied — there is no built-in fallback copy — and
 * each piece (icon/eyebrow/title/text/cta) is only rendered when set.
 * ====================================================================== */
function help_card(array $o = []): string
{
    $icon    = isset($o['icon']) ? (string) $o['icon'] : '';
    $eyebrow = isset($o['eyebrow']) ? (string) $o['eyebrow'] : '';
    $title   = isset($o['title']) ? (string) $o['title'] : '';
    $text    = isset($o['text']) ? (string) $o['text'] : '';
    $extra   = isset($o['class']) ? (string) $o['class'] : '';

    /* `false` for a card with nothing to click — the location card on the
       contact page states where we are and has no action to offer. */
    $cta        = isset($o['cta']) ? $o['cta'] : false;
    $hasCta     = is_array($cta);
    $ctaLabel   = $hasCta && isset($cta['label']) ? (string) $cta['label'] : '';
    $ctaHref    = $hasCta && isset($cta['href']) ? (string) $cta['href'] : '';
    $ctaIcon    = $hasCta && isset($cta['icon']) ? (string) $cta['icon'] : '';
    $ctaIconPos = $hasCta && isset($cta['icon_pos']) ? $cta['icon_pos'] : null;
    /* Written out in full, never concatenated — Tailwind scans source text
       and only emits classes it can see literally (AGENTS.md section 2). */
    $ctaStyle = $hasCta && isset($cta['style']) && $cta['style'] === 'btn-outline' ? 'btn-outline' : 'btn-primary';

    $html = '<div class="relative overflow-hidden rounded-card border border-line bg-white p-6 text-center shadow-card ' . e($extra) . '">'
        /* The brand's geometric motif across the top, faded into the card,
           rather than the loose circles this used to carry. Two elements
           instead of a mask so the fade renders the same in every browser
           we support. */
        . '<span class="pattern-grid pointer-events-none absolute inset-x-0 top-0 h-28 opacity-70" aria-hidden="true"></span>'
        . '<span class="pointer-events-none absolute inset-x-0 top-0 h-28 bg-gradient-to-b from-white/40 to-white" aria-hidden="true"></span>'
        . '<span class="pointer-events-none absolute inset-x-0 top-0 h-0.5 bg-alam-500" aria-hidden="true"></span>'
        . '<div class="relative">';

    if ($icon !== '') {
        $html .= '<span class="mx-auto grid h-14 w-14 place-items-center rounded-card bg-alam-50 text-alam-600 ring-1 ring-alam-100">'
            . icon($icon, 'icon-xl')
            . '</span>';
    }

    if ($eyebrow !== '') {
        $html .= '<p class="eyebrow-plain mt-5">' . e($eyebrow) . '</p>';
    }

    if ($title !== '') {
        $html .= '<p class="mt-2 text-xl font-medium text-ink">' . e($title) . '</p>';
    }

    if ($text !== '') {
        $html .= '<p class="mt-3 text-body-sm text-ink-muted">' . e($text) . '</p>';
    }

    if ($hasCta) {
        $html .= button_link($ctaLabel, $ctaHref, $ctaIcon, $ctaIconPos, $ctaStyle . ' mt-5 w-full', 'icon-sm', null, true);
    }

    $html .= '</div></div>';

    return $html;
}

/* =========================================================================
 * Attraction card + GLightbox detail popup
 *
 * The card is image-first: photograph, heading, short line. The whole card
 * is one trigger — the <button> in the heading is stretched over the card
 * with ::after, so the click target is the card while the accessible name,
 * keyboard focus and Tab order stay on a real button.
 *
 * Long copy is NOT duplicated into data-* attributes. Each trigger carries
 * only `data-attraction="<slug>"`; the page prints one JSON block
 * (attraction_data()), and js/lightbox-init.js builds a GLightbox inline
 * slide from it on open.
 * ====================================================================== */
function attraction_card(array $a, array $o = []): string
{
    $lazy = ($o['eager'] ?? false) ? 'eager' : 'lazy';
    $image = trim((string) ($a['image'] ?? ''));
    $imageIsAbsolute = $image !== '' && is_absolute_url($image);
    $imageUrl = $imageIsAbsolute ? $image : site_base_url($image);

    $html = '<article class="attraction-card group">'
        . '<div class="attraction-card-media">'
        . '<img src="' . e($imageUrl) . '"'
        . (!$imageIsAbsolute ? ' srcset="' . e(srcset($image)) . '"' : '')
        . ' sizes="(min-width: 1024px) 33vw, (min-width: 640px) 45vw, 100vw"'
        . ' alt="' . e($a['alt']) . '"'
        . ' width="960" height="640" loading="' . $lazy . '" decoding="async"'
        . ' class="attraction-card-img">'
        . '</div>'
        . '<div class="attraction-card-body">'
        . '<h3 class="text-card-title font-bold text-ink">'
        . '<button type="button" class="attraction-card-trigger" data-attraction="' . e($a['slug']) . '" aria-haspopup="dialog">'
        . e($a['title'])
        . '</button>'
        . '</h3>'
        . '<p class="mt-2 text-card-body text-ink-muted">' . e($a['short']) . '</p>'
        . '<p class="attraction-card-cue" aria-hidden="true">'
        . e(t('attraction.viewDetails')) . icon('chevron-right', 'icon-xs icon-flip')
        . '</p>'
        . '</div>'
        . '</article>';

    return $html;
}

/**
 * The attraction copy for one tour, as a single JSON island. Printed once
 * per page; js/lightbox-init.js reads it by slug, so no attraction's long
 * text is repeated in the markup.
 */
function attraction_data(array $attractions): string
{
    $payload = [];
    foreach ($attractions as $a) {
        $image = trim((string) ($a['image'] ?? ''));
        $payload[$a['slug']] = [
            'title'   => $a['title'],
            'image'   => $image !== '' && is_absolute_url($image)
                ? $image
                : site_base_url($image),
            'alt'     => $a['alt'],
            'details' => array_values($a['details']),
        ];
    }

    /* HEX_TAG/HEX_AMP keep the payload safe to sit inside <script>. */
    $json = json_encode(
        $payload,
        JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT
    );

    return '<script type="application/json" data-attraction-data>' . $json . '</script>';
}

/* =========================================================================
 * Guide card — display variant (marketing) and select variant (booking)
 * ====================================================================== */
function guide_card(array $g, array $o = []): string
{
    $langs = array_map('language_label', $g['languages']);
    $sep   = t('format.listSeparator');
    $expertise = $g['expertise'] ?? [];
    $tours     = $g['tours'] ?? null;
    $isList    = ($o['layout'] ?? '') === 'list';

    $guideImage = trim((string) ($g['image'] ?? ''));
    $guideImageAbsolute = $guideImage !== '' && is_absolute_url($guideImage);

    $imageTag = '';
    if ($guideImage !== '') {
        $imageTag = '<img src="' . e($guideImageAbsolute ? $guideImage : site_base_url($guideImage)) . '"'
            . (!$guideImageAbsolute
                ? ' srcset="' . e(srcset($guideImage, '-400', 400, 512)) . '" sizes="(min-width: 1024px) 25vw, (min-width: 640px) 50vw, 100vw"'
                : '')
            . ' alt="' . e($g['image_alt'] ?? '') . '"'
            . ' width="512" height="512" loading="lazy" decoding="async" class="h-full w-full object-cover">';
    }

    /* The Tour Guides listing filters cards by language id, through the same
       data-categories matching the Experiences filter uses. */
    $isFilterable = !empty($o['filterable']);
    $filterAttributes = $isFilterable
        ? ' data-guide-card data-categories="' . e(implode(',', $g['language_ids'] ?? [])) . '"'
        : '';

    /* List variant: guide photo on the left, details on the right, languages
       pinned to the footer of the details column — used on the tour detail
       page (single-column list) and, filterable, in the two-column Tour
       Guides listing, where the photo takes a wider share of the card and
       cards in a row share one height. */
    if ($isList) {
        $html = '<article class="card flex flex-col overflow-hidden sm:flex-row'
            . ($isFilterable ? ' h-full' : '') . '"' . $filterAttributes . '>';

        if ($imageTag !== '') {
            $html .= '<div class="aspect-square overflow-hidden bg-alam-100 sm:aspect-auto sm:shrink-0 '
                . ($isFilterable ? 'sm:w-2/5' : 'sm:w-1/4') . '">' . $imageTag . '</div>';
        }

        $html .= '<div class="flex flex-1 flex-col p-5 sm:p-6">'
            . '<p class="eyebrow-plain">' . e($g['title']) . '</p>'
            . '<h3 class="mt-2 text-card-title font-bold text-ink">' . e($g['name']) . '</h3>'
            . '<p class="mt-2 ' . ($isFilterable ? 'mb-4 ' : '') . 'text-card-body text-ink-muted">' . nl2br(e($g['bio'])) . '</p>';

        if (!empty($expertise)) {
            $html .= '<ul class="mt-4 flex flex-wrap gap-1.5">';
            foreach ($expertise as $x) {
                $html .= '<li class="rounded-full bg-alam-50 px-2.5 py-1 text-meta font-medium text-alam-700">' . e($x) . '</li>';
            }
            $html .= '</ul>';
        }

        $html .= '<div class="' . ($isFilterable ? 'mt-auto' : 'mt-4') . ' flex items-start gap-2 border-t border-line pt-4 text-meta text-ink-muted">'
            . icon('language', 'icon-sm text-alam-500 mt-0.5')
            . '<span>' . e(implode($sep, $langs)) . '</span>';

        if ($tours !== null) {
            $html .= '<span class="ms-auto flex shrink-0 items-center gap-1 text-alam-600">'
                . icon('route', 'icon-sm') . e(tn('count.guideTours', count($tours)))
                . '</span>';
        }

        $html .= '</div>'
            . '</div>'
            . '</article>';

        return $html;
    }

    $html = '<article class="card card-hover flex h-full flex-col overflow-hidden"' . $filterAttributes . '>';

    if ($imageTag !== '') {
        $html .= '<div class="aspect-square overflow-hidden bg-alam-100">' . $imageTag . '</div>';
    }

    $html .= '<div class="flex flex-1 flex-col p-5">'
        . '<p class="eyebrow-plain">' . e($g['title']) . '</p>'
        . '<h3 class="mt-2 text-card-title font-bold text-ink">' . e($g['name']) . '</h3>'
        . '<p class="mt-2 line-clamp-3 text-card-body text-ink-muted">' . e($g['bio']) . '</p>'
        . '<ul class="mb-4 mt-4 flex flex-wrap gap-1.5">';

    foreach ($expertise as $x) {
        $html .= '<li class="rounded-full bg-alam-50 px-2.5 py-1 text-meta font-medium text-alam-700">' . e($x) . '</li>';
    }

    $html .= '</ul>'
        . '<div class="mt-auto flex items-start gap-2 border-t border-line pt-4 text-meta text-ink-muted">'
        . icon('language', 'icon-sm text-alam-500 mt-0.5')
        . guide_card_languages($langs, $sep);

    if ($tours !== null) {
        $html .= '<span class="ms-auto flex items-center gap-1 text-alam-600">'
            . icon('route', 'icon-sm') . e(tn('count.guideTours', count($tours)))
            . '</span>';
    }

    $html .= '</div>'
        . '</div>'
        . '</article>';

    return $html;
}

/**
 * A grid guide card's language line: the first three languages, then an
 * "N more" button whose tooltip lists the remaining ones.
 */
function guide_card_languages(array $langs, string $sep, int $max = 3): string
{
    static $tooltipIndex = 0;

    $hidden = count($langs) - $max;
    if ($hidden <= 0) {
        return '<span>' . e(implode($sep, $langs)) . '</span>';
    }

    $tooltipId = 'guide-languages-' . (++$tooltipIndex);

    return '<span>'
        . e(implode($sep, array_slice($langs, 0, $max)) . $sep)
        . '<span class="tooltip" data-tooltip>'
        . '<button type="button" class="tooltip-trigger" aria-describedby="' . e($tooltipId) . '">'
        . e(tn('count.moreLanguages', $hidden))
        . '</button>'
        . '<span role="tooltip" id="' . e($tooltipId) . '" class="tooltip-content">'
        . e(implode($sep, array_slice($langs, $max)))
        . '</span>'
        . '</span>'
        . '</span>';
}

/* =========================================================================
 * FAQ accordion
 *
 * $id prefixes every panel id so several accordions can sit on one page
 * without colliding. An item's own `id` (e.g. a database `faq_id`) is used
 * for its panel when present, for a stable identifier that survives the
 * items being reordered; a caller with no such id (there are none today,
 * but the key stays optional so no existing caller has to change) falls
 * back to its position, exactly as before.
 * ====================================================================== */
function faq_accordion(array $items, string $id, int $openIndex = -1): string
{
    $html = '<div class="divide-y divide-line" data-accordion>';

    foreach ($items as $i => $item) {
        $isOpen = $i === $openIndex;
        $itemId = isset($item['id']) ? (string) (int) $item['id'] : (string) $i;
        $pid    = $id . '-panel-' . $itemId;

        $html .= '<div class="accordion-item">'
            . '<h3>'
            . '<button type="button" class="accordion-trigger" aria-expanded="' . ($isOpen ? 'true' : 'false') . '" aria-controls="' . e($pid) . '">'
            . '<span>' . e($item['q']) . '</span>'
            . '<span class="accordion-icon">' . icon('plus', 'icon-sm') . '</span>'
            . '</button>'
            . '</h3>'
            . '<div id="' . e($pid) . '" class="accordion-panel" data-open="' . ($isOpen ? 'true' : 'false') . '">'
            . '<div>'
            . '<p class="pb-4 pe-10 text-body-sm text-ink-muted">' . e($item['a']) . '</p>'
            . '</div>'
            . '</div>'
            . '</div>';
    }

    $html .= '</div>';

    return $html;
}

/* =========================================================================
 * Empty state
 * ====================================================================== */
function empty_state(string $title, string $text, string $actionsHtml = '', string $icon = 'search'): string
{
    $html = '<div class="flex flex-col items-center justify-center rounded-feature border border-dashed border-line-strong bg-surface-tint px-6 py-14 text-center">'
        . '<span class="grid h-14 w-14 place-items-center rounded-full bg-white text-alam-500 ring-1 ring-line-strong">'
        . icon($icon, 'icon-xl')
        . '</span>'
        . '<p class="mt-4 text-card-title font-bold text-ink">' . e($title) . '</p>'
        . '<p class="mt-2 max-w-md text-body-sm text-ink-muted">' . e($text) . '</p>';

    if ($actionsHtml !== '') {
        $html .= '<div class="mt-6 flex flex-wrap items-center justify-center gap-3">' . $actionsHtml . '</div>';
    }

    $html .= '</div>';

    return $html;
}

/* =========================================================================
 * CTA band
 *
 * Content (eyebrow/title/text/primary/secondary) is always CMS-managed —
 * sourced from miscellaneous_content_sections['plan_with_us'] — so there is
 * no built-in fallback copy here. Callers build this array from that section
 * (see Frontend::buildPlanCtaBand()) and skip rendering the band entirely
 * when the section has no usable content. Each optional line (eyebrow/
 * title/text/secondary) is only rendered when set.
 * ====================================================================== */
function cta_band(array $o = []): string
{
    $primary = isset($o['primary']) ? $o['primary'] : null;
    if ($primary === null) {
        return '';
    }
    $primaryLabel = isset($primary['label']) ? (string) $primary['label'] : '';
    $primaryHref  = isset($primary['href']) ? (string) $primary['href'] : '';

    $eyebrow = isset($o['eyebrow']) ? (string) $o['eyebrow'] : '';
    $title   = isset($o['title']) ? (string) $o['title'] : '';
    $text    = isset($o['text']) ? (string) $o['text'] : '';

    // A caller with a single CMS-managed button passes `secondary => false`
    // to render the band with a primary action only.
    $secondary    = isset($o['secondary']) ? $o['secondary'] : false;
    $hasSecondary = is_array($secondary);
    $secondLabel  = $hasSecondary && isset($secondary['label']) ? (string) $secondary['label'] : '';
    $secondHref   = $hasSecondary && isset($secondary['href']) ? (string) $secondary['href'] : '';

    $image = isset($o['image']) ? trim((string) $o['image']) : '';

    // Background of the band's outer section, so a page can slot it into an
    // alternating white / light-purple rhythm. The inner panel is unaffected.
    $bg = isset($o['bg']) ? (string) $o['bg'] : 'bg-white';

    $html = '<section class="' . e($bg) . ' py-16 sm:py-20"><div class="container">'
        . '<div class="relative isolate overflow-hidden rounded-feature bg-alam-900 px-6 py-12 sm:px-10 sm:py-14 lg:px-14">';

    if ($image !== '') {
        $html .= '<img src="' . e(site_base_url($image)) . '"'
            . ' srcset="' . e(srcset($image, '-1280', 1280, 1920)) . '"'
            . ' sizes="(min-width: 1240px) 1160px, 100vw"'
            . ' alt="" aria-hidden="true"'
            . ' width="1920" height="720" loading="lazy" decoding="async"'
            . ' class="absolute inset-0 -z-10 h-full w-full object-cover object-center opacity-40">'
            . '<div class="absolute inset-0 -z-10 bg-gradient-to-r from-alam-900/95 via-alam-900/85 to-alam-800/65 rtl:bg-gradient-to-l" aria-hidden="true"></div>';
    }

    $html .= '<div class="pattern-grid absolute inset-0 -z-10 opacity-25" aria-hidden="true"></div>'
        . '<div class="absolute -end-16 -top-24 -z-10 h-72 w-72 rounded-full bg-alam-500/40 blur-3xl" aria-hidden="true"></div>'
        . '<div class="grid gap-8 lg:grid-cols-[1.4fr_auto] lg:items-center">'
        . '<div class="max-w-xl">';

    if ($eyebrow !== '') {
        $html .= '<p class="eyebrow text-white/70">' . e($eyebrow) . '</p>';
    }

    if ($title !== '') {
        $html .= '<h2 class="mt-3.5 text-h2 text-white">' . e($title) . '</h2>';
    }

    if ($text !== '') {
        $html .= '<p class="mt-4 text-body text-white/75">' . e($text) . '</p>';
    }

    $html .= '</div><div class="flex flex-wrap gap-3 lg:justify-end">'
        . '<a href="' . e($primaryHref) . '" class="btn bg-white text-alam-800 hover:bg-alam-50 btn-lg">'
        . cta_label_html($primaryLabel) . icon('arrow-right', 'icon-sm icon-flip')
        . '</a>';

    if ($hasSecondary) {
        $html .= '<a href="' . e($secondHref) . '" class="btn-ghost-light btn-lg">' . cta_label_html($secondLabel) . '</a>';
    }

    $html .= '</div></div></div></div></section>';

    return $html;
}

/* =========================================================================
 * Booking summary rows — shared between the wizard and the review step
 * ====================================================================== */
function summary_row(string $label, string $valueId, string $placeholder = '—', string $editStep = ''): string
{
    $html = '<div class="summary-row">'
        . '<dt class="summary-label">' . e($label) . '</dt>'
        . '<dd class="flex items-center gap-2 text-end">'
        . '<span class="summary-value" data-summary="' . e($valueId) . '">' . e($placeholder) . '</span>';

    if ($editStep !== '') {
        $html .= '<button type="button" class="text-alam-600 transition-colors hover:text-alam-800" data-goto-step="' . e($editStep) . '">'
            . '<span class="sr-only">' . e(t('summary.edit', ['label' => $label])) . '</span>'
            . icon('edit', 'icon-xs')
            . '</button>';
    }

    $html .= '</dd></div>';

    return $html;
}

/* =========================================================================
 * Date field — Flatpickr
 *
 * The public frontend has no `<input type="date">` left: every user-facing date
 * is this component. The real form field keeps `type="text"` and submits a
 * `YYYY-MM-DD` value, exactly as the native control did, so PHP handling is
 * unchanged; js/datepicker.js turns it into a Flatpickr control and shows the
 * reader a friendly "27 August 2026" in a generated alt input.
 *
 * Behaviour is declared with data attributes, never with per-page JavaScript:
 *   data-date-type   'booking' (min = today) or 'any' — configured by purpose
 *   data-min-date / data-max-date   Flatpickr date strings ("today", "2026-01-01")
 *   data-alt-format  override the display format for a tight column
 *   data-link-min    id of another date field that may not be earlier than this
 *
 * $o keys: id, name, value, type, min, max, alt_format, link_min, required,
 *          clearable, label, placeholder, class, attrs (extra HTML attributes).
 * ====================================================================== */
function date_field(array $o = []): string
{
    $id       = (string) ($o['id'] ?? '');
    $name     = (string) ($o['name'] ?? '');
    $type     = (string) ($o['type'] ?? 'booking');
    $min      = (string) ($o['min'] ?? ($type === 'booking' ? 'today' : ''));
    $max      = (string) ($o['max'] ?? '');
    $value    = (string) ($o['value'] ?? '');
    $required = !empty($o['required']);
    $ph       = (string) ($o['placeholder'] ?? t('date.select'));
    $class    = trim('input js-datepicker ' . (string) ($o['class'] ?? ''));
    $clearable = !empty($o['clearable']);
    /* The accessible name of the clear button — "Clear arrival date". Callers
       pass a translation key rather than a phrase so the label follows the
       page locale. */
    $label     = t((string) ($o['label'] ?? 'date.label.generic'));

    $attrs = [
        'type'           => 'text',
        'class'          => $class,
        'value'          => $value,
        'placeholder'    => $ph,
        'autocomplete'   => 'off',
        'data-date-type' => $type,
    ];
    if ($id !== '')   { $attrs['id'] = $id; }
    if ($name !== '') { $attrs['name'] = $name; }
    if ($min !== '')  { $attrs['data-min-date'] = $min; }
    if ($max !== '')  { $attrs['data-max-date'] = $max; }
    if (!empty($o['alt_format'])) { $attrs['data-alt-format'] = (string) $o['alt_format']; }
    if (!empty($o['link_min']))   { $attrs['data-link-min'] = (string) $o['link_min']; }
    if ($clearable) { $attrs['data-clearable'] = 'true'; }
    if ($required) { $attrs['required'] = 'required'; }
    foreach (($o['attrs'] ?? []) as $k => $v) {
        $attrs[(string) $k] = (string) $v;
    }

    $attrsHtml = '';
    foreach ($attrs as $k => $v) {
        $attrsHtml .= ' ' . e((string) $k) . '="' . e((string) $v) . '"';
    }

    $html = '<div class="datepicker-field">'
        . '<input' . $attrsHtml . '>';

    if ($clearable) {
        $html .= '<button type="button" class="datepicker-clear" data-datepicker-clear>'
            . '<span class="sr-only">' . e(t('date.clear', ['label' => $label])) . '</span>' . icon('close', 'icon-xs')
            . '</button>';
    }

    $html .= '<span class="datepicker-icon" data-datepicker-toggle aria-hidden="true">' . icon('calendar', 'icon-sm') . '</span>'
        . '</div>';

    return $html;
}

/* =========================================================================
 * Phone field — intl-tel-input
 *
 * One component for every public telephone field. It replaces the old
 * "[+966 Select2] [national number]" pair: intl-tel-input owns the country
 * selector, the dial code, the flag, the search and the country-aware
 * placeholder, so no dial-code list is maintained here any more.
 *
 * What PHP receives does not change. The visible input is deliberately
 * unnamed — it is only what the visitor types into — and the value that gets
 * posted lives in hidden inputs that keep the field names the backend already
 * expects:
 *
 *   name="phone"      the canonical E.164 number, e.g. +966501234567
 *   name="dial_code"  the selected country's dial code, e.g. +966
 *   name="…_country"  the selected ISO2 country, when 'country_name' is given
 *
 * js/phone-input.js fills all three as the visible value or selected country
 * changes. Behaviour is declared with data attributes, never with per-page
 * JavaScript:
 *
 *   data-phone-name / data-dial-name / data-country-name  hidden field names
 *   data-default-country="sa"   fallback country when the value has no + prefix
 *   data-phone-required="true"  validate as required (mirrors the `required` attr)
 *   data-error-key="mobile"     the [data-error="…"] node this field owns
 *
 * $o keys: id, name, value, label, required, placeholder, hint, class,
 *          default_country, country_name, error_key, autocomplete, attrs.
 *
 * `id` is the visible input's id and must differ from `name` (the hidden
 * field's) — pass e.g. id "mobile_input" for name "mobile", and point the
 * <label for> at the same id. Passing them equal is corrected here rather than
 * left to produce a form whose named lookup returns two elements.
 * ====================================================================== */
function phone_field(array $o = []): string
{
    $id       = (string) ($o['id'] ?? 'phone');
    $name     = (string) ($o['name'] ?? 'phone');
    /* The visible input's id may never equal a hidden input's name: a form's
       named-element lookup matches both, so `$form->mobile` (or form.mobile in
       JS) would resolve to two elements and read as empty. */
    if ($id === $name) {
        $id = $name . '_input';
    }
    $value    = (string) ($o['value'] ?? '');
    $required = !empty($o['required']);
    $country  = (string) ($o['default_country'] ?? 'sa');
    $dialName = array_key_exists('dial_name', $o) ? (string) $o['dial_name'] : 'dial_code';
    $isoName  = (string) ($o['country_name'] ?? '');
    $errorKey = (string) ($o['error_key'] ?? $name);
    $class    = trim('input js-phone-input ' . (string) ($o['class'] ?? ''));

    $attrs = [
        'type'                  => 'tel',
        'id'                    => $id,
        'class'                 => $class,
        'autocomplete'          => (string) ($o['autocomplete'] ?? 'tel'),
        'data-phone-name'       => $name,
        'data-default-country'  => $country,
        'data-error-key'        => $errorKey,
    ];
    if ($dialName !== '') { $attrs['data-dial-name'] = $dialName; }
    if ($isoName !== '')  { $attrs['data-country-name'] = $isoName; }
    if ($required) {
        $attrs['required'] = 'required';
        $attrs['data-phone-required'] = 'true';
    }
    foreach (($o['attrs'] ?? []) as $k => $v) {
        $attrs[(string) $k] = (string) $v;
    }

    $attrsHtml = '';
    foreach ($attrs as $k => $v) {
        $attrsHtml .= ' ' . e((string) $k) . '="' . e((string) $v) . '"';
    }

    // The hidden inputs below carry the values the backend reads. The shared
    // phone initialiser keeps them synchronized with the visible control.
    $html = '<div class="phone-field">'
        . '<input' . $attrsHtml . '>'
        . '<input type="hidden" name="' . e($name) . '" value="' . e($value) . '" data-phone-value>';

    if ($dialName !== '') {
        $html .= '<input type="hidden" name="' . e($dialName) . '" value="" data-phone-dial>';
    }

    if ($isoName !== '') {
        $html .= '<input type="hidden" name="' . e($isoName) . '" value="" data-phone-country>';
    }

    $html .= '</div>';

    return $html;
}

/* =========================================================================
 * Language switcher
 *
 * One function, two presentations, so the desktop header and the mobile menu
 * never drift apart:
 *   variant 'desktop' — compact trigger + floating dropdown
 *   variant 'mobile'  — full-width row inside the mobile menu
 *
 * Every locale in locales() is live, and each one's `url` is the CURRENT
 * page in that locale with its query string intact (current_url_in), so
 * switching from /ar/tour-details?tour=uhud-mountain-battle lands on
 * /tour-details?tour=uhud-mountain-battle rather than on the home page.
 *
 * The option for the active locale stays a <button aria-current="true"> — it is
 * a visible state, not a link to the page you are already on. Every other
 * option is a real <a href>, so it works with JavaScript off, opens in a new
 * tab on middle-click, and is crawlable. Loading that explicit locale URL also
 * lets the controller persist the choice in an HttpOnly preference cookie.
 * Menu behaviour lives in js/main.js.
 *
 * `native` is deliberately not translated: a reader hunting for Arabic looks
 * for "العربية" whichever locale the page is currently in. The accessible name
 * around it is translated.
 * ====================================================================== */
function language_switcher(array $o = []): string
{
    $variant  = $o['variant'] ?? 'desktop';
    $id       = $o['id'] ?? 'lang-' . $variant;
    $locales  = locales();
    $current  = current_locale();
    $active   = locale($current) ?? $locales[0];
    $isMobile = $variant === 'mobile';

    $trigger = $isMobile
        ? 'flex w-full items-center justify-between gap-3 rounded-control border border-line-strong bg-white px-3.5 py-3 text-nav font-semibold text-ink min-h-[48px] transition-colors hover:border-alam-400 hover:text-alam-700'
        : 'flex items-center gap-1.5 rounded-control px-2.5 py-2 text-nav font-semibold text-ink-muted min-h-[40px] transition-colors hover:bg-alam-50 hover:text-alam-700 aria-expanded:bg-alam-50 aria-expanded:text-alam-700';

    /* Logical inset: the dropdown hangs off the end edge of the trigger, which
       is the right in English and the left in Arabic. */
    $menu = $isMobile
        ? 'absolute inset-x-0 top-full z-50 mt-1.5 origin-top rounded-card border border-line-strong bg-white p-1.5 shadow-lift'
        : 'absolute end-0 top-full z-50 mt-1.5 w-56 origin-top rounded-card border border-line-strong bg-white p-1.5 shadow-lift';

    $html = '<div class="relative' . ($isMobile ? '' : ' hidden lg:block') . '" data-lang-switcher>'
        . '<button type="button"'
        . ' id="' . e($id) . '-trigger"'
        . ' class="' . $trigger . '"'
        . ' aria-haspopup="menu"'
        . ' aria-expanded="false"'
        . ' aria-controls="' . e($id) . '-menu"'
        . ' data-lang-trigger>';

    if ($isMobile) {
        $html .= '<span class="flex items-center gap-2.5">'
            . icon('language', 'icon-md text-alam-500')
            . '<span class="font-medium text-ink-muted">' . e(t('lang.label')) . '</span>'
            . '</span>'
            . '<span class="flex items-center gap-1.5">'
            . '<span lang="' . e($active['code']) . '" data-lang-current>' . e($active['native']) . '</span>'
            . icon('chevron-down', 'icon-sm text-ink-soft transition-transform duration-200', ['data-lang-chevron' => ''])
            . '</span>';
    } else {
        $html .= '<span class="sr-only">' . e(t('lang.change')) . '</span>'
            . icon('language', 'icon-md text-alam-500')
            . '<span lang="' . e($active['code']) . '" data-lang-current>' . e($active['native']) . '</span>'
            . icon('chevron-down', 'icon-xs text-ink-soft transition-transform duration-200', ['data-lang-chevron' => '']);
    }

    $html .= '</button>'
        . '<div id="' . e($id) . '-menu" class="' . $menu . ' hidden" role="menu"'
        . ' aria-labelledby="' . e($id) . '-trigger" data-lang-menu>';

    foreach ($locales as $l) {
        $isActive = $l['code'] === $current;
        $row = 'flex w-full items-center justify-between gap-3 rounded-[8px] px-3 py-2.5 text-start text-body-sm min-h-[44px] transition-colors';

        if (!$isActive) {
            // A real link to the same page in the other locale. hreflang tells
            // assistive tech and crawlers what is on the other end.
            $html .= '<a href="' . e($l['url']) . '" role="menuitem"'
                . ' class="' . $row . ' text-ink hover:bg-alam-50 hover:text-alam-700"'
                . ' lang="' . e($l['code']) . '" hreflang="' . e(locale_tag($l['code'])) . '"'
                . ' data-locale="' . e($l['code']) . '" data-lang-option>'
                . '<span>' . e($l['native']) . '</span>'
                . '<span class="sr-only">' . e(t('lang.switchTo', ['language' => $l['label']])) . '</span>'
                . '</a>';
        } else {
            $html .= '<button type="button" role="menuitem"'
                . ' class="' . $row . ' bg-alam-50 font-semibold text-alam-700"'
                . ' lang="' . e($l['code']) . '"'
                . ' data-locale="' . e($l['code']) . '"'
                . ' aria-current="true"'
                . ' data-lang-option>'
                . '<span>' . e($l['native']) . '</span>'
                . icon('check', 'icon-sm shrink-0 text-alam-600')
                . '</button>';
        }
    }

    $html .= '</div></div>';

    return $html;
}

/* =========================================================================
 * Breadcrumbs
 * ====================================================================== */
function breadcrumb(array $items, string $tone = 'light'): string
{
    // $tone describes the TEXT: 'light' sits on the purple banner,
    // 'dark' sits on a white or tinted background.
    $isLight = $tone === 'light';
    $base    = $isLight ? 'text-white/65' : 'text-ink-muted';
    $link    = $isLight ? 'hover:text-white' : 'hover:text-alam-700';
    $sep     = $isLight ? 'text-white/40' : 'text-line-strong';
    $current = $isLight ? 'text-white' : 'text-ink';

    $html = '<nav aria-label="' . e(t('breadcrumb.label')) . '" class="text-meta">'
        . '<ol class="flex flex-wrap items-center gap-1.5 ' . $base . '">';

    $last = count($items) - 1;
    foreach ($items as $i => $item) {
        $html .= '<li class="flex items-center gap-1.5">';

        if ($i < $last && !empty($item['href'])) {
            // The separator points the way the trail reads, so it flips with
            // the document; the crumb ORDER is handled by the flex row,
            // which already follows `dir`.
            $html .= '<a href="' . e($item['href']) . '" class="transition-colors ' . $link . '">' . e($item['label']) . '</a>'
                . icon('chevron-right', 'icon-xs icon-flip ' . $sep);
        } else {
            $html .= '<span class="font-medium ' . $current . '" aria-current="page">' . e($item['label']) . '</span>';
        }

        $html .= '</li>';
    }

    $html .= '</ol></nav>';

    return $html;
}

/* =========================================================================
 * Experience card
 *
 * Same visual treatment as tour_card(): the two product types sit next to
 * each other on the homepage, so they share one card style. The differences are
 * in the content, not the styling — the badge carries the experience category,
 * the metadata row is built from whichever fields that activity actually has
 * (experience_meta drops the empty ones), and the calculated starting price
 * uses the same presentation as a tour card.
 *
 * Options:
 *   eager  bool   — first row of a grid, skips lazy loading
 *   cta    string — link label, defaults to 'View Experience'
 *   meta   int    — maximum metadata cells, 0 for all (default 4, as per tours)
 * ====================================================================== */
function experience_card(array $x, array $o = []): string
{
    $href = experience_url($x['slug']);
    $lazy = ($o['eager'] ?? false) ? 'eager' : 'lazy';
    $cta  = $o['cta'] ?? t('cta.viewExperience');
    /* Filter on the translation KEY, never on the visible label: comparing
       against the English word "Area" would silently stop dropping that pill
       the moment the page is rendered in Arabic. */
    $meta = array_values(array_filter(
        experience_meta($x),
        static function (array $m) { return $m['key'] !== 'meta.area'; }
    ));
    $meta = array_slice($meta, 0, (int) ($o['meta'] ?? 4));
    /* Languages sits outside the capped grid and always renders full width,
       last — same treatment tour_card() gives it. */
    $languageLabel = trim((string) ($x['language'] ?? ''));

    /* `category_ids` (stable database ids) drives filtering when a caller
       supplies it; a prototype record with no ids falls back to filtering on
       its own `category` key, exactly as before. */
    $categoryIds = isset($x['category_ids']) && is_array($x['category_ids'])
        ? $x['category_ids']
        : array($x['category']);
    $categoryFallback = isset($x['category_ids'])
        ? (string) $x['category']
        : experience_category_label($x['category'], true);
    $categoryLabels = isset($x['categories']) && is_array($x['categories'])
        ? array_values(array_filter(array_map('strval', $x['categories'])))
        : array();
    if (empty($categoryLabels) && $categoryFallback !== '') {
        $categoryLabels[] = $categoryFallback;
    }

    $experienceImage = trim((string) ($x['image'] ?? ''));
    $experienceImageAbsolute = $experienceImage !== '' && is_absolute_url($experienceImage);

    $html = '<article class="card card-hover group relative flex h-full flex-col overflow-hidden"'
        . ' data-experience-card'
        . ' data-category="' . e($x['category']) . '"'
        . ' data-categories="' . e(implode(',', $categoryIds)) . '"'
        . ' data-slug="' . e($x['slug']) . '">';

    if ($experienceImage !== '') {
        $html .= '<a href="' . e($href) . '" class="relative block aspect-[3/2] overflow-hidden bg-alam-100" tabindex="-1" aria-hidden="true"' . htmx_boost_attr() . '>'
            . '<img src="' . e($experienceImageAbsolute ? $experienceImage : site_base_url($experienceImage)) . '"'
            . (!$experienceImageAbsolute
                ? ' srcset="' . e(srcset($experienceImage)) . '" sizes="(min-width: 1024px) 33vw, (min-width: 640px) 50vw, 100vw"'
                : '')
            . ' alt="" width="960" height="640" loading="' . $lazy . '" decoding="async"'
            . ' class="h-full w-full object-cover transition-transform duration-500 group-hover:scale-[1.04]">';

        $html .= category_badges($categoryLabels);

        $html .= '</a>';
    }

    $html .= '<div class="flex flex-1 flex-col p-5">'
        . '<h3 class="text-card-title font-bold text-ink">'
        . '<a href="' . e($href) . '" class="after:absolute after:inset-0 focus-visible:outline-none"' . htmx_boost_attr() . '>'
        . e($x['name'])
        . '</a></h3>'
        . '<p class="mt-2 line-clamp-3 text-card-body text-ink-muted">' . e($x['short_description']) . '</p>';

    if ($meta || $languageLabel !== '') {
        $html .= '<div class="mt-4 border-t border-line pt-4 text-meta">';

        if ($meta) {
            $html .= '<dl class="grid grid-cols-2 gap-x-3 gap-y-2">';

            foreach ($meta as $m) {
                $html .= '<div class="flex items-center gap-1.5 text-ink-muted">'
                    . icon($m['icon'], 'icon-sm shrink-0 text-alam-500')
                    . '<dt class="sr-only">' . e($m['label']) . '</dt>'
                    . '<dd>' . (($m['icon'] == "users") ? '&nbsp;' : '') . e($m['value']) . '</dd>'
                    . '</div>';
            }

            $html .= '</dl>';
        }

        if ($languageLabel !== '') {
            $html .= '<dl class="' . ($meta ? 'mt-4 ' : '') . 'w-full">'
                . '<div class="flex w-full items-baseline gap-1.5 text-ink-muted">'
                . icon('language', 'icon-sm shrink-0 text-alam-500')
                . '<dt class="sr-only">' . e(t('meta.languages')) . '</dt><dd>' . e($languageLabel) . '</dd>'
                . '</div></dl>';
        }

        $html .= '</div>';
    }

    $html .= '<div class="mt-auto flex items-end justify-between gap-3 pt-5">';

    if (array_key_exists('price', $x)) {
        $html .= '<p class="text-meta text-ink-muted">'
            . e(t('card.from')) . ' <span class="block text-xl font-extrabold leading-tight text-ink">' . e(price($x['price'])) . '</span>'
            . '</p>';
    } elseif (!empty($x['area'])) {
        $html .= '<p class="min-w-0 text-meta text-ink-muted">' . e($x['area']) . '</p>';
    } else {
        $html .= '<span></span>';
    }

    $html .= '<span class="btn-secondary btn-sm relative z-10 shrink-0 pointer-events-none">' . e($cta) . '</span>'
        . '</div>'
        . '</div>'
        . '</article>';

    return $html;
}

/* =========================================================================
 * Experiences teaser — the compact block used on the homepage and About Us.
 *
 * One function so the two pages cannot drift apart. `count` decides how many
 * cards, `columns` the desktop grid, and everything else is copy.
 * ====================================================================== */
function experiences_teaser(array $o = []): string
{
    $count   = (int) ($o['count'] ?? 6);
    /* 'items' lets a caller supply its own record set (e.g. the Home page's
       DB-backed experiences) instead of the shared sample catalogue. */
    $items   = isset($o['items']) && is_array($o['items']) ? $o['items'] : featured_experiences($count);
    $columns = ($o['columns'] ?? 3) === 4
        ? 'sm:grid-cols-2 lg:grid-cols-4'
        : 'sm:grid-cols-2 lg:grid-cols-3';
    $bg      = $o['bg'] ?? 'bg-white';
    $eyebrow = $o['eyebrow'] ?? t('expTeaser.eyebrow');
    $title   = $o['title'] ?? t('expTeaser.title');
    $text    = $o['text'] ?? t('expTeaser.text');
    $ctaHtml = array_key_exists('cta_html', $o)
        ? (string) $o['cta_html']
        : '<a href="' . e(url('experiences.php')) . '" class="btn-outline shrink-0">'
            . e($o['cta'] ?? t('cta.exploreAllExperiences'))
            . icon('arrow-right', 'icon-sm icon-flip')
            . '</a>';

    $html = '<section class="relative overflow-hidden ' . e($bg) . ' py-16 sm:py-20 lg:py-24">'
        . '<div class="absolute -start-24 top-8 -z-0 h-72 w-72 rounded-full bg-alam-100/60 blur-3xl" aria-hidden="true"></div>'
        . '<div class="container relative">'
        . '<div class="flex flex-col gap-6 sm:flex-row sm:items-end sm:justify-between">'
        . section_head([
            'eyebrow' => $eyebrow,
            'title'   => $title,
            'text'    => $text,
        ])
        . $ctaHtml
        . '</div>';

    if (!empty($items)) {
        $html .= '<ul class="mt-10 grid gap-5 ' . $columns . ' lg:mt-12 lg:gap-6">';

        foreach ($items as $i => $x) {
            $html .= '<li data-reveal data-reveal-delay="' . (($i % 3) * 60) . '">' . experience_card($x) . '</li>';
        }

        $html .= '</ul>';
    }

    $html .= '</div></section>';

    return $html;
}

/* =========================================================================
 * Blog category bar
 *
 * A second, sticky navigation strip that sits directly under the site header
 * on every blog page. Categories, and their published-post counts, are
 * prepared by the controller from `blog_categories` — this renderer only
 * turns them into markup.
 *
 * $categories: [['id' => ..., 'slug' => ..., 'label' => ...], ...], already
 * in their managed display order.
 *
 * Options:
 *   active       — category slug of the current view, '' for "All articles"
 *   inline_limit — how many categories sit inline on large screens before the
 *                  remainder move into the "More topics" dropdown
 * ====================================================================== */
function blog_category_nav(array $categories, array $o = []): string
{
    $active = (string) ($o['active'] ?? '');
    $limit  = max(1, (int) ($o['inline_limit'] ?? 5));
    // Option `boost`: the Blog listing loads categories through HTMX.
    $boost  = !empty($o['boost']) ? ' hx-boost="true"' : '';

    $inline = array_slice($categories, 0, $limit);
    $more   = array_slice($categories, $limit);

    /* The category slug is the managed, stable `cat_slug` (English), so the
       English and Arabic URIs select the same set regardless of the display
       locale. */
    $href = static function (string $slug): string {
        return $slug === ''
            ? blog_category_url('')
            : blog_category_url($slug);
    };

    // Phone/tablet: one trigger that opens the full list. Large screens: the
    // categories inline, with overflow in a "More topics" dropdown.
    $html = '<nav class="sticky top-[var(--frontend-header-h)] z-40 border-b border-alam-800/30 bg-alam-600 text-white shadow-[0_1px_0_rgba(0,0,0,0.06)]"'
        . ' aria-label="' . e(t('blog.nav.label')) . '" data-sticky-bar data-blog-cat-nav' . $boost . '>'
        . '<div class="container">'
        . '<button type="button"'
        . ' class="flex h-12 w-full items-center justify-center gap-2 text-nav font-bold text-white lg:hidden"'
        . ' data-blog-cat-toggle aria-expanded="false" aria-controls="blog-cat-panel">'
        . '<span>' . e(t('blog.categories')) . '</span>'
        . icon('chevron-down', 'icon-sm transition-transform duration-200', ['data-blog-cat-chevron' => ''])
        . '</button>'
        . '<ul class="hidden h-12 items-center justify-center gap-1 lg:flex xl:gap-2">'
        . '<li>'
        . '<a href="' . e($href('')) . '" class="blog-cat-link' . ($active === '' ? ' is-active' : '') . '"'
        . ' ' . ($active === '' ? 'aria-current="page"' : '') . '>' . e(t('blog.allArticles')) . '</a>'
        . '</li>';

    foreach ($inline as $c) {
        $html .= '<li>'
            . '<a href="' . e($href($c['slug'])) . '"'
            . ' class="blog-cat-link' . ($active === $c['slug'] ? ' is-active' : '') . '"'
            . ' ' . ($active === $c['slug'] ? 'aria-current="page"' : '') . '>' . e($c['label']) . '</a>'
            . '</li>';
    }

    if ($more) {
        $html .= '<li class="relative" data-blog-cat-more>'
            . '<button type="button" class="blog-cat-link flex items-center gap-2"'
            . ' data-blog-cat-more-toggle aria-expanded="false" aria-controls="blog-cat-more">'
            . e(t('blog.moreTopics'))
            . icon('chevron-down', 'icon-xs transition-transform duration-200', ['data-blog-cat-chevron' => ''])
            . '</button>'
            . '<ul id="blog-cat-more"'
            . ' class="absolute left-1/2 top-full hidden w-60 -translate-x-1/2 overflow-hidden rounded-b-card bg-alam-600 py-2 shadow-lg ring-1 ring-black/10"'
            . ' data-blog-cat-more-panel>';

        foreach ($more as $c) {
            $html .= '<li>'
                . '<a href="' . e($href($c['slug'])) . '"'
                . ' class="block px-5 py-2.5 text-body-sm font-medium text-white transition-colors hover:bg-alam-700' . ($active === $c['slug'] ? ' bg-alam-700' : '') . '"'
                . ' ' . ($active === $c['slug'] ? 'aria-current="page"' : '') . '>'
                . '<span>' . e($c['label']) . '</span>'
                . '</a>'
                . '</li>';
        }

        $html .= '</ul></li>';
    }

    $html .= '</ul></div>';

    // Phone/tablet panel. Ships open-capable but hidden; JS toggles it.
    $html .= '<div id="blog-cat-panel" class="hidden max-h-[70vh] overflow-y-auto border-t border-white/20 bg-alam-600 lg:hidden"'
        . ' data-blog-cat-panel>'
        . '<ul class="container divide-y divide-white/15 py-1">'
        . '<li>'
        . '<a href="' . e($href('')) . '"'
        . ' class="block py-3.5 text-nav font-medium text-white' . ($active === '' ? ' font-bold' : '') . '"'
        . ' ' . ($active === '' ? 'aria-current="page"' : '') . '>'
        . '<span>' . e(t('blog.allArticles')) . '</span>'
        . '</a>'
        . '</li>';

    foreach ($categories as $c) {
        $html .= '<li>'
            . '<a href="' . e($href($c['slug'])) . '"'
            . ' class="block py-3.5 text-nav font-medium text-white' . ($active === $c['slug'] ? ' font-bold' : '') . '"'
            . ' ' . ($active === $c['slug'] ? 'aria-current="page"' : '') . '>'
            . '<span>' . e($c['label']) . '</span>'
            . '</a>'
            . '</li>';
    }

    $html .= '</ul></div></nav>';

    return $html;
}

/* =========================================================================
 * Pagination
 *
 * Numbered pagination built from real <a href> links — the form search engines
 * can actually crawl. Renders nothing for a single page.
 *
 * Options:
 *   current — page currently being viewed (1-based)
 *   total   — total number of pages
 *   base    — URL the page links hang off, e.g. blog_category_url('')
 *   query   — extra query parameters to carry across pages, e.g. ['category' => 'sirah']
 *   window  — how many numbers to show either side of the current page
 *   label   — aria-label for the <nav>
 *   boost   — load the page links through HTMX (the enclosing element sets
 *             hx-target/hx-select; see views/frontend/blog.php)
 * ====================================================================== */
function pagination(array $o = []): string
{
    $total   = max(1, (int) ($o['total'] ?? 1));
    $current = min($total, max(1, (int) ($o['current'] ?? 1)));
    $base    = (string) ($o['base'] ?? site_base_url());
    $query   = (array) ($o['query'] ?? []);
    $window  = max(1, (int) ($o['window'] ?? 1));
    $label   = (string) ($o['label'] ?? t('pagination.label'));

    if ($total < 2) {
        return '';
    }

    /* Page 1 keeps the clean URL — ?page=1 would be a duplicate of it. */
    $href = static function (int $page) use ($base, $query): string {
        $params = $query;
        if ($page > 1) {
            $params['page'] = $page;
        }
        return $params ? $base . '?' . http_build_query($params) : $base;
    };

    /* First, last and a window around the current page; everything else
       collapses into an ellipsis. */
    $pages = [];
    for ($i = 1; $i <= $total; $i++) {
        if ($i === 1 || $i === $total || abs($i - $current) <= $window) {
            $pages[] = $i;
        } elseif (end($pages) !== '…') {
            $pages[] = '…';
        }
    }

    $html = '<nav class="mt-12 border-t border-line pt-8" aria-label="' . e($label) . '"'
        . (!empty($o['boost']) ? ' hx-boost="true"' : '') . '>'
        . '<div class="flex flex-col items-center gap-4 sm:flex-row sm:justify-between">'
        . '<p class="order-2 text-meta text-ink-muted sm:order-1">'
        . e(t('pagination.pageOf', ['current' => number($current), 'total' => number($total)]))
        . '</p>'
        . '<ul class="order-1 flex flex-wrap items-center justify-center gap-1.5 sm:order-2">'
        . '<li>';

    if ($current > 1) {
        $html .= '<a href="' . e($href($current - 1)) . '" class="page-step" rel="prev">'
            . icon('chevron-left', 'icon-xs icon-flip') . '<span class="hidden sm:inline">' . e(t('pagination.prev')) . '</span>'
            . '<span class="sr-only sm:hidden">' . e(t('pagination.prevPage')) . '</span>'
            . '</a>';
    } else {
        $html .= '<span class="page-step is-disabled" aria-disabled="true">'
            . icon('chevron-left', 'icon-xs icon-flip') . '<span class="hidden sm:inline">' . e(t('pagination.prev')) . '</span>'
            . '</span>';
    }

    $html .= '</li>';

    foreach ($pages as $p) {
        $html .= '<li>';

        if ($p === '…') {
            $html .= '<span class="grid h-11 w-8 place-items-center text-meta text-ink-soft" aria-hidden="true">…</span>';
        } elseif ($p === $current) {
            $html .= '<a href="' . e($href((int) $p)) . '" class="page-link is-active" aria-current="page">'
                . e(number($p)) . '<span class="sr-only">' . e(t('pagination.currentPage')) . '</span>'
                . '</a>';
        } else {
            $html .= '<a href="' . e($href((int) $p)) . '" class="page-link">'
                . '<span class="sr-only">' . e(t('pagination.pagePrefix')) . '</span>' . e(number($p))
                . '</a>';
        }

        $html .= '</li>';
    }

    $html .= '<li>';

    if ($current < $total) {
        $html .= '<a href="' . e($href($current + 1)) . '" class="page-step" rel="next">'
            . '<span class="hidden sm:inline">' . e(t('pagination.next')) . '</span><span class="sr-only sm:hidden">' . e(t('pagination.nextPage')) . '</span>'
            . icon('chevron-right', 'icon-xs icon-flip')
            . '</a>';
    } else {
        $html .= '<span class="page-step is-disabled" aria-disabled="true">'
            . '<span class="hidden sm:inline">' . e(t('pagination.next')) . '</span>' . icon('chevron-right', 'icon-xs icon-flip')
            . '</span>';
    }

    $html .= '</li></ul></div></nav>';

    return $html;
}

/* =========================================================================
 * Featured posts rail
 *
 * The blog article sidebar: a titled card — icon tile, eyebrow, heading and a
 * line of supporting copy — over a list of articles, each a thumbnail with a
 * category chip and title.
 *
 * $posts is prepared by the controller (Blog_model::get_rail_posts(), already
 * featured-first then most recent, and already excluding the article being
 * read) — this renderer only turns it into markup. Renders nothing when
 * $posts is empty rather than reaching for sample content.
 *
 * Desktop only — on narrow screens the article page already ends with the
 * "Keep reading" grid, and a second list of the same articles above it is
 * noise. blog-post.php hides the rail below lg.
 *
 * $content is prepared from miscellaneous_content_sections['featured_posts'].
 * A disabled or unavailable managed section hides the rail rather than
 * falling back to hardcoded copy.
 * ====================================================================== */
function featured_posts_rail(array $posts, array $content, bool $boost = false): string
{
    if (!$posts || !$content) {
        return '';
    }

    $iconName = trim((string) ($content['icon'] ?? ''));
    $eyebrow = trim((string) ($content['eyebrow'] ?? ''));
    $title = trim((string) ($content['title'] ?? ''));
    $text = trim((string) ($content['text'] ?? ''));
    $link = isset($content['link']) && is_array($content['link'])
        ? $content['link']
        : array();

    // Same header treatment as help_card(): the brand's geometric motif
    // faded into the top of the card behind a 2px brand rule. Two elements
    // rather than a mask, so the fade renders identically in every browser
    // this site supports.
    $html = '<section class="relative overflow-hidden rounded-card border border-line bg-white p-6 shadow-card"'
        . ($title !== '' ? ' aria-labelledby="featured-posts-title"' : '') . '>'
        . '<span class="pattern-grid pointer-events-none absolute inset-x-0 top-0 h-28 opacity-70" aria-hidden="true"></span>'
        . '<span class="pointer-events-none absolute inset-x-0 top-0 h-28 bg-gradient-to-b from-white/40 to-white" aria-hidden="true"></span>'
        . '<span class="pointer-events-none absolute inset-x-0 top-0 h-0.5 bg-alam-500" aria-hidden="true"></span>'
        . '<div class="relative">';

    if ($iconName !== '') {
        $html .= '<span class="grid h-14 w-14 place-items-center rounded-card bg-alam-50 text-alam-600 ring-1 ring-alam-100">'
            . icon($iconName, 'icon-xl')
            . '</span>';
    }
    if ($eyebrow !== '') {
        $html .= '<p class="eyebrow-plain mt-5">' . e($eyebrow) . '</p>';
    }
    if ($title !== '') {
        $html .= '<h2 id="featured-posts-title" class="mt-2 text-h3 text-ink">' . e($title) . '</h2>';
    }
    if ($text !== '') {
        $html .= '<p class="mt-3 text-body-sm text-ink-muted">' . e($text) . '</p>';
    }

    // $boost: every rail link is a blog post, loaded through HTMX.
    $html .= '</div><ul class="relative mt-6 space-y-1"' . ($boost ? ' hx-boost="true"' : '') . '>';

    foreach ($posts as $p) {
        // The row itself is the hover and focus surface: a 300px-wide
        // sidebar gives a text link a small target, so the whole row lights
        // up instead.
        $html .= '<li>'
            . '<article class="group relative -mx-3 flex items-start gap-3.5 rounded-control px-3 py-2.5'
            . ' transition-colors hover:bg-alam-50 focus-within:bg-alam-50">';

        if (!empty($p['image'])) {
            $html .= '<div class="aspect-[4/3] w-20 shrink-0 overflow-hidden rounded-control bg-alam-100">'
                . '<img src="' . e($p['image']) . '"'
                . ' alt="" width="1200" height="675" loading="lazy" decoding="async"'
                . ' class="h-full w-full object-cover">'
                . '</div>';
        }

        $html .= '<div class="min-w-0">'
            . '<h3 class="line-clamp-2 text-body-sm font-semibold leading-snug text-ink">'
            . '<a href="' . e(blog_post_url($p['slug'])) . '"'
            . ' class="transition-colors after:absolute after:inset-0 group-hover:text-alam-700">'
            . e($p['title'])
            . '</a>'
            . '</h3>'
            . '</div>'
            . '</article>'
            . '</li>';
    }

    $html .= '</ul>';
    $html .= button_link(
        $link['text'] ?? '',
        $link['url'] ?? '',
        $link['icon'] ?? '',
        $link['icon_pos'] ?? '',
        'btn-link relative mt-5',
        'icon-xs icon-flip'
    );
    $html .= '</section>';

    return $html;
}
