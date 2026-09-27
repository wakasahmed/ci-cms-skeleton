<section class="panel hidden p-5 sm:p-7" data-step="2" aria-labelledby="step-2-title">
    <header class="border-b border-line pb-5">
        <p class="eyebrow-plain" data-step-caption><?php echo e(t('book.step.of', ['step' => number(2), 'count' => number($stepCount)])) ?></p>
        <h2 id="step-2-title" class="mt-2.5 text-h3"><?php echo e(t('book.s3.title')) ?></h2>
        <p class="mt-2 text-body-sm text-ink-muted">
            <?php echo e($bookingLabels['s3.text']) ?>
        </p>
    </header>

    <div class="grid gap-6 pt-6 xl:grid-cols-[318px_minmax(0,1fr)]">

        <!-- Calendar -->
        <div class="w-full lg:w-[318px]">
            <div class="rounded-card border border-line-strong p-4">
                <?php /* Flatpickr renders its inline calendar here — see
                                  js/booking.js, which builds it through the shared
                                  window.FrontendDatepicker configuration. */ ?>
                <div data-cal-inline role="group" aria-label="<?php echo e($bookingLabels['s3.calendarLabel']) ?>"></div>

                <div class="mt-4 flex flex-wrap items-center gap-x-4 gap-y-2 border-t border-line pt-3 text-meta text-ink-muted">
                    <span class="flex items-center gap-1.5"><span class="h-2.5 w-2.5 rounded-[3px] bg-alam-500"></span><?php echo e(t('book.s3.legend.selected')) ?></span>
                    <span class="flex items-center gap-1.5"><span class="h-2.5 w-2.5 rounded-[3px] ring-1 ring-inset ring-alam-500"></span><?php echo e(t('book.s3.legend.today')) ?></span>
                    <span class="flex items-center gap-1.5"><span class="h-2.5 w-2.5 rounded-[3px] bg-surface-soft ring-1 ring-inset ring-line-strong"></span><?php echo e(t('book.s3.legend.unavailable')) ?></span>
                </div>
            </div>
            <div class="mt-3 hidden rounded-control border border-line-strong bg-surface-tint p-4" data-cal-empty>
                <p class="flex items-start gap-2 text-body-sm text-ink-muted">
                    <?php echo icon('info', 'mt-0.5 icon-sm shrink-0 text-alam-500') ?>
                    <span><?php echo e($bookingLabels['s3.calEmpty.before']) ?><a href="<?php echo e($bookingListingUrl); ?>" class="font-semibold text-alam-600 underline underline-offset-4"><?php echo e($bookingLabels['s3.calEmpty.link']) ?></a><?php echo e(t('book.s3.calEmpty.after')) ?></span>
                </p>
            </div>
            <input type="hidden" name="tour_date" data-date-input value="<?php echo e($preDate) ?>">
            <p class="mt-2 hidden text-meta font-medium text-red-600" data-error="date"><?php echo e(t('book.error.date')) ?></p>
        </div>

        <!-- Slots -->
        <fieldset class="min-w-0">
            <legend class="text-body-sm font-bold text-ink"><?php echo e(t('book.s3.slots')) ?></legend>
            <p class="mt-1.5 text-body-sm text-ink-muted" data-slot-context><?php echo e(t('book.slot.chooseDate')) ?></p>

            <div class="mt-4 grid gap-3">
                <?php foreach ($slots as $s): ?>
                    <label class="option-card items-center" data-slot-option="<?php echo e($s['id']) ?>">
                        <input type="radio" name="slot_id" value="<?php echo e($s['id']) ?>" disabled>
                        <span class="option-dot" aria-hidden="true"></span>
                        <span class="min-w-0 flex-1">
                            <span class="flex items-center gap-2">
                                <?php echo icon($s['icon'], 'icon-md text-alam-600') ?>
                                <span class="text-body-sm font-bold text-ink"><?php echo e($s['label']) ?></span>
                            </span>
                            <?php /* No forced dir here: the range inherits the page
                                              direction so it aligns with the label and note
                                              and its Arabic AM/PM markers order correctly
                                              under RTL. */ ?>
                            <span class="mt-1 block whitespace-nowrap text-meta font-medium text-alam-700"><?php echo e($s['time']) ?></span>
                            <span class="mt-1 block text-body-sm text-ink-muted" data-slot-note><?php echo e($s['note']) ?></span>
                        </span>
                    </label>
                <?php endforeach; ?>
            </div>
            <p class="mt-2 hidden text-meta font-medium text-red-600" data-error="slot"><?php echo e(t('book.error.slot')) ?></p>

            <div class="mt-5 hidden rounded-control border border-line-strong bg-surface-tint p-4" data-slot-empty>
                <p class="flex items-start gap-2 text-body-sm text-ink-muted">
                    <?php echo icon('info', 'mt-0.5 icon-sm shrink-0 text-alam-500') ?>
                    <span><?php echo e($bookingLabels['s3.slotEmpty']) ?></span>
                </p>
            </div>
        </fieldset>
    </div>

    <footer class="mt-8 flex items-center justify-between gap-3 border-t border-line pt-6">
        <button type="button" class="btn-outline" data-prev="1"><?php echo icon('arrow-left', 'icon-sm icon-flip') ?><?php echo e(t('cta.back')) ?></button>
        <button type="button" class="btn-primary" data-next="3"><?php echo e(t('cta.continue')) ?><?php echo icon('arrow-right', 'icon-sm icon-flip') ?></button>
    </footer>
</section>
