<?php
/**
 * Weather Widget Component
 *
 * @param array $weather - Weather data from getWeatherForLocation()
 * @param string $size - 'compact', 'medium', 'full'
 */

require_once __DIR__ . '/../inc/weather.php';

$weather = $weather ?? null;
$size = $size ?? 'compact';

if (!$weather || !isset($weather['current'])) {
    // Show placeholder if no weather data
    if ($size !== 'compact') {
        $unavailMsg = function_exists('__') ? __('weather.unavailable') : 'Weather unavailable';
        echo '<div class="weather-widget weather-error text-sm text-gray-400">' . h($unavailMsg) . '</div>';
    }
    return;
}

$current = $weather['current'];
$recommendation = getBeachRecommendation($weather);
$uvLevel = getUVLevel($current['uv_index'] ?? 0);
$windDir = getWindDirection($current['wind_direction'] ?? 0);
?>

<?php if ($size === 'compact'): ?>
<!-- Compact: Just temp and icon for beach cards -->
<div class="weather-widget weather-compact flex items-center gap-1.5 text-sm">
    <span class="weather-icon" aria-hidden="true"><?= $current['icon'] ?></span>
    <span class="weather-temp font-medium"><?= round($current['temperature']) ?>°F</span>
</div>

<?php elseif ($size === 'sidebar'): ?>
<!-- Sidebar: Compact but with recommendation for detail page sidebar -->
<div class="weather-widget weather-sidebar">
    <div class="flex items-center justify-between mb-3">
        <div class="flex items-center gap-2">
            <span class="text-2xl"><?= $recommendation['icon'] ?></span>
            <div>
                <div class="font-semibold text-white text-sm"><?= h($recommendation['verdict']) ?></div>
                <div class="text-xs text-gray-500"><?= h($current['description']) ?></div>
            </div>
        </div>
        <div class="text-right">
            <div class="text-2xl font-bold text-brand-yellow"><?= round($current['temperature']) ?>°</div>
        </div>
    </div>
    <div class="grid grid-cols-3 gap-2 text-center text-xs">
        <div class="bg-white/5 rounded p-2">
            <div class="text-gray-400"><?= h(function_exists('__') ? __('weather.wind') : 'Wind') ?></div>
            <div class="text-white font-medium"><?= round($current['wind_speed']) ?><?= h(function_exists('__') ? __('weather_ui.mph') : 'mph') ?></div>
        </div>
        <div class="bg-white/5 rounded p-2">
            <div class="text-gray-400"><?= h(function_exists('__') ? __('weather_ui.uv_label') : 'UV') ?></div>
            <div class="text-brand-yellow font-medium"><?= $uvLevel['level'] ?></div>
        </div>
        <div class="bg-white/5 rounded p-2">
            <div class="text-gray-400"><?= h(function_exists('__') ? __('weather.humidity') : 'Humidity') ?></div>
            <div class="text-white font-medium"><?= round($current['humidity']) ?>%</div>
        </div>
    </div>
    <?php if ($current['uv_index'] >= 6): ?>
    <div class="mt-2 p-2 bg-orange-500/10 border border-orange-500/20 rounded text-xs text-orange-400">
        ⚠️ <?= h($uvLevel['message']) ?>
    </div>
    <?php endif; ?>
</div>

<?php elseif ($size === 'medium'): ?>
<!-- Medium: For beach card expanded view -->
<div class="weather-widget weather-medium bg-gradient-to-r from-blue-50 to-sky-50 rounded-lg p-3">
    <div class="flex items-center justify-between">
        <div class="flex items-center gap-2">
            <span class="text-2xl"><?= $current['icon'] ?></span>
            <div>
                <div class="font-semibold text-lg"><?= round($current['temperature']) ?>°F</div>
                <div class="text-xs text-gray-500"><?= h($current['description']) ?></div>
            </div>
        </div>
        <div class="text-right text-sm">
            <div class="flex items-center gap-1 text-gray-600">
                <span>💨</span>
                <span><?= round($current['wind_speed']) ?> <?= h(function_exists('__') ? __('weather_ui.mph') : 'mph') ?> <?= $windDir ?></span>
            </div>
            <div class="flex items-center gap-1 text-gray-600">
                <span>☀️</span>
                <span><?= h(function_exists('__') ? __('weather_ui.uv_label') : 'UV') ?> <?= round($current['uv_index']) ?></span>
            </div>
        </div>
    </div>

    <!-- Beach Score Bar -->
    <div class="mt-2 pt-2 border-t border-blue-100">
        <div class="flex items-center justify-between text-xs mb-1">
            <span class="text-gray-600"><?= h(function_exists('__') ? __('weather_ui.beach_score') : 'Beach Score') ?></span>
            <span class="font-medium text-<?= $recommendation['color'] ?>-600"><?= $current['beach_score'] ?>%</span>
        </div>
        <div class="h-2 bg-gray-200 rounded-full overflow-hidden">
            <div class="h-full bg-<?= $recommendation['color'] ?>-500 rounded-full transition-all"
                 style="width: <?= $current['beach_score'] ?>%"></div>
        </div>
    </div>
</div>

