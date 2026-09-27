(function () {
    'use strict';

    function setPreview(field, value) {
        var icon = field.querySelector('.admin-icon-picker-preview i');
        var clearButton = field.querySelector('.admin-icon-picker-clear');
        icon.className = value || '';
        clearButton.disabled = !value;
    }

    document.addEventListener('DOMContentLoaded', function () {
        var inputs = Array.prototype.slice.call(document.querySelectorAll('[data-icon-picker]'));
        if (!inputs.length) {
            return;
        }

        var sources;
        try {
            sources = JSON.parse(inputs[0].getAttribute('data-icon-sources'));
        } catch (error) {
            return;
        }

        var pickerTrigger = document.createElement('input');
        pickerTrigger.type = 'hidden';
        document.body.appendChild(pickerTrigger);

        var activeInput = null;
        var activeField = null;
        var picker = new IconPicker(pickerTrigger, {
            theme: 'bootstrap-5',
            iconSource: sources,
            closeOnSelect: true,
            i18n: {
                'input:placeholder': 'Search Font Awesome icons…',
                'text:title': 'Choose an icon',
                'text:empty': 'No matching icons found…',
                'btn:save': 'Select'
            }
        });

        function syncPickerSelection() {
            if (!picker.root || !picker.root.content) {
                return;
            }
            picker.root.search.value = '';
            picker.root.content.querySelectorAll('.icon-element').forEach(function (icon) {
                icon.hidden = false;
                icon.classList.toggle('is-selected', !!activeInput && icon.getAttribute('data-value') === activeInput.value);
            });
        }

        function openFor(input) {
            activeInput = input;
            activeField = input.closest('.admin-icon-picker-field');
            picker.currentlySelectName = input.value || null;
            syncPickerSelection();
            picker.show();
        }

        picker.on('select', function (icon) {
            if (!activeInput || !activeField) {
                return;
            }
            activeInput.value = icon.value;
            setPreview(activeField, icon.value);
            syncPickerSelection();
        });

        picker.on('show', syncPickerSelection);

        inputs.forEach(function (input) {
            var field = input.closest('.admin-icon-picker-field');
            input.addEventListener('click', function () {
                openFor(input);
            });
            field.querySelector('.admin-icon-picker-open').addEventListener('click', function () {
                openFor(input);
            });
            field.querySelector('.admin-icon-picker-clear').addEventListener('click', function () {
                input.value = '';
                if (input === activeInput) {
                    picker.clear();
                    syncPickerSelection();
                }
                setPreview(field, '');
            });
            setPreview(field, input.value);
        });
    });
}());
