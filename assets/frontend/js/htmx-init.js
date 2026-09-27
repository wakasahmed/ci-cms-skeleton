/**
 * Alam Al-Munawara — HTMX partial navigation (progressive enhancement).
 *
 * Loaded only on pages that set $config['htmx'] (see page_scripts() in
 * views/frontend/functions.php), after vendor/htmx and before vendor/alpinejs.
 *
 * How it works: links opt in with hx-boost — menu links between the pages
 * listed in htmx_navigation_pages() (views/frontend/functions.php), tour and
 * experience card links, and the Blog's category bar, pills, pagination and
 * post links. <body> carries the shared hx-target/hx-select settings. Every
 * boosted link keeps its real href, so JavaScript-off, middle-click and
 * ctrl-click all behave as before. The server returns the SAME full page it
 * returns to a normal visit — no controller branches on HX-Request — and HTMX
 * swaps only <main id="main"> out of it. This file then:
 *
 *   - swaps only into a page whose <body> carries data-partial-navigation;
 *     any other response (booking, contact, an error page …) is loaded as a
 *     normal page;
 *   - loads, before the swap, any of the new page's own plugin files this
 *     page does not have yet (the home page's slider and date picker, the
 *     tour page's lightbox). Only files from this site's /assets/ are loaded
 *     this way; a page needing anything else (reCAPTCHA, a payment form) is
 *     loaded as a normal page;
 *   - keeps the JSON data blocks printed inside <main> (availability search,
 *     attraction popups, FAQ structured data) where they are: HTMX would
 *     otherwise drop every <script> from swapped content;
 *   - stops the outgoing page's plugins (slider autoplay, date picker
 *     calendars, lightboxes, Select2) and starts the new page's, in the same
 *     order a full page load runs them;
 *   - falls back to a normal page load on any non-2xx response, so the
 *     server's own error page is what the visitor sees;
 *   - copies the new page's SEO metadata (description, robots, canonical,
 *     hreflang, Open Graph, Twitter, JSON-LD), the header menu's active
 *     state, the body class and the language switcher's links into the
 *     current document, so the header and <head> always describe the URL in
 *     the address bar.
 *
 * Browser Back/Forward reload the real URL from the server (history cache
 * size 0 + refreshOnHistoryMiss), rather than restoring a snapshot whose
 * <head> and header links would belong to another page.
 */
