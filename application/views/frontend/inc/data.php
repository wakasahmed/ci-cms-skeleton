<?php
/**
 * Alam Al-Munawara — temporary frontend data.
 *
 * Editorial and booking records in this file are SAMPLE content standing in
 * for later page integrations. Shared business details already come from the
 * administrator-managed site_settings record. Each remaining accessor returns
 * the same shape the eventual model will provide:
 *
 *   $tours   = tours();          // -> Tour_model->get_all()
 *   $tour    = tour('uhud');     // -> Tour_model->get_by_slug()
 *   $guides  = guides();         // -> Guide_model->get_active()
 *
 * Guide eligibility (guide_matches) mirrors the rule the backend will run
 * against the availability tables — keep the signature stable.
 */


/* =========================================================================
 * Business details
 *
 * The site_settings record is loaded once by Frontend and cached in CI config.
 * Templates read the normalized array returned by business(), never
 * database columns or literal contact details directly.
 * ====================================================================== */
function site_settings(): array
{
    $CI =& get_instance();
    $settings = $CI->config->item('frontend_site_settings');

    return is_array($settings) ? $settings : [];
}

/** Read an administrator-managed text value without carrying stored entities. */
function setting_value(string $key, string $fallback = ''): string
{
    $settings = site_settings();
    $value = isset($settings[$key]) ? trim((string) $settings[$key]) : '';

    return $value !== ''
        ? html_entity_decode($value, ENT_QUOTES, 'UTF-8')
        : $fallback;
}

/** Read a field with an optional `_ar` counterpart for the current locale. */
function localized_setting(string $key, string $fallback = ''): string
{
    if (current_locale() === 'ar') {
        $arabic = setting_value($key . '_ar');
        if ($arabic !== '') {
            return $arabic;
        }
    }

    return setting_value($key, $fallback);
}

/** Normalize a configured public URL while retaining legacy values without a scheme. */
function public_url(string $value): string
{
    $value = trim($value);
    if ($value === '') {
        return '';
    }

    $url = preg_match('#^[a-z][a-z0-9+.-]*://#i', $value)
        ? $value
        : 'https://' . $value;

    $scheme = strtolower((string) parse_url($url, PHP_URL_SCHEME));
    if (!in_array($scheme, ['http', 'https'], true)) {
        return '';
    }

    return filter_var($url, FILTER_VALIDATE_URL) !== false ? $url : '';
}

/** Return an uploaded branding asset, or '' when none is configured. */
function brand_asset(string $column): string
{
    $filename = basename(setting_value($column));
    $relative = 'assets/frontend/images/logo/' . $filename;

    return $filename !== '' && is_file(FCPATH . $relative)
        ? base_url($relative)
        : '';
}

/** Social profiles configured under Manage > Website Settings. */
function social_links(): array
{
    $profiles = [
        'facebook' => ['icon' => 'facebook', 'label' => 'footer.social.facebook'],
        'instagram' => ['icon' => 'instagram', 'label' => 'footer.social.instagram'],
        'twitter' => ['icon' => 'x-social', 'label' => 'footer.social.x'],
        'linkedin' => ['icon' => 'linkedin', 'label' => 'footer.social.linkedin'],
        'youtube' => ['icon' => 'youtube', 'label' => 'footer.social.youtube'],
    ];
    $links = [];

    foreach ($profiles as $column => $profile) {
        $url = public_url(setting_value($column));
        if ($url === '') {
            continue;
        }

        $profile['url'] = $url;
        $links[] = $profile;
    }

    $phoneHref = preg_replace('/[^0-9]/', '', setting_value('phone'));
    if (!empty($phoneHref)) {
        $links[] = [
            'icon' => 'whatsapp',
            'label' => 'footer.social.whatsapp',
            'url' => 'https://wa.me/' . $phoneHref,
        ];
    }

    return $links;
}

/** Locale-aware headings and copy used by the shared footer. */
function footer_content(): array
{
    return [
        'about_heading' => localized_setting('foot_col_1'),
        'about' => localized_setting('website_intro'),
        'explore_heading' => localized_setting('foot_col_2'),
        'support_heading' => localized_setting('foot_col_3'),
        'contact_heading' => localized_setting('foot_col_4'),
        'contact_text' => localized_setting('contact_text'),
        'copyright' => localized_setting('copyright_text'),
        'license' => localized_setting('license_number'),
        'address' => localized_setting('address'),
        'payment_title' => localized_setting('payment_title'),
        'payment_icons' => payment_icons_image(),
    ];
}

/**
 * The "Payment Accepted" icons strip from Manage > Website Settings, resized
 * by width only (height follows the artwork). The footer shows it at up to
 * 320px wide, so a 640px variant covers 2x screens. Empty when not uploaded.
 */
function payment_icons_image(): array
{
    $filename = setting_value('payment_icons');
    $src = upload_thumb('logo', $filename, 320, 0, '');

    if ($src === '') {
        return [];
    }

    return [
        'src' => $src,
        'src_2x' => upload_thumb('logo', $filename, 640, 0, ''),
    ];
}

function business(): array
{
    static $cache = [];

    $locale = current_locale();
    if (isset($cache[$locale])) {
        return $cache[$locale];
    }

    $phone = setting_value('phone');
    $phoneHref = preg_replace('/[^0-9+]/', '', $phone);
    $website = setting_value('website_url');
    $websiteUrl = public_url($website);

    $cache[$locale] = [
        'name' => localized_setting('website_title'),
        'tagline' => t('business.tagline'),
        'location' => preg_replace(
            '/\s+/u',
            ' ',
            localized_setting('address')
        ),
        'phone' => $phone,
        'phone_href' => is_string($phoneHref) ? $phoneHref : '',
        'email' => setting_value('email'),
        'website' => $website,
        'website_url' => $websiteUrl,
        'license' => localized_setting('license_number'),
        'currency' => setting_value('currency_unit'),
        'logo' => brand_asset('logo'),
        'logo_white' => brand_asset('logo_white'),
        'favicon' => brand_asset('favicon'),
        'social_links' => social_links(),
    ];

    return $cache[$locale];
}

/* =========================================================================
 * Booking reference data
 * ====================================================================== */

/**
 * Vehicle / group-capacity options. Capacity and vehicle are one choice.
 *
 * `id`, `capacity`, `surcharge` and `icon` are operational: the booking wizard
 * filters guides on the id, prices on the surcharge and warns on the capacity,
 * so they are identical in both locales. Only `label`, `blurb` and `note` are
 * translated.
 */
function vehicles(): array
{
    return localize_list(vehicles_source(), 'vehicles', 'id');
}

function vehicles_source(): array
{
    return [
        [
            'id'        => 'small-car',
            'label'     => 'Small Car',
            'capacity'  => 2,
            'blurb'     => 'Up to 2 guests',
            'note'      => 'Best for couples and solo visitors.',
            'surcharge' => 0,
            'icon'      => 'car',
        ],
        [
            'id'        => 'sedan',
            'label'     => 'Sedan',
            'capacity'  => 4,
            'blurb'     => 'Up to 4 guests',
            'note'      => 'Comfortable for a small family.',
            'surcharge' => 60,
            'icon'      => 'car',
        ],
        [
            'id'        => 'suv',
            'label'     => 'SUV',
            'capacity'  => 7,
            'blurb'     => 'Up to 7 guests',
            'note'      => 'Extra room for larger families.',
            'surcharge' => 180,
            'icon'      => 'suv',
        ],
        [
            'id'        => 'coaster',
            'label'     => 'Coaster Bus',
            'capacity'  => 15,
            'blurb'     => 'Up to 15 guests',
            'note'      => 'For groups travelling together.',
            'surcharge' => 450,
            'icon'      => 'bus',
        ],
    ];
}

function vehicle(string $id): ?array
{
    foreach (vehicles() as $v) {
        if ($v['id'] === $id) {
            return $v;
        }
    }
    return null;
}

/* =========================================================================
 * Site locales (UI language)
 *
 * NOTE: this is deliberately separate from languages() below. That list is
 * the language a GUIDE speaks on a tour; this list is the language the WEBSITE
 * is rendered in. They happen to overlap today, but they are different things
 * and will diverge (a guide language could be added without translating the UI).
 *
 * Both locales are live. `url` is the CURRENT page in that locale, built by
 * url() from the route map in inc/i18n.php, so the switcher always lands
 * on the equivalent page with its query string intact rather than on the home
 * page. `native` is the language's own name and is never translated — a reader
 * looking for Arabic looks for "العربية", whichever locale they are in.
 * ====================================================================== */
function locales(): array
{
    $out = [];

    foreach (supported_locales() as $code) {
        $native = ['en' => 'English', 'ar' => 'العربية'];

        $out[] = [
            'code'      => $code,
            'label'     => t('lang.name.' . $code),
            'native'    => isset($native[$code]) ? $native[$code] : $code,
            'dir'       => locale_dir($code),
            'available' => true,
            'url'       => current_url_in($code),
        ];
    }

    return $out;
}

function locale(string $code): ?array
{
    foreach (locales() as $l) {
        if ($l['code'] === $code) {
            return $l;
        }
    }
    return null;
}

/**
 * Supported tour languages at this stage.
 *
 * `id` is what the booking form posts and what guide_matches() filters on
 * — 'ar' and 'en' in every locale. Only `label` is translated.
 */
function languages(): array
{
    return localize_list([
        ['id' => 'ar', 'label' => 'Arabic', 'native' => 'العربية'],
        ['id' => 'en', 'label' => 'English', 'native' => 'English'],
    ], 'languages', 'id');
}

function language_label(string $id): string
{
    foreach (languages() as $l) {
        if ($l['id'] === $id) {
            return $l['label'];
        }
    }

    /* Every sample-data caller passes 'ar'/'en' and always matches above.
       A guide's spoken languages (tour_guides, a real 7-language set) are
       resolved to their display name by the homepage controller before reaching
       here, so an unmatched id is passed through as already-correct text
       rather than blanked out. */
    return $id;
}

/**
 * Visiting slots offered each day.
 *
 * `id` is the value the booking form posts and the key guide availability is
 * matched on. The label, the displayed time range and the note are translated.
 */
function slots(): array
{
    return localize_list([
        ['id' => 'fajer',     'label' => 'Fajer',     'time' => '6:00 AM – 9:00 AM',  'note' => 'Cool, quiet and least crowded.'],
        ['id' => 'morning',   'label' => 'Morning',   'time' => '9:00 AM – 12:00 PM', 'note' => 'Our most requested slot.'],
        ['id' => 'afternoon', 'label' => 'Afternoon', 'time' => '1:00 PM – 4:00 PM',  'note' => 'Good for shorter routes.'],
        ['id' => 'evening',   'label' => 'Evening',   'time' => '4:00 PM – 7:00 PM',  'note' => 'Softer light for photographs.'],
    ], 'slots', 'id');
}

function slot(string $id): ?array
{
    foreach (slots() as $s) {
        if ($s['id'] === $id) {
            return $s;
        }
    }
    return null;
}

/**
 * Countries offered in the visitor forms (short, common list + Other).
 *
 * These are VALUES, not labels: the English name is what the form posts and
 * what a future controller stores, in both locales. Print the label a visitor
 * reads with country_label() — a `<option value="Saudi Arabia">` whose
 * text is "المملكة العربية السعودية".
 */
function countries(): array
{
    return [
        'Saudi Arabia', 'United Arab Emirates', 'Egypt', 'Jordan', 'Kuwait', 'Qatar', 'Bahrain', 'Oman',
        'Turkey', 'Pakistan', 'India', 'Bangladesh', 'Indonesia', 'Malaysia', 'Morocco', 'Algeria',
        'Tunisia', 'Nigeria', 'South Africa', 'United Kingdom', 'France', 'Germany', 'Netherlands',
        'Spain', 'United States', 'Canada', 'Australia', 'Other',
    ];
}

