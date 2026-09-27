<?php
/**
 * Alam Al-Munawara — Arabic content overlay.
 *
 * The English editorial content lives ONCE in the corresponding frontend data
 * sources. This layer supplies only the Arabic text for it, keyed
 * by each record's stable identifier; localize() and localize_list()
 * (inc/i18n.php) merge it over the source record on the way out of the data
 * layer.
 *
 * What is translated                 What is NOT translated
 * ------------------------------     ---------------------------------------
 * titles, names, summaries           ids, slugs, category keys
 * prose, bullets, captions           prices, capacities, surcharges
 * image alt text                     image paths
 * question and answer text           availability days, slots, blocked dates
 * labels a visitor reads             the values a form posts
 *
 * Nothing operational is duplicated: an Arabic tour is the English record with
 * its wording replaced, so a price changed in inc/data.php changes in both
 * locales at once and cannot drift.
 *
 * Positional lists
 * ----------------
 * `highlights`, `journey`, `prepare` and `faqs` are merged
 * element by element, in order, by merge_localized(). The `attraction`
 * slug that links a highlight to the attractions catalogue is deliberately
 * absent from the overlay — it survives from the source record. If those lists
 * are reordered in inc/data.php, reorder them here to match.
 *
 * Split by subject
 * ----------------
 * The overlay is large enough that one file would be unreviewable, so it is
 * assembled from content-ar/ — one file per kind of record. Each returns a
 * plain array of groups and they are merged here in a fixed order. Two files
 * must never declare the same group.
 *
 * Place names follow established Arabic usage: أُحُد، الخندق، بدر، قباء،
 * القبلتين، جبل سَلْع، جبل الرماة، المساجد السبعة، متحف دار المدينة.
 */


$groups = [];

foreach ([
    'reference',    // business, vehicles, slots, languages, categories, hero
    'attractions',  // the places a tour stops at
    'tours',        // the nine guided tours
    'guides',       // guide names, roles, biographies, expertise
    'faqs',         // the site-wide FAQ groups
    'experiences',  // experience categories and the experiences themselves
    'blocks',       // photo captions, testimonials, benefits, booking steps
] as $part) {
    $file = __DIR__ . '/content-ar/' . $part . '.php';
    if (!is_file($file)) {
        continue;
    }
    $data = require $file;
    if (is_array($data)) {
        $groups = array_merge($groups, $data);
    }
}

return $groups;
