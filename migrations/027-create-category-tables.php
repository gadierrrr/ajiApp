#!/usr/bin/env php
<?php
/**
 * Migration 027: Create category tables for rivers, waterfalls, trails,
 * restaurants, and photo_spots.
 *
 * Each table has common columns (id, slug, name, municipality, lat, lng,
 * cover_image, description, ratings, publish_status) plus type-specific columns.
 */

require_once __DIR__ . '/../inc/db.php';

$db = getDB();

// ── Rivers ──────────────────────────────────────────────────────────
$db->exec("CREATE TABLE IF NOT EXISTS rivers (
    id TEXT PRIMARY KEY,
    slug TEXT NOT NULL UNIQUE,
    name TEXT NOT NULL,
    municipality TEXT,
    lat REAL,
    lng REAL,
    cover_image TEXT,
    description TEXT,
    description_es TEXT,
    google_rating REAL,
    google_review_count INTEGER DEFAULT 0,
    place_id TEXT,
    publish_status TEXT NOT NULL DEFAULT 'draft',
    published_at TEXT,
    created_at TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP,
    -- River-specific columns
    water_clarity TEXT,
    current_strength TEXT,
    depth_estimate TEXT,
    swimmable INTEGER DEFAULT 1,
    access_difficulty TEXT DEFAULT 'easy',
    best_season TEXT,
    notes TEXT,
    notes_es TEXT,
    safety_info TEXT,
    local_tips TEXT
)");
echo "Created rivers table\n";

$db->exec("CREATE INDEX IF NOT EXISTS idx_rivers_slug ON rivers(slug)");
$db->exec("CREATE INDEX IF NOT EXISTS idx_rivers_municipality ON rivers(municipality)");
$db->exec("CREATE INDEX IF NOT EXISTS idx_rivers_publish_status ON rivers(publish_status)");

// ── Waterfalls ──────────────────────────────────────────────────────
$db->exec("CREATE TABLE IF NOT EXISTS waterfalls (
    id TEXT PRIMARY KEY,
    slug TEXT NOT NULL UNIQUE,
    name TEXT NOT NULL,
    municipality TEXT,
    lat REAL,
    lng REAL,
    cover_image TEXT,
    description TEXT,
    description_es TEXT,
    google_rating REAL,
    google_review_count INTEGER DEFAULT 0,
    place_id TEXT,
    publish_status TEXT NOT NULL DEFAULT 'draft',
    published_at TEXT,
    created_at TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP,
    -- Waterfall-specific columns
    height_meters REAL,
    num_tiers INTEGER DEFAULT 1,
    swimmable INTEGER DEFAULT 1,
    hike_difficulty TEXT DEFAULT 'easy',
    hike_distance_km REAL,
    hike_time_minutes INTEGER,
    water_clarity TEXT,
    best_season TEXT,
    notes TEXT,
    notes_es TEXT,
    safety_info TEXT,
    local_tips TEXT
)");
echo "Created waterfalls table\n";

$db->exec("CREATE INDEX IF NOT EXISTS idx_waterfalls_slug ON waterfalls(slug)");
$db->exec("CREATE INDEX IF NOT EXISTS idx_waterfalls_municipality ON waterfalls(municipality)");
$db->exec("CREATE INDEX IF NOT EXISTS idx_waterfalls_publish_status ON waterfalls(publish_status)");

// ── Trails ──────────────────────────────────────────────────────────
$db->exec("CREATE TABLE IF NOT EXISTS trails (
    id TEXT PRIMARY KEY,
    slug TEXT NOT NULL UNIQUE,
    name TEXT NOT NULL,
    municipality TEXT,
    lat REAL,
    lng REAL,
    cover_image TEXT,
    description TEXT,
    description_es TEXT,
    google_rating REAL,
    google_review_count INTEGER DEFAULT 0,
    place_id TEXT,
    publish_status TEXT NOT NULL DEFAULT 'draft',
    published_at TEXT,
    created_at TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP,
    -- Trail-specific columns
    difficulty TEXT DEFAULT 'moderate',
    distance_km REAL,
    elevation_gain_m REAL,
    estimated_time_minutes INTEGER,
    trail_type TEXT DEFAULT 'out-and-back',
    surface_type TEXT DEFAULT 'dirt',
    dog_friendly INTEGER DEFAULT 0,
    bike_friendly INTEGER DEFAULT 0,
    shaded INTEGER DEFAULT 0,
    best_season TEXT,
    notes TEXT,
    notes_es TEXT,
    safety_info TEXT,
    local_tips TEXT
)");
echo "Created trails table\n";

