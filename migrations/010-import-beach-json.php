<?php
/**
 * Migration 010: Import beaches from JSON with Google Places enrichment
 *
 * This script imports beaches from a JSON file, enriches them with Google Places
 * API data, and either updates existing beaches or inserts new ones.
 *
 * Expected JSON format (supports both structures):
 * {
 *   "beaches": [
 *     {
 *       "name": "Beach Name",
 *       "placeId": "0x...:0x...",  // Hex CID (ignored - using Text Search instead)
 *       "coordinates": {"lat": 18.12345, "lng": -65.12345}
 *     }
 *   ]
 * }
 * OR flat array:
 * [{"name": "Beach Name", "lat": 18.12345, "lng": -65.12345}, ...]
 *
 * Usage: php migrations/010-import-beach-json.php [--dry-run] [--skip-api] [--limit=N]
 */

// Prevent web access
if (php_sapi_name() !== 'cli') {
    die('This script must be run from the command line.');
}

// Parse command line options
$options = getopt('', ['dry-run', 'skip-api', 'limit:', 'verbose', 'help', 'resume:']);

if (isset($options['help'])) {
    echo <<<HELP
Beach Import Migration Script

Usage: php migrations/010-import-beach-json.php [options]

Options:
  --dry-run      Show what would be done without making changes
  --skip-api     Skip Google API calls (use JSON data only)
  --limit=N      Process only first N beaches
  --verbose      Show detailed progress
  --resume=N     Resume from beach index N
  --help         Show this help message

HELP;
    exit(0);
}

$dryRun = isset($options['dry-run']);
$skipApi = isset($options['skip-api']);
$limit = isset($options['limit']) ? (int)$options['limit'] : null;
$verbose = isset($options['verbose']);
$resumeFrom = isset($options['resume']) ? (int)$options['resume'] : 0;

require_once __DIR__ . '/../inc/db.php';
require_once __DIR__ . '/../inc/helpers.php';
require_once __DIR__ . '/../inc/constants.php';
require_once __DIR__ . '/../inc/image-optimizer.php';

// Google Places API (New) Key
$GOOGLE_API_KEY = envRequire('GOOGLE_MAPS_API_KEY');

// Rate limiting delay between API calls (milliseconds)
$API_DELAY_MS = 200;

// Upload directory for photos
$UPLOAD_DIR = __DIR__ . '/../uploads/admin/beaches/';

// JSON source file
$JSON_FILE = __DIR__ . '/../data/beaches-import.json';

// Results tracking
$stats = [
    'total' => 0,
    'matched_exact' => 0,
    'matched_normalized' => 0,
    'matched_coords' => 0,
    'new_inserted' => 0,
    'updated' => 0,
    'api_calls' => 0,
    'api_errors' => 0,
    'api_coord_skipped' => 0,  // Places too far from JSON coordinates
    'photos_downloaded' => 0,
    'photo_errors' => 0,
    'skipped' => 0,
    'errors' => [],
];

/**
 * Log message with timestamp
 */
function logMsg($msg, $level = 'INFO') {
    $timestamp = date('Y-m-d H:i:s');
    echo "[$timestamp] [$level] $msg\n";
}

/**
 * Normalize beach name for matching
 * - Lowercase
 * - Remove accents
 * - Remove common prefixes (Playa, Balneario, etc.)
 * - Remove punctuation
 */
function normalizeName($name) {
    // Lowercase
    $name = mb_strtolower($name, 'UTF-8');

    // Remove accents
    $accents = [
        'á' => 'a', 'é' => 'e', 'í' => 'i', 'ó' => 'o', 'ú' => 'u',
        'à' => 'a', 'è' => 'e', 'ì' => 'i', 'ò' => 'o', 'ù' => 'u',
        'ñ' => 'n', 'ü' => 'u'
    ];
    $name = strtr($name, $accents);

    // Remove common prefixes
    $prefixes = ['playa ', 'balneario ', 'beach ', 'la ', 'el ', 'los ', 'las '];
    foreach ($prefixes as $prefix) {
        if (strpos($name, $prefix) === 0) {
            $name = substr($name, strlen($prefix));
        }
    }

    // Remove punctuation and extra spaces
    $name = preg_replace('/[^a-z0-9\s]/', '', $name);
    $name = preg_replace('/\s+/', ' ', trim($name));

    return $name;
}

