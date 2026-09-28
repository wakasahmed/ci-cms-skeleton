/*
 * Category chips (partials/category_chips.php) show and hide the
 * server-rendered [data-filter-item] elements inside the same
 * [data-category-filter] container, and keep ?category= in the address bar
 * (ported from the reference design's interactions.js). Used by the gallery
 * and the journal.
 */
(function ($) {
    'use strict';

    var CHIP_ACTIVE = ['bg-primary-cta', 'text-primary-foreground', 'shadow-[var(--shadow-card)]'];
    var CHIP_INACTIVE = ['border', 'border-border-strong', 'bg-background', 'text-foreground-soft',
        'hover:border-primary', 'hover:bg-petal', 'hover:text-primary'];
    var COUNT_ACTIVE = ['text-primary-foreground/75'];
    var COUNT_INACTIVE = ['text-muted-foreground'];

    function toggleClasses(element, on, off, active) {
        on.forEach(function (name) {
            element.classList.toggle(name, active);
        });
        off.forEach(function (name) {
            element.classList.toggle(name, !active);
        });
    }

    function setupFilter(root) {
        var chips = root.querySelectorAll('[data-filter-chip]');
        var items = root.querySelectorAll('[data-filter-item]');
        var status = root.querySelector('[data-filter-status]');

        var show = function (category) {
            var shown = 0;

            chips.forEach(function (chip) {
                var active = chip.getAttribute('data-filter-chip') === category;
                chip.setAttribute('aria-checked', active ? 'true' : 'false');
                toggleClasses(chip, CHIP_ACTIVE, CHIP_INACTIVE, active);

                var count = chip.querySelector('[data-filter-count]');
                if (count) {
                    toggleClasses(count, COUNT_ACTIVE, COUNT_INACTIVE, active);
                }
            });

            items.forEach(function (item) {
                item.hidden = category !== 'all' && item.getAttribute('data-filter-item') !== category;
                if (!item.hidden) {
                    shown++;
                }
            });

            if (status) {
                status.textContent = shown + ' ' + status.getAttribute('data-filter-noun') + ' shown';
            }

            var url = new URL(window.location.href);
            if (category === 'all') {
                url.searchParams.delete('category');
            } else {
                url.searchParams.set('category', category);
            }
            window.history.replaceState({}, '', url);
        };

        chips.forEach(function (chip) {
            chip.addEventListener('click', function () {
                show(chip.getAttribute('data-filter-chip'));
            });
        });
    }

    $(function () {
        document.querySelectorAll('[data-category-filter]').forEach(setupFilter);
    });
})(jQuery);