/** The visible label for a country value, translated where a translation exists. */
function country_label(string $country): string
{
    return t('country.' . $country);
}

/* =========================================================================
 * Homepage hero slides
 *
 * Each slide carries its own pre-heading, heading and supporting paragraph,
 * which cross-fade in step with the background image. The CTAs, feature strip
 * and availability form stay fixed — only the message rotates.
 *
 * All three images share one 16:9 frame so they cross-fade without any shift.
 *
 * Slide 1 is the LCP candidate: it is rendered eagerly with fetchpriority=high,
 * and its heading is the page's single <h1> (see slider.php). The rest are lazy.
 * Replace each file with a real Madinah photograph at the same path and ratio
 * (WebP/AVIF preferred) and no markup needs to change.
 *
 * Keep the three headings and paragraphs roughly the same length — the panels
 * are stacked in one grid cell, so the hero is as tall as the longest one.
 * ====================================================================== */
function hero_slides(): array
{
    $slides = hero_slides_source();

    /* Keyed by position — a slide has no id of its own, and the three are a
       fixed set the homepage prints in order. */
    foreach ($slides as $i => $slide) {
        $slides[$i] = localize($slide, 'hero_slides', (string) $i);
    }

    return $slides;
}

function hero_slides_source(): array
{
    return [
        [
            'image'   => 'images/alam/hero/prophets-mosque-plaza.webp',
            'alt'     => 'The plaza and minarets of Al-Masjid an-Nabawi in Madinah',
            'eyebrow' => 'Discover Madinah with local guides',
            'heading' => 'Experience the stories, places and heritage of Madinah',
            'text'    => 'Alam Al-Munawara takes visitors to the historically and culturally important places around Madinah with guides who explain what happened where — and how each site connects to the next.',
        ],
        [
            'image'   => 'images/alam/hero/mount-uhud-panorama.webp',
            'alt'     => 'Mount Uhud rising behind the visitor area north of Madinah',
            'eyebrow' => 'Sirah sites explained on the ground',
            'heading' => 'Stand where the events actually took place',
            'text'    => 'At Uhud and Khandaq the terrain is the story. Our guides set out the ground first — where each side stood, why the high ground mattered — so the day makes sense before you leave.',
        ],
        [
            'image'   => 'images/alam/hero/al-ghamamah-mosque.webp',
            'alt'     => 'The Ottoman-era Al-Ghamamah Mosque in central Madinah',
            'eyebrow' => 'Heritage, museums and city history',
            'heading' => 'See how the landscape shaped the city',
            'text'    => 'Valleys, volcanic plains and date groves decided where Madinah grew and how it was defended. Once you can read the geography, the historical sites stop feeling scattered.',
        ],
    ];
}

/* =========================================================================
 * Attractions catalogue
 *
 * One entry per place a tour can stop at, keyed by slug. Tour `highlights`
 * entries point here with an `attraction` key rather than repeating the
 * image and the long copy in nine places — several attractions (Quba, the
 * Khandaq area, Dar Al Madinah Museum) appear on more than one tour.
 *
 * Per-attraction keys:
 *   image    photograph, sourced and licensed in IMAGE_SOURCES.md. `alt`
 *            describes what the photograph actually shows, which is not
 *            always the same wording as the highlight label.
 *   details  the longer copy shown in the attraction dialog. The short line
 *            on the card stays with each tour's own `highlights` entry, so
 *            per-tour phrasing is preserved.
 * ====================================================================== */
function attractions(): array
{
    /* Cached per locale. A request only ever renders one, but keying the cache
       means a future console script that walks both cannot get a stale set. */
    static $cache = [];

    $locale = current_locale();
    if (isset($cache[$locale])) {
        return $cache[$locale];
    }

    $attractions = attractions_source();

    foreach ($attractions as $slug => $entry) {
        $attractions[$slug] = localize($entry, 'attractions', (string) $slug);
    }

    $cache[$locale] = $attractions;

    return $attractions;
}

