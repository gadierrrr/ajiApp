<?php
/**
 * Admin Quick Add Beach from Google Maps URL
 *
 * POST - Create a new beach from a Google Maps URL
 *
 * Flow:
 * 1. Parse URL to extract coordinates and/or Place ID
 * 2. Check for duplicates (by place_id or coordinates)
 * 3. Fetch full details from Google Places API
 * 4. Download and optimize cover photo
 * 5. Create beach as draft
 */

require_once $_SERVER['DOCUMENT_ROOT'] . '/../bootstrap.php';

require_once APP_ROOT . '/inc/session.php';
session_start();
require_once APP_ROOT . '/inc/db.php';
require_once APP_ROOT . '/inc/helpers.php';
require_once APP_ROOT . '/inc/admin.php';
require_once APP_ROOT . '/inc/constants.php';
require_once APP_ROOT . '/inc/image-optimizer.php';

header('Content-Type: application/json');

// Require admin authentication
if (!isAdmin()) {
    jsonResponse(['error' => 'Unauthorized'], 403);
}

// Only accept POST requests
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    jsonResponse(['error' => 'Method not allowed'], 405);
}

// Get JSON input
$input = json_decode(file_get_contents('php://input'), true);
$url = trim($input['url'] ?? '');
$csrfToken = $input['csrf_token'] ?? '';

// Validate CSRF
if (!validateCsrf($csrfToken)) {
    jsonResponse(['error' => 'Invalid CSRF token'], 403);
}

if (empty($url)) {
    jsonResponse(['error' => 'URL is required'], 400);
}

// Google Maps API Key
$apiKey = envRequire('GOOGLE_MAPS_API_KEY');

// Puerto Rico bounding box
$prBounds = [
    'minLat' => 17.8,
    'maxLat' => 18.6,
    'minLng' => -67.5,
    'maxLng' => -65.0
];

/**
 * Follow redirects and get final URL
 */
function getRedirectedUrl($url) {
    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_MAXREDIRS => 5,
        CURLOPT_TIMEOUT => 10,
        CURLOPT_USERAGENT => 'Mozilla/5.0 (compatible; AJIApp/1.0)'
    ]);
    curl_exec($ch);
    $finalUrl = curl_getinfo($ch, CURLINFO_EFFECTIVE_URL);
    curl_close($ch);
    return $finalUrl;
}

/**
 * Extract coordinates from URL patterns
 */
function extractCoordsFromUrl($url) {
    // Pattern 1: @lat,lng,zoom format
    if (preg_match('/@(-?\d+\.?\d*),(-?\d+\.?\d*)/', $url, $matches)) {
        return ['lat' => (float) $matches[1], 'lng' => (float) $matches[2]];
    }
    // Pattern 2: ?ll=lat,lng
    if (preg_match('/[?&]ll=(-?\d+\.?\d*),(-?\d+\.?\d*)/', $url, $matches)) {
        return ['lat' => (float) $matches[1], 'lng' => (float) $matches[2]];
    }
    // Pattern 3: !3d and !4d data parameters
    if (preg_match('/!3d(-?\d+\.?\d*)!4d(-?\d+\.?\d*)/', $url, $matches)) {
        return ['lat' => (float) $matches[1], 'lng' => (float) $matches[2]];
    }
    return null;
}

/**
 * Extract Place ID from URL
 */
function extractPlaceId($url) {
    if (preg_match('/place_id[=:]([A-Za-z0-9_-]+)/', $url, $matches)) {
        return $matches[1];
    }
    if (preg_match('/!1s(ChIJ[A-Za-z0-9_-]+)/', $url, $matches)) {
        return $matches[1];
    }
    return null;
}

/**
 * Extract place name from URL for search
 */
function extractPlaceName($url) {
    if (preg_match('/\/place\/([^\/\@]+)/', $url, $matches)) {
        $name = urldecode($matches[1]);
        return str_replace('+', ' ', $name);
    }
    return null;
}

/**
 * Get full place details from Google Places API
 */
function getFullPlaceDetails($placeId, $apiKey) {
    $fields = 'name,place_id,formatted_address,address_components,geometry,rating,user_ratings_total,photos,types';
    $url = "https://maps.googleapis.com/maps/api/place/details/json"
         . "?place_id=" . urlencode($placeId)
         . "&fields=" . $fields
         . "&key=" . $apiKey;

    $response = @file_get_contents($url);
    if ($response === false) {
        return ['error' => 'API request failed'];
    }

    $data = json_decode($response, true);

    if ($data['status'] !== 'OK' || empty($data['result'])) {
        return ['error' => $data['status'] . ': ' . ($data['error_message'] ?? 'Place not found')];
    }

    return $data['result'];
}

/**
 * Search for a place by text query
 */
