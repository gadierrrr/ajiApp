<?php
/**
 * Weather Service
 * Fetches and caches weather data from Open-Meteo API
 */

require_once __DIR__ . '/db.php';

/**
 * Get weather data for a beach location
 *
 * @param float $lat Latitude
 * @param float $lng Longitude
 * @return array|null Weather data or null on error
 */
function getWeatherForLocation(float $lat, float $lng): ?array {
    // Round coordinates to 2 decimals for caching (approximately 1km accuracy)
    $cacheKey = round($lat, 2) . '_' . round($lng, 2);

    // Check cache first
    $cached = getWeatherFromCache($cacheKey);
    if ($cached !== null) {
        return $cached;
    }

    // Fetch fresh data
    $weather = fetchWeatherFromApi($lat, $lng);
    if ($weather !== null) {
        saveWeatherToCache($cacheKey, $weather);
    }

    return $weather;
}

/**
 * Get weather from cache
 */
function getWeatherFromCache(string $cacheKey): ?array {
    $db = getDb();
    $stmt = $db->prepare("
        SELECT weather_data FROM weather_cache
        WHERE location_key = :key AND expires_at > datetime('now')
    ");
    $stmt->bindValue(':key', $cacheKey, SQLITE3_TEXT);
    $result = $stmt->execute();
    $row = $result->fetchArray(SQLITE3_ASSOC);

    if ($row) {
        return json_decode($row['weather_data'], true);
    }
    return null;
}

/**
 * Save weather to cache
 */
function saveWeatherToCache(string $cacheKey, array $weather): void {
    $db = getDb();

    // Cache for 30 minutes
    $expiresAt = date('Y-m-d H:i:s', strtotime('+30 minutes'));

    $stmt = $db->prepare("
        INSERT OR REPLACE INTO weather_cache (location_key, weather_data, fetched_at, expires_at)
        VALUES (:key, :data, datetime('now'), :expires)
    ");
    $stmt->bindValue(':key', $cacheKey, SQLITE3_TEXT);
    $stmt->bindValue(':data', json_encode($weather), SQLITE3_TEXT);
    $stmt->bindValue(':expires', $expiresAt, SQLITE3_TEXT);
    $stmt->execute();
}

/**
 * Fetch weather from Open-Meteo API
 */
function fetchWeatherFromApi(float $lat, float $lng): ?array {
    $url = sprintf(
        'https://api.open-meteo.com/v1/forecast?' .
        'latitude=%s&longitude=%s' .
        '&current=temperature_2m,relative_humidity_2m,apparent_temperature,' .
        'precipitation,rain,weather_code,cloud_cover,wind_speed_10m,wind_direction_10m,wind_gusts_10m,uv_index' .
        '&hourly=temperature_2m,precipitation_probability,weather_code,uv_index' .
        '&daily=weather_code,temperature_2m_max,temperature_2m_min,sunrise,sunset,uv_index_max,precipitation_probability_max' .
        '&timezone=America/Puerto_Rico' .
        '&temperature_unit=fahrenheit' .
        '&wind_speed_unit=mph' .
        '&forecast_days=3',
        $lat,
        $lng
    );

    $context = stream_context_create([
        'http' => [
            'timeout' => 5,
            'ignore_errors' => true
        ]
    ]);

    $response = @file_get_contents($url, false, $context);
    if ($response === false) {
        error_log("Weather API request failed for $lat, $lng");
        return null;
    }

    $data = json_decode($response, true);
    if (!$data || isset($data['error'])) {
        error_log("Weather API error: " . ($data['reason'] ?? 'Unknown'));
        return null;
    }

    return parseWeatherResponse($data);
}

/**
 * Parse API response into a cleaner format
 */
function parseWeatherResponse(array $data): array {
    $current = $data['current'] ?? [];
    $daily = $data['daily'] ?? [];
    $hourly = $data['hourly'] ?? [];

    // Get next 12 hours of hourly data
    $hourlyForecast = [];
    $currentHour = (int)date('G');
    for ($i = 0; $i < 12 && $i < count($hourly['time'] ?? []); $i++) {
        $hourlyForecast[] = [
            'time' => $hourly['time'][$i] ?? '',
            'temp' => $hourly['temperature_2m'][$i] ?? null,
            'precipitation_probability' => $hourly['precipitation_probability'][$i] ?? 0,
            'weather_code' => $hourly['weather_code'][$i] ?? 0,
            'uv_index' => $hourly['uv_index'][$i] ?? 0
        ];
    }

    // Get 3-day forecast
    $dailyForecast = [];
    for ($i = 0; $i < count($daily['time'] ?? []); $i++) {
        $dailyForecast[] = [
            'date' => $daily['time'][$i] ?? '',
            'weather_code' => $daily['weather_code'][$i] ?? 0,
            'temp_max' => $daily['temperature_2m_max'][$i] ?? null,
            'temp_min' => $daily['temperature_2m_min'][$i] ?? null,
            'sunrise' => $daily['sunrise'][$i] ?? '',
            'sunset' => $daily['sunset'][$i] ?? '',
            'uv_index_max' => $daily['uv_index_max'][$i] ?? 0,
            'precipitation_probability' => $daily['precipitation_probability_max'][$i] ?? 0
        ];
    }

    return [
        'current' => [
            'temperature' => $current['temperature_2m'] ?? null,
            'feels_like' => $current['apparent_temperature'] ?? null,
            'humidity' => $current['relative_humidity_2m'] ?? null,
            'precipitation' => $current['precipitation'] ?? 0,
            'rain' => $current['rain'] ?? 0,
            'weather_code' => $current['weather_code'] ?? 0,
            'cloud_cover' => $current['cloud_cover'] ?? 0,
            'wind_speed' => $current['wind_speed_10m'] ?? 0,
            'wind_direction' => $current['wind_direction_10m'] ?? 0,
            'wind_gusts' => $current['wind_gusts_10m'] ?? 0,
            'uv_index' => $current['uv_index'] ?? 0,
            'description' => getWeatherDescription($current['weather_code'] ?? 0),
            'icon' => getWeatherIcon($current['weather_code'] ?? 0),
            'beach_score' => calculateBeachScore($current)
        ],
        'hourly' => $hourlyForecast,
        'daily' => $dailyForecast,
        'sunrise' => $daily['sunrise'][0] ?? null,
        'sunset' => $daily['sunset'][0] ?? null
    ];
}

/**
 * Get weather description from WMO code
 */
function getWeatherDescription(int $code): string {
    // Try i18n first
    if (function_exists('__')) {
        $key = 'weather.wmo_' . $code;
        $translated = __($key);
        if ($translated !== $key) {
            return $translated;
        }
    }

    // Fallback to hardcoded English
    $descriptions = [
        0 => 'Clear sky',
        1 => 'Mainly clear',
        2 => 'Partly cloudy',
        3 => 'Overcast',
        45 => 'Foggy',
        48 => 'Depositing rime fog',
        51 => 'Light drizzle',
        53 => 'Moderate drizzle',
        55 => 'Dense drizzle',
        61 => 'Slight rain',
        63 => 'Moderate rain',
        65 => 'Heavy rain',
        66 => 'Light freezing rain',
        67 => 'Heavy freezing rain',
        71 => 'Slight snow',
        73 => 'Moderate snow',
        75 => 'Heavy snow',
        77 => 'Snow grains',
        80 => 'Slight rain showers',
        81 => 'Moderate rain showers',
        82 => 'Violent rain showers',
        85 => 'Slight snow showers',
        86 => 'Heavy snow showers',
        95 => 'Thunderstorm',
        96 => 'Thunderstorm with slight hail',
        99 => 'Thunderstorm with heavy hail'
    ];

    return $descriptions[$code] ?? (function_exists('__') ? __('weather.wmo_unknown') : 'Unknown');
}

/**
 * Get weather icon emoji from WMO code
 */
function getWeatherIcon(int $code): string {
    if ($code === 0) return '☀️';
    if ($code <= 3) return '⛅';
    if ($code <= 48) return '🌫️';
    if ($code <= 55) return '🌧️';
    if ($code <= 65) return '🌧️';
    if ($code <= 67) return '🌨️';
    if ($code <= 77) return '❄️';
    if ($code <= 82) return '🌦️';
    if ($code <= 86) return '🌨️';
    if ($code >= 95) return '⛈️';
    return '🌤️';
}

/**
 * Calculate beach score (0-100) based on current conditions
 * Higher = better beach weather
 */
function calculateBeachScore(array $current): int {
    $score = 100;

    // Temperature (ideal: 79-90°F for beach)
    $temp = $current['temperature_2m'] ?? 77;
    if ($temp < 68) $score -= 30;      // Below 68°F - too cold
    elseif ($temp < 75) $score -= 15;  // 68-75°F - cool
    elseif ($temp > 95) $score -= 20;  // Above 95°F - too hot
    elseif ($temp > 90) $score -= 10;  // 90-95°F - warm

    // Rain (bad for beach)
    $rain = $current['rain'] ?? 0;
    if ($rain > 5) $score -= 40;
    elseif ($rain > 1) $score -= 25;
    elseif ($rain > 0) $score -= 10;

    // Wind (high wind not ideal) - thresholds in mph
    $wind = $current['wind_speed_10m'] ?? 0;
    if ($wind > 25) $score -= 30;      // Above 25 mph - very windy
    elseif ($wind > 15) $score -= 15;  // 15-25 mph - windy
    elseif ($wind > 10) $score -= 5;   // 10-15 mph - breezy

    // Cloud cover
    $clouds = $current['cloud_cover'] ?? 0;
    if ($clouds > 80) $score -= 15;
    elseif ($clouds > 50) $score -= 5;

    // UV Index (very high can be dangerous)
    $uv = $current['uv_index'] ?? 5;
    if ($uv > 10) $score -= 10;

    return max(0, min(100, $score));
}

/**
 * Get UV index level and safety message
 */
function getUVLevel(float $uvIndex): array {
    $t = function_exists('__');
    if ($uvIndex < 3) {
        return ['level' => $t ? __('weather.uv_low') : 'Low', 'color' => 'green', 'message' => $t ? __('weather.uv_msg_low') : 'Minimal protection needed'];
    } elseif ($uvIndex < 6) {
        return ['level' => $t ? __('weather.uv_moderate') : 'Moderate', 'color' => 'yellow', 'message' => $t ? __('weather.uv_msg_moderate') : 'Wear sunscreen SPF 30+'];
    } elseif ($uvIndex < 8) {
        return ['level' => $t ? __('weather.uv_high') : 'High', 'color' => 'orange', 'message' => $t ? __('weather.uv_msg_high') : 'Reduce sun exposure 10am-4pm'];
    } elseif ($uvIndex < 11) {
        return ['level' => $t ? __('weather.uv_very_high') : 'Very High', 'color' => 'red', 'message' => $t ? __('weather.uv_msg_very_high') : 'Extra protection essential'];
    } else {
        return ['level' => $t ? __('weather.uv_extreme') : 'Extreme', 'color' => 'purple', 'message' => $t ? __('weather.uv_msg_extreme') : 'Avoid sun exposure'];
    }
}

/**
 * Get wind direction as compass point
 */
function getWindDirection(int $degrees): string {
    $directions = ['N', 'NNE', 'NE', 'ENE', 'E', 'ESE', 'SE', 'SSE', 'S', 'SSW', 'SW', 'WSW', 'W', 'WNW', 'NW', 'NNW'];
    $index = round($degrees / 22.5) % 16;
    return $directions[$index];
}

/**
 * Format sunrise/sunset time
 */
function formatSunTime(string $isoTime): string {
    if (empty($isoTime)) return '';
    return date('g:i A', strtotime($isoTime));
}

/**
 * Get beach day recommendation based on weather
 */
function getBeachRecommendation(array $weather): array {
    $score = $weather['current']['beach_score'] ?? 50;
    $uv = $weather['current']['uv_index'] ?? 5;
    $rain = $weather['current']['rain'] ?? 0;
    $wind = $weather['current']['wind_speed'] ?? 0;
    $t = function_exists('__');

    if ($score >= 80) {
        return [
            'verdict' => $t ? __('weather.perfect_day') : 'Perfect Beach Day',
            'icon' => '🏖️',
            'color' => 'green',
            'message' => $t ? __('weather.perfect_day_msg') : 'Ideal conditions for the beach!'
        ];
    } elseif ($score >= 60) {
        $tips = [];
        if ($uv > 7) $tips[] = $t ? __('weather.good_uv_tip') : 'UV is high - bring sunscreen';
        if ($wind > 10) $tips[] = $t ? __('weather.good_wind_tip') : 'Breezy - great for water sports';

        return [
            'verdict' => $t ? __('weather.good_day') : 'Good Beach Day',
            'icon' => '👍',
            'color' => 'blue',
            'message' => implode('. ', $tips) ?: ($t ? __('weather.good_day_msg') : 'Enjoy the beach!')
        ];
    } elseif ($score >= 40) {
        return [
            'verdict' => $t ? __('weather.fair_conditions') : 'Fair Conditions',
            'icon' => '🤔',
            'color' => 'yellow',
            'message' => $t ? __('weather.fair_conditions_msg') : 'Check the forecast - conditions may vary'
        ];
    } else {
        if ($rain > 1) {
            $message = $t ? __('weather.not_ideal_rain') : 'Rain expected - consider indoor activities';
        } elseif ($wind > 15) {
            $message = $t ? __('weather.not_ideal_wind') : 'High winds - consider indoor activities';
        } else {
            $message = $t ? __('weather.not_ideal_poor') : 'Poor conditions - consider indoor activities';
        }
        return [
            'verdict' => $t ? __('weather.not_ideal') : 'Not Ideal',
            'icon' => '⚠️',
            'color' => 'red',
            'message' => $message
        ];
    }
}

/**
 * Batch fetch weather for multiple beaches efficiently
 * Groups beaches by location zone (rounded coordinates) to minimize API calls
 *
 * @param array $beaches Array of beach records with lat/lng
 * @param int $limit Max number of unique locations to fetch (for performance)
 * @return array Map of beach_id => weather data
 */
function getBatchWeatherForBeaches(array $beaches, int $limit = 20): array {
    if (empty($beaches)) {
        return [];
    }

    // Group beaches by rounded coordinates (cache key)
    $locationGroups = [];
    foreach ($beaches as $beach) {
        $lat = (float)($beach['lat'] ?? 0);
        $lng = (float)($beach['lng'] ?? 0);

        if ($lat === 0.0 || $lng === 0.0) {
            continue;
        }

        // Round to 2 decimals (same as cache key in getWeatherForLocation)
        $cacheKey = round($lat, 2) . '_' . round($lng, 2);

        if (!isset($locationGroups[$cacheKey])) {
            $locationGroups[$cacheKey] = [
                'lat' => $lat,
                'lng' => $lng,
                'beach_ids' => []
            ];
        }
        $locationGroups[$cacheKey]['beach_ids'][] = $beach['id'];
    }

    // Limit number of locations to fetch
    $locationGroups = array_slice($locationGroups, 0, $limit, true);

    // Fetch weather for each unique location
    $weatherMap = [];
    foreach ($locationGroups as $cacheKey => $group) {
        $weather = getWeatherForLocation($group['lat'], $group['lng']);

        // Map weather to all beaches in this location zone
        foreach ($group['beach_ids'] as $beachId) {
            $weatherMap[$beachId] = $weather;
        }
    }

    return $weatherMap;
}