function attractions_source(): array
{
    static $attractions = null;
    if ($attractions !== null) {
        return $attractions;
    }

    $attractions = [
        'dar-al-madinah-museum' => [
            'title'   => 'Dar Al Madinah Museum',
            'image'   => 'images/alam/blog/dar-al-madinah-museum.webp',
            'alt'     => 'Dar Al Madinah Museum, Madinah',
            'details' => [
                'Dar Al Madinah is a privately run museum dedicated to the history of the city itself: how Madinah was settled, how its quarters and water channels developed, and how daily life was organised in and around them.',
                'The collection is built around scale models, maps, photographs and everyday objects rather than a single headline exhibit, which is why the tour starts here. Seeing the city laid out in miniature first makes the later stops legible — the distances between them, and the reason each one sits where it does.',
            ],
        ],
        'masjid-al-qiblatain' => [
            'title'   => 'Mosque of the Two Qiblas',
            'image'   => 'images/alam/tours/masjid-al-qiblatain.webp',
            'alt'     => 'Masjid al-Qiblatain, the Mosque of the Two Qiblas, Madinah',
            'details' => [
                'Masjid al-Qiblatain stands to the north-west of the city and takes its name — the mosque of the two qiblas — from its association with the change in the direction of prayer from Jerusalem to Makkah.',
                'The building visitors see today is a modern reconstruction; the older structure that preserved two prayer niches was replaced during a twentieth-century rebuild. Your guide explains what stood here historically and what the present building is, so the two are not confused.',
            ],
        ],
        'masjid-al-qiblatain-dusk' => [
            'title'   => 'Mosque of the Two Qiblas',
            'image'   => 'images/alam/blog/masjid-al-qiblatain-dusk.webp',
            'alt'     => 'Masjid al-Qiblatain at dusk, Madinah',
            'details' => [
                'Masjid al-Qiblatain is one of the most visited historical mosques in Madinah after the Prophet\'s Mosque and Quba, and it is the stop most guests recognise by name before they arrive.',
                'On this tour it is treated as a building with a long history rather than a single anecdote: what the site was, how it was rebuilt, and how it relates to the other mosques on the route. The late afternoon and evening slots are the quietest times to visit.',
            ],
        ],
        'uhud-area' => [
            'title'   => 'Uhud area',
            'image'   => 'images/alam/tours/mount-uhud.webp',
            'alt'     => 'Mount Uhud rising above the plain north of Madinah',
            'details' => [
                'Uhud is the mountain ridge on the northern edge of Madinah and the ground in front of it, where the Battle of Uhud took place in the third year after the Hijrah.',
                'The value of stopping here is the geography. With the ridge on one side and the city behind you, the positions, the distances and the sequence of the day become far easier to follow than they are on a page. Your guide walks through the events on site, at the pace the group wants.',
            ],
        ],
        'mount-uhud' => [
            'title'   => 'Mount Uhud',
            'image'   => 'images/alam/tours/mount-uhud.webp',
            'alt'     => 'Mount Uhud, Madinah',
            'details' => [
                'Mount Uhud is the largest mountain around Madinah, a long granite ridge running roughly east to west across the northern approach to the city.',
                'This stop is given proper time. The guide sets out the ground first — where the ridge runs, where the open plain lies, and how the city sat behind it — before turning to the events associated with the site. There is time to look, ask questions and take photographs from the viewing area.',
            ],
        ],
        'archers-hill' => [
            'title'   => 'The archers\' hill area',
            'image'   => 'images/alam/blog/jabal-ar-rumah-uhud.webp',
            'alt'     => 'Jabal ar-Rumah, the small hill beside Mount Uhud',
            'details' => [
                'Jabal ar-Rumah is the low hill facing Mount Uhud across the open ground. It is small enough to be easy to miss, and it is the point that explains most of what happened at Uhud.',
                'Standing at its foot, the reason the position mattered is immediately visible: it commands the gap the plain opens onto. Guides use the spot to explain how the day turned, keeping to what is well established and leaving out the embellishments that tend to attach to the site.',
            ],
        ],
        'uhud-visitor-area' => [
            'title'   => 'Visitor area',
            'image'   => 'images/alam/tours/sayyid-ash-shuhada-uhud.webp',
            'alt'     => 'Sayyid ash-Shuhada Mosque at Uhud, Madinah',
            'details' => [
                'The visitor area at the foot of Uhud has been substantially rebuilt in recent years, with the Sayyid ash-Shuhada mosque, shaded walkways, parking and facilities for the large numbers who come through each day.',
                'It is the practical base for the stop: where the group gathers, where there is shade and water, and where the walking is easiest. Your guide explains the etiquette expected here, which matters because this is a place of visit for many of the people around you.',
            ],
        ],
        'uhud-surrounding-landscape' => [
            'title'   => 'Surrounding landscape',
            'image'   => 'images/alam/hero/mount-uhud-panorama.webp',
            'alt'     => 'Panorama of Mount Uhud and the plain around Madinah',
            'details' => [
                'The ground around Uhud is as much a part of the stop as the mountain. The plain, the harrah beyond it and the line of the city to the south are all visible from the same position.',
                'Seeing them together is what makes the northern approach to Madinah make sense — why an army coming from the north arrives where it does, and why the city was defended from this side. It is also the best open view of the landscape on the tour.',
            ],
        ],
        'khandaq-area' => [
            'title'   => 'Khandaq area',
            'image'   => 'images/alam/tours/khandaq-salman-al-farisi.webp',
            'alt'     => 'Mosque of Salman al-Farisi at the Seven Mosques, Madinah',
            'details' => [
                'The Khandaq is the northern side of the city, associated with the trench dug to defend Madinah during the campaign of the fifth year after the Hijrah.',
                'The trench itself is long gone and the area is now built up, so this stop is about reading the ground: where the natural defences lay, which side was open, and why the northern approach was the one that had to be closed. The small mosques clustered here are covered in the same visit.',
            ],
        ],
        'khandaq-historic-mosques' => [
            'title'   => 'Historic mosques nearby',
            'image'   => 'images/alam/attractions/seven-mosques-umar.webp',
            'alt'     => 'Mosque of Umar ibn al-Khattab at the Seven Mosques, Madinah',
            'details' => [
                'The group of small mosques on the western side of the Khandaq area is known collectively as the Seven Mosques, although the number of buildings standing has changed over time and a large new mosque now sits alongside them.',
                'They are modest structures, and their interest is historical rather than architectural — where they sit in relation to the trench and to Jabal Sal\'. Your guide explains which traditions attached to these buildings are well founded and which are later attributions.',
            ],
        ],
        'jabal-sal' => [
            'title'   => 'Sal\' area',
            'image'   => 'images/alam/blog/seven-mosques-steps.webp',
            'alt'     => 'Steps at the Seven Mosques below Jabal Sal\', Madinah',
            'details' => [
                'Jabal Sal\' is the low rocky hill that sits behind the Khandaq area, immediately north-west of the old city.',
                'It is the reason the trench was dug where it was: the hill closed one flank, so only the open ground beside it needed defending. Looking up at it from the mosques below is the clearest way to see how the city\'s natural defences and its man-made ones fitted together.',
            ],
        ],
        'quba-area' => [
            'title'   => 'Quba area',
            'image'   => 'images/alam/tours/masjid-quba.webp',
            'alt'     => 'The historic Quba Mosque, Madinah',
            'details' => [
                'Quba lies a few kilometres south of the centre and is where the Prophet stopped on arriving from Makkah, before entering the city proper.',
                'The mosque here is the best known building in the area and has been enlarged repeatedly, most recently as part of a large development of the whole quarter. Your guide covers the settlement around it as well as the mosque, since the neighbourhood is the reason the stop is on the route.',
            ],
        ],
        'masjid-quba' => [
            'title'   => 'Quba area mosque',
            'image'   => 'images/alam/tours/masjid-quba.webp',
            'alt'     => 'The historic Quba Mosque, Madinah',
            'details' => [
                'Masjid Quba is the first mosque built in Madinah after the Hijrah, and the oldest foundation of the several on this tour.',
                'Almost nothing of the original structure is visible: the building has been rebuilt and expanded many times, and the current mosque is a modern one on the historic site. The guide explains the sequence of those rebuilds, which is the honest way to look at a place this old.',
            ],
        ],
        'early-settlement-areas' => [
            'title'   => 'Early settlement areas',
            'image'   => 'images/alam/blog/masjid-quba-courtyard.webp',
            'alt'     => 'Courtyard of the Quba Mosque, Madinah',
            'details' => [
                'The quarters around Quba are where the earliest community settled, and they were separate from the city centre by some distance when they were first occupied.',
                'Very little of the early fabric survives above ground, so this part of the walk is about layout rather than buildings: which areas were occupied, how far apart they were, and how the gap between Quba and the centre was gradually filled in as the city grew.',
            ],
        ],
        'hijrah-route' => [
            'title'   => 'The route into the city',
            'image'   => 'images/alam/tours/madinah-palm-groves.webp',
            'alt'     => 'Palm groves in the Madinah region',
            'details' => [
                'The stretch between Quba and the centre of Madinah is the route taken on entering the city, and it is the spine this walk follows.',
                'It ran through cultivated ground — the palm groves that supported the settlements on either side. Much of that is now built over, but enough remains around the city to show what the ground looked like, and the walk is paced so the distance itself registers rather than being driven past.',
            ],
        ],
        'street-level-history' => [
            'title'   => 'Street-level history',
            'image'   => 'images/alam/tours/hejaz-railway-station.webp',
            'alt'     => 'The 1908 Hejaz Railway station, Madinah',
            'details' => [
                'Alongside the well-known sites, the walk points out the ordinary layers of the city: Ottoman-era buildings, the old station, street names, and the boundaries of quarters that no longer exist administratively.',
                'The Hejaz Railway station of 1908 is the clearest survivor of that later period. It is the sort of detail that is easy to walk past, and it is included because it shows Madinah as a city with a continuous history rather than a set of separate historical episodes.',
            ],
        ],
        'prophets-mosque-central-area' => [
            'title'   => 'Central area orientation',
            'image'   => 'images/alam/hero/prophets-mosque-plaza.webp',
            'alt'     => 'The plaza around the Prophet\'s Mosque, Madinah',
            'details' => [
                'The tour begins with the central area as a whole: the mosque at its middle, the plazas around it, and the ring of hotels and streets that replaced the old quarters.',
                'The orientation is practical as well as historical. Knowing which side you are on, where the gates are and how the plazas connect makes the rest of your stay in Madinah considerably easier, particularly at prayer times when the crowds are heaviest.',
            ],
        ],
        'mosque-expansion-history' => [
            'title'   => 'Expansion history',
            'image'   => 'images/alam/attractions/prophets-mosque-courtyard.webp',
            'alt'     => 'Courtyard of the Prophet\'s Mosque, Madinah',
            'details' => [
                'The Prophet\'s Mosque began as a small structure and has been enlarged in stages ever since — under the early caliphs, the Umayyads, the Mamluks, the Ottomans, and on a far larger scale in the Saudi period.',
                'Each expansion absorbed streets and buildings that had stood around it, which is why the old quarters immediately next to the mosque no longer exist. Your guide traces those stages from outside, so the present building reads as a sequence rather than a single design.',
            ],
        ],
        'historic-quarters' => [
            'title'   => 'Historic quarters',
            'image'   => 'images/alam/tours/hejaz-railway-station.webp',
            'alt'     => 'The 1908 Hejaz Railway station, Madinah',
            'details' => [
                'The quarters that surrounded the Prophet\'s Mosque were dense, walled and lived-in until well into the twentieth century. Most were cleared during the successive expansions.',
                'What remains is fragmentary — a few Ottoman-period buildings, the old station, and the lines of some original streets preserved in the modern layout. The guide points out what survives and explains what stood in the places that are now open plaza.',
            ],
        ],
        'nearby-landmarks' => [
            'title'   => 'Nearby landmarks',
            'image'   => 'images/alam/hero/al-ghamamah-mosque.webp',
            'alt'     => 'The Ottoman-era Al-Ghamamah Mosque in central Madinah',
            'details' => [
                'Several small historical mosques stand within walking distance of the Prophet\'s Mosque, including Masjid al-Ghamamah just to the south-west and the mosques of Abu Bakr and Umar nearby.',
                'They are Ottoman-era buildings on older sites, easily missed among the modern development around them. The stop is short at each one, and the point is to show that the central area still holds standing historical fabric.',
            ],
        ],
        'other-historic-mosques' => [
            'title'   => 'Other historic sites',
            'image'   => 'images/alam/attractions/masjid-abu-bakr.webp',
            'alt'     => 'Mosque of Abu Bakr, Madinah, renovated by the Ottomans in 1789',
            'details' => [
                'Beyond the best-known mosques, the city holds a number of smaller historical buildings — among them the mosques of Abu Bakr and Umar in the central area, and Masjid al-Ghamamah beside them.',
                'Most are Ottoman restorations of much older foundations and are modest in scale. Which of them the tour includes depends on the time available, access on the day and the pace your group prefers.',
            ],
        ],
        'city-viewpoints' => [
            'title'   => 'City viewpoints',
            'image'   => 'images/alam/hero/prophets-mosque-plaza.webp',
            'alt'     => 'The Prophet\'s Mosque and the modern city around it, Madinah',
            'details' => [
                'At a few points on the route, the old city and the modern one can be seen in the same view — the mosque and its plazas at the centre, the ring of towers around them, and the older ground beyond.',
                'These stops are brief and exist to tie the tour together. Once the shape of the city is visible from a distance, the individual sites visited earlier in the day fall into their proper positions on the map.',
            ],
        ],
        'city-edge-viewpoints' => [
            'title'   => 'City edge viewpoints',
            'image'   => 'images/alam/hero/mount-uhud-panorama.webp',
            'alt'     => 'Panorama of Mount Uhud and the plain around Madinah',
            'details' => [
                'The northern edge of the city is the best place to see how Madinah sat within its defences: the open approach from the north, the hills that closed the other sides, and the cultivated ground in between.',
                'From here the Khandaq stop makes considerably more sense. The distances involved are short, and seeing them at a glance explains the scale of what was dug and why it was enough.',
            ],
        ],
        'harrah-volcanic-plains' => [
            'title'   => 'Volcanic plains',
            'image'   => 'images/alam/hero/mount-uhud-panorama.webp',
            'alt'     => 'The open plain and dark volcanic ground around Madinah',
            'details' => [
                'Madinah sits between two large lava fields, the harrahs, whose dark basalt ground stretches away on the eastern and western sides of the city.',
                'They are the single biggest reason the city developed as it did: they blocked movement on two sides, left the north as the open approach, and their weathered soil supports the cultivation the settlements depended on. The stop looks at that ground directly rather than describing it.',
            ],
        ],
        'madinah-date-groves' => [
            'title'   => 'Date groves',
            'image'   => 'images/alam/tours/madinah-palm-groves.webp',
            'alt'     => 'Palm groves in the Madinah region',
            'details' => [
                'The palm groves around Madinah are the oldest continuous land use in the area and the economic base the early settlements rested on.',
                'They also explain the city\'s shape: cultivation followed the water, settlement followed cultivation, and the quarters grew up between the groves rather than in a single block. The stop includes how the groves are irrigated and why particular areas were farmed and others were not.',
            ],
        ],
        'madinah-mountain-surroundings' => [
            'title'   => 'Mountain surroundings',
            'image'   => 'images/alam/tours/mount-uhud.webp',
            'alt'     => 'Mount Uhud, Madinah',
            'details' => [
                'Madinah lies in a basin with high ground on most sides — Uhud to the north, Sal\' on the north-west, and lower ridges and outcrops elsewhere around the plain.',
                'This part of the tour treats those as a system rather than as separate landmarks. Where the gaps are, which routes they open, and how they shaped the approaches to the city are all covered from viewpoints where more than one of them is visible at once.',
            ],
        ],
        'badr-route' => [
            'title'   => 'The route from Madinah',
            'image'   => 'images/alam/tours/mount-uhud.webp',
            'alt'     => 'Mount Uhud on the northern approach to Madinah',
            'details' => [
                'Badr lies roughly 150 kilometres south-west of Madinah, and the drive is a substantial part of the day rather than an interruption to it.',
                'The road crosses the volcanic ground and then descends towards the coastal plain, and the change in the landscape is the point: the distance and the ground are what the historical accounts assume and rarely state. Commentary is given along the way, with the pacing kept comfortable.',
            ],
        ],
        'badr-area' => [
            'title'   => 'Badr historical area',
            'image'   => 'images/alam/tours/badr-masjid-al-arish.webp',
            'alt'     => 'Masjid al-Arish at Badr, Saudi Arabia',
            'details' => [
                'Badr is a small town on the route between Madinah and the coast, and the site of the battle fought in the second year after the Hijrah.',
                'The ground is open, and the positions described in the sources can be followed on it. Masjid al-Arish stands at the place associated with the command post during the battle. Your guide explains the layout on site and keeps to what the accounts support.',
            ],
        ],
        'badr-landscape' => [
            'title'   => 'Surrounding landscape',
            'image'   => 'images/alam/hero/mount-uhud-panorama.webp',
            'alt'     => 'Open plain and high ground on the route out of Madinah',
            'details' => [
                'The country between Madinah and Badr changes character several times — volcanic ground, open plain, wadis and the hills near the coast.',
                'Seeing it in sequence is the clearest way to understand why the routes ran where they did and why the wells at Badr mattered to anyone crossing it. There are stops along the way where the landscape is worth looking at properly.',
            ],
        ],
        'badr-rest-stops' => [
            'title'   => 'Rest stops',
            'image'   => 'images/alam/tours/madinah-palm-groves.webp',
            'alt'     => 'Cultivated palm groves in the Madinah region',
            'details' => [
                'Because this is a full-day tour with a long drive at either end, breaks are built into the schedule rather than fitted in if there is time.',
                'Stops are made for refreshments, prayer and stretching, at points chosen for facilities and shade. Tell your guide at the start if your group needs them more frequently, and the day is planned around that.',
            ],
        ],
        'heritage-exhibitions' => [
            'title'   => 'Heritage exhibitions',
            'image'   => 'images/alam/tours/hejaz-railway-station.webp',
            'alt'     => 'The 1908 Hejaz Railway station, Madinah',
            'details' => [
                'Madinah\'s heritage collections are spread across several sites, including the restored Hejaz Railway station of 1908, which now houses exhibition space alongside the original platforms and workshops.',
                'The station is worth the visit for the building as much as the displays: it is one of the few large structures from the late Ottoman period still standing in the city, and it marks the point where Madinah was connected to Damascus by rail.',
            ],
        ],
        'architectural-models' => [
            'title'   => 'Architectural models',
            'image'   => 'images/alam/hero/al-ghamamah-mosque.webp',
            'alt'     => 'The Ottoman-era Al-Ghamamah Mosque in central Madinah',
            'details' => [
                'Scale models are the most useful thing in Madinah\'s museums, because so much of what they depict has been rebuilt or cleared.',
                'They show the mosque at successive stages of its expansion, the old walled city with its gates, and individual buildings such as Masjid al-Ghamamah, whose Ottoman form still stands nearby for comparison. Seeing the model and then the building is the point of the pairing.',
            ],
        ],
        'manuscripts-objects' => [
            'title'   => 'Manuscripts and objects',
            'image'   => 'images/alam/attractions/masjid-abu-bakr.webp',
            'alt'     => 'Mosque of Abu Bakr, Madinah, renovated by the Ottomans in 1789',
            'details' => [
                'The collections include manuscripts, documents, architectural fragments, tilework and household objects, much of it from the Ottoman period that shaped the buildings still standing in the central area.',
                'Displays change and not everything is on show at any one time, so your guide will confirm what is currently exhibited. Photography rules vary between the sites visited and are explained before you go in.',
            ],
        ],
    ];

    return $attractions;
}

