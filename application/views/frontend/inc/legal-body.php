<?php
/**
 * Shared layout for the policy pages.
 *
 * The calling page defines $sections before including this file:
 *   $sections = [
 *     ['id' => 'scope', 'title' => 'Scope', 'blocks' => [
 *       ['p', 'Paragraph text'],
 *       ['ul', ['Item one', 'Item two']],
 *     ]],
 *   ];
 * Optionally $legalNotice (string) and $legalUpdated (Y-m-d). Managed policy
 * pages instead pass $managedLegalContent, $legalIntroHtml, support and
 * contact data prepared by the controller.
 */

$managedLegalContent = isset($managedLegalContent) && $managedLegalContent === true;
$sections = isset($sections) && is_array($sections) ? $sections : [];
$legalIntroHtml = isset($legalIntroHtml) ? trim((string) $legalIntroHtml) : '';
$legalSupportCard = isset($legalSupportCard) && is_array($legalSupportCard)
    ? $legalSupportCard
    : null;
$legalContact = isset($legalContact) && is_array($legalContact) ? $legalContact : null;

if (!$managedLegalContent) {
    $legalUpdated = $legalUpdated ?? date('Y-m-d');
    $legalNotice = $legalNotice ?? t('legal.notice');
}

$hasToc = !empty($sections);
$hasSidebar = $hasToc || $legalSupportCard !== null || !$managedLegalContent;
?>
<div class="bg-white py-12 sm:py-14 lg:py-16">
  <div class="container">
    <div class="grid gap-10 <?php echo $hasSidebar ? 'lg:grid-cols-[264px_minmax(0,1fr)] lg:gap-14' : '' ?>">

      <!-- Contents -->
      <?php if ($hasSidebar): ?>
        <aside class="lg:sticky lg:top-[calc(var(--frontend-header-h)+28px)] lg:self-start">
          <?php if ($hasToc): ?>
            <nav aria-label="<?php echo e(t('legal.onThisPage')) ?>" data-toc-nav>
              <h2 class="text-eyebrow font-bold uppercase text-ink-soft"><?php echo e(t('legal.onThisPage')) ?></h2>
              <ul class="mt-4 space-y-1">
                <?php foreach ($sections as $i => $s): ?>
                  <li>
                    <a href="#<?php echo e($s['id']) ?>" class="toc-link"
                       <?php echo $i === 0 ? 'aria-current="true"' : '' ?>>
                      <?php echo e($s['title']) ?>
                    </a>
                  </li>
                <?php endforeach; ?>
              </ul>
            </nav>
          <?php endif; ?>

          <?php if ($managedLegalContent && $legalSupportCard !== null): ?>
            <?php echo help_card(array_merge($legalSupportCard, ['class' => 'mt-6'])) ?>
          <?php elseif (!$managedLegalContent): ?>
            <?php echo help_card([
                'eyebrow' => t('legal.help.eyebrow'),
                'text' => t('legal.help.text'),
                'class' => 'mt-6',
            ]) ?>
          <?php endif; ?>
        </aside>
      <?php endif; ?>

      <!-- Body -->
      <div class="min-w-0">
        <?php if (!$managedLegalContent): ?>
          <p class="flex items-start gap-2.5 rounded-card border border-amber-200 bg-amber-50 p-4 text-body-sm text-amber-900">
            <?php echo icon('info', 'mt-0.5 icon-sm shrink-0') ?>
            <span><?php echo e($legalNotice) ?></span>
          </p>

          <p class="mt-6 text-meta text-ink-soft">
            <?php echo e(t('legal.lastUpdated', ['date' => site_date($legalUpdated)])) ?>
          </p>
        <?php elseif ($legalIntroHtml !== ''): ?>
          <div class="page-contents">
            <?php echo $legalIntroHtml ?>
          </div>
        <?php endif; ?>

        <div class="mt-8 space-y-10">
          <?php foreach ($sections as $s): ?>
            <section id="<?php echo e($s['id']) ?>" class="scroll-mt-28">
              <h2 class="text-h3"><?php echo e($s['title']) ?></h2>
              <div class="page-contents mt-4">
                <?php if (isset($s['html'])): ?>
                  <?php echo $s['html'] ?>
                <?php else: ?>
                  <?php foreach ($s['blocks'] as $block): ?>
                    <?php if ($block[0] === 'p'): ?>
                      <p><?php echo e($block[1]) ?></p>
                    <?php elseif ($block[0] === 'ul'): ?>
                      <ul>
                        <?php foreach ($block[1] as $li): ?>
                          <li><?php echo e($li) ?></li>
                        <?php endforeach; ?>
                      </ul>
                    <?php elseif ($block[0] === 'h3'): ?>
                      <h3><?php echo e($block[1]) ?></h3>
                    <?php endif; ?>
                  <?php endforeach; ?>
                <?php endif; ?>
              </div>
            </section>
          <?php endforeach; ?>
        </div>

        <?php if ($managedLegalContent && $legalContact !== null): ?>
          <div class="mt-12 rounded-card border border-line bg-surface-tint p-6">
            <?php if ($legalContact['title'] !== ''): ?>
              <h2 class="text-card-title font-bold text-ink"><?php echo e($legalContact['title']) ?></h2>
            <?php endif; ?>
            <?php if ($legalContact['text'] !== ''): ?>
              <p class="mt-2 text-body-sm text-ink-muted"><?php echo e($legalContact['text']) ?></p>
            <?php endif; ?>
            <?php if ($legalContact['email'] !== '' || $legalContact['phone'] !== ''): ?>
              <ul class="mt-4 space-y-2.5 text-meta">
                <?php if ($legalContact['email'] !== ''): ?>
                  <li class="flex items-center gap-2.5">
                    <?php echo icon('mail', 'icon-sm text-alam-500') ?>
                    <a href="mailto:<?php echo e($legalContact['email']) ?>" class="break-all text-alam-700 hover:underline"><bdi dir="ltr"><?php echo e($legalContact['email']) ?></bdi></a>
                  </li>
                <?php endif; ?>
                <?php if ($legalContact['phone'] !== ''): ?>
                  <li class="flex items-center gap-2.5">
                    <?php echo icon('phone', 'icon-sm text-alam-500') ?>
                    <a href="tel:<?php echo e($legalContact['phone_href']) ?>" class="text-alam-700 hover:underline"><bdi dir="ltr"><?php echo e($legalContact['phone']) ?></bdi></a>
                  </li>
                <?php endif; ?>
              </ul>
            <?php endif; ?>
          </div>
        <?php elseif (!$managedLegalContent): ?>
          <div class="mt-12 rounded-card border border-line bg-surface-tint p-6">
            <h2 class="text-card-title font-bold text-ink"><?php echo e(t('legal.touch.title')) ?></h2>
            <p class="mt-2 text-body-sm text-ink-muted">
              <?php echo e(t('legal.touch.text', [
                  'name' => business()['name'],
                  'location' => business()['location'],
              ])) ?>
            </p>
            <ul class="mt-4 space-y-2.5 text-meta">
              <li class="flex items-center gap-2.5">
                <?php echo icon('mail', 'icon-sm text-alam-500') ?>
                <a href="mailto:<?php echo e(business()['email']) ?>" class="break-all text-alam-700 hover:underline"><bdi dir="ltr"><?php echo e(business()['email']) ?></bdi></a>
              </li>
              <li class="flex items-center gap-2.5">
                <?php echo icon('phone', 'icon-sm text-alam-500') ?>
                <a href="tel:<?php echo e(business()['phone_href']) ?>" class="text-alam-700 hover:underline"><bdi dir="ltr"><?php echo e(business()['phone']) ?></bdi></a>
              </li>
            </ul>
          </div>
        <?php endif; ?>
      </div>
    </div>
  </div>
</div>
