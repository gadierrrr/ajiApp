<?php
/**
 * API: Beach Match Quiz
 * POST /api/quiz/match.php
 *
 * Receives quiz answers and returns matched beaches with scores
 */

require_once $_SERVER['DOCUMENT_ROOT'] . '/../bootstrap.php';

require_once APP_ROOT . '/inc/session.php';
if (isset($_COOKIE['AJI_APP_SESSION']) && session_status() === PHP_SESSION_NONE) {
    session_cache_limiter('');
    session_start();
}

require_once APP_ROOT . '/inc/db.php';
require_once APP_ROOT . '/inc/helpers.php';

header('Content-Type: application/json');

// Only POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    jsonResponse(['success' => false, 'error' => 'Method not allowed'], 405);
}

// Get input
$input = json_decode(file_get_contents('php://input'), true);

if (!$input) {
    jsonResponse(['success' => false, 'error' => 'Invalid input'], 400);
}

// Extract answers
$activity = $input['activity'] ?? '';
$group = $input['group'] ?? '';
$facilities = $input['facilities'] ?? [];
$crowd = $input['crowd'] ?? '';
$location = $input['location'] ?? '';

// Location region mappings
$regionMunicipalities = [
    'san_juan' => ['San Juan', 'Carolina', 'Loíza', 'Río Grande', 'Cataño'],
    'west' => ['Rincón', 'Aguadilla', 'Isabela', 'Aguada', 'Mayagüez', 'Añasco', 'Cabo Rojo', 'Arecibo', 'Hatillo', 'Quebradillas', 'Camuy', 'Manatí', 'Barceloneta'],
    'east' => ['Fajardo', 'Luquillo', 'Río Grande', 'Ceiba', 'Naguabo', 'Humacao', 'Yabucoa', 'Maunabo', 'Patillas'],
    'south' => ['Ponce', 'Guánica', 'Lajas', 'Peñuelas', 'Juana Díaz', 'Santa Isabel', 'Salinas', 'Guayama', 'Arroyo'],
    'islands' => ['Vieques', 'Culebra']
];

// Fetch all published beaches with their tags and amenities
$beaches = query("SELECT * FROM beaches WHERE publish_status = 'published'");

// Get tags and amenities for each beach
foreach ($beaches as &$beach) {
    $beach['tags'] = array_column(
        query('SELECT tag FROM beach_tags WHERE beach_id = :id', [':id' => $beach['id']]),
        'tag'
    );
    $beach['amenities'] = array_column(
        query('SELECT amenity FROM beach_amenities WHERE beach_id = :id', [':id' => $beach['id']]),
        'amenity'
    );
}
unset($beach);

// Score each beach
$scoredBeaches = [];

