# Analytics Integration (Umami + Plunk)

This repo uses Umami (Cloud or self-hosted) for product analytics, with a thin client wrapper that is safe when analytics is disabled or blocked.
It also forwards client tracking events to Plunk via a public-key bridge.

## Configuration

Set these in `.env` (see `.env.example`):

- `UMAMI_ENABLED=1`
- `UMAMI_SCRIPT_URL=https://cloud.umami.is/script.js`
- `UMAMI_WEBSITE_ID=...`
- `UMAMI_DOMAINS=puertoricobeachfinder.com,www.puertoricobeachfinder.com` (optional)

The script tag is injected in `components/header.php` only when `UMAMI_ENABLED=1` and `UMAMI_WEBSITE_ID` is non-empty.
`inc/security_headers.php` also extends CSP allowlists using `UMAMI_SCRIPT_URL` host when Umami is enabled.

## Client wrapper

- `public/assets/js/analytics.js` defines `window.bfTrack(eventName, props)`.
- If Umami is available, events are forwarded via `window.umami.track(eventName, props)`.
- A persistent anonymous id cookie `BF_ANON_ID` is created (180 days) and included in event props, plus `authenticated` and `user_id` when available.
- `public/assets/js/plunk-client.js` wraps `window.bfTrack()` and forwards events to Plunk `/v1/track` using `PLUNK_PUBLIC_KEY`.
- Runtime Plunk config is exposed from `components/footer.php` in `window.BF_CONFIG`.
- In `prod`, `bfTrack()` logs a one-time console warning when Umami is unavailable.
- Add `?bf_analytics_probe=1` to any page URL to fire `health_analytics_probe` and send a client probe beacon to `/api/health/analytics.php`.

## Funnel event map (minimal schema)

Activation:

- `A1_list_to_detail_click`: fired when the beach drawer swaps in (HTMX) after a list "Details" click.
- `A2_quiz_complete`: fired after quiz match results are returned/rendered.
- `A3_directions_click`: fired from directions links marked with `data-bf-track="directions"`.

Lead capture:

- `L1_results_sent`: fired when quiz results are sent (email/SMS/WhatsApp flow).
- `L2_list_sent`: fired when a list page capture form is submitted.

Signup attribution:

- `S1_signup_from_quiz`: fired on first authenticated page view when URL contains `?src=quiz`.
- `S2_signup_from_checkin`: fired on first authenticated page view when URL contains `?src=checkin`.

UGC:

- `U1_checkin_submitted`: fired after a check-in is successfully submitted.

Other utility events (implementation-specific):

- `share_click`, `share_copy_link` from `public/assets/js/share.js`
- `favorite_add`, `favorite_remove` (favorites toggles)

## Implementation references

- Umami script injection: `components/header.php`
- Dynamic CSP host allowlist: `inc/security_headers.php`
- Global user meta for analytics: `components/footer.php`
- Tracking wrapper + delegated listeners: `public/assets/js/analytics.js`
- Plunk client event forwarding: `public/assets/js/plunk-client.js`
- Share tracking: `public/assets/js/share.js`
- Quiz results landing + tokenized page: `public/quiz-results.php`
- Analytics health endpoint: `public/api/health/analytics.php`
- CI/deploy tag check: `scripts/check-analytics-umami.php`
- Synthetic browser smoke script: `scripts/synthetic-analytics-probe.sh`

## Operational checks

Configuration + page probe:

```bash
curl -sS "https://www.puertoricobeachfinder.com/api/health/analytics.php?page_probe=1&network_probe=1"
```

Expected in production:
- `ok: true`
- `checks.config.enabled: true`
- `checks.page_probe.umami_tag_present: true`
- `checks.page_probe.umami_website_id_present: true`

Rendered HTML guardrail:

```bash
php scripts/check-analytics-umami.php \
  --urls=https://www.puertoricobeachfinder.com/,https://www.puertoricobeachfinder.com/best-beaches \
  --expect-script-host=cloud.umami.is
```

Synthetic browser probe (headless):

```bash
scripts/synthetic-analytics-probe.sh https://www.puertoricobeachfinder.com
```

This loads a page with `?bf_analytics_probe=1`, then verifies `/api/health/analytics.php?page_probe=1` reports a fresh client probe and Umami availability.

## Notes

- Tokenized quiz results pages (`/quiz-results?token=...`) are `noindex` to avoid indexing user-specific pages.
- The canonical `/quiz-results` route exists as a landing URL and is included in `public/sitemap.php`.
