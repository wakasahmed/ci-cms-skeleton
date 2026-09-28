/*
 * Gallery page (ported from the reference design's interactions.js):
 *   - category chips show and hide the server-rendered tiles and keep
 *     ?category= in the address bar;
 *   - a tile opens the visible photos in a PhotoSwipe lightbox. Without
 *     JavaScript (or PhotoSwipe) the tile links to the full-size image.
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

    function setupGallery(root) {
        var chips = root.querySelectorAll('[data-gallery-filter]');
        var items = Array.prototype.slice.call(root.querySelectorAll('[data-gallery-category]'));
        var status = root.querySelector('[data-gallery-status]');

        var visibleItems = function () {
            return items.filter(function (item) {
                return !item.hidden;
            });
        };

        var show = function (category) {
            chips.forEach(function (chip) {
                var active = chip.getAttribute('data-gallery-filter') === category;
                chip.setAttribute('aria-checked', active ? 'true' : 'false');
                toggleClasses(chip, CHIP_ACTIVE, CHIP_INACTIVE, active);

                var count = chip.querySelector('[data-gallery-count]');
                if (count) {
                    toggleClasses(count, COUNT_ACTIVE, COUNT_INACTIVE, active);
                }
            });

            items.forEach(function (item) {
                item.hidden = category !== 'all' && item.getAttribute('data-gallery-category') !== category;
            });

            if (status) {
                status.textContent = visibleItems().length + ' photos shown';
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
                show(chip.getAttribute('data-gallery-filter'));
            });
        });

        root.addEventListener('click', function (event) {
            var link = event.target.closest('[data-gallery-category] > a');
            if (!link || typeof window.PhotoSwipe !== 'function') {
                return;
            }

            event.preventDefault();

            var visible = visibleItems();
            var dataSource = visible.map(function (item) {
                var anchor = item.querySelector('a');
                var image = anchor.querySelector('img');

                return {
                    src: anchor.getAttribute('href'),
                    width: parseInt(anchor.getAttribute('data-pswp-width'), 10) || 1600,
                    height: parseInt(anchor.getAttribute('data-pswp-height'), 10) || 1200,
                    alt: image ? image.alt : ''
                };
            });

            var lightbox = new window.PhotoSwipe({
                dataSource: dataSource,
                index: Math.max(0, visible.indexOf(link.parentElement)),
                bgOpacity: 0.9,
                showHideAnimationType: 'zoom',
                wheelToZoom: true
            });
            lightbox.init();
        });
    }

    $(function () {
        document.querySelectorAll('[data-gallery]').forEach(setupGallery);
    });
})(jQuery);
