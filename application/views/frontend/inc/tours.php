<?php

defined('BASEPATH') OR exit('No direct script access allowed');

/** Render the CMS-backed Tours listing and its database-backed filters. */
function tour_listing_page(
    array $section,
    array $tours,
    array $categories,
    array $capacities,
    array $languages
): string
{
    $head = isset($section['head']) && is_array($section['head'])
        ? $section['head']
        : array();
    $hasIntroduction = !empty($head['eyebrow'])
        || !empty($head['title'])
        || !empty($section['contents_html']);

    $html = '';

    if ($hasIntroduction) {
        $html .= '<section class="bg-white pt-8 sm:pt-10">';
        $html .= '<div class="container">';
        $html .= '<div class="w-full">';

        if (!empty($head['eyebrow'])) {
            $html .= '<p class="eyebrow">' . e($head['eyebrow']) . '</p>';
        }

        if (!empty($head['title'])) {
            $html .= '<h2 class="' . (!empty($head['eyebrow']) ? 'mt-2.5 ' : '') . 'text-card-title  text-h3 text-ink">';
            $html .= e($head['title']);
            $html .= '</h2>';
        }

        if (!empty($section['contents_html'])) {
            $html .= '<div class="page-contents mt-2 text-body-lg text-ink-muted">';
            $html .= $section['contents_html'];
            $html .= '</div>';
        }

        $html .= '</div></div>';
        $html .= '</section>';
    }

    $html .= '<div>';

    $html .= '<section class="sticky top-[var(--frontend-header-h)] z-30 bg-white py-4" data-sticky-bar>';
    $html .= '<div class="container">';
    $html .= '<form data-tour-filter aria-label="' . e(t('tours.filter.label')) . '">';

    $html .= '<button type="button" class="btn-outline btn-sm w-full justify-between sm:hidden" data-filter-toggle aria-expanded="true" aria-controls="tour-filters">';
    $html .= '<span class="flex items-center gap-2">';
    $html .= icon('filter', 'icon-xs text-alam-500') . e(t('tours.filter.button'));
    $html .= '</span>';
    $html .= '<span class="flex items-center gap-2">';
    $html .= '<span class="font-semibold text-alam-700" data-filter-toggle-count hidden></span>';
    $html .= icon(
        'chevron-down',
        'icon-xs transition-transform duration-200',
        array('data-filter-chevron' => '')
    );
    $html .= '</span>';
    $html .= '</button>';

    $html .= '<div id="tour-filters" class="mt-3 grid gap-3 sm:mt-0 sm:grid sm:grid-cols-2 lg:grid-cols-[1fr_1fr_1fr_auto] lg:items-end lg:gap-4" data-tour-fields>';

    $html .= '<div>';
    $html .= '<label for="filter-category" class="field-label flex items-center gap-2">';
    $html .= icon('compass', 'icon-xs text-alam-500') . e(t('tours.filter.category'));
    $html .= '</label>';
    $html .= '<select id="filter-category" name="category" class="select js-select2">';
    $html .= '<option value="">' . e(t('tours.filter.allCategories')) . '</option>';
    foreach ($categories as $category) {
        $html .= '<option value="' . e($category['id'] ?? '') . '">';
        $html .= e($category['label'] ?? '');
        $html .= '</option>';
    }
    $html .= '</select>';
    $html .= '</div>';

    $html .= '<div>';
    $html .= '<label for="filter-capacity" class="field-label flex items-center gap-2">';
    $html .= icon('users', 'icon-xs text-alam-500') . e(t('tours.filter.capacity'));
    $html .= '</label>';
    $html .= '<select id="filter-capacity" name="capacity" class="select js-select2">';
    $html .= '<option value="">' . e(t('tours.filter.anyCapacity')) . '</option>';
    foreach ($capacities as $capacity) {
        $html .= '<option value="' . (int) $capacity . '">';
        $html .= e(tn('count.upTo', (int) $capacity));
        $html .= '</option>';
    }
    $html .= '</select>';
    $html .= '</div>';

    $html .= '<div>';
    $html .= '<label for="filter-language" class="field-label flex items-center gap-2">';
    $html .= icon('language', 'icon-xs text-alam-500') . e(t('tours.filter.language'));
    $html .= '</label>';
    $html .= '<select id="filter-language" name="language" class="select js-select2">';
    $html .= '<option value="">' . e(t('tours.filter.anyLanguage')) . '</option>';
    foreach ($languages as $language) {
        $html .= '<option value="' . e($language['id'] ?? '') . '">';
        $html .= e($language['label'] ?? '');
        $html .= '</option>';
    }
    $html .= '</select>';
    $html .= '</div>';

    $html .= '<div class="flex sm:col-span-2 lg:col-span-1">';
    $html .= '<button type="reset" class="btn-outline w-full lg:w-auto" data-tour-reset disabled>';
    $html .= icon('reset', 'icon-xs') . e(t('cta.reset'));
    $html .= '</button>';
    $html .= '</div>';

    $html .= '</div>';

    $html .= '</form>';
    $html .= '</div>';
    $html .= '</section>';

    $html .= '<section class="bg-surface-soft py-6 sm:py-8 lg:py-10">';
    $html .= '<div class="container">';
    $html .= '<p class="flex flex-wrap items-baseline gap-x-2.5 text-meta font-semibold text-ink">';
    $html .= '<span data-tour-count>' . e(tn('count.tours', count($tours))) . '</span>';
    $html .= '<span class="text-alam-700" data-tour-active hidden></span>';
    $html .= '</p>';

    if (!empty($tours)) {
        $html .= '<ul class="mt-4 grid gap-5 sm:grid-cols-2 lg:grid-cols-3 lg:gap-6" data-tour-grid>';
        foreach ($tours as $index => $tour) {
            $html .= '<li>' . tour_card($tour, array('eager' => $index < 3)) . '</li>';
        }
        $html .= '</ul>';
    }

    $html .= '<div class="mt-6' . (!empty($tours) ? ' hidden' : '') . '" data-tour-empty>';
    $html .= empty_state(
        t('tours.empty.title'),
        t('tours.empty.text'),
        '<a href="' . e(url('plan-your-trip.php')) . '" class="btn-primary btn-sm">'
            . e(t('cta.planVisitShort'))
            . '</a><a href="' . e(url('contact.php')) . '" class="btn-outline btn-sm">'
            . e(t('cta.contactUsShort'))
            . '</a>'
    );
    $html .= '</div>';

    $html .= '</div>';
    $html .= '</section>';

    $html .= '</div>';

    return $html;
}
