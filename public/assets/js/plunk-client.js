/*
 * Plunk client tracking bridge.
 * Uses public key only and never blocks UI flows.
 */
(function () {
  if (window.__bfPlunkClientLoaded) return;
  window.__bfPlunkClientLoaded = true;

  var cfg = window.BF_CONFIG || {};
  var publicKey = cfg.plunkPublicKey || "";
  var baseUrl = (cfg.plunkBaseUrl || "https://next-api.useplunk.com").replace(/\/$/, "");

  function canTrack() {
    return typeof publicKey === "string" && publicKey.length > 0;
  }

  function safeFetch(eventName, props) {
    if (!canTrack()) return;

    var payload = {
      event: String(eventName || "unknown"),
      data: Object.assign(
        {
          source: "web_client",
          path: window.location.pathname,
        },
        props || {}
      ),
    };

    try {
      fetch(baseUrl + "/v1/track", {
        method: "POST",
        headers: {
          Authorization: "Bearer " + publicKey,
          "Content-Type": "application/json",
          Accept: "application/json",
        },
        body: JSON.stringify(payload),
        keepalive: true,
        credentials: "omit",
      }).catch(function () {
        // Ignore tracking failures.
      });
    } catch (err) {
      // Ignore tracking failures.
    }
  }

  var originalTrack = typeof window.bfTrack === "function" ? window.bfTrack : null;

  window.bfTrackClient = function bfTrackClient(eventName, props) {
    safeFetch(eventName, props);
  };

  window.bfTrack = function bfTrack(eventName, props) {
    try {
      if (typeof originalTrack === "function") {
        originalTrack(eventName, props);
      }
    } finally {
      safeFetch(eventName, props);
    }
  };
})();
