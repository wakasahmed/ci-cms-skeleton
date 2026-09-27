<?php

require_once __DIR__ . '/functions.php';
require_once __DIR__ . '/header.php';

if (!empty($config['banner'])) {
    require_once __DIR__ . '/banner.php';
}

require_once __DIR__ . '/inc/page-content.php';

$audience = isset($sections['audience_fit']) && is_array($sections['audience_fit']) ? $sections['audience_fit'] : [];
$audienceHead = isset($audience['head']) && is_array($audience['head']) ? $audience['head'] : [];
$audienceCards = isset($audience['cards']) && is_array($audience['cards']) ? $audience['cards'] : [];
$hasAudience = !empty($audienceHead['eyebrow']) || !empty($audienceHead['title'])
    || !empty($audience['contents_html']) || !empty($audienceCards);

$formSection = isset($sections['plan_your_visit_form']) && is_array($sections['plan_your_visit_form']) ? $sections['plan_your_visit_form'] : [];
$formHead = isset($formSection['head']) && is_array($formSection['head']) ? $formSection['head'] : [];
$formIntroHtml = !empty($formSection['contents_html']) ? $formSection['contents_html'] : '';

$values = $form['values'];
$errors = $form['errors'];
$activeStep = (int) $form['activeStep'];
?>

<?php if ($hasAudience): ?>
<!-- ==================================================================
     Who this helps
     ============================================================== -->
<section class="bg-white py-14 sm:py-16 lg:py-20">
  <div class="container">
    <?php echo section_head([
        'eyebrow' => $audienceHead['eyebrow'] ?? '',
        'title'   => $audienceHead['title'] ?? '',
        'align'   => 'center',
    ]) ?>
    <?php if (!empty($audience['contents_html'])): ?>
      <div class="page-contents mx-auto mt-4 max-w-2xl text-center"><?php echo $audience['contents_html'] ?></div>
    <?php endif; ?>

    <?php if (!empty($audienceCards)): ?>
      <ul class="mt-10 grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
        <?php foreach ($audienceCards as $i => $card): ?>
          <li class="card flex gap-4 p-5" data-reveal data-reveal-delay="<?php echo $i * 50 ?>">
            <?php if (!empty($card['icon'])): ?>
              <span class="grid h-10 w-10 shrink-0 place-items-center rounded-control bg-alam-50 text-alam-600">
                <?php echo icon($card['icon'], 'icon-md') ?>
              </span>
            <?php endif; ?>
            <div>
              <?php if (!empty($card['title'])): ?>
                <h3 class="text-body-sm font-bold text-ink"><?php echo e($card['title']) ?></h3>
              <?php endif; ?>
              <?php if (!empty($card['text'])): ?>
                <p class="mt-1.5 text-body-sm text-ink-muted"><?php echo e($card['text']) ?></p>
              <?php endif; ?>
            </div>
          </li>
        <?php endforeach; ?>
      </ul>
    <?php endif; ?>
  </div>
</section>
<?php endif; ?>

<!-- ==================================================================
     Form
     ============================================================== -->
