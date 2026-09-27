/**
 * Alam Al-Munawara — Plan Your Trip wizard persistence.
 *
 * initFormWizard() (js/main.js) owns step navigation, client-side validation
 * and progress announcements for every `[data-wizard]` form on the site. This
 * file adds the one thing specific to Plan Your Trip: saving each Continue to
 * the server-side draft before the wizard is allowed to advance.
 *
 * It listens for the generic, cancelable `wizard:beforenext` event the wizard
 * dispatches on Continue (after its own client-side validation already
 * passed), calls preventDefault() to hold the advance, POSTs the visible
 * step's fields to the locale-aware save-step endpoint (read from
 * `data-save-step-url`, never hard-coded), and — only once the server
 * confirms the step was saved — advances the wizard itself via
 * `form.wizardGoTo()`. Server-returned field errors are rendered beside the
 * matching controls, exactly like the page's own server-rendered errors.
 *
 * The draft's identity lives entirely in the CodeIgniter session on the
 * server; nothing here reads, stores or posts a token.
 */
(function () {
  'use strict';

  var form = document.querySelector('form[data-save-step-url]');
  if (!form) return;

  var saveUrl = form.getAttribute('data-save-step-url') || '';
  if (!saveUrl) return;

  var i18n = window.FrontendI18n;
  var nextBtn = form.querySelector('[data-wizard-next]');
  var submitBtn = form.querySelector('[data-wizard-submit]');
  var announce = form.querySelector('[data-wizard-announce]');
  var statusError = document.getElementById('plan-status-error');
  var statusSuccess = document.getElementById('plan-status-success');
  var formToken = form.querySelector('[data-plan-form-token]');
  var recaptchaToken = form.querySelector('[data-recaptcha-token]');
  var recaptchaScript = document.querySelector('script[data-recaptcha-site-key]');
  var recaptchaSiteKey = recaptchaScript ? recaptchaScript.getAttribute('data-recaptcha-site-key') : '';
  var recaptchaAction = recaptchaScript ? recaptchaScript.getAttribute('data-recaptcha-action') : '';
  var originalNextHtml = nextBtn ? nextBtn.innerHTML : '';
  var busy = false;

  function t(key) {
    return i18n ? i18n.t(key) : key;
  }

  /* The phone field's visible input is intentionally unnamed (see
     phone_field() in inc/components.php) — the posted `phone` name lives on
     a hidden sibling — so a server error for "phone" has to land on the
     visible `.js-phone-input` control, not on `[name="phone"]`. */
  function controlFor(name) {
    if (name === 'phone') return form.querySelector('.js-phone-input');
    return form.querySelector('[name="' + name + '"]');
  }

  function errorNodeFor(name) {
    var known = form.querySelector('[data-error="' + name + '"]');
    if (known) return known;
    var byId = document.getElementById('error-' + name);
    return byId || null;
  }

  function showFieldError(name, message) {
    var node = errorNodeFor(name);
    if (node) {
      node.textContent = message;
      node.classList.remove('hidden');
    }
    var control = controlFor(name);
    if (control) control.setAttribute('aria-invalid', 'true');
  }

  function clearStepErrors(step) {
    var section = form.querySelector('[data-wizard-step="' + step + '"]');
    if (!section) return;
    Array.prototype.forEach.call(section.querySelectorAll('[data-error], [id^="error-"]'), function (node) {
      node.classList.add('hidden');
    });
    Array.prototype.forEach.call(section.querySelectorAll('[aria-invalid]'), function (control) {
      control.setAttribute('aria-invalid', 'false');
    });
  }

  function setBusy(state) {
    busy = state;
    if (!nextBtn) return;
    nextBtn.disabled = state;
    nextBtn.setAttribute('aria-busy', state ? 'true' : 'false');
    nextBtn.innerHTML = state ? t('plan.step.saving') : originalNextHtml;
  }

  function say(message) {
    if (announce && message) announce.textContent = message;
  }

  function clearStatusError() {
    if (statusError) statusError.classList.add('hidden');
  }

  function showStatusError(message) {
    clearStatusSuccess();
    say(message);
    if (!statusError) return;
    var messageNode = statusError.querySelector('[data-plan-status-message]');
    if (messageNode) messageNode.textContent = message;
    statusError.classList.remove('hidden');
    statusError.focus();
  }

  function clearStatusSuccess() {
    if (statusSuccess) statusSuccess.classList.add('hidden');
  }

  /* Mirrors the Contact form's success handling (setStatus() in
     form-validate.js): the message is shown in place, focused — which
     scrolls it into view through the site's smooth scroll-behavior — and
     the form itself stays in the DOM instead of being replaced. */
  function showStatusSuccess(message) {
    clearStatusError();
    say(message);
    if (!statusSuccess) return;
    var messageNode = statusSuccess.querySelector('[data-plan-status-message]');
    if (messageNode) messageNode.textContent = message;
    statusSuccess.classList.remove('hidden');
    statusSuccess.focus();
  }

  function refreshFormToken(data) {
    if (formToken && data && typeof data.form_token === 'string' && data.form_token) {
      formToken.value = data.form_token;
      formToken.defaultValue = data.form_token;
    }
  }

  function validDateOrder() {
    var arrival = form.querySelector('[name="arrival_date"]');
    var departure = form.querySelector('[name="departure_date"]');
    if (!arrival || !departure || !arrival.value || !departure.value) return true;
    if (arrival.value <= departure.value) return true;

    showFieldError('departure_date', t('plan.error.dateOrder'));
    departure.focus();
    return false;
  }

  /* Every value the visible step's controls currently hold — checkboxes only
     when checked, matching how a native form submission already behaves. */
  function stepFormData(step) {
    var section = form.querySelector('[data-wizard-step="' + step + '"]');
    var data = new FormData();
    data.append('step', String(step));
    if (formToken) data.append('form_token', formToken.value);
    if (!section) return data;

    Array.prototype.forEach.call(section.querySelectorAll('input, select, textarea'), function (field) {
      if (!field.name) return;
      if (field.type === 'checkbox' || field.type === 'radio') {
        if (field.checked) data.append(field.name, field.value);
        return;
      }
      data.append(field.name, field.value);
    });

    return data;
  }

  form.addEventListener('wizard:beforenext', function (ev) {
    if (busy) {
      ev.preventDefault();
      return;
    }

    var step = ev.detail.step;
    var next = ev.detail.next;
    ev.preventDefault();
    clearStatusError();
    clearStepErrors(step);
    if (step === 2 && !validDateOrder()) {
      say(t('plan.error.summary'));
      return;
    }
    setBusy(true);

    fetch(saveUrl, {
      method: 'POST',
      body: stepFormData(step),
      credentials: 'same-origin',
      headers: { 'X-Requested-With': 'XMLHttpRequest' }
    }).then(function (response) {
      return response.json().catch(function () { return {}; }).then(function (data) {
        return { ok: response.ok, data: data };
      });
    }).then(function (result) {
      setBusy(false);
      refreshFormToken(result.data);

      if (result.ok && result.data && result.data.success) {
        clearStatusError();
        form.wizardGoTo(next, true);
        return;
      }

      var data = result.data || {};
      var errors = data.errors || {};
      var hasFieldErrors = false;
      Object.keys(errors).forEach(function (name) {
        hasFieldErrors = true;
        showFieldError(name, errors[name]);
      });

      var message = data.message || t('plan.error.saveFailed');
      say(message);

      if (data.expired) {
        window.location.reload();
        return;
      }

      if (!hasFieldErrors) {
        showStatusError(message);
      }
    }).catch(function () {
      setBusy(false);
      showStatusError(t('plan.error.saveFailed'));
    });
  });

  function setFinalBusy(state) {
    busy = state;
    if (!submitBtn) return;
    submitBtn.disabled = state;
    submitBtn.setAttribute('aria-busy', state ? 'true' : 'false');
  }

  /* Mirrors submitViaAjax() in form-validate.js (the Contact form): submit the
     whole wizard as one AJAX request instead of a real navigation, so a
     successful submission never replaces the page — it shows the success
     message in place and resets the wizard back to step 1 via the form's own
     `reset` listener in main.js. */
  function submitFinal() {
    fetch(form.action, {
      method: 'POST',
      body: new FormData(form),
      credentials: 'same-origin',
      headers: { 'X-Requested-With': 'XMLHttpRequest' }
    }).then(function (response) {
      return response.json().catch(function () { return {}; }).then(function (data) {
        return { ok: response.ok, data: data };
      });
    }).then(function (result) {
      setFinalBusy(false);
      recaptchaToken.value = '';
      var data = result.data || {};
      refreshFormToken(data);

      if (result.ok && data.success) {
        form.reset();
        if (window.jQuery) window.jQuery(form).find('.js-select2').trigger('change');
        showStatusSuccess(data.message || t('plan.completed.fallbackLabel'));
        return;
      }

      if (data.expired) {
        say(data.message || t('plan.error.expired'));
        window.location.reload();
        return;
      }

      var errors = data.errors || {};
      var hasFieldErrors = false;
      Object.keys(errors).forEach(function (name) {
        hasFieldErrors = true;
        showFieldError(name, errors[name]);
      });

      var message = data.message || t('plan.error.saveFailed');
      if (hasFieldErrors) {
        say(message);
      } else {
        showStatusError(message);
      }
    }).catch(function () {
      setFinalBusy(false);
      showStatusError(t('plan.error.saveFailed'));
    });
  }

  form.addEventListener('submit', function (ev) {
    if (busy) {
      ev.preventDefault();
      return;
    }

    ev.preventDefault();
    clearStatusError();
    clearStatusSuccess();
    for (var step = 1; step <= 4; step++) clearStepErrors(step);
    if (
      !recaptchaSiteKey
      || !recaptchaAction
      || !recaptchaToken
      || !window.grecaptcha
      || !window.grecaptcha.enterprise
    ) {
      showStatusError(t('plan.error.recaptcha'));
      return;
    }

    setFinalBusy(true);
    recaptchaToken.value = '';

    window.grecaptcha.enterprise.ready(function () {
      window.grecaptcha.enterprise.execute(recaptchaSiteKey, { action: recaptchaAction })
        .then(function (token) {
          recaptchaToken.value = token;
          submitFinal();
        })
        .catch(function () {
          setFinalBusy(false);
          showStatusError(t('plan.error.recaptcha'));
        });
    });
  });
})();
