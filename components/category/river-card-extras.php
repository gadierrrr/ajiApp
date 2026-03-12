<?php
/**
 * River card extras: water clarity, current strength.
 */
$waterClarity = $place['water_clarity'] ?? null;
$currentStrength = $place['current_strength'] ?? null;
$swimmable = $place['swimmable'] ?? null;

if ($waterClarity || $currentStrength || $swimmable !== null): ?>
<div class="flex items-center gap-3 mt-3 text-xs text-white/70">
    <?php if ($waterClarity): ?>
    <span class="inline-flex items-center gap-1">
        <i data-lucide="droplets" class="w-3.5 h-3.5"></i>
        <?= h(WATER_CLARITY_LABELS[$waterClarity] ?? ucfirst($waterClarity)) ?>
    </span>
    <?php endif; ?>
    <?php if ($currentStrength): ?>
    <span class="inline-flex items-center gap-1">
        <i data-lucide="waves" class="w-3.5 h-3.5"></i>
        <?= h(CURRENT_STRENGTH_LABELS[$currentStrength] ?? ucfirst($currentStrength)) ?>
    </span>
    <?php endif; ?>
    <?php if ($swimmable): ?>
    <span class="inline-flex items-center gap-1 text-green-400">
        <i data-lucide="check-circle" class="w-3.5 h-3.5"></i>
        Swimmable
    </span>
    <?php endif; ?>
</div>
<?php endif; ?>
