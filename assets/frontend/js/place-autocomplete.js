/**
 * Alam Al-Munawara — place autocomplete
 *
 * Turns a text input into an accessible combobox that suggests places from
 * Google Places, limited to Madinah and written in the site language. The
 * browser never talks to Google: it calls this site's own endpoints
 * (Frontend::booking_places() and Frontend::booking_place()), which use the
 * service account on the server.
 *
 * Markup (see views/frontend/partials/booking/details.php):
 *
 *   <div data-place-autocomplete data-suggest-url="…" data-details-url="…">
 *     <input data-place-input>            the visible field, still plain free text
 *     <input type="hidden" data-place-token>   filled only after a suggestion is picked
 *     <ul data-place-list></ul>           the suggestion listbox
 *     <p data-place-status></p>           screen-reader announcements
 *   </div>
 *
 * The token is cleared as soon as the text is edited, so the saved place always
 * matches what the guest can see in the field. The typing session token groups
 * the suggestion requests with the final place lookup for Google's billing.
 */
(function () {
  'use strict';

  var i18n = window.FrontendI18n;
  var DEBOUNCE_MS = 250;
  var MIN_CHARACTERS = 2;
  var MAX_CHARACTERS = 255;

  function message(key, params) {
    return i18n ? i18n.t(key, params) : '';
  }

  function newSessionToken() {
    var crypto = window.crypto;
    if (crypto && typeof crypto.randomUUID === 'function') return crypto.randomUUID();

    var bytes = new Uint8Array(16);
    if (crypto && crypto.getRandomValues) {
      crypto.getRandomValues(bytes);
    } else {
      for (var i = 0; i < bytes.length; i += 1) bytes[i] = Math.floor(Math.random() * 256);
    }
    return Array.prototype.map.call(bytes, function (byte) {
      return ('0' + byte.toString(16)).slice(-2);
    }).join('');
  }

  function post(url, params, signal) {
    var body = new URLSearchParams();
    Object.keys(params).forEach(function (key) {
      body.set(key, params[key]);
    });

    return fetch(url, {
      method: 'POST',
      body: body,
      credentials: 'same-origin',
      headers: { 'Accept': 'application/json' },
      signal: signal
    }).then(function (response) {
      if (!response.ok) throw new Error('HTTP ' + response.status);
      return response.json();
    });
  }

  function fire(element, type) {
    element.dispatchEvent(new Event(type, { bubbles: true }));
  }

  function init(root) {
    var input = root.querySelector('[data-place-input]');
    var tokenField = root.querySelector('[data-place-token]');
    var list = root.querySelector('[data-place-list]');
    var status = root.querySelector('[data-place-status]');
    var suggestUrl = root.getAttribute('data-suggest-url');
    var detailsUrl = root.getAttribute('data-details-url');
    var bookingRoot = input ? input.closest('#booking-form') : null;
    var bookingTokenField = bookingRoot
      ? bookingRoot.querySelector('[name="booking_token"]')
      : null;

    if (!input || !tokenField || !list || !suggestUrl || !detailsUrl || !bookingTokenField) {
      return;
    }
    if (typeof fetch !== 'function' || typeof URLSearchParams !== 'function') return;

    var suggestions = [];
    var activeIndex = -1;
    var sessionToken = newSessionToken();
    var timer = null;
    var controller = null;
    var requestId = 0;
    var programmatic = false;

    input.setAttribute('role', 'combobox');
    input.setAttribute('aria-autocomplete', 'list');
    input.setAttribute('aria-expanded', 'false');
    input.setAttribute('aria-controls', list.id);
    input.setAttribute('autocomplete', 'off');

    function announce(text) {
      if (status) status.textContent = text;
    }

    function bookingToken() {
      return bookingTokenField.value;
    }

    function isOpen() {
      return !list.classList.contains('hidden');
    }

    function close() {
      list.classList.add('hidden');
      input.setAttribute('aria-expanded', 'false');
      input.removeAttribute('aria-activedescendant');
      activeIndex = -1;
    }

    function setActive(index) {
      var options = list.children;
      if (activeIndex >= 0 && options[activeIndex]) {
        options[activeIndex].removeAttribute('aria-selected');
        options[activeIndex].classList.remove('is-active');
      }

      activeIndex = index;

      if (index >= 0 && options[index]) {
        options[index].setAttribute('aria-selected', 'true');
        options[index].classList.add('is-active');
        input.setAttribute('aria-activedescendant', options[index].id);
        if (options[index].scrollIntoView) options[index].scrollIntoView({ block: 'nearest' });
      } else {
        input.removeAttribute('aria-activedescendant');
      }
    }

    function render() {
      list.textContent = '';

      suggestions.forEach(function (suggestion, index) {
        var option = document.createElement('li');
        var main = document.createElement('span');
        var secondary = document.createElement('span');

        option.id = list.id + '-option-' + index;
        option.className = 'place-option';
        option.setAttribute('role', 'option');
        main.className = 'place-option-main';
        main.textContent = suggestion.main_text;
        option.appendChild(main);

        if (suggestion.secondary_text) {
          secondary.className = 'place-option-secondary';
          secondary.textContent = suggestion.secondary_text;
          option.appendChild(secondary);
        }

        // Keeps focus in the input, so choosing an option never blurs the field.
        option.addEventListener('mousedown', function (event) {
          event.preventDefault();
        });
        option.addEventListener('click', function () {
          choose(index);
        });
        option.addEventListener('mousemove', function () {
          if (activeIndex !== index) setActive(index);
        });

        list.appendChild(option);
      });

      activeIndex = -1;
      list.classList.remove('hidden');
      input.setAttribute('aria-expanded', 'true');
      announce(message('book.place.available'));
    }

    function cancelPending() {
      window.clearTimeout(timer);
      requestId += 1;
      if (controller) controller.abort();
      controller = null;
    }

    function search(query) {
      var id;

      cancelPending();
      id = requestId;
      controller = typeof AbortController === 'function' ? new AbortController() : null;

      post(suggestUrl, {
        booking_token: bookingToken(),
        q: query,
        s: sessionToken
      }, controller ? controller.signal : undefined).then(function (data) {
        if (id !== requestId) return;

        suggestions = data && data.success && Array.isArray(data.suggestions) ? data.suggestions : [];
        if (suggestions.length === 0) {
          close();
          announce(message('book.place.empty'));
          return;
        }
        render();
      }).catch(function (error) {
        if (id !== requestId || (error && error.name === 'AbortError')) return;

        // Free text keeps working, so a failed lookup is only announced, never blocking.
        suggestions = [];
        close();
        announce(message('book.place.error'));
      });
    }

    function choose(index) {
      var suggestion = suggestions[index];
      var text;
      var lookupToken;

      if (!suggestion) return;

      cancelPending();
      suggestions = [];
      text = String(suggestion.text).slice(0, MAX_CHARACTERS);

      // The programmatic events below must not be read as the guest typing.
      programmatic = true;
      input.value = text;
      tokenField.value = '';
      fire(input, 'input');
      fire(input, 'change');
      programmatic = false;

      close();
      announce(message('book.place.selected', { place: text }));

      // The session ends with the place lookup; the next search starts a new one.
      lookupToken = sessionToken;
      sessionToken = newSessionToken();

      post(detailsUrl, {
        booking_token: bookingToken(),
        place_id: suggestion.place_id,
        s: lookupToken
      }).then(function (data) {
        // Ignore the answer if the guest edited the text while it was on its way.
        if (data && data.success && data.token && input.value === text) {
          tokenField.value = data.token;
        }
      }).catch(function () {
        tokenField.value = '';
      });
    }

    input.addEventListener('input', function () {
      var query = input.value.trim();

      if (programmatic) return;

      tokenField.value = '';
      cancelPending();

      if (query.length < MIN_CHARACTERS) {
        suggestions = [];
        close();
        return;
      }

      timer = window.setTimeout(function () {
        search(query);
      }, DEBOUNCE_MS);
    });

    input.addEventListener('keydown', function (event) {
      var count = suggestions.length;

      if (event.key === 'ArrowDown' || event.key === 'ArrowUp') {
        if (count === 0) return;
        event.preventDefault();

        if (!isOpen()) {
          list.classList.remove('hidden');
          input.setAttribute('aria-expanded', 'true');
        }

        var step = event.key === 'ArrowDown' ? 1 : -1;
        setActive(activeIndex < 0 ? (step === 1 ? 0 : count - 1) : (activeIndex + step + count) % count);
      } else if (event.key === 'Enter') {
        if (isOpen() && activeIndex >= 0) {
          event.preventDefault();
          choose(activeIndex);
        }
      } else if (event.key === 'Escape') {
        if (isOpen()) {
          event.preventDefault();
          close();
        }
      } else if (event.key === 'Tab') {
        close();
      }
    });

    input.addEventListener('blur', function () {
      close();
    });
  }

  document.querySelectorAll('[data-place-autocomplete]').forEach(init);
}());
