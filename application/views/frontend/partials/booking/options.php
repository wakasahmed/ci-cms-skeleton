<section class="panel hidden p-5 sm:p-7" data-step="3" aria-labelledby="step-3-title">
    <header class="border-b border-line pb-5">
        <p class="eyebrow-plain" data-step-caption><?php echo e(t('book.step.of', ['step' => number(3), 'count' => number($stepCount)])) ?></p>
        <h2 id="step-3-title" class="mt-2.5 text-h3"><?php echo e($bookingLabels['s2.title']) ?></h2>
        <p class="mt-2 text-body-sm text-ink-muted">
            <?php echo e($bookingLabels['s2.text']) ?>
        </p>
    </header>

    <input type="hidden" name="tour_id" value="<?php echo e($preTour); ?>">

    <!-- Language -->
    <fieldset class="pt-6" data-language-section>
        <legend class="pt-2.5 text-body-sm font-bold text-ink"><?php echo e(t('book.s2.language')) ?></legend>
        <p class="mt-1.5 text-body-sm text-ink-muted"><?php echo e(t('book.s2.languageText')) ?></p>

        <div class="mt-4">
            <label for="preferred-language" class="sr-only"><?php echo e(t('book.s2.language')) ?></label>
            <select id="preferred-language" name="language" class="select js-select2" data-select2-search="true"></select>
        </div>
    </fieldset>

    <!-- Vehicle -->
    <fieldset class="mt-6 border-t border-line pt-6">
        <legend class="pt-2.5 text-body-sm font-bold text-ink"><?php echo e(t('book.s2.vehicle')) ?></legend>
        <p class="mt-1.5 text-body-sm text-ink-muted"><?php echo e(t('book.s2.vehicleText')) ?></p>

        <div class="mt-4 grid gap-3 sm:grid-cols-2">
            <?php foreach ($vehicles as $v): ?>
                <label class="card option-card booking-vehicle-card flex flex-row overflow-hidden" data-vehicle-option="<?php echo e($v['id']) ?>">
                    <input type="radio" name="vehicle_id" value="<?php echo e($v['id']) ?>"<?php echo $v['id'] === $bookingData['preselect']['vehicle'] ? ' checked' : '' ?>>
                    <span class="option-dot" aria-hidden="true"></span>
                    <span class="min-w-0 flex-1">
                        <span class="block text-card-title font-bold text-ink"><?php echo e($v['name']) ?></span>
                        <span class="mt-1 block text-meta font-semibold text-alam-700"><?php echo e(tn('count.upTo', $v['capacity'])) ?></span>
                        <span class="mt-1.5 block text-body-sm text-ink-muted"><?php echo nl2br(e($v['details'])) ?></span>
                    </span>
                </label>
            <?php endforeach; ?>
        </div>
        <p class="mt-3 hidden text-body-sm text-red-600" data-vehicle-empty role="status"><?php echo e(t('book.vehicle.empty')) ?> <button type="button" class="underline" data-goto-step="1"><?php echo e(t('book.changePreferences')) ?></button></p>
        <p class="mt-2 hidden text-meta font-medium text-red-600" data-error="vehicle"><?php echo e(t('book.error.vehicle')) ?></p>
        <p class="mt-3 hidden items-start gap-2 rounded-control border border-amber-200 bg-amber-50 p-3 text-meta text-amber-900" data-capacity-warning>
            <?php echo icon('info', 'mt-0.5 icon-sm shrink-0') ?>
            <span><?php echo e($bookingLabels['s2.capacityWarning']) ?></span>
        </p>
    </fieldset>

    <!-- Notes -->
    <div class="mt-8 border-t border-line pt-6">
        <label for="notes" class="field-label"><?php echo e(t('book.s2.notes')) ?> <span class="font-normal text-ink-soft"><?php echo e(t('book.s2.notesOptional')) ?></span></label>
        <textarea id="notes" name="notes" class="input" rows="4"
                            placeholder="<?php echo e(t('book.s2.notesPlaceholder')) ?>"></textarea>
        <span class="field-hint"><?php echo e(t('book.s2.notesHint')) ?></span>
    </div>

    <footer class="mt-8 flex items-center justify-between gap-3 border-t border-line pt-6">
        <button type="button" class="btn-outline" data-prev="2"><?php echo icon('arrow-left', 'icon-sm icon-flip') ?><?php echo e(t('cta.back')) ?></button>
        <button type="button" class="btn-primary" data-next="4">
            <?php echo e(t('cta.continue')) ?><?php echo icon('arrow-right', 'icon-sm icon-flip') ?>
        </button>
    </footer>
</section>