/**
 * Calculate distance between two coordinates in meters
 */
function calculateDistance($lat1, $lng1, $lat2, $lng2) {
    $earthRadius = 6371000; // meters

    $lat1Rad = deg2rad($lat1);
    $lat2Rad = deg2rad($lat2);
    $deltaLat = deg2rad($lat2 - $lat1);
    $deltaLng = deg2rad($lng2 - $lng1);

    $a = sin($deltaLat / 2) * sin($deltaLat / 2) +
         cos($lat1Rad) * cos($lat2Rad) *
         sin($deltaLng / 2) * sin($deltaLng / 2);

    $c = 2 * atan2(sqrt($a), sqrt(1 - $a));

    return $earthRadius * $c;
}

/**
 * Find matching beach by name or coordinates
 * Returns: ['type' => 'exact|normalized|coords|none', 'beach' => $beach or null]
 */
function findMatchingBeach($name, $lat, $lng, $existingBeaches, $normalizedLookup, $verbose = false) {
    // Tier 1: Exact match (case-insensitive)
    $nameLower = mb_strtolower($name, 'UTF-8');
    foreach ($existingBeaches as $beach) {
        if (mb_strtolower($beach['name'], 'UTF-8') === $nameLower) {
            if ($verbose) logMsg("  Exact match: {$beach['name']} (ID: {$beach['id']})", 'DEBUG');
            return ['type' => 'exact', 'beach' => $beach];
        }
    }

    // Tier 2: Normalized match
    $normalizedName = normalizeName($name);
    if (isset($normalizedLookup[$normalizedName])) {
        $beach = $normalizedLookup[$normalizedName];
        if ($verbose) logMsg("  Normalized match: {$beach['name']} (ID: {$beach['id']})", 'DEBUG');
        return ['type' => 'normalized', 'beach' => $beach];
    }

    // Tier 3: Coordinate proximity (<200m)
    foreach ($existingBeaches as $beach) {
        if ($beach['lat'] && $beach['lng']) {
            $distance = calculateDistance($lat, $lng, $beach['lat'], $beach['lng']);
            if ($distance < 200) {
                if ($verbose) logMsg("  Coord match: {$beach['name']} ({$distance}m away)", 'DEBUG');
                return ['type' => 'coords', 'beach' => $beach];
            }
        }
    }

    return ['type' => 'none', 'beach' => null];
}

/**
 * Make a POST request to the new Places API
 */
function placesApiPost($endpoint, $body, $fieldMask, $apiKey) {
    global $stats;

    $url = "https://places.googleapis.com/v1/" . $endpoint;

    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST => true,
        CURLOPT_POSTFIELDS => json_encode($body),
        CURLOPT_HTTPHEADER => [
            'Content-Type: application/json',
            'X-Goog-Api-Key: ' . $apiKey,
            'X-Goog-FieldMask: ' . $fieldMask
        ],
        CURLOPT_TIMEOUT => 30
    ]);

    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    $stats['api_calls']++;

    if ($response === false || $httpCode >= 400) {
        return null;
    }

    return json_decode($response, true);
}

/**
 * Make a POST request with retry logic and exponential backoff
 */
function placesApiPostWithRetry($endpoint, $body, $fieldMask, $apiKey, $maxRetries = 3) {
    global $stats;

    for ($attempt = 1; $attempt <= $maxRetries; $attempt++) {
        $result = placesApiPost($endpoint, $body, $fieldMask, $apiKey);
        if ($result !== null) {
            return $result;
        }

        // Exponential backoff: 500ms, 1s, 1.5s
        if ($attempt < $maxRetries) {
            usleep(500000 * $attempt);
        }
    }

    $stats['api_errors']++;
    return null;
}

/**
 * Find place using Text Search (New API)
 * Returns place data including id if found
 */