/**
 * Resolve one tour's `highlights` into full attraction records for the
 * Places & highlights cards and the attraction dialog.
 *
 * The tour keeps its own `title` and `text` (the short line on the card), so
 * per-tour phrasing survives; the image and the long `details` come from the
 * catalogue. A highlight with no `attraction` key, or an unknown slug, still
 * renders — it falls back to the tour's own copy and the tour photograph.
 *
 * @return array<int, array{slug:string,title:string,image:string,alt:string,short:string,details:array<int,string>}>
 */
function tour_attractions(array $tour): array
{
    $catalogue = attractions();
    $out = [];

    foreach ($tour['highlights'] as $i => $h) {
        $key   = isset($h['attraction']) ? $h['attraction'] : '';
        $entry = isset($catalogue[$key]) ? $catalogue[$key] : [];

        $out[] = [
            'slug'    => $key !== '' ? $key : $tour['slug'] . '-highlight-' . ($i + 1),
            'title'   => $h['title'],
            'image'   => isset($entry['image']) ? $entry['image'] : $tour['image'],
            'alt'     => isset($entry['alt']) ? $entry['alt'] : $h['title'],
            'short'   => $h['text'],
            'details' => isset($entry['details']) ? $entry['details'] : [$h['text']],
        ];
    }

    return $out;
}

/**
 * Resolve one tour's `journey` into itinerary steps for the timeline.
 *
 * Same arrangement as tour_attractions(): the step keeps its own time,
 * title and text, and an optional `attraction` key pulls the thumbnail from
 * the catalogue. Pickup and return steps have no attraction, so they render
 * without an image — `image` is an empty string for those.
 *
 * @return array<int, array{time:string,title:string,text:string,image:string,alt:string}>
 */
function tour_journey(array $tour): array
{
    $catalogue = attractions();
    $out = [];

    foreach ($tour['journey'] as $j) {
        $key   = isset($j['attraction']) ? $j['attraction'] : '';
        $entry = isset($catalogue[$key]) ? $catalogue[$key] : [];

        $out[] = [
            'time'  => $j['time'],
            'title' => $j['title'],
            'text'  => $j['text'],
            'image' => isset($entry['image']) ? $entry['image'] : '',
            'alt'   => isset($entry['alt']) ? $entry['alt'] : '',
        ];
    }

    return $out;
}

/* =========================================================================
 * Tours
 * ====================================================================== */
function tours(): array
{
    static $cache = [];

    $locale = current_locale();
    if (isset($cache[$locale])) {
        return $cache[$locale];
    }

    $tours = localize_list(tours_source(), 'tours', 'slug');

    /* `category` is a LABEL, and the badge on a card shows it translated. The
       listing filter and the card's data-category attribute need something
       stable to compare on, so the English label is preserved as
       `category_key` before the display label is swapped in. */
    foreach ($tours as $i => $tour) {
        $key = $tour['category'];
        $tours[$i]['category_key'] = $key;
        $tours[$i]['category']     = (string) content('tour_categories', $key, 'label', $key);
    }

    $cache[$locale] = $tours;

    return $tours;
}

