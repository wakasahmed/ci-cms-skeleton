/*
 * Gallery page (ported from the reference design's interactions.js): a tile
 * opens the photos left visible by the category chips in a PhotoSwipe
 * lightbox. Without JavaScript (or PhotoSwipe) the tile links to the
 * full-size image.
 */
(function ($) {
    'use strict';

    function setupGallery(root) {
        var items = Array.prototype.slice.call(root.querySelectorAll('[data-filter-item]'));

        // Items hidden by the category chips (js/category-filter.js) are left out.
        var visibleItems = function () {
            return items.filter(function (item) {
                return !item.hidden;
            });
        };

        root.addEventListener('click', function (event) {
            var link = event.target.closest('[data-filter-item] > a');
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
