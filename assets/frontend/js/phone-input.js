/**
 * Alam Al-Munawara — intl-tel-input initialiser
 *
 * One place where every public telephone field is configured. Pages never call
 * intlTelInput() themselves: they print phone_field() (inc/components.php),
 * which emits `<input type="tel" class="input js-phone-input">` plus the hidden
 * inputs the backend reads, and tune behaviour with data attributes:
 *
 *   data-phone-name="mobile"      hidden field carrying the E.164 number
 *   data-dial-name="dial_code"    hidden field carrying the dial code (+966)
 *   data-country-name="…"         hidden field carrying the ISO2 country
 *   data-default-country="sa"     fallback country — Madinah is in Saudi Arabia
 *   data-phone-required="true"    the field may not be left empty
 *   data-error-key="mobile"       the [data-error="…"] node this field owns
 *
 * The visible input is unnamed on purpose: it only holds what the visitor is
 * typing (national format, formatted as they type). Everything the form posts
 * lives in the hidden inputs, so `$_POST['mobile']` and `$_POST['dial_code']`
 * keep working exactly as they did with the old dial-code <select>.
 *
 * ONE copy of this file serves both locales. The plugin's interface strings and
 * the validation messages come from window.FrontendI18n (js/main.js), passed
 * through intl-tel-input's documented `i18n` option — there is no second bundle
 * to load and no vendor file is patched. See vendor/intl-tel-input/VERSION.txt.
 */