function findPlaceByNameAndCoords($name, $lat, $lng, $apiKey) {
    global $stats;

    // Don't add "beach" if name already contains it
    $searchQuery = $name . ' Puerto Rico';
    if (stripos($name, 'beach') === false && stripos($name, 'playa') === false) {
        $searchQuery = $name . ' beach Puerto Rico';
    }

    $body = [
        'textQuery' => $searchQuery,
        'locationBias' => [
            'circle' => [
                'center' => [
                    'latitude' => $lat,
                    'longitude' => $lng
                ],
                'radius' => 500.0
            ]
        ],
        'maxResultCount' => 1
    ];

    // Include addressComponents for reliable municipality extraction
    $fieldMask = 'places.id,places.displayName,places.formattedAddress,places.addressComponents,places.location,places.rating,places.userRatingCount,places.photos';

    // Use retry wrapper for resilience
    $data = placesApiPostWithRetry('places:searchText', $body, $fieldMask, $apiKey);

    if ($data && !empty($data['places'])) {
        return $data['places'][0];
    }

    return null;
}

/**
 * Find place using Nearby Search (New API) as fallback
 */
function findPlaceByCoords($lat, $lng, $apiKey) {
    global $stats;

    $body = [
        'locationRestriction' => [
            'circle' => [
                'center' => [
                    'latitude' => $lat,
                    'longitude' => $lng
                ],
                'radius' => 100.0
            ]
        ],
        'maxResultCount' => 1
    ];

    // Include addressComponents for reliable municipality extraction
    $fieldMask = 'places.id,places.displayName,places.formattedAddress,places.addressComponents,places.location,places.rating,places.userRatingCount,places.photos';

    // Use retry wrapper for resilience
    $data = placesApiPostWithRetry('places:searchNearby', $body, $fieldMask, $apiKey);

    if ($data && !empty($data['places'])) {
        return $data['places'][0];
    }

    return null;
}

/**
 * Fetch place details from Google Places API (New) using place resource name
 */
function fetchPlaceDetails($placeResourceName, $apiKey) {
    global $stats;

    // New API uses resource names like "places/ChIJ..."
    $url = "https://places.googleapis.com/v1/" . $placeResourceName;

    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_HTTPHEADER => [
            'X-Goog-Api-Key: ' . $apiKey,
            'X-Goog-FieldMask: id,displayName,formattedAddress,addressComponents,location,rating,userRatingCount,photos'
        ],
        CURLOPT_TIMEOUT => 30
    ]);

    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    $stats['api_calls']++;

    if ($response === false || $httpCode >= 400) {
        $stats['api_errors']++;
        return ['error' => 'API request failed (HTTP ' . $httpCode . ')'];
    }

    $data = json_decode($response, true);

    if (empty($data) || isset($data['error'])) {
        $stats['api_errors']++;
        $errorMsg = $data['error']['message'] ?? 'Place not found';
        return ['error' => $errorMsg];
    }

    return $data;
}

/**
 * Convert new API response to legacy format for compatibility
 */
function convertToLegacyFormat($place) {
    if (!$place) return null;

    // Extract place ID - the new API returns "places/ChIJ..." format
    $placeId = $place['id'] ?? '';
    if (strpos($placeId, 'places/') === 0) {
        $placeId = substr($placeId, 7);
    }

    return [
        'place_id' => $placeId,
        'name' => $place['displayName']['text'] ?? '',
        'formatted_address' => $place['formattedAddress'] ?? '',
        // Include address components for structured municipality extraction
        'address_components' => $place['addressComponents'] ?? [],
        'geometry' => [
            'location' => [
                'lat' => $place['location']['latitude'] ?? null,
                'lng' => $place['location']['longitude'] ?? null
            ]
        ],
        'rating' => $place['rating'] ?? null,
        'user_ratings_total' => $place['userRatingCount'] ?? null,
        // Photos in new API have 'name' field which is the full resource name
        // e.g., "places/ChIJ.../photos/AXy..."
        'photos' => array_map(function($photo) {
            return ['photo_reference' => $photo['name'] ?? null];
        }, $place['photos'] ?? [])
    ];
}

/**
 * Find and fetch place details using coordinates
 * This is the main function that combines search + details
 */
