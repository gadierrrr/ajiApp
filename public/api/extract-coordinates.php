<?php
/**
 * API: Extract Coordinates from Google Maps URL
 *
 * Parses various Google Maps URL formats to extract coordinates.
 * Supports:
 * - Direct coordinate URLs (@lat,lng)
 * - Place URLs with coordinates
 * - Place IDs (uses Google Places API)
 * - Short URLs (follows redirects)
 */

require_once $_SERVER['DOCUMENT_ROOT'] . '/../bootstrap.php';

// Session must be configured before starting
require_once APP_ROOT . '/inc/session.php';
session_start();

require_once APP_ROOT . '/inc/db.php';
require_once APP_ROOT . '/inc/helpers.php';
require_once APP_ROOT . '/inc/admin.php';

// CORS headers for AJAX requests
header('Content-Type: application/json');

// Only accept POST requests
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed']);
    exit;
}

// Require admin authentication
if (!isAdmin()) {
    http_response_code(403);
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}

// Get the URL from POST data
$input = json_decode(file_get_contents('php://input'), true) ?: [];
$csrfToken = $input['csrf_token'] ?? '';
$url = trim($input['url'] ?? '');

if (!validateCsrf($csrfToken)) {
    http_response_code(403);
    echo json_encode(['error' => 'Invalid CSRF token']);
    exit;
}

if (empty($url)) {
    http_response_code(400);
    echo json_encode(['error' => 'URL is required']);
    exit;
}

// Google Maps API Key
$apiKey = envRequire('GOOGLE_MAPS_API_KEY');

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
    // Pattern 1: @lat,lng,zoom format (most common)
    // Example: https://www.google.com/maps/@18.4719,-66.0997,15z
    // Example: https://www.google.com/maps/place/Playa+Flamenco/@18.3263,-65.3206,15z
    if (preg_match('/@(-?\d+\.?\d*),(-?\d+\.?\d*)/', $url, $matches)) {
        return [
            'lat' => (float) $matches[1],
            'lng' => (float) $matches[2],
            'source' => 'url_coordinates'
        ];
    }

    // Pattern 2: ?ll=lat,lng or &ll=lat,lng
    if (preg_match('/[?&]ll=(-?\d+\.?\d*),(-?\d+\.?\d*)/', $url, $matches)) {
        return [
            'lat' => (float) $matches[1],
            'lng' => (float) $matches[2],
            'source' => 'll_parameter'
        ];
    }

    // Pattern 3: /dir/lat,lng/ or destination=lat,lng
    if (preg_match('/(?:dir|destination)[\/=](-?\d+\.?\d*),(-?\d+\.?\d*)/', $url, $matches)) {
        return [
            'lat' => (float) $matches[1],
            'lng' => (float) $matches[2],
            'source' => 'direction_coordinates'
        ];
    }

    // Pattern 4: !3d and !4d data parameters (lat and lng)
    // Example: !3d18.3263447!4d-65.3205611
    if (preg_match('/!3d(-?\d+\.?\d*)!4d(-?\d+\.?\d*)/', $url, $matches)) {
        return [
            'lat' => (float) $matches[1],
            'lng' => (float) $matches[2],
            'source' => 'data_3d4d'
        ];
    }

    return null;
}

/**
 * Extract Place ID from URL
 */
function extractPlaceId($url) {
    // Pattern 1: place_id= parameter
    if (preg_match('/place_id[=:]([A-Za-z0-9_-]+)/', $url, $matches)) {
        return $matches[1];
    }

    // Pattern 2: !1s prefix in data (Place ID starts with ChIJ usually)
    // Example: !1sChIJxxxxxx
    if (preg_match('/!1s(ChIJ[A-Za-z0-9_-]+)/', $url, $matches)) {
        return $matches[1];
    }

    // Pattern 3: /place/.../ format - extract CID or place reference
    // The hex ID after 0x is a CID, not directly usable

    return null;
}

/**
 * Get coordinates from Place ID via Google Places API
 */
function getPlaceDetails($placeId, $apiKey) {
    $url = "https://maps.googleapis.com/maps/api/place/details/json"
         . "?place_id=" . urlencode($placeId)
         . "&fields=name,geometry,formatted_address"
         . "&key=" . $apiKey;

    $response = file_get_contents($url);
    if ($response === false) {
        return ['error' => 'API request failed'];
    }

    $data = json_decode($response, true);

    if ($data['status'] === 'OK' && !empty($data['result'])) {
        $result = $data['result'];
        return [
            'lat' => $result['geometry']['location']['lat'],
            'lng' => $result['geometry']['location']['lng'],
            'name' => $result['name'] ?? '',
            'address' => $result['formatted_address'] ?? '',
            'place_id' => $placeId,
            'source' => 'place_api'
        ];
    }

    return ['error' => $data['status'] . ': ' . ($data['error_message'] ?? 'Place not found')];
}

