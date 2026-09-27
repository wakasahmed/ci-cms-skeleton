<?php

require_once __DIR__ . '/functions.php';
require_once __DIR__ . '/header.php';
?>

<section class="relative isolate overflow-hidden bg-alam-800">
  <div class="pattern-grid absolute inset-0 -z-10 opacity-20" aria-hidden="true"></div>
  <div class="absolute inset-0 -z-10 bg-gradient-to-r from-alam-900/80 via-alam-800/60 to-alam-700/25 rtl:bg-gradient-to-l" aria-hidden="true"></div>

  <div class="container">
    <div class="mx-auto max-w-2xl py-14 text-center sm:py-20 lg:py-24">
      <p class="eyebrow mt-0 text-white/70"><?php echo e(t('error404.eyebrow')) ?></p>
      <p class="mt-3.5 text-display text-white">404</p>
      <h1 class="mt-4 text-h1 text-white"><?php echo e(t('error404.title')) ?></h1>
      <p class="mt-4 text-body sm:text-base text-white/75"><?php echo e(t('error404.text')) ?></p>

      <div class="mt-8 flex flex-wrap items-center justify-center gap-3">
        <a href="<?php echo e(url('index.php')) ?>" class="btn-primary btn-lg">
          <?php echo e(t('error404.cta.home')) ?>
          <?php echo icon('arrow-right', 'icon-sm icon-flip') ?>
        </a>
        <a href="<?php echo e(url('tours.php')) ?>" class="btn-ghost-light btn-lg">
          <?php echo e(t('error404.cta.tours')) ?>
        </a>
      </div>
    </div>
  </div>

  <div class="absolute inset-x-0 bottom-0 h-px bg-white/15" aria-hidden="true"></div>
</section>

<section class="bg-white py-14 sm:py-16 lg:py-20">
  <div class="container">
    <div class="mx-auto max-w-2xl text-center">
      <p class="text-eyebrow font-bold uppercase text-ink-soft"><?php echo e(t('error404.links.heading')) ?></p>
      <ul class="mt-4 flex flex-wrap items-center justify-center gap-2">
        <li><a href="<?php echo e(url('index.php')) ?>" class="toc-link toc-link--chip"><?php echo e(t('nav.home')) ?></a></li>
        <li><a href="<?php echo e(url('tours.php')) ?>" class="toc-link toc-link--chip"><?php echo e(t('nav.tours')) ?></a></li>
        <li><a href="<?php echo e(url('experiences.php')) ?>" class="toc-link toc-link--chip"><?php echo e(t('nav.experiences')) ?></a></li>
        <li><a href="<?php echo e(url('faqs.php')) ?>" class="toc-link toc-link--chip"><?php echo e(t('nav.faqs')) ?></a></li>
        <li><a href="<?php echo e(url('contact.php')) ?>" class="toc-link toc-link--chip"><?php echo e(t('nav.contact')) ?></a></li>
      </ul>
    </div>
  </div>
</section>

<?php require_once __DIR__ . '/footer.php'; ?>
