<section class="panel hidden p-5 sm:p-7" data-step="6" aria-labelledby="step-6-title">
    <header class="border-b border-line pb-5">
        <p class="eyebrow-plain" data-step-caption><?php echo e(t('book.step.of', ['step' => number(6), 'count' => number($stepCount)])); ?></p>
        <h2 id="step-6-title" class="mt-2.5 text-h3"><?php echo e(t('book.payment.title')); ?></h2>
        <p class="mt-3 flex items-start gap-2 rounded-control border border-alam-200 bg-alam-50 p-3 text-body-sm text-alam-800">
            <?php echo icon('shield', 'mt-0.5 icon-sm shrink-0 text-alam-600') ?>
            <span><?php echo e(t('book.payment.text')); ?></span>
        </p>
    </header>

    <div class="mt-5 rounded-card border border-line-strong p-4 sm:p-5">
        <div class="mysr-form" data-moyasar-form></div>
        <div class="flex items-center gap-2 text-body-sm text-ink-muted" data-payment-loading role="status">
            <span class="inline-block h-4 w-4 animate-spin rounded-full border-2 border-alam-200 border-t-alam-700" aria-hidden="true"></span>
            <span><?php echo e(t('book.saving')); ?></span>
        </div>
        <p class="hidden rounded-control border border-red-200 bg-red-50 p-3 text-body-sm text-red-700" data-payment-error role="alert">
            <?php echo e(t('book.payment.error')); ?>
        </p>
    </div>
</section>
