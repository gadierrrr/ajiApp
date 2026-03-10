#!/usr/bin/env php
<?php
/**
 * Migration 028: Create the `places` SQL VIEW.
 *
 * UNION ALL across all category tables, selecting only common columns
 * plus a `place_type` discriminator. Enables cross-category search and map display.
 */

require_once __DIR__ . '/../inc/db.php';

$db = getDB();

$db->exec("DROP VIEW IF EXISTS places");

$db->exec("CREATE VIEW places AS
    SELECT
        id, slug, name, municipality, lat, lng, cover_image,
        description, description_es,
        google_rating, google_review_count, place_id,
        publish_status, published_at, created_at, updated_at,
        'beach' AS place_type
    FROM beaches
    UNION ALL
    SELECT
        id, slug, name, municipality, lat, lng, cover_image,
        description, description_es,
        google_rating, google_review_count, place_id,
        publish_status, published_at, created_at, updated_at,
        'river' AS place_type
    FROM rivers
    UNION ALL
    SELECT
        id, slug, name, municipality, lat, lng, cover_image,
        description, description_es,
        google_rating, google_review_count, place_id,
        publish_status, published_at, created_at, updated_at,
        'waterfall' AS place_type
    FROM waterfalls
    UNION ALL
    SELECT
        id, slug, name, municipality, lat, lng, cover_image,
        description, description_es,
        google_rating, google_review_count, place_id,
        publish_status, published_at, created_at, updated_at,
        'trail' AS place_type
    FROM trails
    UNION ALL
    SELECT
        id, slug, name, municipality, lat, lng, cover_image,
        description, description_es,
        google_rating, google_review_count, place_id,
        publish_status, published_at, created_at, updated_at,
        'restaurant' AS place_type
    FROM restaurants
    UNION ALL
    SELECT
        id, slug, name, municipality, lat, lng, cover_image,
        description, description_es,
        google_rating, google_review_count, place_id,
        publish_status, published_at, created_at, updated_at,
        'photo_spot' AS place_type
    FROM photo_spots
");

echo "Created places VIEW (UNION ALL of 6 category tables)\n";
echo "Migration 028 complete.\n";