$db->exec("CREATE INDEX IF NOT EXISTS idx_trails_slug ON trails(slug)");
$db->exec("CREATE INDEX IF NOT EXISTS idx_trails_municipality ON trails(municipality)");
$db->exec("CREATE INDEX IF NOT EXISTS idx_trails_publish_status ON trails(publish_status)");
$db->exec("CREATE INDEX IF NOT EXISTS idx_trails_difficulty ON trails(difficulty)");

// ── Restaurants ─────────────────────────────────────────────────────
$db->exec("CREATE TABLE IF NOT EXISTS restaurants (
    id TEXT PRIMARY KEY,
    slug TEXT NOT NULL UNIQUE,
    name TEXT NOT NULL,
    municipality TEXT,
    lat REAL,
    lng REAL,
    cover_image TEXT,
    description TEXT,
    description_es TEXT,
    google_rating REAL,
    google_review_count INTEGER DEFAULT 0,
    place_id TEXT,
    publish_status TEXT NOT NULL DEFAULT 'draft',
    published_at TEXT,
    created_at TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP,
    -- Restaurant-specific columns
    cuisine_type TEXT,
    price_range TEXT DEFAULT '$$',
    phone TEXT,
    website TEXT,
    instagram TEXT,
    hours_json TEXT,
    reservations_url TEXT,
    delivery_available INTEGER DEFAULT 0,
    takeout_available INTEGER DEFAULT 0,
    outdoor_seating INTEGER DEFAULT 0,
    notes TEXT,
    notes_es TEXT
)");
echo "Created restaurants table\n";

$db->exec("CREATE INDEX IF NOT EXISTS idx_restaurants_slug ON restaurants(slug)");
$db->exec("CREATE INDEX IF NOT EXISTS idx_restaurants_municipality ON restaurants(municipality)");
$db->exec("CREATE INDEX IF NOT EXISTS idx_restaurants_publish_status ON restaurants(publish_status)");
$db->exec("CREATE INDEX IF NOT EXISTS idx_restaurants_price_range ON restaurants(price_range)");

// ── Photo Spots ─────────────────────────────────────────────────────
$db->exec("CREATE TABLE IF NOT EXISTS photo_spots (
    id TEXT PRIMARY KEY,
    slug TEXT NOT NULL UNIQUE,
    name TEXT NOT NULL,
    municipality TEXT,
    lat REAL,
    lng REAL,
    cover_image TEXT,
    description TEXT,
    description_es TEXT,
    google_rating REAL,
    google_review_count INTEGER DEFAULT 0,
    place_id TEXT,
    publish_status TEXT NOT NULL DEFAULT 'draft',
    published_at TEXT,
    created_at TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP,
    -- Photo spot-specific columns
    best_light TEXT,
    best_time_of_day TEXT,
    tripod_recommended INTEGER DEFAULT 0,
    drone_allowed INTEGER DEFAULT 1,
    accessibility TEXT DEFAULT 'easy',
    viewpoint_type TEXT,
    instagram_hashtag TEXT,
    notes TEXT,
    notes_es TEXT,
    local_tips TEXT
)");
echo "Created photo_spots table\n";

$db->exec("CREATE INDEX IF NOT EXISTS idx_photo_spots_slug ON photo_spots(slug)");
$db->exec("CREATE INDEX IF NOT EXISTS idx_photo_spots_municipality ON photo_spots(municipality)");
$db->exec("CREATE INDEX IF NOT EXISTS idx_photo_spots_publish_status ON photo_spots(publish_status)");

echo "Migration 027 complete.\n";
