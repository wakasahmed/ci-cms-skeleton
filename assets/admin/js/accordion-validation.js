/*
 * Reveals invalid fields inside collapsed Bootstrap accordion panels.
 *
 * Applies to forms marked [data-accordion-validation]. jQuery Validate
 * ignores hidden fields, so a required field inside a closed panel would
 * otherwise be skipped on the client. On submit (or a native "invalid"
 * event) the first missing required field is found, its panel is opened
 * through Bootstrap Collapse, and the field is focused and validated once
 * the panel's "shown" event fires. The Save button is left enabled so the
 * administrator can correct the field and resubmit.
 *
 * Server-side errors are handled in the view: panels that contain invalid
 * fields are rendered open.
 */
(function (window, document, $) {
    'use strict';

    function isMissing(field) {
        if (field.disabled) {
            return false;
        }

        if (field.type === 'checkbox' || field.type === 'radio') {
            return !field.checked;
        }

        if (field.tagName === 'SELECT' && field.multiple) {
            return field.selectedOptions.length === 0;
        }

        return String(field.value || '').trim() === '';
    }

    function focusTarget(field) {
        var $field = $(field);

        if ($field.hasClass('select2') && $field.next('.select2').length) {
            return $field.next('.select2').find('.select2-selection').get(0) || field;
        }

        return field;
    }

    function reveal(field) {
        var panel = field.closest('.accordion-collapse');
        var focusAndValidate = function () {
            if ($.fn.validate) {
                $(field).valid();
            }

            focusTarget(field).focus();
        };

        if (panel && window.bootstrap && window.bootstrap.Collapse && !panel.classList.contains('show')) {
            panel.addEventListener('shown.bs.collapse', focusAndValidate, { once: true });
            window.bootstrap.Collapse.getOrCreateInstance(panel, { toggle: false }).show();

            return;
        }

        focusAndValidate();
    }

    function initialize(form) {
        form.addEventListener('submit', function (event) {
            var fields = Array.prototype.slice.call(form.querySelectorAll('[required]'));
            var missing = fields.find(function (field) {
                return field.closest('.accordion-collapse:not(.show)') && isMissing(field);
            });

            if (!missing) {
                return;
            }

            event.preventDefault();
            event.stopImmediatePropagation();
            reveal(missing);
        }, true);

        form.addEventListener('invalid', function (event) {
            event.preventDefault();
            reveal(event.target);
        }, true);
    }

    $(function () {
        document.querySelectorAll('form[data-accordion-validation]').forEach(initialize);
    });
})(window, document, window.jQuery);
