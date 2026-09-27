<?php

require_once __DIR__ . '/functions.php';

require_once __DIR__ . '/header.php';

$this->load->view('frontend/slider', [
    'slideRows' => $heroSlides,
    'heroSearch' => $heroSearch,
]);
require_once __DIR__ . '/inc/page-content.php';

// Each Manage > Web Page Sections entry, rendered to a string (see the
// home_render_*_section() functions in inc/home.php). Echoed below in the
// administrator's own `sort_order` — reordering sections in Manage reorders
// the live page, and disabling one there removes it from the page entirely.
$sectionsHtml = [
    'tours' => home_render_tours_section($featuredTours, $sections['tours'] ?? []),
    'discover_madinah' => home_render_discover_madinah_section($sections['discover_madinah'] ?? []),
    'experiences' => home_render_experiences_section($featuredExperiences, $sections['experiences'] ?? []),
    'why_alam_almunawara' => home_render_why_section($sections['why_alam_almunawara'] ?? []),
    'meet_the_guides' => home_render_guides_section($guides, $sections['meet_the_guides'] ?? []),
    'how_booking_works' => home_render_booking_steps_section($sections['how_booking_works'] ?? []),
    'customer_reviews' => home_render_reviews_section($testimonials, $sections['customer_reviews'] ?? []),
    'faqs' => home_render_faqs_section($homeFaqs, $sections['faqs'] ?? []),
    'blogs' => home_render_blogs_section($posts, $sections['blogs'] ?? []),
];
?>

<?php if (empty($heroSlides)): ?>
<!-- No hero slides configured (Manage > Pages > Home > Slider) — a plain,
     fixed-text page heading stands in so the page still has one <h1>. -->
<section class="bg-white pt-12 pb-2 sm:pt-16">
  <div class="container">
    <?php echo section_head(['title' => t('home.meta.title'), 'tag' => 'h1']) ?>
  </div>
</section>
<?php endif; ?>

<?php foreach ($sectionOrder as $sectionKey): ?>
  <?php echo $sectionsHtml[$sectionKey] ?? '' ?>
<?php endforeach; ?>

<?php if ($cta !== null): ?>
  <?php echo cta_band([
      'bg'        => 'bg-surface-tint',
      'eyebrow'   => $cta['eyebrow'],
      'title'     => $cta['title'],
      'text'      => $cta['text'],
      'primary'   => $cta['primary'],
      'secondary' => $cta['secondary'],
  ]) ?>
<?php endif; ?>

<?php require_once __DIR__ . '/footer.php'; ?>
