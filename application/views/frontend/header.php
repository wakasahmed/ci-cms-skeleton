<?php
/**
 * Shared document head + site header.
 *
 * One header for both locales. Everything that differs — the document
 * direction, the font stack, the canonical, the hreflang pair, the Open Graph
 * locale and every visible string — is derived from current_locale();
 * there is no Arabic copy of this file.
 *
 * Expects the calling page to have set (all optional):
 *   $config['page_title'], $config['meta_description'], $config['active'],
 *   $config['body_class'], $config['canonical'],
 *   $config['og_title'], $config['og_description'] (default to the two above),
 *   $config['og_image'] (path or absolute URL), $config['og_image_width'],
 *   $config['og_image_height'], $config['og_image_alt'],
 *   $config['og_type'] ('website' by default; 'article' for blog posts),
 *   $config['article'] (published, modified, author, section — blog posts),
 *   $config['robots'], $config['keywords'],
 *   $config['breadcrumbs'] (label/href pairs; the banner crumbs when absent),
 *   $config['schema'] (extra JSON-LD nodes for this page's record),
 *   $config['styles'] (extra stylesheets, loaded before css/app.css),
 *   $config['select2'] (bool — pull in the shared Select2 assets for this page)
 */


if (!function_exists('base_url')) {
    require_once __DIR__ . '/functions.php';
}

$config      = $config ?? [];
// The shared booking dialog uses Select2 on every frontend page.
$config['select2'] = true;
// Partial page navigation between the pages listed in htmx_navigation_pages().
$config['htmx'] = !empty($config['htmx']) || htmx_navigation_enabled();
$seo         = get_instance()->frontend_seo;
$biz         = business();
$activeKey   = (string) ($config['active'] ?? '');
$pageTitle   = site_meta($config, 'page_title', t('home.meta.title'));
$metaDesc    = site_meta(
    $config,
    'meta_description',
    $seo->siteDescription() !== '' ? $seo->siteDescription() : t('home.meta.description')
);
$ogTitle     = site_meta($config, 'og_title', $pageTitle);
$ogDesc      = site_meta($config, 'og_description', $metaDesc);
$keywords    = site_meta($config, 'keywords');

/* A site still under construction is kept out of search results entirely,
   whatever an individual page allows. Indexable pages also opt in to large
   image previews and full snippets. */
$robots      = $seo->isUnderConstruction()
    ? 'noindex, nofollow'
    : $seo->withSnippetDirectives(site_meta($config, 'robots', 'index, follow'));

/* The page's own sharing image, else the site-wide default with its real size. */
if (!empty($config['og_image'])) {
    $ogImage = is_absolute_url((string) $config['og_image'])
        ? (string) $config['og_image']
        : site_base_url((string) $config['og_image']);
    $ogImageWidth  = (int) ($config['og_image_width'] ?? 0);
    $ogImageHeight = (int) ($config['og_image_height'] ?? 0);
} else {
    $defaultImage  = $seo->image([]);
    $ogImage       = $defaultImage['url'];
    $ogImageWidth  = $defaultImage['width'];
    $ogImageHeight = $defaultImage['height'];
}
$ogImageAlt  = site_meta($config, 'og_image_alt', $ogTitle);
$ogType      = site_meta($config, 'og_type', 'website');
$article     = is_array($config['article'] ?? null) ? $config['article'] : [];
$bodyClass   = trim(($config['body_class'] ?? '') . ' locale-' . current_locale());

$locale      = current_locale();
$isRtl       = locale_is_rtl($locale);
$sameAs      = array_values(array_unique(array_filter(array_merge(
    [$biz['website_url']],
    array_column($biz['social_links'], 'url')
))));

/* Canonical is built from the route map rather than from REQUEST_URI, so
   /tours.php and /tours resolve to one address, and only the query parameters
   that change what the page shows survive (see canonical_query()). Tracking
   parameters never become a second indexed address. A page may still override
   it. Non-ASCII slugs are percent-encoded so canonical, hreflang and the XML
   sitemap all print the same string. */
