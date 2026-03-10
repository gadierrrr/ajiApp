<?php
/**
 * Trail card extras: difficulty badge, distance, elevation.
 */
$difficulty = $place['difficulty'] ?? null;
$trailDistance = $place['distance_km'] ?? null;
$elevationGain = $place['elevation_gain_m'] ?? null;
$estimatedTime = $place['estimated_time_minutes'] ?? null;

if ($difficulty || $trailDistance || $elevationGain): ?>
<div class="flex items-center gap-3 mt-3 text-xs text-gray-500 dark:text-gray-400">
    <?php if ($difficulty): ?>
    <?php
        $diffColors = [
            'easy' => 'bg-green-100 text-green-700 dark:bg-green-900/30 dark:text-green-400',
            'moderate' => 'bg-yellow-100 text-yellow-700 dark:bg-yellow-900/30 dark:text-yellow-400',
            'difficult' => 'bg-orange-100 text-orange-700 dark:bg-orange-900/30 dark:text-orange-400',
            'expert' => 'bg-red-100 text-red-700 dark:bg-red-900/30 dark:text-red-400',
        ];
        $diffClass = $diffColors[$difficulty] ?? 'bg-gray-100 text-gray-700';
    ?>
    <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full font-medium <?= $diffClass ?>">
        <i data-lucide="mountain" class="w-3.5 h-3.5"></i>
        <?= h(TRAIL_DIFFICULTY_LABELS[$difficulty] ?? ucfirst($difficulty)) ?>
    </span>
    <?php endif; ?>
    <?php if ($trailDistance): ?>
    <span class="inline-flex items-center gap-1">
        <i data-lucide="ruler" class="w-3.5 h-3.5"></i>
        <?= number_format($trailDistance, 1) ?> km
    </span>
    <?php endif; ?>
    <?php if ($elevationGain): ?>
    <span class="inline-flex items-center gap-1">
        <i data-lucide="trending-up" class="w-3.5 h-3.5"></i>
        <?= number_format($elevationGain) ?>m gain
    </span>
    <?php endif; ?>
    <?php if ($estimatedTime): ?>
    <span class="inline-flex items-center gap-1">
        <i data-lucide="clock" class="w-3.5 h-3.5"></i>
        <?php
        $hours = intdiv($estimatedTime, 60);
        $mins = $estimatedTime % 60;
        echo $hours > 0 ? "{$hours}h " : '';
        echo $mins > 0 ? "{$mins}m" : '';
        ?>
    </span>
    <?php endif; ?>
</div>
<?php endif; ?>
