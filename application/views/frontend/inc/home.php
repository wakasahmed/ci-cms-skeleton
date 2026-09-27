<?php
/**
 * Homepage section renderers.
 *
 * All database access and view-model preparation happens before this file is
 * loaded. These functions only turn controller-provided arrays into markup.
 */

/* =========================================================================
 * Section rendering
 *
 * Each Manage > Web Page Sections entry has its own render function here,
 * building its markup into a $html string and returning it (the same
 * concatenation style section_head() in inc/components.php already uses)
 * rather than echoing directly — so index.php can build the full set once,
 * then echo them back in the administrator's own `sort_order`.
 * ====================================================================== */

/** Featured tours. */
function home_render_tours_section(array $tours, array $section): string
{
    if (empty($tours)) {
        return '';
    }

    $cardsHtml = '';
    foreach ($tours as $i => $tour) {
        $cardsHtml .= '<li data-reveal data-reveal-delay="' . ($i * 60) . '">'
            . tour_card($tour, ['eager' => $i < 3])
            . '</li>';
    }

    $html = '<section class="' . e($section['background'] ?? 'bg-white') . ' py-16 sm:py-20 lg:py-24" id="tours">';
    $html .= '<div class="container">';
    $html .= '<div class="flex flex-col gap-6 sm:flex-row sm:items-end sm:justify-between">';
    $html .= section_head($section['head'] ?? []);
    $html .= prepared_section_button($section, 'btn-outline shrink-0');
    $html .= '</div>';
    $html .= '<ul class="mt-10 grid gap-5 sm:grid-cols-2 lg:mt-12 lg:grid-cols-3 lg:gap-6">' . $cardsHtml . '</ul>';
    $html .= '</div>';
    $html .= '</section>';

    return $html;
}

/** Discover Madinah — storytelling, open layout. */
function home_render_discover_madinah_section(array $section): string
{
    $discover = $section['head'] ?? [];
    $discoverHtml = $section['contents_html'] ?? '';
    $discoverImage = $section['image'] ?? '';
    $discoverCards = $section['cards'] ?? [];
    $imageSrc      = $discoverImage !== '' ? $discoverImage : '';

    $html = '<section class="relative overflow-hidden ' . e($section['background'] ?? 'bg-surface-tint') . ' py-16 sm:py-20 lg:py-28">';
    $html .= '<div class="absolute end-0 top-1/4 -z-10 h-96 w-96 translate-x-1/3 rounded-full bg-alam-100 rtl:-translate-x-1/3" aria-hidden="true"></div>';
    $html .= '<div class="container">';
    $html .= '<div class="grid items-center gap-10 lg:grid-cols-[0.9fr_1.1fr] lg:gap-16">';

    if ($imageSrc !== '') {
        $html .= '<div class="relative order-2 lg:order-1" data-reveal>';
        $html .= '<div class="arch-soft overflow-hidden bg-alam-100">';
        $html .= '<img src="' . e($imageSrc) . '" alt="' . e($discover['title'] ?? '') . '"'
            . ' width="960" height="1200" loading="lazy" decoding="async" class="h-full w-full object-cover">';
        $html .= '</div>';
        $html .= '</div>';
    }

    $html .= '<div class="order-1 lg:order-2' . ($imageSrc === '' ? ' lg:col-span-2' : '') . '">';
    if (!empty($discover['eyebrow'])) {
        $html .= '<p class="eyebrow">' . e($discover['eyebrow']) . '</p>';
    }
    if (!empty($discover['title'])) {
        $html .= '<h2 class="mt-3.5 max-w-xl text-h2">' . e($discover['title']) . '</h2>';
    }
    if ($discoverHtml !== '') {
        $html .= '<div class="page-contents mt-5 max-w-prose">' . $discoverHtml . '</div>';
    }
    if ($discoverCards) {
        $cardsHtml = '';
        foreach ($discoverCards as $item) {
            $cardsHtml .= '<li class="flex gap-3.5">'
                . '<span class="grid h-10 w-10 shrink-0 place-items-center rounded-control bg-alam-100 text-alam-700">'
                . icon($item['icon'], 'icon-md') . '</span>'
                . '<div>'
                . '<h3 class="text-body-sm font-bold text-ink">' . e($item['title']) . '</h3>'
                . '<p class="mt-1 text-body-sm text-ink-muted">' . e($item['text']) . '</p>'
                . '</div>'
                . '</li>';
        }
        $html .= '<ul class="mt-8 grid gap-5 sm:grid-cols-2">' . $cardsHtml . '</ul>';
    }
    $html .= prepared_section_button($section, 'btn-link mt-8');
    $html .= '</div>';

    $html .= '</div>';
    $html .= '</div>';
    $html .= '</section>';

    return $html;
}

/** Featured experiences — a separate product type from Tours. */
function home_render_experiences_section(array $items, array $section): string
{
    if (empty($items)) {
        return '';
    }
    $head = $section['head'] ?? [];

    return experiences_teaser([
        'eyebrow' => $head['eyebrow'],
        'title'   => $head['title'],
        'text'    => $head['text'],
        'items'   => $items,
        'count'   => 6,
        'columns' => 3,
        'bg'       => $section['background'] ?? 'bg-white',
        'cta_html' => prepared_section_button($section, 'btn-outline shrink-0'),
    ]);
}

