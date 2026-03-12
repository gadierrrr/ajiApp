<?php
/**
 * Migration 031: Import trails from JSON
 *
 * Imports 200 Puerto Rico trails from data/trails-import.json into the trails table,
 * along with tags and amenities into the shared place_tags/place_amenities tables.
 *
 * Usage: php migrations/031-import-trails.php [--dry-run] [--limit=N] [--verbose]
 */

// Prevent web access
if (php_sapi_name() !== 'cli') {
    die('This script must be run from the command line.');
}

// Parse command line options
$options = getopt('', ['dry-run', 'limit:', 'verbose', 'help']);

if (isset($options['help'])) {
    echo <<<HELP
Trail Import Migration Script

Usage: php migrations/031-import-trails.php [options]

Options:
  --dry-run      Show what would be done without making changes
  --limit=N      Process only first N trails
  --verbose      Show detailed progress
  --help         Show this help message

HELP;
    exit(0);
}

$dryRun  = isset($options['dry-run']);
$limit   = isset($options['limit']) ? (int)$options['limit'] : null;
$verbose = isset($options['verbose']);

require_once __DIR__ . '/../inc/db.php';
require_once __DIR__ . '/../inc/helpers.php';
require_once __DIR__ . '/../inc/constants.php';

// JSON source file
$JSON_FILE = __DIR__ . '/../data/trails-import.json';

// Results tracking
$stats = [
    'total'     => 0,
    'inserted'  => 0,
    'skipped'   => 0,
    'errors'    => [],
];

/**
 * Log message with timestamp
 */
function logMsg($msg, $level = 'INFO') {
    $timestamp = date('Y-m-d H:i:s');
    echo "[$timestamp] [$level] $msg\n";
}

/**
 * Generate a unique slug for a trail, appending a suffix if needed
 */
function generateUniqueTrailSlug($name, $lat, $lng) {
    $baseSlug = slugify($name);

    $existing = queryOne(
        'SELECT slug FROM trails WHERE slug = :slug',
        [':slug' => $baseSlug]
    );

    if (!$existing) {
        return $baseSlug;
    }

    // Append coordinate-based suffix
    $coordSuffix = round($lat * 100) . '-' . abs(round($lng * 100));
    $slug = $baseSlug . '-' . $coordSuffix;

    $existing = queryOne(
        'SELECT slug FROM trails WHERE slug = :slug',
        [':slug' => $slug]
    );

    if (!$existing) {
        return $slug;
    }

    // Last resort: append random string
    return $slug . '-' . substr(uniqid(), -6);
}

// ============================================================================
// MAIN SCRIPT
// ============================================================================

logMsg("Trail Import Migration Starting", 'INFO');
logMsg("Options: dry-run=" . ($dryRun ? 'yes' : 'no') .
       ", limit=" . ($limit ?? 'none') .
       ", verbose=" . ($verbose ? 'yes' : 'no'), 'INFO');

// Load JSON file
if (!file_exists($JSON_FILE)) {
    logMsg("ERROR: JSON file not found: $JSON_FILE", 'ERROR');
    exit(1);
}

$jsonContent = file_get_contents($JSON_FILE);
$jsonData = json_decode($jsonContent, true);

if (json_last_error() !== JSON_ERROR_NONE) {
    logMsg("ERROR: Invalid JSON: " . json_last_error_msg(), 'ERROR');
    exit(1);
}

// Handle {"trails": [...]} or flat array
$trailsJson = $jsonData['trails'] ?? $jsonData;

if (!is_array($trailsJson)) {
    logMsg("ERROR: JSON must be an array or contain a 'trails' array", 'ERROR');
    exit(1);
}

$stats['total'] = count($trailsJson);
logMsg("Loaded {$stats['total']} trails from JSON", 'INFO');

// Check current count
$currentCount = queryOne('SELECT COUNT(*) as count FROM trails', []);
logMsg("Current trails in database: {$currentCount['count']}", 'INFO');

