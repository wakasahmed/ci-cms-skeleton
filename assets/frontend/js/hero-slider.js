/**
 * Alam Al-Munawara — homepage hero background slider.
 *
 * Swiper (vendored at vendor/swiper/) rotates the hero background, and the copy
 * panels above it (eyebrow + heading + paragraph) cross-fade in step. The CTAs,
 * feature strip and availability form stay fixed.
 *
 * The panels are plain stacked divs rather than a second Swiper: one instance
 * stays the single source of truth for which slide is showing, and the fade is
 * a two-line CSS transition.
 *
 * Behaviour: fade, ~5.5s per slide, ~1000ms transition, looped, paused on
 * hover and on keyboard focus inside the hero. Swiper pauses on tab-hide
 * natively. prefers-reduced-motion disables autoplay and transitions entirely.
 *
 * ONE copy of this file serves both locales. Swiper is told the document
 * direction so its pagination bullets run the right way, and the accessibility
 * strings come from window.FrontendI18n (js/main.js). The transition itself is a
 * cross-fade, which has no direction to mirror.
 *
 * window.AlamHeroSlider.init(scope) / destroy(scope) let js/htmx-init.js start
 * the slider on a home page swapped in without a page load, and stop its
 * autoplay timer when that content is swapped out again.
 */
(function () {
  'use strict';

  function init(scope) {
    var root = (scope || document).querySelector('[data-hero-slider]:not([data-hero-ready])');
    if (!root) return;
    root.setAttribute('data-hero-ready', '');

    var el = root.querySelector('.swiper');
    if (!el) return;

    // Swiper is loaded only on this page; fail quietly (first slide still shows)
    if (typeof window.Swiper !== 'function') return;

    var slides = el.querySelectorAll('.swiper-slide');
    if (slides.length < 2) return;

    var reduceMotion = window.matchMedia('(prefers-reduced-motion: reduce)').matches;
    var i18n = window.FrontendI18n;
    var scopeRoot = scope || document;
    var pagination = scopeRoot.querySelector('[data-hero-pagination]');
    var panels = Array.prototype.slice.call(scopeRoot.querySelectorAll('[data-hero-panel]'));

    if (pagination) pagination.classList.remove('hidden');

    // Show the copy belonging to the slide that is now on screen
    function showPanel(index) {
      panels.forEach(function (panel, i) {
        var active = i === index;
        panel.classList.toggle('opacity-0', !active);
        panel.classList.toggle('pointer-events-none', !active);
        if (active) {
          panel.removeAttribute('aria-hidden');
        } else {
          panel.setAttribute('aria-hidden', 'true');
        }
      });
    }

    var options = {
      effect: 'fade',
      fadeEffect: { crossFade: true },
      /* Swiper reads this rather than the document, so the bullet order and the
         internal index follow the page direction. */
      rtl: !!(i18n && i18n.rtl),
      speed: reduceMotion ? 0 : 1000,
      loop: !reduceMotion,
      allowTouchMove: false,          // background art, not a swipeable gallery
      grabCursor: false,
      // NOTE: Swiper's own pauseOnMouseEnter cannot fire here — the slider layer
      // is pointer-events:none so it never steals clicks from the hero content.
      // Hover pausing is wired to the hero <section> below instead.
      autoplay: reduceMotion
        ? false
        : {
            delay: 5500,
            disableOnInteraction: false
          },
      a11y: {
        enabled: true,
        containerRoleDescriptionMessage: i18n ? i18n.t('hero.slider.label') : 'Hero background images',
        slideRole: 'group',
        itemRoleDescriptionMessage: i18n ? i18n.t('hero.slide.label') : 'background image',
        /* Swiper substitutes {{index}} itself, so the placeholder passes
           through t() untouched. */
        paginationBulletMessage: i18n ? i18n.t('hero.bullet.label') : 'Show background image {{index}}'
      }
    };

    if (pagination) {
      options.pagination = {
        el: pagination,
        clickable: true,
        bulletClass: 'hero-bullet',
        bulletActiveClass: 'hero-bullet--active'
      };
    }

    if (panels.length > 1) {
      options.on = {
        // Start the copy fading as the image does, not after it lands
        slideChangeTransitionStart: function () {
          showPanel(this.realIndex);
        }
      };
    }

    var swiper = new window.Swiper(el, options);

    if (reduceMotion || !swiper.autoplay) return;

    var hero = root.closest('section') || root.parentElement;
    if (!hero) return;

    function pause() {
      if (swiper.autoplay && !swiper.autoplay.paused) swiper.autoplay.pause();
    }
    function resume() {
      if (swiper.autoplay && swiper.autoplay.paused) swiper.autoplay.resume();
    }

    // Hold still while someone is reading or interacting with the hero
    hero.addEventListener('mouseenter', pause);
    hero.addEventListener('mouseleave', resume);
    hero.addEventListener('focusin', pause);
    hero.addEventListener('focusout', function (ev) {
      if (hero.contains(ev.relatedTarget)) return;
      resume();
    });
  }

  /* Swiper keeps its instance on the element (el.swiper); destroying it
     clears the autoplay timer a detached slider would otherwise keep. */
  function destroy(scope) {
    Array.prototype.forEach.call(
      (scope || document).querySelectorAll('[data-hero-slider] .swiper'),
      function (el) {
        if (el.swiper && typeof el.swiper.destroy === 'function') {
          el.swiper.destroy(true, false);
        }
      }
    );
  }

  window.AlamHeroSlider = { init: init, destroy: destroy };

  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', function () { init(); });
  } else {
    init();
  }
})();
