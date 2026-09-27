<?php

require_once __DIR__ . '/functions.php';
require_once __DIR__ . '/header.php';

if (!empty($config['banner'])) {
    require_once __DIR__ . '/banner.php';
}

require_once __DIR__ . '/inc/page-content.php';
?>

<section class="bg-white py-14 sm:py-16 lg:py-20">
  <div class="container">
    <?php if (empty($faqGroups)): ?>
      <?php echo empty_state(
          t('faqs.empty.title'),
          t('faqs.empty.text'),
          '',
          'question'
      ) ?>
    <?php else: ?>
      <div class="grid gap-8 lg:grid-cols-[248px_minmax(0,1fr)] lg:gap-12">

        <!-- Category rail -->
        <nav class="min-w-0 lg:sticky lg:top-[calc(var(--frontend-header-h)+28px)] lg:self-start"
             aria-label="<?php echo e(t('faqs.categories.label')) ?>" data-toc-nav>
          <p class="text-eyebrow font-bold uppercase text-ink-soft"><?php echo e(t('faqs.categories')) ?></p>
          <ul class="no-scrollbar mt-3 flex gap-2 overflow-x-auto pb-1 lg:mt-4 lg:flex-col lg:gap-1 lg:overflow-visible lg:pb-0">
            <?php foreach ($faqGroups as $gi => $g): ?>
              <li class="shrink-0">
                <a href="#faq-category-<?php echo (int) $g['id'] ?>" class="toc-link toc-link--chip"
                   <?php echo $gi === 0 ? 'aria-current="true"' : '' ?>>
                  <?php echo e($g['title']) ?>
                </a>
              </li>
            <?php endforeach; ?>
          </ul>

          <?php if ($supportCard !== null): ?>
            <?php echo help_card(array_merge($supportCard, ['class' => 'mt-6 hidden lg:block'])) ?>
          <?php endif; ?>
        </nav>

        <!-- Groups -->
        <div class="min-w-0 space-y-10">
          <?php foreach ($faqGroups as $gi => $g): ?>
            <section id="faq-category-<?php echo (int) $g['id'] ?>" class="scroll-mt-28">
              <h2 class="text-h3"><?php echo e($g['title']) ?></h2>
              <?php if ($g['description'] !== ''): ?>
                <p class="mt-2 max-w-2xl text-body-sm text-ink-muted"><?php echo e($g['description']) ?></p>
              <?php endif; ?>

              <div class="card mt-4 px-5 sm:px-6">
                <?php echo faq_accordion($g['items'], 'faq-category-' . $g['id'], $gi === 0 ? 0 : -1) ?>
              </div>
            </section>
          <?php endforeach; ?>
        </div>
      </div>
    <?php endif; ?>
  </div>
</section>

<?php if ($faqSchemaJson !== ''): ?>
<script type="application/ld+json">
<?php echo $faqSchemaJson ?>
</script>
<?php endif; ?>

<?php require_once __DIR__ . '/footer.php'; ?>
