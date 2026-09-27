<?php
/**
 * Inner-page banner.
 *
 * Configure from the calling page before the include:
 *   $config['banner'] = [
 *     'eyebrow' => 'About us',
 *     'title'   => 'Page title',
 *     'text'    => 'Optional supporting line',
 *     'crumbs'  => [['label' => 'Home', 'href' => site_base_url()], ['label' => 'About']],
 *     'image'   => 'images/alam/banners/masjid-quba-evening.webp',  // optional
 *     'overlay' => true,        // optional
 *     'size'    => 'compact',   // optional — see below
 *   ];
 *
 * `size` controls the vertical padding only. The default banner is deliberately
 * tall: on a content page it is the page's opening statement. On a listing page
 * it is 449px of chrome in front of the thing the visitor came to browse, so
 * those pages pass 'compact' and win back ~64px, which is the difference
 * between the first row of cards being on or off the fold on a 768px laptop.
 */


$b = ($config['banner'] ?? []) + [
    'eyebrow' => '',
    'title'   => '',
    'text'    => '',
    'crumbs'  => [],
    'image'   => '',
    'overlay' => true,
    'size'    => 'default',
];

$bannerPad = $b['size'] === 'compact'
    ? 'py-12 sm:py-16 lg:py-16'
    : 'py-14 sm:py-20 lg:py-24';
$bannerImageAbsolute = $b['image'] !== '' && is_absolute_url($b['image']);
$backgroundStyle = background_gradient_style(
    $b['background_colors'][0] ?? null,
    $b['background_colors'][1] ?? null
);
$eyebrowStyle = text_gradient_style(
    $b['eyebrow_colors'][0] ?? null,
    $b['eyebrow_colors'][1] ?? null
);
$headingStyle = text_gradient_style(
    $b['heading_colors'][0] ?? null,
    $b['heading_colors'][1] ?? null
);
$textStyle = text_gradient_style(
    $b['text_colors'][0] ?? null,
    $b['text_colors'][1] ?? null
);

?>
<section class="relative isolate overflow-hidden bg-alam-800"<?php echo $backgroundStyle !== '' ? ' style="' . e($backgroundStyle) . '"' : '' ?>>
  <?php if ($b['image'] !== ''): ?>
  <img src="<?php echo e($bannerImageAbsolute ? $b['image'] : site_base_url($b['image'])) ?>"
       <?php if (!$bannerImageAbsolute): ?>srcset="<?php echo e(srcset($b['image'], '-1280', 1280, 1920)) ?>"
       sizes="100vw"<?php endif; ?>
       alt="" aria-hidden="true"
       width="1920" height="720" fetchpriority="high" decoding="async"
       class="absolute inset-0 -z-10 h-full w-full object-cover object-center opacity-75">
  <?php endif; ?>
  <?php /* The tint runs from the copy edge outwards, so it follows the reading
           direction. The photograph is not mirrored — a mosque flipped left to
           right is no longer that mosque. */ ?>
  <?php if (!empty($b['overlay'])): ?>
  <div class="absolute inset-0 -z-10 bg-gradient-to-r from-alam-900/80 via-alam-800/60 to-alam-700/25 rtl:bg-gradient-to-l" aria-hidden="true"></div>
  <?php endif; ?>
  <div class="pattern-grid absolute inset-0 -z-10 opacity-20" aria-hidden="true"></div>

  <div class="container">
    <div class="max-w-2xl <?php echo e($bannerPad) ?>">
      <?php if (!empty($b['crumbs'])): ?>
        <?php echo breadcrumb($b['crumbs']) ?>
      <?php endif; ?>

      <?php if ($b['eyebrow'] !== ''): ?>
        <p class="eyebrow mt-6<?php echo $eyebrowStyle === '' ? ' text-white/70' : '' ?>"<?php echo $eyebrowStyle !== '' ? ' style="' . e($eyebrowStyle) . '"' : '' ?>><?php echo e($b['eyebrow']) ?></p>
      <?php endif; ?>

      <?php if ($b['title'] !== ''): ?>
        <h1 class="mt-3.5 text-h1<?php echo $headingStyle === '' ? ' text-white' : '' ?>"<?php echo $headingStyle !== '' ? ' style="' . e($headingStyle) . '"' : '' ?>><?php echo e($b['title']) ?></h1>
      <?php endif; ?>

      <?php if ($b['text'] !== ''): ?>
        <p class="mt-4 max-w-xl text-body sm:text-base<?php echo $textStyle === '' ? ' text-white/75' : '' ?>"<?php echo $textStyle !== '' ? ' style="' . e($textStyle) . '"' : '' ?>><?php echo e($b['text']) ?></p>
      <?php endif; ?>
    </div>
  </div>

  <div class="absolute inset-x-0 bottom-0 h-px bg-white/15" aria-hidden="true"></div>
</section>
