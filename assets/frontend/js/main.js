/**
 * Alam Al-Munawara — site-wide behaviour.
 * Vanilla JS, no framework. Every widget is opt-in via a data attribute so the
 * same file can be dropped into CodeIgniter views unchanged.
 *
 * ONE copy of this file serves both locales. Nothing here is written twice for
 * Arabic: every string the script generates comes from window.FrontendI18n, which
 * reads the JSON island header.php prints (see locale_script() in
 * inc/i18n.php). Layout that depends on direction is handled in CSS through
 * [dir="rtl"] and Tailwind's logical utilities, not here.
 */

/* =========================================================================
 * Locale runtime
 *
 * Defined outside the main IIFE and attached to window, because the other
 * shared scripts need it: js/booking.js, js/datepicker.js, js/phone-input.js
 * and js/select2-init.js all load after main.js (footer.php emits main.js
 * first and every tag is `defer`, so document order is execution order).
 *
 * Everything degrades: a missing payload leaves `locale` at 'en', `dir` at
 * 'ltr' and t() returning the key, which is visible during review rather than
 * silently blank.
 * ====================================================================== */
(function () {
  'use strict';

  var CONFIG = { locale: 'en', tag: 'en', dir: 'ltr', rtl: false, currency: 'SAR', messages: {}, plurals: {} };

  var node = document.getElementById('alam-locale-data');
  if (node) {
    try {
      var parsed = JSON.parse(node.textContent);
      for (var k in parsed) {
        if (Object.prototype.hasOwnProperty.call(parsed, k)) CONFIG[k] = parsed[k];
      }
    } catch (err) {
      /* Leave the English defaults in place rather than taking the page down. */
    }
  }

  /** Fill {token} placeholders. Mirrors interpolate() in inc/i18n.php. */
  function interpolate(text, params) {
    if (!params) return text;
    return String(text).replace(/\{(\w+)\}/g, function (match, name) {
      return Object.prototype.hasOwnProperty.call(params, name) ? String(params[name]) : match;
    });
  }

  /**
   * The CLDR plural category for n. Mirrors plural_category() in
   * inc/i18n.php exactly — a count rendered by PHP and the same count
   * re-rendered here by a filter have to agree.
   */
  function pluralCategory(n) {
    if (CONFIG.locale !== 'ar') return n === 1 ? 'one' : 'other';
    if (n === 0) return 'zero';
    if (n === 1) return 'one';
    if (n === 2) return 'two';
    var mod100 = n % 100;
    if (mod100 >= 3 && mod100 <= 10) return 'few';
    if (mod100 >= 11 && mod100 <= 99) return 'many';
    return 'other';
  }

  /**
   * A grouped integer. Intl.NumberFormat where the browser has it, falling
   * back to a manual thousands separator on the older Android and iOS devices
   * this site still supports. The digits stay Western in both locales — the
   * 'en-US' tag is a formatting choice, not a language one, and it is what
   * keeps 1,250 reading the same way in an Arabic price as in an English one.
   */
  var numberFormatter = null;
  if (window.Intl && typeof window.Intl.NumberFormat === 'function') {
    try {
      numberFormatter = new window.Intl.NumberFormat('en-US');
    } catch (err) {
      numberFormatter = null;
    }
  }

  function formatNumber(value) {
    var n = Number(value);
    if (isNaN(n)) return String(value);
    if (numberFormatter) return numberFormatter.format(n);
    return String(n).replace(/\B(?=(\d{3})+(?!\d))/g, ',');
  }

  /**
   * A date for display, from a Y-m-d string.
   *
   * Intl.DateTimeFormat with the page's BCP 47 tag gives Arabic weekday and
   * month names for free. `numberingSystem: 'latn'` pins the digits to 0-9,
   * because ar-SA otherwise renders Eastern Arabic numerals, which would not
   * match the prices and the telephone numbers beside them. Falls back to the
   * ISO string on a browser without Intl.
   */
  var dateFormatter = null;
  if (window.Intl && typeof window.Intl.DateTimeFormat === 'function') {
    try {
      dateFormatter = new window.Intl.DateTimeFormat(CONFIG.tag + '-u-nu-latn', {
        weekday: 'short', day: 'numeric', month: 'short', year: 'numeric'
      });
    } catch (err) {
      dateFormatter = null;
    }
  }

  function formatDate(iso) {
    var parts = String(iso).split('-');
    if (parts.length !== 3) return String(iso);
    var d = new Date(Number(parts[0]), Number(parts[1]) - 1, Number(parts[2]));
    if (isNaN(d.getTime())) return String(iso);
    if (dateFormatter) {
      try {
        return dateFormatter.format(d);
      } catch (err) { /* fall through */ }
    }
    return String(iso);
  }

  window.FrontendI18n = {
    locale: CONFIG.locale,
    tag: CONFIG.tag,
    dir: CONFIG.dir,
    rtl: !!CONFIG.rtl,
    currency: CONFIG.currency,
    config: CONFIG,

    /** One message by key, with {token} interpolation. */
    t: function (key, params) {
      var value = Object.prototype.hasOwnProperty.call(CONFIG.messages, key)
        ? CONFIG.messages[key]
        : key;
      return interpolate(value, params);
    },

    /** A counted message, choosing the plural form this locale uses. */
    tn: function (key, count, params) {
      var forms = CONFIG.plurals[key];
      if (!forms) return interpolate(key, params);

      var category = pluralCategory(count);
      var template = Object.prototype.hasOwnProperty.call(forms, category)
        ? forms[category]
        : forms.other;

      var merged = { count: formatNumber(count) };
      if (params) {
        for (var k in params) {
          if (Object.prototype.hasOwnProperty.call(params, k)) merged[k] = params[k];
        }
      }
      return interpolate(template, merged);
    },

    plural: pluralCategory,
    number: formatNumber,
    date: formatDate,

    /** A price, using the same template and managed currency as PHP. Always a whole
     * currency unit, matching the integer-only money columns on the server. */
    price: function (amount) {
      return interpolate(CONFIG.messages['format.price'] || '{currency} {amount}', {
        amount: formatNumber(Math.round(Number(amount) || 0)),
        currency: CONFIG.currency
      });
    }
  };
}());

