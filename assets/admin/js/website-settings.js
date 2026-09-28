(function () {
  'use strict';

  var STORAGE_KEY = 'websiteSettingsExpandedSections_v2';

  document.addEventListener('DOMContentLoaded', function () {
    var root = document.querySelector('.website-settings');
    if (!root || !window.bootstrap) return;

    var form = root.querySelector('[data-website-settings-form]');
    var sections = Array.prototype.slice.call(root.querySelectorAll('[data-website-settings-section]'));
    if (!sections.length) return;

    function panelOf(section) {
      return section.querySelector('.accordion-collapse');
    }

    function collapseInstance(panel) {
      return bootstrap.Collapse.getOrCreateInstance(panel, { toggle: false });
    }

    function readStoredKeys() {
      try {
        var raw = window.localStorage.getItem(STORAGE_KEY);
        var parsed = raw ? JSON.parse(raw) : [];
        return Array.isArray(parsed) ? parsed : [];
      } catch (error) {
        return [];
      }
    }

    function writeStoredKeys() {
      try {
        var open = sections.filter(function (section) {
          var panel = panelOf(section);
          return panel && panel.classList.contains('show');
        }).map(function (section) {
          return section.getAttribute('data-website-settings-section');
        });
        window.localStorage.setItem(STORAGE_KEY, JSON.stringify(open));
      } catch (error) {
        // Storage may be unavailable (private browsing, blocked site data); ignore.
      }
    }

    // Restore the administrator's last expanded sections, but never let a
    // stored preference hide a section the server flagged as having an error.
    var storedKeys = readStoredKeys();
    sections.forEach(function (section) {
      if (section.getAttribute('data-force-open') === 'true') return;
      var panel = panelOf(section);
      if (!panel) return;
      var key = section.getAttribute('data-website-settings-section');
      var shouldOpen = storedKeys.indexOf(key) !== -1;
      var isOpen = panel.classList.contains('show');
      if (shouldOpen !== isOpen) {
        collapseInstance(panel).toggle();
      }
    });

    sections.forEach(function (section) {
      var panel = panelOf(section);
      if (!panel) return;
      panel.addEventListener('shown.bs.collapse', writeStoredKeys);
      panel.addEventListener('hidden.bs.collapse', writeStoredKeys);
    });

    // Same "open the collapsible containing the first missing required
    // field" logic as addPage.php: a required field can be hidden inside a
    // collapsed section, so the browser's own validation message would
    // otherwise appear on a field the administrator cannot see.
    var $ = window.jQuery;
    if (form && window.bootstrap.Collapse) {
      var firstMissingRequiredField = function () {
        return Array.prototype.slice.call(form.querySelectorAll('[required]')).find(function (field) {
          if (field.disabled) return false;
          if (field.type === 'checkbox' || field.type === 'radio') return !field.checked;
          return !String(field.value || '').trim();
        });
      };
      var revealRequiredField = function (field) {
        var panel = field.closest('.accordion-collapse');
        var focusAndValidate = function () {
          if ($ && $.fn && $.fn.validate) $(field).valid();
          field.focus();
        };
        if (panel && !panel.classList.contains('show')) {
          panel.addEventListener('shown.bs.collapse', focusAndValidate, { once: true });
          collapseInstance(panel).show();
        } else {
          focusAndValidate();
        }
      };
      form.addEventListener('submit', function (event) {
        var firstMissingField = firstMissingRequiredField();
        if (!firstMissingField) return;
        event.preventDefault();
        event.stopImmediatePropagation();
        revealRequiredField(firstMissingField);
      }, true);
      form.addEventListener('invalid', function (event) {
        event.preventDefault();
        revealRequiredField(event.target);
      }, true);
    }
  });
}());
