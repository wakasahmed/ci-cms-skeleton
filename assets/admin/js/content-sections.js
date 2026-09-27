(function () {
    'use strict';

    document.addEventListener('DOMContentLoaded', function () {
        var form = document.querySelector('[data-content-sections-form]');
        if (!form) return;

        function showSectionAttention(card) {
            if (!card) return;

            card.classList.add('has-error');

            var status = card.querySelector('.admin-form-section-status');
            if (!status) {
                status = document.createElement('span');
                status.className = 'badge text-bg-danger admin-form-section-status';
                status.textContent = 'Needs attention';

                var heading = card.querySelector('.admin-form-section-heading');
                var miscellaneousContext = card.querySelector('.miscellaneous-editor-context');

                if (heading) {
                    heading.insertAdjacentElement('afterend', status);
                } else if (miscellaneousContext) {
                    status.classList.add('miscellaneous-editor-attention');
                    miscellaneousContext.appendChild(status);
                }
            } else {
                status.className = 'badge text-bg-danger admin-form-section-status';
                status.textContent = 'Needs attention';
            }
        }

        function revealField(field) {
            if (!field) return;

            var card = field.closest('[data-section-card]');
            var collapse = card && card.querySelector('.accordion-collapse');
            showSectionAttention(card);

            if (collapse && window.bootstrap) {
                bootstrap.Collapse.getOrCreateInstance(collapse, { toggle: false }).show();
            }

            window.setTimeout(function () {
                field.scrollIntoView({ behavior: 'smooth', block: 'center' });
                field.focus();
            }, 180);
        }

        function isMissingRequiredValue(field) {
            if (field.disabled || !field.required) return false;

            if (field.type === 'checkbox') {
                return !field.checked;
            }

            if (field.type === 'radio') {
                var group = form.elements[field.name];
                if (!group) return true;

                return typeof group.length === 'number'
                    ? !group.value
                    : !group.checked;
            }

            if (field.type === 'file') {
                return !field.files || !field.files.length;
            }

            return !String(field.value || '').trim();
        }

        function firstMissingRequiredField() {
            var fields = form.querySelectorAll('[required]');

            for (var index = 0; index < fields.length; index += 1) {
                if (isMissingRequiredValue(fields[index])) return fields[index];
            }

            return null;
        }

        var firstError = form.dataset.firstError;
        if (firstError) {
            revealField(form.elements[firstError]);
        }

        form.addEventListener('submit', function (event) {
            var missingField = firstMissingRequiredField();
            if (!missingField) return;

            event.preventDefault();
            event.stopImmediatePropagation();
            missingField.classList.add('is-invalid');
            revealField(missingField);
        }, true);

        function clearFieldError(event) {
            var field = event.target;
            if (!field.required || isMissingRequiredValue(field)) return;

            field.classList.remove('is-invalid');
        }

        form.addEventListener('input', clearFieldError);
        form.addEventListener('change', clearFieldError);

        var sortable = form.querySelector('[data-section-sortable]');
        if (!sortable) return;

        var dragging = null;
        var reduceMotion = window.matchMedia('(prefers-reduced-motion: reduce)').matches;

        function cards() {
            return Array.prototype.slice.call(sortable.querySelectorAll('[data-section-card]'));
        }

        function positions() {
            var result = new Map();
            cards().forEach(function (card) { result.set(card, card.getBoundingClientRect()); });
            return result;
        }

        function animateFrom(previousPositions) {
            if (reduceMotion) return;
            cards().forEach(function (card) {
                if (card === dragging || !previousPositions.has(card)) return;
                var previous = previousPositions.get(card);
                var current = card.getBoundingClientRect();
                var deltaY = previous.top - current.top;
                if (!deltaY || typeof card.animate !== 'function') return;
                card.animate([
                    { transform: 'translateY(' + deltaY + 'px)' },
                    { transform: 'translateY(0)' }
                ], { duration: 190, easing: 'cubic-bezier(.2,.8,.2,1)' });
            });
        }

        function clearDropIndicators() {
            sortable.querySelectorAll('.is-drop-before, .is-drop-after').forEach(function (card) {
                card.classList.remove('is-drop-before', 'is-drop-after');
            });
        }

        function cardAfterPointer(pointerY) {
            var candidate = null;
            var closestOffset = Number.NEGATIVE_INFINITY;
            cards().forEach(function (card) {
                if (card === dragging) return;
                var rect = card.getBoundingClientRect();
                var offset = pointerY - rect.top - (rect.height / 2);
                if (offset < 0 && offset > closestOffset) {
                    closestOffset = offset;
                    candidate = card;
                }
            });
            return candidate;
        }

        function showDropIndicator(nextCard) {
            clearDropIndicators();
            if (nextCard) {
                nextCard.classList.add('is-drop-before');
                return;
            }
            var remaining = cards().filter(function (card) { return card !== dragging; });
            if (remaining.length) remaining[remaining.length - 1].classList.add('is-drop-after');
        }

        cards().forEach(function (card) {
            card.addEventListener('dragstart', function (event) {
                if (!event.target.closest('.content-section-drag-handle')) {
                    event.preventDefault();
                    return;
                }
                dragging = card;
                card.classList.add('is-dragging');
                event.dataTransfer.effectAllowed = 'move';
                event.dataTransfer.setData('text/plain', card.dataset.sectionKey || 'section');
            });

            card.addEventListener('dragend', function () {
                card.classList.remove('is-dragging');
                clearDropIndicators();
                dragging = null;
            });
        });

        sortable.addEventListener('dragover', function (event) {
            if (!dragging) return;
            event.preventDefault();
            event.dataTransfer.dropEffect = 'move';

            var nextCard = cardAfterPointer(event.clientY);
            showDropIndicator(nextCard);
            if (nextCard === dragging.nextElementSibling || (!nextCard && dragging === sortable.lastElementChild)) return;

            var previousPositions = positions();
            if (nextCard) sortable.insertBefore(dragging, nextCard);
            else sortable.appendChild(dragging);
            animateFrom(previousPositions);
            form.dataset.dirty = 'true';
        });

        sortable.addEventListener('drop', function (event) {
            if (!dragging) return;
            event.preventDefault();
            clearDropIndicators();
            form.dataset.dirty = 'true';
        });
    });
}());
