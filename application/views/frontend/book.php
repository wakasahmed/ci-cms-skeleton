<?php

require_once __DIR__ . '/functions.php';

// Assignments and encrypted references are prepared by the controller.
$tours = $bookingData['tours'];
$vehicles = $bookingData['vehicles'];
$slots = $bookingData['slots'];
$countries = countries();
$preTour = $bookingData['preselect']['tour'];
$preLanguage = $bookingData['preselect']['language'];
$preDate = $bookingData['preselect']['date'];
$bookingBackgroundImage = $bookingHero['background_image'];
$isExperience = !empty($bookingHero['isExperience']);
$bookingListingUrl = url($isExperience ? 'experiences.php' : 'tours.php');
$bookingLabels = [];
foreach ([
    'meta.title',
    'meta.description',
    'step.3',
    'summary.tour',
    'summary.noTour',
    'priceNote',
    'backToTours',
    's2.title',
    's2.text',
    's2.capacityWarning',
    's3.text',
    's3.calendarLabel',
    's3.calEmpty.before',
    's3.calEmpty.link',
    's3.slotEmpty',
    's5.tourBlock',
    'error.guests',
] as $key) {
    $bookingLabels[$key] = t('book.' . ($isExperience ? 'experience.' : '') . $key);
}
$bookingData['alerts'] = [
    'errorTitle' => t('book.alert.errorTitle'),
    'confirm' => t('book.alert.confirm'),
    'saving' => t('book.saving'),
    'errors' => [
        'expired' => t('book.save.expired'),
        'details' => t('book.save.details'),
        'preferences' => t('book.save.preferences'),
        'unavailable' => t('book.save.unavailable'),
        'payment' => t('book.payment.error'),
        'promo' => t('book.save.promo'),
        'recaptcha' => t('book.save.recaptcha'),
    ],
    'promoErrors' => [
        'invalid' => t('book.promo.error.invalid'),
        'expired' => t('book.promo.error.expired'),
        'unavailable' => t('book.promo.error.unavailable'),
        'exhausted' => t('book.promo.error.limitGlobal'),
        'customer_limit' => t('book.promo.error.limitCustomer'),
    ],
];
$bookingData['busy'] = [
    'steps' => [
        1 => t('book.busy.step1'),
        2 => t('book.busy.step2'),
        3 => t('book.busy.step3'),
        4 => t('book.busy.step4'),
        5 => t('book.busy.step5'),
    ],
    'slow' => t('book.busy.slow'),
];
$bookingData['discountUrl'] = base_url(current_locale() . '/booking-discount');
$bookingData['paymentPrepareUrl'] = base_url(current_locale() . '/payments/prepare');
$bookingData['moyasarEnabled'] = MOYASAR_ENABLED;
$bookingData['currency'] = business()['currency'];
$bookingData['stepCaption'] = t('book.step.of', ['step' => '{step}', 'count' => '{count}']);

$config = [
    'page_title'       => $bookingLabels['meta.title'],
    'meta_description' => $bookingLabels['meta.description'],
    /* A booking form is a step in a transaction, not a page to rank. Crawlers
       may still follow its links back to the tour. */
    'robots'           => 'noindex, follow',
    'active'           => $bookingHero['isExperience'] ? 'experiences' : 'tours',
    'select2'          => true,
    'phone'            => true,
    'sweetalert2'       => true,
    'datepicker'       => true,
    'moyasar'          => MOYASAR_ENABLED,
    'scripts'          => ['js/number-stepper.js', 'js/place-autocomplete.js', 'js/booking.js'],
];

require_once __DIR__ . '/header.php';

$stepLabels = [
    t('book.step.1'), t('book.step.2'), $bookingLabels['step.3'],
    t('book.step.4'), t('book.step.5'), t('book.step.6'),
];
$stepCount = $isExperience ? 5 : count($stepLabels);
$bookingData['stepLabels'] = $stepLabels;
?>

<!-- ==================================================================
     Booking header + progress
     ============================================================== -->
<section class="relative isolate overflow-hidden bg-alam-900">
  <?php if ($bookingBackgroundImage !== ''): ?>
    <img src="<?php echo e(
        is_absolute_url($bookingBackgroundImage)
            ? $bookingBackgroundImage
            : site_base_url($bookingBackgroundImage)
    ) ?>" alt="" aria-hidden="true" width="960" height="640"
         fetchpriority="high" decoding="async"
         class="absolute inset-0 -z-10 h-full w-full object-cover opacity-60">
    <div class="absolute inset-0 -z-10 bg-gradient-to-r from-alam-900/90 via-alam-900/70 to-alam-800/35" aria-hidden="true"></div>
  <?php endif; ?>
  <div class="container">
    <div class="py-12 sm:py-16 lg:py-20">
      <div class="max-w-2xl">
        <?php echo breadcrumb($bookingBreadcrumbItems) ?>
        <p class="eyebrow mt-6 text-white/70"><?php echo e($bookingHero['category']) ?></p>
        <h1 class="mt-3.5 text-h1 text-white text-shadow-hero"><?php echo e($bookingHero['title']) ?></h1>
        <p class="mt-4 max-w-xl text-body text-white/80"><?php echo e($bookingHero['summary']) ?></p>
      </div>
    </div>
  </div>