function searchPlace($query, $apiKey) {
    $searchQuery = $query . ' Puerto Rico';
    $url = "https://maps.googleapis.com/maps/api/place/findplacefromtext/json"
         . "?input=" . urlencode($searchQuery)
         . "&inputtype=textquery"
         . "&fields=place_id,name,geometry,formatted_address"
         . "&locationbias=circle:100000@18.2208,-66.5901"
         . "&key=" . $apiKey;

    $response = @file_get_contents($url);
    if ($response === false) {
        return ['error' => 'API request failed'];
    }

    $data = json_decode($response, true);

    if ($data['status'] === 'OK' && !empty($data['candidates'])) {
        return $data['candidates'][0];
    }

    return ['error' => 'Place not found: ' . $query];
}

/**
 * Find Place ID from coordinates (reverse geocode)
 */
function findPlaceIdByCoords($lat, $lng, $apiKey) {
    $url = "https://maps.googleapis.com/maps/api/place/nearbysearch/json"
         . "?location=" . $lat . "," . $lng
         . "&radius=50"
         . "&key=" . $apiKey;

    $response = @file_get_contents($url);
    if ($response === false) {
        return null;
    }

    $data = json_decode($response, true);

    if ($data['status'] === 'OK' && !empty($data['results'])) {
        return $data['results'][0]['place_id'];
    }

    return null;
}

/**
 * Download photo from Google Places API
 */
function downloadPlacePhoto($photoReference, $apiKey, $maxWidth = 1200) {
    $url = "https://maps.googleapis.com/maps/api/place/photo"
         . "?maxwidth=" . $maxWidth
         . "&photo_reference=" . urlencode($photoReference)
         . "&key=" . $apiKey;

    $ch = curl_init($url);
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

    return $tempFile;
}

/**
 * Detect municipality from formatted address
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
 * Check for duplicate beaches
 */
function checkDuplicate($placeId, $lat, $lng) {
    // Check by place_id first
    if ($placeId) {
        $existing = queryOne(
            'SELECT id, name, slug FROM beaches WHERE place_id = :place_id LIMIT 1',
            [':place_id' => $placeId]
        );
        if ($existing) {
            return ['type' => 'place_id', 'beach' => $existing];
        }
    }

    // Check by coordinates (within ~100m = 0.001 degrees)
    $existing = queryOne(
        'SELECT id, name, slug FROM beaches
         WHERE lat BETWEEN :lat_min AND :lat_max
           AND lng BETWEEN :lng_min AND :lng_max
         LIMIT 1',
        [
            ':lat_min' => $lat - 0.001,
            ':lat_max' => $lat + 0.001,
            ':lng_min' => $lng - 0.001,
            ':lng_max' => $lng + 0.001
        ]
    );

    if ($existing) {
        return ['type' => 'coordinates', 'beach' => $existing];
    }

    return null;
}

