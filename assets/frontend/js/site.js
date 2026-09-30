/*
 * Behaviour shared by every public page (ported from the reference design's
 * site.js):
 *   - reveal blocks are marked visible;
 *   - opening one <details> closes its open siblings (accordion groups);
 *   - forms without a real action do not submit;
 *   - the header gains its "scrolled" style after the first few pixels;
 *   - the mobile menu panel is built from the primary navigation;
 *   - the skip link moves keyboard focus into <main>.
 */
(function ($) {
    'use strict';

    function markRevealed() {
        document.querySelectorAll('.reveal').forEach(function (element) {
            element.setAttribute('data-visible', 'true');
        });
    }

    function setupAccordions() {
        document.querySelectorAll('details').forEach(function (details) {
            details.addEventListener('toggle', function () {
                if (!details.open || !details.parentElement) {
                    return;
                }

                details.parentElement.querySelectorAll('details[open]').forEach(function (other) {
                    if (other !== details) {
                        other.removeAttribute('open');
                    }
                });
            });
        });
    }

    function preventPlaceholderForms() {
        document.querySelectorAll('form').forEach(function (form) {
            form.addEventListener('submit', function (event) {
                var action = form.getAttribute('action') || '';

                if (!action || action === '#') {
                    event.preventDefault();
                }
            });
        });
    }

    function setupHeader() {
        var header = document.querySelector('header');
        if (!header) {
            return;
        }

        var updateHeader = function () {
            header.classList.toggle('ci-scrolled', window.scrollY > 12);
        };

        updateHeader();
        window.addEventListener('scroll', updateHeader, { passive: true });

        setupMobileMenu(header);
    }

    function setupMobileMenu(header) {
        var toggle = header.querySelector('[data-menu-toggle]');
        var primary = header.querySelector('nav[aria-label="Primary"]');
        if (!toggle || !primary) {
            return;
        }

        var menu = document.createElement('nav');
        menu.id = 'mobile-menu';
        menu.className = 'ci-mobile-menu';
        menu.setAttribute('aria-label', 'Mobile navigation');
        menu.innerHTML = primary.innerHTML;
        header.appendChild(menu);

        var isOpen = function () {
            return menu.classList.contains('ci-open');
        };

        var setOpen = function (open) {
            menu.classList.toggle('ci-open', open);
            toggle.setAttribute('aria-expanded', open ? 'true' : 'false');
            toggle.setAttribute('aria-label', open ? 'Close menu' : 'Open menu');
        };

        toggle.addEventListener('click', function () {
            setOpen(!isOpen());
        });

        menu.addEventListener('click', function (event) {
            if (event.target.closest('a')) {
                setOpen(false);
            }
        });

        document.addEventListener('keydown', function (event) {
            if (event.key === 'Escape' && isOpen()) {
                setOpen(false);
                toggle.focus();
            }
        });
    }

    // Following "#main" scrolls but leaves focus on the link in some browsers;
    // focus <main> so the next Tab starts inside the page content.
    function setupSkipLink() {
        document.querySelectorAll('a[href="#main"]').forEach(function (link) {
            link.addEventListener('click', function () {
                var main = document.getElementById('main');
                if (!main) {
                    return;
                }

                if (!main.hasAttribute('tabindex')) {
                    main.setAttribute('tabindex', '-1');
                }
                main.focus();
            });
        });
    }

    $(function () {
        setupSkipLink();
        markRevealed();
        setupAccordions();
        preventPlaceholderForms();
        setupHeader();
    });
})(jQuery);