</section>

<div class="sticky top-[var(--frontend-header-h)] z-30 border-b border-line bg-white/95 backdrop-blur" data-progress-bar>
  <div class="container">
    <ol class="no-scrollbar -mx-1 flex items-center gap-1 overflow-x-auto py-3" aria-label="<?php echo e(t('book.progress.label')) ?>">
      <?php foreach ($stepLabels as $i => $label): ?>
        <?php $n = $i + 1; ?>
        <li class="flex shrink-0 items-center<?php echo $isExperience && $n === 4 ? ' hidden' : ''; ?>">
          <button type="button" class="step-pill" data-step-pill="<?php echo $n ?>" data-state="<?php echo $n === 1 ? 'active' : 'todo' ?>"
                  <?php echo $n === 1 ? 'aria-current="step"' : '' ?>>
            <span class="step-index"><?php echo e(number($isExperience && $n >= 5 ? $n - 1 : $n)) ?></span>
            <span><?php echo e($label) ?></span>
          </button>
          <?php if ($n < count($stepLabels)): ?>
            <span class="mx-1 h-px w-4 shrink-0 bg-line-strong sm:w-7" aria-hidden="true"></span>
          <?php endif; ?>
        </li>
      <?php endforeach; ?>
    </ol>
    <div class="h-1 w-full overflow-hidden rounded-full bg-line" aria-hidden="true">
      <div class="h-full w-1/5 rounded-full bg-alam-500 transition-[width] duration-300" data-progress-fill></div>
    </div>
    <?php /* js/booking.js re-renders this in the active language as the
             visitor moves between steps; this is the starting value. */ ?>
    <p class="sr-only" aria-live="polite" data-progress-announce><?php echo e(tn('book.step.announce', $stepCount, ['step' => number(1), 'count' => number($stepCount), 'title' => $stepLabels[0]])) ?></p>
  </div>
</div>

<!-- ==================================================================
     Wizard
     ============================================================== -->
