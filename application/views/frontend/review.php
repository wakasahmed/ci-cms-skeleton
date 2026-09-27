<?php

require_once __DIR__ . '/functions.php';

$isTour = trim((string) $booking['tour_type']) === 'Tour';
$bookingId = (int) $booking['book_id'];
$action = base_url(
    current_locale() . '/review/' . $bookingId . '/' . rawurlencode($reviewToken)
);
$config = array(
    'page_title' => t('review.meta.title'),
    'meta_description' => t('review.meta.description'),
    'active' => '',
    'robots' => 'noindex, nofollow',
    'canonical' => $action,
    'fontawesome_all' => true,
    'scripts' => array('js/review.js'),
);
$ratingFields = array(
    'overall_rating' => t('review.overall'),
    'activity_rating' => t($isTour ? 'review.activity' : 'review.activity.experience'),
);
if ($isTour) {
    $ratingFields['guide_rating'] = t('review.guideRating');
}
$ratingFields['vehicle_rating'] = t('review.vehicleRating');
$ratingFields['driver_rating'] = t('review.driverRating');
$ratingFields['service_rating'] = t('review.serviceRating');

$renderRating = function ($name, $label) use ($reviewValues, $reviewErrors) {
    $value = isset($reviewValues[$name]) ? (int) $reviewValues[$name] : 0;
    $hasError = isset($reviewErrors[$name]);
    ?>
    <fieldset class="rounded-card border p-4 sm:p-5 <?php echo $hasError ? 'border-red-300 bg-red-50/40' : 'border-line bg-white'; ?>" data-review-rating data-empty-label="<?php echo e(t('review.rating.empty')); ?>">
      <legend class="px-1 text-body-sm font-bold text-ink">
        <?php echo e($label); ?>
        <span class="ms-1 text-meta font-semibold text-alam-600"><?php echo e(t('review.required')); ?></span>
      </legend>
      <input type="hidden" name="<?php echo e($name); ?>" value="<?php echo $value > 0 ? $value : ''; ?>" data-review-rating-input>
      <div class="mt-2 flex flex-wrap items-center gap-3">
        <div class="flex gap-2" role="radiogroup" aria-label="<?php echo e($label); ?>">
          <?php for ($star = 1; $star <= 5; $star++) { ?>
            <button type="button"
                    class="inline-flex items-center justify-center border-0 bg-transparent p-1 text-3xl leading-none text-line-strong transition duration-150 hover:scale-110 hover:text-alam-500 focus:outline-none"
                    role="radio"
                    aria-checked="<?php echo $value === $star ? 'true' : 'false'; ?>"
                    aria-label="<?php echo $star; ?> <?php echo e(t($star === 1 ? 'review.star' : 'review.stars')); ?>"
                    data-review-star="<?php echo $star; ?>">
              <i class="<?php echo $star <= $value ? 'fa-solid' : 'fa-regular'; ?> fa-star" aria-hidden="true"></i>
            </button>
          <?php } ?>
        </div>
        <span class="min-w-32 text-meta text-ink-muted" data-review-rating-caption aria-live="polite">
          <?php echo $value > 0
              ? $value . ' / 5'
              : e(t('review.rating.empty')); ?>
        </span>
      </div>
      <?php if ($hasError) { ?>
        <p class="mt-2 text-meta font-semibold text-red-700" data-rating-error><?php echo e($reviewErrors[$name]); ?></p>
      <?php } ?>
    </fieldset>
    <?php
};

require_once __DIR__ . '/header.php';
?>

