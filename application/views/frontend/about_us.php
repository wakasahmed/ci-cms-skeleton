<?php

require_once __DIR__ . '/functions.php';
require_once __DIR__ . '/header.php';

if (!empty($config['banner'])) {
    require_once __DIR__ . '/banner.php';
}

require_once __DIR__ . '/inc/page-content.php';

$sectionsHtml = array(
    'alam_almunawara' => about_introduction($sections['alam_almunawara'] ?? array()),
    'our_approach' => about_approach($sections['our_approach'] ?? array()),
    'knowledge_matters' => about_knowledge($sections['knowledge_matters'] ?? array()),
    'guiding_principles' => about_principles($sections['guiding_principles'] ?? array()),
    'meet_the_guides' => about_guides(
        $sections['meet_the_guides'] ?? array(),
        $guides
    ),
    'experiences' => about_experiences(
        $sections['experiences'] ?? array(),
        $featuredExperiences
    ),
    'tours' => about_tours(
        $sections['tours'] ?? array(),
        $featuredTours
    ),
);

foreach ($sectionOrder as $sectionKey) {
    echo $sectionsHtml[$sectionKey] ?? '';
}

require_once __DIR__ . '/footer.php';