// Main logic
try {
    // Handle short URLs
    if (preg_match('/goo\.gl|maps\.app/', $url)) {
        $url = getRedirectedUrl($url);
        if (!$url) {
            jsonResponse(['success' => false, 'error' => 'url_invalid', 'message' => 'Could not resolve short URL'], 400);
        }
    }

    // Extract Place ID from URL
    $placeId = extractPlaceId($url);
    $coords = extractCoordsFromUrl($url);
    $placeName = extractPlaceName($url);

    // If we have a place name but no place ID, search for it
    if (!$placeId && $placeName) {
        $searchResult = searchPlace($placeName, $apiKey);
        if (!isset($searchResult['error'])) {
            $placeId = $searchResult['place_id'];
            if (!$coords && isset($searchResult['geometry']['location'])) {
                $coords = [
                    'lat' => $searchResult['geometry']['location']['lat'],
                    'lng' => $searchResult['geometry']['location']['lng']
                ];
            }
        }
    }

    // If we have coords but no place ID, try reverse lookup
    if (!$placeId && $coords) {
        $placeId = findPlaceIdByCoords($coords['lat'], $coords['lng'], $apiKey);
    }

    // We need at least a place ID to proceed
    if (!$placeId) {
        jsonResponse([
            'success' => false,
            'error' => 'no_place_id',
            'message' => 'Could not extract place information from URL. Try a direct Google Maps place URL.'
        ], 400);
    }

    // Get full place details
    $placeDetails = getFullPlaceDetails($placeId, $apiKey);
    if (isset($placeDetails['error'])) {
        jsonResponse([
            'success' => false,
            'error' => 'api_error',
            'message' => $placeDetails['error']
        ], 400);
    }

    // Extract coordinates from place details if we don't have them
    $lat = $coords['lat'] ?? $placeDetails['geometry']['location']['lat'] ?? null;
    $lng = $coords['lng'] ?? $placeDetails['geometry']['location']['lng'] ?? null;

    if (!$lat || !$lng) {
        jsonResponse([
            'success' => false,
            'error' => 'no_coordinates',
            'message' => 'Could not determine location coordinates'
        ], 400);
    }

    // Validate coordinates are within Puerto Rico
    if ($lat < $prBounds['minLat'] || $lat > $prBounds['maxLat'] ||
        $lng < $prBounds['minLng'] || $lng > $prBounds['maxLng']) {
        jsonResponse([
            'success' => false,
            'error' => 'outside_pr',
            'message' => 'Location appears to be outside Puerto Rico. Coordinates: ' . round($lat, 4) . ', ' . round($lng, 4)
        ], 400);
    }

    // Check for duplicates
    $duplicate = checkDuplicate($placeId, $lat, $lng);
    if ($duplicate) {
        jsonResponse([
            'success' => false,
            'error' => 'duplicate',
            'message' => 'A beach already exists at this location',
            'duplicate_type' => $duplicate['type'],
            'existing_beach' => [
                'id' => $duplicate['beach']['id'],
                'name' => $duplicate['beach']['name'],
                'slug' => $duplicate['beach']['slug'],
                'edit_url' => '/admin/beaches.php?action=edit&id=' . $duplicate['beach']['id']
            ]
        ], 409);
    }

    // Extract beach data
    $name = $placeDetails['name'] ?? 'Unnamed Beach';
    $address = $placeDetails['formatted_address'] ?? '';
    $municipality = detectMunicipality($address);
    $googleRating = $placeDetails['rating'] ?? null;
    $googleReviewCount = $placeDetails['user_ratings_total'] ?? null;

    // Generate ID and slug
    $id = adminGenerateUuid();
    $baseSlug = slugify($name);
    $slug = $baseSlug . '-' . substr($id, 0, 8);

    // Process cover image
    $coverImage = '/images/beaches/placeholder-beach.webp';
    $imageProcessed = false;
    $imageError = null;

    if (!empty($placeDetails['photos'][0]['photo_reference'])) {
        $photoRef = $placeDetails['photos'][0]['photo_reference'];
        $tempFile = downloadPlacePhoto($photoRef, $apiKey);

        if ($tempFile && file_exists($tempFile)) {
            // Ensure upload directory exists
            $uploadDir = APP_ROOT . '/uploads/admin/beaches/';
            if (!is_dir($uploadDir)) {
                mkdir($uploadDir, 0755, true);
            }

            // Optimize image
            $imageResult = optimizeImage($tempFile, $slug, 'google-places.jpg');

            if (isset($imageResult['success']) && $imageResult['success']) {
                $coverImage = $imageResult['urls']['medium'];
                $imageProcessed = true;
            } else {
                $imageError = $imageResult['error'] ?? 'Image optimization failed';
            }

            // Clean up temp file
            @unlink($tempFile);
        } else {
            $imageError = 'Failed to download photo from Google';
        }
    }

    // Insert beach record
    $insertResult = execute("
        INSERT INTO beaches (
            id, slug, name, municipality, lat, lng, cover_image,
            place_id, google_rating, google_review_count,
            publish_status, created_at, updated_at
        ) VALUES (
            :id, :slug, :name, :municipality, :lat, :lng, :cover_image,
            :place_id, :google_rating, :google_review_count,
            'draft', datetime('now'), datetime('now')
        )
    ", [
        ':id' => $id,
        ':slug' => $slug,
        ':name' => $name,
        ':municipality' => $municipality ?? 'Unknown',
        ':lat' => round($lat, 6),
        ':lng' => round($lng, 6),
        ':cover_image' => $coverImage,
        ':place_id' => $placeId,
        ':google_rating' => $googleRating,
        ':google_review_count' => $googleReviewCount
    ]);

    if (!$insertResult) {
        jsonResponse([
            'success' => false,
            'error' => 'db_error',
            'message' => 'Failed to create beach record'
        ], 500);
    }

    // Build response
    $response = [
        'success' => true,
        'message' => 'Beach created successfully as draft',
        'beach' => [
            'id' => $id,
            'name' => $name,
            'slug' => $slug,
            'municipality' => $municipality ?? 'Unknown',
            'lat' => round($lat, 6),
            'lng' => round($lng, 6),
            'google_rating' => $googleRating,
            'google_review_count' => $googleReviewCount,
            'cover_image' => $coverImage,
            'publish_status' => 'draft'
        ],
        'edit_url' => '/admin/beaches.php?action=edit&id=' . $id
    ];

    // Add warnings if applicable
    $warnings = [];
    if (!$municipality) {
        $warnings[] = 'Could not detect municipality from address. Please set it manually.';
    }
    if (!$imageProcessed) {
        $warnings[] = $imageError ?: 'No cover photo available. Please upload one manually.';
    }
    if (!empty($warnings)) {
        $response['warnings'] = $warnings;
    }

    jsonResponse($response);

} catch (Exception $e) {
    jsonResponse([
        'success' => false,
        'error' => 'exception',
        'message' => 'An error occurred: ' . $e->getMessage()
    ], 500);
}
