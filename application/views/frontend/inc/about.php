<?php

defined('BASEPATH') OR exit('No direct script access allowed');

/** Whether a prepared About section contains administrator-managed content. */
function about_section_has_content(array $section): bool
{
    foreach (array('eyebrow', 'title', 'text') as $field) {
        if (trim((string) ($section['head'][$field] ?? '')) !== '') {
            return true;
        }
    }

    if (
        trim((string) ($section['contents_html'] ?? '')) !== ''
        || trim((string) ($section['image'] ?? '')) !== ''
        || !empty($section['card'])
        || !empty($section['cards'])
    ) {
        return true;
    }

    foreach (($section['buttons'] ?? array()) as $button) {
        if (
            trim((string) ($button['text'] ?? '')) !== ''
            && trim((string) ($button['url'] ?? '')) !== ''
        ) {
            return true;
        }
    }

    return false;
}

/** Render the About introduction and its optional managed image/card. */
function about_introduction(array $section): string
{
    if (!about_section_has_content($section)) {
        return '';
    }

    $image = trim((string) ($section['image'] ?? ''));
    $card = isset($section['card']) && is_array($section['card'])
        ? $section['card']
        : array();
    $hasVisual = $image !== '' || !empty($card);

    $html = '<section class="' . e($section['background'] ?? 'bg-white') . ' py-16 sm:py-20 lg:py-24">';
    $html .= '<div class="container">';
    $html .= '<div class="grid gap-10 ' . ($hasVisual ? 'lg:grid-cols-[1.05fr_0.95fr] lg:gap-16' : '') . '">';

    $html .= '<div>';
    $html .= section_head(array(
        'eyebrow' => $section['head']['eyebrow'] ?? '',
        'title' => $section['head']['title'] ?? '',
    ));
    if (!empty($section['contents_html'])) {
        $html .= '<div class="page-contents mt-5 max-w-prose">' . $section['contents_html'] . '</div>';
    }
    $html .= '</div>';

    if ($hasVisual) {
        $html .= '<div class="relative' . ($image !== '' && !empty($card) ? ' pb-10' : '') . '">';

        if ($image !== '') {
            $html .= '<div class="arch-soft overflow-hidden bg-alam-100">';
            $html .= '<img src="' . e($image) . '" alt="" width="1200" height="900"'
                . ' loading="lazy" decoding="async" class="aspect-[4/5] w-full object-cover">';
            $html .= '</div>';
        }

        if (!empty($card)) {
            $html .= '<div class="' . ($image !== '' ? 'absolute -bottom-5 inset-x-5 sm:inset-x-8' : '') . ' rounded-card border border-line bg-white p-5 shadow-card">';

            if (!empty($card['title'])) {
                $html .= '<p class="flex items-center gap-2 text-eyebrow font-bold uppercase text-alam-600">';
                if (!empty($card['icon'])) {
                    $html .= icon($card['icon'], 'icon-sm');
                }
                $html .= e($card['title']);
                $html .= '</p>';
            }

            if (!empty($card['text'])) {
                $html .= '<p class="mt-2 text-body-sm text-ink-muted">' . e($card['text']) . '</p>';
            }

            $html .= '</div>';
        }

        $html .= '</div>';
    }

    $html .= '</div>';
    $html .= '</div>';
    $html .= '</section>';

    return $html;
}

