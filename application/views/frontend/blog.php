<?php

require_once __DIR__ . '/functions.php';
require_once __DIR__ . '/header.php';
?>

<?php /* The category bar, category pills and pagination navigate through HTMX
         (js/htmx-init.js); <body> carries the swap settings they inherit. */ ?>
<?php echo blog_category_nav($nav['items'], ['active' => $activeCategory, 'boost' => true]) ?>

<?php if (!empty($config['banner'])): ?>
  <?php require_once __DIR__ . '/banner.php'; ?>
<?php endif; ?>

<?php require_once __DIR__ . '/inc/page-content.php'; ?>

<?php echo blog_listing_page(
    $lead,
    $posts,
    $totalPosts,
    $activeCategory,
    $categoryLabel,
    $pagination,
    true
) ?>

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
