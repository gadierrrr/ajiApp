# Plunk Email Operations Runbook

This runbook covers configuration, rotation, troubleshooting, and production verification for email delivery via Plunk.

## Scope

Email flows using `inc/email.php`:
- Magic link auth email (`magic-link`)
- Welcome email (`welcome`)
- List send email (`list-send`)
- Quiz results email (`quiz-results`)
- Admin test send (`admin_test`)

## Configuration

Set in `.env`:

- `PLUNK_SECRET_KEY=sk_...`
- `PLUNK_PUBLIC_KEY=pk_...`
- `PLUNK_BASE_URL=https://next-api.useplunk.com`
- `PLUNK_WEBHOOK_SECRET=<random hex secret>`
- `EMAIL_PROVIDER=plunk`

Related endpoints:
- Webhook receiver: `/api/webhooks/plunk.php`
- Health probe: `/api/health/email`

## Key Rotation

### Secret key (`PLUNK_SECRET_KEY`)

1. Create a new secret key in Plunk.
2. Update `.env` with the new key.
3. Deploy (`./deploy.sh`).
4. Verify:
   - `curl -sS -i https://www.puertoricobeachfinder.com/api/health/email`
   - Ensure JSON shows `ok: true`.
5. Deactivate old key in Plunk.

### Public key (`PLUNK_PUBLIC_KEY`)

1. Create new public key in Plunk.
2. Update `.env`.
3. Deploy.
4. Verify client tracking events continue in Plunk.
5. Revoke old public key.

### Webhook secret (`PLUNK_WEBHOOK_SECRET`)

1. Generate random secret (example):
   - `openssl rand -hex 32`
2. Update `.env` and Plunk webhook config with the same secret.
3. Deploy.
4. Send test webhook from Plunk and verify `2xx` response.

## Health Checks

### Provider health

```bash
curl -sS -i https://www.puertoricobeachfinder.com/api/health/email
```

Expected:
- HTTP `200`
- JSON `ok: true`
- `checks.api.reachable: true`
- `checks.api.authenticated: true`

### Telemetry spot-check

Use `email_messages` for recent statuses:

```bash
php -r 'require "./bootstrap.php"; require APP_ROOT."/inc/db.php"; echo json_encode(query("SELECT template_slug,category,status,created_at FROM email_messages ORDER BY created_at DESC LIMIT 10"), JSON_PRETTY_PRINT),"\n";'
```

## Production Test Checklist

Run these after deploy:

1. Send-list endpoint:
```bash
curl -sS https://www.puertoricobeachfinder.com/api/send-list.php -X POST \
  --data-urlencode 'email=qa-send-list@example.com' \
  --data-urlencode 'context_type=collection' \
  --data-urlencode 'context_key=best-beaches' \
  --data-urlencode 'filters_query=' \
  --data-urlencode 'page_path=/best-beaches'
```
Expected: `{"success":true}`

2. Send-quiz-results endpoint:
```bash
curl -sS https://www.puertoricobeachfinder.com/api/send-quiz-results.php -X POST \
  --data-urlencode 'email=qa-quiz@example.com' \
  --data-urlencode 'results_token=<valid_token>'
```
Expected: `{"success":true}`

3. Magic-link path:
```bash
php -r 'require "./bootstrap.php"; require APP_ROOT."/inc/auth.php"; echo json_encode(sendMagicLink("qa-magic@example.com")),"\n";'
```
Expected: `{"success":true,...}`

4. Webhook validation:
- Invalid signature should return `401`.
- Valid signature should return `{"success":true}`.

## Troubleshooting

### `Email sending is not configured`

Likely causes:
- `PLUNK_SECRET_KEY` missing/blank
- `EMAIL_PROVIDER` not set to `plunk`
- runtime `.env` not loaded as expected

Checks:
- `php -r 'require "./bootstrap.php"; var_dump(env("PLUNK_SECRET_KEY"), env("EMAIL_PROVIDER"));'`

### `401 Unauthorized` from Plunk

Likely causes:
- Wrong key type for endpoint
- typo/revoked key

Rules:
- `/v1/send` and `/v1/contacts` use `sk_*`
- `/v1/track` uses `pk_*`

### `Could not resolve host: next-api.useplunk.com`

Likely causes:
- server DNS/network egress issue

Checks:
- host DNS resolution
- firewall/egress restrictions
- retry `GET /api/health/email`

### Webhook signature failures

Likely causes:
- `PLUNK_WEBHOOK_SECRET` mismatch between Plunk dashboard and `.env`
- wrong signature header

Action:
- rotate secret in both places and retest

## Data Model Notes

Migration `021-add-email-delivery-tracking.php` adds:
- `email_messages`
- `email_events`
- `email_contacts`

Use these tables for incident triage and delivery audits.
