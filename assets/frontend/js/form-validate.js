/**
 * Alam Al-Munawara — Contact form: real-time validation and AJAX submit.
 *
 * Uses the jQuery Validation Plugin — the same vetted build already used by
 * the manage/admin forms (assets/admin/vendor/jquery-validation), not a new
 * dependency.
 *
 * Rendering is fully manual (a `showErrors` override, not the plugin's own
 * `errorPlacement`): the page already ships one `#error-<field>` paragraph
 * per field, so this only ever shows/hides/fills those rather than growing
 * a second, plugin-managed set of labels next to them.
 *
 * Submission itself is progressive enhancement: `submitHandler` posts the
 * form with $.ajax to the same locale-aware `action` the form already has,
 * and CodeIgniter tells the two paths apart by the `X-Requested-With`
 * header jQuery sets automatically — nothing here hard-codes a second URL.
 * With this script absent (or failing to load), the form is a plain
 * `method="post"` submit and the server validates and redisplays it exactly
 * as before.
 */
(function ($) {
  'use strict';
  if (!$ || !$.fn || !$.fn.validate) return;

  var i18n = window.FrontendI18n;
  function t(key) {
    return i18n ? i18n.t(key) : key;
  }

  /* Localise the plugin defaults as well as the contact form's field-level
     messages. This keeps attribute-derived rules such as `maxlength`, and any
     future rule without an explicit field message, in the active page locale. */
  $.extend($.validator.messages, {
    required: t('contact.error.required'),
    email: t('contact.error.email'),
    maxlength: t('contact.error.maxlength')
  });

  $.validator.addMethod('emailWithDomainSuffix', function (value, element) {
    if (this.optional(element)) return true;
    if (!$.validator.methods.email.call(this, value, element)) return false;

    var separator = value.lastIndexOf('@');
    var domain = separator >= 0 ? value.slice(separator + 1) : '';

    return domain.indexOf('.') > 0 && domain.charAt(domain.length - 1) !== '.';
  }, t('contact.error.email'));

  $.validator.addMethod('minimumWords', function (value, element, minimum) {
    if (this.optional(element)) return true;

    var words = value.trim().split(/\s+/).filter(function (word) {
      return /[A-Za-z\u00C0-\u024F\u0600-\u06FF]/.test(word);
    });

    return words.length >= minimum;
  }, t('contact.error.message'));

  var CLIENT_FIELDS = ['first_name', 'last_name', 'email', 'subject', 'message'];

  /* The phone field's visible input is intentionally unnamed (see
     phone_field() in inc/components.php) — the posted `phone` name lives on
     a hidden sibling, and its own error paragraph keeps the id
     phone-input.js already manages (`phone-error-phone`), not `error-phone`
     like every other field. */
  function errorNode(name) {
    if (name === 'phone') return document.getElementById('phone-error-phone');
    return document.getElementById('error-' + name);
  }

  function controlFor(name) {
    if (name === 'phone') return document.querySelector('.js-phone-input');
    return document.getElementsByName(name)[0];
  }

  function setFieldError(name, message) {
    var node = errorNode(name);
    if (node) {
      node.textContent = message || '';
      node.classList.toggle('hidden', !message);
    }
    var field = controlFor(name);
    if (field) field.setAttribute('aria-invalid', message ? 'true' : 'false');
  }

  function clearAllFieldErrors(form) {
    Array.prototype.forEach.call(form.querySelectorAll('[name]'), function (field) {
      if (field.name) setFieldError(field.name, '');
    });
  }

  /* phone-input.js's own check() is the authority for the phone field —
     it already renders into #phone-error-phone and toggles is-invalid/
     aria-invalid on the right elements (see phone-input.js), so this only
     has to call it and report whether the field is valid. */
  function checkPhoneField(formEl) {
    var input = formEl.querySelector('.js-phone-input');
    if (!input || !window.FrontendPhone) return true;
    return window.FrontendPhone.check(input);
  }

  /* The validation summary has to reflect the phone field too, even though
     jQuery Validate itself never manages it — so this checks the same
     error paragraphs `showErrors` and `checkPhoneField` just updated,
     rather than trusting jQuery Validate's own (phone-blind) error count. */
  function refreshSummary(formEl) {
    var summary = document.getElementById('contact-validation-summary');
    if (!summary) return;
    var anyVisible = CLIENT_FIELDS.concat(['phone']).some(function (name) {
      var node = errorNode(name);
      return node && !node.classList.contains('hidden');
    });
    summary.classList.toggle('hidden', !anyVisible);
  }

  function setStatus(form, state, message) {
    var success = document.getElementById('contact-status-success');
    var error = document.getElementById('contact-status-error');
    var summary = document.getElementById('contact-validation-summary');

    if (success) {
      success.classList.toggle('hidden', state !== 'success');
      if (state === 'success' && message) {
        var successMessage = success.querySelector('[data-contact-status-message]');
        if (successMessage) successMessage.textContent = message;
      }
    }
    if (error) {
      error.classList.toggle('hidden', state !== 'error');
      if (state === 'error' && message) {
        var span = error.querySelector('[data-contact-status-message]');
        if (span) span.textContent = message;
      }
    }
    if (summary) summary.classList.toggle('hidden', state !== 'summary');

    var toFocus = state === 'success' ? success : (state === 'error' ? error : null);
    if (toFocus) toFocus.focus();
  }

  function clearStatus(form) {
    setStatus(form, null);
  }

  function refreshFormToken(form, data) {
    var input = form.querySelector('[data-contact-form-token]');
    if (input && data && typeof data.form_token === 'string' && data.form_token) {
      input.value = data.form_token;
      input.defaultValue = data.form_token;
    }
  }

  function enhance(form) {
    var $form = $(form);
    var recaptchaScript = document.querySelector('script[data-recaptcha-site-key]');
    var recaptchaToken = form.querySelector('[data-recaptcha-token]');
    var recaptchaSiteKey = recaptchaScript ? recaptchaScript.getAttribute('data-recaptcha-site-key') : '';
    var recaptchaAction = recaptchaScript ? recaptchaScript.getAttribute('data-recaptcha-action') : '';
    var submitBtn = form.querySelector('button[type="submit"]');
    var submitLabel = submitBtn ? submitBtn.querySelector('[data-submit-label]') : null;
    var originalSubmitLabel = submitLabel ? submitLabel.textContent : '';
    var messageField = form.querySelector('[name="message"]');
    var messageMinimumWords = messageField
      ? parseInt(messageField.getAttribute('data-min-words'), 10)
      : 3;
    var busy = false;

    if (!messageMinimumWords || messageMinimumWords < 1) messageMinimumWords = 3;

    function setBusy(state) {
      busy = state;
      if (!submitBtn) return;
      submitBtn.disabled = state;
      submitBtn.setAttribute('aria-busy', state ? 'true' : 'false');
      if (submitLabel) {
        submitLabel.textContent = state ? t('contact.sending') : (t('contact.submit') || originalSubmitLabel);
      }
    }

    $form.validate({
      ignore: [],
      rules: {
        first_name: { required: true },
        last_name: { required: true },
        email: { required: true, emailWithDomainSuffix: true },
        subject: { required: true },
        message: { required: true, minimumWords: messageMinimumWords }
      },
      messages: {
        first_name: t('contact.error.firstName'),
        last_name: t('contact.error.lastName'),
        email: t('contact.error.email'),
        subject: t('contact.error.subject'),
        message: t('contact.error.message')
      },
      /* Full manual control instead of errorPlacement/success: the plugin
         only tells us who is valid and who is not, and we decide how that
         renders into the page's own existing error paragraphs. */
      showErrors: function (errorMap, errorList) {
        errorList.forEach(function (error) {
          setFieldError(error.element.name, error.message);
        });
        this.successList.forEach(function (field) {
          setFieldError(field.name, '');
        });
        refreshSummary(form);
      },
      /* jQuery Validate only calls submitHandler when every field IT manages
         passes — so a submit with, say, both an empty first name AND an
         empty phone number never reaches submitHandler at all, and the
         phone-specific check below would never run. invalidHandler fires on
         every failed submit attempt regardless, so the phone field is
         always checked and rendered here too, not only in the one case
         where it is the sole problem. */
      invalidHandler: function (event, validator) {
        checkPhoneField(form);
        refreshSummary(form);
      },
      submitHandler: function (formEl) {
        if (busy) return;
        /* The phone field's visible input is intentionally unnamed (see
           phone_field() in inc/components.php), so jQuery Validate never
           sees it as a field to manage — phone-input.js's own required/
           format check is the authority for it, and gates the submit here
           exactly the way a jQuery-Validate-managed field would. */
        if (!checkPhoneField(formEl)) {
          refreshSummary(formEl);
          var phoneInput = formEl.querySelector('.js-phone-input');
          if (phoneInput) phoneInput.focus();
          return;
        }
        generateRecaptchaToken(formEl);
      }
    });

    /* select2 replaces the native <select> with its own widget and hides
       the original, so the keyup/blur events the plugin listens for never
       reach it. Its "change" event still fires on every real selection, so
       re-validating that one field on change keeps Subject just as live as
       the plain text fields. */
    $form.find('select[name="subject"]').on('change', function () {
      $form.validate().element(this);
    });

    function generateRecaptchaToken(formEl) {
      setBusy(true);
      clearStatus(formEl);

      if (
        !recaptchaSiteKey
        || !recaptchaAction
        || !recaptchaToken
        || !window.grecaptcha
        || !window.grecaptcha.enterprise
      ) {
        setBusy(false);
        setStatus(formEl, 'error', t('contact.error.recaptcha'));
        return;
      }

      recaptchaToken.value = '';
      window.grecaptcha.enterprise.ready(function () {
        window.grecaptcha.enterprise.execute(recaptchaSiteKey, { action: recaptchaAction })
          .then(function (token) {
            recaptchaToken.value = token;
            submitViaAjax($(formEl));
          })
          .catch(function () {
            setBusy(false);
            setStatus(formEl, 'error', t('contact.error.recaptcha'));
          });
      });
    }

    function submitViaAjax($f) {
      var formEl = $f[0];

      $.ajax({
        url: $f.attr('action'),
        method: 'POST',
        data: $f.serialize(),
        dataType: 'json'
      }).done(function (data) {
        setBusy(false);
        recaptchaToken.value = '';
        refreshFormToken(formEl, data);
        if (data && data.success) {
          /* formEl.reset() alone is enough to redraw the select2 controls too
             — select2-init.js listens for the form's own `reset` event and
             refreshes every `.js-select2` through Select2's namespaced
             `change.select2` event. A bare `.trigger('change')` here would
             fire the Subject field's own change listener (below) as well,
             which re-validates on selection and would resurface "Choose a
             subject" on the now-empty field right after this success. */
          formEl.reset();
          /* resetForm() clears the plugin's own invalid/submitted state (not
             just the visible error text clearAllFieldErrors() hides) so a
             later interaction on the fresh, empty form can't resurface a
             validation message left over from this submission. */
          $f.validate().resetForm();
          clearAllFieldErrors(formEl);
          refreshSummary(formEl);
          setStatus(formEl, 'success', data.message);
          return;
        }
        renderServerResponse(formEl, data);
      }).fail(function (xhr) {
        setBusy(false);
        recaptchaToken.value = '';
        var data = xhr.responseJSON;
        refreshFormToken(formEl, data);
        if (data && data.errors) {
          renderServerResponse(formEl, data);
        } else {
          setStatus(formEl, 'error', data && data.message ? data.message : t('contact.error.general'));
        }
      });
    }

    function renderServerResponse(formEl, data) {
      data = data || {};
      var errors = data.errors || {};
      var hasErrors = false;

      clearAllFieldErrors(formEl);
      Object.keys(errors).forEach(function (name) {
        hasErrors = true;
        setFieldError(name, errors[name]);
      });

      if (hasErrors) {
        setStatus(formEl, 'summary');
        var firstInvalid = null;
        Object.keys(errors).some(function (name) {
          var field = controlFor(name);
          if (field) firstInvalid = field;
          return !!field;
        });
        if (firstInvalid) firstInvalid.focus();
      } else {
        setStatus(formEl, 'error', data.message || t('contact.error.general'));
      }
    }
  }

  function init() {
    var form = document.getElementById('contactForm');
    if (form) enhance(form);
  }

  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', init);
  } else {
    init();
  }
})(window.jQuery);