(function () {
  'use strict';

  var reduceMotion = window.matchMedia('(prefers-reduced-motion: reduce)').matches;
  var i18n = window.FrontendI18n;

  /* ---------------------------------------------------------------------
   * Mobile navigation
   * ------------------------------------------------------------------ */
  function initMenu() {
    var toggle = document.querySelector('[data-menu-toggle]');
    var panel = document.querySelector('[data-menu-panel]');
    if (!toggle || !panel) return;

    var openIcon = toggle.querySelector('[data-menu-open]');
    var closeIcon = toggle.querySelector('[data-menu-close]');

    function setOpen(open) {
      toggle.setAttribute('aria-expanded', open ? 'true' : 'false');
      panel.classList.toggle('hidden', !open);
      if (openIcon) openIcon.classList.toggle('hidden', open);
      if (closeIcon) closeIcon.classList.toggle('hidden', !open);
      toggle.querySelector('.sr-only').textContent = i18n.t(open ? 'menu.close' : 'menu.open');
      document.body.style.overflow = open ? 'hidden' : '';
    }

    toggle.addEventListener('click', function () {
      setOpen(toggle.getAttribute('aria-expanded') !== 'true');
    });

    document.addEventListener('keydown', function (ev) {
      if (ev.key === 'Escape' && toggle.getAttribute('aria-expanded') === 'true') {
        setOpen(false);
        toggle.focus();
      }
    });

    window.addEventListener('resize', function () {
      if (window.innerWidth >= 1024 && toggle.getAttribute('aria-expanded') === 'true') setOpen(false);
    });
  }

  /* ---------------------------------------------------------------------
   * Language switcher
   *
   * Works for every [data-lang-switcher] on the page (desktop header + mobile
   * menu) from one instance of this code.
   *
   * Every locale is live, so each option is either a real <a href> to the same
   * page in the other locale — which this code deliberately does NOT intercept,
   * so middle-click, ctrl-click and JavaScript-off all behave — or a <button>
   * marking the locale already being read. The menu is a roving-focus menu:
   * arrows, Home, End, Escape and Tab all move or close it.
   * ------------------------------------------------------------------ */
  function initLanguageSwitchers() {
    var switchers = Array.prototype.slice.call(document.querySelectorAll('[data-lang-switcher]'));
    if (!switchers.length) return;

    var open = null;

    function closeAll(refocus) {
      switchers.forEach(function (root) {
        var trigger = root.querySelector('[data-lang-trigger]');
        var menu = root.querySelector('[data-lang-menu]');
        var chevron = root.querySelector('[data-lang-chevron]');
        if (!trigger || !menu) return;
        trigger.setAttribute('aria-expanded', 'false');
        menu.classList.add('hidden');
        if (chevron) chevron.classList.remove('rotate-180');
      });
      if (refocus && open) {
        var t = open.querySelector('[data-lang-trigger]');
        if (t) t.focus();
      }
      open = null;
    }

    function items(root) {
      return Array.prototype.slice.call(root.querySelectorAll('[data-lang-option]'));
    }

    function openMenu(root) {
      closeAll(false);
      var trigger = root.querySelector('[data-lang-trigger]');
      var menu = root.querySelector('[data-lang-menu]');
      var chevron = root.querySelector('[data-lang-chevron]');
      trigger.setAttribute('aria-expanded', 'true');
      menu.classList.remove('hidden');
      if (chevron) chevron.classList.add('rotate-180');
      open = root;
    }

    switchers.forEach(function (root) {
      var trigger = root.querySelector('[data-lang-trigger]');
      var menu = root.querySelector('[data-lang-menu]');
      if (!trigger || !menu) return;

      trigger.addEventListener('click', function (ev) {
        ev.stopPropagation();
        if (trigger.getAttribute('aria-expanded') === 'true') {
          closeAll(false);
        } else {
          openMenu(root);
        }
      });

      // Open with the keyboard and land on the first option
      trigger.addEventListener('keydown', function (ev) {
        if (ev.key === 'ArrowDown' || ev.key === 'ArrowUp') {
          ev.preventDefault();
          openMenu(root);
          var list = items(root);
          var target = ev.key === 'ArrowDown' ? list[0] : list[list.length - 1];
          if (target) target.focus();
        }
      });

      menu.addEventListener('click', function (ev) { ev.stopPropagation(); });

      items(root).forEach(function (option, index) {
        option.addEventListener('click', function (ev) {
          // The active locale is a <button>: it is a state, not a destination.
          if (option.tagName === 'BUTTON') {
            ev.preventDefault();
            closeAll(true);
          }
          // A real <a href> for the other locale falls through and navigates.
        });

        /* Up/Down walk the list in visual order, which the browser already
           mirrors under dir="rtl" — a vertical menu needs no direction-aware
           key handling. */
        option.addEventListener('keydown', function (ev) {
          var list = items(root);
          if (ev.key === 'ArrowDown') {
            ev.preventDefault();
            (list[index + 1] || list[0]).focus();
          } else if (ev.key === 'ArrowUp') {
            ev.preventDefault();
            (list[index - 1] || list[list.length - 1]).focus();
          } else if (ev.key === 'Home') {
            ev.preventDefault();
            list[0].focus();
          } else if (ev.key === 'End') {
            ev.preventDefault();
            list[list.length - 1].focus();
          } else if (ev.key === 'Tab') {
            closeAll(false);
          }
        });
      });
    });

    document.addEventListener('click', function () { closeAll(false); });
    document.addEventListener('keydown', function (ev) {
      if (ev.key === 'Escape' && open) closeAll(true);
    });
  }

  /* ---------------------------------------------------------------------
   * Accordions (FAQs, tour details)
   *
   * `scope` limits the search to newly swapped content (see initRegion());
   * the ready marker keeps a second run from binding a trigger twice.
   * ------------------------------------------------------------------ */
  function initAccordions(scope) {
    (scope || document).querySelectorAll('[data-accordion]:not([data-accordion-ready])').forEach(function (root) {
      root.setAttribute('data-accordion-ready', '');
      var single = root.hasAttribute('data-accordion-single');

      root.querySelectorAll('.accordion-trigger').forEach(function (trigger) {
        trigger.addEventListener('click', function () {
          var panel = document.getElementById(trigger.getAttribute('aria-controls'));
          var willOpen = trigger.getAttribute('aria-expanded') !== 'true';

          if (single && willOpen) {
            root.querySelectorAll('.accordion-trigger[aria-expanded="true"]').forEach(function (other) {
              if (other === trigger) return;
              other.setAttribute('aria-expanded', 'false');
              var op = document.getElementById(other.getAttribute('aria-controls'));
              if (op) op.setAttribute('data-open', 'false');
            });
          }

          trigger.setAttribute('aria-expanded', willOpen ? 'true' : 'false');
          if (panel) panel.setAttribute('data-open', willOpen ? 'true' : 'false');
        });
      });
    });
  }

  /* ---------------------------------------------------------------------
   * Reveal on scroll — purely decorative, skipped when motion is reduced
   * ------------------------------------------------------------------ */
  function initReveal(scope) {
    var items = (scope || document).querySelectorAll('[data-reveal]:not([data-reveal-ready])');
    if (!items.length) return;

    items.forEach(function (el) { el.setAttribute('data-reveal-ready', ''); });

    if (reduceMotion || !('IntersectionObserver' in window)) {
      items.forEach(function (el) { el.classList.add('is-revealed'); });
      return;
    }

    items.forEach(function (el) {
      el.style.opacity = '0';
      el.style.transform = 'translateY(14px)';
      el.style.transition = 'opacity .55s cubic-bezier(.22,.61,.36,1), transform .55s cubic-bezier(.22,.61,.36,1)';
    });

    var io = new IntersectionObserver(function (entries) {
      entries.forEach(function (entry) {
        if (!entry.isIntersecting) return;
        var el = entry.target;
        var delay = parseInt(el.getAttribute('data-reveal-delay') || '0', 10);
        setTimeout(function () {
          el.style.opacity = '1';
          el.style.transform = 'none';
        }, delay);
        io.unobserve(el);
      });
    }, { rootMargin: '0px 0px -8% 0px', threshold: 0.08 });

    items.forEach(function (el) { io.observe(el); });
  }

  /* ---------------------------------------------------------------------
   * Scroll a filtered listing back to its first result.
   *
   * Both listing pages put a sticky bar under the sticky header, so the
   * usual scrollIntoView would tuck the card underneath both of them. The
   * offset is measured rather than hardcoded because the header changes
   * height at lg and the bar changes height when a phone panel opens.
   * ------------------------------------------------------------------ */
  function scrollToFirstResult(target) {
    if (!target) return;

    var header = document.querySelector('[data-site-header]');
    var bar = document.querySelector('[data-sticky-bar]');
    var offset = (header ? header.offsetHeight : 0) + (bar ? bar.offsetHeight : 0) + 16;
    var top = target.getBoundingClientRect().top + window.pageYOffset - offset;

    window.scrollTo({
      top: Math.max(0, top),
      behavior: reduceMotion ? 'auto' : 'smooth'
    });
  }

  /* ---------------------------------------------------------------------
   * Shared by the Tours and Experiences listings: a card's `data-categories`
   * (a comma list of stable database ids) is checked first, falling back to
   * the single `data-category` a prototype-shaped card still carries.
   * ------------------------------------------------------------------ */
  function cardMatchesCategory(card, value) {
    var categoryValues = card.getAttribute('data-categories')
      || card.getAttribute('data-category')
      || '';
    return categoryValues.split(',').indexOf(value) !== -1;
  }

  /* ---------------------------------------------------------------------
   * Tours listing — client-side filtering
   * ------------------------------------------------------------------ */
  function initTourFilter(scope) {
    var form = (scope || document).querySelector('[data-tour-filter]:not([data-tour-filter-ready])');
    if (!form) return;
    form.setAttribute('data-tour-filter-ready', '');

    var grid = document.querySelector('[data-tour-grid]');
    var empty = document.querySelector('[data-tour-empty]');
    var count = document.querySelector('[data-tour-count]');
    // The applied-filters chip sits with the result count, outside the form.
    var active = document.querySelector('[data-tour-active]');
    var reset = form.querySelector('[data-tour-reset]');
    var toggle = form.querySelector('[data-filter-toggle]');
    var fields = form.querySelector('[data-tour-fields]');
    var toggleCount = form.querySelector('[data-filter-toggle-count]');
    var cards = Array.prototype.slice.call(document.querySelectorAll('[data-tour-card]'));

    function value(name) {
      return (form.elements[name] && form.elements[name].value) || '';
    }

    /* The first card still showing, or the empty state when a combination
       matches nothing — either way, the thing the visitor should now see. */
    function firstResult() {
      for (var i = 0; i < cards.length; i++) {
        if (!cards[i].parentElement.classList.contains('hidden')) return cards[i];
      }
      return empty && !empty.classList.contains('hidden') ? empty : null;
    }

    function apply() {
      var category = value('category');
      var capacity = parseInt(value('capacity') || '0', 10);
      var language = value('language');
      var applied = 0;
      var shown = 0;

      if (category) applied++;
      if (capacity) applied++;
      if (language) applied++;

      cards.forEach(function (card) {
        var ok = true;
        if (category && !cardMatchesCategory(card, category)) ok = false;
        if (capacity && parseInt(card.getAttribute('data-capacity'), 10) < capacity) ok = false;
        if (language) {
          var langs = (card.getAttribute('data-languages') || '').split(',');
          if (langs.indexOf(language) === -1) ok = false;
        }

        card.parentElement.classList.toggle('hidden', !ok);
        if (ok) shown++;
      });

      if (empty) empty.classList.toggle('hidden', shown > 0);
      if (grid) grid.classList.toggle('hidden', shown === 0);
      if (count) count.textContent = i18n.tn('count.tours', shown);

      // Reset is only meaningful once something is narrowing the list, and the
      // chip tells you how much is applied when the form has scrolled away.
      if (reset) reset.disabled = applied === 0;

      var label = i18n.tn('count.filters', applied);
      if (active) {
        active.hidden = applied === 0;
        active.textContent = label;
      }
      // The phone toggle carries the same count, so a collapsed panel still
      // says whether anything is narrowing the list.
      if (toggleCount) {
        toggleCount.hidden = applied === 0;
        toggleCount.textContent = i18n.tn('count.filtersShort', applied);
      }
    }

    // apply() also runs on load, so the scroll lives on the events a person
    // triggers rather than inside apply() itself.
    form.addEventListener('change', function () {
      apply();
      scrollToFirstResult(firstResult());
    });

    // There is no submit button — the selects filter as they change — but a
    // stray Enter key must not reload the page and lose the current filter.
    form.addEventListener('submit', function (ev) {
      ev.preventDefault();
      apply();
    });

    // Reset is a native type="reset" button, so the browser clears the selects
    // and select2-init.js redraws them off the same event. This only has to
    // re-run the filter once those values are back to their defaults.
    form.addEventListener('reset', function () {
      window.setTimeout(function () {
        apply();
        scrollToFirstResult(firstResult());
      }, 0);
    });

    /* -------------------------------------------------------------------
     * Phone disclosure. `sm:grid` on the panel outranks `hidden`, so the
     * class only collapses anything below 640px — the same breakpoint that
     * hides the button. Nothing here runs on a laptop.
     * ---------------------------------------------------------------- */
    if (toggle && fields) {
      var chevron = toggle.querySelector('[data-filter-chevron]');

      var setOpen = function (open) {
        fields.classList.toggle('hidden', !open);
        toggle.setAttribute('aria-expanded', open ? 'true' : 'false');
        if (chevron) chevron.classList.toggle('rotate-180', open);
      };

      setOpen(false);

      toggle.addEventListener('click', function () {
        setOpen(toggle.getAttribute('aria-expanded') !== 'true');
      });
    }

    apply();
  }

  /* ---------------------------------------------------------------------
   * Multi-step forms — [data-wizard]
   *
   * Generic: any form can opt in by wrapping its fields in
   * [data-wizard-step="n"] sections and adding the next/prev/submit buttons.
   * Nothing here knows what the fields mean.
   *
   * Progressive enhancement: the markup ships with every step visible and
   * `required` intact, so with JavaScript off it degrades to one long form
   * the browser still validates. Once this runs, `required` moves to
   * `aria-required` — a hidden empty control would otherwise make the
   * browser refuse to submit a form it cannot focus.
   * ------------------------------------------------------------------ */
  function initFormWizard() {
    document.querySelectorAll('[data-wizard]').forEach(function (form) {
      var steps = Array.prototype.slice.call(form.querySelectorAll('[data-wizard-step]'));
      if (steps.length < 2) return;

      var nextBtn = form.querySelector('[data-wizard-next]');
      var prevBtn = form.querySelector('[data-wizard-prev]');
      var submitBtn = form.querySelector('[data-wizard-submit]');
      var label = form.querySelector('[data-wizard-label]');
      var announce = form.querySelector('[data-wizard-announce]');
      var total = steps.length;
      var current = 1;

      /* Take ownership of validation, remembering what was required. */
      var required = [];
      steps.forEach(function (step, i) {
        Array.prototype.forEach.call(step.querySelectorAll('[required]'), function (field) {
          field.removeAttribute('required');
          field.setAttribute('aria-required', 'true');
          required.push({ step: i + 1, field: field });
        });
      });

      function error(name, show) {
        var el = form.querySelector('[data-error="' + name + '"]');
        if (el) el.classList.toggle('hidden', !show);
      }

      /* One rule per control type — enough for a contact form, and it keeps
         the wizard reusable rather than teaching it these field names. */
      function isValid(field) {
        var v = (field.value || '').trim();
        if (v === '') return false;
        if (field.type === 'email') return /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(v);
        if (field.type === 'tel') return v.replace(/[^0-9]/g, '').length >= 6;
        if (field.type === 'number') {
          var n = Number(v);
          if (isNaN(n)) return false;
          if (field.min !== '' && n < Number(field.min)) return false;
          if (field.max !== '' && n > Number(field.max)) return false;
          return true;
        }
        if (field.tagName === 'SELECT') return true;
        return v.length > 1;
      }

      function validate(step) {
        var ok = true;
        var firstInvalid = null;

        required.forEach(function (entry) {
          if (entry.step !== step) return;
          var good = isValid(entry.field);
          error(entry.field.name, !good);
          entry.field.setAttribute('aria-invalid', good ? 'false' : 'true');
          if (good) return;
          ok = false;
          firstInvalid = firstInvalid || entry.field;
        });

        if (firstInvalid) {
          // Select2 draws its own control over the native one, so focus the
          // replacement when there is one.
          var container = firstInvalid.nextElementSibling;
          var proxy = container && container.classList.contains('select2-container')
            ? container.querySelector('.select2-selection')
            : null;
          (proxy || firstInvalid).focus();
        }
        return ok;
      }

      function show(step, moveFocus) {
        current = step;

        steps.forEach(function (section, i) {
          section.classList.toggle('hidden', i + 1 !== step);
        });

        form.querySelectorAll('[data-wizard-bar]').forEach(function (bar) {
          var n = Number(bar.getAttribute('data-wizard-bar'));
          bar.classList.toggle('scale-x-100', n <= step);
          bar.classList.toggle('scale-x-0', n > step);
        });

        var last = step === total;
        if (nextBtn) nextBtn.classList.toggle('hidden', last);
        if (submitBtn) submitBtn.classList.toggle('hidden', !last);
        if (prevBtn) prevBtn.classList.toggle('hidden', step === 1);

        /* Both strings are rebuilt from the catalogue, so the announcement a
           screen reader hears is in the language of the page it is on. */
        var stepParams = { step: i18n.number(step), count: i18n.number(total) };
        if (label) label.textContent = i18n.tn('plan.step.label', total, stepParams);
        if (announce) {
          var title = steps[step - 1].getAttribute('data-wizard-title') || '';
          announce.textContent = i18n.tn('plan.step.announce', total, {
            step: stepParams.step, count: stepParams.count, title: title
          });
        }

        if (moveFocus) {
          var heading = steps[step - 1].querySelector('h3');
          if (heading) {
            heading.setAttribute('tabindex', '-1');
            heading.focus();
          }
          scrollToFirstResult(form);
        }
      }

      if (nextBtn) {
        nextBtn.addEventListener('click', function () {
          if (!validate(current)) return;
          var next = Math.min(current + 1, total);
          /* A page-specific listener (e.g. plan-your-trip.js) can intercept
             this to save the step to the server first: calling
             preventDefault() pauses the advance here, and the listener
             calls form.wizardGoTo(next) itself once its own request
             succeeds. No listener means the event is a no-op and the
             wizard advances immediately, exactly as before. */
          var proceed = form.dispatchEvent(new CustomEvent('wizard:beforenext', {
            cancelable: true,
            detail: { step: current, next: next }
          }));
          if (!proceed) return;
          show(next, true);
        });
      }

      if (prevBtn) {
        prevBtn.addEventListener('click', function () {
          show(Math.max(current - 1, 1), true);
        });
      }

      /* Guard the real submit: every step has to pass, not just the last. */
      form.addEventListener('submit', function (ev) {
        for (var step = 1; step <= total; step++) {
          if (validate(step)) continue;
          ev.preventDefault();
          ev.stopImmediatePropagation();
          show(step, true);
          return;
        }
      });

      /* Clear an error as soon as the visitor fixes the field. */
      form.addEventListener('change', function (ev) {
        var match = required.filter(function (entry) { return entry.field === ev.target; })[0];
        if (match && isValid(match.field)) {
          error(match.field.name, false);
          match.field.setAttribute('aria-invalid', 'false');
        }
      });

      /* The demo handler resets the form after a successful send; put the
         wizard back at the beginning so the next visitor starts at step 1. */
      form.addEventListener('reset', function () {
        window.setTimeout(function () {
          required.forEach(function (entry) {
            error(entry.field.name, false);
            entry.field.setAttribute('aria-invalid', 'false');
          });
          show(1, false);
        }, 0);
      });

      /* Exposed so a page-specific listener can navigate the wizard itself
         after an asynchronous step save, and so it can read what step is
         currently visible without keeping its own copy of that state. */
      form.wizardGoTo = function (step, moveFocus) {
        show(Math.max(1, Math.min(total, Number(step) || 1)), moveFocus !== false);
      };
      form.wizardCurrentStep = function () { return current; };

      var startStep = parseInt(form.getAttribute('data-wizard-active-step'), 10);
      if (!startStep || startStep < 1 || startStep > total) startStep = 1;
      show(startStep, false);
    });
  }

  /* ---------------------------------------------------------------------
   * Experiences / Tour Guides listings — chip filter
   *
   * Progressive enhancement: the markup ships with every card visible in
   * one grid, so with JavaScript off the page is a complete, readable
   * listing. This only adds the ability to narrow it.
   *
   * The controls are real <button> elements carrying aria-pressed, so they
   * work from the keyboard without any extra key handling, and the result
   * count is announced through a polite live region.
   *
   * `name` selects the data-<name>-filter/-card/-count/-empty hooks,
   * `countKey` the counted message and `param` the deep-link parameter.
   * ------------------------------------------------------------------ */
  function initChipFilter(name, countKey, param, scope) {
    var bar = (scope || document).querySelector('[data-' + name + '-filter]:not([data-chip-filter-ready])');
    if (!bar) return;
    bar.setAttribute('data-chip-filter-ready', '');

    var buttons = Array.prototype.slice.call(bar.querySelectorAll('[data-filter]'));
    var cards = Array.prototype.slice.call(document.querySelectorAll('[data-' + name + '-card]'));
    var count = document.querySelector('[data-' + name + '-count]');
    var empty = document.querySelector('[data-' + name + '-empty]');
    if (!buttons.length || !cards.length) return;

    function apply(value) {
      var shown = 0;

      cards.forEach(function (card) {
        var ok = value === 'all' || cardMatchesCategory(card, value);
        var item = card.parentElement;
        if (item) item.classList.toggle('hidden', !ok);
        if (ok) shown++;
      });

      buttons.forEach(function (btn) {
        btn.setAttribute('aria-pressed', btn.getAttribute('data-filter') === value ? 'true' : 'false');
      });

      if (count) {
        count.textContent = i18n.tn(countKey, shown);
      }
      if (empty) empty.classList.toggle('hidden', shown > 0);

      /* history.state is kept, not cleared: after a partial navigation it
         holds the marker HTMX needs to handle Back/Forward for this entry
         (js/htmx-init.js). On a normal page load it is null either way. */
      if (history.replaceState) {
        history.replaceState(history.state, '', value === 'all' ? location.pathname : location.pathname + '?' + param + '=' + value);
      }
    }

    function firstResult() {
      for (var i = 0; i < cards.length; i++) {
        if (!cards[i].parentElement.classList.contains('hidden')) return cards[i];
      }
      return empty && !empty.classList.contains('hidden') ? empty : null;
    }

    buttons.forEach(function (btn) {
      btn.addEventListener('click', function () {
        apply(btn.getAttribute('data-filter'));
        scrollToFirstResult(firstResult());
      });
    });

    // Deep link support: /experiences?category=3, /tour-guides?language=2
    var initial = (location.search.match(new RegExp('[?&]' + param + '=([^&]+)')) || [])[1];
    initial = initial ? decodeURIComponent(initial) : 'all';
    if (!buttons.some(function (b) { return b.getAttribute('data-filter') === initial; })) {
      initial = 'all';
    }
    apply(initial);
  }

  /* ---------------------------------------------------------------------
   * Lightweight testimonial / card carousel
   * ------------------------------------------------------------------ */
  function initCarousels(scope) {
    (scope || document).querySelectorAll('[data-carousel]:not([data-carousel-ready])').forEach(function (root) {
      root.setAttribute('data-carousel-ready', '');
      var track = root.querySelector('[data-carousel-track]');
      var prev = root.querySelector('[data-carousel-prev]');
      var next = root.querySelector('[data-carousel-next]');
      if (!track) return;

      function step() {
        var first = track.firstElementChild;
        return first ? first.getBoundingClientRect().width + 20 : 320;
      }
      function scrollBy(dir) {
        track.scrollBy({ left: dir * step(), behavior: reduceMotion ? 'auto' : 'smooth' });
      }
      function sync() {
        var max = track.scrollWidth - track.clientWidth - 2;
        if (prev) prev.disabled = track.scrollLeft <= 2;
        if (next) next.disabled = track.scrollLeft >= max;
      }

      if (prev) prev.addEventListener('click', function () { scrollBy(-1); });
      if (next) next.addEventListener('click', function () { scrollBy(1); });
      track.addEventListener('scroll', sync, { passive: true });
      window.addEventListener('resize', sync);
      sync();
    });
  }

  /* ---------------------------------------------------------------------
   * The line a section has to cross to count as "the one being read".
   * Derived from the same two values the browser uses to place an anchor
   * jump — the scrollport's scroll-padding-top and the target's own
   * scroll-margin-top — so a clicked section always lands past its own
   * line. Shared by initSectionNav() and initTocNav() so both rails agree
   * with the browser's own landing position.
   * ------------------------------------------------------------------ */
  function scrollReadingLine(section) {
    var padTop = parseFloat(getComputedStyle(document.documentElement).scrollPaddingTop);
    if (isNaN(padTop)) {
      var headerH = parseInt(
        getComputedStyle(document.documentElement).getPropertyValue('--frontend-header-h'),
        10
      );
      padTop = (isNaN(headerH) ? 68 : headerH) + 16;
    }
    var marginTop = parseFloat(getComputedStyle(section).scrollMarginTop) || 0;
    return padTop + marginTop + 8;
  }

  /* ---------------------------------------------------------------------
   * Sticky in-page tabs on the tour/experience detail page
   *
   * The bar is itself sticky just below the header, so a clicked section
   * needs to clear both. A click drives window.scrollTo() directly rather
   * than letting the <a href="#..."> navigate natively: relying on the
   * native jump meant this handler's own scrollIntoView() call (to reveal
   * the clicked pill in the horizontal chip strip) raced it on the same
   * window scroller, and jumping several sections away could lose that
   * race and land short. Driving the scroll ourselves — the same pattern
   * scrollToFirstResult() above uses for the tours/experiences listing
   * bar — removes the second scroll source entirely, and the pill reveal
   * now moves only the chip strip's own scrollLeft. The active pill itself
   * is one shared indicator element that slides under the current link —
   * sliding a single element, rather than recoloring each pill, is what
   * makes the transition read as movement.
   * ------------------------------------------------------------------ */
  /* The bar lives inside <main>, which a partial navigation replaces
     (js/htmx-init.js). The window listeners are added once and always act on
     the bar currently in the page, through these two hooks. */
  var sectionNavScroll = null;
  var sectionNavResize = null;
  var sectionNavListening = false;

  function initSectionNav(scope) {
    var nav = (scope || document).querySelector('[data-section-nav]:not([data-section-nav-ready])');
    if (!nav) return;
    nav.setAttribute('data-section-nav-ready', '');

    var header = document.querySelector('[data-site-header]');
    var list = nav.querySelector('[data-section-nav-list]');
    var indicator = nav.querySelector('[data-section-nav-indicator]');
    var links = Array.prototype.slice.call(nav.querySelectorAll('[data-section-nav-link]'));
    var sections = links
      .map(function (a) { return document.querySelector(a.getAttribute('href')); })
      .filter(Boolean);
    if (!sections.length || sections.length !== links.length) return;

    var current = -1;
    var locked = -1;
    var lockTimer = 0;
    var ticking = false;
    var jumpOffset = 0;

    /* scroll-margin-top (on top of the header that <html>'s site-wide
       scroll-padding-top already reserves) keeps the scroll-spy threshold
       lined up with the bar for anyone who still lands here natively
       (typed/bookmarked #hash, browser back/forward). jumpOffset is the
       same bar height plus the header itself, because scroll-padding-top
       has no effect on the explicit window.scrollTo() below. */
    function syncOffsets() {
      var barOffset = Math.ceil(nav.getBoundingClientRect().height) + 16;
      jumpOffset = (header ? header.offsetHeight : 0) + barOffset;
      sections.forEach(function (section) {
        section.style.scrollMarginTop = barOffset + 'px';
      });
    }

    function moveIndicator(link, animate) {
      if (!indicator || !list) return;
      var listRect = list.getBoundingClientRect();
      var linkRect = link.getBoundingClientRect();

      if (!animate) indicator.style.transitionDuration = '0s';
      indicator.style.width = linkRect.width + 'px';
      indicator.style.transform = 'translateX(' + (linkRect.left - listRect.left + list.scrollLeft) + 'px)';
      indicator.style.opacity = '1';
      if (!animate) {
        void indicator.offsetWidth;
        indicator.style.transitionDuration = '';
      }
    }

    /* Brings the pill into view within the horizontal chip strip only.
       scrollIntoView() would do this via every scrollable ancestor, the
       window included — which is exactly what raced the vertical jump. */
    function revealLink(link) {
      if (!list) return;
      var listRect = list.getBoundingClientRect();
      var linkRect = link.getBoundingClientRect();
      if (linkRect.left < listRect.left) {
        list.scrollLeft -= (listRect.left - linkRect.left) + 16;
      } else if (linkRect.right > listRect.right) {
        list.scrollLeft += (linkRect.right - listRect.right) + 16;
      }
    }

    function setActive(index, animate) {
      if (index === current) return;
      current = index;
      links.forEach(function (a, i) {
        if (i === index) {
          a.setAttribute('aria-current', 'true');
        } else {
          a.removeAttribute('aria-current');
        }
      });

      var active = links[index];
      if (!active) return;
      moveIndicator(active, animate !== false);
      revealLink(active);
    }

    function update(initial) {
      ticking = false;

      if (locked >= 0) {
        setActive(locked, false);
        return;
      }

      var index = 0;
      sections.forEach(function (section, i) {
        if (section.getBoundingClientRect().top <= scrollReadingLine(section)) {
          index = i;
        }
      });

      if (window.innerHeight + window.scrollY >= document.documentElement.scrollHeight - 2) {
        index = sections.length - 1;
      }

      setActive(index, !initial);
    }

    function requestUpdate() {
      if (ticking) return;
      ticking = true;
      window.requestAnimationFrame(function () { update(false); });
    }

    links.forEach(function (link, index) {
      link.addEventListener('click', function (ev) {
        /* Leave modified/middle clicks (open in a new tab, etc.) alone. */
        if (ev.button !== 0 || ev.metaKey || ev.ctrlKey || ev.shiftKey || ev.altKey) {
          return;
        }
        var section = sections[index];
        if (!section) return;
        ev.preventDefault();

        setActive(index);
        locked = index;
        window.clearTimeout(lockTimer);
        /* Wins outright until the smooth scroll has settled, so the
           animation's intermediate positions cannot flip the marker back. */
        lockTimer = window.setTimeout(function () { locked = -1; }, 800);

        var top = section.getBoundingClientRect().top + window.pageYOffset - jumpOffset;
        window.scrollTo({ top: Math.max(0, top), behavior: reduceMotion ? 'auto' : 'smooth' });

        /* Keep history.state: after a partial navigation it holds HTMX's
           Back/Forward marker. On a normal page load it is null anyway. */
        if (history.replaceState) {
          history.replaceState(history.state, '', link.getAttribute('href'));
        }
      });
    });

    sectionNavScroll = requestUpdate;
    sectionNavResize = function () {
      syncOffsets();
      if (links[current]) moveIndicator(links[current], false);
      requestUpdate();
    };

    if (!sectionNavListening) {
      sectionNavListening = true;
      window.addEventListener('scroll', function () {
        if (sectionNavScroll) sectionNavScroll();
      }, { passive: true });
      window.addEventListener('resize', function () {
        if (sectionNavResize) sectionNavResize();
      });
    }

    syncOffsets();
    update(true);
  }

  /* ---------------------------------------------------------------------
   * Section table of contents - scroll spy
   *
   * Drives every [data-toc-nav] rail on the page (FAQ categories, and the
   * policy pages' "on this page"). Exactly one link carries aria-current at
   * a time. The active one is computed from document order rather than
   * toggled per observer entry: several sections are on screen together on
   * a tall viewport, so reacting to each intersection separately would
   * light up more than one. The rule is "the last section whose top has
   * passed the reading line", with the final section pinned once the page
   * is scrolled to the bottom, where no further section top can pass it.
   * ------------------------------------------------------------------ */
  /* The rails live inside <main>, which a partial navigation replaces
     (js/htmx-init.js). The window listeners are added once and always drive
     the rails currently in the page. */
  var tocRails = [];
  var tocListening = false;

  function initTocNav(scope) {
    var navs = Array.prototype.slice.call(
      (scope || document).querySelectorAll('[data-toc-nav]:not([data-toc-ready])')
    );
    if (!navs.length) return;

    var newRails = navs.map(function (nav) {
      nav.setAttribute('data-toc-ready', '');
      var links = Array.prototype.slice.call(nav.querySelectorAll('a[href^="#"]'));
      var sections = links
        .map(function (a) { return document.querySelector(a.getAttribute('href')); })
        .filter(Boolean);
      return { nav: nav, links: links, sections: sections, current: -1, locked: -1, lockTimer: 0 };
    }).filter(function (rail) {
      return rail.sections.length && rail.sections.length === rail.links.length;
    });

    // Rails from a swapped-out <main> are no longer in the document.
    tocRails = tocRails.filter(function (rail) {
      return document.documentElement.contains(rail.nav);
    }).concat(newRails);
    if (!tocRails.length) return;

    function setActive(rail, index) {
      if (index === rail.current) return;
      rail.current = index;
      rail.links.forEach(function (a, i) {
        if (i === index) {
          a.setAttribute('aria-current', 'true');
        } else {
          a.removeAttribute('aria-current');
        }
      });
    }

    /* See scrollReadingLine() above initSectionNav() — same line, shared so
       this rail and the tour/experience section nav agree with the
       browser's own anchor-jump landing position. */
    var lineFor = scrollReadingLine;

    function update() {
      var atBottom = window.innerHeight + window.pageYOffset >=
        document.documentElement.scrollHeight - 2;

      tocRails.forEach(function (rail) {
        /* A click wins outright until the smooth scroll has settled, so the
           intermediate positions of the animation cannot flip the marker. */
        if (rail.locked >= 0) {
          setActive(rail, rail.locked);
          return;
        }

        var active = 0;
        rail.sections.forEach(function (section, i) {
          if (section.getBoundingClientRect().top <= lineFor(section)) active = i;
        });
        /* At the bottom of the page the last sections may all sit above the
           line already; make sure the final one wins there. */
        if (atBottom) active = rail.sections.length - 1;
        setActive(rail, active);
      });
    }

    var ticking = false;
    function onScroll() {
      if (ticking) return;
      ticking = true;
      window.requestAnimationFrame(function () {
        ticking = false;
        update();
      });
    }

    newRails.forEach(function (rail) {
      rail.links.forEach(function (link, index) {
        link.addEventListener('click', function () {
          setActive(rail, index);
          rail.locked = index;
          window.clearTimeout(rail.lockTimer);
          rail.lockTimer = window.setTimeout(function () { rail.locked = -1; }, 800);
        });
      });
    });

    if (!tocListening) {
      tocListening = true;
      window.addEventListener('scroll', onScroll, { passive: true });
      window.addEventListener('resize', onScroll);
    }
    update();
  }


  /* ---------------------------------------------------------------------
   * Basic client-side form feedback (no backend submission yet)
   * ------------------------------------------------------------------ */
  function initDemoForms() {
    document.querySelectorAll('[data-demo-form]').forEach(function (form) {
      form.addEventListener('submit', function (ev) {
        ev.preventDefault();
        if (!form.reportValidity()) return;
        var note = form.querySelector('[data-demo-note]');
        if (note) {
          note.classList.remove('hidden');
          note.focus();
        }
        form.reset();
      });
    });
  }

  /* ---------------------------------------------------------------------
   * Blog category bar
   *
   * Sticky strip under the site header on the blog pages. Two toggles share
   * this code: the phone/tablet "Blog Categories" trigger that reveals the
   * whole list, and the large-screen "More topics" overflow dropdown.
   * ------------------------------------------------------------------ */
  /* The bar is inside <main>, so a partial navigation (js/htmx-init.js)
     replaces it. The document-level listeners are added once and always act
     on the bar currently in the page. */
  var blogCatNav = null;
  var blogCatNavListening = false;

  function initBlogCategoryNav(scope) {
    var nav = (scope || document).querySelector('[data-blog-cat-nav]');
    if (!nav || nav.hasAttribute('data-blog-cat-ready')) return;
    nav.setAttribute('data-blog-cat-ready', '');

    var toggle = nav.querySelector('[data-blog-cat-toggle]');
    var panel = nav.querySelector('[data-blog-cat-panel]');
    var moreToggle = nav.querySelector('[data-blog-cat-more-toggle]');
    var morePanel = nav.querySelector('[data-blog-cat-more-panel]');

    function setOpen(trigger, target, open) {
      if (!trigger || !target) return;
      trigger.setAttribute('aria-expanded', open ? 'true' : 'false');
      target.classList.toggle('hidden', !open);
      var chevron = trigger.querySelector('[data-blog-cat-chevron]');
      if (chevron) chevron.classList.toggle('rotate-180', open);
    }

    function closeAll() {
      setOpen(toggle, panel, false);
      setOpen(moreToggle, morePanel, false);
    }

    if (toggle && panel) {
      toggle.addEventListener('click', function (ev) {
        ev.stopPropagation();
        var open = toggle.getAttribute('aria-expanded') !== 'true';
        closeAll();
        setOpen(toggle, panel, open);
      });
    }

    if (moreToggle && morePanel) {
      moreToggle.addEventListener('click', function (ev) {
        ev.stopPropagation();
        var open = moreToggle.getAttribute('aria-expanded') !== 'true';
        closeAll();
        setOpen(moreToggle, morePanel, open);
      });
    }

    blogCatNav = {
      nav: nav,
      toggle: toggle,
      moreToggle: moreToggle,
      closeAll: closeAll
    };

    if (blogCatNavListening) return;
    blogCatNavListening = true;

    document.addEventListener('click', function (ev) {
      if (blogCatNav && !blogCatNav.nav.contains(ev.target)) blogCatNav.closeAll();
    });

    document.addEventListener('keydown', function (ev) {
      if (ev.key !== 'Escape' || !blogCatNav) return;
      var current = blogCatNav;
      if (current.moreToggle && current.moreToggle.getAttribute('aria-expanded') === 'true') {
        current.closeAll();
        current.moreToggle.focus();
      } else if (current.toggle && current.toggle.getAttribute('aria-expanded') === 'true') {
        current.closeAll();
        current.toggle.focus();
      }
    });

    // Crossing the lg breakpoint swaps which control is visible; anything left
    // open belongs to the layout that just disappeared.
    window.addEventListener('resize', function () {
      if (blogCatNav) blogCatNav.closeAll();
    });
  }

  /* ---------------------------------------------------------------------
   * CMS tables — wrap every <table> pasted into `.page-contents` in a
   * `.page-contents-table-wrap` card.
   *
   * The scroll/card chrome (border, radius, shadow, horizontal scroll)
   * cannot live on the <table> itself: `display: block` on a <table>
   * stops it being a table for layout purposes, so the browser drops in
   * an anonymous, shrink-to-fit table box for the row groups underneath
   * — `width: 100%` then only reaches that outer block box, and the
   * header/cell backgrounds stop short of the card's actual width. A
   * plain wrapper element sidesteps that: the table stays a real
   * `display: table` and simply fills the wrapper.
   *
   * Exposed on window so lightbox-init.js can wrap tables inside the
   * attraction popup's CKEditor content too, after it sets innerHTML.
   * ------------------------------------------------------------------ */
  function wrapPageContentTables(root) {
    (root || document).querySelectorAll('.page-contents table:not([data-table-wrapped])').forEach(function (table) {
      table.setAttribute('data-table-wrapped', 'true');
      var wrap = document.createElement('div');
      wrap.className = 'page-contents-table-wrap';
      table.parentNode.insertBefore(wrap, table);
      wrap.appendChild(table);
    });
  }
  window.AlamPageContents = { wrapTables: wrapPageContentTables };

  /* ---------------------------------------------------------------------
   * Tooltips — shown by CSS on hover/focus. Escape hides any open one
   * without moving the pointer or focus; it can show again once the
   * pointer or focus leaves and comes back.
   * ------------------------------------------------------------------ */
  var tooltipsListening = false;

  function initTooltips(scope) {
    var tooltips = Array.prototype.slice.call(
      (scope || document).querySelectorAll('[data-tooltip]:not([data-tooltip-ready])')
    );
    if (!tooltips.length) return;

    tooltips.forEach(function (tooltip) {
      tooltip.setAttribute('data-tooltip-ready', '');
      function reset() {
        tooltip.classList.remove('is-dismissed');
      }
      tooltip.addEventListener('mouseleave', reset);
      tooltip.addEventListener('focusout', reset);
    });

    if (tooltipsListening) return;
    tooltipsListening = true;

    // Looked up on each Escape so tooltips swapped in later are included.
    document.addEventListener('keydown', function (ev) {
      if (ev.key !== 'Escape') return;
      document.querySelectorAll('[data-tooltip]').forEach(function (tooltip) {
        if (tooltip.matches(':hover') || tooltip.contains(document.activeElement)) {
          tooltip.classList.add('is-dismissed');
        }
      });
    });
  }

  /* ---------------------------------------------------------------------
   * Re-initialise the content-level widgets inside a region that was
   * replaced without a page load (js/htmx-init.js swaps <main>). Header
   * widgets (menu, language switcher) are never swapped, and the form
   * wizard is on a page that is always loaded in full, so neither is
   * repeated here.
   * ------------------------------------------------------------------ */
  function initRegion(root) {
    // Only one section bar exists at a time; a swap without one retires it.
    if (!root.querySelector || !root.querySelector('[data-section-nav]')) {
      sectionNavScroll = null;
      sectionNavResize = null;
    }
    initBlogCategoryNav(root);
    initTourFilter(root);
    initChipFilter('experience', 'count.experiences', 'category', root);
    initChipFilter('guide', 'count.guides', 'language', root);
    initAccordions(root);
    initReveal(root);
    initCarousels(root);
    initTooltips(root);
    initTocNav(root);
    initSectionNav(root);
    wrapPageContentTables(root);
  }
  window.AlamFrontend = { initRegion: initRegion };

  function init() {
    initMenu();
    initBlogCategoryNav();
    initLanguageSwitchers();
    initAccordions();
    initReveal();
    initTourFilter();
    initChipFilter('experience', 'count.experiences', 'category');
    initChipFilter('guide', 'count.guides', 'language');
    initFormWizard();
    initCarousels();
    initTooltips();
    initSectionNav();
    initTocNav();
    initDemoForms();
    wrapPageContentTables();
  }

  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', init);
  } else {
    init();
  }
})();
