<?php

defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Render the CMS-backed Tour Guides listing and its database-backed language
 * filter. Mirrors experience_listing_page() — the caller supplies fully
 * prepared data, so this partial only renders markup.
 */
function guide_listing_page(
    array $section,
    array $guides,
    array $languages
): string
{
    $head = isset($section['head']) && is_array($section['head'])
        ? $section['head']
        : array();
    $hasIntroduction = !empty($head['eyebrow'])
        || !empty($head['title'])
        || !empty($section['contents_html']);
    $total = count($guides);

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
    $html .= '<div class="no-scrollbar -mx-1 flex gap-2 overflow-x-auto px-1 pb-1 lg:flex-wrap lg:overflow-visible" role="group" aria-label="' . e(t('guides.filter.label')) . '" data-guide-filter>';

    // data-filter carries the language ID, never the label, so the
    // filter keeps working when the chip text is translated.
    $html .= '<button type="button" class="filter-chip" data-filter="all" aria-pressed="true">';
    $html .= e(t('guides.filter.all'));
    $html .= '<span class="filter-chip__count">' . e(number($total)) . '</span>';
    $html .= '</button>';

    foreach ($languages as $language) {
        $languageId = isset($language['id']) ? (string) $language['id'] : '';
        if ($languageId === '') {
            continue;
        }
        $languageCount = 0;
        foreach ($guides as $guide) {
            if (in_array($languageId, $guide['language_ids'] ?? array(), true)) {
                $languageCount++;
            }
        }

        $html .= '<button type="button" class="filter-chip" data-filter="' . e($languageId) . '" aria-pressed="false">';
        $html .= e($language['label'] ?? '');
        $html .= '<span class="filter-chip__count">' . e(number($languageCount)) . '</span>';
        $html .= '</button>';
    }

    $html .= '</div>';
    $html .= '</div>';
    $html .= '</section>';

    $html .= '<section class="bg-surface-soft py-6 sm:py-8 lg:py-10">';
    $html .= '<div class="container">';
    $html .= '<p class="text-meta font-semibold text-ink" role="status" aria-live="polite">';
    $html .= '<span data-guide-count>' . e(tn('count.guides', $total)) . '</span>';
    $html .= '</p>';

    if (!empty($guides)) {
        $html .= '<ul class="mt-4 grid gap-5 lg:grid-cols-2 lg:gap-6">';
        foreach ($guides as $guide) {
            $html .= '<li>' . guide_card($guide, ['layout' => 'list', 'filterable' => true]) . '</li>';
        }
        $html .= '</ul>';
    }

    $html .= '<div class="mt-6' . (!empty($guides) ? ' hidden' : '') . '" data-guide-empty>';
    $html .= empty_state(
        t('guides.empty.title'),
        t('guides.empty.text'),
        '<a href="' . e(url('contact.php')) . '" class="btn-primary btn-sm">' . e(t('cta.contactUsShort')) . '</a>',
        'guide'
    );
    $html .= '</div>';

    $html .= '</div>';
    $html .= '</section>';

    $html .= '</div>';

    return $html;
}
