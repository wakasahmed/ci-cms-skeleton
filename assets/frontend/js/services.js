/*
 * Services listing: category chips filter the server-rendered menu without
 * a reload and keep ?category= in the address bar (ported from the
 * reference design's interactions.js). Without JavaScript the page still
 * honours ?category= on the server.
 */
(function ($) {
    'use strict';

    var ACTIVE = ['bg-primary-cta', 'text-primary-foreground'];
    var INACTIVE = ['border', 'border-border-strong', 'bg-background'];

    function setChip(button, active) {
        button.setAttribute('aria-checked', active ? 'true' : 'false');
        ACTIVE.forEach(function (name) {
            button.classList.toggle(name, active);
        });
        INACTIVE.forEach(function (name) {
            button.classList.toggle(name, !active);
        });
    }

    function setupServiceMenu(root) {
        var buttons = root.querySelectorAll('button[data-service-group]');
        var groups = root.querySelectorAll('[data-service-category]');
        var status = root.querySelector('[data-service-status]');

        var show = function (category) {
            var shown = 0;

            buttons.forEach(function (button) {
                setChip(button, button.getAttribute('data-service-group') === category);
            });

            groups.forEach(function (group) {
                var visible = category === 'all' || group.getAttribute('data-service-category') === category;
                group.hidden = !visible;
                if (visible) {
                    shown += parseInt(group.getAttribute('data-service-count'), 10) || 0;
                }
            });

            if (status) {
                status.textContent = shown + ' services shown';
            }

            var url = new URL(window.location.href);
            if (category === 'all') {
                url.searchParams.delete('category');
            } else {
                url.searchParams.set('category', category);
            }
            window.history.replaceState({}, '', url);
        };

        buttons.forEach(function (button) {
            button.addEventListener('click', function () {
                show(button.getAttribute('data-service-group'));
            });
        });
    }

    $(function () {
        document.querySelectorAll('[data-service-menu]').forEach(setupServiceMenu);
    });
})(jQuery);
