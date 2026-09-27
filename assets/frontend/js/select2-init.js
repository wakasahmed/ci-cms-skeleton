/**
 * Alam Al-Munawara — Select2 initialiser
 *
 * One place where every public-facing <select class="js-select2"> is enhanced.
 * Pages never call .select2() themselves; they opt a field in with the class and
 * tune it, if needed, with data attributes:
 *
 *   data-placeholder="Select country"   native Select2 placeholder (needs an
 *                                       <option value=""> to attach to)
 *   data-select2-search="true|false"    force the search box on or off; the
 *                                       default is "on once the list is long"
 *   data-select2-clear="true"           show the little × that clears the value
 *
 * The native <select> stays the real form field: same name, id, options, selected
 * value, required flag and validation. Select2 only draws the control, so PHP
 * handling and existing jQuery-free page scripts keep working unchanged.
 *
 * ONE copy of this file serves both locales. The language pack and the text
 * direction come from window.FrontendI18n (js/main.js); the Arabic pack itself is
 * Select2's own unmodified i18n/ar.js, which functions.php loads before this
 * file on Arabic pages (see vendor/select2/VERSION.txt).
 */
(function () {
  'use strict';

  if (!window.jQuery || !window.jQuery.fn || !window.jQuery.fn.select2) return;

  var $ = window.jQuery;
  var i18n = window.FrontendI18n;
  var select2Config = (i18n && i18n.config && i18n.config.select2) || {};

  /* Lists shorter than this read fine without a search box — a two-option
   * "Preferred language" with a search field looks broken, not helpful. */
  var SEARCH_THRESHOLD = 8;

  function optionCount(select) {
    return select.querySelectorAll('option').length;
  }

  function wantsSearch(select) {
    var forced = select.getAttribute('data-select2-search');
    if (forced === 'true') return true;
    if (forced === 'false') return false;
    return optionCount(select) >= SEARCH_THRESHOLD;
  }

  /**
   * Give the control an accessible name from its visible <label>, which stays
   * on the page and keeps pointing at the native select.
   */
  function nameFromLabel(select, $container) {
    if (!select.id) return;
    var label = document.querySelector('label[for="' + select.id + '"]');
    if (!label) return;
    if (!label.id) label.id = select.id + '-label';

    var selection = $container[0].querySelector('.select2-selection');
    if (!selection) return;
    var existing = selection.getAttribute('aria-labelledby');
    selection.setAttribute('aria-labelledby', existing ? label.id + ' ' + existing : label.id);
  }

  /**
   * Re-broadcast Select2's jQuery-only change as a real DOM event.
   *
   * Every script on this site binds with addEventListener, and jQuery's
   * .trigger('change') never reaches those listeners. Without this the tour
   * filter, the booking state and the booking summary would all stop updating.
   */
  function relayNativeEvents(select) {
    $(select).on('select2:select select2:unselect select2:clear', function () {
      select.dispatchEvent(new Event('input', { bubbles: true }));
      select.dispatchEvent(new Event('change', { bubbles: true }));
    });
  }

  function enhance(select, overrides) {
    var $select = $(select);
    if ($select.data('select2')) return;   // never initialise the same field twice

    var options = {
      width: '100%',
      minimumResultsForSearch: wantsSearch(select) ? 0 : Infinity,
      // Select2 only draws a clear button when there is a placeholder to fall
      // back to, so the two are checked together.
      allowClear: select.getAttribute('data-select2-clear') === 'true'
                  && !!select.getAttribute('data-placeholder'),
      /* `dir` mirrors the control, the dropdown and the clear ×; `language`
         swaps the plugin's own strings ("No results found", the search
         prompts). Only set when the pack is actually loaded — passing a
         language Select2 has no module for throws. */
      dir: select2Config.dir || 'ltr'
    };

    var pack = select2Config.language;
    if (pack && pack !== 'en' && $.fn.select2.amd) {
      options.language = pack;
    }

    $select.select2($.extend({}, options, overrides || {}));

    /* The theme hooks. `containerCssClass` / `dropdownCssClass` would do this,
     * but they live in Select2's compat modules, which the plain dist build
     * does not ship — so the classes go on directly. The dropdown is rebuilt
     * every time it opens, hence the event. */
    var $container = $select.next('.select2-container').addClass('alam-select2');
    $select.on('select2:open', function () {
      $('.select2-container--open .select2-dropdown').addClass('alam-select2-dropdown');
    });

    nameFromLabel(select, $container);
    relayNativeEvents(select);

    /* Select2 forwards focus from the hidden select to its own control, but the
     * booking wizard leans on that when it jumps to the first invalid field, so
     * it is pinned down here too. Focusing an already-focused element is a no-op,
     * which makes the belt-and-braces free. */
    select.addEventListener('focus', function () {
      var selection = $container[0].querySelector('.select2-selection');
      if (selection) selection.focus();
    });
  }

  /** Redraw a field after its value or its options changed in JavaScript. */
  function refresh(select) {
    if ($(select).data('select2')) $(select).trigger('change.select2');
  }

  function enhanceAll(root) {
    var scope = root || document;
    Array.prototype.forEach.call(scope.querySelectorAll('select.js-select2'), enhance);
  }

  /* form.reset() restores the native values silently — Select2 has to be told. */
  document.addEventListener('reset', function (ev) {
    if (!ev.target || !ev.target.querySelectorAll) return;
    var selects = ev.target.querySelectorAll('select.js-select2');
    if (!selects.length) return;
    window.setTimeout(function () {
      Array.prototype.forEach.call(selects, refresh);
    }, 0);
  });

  /* Exposed so page scripts that rebuild <option> lists can redraw the control
   * instead of tearing the plugin down and setting it up again. */
  window.FrontendSelect2 = { enhance: enhance, enhanceAll: enhanceAll, refresh: refresh };

  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', function () { enhanceAll(); });
  } else {
    enhanceAll();
  }
}());
