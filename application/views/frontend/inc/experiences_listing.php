<?php

defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Render the CMS-backed Experiences listing and its database-backed category
 * filter. Mirrors tour_listing_page() — the caller supplies fully prepared
 * data, so this partial only renders markup.
 */
function experience_listing_page(
    array $section,
    array $experiences,
    array $categories
): string
{
    $head = isset($section['head']) && is_array($section['head'])
        ? $section['head']
        : array();
    $hasIntroduction = !empty($head['eyebrow'])
        || !empty($head['title'])
        || !empty($section['contents_html']);
    $total = count($experiences);

    $html = '';

    if ($hasIntroduction) {
        $html .= '<section class="bg-white pt-8 sm:pt-10">';
        $html .= '<div class="container">';

        if (!empty($head['eyebrow'])) {
            $html .= '<p class="eyebrow">' . e($head['eyebrow']) . '</p>';
        }

        if (!empty($head['title'])) {
            $html .= '<h2 class="' . (!empty($head['eyebrow']) ? 'mt-3.5 ' : '') . 'text-card-title font-bold text-ink">';
            $html .= e($head['title']);
            $html .= '</h2>';
        }

        if (!empty($section['contents_html'])) {
            $html .= '<div class="page-contents mt-1 max-w-2xl text-meta text-ink-muted">';
            $html .= $section['contents_html'];
            $html .= '</div>';
        }

        $html .= '</div>';
        $html .= '</section>';
    }

    $html .= '<div>';

    $html .= '<section class="sticky top-[var(--frontend-header-h)] z-30 bg-white py-4" data-sticky-bar>';
    $html .= '<div class="container">';
    $html .= '<div class="no-scrollbar -mx-1 flex gap-2 overflow-x-auto px-1 pb-1 lg:flex-wrap lg:overflow-visible" role="group" aria-label="' . e(t('experiences.filter.label')) . '" data-experience-filter>';

    // data-filter carries the category ID, never the label, so the
    // filter keeps working when the chip text is translated.
    $html .= '<button type="button" class="filter-chip" data-filter="all" aria-pressed="true">';
    $html .= e(t('experiences.filter.all'));
    $html .= '<span class="filter-chip__count">' . e(number($total)) . '</span>';
    $html .= '</button>';

    foreach ($categories as $category) {
        $categoryId = isset($category['id']) ? (string) $category['id'] : '';
        if ($categoryId === '') {
            continue;
        }
        $categoryCount = 0;
        foreach ($experiences as $experience) {
            if (in_array($categoryId, $experience['category_ids'] ?? array(), true)) {
                $categoryCount++;
            }
        }

        $html .= '<button type="button" class="filter-chip" data-filter="' . e($categoryId) . '" aria-pressed="false">';
        $html .= e($category['label'] ?? '');
        $html .= '<span class="filter-chip__count">' . e(number($categoryCount)) . '</span>';
        $html .= '</button>';
    }

    $html .= '</div>';
    $html .= '</div>';
    $html .= '</section>';

    $html .= '<section class="bg-surface-soft py-6 sm:py-8 lg:py-10">';
    $html .= '<div class="container">';
    $html .= '<p class="text-meta font-semibold text-ink" role="status" aria-live="polite">';
    $html .= '<span data-experience-count>' . e(tn('count.experiences', $total)) . '</span>';
    $html .= '</p>';

    if (!empty($experiences)) {
        $html .= '<ul class="mt-4 grid gap-5 sm:grid-cols-2 lg:grid-cols-3 lg:gap-6">';
        foreach ($experiences as $i => $experience) {
            $html .= '<li>' . experience_card($experience, ['eager' => $i < 3]) . '</li>';
        }
        $html .= '</ul>';
    }

    $html .= '<div class="mt-6' . (!empty($experiences) ? ' hidden' : '') . '" data-experience-empty>';
    $html .= empty_state(
        t('experiences.empty.title'),
        t('experiences.empty.text'),
        '<a href="' . e(url('contact.php')) . '" class="btn-primary btn-sm">' . e(t('cta.contactUsShort')) . '</a>',
        'compass'
    );
    $html .= '</div>';

    $html .= '</div>';
    $html .= '</section>';

    $html .= '</div>';

    return $html;
}