(function () {
  'use strict';

  var iti = window.intlTelInput;
  if (!iti) return;

  var i18n = window.FrontendI18n;
  var phoneConfig = (i18n && i18n.config && i18n.config.phone) || {};

  /* The utils module (formatting, validation, example placeholders) is the big
   * half of the library, so it is fetched on demand the way the docs recommend
   * — intlTelInput.min.js stays 50KB and the page is interactive before it
   * lands. The URL is derived from this script's own src so nothing has to be
   * templated into the page. */
  var script = document.currentScript;
  var base = script && script.src ? script.src.replace(/js\/phone-input\.js.*$/, '') : '';
  var UTILS_URL = base + 'vendor/intl-tel-input/js/utils.js';

  /* utils.js is an ES module, so it is pulled in with a dynamic import. The
   * import lives inside a constructed function: on a browser too old to parse
   * `import()` that is a catchable error at construction time instead of a
   * SyntaxError that would take this whole file down with it. Such a browser
   * simply gets the picker without formatting or validation. */
  var importModule = (function () {
    try {
      return new Function('url', 'return import(url);');
    } catch (err) {
      return null;
    }
  }());

  /* Countries a Madinah tour operator sees most, lifted to the top of the list.
   * The full international list stays underneath — this is ordering, not
   * filtering. */
  var COUNTRY_ORDER = [
    'sa', 'ae', 'kw', 'qa', 'bh', 'om', 'eg', 'jo',
    'pk', 'in', 'bd', 'id', 'my', 'tr', 'gb', 'us'
  ];

  /**
   * The plugin's own interface text, in the page's language.
   *
   * The option is `uiTranslations` — v29's name for it. Country NAMES are a
   * separate option, `countryNameLocale`, which this version resolves through
   * Intl.DisplayNames, so the whole list arrives in Arabic without a second
   * bundle to vendor; the dial codes stay Latin either way.
   *
   * `searchSummaryAria` is a counted string, so it goes through the same plural
   * rules as the rest of the site rather than an is/are switch.
   */
  function uiTranslations() {
    if (!i18n) return {};
    return {
      selectedCountryAriaLabel: i18n.t('iti.selectedCountryAriaLabel', {
        countryName: '${countryName}', dialCode: '${dialCode}'
      }),
      noCountrySelected: i18n.t('iti.noCountrySelected'),
      countryListAriaLabel: i18n.t('iti.countryListAriaLabel'),
      searchPlaceholder: i18n.t('iti.searchPlaceholder'),
      clearSearchAriaLabel: i18n.t('iti.clearSearchAriaLabel'),
      searchEmptyState: i18n.t('iti.searchEmptyState'),
      searchSummaryAria: function (count) {
        return i18n.tn('count.searchResults', count);
      }
    };
  }

  var BASE = {
    initialCountry: 'sa',
    countryOrder: COUNTRY_ORDER,
    countrySearch: true,          // search by country name or dial code
    separateDialCode: true,       // the selector reads "🇸🇦 +966"
    countrySelectorMode: 'AUTO',  // dropdown on desktop, fullscreen on phones
    formatAsYouType: true,
    strictMode: true,             // digits only, capped at the country's length
    numberDisplayFormat: 'NATIONAL',
    placeholderNumberPolicy: 'POLITE',
    placeholderNumberType: 'MOBILE',
    // Scoping classes for the Alam theme in css/src/tailwind.css. The popup and
    // the fullscreen container are rendered outside the field, so they get the
    // class too or the theme could not reach them.
    classNames: {
      container: 'alam-iti',
      countrySelector: 'alam-iti-popup',
      countrySelectorContainer: 'alam-iti-popup-container'
    },
    countryNameLocale: phoneConfig.countryNameLocale || 'en',
    uiTranslations: uiTranslations()
  };

  /* Keyed by the plugin's own error constants, which are stable identifiers and
     are never translated — only the message a visitor reads is. */
  function validationMessage(key) {
    var keys = {
      empty: 'phone.error.empty',
      invalid: 'phone.error.invalid',
      TOO_SHORT: 'phone.error.tooShort',
      TOO_LONG: 'phone.error.tooLong',
      INVALID_COUNTRY_CODE: 'phone.error.countryCode'
    };
    return i18n && keys[key] ? i18n.t(keys[key]) : null;
  }

  function attr(el, name) { return el.getAttribute(name) || ''; }

  /* getNumber(), isValidNumber() and getValidationError() all throw until the
   * utils module has landed, and the first sync always runs before it has. Every
   * call goes through these two helpers so a slow (or blocked, or unsupported)
   * utils load degrades to a plain "+dial + digits" number instead of taking the
   * initialiser down with it. */
  function utilsReady() { return !!iti.utils; }

  function digits(value) { return String(value).replace(/\D/g, ''); }

  /**
   * The canonical number for `input`, in `format` (default E.164).
   * Falls back to the dial code plus the typed digits while utils are missing.
   */
  function numberOf(instance, input, format) {
    if (!input.value.trim()) return '';
    if (utilsReady()) {
      try {
        var formatted = instance.getNumber(format);
        if (formatted) return formatted;
      } catch (err) { /* fall through to the plain form below */ }
    }
    var country = instance.getSelectedCountry();
    var national = digits(input.value);
    if (!country || !country.dialCode) return national ? '+' + national : '';
    return '+' + country.dialCode + national.replace(new RegExp('^0+'), '');
  }

  function fieldOf(el) { return el.closest ? el.closest('.phone-field') : null; }

  function hidden(el, hook) {
    var field = fieldOf(el);
    return field ? field.querySelector('[' + hook + ']') : null;
  }

  /** Merge plain objects, left to right. */
  function merge() {
    var out = {};
    for (var i = 0; i < arguments.length; i++) {
      var src = arguments[i] || {};
      for (var k in src) {
        if (Object.prototype.hasOwnProperty.call(src, k)) out[k] = src[k];
      }
    }
    return out;
  }

  /* ---- One field --------------------------------------------------------- */

  function enhance(input) {
    if (input.itiInstance) return input.itiInstance;

    var valueEl   = hidden(input, 'data-phone-value');
    var dialEl    = hidden(input, 'data-phone-dial');
    var countryEl = hidden(input, 'data-phone-country');
    var preset    = valueEl ? valueEl.value.trim() : '';

    var instance = iti(input, merge(BASE, {
      initialCountry: attr(input, 'data-default-country') || BASE.initialCountry,
      loadUtils: importModule ? function () { return importModule(UTILS_URL); } : null
    }));

    input.itiInstance = instance;

    /* A saved number arrives in E.164 (+966501234567): let the plugin read the
     * country out of it, which is what makes Review → Edit come back with the
     * right flag. A number with no + falls back to data-default-country. */
    if (preset) instance.setNumber(preset);

    function sync() {
      var number = numberOf(instance, input);
      var country = instance.getSelectedCountry();
      if (valueEl) valueEl.value = number;
      if (dialEl) dialEl.value = country && country.dialCode ? '+' + country.dialCode : '';
      if (countryEl) countryEl.value = country ? country.iso2 : '';
      /* Page scripts (the booking wizard) listen on the hidden input, so they
       * see one ordinary change event carrying the canonical value. */
      if (valueEl) {
        valueEl.dispatchEvent(new Event('input', { bubbles: true }));
        valueEl.dispatchEvent(new Event('change', { bubbles: true }));
      }
    }

    input.addEventListener('input', sync);
    input.addEventListener('countrychange', sync);
    /* Formatting is applied once the utils module lands, so re-read then. */
    instance.promise.then(sync, sync);
    sync();

    return instance;
  }

  /* ---- Validation -------------------------------------------------------- */

  /**
   * Validate one field with the library's own rules — no per-country regex.
   * Returns null when the field is fine, otherwise a human-readable message.
   *
   * An optional field left empty is valid. A field is only judged invalid once
   * the utils module has loaded; before that isValidNumber() returns null and
   * the number is let through rather than wrongly rejected.
   */
  function validate(input) {
    var instance = input.itiInstance;
    if (!instance) return null;

    var isRequired = input.required || attr(input, 'data-phone-required') === 'true';
    var typed = input.value.trim();

    if (!typed) return isRequired ? validationMessage('empty') : null;

    /* Without utils there are no per-country rules to judge against, so a
     * number that was typed is accepted rather than wrongly rejected. */
    if (!utilsReady()) return null;

    var valid, error;
    try {
      valid = instance.isValidNumber();
      if (valid === null || valid === true) return null;
      error = instance.getValidationError();
    } catch (err) {
      return null;
    }
    return validationMessage(error) || validationMessage('invalid');
  }

  /** Paint the existing error styling onto the whole control. */
  function setInvalid(input, invalid) {
    var field = fieldOf(input);
    input.classList.toggle('is-invalid', !!invalid);
    input.setAttribute('aria-invalid', invalid ? 'true' : 'false');
    if (field) field.classList.toggle('is-invalid', !!invalid);
  }

  /**
   * Validate and show/hide the page's own [data-error="…"] message, so the
   * frontend keeps one validation system rather than gaining a second.
   */
  function check(input, options) {
    var message = validate(input);
    var key = attr(input, 'data-error-key');
    var node = key ? document.querySelector('[data-error="' + key + '"]') : null;

    setInvalid(input, !!message);
    if (node) {
      node.classList.toggle('hidden', !message);
      if (message) node.textContent = message;
      if (!node.id) node.id = 'phone-error-' + key;
      input.setAttribute('aria-describedby', node.id);
    }
    if (message && options && options.focus) input.focus();
    return !message;
  }

  function enhanceAll(root) {
    var scope = root || document;
    Array.prototype.forEach.call(scope.querySelectorAll('input.js-phone-input'), function (input) {
      /* A page may carry several phone fields; one that fails to start must not
       * leave the rest as plain text boxes. */
      try {
        enhance(input);
      } catch (err) {
        if (window.console) window.console.error('Phone field failed to initialise', input, err);
        return;
      }
      /* Complain only once the visitor has left the field, never mid-typing. */
      input.addEventListener('blur', function () { check(input); });
      input.addEventListener('input', function () {
        if (input.classList.contains('is-invalid')) check(input);
      });
    });
  }

  /* form.reset() empties the visible input silently — the hidden values and the
   * country have to be put back by hand. */
  document.addEventListener('reset', function (ev) {
    if (!ev.target || !ev.target.querySelectorAll) return;
    var inputs = ev.target.querySelectorAll('input.js-phone-input');
    if (!inputs.length) return;
    window.setTimeout(function () {
      Array.prototype.forEach.call(inputs, function (input) {
        var instance = input.itiInstance;
        if (!instance) return;
        var valueEl = hidden(input, 'data-phone-value');
        instance.setNumber(valueEl ? valueEl.defaultValue : '');
        setInvalid(input, false);
        input.dispatchEvent(new Event('input', { bubbles: true }));
      });
    }, 0);
  });

  /* Exposed for page scripts: the booking wizard validates a step at a time and
   * reads the canonical number for its summary. Every field keeps its own
   * instance on the element, so a page may carry as many as it likes. */
  window.FrontendPhone = {
    enhance: enhance,
    enhanceAll: enhanceAll,
    check: check,
    validate: validate,
    instance: function (input) { return input ? input.itiInstance || null : null; },
    /** Canonical E.164, e.g. +966501234567 — what the backend stores. */
    e164: function (input) {
      var instance = input && input.itiInstance;
      return instance ? numberOf(instance, input) : '';
    },
    /** Readable international form, e.g. +966 50 123 4567 — what people read. */
    display: function (input) {
      var instance = input && input.itiInstance;
      if (!instance) return '';
      return numberOf(instance, input, 'INTERNATIONAL') || numberOf(instance, input);
    }
  };

  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', function () { enhanceAll(); });
  } else {
    enhanceAll();
  }
}());