$canonical   = $seo->encodeUrl($config['canonical'] ?? canonical_url_in($locale));

/* Structured data: the business and site on every page, then this page and its
   record (tour, article, listing …). */
$pageTypes   = [
    'about-us.php'   => 'AboutPage',
    'contact.php'    => 'ContactPage',
    'tours.php'      => 'CollectionPage',
    'experiences.php' => 'CollectionPage',
    'blog.php'       => 'CollectionPage',
    'tour-details.php' => 'ItemPage',
];
$crumbs      = $config['breadcrumbs'] ?? ($config['banner']['crumbs'] ?? []);
$schemaGraph = $seo->graph([
    'canonical'    => $canonical,
    'title'        => $pageTitle,
    'description'  => $metaDesc,
    'locale_tag'   => locale_tag($locale),
    'locales'      => array_map('locale_tag', supported_locales()),
    'page_type'    => $pageTypes[current_script()] ?? 'WebPage',
    'image'        => ['url' => $ogImage, 'width' => $ogImageWidth, 'height' => $ogImageHeight],
    'crumbs'       => is_array($crumbs) ? $crumbs : [],
    'nodes'        => is_array($config['schema'] ?? null) ? $config['schema'] : [],
    'is_home'      => current_script() === 'index.php',
    'organization' => [
        'name'          => $biz['name'],
        'url'           => url('index.php'),
        'description'   => $seo->siteDescription(),
        'logo'          => $biz['logo'],
        'telephone'     => $biz['phone'],
        'email'         => $biz['email'],
        'area_served'   => t('business.areaServed'),
        /* Business facts, identical in both locales. */
        'address'       => [
            '@type'           => 'PostalAddress',
            'addressLocality' => t('business.city'),
            'addressCountry'  => 'SA',
        ],
        'same_as'       => $sameAs,
        'license_label' => t('business.license.label'),
        'license_value' => $biz['license'],
    ],
]);
?><!doctype html>
<html lang="<?php echo e(locale_tag($locale)) ?>" dir="<?php echo e(locale_dir($locale)) ?>" data-locale="<?php echo e($locale) ?>">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <?php echo setting_value('script_after_head') ?>
  <title><?php echo e($pageTitle) ?></title>
  <meta name="description" content="<?php echo e($metaDesc) ?>">
  <?php if ($keywords !== ''): ?>
  <meta name="keywords" content="<?php echo e($keywords) ?>">
  <?php endif; ?>
  <meta name="robots" content="<?php echo e($robots) ?>">
  <?php if (!empty($article['author'])): ?>
  <meta name="author" content="<?php echo e($article['author']) ?>">
  <?php endif; ?>
  <meta name="theme-color" content="#63569B">
  <link rel="canonical" href="<?php echo e($canonical) ?>">

  <?php /* Reciprocal hreflang for both locales plus x-default. Each URL is the
           SAME page in that locale — using that locale's own slug for a tour,
           blog post or page — so the two versions point at each other and never
           at a locale's home page. x-default is the language set as the default in
           Website Settings, because that is where the site root sends a visitor
           with no saved language. */ ?>
  <?php foreach (supported_locales() as $altLocale): ?>
  <link rel="alternate" hreflang="<?php echo e(locale_tag($altLocale)) ?>" href="<?php echo e($seo->encodeUrl(canonical_url_in($altLocale))) ?>">
  <?php endforeach; ?>
  <link rel="alternate" hreflang="x-default" href="<?php echo e($seo->encodeUrl(canonical_url_in($seo->defaultLocale()))) ?>">

  <meta property="og:type" content="<?php echo e($ogType) ?>">
  <meta property="og:site_name" content="<?php echo e($biz['name']) ?>">
  <meta property="og:title" content="<?php echo e($ogTitle) ?>">
  <meta property="og:description" content="<?php echo e($ogDesc) ?>">
  <meta property="og:image" content="<?php echo e($ogImage) ?>">
  <?php if ($ogImageWidth > 0 && $ogImageHeight > 0): ?>
  <meta property="og:image:width" content="<?php echo (int) $ogImageWidth ?>">
  <meta property="og:image:height" content="<?php echo (int) $ogImageHeight ?>">
  <?php endif; ?>
  <meta property="og:image:alt" content="<?php echo e($ogImageAlt) ?>">
  <meta property="og:url" content="<?php echo e($canonical) ?>">
  <meta property="og:locale" content="<?php echo e(og_locale($locale)) ?>">
  <meta property="og:locale:alternate" content="<?php echo e(og_locale(other_locale($locale))) ?>">
  <?php if ($ogType === 'article'): ?>
    <?php foreach (['published_time' => 'published', 'modified_time' => 'modified', 'section' => 'section'] as $property => $key): ?>
      <?php if (!empty($article[$key])): ?>
  <meta property="article:<?php echo e($property) ?>" content="<?php echo e($article[$key]) ?>">
      <?php endif; ?>
    <?php endforeach; ?>
  <?php endif; ?>
  <meta name="twitter:card" content="summary_large_image">
  <meta name="twitter:title" content="<?php echo e($ogTitle) ?>">
  <meta name="twitter:description" content="<?php echo e($ogDesc) ?>">
  <meta name="twitter:image" content="<?php echo e($ogImage) ?>">
  <meta name="twitter:image:alt" content="<?php echo e($ogImageAlt) ?>">

  <link rel="icon" href="<?php echo e($biz['favicon']) ?>" type="image/png">
  <link rel="apple-touch-icon" href="<?php echo e($biz['favicon']) ?>">

  <?php /* Fonts are self-hosted — no CDN, for either family. Plus Jakarta Sans
           carries the Latin UI in both locales; IBM Plex Sans Arabic is added
           only on Arabic pages, so an English visitor downloads none of it.
           See vendor/fonts/VERSION.txt for versions and licences. */ ?>
  <link rel="preload" href="<?php echo e(site_base_url('vendor/fonts/plus-jakarta-sans/plus-jakarta-sans-latin.woff2')) ?>"
        as="font" type="font/woff2" crossorigin>
  <link rel="stylesheet" href="<?php echo e(asset('vendor/fonts/plus-jakarta-sans/plus-jakarta-sans.css')) ?>">
  <?php if ($isRtl): ?>
  <link rel="preload" href="<?php echo e(site_base_url('vendor/fonts/ibm-plex-sans-arabic/ibm-plex-sans-arabic-400-arabic.woff2')) ?>"
        as="font" type="font/woff2" crossorigin>
  <link rel="stylesheet" href="<?php echo e(asset('vendor/fonts/ibm-plex-sans-arabic/ibm-plex-sans-arabic.css')) ?>">
  <?php endif; ?>

  <?php /* Font Awesome Free 7 — self-hosted. Most pages use the smaller Solid
           + Brands bundle; controls that need Regular icons opt into the
           existing complete local bundle. Never add a CDN link or JS kit. */ ?>
  <?php if (!empty($config['fontawesome_all'])): ?>
  <link rel="stylesheet" href="<?php echo e(base_url('assets/admin/vendor/fontawesome-free/css/all.min.css')) ?>">
  <?php else: ?>
  <link rel="preload" href="<?php echo e(site_base_url('vendor/fontawesome/webfonts/fa-solid-900.woff2')) ?>"
        as="font" type="font/woff2" crossorigin>
  <link rel="stylesheet" href="<?php echo e(asset('vendor/fontawesome/css/fontawesome.min.css')) ?>">
  <link rel="stylesheet" href="<?php echo e(asset('vendor/fontawesome/css/solid.min.css')) ?>">
  <link rel="stylesheet" href="<?php echo e(asset('vendor/fontawesome/css/brands.min.css')) ?>">
  <?php endif; ?>

  <?php foreach (page_styles($config) as $style): ?>
  <link rel="stylesheet" href="<?php echo e(asset($style)) ?>">
  <?php endforeach; ?>
  <?php if (!empty($config['moyasar'])): ?>
  <link rel="stylesheet" href="https://cdn.moyasar.com/mpf/<?php echo rawurlencode(MOYASAR_FORM_VERSION); ?>/moyasar.css">
  <?php endif; ?>
  <link rel="stylesheet" href="<?php echo e(asset('css/app.css')) ?>">

  <?php /* The locale payload every shared script reads: current locale,
           direction, plugin configuration and the messages the scripts build at
           runtime. One JSON island rather than a second copy of main.js. It is
           printed here, in <head>, so it exists before any deferred script
           runs. */ ?>
  <?php echo locale_script() ?>

  <?php /* JSON_HEX_TAG keeps a "</script>" inside any managed text from ending the block. */ ?>
  <script type="application/ld+json">
  <?php echo json_encode(
      $schemaGraph,
      JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP | JSON_PRETTY_PRINT
  ) ?>
  </script>
  <?php echo setting_value('script_before_head') ?>