function tours_source(): array
{
    static $tours = null;
    if ($tours !== null) {
        return $tours;
    }

    $tours = [
        [
            'slug'      => 'essential-sirah-landscapes',
            'title'     => 'Essential Sirah Landscapes',
            'category'  => 'Signature',
            'featured'  => true,
            'image'     => 'images/alam/tours/sayyid-ash-shuhada-uhud.webp',
            'summary'   => 'A guided route through the places most closely tied to the Prophet\'s life in Madinah, explained in the order the events happened.',
            'duration'  => '4 hours',
            'duration_hours' => 4,
            'period'    => 'Year-round',
            'capacity'  => 15,
            'walking'   => 'Light to moderate',
            'pickup'    => 'Hotel pickup in central Madinah',
            'price'     => 450,
            'overview'  => [
                'Essential Sirah Landscapes is our signature introduction to Madinah. Rather than moving between landmarks as isolated stops, the route follows the sequence of events, so each place makes sense in relation to the one before it.',
                'Your guide sets the geography first — where the early community settled, how the city was laid out, and where the main events took place. From there the stops build on one another, and by the end of the tour the map of Madinah reads very differently than it did at the start.',
            ],
            'experience' => [
                'An opening orientation that explains how Madinah is arranged and why its geography mattered.',
                'Guided commentary at each stop in your chosen language, with time to ask questions.',
                'Context on how the sites relate to one another rather than a list of facts at each one.',
                'Unhurried pacing, with breaks and time for photographs where appropriate.',
                'Practical guidance on visiting etiquette at each location.',
            ],
            'highlights' => [
                ['attraction' => 'dar-al-madinah-museum', 'title' => 'Dar Al Madinah Museum', 'text' => 'A grounding stop that lays out the city\'s history, urban development and daily life through models, maps and exhibits.'],
                ['attraction' => 'masjid-al-qiblatain', 'title' => 'Mosque of the Two Qiblas', 'text' => 'One of Madinah\'s best-known historical mosques, associated with the change in the direction of prayer.'],
                ['attraction' => 'uhud-area', 'title' => 'Uhud area', 'text' => 'The mountain and surrounding ground associated with the Battle of Uhud, viewed with the events explained on site.'],
                ['attraction' => 'khandaq-area', 'title' => 'Khandaq area', 'text' => 'The northern side of the city associated with the trench and the mosques built in the area.'],
                ['attraction' => 'quba-area', 'title' => 'Quba area', 'text' => 'The neighbourhood connected with the first settlement of the community after the Hijrah.'],
                ['attraction' => 'city-viewpoints', 'title' => 'City viewpoints', 'text' => 'Points where the layout of old and modern Madinah can be seen together.'],
            ],
            'journey' => [
                ['time' => '0:00', 'title' => 'Pickup and orientation', 'text' => 'Your guide meets you at your hotel, confirms the route and gives a short orientation to the city\'s geography.'],
                ['attraction' => 'dar-al-madinah-museum', 'time' => '0:30', 'title' => 'Museum context stop', 'text' => 'A walk-through of the exhibits that set the historical background for everything that follows.'],
                ['attraction' => 'masjid-al-qiblatain', 'time' => '1:30', 'title' => 'Historical mosque visit', 'text' => 'A guided stop with commentary and time for questions and photographs.'],
                ['attraction' => 'uhud-area', 'time' => '2:15', 'title' => 'Uhud area', 'text' => 'Commentary on the ground and the events associated with it, with time to walk the viewing area.'],
                ['attraction' => 'khandaq-area', 'time' => '3:15', 'title' => 'Northern sites', 'text' => 'The Khandaq area and the mosques around it, explained in sequence.'],
                ['time' => '4:00', 'title' => 'Return', 'text' => 'Drop-off at your hotel, with suggestions for what to visit independently afterwards.'],
            ],
            'prepare' => [
                ['label' => 'Duration', 'value' => 'About 4 hours door to door'],
                ['label' => 'Group size', 'value' => 'Set by the vehicle you choose, up to 15 guests'],
                ['label' => 'Language', 'value' => 'Arabic or English'],
                ['label' => 'Pickup', 'value' => 'Hotels in central Madinah; other areas on request'],
                ['label' => 'Walking', 'value' => 'Light to moderate — mostly short walks between stops'],
                ['label' => 'Weather', 'value' => 'Summer afternoons are hot; the Fajer and evening slots are cooler'],
                ['label' => 'Bring', 'value' => 'Comfortable shoes, water, sun protection and modest clothing'],
                ['label' => 'Accessibility', 'value' => 'Tell us in advance and we will plan a route that suits your group'],
            ],
            'faqs' => [
                ['q' => 'Does this tour enter the Prophet\'s Mosque?', 'a' => 'The route focuses on the historical sites around the city. Your guide will explain the area and advise on visiting times, but the visit itself is made independently.'],
                ['q' => 'Can the route be adjusted?', 'a' => 'Yes. Tell your guide at the start of the tour if you would like more time at a particular stop, and the pacing can be adjusted within the tour duration.'],
                ['q' => 'Is the tour suitable for children?', 'a' => 'Yes. Guides adjust the depth of the commentary when younger children are in the group. Choose a vehicle option with enough seats for everyone.'],
                ['q' => 'What if we book the Fajer slot?', 'a' => 'The Fajer slot starts early and is the coolest and quietest time of day. Pickup times are confirmed with you after booking.'],
            ],
        ],
        [
            'slug'      => 'prophets-mosque-surroundings',
            'title'     => 'Prophet\'s Mosque & Surroundings',
            'category'  => 'City & Heritage',
            'featured'  => true,
            'image'     => 'images/alam/tours/prophets-mosque.webp',
            'summary'   => 'A walking orientation to the area around the Prophet\'s Mosque, its expansions and the historic quarters that once surrounded it.',
            'duration'  => '2.5 hours',
            'duration_hours' => 2.5,
            'period'    => 'Year-round',
            'capacity'  => 15,
            'walking'   => 'Moderate — mostly walking',
            'pickup'    => 'Meeting point near the central area',
            'price'     => 250,
            'overview'  => [
                'This is a walking tour of the area surrounding the Prophet\'s Mosque. Your guide explains how the mosque and the district around it developed over time, and what stood where before the modern expansions.',
                'It works well as a first activity after arriving in Madinah, because it gives you a mental map of the central area that makes the rest of your stay easier.',
            ],
            'experience' => [
                'How the central area changed across successive expansions.',
                'The historic quarters and gates that once surrounded the mosque.',
                'Orientation to the surrounding streets, entrances and directions.',
                'Practical advice on prayer times, crowd patterns and quieter hours.',
            ],
            'highlights' => [
                ['attraction' => 'prophets-mosque-central-area', 'title' => 'Central area orientation', 'text' => 'Where the main gates, courtyards and approaches are, and how to navigate them.'],
                ['attraction' => 'historic-quarters', 'title' => 'Historic quarters', 'text' => 'Commentary on the neighbourhoods that once surrounded the mosque.'],
                ['attraction' => 'mosque-expansion-history', 'title' => 'Expansion history', 'text' => 'How the building and the plaza around it grew over the centuries.'],
                ['attraction' => 'nearby-landmarks', 'title' => 'Nearby landmarks', 'text' => 'Points of interest within walking distance of the central area.'],
            ],
            'journey' => [
                ['time' => '0:00', 'title' => 'Meet your guide', 'text' => 'Introductions and a short overview of the route.'],
                ['attraction' => 'prophets-mosque-central-area', 'time' => '0:20', 'title' => 'Orientation walk', 'text' => 'The approaches and layout of the central area.'],
                ['attraction' => 'historic-quarters', 'time' => '1:10', 'title' => 'Historic quarters', 'text' => 'Commentary on what stood in the surrounding district.'],
                ['time' => '2:00', 'title' => 'Questions and close', 'text' => 'Time for questions and recommendations for the rest of your stay.'],
            ],
            'prepare' => [
                ['label' => 'Duration', 'value' => 'About 2.5 hours'],
                ['label' => 'Group size', 'value' => 'Set by the vehicle you choose'],
                ['label' => 'Language', 'value' => 'Arabic or English'],
                ['label' => 'Pickup', 'value' => 'Central hotels, or meet your guide at an agreed point'],
                ['label' => 'Walking', 'value' => 'Moderate — this tour is mostly on foot'],
                ['label' => 'Weather', 'value' => 'Choose Fajer or evening in summer'],
            ],
            'faqs' => [
                ['q' => 'Is this tour mostly walking?', 'a' => 'Yes. It is designed as a walking orientation, with short distances and regular stops.'],
                ['q' => 'Can we combine it with another tour?', 'a' => 'Yes — many visitors take this on their first day and a longer historical tour later. Contact us and we will help you plan the order.'],
            ],
        ],
        [
            'slug'      => 'uhud-mountain-battle',
            'title'     => 'Uhud Mountain & the Battle of Uhud',
            'category'  => 'Sirah Sites',
            'featured'  => true,
            'image'     => 'images/alam/tours/mount-uhud.webp',
            'summary'   => 'A focused visit to the Uhud area with the events of the battle explained on the ground where they took place.',
            'duration'  => '3 hours',
            'duration_hours' => 3,
            'period'    => 'Year-round',
            'capacity'  => 15,
            'walking'   => 'Moderate — uneven ground in places',
            'pickup'    => 'Hotel pickup in central Madinah',
            'price'     => 300,
            'overview'  => [
                'Uhud is one of the most visited historical locations in Madinah, and one of the easiest to misunderstand without context. This tour slows the visit down and explains the terrain: where the two sides were positioned, why the high ground mattered, and how the day unfolded.',
                'Standing in the area while the sequence is explained makes the geography click in a way that reading about it rarely does.',
            ],
            'experience' => [
                'A clear explanation of the terrain and the positions of both sides.',
                'The sequence of the day explained step by step, on site.',
                'Time at the viewing areas for reflection and photographs.',
                'Context on how the events connect to the wider Sirah.',
            ],
            'highlights' => [
                ['attraction' => 'mount-uhud', 'title' => 'Mount Uhud', 'text' => 'The ridge itself and the ground in front of it, viewed from the main visiting area.'],
                ['attraction' => 'archers-hill', 'title' => 'The archers\' hill area', 'text' => 'Commentary on the high ground and the role it played in the battle.'],
                ['attraction' => 'uhud-visitor-area', 'title' => 'Visitor area', 'text' => 'Time at the designated visiting area with your guide.'],
                ['attraction' => 'uhud-surrounding-landscape', 'title' => 'Surrounding landscape', 'text' => 'How the northern approach to the city looked at the time.'],
            ],
            'journey' => [
                ['time' => '0:00', 'title' => 'Pickup', 'text' => 'Your guide collects you and outlines the route.'],
                ['attraction' => 'mount-uhud', 'time' => '0:30', 'title' => 'Arrival and orientation', 'text' => 'The terrain explained before moving to the main visiting area.'],
                ['attraction' => 'archers-hill', 'time' => '1:15', 'title' => 'The sequence of the day', 'text' => 'Guided commentary in stages, following the events in order.'],
                ['attraction' => 'uhud-visitor-area', 'time' => '2:20', 'title' => 'Reflection and questions', 'text' => 'Free time at the viewing area and time for questions.'],
                ['time' => '3:00', 'title' => 'Return', 'text' => 'Drop-off at your hotel.'],
            ],
            'prepare' => [
                ['label' => 'Duration', 'value' => 'About 3 hours'],
                ['label' => 'Group size', 'value' => 'Set by the vehicle you choose'],
                ['label' => 'Language', 'value' => 'Arabic or English'],
                ['label' => 'Walking', 'value' => 'Moderate, with some uneven ground'],
                ['label' => 'Weather', 'value' => 'Open and exposed — avoid midday in summer'],
                ['label' => 'Bring', 'value' => 'Water, sun protection and comfortable shoes'],
            ],
            'faqs' => [
                ['q' => 'Do we climb the mountain?', 'a' => 'No. The tour stays in the designated visiting areas at the base and explains the terrain from there.'],
                ['q' => 'Is it suitable for older visitors?', 'a' => 'Generally yes, though there is some walking on uneven ground. Let us know in advance and the guide will keep to the easier paths.'],
            ],
        ],
        [
            'slug'      => 'khandaq-trench',
            'title'     => 'Khandaq & the Northern Sites',
            'category'  => 'Sirah Sites',
            'featured'  => true,
            'image'     => 'images/alam/tours/khandaq-salman-al-farisi.webp',
            'summary'   => 'The northern side of Madinah, where the trench was dug, explained alongside the historic mosques in the surrounding area.',
            'duration'  => '3 hours',
            'duration_hours' => 3,
            'period'    => 'Year-round',
            'capacity'  => 15,
            'walking'   => 'Light',
            'pickup'    => 'Hotel pickup in central Madinah',
            'price'     => 300,
            'overview'  => [
                'The Battle of the Trench is defined by geography: the city was protected on most sides, and the open northern approach had to be dealt with. This tour visits that side of Madinah and explains why the trench was dug where it was.',
                'The route also covers the historic mosques in the surrounding area and how they came to be associated with the events.',
            ],
            'experience' => [
                'The northern approach to the city explained on the ground.',
                'Why the defensive line ran where it did.',
                'The historic mosques in the area and their background.',
                'Time for questions throughout.',
            ],
            'highlights' => [
                ['attraction' => 'khandaq-area', 'title' => 'The Khandaq area', 'text' => 'The northern side of the city associated with the trench.'],
                ['attraction' => 'khandaq-historic-mosques', 'title' => 'Historic mosques nearby', 'text' => 'The small mosques in the surrounding area, with background on each.'],
                ['attraction' => 'jabal-sal', 'title' => 'Sal\' area', 'text' => 'The rise on the northern side and its role in the defence of the city.'],
                ['attraction' => 'city-edge-viewpoints', 'title' => 'City edge viewpoints', 'text' => 'Where the old boundary of the city ran.'],
            ],
            'journey' => [
                ['time' => '0:00', 'title' => 'Pickup', 'text' => 'Meet your guide and set out.'],
                ['attraction' => 'khandaq-area', 'time' => '0:25', 'title' => 'Northern approach', 'text' => 'Orientation to the terrain and the defensive problem it created.'],
                ['attraction' => 'khandaq-historic-mosques', 'time' => '1:10', 'title' => 'Mosques in the area', 'text' => 'Guided stops with commentary at each.'],
                ['attraction' => 'city-edge-viewpoints', 'time' => '2:20', 'title' => 'Viewpoint', 'text' => 'A last stop looking back over the area.'],
                ['time' => '3:00', 'title' => 'Return', 'text' => 'Drop-off at your hotel.'],
            ],
            'prepare' => [
                ['label' => 'Duration', 'value' => 'About 3 hours'],
                ['label' => 'Group size', 'value' => 'Set by the vehicle you choose'],
                ['label' => 'Language', 'value' => 'Arabic or English'],
                ['label' => 'Walking', 'value' => 'Light — short walks between stops'],
                ['label' => 'Weather', 'value' => 'Comfortable in the Fajer, morning and evening slots'],
            ],
            'faqs' => [
                ['q' => 'How much walking is involved?', 'a' => 'Little. Most stops are a short walk from where the vehicle parks.'],
            ],
        ],
        [
            'slug'      => 'badr-historical-tour',
            'title'     => 'Badr Historical Day Tour',
            'category'  => 'Day Trip',
            'featured'  => false,
            'image'     => 'images/alam/tours/badr-masjid-al-arish.webp',
            'summary'   => 'A full-day trip from Madinah to the Badr area, with the historical background explained along the route.',
            'duration'  => '9 hours',
            'duration_hours' => 9,
            'period'    => 'October – April recommended',
            'capacity'  => 15,
            'walking'   => 'Light',
            'pickup'    => 'Early hotel pickup',
            'price'     => 950,
            'overview'  => [
                'Badr sits some distance from Madinah, so this is a full-day trip. The drive is part of the experience: your guide uses the journey to set out the background, so the site itself is not the first time you hear the story.',
                'The day includes rest stops and time at the site, returning to Madinah in the late afternoon or evening.',
            ],
            'experience' => [
                'Background explained during the drive, not rushed on arrival.',
                'Time at the historical area with guided commentary.',
                'Planned rest and refreshment stops along the way.',
                'A calmer, longer format for visitors who want depth.',
            ],
            'highlights' => [
                ['attraction' => 'badr-route', 'title' => 'The route from Madinah', 'text' => 'The road out of the city, with commentary on the terrain along the way.'],
                ['attraction' => 'badr-area', 'title' => 'Badr historical area', 'text' => 'Time at the site with the events explained on the ground.'],
                ['attraction' => 'badr-landscape', 'title' => 'Surrounding landscape', 'text' => 'The valley and the geography that shaped the day.'],
                ['attraction' => 'badr-rest-stops', 'title' => 'Rest stops', 'text' => 'Planned breaks in both directions.'],
            ],
            'journey' => [
                ['time' => '0:00', 'title' => 'Early pickup', 'text' => 'Depart Madinah early to make the most of the day.'],
                ['attraction' => 'badr-route', 'time' => '1:00', 'title' => 'Background en route', 'text' => 'Your guide sets out the context during the drive.'],
                ['attraction' => 'badr-area', 'time' => '3:00', 'title' => 'Arrival at Badr', 'text' => 'Orientation and guided time at the historical area.'],
                ['attraction' => 'badr-rest-stops', 'time' => '5:30', 'title' => 'Break', 'text' => 'Rest and refreshment before setting off back.'],
                ['time' => '9:00', 'title' => 'Return to Madinah', 'text' => 'Drop-off at your hotel.'],
            ],
            'prepare' => [
                ['label' => 'Duration', 'value' => 'A full day, about 9 hours'],
                ['label' => 'Group size', 'value' => 'Set by the vehicle you choose'],
                ['label' => 'Language', 'value' => 'Arabic or English'],
                ['label' => 'Pickup', 'value' => 'Early morning from your hotel'],
                ['label' => 'Walking', 'value' => 'Light, but a long day overall'],
                ['label' => 'Weather', 'value' => 'Best between October and April'],
                ['label' => 'Bring', 'value' => 'Plenty of water, snacks and sun protection'],
            ],
            'faqs' => [
                ['q' => 'Are meals included?', 'a' => 'Meals are not included. The route includes stops where food and refreshments can be bought.'],
                ['q' => 'Is this suitable for young children?', 'a' => 'It is a long day in a vehicle. Families with very young children often prefer one of the shorter Madinah tours.'],
            ],
        ],
        [
            'slug'      => 'hijrah-walk',
            'title'     => 'The Hijrah Walk',
            'category'  => 'Walking',
            'featured'  => false,
            'image'     => 'images/alam/tours/masjid-quba.webp',
            'summary'   => 'A walking route through the areas connected with the arrival of the Prophet in Madinah and the first days of the community.',
            'duration'  => '3 hours',
            'duration_hours' => 3,
            'period'    => 'Year-round',
            'capacity'  => 7,
            'walking'   => 'Moderate to high',
            'pickup'    => 'Hotel pickup, walking route thereafter',
            'price'     => 320,
            'overview'  => [
                'This tour follows the arrival into Madinah on foot, taking in the areas connected with the first days of the community and how the city received them.',
                'It is a slower, more reflective format, and it suits visitors who prefer walking to driving between stops.',
            ],
            'experience' => [
                'A walking route through connected areas rather than isolated stops.',
                'The early settlement of the community explained in sequence.',
                'A slower pace with time to stop and talk.',
                'Small groups only, so the walk stays manageable.',
            ],
            'highlights' => [
                ['attraction' => 'quba-area', 'title' => 'Quba area', 'text' => 'The neighbourhood connected with the first days after the arrival.'],
                ['attraction' => 'hijrah-route', 'title' => 'The route into the city', 'text' => 'The direction of approach and what stood along the way.'],
                ['attraction' => 'early-settlement-areas', 'title' => 'Early settlement areas', 'text' => 'Where the first community established itself.'],
                ['attraction' => 'street-level-history', 'title' => 'Street-level history', 'text' => 'How the modern city sits on top of the older one.'],
            ],
            'journey' => [
                ['time' => '0:00', 'title' => 'Pickup and briefing', 'text' => 'Meet your guide and go through the walking route.'],
                ['attraction' => 'quba-area', 'time' => '0:30', 'title' => 'Quba area', 'text' => 'The first section of the walk with commentary.'],
                ['attraction' => 'hijrah-route', 'time' => '1:30', 'title' => 'Along the route', 'text' => 'Continuing on foot with stops along the way.'],
                ['attraction' => 'early-settlement-areas', 'time' => '2:30', 'title' => 'Arrival area', 'text' => 'The final section and a summary of the sequence.'],
                ['time' => '3:00', 'title' => 'Return', 'text' => 'Drop-off or finish at an agreed point.'],
            ],
            'prepare' => [
                ['label' => 'Duration', 'value' => 'About 3 hours'],
                ['label' => 'Group size', 'value' => 'Small groups, up to 7 guests'],
                ['label' => 'Language', 'value' => 'Arabic or English'],
                ['label' => 'Walking', 'value' => 'This is a walking tour — expect to be on your feet'],
                ['label' => 'Weather', 'value' => 'Fajer or evening slots are strongly recommended in summer'],
                ['label' => 'Bring', 'value' => 'Comfortable walking shoes and water'],
            ],
            'faqs' => [
                ['q' => 'How far do we walk?', 'a' => 'The route is broken into sections with stops throughout, but it is a walking tour and comfortable shoes matter.'],
                ['q' => 'Can we do part of it by car?', 'a' => 'Yes. Tell your guide at the start and sections can be driven instead.'],
            ],
        ],
        [
            'slug'      => 'historical-mosques',
            'title'     => 'Historical Mosques of Madinah',
            'category'  => 'Sirah Sites',
            'featured'  => true,
            'image'     => 'images/alam/tours/masjid-al-qiblatain.webp',
            'summary'   => 'A route through the historical mosques of Madinah, with the background and significance of each explained.',
            'duration'  => '4 hours',
            'duration_hours' => 4,
            'period'    => 'Year-round',
            'capacity'  => 15,
            'walking'   => 'Light to moderate',
            'pickup'    => 'Hotel pickup in central Madinah',
            'price'     => 420,
            'overview'  => [
                'Madinah has a number of historical mosques spread across the city, and visiting them without context can feel repetitive. This tour arranges them into a route that makes the connections between them clear.',
                'Your guide explains the background of each, what is documented about it and how it fits into the wider picture of the city.',
            ],
            'experience' => [
                'A planned route rather than a scattered set of stops.',
                'Background on each mosque and why it is associated with the events it is.',
                'Guidance on visiting etiquette at each location.',
                'Time for questions and photographs.',
            ],
            'highlights' => [
                ['attraction' => 'masjid-al-qiblatain-dusk', 'title' => 'Mosque of the Two Qiblas', 'text' => 'Associated with the change in the direction of prayer.'],
                ['attraction' => 'masjid-quba', 'title' => 'Quba area mosque', 'text' => 'Connected with the first days of the community in Madinah.'],
                ['attraction' => 'khandaq-historic-mosques', 'title' => 'Mosques near the Khandaq area', 'text' => 'The cluster of small historic mosques on the northern side.'],
                ['attraction' => 'other-historic-mosques', 'title' => 'Other historic sites', 'text' => 'Further locations depending on the time available and the route chosen.'],
            ],
            'journey' => [
                ['time' => '0:00', 'title' => 'Pickup', 'text' => 'Meet your guide and review the route.'],
                ['attraction' => 'masjid-quba', 'time' => '0:30', 'title' => 'First stops', 'text' => 'The southern part of the route with commentary.'],
                ['attraction' => 'masjid-al-qiblatain-dusk', 'time' => '1:45', 'title' => 'Central stops', 'text' => 'Continuing through the historical mosques.'],
                ['attraction' => 'khandaq-historic-mosques', 'time' => '3:00', 'title' => 'Northern stops', 'text' => 'The mosques in the Khandaq area.'],
                ['time' => '4:00', 'title' => 'Return', 'text' => 'Drop-off at your hotel.'],
            ],
            'prepare' => [
                ['label' => 'Duration', 'value' => 'About 4 hours'],
                ['label' => 'Group size', 'value' => 'Set by the vehicle you choose'],
                ['label' => 'Language', 'value' => 'Arabic or English'],
                ['label' => 'Walking', 'value' => 'Light to moderate'],
                ['label' => 'Dress', 'value' => 'Modest clothing is required at every stop'],
            ],
            'faqs' => [
                ['q' => 'Can we pray at the stops?', 'a' => 'Yes, where prayer times fall during the tour your guide will plan for it.'],
            ],
        ],
        [
            'slug'      => 'madinah-geographical-tour',
            'title'     => 'Madinah Geographical Tour',
            'category'  => 'City & Heritage',
            'featured'  => false,
            'image'     => 'images/alam/tours/madinah-palm-groves.webp',
            'summary'   => 'How Madinah is shaped — its valleys, volcanic plains, mountains and agricultural areas — and why that geography mattered historically.',
            'duration'  => '4 hours',
            'duration_hours' => 4,
            'period'    => 'Year-round',
            'capacity'  => 15,
            'walking'   => 'Light',
            'pickup'    => 'Hotel pickup in central Madinah',
            'price'     => 400,
            'overview'  => [
                'Most visitors see Madinah as a set of destinations. This tour is about the landscape that connects them: the valleys, the volcanic plains that border the city, the mountains that frame it and the agricultural areas that sustained it.',
                'Once the geography is clear, the historical events that happened here make far more sense.',
            ],
            'experience' => [
                'The natural boundaries of the city and how they shaped its history.',
                'The valleys and plains around Madinah, viewed from accessible points.',
                'The agricultural areas and date groves that supported the city.',
                'How the modern city has grown across the landscape.',
            ],
            'highlights' => [
                ['attraction' => 'city-viewpoints', 'title' => 'City viewpoints', 'text' => 'Points where the shape of the valley and the surrounding mountains can be seen.'],
                ['attraction' => 'harrah-volcanic-plains', 'title' => 'Volcanic plains', 'text' => 'The lava fields that border the city on more than one side.'],
                ['attraction' => 'madinah-date-groves', 'title' => 'Date groves', 'text' => 'The agricultural belt that has sustained Madinah for centuries.'],
                ['attraction' => 'madinah-mountain-surroundings', 'title' => 'Mountain surroundings', 'text' => 'The ranges that frame the city and the passes between them.'],
            ],
            'journey' => [
                ['time' => '0:00', 'title' => 'Pickup', 'text' => 'Meet your guide and set out.'],
                ['attraction' => 'city-viewpoints', 'time' => '0:30', 'title' => 'Valley orientation', 'text' => 'How the city sits within the valley.'],
                ['attraction' => 'harrah-volcanic-plains', 'time' => '1:30', 'title' => 'Plains and mountains', 'text' => 'The natural boundaries of Madinah.'],
                ['attraction' => 'madinah-date-groves', 'time' => '2:45', 'title' => 'Agricultural areas', 'text' => 'The date groves and farming belt.'],
                ['time' => '4:00', 'title' => 'Return', 'text' => 'Drop-off at your hotel.'],
            ],
            'prepare' => [
                ['label' => 'Duration', 'value' => 'About 4 hours'],
                ['label' => 'Group size', 'value' => 'Set by the vehicle you choose'],
                ['label' => 'Language', 'value' => 'Arabic or English'],
                ['label' => 'Walking', 'value' => 'Light — mostly driving with short stops'],
                ['label' => 'Best for', 'value' => 'Repeat visitors and anyone interested in landscape and history together'],
            ],
            'faqs' => [
                ['q' => 'Is this tour mostly driving?', 'a' => 'Yes. It covers a wide area, with short stops at viewpoints along the way.'],
            ],
        ],
        [
            'slug'      => 'museums-heritage',
            'title'     => 'Museums & Heritage Tour',
            'category'  => 'Museums & Culture',
            'featured'  => true,
            'image'     => 'images/alam/tours/hejaz-railway-station.webp',
            'summary'   => 'Madinah\'s museums and heritage exhibitions, with a guide to help you get more out of the collections.',
            'duration'  => '3.5 hours',
            'duration_hours' => 3.5,
            'period'    => 'Year-round — check museum opening days',
            'capacity'  => 15,
            'walking'   => 'Moderate — indoor walking',
            'pickup'    => 'Hotel pickup in central Madinah',
            'price'     => 380,
            'overview'  => [
                'Madinah\'s museums hold detailed models, maps, manuscripts and everyday objects that explain the city far better than a photograph can. Visiting them with a guide turns a walk past display cases into something you remember.',
                'This tour is a good choice on a hot day, and it pairs well with a historical site tour earlier or later in your stay.',
            ],
            'experience' => [
                'Guided commentary through the main exhibitions.',
                'Context linking the exhibits to the sites around the city.',
                'A comfortable, mostly indoor option during hot weather.',
                'Time to look at the collections at your own pace.',
            ],
            'highlights' => [
                ['attraction' => 'dar-al-madinah-museum', 'title' => 'Dar Al Madinah Museum', 'text' => 'Models, maps and exhibits on the city\'s history, architecture and daily life.'],
                ['attraction' => 'heritage-exhibitions', 'title' => 'Heritage exhibitions', 'text' => 'Displays covering crafts, trade and the everyday life of the city.'],
                ['attraction' => 'architectural-models', 'title' => 'Architectural models', 'text' => 'Scale models showing how the city and its central area changed over time.'],
                ['attraction' => 'manuscripts-objects', 'title' => 'Manuscripts and objects', 'text' => 'Collections that document the written and material history of Madinah.'],
            ],
            'journey' => [
                ['time' => '0:00', 'title' => 'Pickup', 'text' => 'Meet your guide and travel to the first museum.'],
                ['attraction' => 'dar-al-madinah-museum', 'time' => '0:30', 'title' => 'Main exhibition', 'text' => 'A guided walk-through of the collections.'],
                ['attraction' => 'heritage-exhibitions', 'time' => '1:45', 'title' => 'Second venue', 'text' => 'Heritage exhibitions with commentary.'],
                ['time' => '3:00', 'title' => 'Free time', 'text' => 'Time to revisit anything that interested you.'],
                ['time' => '3:30', 'title' => 'Return', 'text' => 'Drop-off at your hotel.'],
            ],
            'prepare' => [
                ['label' => 'Duration', 'value' => 'About 3.5 hours'],
                ['label' => 'Group size', 'value' => 'Set by the vehicle you choose'],
                ['label' => 'Language', 'value' => 'Arabic or English'],
                ['label' => 'Tickets', 'value' => 'Museum entry is arranged with you before the visit'],
                ['label' => 'Walking', 'value' => 'Moderate indoor walking, with places to sit'],
                ['label' => 'Note', 'value' => 'Opening days vary — we confirm the schedule when you book'],
            ],
            'faqs' => [
                ['q' => 'Are entry tickets included?', 'a' => 'Entry is arranged with you before the visit, and we confirm the cost at the time of booking.'],
                ['q' => 'Is photography allowed?', 'a' => 'It varies by venue. Your guide will tell you where photography is permitted.'],
            ],
        ],
    ];

    return $tours;
}

