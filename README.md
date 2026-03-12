# AJI - Explore Puerto Rico

Discover beaches, rivers, waterfalls, trails, restaurants, and photo spots across Puerto Rico. AJI is a bilingual (EN/ES) web app that helps locals and travelers find the best outdoor experiences on the island.

## Features

- **Multi-category discovery** — Browse 468+ beaches, rivers, waterfalls, trails, restaurants, and photo spots
- **Interactive maps** — Explore places with Google Maps integration and geolocation
- **Smart filters** — Filter by activity, amenity, municipality, and conditions
- **Personalization quiz** — Get matched to places based on your preferences
- **Favorites & lists** — Save places and email curated lists to yourself
- **Bilingual** — Full English and Spanish support
- **PWA-ready** — Installable with offline support
- **Dark mode** — Automatic and manual theme switching

## Tech Stack

- **Backend:** PHP 8.x (no framework)
- **Database:** SQLite3 with WAL mode
- **Frontend:** HTMX + vanilla JavaScript
- **CSS:** Tailwind CSS 3.x + custom design tokens
- **Auth:** Google OAuth + magic links
- **Email:** Plunk transactional email
- **Analytics:** Umami (privacy-friendly)
- **Icons:** Lucide

## Getting Started

```bash
# 1. Clone and configure
cp .env.example .env
# Edit .env with your API keys

# 2. Install dependencies
npm ci

# 3. Build assets
npm run build

# 4. Initialize database
php scripts/init-db.php

# 5. Run migrations
php scripts/migrate.php

# 6. Start dev server
php -S localhost:8084 -t public scripts/dev-router.php
```

## Environment Variables

See `.env.example` for the full list. Key variables:

| Variable | Purpose |
|----------|---------|
| `DB_PATH` | SQLite database path |
| `APP_URL` | Base URL for the app |
| `GOOGLE_MAPS_API_KEY` | Google Maps JavaScript API |
| `GOOGLE_CLIENT_ID/SECRET` | Google OAuth credentials |
| `PLUNK_SECRET_KEY` | Transactional email via Plunk |
| `UMAMI_WEBSITE_ID` | Analytics tracking (optional) |

## Project Structure

```
public/          # Web document root (Nginx serves this)
  api/           # JSON/HTML API endpoints (HTMX)
  admin/         # Admin panel
  assets/        # CSS, JS, images, icons
  guides/        # Editorial guide pages
components/      # Reusable PHP UI components
inc/             # Core includes (db, helpers, auth, i18n)
data/            # SQLite database
migrations/      # Database migrations
scripts/         # CLI tools and build scripts
```

## Development

```bash
# Watch Tailwind during development
npm run dev

# Build all assets (CSS + JS)
npm run build

# Run database migrations
php scripts/migrate.php

# Deploy (lint + build + migrate + smoke tests)
./deploy.sh
```

## License

All rights reserved.
