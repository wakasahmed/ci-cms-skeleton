<?php

require_once __DIR__ . '/functions.php';
require_once __DIR__ . '/header.php';

if (!empty($config['banner'])) {
    require_once __DIR__ . '/banner.php';
} else {
    /* A page always needs one <h1>. With the banner switched off in Manage >
       Web Pages the heading is kept for search engines and screen readers. */
    echo '<h1 class="sr-only">' . e((string) ($config['page_heading'] ?? $config['page_title'] ?? '')) . '</h1>';
}

$managedLegalContent = true;
$sections = $legalSections;

require_once __DIR__ . '/inc/legal-body.php';
require_once __DIR__ . '/footer.php';
