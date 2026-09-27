/**
 * Alam Al-Munawara — Flatpickr initialiser
 *
 * One place where every public-facing date control is configured. Pages never
 * call flatpickr() themselves: they print date_field() (inc/components.php),
 * which emits `<input type="text" class="input js-datepicker">` plus the calendar
 * icon, and tune the behaviour with data attributes:
 *
 *   data-date-type="booking"     min = today (the default for a tour date)
 *   data-date-type="any"         no minimum — historical dates stay reachable
 *   data-min-date="today"        explicit Flatpickr min ("today", "2026-01-01")
 *   data-max-date="2027-12-31"   explicit Flatpickr max
 *   data-alt-format="j M Y"      shorter display format for a tight column
 *   data-link-min="plan_arrival" this field can never be earlier than #plan_arrival
 *   data-clearable="true"        show the × that empties an optional field
 *
 * The real <input> keeps its name, id, required flag and a `Y-m-d` value, so PHP
 * handling, validation and backend expectations are untouched — Flatpickr only
 * draws the control and shows a friendly "27 August 2026" in its alt input.
 *
 * ONE copy of this file serves both locales. Everything locale-dependent comes
 * from window.FrontendI18n (js/main.js): the Flatpickr locale bundle to use, the
 * display format, and the screen-reader labels on the month arrows. The real
 * input still submits `Y-m-d` in both locales — the display format is the alt
 * input's business only, so PHP handling is untouched.
 */
