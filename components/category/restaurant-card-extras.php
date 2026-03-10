<?php
/**
 * Restaurant card extras: price range, cuisine, open status.
 */
$priceRange = $place['price_range'] ?? null;
$cuisineType = $place['cuisine_type'] ?? null;

if ($priceRange || $cuisineType): ?>
<div class="flex items-center gap-3 mt-3 text-xs text-gray-500 dark:text-gray-400">
    <?php if ($priceRange): ?>
    <span class="inline-flex items-center gap-1 font-semibold text-green-600 dark:text-green-400">
        <?= h($priceRange) ?>
    </span>
    <?php endif; ?>
    <?php if ($cuisineType): ?>
    <span class="inline-flex items-center gap-1">
        <i data-lucide="chef-hat" class="w-3.5 h-3.5"></i>
        <?= h(ucfirst($cuisineType)) ?>
    </span>
    <?php endif; ?>
    <?php if (!empty($place['outdoor_seating'])): ?>
    <span class="inline-flex items-center gap-1">
        <i data-lucide="sun" class="w-3.5 h-3.5"></i>
        Outdoor
    </span>
    <?php endif; ?>
</div>
<?php endif; ?>
