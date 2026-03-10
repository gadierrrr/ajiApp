<?php
/**
 * Photo spot card extras: best light conditions.
 */
$bestLight = $place['best_light'] ?? null;
$bestTimeOfDay = $place['best_time_of_day'] ?? null;
$tripodRecommended = $place['tripod_recommended'] ?? null;
$droneAllowed = $place['drone_allowed'] ?? null;

if ($bestLight || $bestTimeOfDay || $tripodRecommended): ?>
<div class="flex items-center gap-3 mt-3 text-xs text-gray-500 dark:text-gray-400">
    <?php if ($bestLight): ?>
    <span class="inline-flex items-center gap-1">
        <i data-lucide="sun" class="w-3.5 h-3.5"></i>
        <?= h(BEST_LIGHT_LABELS[$bestLight] ?? ucfirst($bestLight)) ?>
    </span>
    <?php endif; ?>
    <?php if ($bestTimeOfDay): ?>
    <span class="inline-flex items-center gap-1">
        <i data-lucide="clock" class="w-3.5 h-3.5"></i>
        <?= h($bestTimeOfDay) ?>
    </span>
    <?php endif; ?>
    <?php if ($tripodRecommended): ?>
    <span class="inline-flex items-center gap-1">
        <i data-lucide="triangle" class="w-3.5 h-3.5"></i>
        Tripod
    </span>
    <?php endif; ?>
    <?php if ($droneAllowed): ?>
    <span class="inline-flex items-center gap-1 text-green-600 dark:text-green-400">
        <i data-lucide="plane" class="w-3.5 h-3.5"></i>
        Drone OK
    </span>
    <?php endif; ?>
</div>
<?php endif; ?>
