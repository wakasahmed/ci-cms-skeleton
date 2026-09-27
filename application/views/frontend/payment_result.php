<?php
require_once __DIR__ . '/functions.php';

$success = !empty($paymentResult['success']);
$pending = !$success
    && isset($paymentResult['error'])
    && $paymentResult['error'] === 'payment_pending';
$reference = isset($paymentResult['booking_reference'])
    ? (string) $paymentResult['booking_reference']
    : '';
$isArabic = current_locale() === 'ar';
$title = $success
    ? ($isArabic ? 'تم تأكيد الحجز' : 'Booking confirmed')
    : ($pending
        ? ($isArabic ? 'جاري التحقق من الدفع' : 'Payment is being verified')
        : ($isArabic ? 'تعذر تأكيد الدفع' : 'Payment could not be confirmed'));
$message = $success
    ? ($isArabic
        ? 'تم التحقق من عملية الدفع بنجاح. أرسلنا تفاصيل الحجز إلى بريدك الإلكتروني.'
        : 'Your payment was verified successfully. We sent the booking details to your email.')
    : ($pending
        ? ($isArabic
            ? 'لم تكتمل عملية الدفع بعد. يرجى الانتظار قليلاً ثم تحديث هذه الصفحة.'
            : 'The payment has not completed yet. Please wait briefly and refresh this page.')
        : ($isArabic
            ? 'لم نغيّر حالة الحجز. يرجى المحاولة مرة أخرى أو التواصل معنا إذا تم خصم المبلغ.'
            : 'Your booking was not marked paid. Please try again or contact us if you were charged.'));

$config = array(
    'page_title' => $title,
    'meta_description' => $message,
    'robots' => 'noindex, nofollow',
    'active' => '',
);
require_once __DIR__ . '/header.php';
?>

<div class="bg-surface-soft py-16 sm:py-24">
    <div class="container">
        <section class="card mx-auto max-w-2xl p-6 text-center shadow-card sm:p-10" aria-labelledby="payment-result-title">
            <span class="mx-auto grid h-14 w-14 place-items-center rounded-full <?php echo $success ? 'bg-alam-500 text-white' : ($pending ? 'bg-amber-100 text-amber-700' : 'bg-red-100 text-red-700'); ?>" aria-hidden="true">
                <?php echo icon($success ? 'check' : ($pending ? 'clock' : 'alert-triangle'), 'icon-lg'); ?>
            </span>
            <h1 id="payment-result-title" class="mt-5 text-h2"><?php echo e($title); ?></h1>
            <p class="mt-3 text-body text-ink-muted"><?php echo e($message); ?></p>
            <?php if ($reference !== '') { ?>
                <p class="mt-5 rounded-control bg-surface-tint px-4 py-3 text-body-sm">
                    <?php echo e($isArabic ? 'مرجع الحجز' : 'Booking reference'); ?>:
                    <strong dir="ltr"><?php echo e($reference); ?></strong>
                </p>
            <?php } ?>
            <div class="mt-7 flex flex-wrap justify-center gap-3">
                <?php if ($pending) { ?>
                    <button type="button" class="btn-primary" onclick="window.location.reload()">
                        <?php echo e($isArabic ? 'تحديث الحالة' : 'Refresh status'); ?>
                    </button>
                <?php } ?>
                <a class="btn-primary" href="<?php echo e(base_url(current_locale())); ?>">
                    <?php echo e($isArabic ? 'العودة إلى الرئيسية' : 'Return home'); ?>
                </a>
            </div>
        </section>
    </div>
</div>

<?php require_once __DIR__ . '/footer.php'; ?>
