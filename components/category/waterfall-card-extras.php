<?php
/**
 * Waterfall card extras: height, hike difficulty.
 */
$height = $place['height_meters'] ?? null;
$hikeDifficulty = $place['hike_difficulty'] ?? null;
$hikeDistance = $place['hike_distance_km'] ?? null;

if ($height || $hikeDifficulty || $hikeDistance): ?>
<div class="flex items-center gap-3 mt-3 text-xs text-white/70">
    <?php if ($height): ?>
    <span class="inline-flex items-center gap-1">
        <i data-lucide="arrow-down" class="w-3.5 h-3.5"></i>
        <?= number_format($height) ?>m tall
    </span>
    <?php endif; ?>
    <?php if ($hikeDifficulty): ?>
    <span class="inline-flex items-center gap-1 <?= $hikeDifficulty === 'difficult' || $hikeDifficulty === 'expert' ? 'text-orange-400' : '' ?>">
        <i data-lucide="mountain" class="w-3.5 h-3.5"></i>
        <?= h(TRAIL_DIFFICULTY_LABELS[$hikeDifficulty] ?? ucfirst($hikeDifficulty)) ?> hike
    </span>
    <?php endif; ?>
    <?php if ($hikeDistance): ?>
    <span class="inline-flex items-center gap-1">
        <i data-lucide="ruler" class="w-3.5 h-3.5"></i>
        <?= number_format($hikeDistance, 1) ?> km
    </span>
    <?php endif; ?>
</div>
<?php endif; ?>