/** Why choose Alam Al-Munawara. */
function home_render_why_section(array $section): string
{
    $benefits = $section['cards'] ?? [];
    if (empty($benefits)) {
        return '';
    }

    $cardsHtml = '';
    foreach ($benefits as $i => $b) {
        $cardsHtml .= '<li class="card flex gap-4 p-5" data-reveal data-reveal-delay="' . ($i * 50) . '">'
            . '<span class="grid h-11 w-11 shrink-0 place-items-center rounded-control bg-alam-500/10 text-alam-600">'
            . icon($b['icon'], 'icon-lg') . '</span>'
            . '<div>'
            . '<h3 class="text-body-sm font-bold text-ink">' . e($b['title']) . '</h3>'
            . '<p class="mt-1.5 text-body-sm text-ink-muted">' . e($b['text']) . '</p>'
            . '</div>'
            . '</li>';
    }

    $html = '<section class="' . e($section['background'] ?? 'bg-surface-tint') . ' py-16 sm:py-20 lg:py-24">';
    $html .= '<div class="container">';
    $html .= section_head(array_merge($section['head'] ?? [], ['align' => 'center']));
    $html .= '<ul class="mt-12 grid gap-4 sm:grid-cols-2 lg:grid-cols-3 lg:gap-5">' . $cardsHtml . '</ul>';
    $html .= '</div>';
    $html .= '</section>';

    return $html;
}

/** Meet the guides. */
function home_render_guides_section(array $guides, array $section): string
{
    if (empty($guides)) {
        return '';
    }

    $cardsHtml = '';
    foreach ($guides as $i => $g) {
        $cardsHtml .= '<li data-reveal data-reveal-delay="' . ($i * 60) . '">' . guide_card($g) . '</li>';
    }

    $html = '<section class="' . e($section['background'] ?? 'bg-white') . ' py-16 sm:py-20 lg:py-24">';
    $html .= '<div class="container">';
    $html .= '<div class="flex flex-col gap-6 sm:flex-row sm:items-end sm:justify-between">';
    $html .= section_head($section['head'] ?? []);
    $html .= prepared_section_button($section, 'btn-outline shrink-0');
    $html .= '</div>';
    $html .= '<ul class="mt-10 grid gap-5 sm:grid-cols-2 lg:mt-12 lg:grid-cols-3 lg:gap-6">' . $cardsHtml . '</ul>';
    $html .= '</div>';
    $html .= '</section>';

    return $html;
}

/** How booking works — the one section with its own fixed dark band. */
function home_render_booking_steps_section(array $section): string
{
    $steps = $section['steps'] ?? [];
    if (empty($steps)) {
        return '';
    }

    $stepsHtml = '';
    foreach ($steps as $i => $s) {
        $stepsHtml .= '<li class="relative rounded-card border border-white/15 bg-white/[0.06] p-5" data-reveal data-reveal-delay="'
            . ($i * 60) . '">'
            . '<span class="grid h-9 w-9 place-items-center rounded-full bg-white text-meta font-extrabold text-alam-700">'
            . e(number($s['n'])) . '</span>'
            . '<h3 class="mt-4 text-body-sm font-bold text-white">' . e($s['title']) . '</h3>'
            . '<p class="mt-2 text-body-sm text-white/70">' . e($s['text']) . '</p>'
            . '</li>';
    }

    $gridColumns = [
        1 => 'lg:grid-cols-1',
        2 => 'lg:grid-cols-2',
        3 => 'lg:grid-cols-3',
        4 => 'lg:grid-cols-4',
        5 => 'lg:grid-cols-5',
        6 => 'lg:grid-cols-3',
    ];
    $desktopGridClass = $gridColumns[count($steps)] ?? 'lg:grid-cols-3';

    $html = '<section class="relative overflow-hidden bg-alam-800 py-16 text-white sm:py-20 lg:py-24">';
    $html .= '<div class="pattern-grid absolute inset-0 opacity-25" aria-hidden="true"></div>';
    $html .= '<div class="container relative">';
    $html .= section_head(array_merge(
        $section['head'] ?? [],
        ['align' => 'center', 'tone' => 'light']
    ));
    $html .= '<ol class="mt-12 grid gap-4 sm:grid-cols-2 ' . $desktopGridClass . ' lg:gap-3">'
        . $stepsHtml . '</ol>';
    $html .= '<div class="mt-10 flex flex-wrap justify-center gap-3">';
    $html .= prepared_section_button($section, 'btn bg-white text-alam-800 hover:bg-alam-50 btn-lg', 'button_1');
    $html .= prepared_section_button($section, 'btn-ghost-light btn-lg', 'button_2');
    $html .= '</div>';
    $html .= '</div>';
    $html .= '</section>';

    return $html;
}