<div id="booking-form" class="bg-surface-soft py-8 sm:py-10 lg:py-12" data-action="<?php echo e(url('book.php', ['i' => $preTour])) ?>">
  <div class="container">
    <div class="grid gap-6 lg:grid-cols-[minmax(0,1fr)_340px] lg:gap-8 xl:grid-cols-[minmax(0,1fr)_368px]">

      <!-- ============ Steps column ============ -->
      <div class="min-w-0">

        <!-- Mobile summary -->
        <div class="mb-5 lg:hidden">
          <div class="card overflow-hidden">
            <button type="button" class="flex w-full items-center justify-between gap-3 p-4 text-left"
                    data-mobile-summary-toggle aria-expanded="false" aria-controls="mobile-summary">
              <span>
                <span class="block text-eyebrow font-bold uppercase text-alam-600"><?php echo e(t('book.summary.total')) ?></span>
                <span class="mt-1 block text-meta font-semibold text-ink" data-summary="tour"><?php echo e($bookingLabels['summary.noTour']) ?></span>
              </span>
              <span class="flex items-center gap-2">
                <span class="text-body-sm font-extrabold text-ink" data-summary="total">—</span>
                <?php echo icon('chevron-down', 'icon-sm text-ink-muted transition-transform duration-300', ['data-summary-chevron' => '']) ?>
              </span>
            </button>
            <div id="mobile-summary" class="accordion-panel" data-open="false">
              <div>
                <dl class="divide-y divide-line border-t border-line px-4 pb-4">
                  <?php echo summary_row($bookingLabels['summary.tour'], 'tour', t('book.notSelected')) ?>
                  <?php echo summary_row(t('book.summary.vehicle'), 'vehicle', t('book.notSelected'), '3') ?>
                  <?php echo summary_row(t('book.summary.language'), 'language', language_label($preLanguage), '3') ?>
                  <?php echo summary_row(t('book.summary.date'), 'date', t('book.notSelected'), '2') ?>
                  <?php echo summary_row(t('book.summary.slot'), 'slot', t('book.notSelected'), '2') ?>
                  <?php echo summary_row(t('book.summary.guide'), 'guide', t('book.notSelected'), '4') ?>
                  <div class="summary-row hidden" data-summary-row="original">
                    <dt class="summary-label"><?php echo e(t('book.summary.originalTotal')) ?></dt>
                    <dd class="summary-value" data-summary="original_total">—</dd>
                  </div>
                  <div class="summary-row hidden" data-summary-row="discount">
                    <dt class="summary-label"><?php echo e(t('book.summary.discount')) ?></dt>
                    <dd class="summary-value text-alam-700" data-summary="discount_amount">—</dd>
                  </div>
                  <?php echo summary_row(t('book.summary.total'), 'total-row', '—') ?>
                </dl>
              </div>
            </div>
          </div>
        </div>

        <!-- ---------------------------------------------------------
             Step 1 — Your details
             --------------------------------------------------------- -->
        <?php require __DIR__ . '/partials/booking/details.php'; ?>

        <!-- ---------------------------------------------------------
             Step 2 — Date & Time
             --------------------------------------------------------- -->
        <?php require __DIR__ . '/partials/booking/date_time.php'; ?>

        <!-- Step 3 — Tour / Experience options -->
        <?php require __DIR__ . '/partials/booking/options.php'; ?>

        <!-- Step 4 — Guide -->
        <?php require __DIR__ . '/partials/booking/guide.php'; ?>

        <!-- ---------------------------------------------------------
             Step 5 — Review & confirm
             --------------------------------------------------------- -->
        <?php require __DIR__ . '/partials/booking/review.php'; ?>

        <?php require __DIR__ . '/partials/booking/payment.php'; ?>
      </div>

      <!-- ============ Sticky summary (desktop) ============ -->
      <aside class="hidden lg:block" aria-labelledby="summary-title">
        <div class="sticky top-[calc(var(--frontend-header-h)+88px)]">
          <div class="card overflow-hidden">
            <div class="border-b border-line bg-surface-tint px-5 py-4">
              <div class="flex items-baseline justify-between gap-3">
                <h2 id="summary-title" class="text-body-sm font-bold text-ink"><?php echo e(t('book.summary.total')) ?></h2>
                <p class="text-lg font-extrabold text-alam-800" data-summary="total">—</p>
              </div>
              <?php if (trim((string) $bookingHero['price_details']) !== ''): ?>
                <p class="mt-1.5 text-body-sm text-ink-muted">
                  <?php echo nl2br(e($bookingHero['price_details'])) ?>
                </p>
              <?php endif; ?>
            </div>

            <dl class="divide-y divide-line px-5 py-1">
              <?php echo summary_row($bookingLabels['summary.tour'], 'tour', t('book.notSelected')) ?>
              <?php echo summary_row(t('book.summary.vehicle'), 'vehicle', t('book.notSelected'), '3') ?>
              <?php echo summary_row(t('book.summary.language'), 'language', language_label($preLanguage), '3') ?>
              <?php echo summary_row(t('book.summary.date'), 'date', t('book.notSelected'), '2') ?>
              <?php echo summary_row(t('book.summary.slot'), 'slot', t('book.notSelected'), '2') ?>
              <?php echo summary_row(t('book.summary.guide'), 'guide', t('book.notSelected'), '4') ?>
              <div class="summary-row hidden" data-summary-row="original">
                <dt class="summary-label"><?php echo e(t('book.summary.originalTotal')) ?></dt>
                <dd class="summary-value" data-summary="original_total">—</dd>
              </div>
              <div class="summary-row hidden" data-summary-row="discount">
                <dt class="summary-label"><?php echo e(t('book.summary.discount')) ?></dt>
                <dd class="summary-value text-alam-700" data-summary="discount_amount">—</dd>
              </div>
            </dl>

            <div class="border-t border-line bg-alam-50 px-5 py-4">
              <p class="mt-1.5 text-body-sm text-ink-muted">
                <?php echo e($bookingLabels['priceNote']) ?>
              </p>
            </div>
          </div>

          <?php if ($helpCard !== null): ?>
            <?php echo help_card(array_merge($helpCard, ['class' => 'mt-4'])) ?>
          <?php endif; ?>
        </div>
      </aside>
    </div>
  </div>

  <!-- Hidden fields carrying resolved labels for the future POST handler -->
  <input type="hidden" name="booking_token" value="<?php echo e($bookingData['submissionToken']); ?>">
  <input type="hidden" name="<?php echo e($this->security->get_csrf_token_name()); ?>" value="<?php echo e($this->security->get_csrf_hash()); ?>">
  <input type="hidden" name="guide_name" data-hidden="guide_name">
  <input type="hidden" name="estimated_total" data-hidden="estimated_total">

  <?php /* Loading overlay: booking.js moves it into the step being saved. */ ?>
  <div class="booking-busy hidden" data-booking-busy>
    <div class="booking-busy-card" role="status" aria-live="polite" tabindex="-1" data-booking-busy-card>
      <span class="booking-busy-spinner" aria-hidden="true"></span>
      <p class="text-body-sm font-semibold text-ink" data-booking-busy-text></p>
    </div>
  </div>
</div>

<script type="application/json" id="alam-booking-data">
<?php echo json_encode($bookingData, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) ?>
</script>

<script
  src="https://www.google.com/recaptcha/enterprise.js?render=<?php echo rawurlencode($bookingData['recaptcha']['siteKey']) ?>"
  defer></script>
<?php require_once __DIR__ . '/footer.php'; ?>
