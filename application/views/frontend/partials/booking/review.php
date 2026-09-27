<section class="panel hidden p-5 sm:p-7" data-step="5" aria-labelledby="step-5-title">
    <header class="border-b border-line pb-5">
        <p class="eyebrow-plain" data-step-caption><?php echo e(t('book.step.of', ['step' => number(5), 'count' => number($stepCount)])) ?></p>
        <h2 id="step-5-title" class="mt-2.5 text-h3"><?php echo e(t('book.s5.title')) ?></h2>
        <p class="mt-2 text-body-sm text-ink-muted">
            <?php echo e(t('book.s5.text')) ?>
        </p>
    </header>

    <div class="space-y-5 pt-6">

        <!-- Customer block -->
        <div class="rounded-card border border-line">
            <div class="flex items-center justify-between gap-3 border-b border-line px-4 py-3">
                <h3 class="text-meta font-bold text-ink"><?php echo e(t('book.s5.detailsBlock')) ?></h3>
                <button type="button" class="btn-link text-meta" data-goto-step="1">
                    <?php echo icon('edit', 'icon-xs') ?><?php echo e(t('cta.edit')) ?>
                </button>
            </div>
            <dl class="divide-y divide-line px-4 py-1">
                <?php echo summary_row(t('book.summary.name'), 'full_name', '—') ?>
                <?php echo summary_row(t('book.summary.email'), 'email', '—') ?>
                <?php echo summary_row(t('book.summary.country'), 'country', '—') ?>
                <?php echo summary_row(t('book.summary.mobile'), 'mobile', '—') ?>
                <?php echo summary_row(t('book.field.guests'), 'guests', '—') ?>
                <?php echo summary_row(t('book.summary.pickup'), 'pickup_location', '—') ?>
            </dl>
        </div>

        <!-- Schedule block -->
        <div class="rounded-card border border-line">
            <div class="flex items-center justify-between gap-3 border-b border-line px-4 py-3">
                <h3 class="text-meta font-bold text-ink"><?php echo e(t('book.s5.scheduleBlock')) ?></h3>
                <button type="button" class="btn-link text-meta" data-goto-step="2">
                    <?php echo icon('edit', 'icon-xs') ?><?php echo e(t('cta.edit')) ?>
                </button>
            </div>
            <dl class="divide-y divide-line px-4 py-1">
                <?php echo summary_row(t('book.summary.date'), 'date', t('book.notSelected')) ?>
                <?php echo summary_row(t('book.summary.slot'), 'slot', t('book.notSelected')) ?>
            </dl>
        </div>

        <!-- Tour block -->
        <div class="rounded-card border border-line">
            <div class="flex items-center justify-between gap-3 border-b border-line px-4 py-3">
                <h3 class="text-meta font-bold text-ink"><?php echo e($bookingLabels['s5.tourBlock']) ?></h3>
                <button type="button" class="btn-link text-meta" data-goto-step="3">
                    <?php echo icon('edit', 'icon-xs') ?><?php echo e(t('cta.edit')) ?>
                </button>
            </div>
            <dl class="divide-y divide-line px-4 py-1">
                <?php echo summary_row($bookingLabels['summary.tour'], 'tour', t('book.notSelected')) ?>
                <?php echo summary_row(t('book.summary.languageLong'), 'language', language_label($preLanguage)) ?>
                <?php echo summary_row(t('book.summary.vehicle'), 'vehicle', t('book.notSelected')) ?>
                <div class="summary-row hidden" data-notes-row>
                    <dt class="summary-label"><?php echo e(t('book.summary.notes')) ?></dt>
                    <dd class="summary-value max-w-[60%] font-normal text-ink-muted" data-summary="notes"></dd>
                </div>
            </dl>
        </div>

        <!-- Guide block -->
        <div class="rounded-card border border-line" data-guide-review>
            <div class="flex items-center justify-between gap-3 border-b border-line px-4 py-3">
                <h3 class="text-meta font-bold text-ink"><?php echo e(t('book.s5.guideBlock')) ?></h3>
                <button type="button" class="btn-link text-meta" data-goto-step="4">
                    <?php echo icon('edit', 'icon-xs') ?><?php echo e(t('cta.edit')) ?>
                </button>
            </div>
            <dl class="divide-y divide-line px-4 py-1">
                <?php echo summary_row(t('book.summary.guideChosen'), 'guide', t('book.notSelected')) ?>
            </dl>
        </div>

        <!-- Promo code -->
        <div class="rounded-card border border-line p-4" data-promo-section>
            <label for="promo_code_input" class="field-label"><?php echo e(t('book.promo.label')) ?></label>

            <div class="flex flex-col gap-2 sm:flex-row" data-promo-form>
                <input type="text" id="promo_code_input" class="input flex-1" dir="ltr" maxlength="40"
                              autocomplete="off" placeholder="<?php echo e(t('book.promo.placeholder')) ?>" data-promo-input>
                <button type="button" class="btn-primary btn-sm shrink-0" data-promo-apply>
                    <?php echo e(t('book.promo.apply')) ?>
                </button>
            </div>

            <div class="summary-row hidden items-center rounded-control bg-alam-50 px-3.5 py-3" data-promo-applied>
                <div class="min-w-0">
                    <p class="truncate text-meta font-bold text-alam-800" dir="ltr" data-promo-applied-code></p>
                    <p class="mt-0.5 text-form-help text-ink-soft" data-promo-applied-detail></p>
                </div>
                <button type="button" class="btn-link shrink-0 text-meta" data-promo-remove>
                    <?php echo icon('close', 'icon-xs') ?><?php echo e(t('book.promo.remove')) ?>
                </button>
            </div>

            <p class="mt-2 text-meta font-medium" role="status" aria-live="polite" data-promo-status></p>
        </div>

        <!-- Price -->
        <div class="rounded-card border border-alam-200 bg-alam-50 p-4">
            <div class="summary-row hidden" data-summary-row="original">
                <p class="summary-label"><?php echo e(t('book.summary.originalTotal')) ?></p>
                <p class="summary-value" data-summary="original_total">—</p>
            </div>
            <div class="summary-row hidden" data-summary-row="discount">
                <p class="summary-label"><?php echo e(t('book.summary.discount')) ?></p>
                <p class="summary-value text-alam-700" data-summary="discount_amount">—</p>
            </div>
            <div class="flex items-center justify-between gap-4">
                <div>
                    <p class="text-meta font-bold text-ink"><?php echo e(t('book.summary.total')) ?></p>
                    <p class="mt-1 text-meta text-ink-muted"><?php echo e(t('book.priceNoteReview')) ?></p>
                </div>
                <p class="text-xl font-extrabold text-alam-800" data-summary="total">—</p>
            </div>
        </div>

        <!-- Terms -->
        <div>
            <label class="flex cursor-pointer items-start gap-3 rounded-card border border-line-strong p-4 transition-colors hover:border-alam-400">
                <input type="checkbox" name="terms" value="1" required
                              class="mt-0.5 h-5 w-5 shrink-0 rounded border-line-strong text-alam-500 focus:ring-alam-500/30">
                <?php /* Two links inside one sentence: the anchors stay in the
                                  template and the three text fragments around them are
                                  translated separately, so the Arabic can put the verb
                                  where Arabic puts it. */ ?>
                <span class="text-body-sm text-ink-muted">
                    <?php echo e(t('book.s5.terms.before')) ?><a href="<?php echo e(url('privacy-policy.php')) ?>" class="font-semibold text-alam-600 underline underline-offset-4"><?php echo e(t('legal.privacyPolicy')) ?></a><?php echo e(t('book.s5.terms.and')) ?><a href="<?php echo e(url('cancellation-policy.php')) ?>" class="font-semibold text-alam-600 underline underline-offset-4"><?php echo e(t('legal.cancellationPolicy')) ?></a><?php echo e(t('book.s5.terms.after')) ?>
                    <span class="text-red-600">*</span>
                </span>
            </label>
            <p class="mt-1.5 hidden text-meta font-medium text-red-600" data-error="terms"><?php echo e(t('book.error.terms')) ?></p>
        </div>

        <?php if (WHATSAPP_BOOKING_ENABLED && WHATSAPP_ENABLED) { ?>
            <!-- WhatsApp consent (optional). Meta requires opt-in before business-initiated WhatsApp messages. -->
            <label class="flex cursor-pointer items-start gap-3 rounded-card border border-line-strong p-4 transition-colors hover:border-alam-400">
                <input type="checkbox" name="whatsapp_consent" value="1"
                              class="mt-0.5 h-5 w-5 shrink-0 rounded border-line-strong text-alam-500 focus:ring-alam-500/30">
                <span class="flex items-start gap-2 text-body-sm text-ink-muted">
                    <?php echo icon('whatsapp', 'mt-0.5 icon-sm shrink-0 text-alam-500') ?>
                    <span><?php echo e(t('book.s5.whatsappConsent')) ?></span>
                </span>
            </label>
        <?php } ?>

        <p class="flex items-start gap-2 text-body-sm text-ink-muted">
            <?php echo icon('shield', 'mt-0.5 icon-sm shrink-0 text-alam-500') ?>
            <span><?php echo e(t('book.s5.assurance')) ?></span>
        </p>
    </div>

    <footer class="mt-8 flex flex-col gap-3 border-t border-line pt-6 sm:flex-row sm:items-center sm:justify-between">
        <button type="button" class="btn-outline" data-prev="4"><?php echo icon('arrow-left', 'icon-sm icon-flip') ?><?php echo e(t('cta.back')) ?></button>
        <button type="button" data-next="6" class="btn-primary btn-lg w-full shrink-0 whitespace-nowrap sm:w-auto">
            <?php echo icon('check', 'icon-sm') ?><?php echo e(t('book.payment.continue')) ?>
        </button>
    </footer>
</section>
