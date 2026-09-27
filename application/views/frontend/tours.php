<?php

require_once __DIR__ . '/functions.php';
require_once __DIR__ . '/header.php';

if (!empty($config['banner'])) {
    require_once __DIR__ . '/banner.php';
}

require_once __DIR__ . '/inc/page-content.php';

echo tour_listing_page(
    $searchSection,
    $tours,
    $filterCategories,
    $filterCapacities,
    $filterLanguages
);

require_once __DIR__ . '/footer.php';