<section class="bg-surface-tint py-14 sm:py-16 lg:py-20">
  <div class="container">
    <div class="grid gap-8 lg:grid-cols-[minmax(0,1fr)_340px] lg:gap-10">

      <?php if (!empty($form['completed'])): ?>
        <p id="plan-status-success"
           class="self-start flex items-start gap-2.5 rounded-control border border-alam-200 bg-alam-50 p-4 text-meta text-alam-800"
           role="status" tabindex="-1">
          <?php echo icon('check', 'mt-0.5 icon-sm shrink-0') ?>
          <span>
            <?php echo $form['successMessage'] !== ''
                ? nl2br(e($form['successMessage']))
                : e(t('plan.completed.fallbackLabel')) ?>
          </span>
        </p>
      <?php else: ?>
        <!-- ---------------------------------------------------------------
             Planner request — a four-step wizard.

             The markup ships with every step visible, so with JavaScript off
             this stays one long form with native `required` validation intact.
             initFormWizard() in main.js collapses it to one step at a time;
             plan-your-trip.js saves each Continue to the server-side draft
             before the wizard advances.
             --------------------------------------------------------------- -->
        <form class="panel p-5 sm:p-7 lg:p-8" method="post" action="<?php echo e($formAction) ?>"
              data-wizard data-wizard-active-step="<?php echo (int) $activeStep ?>" data-save-step-url="<?php echo e($saveStepUrl) ?>" novalidate>
          <input type="hidden" name="form_token" value="<?php echo e($formToken) ?>" data-plan-form-token>
          <input type="hidden" name="recaptcha_token" value="" data-recaptcha-token>
          <?php if (!empty($formHead['eyebrow'])): ?><p class="eyebrow"><?php echo e($formHead['eyebrow']) ?></p><?php endif; ?>
          <?php if (!empty($formHead['title'])): ?>
            <h2 class="mt-3.5 text-h3"><?php echo e($formHead['title']) ?></h2>
          <?php endif; ?>
          <?php if ($formIntroHtml !== ''): ?>
            <div class="page-contents mt-2.5 max-w-prose text-body-sm text-ink-muted"><?php echo $formIntroHtml ?></div>
          <?php endif; ?>

          <?php /* Hidden unless plan-your-trip.js shows it after an AJAX success
                   response — the wizard stays in the DOM, resets and returns to
                   step 1 instead of being replaced (see the form's `reset`
                   listener in main.js). */ ?>
          <p id="plan-status-success"
             class="hidden mt-4 flex items-start gap-2.5 rounded-control border border-alam-200 bg-alam-50 p-4 text-meta text-alam-800"
             role="status" tabindex="-1">
            <?php echo icon('check', 'mt-0.5 icon-sm shrink-0') ?>
            <span data-plan-status-message></span>
          </p>
          <p id="plan-status-error"
             class="<?php echo isset($errors['_general']) ? '' : 'hidden ' ?>mt-4 contact-alert-danger flex items-start gap-2.5 rounded-control border p-4 text-meta"
             role="alert" tabindex="-1">
            <?php echo icon('info', 'mt-0.5 icon-sm shrink-0') ?>
            <span data-plan-status-message><?php echo e(isset($errors['_general']) ? $errors['_general'] : t('plan.error.saveFailed')) ?></span>
          </p>
          <?php if (!isset($errors['_general']) && !empty($errors)): ?>
            <p class="mt-4 text-meta font-semibold text-red-700" role="alert"><?php echo e(t('plan.error.summary')) ?></p>
          <?php endif; ?>

          <!-- Progress -->
          <div class="mt-7 flex items-center gap-4">
            <ol class="flex min-w-0 flex-1 items-center gap-2" aria-hidden="true">
              <?php for ($n = 1; $n <= 4; $n++): ?>
                <li class="h-1.5 flex-1 overflow-hidden rounded-full bg-line">
                  <span class="block h-full w-full origin-left scale-x-0 rounded-full bg-alam-500 transition-transform duration-300"
                        data-wizard-bar="<?php echo $n ?>"></span>
                </li>
              <?php endfor; ?>
            </ol>
            <p class="shrink-0 text-meta font-bold text-ink" data-wizard-label><?php echo e(tn('plan.step.label', 4, ['step' => number($activeStep), 'count' => number(4)])) ?></p>
          </div>
          <p class="sr-only" aria-live="polite" data-wizard-announce><?php echo e(tn('plan.step.announce', 4, ['step' => number($activeStep), 'count' => number(4), 'title' => t('plan.step1.title')])) ?></p>

          <!-- ---------- Step 1 — contact ---------- -->
          <section data-wizard-step="1" data-wizard-title="<?php echo e(t('plan.step1.title')) ?>" aria-labelledby="plan-step-1-title">
            <header class="mt-7 flex items-start gap-3.5">
              <span class="grid h-9 w-9 shrink-0 place-items-center rounded-control bg-alam-500 text-body-sm font-bold text-white">1</span>
              <div>
                <h3 id="plan-step-1-title" class="text-card-title font-bold text-ink"><?php echo e(t('plan.step1.title')) ?></h3>
                <p class="mt-1 text-body-sm text-ink-muted"><?php echo e(t('plan.step1.text')) ?></p>
              </div>
            </header>

            <div class="mt-5 grid gap-5 sm:grid-cols-2">
              <div>
                <label for="plan_name" class="field-label"><?php echo e(t('plan.field.name')) ?> <span class="text-red-600">*</span></label>
                <input type="text" id="plan_name" name="name" class="input" autocomplete="name" required
                       maxlength="255" value="<?php echo e($values['name']) ?>"
                       <?php echo isset($errors['name']) ? 'aria-invalid="true" aria-describedby="error-name"' : '' ?>>
                <p class="mt-1.5 <?php echo isset($errors['name']) ? '' : 'hidden ' ?>text-meta font-medium text-red-600" data-error="name" id="error-name">
                  <?php echo e($errors['name'] ?? t('plan.error.name')) ?>
                </p>
              </div>

              <div>
                <label for="plan_email" class="field-label"><?php echo e(t('plan.field.email')) ?> <span class="text-red-600">*</span></label>
                <input type="email" id="plan_email" name="email" class="input" autocomplete="email" dir="ltr" required
                       maxlength="255" value="<?php echo e($values['email']) ?>"
                       <?php echo isset($errors['email']) ? 'aria-invalid="true" aria-describedby="error-email"' : '' ?>>
                <p class="mt-1.5 <?php echo isset($errors['email']) ? '' : 'hidden ' ?>text-meta font-medium text-red-600" data-error="email" id="error-email">
                  <?php echo e($errors['email'] ?? t('plan.error.email')) ?>
                </p>
              </div>

              <div>
                <label for="plan_phone" class="field-label"><?php echo e(t('plan.field.phone')) ?> <span class="text-red-600">*</span></label>
                <?php echo phone_field([
                    'id'        => 'plan_phone',
                    'name'      => 'phone',
                    'value'     => $values['phone'],
                    'required'  => true,
                    'error_key' => 'phone',
                    'attrs'     => isset($errors['phone'])
                        ? ['aria-invalid' => 'true', 'aria-describedby' => 'phone-error-phone']
                        : [],
                ]) ?>
                <p class="mt-1.5 <?php echo isset($errors['phone']) ? '' : 'hidden ' ?>text-meta font-medium text-red-600" data-error="phone" id="phone-error-phone">
                  <?php echo e($errors['phone'] ?? t('plan.error.phone')) ?>
                </p>
              </div>

              <div>
                <label for="plan_country" class="field-label"><?php echo e(t('plan.field.country')) ?> <span class="text-red-600">*</span></label>
                <select id="plan_country" name="country" class="select js-select2" data-placeholder="<?php echo e(t('select.countryPlaceholder')) ?>" required
                        <?php echo isset($errors['country']) ? 'aria-invalid="true" aria-describedby="error-country"' : '' ?>>
                  <option value=""><?php echo e(t('select.chooseCountry')) ?></option>
                  <?php foreach ($countries as $c): ?>
                    <option value="<?php echo (int) $c['id'] ?>" <?php echo $values['country'] === (int) $c['id'] ? 'selected' : '' ?>>
                      <?php echo e($c['label']) ?>
                    </option>
                  <?php endforeach; ?>
                </select>
                <p class="mt-1.5 <?php echo isset($errors['country']) ? '' : 'hidden ' ?>text-meta font-medium text-red-600" data-error="country" id="error-country">
                  <?php echo e($errors['country'] ?? t('plan.error.country')) ?>
                </p>
              </div>
            </div>
          </section>

          <!-- ---------- Step 2 — the trip ---------- -->
          <section data-wizard-step="2" data-wizard-title="<?php echo e(t('plan.step2.title')) ?>" aria-labelledby="plan-step-2-title">
            <header class="mt-7 flex items-start gap-3.5">
              <span class="grid h-9 w-9 shrink-0 place-items-center rounded-control bg-alam-500 text-body-sm font-bold text-white">2</span>
              <div>
                <h3 id="plan-step-2-title" class="text-card-title font-bold text-ink"><?php echo e(t('plan.step2.title')) ?></h3>
                <p class="mt-1 text-body-sm text-ink-muted"><?php echo e(t('plan.step2.text')) ?></p>
              </div>
            </header>

            <div class="mt-5 grid gap-5 sm:grid-cols-2">
              <div>
                <label for="plan_arrival" class="field-label"><?php echo e(t('plan.field.arrival')) ?></label>
                <?php echo date_field([
                    'id'        => 'plan_arrival',
                    'name'      => 'arrival_date',
                    'type'      => 'booking',
                    'min'       => date('Y-m-d'),
                    'value'     => $values['arrival_date'],
                    'clearable' => true,
                    'label'     => 'date.label.arrival',
                    'attrs'     => isset($errors['arrival_date'])
                        ? ['aria-invalid' => 'true', 'aria-describedby' => 'error-arrival_date']
                        : [],
                ]) ?>
                <p class="mt-1.5 <?php echo isset($errors['arrival_date']) ? '' : 'hidden ' ?>text-meta font-medium text-red-600" data-error="arrival_date" id="error-arrival_date">
                  <?php echo e($errors['arrival_date'] ?? t('plan.error.arrival')) ?>
                </p>
              </div>

              <div>
                <label for="plan_departure" class="field-label"><?php echo e(t('plan.field.departure')) ?></label>
                <?php echo date_field([
                    'id'        => 'plan_departure',
                    'name'      => 'departure_date',
                    'type'      => 'booking',
                    'min'       => date('Y-m-d'),
                    'value'     => $values['departure_date'],
                    'link_min'  => 'plan_arrival',
                    'clearable' => true,
                    'label'     => 'date.label.departure',
                    'attrs'     => isset($errors['departure_date'])
                        ? ['aria-invalid' => 'true', 'aria-describedby' => 'error-departure_date']
                        : [],
                ]) ?>
                <p class="mt-1.5 <?php echo isset($errors['departure_date']) ? '' : 'hidden ' ?>text-meta font-medium text-red-600" data-error="departure_date" id="error-departure_date">
                  <?php echo e($errors['departure_date'] ?? t('plan.error.departure')) ?>
                </p>
              </div>

              <div>
                <label for="plan_guests" class="field-label"><?php echo e(t('plan.field.guests')) ?> <span class="text-red-600">*</span></label>
                <input type="number" id="plan_guests" name="guests" class="input" min="1" max="60" step="1"
                       value="<?php echo (int) $values['guests'] ?>" dir="ltr" required
                       <?php echo isset($errors['guests']) ? 'aria-invalid="true" aria-describedby="error-guests"' : '' ?>>
                <span class="field-hint"><?php echo e(t('plan.field.guestsHint')) ?></span>
                <p class="mt-1.5 <?php echo isset($errors['guests']) ? '' : 'hidden ' ?>text-meta font-medium text-red-600" data-error="guests" id="error-guests">
                  <?php echo e($errors['guests'] ?? t('plan.error.guests')) ?>
                </p>
              </div>

              <div>
                <label for="plan_language" class="field-label"><?php echo e(t('field.language')) ?></label>
                <select id="plan_language" name="language" class="select js-select2"
                        <?php echo isset($errors['language']) ? 'aria-invalid="true" aria-describedby="error-language"' : '' ?>>
                  <option value=""><?php echo e(t('plan.field.slotAny')) ?></option>
                  <?php foreach ($languages as $l): ?>
                    <option value="<?php echo (int) $l['id'] ?>" <?php echo $values['language'] === (int) $l['id'] ? 'selected' : '' ?>>
                      <?php echo e($l['label']) ?>
                    </option>
                  <?php endforeach; ?>
                </select>
                <?php if (isset($errors['language'])): ?>
                  <p class="mt-1.5 text-meta font-medium text-red-600" id="error-language"><?php echo e($errors['language']) ?></p>
                <?php endif; ?>
              </div>
            </div>
          </section>

          <!-- ---------- Step 3 — interests ---------- -->
          <section data-wizard-step="3" data-wizard-title="<?php echo e(t('plan.step3.title')) ?>" aria-labelledby="plan-step-3-title">
            <header class="mt-7 flex items-start gap-3.5">
              <span class="grid h-9 w-9 shrink-0 place-items-center rounded-control bg-alam-500 text-body-sm font-bold text-white">3</span>
              <div>
                <h3 id="plan-step-3-title" class="text-card-title font-bold text-ink"><?php echo e(t('plan.step3.title')) ?></h3>
                <p class="mt-1 text-body-sm text-ink-muted"><?php echo e(t('plan.step3.text')) ?></p>
              </div>
            </header>

            <div class="mt-5 grid gap-5">
              <fieldset>
                <legend class="field-label"><?php echo e($interestsLabel) ?></legend>
                <div class="mt-1 grid gap-2.5 sm:grid-cols-2">
                  <?php foreach ($interestOptions as $option): ?>
                    <label class="flex cursor-pointer items-center gap-2.5 rounded-control border border-line-strong bg-white px-3.5 py-2.5 text-meta transition-colors hover:border-alam-400">
                      <input type="checkbox" name="interests[]" value="<?php echo e($option['value']) ?>"
                             class="h-4 w-4 rounded border-line-strong text-alam-500 focus:ring-alam-500/30"
                             <?php echo in_array($option['value'], $values['interests'], true) ? 'checked' : '' ?>>
                      <span class="text-ink"><?php echo e($option['label']) ?></span>
                    </label>
                  <?php endforeach; ?>
                </div>
                <?php if (isset($errors['interests'])): ?>
                  <p class="mt-1.5 text-meta font-medium text-red-600" data-error="interests"><?php echo e($errors['interests']) ?></p>
                <?php endif; ?>
              </fieldset>

              <div>
                <label for="plan_preferred_slot" class="field-label"><?php echo e($timeLabel) ?></label>
                <select id="plan_preferred_slot" name="preferred_slot" class="select js-select2"
                        <?php echo isset($errors['preferred_slot']) ? 'aria-invalid="true" aria-describedby="error-preferred_slot"' : '' ?>>
                  <option value=""><?php echo e(t('plan.field.slotAny')) ?></option>
                  <?php foreach ($timeOptions as $s): ?>
                    <option value="<?php echo e($s['value']) ?>" <?php echo $values['preferred_slot'] === $s['value'] ? 'selected' : '' ?>>
                      <?php echo e($s['label']) ?>
                    </option>
                  <?php endforeach; ?>
                </select>
                <p class="mt-1.5 <?php echo isset($errors['preferred_slot']) ? '' : 'hidden ' ?>text-meta font-medium text-red-600" data-error="preferred_slot" id="error-preferred_slot">
                  <?php echo e($errors['preferred_slot'] ?? t('plan.error.slot')) ?>
                </p>
              </div>
            </div>
          </section>

          <!-- ---------- Step 4 — anything else ---------- -->
          <section data-wizard-step="4" data-wizard-title="<?php echo e(t('plan.step4.titleShort')) ?>" aria-labelledby="plan-step-4-title">
            <header class="mt-7 flex items-start gap-3.5">
              <span class="grid h-9 w-9 shrink-0 place-items-center rounded-control bg-alam-500 text-body-sm font-bold text-white">4</span>
              <div>
                <h3 id="plan-step-4-title" class="text-card-title font-bold text-ink"><?php echo e(t('plan.step4.title')) ?></h3>
                <p class="mt-1 text-body-sm text-ink-muted"><?php echo e(t('plan.step4.text')) ?></p>
              </div>
            </header>

            <div class="mt-5">
              <label for="plan_notes" class="field-label"><?php echo e(t('plan.field.notes')) ?></label>
              <textarea id="plan_notes" name="notes" class="input" rows="5" maxlength="5000"
                        placeholder="<?php echo e(t('plan.field.notesPlaceholder')) ?>"
                        <?php echo isset($errors['notes']) ? 'aria-invalid="true" aria-describedby="error-notes"' : '' ?>><?php echo e($values['notes']) ?></textarea>
              <?php if (isset($errors['notes'])): ?>
                <p class="mt-1.5 text-meta font-medium text-red-600" id="error-notes"><?php echo e($errors['notes']) ?></p>
              <?php endif; ?>
            </div>

            <p class="mt-5 text-body-sm text-ink-soft">
              <?php echo e(t('privacy.notice.before')) ?><a href="<?php echo e(url('privacy-policy.php')) ?>" class="font-semibold text-alam-600 underline underline-offset-4"><?php echo e(t('legal.privacyPolicy')) ?></a><?php echo e(t('privacy.notice.after')) ?>
            </p>
          </section>

          <!-- Navigation -->
          <div class="mt-7 border-t border-line pt-6">
            <div class="flex flex-col gap-3 sm:flex-row-reverse sm:items-center">
              <button type="button" class="btn-primary btn-lg w-full sm:flex-1" data-wizard-next>
                <?php echo e(t('cta.continue')) ?><?php echo icon('arrow-right', 'icon-sm icon-flip') ?>
              </button>
              <button type="submit" class="btn-primary btn-lg w-full sm:flex-1" data-wizard-submit>
                <?php echo e(t('plan.submit')) ?><?php echo icon('arrow-right', 'icon-sm icon-flip') ?>
              </button>
              <button type="button" class="btn-outline btn-lg w-full sm:w-auto" data-wizard-prev>
                <?php echo icon('arrow-left', 'icon-sm icon-flip') ?><?php echo e(t('cta.back')) ?>
              </button>
            </div>

            <p class="mt-4 flex flex-wrap items-center justify-center gap-x-2 gap-y-1 text-center text-meta text-ink-soft">
              <span><?php echo e(t('plan.assure.reply')) ?></span>
              <span aria-hidden="true">&middot;</span>
              <span><?php echo e(t('plan.assure.notBooked')) ?></span>
              <span aria-hidden="true">&middot;</span>
              <span><?php echo e(t('plan.assure.private')) ?></span>
            </p>
          </div>
        </form>
      <?php endif; ?>

      <!-- Side rail. Every card uses help_card(), so this page matches
           the booking sidebar and the FAQ rail. -->
      <aside class="space-y-4 lg:sticky lg:top-[calc(var(--frontend-header-h)+28px)] lg:self-start">
        <?php foreach ($support as $card): ?>
          <?php echo help_card($card) ?>
        <?php endforeach; ?>
      </aside>
    </div>
  </div>
</section>

<?php if ($cta !== null): ?>
  <?php echo cta_band([
      'bg'        => 'bg-white',
      'eyebrow'   => $cta['eyebrow'],
      'title'     => $cta['title'],
      'text'      => $cta['text'],
      'primary'   => $cta['primary'],
      'secondary' => $cta['secondary'],
  ]) ?>
<?php endif; ?>

<?php if (empty($form['completed'])): ?>
<script
  src="https://www.google.com/recaptcha/enterprise.js?render=<?php echo rawurlencode($recaptchaSiteKey) ?>"
  data-recaptcha-site-key="<?php echo e($recaptchaSiteKey) ?>"
  data-recaptcha-action="<?php echo e($recaptchaAction) ?>"
  defer></script>
<?php endif; ?>
<?php require_once __DIR__ . '/footer.php'; ?>