foreach ($beaches as $beach) {
    $score = 0;
    $maxScore = 0;
    $matchReasons = [];

    // =====================
    // ACTIVITY SCORING (30 points max)
    // =====================
    $maxScore += 30;
    $tags = $beach['tags'];
    $surf = strtolower($beach['surf'] ?? '');

    switch ($activity) {
        case 'swimming':
            if (in_array('calm-waters', $tags)) {
                $score += 20;
                $matchReasons[] = 'Calm waters';
            }
            if (in_array('swimming', $tags)) {
                $score += 10;
                $matchReasons[] = 'Great for swimming';
            }
            if (in_array('family-friendly', $tags)) {
                $score += 5;
            }
            // Penalize high surf beaches
            if (strpos($surf, 'high') !== false || strpos($surf, 'rough') !== false) {
                $score -= 15;
            }
            break;

        case 'surfing':
            if (in_array('surfing', $tags)) {
                $score += 25;
                $matchReasons[] = 'Known surf spot';
            }
            if (strpos($surf, 'moderate') !== false || strpos($surf, 'high') !== false) {
                $score += 10;
                $matchReasons[] = 'Good waves';
            }
            // Calm beaches not great for surfing
            if (in_array('calm-waters', $tags)) {
                $score -= 10;
            }
            break;

        case 'snorkeling':
            if (in_array('snorkeling', $tags)) {
                $score += 25;
                $matchReasons[] = 'Great snorkeling';
            }
            if (in_array('diving', $tags)) {
                $score += 10;
                $matchReasons[] = 'Clear waters';
            }
            if (in_array('calm-waters', $tags)) {
                $score += 5;
            }
            break;

        case 'relaxing':
            if (in_array('scenic', $tags)) {
                $score += 15;
                $matchReasons[] = 'Scenic views';
            }
            if (in_array('calm-waters', $tags)) {
                $score += 10;
            }
            // Bonus for shade
            if (in_array('shade-structures', $beach['amenities'])) {
                $score += 5;
                $matchReasons[] = 'Has shade';
            }
            break;
    }

    // =====================
    // GROUP SCORING (20 points max)
    // =====================
    $maxScore += 20;

    switch ($group) {
        case 'solo':
            // Solo travelers get points for any beach
            $score += 10;
            if (in_array('scenic', $tags)) {
                $score += 5;
            }
            if (in_array('secluded', $tags)) {
                $score += 5;
                $matchReasons[] = 'Peaceful spot';
            }
            break;

        case 'couple':
            if (in_array('scenic', $tags)) {
                $score += 10;
                $matchReasons[] = 'Romantic setting';
            }
            if (in_array('secluded', $tags)) {
                $score += 10;
            }
            break;

        case 'family':
            if (in_array('family-friendly', $tags)) {
                $score += 15;
                $matchReasons[] = 'Family-friendly';
            }
            if ($beach['safe_for_children']) {
                $score += 5;
            }
            // Families need amenities
            if (in_array('restrooms', $beach['amenities'])) {
                $score += 5;
            }
            if (in_array('parking', $beach['amenities'])) {
                $score += 5;
            }
            break;

        case 'friends':
            if (in_array('popular', $tags)) {
                $score += 10;
                $matchReasons[] = 'Popular hangout';
            }
            // Friends often want food/activities nearby
            if (in_array('food', $beach['amenities'])) {
                $score += 5;
            }
            // Camping beaches great for friend groups
            if (in_array('camping', $tags)) {
                $score += 5;
            }
            break;
    }

    // =====================
    // FACILITIES SCORING (25 points max)
    // =====================
    $maxScore += 25;

    if (in_array('none', $facilities)) {
        // User doesn't need facilities - boost natural/secluded beaches
        $score += 15;
        if (in_array('secluded', $tags)) {
            $score += 10;
        }
    } else {
        $facilityMatches = 0;
        $facilityCount = count($facilities);

        foreach ($facilities as $facility) {
            switch ($facility) {
                case 'restrooms':
                    if (in_array('restrooms', $beach['amenities'])) {
                        $facilityMatches++;
                        $matchReasons[] = 'Has restrooms';
                    }
                    break;
                case 'parking':
                    if (in_array('parking', $beach['amenities'])) {
                        $facilityMatches++;
                        $matchReasons[] = 'Easy parking';
                    }
                    break;
                case 'food':
                    if (in_array('food', $beach['amenities'])) {
                        $facilityMatches++;
                        $matchReasons[] = 'Food nearby';
                    }
                    break;
                case 'lifeguard':
                    if ($beach['has_lifeguard'] || in_array('lifeguard', $beach['amenities'])) {
                        $facilityMatches++;
                        $matchReasons[] = 'Lifeguard on duty';
                    }
                    break;
                case 'shade':
                    if (in_array('shade-structures', $beach['amenities'])) {
                        $facilityMatches++;
                        $matchReasons[] = 'Has shade';
                    }
                    break;
            }
        }

        // Calculate facility score based on how many matched
        if ($facilityCount > 0) {
            $score += round(($facilityMatches / $facilityCount) * 25);
        }
    }

    // =====================
    // CROWD SCORING (15 points max)
    // =====================
    $maxScore += 15;

    switch ($crowd) {
        case 'popular':
            if (in_array('popular', $tags)) {
                $score += 15;
                $matchReasons[] = 'Popular beach';
            }
            // High Google rating indicates popularity
            if ($beach['google_rating'] >= 4.5) {
                $score += 5;
            }
            break;

        case 'moderate':
            // Moderate gets points for not being too secluded or too popular
            if (!in_array('secluded', $tags) && !in_array('popular', $tags)) {
                $score += 15;
            } elseif (in_array('popular', $tags)) {
                $score += 8;
            } else {
                $score += 8;
            }
            break;

        case 'secluded':
            if (in_array('secluded', $tags)) {
                $score += 15;
                $matchReasons[] = 'Secluded beach';
            }
            // Fewer amenities often means less crowded
            if (count($beach['amenities']) <= 2) {
                $score += 5;
            }
            break;
    }

    // =====================
    // LOCATION SCORING (10 points max)
    // =====================
    $maxScore += 10;

    if ($location !== 'anywhere' && isset($regionMunicipalities[$location])) {
        $targetMunicipalities = $regionMunicipalities[$location];
        if (in_array($beach['municipality'], $targetMunicipalities)) {
            $score += 10;
            // Add region-specific reasons
            switch ($location) {
                case 'san_juan':
                    $matchReasons[] = 'Near San Juan';
                    break;
                case 'west':
                    $matchReasons[] = 'West coast';
                    break;
                case 'east':
                    $matchReasons[] = 'East coast';
                    break;
                case 'south':
                    $matchReasons[] = 'South coast';
                    break;
                case 'islands':
                    $matchReasons[] = 'Island getaway';
                    break;
            }
        } else {
            // Slight penalty for wrong region
            $score -= 5;
        }
    } else {
        // "Anywhere" gets full points
        $score += 10;
    }

    // =====================
    // BONUS SCORING
    // =====================

    // Bonus for high Google rating
    if ($beach['google_rating'] >= 4.5) {
        $score += 5;
        if (!in_array('Highly rated', $matchReasons)) {
            $matchReasons[] = 'Highly rated';
        }
    }

    // Bonus for having user reviews
    if ($beach['user_review_count'] > 0) {
        $score += 2;
    }

    // Calculate percentage score (0-100)
    $percentageScore = max(0, min(100, round(($score / $maxScore) * 100)));

    // Remove duplicate reasons and limit to 4
    $matchReasons = array_unique($matchReasons);
    $matchReasons = array_slice($matchReasons, 0, 4);

    $scoredBeaches[] = [
        'id' => $beach['id'],
        'slug' => $beach['slug'],
        'name' => $beach['name'],
        'municipality' => $beach['municipality'],
        'cover_image' => $beach['cover_image'],
        'google_rating' => $beach['google_rating'],
        'score' => $percentageScore,
        'match_reasons' => array_values($matchReasons),
        'tags' => $beach['tags']
    ];
}

