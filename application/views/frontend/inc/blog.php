<?php

defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Alam Al-Munawara — Blog listing and Blog Post render partials.
 *
 * Every function here is render-only: it turns an array the controller
 * already prepared from Blog_model into markup. None of them query the
 * database, read $_GET, filter, paginate, or choose a fallback — that is the
 * controller's job (see Frontend::buildBlogViewData() /
 * Frontend::buildBlogPostViewData()).
 */


/**
 * One blog card. Used for the listing grid and the "Keep reading" related
 * grid, which differ only in how much of the excerpt shows.
 *
 * $p: ['slug','title','excerpt','image','read','date','category', ...] — see
 * Frontend::prepareBlogCards(). `image` is already an absolute, thumbnailed
 * URL (or '' when the post has neither image). It prefers `blog_image` and
 * falls back to `blog_cover_image`.
 *
 * Options:
 *   clamp        2 or 3 — excerpt line clamp (default 3)
 *   boost        load the post and category links through HTMX
 */
function blog_card(array $p, array $o = []): string
{
    $clampClass = ((int) ($o['clamp'] ?? 3)) === 2 ? 'line-clamp-2' : 'line-clamp-3';
    // Every link in a card (the post, its category pills) is a blog page.
    $boost = !empty($o['boost']) ? ' hx-boost="true"' : '';

    $html = '<article class="card card-hover group relative flex h-full flex-col overflow-hidden"' . $boost . '>';

    if (!empty($p['image'])) {
        $html .= '<div class="aspect-[16/9] overflow-hidden bg-alam-100">';
        $html .= '<img src="' . e($p['image']) . '"'
            . ' alt="" width="1200" height="675" loading="lazy" decoding="async"'
            . ' class="h-full w-full object-cover transition-transform duration-500 group-hover:scale-[1.04]">';
        $html .= '</div>';
    }

    $html .= '<div class="flex flex-1 flex-col p-5">';
    $html .= '<h3 class="text-card-title font-bold text-ink">';
    $html .= '<a href="' . e(blog_post_url($p['slug'])) . '" class="after:absolute after:inset-0">';
    $html .= e($p['title']);
    $html .= '</a>';
    $html .= '</h3>';
    $html .= '<p class="mt-2 ' . $clampClass . ' text-card-body text-ink-muted">' . e($p['excerpt']) . '</p>';
    $html .= blog_category_pills($p['categories'] ?? []);
    $html .= blog_card_meta($p['date'], $p['read']);
    $html .= '</div>';
    $html .= '</article>';

    return $html;
}

/**
 * Every category assigned to a post, displayed as compact metadata pills.
 * $boost loads them through HTMX.
 */
function blog_category_pills(array $categories, bool $boost = false): string
{
    $items = '';
    foreach ($categories as $category) {
        $label = trim((string) ($category['label'] ?? ''));
        $slug = trim((string) ($category['slug'] ?? ''));
        if ($label === '' || $slug === '') {
            continue;
        }

        $items .= '<li>'
            . '<a href="' . e(blog_category_url($slug)) . '"'
            . ' class="relative z-10 inline-flex rounded-full bg-alam-50 px-2.5 py-1 text-meta font-medium text-alam-700 transition-colors hover:bg-alam-100 hover:text-alam-800 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-alam-500 focus-visible:ring-offset-2">'
            . e($label)
            . '</a>'
            . '</li>';
    }

    return $items !== ''
        ? '<ul class="mb-4 mt-4 flex flex-wrap gap-1.5"' . ($boost ? ' hx-boost="true"' : '') . '>' . $items . '</ul>'
        : '';
}

/** Blog card date on the left and estimated reading time on the right. */
function blog_card_meta(string $date, string $read): string
{
    return '<p class="mt-auto flex items-center justify-between gap-4 pt-4 text-meta text-ink-soft">'
        . '<span>' . e(site_date($date)) . '</span>'
        . '<span>' . e($read) . '</span>'
        . '</p>';
}

/**
 * The Blog listing's lead article — the page's most prominent post. $boost
 * loads its links (the post, its category pills) through HTMX.
 */