// Process each trail
$processed = 0;
foreach ($trailsJson as $index => $t) {
    if ($limit !== null && $processed >= $limit) {
        logMsg("Limit of $limit trails reached", 'INFO');
        break;
    }

    $processed++;
    $name = trim($t['name'] ?? '');
    $lat  = (float)($t['lat'] ?? 0);
    $lng  = (float)($t['lng'] ?? 0);

    if (empty($name) || !$lat || !$lng) {
        logMsg("[$index] Skipping - missing required data: $name", 'WARN');
        $stats['skipped']++;
        continue;
    }

    // Validate coordinates within PR bounds
    if (!isWithinPRBounds($lat, $lng)) {
        logMsg("[$index] Skipping - coordinates outside PR bounds: $name ($lat, $lng)", 'WARN');
        $stats['skipped']++;
        continue;
    }

    // Check for duplicate by slug
    $slug = slugify($name);
    $existing = queryOne('SELECT id FROM trails WHERE slug = :slug', [':slug' => $slug]);
    if ($existing) {
        if ($verbose) logMsg("[$index] Skipping duplicate: $name (slug: $slug)", 'DEBUG');
        $stats['skipped']++;
        continue;
    }

    // Map fields
    $municipality         = trim($t['municipality'] ?? '');
    $difficulty           = $t['difficulty'] ?? 'moderate';
    $distanceKm           = round((float)($t['distance_km'] ?? 0), 2);
    $elevationGainM       = round((float)($t['elevation_gain_m'] ?? 0), 0);
    $estimatedTimeMinutes = (int)($t['estimated_time_minutes'] ?? 0);
    $trailType            = $t['trail_type'] ?? 'out-and-back';
    $surfaceType          = $t['surface_type'] ?? 'dirt';
    $dogFriendly          = (int)($t['dog_friendly'] ?? 0);
    $bikeFriendly         = (int)($t['bike_friendly'] ?? 0);
    $shaded               = (int)($t['shaded'] ?? 0);
    $googleRating         = isset($t['google_rating']) ? round((float)$t['google_rating'], 1) : null;
    $googleReviewCount    = isset($t['google_review_count']) ? (int)$t['google_review_count'] : null;
    $description          = trim($t['description'] ?? '');
    $descriptionEs        = trim($t['description_es'] ?? '');
    $tags                 = $t['tags'] ?? [];
    $amenities            = $t['amenities'] ?? [];

    // Validate difficulty
    if (!in_array($difficulty, TRAIL_DIFFICULTIES)) {
        $difficulty = 'moderate';
    }

    // Validate municipality
    if ($municipality && !isValidMunicipality($municipality)) {
        logMsg("[$index] WARNING: Invalid municipality '$municipality' for $name", 'WARN');
    }

    // Filter tags and amenities to valid values
    $validTags = array_filter($tags, function($tag) {
        return in_array($tag, TRAIL_TAGS);
    });
    $validAmenities = array_filter($amenities, function($amenity) {
        return in_array($amenity, TRAIL_AMENITIES);
    });

    if ($verbose) {
        logMsg("[$index] $name - $difficulty, {$distanceKm}km, {$elevationGainM}m gain, $municipality", 'DEBUG');
        logMsg("  Tags: " . implode(', ', $validTags), 'DEBUG');
        logMsg("  Amenities: " . implode(', ', $validAmenities), 'DEBUG');
    }

    if ($dryRun) {
        logMsg("[$index] [DRY-RUN] Would insert: $name ($slug)", 'INFO');
        $stats['inserted']++;
        continue;
    }

    // Generate unique slug and UUID
    $finalSlug = generateUniqueTrailSlug($name, $lat, $lng);
    $id = uuid();

    // INSERT into trails
    $result = execute("
        INSERT INTO trails (
            id, slug, name, municipality, lat, lng,
            difficulty, distance_km, elevation_gain_m, estimated_time_minutes,
            trail_type, surface_type, dog_friendly, bike_friendly, shaded,
            google_rating, google_review_count,
            description, description_es,
            publish_status, published_at, created_at, updated_at
        ) VALUES (
            :id, :slug, :name, :municipality, :lat, :lng,
            :difficulty, :distance_km, :elevation_gain_m, :estimated_time_minutes,
            :trail_type, :surface_type, :dog_friendly, :bike_friendly, :shaded,
            :google_rating, :google_review_count,
            :description, :description_es,
            'published', datetime('now'), datetime('now'), datetime('now')
        )
    ", [
        ':id'                     => $id,
        ':slug'                   => $finalSlug,
        ':name'                   => $name,
        ':municipality'           => $municipality ?: null,
        ':lat'                    => round($lat, 6),
        ':lng'                    => round($lng, 6),
        ':difficulty'             => $difficulty,
        ':distance_km'            => $distanceKm,
        ':elevation_gain_m'       => $elevationGainM,
        ':estimated_time_minutes' => $estimatedTimeMinutes,
        ':trail_type'             => $trailType,
        ':surface_type'           => $surfaceType,
        ':dog_friendly'           => $dogFriendly,
        ':bike_friendly'          => $bikeFriendly,
        ':shaded'                 => $shaded,
        ':google_rating'          => $googleRating,
        ':google_review_count'    => $googleReviewCount,
        ':description'            => $description ?: null,
        ':description_es'         => $descriptionEs ?: null,
    ]);

    if (!$result) {
        logMsg("[$index] ERROR: Failed to insert $name", 'ERROR');
        $stats['errors'][] = "[$index] Failed to insert: $name";
        continue;
    }

    // INSERT tags into place_tags
    foreach ($validTags as $tag) {
        execute("
            INSERT OR IGNORE INTO place_tags (id, place_type, place_id, tag)
            VALUES (:id, 'trail', :place_id, :tag)
        ", [
            ':id'       => uuid(),
            ':place_id' => $id,
            ':tag'      => $tag,
        ]);
    }

    // INSERT amenities into place_amenities
    foreach ($validAmenities as $amenity) {
        execute("
            INSERT OR IGNORE INTO place_amenities (id, place_type, place_id, amenity)
            VALUES (:id, 'trail', :place_id, :amenity)
        ", [
            ':id'       => uuid(),
            ':place_id' => $id,
            ':amenity'  => $amenity,
        ]);
    }

    $stats['inserted']++;
    if ($verbose) {
        logMsg("[$index] Inserted: $name (ID: $id, slug: $finalSlug)", 'INFO');
    } else {
        // Show progress every 25 trails
        if ($stats['inserted'] % 25 === 0) {
            logMsg("Progress: {$stats['inserted']} trails inserted...", 'INFO');
        }
    }
}

