/*
 * Home page hero carousel (ported from the reference design's
 * interactions.js). The slides' images and copy are rendered on the server;
 * Swiper fades between the images and this script shows the matching copy
 * and updates the dots. Without JavaScript the first slide stays visible.
 */
(function ($) {
    'use strict';

    function setupHero(hero) {
        var frame = hero.querySelector('[data-hero-frame]');
        var images = frame ? Array.prototype.slice.call(frame.querySelectorAll('img.slide')) : [];
        if (images.length < 2 || typeof window.Swiper !== 'function') {
            return;
        }

        var copies = hero.querySelectorAll('[data-hero-copy]');
        var dots = Array.prototype.slice.call(hero.querySelectorAll('[data-hero-dot]'));
        var wrapper = document.createElement('div');
        wrapper.className = 'swiper-wrapper';

        images.forEach(function (image) {
            image.classList.remove('absolute', 'inset-0', 'opacity-100', 'opacity-0', 'scale-[1.04]');
            image.classList.add('swiper-slide');
            image.style.position = 'relative';
            image.style.width = '100%';
            image.style.height = '100%';
            wrapper.appendChild(image);
        });
        frame.classList.add('swiper');
        frame.appendChild(wrapper);

        var update = function (index) {
            images.forEach(function (image, imageIndex) {
                image.setAttribute('aria-hidden', imageIndex === index ? 'false' : 'true');
            });
            copies.forEach(function (copy, copyIndex) {
                copy.hidden = copyIndex !== index;
            });
            dots.forEach(function (dot, dotIndex) {
                var active = dotIndex === index;
                var bar = dot.querySelector('span');

                dot.setAttribute('aria-current', active ? 'true' : 'false');
                bar.classList.toggle('w-8', active);
                bar.classList.toggle('bg-accent', active);
                bar.classList.toggle('w-3', !active);
                bar.classList.toggle('bg-rose-300', !active);
                bar.classList.toggle('group-hover:bg-secondary', !active);
            });
        };

        var reduceMotion = window.matchMedia && window.matchMedia('(prefers-reduced-motion: reduce)').matches;
        // rewind rather than loop: Swiper's loop mode needs more slides than a
        // hero usually has, and slideToLoop() does nothing with three.
        var slider = new window.Swiper(frame, {
            rewind: true,
            effect: 'fade',
            speed: reduceMotion ? 0 : 850,
            autoplay: reduceMotion ? false : { delay: 6000, disableOnInteraction: false, pauseOnMouseEnter: true },
            keyboard: { enabled: true },
            a11y: { enabled: true },
            on: {
                activeIndexChange: function (instance) {
                    update(instance.activeIndex);
                }
            }
        });

        var previous = hero.querySelector('[data-hero-prev]');
        var next = hero.querySelector('[data-hero-next]');
        if (previous) {
            previous.addEventListener('click', function () {
                slider.slidePrev();
            });
        }
        if (next) {
            next.addEventListener('click', function () {
                slider.slideNext();
            });
        }
        dots.forEach(function (dot, index) {
            dot.addEventListener('click', function () {
                slider.slideTo(index);
            });
        });

        if (!reduceMotion && slider.autoplay) {
            // Pause while the keyboard is inside the carousel or the tab is hidden.
            hero.addEventListener('focusin', function () {
                slider.autoplay.stop();
            });
            hero.addEventListener('focusout', function (event) {
                if (!hero.contains(event.relatedTarget)) {
                    slider.autoplay.start();
                }
            });
            document.addEventListener('visibilitychange', function () {
                if (document.hidden) {
                    slider.autoplay.stop();
                } else {
                    slider.autoplay.start();
                }
            });
        }

        update(0);
    }

    $(function () {
        document.querySelectorAll('[data-hero]').forEach(setupHero);
    });
})(jQuery);
