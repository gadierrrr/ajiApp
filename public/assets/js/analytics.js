/**
 * AJI analytics wrapper (Umami-compatible).
 *
 * Goals:
 * - Never break UX when analytics is blocked/disabled.
 * - Provide a single bfTrack() API for the app.
 * - Provide delegated click/form tracking for key funnel events.
 */

(function () {
  "use strict";
  var umamiUnavailableWarned = false;
  var clientProbeSent = false;

  function getMeta() {
    return window.AJIAppMeta || { authenticated: 0 };
  }

  function getConfig() {
    return window.BF_CONFIG || {};
  }

  function isProdRuntime() {
    return getConfig().appEnv === "prod";
  }

  function postClientProbe(eventName, umamiAvailable) {
    if (clientProbeSent) return;
    clientProbeSent = true;

    var payload = {
      event_name: String(eventName || "unknown"),
      path: window.location.pathname,
      umami_available: !!umamiAvailable,
    };

    try {
      if (navigator.sendBeacon) {
        var blob = new Blob([JSON.stringify(payload)], { type: "application/json" });
        navigator.sendBeacon("/api/health/analytics.php?client_probe=1", blob);
        return;
      }
    } catch (e) {}

    try {
      fetch("/api/health/analytics.php?client_probe=1", {
        method: "POST",
        headers: { "Content-Type": "application/json" },
        body: JSON.stringify(payload),
        keepalive: true,
        credentials: "same-origin",
      }).catch(function () {});
    } catch (e) {}
  }

  function warnUmamiUnavailable(eventName) {
    if (!isProdRuntime() || umamiUnavailableWarned) return;
    umamiUnavailableWarned = true;
    postClientProbe(eventName, false);

    try {
      if (typeof console !== "undefined" && typeof console.warn === "function") {
        console.warn("[analytics] bfTrack called but window.umami is unavailable", {
          event_name: eventName || "",
          path: window.location.pathname,
        });
      }
    } catch (e) {}
  }

  function safeJsonParse(value, fallback) {
    try {
      return JSON.parse(value);
    } catch (e) {
      return fallback;
    }
  }

  function toBool01(value) {
    return value ? 1 : 0;
  }

  function setInteracted() {
    try {
      sessionStorage.setItem("bf_interacted", "1");
    } catch (e) {}
  }

  function hasInteracted() {
    try {
      return sessionStorage.getItem("bf_interacted") === "1";
    } catch (e) {
      return false;
    }
  }

  function uuidLike() {
    if (window.crypto && typeof window.crypto.randomUUID === "function") {
      return window.crypto.randomUUID();
    }
    // RFC4122-ish fallback (not perfect, but stable enough for anon id).
    const bytes = new Uint8Array(16);
    if (window.crypto && typeof window.crypto.getRandomValues === "function") {
      window.crypto.getRandomValues(bytes);
    } else {
      for (let i = 0; i < bytes.length; i++) bytes[i] = Math.floor(Math.random() * 256);
    }
    bytes[6] = (bytes[6] & 0x0f) | 0x40;
    bytes[8] = (bytes[8] & 0x3f) | 0x80;
    const hex = Array.from(bytes).map((b) => b.toString(16).padStart(2, "0")).join("");
    return (
      hex.slice(0, 8) +
      "-" +
      hex.slice(8, 12) +
      "-" +
      hex.slice(12, 16) +
      "-" +
      hex.slice(16, 20) +
      "-" +
      hex.slice(20)
    );
  }

  function getCookie(name) {
    const cookies = document.cookie ? document.cookie.split(";") : [];
    for (let i = 0; i < cookies.length; i++) {
      const part = cookies[i].trim();
      if (part.startsWith(name + "=")) {
        return decodeURIComponent(part.slice(name.length + 1));
      }
    }
    return "";
  }

  function setCookie(name, value, maxAgeSeconds) {
    const secure = window.location.protocol === "https:" ? "; Secure" : "";
    document.cookie =
      name +
      "=" +
      encodeURIComponent(value) +
      "; Path=/" +
      "; Max-Age=" +
      String(maxAgeSeconds) +
      "; SameSite=Lax" +
      secure;
  }

  function ensureAnonId() {
    const existing = getCookie("BF_ANON_ID");
    if (existing) return existing;
    const next = uuidLike();
    // 180 days
    setCookie("BF_ANON_ID", next, 180 * 24 * 60 * 60);
    return next;
  }

  function baseProps(extra) {
    const meta = getMeta();
    const anonId = ensureAnonId();
    return Object.assign(
      {
        auth: toBool01(meta.authenticated === 1 || meta.authenticated === "1"),
        anon_id: anonId,
      },
      extra || {}
    );
  }

  /**
   * Public tracking API.
   * Uses Umami when present; otherwise no-op.
   */
  window.bfTrack = function bfTrack(eventName, props) {
    try {
      if (!eventName) return;
      setInteracted();
      const payload = baseProps(props);
      if (window.umami && typeof window.umami.track === "function") {
        window.umami.track(eventName, payload);
      } else {
        warnUmamiUnavailable(eventName);
      }
    } catch (e) {
      // Never throw from analytics.
    }
  };

  function trackSignupAttribution() {
    const meta = getMeta();
    const authed = meta && (meta.authenticated === 1 || meta.authenticated === "1");
    if (!authed) return;

    const url = new URL(window.location.href);
    const src = (url.searchParams.get("src") || "").trim().toLowerCase();
    if (!src) return;

    if (src === "quiz") {
      window.bfTrack("S1_signup_from_quiz", { source: "quiz" });
    } else if (src === "checkin") {
      window.bfTrack("S2_signup_from_checkin", { source: "checkin" });
    } else {
      return;
    }

    url.searchParams.delete("src");
    window.history.replaceState({}, "", url.toString());
  }

  function beachPropsFromEl(el) {
    const container = el.closest("[data-bf-beach-id]") || el;
    const beachId = container.getAttribute("data-bf-beach-id") || "";
    const beachSlug = container.getAttribute("data-bf-beach-slug") || "";
    const municipality = container.getAttribute("data-bf-municipality") || "";
    const source = container.getAttribute("data-bf-source") || "";
    const props = {};
    if (beachId) props.beach_id = beachId;
    if (beachSlug) props.beach_slug = beachSlug;
    if (municipality) props.municipality = municipality;
    if (source) props.source = source;
    return props;
  }

  function referralPropsFromEl(el) {
    const container = el.closest("[data-bf-referral-campaign]") || el;
    const props = {};

    const provider = container.getAttribute("data-bf-referral-provider") || "";
    const campaign = container.getAttribute("data-bf-referral-campaign") || "";
    const placement = container.getAttribute("data-bf-referral-placement") || "";
    const pageType = container.getAttribute("data-bf-referral-page-type") || "";
    const pageSlug = container.getAttribute("data-bf-referral-page-slug") || "";
    const locale = container.getAttribute("data-bf-referral-locale") || "";
    const block = container.getAttribute("data-bf-referral-block") || "";

    if (provider) props.provider = provider;
    if (campaign) props.campaign = campaign;
    if (placement) props.placement = placement;
    if (pageType) props.page_type = pageType;
    if (pageSlug) props.page_slug = pageSlug;
    if (locale) props.locale = locale;
    if (block) props.block = block;
    return props;
  }

  function initDelegatedClickTracking() {
    document.addEventListener("click", function (event) {
      const target = event.target && event.target.closest ? event.target.closest("[data-bf-track]") : null;
      if (!target) return;

      const kind = (target.getAttribute("data-bf-track") || "").trim();
      const props = beachPropsFromEl(target);

      if (kind === "directions") {
        window.bfTrack("A3_directions_click", props);
        return;
      }

      if (kind === "share") {
        window.bfTrack("share_click", props);
        return;
      }

      if (kind === "details") {
        window.bfTrack("A1_list_to_detail_click", props);
        return;
      }

      if (kind === "referral-click") {
        const referralProps = referralPropsFromEl(target);
        window.bfTrack("R2_referral_click", Object.assign({}, props, referralProps));
        return;
      }
    });
  }

  function initReferralImpressionTracking() {
    if (typeof window.IntersectionObserver !== "function") return;

    const seen = new WeakSet();
    const observer = new IntersectionObserver(
      function (entries) {
        entries.forEach(function (entry) {
          if (!entry.isIntersecting) return;
          const el = entry.target;
          if (seen.has(el)) return;
          seen.add(el);
          observer.unobserve(el);

          const props = referralPropsFromEl(el);
          window.bfTrack("R1_referral_impression", props);
        });
      },
      { threshold: 0.25 }
    );

    function observeCurrentNodes() {
      const nodes = document.querySelectorAll('[data-bf-track="referral-impression"]');
      nodes.forEach(function (node) {
        if (seen.has(node)) return;
        observer.observe(node);
      });
    }

    observeCurrentNodes();
    document.body.addEventListener("htmx:afterSwap", observeCurrentNodes);
  }

  function initHtmxDrawerTracking() {
    document.body.addEventListener("htmx:afterSwap", function (event) {
      const target = event.detail && event.detail.target;
      if (!target || target.id !== "drawer-content-inner") return;

      // beach drawer markup includes these attributes after our patch.
      const beachEl = target.querySelector("[data-bf-beach-id]");
      const props = beachEl ? beachPropsFromEl(beachEl) : {};
      window.bfTrack("A1_list_to_detail_click", Object.assign({ source: props.source || "drawer" }, props));
    });
  }

  function initFavoriteTrackingFromHtmx() {
    document.body.addEventListener("htmx:afterRequest", function (event) {
      const path = event.detail && event.detail.pathInfo && event.detail.pathInfo.requestPath;
      if (!path || String(path).indexOf("toggle-favorite") === -1) return;
      const resp = event.detail && event.detail.xhr && event.detail.xhr.response;
      if (typeof resp !== "string") return;

      // Heuristic: the response HTML includes either ❤️ or 🤍.
      if (resp.indexOf("❤️") !== -1) {
        window.bfTrack("favorite_add", { source: "htmx" });
      } else if (resp.indexOf("🤍") !== -1) {
        window.bfTrack("favorite_remove", { source: "htmx" });
      }
    });
  }

  function initSendListForms() {
    document.addEventListener("submit", async function (event) {
      const form = event.target;
      if (!form || !form.matches || !form.matches('form[data-bf-form="send-list"]')) return;

      event.preventDefault();

      const submitBtn = form.querySelector('button[type="submit"]');
      if (submitBtn) submitBtn.disabled = true;

      try {
        const res = await fetch("/api/send-list.php", {
          method: "POST",
          body: new FormData(form),
        });
        const payload = await res.json();
        if (!res.ok || !payload.success) {
          throw new Error(payload.error || "Unable to send list.");
        }

        if (typeof window.showToast === "function") {
          window.showToast((window.BF_STRINGS || {}).email_subscribed || "Sent! Check your inbox for the list.", "success", 3500);
        }

        const contextType = form.querySelector('input[name="context_type"]')?.value || "";
        const contextKey = form.querySelector('input[name="context_key"]')?.value || "";
        window.bfTrack("L2_list_sent", { context_type: contextType, context_key: contextKey });
        form.reset();
      } catch (e) {
        if (typeof window.showToast === "function") {
          window.showToast((window.BF_STRINGS || {}).email_error || "Could not send the list. Please try again.", "error", 4000);
        }
      } finally {
        if (submitBtn) submitBtn.disabled = false;
      }
    });
  }

  function initWelcomePopupSuppression() {
    // If user already interacted (or analytics fired), suppress timed welcome popup.
    if (!hasInteracted()) return;
    try {
      localStorage.setItem("welcome_popup_dismissed", String(Date.now()));
    } catch (e) {}
  }

  function initSyntheticProbe() {
    var url;
    try {
      url = new URL(window.location.href);
    } catch (e) {
      return;
    }

    if (url.searchParams.get("bf_analytics_probe") !== "1") return;

    window.setTimeout(function () {
      var umamiAvailable = !!(window.umami && typeof window.umami.track === "function");
      postClientProbe("health_analytics_probe", umamiAvailable);
      window.bfTrack("health_analytics_probe", {
        source: "synthetic_probe",
        path: window.location.pathname,
      });
    }, 1200);
  }

  document.addEventListener("DOMContentLoaded", function () {
    ensureAnonId();
    trackSignupAttribution();
    initDelegatedClickTracking();
    initReferralImpressionTracking();
    initHtmxDrawerTracking();
    initFavoriteTrackingFromHtmx();
    initSendListForms();
    initWelcomePopupSuppression();
    initSyntheticProbe();
  });
})();
