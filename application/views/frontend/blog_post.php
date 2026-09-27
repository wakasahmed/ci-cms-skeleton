<?php

require_once __DIR__ . '/functions.php';
require_once __DIR__ . '/header.php';
?>

<?php /* The category bar, category pills, side rail, related posts and the
         "back to all" link navigate through HTMX (js/htmx-init.js). */ ?>
<?php echo blog_category_nav($nav['items'], ['active' => $post['category_slug'], 'boost' => true]) ?>

<?php if (!empty($post['banner'])): ?>
  <?php $config['banner'] = $post['banner']; ?>
  <?php require __DIR__ . '/banner.php'; ?>
<?php else: ?>
  <?php echo blog_article_header($post, true) ?>
<?php endif; ?>

<div class="bg-white py-12 sm:py-14 lg:py-16">
  <div class="container">
    <div class="grid gap-10 lg:grid-cols-[minmax(0,1fr)_336px] lg:gap-14">

      <article class="min-w-0">
        <?php echo blog_article_body($post['body_html']) ?>

        <div class="hairline my-10"></div>

        <div class="flex flex-wrap items-center justify-between gap-4">
          <?php if ($post['author'] !== ''): ?>
            <p class="text-meta text-ink-muted">
              <?php echo e(t('post.writtenBy', ['author' => $post['author']])) ?>
            </p>
          <?php endif; ?>
          <a href="<?php echo e(blog_category_url('')) ?>" class="btn-link" hx-boost="true">
            <?php echo icon('arrow-left', 'icon-sm icon-flip') ?><?php echo e(t('post.backToAll')) ?>
          </a>
        </div>
      </article>

      <!-- Side rail -->
      <aside class="hidden lg:sticky lg:top-[calc(var(--frontend-header-h)+76px)] lg:block lg:self-start">
        <?php echo featured_posts_rail($rail, $railContent, true) ?>
      </aside>
    </div>
  </div>
</div>

<?php if (!empty($related)): ?>
  <!-- ==================================================================
       Related
       ============================================================== -->
  <section class="border-t border-line bg-surface-soft py-14 sm:py-16 lg:py-20">
    <div class="container">
      <?php echo section_head([
          'eyebrow' => t('post.related.eyebrow'),
          'title'   => t('post.related.title'),
      ]) ?>

      <?php echo blog_related_grid($related, true) ?>
    </div>
  </section>
<?php endif; ?>

<?php /* Article structured data is part of the @graph printed by header.php. */ ?>

<?php require_once __DIR__ . '/footer.php'; ?>