function tour(string $slug): ?array
{
    foreach (tours() as $t) {
        if ($t['slug'] === $slug) {
            return $t;
        }
    }
    return null;
}

/* =========================================================================
 * Tour gallery photographs
 *
 * Every entry is a real photograph of the place it names — see IMAGE_SOURCES.md
 * for the licence and source page of each file. Tours that visit a location
 * share the same photograph of it; tours with no verified photography of a stop
 * fall back to the general Madinah set rather than showing a stand-in location.
 * ====================================================================== */
function photos(): array
{
    return [
        'prophets-mosque'  => ['images/alam/tours/prophets-mosque.webp', 'The courtyard and green dome of Al-Masjid an-Nabawi in Madinah'],
        'nabawi-courtyard' => ['images/alam/attractions/prophets-mosque-courtyard.webp', 'Shade canopies over the courtyard of Al-Masjid an-Nabawi'],
        'quba'             => ['images/alam/tours/masjid-quba.webp', 'Masjid Quba in Madinah'],
        'quba-courtyard'   => ['images/alam/blog/masjid-quba-courtyard.webp', 'The courtyard and minarets of Masjid Quba'],
        'qiblatain'        => ['images/alam/tours/masjid-al-qiblatain.webp', 'Masjid Al-Qiblatain in Madinah'],
        'qiblatain-dusk'   => ['images/alam/blog/masjid-al-qiblatain-dusk.webp', 'Masjid Al-Qiblatain at dusk'],
        'uhud'             => ['images/alam/tours/mount-uhud.webp', 'Mount Uhud and the visitor area on its southern side'],
        'uhud-panorama'    => ['images/alam/hero/mount-uhud-panorama-1280.webp', 'The Mount Uhud ridge seen across the open ground below it'],
        'jabal-ar-rumah'   => ['images/alam/blog/jabal-ar-rumah-uhud.webp', "Jabal ar-Rumah, the archers' hill beside Mount Uhud"],
        'sayyid-shuhada'   => ['images/alam/tours/sayyid-ash-shuhada-uhud.webp', 'Sayyid Ash-Shuhada Mosque below the Uhud mountains'],
        'khandaq-salman'   => ['images/alam/tours/khandaq-salman-al-farisi.webp', 'The Mosque of Salman al-Farisi at the Seven Mosques in Madinah'],
        'khandaq-umar'     => ['images/alam/attractions/seven-mosques-umar.webp', 'The Mosque of Umar ibn al-Khattab at the Seven Mosques'],
        'khandaq-steps'    => ['images/alam/blog/seven-mosques-steps.webp', 'Steps up the rocky ground at the Seven Mosques in the Khandaq area'],
        'ghamamah'         => ['images/alam/hero/al-ghamamah-mosque-1280.webp', 'The Ottoman-era Al-Ghamamah Mosque in central Madinah'],
        'abu-bakr'         => ['images/alam/attractions/masjid-abu-bakr.webp', 'The Mosque of Abu Bakr in central Madinah'],
        'museum'           => ['images/alam/blog/dar-al-madinah-museum.webp', 'Inside the Dar Al Madinah Museum in Madinah'],
        'hejaz-railway'    => ['images/alam/tours/hejaz-railway-station.webp', 'The Ottoman Hejaz Railway station building in Madinah'],
        'palm-groves'      => ['images/alam/tours/madinah-palm-groves.webp', 'Date palm farms in the Madinah region'],
        'badr'             => ['images/alam/tours/badr-masjid-al-arish.webp', 'Masjid al-Arish at Badr, south-west of Madinah'],
    ];
}

