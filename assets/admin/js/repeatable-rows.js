/*
 * Repeatable form rows for manage/admin forms.
 *
 * Markup:
 *   <div data-repeatable data-repeatable-max="20" data-repeatable-label="Add-on">
 *     <div data-repeatable-list>
 *       <div data-repeatable-row> ...inputs with name="field[]"...
 *         <button type="button" data-repeatable-remove>Remove</button>
 *       </div>
 *     </div>
 *     <p data-repeatable-empty>Nothing added yet.</p>
 *     <template data-repeatable-template> ...one empty row... </template>
 *     <button type="button" data-repeatable-add>Add</button>
 *   </div>
 *
 * Inputs carry [data-repeatable-aria="label"]; each row's inputs are given
 * an accessible name such as "Add-on 2 label" whenever rows change.
 */
(function (window, document) {
    'use strict';

    function initialize(container) {
        var list = container.querySelector('[data-repeatable-list]');
        var template = container.querySelector('template[data-repeatable-template]');
        var addButton = container.querySelector('[data-repeatable-add]');
        var emptyMessage = container.querySelector('[data-repeatable-empty]');
        var max = parseInt(container.getAttribute('data-repeatable-max'), 10) || 50;
        var rowLabel = container.getAttribute('data-repeatable-label') || 'Row';

        if (!list || !template || !addButton) {
            return;
        }

        function rows() {
            return Array.prototype.slice.call(list.querySelectorAll('[data-repeatable-row]'));
        }

        function refresh() {
            var currentRows = rows();

            currentRows.forEach(function (row, index) {
                var number = index + 1;

                row.querySelectorAll('[data-repeatable-aria]').forEach(function (field) {
                    field.setAttribute('aria-label', rowLabel + ' ' + number + ' ' + field.getAttribute('data-repeatable-aria'));
                });

                row.querySelectorAll('[data-repeatable-remove]').forEach(function (button) {
                    button.setAttribute('aria-label', 'Remove ' + rowLabel.toLowerCase() + ' ' + number);
                });
            });

            addButton.disabled = currentRows.length >= max;
            list.hidden = currentRows.length === 0;

            if (emptyMessage) {
                emptyMessage.hidden = currentRows.length > 0;
            }
        }

        addButton.addEventListener('click', function () {
            if (rows().length >= max) {
                return;
            }

            list.appendChild(template.content.cloneNode(true));
            refresh();

            var newRow = rows().pop();
            var firstField = newRow ? newRow.querySelector('input, select, textarea') : null;

            if (firstField) {
                firstField.focus();
            }
        });

        list.addEventListener('click', function (event) {
            var button = event.target.closest('[data-repeatable-remove]');

            if (!button) {
                return;
            }

            var row = button.closest('[data-repeatable-row]');

            if (row) {
                row.remove();
                refresh();
                addButton.focus();
            }
        });

        refresh();
    }

    document.addEventListener('DOMContentLoaded', function () {
        document.querySelectorAll('[data-repeatable]').forEach(initialize);
    });
})(window, document);
