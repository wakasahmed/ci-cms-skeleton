<?php
/**
 * Holding page shown to the public while Website Settings > Website Under
 * Construction is "Yes". A standalone document on purpose: the shared header
 * would link to pages that are not available yet. Signed-in administrators
 * never see this page; they browse the real site.
 */

require_once __DIR__ . '/functions.php';

$biz    = business();
$locale = current_locale();
$title  = t('maintenance.meta.title') . ' | ' . $biz['name'];
?><!doctype html>
<html lang="<?php echo e(locale_tag($locale)) ?>" dir="<?php echo e(locale_dir($locale)) ?>" data-locale="<?php echo e($locale) ?>">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title><?php echo e($title) ?></title>
  <meta name="description" content="<?php echo e(t('maintenance.meta.description')) ?>">
  <meta name="robots" content="noindex, nofollow">
  <meta name="theme-color" content="#63569B">
  <link rel="icon" href="<?php echo e($biz['favicon']) ?>" type="image/png">

  <link rel="stylesheet" href="<?php echo e(asset('vendor/fonts/plus-jakarta-sans/plus-jakarta-sans.css')) ?>">
  <?php if (locale_is_rtl($locale)): ?>
  <link rel="stylesheet" href="<?php echo e(asset('vendor/fonts/ibm-plex-sans-arabic/ibm-plex-sans-arabic.css')) ?>">
  <?php endif; ?>
  <link rel="stylesheet" href="<?php echo e(asset('css/app.css')) ?>">
</head>
<body class="locale-<?php echo e($locale) ?>">

<main class="relative isolate flex min-h-screen items-center overflow-hidden bg-alam-800">
  <div class="pattern-grid absolute inset-0 -z-10 opacity-20" aria-hidden="true"></div>
  <div class="absolute inset-0 -z-10 bg-gradient-to-r from-alam-900/80 via-alam-800/60 to-alam-700/25 rtl:bg-gradient-to-l" aria-hidden="true"></div>

  <div class="container">
    <div class="mx-auto max-w-2xl py-14 text-center sm:py-20">
      <?php if ($biz['logo_white'] !== ''): ?>
        <img src="<?php echo e($biz['logo_white']) ?>" alt="<?php echo e($biz['name']) ?>" class="mx-auto mb-10 h-14 w-auto">
      <?php endif; ?>

      <p class="eyebrow mt-0 text-white/70"><?php echo e(t('maintenance.eyebrow')) ?></p>
      <h1 class="mt-4 text-h1 text-white"><?php echo e(t('maintenance.title')) ?></h1>
      <p class="mt-4 text-body sm:text-base text-white/75"><?php echo e(t('maintenance.text')) ?></p>

      <div class="mt-8 flex flex-wrap items-center justify-center gap-3">
        <?php if ($biz['email'] !== ''): ?>
          <a href="mailto:<?php echo e($biz['email']) ?>" class="btn-primary btn-lg"><?php echo e(t('maintenance.cta.email')) ?></a>
        <?php endif; ?>
        <?php if ($biz['phone_href'] !== ''): ?>
          <a href="tel:<?php echo e($biz['phone_href']) ?>" class="btn-ghost-light btn-lg"><?php echo e(t('maintenance.cta.call')) ?></a>
        <?php endif; ?>
      </div>

      <p class="mt-10">
        <a href="<?php echo e(base_url(other_locale())) ?>" hreflang="<?php echo e(locale_tag(other_locale())) ?>" lang="<?php echo e(locale_tag(other_locale())) ?>" class="text-white/75 underline hover:text-white"><?php echo e(t('maintenance.switch')) ?></a>
      </p>
    </div>
  </div>
</main>

</body>
</html>
