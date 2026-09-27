/**
 * WhatsApp template form helpers: suggest the Meta template name from the
 * title and show a live character count. Short tags are inserted by
 * short-tag-picker.js.
 */
(function () {
  'use strict';

  function initialize() {
    var form = document.querySelector('[data-whatsapp-template-form]');
    if (!form || form.getAttribute('data-whatsapp-template-ready') === 'true') {
      return;
    }
    form.setAttribute('data-whatsapp-template-ready', 'true');

    initializeNameSuggestion(form);
    initializeCharacterCounts(form);
  }

  function metaName(text) {
    return String(text || '')
      .toLowerCase()
      .replace(/[^a-z0-9]+/g, '_')
      .replace(/^[^a-z]+/, '')
      .replace(/_+$/, '')
      .slice(0, 100);
  }

  // Fill the Meta name from the title until the administrator edits it directly.
  function initializeNameSuggestion(form) {
    var name = form.querySelector('[data-name-source]');
    if (!name || name.readOnly) {
      return;
    }

    var source = form.querySelector(name.getAttribute('data-name-source'));
    if (!source) {
      return;
    }

    var followsTitle = name.value === '' || name.value === metaName(source.value);
    source.addEventListener('input', function () {
      if (followsTitle) {
        name.value = metaName(source.value);
      }
    });
    name.addEventListener('input', function () {
      followsTitle = name.value === '';
      name.value = name.value.toLowerCase().replace(/[^a-z0-9_]/g, '_');
    });
  }

  function initializeCharacterCounts(form) {
    form.querySelectorAll('[data-character-count-for]').forEach(function (counter) {
      var field = document.getElementById(counter.getAttribute('data-character-count-for'));
      if (!field) {
        return;
      }

      var update = function () {
        var max = parseInt(field.getAttribute('maxlength'), 10) || 0;
        counter.textContent = field.value.length + (max ? ' / ' + max : '') + ' characters';
      };
      field.addEventListener('input', update);
      update();
    });
  }

  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', initialize);
  } else {
    initialize();
  }
})();
