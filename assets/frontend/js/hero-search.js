/**
 * Alam Al-Munawara — homepage availability search.
 *
 * The tour select is rendered by PHP. Vehicles and languages depend on the
 * chosen tour, so their options are swapped here from the JSON block printed
 * inside the form (see slider_tour_search.php), which also carries the
 * translated alert copy. Every value is an encrypted booking reference — this
 * script never builds or inspects IDs itself.
 *
 * Tour and date are required. On submit the choice is checked against the
 * server (data-availability-url): if a guide is free the visitor is sent to
 * the booking page with their selection, otherwise a SweetAlert2 message asks
 * them to change the date or preferences. The date window itself (tomorrow
 * onwards) is set by the server on the date field, and enforced again by the
 * endpoint.
 */
(function () {
  'use strict';

  /* The form belongs to the home page, which js/htmx-init.js can swap in and
     out without a page load, so everything below runs per form through
     init(scope). One pageshow listener serves whichever form is current. */
  var onPageShow = null;

  window.addEventListener('pageshow', function (event) {
    if (onPageShow) onPageShow(event);
  });

  function init(scope) {
    var form = (scope || document).querySelector('[data-availability-form]:not([data-availability-ready])');
    if (!form) return;
    form.setAttribute('data-availability-ready', '');

    var tourSelect = form.querySelector('[data-availability-tour]');
    var vehicleSelect = form.querySelector('[data-availability-vehicle]');
    var languageSelect = form.querySelector('[data-availability-language]');
    var dateInput = form.querySelector('input[name="date"]');
    var submitButton = form.querySelector('button[type="submit"]');
    var dataNode = form.querySelector('[data-availability-data]');
    var checkUrl = form.getAttribute('data-availability-url');
    if (!tourSelect || !vehicleSelect || !languageSelect || !dateInput || !submitButton || !checkUrl) return;

    var config = {};
    try {
      config = JSON.parse(dataNode ? dataNode.textContent : '{}') || {};
    } catch (e) {
      config = {};
    }
    var toursById = config.tours || {};
    var messages = config.messages || {};
    var submitLabel = submitButton.innerHTML;
    var checking = false;
    var ALERT_SECONDS = 5;

    function refreshSelect2(select) {
      if (window.FrontendSelect2) window.FrontendSelect2.refresh(select);
    }

    /** Keep the placeholder option, replace the rest, and lock the field when empty. */
    function fill(select, items) {
      while (select.options.length > 1) {
        select.remove(1);
      }
      items.forEach(function (item) {
        select.add(new Option(item.label, item.id));
      });
      select.value = '';
      select.disabled = items.length === 0;
      refreshSelect2(select);
    }

    function setError(name, visible) {
      var box = form.querySelector('[data-availability-error="' + name + '"]');
      var field = name === 'tour' ? tourSelect : dateInput;
      if (box) box.classList.toggle('hidden', !visible);
      field.setAttribute('aria-invalid', visible ? 'true' : 'false');
    }

    function focusDate() {
      var picker = dateInput._flatpickr;
      if (picker && picker.altInput) {
        picker.altInput.focus();
      } else {
        dateInput.focus();
      }
    }

    function setChecking(on) {
      checking = on;
      submitButton.disabled = on;
      submitButton.setAttribute('aria-busy', on ? 'true' : 'false');
      submitButton.innerHTML = on ? messages.checking || '' : submitLabel;
    }

    function selection() {
      var params = new URLSearchParams();
      [tourSelect, dateInput, vehicleSelect, languageSelect].forEach(function (field) {
        if (field.value) params.set(field.name, field.value);
      });
      return params.toString();
    }

    function alertUser(title, text) {
      if (!window.Swal) {
        window.alert(text);
        return;
      }
      window.Swal.fire({
        icon: 'info',
        titleText: title,
        text: text,
        confirmButtonText: messages.confirm || 'OK',
        buttonsStyling: false,
        customClass: { popup: 'booking-alert-popup availability-alert-popup', confirmButton: 'btn-primary' },
        returnFocus: false,
        // Dismissible by the ✕, the button, Esc or a click outside; otherwise it
        // closes itself once the visitor has had time to read it.
        showCloseButton: true,
        closeButtonHtml: '&times;',
        timer: ALERT_SECONDS * 1000,
        timerProgressBar: true,
        didOpen: function (popup) {
          // Hovering the message pauses the countdown so it never closes mid-read.
          popup.addEventListener('mouseenter', window.Swal.stopTimer);
          popup.addEventListener('mouseleave', window.Swal.resumeTimer);
        }
      });
    }

    function alertUnavailable() {
      alertUser(messages.unavailableTitle, messages.unavailableText);
    }

    function alertError() {
      alertUser(messages.errorTitle, messages.errorText);
    }

    function checkAvailability(query) {
      var controller = new AbortController();
      var timeout = window.setTimeout(function () {
        controller.abort();
      }, 15000);

      setChecking(true);
      fetch(checkUrl + '?' + query, {
        method: 'GET',
        credentials: 'same-origin',
        headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
        signal: controller.signal
      }).then(function (response) {
        return response.json();
      }).then(function (result) {
        if (result && result.success && result.available) {
          // Leave the button in its checking state while the browser navigates.
          window.location.assign(form.action + '?' + query);
          return;
        }
        setChecking(false);
        if (result && result.success) {
          alertUnavailable();
        } else {
          alertError();
        }
      }).catch(function () {
        setChecking(false);
        alertError();
      }).finally(function () {
        window.clearTimeout(timeout);
      });
    }

    tourSelect.addEventListener('change', function () {
      var tour = toursById[tourSelect.value] || { vehicles: [], languages: [] };
      fill(vehicleSelect, tour.vehicles);
      fill(languageSelect, tour.languages);
      if (tourSelect.value) setError('tour', false);
    });

    dateInput.addEventListener('change', function () {
      if (dateInput.value) setError('date', false);
    });

    form.addEventListener('submit', function (event) {
      event.preventDefault();
      if (checking) return;

      var missingTour = !tourSelect.value;
      var missingDate = !dateInput.value;
      setError('tour', missingTour);
      setError('date', missingDate);
      if (missingTour) {
        tourSelect.focus();
        return;
      }
      if (missingDate) {
        focusDate();
        return;
      }

      checkAvailability(selection());
    });

    // A page restored from the back/forward cache must not stay stuck on "Checking…".
    onPageShow = function (event) {
      if (event.persisted && checking) setChecking(false);
    };

    // Restore dependent options when the browser brings back a remembered tour.
    if (tourSelect.value) {
      tourSelect.dispatchEvent(new Event('change'));
    }
  }

  window.AlamHeroSearch = { init: init };

  init();
})();