/**
 * Search for Place ID using coordinates (reverse lookup)
 */
function findPlaceIdByCoords($lat, $lng, $apiKey) {
    // Use nearby search to find the closest place
    $url = "https://maps.googleapis.com/maps/api/place/nearbysearch/json"
         . "?location=" . $lat . "," . $lng
         . "&radius=50"
         . "&key=" . $apiKey;

    $response = file_get_contents($url);
    if ($response === false) {
        return null;
    }

    $data = json_decode($response, true);

    if ($data['status'] === 'OK' && !empty($data['results'])) {
        return $data['results'][0]['place_id'] ?? null;
    }

    return null;
}

/**
 * Extract place name from URL and search for it
 */
function searchPlaceByName($url, $apiKey) {
    // Extract place name from URL path
    // Example: /maps/place/Playa+Flamenco/ -> "Playa Flamenco"
    if (preg_match('/\/place\/([^\/\@]+)/', $url, $matches)) {
        $placeName = urldecode($matches[1]);
        $placeName = str_replace('+', ' ', $placeName);

        // Add "Puerto Rico" to help narrow down results
        $searchQuery = $placeName . ' Puerto Rico beach';

        $apiUrl = "https://maps.googleapis.com/maps/api/place/findplacefromtext/json"
                . "?input=" . urlencode($searchQuery)
                . "&inputtype=textquery"
                . "&fields=name,geometry,formatted_address,place_id"
                . "&locationbias=circle:100000@18.2208,-66.5901"
                . "&key=" . $apiKey;

        $response = file_get_contents($apiUrl);
        if ($response === false) {
            return ['error' => 'API request failed'];
        }

        $data = json_decode($response, true);

        if ($data['status'] === 'OK' && !empty($data['candidates'])) {
            $place = $data['candidates'][0];
            return [
                'lat' => $place['geometry']['location']['lat'],
                'lng' => $place['geometry']['location']['lng'],
                'name' => $place['name'] ?? '',
                'address' => $place['formatted_address'] ?? '',
                'place_id' => $place['place_id'] ?? '',
                'source' => 'text_search',
                'search_query' => $placeName
            ];
        }

        return ['error' => 'Place not found: ' . $placeName];
    }

    return ['error' => 'Could not extract place name from URL'];
}

// Main logic
$result = null;
$extractedPlaceId = null;

// Handle short URLs (goo.gl, maps.app.goo.gl)
if (preg_match('/goo\.gl|maps\.app/', $url)) {
    $url = getRedirectedUrl($url);
    if (!$url) {
        http_response_code(400);
        echo json_encode(['error' => 'Could not resolve short URL']);
        exit;
    }
}

// Try to extract Place ID from URL first (we want to preserve this)
$extractedPlaceId = extractPlaceId($url);

// Try to extract coordinates directly from URL
$result = extractCoordsFromUrl($url);

// If we have coordinates from URL but also have a Place ID, add it to result
if ($result && $extractedPlaceId) {
    $result['place_id'] = $extractedPlaceId;
}

// If no direct coordinates, try Place ID lookup
if (!$result && $extractedPlaceId) {
    $result = getPlaceDetails($extractedPlaceId, $apiKey);
}

// If still no result, try searching by place name
if (!$result || isset($result['error'])) {
    $searchResult = searchPlaceByName($url, $apiKey);
    if (!isset($searchResult['error'])) {
        $result = $searchResult;
    } elseif (!$result) {
        $result = $searchResult;
    }
}

// If we have coordinates but no Place ID, try to find one via reverse lookup
if ($result && !isset($result['error']) && empty($result['place_id'])) {
    $foundPlaceId = findPlaceIdByCoords($result['lat'], $result['lng'], $apiKey);
    if ($foundPlaceId) {
        $result['place_id'] = $foundPlaceId;
        $result['place_id_source'] = 'reverse_lookup';
    }
}

// Validate coordinates are in Puerto Rico region
if ($result && !isset($result['error'])) {
    $lat = $result['lat'];
    $lng = $result['lng'];

    // Puerto Rico bounding box (approximate)
    $prMinLat = 17.8;
    $prMaxLat = 18.6;
    $prMinLng = -67.5;
    $prMaxLng = -65.0;

    if ($lat < $prMinLat || $lat > $prMaxLat || $lng < $prMinLng || $lng > $prMaxLng) {
        $result['warning'] = 'Coordinates appear to be outside Puerto Rico region';
    }

    // Round coordinates to reasonable precision (6 decimal places ~11cm)
    $result['lat'] = round($result['lat'], 6);
    $result['lng'] = round($result['lng'], 6);

    echo json_encode([
        'success' => true,
        'data' => $result,
        'original_url' => $url
    ]);
} else {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'error' => $result['error'] ?? 'Could not extract coordinates from URL',
        'original_url' => $url
    ]);
}