<?php else: ?>
<!-- Full: For beach detail page - Dark Theme -->
<?php
$_t = function_exists('__');
$_feelsLabel = $_t ? __('weather.feels') : 'Feels';
$_currentLabel = $_t ? __('weather_ui.current_label') : 'Current';
$_windSuffix = $_t ? __('weather_ui.wind_label') : 'wind';
$_humidityLabel = $_t ? __('weather.humidity') : 'Humidity';
$_sunriseLabel = $_t ? __('weather.sunrise') : 'Sunrise';
$_sunsetLabel = $_t ? __('weather.sunset') : 'Sunset';
$_forecastLabel = $_t ? __('weather.three_day_forecast') : '3-Day Forecast';
$_todayLabel = $_t ? __('weather.today') : 'Today';
?>
<div class="weather-widget weather-full beach-detail-card overflow-hidden">
    <!-- Header with recommendation - Dark background -->
    <div class="bg-[#1c2128] p-5 border-b border-white/10">
        <div class="flex items-center justify-between">
            <div>
                <div class="flex items-center gap-2 text-lg font-semibold text-white">
                    <span><?= $recommendation['icon'] ?></span>
                    <span><?= h($recommendation['verdict']) ?></span>
                </div>
                <p class="text-sm text-gray-400 mt-1"><?= h($recommendation['message']) ?></p>
            </div>
            <div class="text-right">
                <div class="text-4xl font-bold text-brand-yellow"><?= round($current['temperature']) ?>°</div>
                <div class="text-sm text-gray-400"><?= h($_feelsLabel) ?> <?= round($current['feels_like']) ?>°</div>
            </div>
        </div>
    </div>

    <!-- Current conditions grid -->
    <div class="p-4">
        <div class="weather-conditions-grid grid grid-cols-2 gap-2 sm:gap-3">
            <!-- Weather -->
            <div class="text-center p-3 bg-white/5 rounded-lg border border-white/5">
                <div class="text-2xl mb-1"><?= $current['icon'] ?></div>
                <div class="text-sm font-medium text-white"><?= h($current['description']) ?></div>
                <div class="text-xs text-gray-500"><?= h($_currentLabel) ?></div>
            </div>

            <!-- Wind -->
            <div class="text-center p-3 bg-white/5 rounded-lg border border-white/5">
                <div class="text-2xl mb-1">💨</div>
                <div class="text-sm font-medium text-white"><?= round($current['wind_speed']) ?> <?= h(function_exists('__') ? __('weather_ui.mph') : 'mph') ?></div>
                <div class="text-xs text-gray-500"><?= $windDir ?> <?= h($_windSuffix) ?></div>
            </div>

            <!-- UV Index -->
            <div class="text-center p-3 bg-white/5 rounded-lg border border-white/5">
                <div class="text-2xl mb-1">☀️</div>
                <div class="text-sm font-medium text-brand-yellow"><?= $uvLevel['level'] ?></div>
                <div class="text-xs text-gray-500"><?= h(function_exists('__') ? __('weather_ui.uv_label') : 'UV') ?> <?= round($current['uv_index']) ?></div>
            </div>

            <!-- Humidity -->
            <div class="text-center p-3 bg-white/5 rounded-lg border border-white/5">
                <div class="text-2xl mb-1">💧</div>
                <div class="text-sm font-medium text-white"><?= round($current['humidity']) ?>%</div>
                <div class="text-xs text-gray-500"><?= h($_humidityLabel) ?></div>
            </div>
        </div>

        <!-- UV Safety Message -->
        <?php if ($current['uv_index'] >= 6): ?>
        <div class="mt-4 p-3 bg-orange-500/10 border border-orange-500/20 rounded-lg">
            <div class="flex items-center gap-2 text-sm text-orange-400">
                <span>⚠️</span>
                <span><?= h($uvLevel['message']) ?></span>
            </div>
        </div>
        <?php endif; ?>

        <!-- Sun times -->
        <?php if (!empty($weather['sunrise']) && !empty($weather['sunset'])): ?>
        <div class="mt-4 flex items-center justify-center gap-6 text-sm text-gray-400">
            <div class="flex items-center gap-1">
                <span>🌅</span>
                <span><?= h($_sunriseLabel) ?> <?= formatSunTime($weather['sunrise']) ?></span>
            </div>
            <div class="flex items-center gap-1">
                <span>🌇</span>
                <span><?= h($_sunsetLabel) ?> <?= formatSunTime($weather['sunset']) ?></span>
            </div>
        </div>
        <?php endif; ?>
    </div>

    <!-- 3-day forecast -->
    <?php if (!empty($weather['daily'])): ?>
    <div class="border-t border-white/10 p-4">
        <h4 class="text-sm font-semibold text-gray-400 mb-3"><?= h($_forecastLabel) ?></h4>
        <div class="grid grid-cols-3 gap-2">
            <?php foreach (array_slice($weather['daily'], 0, 3) as $i => $day): ?>
            <?php
                $dayNum = date('w', strtotime($day['date'])); // 0=Sun, 1=Mon, ...
                $dayKeys = ['day_sun', 'day_mon', 'day_tue', 'day_wed', 'day_thu', 'day_fri', 'day_sat'];
                $dayLabel = function_exists('__') ? __('weather_ui.' . $dayKeys[$dayNum]) : date('D', strtotime($day['date']));
            ?>
            <div class="text-center p-2 <?= $i === 0 ? 'bg-brand-yellow/10 border border-brand-yellow/20 rounded-lg' : '' ?>">
                <div class="text-xs font-medium <?= $i === 0 ? 'text-brand-yellow' : 'text-gray-500' ?>">
                    <?= $i === 0 ? h($_todayLabel) : h($dayLabel) ?>
                </div>
                <div class="text-xl my-1"><?= getWeatherIcon($day['weather_code']) ?></div>
                <div class="text-sm font-medium text-white">
                    <?= round($day['temp_max']) ?>° / <?= round($day['temp_min']) ?>°
                </div>
                <?php if ($day['precipitation_probability'] > 30): ?>
                <div class="text-xs text-cyan-400 mt-1">
                    🌧 <?= $day['precipitation_probability'] ?>%
                </div>
                <?php endif; ?>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
    <?php endif; ?>
</div>
<?php endif; ?>
