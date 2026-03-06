# Puerto Rico Beach Finder

A PHP + SQLite web application for exploring beaches in Puerto Rico.

## Local setup

1. Copy environment template and configure values:

```bash
cp .env.example .env
```

2. Install frontend dependencies:

```bash
npm ci
```

3. Build frontend assets:

```bash
npm run build
```

4. Initialize database (first run only):

```bash
php scripts/init-db.php
```

5. Run migrations:

```bash
php scripts/migrate.php
```

## Local development

Run with the built-in PHP server using the `public/` docroot:

```bash
php -S localhost:8082 -t public scripts/dev-router.php
```

## Required environment variables

Defined in `.env.example`:

- `DB_PATH`
- `APP_URL`
- `APP_NAME`
- `GOOGLE_MAPS_API_KEY`
- `GOOGLE_CLIENT_ID`
- `GOOGLE_CLIENT_SECRET`
- `PLUNK_SECRET_KEY`
- `PLUNK_PUBLIC_KEY`
- `PLUNK_BASE_URL`
- `PLUNK_WEBHOOK_SECRET`
- `PLUNK_WEBHOOK_EXPECT_ENV` (optional: `dev`, `staging`, `prod`)
- `EMAIL_PROVIDER` (`plunk`)
- `ANTHROPIC_API_KEY`
- `REFERRAL_ALLOWED_HOSTS`
- `BACKUP_DIR` (default: `./backups/db`)
- `BACKUP_KEEP_DAYS` (default: `30`)
- `APP_ENV` (`dev`, `staging`, `prod`)
- `APP_DEBUG` (`0` or `1`)
- Umami analytics (optional):
  - `UMAMI_ENABLED` (`0` or `1`)
  - `UMAMI_SCRIPT_URL` (default: `https://cloud.umami.is/script.js`)
  - `UMAMI_WEBSITE_ID`
  - `UMAMI_DOMAINS` (optional)

## Funnel + analytics notes

This codebase includes a lightweight funnel implementation and Umami-compatible client tracking:

- Quiz:
  - `/quiz` returns a `results_token` from `public/api/quiz/match.php` and can generate a shareable URL.
  - `/quiz-results?token=...` renders stored quiz matches (tokenized pages are `noindex`).
- Lead capture:
  - List pages can post to `public/api/send-list.php` ("Send me this list").
  - Quiz results can post to `public/api/send-quiz-results.php` ("Send my matches").
  - Email delivery uses Plunk API keys.
  - Webhook receiver: `public/api/webhooks/plunk.php`
  - Set a per-environment webhook URL such as `/api/webhooks/plunk.php?env=prod` in production and `/api/webhooks/plunk.php?env=staging` in staging.
  - Use a distinct `PLUNK_WEBHOOK_SECRET` for each environment and set `PLUNK_WEBHOOK_EXPECT_ENV` to the matching environment tag.
  - Email health probe: `public/api/health/email.php`
- Tracking:
  - `public/assets/js/analytics.js` defines `window.bfTrack()` and forwards events to Umami when available.
  - `public/assets/js/plunk-client.js` bridges `window.bfTrack()` events to Plunk `/v1/track` using `PLUNK_PUBLIC_KEY`.
  - Event naming follows the funnel schema (A1/A2/A3, L1/L2, S1/S2, U1...).
  - See `docs/analytics-umami.md` for the event map and implementation details.

## Email health check

Use the email provider probe endpoint to validate Plunk config/connectivity:

```bash
curl -sS -i https://www.puertoricobeachfinder.com/api/health/email
```

Expected:
- `200` when keys are configured and Plunk is reachable/authenticated
- `503` when config/auth/connectivity is unhealthy

Operational runbook:
- `docs/email-plunk.md`

## Analytics health checks

Use the analytics probe endpoint to validate Umami config and rendered script injection:

```bash
curl -sS "https://www.puertoricobeachfinder.com/api/health/analytics.php?page_probe=1&network_probe=1"
```

Guardrail check for rendered HTML:

```bash
php scripts/check-analytics-umami.php --url=https://www.puertoricobeachfinder.com
```

Synthetic browser probe (headless Chrome/Chromium required):

```bash
scripts/synthetic-analytics-probe.sh https://www.puertoricobeachfinder.com
```

Operational runbook:
- `docs/analytics-umami.md`

## Migration commands

```bash
# List pending automatic migrations
php scripts/migrate.php --dry-run

# Apply pending automatic migrations
php scripts/migrate.php

# Fail if pending migrations exist
php scripts/migrate.php --check

# One-time baseline for existing DBs
php scripts/migrate.php --baseline

# Include manual/data migrations (default excludes manual set)
php scripts/migrate.php --include-manual
```

## Deploy command

Use the unified deploy script:

```bash
./deploy.sh
```

It runs:

1. PHP syntax lint
2. `npm ci`
3. `npm run build`
4. generated asset consistency check
5. migrations (`php scripts/migrate.php`)
6. smoke checks (migration check + secret grep)

## Rollback notes

1. Back up DB before deploy:

```bash
php scripts/backup-db.php
```

2. If deploy fails after migration:

- Roll back application code to previous commit.
- Restore DB backup.
- Re-run `php scripts/migrate.php --check`.

3. If migration runner was newly adopted on an existing DB:

- Use `php scripts/migrate.php --baseline` once to mark already-applied migrations.

## Backup automation

Create a verified SQLite backup:

```bash
php scripts/backup-db.php
```

Run a restore smoke test against the latest backup:

```bash
php scripts/restore-smoke-test.php
```

Suggested daily automation:

```bash
0 3 * * * cd /var/www/beach-finder && php scripts/backup-db.php >> logs/backup-db.log 2>&1
20 3 * * * cd /var/www/beach-finder && php scripts/restore-smoke-test.php >> logs/restore-smoke.log 2>&1
```

## Security operations

- Secret scanning is enforced in CI and pre-commit (`gitleaks`).
- Google key rotation checklist is documented in `docs/google-key-rotation.md`.
- See `docs/secret-history-cleanup.md` for history rewrite runbook if secrets were previously committed.
- Nginx hardening template lives at `deploy/nginx/beach-finder.conf`.