(function () {
  'use strict';

  if (!window.htmx) return;

  var htmx = window.htmx;

  htmx.config.historyCacheSize = 0;
  htmx.config.refreshOnHistoryMiss = true;
  // Indicator styles live in the Tailwind build (css/src/tailwind.css).
  htmx.config.includeIndicatorStyles = false;
  // Executable scripts inside swapped content never run: page scripts are
  // loaded as files (see loadAssets()), and must never run twice. JSON data
  // blocks are carried through the swap separately (protectDataScripts()).
  htmx.config.allowScriptTags = false;
  htmx.config.allowEval = false;
  htmx.config.selfRequestsOnly = true;

  /* Everything in <head> that describes the page rather than the site. The
     title is handled by HTMX itself. */
  var HEAD_METADATA = [
    'meta[name="description"]',
    'meta[name="keywords"]',
    'meta[name="robots"]',
    'meta[name="author"]',
    'link[rel="canonical"]',
    'link[rel="alternate"][hreflang]',
    'meta[property^="og:"]',
    'meta[property^="article:"]',
    'meta[name^="twitter:"]',
    'script[type="application/ld+json"]'
  ].join(',');

  // footer.php marks the progress bar when CodeIgniter runs in development.
  var progress = document.getElementById('page-progress');
  var isDevelopment = !!(progress && progress.hasAttribute('data-debug'));

  function logError(message, detail) {
    if (isDevelopment && window.console) {
      window.console.error('[htmx] ' + message, detail || '');
    }
  }

  /** Only requests made by boosted links are partial page navigations. */
  function isPageNavigation(detail) {
    return !!(detail && detail.boosted);
  }

  function requestUrl(detail) {
    var path = detail && detail.requestConfig ? detail.requestConfig.path : '';
    return path || location.href;
  }

  function responseUrl(detail) {
    return (detail && detail.xhr && detail.xhr.responseURL) || requestUrl(detail);
  }

  /** Leave HTMX and let the browser load the URL itself. */
  function fullNavigation(url) {
    window.location.assign(url);
  }

  /* -------------------------------------------------------------------
   * Page assets
   * ---------------------------------------------------------------- */

  /* An asset's identity without the ?v= cache-buster asset() appends, so the
     same file compares equal however recently it was edited. */
  function assetKey(url) {
    return String(url || '').split('?')[0];
  }

  function assetNodes(doc) {
    return Array.prototype.slice.call(
      doc.querySelectorAll('link[rel="stylesheet"][href], script[src]')
    );
  }

  function assetUrl(node) {
    return node.getAttribute('src') || node.getAttribute('href') || '';
  }

  /** Only this site's own files under /assets/ may be loaded on demand. */
  function isOwnAsset(url) {
    try {
      var parsed = new URL(url, location.href);
      return parsed.origin === location.origin && parsed.pathname.indexOf('/assets/') !== -1;
    } catch (err) {
      return false;
    }
  }

  /** The new page's stylesheets and scripts this page has not loaded, in order. */
  function missingAssets(newDoc) {
    var current = {};
    assetNodes(document).forEach(function (node) {
      current[assetKey(assetUrl(node))] = true;
    });

    var missing = { styles: [], scripts: [], foreign: false };
    assetNodes(newDoc).forEach(function (node) {
      var url = assetUrl(node);
      if (current[assetKey(url)]) return;

      if (!isOwnAsset(url)) {
        missing.foreign = true;
      } else if (node.tagName === 'LINK') {
        missing.styles.push(url);
      } else {
        missing.scripts.push(url);
      }
    });

    return missing;
  }

  function loaded(node) {
    return new Promise(function (resolve, reject) {
      node.addEventListener('load', resolve);
      node.addEventListener('error', reject);
    });
  }

  /* Stylesheets go before css/app.css, as header.php prints them, so the
     site's own rules still win. Scripts are inserted with async = false, which
     makes the browser run them in document order — vendor library, locale
     bundle, then the initialiser — exactly as the page's defer tags would. */
  function loadAssets(missing) {
    var waits = [];
    var appCss = null;

    Array.prototype.forEach.call(document.querySelectorAll('link[rel="stylesheet"]'), function (link) {
      if (assetKey(link.getAttribute('href')).slice(-12) === '/css/app.css') appCss = link;
    });

    missing.styles.forEach(function (url) {
      var link = document.createElement('link');
      link.rel = 'stylesheet';
      link.href = url;
      waits.push(loaded(link));
      document.head.insertBefore(link, appCss);
    });

    missing.scripts.forEach(function (url) {
      var script = document.createElement('script');
      script.src = url;
      script.async = false;
      waits.push(loaded(script));
      document.body.appendChild(script);
    });

    return Promise.all(waits);
  }

  /* -------------------------------------------------------------------
   * JSON data blocks inside <main>
   *
   * With allowScriptTags off, HTMX removes every <script> from swapped
   * content. The non-executable ones are data the page's scripts read in
   * place (the availability search's options inside its form, the
   * attraction popups' copy, FAQ structured data), so before the swap each
   * is parked inside an inert <template> at its own position, and put back
   * after it.
   * ---------------------------------------------------------------- */
  function isDataScript(script) {
    var type = (script.getAttribute('type') || '').toLowerCase();
    return type !== '' && type !== 'text/javascript' && type !== 'module';
  }

  function protectDataScripts(doc) {
    var main = doc.getElementById('main');
    if (!main) return false;

    var scripts = Array.prototype.filter.call(main.querySelectorAll('script'), isDataScript);
    scripts.forEach(function (script) {
      var holder = doc.createElement('template');
      holder.setAttribute('data-inert-script', '');
      script.parentNode.replaceChild(holder, script);
      holder.content.appendChild(script);
    });

    return scripts.length > 0;
  }

  function restoreDataScripts(root) {
    Array.prototype.forEach.call(root.querySelectorAll('template[data-inert-script]'), function (holder) {
      var script = holder.content.firstElementChild;
      if (script) {
        holder.parentNode.replaceChild(document.importNode(script, true), holder);
      } else {
        holder.parentNode.removeChild(holder);
      }
    });
  }

  /* -------------------------------------------------------------------
   * Document chrome outside <main>
   * ---------------------------------------------------------------- */

  function syncHeadMetadata(newDoc) {
    var oldNodes = Array.prototype.slice.call(document.head.querySelectorAll(HEAD_METADATA));
    var newNodes = Array.prototype.slice.call(newDoc.head.querySelectorAll(HEAD_METADATA));
    var anchor = oldNodes.length ? oldNodes[0] : null;

    newNodes.forEach(function (node) {
      var copy = document.importNode(node, true);
      if (anchor) {
        document.head.insertBefore(copy, anchor);
      } else {
        document.head.appendChild(copy);
      }
    });

    oldNodes.forEach(function (node) {
      node.parentNode.removeChild(node);
    });
  }

  /* The switcher's other-locale option is a real link to THIS page in that
     locale, so it has to follow the page. Only hrefs change; the switcher's
     own elements and listeners stay in place. */
  function syncLanguageLinks(newDoc) {
    var incoming = {};

    Array.prototype.forEach.call(
      newDoc.querySelectorAll('a[data-lang-option][data-locale]'),
      function (link) {
        incoming[link.getAttribute('data-locale')] = link.getAttribute('href');
      }
    );

    Array.prototype.forEach.call(
      document.querySelectorAll('a[data-lang-option][data-locale]'),
      function (link) {
        var href = incoming[link.getAttribute('data-locale')];
        if (href) {
          link.setAttribute('href', href);
        }
      }
    );
  }

  /* The menus' current-page highlight (class + aria-current) is rendered by
     PHP per page. The header is not swapped, so its links take those two
     attributes from the same links in the new page; the elements and their
     listeners stay in place. */
  function syncHeaderNavigation(newDoc) {
    var selector = '[data-site-header] nav a';
    var oldLinks = document.querySelectorAll(selector);
    var newLinks = newDoc.querySelectorAll(selector);
    if (oldLinks.length !== newLinks.length) return;

    Array.prototype.forEach.call(oldLinks, function (link, i) {
      link.className = newLinks[i].className;
      if (newLinks[i].hasAttribute('aria-current')) {
        link.setAttribute('aria-current', newLinks[i].getAttribute('aria-current'));
      } else {
        link.removeAttribute('aria-current');
      }
    });
  }

  /* A link in the open phone menu: close the menu through main.js's own
     toggle, which also restores the page scroll it locked. */
  function closeMobileMenu() {
    var toggle = document.querySelector('[data-menu-toggle]');
    if (toggle && toggle.getAttribute('aria-expanded') === 'true') {
      toggle.click();
    }
  }

  function setLoading(loading) {
    document.documentElement.classList.toggle('is-page-loading', loading);
    // HTMX clears its own indicator class when the response arrives; this
    // one keeps the bar running while a page's plugin files still load.
    if (progress) progress.classList.toggle('is-loading', loading);

    var main = document.getElementById('main');
    if (main) {
      if (loading) {
        main.setAttribute('aria-busy', 'true');
      } else {
        main.removeAttribute('aria-busy');
      }
    }
  }

  /* The new page's title goes in BEFORE its URL is pushed. HTMX itself sets
     the title only after the swap, but analytics tags that log a page view on
     a history change (GA4 Enhanced Measurement, GTM's History Change trigger,
     the Meta Pixel) read document.title at the moment of the push — they
     would otherwise record the new URL under the previous page's title. */
  function applyTitle(doc) {
    if (doc && doc.title) {
      document.title = doc.title;
    }
  }

  /* The new <main> takes focus without scrolling, so keyboard and
     screen-reader users start at the new content — as they would after a full
     page load — instead of on a link that no longer exists. */
  function focusMain() {
    var main = document.getElementById('main');
    if (!main) return;

    if (!main.hasAttribute('tabindex')) {
      main.setAttribute('tabindex', '-1');
    }

    try {
      main.focus({ preventScroll: true });
    } catch (err) {
      main.focus();
    }
  }

  /* -------------------------------------------------------------------
   * Page widgets
   * ---------------------------------------------------------------- */

  function call(namespace, method, root) {
    var api = window[namespace];
    if (api && typeof api[method] === 'function') api[method](root);
  }

  /* Plugins that outlive their markup: Swiper's autoplay timer, Flatpickr's
     calendars (appended to <body>), GLightbox's instances and any open Select2
     dropdown. Each is stopped before its content is replaced. */
  function teardownRegion(root) {
    if (!root) return;

    call('AlamHeroSlider', 'destroy', root);
    call('FrontendDatepicker', 'destroyAll', root);
    call('AlamLightbox', 'destroy', root);

    var $ = window.jQuery;
    if ($ && $.fn && $.fn.select2) {
      $(root).find('select').each(function () {
        if ($(this).data('select2')) $(this).select2('destroy');
      });
    }
  }

  /* Same order as a full page load runs the page's scripts: main.js, then
     Select2 (js/select2-init.js) and Flatpickr (js/datepicker.js), then the
     page's own scripts. Each skips anything it has already set up. */
  function initRegion(root) {
    call('AlamFrontend', 'initRegion', root);
    call('FrontendSelect2', 'enhanceAll', root);
    call('FrontendDatepicker', 'enhanceAll', root);
    call('AlamHeroSlider', 'init', root);
    call('AlamHeroSearch', 'init', root);
    call('AlamLightbox', 'init', root);
  }

  /* -------------------------------------------------------------------
   * Navigation lifecycle
   * ---------------------------------------------------------------- */

  /* The response document for the swap in progress. Parsed once in
     beforeSwap and reused in afterSwap. */
  var pendingDoc = null;

  /* Incremented by every navigation, so a page whose files are still loading
     is never swapped in over one the visitor has clicked since. */
  var navigationId = 0;
  var loadingAssets = false;

  document.addEventListener('htmx:beforeRequest', function (event) {
    if (!isPageNavigation(event.detail)) return;
    navigationId++;
    closeMobileMenu();
    setLoading(true);
  });

  document.addEventListener('htmx:afterRequest', function (event) {
    if (!isPageNavigation(event.detail) || loadingAssets) return;
    setLoading(false);
  });

  /* Swap a page whose plugin files had to be loaded first. HTMX's own swap
     has already been declined; this performs the same outerHTML swap of <main>
     through htmx.swap(), pushing the URL as HTMX would have. */
  function swapAfterAssets(detail, response, doc, missing) {
    var id = navigationId;
    var url = responseUrl(detail);

    loadingAssets = true;
    setLoading(true);

    loadAssets(missing).then(function () {
      loadingAssets = false;
      if (id !== navigationId) return;

      pendingDoc = doc;
      teardownRegion(document.getElementById('main'));
      htmx.swap('#main', response, {
        swapStyle: 'outerHTML',
        swapDelay: 0,
        settleDelay: 20
      }, {
        select: '#main',
        eventInfo: detail,
        contextElement: document.body,
        beforeSwapCallback: function () {
          applyTitle(doc);
          history.pushState({ htmx: true }, '', url);
        }
      });
      setLoading(false);
    }, function () {
      loadingAssets = false;
      logError('Could not load page assets', url);
      if (id === navigationId) fullNavigation(url);
    });
  }

  document.addEventListener('htmx:beforeSwap', function (event) {
    var detail = event.detail;
    if (!isPageNavigation(detail)) return;

    // A 4xx/5xx is not swapped by HTMX; the responseError handler below
    // takes it from here.
    if (!detail.shouldSwap) return;

    var doc = new DOMParser().parseFromString(detail.xhr.responseText, 'text/html');
    var missing = missingAssets(doc);

    if (
      !doc.getElementById('main')
      || !doc.body.hasAttribute('data-partial-navigation')
      || missing.foreign
    ) {
      detail.shouldSwap = false;
      fullNavigation(responseUrl(detail));
      return;
    }

    var response = protectDataScripts(doc)
      ? '<!DOCTYPE html>' + doc.documentElement.outerHTML
      : detail.serverResponse;

    if (missing.styles.length || missing.scripts.length) {
      detail.shouldSwap = false;
      swapAfterAssets(detail, response, doc, missing);
      return;
    }

    detail.serverResponse = response;
    pendingDoc = doc;
    teardownRegion(document.getElementById('main'));
  });

  // Fired by HTMX immediately before it pushes the URL of a normal swap.
  document.addEventListener('htmx:beforeHistoryUpdate', function (event) {
    if (!isPageNavigation(event.detail)) return;
    applyTitle(pendingDoc);
  });

  document.addEventListener('htmx:afterSwap', function (event) {
    if (!isPageNavigation(event.detail)) return;

    var main = document.getElementById('main');

    if (pendingDoc) {
      syncHeadMetadata(pendingDoc);
      syncLanguageLinks(pendingDoc);
      syncHeaderNavigation(pendingDoc);
      document.body.className = pendingDoc.body.className;
      pendingDoc = null;
    }

    if (main) restoreDataScripts(main);
    initRegion(main || document);

    /* A new page starts at the top, as a full page load would.
       Done here rather than with hx-swap's show:window:top, which scrolls
       body into view and lands a few pixels short under the site's smooth
       scroll-behavior. 'instant' overrides that CSS for this one jump. */
    window.scrollTo({ top: 0, left: 0, behavior: 'instant' });
  });

  /* After settling, the new title and URL are both in place: the point at
     which a page view hook (analytics) should run. */
  document.addEventListener('htmx:afterSettle', function (event) {
    if (!isPageNavigation(event.detail)) return;

    focusMain();
    document.dispatchEvent(new CustomEvent('alam-page-navigated', {
      detail: { url: location.href, title: document.title }
    }));
  });

  /* Server errors: show the server's own error page for that URL. */
  document.addEventListener('htmx:responseError', function (event) {
    if (!isPageNavigation(event.detail)) return;

    setLoading(false);
    logError('Response ' + event.detail.xhr.status, requestUrl(event.detail));
    fullNavigation(requestUrl(event.detail));
  });

  /* Network failures: nothing to fall back to, so keep the current page as it
     is and tell the visitor (the Alpine notice in footer.php). */
  document.addEventListener('htmx:sendError', function (event) {
    if (!isPageNavigation(event.detail)) return;

    setLoading(false);
    logError('Network error', requestUrl(event.detail));
    window.dispatchEvent(new CustomEvent('alam-navigation-error', {
      detail: { url: requestUrl(event.detail) }
    }));
  });

  document.addEventListener('htmx:timeout', function (event) {
    if (!isPageNavigation(event.detail)) return;

    setLoading(false);
    window.dispatchEvent(new CustomEvent('alam-navigation-error', {
      detail: { url: requestUrl(event.detail) }
    }));
  });

  /* Alpine component for the navigation-error notice. Registered before
     vendor/alpinejs starts (it loads after this file). Its copy is printed by
     PHP; Alpine only holds whether it is open and which URL to retry. */
  document.addEventListener('alpine:init', function () {
    window.Alpine.data('navigationNotice', function () {
      return {
        open: false,
        url: '',

        show: function (url) {
          this.url = url || location.href;
          this.open = true;
        },

        dismiss: function () {
          this.open = false;
        }
      };
    });
  });
})();