/**
 * Six photographs for a tour detail page, in the order the tour visits them.
 * Returns a list of ['src' => ..., 'alt' => ...].
 */
function tour_gallery(string $slug): array
{
    $sets = [
        'essential-sirah-landscapes' => ['museum', 'qiblatain', 'uhud', 'khandaq-salman', 'quba', 'ghamamah'],
        'prophets-mosque-surroundings' => ['prophets-mosque', 'nabawi-courtyard', 'ghamamah', 'abu-bakr', 'qiblatain-dusk', 'museum'],
        'uhud-mountain-battle'       => ['uhud', 'uhud-panorama', 'jabal-ar-rumah', 'sayyid-shuhada', 'khandaq-umar', 'prophets-mosque'],
        'khandaq-trench'             => ['khandaq-salman', 'khandaq-umar', 'khandaq-steps', 'uhud-panorama', 'ghamamah', 'prophets-mosque'],
        'badr-historical-tour'       => ['badr', 'palm-groves', 'uhud-panorama', 'prophets-mosque', 'museum', 'quba'],
        'hijrah-walk'                => ['quba', 'quba-courtyard', 'qiblatain', 'ghamamah', 'prophets-mosque', 'palm-groves'],
        'historical-mosques'         => ['qiblatain', 'qiblatain-dusk', 'ghamamah', 'abu-bakr', 'khandaq-salman', 'quba'],
        'madinah-geographical-tour'  => ['palm-groves', 'uhud-panorama', 'khandaq-steps', 'quba', 'hejaz-railway', 'prophets-mosque'],
        'museums-heritage'           => ['museum', 'hejaz-railway', 'ghamamah', 'abu-bakr', 'khandaq-salman', 'prophets-mosque'],
    ];

    $photos = photos();
    $keys   = $sets[$slug] ?? ['prophets-mosque', 'quba', 'uhud', 'qiblatain', 'khandaq-salman', 'museum'];

    $out = [];
    foreach ($keys as $k) {
        if (isset($photos[$k])) {
            $out[] = [
                'src' => $photos[$k][0],
                /* The photograph is the same file in both locales; only its
                   description is translated. */
                'alt' => (string) content('photos', $k, 'alt', $photos[$k][1]),
            ];
        }
    }
    return $out;
}

function featured_tours(int $limit = 6): array
{
    $out = array_values(array_filter(tours(), static fn ($t) => !empty($t['featured'])));
    return array_slice($out, 0, $limit);
}

/**
 * The categories the tours actually use, for the listing filter.
 *
 * Returns [['key' => 'Sirah Sites', 'label' => 'مواقع السيرة'], …] — `key` is
 * the English label, which is the stable value a card's data-category carries
 * and the filter's <option value> compares on, and `label` is what the visitor
 * reads.
 */
function tour_categories(): array
{
    $out = [];
    foreach (tours() as $t) {
        $key = isset($t['category_key']) ? $t['category_key'] : $t['category'];
        if (!isset($out[$key])) {
            $out[$key] = ['key' => $key, 'label' => $t['category']];
        }
    }
    return array_values($out);
}

/* =========================================================================
 * Guides
 *
 * availability:
 *   days   — weekday numbers the guide works (0 = Sunday … 6 = Saturday)
 *   slots  — visiting slots the guide covers
 *   blocked — specific ISO dates the guide is unavailable
 * ====================================================================== */
function guides(): array
{
    /* Only name, role, bio, expertise and the portrait's alt text are
       translated. `languages`, `tours`, `vehicles`, `active` and
       `availability` are what guide_matches() filters on and are
       identical in both locales — a guide who works in Arabic matches
       language 'ar' whichever locale the visitor is browsing in. */
    return localize_list(guides_source(), 'guides', 'id');
}

function guides_source(): array
{
    return [
        [
            'id'         => 'g-ibrahim',
            'name'       => 'Ibrahim Al-Harbi',
            'image'      => 'images/guides/ibrahim-al-harbi-guide.webp',
            'image_alt'  => 'Portrait of Ibrahim Al-Harbi',
            'role'       => 'Senior Sirah guide',
            'bio'        => 'Ibrahim has guided historical tours in Madinah for years and is at his best on the long Sirah routes, where he keeps the sequence of events clear from the first stop to the last.',
            'languages'  => ['ar', 'en'],
            'expertise'  => ['Sirah sequence', 'Battle sites', 'City history'],
            'tours'      => ['essential-sirah-landscapes', 'uhud-mountain-battle', 'khandaq-trench', 'historical-mosques', 'badr-historical-tour'],
            'vehicles'   => ['small-car', 'sedan', 'suv', 'coaster'],
            'active'     => true,
            'availability' => ['days' => [0, 1, 2, 3, 4, 6], 'slots' => ['fajer', 'morning', 'afternoon', 'evening'], 'blocked' => []],
        ],
        [
            'id'         => 'g-aisha',
            'name'       => 'Aisha Rasheed',
            'image'      => 'images/guides/aisha-rasheed-guide.webp',
            'image_alt'  => 'Portrait of Aisha Rasheed',
            'role'       => 'Heritage & museums guide',
            'bio'        => 'Aisha works mainly with families and women\'s groups. She guides the museum and heritage routes and is known for making the collections engaging for younger visitors.',
            'languages'  => ['ar', 'en'],
            'expertise'  => ['Museums', 'Heritage', 'Family groups'],
            'tours'      => ['museums-heritage', 'prophets-mosque-surroundings', 'essential-sirah-landscapes', 'historical-mosques'],
            'vehicles'   => ['small-car', 'sedan', 'suv'],
            'active'     => true,
            'availability' => ['days' => [0, 1, 2, 3, 4], 'slots' => ['morning', 'afternoon', 'evening'], 'blocked' => []],
        ],
        [
            'id'         => 'g-musa',
            'name'       => 'Musa Siddiq',
            'image'      => 'images/guides/musa-siddiq-guide.webp',
            'image_alt'  => 'Portrait of Musa Siddiq',
            'role'       => 'Geography & landscape guide',
            'bio'        => 'Musa focuses on the landscape around Madinah — the valleys, the volcanic plains and the agricultural belt — and how the geography shaped the history of the city.',
            'languages'  => ['ar'],
            'expertise'  => ['Geography', 'Landscape', 'Day trips'],
            'tours'      => ['madinah-geographical-tour', 'badr-historical-tour', 'uhud-mountain-battle', 'khandaq-trench'],
            'vehicles'   => ['sedan', 'suv', 'coaster'],
            'active'     => true,
            'availability' => ['days' => [1, 2, 3, 4, 5], 'slots' => ['fajer', 'morning', 'afternoon'], 'blocked' => []],
        ],
        [
            'id'         => 'g-sumayyah',
            'name'       => 'Sumayyah Adil',
            'image'      => 'images/guides/sumayyah-adil-guide.webp',
            'image_alt'  => 'Portrait of Sumayyah Adil',
            'role'       => 'Walking tour guide',
            'bio'        => 'Sumayyah guides the walking routes through the central and southern areas of the city. She keeps groups small and the pace unhurried, with plenty of room for questions.',
            'languages'  => ['en'],
            'expertise'  => ['Walking routes', 'Early Madinah', 'Small groups'],
            'tours'      => ['hijrah-walk', 'prophets-mosque-surroundings', 'historical-mosques'],
            'vehicles'   => ['small-car', 'sedan'],
            'active'     => true,
            'availability' => ['days' => [0, 2, 3, 5, 6], 'slots' => ['fajer', 'morning', 'evening'], 'blocked' => []],
        ],
        [
            'id'         => 'g-yahya',
            'name'       => 'Yahya Kamal',
            'image'      => 'images/guides/yahya-kamal-guide.webp',
            'image_alt'  => 'Portrait of Yahya Kamal',
            'role'       => 'Group tour guide',
            'bio'        => 'Yahya handles larger groups and coach tours. He is used to keeping a big group together and on schedule without the day feeling rushed.',
            'languages'  => ['ar', 'en'],
            'expertise'  => ['Large groups', 'Coach tours', 'Scheduling'],
            'tours'      => ['essential-sirah-landscapes', 'uhud-mountain-battle', 'khandaq-trench', 'madinah-geographical-tour', 'museums-heritage', 'badr-historical-tour'],
            'vehicles'   => ['suv', 'coaster'],
            'active'     => true,
            'availability' => ['days' => [0, 1, 3, 4, 5, 6], 'slots' => ['morning', 'afternoon', 'evening'], 'blocked' => []],
        ],
        [
            'id'         => 'g-nabil',
            'name'       => 'Nabil Farouk',
            'image'      => 'images/guides/nabil-farouk-guide.webp',
            'image_alt'  => 'Portrait of Nabil Farouk',
            'role'       => 'Historical mosques guide',
            'bio'        => 'Nabil guides the historical mosque routes and the northern sites. He is careful to separate what is well documented from what is popularly repeated.',
            'languages'  => ['ar', 'en'],
            'expertise'  => ['Historical mosques', 'Northern sites', 'Sources'],
            'tours'      => ['historical-mosques', 'khandaq-trench', 'essential-sirah-landscapes', 'prophets-mosque-surroundings', 'hijrah-walk'],
            'vehicles'   => ['small-car', 'sedan', 'suv'],
            'active'     => true,
            'availability' => ['days' => [1, 2, 4, 5, 6], 'slots' => ['fajer', 'afternoon', 'evening'], 'blocked' => []],
        ],
    ];
}

