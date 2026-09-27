<?php
/**
 * Hero availability search — the tour/date/vehicle/language quick-check form.
 *
 * Tours come from the database ($heroSearch, built by
 * Frontend::buildHeroSearchData()). The tour and date are required; language
 * and vehicle are optional. js/hero-search.js first asks
 * Frontend::booking_availability() whether a guide is free for that choice,
 * then sends the visitor to the booking page, or explains why not with
 * SweetAlert2. Every submitted ID is an encrypted booking reference.
 * Vehicles and languages depend on the selected tour, so the script swaps
 * their options from the JSON block below, which also carries the alert copy.
 */

$heroSearch = isset($heroSearch) && is_array($heroSearch) ? $heroSearch : [];
$heroTours = isset($heroSearch['tours']) && is_array($heroSearch['tours'])
    ? $heroSearch['tours']
    : [];

$heroTourOptions = [];

foreach ($heroTours as $heroTour) {
    $vehicles = [];

    foreach ($heroTour['vehicles'] as $vehicle) {
        $vehicles[] = [
            'id'    => $vehicle['id'],
            'label' => $vehicle['name'] . ' · ' . tn('count.upTo', (int) $vehicle['capacity']),
        ];
    }

    $heroTourOptions[$heroTour['id']] = [
        'vehicles'  => $vehicles,
        'languages' => $heroTour['languages'],
    ];
}

$heroConfig = [
    'tours'    => $heroTourOptions,
    'messages' => [
        'checking'         => t('hero.form.checking'),
        'confirm'          => t('book.alert.confirm'),
        'unavailableTitle' => t('hero.form.unavailable.title'),
        'unavailableText'  => t('hero.form.unavailable.text'),
        'errorTitle'       => t('hero.form.error.title'),
        'errorText'        => t('hero.form.error.text'),
    ],
];
?>
<!-- Availability search -->
<div class="lg:justify-self-end lg:w-full lg:max-w-[440px]">
  <form class="rounded-feature border border-white/15 bg-white/95 p-5 shadow-panel backdrop-blur sm:p-6"
        action="<?php echo e(url('book.php')) ?>" method="get" data-availability-form
        data-availability-url="<?php echo e(base_url(current_locale() . '/booking-availability')) ?>" novalidate>
    <div class="flex items-center gap-2.5">
      <span class="grid h-9 w-9 place-items-center rounded-control bg-alam-100 text-alam-600">
        <?php echo icon('search', 'icon-md') ?>
      </span>
      <div>
        <h2 class="text-body-sm font-bold text-ink"><?php echo e(t('hero.form.title')) ?></h2>
        <p class="text-meta text-ink-muted"><?php echo e(t('hero.form.text')) ?></p>
      </div>
    </div>

    <div class="mt-5 space-y-3.5">
      <div>
        <label for="hero-tour" class="field-label"><?php echo e(t('field.tour')) ?> <span class="text-red-600">*</span></label>
        <select id="hero-tour" name="i" class="select js-select2" data-availability-tour
                aria-describedby="hero-tour-error" required>
          <option value=""><?php echo e(t('hero.form.selectTour')) ?></option>
          <?php foreach ($heroTours as $heroTour): ?>
            <option value="<?php echo e($heroTour['id']) ?>"><?php echo e($heroTour['title']) ?></option>
          <?php endforeach; ?>
        </select>
        <p id="hero-tour-error" class="mt-1.5 hidden text-meta font-medium text-red-600" data-availability-error="tour" role="alert">
          <?php echo e(t('book.error.tour')) ?>
        </p>
      </div>

      <div class="grid gap-3.5 sm:grid-cols-2">
        <div>
          <label for="hero-date" class="field-label"><?php echo e(t('field.date')) ?> <span class="text-red-600">*</span></label>
          <?php echo date_field([
              'id'          => 'hero-date',
              'name'        => 'date',
              'type'        => 'booking',
              // Server-side dates: the first bookable day is tomorrow, so today and earlier stay disabled.
              'min'         => $heroSearch['minimumDate'] ?? date('Y-m-d', strtotime('tomorrow')),
              'max'         => $heroSearch['maximumDate'] ?? '',
              'alt_format'  => t('format.flatpickr.short'),
              'placeholder' => t('date.select'),
              'required'    => true,
              'attrs'       => ['aria-describedby' => 'hero-date-error'],
          ]) ?>
          <p id="hero-date-error" class="mt-1.5 hidden text-meta font-medium text-red-600" data-availability-error="date" role="alert">
            <?php echo e(t('book.error.date')) ?>
          </p>
        </div>
        <div>
          <label for="hero-language" class="field-label"><?php echo e(t('field.language')) ?></label>
          <select id="hero-language" name="language" class="select js-select2" data-availability-language
                  data-placeholder="<?php echo e(t('hero.form.anyLanguage')) ?>" disabled>
            <option value=""><?php echo e(t('hero.form.anyLanguage')) ?></option>
          </select>
        </div>
      </div>

      <div>
        <label for="hero-vehicle" class="field-label"><?php echo e(t('field.guestsVehicle')) ?></label>
        <select id="hero-vehicle" name="vehicle" class="select js-select2" data-availability-vehicle
                data-placeholder="<?php echo e(t('hero.form.anyVehicle')) ?>" disabled>
          <option value=""><?php echo e(t('hero.form.anyVehicle')) ?></option>
        </select>
      </div>
    </div>

    <button type="submit" class="btn-primary mt-5 w-full">
      <?php echo e(t('hero.form.submit')) ?><?php echo icon('arrow-right', 'icon-sm icon-flip') ?>
    </button>
    <p class="mt-3 text-center text-body-sm text-ink-soft">
      <?php echo e(t('hero.form.note')) ?>
    </p>

    <script type="application/json" data-availability-data>
<?php echo json_encode($heroConfig, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) ?>
    </script>
  </form>
</div>