/** Render the managed approach cards. */
function about_approach(array $section): string
{
    if (!about_section_has_content($section)) {
        return '';
    }

    $html = '<section class="' . e($section['background'] ?? 'bg-surface-tint') . ' py-16 sm:py-20 lg:py-24">';
    $html .= '<div class="container">';
    $html .= section_head(array_merge($section['head'] ?? array(), array('align' => 'center')));

    if (!empty($section['cards'])) {
        $html .= '<ol class="mt-12 grid gap-5 md:grid-cols-3">';
        foreach ($section['cards'] as $index => $card) {
            $html .= '<li class="card p-6" data-reveal data-reveal-delay="' . ((int) $index * 60) . '">';

            if (!empty($card['icon'])) {
                $html .= '<span class="grid h-11 w-11 place-items-center rounded-control bg-alam-500/10 text-alam-600">';
                $html .= icon($card['icon'], 'icon-lg');
                $html .= '</span>';
            }

            if (!empty($card['title'])) {
                $html .= '<h3 class="mt-4 text-card-title font-bold text-ink">' . e($card['title']) . '</h3>';
            }

            if (!empty($card['text'])) {
                $html .= '<p class="mt-2.5 text-body-sm text-ink-muted">' . e($card['text']) . '</p>';
            }

            $html .= '</li>';
        }
        $html .= '</ol>';
    }

    $html .= '</div>';
    $html .= '</section>';

    return $html;
}

/** Render the managed local-knowledge section and reason list. */
function about_knowledge(array $section): string
{
    if (!about_section_has_content($section)) {
        return '';
    }

    $image = trim((string) ($section['image'] ?? ''));

    $html = '<section class="' . e($section['background'] ?? 'bg-white') . ' py-16 sm:py-20 lg:py-24">';
    $html .= '<div class="container">';
    $html .= '<div class="grid items-center gap-10 ' . ($image !== '' ? 'lg:grid-cols-2 lg:gap-16' : '') . '">';

    if ($image !== '') {
        $html .= '<div class="order-2 lg:order-1">';
        $html .= '<div class="arch-soft overflow-hidden bg-alam-100">';
        $html .= '<img src="' . e($image) . '" alt="" width="900" height="1100"'
            . ' loading="lazy" decoding="async" class="aspect-[4/5] w-full object-cover">';
        $html .= '</div>';
        $html .= '</div>';
    }

    $html .= '<div class="order-1 lg:order-2">';
    $html .= section_head(array(
        'eyebrow' => $section['head']['eyebrow'] ?? '',
        'title' => $section['head']['title'] ?? '',
    ));
    if (!empty($section['contents_html'])) {
        $html .= '<div class="page-contents mt-5 max-w-prose">' . $section['contents_html'] . '</div>';
    }

    if (!empty($section['cards'])) {
        $html .= '<ul class="mt-8 space-y-4">';
        foreach ($section['cards'] as $card) {
            $html .= '<li class="flex gap-3.5 border-b border-line pb-4 last:border-b-0">';

            if (!empty($card['icon'])) {
                $html .= '<span class="mt-0.5 grid h-6 w-6 shrink-0 place-items-center rounded-full bg-alam-100 text-alam-700">';
                $html .= icon($card['icon'], 'icon-xs');
                $html .= '</span>';
            }

            $html .= '<div>';
            if (!empty($card['title'])) {
                $html .= '<h3 class="text-body-sm font-bold text-ink">' . e($card['title']) . '</h3>';
            }
            if (!empty($card['text'])) {
                $html .= '<p class="mt-1 text-body-sm text-ink-muted">' . e($card['text']) . '</p>';
            }
            $html .= '</div>';

            $html .= '</li>';
        }
        $html .= '</ul>';
    }

    $html .= '</div>';
    $html .= '</div>';
    $html .= '</div>';
    $html .= '</section>';

    return $html;
}