(function () {
  'use strict';

  if (!window.flatpickr) return;

  var i18n = window.FrontendI18n;
  var dateConfig = (i18n && i18n.config && i18n.config.date) || {};

  /* ---- Shared configuration --------------------------------------------
   * Every Alam picker starts from this. `altInputClass` is spelled out because
   * Flatpickr otherwise copies the source input's class list onto the visible
   * field — which would carry `js-datepicker` and re-enter this initialiser. */
  /**
   * The Arabic month and weekday names come from Flatpickr's own l10n bundle,
   * which functions.php loads before this file when the page locale is Arabic
   * (vendor/flatpickr/l10n/ar.js — unmodified, see its VERSION.txt). Only the
   * pieces the bundle cannot know are set here.
   *
   * firstDayOfWeek is pinned to Sunday in BOTH locales: it is the Saudi working
   * week, and it matches the weekday numbers guide availability uses in
   * inc/data.php. The Arabic bundle ships Saturday, which would put the
   * calendar a day out of step with the availability data behind it.
   */
  function localeOption() {
    var name = dateConfig.flatpickrLocale;
    var base = (name && name !== 'default' && window.flatpickr.l10ns && window.flatpickr.l10ns[name])
      ? window.flatpickr.l10ns[name]
      : {};
    var overrides = { firstDayOfWeek: dateConfig.firstDayOfWeek || 0 };

    /* The Arabic bundle abbreviates months as numbers ("1"…"12"), so the short
       display format ("j M Y") would read "25 9 2026". Arabic has no common
       abbreviations, so the full month names serve as the short ones too. */
    if (base.months && base.months.longhand) {
      overrides.months = { shorthand: base.months.longhand, longhand: base.months.longhand };
    }

    return merge(base, overrides);
  }

  function srLabel(key, fallback) {
    return '<span class="sr-only">' + (i18n ? i18n.t(key) : fallback) + '</span>';
  }

  var DEFAULTS = {
    dateFormat: 'Y-m-d',          // what PHP receives — unchanged in both locales
    altInput: true,
    altFormat: dateConfig.altFormat || 'j F Y',   // what the visitor reads
    altInputClass: 'input datepicker-alt',
    disableMobile: true,          // never fall back to the native mobile UI
    monthSelectorType: 'static',
    nextArrow: srLabel('date.nextMonth', 'Next month'),
    prevArrow: srLabel('date.prevMonth', 'Previous month')
  };

  /* Flatpickr draws its own arrow glyphs inside whatever `*Arrow` HTML it gets;
   * the two above only add a screen-reader label, the chevrons come from CSS. */

  function attr(el, name) { return el.getAttribute(name) || ''; }

  /** Per-field options, read from the markup rather than hard-coded per page. */
  function optionsFor(el) {
    var opts = {};
    var type = attr(el, 'data-date-type') || 'booking';
    var min  = attr(el, 'data-min-date');
    var max  = attr(el, 'data-max-date');
    var alt  = attr(el, 'data-alt-format');

    if (min) {
      opts.minDate = min;
    } else if (type === 'booking') {
      opts.minDate = 'today';     // a tour cannot start in the past
    }
    if (max) opts.maxDate = max;
    if (alt) opts.altFormat = alt;

    return opts;
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

  /**
   * Create a picker on `el` with the Alam defaults underneath.
   * Page scripts (the booking wizard) use this instead of calling flatpickr()
   * so the theme, the value format and the mobile behaviour stay in one place.
   */
  function create(el, opts) {
    if (!el || el._flatpickr) return el ? el._flatpickr : null;
    var config = merge(DEFAULTS, opts || {});
    config.locale = merge(localeOption(), (opts && opts.locale) || {});
    return window.flatpickr(el, config);
  }

  /* ---- Field wiring ----------------------------------------------------- */

  function wrapperOf(el) { return el.closest ? el.closest('.datepicker-field') : null; }

  /** Swap the calendar icon for the clear × once the field holds a date. */
  function syncClear(fp) {
    var wrap = wrapperOf(fp.input);
    if (wrap) wrap.classList.toggle('is-filled', !!fp.input.value);
  }

  /**
   * Keep the original input's `change`/`input` events flowing.
   *
   * Flatpickr writes the value straight into the hidden input, and existing page
   * scripts listen with addEventListener — so the events are dispatched by hand,
   * the same way js/select2-init.js relays Select2's jQuery-only change.
   */
  function relayNativeEvents(fp) {
    fp.input.dispatchEvent(new Event('input', { bubbles: true }));
    fp.input.dispatchEvent(new Event('change', { bubbles: true }));
  }

  function enhance(el) {
    if (el._flatpickr) return el._flatpickr;

    var fp = create(el, merge(optionsFor(el), {
      onReady: function (dates, str, inst) {
        var wrap = wrapperOf(inst.input);
        if (wrap) wrap.classList.add('is-ready');
        if (inst.altInput) {
          // Mirror the accessible name and the invalid state onto the field the
          // visitor actually focuses.
          if (inst.input.id) {
            var label = document.querySelector('label[for="' + inst.input.id + '"]');
            if (label) inst.altInput.setAttribute('aria-labelledby', label.id || (label.id = inst.input.id + '-label'));
          }
          inst.altInput.setAttribute('data-datepicker-input', '');
        }
        inst.calendarContainer.classList.add('alam-flatpickr');
        syncClear(inst);
      },
      onChange: function (dates, str, inst) {
        syncClear(inst);
        relayNativeEvents(inst);
      }
    }));

    /* The calendar icon opens the picker — the visible input is readonly, so a
     * click anywhere in the field should feel the same. */
    var wrap = wrapperOf(el);
    if (wrap) {
      var toggle = wrap.querySelector('[data-datepicker-toggle]');
      if (toggle) {
        toggle.addEventListener('click', function () {
          if (fp.isOpen) { fp.close(); } else { fp.open(); }
        });
      }
      var clear = wrap.querySelector('[data-datepicker-clear]');
      if (clear) {
        clear.addEventListener('click', function () {
          fp.clear();
          syncClear(fp);
          relayNativeEvents(fp);
        });
      }
    }

    return fp;
  }

  /** "Leaving Madinah" can never be before "Arriving in Madinah". */
  function linkRanges(scope) {
    Array.prototype.forEach.call(scope.querySelectorAll('input.js-datepicker[data-link-min]'), function (el) {
      var source = document.getElementById(el.getAttribute('data-link-min'));
      if (!source || !el._flatpickr) return;

      var floor = el._flatpickr.config.minDate || null;   // the field's own minimum
      function apply() {
        el._flatpickr.set('minDate', source.value || floor);
      }
      source.addEventListener('change', apply);
      if (source.value) apply();
    });
  }

  function enhanceAll(root) {
    var scope = root || document;
    Array.prototype.forEach.call(scope.querySelectorAll('input.js-datepicker'), enhance);
    linkRanges(scope);
  }

  /* form.reset() empties the real input silently — Flatpickr has to be told. */
  document.addEventListener('reset', function (ev) {
    if (!ev.target || !ev.target.querySelectorAll) return;
    var fields = ev.target.querySelectorAll('input.js-datepicker');
    if (!fields.length) return;
    window.setTimeout(function () {
      Array.prototype.forEach.call(fields, function (el) {
        if (el._flatpickr) { el._flatpickr.setDate(el.defaultValue || null, false); syncClear(el._flatpickr); }
      });
    }, 0);
  });

  /* Flatpickr appends each calendar to <body>, outside the content that
   * js/htmx-init.js swaps; destroying the pickers in a region that is about
   * to be replaced removes those calendars with it. */
  function destroyAll(root) {
    var scope = root || document;
    Array.prototype.forEach.call(scope.querySelectorAll('input.js-datepicker'), function (el) {
      if (el._flatpickr) el._flatpickr.destroy();
    });
  }

  /* Exposed so page scripts can build a picker (the booking wizard's inline
   * calendar) or refresh one after its available dates changed. */
  window.FrontendDatepicker = {
    defaults: DEFAULTS,
    create: create,
    enhance: enhance,
    enhanceAll: enhanceAll,
    destroyAll: destroyAll
  };

  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', function () { enhanceAll(); });
  } else {
    enhanceAll();
  }
}());