/** Customer reviews / testimonials. */
function home_render_reviews_section(array $testimonials, array $section): string
{
    if (empty($testimonials)) {
        return '';
    }
    $reviewsNote = $section['contents_text'] ?? '';

    $cardsHtml = '';
    foreach ($testimonials as $i => $t) {
        // An opening quotation mark is directional: it belongs at the start
        // of the quote, which is the right-hand side in Arabic.
        $cardsHtml .= '<li class="card flex flex-col p-6" data-reveal data-reveal-delay="' . ($i * 60) . '">'
            . icon('quote', 'icon-2xl text-alam-200 icon-flip')
            . '<blockquote class="mt-4 flex-1 text-body text-ink"><p>' . e($t['quote']) . '</p></blockquote>'
            . '<footer class="mt-5 border-t border-line pt-4">'
            . '<p class="text-meta font-bold text-ink">' . e($t['name']) . '</p>'
            . '<p class="text-meta text-ink-muted">' . e($t['meta']) . '</p>'
            . '</footer>'
            . '</li>';
    }

    $html = '<section class="' . e($section['background'] ?? 'bg-white') . ' py-16 sm:py-20 lg:py-24">';
    $html .= '<div class="container">';
    $html .= section_head([
        'eyebrow' => $section['head']['eyebrow'] ?? '',
        'title'   => $section['head']['title'] ?? '',
        'align'   => 'center',
    ]);
    if ($reviewsNote !== '') {
        $html .= '<p class="mx-auto mt-3 max-w-lg text-center text-meta text-ink-soft">' . e($reviewsNote) . '</p>';
    }
    $html .= '<ul class="mt-10 grid gap-5 lg:grid-cols-3">' . $cardsHtml . '</ul>';
    $html .= '</div>';
    $html .= '</section>';

    return $html;
}

/** Home FAQs. */
function home_render_faqs_section(array $faqs, array $section): string
{
    if (empty($faqs)) {
        return '';
    }
    $faqHead = $section['head'] ?? [];

    $intro = '<div>';
    if (!empty($faqHead['eyebrow'])) {
        $intro .= '<p class="eyebrow">' . e($faqHead['eyebrow']) . '</p>';
    }
    if (!empty($faqHead['title'])) {
        $intro .= '<h2 class="mt-3.5 text-h2">' . e($faqHead['title']) . '</h2>';
    }
    if (!empty($faqHead['text'])) {
        $intro .= '<p class="mt-4 text-body text-ink-muted">' . e($faqHead['text']) . '</p>';
    }
    $intro .= prepared_section_button($section, 'btn-outline mt-6');
    $intro .= '</div>';

    $html = '<section class="' . e($section['background'] ?? 'bg-surface-tint') . ' py-16 sm:py-20 lg:py-24">';
    $html .= '<div class="container">';
    $html .= '<div class="grid gap-10 lg:grid-cols-[0.85fr_1.15fr] lg:gap-16">';
    $html .= $intro;
    $html .= '<div class="card px-5 sm:px-6">' . faq_accordion($faqs, 'home-faq', 0) . '</div>';
    $html .= '</div>';
    $html .= '</div>';
    $html .= '</section>';

    return $html;
}

/** Blog. */
function home_render_blogs_section(array $posts, array $section): string
{
    if (empty($posts)) {
        return '';
    }

    $cardsHtml = '';
    foreach ($posts as $i => $p) {
        $cardsHtml .= '<li data-reveal data-reveal-delay="' . ($i * 60) . '">'
            . '<article class="card card-hover group relative flex h-full flex-col overflow-hidden">'
            . '<div class="aspect-[16/9] overflow-hidden bg-alam-100">'
            . '<img src="' . e($p['image']) . '" alt="" width="1200" height="675"'
            . ' loading="lazy" decoding="async" class="h-full w-full object-cover transition-transform duration-500 group-hover:scale-[1.04]">'
            . '</div>'
            . '<div class="flex flex-1 flex-col p-5">'
            . '<h3 class="text-card-title font-bold text-ink"><a href="'
            . e(blog_post_url($p['slug'])) . '" class="after:absolute after:inset-0">'
            . e($p['title']) . '</a></h3>'
            . '<p class="mt-2 line-clamp-3 text-card-body text-ink-muted">' . e($p['excerpt']) . '</p>'
            . blog_category_pills($p['categories'] ?? [])
            . blog_card_meta($p['date'], $p['read'])
            . '</div>'
            . '</article>'
            . '</li>';
    }

    $html = '<section class="' . e($section['background'] ?? 'bg-white') . ' py-16 sm:py-20 lg:py-24">';
    $html .= '<div class="container">';
    $html .= '<div class="flex flex-col gap-6 sm:flex-row sm:items-end sm:justify-between">';
    $html .= section_head($section['head'] ?? []);
    $html .= prepared_section_button($section, 'btn-outline shrink-0');
    $html .= '</div>';
    $html .= '<ul class="mt-10 grid gap-5 sm:grid-cols-2 lg:mt-12 lg:grid-cols-3 lg:gap-6">' . $cardsHtml . '</ul>';
    $html .= '</div>';
    $html .= '</section>';

    return $html;
}