/** Render the dark managed principles section. */
function about_principles(array $section): string
{
    if (!about_section_has_content($section)) {
        return '';
    }

    $html = '<section class="relative overflow-hidden ' . e($section['background'] ?? 'bg-alam-800') . ' py-16 text-white sm:py-20 lg:py-24">';
    $html .= '<div class="pattern-grid absolute inset-0 opacity-25" aria-hidden="true"></div>';
    $html .= '<div class="container relative">';
    $html .= section_head(array_merge(
        $section['head'] ?? array(),
        array('align' => 'center', 'tone' => 'light')
    ));

    if (!empty($section['cards'])) {
        $html .= '<ul class="mt-12 grid gap-4 sm:grid-cols-2 lg:grid-cols-3">';
        foreach ($section['cards'] as $index => $card) {
            $html .= '<li class="rounded-card border border-white/15 bg-white/[0.06] p-5"'
                . ' data-reveal data-reveal-delay="' . ((int) $index * 50) . '">';

            if (!empty($card['icon'])) {
                $html .= '<span class="grid h-10 w-10 place-items-center rounded-control bg-white/10 text-white">';
                $html .= icon($card['icon'], 'icon-md');
                $html .= '</span>';
            }

            if (!empty($card['title'])) {
                $html .= '<h3 class="mt-4 text-body-sm font-bold text-white">' . e($card['title']) . '</h3>';
            }

            if (!empty($card['text'])) {
                $html .= '<p class="mt-2 text-body-sm text-white/70">' . e($card['text']) . '</p>';
            }

            $html .= '</li>';
        }
        $html .= '</ul>';
    }

    $html .= '</div>';
    $html .= '</section>';

    return $html;
}

/** Render a managed heading/CTA followed by guide cards. */
function about_guides(array $section, array $guides): string
{
    if (!about_section_has_content($section)) {
        return '';
    }

    $html = '<section id="guides" class="scroll-mt-28 ' . e($section['background'] ?? 'bg-white') . ' py-16 sm:py-20 lg:py-24">';
    $html .= '<div class="container">';
    $html .= section_head(array_merge($section['head'] ?? array(), array('align' => 'center')));

    if (!empty($guides)) {
        $html .= '<ul class="mt-12 grid gap-5 sm:grid-cols-2 lg:grid-cols-3 lg:gap-6">';
        foreach ($guides as $index => $guide) {
            $html .= '<li data-reveal data-reveal-delay="' . (((int) $index % 3) * 60) . '">';
            $html .= guide_card($guide);
            $html .= '</li>';
        }
        $html .= '</ul>';
    }

    $button = prepared_section_button($section, 'btn-outline');
    if ($button !== '') {
        $html .= '<div class="mt-10 flex justify-center">' . $button . '</div>';
    }

    $html .= '</div>';
    $html .= '</section>';

    return $html;
}

/** Render managed About copy around reusable experience cards. */
function about_experiences(array $section, array $experiences): string
{
    if (!about_section_has_content($section)) {
        return '';
    }

    return experiences_teaser(array(
        'items' => $experiences,
        'count' => count($experiences),
        'columns' => 3,
        'bg' => $section['background'] ?? 'bg-surface-tint',
        'eyebrow' => $section['head']['eyebrow'] ?? '',
        'title' => $section['head']['title'] ?? '',
        'text' => $section['head']['text'] ?? '',
        'cta_html' => prepared_section_button($section, 'btn-outline shrink-0'),
    ));
}

/** Render managed About copy around reusable tour cards. */
function about_tours(array $section, array $tours): string
{
    if (!about_section_has_content($section)) {
        return '';
    }

    $html = '<section class="' . e($section['background'] ?? 'bg-white') . ' py-16 sm:py-20 lg:py-24">';
    $html .= '<div class="container">';
    $html .= '<div class="flex flex-col gap-6 sm:flex-row sm:items-end sm:justify-between">';
    $html .= section_head($section['head'] ?? array());
    $html .= prepared_section_button($section, 'btn-outline shrink-0');
    $html .= '</div>';

    if (!empty($tours)) {
        $html .= '<ul class="mt-10 grid gap-5 sm:grid-cols-2 lg:mt-12 lg:grid-cols-3 lg:gap-6">';
        foreach ($tours as $index => $tour) {
            $html .= '<li data-reveal data-reveal-delay="' . (((int) $index % 3) * 60) . '">';
            $html .= tour_card($tour);
            $html .= '</li>';
        }
        $html .= '</ul>';
    }

    $html .= '</div>';
    $html .= '</section>';

    return $html;
}