function findAndFetchPlaceDetails($name, $lat, $lng, $apiKey) {
    global $stats;

    // Try to find place using name + coords (Text Search)
    $place = findPlaceByNameAndCoords($name, $lat, $lng, $apiKey);

    // Fallback to nearby search if text search fails
    if (!$place) {
        $place = findPlaceByCoords($lat, $lng, $apiKey);
    }

    if (!$place) {
        $stats['api_errors']++;
        return ['error' => 'Could not find place at coordinates'];
    }

    // Convert to legacy format for compatibility with rest of script
    return convertToLegacyFormat($place);
}

/**
 * Extract municipality from Google address components (New API format)
 * This is more reliable than string matching on formatted address
 */
function extractMunicipalityFromAddressComponents($addressComponents) {
    if (empty($addressComponents)) {
        return null;
    }

    // Priority order: locality > administrative_area_level_2 > administrative_area_level_1
    $typesPriority = ['locality', 'administrative_area_level_2', 'administrative_area_level_1'];

    foreach ($typesPriority as $targetType) {
        foreach ($addressComponents as $component) {
            $types = $component['types'] ?? [];
            if (in_array($targetType, $types)) {
                $text = $component['longText'] ?? $component['shortText'] ?? '';
                // Validate against known municipalities
                if (isValidMunicipality($text)) {
                    return $text;
                }
                // Try without accents for matching
                $normalized = normalizeForMunicipality($text);
                foreach (MUNICIPALITIES as $municipality) {
                    if (strcasecmp(normalizeForMunicipality($municipality), $normalized) === 0) {
                        return $municipality;
                    }
                }
            }
        }
    }

    return null;
}

/**
 * Normalize text for municipality matching (remove accents)
 */
function normalizeForMunicipality($text) {
    $accents = [
        'á' => 'a', 'é' => 'e', 'í' => 'i', 'ó' => 'o', 'ú' => 'u',
        'Á' => 'A', 'É' => 'E', 'Í' => 'I', 'Ó' => 'O', 'Ú' => 'U',
        'ñ' => 'n', 'Ñ' => 'N', 'ü' => 'u', 'Ü' => 'U'
    ];
    return strtr($text, $accents);
}

/**
 * Detect municipality from Google formatted address (fallback method)
 */
function detectMunicipality($address) {
    foreach (MUNICIPALITIES as $municipality) {
        if (stripos($address, $municipality) !== false) {
            return $municipality;
        }
    }
    return null;
}

/**
 * Get municipality with fallback chain:
 * 1. addressComponents (most reliable)
 * 2. formattedAddress string match
 * 3. null (caller handles fallback)
 */
function getMunicipalityFromPlaceDetails($placeDetails) {
    // First try structured address components
    if (!empty($placeDetails['address_components'])) {
        $municipality = extractMunicipalityFromAddressComponents($placeDetails['address_components']);
        if ($municipality) {
            return $municipality;
        }
    }

    // Fall back to formatted address string matching
    if (!empty($placeDetails['formatted_address'])) {
        return detectMunicipality($placeDetails['formatted_address']);
    }

    return null;
}

/**
 * Download and optimize photo from Google Places API (New)
 * The new API uses photo resource names like "places/ChIJ.../photos/AXy..."
 */