function guide(string $id): ?array
{
    foreach (guides() as $g) {
        if ($g['id'] === $id) {
            return $g;
        }
    }
    return null;
}

/**
 * Guide eligibility rule — the single place the matching logic lives.
 *
 * A guide is offered only when EVERY requirement is satisfied:
 *   supports the tour AND the language AND the vehicle/capacity
 *   AND is active AND works that weekday AND covers that visiting slot
 *   AND is not blocked on that date.
 *
 * The same rule is mirrored in js/booking.js so the wizard can filter without a
 * round trip. When the backend arrives, replace both with the API result.
 *
 * @param array  $criteria tour, language, vehicle, date (Y-m-d), slot
 */
function guide_matches(array $guide, array $criteria): bool
{
    if (empty($guide['active'])) {
        return false;
    }
    if (!empty($criteria['tour']) && !in_array($criteria['tour'], $guide['tours'], true)) {
        return false;
    }
    if (!empty($criteria['language']) && !in_array($criteria['language'], $guide['languages'], true)) {
        return false;
    }
    if (!empty($criteria['vehicle']) && !in_array($criteria['vehicle'], $guide['vehicles'], true)) {
        return false;
    }
    if (!empty($criteria['slot']) && !in_array($criteria['slot'], $guide['availability']['slots'], true)) {
        return false;
    }
    if (!empty($criteria['date'])) {
        if (in_array($criteria['date'], $guide['availability']['blocked'], true)) {
            return false;
        }
        $weekday = (int) date('w', strtotime($criteria['date']));
        if (!in_array($weekday, $guide['availability']['days'], true)) {
            return false;
        }
    }
    return true;
}

/** @return array<int,array> guides matching the given booking criteria */
function available_guides(array $criteria): array
{
    return array_values(array_filter(
        guides(),
        static fn ($g) => guide_matches($g, $criteria)
    ));
}

/* =========================================================================
 * FAQs
 * ====================================================================== */
function faq_groups(): array
{
    /* `id` stays English — it is the #faq-booking anchor the category rail
       links to and the FAQPage schema is built from the localised text. */
    return localize_list(faq_groups_source(), 'faq_groups', 'id');
}

function faq_groups_source(): array
{
    return [
        [
            'id'    => 'booking',
            'title' => 'Booking & Availability',
            'items' => [
                ['q' => 'How do I book a tour?', 'a' => 'Choose a tour, then work through the five booking steps: tour options, date and visiting time, your guide, your details, and a final review. You can go back and change anything before you confirm.'],
                ['q' => 'How far in advance should I book?', 'a' => 'Earlier is better, particularly in busy seasons and for the Fajer slot. If your dates are close, contact us and we will tell you what is still open.'],
                ['q' => 'What happens after I confirm a booking?', 'a' => 'We review the request and come back to you to confirm the guide, the pickup time and the meeting arrangements.'],
                ['q' => 'Can I book more than one tour?', 'a' => 'Yes. Book them separately, or send us your dates through Plan Your Visit and we will put a schedule together for you.'],
            ],
        ],
        [
            'id'    => 'tours',
            'title' => 'Tours',
            'items' => [
                ['q' => 'How long are the tours?', 'a' => 'Most Madinah tours run between two and a half and four hours. The Badr trip is a full day. The duration is listed on every tour page.'],
                ['q' => 'Can a tour be adjusted to what we want to see?', 'a' => 'Within the tour duration, yes. Tell your guide at the start if you would like longer at a particular stop.'],
                ['q' => 'Are the tours suitable for children?', 'a' => 'Yes. Guides adapt the commentary for younger visitors. For long days such as the Badr trip, a shorter Madinah tour is usually a better fit.'],
                ['q' => 'What should I wear?', 'a' => 'Modest clothing is required at all the sites, along with comfortable shoes. Sun protection matters for most of the year.'],
            ],
        ],
        [
            'id'    => 'guides',
            'title' => 'Guides',
            'items' => [
                ['q' => 'Can I choose my guide?', 'a' => 'Yes. At step three of the booking you will see the guides who match your tour, language, group size, date and visiting time, and you can pick one.'],
                ['q' => 'What does "Any Available Guide" mean?', 'a' => 'It means you have no particular preference. We assign a suitable available guide based on your tour, date, time, language and group requirements, and confirm who it is before your tour.'],
                ['q' => 'Why can I not see a particular guide?', 'a' => 'A guide only appears when every requirement matches — the tour, the language, the vehicle you selected, the date and the visiting slot. Changing the date or the slot often brings more guides into the list.'],
                ['q' => 'Are the guides local to Madinah?', 'a' => 'Yes. Our guides live in Madinah and know the sites, the routes and the practical detail of visiting them.'],
            ],
        ],
        [
            'id'    => 'languages',
            'title' => 'Languages',
            'items' => [
                ['q' => 'Which languages are available?', 'a' => 'Tours are currently offered in Arabic and English. We are adding more languages, and any new language will appear in the booking form when it is ready.'],
                ['q' => 'Does the language affect who can guide us?', 'a' => 'Yes. Only guides who work in your chosen language will appear at the guide step.'],
                ['q' => 'Can a group mix languages?', 'a' => 'Choose the main language for the group. If you need something more flexible, mention it in the notes and we will do what we can.'],
            ],
        ],
        [
            'id'    => 'groups',
            'title' => 'Group Size & Vehicles',
            'items' => [
                ['q' => 'How is group size decided?', 'a' => 'The vehicle you choose sets the group size: Small Car up to 2 guests, Sedan up to 4, SUV up to 7 and Coaster Bus up to 15.'],
                ['q' => 'What if my group is larger than 15?', 'a' => 'Contact us through Plan Your Visit and we will arrange more than one vehicle and coordinate the guides.'],
                ['q' => 'Can I book a private tour?', 'a' => 'Every tour is private to your group. You are not joined with other travellers.'],
                ['q' => 'Are child seats available?', 'a' => 'Ask in the notes when you book and we will confirm what we can arrange for your date.'],
            ],
        ],
        [
            'id'    => 'meeting',
            'title' => 'Meeting & Pickup',
            'items' => [
                ['q' => 'Where does the tour start?', 'a' => 'Most tours start with pickup from your hotel in central Madinah. Add your pickup location at the details step and we will confirm it with you.'],
                ['q' => 'What if my hotel is outside the central area?', 'a' => 'Tell us the address in the booking and we will confirm whether it is covered or suggest a nearby meeting point.'],
                ['q' => 'How will I recognise my guide?', 'a' => 'We send the guide\'s name and contact number before the tour, and they will call you at the agreed pickup time.'],
                ['q' => 'What if I am running late?', 'a' => 'Call the number we send you. Guides will wait where they can, though a late start may shorten the tour.'],
            ],
        ],
        [
            'id'    => 'payments',
            'title' => 'Payments',
            'items' => [
                ['q' => 'How do I pay?', 'a' => 'Online payment is not yet available on this site. After you confirm a booking we contact you to arrange payment and confirm the total.'],
                ['q' => 'Are the prices per person or per group?', 'a' => 'Prices shown are for the group, based on the vehicle option you select. They are sample prices at this stage.'],
                ['q' => 'Are entry tickets included?', 'a' => 'Museum entry and similar costs are confirmed with you before the visit and are not part of the tour price shown.'],
            ],
        ],
        [
            'id'    => 'changes',
            'title' => 'Changes & Cancellation',
            'items' => [
                ['q' => 'Can I change my date or visiting time?', 'a' => 'Contact us as early as you can. We will move your booking where availability allows, and confirm the guide again if the guide changes.'],
                ['q' => 'Can I cancel a booking?', 'a' => 'Yes. See the Cancellation Policy for the current terms and how to request a cancellation.'],
                ['q' => 'What happens in bad weather?', 'a' => 'Madinah tours run in most conditions. If a route is genuinely unsafe we will contact you to move the tour or adjust it.'],
            ],
        ],
    ];
}

/**
 * The homepage controller prepares its DB-backed FAQ selection in this same
 * question/answer shape.
 */


/* =========================================================================
 * Sample testimonials — clearly marked as placeholder content
 * ====================================================================== */
function testimonials(): array
{
    $items = [
        ['quote' => 'The guide explained the sites in order, so by the end of the morning the whole map of the city made sense. That was the part we did not expect.', 'name' => 'Sample review', 'meta' => 'Essential Sirah Landscapes'],
        ['quote' => 'We travelled as a family of six and the SUV option worked well. Everything was confirmed in advance and the pickup was on time.', 'name' => 'Sample review', 'meta' => 'Historical Mosques of Madinah'],
        ['quote' => 'Being able to choose an English-speaking guide before booking made the decision easy. The pace suited older members of our group.', 'name' => 'Sample review', 'meta' => 'Uhud Mountain & the Battle of Uhud'],
    ];

    foreach ($items as $i => $item) {
        $items[$i] = localize($item, 'testimonials', (string) $i);
    }

    return $items;
}

/* =========================================================================
 * Static content blocks reused across pages
 * ====================================================================== */
function benefits(): array
{
    /* Keyed by icon — the one field on these records that never changes and is
       not itself copy. */
    return localize_list([
        ['icon' => 'guide', 'title' => 'Knowledgeable local guides', 'text' => 'Our guides live in Madinah and know the sites, the routes and the practical detail of visiting them.'],
        ['icon' => 'route', 'title' => 'Carefully planned experiences', 'text' => 'Routes are arranged in a sequence that makes historical sense, not just a list of nearby stops.'],
        ['icon' => 'group', 'title' => 'Flexible group options', 'text' => 'From a couple in a small car to a group of fifteen in a coaster bus, the vehicle sets the group size.'],
        ['icon' => 'language', 'title' => 'Arabic and English', 'text' => 'Choose your language when you book and only guides who work in that language are offered.'],
        ['icon' => 'calendar', 'title' => 'Straightforward booking', 'text' => 'Five clear steps: options, date and time, guide, your details, and a review before anything is confirmed.'],
        ['icon' => 'support', 'title' => 'Local support', 'text' => 'A local team you can reach before and during your visit if plans need to change.'],
    ], 'benefits', 'icon');
}

function booking_steps(): array
{
    return localize_list([
        ['n' => 1, 'title' => 'Choose your tour options', 'text' => 'Pick the tour, the vehicle that fits your group and the language you would like.'],
        ['n' => 2, 'title' => 'Pick a date & visiting time', 'text' => 'Choose a date and one of four visiting slots, from Fajer through to evening.'],
        ['n' => 3, 'title' => 'Choose your guide', 'text' => 'See the guides who match everything you selected, or let us assign one for you.'],
        ['n' => 4, 'title' => 'Add your details', 'text' => 'Name, contact, where you are coming from and your pickup location.'],
        ['n' => 5, 'title' => 'Review & confirm', 'text' => 'Check everything on one screen, edit anything that needs changing, then confirm.'],
    ], 'booking_steps', 'n');
}
