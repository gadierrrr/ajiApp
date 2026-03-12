<?php
/**
 * Beach card extras: conditions meter (sargassum, surf, wind).
 * Included by place-card.php when place_type === 'beach'.
 */
$sargassum = $place['sargassum'] ?? null;
$surf = $place['surf'] ?? null;
$wind = $place['wind'] ?? null;

if ($sargassum || $surf || $wind): ?>
<div class="flex items-center gap-3 mt-3 text-xs text-white/70">
    <?php if ($surf): ?>
    <span class="inline-flex items-center gap-1" title="Surf: <?= h(getConditionLabel('surf', $surf)) ?>">
        <i data-lucide="waves" class="w-3.5 h-3.5"></i>
        <?= h(getConditionLabel('surf', $surf)) ?>
    </span>
    <?php endif; ?>
    <?php if ($wind): ?>
    <span class="inline-flex items-center gap-1" title="Wind: <?= h(getConditionLabel('wind', $wind)) ?>">
        <i data-lucide="wind" class="w-3.5 h-3.5"></i>
        <?= h(getConditionLabel('wind', $wind)) ?>
    </span>
    <?php endif; ?>
    <?php if ($sargassum && $sargassum !== 'none'): ?>
    <span class="inline-flex items-center gap-1 <?= $sargassum === 'heavy' ? 'text-orange-400' : '' ?>" title="Sargassum: <?= h(getConditionLabel('sargassum', $sargassum)) ?>">
        <i data-lucide="leaf" class="w-3.5 h-3.5"></i>
        <?= h(getConditionLabel('sargassum', $sargassum)) ?>
    </span>
    <?php endif; ?>
</div>
<?php endif; ?>
<?php if (!empty($place['has_lifeguard'])): ?>
<div class="mt-2">
    <span class="inline-flex items-center gap-1 text-xs text-green-400">
        <i data-lucide="shield-check" class="w-3.5 h-3.5"></i>
        Lifeguard
    </span>
</div>
<?php endif; ?>
