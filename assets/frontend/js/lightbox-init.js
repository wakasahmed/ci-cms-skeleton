/**
 * GLightbox wiring for the tour detail page: the "Places & highlights"
 * attraction popup and the photo gallery both open through the same
 * vendored library (assets/frontend/vendor/glightbox), so there is one
 * smooth, well-tested open/close animation instead of two hand-rolled ones.
 *
 * Loaded only on pages that opt in via $config['scripts'] — see
 * Frontend::prepareTourExperienceConfig() in application/controllers/Frontend.php.
 *
 * window.AlamLightbox.init(scope) / destroy() let js/htmx-init.js wire a tour
 * page swapped in without a page load, and release the previous page's
 * GLightbox instances when it is swapped out.
 */
(function () {
  'use strict';

  if (typeof GLightbox !== 'function') return;

  var instances = [];

  /* -----------------------------------------------------------------------
   * Attraction detail popup
   *
   * Cards carry only data-attraction="<slug>"; the copy comes from the JSON
   * island printed by attraction_data() (inc/components.php), so nothing
   * long is duplicated in the markup. All attractions are loaded into one
   * GLightbox instance up front (same pattern as the photo gallery below),
   * so a visitor can step through every "Places & highlights" entry with
   * prev/next instead of closing the popup and reopening a different card.
   * touchNavigation is off: dragging to read a long description would
   * otherwise be read as a swipe to the next slide.
   * -------------------------------------------------------------------- */
  function initAttractionLightbox(scope) {
    var root = scope || document;
    var triggers = Array.prototype.slice.call(root.querySelectorAll('[data-attraction]:not([data-lightbox-ready])'));
    var source = root.querySelector('[data-attraction-data]');
    if (!triggers.length || !source) return;
    triggers.forEach(function (trigger) { trigger.setAttribute('data-lightbox-ready', ''); });

    var data;
    try {
      data = JSON.parse(source.textContent);
    } catch (err) {
      return;
    }

    function buildSlide(item) {
      var wrap = document.createElement('div');
      wrap.className = 'attraction-lightbox';

      /* Fixed header: a plain shrink-0 sibling of .attraction-lightbox-content
         (the flex-1 scrolling area) — see tailwind.css for why this popup
         does its own flex/scroll layout instead of GLightbox's. */
      var header = document.createElement('div');
      header.className = 'attraction-lightbox-header';
      var heading = document.createElement('h2');
      heading.className = 'attraction-lightbox-title';
      heading.textContent = item.title;
      header.appendChild(heading);
      wrap.appendChild(header);

      var content = document.createElement('div');
      content.className = 'attraction-lightbox-content';

      var img = document.createElement('img');
      img.className = 'attraction-lightbox-img';
      img.src = item.image;
      img.alt = item.alt || '';
      img.width = 1200;
      img.height = 800;
      img.decoding = 'async';
      content.appendChild(img);

      var body = document.createElement('div');
      body.className = 'attraction-lightbox-body page-contents';
      /* Attraction descriptions are trusted, administrator-authored CKEditor
         content, matching the other managed HTML fields on the frontend. */
      body.innerHTML = (item.details || []).join('');
      if (window.AlamPageContents) window.AlamPageContents.wrapTables(body);
      content.appendChild(body);

      wrap.appendChild(content);

      return wrap;
    }

    var elements = triggers.map(function (trigger) {
      return { content: buildSlide(data[trigger.getAttribute('data-attraction')]), type: 'inline' };
    });

    /* GLightbox's global default is width:'900px', height:'506px' — for an
       inline slide it writes that straight onto .gslide-media as an inline
       style, a fixed box fighting .attraction-lightbox's own fluid,
       content-driven sizing (max-h-[85vh], variable-length CKEditor body).
       'auto' for both leaves sizing entirely to our own CSS. */
    var lightbox = GLightbox({
      elements: elements,
      loop: true,
      touchNavigation: false,
      width: 'auto',
      height: 'auto'
    });
    instances.push(lightbox);

    triggers.forEach(function (trigger, index) {
      trigger.addEventListener('click', function () {
        lightbox.openAt(index);
      });
    });
  }

  /* -----------------------------------------------------------------------
   * Photo gallery — a real GLightbox image group, so visitors can step
   * between photos with prev/next instead of closing and reopening.
   *
   * The grid tile is a fixed 600x600 centre-cropped thumbnail; the lightbox
   * opens data-gallery-full instead — a larger, width-capped, uncropped
   * version (see prepareTourGallery() in Frontend.php) — so the full photo
   * is visible, not a cropped square blown up past its own resolution.
   * -------------------------------------------------------------------- */
  function initGalleryLightbox(scope) {
    var gallery = (scope || document).querySelector('[data-gallery]:not([data-lightbox-ready])');
    if (!gallery) return;
    gallery.setAttribute('data-lightbox-ready', '');

    var items = Array.prototype.slice.call(gallery.querySelectorAll('[data-gallery-item]'));
    if (!items.length) return;

    var elements = items.map(function (btn) {
      var img = btn.querySelector('img');
      var full = btn.getAttribute('data-gallery-full');
      return {
        href: full || (img ? (img.currentSrc || img.src) : ''),
        type: 'image',
        alt: img ? img.alt : ''
      };
    });

    var lightbox = GLightbox({ elements: elements, loop: true });
    instances.push(lightbox);

    items.forEach(function (btn, index) {
      btn.addEventListener('click', function () {
        lightbox.openAt(index);
      });
    });
  }

  function init(scope) {
    initAttractionLightbox(scope);
    initGalleryLightbox(scope);
  }

  function destroy() {
    instances.forEach(function (lightbox) {
      if (lightbox && typeof lightbox.destroy === 'function') lightbox.destroy();
    });
    instances = [];
  }

  window.AlamLightbox = { init: init, destroy: destroy };

  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', function () { init(); });
  } else {
    init();
  }
})();