function downloadAndOptimizePhoto($photoResourceName, $apiKey, $beachSlug) {
    global $stats, $UPLOAD_DIR;

    // New API format: GET https://places.googleapis.com/v1/{photo_name}/media?maxHeightPx=1200
    $url = "https://places.googleapis.com/v1/" . $photoResourceName . "/media"
         . "?maxHeightPx=1200"
         . "&maxWidthPx=1200"
         . "&skipHttpRedirect=true";

    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_HTTPHEADER => [
            'X-Goog-Api-Key: ' . $apiKey
        ],
        CURLOPT_TIMEOUT => 30
    ]);

    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($httpCode !== 200 || empty($response)) {
        $stats['photo_errors']++;
        return null;
    }

    // The response contains a photoUri we need to fetch
    $data = json_decode($response, true);
    $photoUri = $data['photoUri'] ?? null;

    if (!$photoUri) {
        $stats['photo_errors']++;
        return null;
    }

    // Now fetch the actual image
    $ch = curl_init($photoUri);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_TIMEOUT => 30,
        CURLOPT_USERAGENT => 'Mozilla/5.0 (compatible; AJIApp/1.0)'
    ]);

    $imageData = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $contentType = curl_getinfo($ch, CURLINFO_CONTENT_TYPE);
    curl_close($ch);

    if ($httpCode !== 200 || empty($imageData)) {
        $stats['photo_errors']++;
        return null;
    }

    // Save to temp file
    $extension = 'jpg';
    if (strpos($contentType, 'png') !== false) {
        $extension = 'png';
    } elseif (strpos($contentType, 'webp') !== false) {
        $extension = 'webp';
    }

    $tempFile = tempnam(sys_get_temp_dir(), 'beach_') . '.' . $extension;
    file_put_contents($tempFile, $imageData);

    // Ensure upload directory exists
    if (!is_dir($UPLOAD_DIR)) {
        mkdir($UPLOAD_DIR, 0755, true);
    }

    // Optimize image
    $imageResult = optimizeImage($tempFile, $beachSlug, 'google-places.jpg');

    // Clean up temp file
    @unlink($tempFile);

    if (isset($imageResult['success']) && $imageResult['success']) {
        $stats['photos_downloaded']++;
        return $imageResult['urls']['medium'];
    }

    $stats['photo_errors']++;
    return null;
}

/**
 * Generate unique slug for beach
 */
