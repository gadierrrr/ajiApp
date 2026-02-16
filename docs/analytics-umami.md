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

## Client wrapper

- `public/assets/js/analytics.js` defines `window.bfTrack(eventName, props)`.
- If Umami is available, events are forwarded via `window.umami.track(eventName, props)`.
- A persistent anonymous id cookie `BF_ANON_ID` is created (180 days) and included in event props, plus `authenticated` and `user_id` when available.
- `public/assets/js/plunk-client.js` wraps `window.bfTrack()` and forwards events to Plunk `/v1/track` using `PLUNK_PUBLIC_KEY`.
- Runtime Plunk config is exposed from `components/footer.php` in `window.BF_CONFIG`.

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
- Global user meta for analytics: `components/footer.php`
- Tracking wrapper + delegated listeners: `public/assets/js/analytics.js`
- Plunk client event forwarding: `public/assets/js/plunk-client.js`
- Share tracking: `public/assets/js/share.js`
- Quiz results landing + tokenized page: `public/quiz-results.php`

## Notes

- Tokenized quiz results pages (`/quiz-results?token=...`) are `noindex` to avoid indexing user-specific pages.
- The canonical `/quiz-results` route exists as a landing URL and is included in `public/sitemap.php`.
