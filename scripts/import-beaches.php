<?php
/**
 * Beach Data Import Script
 * Imports beaches from the Next.js app's JSON data
 *
 * Usage: php scripts/import-beaches.php
 */

require_once __DIR__ . '/../inc/db.php';

$jsonPath = '/home/deploy/prtd/data/beaches.json';

echo "AJI - Data Import\n";
echo "==========================\n\n";

// Check source file
if (!file_exists($jsonPath)) {
    die("Error: Source file not found: {$jsonPath}\n");
}

echo "Reading beaches from: {$jsonPath}\n";
$json = file_get_contents($jsonPath);
$beaches = json_decode($json, true);

if (!$beaches || !is_array($beaches)) {
    die("Error: Failed to parse JSON data\n");
}

echo "Found " . count($beaches) . " beaches to import\n\n";

$db = getDB();
$now = date('Y-m-d H:i:s');

// Counters
$imported = 0;
$skipped = 0;
$errors = 0;

// Start transaction
$db->exec('BEGIN TRANSACTION');

try {
    foreach ($beaches as $beach) {
        // Check if beach already exists
        $existing = queryOne('SELECT id FROM beaches WHERE id = :id', [':id' => $beach['id']]);
        if ($existing) {
            $skipped++;
            continue;
        }

        // Extract coordinates
        $lat = $beach['coords']['lat'] ?? 0;
        $lng = $beach['coords']['lng'] ?? 0;

        // Insert main beach record
        $stmt = $db->prepare('INSERT INTO beaches (
            id, slug, name, municipality, lat, lng,
            sargassum, surf, wind,
            cover_image, access_label, notes,
            description, parking_details, safety_info, local_tips, best_time,
            place_id, google_rating, google_review_count,
            publish_status, created_at, updated_at
        ) VALUES (
            :id, :slug, :name, :municipality, :lat, :lng,
            :sargassum, :surf, :wind,
            :cover_image, :access_label, :notes,
            :description, :parking_details, :safety_info, :local_tips, :best_time,
            :place_id, :google_rating, :google_review_count,
            :publish_status, :created_at, :updated_at
        )');

        $stmt->bindValue(':id', $beach['id']);
        $stmt->bindValue(':slug', $beach['slug']);
        $stmt->bindValue(':name', $beach['name']);
        $stmt->bindValue(':municipality', $beach['municipality']);
        $stmt->bindValue(':lat', $lat);
        $stmt->bindValue(':lng', $lng);
        $stmt->bindValue(':sargassum', $beach['sargassum'] ?? null);
        $stmt->bindValue(':surf', $beach['surf'] ?? null);
        $stmt->bindValue(':wind', $beach['wind'] ?? null);
        $stmt->bindValue(':cover_image', $beach['coverImage'] ?? '');
        $stmt->bindValue(':access_label', $beach['accessLabel'] ?? null);
        $stmt->bindValue(':notes', $beach['notes'] ?? null);
        $stmt->bindValue(':description', $beach['description'] ?? null);
        $stmt->bindValue(':parking_details', $beach['parkingDetails'] ?? null);
        $stmt->bindValue(':safety_info', $beach['safetyInfo'] ?? null);
        $stmt->bindValue(':local_tips', $beach['localTips'] ?? null);
        $stmt->bindValue(':best_time', $beach['bestTime'] ?? null);
        $stmt->bindValue(':place_id', $beach['placeId'] ?? null);
        $stmt->bindValue(':google_rating', $beach['googleRating'] ?? null);
        $stmt->bindValue(':google_review_count', $beach['googleReviewCount'] ?? null);
        $stmt->bindValue(':publish_status', 'published');
        $stmt->bindValue(':created_at', $now);
        $stmt->bindValue(':updated_at', $beach['updatedAt'] ?? $now);

        if (!$stmt->execute()) {
            echo "  Error importing beach: {$beach['name']}\n";
            $errors++;
            continue;
        }

        // Insert tags
        if (!empty($beach['tags']) && is_array($beach['tags'])) {
            foreach ($beach['tags'] as $tag) {
                $db->exec("INSERT OR IGNORE INTO beach_tags (beach_id, tag) VALUES ('{$beach['id']}', '{$tag}')");
            }
        }

        // Insert amenities
        if (!empty($beach['amenities']) && is_array($beach['amenities'])) {
            foreach ($beach['amenities'] as $amenity) {
                $db->exec("INSERT OR IGNORE INTO beach_amenities (beach_id, amenity) VALUES ('{$beach['id']}', '{$amenity}')");
            }
        }

        // Insert gallery images
        if (!empty($beach['gallery']) && is_array($beach['gallery'])) {
            foreach ($beach['gallery'] as $position => $imageUrl) {
                $escapedUrl = SQLite3::escapeString($imageUrl);
                $db->exec("INSERT INTO beach_gallery (beach_id, image_url, position) VALUES ('{$beach['id']}', '{$escapedUrl}', {$position})");
            }
        }

        // Insert aliases
        if (!empty($beach['aliases']) && is_array($beach['aliases'])) {
            foreach ($beach['aliases'] as $alias) {
                $escapedAlias = SQLite3::escapeString($alias);
                $db->exec("INSERT INTO beach_aliases (beach_id, alias) VALUES ('{$beach['id']}', '{$escapedAlias}')");
            }
        }

        // Insert features
        if (!empty($beach['features']) && is_array($beach['features'])) {
            foreach ($beach['features'] as $position => $feature) {
                $title = SQLite3::escapeString($feature['title'] ?? '');
                $description = SQLite3::escapeString($feature['description'] ?? '');
                $db->exec("INSERT INTO beach_features (beach_id, title, description, position) VALUES ('{$beach['id']}', '{$title}', '{$description}', {$position})");
            }
        }

        // Insert tips
        if (!empty($beach['tips']) && is_array($beach['tips'])) {
            foreach ($beach['tips'] as $position => $tip) {
                $category = SQLite3::escapeString($tip['category'] ?? '');
                $tipText = SQLite3::escapeString($tip['tip'] ?? '');
                $db->exec("INSERT INTO beach_tips (beach_id, category, tip, position) VALUES ('{$beach['id']}', '{$category}', '{$tipText}', {$position})");
            }
        }

        $imported++;

        if ($imported % 50 == 0) {
            echo "  Imported {$imported} beaches...\n";
        }
    }

    $db->exec('COMMIT');

    echo "\n";
    echo "Import complete!\n";
    echo "================\n";
    echo "Imported: {$imported}\n";
    echo "Skipped (existing): {$skipped}\n";
    echo "Errors: {$errors}\n";

    // Verify counts
    $beachCount = queryOne('SELECT COUNT(*) as count FROM beaches')['count'];
    $tagCount = queryOne('SELECT COUNT(*) as count FROM beach_tags')['count'];
    $amenityCount = queryOne('SELECT COUNT(*) as count FROM beach_amenities')['count'];
    $featureCount = queryOne('SELECT COUNT(*) as count FROM beach_features')['count'];
    $tipCount = queryOne('SELECT COUNT(*) as count FROM beach_tips')['count'];

    echo "\nDatabase totals:\n";
    echo "  Beaches: {$beachCount}\n";
    echo "  Tags: {$tagCount}\n";
    echo "  Amenities: {$amenityCount}\n";
    echo "  Features: {$featureCount}\n";
    echo "  Tips: {$tipCount}\n";

} catch (Exception $e) {
    $db->exec('ROLLBACK');
    die("Error: " . $e->getMessage() . "\n");
}