function generateUniqueSlug($name, $lat, $lng) {
    $baseSlug = slugify($name);

    // Check if slug already exists
    $existing = queryOne(
        'SELECT slug FROM beaches WHERE slug = :slug',
        [':slug' => $baseSlug]
    );

    if (!$existing) {
        return $baseSlug;
    }

    // Append coordinate-based suffix
    $coordSuffix = round($lat * 100) . '-' . abs(round($lng * 100));
    $slug = $baseSlug . '-' . $coordSuffix;

    // Final check
    $existing = queryOne(
        'SELECT slug FROM beaches WHERE slug = :slug',
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

logMsg("Beach Import Migration Starting", 'INFO');
logMsg("Options: dry-run=" . ($dryRun ? 'yes' : 'no') .
       ", skip-api=" . ($skipApi ? 'yes' : 'no') .
       ", limit=" . ($limit ?? 'none') .
       ", resume=" . $resumeFrom, 'INFO');

// Pre-flight API key validation (unless skipping API)
if (!$skipApi) {
    logMsg("Validating Google Places API key...", 'INFO');
    $testBody = [
        'textQuery' => 'Flamenco Beach Culebra Puerto Rico',
        'maxResultCount' => 1
    ];
    $testResult = placesApiPost('places:searchText', $testBody, 'places.id', $GOOGLE_API_KEY);
    if ($testResult === null) {
        logMsg("ERROR: API key validation failed. Check your API key and billing.", 'ERROR');
        exit(1);
    }
    logMsg("API key validated successfully", 'INFO');
}

// Load JSON file
if (!file_exists($JSON_FILE)) {
    logMsg("ERROR: JSON file not found: $JSON_FILE", 'ERROR');
    logMsg("Please create the file with beach data in the format:", 'ERROR');
    logMsg('[{"name": "Beach Name", "placeId": "ChIJ...", "lat": 18.0, "lng": -65.0}, ...]', 'ERROR');
    exit(1);
}

$jsonContent = file_get_contents($JSON_FILE);
$jsonData = json_decode($jsonContent, true);

if (json_last_error() !== JSON_ERROR_NONE) {
    logMsg("ERROR: Invalid JSON: " . json_last_error_msg(), 'ERROR');
    exit(1);
}

// Handle both formats: {"beaches": [...]} or flat array [...]
$beachesJson = $jsonData['beaches'] ?? $jsonData;

if (!is_array($beachesJson)) {
    logMsg("ERROR: JSON must be an array or contain a 'beaches' array", 'ERROR');
    exit(1);
}

$stats['total'] = count($beachesJson);
logMsg("Loaded {$stats['total']} beaches from JSON", 'INFO');

// Log total count if provided
if (isset($jsonData['totalCount'])) {
    logMsg("JSON reports totalCount: {$jsonData['totalCount']}", 'INFO');
}

// Load existing beaches
$existingBeaches = query('SELECT * FROM beaches', []);
logMsg("Found " . count($existingBeaches) . " existing beaches in database", 'INFO');

// Build normalized name lookup
$normalizedLookup = [];
foreach ($existingBeaches as $beach) {
    $normalized = normalizeName($beach['name']);
    if (!isset($normalizedLookup[$normalized])) {
        $normalizedLookup[$normalized] = $beach;
    }
}

// Process each beach
$processed = 0;
foreach ($beachesJson as $index => $beachData) {
    // Skip if resuming
    if ($index < $resumeFrom) {
        continue;
    }

    // Check limit
    if ($limit !== null && $processed >= $limit) {
        logMsg("Limit of $limit beaches reached", 'INFO');
        break;
    }

    $processed++;
    $name = trim($beachData['name'] ?? '');

    // Handle both coordinate formats: nested {coordinates: {lat, lng}} or flat {lat, lng}
    if (isset($beachData['coordinates'])) {
        $lat = (float)($beachData['coordinates']['lat'] ?? 0);
        $lng = (float)($beachData['coordinates']['lng'] ?? 0);
    } else {
        $lat = (float)($beachData['lat'] ?? 0);
        $lng = (float)($beachData['lng'] ?? 0);
    }

    // placeId from JSON is hex CID format - we'll get proper ChIJ format from API
    // So we don't require placeId from JSON, just name and coordinates
    $jsonPlaceId = trim($beachData['placeId'] ?? '');

    // Skip if missing required data (name and valid coordinates)
    if (empty($name) || !$lat || !$lng) {
        logMsg("[$index] Skipping - missing required data: $name (lat: $lat, lng: $lng)", 'WARN');
        $stats['skipped']++;
        continue;
    }

    // Validate coordinates are within Puerto Rico bounds
    if (!isWithinPRBounds($lat, $lng)) {
        logMsg("[$index] Skipping - coordinates outside PR bounds: $name ($lat, $lng)", 'WARN');
        $stats['skipped']++;
        continue;
    }

    logMsg("[$index] Processing: $name", 'INFO');

    // Find matching beach
    $match = findMatchingBeach($name, $lat, $lng, $existingBeaches, $normalizedLookup, $verbose);

    // Fetch Google Places data using coordinates (unless skipping API)
    $placeDetails = null;
    $municipality = null;
    $googleRating = null;
    $googleReviewCount = null;
    $coverImage = null;
    $foundPlaceId = null;

    if (!$skipApi) {
        usleep($API_DELAY_MS * 1000); // Rate limiting

        // Use coordinate-based lookup (ignores hex placeId from JSON)
        $placeDetails = findAndFetchPlaceDetails($name, $lat, $lng, $GOOGLE_API_KEY);

        if (isset($placeDetails['error'])) {
            logMsg("  API Error: {$placeDetails['error']}", 'WARN');
            $stats['errors'][] = "[$index] $name: {$placeDetails['error']}";
        } else {
            // Extract data from API response
            $foundPlaceId = $placeDetails['place_id'] ?? null;
            $googleRating = $placeDetails['rating'] ?? null;
            $googleReviewCount = $placeDetails['user_ratings_total'] ?? null;

            // Use new improved municipality extraction (addressComponents first, then fallback)
            $municipality = getMunicipalityFromPlaceDetails($placeDetails);

            // Coordinate distance safety check
            if (isset($placeDetails['geometry']['location'])) {
                $apiLat = $placeDetails['geometry']['location']['lat'];
                $apiLng = $placeDetails['geometry']['location']['lng'];
                $coordDiff = calculateDistance($lat, $lng, $apiLat, $apiLng);

                if ($coordDiff > 500) {
                    // API returned a place too far away - likely wrong result
                    logMsg("  WARNING: API returned place {$coordDiff}m away - skipping API data", 'WARN');
                    $stats['api_coord_skipped']++;
                    // Don't use API data for this beach
                    $foundPlaceId = null;
                    $googleRating = null;
                    $googleReviewCount = null;
                    $municipality = null;
                } elseif ($coordDiff <= 100) {
                    // Close match - use more precise API coordinates
                    if ($verbose) {
                        logMsg("  Coordinate difference: {$coordDiff}m - using API coords (more precise)", 'DEBUG');
                    }
                    $lat = $apiLat;
                    $lng = $apiLng;
                } else {
                    // 100-500m: keep JSON coords but still use place_id
                    if ($verbose) {
                        logMsg("  Coordinate difference: {$coordDiff}m - keeping JSON coords, using API place_id", 'DEBUG');
                    }
                }
            }

            // Download photo (only for new beaches or those missing cover image)
            if ($foundPlaceId && !empty($placeDetails['photos'][0]['photo_reference'])) {
                $needsPhoto = ($match['type'] === 'none') ||
                              (isset($match['beach']) &&
                               (!$match['beach']['cover_image'] ||
                                strpos($match['beach']['cover_image'], 'placeholder') !== false));

                if ($needsPhoto && !$dryRun) {
                    $slug = $match['beach']['slug'] ?? generateUniqueSlug($name, $lat, $lng);
                    $coverImage = downloadAndOptimizePhoto(
                        $placeDetails['photos'][0]['photo_reference'],
                        $GOOGLE_API_KEY,
                        $slug
                    );
                    if ($coverImage) {
                        logMsg("  Downloaded photo", 'INFO');
                    }
                }
            }

            if ($verbose) {
                logMsg("  Found Place ID: " . ($foundPlaceId ?? 'N/A'), 'DEBUG');
                logMsg("  Municipality: " . ($municipality ?? 'Unknown'), 'DEBUG');
                logMsg("  Rating: " . ($googleRating ?? 'N/A') . " (" . ($googleReviewCount ?? 0) . " reviews)", 'DEBUG');
            }
        }
    }

    // Handle based on match type
    if ($match['type'] !== 'none') {
        // Update existing beach
        $existingBeach = $match['beach'];
        $stats['matched_' . $match['type']]++;

        if ($dryRun) {
            logMsg("  [DRY-RUN] Would update: {$existingBeach['name']} (ID: {$existingBeach['id']})", 'INFO');
        } else {
            // Build update fields
            $updateFields = [];
            $updateParams = [':id' => $existingBeach['id']];

            // Always update coordinates
            $updateFields[] = 'lat = :lat';
            $updateFields[] = 'lng = :lng';
            $updateParams[':lat'] = round($lat, 6);
            $updateParams[':lng'] = round($lng, 6);

            // Update place_id if we found one from API
            if ($foundPlaceId) {
                $updateFields[] = 'place_id = :place_id';
                $updateParams[':place_id'] = $foundPlaceId;
            }

            // Update Google data if available
            if ($googleRating !== null) {
                $updateFields[] = 'google_rating = :google_rating';
                $updateParams[':google_rating'] = $googleRating;
            }
            if ($googleReviewCount !== null) {
                $updateFields[] = 'google_review_count = :google_review_count';
                $updateParams[':google_review_count'] = $googleReviewCount;
            }

            // Update municipality if missing and we have one
            if ($municipality && (!$existingBeach['municipality'] || $existingBeach['municipality'] === 'Unknown')) {
                $updateFields[] = 'municipality = :municipality';
                $updateParams[':municipality'] = $municipality;
            }

            // Update cover image if we downloaded one
            if ($coverImage) {
                $updateFields[] = 'cover_image = :cover_image';
                $updateParams[':cover_image'] = $coverImage;
            }

            $updateFields[] = 'updated_at = datetime(\'now\')';

            $sql = 'UPDATE beaches SET ' . implode(', ', $updateFields) . ' WHERE id = :id';
            $result = execute($sql, $updateParams);

            if ($result) {
                $stats['updated']++;
                logMsg("  Updated: {$existingBeach['name']}", 'INFO');
            } else {
                logMsg("  ERROR: Failed to update {$existingBeach['name']}", 'ERROR');
                $stats['errors'][] = "[$index] Failed to update: {$existingBeach['name']}";
            }
        }
    } else {
        // Insert new beach
        if ($dryRun) {
            logMsg("  [DRY-RUN] Would insert new beach: $name", 'INFO');
        } else {
            $id = uuid();
            $slug = generateUniqueSlug($name, $lat, $lng);

            // Use placeholder if no photo downloaded
            if (!$coverImage) {
                $coverImage = '/images/beaches/placeholder-beach.webp';
            }

            // Default municipality if not detected
            if (!$municipality) {
                // Try to find nearest beach's municipality
                $nearestDist = PHP_INT_MAX;
                foreach ($existingBeaches as $beach) {
                    if ($beach['municipality'] && $beach['lat'] && $beach['lng']) {
                        $dist = calculateDistance($lat, $lng, $beach['lat'], $beach['lng']);
                        if ($dist < $nearestDist) {
                            $nearestDist = $dist;
                            $municipality = $beach['municipality'];
                        }
                    }
                }
                if (!$municipality) {
                    $municipality = 'Unknown';
                }
            }

            $result = execute("
                INSERT INTO beaches (
                    id, slug, name, municipality, lat, lng, cover_image,
                    place_id, google_rating, google_review_count,
                    publish_status, created_at, updated_at
                ) VALUES (
                    :id, :slug, :name, :municipality, :lat, :lng, :cover_image,
                    :place_id, :google_rating, :google_review_count,
                    'published', datetime('now'), datetime('now')
                )
            ", [
                ':id' => $id,
                ':slug' => $slug,
                ':name' => $name,
                ':municipality' => $municipality,
                ':lat' => round($lat, 6),
                ':lng' => round($lng, 6),
                ':cover_image' => $coverImage,
                ':place_id' => $foundPlaceId,  // Use the place_id from API, not hex CID from JSON
                ':google_rating' => $googleRating,
                ':google_review_count' => $googleReviewCount
            ]);

            if ($result) {
                $stats['new_inserted']++;
                logMsg("  Inserted: $name (ID: $id)", 'INFO');

                // Add to existing beaches array for future matching
                $existingBeaches[] = [
                    'id' => $id,
                    'slug' => $slug,
                    'name' => $name,
                    'lat' => $lat,
                    'lng' => $lng,
                    'municipality' => $municipality,
                    'cover_image' => $coverImage
                ];

                // Also add to normalized lookup
                $normalizedLookup[normalizeName($name)] = $existingBeaches[count($existingBeaches) - 1];
            } else {
                logMsg("  ERROR: Failed to insert $name", 'ERROR');
                $stats['errors'][] = "[$index] Failed to insert: $name";
            }
        }

        $stats['new_inserted'] += $dryRun ? 0 : 0; // Handled above
    }
}

// Print summary
logMsg("", 'INFO');
logMsg("========================================", 'INFO');
logMsg("MIGRATION SUMMARY", 'INFO');
logMsg("========================================", 'INFO');
logMsg("Total beaches in JSON: {$stats['total']}", 'INFO');
logMsg("Processed: $processed", 'INFO');
logMsg("", 'INFO');
logMsg("Matches:", 'INFO');
logMsg("  - Exact name match: {$stats['matched_exact']}", 'INFO');
logMsg("  - Normalized name match: {$stats['matched_normalized']}", 'INFO');
logMsg("  - Coordinate proximity match: {$stats['matched_coords']}", 'INFO');
logMsg("", 'INFO');
logMsg("Actions:", 'INFO');
logMsg("  - Updated existing: {$stats['updated']}", 'INFO');
logMsg("  - Inserted new: {$stats['new_inserted']}", 'INFO');
logMsg("  - Skipped: {$stats['skipped']}", 'INFO');
logMsg("", 'INFO');
logMsg("API:", 'INFO');
logMsg("  - API calls made: {$stats['api_calls']}", 'INFO');
logMsg("  - API errors: {$stats['api_errors']}", 'INFO');
logMsg("  - Coord distance skips: {$stats['api_coord_skipped']}", 'INFO');
logMsg("  - Photos downloaded: {$stats['photos_downloaded']}", 'INFO');
logMsg("  - Photo errors: {$stats['photo_errors']}", 'INFO');

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
$finalCount = queryOne('SELECT COUNT(*) as count FROM beaches', []);
logMsg("", 'INFO');
logMsg("Final beach count in database: {$finalCount['count']}", 'INFO');

logMsg("", 'INFO');
logMsg("Migration complete!", 'INFO');
