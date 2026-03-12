# AGENTS.md

## Architecture (1 minute)

This repo is a PHP + SQLite site with **no framework**. The production web server should serve **only** the `public/` directory as the document root.

The app also includes:
- Tailwind CSS plus custom CSS partials and vanilla JS assets
- HTMX-style API endpoints that often return HTML for `HX-Request` and JSON otherwise
- Auth, admin, reviews/check-ins, referrals, collections, guides, locale routing, and email/analytics integrations

Everything outside `public/` is server-side code, source assets, runtime data, or CLI-only utilities.

## Where to find things

- **Web entrypoints:** `public/`
  - Pages: `public/*.php`
  - API endpoints: `public/api/`
  - API subtrees: `public/api/admin/`, `public/api/favorites/`, `public/api/health/`, `public/api/quiz/`, `public/api/reviews/`, `public/api/webhooks/`
  - Admin UI: `public/admin/`
  - Auth handlers: `public/auth/`
  - Error pages: `public/errors/`
  - Guides: `public/guides/`
  - Served assets on disk: `public/assets/` (URL path `/assets/...`)
  - Served images: `public/images/`
- **Core app code:** `inc/`
  - Bootstrapping, DB, helpers, auth, email, referrals, guide CMS, locale/i18n, security/session helpers
- **Reusable UI:** `components/`
  - Collection UI lives in `components/collection/`
  - Legal content partials live in `components/legal/`
- **Templates:** `templates/`
- **CLI scripts:** `scripts/`
  - Content generation helpers live in `scripts/content/`
- **Migrations:** `migrations/` (run via `scripts/migrate.php`)
- **Database and runtime data:** `data/` (never web-served)
- **User uploads:** `uploads/` (kept outside `public/`, typically served by Nginx alias at `/uploads/`)
- **Deploy configs:** `deploy/`
- **Docs:** `docs/`
- **Config and source assets:** `config/`, `assets/`
  - Root `assets/` is source/reference material, not the web-served asset directory
- **Operational/runtime artifacts:** `logs/`, `backups/`
- **Legacy guide backups/reference files:** `guides/` at repo root is not the live public guides directory

## Bootstrap / include conventions (important)

- Repo root has `bootstrap.php` which defines:
  - `APP_ROOT` (repo root absolute path)
  - `PUBLIC_ROOT` (`APP_ROOT . '/public'`)
  - then loads `inc/bootstrap.php`
- **All public PHP entrypoints** should start with:
  - `require_once $_SERVER['DOCUMENT_ROOT'] . '/../bootstrap.php';`
- For cross-directory includes, prefer filesystem paths via `APP_ROOT` / `PUBLIC_ROOT` to avoid fragile relative traversal.
- `__DIR__` is fine for includes within the same subsystem.
- Avoid relative traversal like `../../inc/...` when a stable app-root path is available.
- Shared components such as `components/header.php` own common head/meta/CSS loading. Reuse them instead of hand-rolling duplicate page head setup.

## Routing and URL conventions

- Use clean public URLs in links, nav, sitemap, canonicals, and templates. Avoid linking to `.php` routes directly.
- `public/index.php` acts as the locale-aware front controller for requests that fall through to the homepage entrypoint.
- Non-directory public routes use trailing-slash normalization.
- `public/guides/cms-router.php` resolves CMS-backed guide slugs and falls back to legacy static guide files when needed.
- `public/guides/` is a real public directory; root-level `guides/` is not.

## Common commands

```bash
# Install deps
npm ci

# Build all frontend assets
npm run build

# Build CSS from partials
npm run build:css

# Build Tailwind bundle
npm run build:tailwind

# Minify frontend JavaScript
npm run build:js

# Watch Tailwind during local development
npm run dev

# Design-system lint
node scripts/lint-design-system.js

# Initialize/reset DB (reads .env DB_PATH)
php scripts/init-db.php

# Migrations
php scripts/migrate.php --dry-run
php scripts/migrate.php
php scripts/migrate.php --check
php scripts/migrate.php --baseline
php scripts/migrate.php --include-manual
```

## Local dev (PHP built-in server)

Use the public docroot:

```bash
php -S localhost:8084 -t public scripts/dev-router.php
```

## Agent guardrails

- Never put secrets into `public/` (or any committed file). CI runs secret scanning.
- Treat `data/`, `uploads/`, `logs/`, and `backups/` as runtime or sensitive areas, not normal source directories.
- Edit CSS source in `public/assets/css/partials/`, then rebuild generated assets with `npm run build:css` or `npm run build`.
- When editing frontend behavior, remember `public/assets/` is the served asset tree; root `assets/` is not.
- Prefer existing shared components before creating new inline UI patterns.
- If you touch guides, account for both CMS-backed rendering and legacy static guide fallbacks.
- If you touch localized routes or translated copy, inspect `inc/i18n.php`, `inc/locale_routes.php`, and `inc/lang/`.
- If you touch email or analytics flows, note the existing webhook and health endpoints under `public/api/` plus the runbooks in `docs/`.
- `public/llms-full.txt` can be large; only open it if you specifically need the full content.

## Further reading

- `README.md` for setup, env vars, deploy flow, and health checks
- `docs/codebase-map.md` for a deeper repo navigation map
- `docs/analytics-umami.md` for analytics and event instrumentation
- `docs/email-plunk.md` for email delivery and webhook operations
- `scripts/SYSTEM-ARCHITECTURE.md` for deeper generation/content-system context
