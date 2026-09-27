<section class="panel hidden p-5 sm:p-7" data-step="4" aria-labelledby="step-4-title">
    <header class="border-b border-line pb-5">
        <p class="eyebrow-plain" data-step-caption><?php echo e(t('book.step.of', ['step' => number(4), 'count' => number($stepCount)])) ?></p>
        <h2 id="step-4-title" class="mt-2.5 text-h3"><?php echo e(t('book.s4.title')) ?></h2>
        <p class="mt-2 text-body-sm text-ink-muted">
            <?php echo e(t('book.s4.text')) ?>
        </p>
    </header>

    <div class="pt-6">
        <p class="mb-4 text-meta font-medium text-ink-muted" data-guide-count aria-live="polite"></p>

        <template id="booking-guide-card">
            <label class="option-card items-start" data-guide-card>
                <input type="radio" name="guide_id" required>
                <span class="option-dot" aria-hidden="true"></span>
                <span class="flex min-w-0 flex-1 flex-col gap-3 sm:flex-row">
                    <img alt="" width="400" height="300" loading="lazy"
                              class="h-16 w-16 shrink-0 rounded-control object-cover" data-guide-image>
                    <span class="min-w-0 flex-1">
                        <span class="block text-body-sm font-bold text-ink" data-guide-name></span>
                        <span class="mt-1 block text-meta text-ink-muted" data-guide-role></span>
                        <span class="mt-1.5 block text-body-sm text-ink-muted" data-guide-bio></span>
                        <span class="mt-2.5 flex items-start gap-2 text-meta font-semibold text-alam-700 rtl:leading-4">
                            <?php echo icon('language', 'icon-sm shrink-0'); ?>
                            <span data-guide-languages></span>
                        </span>
                    </span>
                </span>
            </label>
        </template>

        <fieldset>
            <legend class="sr-only"><?php echo e(t('book.s4.legend')) ?></legend>
            <div class="grid gap-3" data-guide-list>
                <!-- Matching guides are injected here by js/booking.js -->
            </div>
            <p class="mt-2 hidden text-meta font-medium text-red-600" data-error="guide"><?php echo e(t('book.error.guide')); ?></p>
        </fieldset>

        <div class="mt-5 hidden" data-guide-empty>
            <?php echo empty_state(
                    t('book.s4.empty.title'),
                    t('book.s4.empty.text'),
                    '<button type="button" class="btn-outline btn-sm" data-goto-step="2">' . e(t('book.s4.empty.changeDate')) . '</button>'
                    . '<button type="button" class="btn-outline btn-sm" data-goto-step="3">' . e(t('book.s4.empty.changeLanguage')) . '</button>',
                    'guide'
            ) ?>
        </div>
    </div>

    <footer class="mt-8 flex items-center justify-between gap-3 border-t border-line pt-6">
        <button type="button" class="btn-outline" data-prev="3"><?php echo icon('arrow-left', 'icon-sm icon-flip') ?><?php echo e(t('cta.back')) ?></button>
        <button type="button" class="btn-primary" data-next="5"><?php echo e(t('cta.continue')) ?><?php echo icon('arrow-right', 'icon-sm icon-flip') ?></button>
    </footer>
</section>