// Sort by score descending
usort($scoredBeaches, function($a, $b) {
    return $b['score'] - $a['score'];
});

// Return top 8 matches
$topMatches = array_slice($scoredBeaches, 0, 8);

// Ensure variety in results - if top results are all from same municipality, diversify
$municipalities = [];
$diverseMatches = [];
foreach ($topMatches as $match) {
    if (!isset($municipalities[$match['municipality']]) || $municipalities[$match['municipality']] < 3) {
        $diverseMatches[] = $match;
        $municipalities[$match['municipality']] = ($municipalities[$match['municipality']] ?? 0) + 1;
    }
}

// If we removed too many, add back from remaining
if (count($diverseMatches) < 5) {
    foreach ($topMatches as $match) {
        if (!in_array($match, $diverseMatches)) {
            $diverseMatches[] = $match;
            if (count($diverseMatches) >= 5) break;
        }
    }
}

// Persist a shareable quiz result token
$token = bin2hex(random_bytes(16));
$anonId = (string)($_COOKIE['BF_ANON_ID'] ?? '');
if ($anonId === '') {
    $anonId = uuid();
    $secure = getRequestScheme() === 'https';
    setcookie('BF_ANON_ID', $anonId, [
        'expires' => time() + 180 * 24 * 60 * 60,
        'path' => '/',
        'secure' => $secure,
        'httponly' => false,
        'samesite' => 'Lax',
    ]);
}

$quizId = uuid();
$userId = isAuthenticated() ? (string)($_SESSION['user_id'] ?? '') : null;
if ($userId === '') {
    $userId = null;
}

// Best-effort insert (migration adds quiz_results.token; if missing, it will fail silently and the UI can still function)
@execute("
    INSERT INTO quiz_results (id, user_id, session_id, token, answers, matched_beaches, created_at)
    VALUES (:id, :user_id, :session_id, :token, :answers, :matched_beaches, datetime('now'))
", [
    ':id' => $quizId,
    ':user_id' => $userId,
    ':session_id' => $anonId,
    ':token' => $token,
    ':answers' => json_encode($input),
    ':matched_beaches' => json_encode(array_slice($diverseMatches, 0, 8)),
]);

jsonResponse([
    'success' => true,
    'matches' => array_slice($diverseMatches, 0, 8),
    'results_token' => $token,
    'total_scored' => count($beaches)
]);