function blog_lead(array $p, bool $boost = false): string
{
    $html = '<article class="group grid gap-8 lg:grid-cols-2 lg:items-center lg:gap-12"'
        . ($boost ? ' hx-boost="true"' : '') . '>';

    if (!empty($p['image'])) {
        $html .= '<div class="overflow-hidden rounded-feature bg-alam-100">';
        $html .= '<img src="' . e($p['image']) . '" alt="" width="1200" height="675"'
            . ' fetchpriority="high" decoding="async"'
            . ' class="aspect-[16/10] w-full object-cover transition-transform duration-500 group-hover:scale-[1.03]">';
        $html .= '</div>';
    }

    $html .= '<div>';
    $html .= '<h2 class="text-h2">';
    $html .= '<a href="' . e(blog_post_url($p['slug'])) . '"'
        . ' class="transition-colors hover:text-alam-700">' . e($p['title']) . '</a>';
    $html .= '</h2>';

    if ($p['excerpt'] !== '') {
        $html .= '<p class="mt-4 max-w-prose text-body text-ink-muted">' . e($p['excerpt']) . '</p>';
    }

    $html .= blog_category_pills($p['categories'] ?? []);
    $html .= blog_card_meta($p['date'], $p['read']);
    $html .= '<a href="' . e(blog_post_url($p['slug'])) . '" class="btn-primary mt-7">';
    $html .= e(t('cta.readArticle')) . icon('arrow-right', 'icon-sm icon-flip');
    $html .= '</a>';
    $html .= '</div>';
    $html .= '</article>';

    return $html;
}

/**
 * The complete Blog listing body: lead article, article grid, empty states
 * and real pagination.
 *
 * $lead is null when there are no matching published posts at all (an empty
 * category, or no posts published yet) — the whole section then renders one
 * empty state instead of a broken "lead plus grid" layout.
 *
 * $pagination: ['current' => int, 'total' => int, 'query' => array] — passed
 * straight through to the shared pagination() component, which itself
 * renders nothing for a single page.
 *
 * $boost loads pagination and category pills through HTMX (views/frontend/
 * blog.php supplies the hx-target/hx-select they inherit).
 */
function blog_listing_page(
    ?array $lead,
    array $posts,
    int $totalPosts,
    string $activeCategory,
    string $categoryLabel,
    array $pagination,
    bool $boost = false
): string
{
    $html = '';

    if ($lead === null) {
        $html .= '<section class="bg-white py-14 sm:py-16">';
        $html .= '<div class="container">';
        $html .= empty_state(
            $activeCategory !== '' ? t('blog.empty.titleCategory') : t('blog.empty.title'),
            $activeCategory !== '' ? t('blog.empty.textCategory') : t('blog.empty.text'),
            '<a href="' . e(blog_category_url('')) . '" class="btn-primary btn-sm">' . e(t('blog.readWhole')) . '</a>',
            'journal'
        );
        $html .= '</div>';
        $html .= '</section>';
    } else {
        $html .= '<section class="bg-white py-14 sm:py-16">';
        $html .= '<div class="container">';
        $html .= blog_lead($lead, $boost);
        $html .= '</div>';
        $html .= '</section>';

        $html .= '<section class="bg-surface-soft py-14 sm:py-16 lg:py-20">';
        $html .= '<div class="container">';
        $html .= '<div class="flex flex-col gap-5 sm:flex-row sm:items-end sm:justify-between">';
        $html .= section_head([
            'eyebrow' => $activeCategory !== '' ? $categoryLabel : t('blog.more.eyebrow'),
            'title'   => $activeCategory !== ''
                ? t('blog.more.catTitle', ['category' => $categoryLabel])
                : t('blog.more.title'),
        ]);
        $html .= '<p class="text-meta text-ink-muted">';
        $html .= e(tn('count.articles', $totalPosts)) . ($activeCategory !== '' ? e(t('blog.count.inCategory')) : '');
        $html .= '</p>';
        $html .= '</div>';

        if (!empty($posts)) {
            $html .= '<ul class="mt-10 grid gap-5 sm:grid-cols-2 lg:grid-cols-3 lg:gap-6">';
            foreach ($posts as $i => $p) {
                $html .= '<li data-reveal data-reveal-delay="' . (($i % 3) * 60) . '">' . blog_card($p, ['boost' => $boost]) . '</li>';
            }
            $html .= '</ul>';
        } elseif ((int) ($pagination['total'] ?? 1) <= 1) {
            $html .= '<p class="mt-8 flex flex-wrap items-center gap-3 text-body text-ink-muted">';
            $html .= e(t('blog.onlyOne'));
            $html .= '<a href="' . e(blog_category_url('')) . '" class="btn-link">' . e(t('blog.readWhole')) . '</a>';
            $html .= '</p>';
        }

        $html .= pagination([
            'current' => $pagination['current'] ?? 1,
            'total'   => $pagination['total'] ?? 1,
            'base'    => $pagination['base'] ?? blog_category_url(''),
            'query'   => $pagination['query'] ?? [],
            'label'   => t('blog.pages.label'),
            'boost'   => $boost,
        ]);
        $html .= '</div>';
        $html .= '</section>';
    }

    return $html;
}