</head>
<?php /* Partial navigation settings, inherited by every hx-boost link on the
         page: fetch the full page and swap only its <main> (js/htmx-init.js).
         hx-history="false": Back/Forward reload from the server, so HTMX
         must not snapshot (clone) the page before each navigation.
         data-partial-navigation marks a page that may be swapped in; any
         other response is loaded as a normal page. */ ?>
<body class="<?php echo e(trim($bodyClass)) ?>"<?php if (!empty($config['htmx'])): ?>
      data-partial-navigation
      hx-history="false"
      hx-target="#main"
      hx-select="#main"
      hx-swap="outerHTML"
      hx-push-url="true"
      hx-indicator="#page-progress"<?php endif; ?>>
<?php echo setting_value('script_after_body') ?>

<a href="#main" class="sr-only focus:not-sr-only focus:fixed focus:start-4 focus:top-4 focus:z-[100] focus:rounded-control focus:bg-alam-500 focus:px-4 focus:py-2.5 focus:text-button focus:font-semibold focus:text-white">
  <?php echo e(t('skip.main')) ?>
</a>

<header class="sticky top-0 z-50 border-b border-line bg-white/90 backdrop-blur-md" data-site-header>
  <div class="container">
    <div class="flex h-[var(--frontend-header-h)] items-center justify-between gap-4">

      <a href="<?php echo e(base_url(current_locale())) ?>" class="shrink-0" aria-label="<?php echo e(t('header.homeLink', ['name' => $biz['name']])) ?>"<?php echo htmx_boost_attr() ?>>
        <img src="<?php echo e($biz['logo']) ?>" alt="<?php echo e($biz['name']) ?>"
             class="h-11 w-auto sm:h-12 lg:h-14">
      </a>

      <nav class="hidden lg:block" aria-label="<?php echo e(t('nav.primary')) ?>">
        <ul class="flex items-center gap-4 xl:gap-7">
          <?php foreach (nav() as $item): ?>
            <?php
            $children = isset($item['children']) && is_array($item['children'])
                ? $item['children']
                : [];
            $isActive = nav_item_is_active($item, $activeKey);
            ?>
            <li class="<?php echo $children ? 'nav-has-submenu relative' : '' ?>">
              <?php if ($children): ?>
                <a href="<?php echo e($item['href']) ?>"
                   class="nav-link flex items-center gap-1.5<?php echo $isActive ? ' is-active' : '' ?>"
                   aria-haspopup="true"
                   <?php echo $isActive ? 'aria-current="page"' : '' ?><?php echo htmx_nav_attr($item) ?>>
                  <?php echo e($item['label']) ?>
                  <?php echo icon('chevron-down', 'icon-xs text-ink-soft') ?>
                </a>
                <ul class="nav-submenu absolute top-full z-50 w-56 rounded-card border border-line-strong bg-white p-1.5 shadow-lift">
                  <?php foreach ($children as $child): ?>
                    <li>
                      <a href="<?php echo e($child['href']) ?>"
                         class="block rounded-control px-3 py-2.5 text-nav text-ink hover:bg-alam-50 hover:text-alam-700"
                         <?php echo nav_item_is_active($child, $activeKey) ? 'aria-current="page"' : '' ?><?php echo htmx_nav_attr($child) ?>>
                        <?php echo e($child['label']) ?>
                      </a>
                    </li>
                  <?php endforeach; ?>
                </ul>
              <?php else: ?>
                <a href="<?php echo e($item['href']) ?>"
                   class="nav-link<?php echo $isActive ? ' is-active' : '' ?>"
                   <?php echo $isActive ? 'aria-current="page"' : '' ?><?php echo htmx_nav_attr($item) ?>><?php echo e($item['label']) ?></a>
              <?php endif; ?>
            </li>
          <?php endforeach; ?>
        </ul>
      </nav>

      <div class="flex items-center gap-2 sm:gap-3">
        <?php echo language_switcher(['variant' => 'desktop', 'id' => 'lang-desktop']) ?>

        <button type="button" class="btn-primary btn-sm" data-booking-picker-open aria-haspopup="dialog" aria-controls="booking-picker"><?php echo e(t('cta.bookTour')) ?></button>

        <button type="button"
                class="grid h-11 w-11 shrink-0 place-items-center rounded-control border border-line-strong text-ink transition-colors hover:border-alam-400 hover:text-alam-700 lg:hidden"
                data-menu-toggle aria-expanded="false" aria-controls="mobile-nav">
          <span class="sr-only"><?php echo e(t('menu.open')) ?></span>
          <?php echo icon('menu', 'icon-lg', ['data-menu-open' => '']) ?>
          <?php echo icon('close', 'hidden icon-lg', ['data-menu-close' => '']) ?>
        </button>
      </div>
    </div>
  </div>

  <!-- Mobile navigation -->
  <div id="mobile-nav" class="hidden border-t border-line bg-white lg:hidden" data-menu-panel>
    <nav class="container py-4" aria-label="<?php echo e(t('nav.mobile')) ?>">
      <ul class="divide-y divide-line">
        <?php foreach (nav() as $item): ?>
          <?php
          $children = isset($item['children']) && is_array($item['children'])
              ? $item['children']
              : [];
          $isActive = nav_item_is_active($item, $activeKey);
          ?>
          <li>
            <a href="<?php echo e($item['href']) ?>"
               class="flex items-center justify-between py-3.5 text-nav font-medium<?php echo $isActive ? ' font-bold text-alam-700' : ' text-ink' ?>"
               <?php echo nav_item_is_active($item, $activeKey) ? 'aria-current="page"' : '' ?><?php echo htmx_nav_attr($item) ?>>
              <?php echo e($item['label']) ?>
              <?php /* A navigation chevron is directional: it points the way
                       the reader moves, so it flips with the document. */ ?>
              <?php echo icon('chevron-right', 'icon-sm text-ink-soft icon-flip') ?>
            </a>
            <?php if ($children): ?>
              <ul class="border-t border-line bg-surface-soft">
                <?php foreach ($children as $child): ?>
                  <li>
                    <a href="<?php echo e($child['href']) ?>"
                       class="block py-3 ps-5 text-nav<?php echo nav_item_is_active($child, $activeKey) ? ' font-bold text-alam-700' : ' text-ink-muted' ?>"
                       <?php echo nav_item_is_active($child, $activeKey) ? 'aria-current="page"' : '' ?><?php echo htmx_nav_attr($child) ?>>
                      <?php echo e($child['label']) ?>
                    </a>
                  </li>
                <?php endforeach; ?>
              </ul>
            <?php endif; ?>
          </li>
        <?php endforeach; ?>
      </ul>
      <div class="mt-5 border-t border-line pt-5">
        <?php echo language_switcher(['variant' => 'mobile', 'id' => 'lang-mobile']) ?>
      </div>

      <div class="mt-4 pb-2">
        <button type="button" class="btn-primary w-full" data-booking-picker-open aria-haspopup="dialog" aria-controls="booking-picker"><?php echo e(t('cta.bookTour')) ?></button>
      </div>
    </nav>
  </div>
</header>

<main id="main">
