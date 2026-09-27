/** Shared header/footer tour picker. Cache one request/result for this page. */
(function () {
  'use strict';

  var dialog = document.getElementById('booking-picker');
  if (!dialog || typeof dialog.showModal !== 'function') return;

  var select = dialog.querySelector('#booking-picker-tour');
  var content = dialog.querySelector('[data-booking-picker-content]');
  var loading = dialog.querySelector('[data-picker-loading]');
  var choices = dialog.querySelector('[data-picker-choices]');
  var empty = dialog.querySelector('[data-picker-empty]');
  var error = dialog.querySelector('[data-picker-error]');
  var continueButton = dialog.querySelector('[data-picker-continue]');
  var tours = [];
  var request = null;
  var opener = null;
  var previousOverflow = '';

  function selectedTour() {
    return tours.find(function (tour) {
      return tour.id === select.value;
    });
  }

  function showState(state) {
    loading.hidden = state !== 'loading';
    choices.hidden = state !== 'ready';
    empty.hidden = state !== 'empty';
    error.hidden = state !== 'error';
    content.setAttribute('aria-busy', state === 'loading' ? 'true' : 'false');
    select.disabled = state !== 'ready';
    continueButton.disabled = state !== 'ready' || !selectedTour();
  }

  function renderSelection() {
    continueButton.disabled = !selectedTour();
  }

  function tourOption(option, compact) {
    var tour = tours.find(function (item) { return item.id === option.id; });
    if (!tour) return option.text;

    var $ = window.jQuery;
    var card = $('<span>', { 'class': 'booking-tour-option' + (compact ? ' is-selected-value' : '') });
    var image = $('<span>', { 'class': 'booking-tour-option-image', 'aria-hidden': 'true' });
    if (tour.image) {
      image.append($('<img>', { src: tour.image, alt: '', loading: 'lazy' }));
    } else {
      image.text(tour.title.trim().charAt(0));
    }
    var details = $('<span>', { 'class': 'booking-tour-option-details' });
    details.append($('<span>', { 'class': 'booking-tour-option-title', text: tour.title }));
    if (!compact && tour.summary) {
      details.append($('<span>', { 'class': 'booking-tour-option-summary', text: tour.summary }));
    }
    details.append($('<span>', {
      'class': 'booking-tour-option-meta',
      text: window.FrontendI18n.t('book.from', { price: window.FrontendI18n.price(tour.price) })
    }));
    return card.append(image, details);
  }

  function enhanceTourSelect() {
    if (!window.FrontendSelect2) return;
    window.FrontendSelect2.enhance(select, {
      dropdownParent: window.jQuery(dialog),
      minimumResultsForSearch: 0,
      placeholder: select.options[0].text,
      templateResult: function (option) { return tourOption(option, false); },
      templateSelection: function (option) { return tourOption(option, true); }
    });
    window.jQuery(select).next('.select2-container').addClass('booking-tour-select');
    window.FrontendSelect2.refresh(select);
  }

  function loadTours() {
    // Keep both the in-flight request and the completed result across close/reopen.
    if (request) return request;
    showState('loading');
    var controller = new AbortController();
    var timeout = window.setTimeout(function () {
      controller.abort();
    }, 15000);

    request = fetch(dialog.getAttribute('data-options-url'), {
      method: 'GET',
      credentials: 'same-origin',
      headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
      signal: controller.signal
    }).then(function (response) {
      if (!response.ok) throw new Error('Request failed');
      return response.json();
    }).then(function (data) {
      if (!data.success || !Array.isArray(data.tours)) throw new Error('Invalid response');
      tours = data.tours;
      tours.forEach(function (tour) {
        select.add(new Option(tour.title, tour.id));
      });
      showState(tours.length ? 'ready' : 'empty');
      enhanceTourSelect();
      renderSelection();
    }).catch(function () {
      showState('error');
    }).finally(function () {
      window.clearTimeout(timeout);
    });
    return request;
  }

  document.querySelectorAll('[data-booking-picker-open]').forEach(function (button) {
    button.addEventListener('click', function () {
      if (dialog.open) return;
      // Close the shared mobile navigation through its existing handler.
      var menuToggle = document.querySelector('[data-menu-toggle]');
      if (menuToggle && menuToggle.getAttribute('aria-expanded') === 'true') menuToggle.click();
      opener = button.getClientRects().length ? button : (menuToggle || button);
      previousOverflow = document.body.style.overflow;
      document.body.style.overflow = 'hidden';
      dialog.showModal();
      loadTours();
    });
  });

  dialog.querySelectorAll('[data-booking-picker-close]').forEach(function (button) {
    button.addEventListener('click', function () {
      dialog.close();
    });
  });
  dialog.addEventListener('click', function (event) {
    if (event.target !== dialog) return;
    var bounds = dialog.getBoundingClientRect();
    if (event.clientX < bounds.left || event.clientX > bounds.right || event.clientY < bounds.top || event.clientY > bounds.bottom) {
      dialog.close();
    }
  });
  dialog.addEventListener('close', function () {
    if (window.jQuery && window.jQuery(select).data('select2')) window.jQuery(select).select2('close');
    document.body.style.overflow = previousOverflow;
    if (opener && opener.getClientRects().length) opener.focus();
  });
  select.addEventListener('change', renderSelection);
  dialog.querySelector('[data-picker-retry]').addEventListener('click', function () {
    request = null;
    loadTours();
  });
  continueButton.addEventListener('click', function () {
    var tour = selectedTour();
    if (!tour || continueButton.disabled) return;
    window.location.assign(dialog.getAttribute('data-book-url') + '?i=' + encodeURIComponent(tour.id));
  });
})();
