<section class="panel p-5 sm:p-7" data-step="1" aria-labelledby="step-1-title">
    <header class="border-b border-line pb-5">
        <p class="eyebrow-plain" data-step-caption><?php echo e(t('book.step.of', ['step' => number(1), 'count' => number($stepCount)])) ?></p>
        <h2 id="step-1-title" class="mt-2.5 text-h3"><?php echo e(t('book.s1.title')) ?></h2>
        <p class="mt-2 text-body-sm text-ink-muted">
            <?php echo e(t('book.s1.text')) ?>
        </p>
    </header>

    <div class="grid gap-5 pt-6 sm:grid-cols-2">
        <div class="sm:col-span-2">
            <label for="full_name" class="field-label"><?php echo e(t('book.field.fullName')) ?> <span class="text-red-600">*</span></label>
            <input type="text" id="full_name" name="full_name" class="input" autocomplete="name" required>
            <p class="mt-1.5 hidden text-meta font-medium text-red-600" data-error="full_name"><?php echo e(t('book.error.fullName')) ?></p>
        </div>

        <div>
            <label for="email" class="field-label"><?php echo e(t('book.field.email')) ?> <span class="text-red-600">*</span></label>
            <?php /* An email address is a Latin, left-to-right value: dir keeps
                              the @ and the dots where the reader expects them. */ ?>
            <input type="email" id="email" name="email" class="input" autocomplete="email" dir="ltr" required>
            <p class="mt-1.5 hidden text-meta font-medium text-red-600" data-error="email"><?php echo e(t('book.error.email')) ?></p>
        </div>

        <div>
            <label for="country" class="field-label"><?php echo e(t('book.field.country')) ?> <span class="text-red-600">*</span></label>
            <?php /* autocomplete="country-name" is kept, and so is the posted
                              value: the browser's stored country name is the English
                              one, and that is still what this field submits. */ ?>
            <select id="country" name="country" class="select js-select2" data-placeholder="<?php echo e(t('select.countryPlaceholder')) ?>"
                            autocomplete="country-name" required>
                <option value=""><?php echo e(t('select.chooseCountry')) ?></option>
                <?php foreach ($countries as $c): ?>
                    <option value="<?php echo e($c) ?>"><?php echo e(country_label($c)) ?></option>
                <?php endforeach; ?>
            </select>
            <p class="mt-1.5 hidden text-meta font-medium text-red-600" data-error="country"><?php echo e(t('book.error.country')) ?></p>
        </div>

        <div class="sm:col-span-2">
            <label for="mobile_input" class="field-label"><?php echo e(t('book.field.mobile')) ?> <span class="text-red-600">*</span></label>
            <?php echo phone_field([
                    'id'        => 'mobile_input',
                    'name'      => 'mobile',
                    'required'  => true,
                    'error_key' => 'mobile',
            ]) ?>
            <span class="field-hint"><?php echo e(t('book.field.mobileHint')) ?></span>
            <p class="mt-1.5 hidden text-meta font-medium text-red-600" data-error="mobile"><?php echo e(t('book.error.mobile')) ?></p>
        </div>

        <div class="sm:col-span-2">
            <label for="guests" class="field-label"><?php echo e(t('book.field.guests')) ?> <span class="text-red-600">*</span></label>
            <?php
            $guestMaximum = 0;
            foreach ($tours as $bookingTour) {
                    if ($bookingTour['slug'] === $preTour) {
                            $guestMaximum = (int) $bookingTour['capacity'];
                            break;
                    }
            }
            $numberStepper = [
                    'id' => 'guests',
                    'name' => 'guests',
                    'max' => $guestMaximum,
                    'value' => 1,
                    'required' => true,
            ];
            require dirname(__DIR__) . '/number_stepper.php';
            ?>
            <p class="mt-1.5 hidden text-meta text-red-600" data-error="guests"><?php echo e($bookingLabels['error.guests']) ?></p>
        </div>

        <div class="sm:col-span-2">
            <label for="pickup_location" class="field-label"><?php echo e(t('book.field.pickup')) ?> <span class="text-red-600">*</span></label>
            <?php /* js/place-autocomplete.js turns this into a Google Places combobox
                     limited to Madinah. Free text still works: the token field is only
                     filled when a suggestion is picked, and cleared when the text is edited. */ ?>
            <div class="relative" data-place-autocomplete
                 data-suggest-url="<?php echo e(base_url(current_locale() . '/booking-places')) ?>"
                 data-details-url="<?php echo e(base_url(current_locale() . '/booking-place')) ?>">
                <input type="text" id="pickup_location" name="pickup_location" class="input" maxlength="255"
                              placeholder="<?php echo e(t('book.field.pickupPlaceholder')) ?>" data-place-input required>
                <input type="hidden" name="pickup_place_token" value="" data-place-token>
                <ul id="pickup_location-listbox" class="place-suggestions hidden" role="listbox"
                    aria-label="<?php echo e(t('book.place.listLabel')) ?>" data-place-list></ul>
                <p class="sr-only" role="status" aria-live="polite" data-place-status></p>
            </div>
            <span class="field-hint"><?php echo e(t('book.field.pickupHint')) ?></span>
            <p class="mt-1.5 hidden text-meta font-medium text-red-600" data-error="pickup_location"><?php echo e(t('book.error.pickup')) ?></p>
        </div>

    </div>

    <footer class="mt-8 flex items-center justify-between gap-3 border-t border-line pt-6">
        <a href="<?php echo e($bookingListingUrl) ?>" class="btn-link">
            <?php echo icon('arrow-left', 'icon-sm icon-flip') ?><?php echo e($bookingLabels['backToTours']) ?>
        </a>
        <button type="button" class="btn-primary" data-next="2"><?php echo e(t('cta.continue')) ?><?php echo icon('arrow-right', 'icon-sm icon-flip') ?></button>
    </footer>
</section>
