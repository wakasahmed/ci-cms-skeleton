<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>

<dialog id="booking-picker" class="booking-picker-dialog panel text-ink" aria-labelledby="booking-picker-title"
        aria-describedby="booking-picker-intro" data-options-url="<?php echo e(base_url(current_locale() . '/booking-options')) ?>"
        data-book-url="<?php echo e(url('book.php')) ?>">
    <div class="p-5 sm:p-7">
        <header class="flex items-start justify-between gap-4">
            <div>
                <p class="eyebrow"><?php echo e(t('bookingPicker.eyebrow')) ?></p>
                <h2 id="booking-picker-title" class="mt-2 text-h3"><?php echo e(t('bookingPicker.title')) ?></h2>
            </div>
            <button type="button" class="btn-ghost shrink-0" data-booking-picker-close aria-label="<?php echo e(t('bookingPicker.close')) ?>" autofocus>
                <?php echo icon('close', 'icon-md') ?>
            </button>
        </header>
        <p id="booking-picker-intro" class="mt-3 text-body-sm text-ink-muted"><?php echo e(t('bookingPicker.intro')) ?></p>

        <div class="mt-5" data-booking-picker-content aria-busy="false">
            <div class="rounded-card border border-line bg-surface-soft p-5" data-picker-loading role="status" hidden>
                <div class="flex items-center gap-3">
                    <span class="h-5 w-5 shrink-0 animate-spin rounded-full border-2 border-line-strong border-t-alam-600 motion-reduce:animate-none" aria-hidden="true"></span>
                    <p class="text-body-sm font-semibold"><?php echo e(t('bookingPicker.loading')) ?></p>
                </div>
                <p class="mt-2 text-meta text-ink-muted"><?php echo e(t('bookingPicker.loadingHint')) ?></p>
            </div>
            <p class="rounded-card border border-line bg-surface-soft p-4 text-body-sm text-ink-muted" data-picker-empty role="status" hidden><?php echo e(t('bookingPicker.empty')) ?></p>
            <div class="rounded-card border border-red-200 bg-red-50 p-4" data-picker-error role="alert" hidden>
                <p class="text-body-sm text-red-700"><?php echo e(t('bookingPicker.error')) ?></p>
                <button type="button" class="btn-outline btn-sm mt-3" data-picker-retry><?php echo e(t('bookingPicker.retry')) ?></button>
            </div>
            <div data-picker-choices hidden>
                <label for="booking-picker-tour" class="field-label"><?php echo e(t('bookingPicker.label')) ?> <span class="text-red-600">*</span></label>
                <select id="booking-picker-tour" class="select" required disabled>
                    <option value=""><?php echo e(t('bookingPicker.placeholder')) ?></option>
                </select>
                <p class="mt-2 text-meta text-ink-muted"><?php echo e(t('bookingPicker.hint')) ?></p>
            </div>
        </div>
        <footer class="mt-6 flex flex-col gap-3 border-t border-line pt-5 sm:flex-row sm:justify-end">
            <button type="button" class="btn-outline" data-booking-picker-close><?php echo e(t('bookingPicker.cancel')) ?></button>
            <button type="button" class="btn-primary" data-picker-continue disabled>
                <?php echo e(t('bookingPicker.continue')) ?><?php echo icon('arrow-right', 'icon-sm icon-flip') ?>
            </button>
        </footer>
    </div>
</dialog>