// Print summary
logMsg("", 'INFO');
logMsg("========================================", 'INFO');
logMsg("MIGRATION SUMMARY", 'INFO');
logMsg("========================================", 'INFO');
logMsg("Total trails in JSON: {$stats['total']}", 'INFO');
logMsg("Processed: $processed", 'INFO');
logMsg("Inserted: {$stats['inserted']}", 'INFO');
logMsg("Skipped: {$stats['skipped']}", 'INFO');

if (!empty($stats['errors'])) {
    logMsg("", 'INFO');
    logMsg("ERRORS:", 'ERROR');
    foreach ($stats['errors'] as $error) {
        logMsg("  $error", 'ERROR');
    }
}

if ($dryRun) {
    logMsg("", 'INFO');
    logMsg("This was a DRY RUN - no changes were made", 'WARN');
}

// Verify final count
$finalCount = queryOne('SELECT COUNT(*) as count FROM trails', []);
logMsg("", 'INFO');
logMsg("Final trail count in database: {$finalCount['count']}", 'INFO');

// Tag/amenity count
$tagCount = queryOne("SELECT COUNT(*) as count FROM place_tags WHERE place_type = 'trail'", []);
$amenityCount = queryOne("SELECT COUNT(*) as count FROM place_amenities WHERE place_type = 'trail'", []);
logMsg("Total trail tags: {$tagCount['count']}", 'INFO');
logMsg("Total trail amenities: {$amenityCount['count']}", 'INFO');

logMsg("", 'INFO');
logMsg("Migration complete!", 'INFO');