<main id="main" class="bg-surface-soft">
  <section class="relative isolate overflow-hidden bg-alam-900">
    <div class="absolute inset-0 -z-10 bg-[radial-gradient(circle_at_top_right,rgba(255,255,255,.16),transparent_40%),linear-gradient(135deg,#312b55,#63569b)]" aria-hidden="true"></div>
    <div class="container py-12 sm:py-16 lg:py-20">
      <div class="max-w-3xl">
        <p class="eyebrow text-alam-200"><?php echo e(t('review.eyebrow')); ?></p>
        <h1 class="mt-3.5 text-h1 text-white text-shadow-hero"><?php echo e(t('review.title')); ?></h1>
        <p class="mt-4 max-w-2xl text-body text-white/80"><?php echo e(t('review.intro')); ?></p>
      </div>
    </div>
  </section>

  <div class="container py-8 sm:py-10 lg:py-12">
    <div class="mx-auto grid max-w-6xl gap-6 lg:grid-cols-[minmax(0,1fr)_330px] lg:gap-8">
      <div class="min-w-0">
        <?php if (in_array($reviewState, array('success', 'submitted', 'unavailable'), true)) { ?>
          <?php
          $stateTitle = $reviewState === 'success'
              ? t('review.thanks.title')
              : t($reviewState === 'submitted' ? 'review.submitted.title' : 'review.unavailable.title');
          $stateText = $reviewState === 'success'
              ? t('review.thanks.text')
              : t($reviewState === 'submitted' ? 'review.submitted.text' : 'review.unavailable.text');
          ?>
          <section class="card p-6 text-center sm:p-10" aria-labelledby="review-state-title"<?php echo $reviewState === 'success' ? ' data-review-success' : ''; ?>>
            <span class="mx-auto grid h-16 w-16 place-items-center rounded-full bg-alam-50 text-3xl text-alam-600" aria-hidden="true">
              <i class="fa-solid <?php echo $reviewState === 'unavailable' ? 'fa-clock' : 'fa-circle-check'; ?>"></i>
            </span>
            <h2 id="review-state-title" class="mt-5 text-h3 text-ink"><?php echo e($stateTitle); ?></h2>
            <p class="mx-auto mt-3 max-w-xl text-body text-ink-muted"><?php echo e($stateText); ?></p>
            <a class="btn btn-primary mt-7" href="<?php echo e(url('tours.php')); ?>">
              <?php echo e(t('review.back')); ?>
              <?php echo icon('arrow-right', 'icon-sm icon-flip'); ?>
            </a>
          </section>
        <?php } else { ?>
          <section class="card p-5 sm:p-7 lg:p-8" aria-labelledby="review-form-title">
            <h2 id="review-form-title" class="text-h3 text-ink"><?php echo e(t('review.form.title')); ?></h2>
            <p class="mt-2 text-body-sm text-ink-muted"><?php echo e(t('review.form.hint')); ?></p>

            <?php if (!empty($reviewErrors)) { ?>
              <div class="mt-5 rounded-card border border-red-200 bg-red-50 p-4 text-body-sm text-red-800" role="alert">
                <?php if (count($reviewErrors) > (isset($reviewErrors['form']) ? 1 : 0)) { ?>
                  <strong><?php echo e(t('review.error.summary')); ?></strong>
                <?php } ?>
                <?php if (isset($reviewErrors['form'])) { ?>
                  <span class="mt-1 block"><?php echo e($reviewErrors['form']); ?></span>
                <?php } ?>
              </div>
            <?php } ?>

            <form class="mt-6" method="post" action="<?php echo e($action); ?>" data-review-form novalidate>
              <input type="hidden" name="recaptcha_token" value="" data-recaptcha-token>
              <div class="grid gap-4 sm:grid-cols-2">
                <?php foreach ($ratingFields as $name => $label) { ?>
                  <?php $renderRating($name, $label); ?>
                <?php } ?>
              </div>

              <div class="mt-6">
                <label class="text-body-sm font-bold text-ink" for="review-comments"><?php echo e(t('review.comments')); ?></label>
                <textarea id="review-comments" name="comments" rows="6" maxlength="5000"
                          class="mt-2 w-full rounded-control border border-line-strong bg-white px-4 py-3 text-body-sm text-ink outline-none transition placeholder:text-ink-faint focus:border-alam-500 focus:ring-2 focus:ring-alam-100"
                          placeholder="<?php echo e(t('review.comments.placeholder')); ?>"><?php echo e($reviewValues['comments']); ?></textarea>
                <p class="mt-2 text-meta text-ink-muted"><?php echo e(t('review.comments.hint')); ?></p>
                <?php if (isset($reviewErrors['comments'])) { ?>
                  <p class="mt-1 text-meta font-semibold text-red-700"><?php echo e($reviewErrors['comments']); ?></p>
                <?php } ?>
              </div>

              <label class="mt-5 flex cursor-pointer items-start gap-3 rounded-control border border-line bg-surface-soft p-4 text-body-sm text-ink">
                <input type="checkbox" name="public_consent" value="1" class="mt-1 h-4 w-4 rounded border-line-strong text-alam-600 focus:ring-alam-500"<?php echo !empty($reviewValues['public_consent']) ? ' checked' : ''; ?>>
                <span><?php echo e(t('review.consent')); ?></span>
              </label>

              <button type="submit" class="btn btn-primary mt-6 w-full sm:w-auto" data-review-submit data-submitting-label="<?php echo e(t('review.submitting')); ?>">
                <span data-review-submit-label><?php echo e(t('review.submit')); ?></span>
                <?php echo icon('arrow-right', 'icon-sm icon-flip'); ?>
              </button>
            </form>
          </section>
        <?php } ?>
      </div>

      <aside>
        <section class="card overflow-hidden" aria-labelledby="booking-summary-title">
          <div class="bg-alam-50 p-5">
            <p class="eyebrow text-alam-600"><?php echo e(t('review.completed')); ?></p>
            <h2 id="booking-summary-title" class="mt-2 text-h4 text-ink"><?php echo e($booking['book_tour_name']); ?></h2>
            <p class="mt-2 text-meta text-ink-muted"><?php echo e(t('review.booking')); ?> #<?php echo $bookingId; ?></p>
          </div>
          <dl class="divide-y divide-line px-5">
            <div class="py-4"><dt class="text-meta font-semibold text-ink-muted"><?php echo e(t('review.customer')); ?></dt><dd class="mt-1 text-body-sm font-bold text-ink"><?php echo e($booking['book_name']); ?></dd></div>
            <div class="py-4"><dt class="text-meta font-semibold text-ink-muted"><?php echo e(t('review.date')); ?></dt><dd class="mt-1 text-body-sm font-bold text-ink"><?php echo e(site_date($booking['book_date'])); ?> · <?php echo e($booking['review_slot_name']); ?></dd></div>
            <?php if ($isTour && !empty($booking['review_guide_name'])) { ?>
              <div class="py-4"><dt class="text-meta font-semibold text-ink-muted"><?php echo e(t('review.guide')); ?></dt><dd class="mt-1 text-body-sm font-bold text-ink"><?php echo e($booking['review_guide_name']); ?></dd></div>
            <?php } ?>
            <?php if (!empty($booking['book_vehicle_name'])) { ?>
              <div class="py-4"><dt class="text-meta font-semibold text-ink-muted"><?php echo e(t('review.vehicle')); ?></dt><dd class="mt-1 text-body-sm font-bold text-ink"><?php echo e($booking['book_vehicle_name']); ?></dd></div>
            <?php } ?>
          </dl>
        </section>
      </aside>
    </div>
  </div>
</main>

<?php if ($reviewState === 'form') { ?>
  <script
    src="https://www.google.com/recaptcha/enterprise.js?render=<?php echo rawurlencode($recaptchaSiteKey); ?>"
    data-recaptcha-site-key="<?php echo e($recaptchaSiteKey); ?>"
    data-recaptcha-action="<?php echo e($recaptchaAction); ?>"
    defer></script>
<?php } ?>
<?php require_once __DIR__ . '/footer.php'; ?>
