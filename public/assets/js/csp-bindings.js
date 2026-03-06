/**
 * CSP Event Delegation
 *
 * Replaces inline event handlers (onclick, onchange, etc.) with a single
 * delegated listener system.  Elements declare their intent via data attributes:
 *
 *   data-action="functionName"          – the global function to call
 *   data-action-args='["arg1","arg2"]'  – optional JSON array of arguments
 *   data-action-stop                    – call event.stopPropagation()
 *   data-action-prevent                 – call event.preventDefault()
 *   data-action-confirm="message"       – show confirm() before proceeding
 *
 * For events other than click, use data-on="change" (or submit, input, keydown …).
 * Default event is "click".
 *
 * For keydown filtering: data-action-keys="Enter, " (comma-separated list of
 * event.key values that should trigger the action).
 */
(function () {
  'use strict';

  function handle(event) {
    // Walk from the event target up to find the nearest [data-action] element.
    var el = event.target;
    while (el && el !== document) {
      if (el.hasAttribute && el.hasAttribute('data-action')) {
        break;
      }
      el = el.parentElement;
    }
    if (!el || el === document) return;

    // Check that the declared event type matches (default: click).
    // Supports comma-separated values: data-on="click,keydown"
    var declaredEvents = (el.getAttribute('data-on') || 'click').toLowerCase().split(',');
    for (var d = 0; d < declaredEvents.length; d++) declaredEvents[d] = declaredEvents[d].trim();
    if (declaredEvents.indexOf(event.type) === -1) return;

    // Key filtering for keydown/keyup
    if ((event.type === 'keydown' || event.type === 'keyup') && el.hasAttribute('data-action-keys')) {
      var allowedKeys = el.getAttribute('data-action-keys').split(',').map(function (k) { return k.trim(); });
      if (allowedKeys.indexOf(event.key) === -1) return;
    }

    // Stop propagation / prevent default
    if (el.hasAttribute('data-action-stop')) event.stopPropagation();
    if (el.hasAttribute('data-action-prevent')) event.preventDefault();

    // Confirm dialog — always preventDefault first so canceling doesn't
    // let the native action proceed (e.g. form submit, link navigation).
    if (el.hasAttribute('data-action-confirm')) {
      event.preventDefault();
      if (!confirm(el.getAttribute('data-action-confirm'))) return;
    }

    var fnName = el.getAttribute('data-action');
    if (!fnName) return;

    // Resolve the function (supports dotted names like "window.foo")
    var fn = window;
    var parts = fnName.split('.');
    for (var i = 0; i < parts.length; i++) {
      fn = fn[parts[i]];
      if (!fn) return;
    }
    if (typeof fn !== 'function') return;

    // Parse arguments
    var args = [];
    var argsAttr = el.getAttribute('data-action-args');
    if (argsAttr) {
      try {
        args = JSON.parse(argsAttr);
        if (!Array.isArray(args)) args = [args];
      } catch (e) {
        args = [argsAttr];
      }
    }

    // For certain patterns, pass the event or element as context
    // "event" as a special arg placeholder is replaced with the real event
    for (var j = 0; j < args.length; j++) {
      if (args[j] === '__event__') args[j] = event;
      if (args[j] === '__this__') args[j] = el;
    }

    // --- Inline arg transformations for functions whose call-site changed ---
    // setLanguage: data-action passes (lang, element) but function expects (lang, url)
    if (fnName === 'setLanguage' && args.length >= 2 && args[1] && typeof args[1] === 'object' && args[1].dataset) {
      args[1] = args[1].dataset.targetUrl || '';
    }
    // filterBeaches: data-action passes element but function expects query string
    if (fnName === 'filterBeaches' && args.length >= 1 && args[0] && typeof args[0] === 'object' && 'value' in args[0]) {
      args[0] = args[0].value;
    }

    fn.apply(null, args);
  }

  // --- Helper functions required by data-action conversions ---

  // No-op: used for data-action-stop-only handlers
  window.noop = function () {};

  // Replace location.reload() inline handlers
  window.reloadPage = function () { location.reload(); };

  // Submit a form found by its DOM id (used with data-action-confirm)
  window.submitFormById = function (id) {
    var form = document.getElementById(id);
    if (form && typeof form.submit === 'function') form.submit();
  };

  // Submit the nearest parent <form> of the triggering element
  window.submitParentForm = function (el) {
    var form = el && el.closest ? el.closest('form') : null;
    if (form && typeof form.submit === 'function') form.submit();
  };

  // --- Handle data-fallback-src on images (replaces onerror handlers) ---
  function initFallbackImages() {
    var imgs = document.querySelectorAll('img[data-fallback-src]');
    for (var k = 0; k < imgs.length; k++) {
      (function (img) {
        img.addEventListener('error', function () {
          var fallback = img.getAttribute('data-fallback-src');
          if (fallback && img.src !== fallback) {
            img.src = fallback;
          }
        }, { once: true });
      })(imgs[k]);
    }
  }

  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', initFallbackImages);
  } else {
    initFallbackImages();
  }

  // Also observe DOM mutations so dynamically inserted images get handled
  if (typeof MutationObserver !== 'undefined') {
    new MutationObserver(function (mutations) {
      for (var m = 0; m < mutations.length; m++) {
        var added = mutations[m].addedNodes;
        for (var n = 0; n < added.length; n++) {
          var node = added[n];
          if (node.nodeType !== 1) continue;
          if (node.tagName === 'IMG' && node.hasAttribute('data-fallback-src')) {
            (function (img) {
              img.addEventListener('error', function () {
                var fb = img.getAttribute('data-fallback-src');
                if (fb && img.src !== fb) img.src = fb;
              }, { once: true });
            })(node);
          }
          // Check children
          var childImgs = node.querySelectorAll ? node.querySelectorAll('img[data-fallback-src]') : [];
          for (var ci = 0; ci < childImgs.length; ci++) {
            (function (img) {
              img.addEventListener('error', function () {
                var fb = img.getAttribute('data-fallback-src');
                if (fb && img.src !== fb) img.src = fb;
              }, { once: true });
            })(childImgs[ci]);
          }
        }
      }
    }).observe(document.documentElement, { childList: true, subtree: true });
  }

  // Delegate all interactive events from document
  var events = ['click', 'change', 'submit', 'input', 'keydown', 'keyup'];
  events.forEach(function (evt) {
    document.addEventListener(evt, handle, false);
  });

  // Handle the special case of onload for <link rel="preload" as="style">
  // These cannot use data-action because onload fires before the DOM is interactive.
  // Instead, we observe [data-lazy-style] links and swap rel on load.
  function initLazyStyles() {
    var links = document.querySelectorAll('link[data-lazy-style]');
    for (var k = 0; k < links.length; k++) {
      (function(link) {
        if (link.rel === 'stylesheet') return; // already loaded
        link.onload = function () {
          this.onload = null;
          this.rel = 'stylesheet';
        };
        // If already loaded (cached), swap immediately
        if (link.sheet) {
          link.rel = 'stylesheet';
        }
      })(links[k]);
    }
  }

  // Run immediately and also after DOM ready
  initLazyStyles();
  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', initLazyStyles);
  }

  // Handle Lucide icon loading - watch for the script and init icons when ready
  function initLucideWhenReady() {
    if (window.lucideLoaded || (typeof lucide !== 'undefined')) {
      try { lucide.createIcons(); } catch(e) {}
      return;
    }
    // Set up a small poll for lucide loading (the defer script may load any time)
    var attempts = 0;
    var timer = setInterval(function() {
      attempts++;
      if (typeof lucide !== 'undefined') {
        clearInterval(timer);
        window.lucideLoaded = true;
        lucide.createIcons();
      } else if (attempts > 100) {
        clearInterval(timer);
      }
    }, 100);
  }

  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', initLucideWhenReady);
  } else {
    initLucideWhenReady();
  }
})();