/**
 * The Blog Post article header — breadcrumb, category, title, excerpt and
 * meta row over the post cover image, falling back to the post image.
 *
 * The controller resolves and resizes the selected source to the 1920 x 640
 * dimensions declared below. $boost loads the category pills through HTMX.
 */
function blog_article_header(array $post, bool $boost = false): string
{
    $banner = $post['banner'] ?? [];
    $image = isset($post['header_image']) ? (string) $post['header_image'] : '';
    $hasImage = $image !== '';
    $headingStyle = text_gradient_style($banner['heading_colors'][0] ?? null, $banner['heading_colors'][1] ?? null);
    $textStyle = text_gradient_style($banner['text_colors'][0] ?? null, $banner['text_colors'][1] ?? null);

    $html = '<section class="relative isolate overflow-hidden bg-alam-800">';

    if ($hasImage) {
        $html .= '<img src="' . e($image) . '" alt="" aria-hidden="true"'
            . ' width="1920" height="640" fetchpriority="high" decoding="async"'
            . ' class="absolute inset-0 -z-10 h-full w-full object-cover opacity-60">'
            . '<div class="absolute inset-0 -z-10 bg-gradient-to-r from-alam-900/85 via-alam-900/70 to-alam-800/40" aria-hidden="true"></div>';
    } else {
        $html .= '<div class="absolute inset-x-0 bottom-0 h-px bg-white/15" aria-hidden="true"></div>';
    }

    $html .= '<div class="container">';
    $html .= '<div class="max-w-3xl py-14 sm:py-20 lg:py-24">';
    $html .= breadcrumb([
        ['label' => managed_page_menu_label(1), 'href' => site_base_url(current_locale() . '/')],
        ['label' => managed_page_menu_label(8), 'href' => blog_category_url('')],
        ['label' => $post['category'], 'href' => blog_category_url($post['category_slug'])],
        ['label' => $post['title']],
    ]);

    $html .= '<h1 class="mt-6 text-h1 text-shadow-hero' . ($headingStyle === '' ? ' text-white' : '') . '"'
        . ($headingStyle !== '' ? ' style="' . e($headingStyle) . '"' : '') . '>';
    $html .= e($post['title']);
    $html .= '</h1>';

    if ($post['excerpt'] !== '') {
        $html .= '<p class="mt-4 max-w-2xl text-body' . ($textStyle === '' ? ' text-white/80' : '') . '"'
            . ($textStyle !== '' ? ' style="' . e($textStyle) . '"' : '') . '>';
        $html .= e($post['excerpt']);
        $html .= '</p>';
    }

    $html .= blog_category_pills($post['categories'] ?? [], $boost);

    $html .= '<div class="mt-6 flex items-center justify-between gap-4 border-t border-white/15 pt-5 text-meta text-white/70">';
    $html .= '<span class="flex items-center gap-2">' . icon('calendar', 'icon-sm') . e(site_date($post['date'])) . '</span>';
    $html .= '<span class="flex items-center gap-2">' . icon('clock', 'icon-sm') . e($post['read']) . '</span>';
    $html .= '</div>';
    $html .= '</div>';
    $html .= '</div>';
    $html .= '</section>';

    return $html;
}

/** The managed `blog_text` CKEditor body, rendered as trusted admin HTML. */
function blog_article_body(string $html): string
{
    $html = trim($html);
    if ($html === '') {
        return '';
    }

    return '<div class="page-contents">' . $html . '</div>';
}

/**
 * The "Keep reading" related-post grid. Renders nothing when $posts is empty.
 * $boost loads the cards' links through HTMX.
 */
function blog_related_grid(array $posts, bool $boost = false): string
{
    if (empty($posts)) {
        return '';
    }

    $html = '<ul class="mt-10 grid gap-5 sm:grid-cols-2 lg:grid-cols-3 lg:gap-6">';
    foreach ($posts as $p) {
        $html .= '<li>' . blog_card($p, ['clamp' => 2, 'boost' => $boost]) . '</li>';
    }
    $html .= '</ul>';

    return $html;
}
