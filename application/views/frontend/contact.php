<?php

require_once __DIR__ . '/functions.php';
require_once __DIR__ . '/header.php';

if (!empty($config['banner'])) {
    require_once __DIR__ . '/banner.php';
}

$pageContent = isset($pageContent) ? trim((string) $pageContent) : '';
$formHead = isset($formSection['head']) && is_array($formSection['head']) ? $formSection['head'] : [];
$formIntroHtml = !empty($formSection['contents_html']) ? $formSection['contents_html'] : '';
$hasFormIntro = !empty($formHead['eyebrow']) || !empty($formHead['title']) || $formIntroHtml !== '';
?>

<!-- ==================================================================
     Form + support
     ============================================================== -->
<section class="bg-surface-soft py-14 sm:py-16 lg:py-20">
  <div class="container">
    <div class="grid gap-8 lg:grid-cols-[minmax(0,1fr)_336px] lg:gap-10">

      <div class="space-y-8">
        <?php if ($pageContent !== ''): ?>
          <div class="prose prose-lg page-contents max-w-4xl mb-4">
            <?php echo $pageContent ?>
          </div>
        <?php endif; ?>

        <form id="contactForm" class="panel p-5 sm:p-7 lg:p-8" method="post" action="<?php echo e($formAction) ?>" novalidate>
        <input type="hidden" name="form_token" value="<?php echo e($formToken) ?>" data-contact-form-token>
        <input type="hidden" name="recaptcha_token" value="" data-recaptcha-token>
        <?php if ($hasFormIntro): ?>
          <?php if (!empty($formHead['eyebrow'])): ?>
            <p class="eyebrow"><?php echo e($formHead['eyebrow']) ?></p>
          <?php endif; ?>
          <?php if (!empty($formHead['title'])): ?>
            <h2 class="<?php echo !empty($formHead['eyebrow']) ? 'mt-3.5 ' : '' ?>text-h3"><?php echo e($formHead['title']) ?></h2>
          <?php endif; ?>
          <?php if ($formIntroHtml !== ''): ?>
            <div class="page-contents mt-2.5 max-w-prose text-body-sm text-ink-muted"><?php echo $formIntroHtml ?></div>
          <?php endif; ?>
        <?php endif; ?>

        <?php /* Always present so JS (AJAX submit) can show/update these in
                 place; hidden unless a server-rendered page load already has
                 that status (no-JS Post/Redirect/Get). */ ?>
        <p id="contact-status-success"
           class="<?php echo $status === 'success' ? '' : 'hidden ' ?><?php echo $hasFormIntro ? 'mt-7 ' : '' ?>flex items-start gap-2.5 rounded-control border border-alam-200 bg-alam-50 p-4 text-meta text-alam-800"
           role="status" tabindex="-1">
          <?php echo icon('check', 'mt-0.5 icon-sm shrink-0') ?>
          <span data-contact-status-message><?php echo e($successMessage) ?></span>
        </p>
        <p id="contact-status-error"
           class="<?php echo $status === 'error' ? '' : 'hidden ' ?><?php echo $hasFormIntro ? 'mt-7 ' : '' ?>contact-alert-danger flex items-start gap-2.5 rounded-control border p-4 text-meta"
           role="alert" tabindex="-1">
          <?php echo icon('info', 'mt-0.5 icon-sm shrink-0') ?>
          <span data-contact-status-message><?php echo e($statusMessage !== '' ? $statusMessage : t('contact.error.general')) ?></span>
        </p>
        <p id="contact-validation-summary"
           class="<?php echo empty($errors) ? 'hidden ' : '' ?><?php echo ($hasFormIntro || $status !== '') ? 'mt-4 ' : '' ?>text-meta font-semibold text-red-700"
           role="alert">
          <?php echo e(t('contact.error.summary')) ?>
        </p>

        <div class="mt-7 grid gap-5 sm:grid-cols-2">
          <div>
            <label for="first_name" class="field-label"><?php echo e(t('contact.field.firstName')) ?> <span class="text-red-600">*</span></label>
            <input type="text" id="first_name" name="first_name" class="input" autocomplete="given-name" required
                   maxlength="255" value="<?php echo e($old['first_name'] ?? '') ?>"
                   aria-invalid="<?php echo isset($errors['first_name']) ? 'true' : 'false' ?>" aria-describedby="error-first_name">
            <p class="mt-1.5 <?php echo isset($errors['first_name']) ? '' : 'hidden ' ?>text-meta font-medium text-red-600" id="error-first_name">
              <?php echo e($errors['first_name'] ?? '') ?>
            </p>
          </div>

          <div>
            <label for="last_name" class="field-label"><?php echo e(t('contact.field.lastName')) ?> <span class="text-red-600">*</span></label>
            <input type="text" id="last_name" name="last_name" class="input" autocomplete="family-name" required
                   maxlength="255" value="<?php echo e($old['last_name'] ?? '') ?>"
                   aria-invalid="<?php echo isset($errors['last_name']) ? 'true' : 'false' ?>" aria-describedby="error-last_name">
            <p class="mt-1.5 <?php echo isset($errors['last_name']) ? '' : 'hidden ' ?>text-meta font-medium text-red-600" id="error-last_name">
              <?php echo e($errors['last_name'] ?? '') ?>
            </p>
          </div>

          <div>
            <label for="contact_email" class="field-label"><?php echo e(t('contact.field.email')) ?> <span class="text-red-600">*</span></label>
            <input type="email" id="contact_email" name="email" class="input" autocomplete="email" required
                   maxlength="100" value="<?php echo e($old['email'] ?? '') ?>"
                   aria-invalid="<?php echo isset($errors['email']) ? 'true' : 'false' ?>" aria-describedby="error-email">
            <p class="mt-1.5 <?php echo isset($errors['email']) ? '' : 'hidden ' ?>text-meta font-medium text-red-600" id="error-email">
              <?php echo e($errors['email'] ?? '') ?>
            </p>
          </div>

          <div>
            <label for="contact_country" class="field-label"><?php echo e(t('contact.field.country')) ?></label>
            <select id="contact_country" name="country" class="select js-select2"
                    data-placeholder="<?php echo e(t('select.countryPlaceholder')) ?>"
                    aria-invalid="<?php echo isset($errors['country']) ? 'true' : 'false' ?>" aria-describedby="error-country">
              <option value=""><?php echo e(t('select.chooseCountry')) ?></option>
              <?php foreach ($countries as $c): ?>
                <option value="<?php echo (int) $c['id'] ?>"
                        <?php echo (isset($old['country']) && (int) $old['country'] === (int) $c['id']) ? 'selected' : '' ?>>
                  <?php echo e($c['label']) ?>
                </option>
              <?php endforeach; ?>
            </select>
            <p class="mt-1.5 <?php echo isset($errors['country']) ? '' : 'hidden ' ?>text-meta font-medium text-red-600" id="error-country">
              <?php echo e($errors['country'] ?? '') ?>
            </p>
          </div>

          <div class="sm:col-span-2">
            <label for="contact_phone" class="field-label"><?php echo e(t('contact.field.phone')) ?> <span class="text-red-600">*</span></label>
            <?php echo phone_field([
                'id'        => 'contact_phone',
                'name'      => 'phone',
                'value'     => $old['phone'] ?? '',
                'required'  => true,
                'error_key' => 'phone',
                'attrs'     => isset($errors['phone'])
                    ? ['aria-invalid' => 'true', 'aria-describedby' => 'phone-error-phone']
                    : [],
            ]) ?>
            <p class="mt-1.5 <?php echo isset($errors['phone']) ? '' : 'hidden ' ?>text-meta font-medium text-red-600"
               data-error="phone" id="phone-error-phone">
              <?php echo e($errors['phone'] ?? t('contact.error.phone')) ?>
            </p>
          </div>

          <div class="sm:col-span-2">
            <label for="subject" class="field-label"><?php echo e(t('contact.field.subject')) ?> <span class="text-red-600">*</span></label>
            <select id="subject" name="subject" class="select js-select2"
                    data-placeholder="<?php echo e(t('contact.field.subjectPlaceholder')) ?>" required
                    aria-invalid="<?php echo isset($errors['subject']) ? 'true' : 'false' ?>" aria-describedby="error-subject">
              <option value=""><?php echo e(t('contact.field.subjectEmpty')) ?></option>
              <?php foreach ($subjects as $s): ?>
                <option value="<?php echo e($s['value']) ?>"
                        <?php echo (isset($old['subject']) && $old['subject'] === $s['value']) ? 'selected' : '' ?>>
                  <?php echo e($s['label']) ?>
                </option>
              <?php endforeach; ?>
            </select>
            <p class="mt-1.5 <?php echo isset($errors['subject']) ? '' : 'hidden ' ?>text-meta font-medium text-red-600" id="error-subject">
              <?php echo e($errors['subject'] ?? '') ?>
            </p>
          </div>

          <div class="sm:col-span-2">
            <label for="message" class="field-label"><?php echo e(t('contact.field.message')) ?> <span class="text-red-600">*</span></label>
            <textarea id="message" name="message" class="input" rows="6" required maxlength="5000" data-min-words="3"
                      placeholder="<?php echo e(t('contact.field.messagePlaceholder')) ?>"
                      aria-invalid="<?php echo isset($errors['message']) ? 'true' : 'false' ?>" aria-describedby="error-message"><?php echo e($old['message'] ?? '') ?></textarea>
            <p class="mt-1.5 <?php echo isset($errors['message']) ? '' : 'hidden ' ?>text-meta font-medium text-red-600" id="error-message">
              <?php echo e($errors['message'] ?? '') ?>
            </p>
          </div>
        </div>

        <div class="mt-7 flex flex-col gap-4 border-t border-line pt-6 sm:flex-row sm:items-center sm:justify-between">
          <?php if (!empty($privacySection['contents_html'])): ?>
            <div class="page-contents max-w-sm text-body-sm text-ink-soft">
              <?php echo $privacySection['contents_html'] ?>
            </div>
          <?php endif; ?>
          <button type="submit" class="btn-primary btn-lg w-full shrink-0 whitespace-nowrap sm:w-auto">
            <span data-submit-label><?php echo e(t('contact.submit')) ?></span><?php echo icon('arrow-right', 'icon-sm icon-flip') ?>
          </button>
        </div>
        </form>
      </div>

      <!-- Rail. The contact details used to sit in a three-across strip above
           the form; as help cards beside it they stay in view while the form
           is being filled in, and match the rails on the other pages. -->
      <aside class="space-y-4">
        <?php foreach ($support as $card): ?>
          <?php echo help_card($card) ?>
        <?php endforeach; ?>

      </aside>
    </div>
  </div>
</section>

<script
  src="https://www.google.com/recaptcha/enterprise.js?render=<?php echo rawurlencode($recaptchaSiteKey) ?>"
  data-recaptcha-site-key="<?php echo e($recaptchaSiteKey) ?>"
  data-recaptcha-action="<?php echo e($recaptchaAction) ?>"
  defer></script>
<?php require_once __DIR__ . '/footer.php'; ?>
