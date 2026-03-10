#!/usr/bin/env php
<?php
/**
 * Migration 029: Create shared relational tables for non-beach place types
 * and extend user_favorites with place_type support.
 *
 * Tables: place_tags, place_amenities, place_gallery, place_reviews, place_favorites
 * All use (place_type, place_id) composite key for polymorphic relations.
 */

require_once __DIR__ . '/../inc/db.php';

$db = getDB();

// ── Shared tags (for non-beach types) ───────────────────────────────
$db->exec("CREATE TABLE IF NOT EXISTS place_tags (
    id TEXT PRIMARY KEY,
    place_type TEXT NOT NULL,
    place_id TEXT NOT NULL,
    tag TEXT NOT NULL,
    UNIQUE(place_type, place_id, tag)
)");
$db->exec("CREATE INDEX IF NOT EXISTS idx_place_tags_lookup ON place_tags(place_type, place_id)");
$db->exec("CREATE INDEX IF NOT EXISTS idx_place_tags_tag ON place_tags(tag)");
echo "Created place_tags table\n";

// ── Shared amenities (for non-beach types) ──────────────────────────
$db->exec("CREATE TABLE IF NOT EXISTS place_amenities (
    id TEXT PRIMARY KEY,
    place_type TEXT NOT NULL,
    place_id TEXT NOT NULL,
    amenity TEXT NOT NULL,
    UNIQUE(place_type, place_id, amenity)
)");
$db->exec("CREATE INDEX IF NOT EXISTS idx_place_amenities_lookup ON place_amenities(place_type, place_id)");
echo "Created place_amenities table\n";

// ── Shared gallery ──────────────────────────────────────────────────
$db->exec("CREATE TABLE IF NOT EXISTS place_gallery (
    id TEXT PRIMARY KEY,
    place_type TEXT NOT NULL,
    place_id TEXT NOT NULL,
    image_url TEXT NOT NULL,
    caption TEXT,
    caption_es TEXT,
    position INTEGER DEFAULT 0,
    created_at TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP
)");
$db->exec("CREATE INDEX IF NOT EXISTS idx_place_gallery_lookup ON place_gallery(place_type, place_id)");
echo "Created place_gallery table\n";

// ── Shared reviews ──────────────────────────────────────────────────
$db->exec("CREATE TABLE IF NOT EXISTS place_reviews (
    id TEXT PRIMARY KEY,
    place_type TEXT NOT NULL,
    place_id TEXT NOT NULL,
    user_id TEXT NOT NULL,
    rating INTEGER NOT NULL CHECK (rating >= 1 AND rating <= 5),
    title TEXT,
    review_text TEXT,
    visit_date TEXT,
    would_recommend INTEGER DEFAULT 1,
    helpful_count INTEGER DEFAULT 0,
    status TEXT NOT NULL DEFAULT 'pending',
    created_at TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP
)");
$db->exec("CREATE INDEX IF NOT EXISTS idx_place_reviews_lookup ON place_reviews(place_type, place_id)");
$db->exec("CREATE INDEX IF NOT EXISTS idx_place_reviews_user ON place_reviews(user_id)");
$db->exec("CREATE INDEX IF NOT EXISTS idx_place_reviews_status ON place_reviews(status)");
echo "Created place_reviews table\n";

// ── Shared favorites ────────────────────────────────────────────────
$db->exec("CREATE TABLE IF NOT EXISTS place_favorites (
    id TEXT PRIMARY KEY,
    user_id TEXT NOT NULL,
    place_type TEXT NOT NULL,
    place_id TEXT NOT NULL,
    created_at TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP,
    UNIQUE(user_id, place_type, place_id)
)");
$db->exec("CREATE INDEX IF NOT EXISTS idx_place_favorites_user ON place_favorites(user_id)");
$db->exec("CREATE INDEX IF NOT EXISTS idx_place_favorites_lookup ON place_favorites(place_type, place_id)");
echo "Created place_favorites table\n";

// ── Extend user_favorites with place_type column ────────────────────
// Add place_type column with default 'beach' for backward compatibility.
// SQLite doesn't support ADD COLUMN IF NOT EXISTS, so check first.
$cols = $db->query("PRAGMA table_info(user_favorites)");
$hasPlaceType = false;
if ($cols) {
    while ($col = $cols->fetchArray(SQLITE3_ASSOC)) {
        if ($col['name'] === 'place_type') {
            $hasPlaceType = true;
            break;
        }
    }
}
if (!$hasPlaceType) {
    $db->exec("ALTER TABLE user_favorites ADD COLUMN place_type TEXT NOT NULL DEFAULT 'beach'");
    echo "Added place_type column to user_favorites\n";
} else {
    echo "user_favorites already has place_type column\n";
}

echo "Migration 029 complete.\n";
