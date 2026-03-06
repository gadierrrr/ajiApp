<?php
/**
 * Beach Comparison Page
 * Compare up to 3 beaches side-by-side
 */

require_once $_SERVER['DOCUMENT_ROOT'] . '/../bootstrap.php';

require_once APP_ROOT . '/inc/db.php';
require_once APP_ROOT . '/inc/helpers.php';
require_once APP_ROOT . '/inc/constants.php';
require_once APP_ROOT . '/inc/i18n.php';

// Get beach IDs from URL
$beachIds = isset($_GET['beaches']) ? array_filter(array_map('trim', explode(',', $_GET['beaches']))) : [];
$beachIds = array_slice($beachIds, 0, 3); // Max 3 beaches

// Page metadata
$pageTitle = __('compare.title');
$pageDescription = __('compare.description');

// Fetch beaches if IDs provided
$beaches = [];
if (!empty($beachIds)) {
    $placeholders = implode(',', array_fill(0, count($beachIds), '?'));
    $beaches = query("
        SELECT * FROM beaches
        WHERE id IN ($placeholders) AND publish_status = 'published'
    ", $beachIds);

    // Maintain order from URL
    $beachesById = [];
    foreach ($beaches as $beach) {
        $beachesById[$beach['id']] = $beach;
    }
    $beaches = [];
    foreach ($beachIds as $id) {
        if (isset($beachesById[$id])) {
            $beaches[] = $beachesById[$id];
        }
    }

    // Fetch tags and amenities for each beach
    foreach ($beaches as &$beach) {
        $beach['tags'] = array_column(
            query('SELECT tag FROM beach_tags WHERE beach_id = :id', [':id' => $beach['id']]),
            'tag'
        );
        $beach['amenities'] = array_column(
            query('SELECT amenity FROM beach_amenities WHERE beach_id = :id', [':id' => $beach['id']]),
            'amenity'
        );

        // Get review stats
        $reviewStats = queryOne("
            SELECT AVG(rating) as avg_rating, COUNT(*) as count
            FROM beach_reviews
            WHERE beach_id = :id AND status = 'published'
        ", [':id' => $beach['id']]);
        $beach['user_rating'] = $reviewStats['avg_rating'] ? round($reviewStats['avg_rating'], 1) : null;
        $beach['user_review_count'] = $reviewStats['count'];
    }
    unset($beach);

    if (count($beaches) > 1) {
        $beachNames = array_map(fn($b) => $b['name'], $beaches);
        $pageTitle = __('compare.title_vs', ['names' => implode(' vs ', $beachNames)]);
    }
}

// Breadcrumbs
$breadcrumbs = [
    ['name' => __('nav.home'), 'url' => '/'],
    ['name' => __('compare.title')]
];

include APP_ROOT . '/components/header.php';
?>

<main id="main-content" class="min-h-screen bg-brand-darker">
    <!-- Header -->
    <section class="bg-brand-dark border-b border-white/10 py-6">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <!-- Breadcrumbs -->
            <div class="mb-4">
                <?php include APP_ROOT . '/components/breadcrumbs.php'; ?>
            </div>
            <div class="flex items-center justify-between flex-wrap gap-4">
                <div>
                    <h1 class="text-2xl md:text-3xl font-bold text-white"><?= h(__('compare.title')) ?></h1>
                    <p class="text-gray-400 mt-1"><?= h(__('compare.subtitle')) ?></p>
                </div>
                <div class="flex gap-2">
                    <button data-action="openBeachSelector"
                            class="bg-brand-yellow hover:bg-yellow-300 text-brand-darker px-4 py-2 rounded-lg font-semibold transition-colors flex items-center gap-2">
                        <i data-lucide="plus" class="w-4 h-4" aria-hidden="true"></i>
                        <span><?= h(__('compare.add_beach')) ?></span>
                    </button>
                    <?php if (!empty($beaches)): ?>
                    <button data-action="clearComparison"
                            class="bg-white/10 hover:bg-white/20 text-white px-4 py-2 rounded-lg font-medium transition-colors flex items-center gap-2 border border-white/10">
                        <i data-lucide="x" class="w-4 h-4" aria-hidden="true"></i>
                        <span><?= h(__('compare.clear_all')) ?></span>
                    </button>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </section>

    <?php if (empty($beaches)): ?>
    <!-- Empty State -->
    <section class="py-16">
        <div class="max-w-2xl mx-auto px-4 text-center">
            <div class="text-6xl mb-4">⚖️</div>
            <h2 class="text-xl font-semibold text-white mb-2"><?= h(__('compare.no_beaches_selected')) ?></h2>
            <p class="text-gray-400 mb-6"><?= h(__('compare.empty_state_description')) ?></p>
            <button data-action="openBeachSelector"
                    class="bg-brand-yellow hover:bg-yellow-300 text-brand-darker px-6 py-3 rounded-lg font-semibold transition-colors inline-flex items-center gap-2">
                <i data-lucide="plus" class="w-5 h-5" aria-hidden="true"></i>
                <span><?= h(__('compare.add_first_beach')) ?></span>
            </button>
            <p class="text-sm text-gray-500 mt-4">
                <?= h(__('compare.tip_also_add')) ?> <a href="/" class="text-brand-yellow hover:text-yellow-300"><?= h(__('compare.beach_listing')) ?></a> <?= h(__('compare.tip_compare_button')) ?>
            </p>
        </div>
    </section>
    <?php elseif (count($beaches) === 1): ?>
    <!-- Single Beach - Prompt to add more -->
    <section class="py-8">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="bg-brand-yellow/10 border border-brand-yellow/30 rounded-lg p-4 mb-6 flex items-center gap-3">
                <i data-lucide="info" class="w-5 h-5 text-brand-yellow flex-shrink-0" aria-hidden="true"></i>
                <p class="text-gray-300"><?= h(__('compare.add_more_prompt')) ?></p>
                <button data-action="openBeachSelector" class="ml-auto bg-brand-yellow hover:bg-yellow-300 text-brand-darker px-3 py-1.5 rounded-lg text-sm font-semibold transition-colors">
                    <?= h(__('compare.add_beach')) ?>
                </button>
            </div>

            <!-- Show single beach card -->
            <div class="max-w-md">
                <?php $beach = $beaches[0]; ?>
                <div class="beach-detail-card overflow-hidden">
                    <div class="relative">
                        <img src="<?= h($beach['cover_image'] ?: '/images/beaches/placeholder-beach.webp') ?>"
                             alt="<?= h($beach['name']) ?>"
                             class="w-full h-48 object-cover">
                        <button data-action="removeFromComparison" data-action-args='["<?= h($beach['id']) ?>"]'
                                class="absolute top-2 right-2 bg-black/50 hover:bg-black/70 text-white p-1.5 rounded-full border border-white/10"
                                aria-label="<?= h(__('compare.aria_remove')) ?>">
                            <i data-lucide="x" class="w-4 h-4"></i>
                        </button>
                    </div>
                    <div class="p-4">
                        <h3 class="font-bold text-lg text-white"><?= h($beach['name']) ?></h3>
                        <p class="text-gray-400 text-sm"><?= h($beach['municipality']) ?></p>
                    </div>
                </div>
            </div>
        </div>
    </section>
    <?php else: ?>
    <!-- Comparison Table -->
    <section class="py-8">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">

            <!-- Beach Headers -->
            <div class="compare-grid grid grid-cols-1 sm:grid-cols-<?= count($beaches) ?> gap-4 mb-6">
                <?php foreach ($beaches as $beach): ?>
                <div class="beach-detail-card overflow-hidden">
                    <div class="relative">
                        <a href="/beach/<?= h($beach['slug']) ?>">
                            <img src="<?= h($beach['cover_image'] ?: '/images/beaches/placeholder-beach.webp') ?>"
                                 alt="<?= h($beach['name']) ?>"
                                 class="w-full h-40 object-cover hover:opacity-90 transition-opacity">
                        </a>
                        <button data-action="removeFromComparison" data-action-args='["<?= h($beach['id']) ?>"]'
                                class="absolute top-2 right-2 bg-black/50 hover:bg-black/70 text-white p-1.5 rounded-full border border-white/10 transition-colors"
                                aria-label="<?= h(__('compare.aria_remove_named', ['name' => $beach['name']])) ?>">
                            <i data-lucide="x" class="w-4 h-4"></i>
                        </button>
                    </div>
                    <div class="p-4 text-center">
                        <a href="/beach/<?= h($beach['slug']) ?>" class="font-bold text-lg text-white hover:text-brand-yellow transition-colors">
                            <?= h($beach['name']) ?>
                        </a>
                        <p class="text-gray-400 text-sm"><?= h($beach['municipality']) ?></p>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>

            <!-- Comparison Sections -->
            <div class="space-y-4">

                <!-- Ratings -->
                <div class="beach-detail-card overflow-hidden">
                    <div class="bg-white/5 px-4 py-3 border-b border-white/10">
                        <h3 class="font-semibold text-white flex items-center gap-2">
                            <i data-lucide="star" class="w-4 h-4 text-brand-yellow" aria-hidden="true"></i>
                            <?= h(__('compare.section_ratings')) ?>
                        </h3>
                    </div>
                    <div class="compare-grid grid grid-cols-1 sm:grid-cols-<?= count($beaches) ?> divide-y sm:divide-y-0 sm:divide-x divide-white/10">
                        <?php foreach ($beaches as $beach): ?>
                        <div class="p-4 text-center">
                            <!-- Google Rating -->
                            <?php if ($beach['google_rating']): ?>
                            <div class="mb-3">
                                <div class="text-2xl font-bold text-brand-yellow"><?= number_format($beach['google_rating'], 1) ?></div>
                                <div class="text-xs text-gray-500"><?= h(__('compare.google_rating')) ?></div>
                                <?php if ($beach['google_review_count']): ?>
                                <div class="text-xs text-gray-500">(<?= number_format($beach['google_review_count']) ?> <?= h(__('compare.reviews_count')) ?>)</div>
                                <?php endif; ?>
                            </div>
                            <?php else: ?>
                            <div class="mb-3 text-gray-500 text-sm"><?= h(__('compare.no_google_rating')) ?></div>
                            <?php endif; ?>

                            <!-- User Rating -->
                            <?php if ($beach['user_rating']): ?>
                            <div>
                                <div class="text-xl font-bold text-brand-yellow"><?= number_format($beach['user_rating'], 1) ?></div>
                                <div class="text-xs text-gray-500"><?= h(__('compare.community_rating')) ?></div>
                                <div class="text-xs text-gray-500">(<?= $beach['user_review_count'] ?> <?= h(__('compare.reviews_count')) ?>)</div>
                            </div>
                            <?php else: ?>
                            <div class="text-gray-500 text-sm"><?= h(__('compare.no_community_reviews')) ?></div>
                            <?php endif; ?>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>

                <!-- Activities (Tags) -->
                <div class="beach-detail-card overflow-hidden">
                    <div class="bg-white/5 px-4 py-3 border-b border-white/10">
                        <h3 class="font-semibold text-white flex items-center gap-2">
                            <i data-lucide="activity" class="w-4 h-4 text-brand-yellow" aria-hidden="true"></i>
                            <?= h(__('compare.section_activities')) ?>
                        </h3>
                    </div>
                    <div class="compare-grid grid grid-cols-1 sm:grid-cols-<?= count($beaches) ?> divide-y sm:divide-y-0 sm:divide-x divide-white/10">
                        <?php foreach ($beaches as $beach): ?>
                        <div class="p-4">
                            <?php if (!empty($beach['tags'])): ?>
                            <div class="flex flex-wrap gap-1.5 justify-center">
                                <?php foreach ($beach['tags'] as $tag): ?>
                                <span class="inline-block bg-brand-yellow/10 text-brand-yellow px-2 py-1 rounded-full text-xs border border-brand-yellow/20">
                                    <?= h(getTagLabel($tag)) ?>
                                </span>
                                <?php endforeach; ?>
                            </div>
                            <?php else: ?>
                            <div class="text-center text-gray-500 text-sm"><?= h(__('compare.not_specified')) ?></div>
                            <?php endif; ?>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>

                <!-- Amenities -->
                <div class="beach-detail-card overflow-hidden">
                    <div class="bg-white/5 px-4 py-3 border-b border-white/10">
                        <h3 class="font-semibold text-white flex items-center gap-2">
                            <i data-lucide="check-circle" class="w-4 h-4 text-green-400" aria-hidden="true"></i>
                            <?= h(__('compare.section_amenities')) ?>
                        </h3>
                    </div>
                    <div class="compare-grid grid grid-cols-1 sm:grid-cols-<?= count($beaches) ?> divide-y sm:divide-y-0 sm:divide-x divide-white/10">
                        <?php
                        // Collect all amenities across beaches
                        $allAmenities = [];
                        foreach ($beaches as $beach) {
                            $allAmenities = array_merge($allAmenities, $beach['amenities']);
                        }
                        $allAmenities = array_unique($allAmenities);
                        sort($allAmenities);
                        ?>
                        <?php foreach ($beaches as $beach): ?>
                        <div class="p-4">
                            <?php if (!empty($beach['amenities'])): ?>
                            <ul class="space-y-1.5">
                                <?php foreach ($allAmenities as $amenity): ?>
                                <li class="flex items-center gap-2 text-sm <?= in_array($amenity, $beach['amenities']) ? 'text-gray-300' : 'text-gray-600' ?>">
                                    <?php if (in_array($amenity, $beach['amenities'])): ?>
                                    <i data-lucide="check" class="w-4 h-4 text-green-400" aria-hidden="true"></i>
                                    <?php else: ?>
                                    <i data-lucide="x" class="w-4 h-4" aria-hidden="true"></i>
                                    <?php endif; ?>
                                    <span><?= h(getAmenityLabel($amenity)) ?></span>
                                </li>
                                <?php endforeach; ?>
                            </ul>
                            <?php else: ?>
                            <div class="text-center text-gray-500 text-sm"><?= h(__('compare.no_amenities_listed')) ?></div>
                            <?php endif; ?>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>

                <!-- Conditions -->
                <div class="beach-detail-card overflow-hidden">
                    <div class="bg-white/5 px-4 py-3 border-b border-white/10">
                        <h3 class="font-semibold text-white flex items-center gap-2">
                            <i data-lucide="waves" class="w-4 h-4 text-cyan-400" aria-hidden="true"></i>
                            <?= h(__('compare.section_conditions')) ?>
                        </h3>
                    </div>
                    <div class="compare-grid grid grid-cols-1 sm:grid-cols-<?= count($beaches) ?> divide-y sm:divide-y-0 sm:divide-x divide-white/10">
                        <?php foreach ($beaches as $beach): ?>
                        <div class="p-4 space-y-2">
                            <?php if ($beach['sargassum'] || $beach['surf'] || $beach['wind']): ?>
                                <?php if ($beach['sargassum']): ?>
                                <div class="flex justify-between items-center text-sm">
                                    <span class="text-gray-400"><?= h(__('compare.condition_sargassum')) ?></span>
                                    <span class="<?= getConditionClassDark($beach['sargassum']) ?> px-2 py-0.5 rounded text-xs">
                                        <?= h(getConditionLabel('sargassum', $beach['sargassum'])) ?>
                                    </span>
                                </div>
                                <?php endif; ?>
                                <?php if ($beach['surf']): ?>
                                <div class="flex justify-between items-center text-sm">
                                    <span class="text-gray-400"><?= h(__('compare.condition_surf')) ?></span>
                                    <span class="<?= getConditionClassDark($beach['surf']) ?> px-2 py-0.5 rounded text-xs">
                                        <?= h(getConditionLabel('surf', $beach['surf'])) ?>
                                    </span>
                                </div>
                                <?php endif; ?>
                                <?php if ($beach['wind']): ?>
                                <div class="flex justify-between items-center text-sm">
                                    <span class="text-gray-400"><?= h(__('compare.condition_wind')) ?></span>
                                    <span class="<?= getConditionClassDark($beach['wind']) ?> px-2 py-0.5 rounded text-xs">
                                        <?= h(getConditionLabel('wind', $beach['wind'])) ?>
                                    </span>
                                </div>
                                <?php endif; ?>
                            <?php else: ?>
                            <div class="text-center text-gray-500 text-sm"><?= h(__('compare.no_conditions_data')) ?></div>
                            <?php endif; ?>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>

                <!-- Access & Parking -->
                <div class="beach-detail-card overflow-hidden">
                    <div class="bg-white/5 px-4 py-3 border-b border-white/10">
                        <h3 class="font-semibold text-white flex items-center gap-2">
                            <i data-lucide="car" class="w-4 h-4 text-brand-yellow" aria-hidden="true"></i>
                            <?= h(__('compare.section_access')) ?>
                        </h3>
                    </div>
                    <div class="compare-grid grid grid-cols-1 sm:grid-cols-<?= count($beaches) ?> divide-y sm:divide-y-0 sm:divide-x divide-white/10">
                        <?php foreach ($beaches as $beach): ?>
                        <div class="p-4 space-y-2 text-sm">
                            <?php if ($beach['access_label']): ?>
                            <div>
                                <span class="text-gray-500"><?= h(__('compare.access_label')) ?></span>
                                <span class="text-white font-medium ml-1"><?= h($beach['access_label']) ?></span>
                            </div>
                            <?php endif; ?>
                            <?php if ($beach['parking_details']): ?>
                            <div>
                                <span class="text-gray-500"><?= h(__('compare.parking_label')) ?></span>
                                <span class="text-gray-300 ml-1"><?= h($beach['parking_details']) ?></span>
                            </div>
                            <?php endif; ?>
                            <?php if (!$beach['access_label'] && !$beach['parking_details']): ?>
                            <div class="text-center text-gray-500"><?= h(__('compare.no_info_available')) ?></div>
                            <?php endif; ?>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>

                <!-- Actions -->
                <div class="beach-detail-card overflow-hidden">
                    <div class="compare-grid compare-actions grid grid-cols-1 sm:grid-cols-<?= count($beaches) ?> divide-y sm:divide-y-0 sm:divide-x divide-white/10">
                        <?php foreach ($beaches as $beach): ?>
                        <div class="p-4 text-center">
                            <a href="/beach/<?= h($beach['slug']) ?>"
                               class="inline-block bg-brand-yellow hover:bg-yellow-300 text-brand-darker px-4 py-2 rounded-lg font-semibold transition-colors">
                                <?= h(__('compare.view_details')) ?>
                            </a>
                            <a href="<?= h(getDirectionsUrl($beach)) ?>"
                               target="_blank"
                               class="inline-block ml-2 bg-white/10 hover:bg-white/20 text-white px-4 py-2 rounded-lg font-medium transition-colors border border-white/10">
                                <?= h(__('compare.directions')) ?>
                            </a>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>

            </div>
        </div>
    </section>
    <?php endif; ?>
</main>

<!-- Beach Selector Modal -->
<div id="beach-selector-modal" class="fixed inset-0 bg-black/70 backdrop-blur-sm z-50 hidden items-center justify-center p-4"
     role="dialog" aria-modal="true" aria-labelledby="selector-title" data-action="closeBeachSelector">
    <div class="bg-brand-dark rounded-xl shadow-2xl max-w-lg w-full max-h-[80vh] flex flex-col border border-white/10" data-action-stop data-action="noop" data-on="click">
        <div class="border-b border-white/10 px-6 py-4 flex items-center justify-between flex-shrink-0">
            <h2 id="selector-title" class="text-lg font-semibold text-white"><?= h(__('compare.modal_title')) ?></h2>
            <button data-action="closeBeachSelector" class="text-gray-400 hover:text-white p-1" aria-label="<?= h(__('compare.aria_close')) ?>">
                <i data-lucide="x" class="w-5 h-5"></i>
            </button>
        </div>

        <div class="p-4 border-b border-white/10 flex-shrink-0">
            <div class="relative">
                <i data-lucide="search" class="w-5 h-5 text-gray-500 absolute left-3 top-1/2 -translate-y-1/2" aria-hidden="true"></i>
                <input type="text" id="beach-search" placeholder="<?= h(__('compare.search_placeholder')) ?>"
                       class="w-full pl-10 pr-4 py-2 bg-white/5 border border-white/10 rounded-lg text-white placeholder-gray-500 focus:ring-2 focus:ring-brand-yellow/50 focus:border-brand-yellow/50"
                       data-action="filterBeaches" data-action-args='["__this__"]' data-on="input"
                       aria-label="<?= h(__('compare.search_placeholder')) ?>">
            </div>
        </div>

        <div id="beach-list" class="overflow-y-auto flex-1 p-2">
            <!-- Beaches loaded via JS -->
            <div class="text-center py-8 text-gray-500"><?= h(__('compare.loading_beaches')) ?></div>
        </div>
    </div>
</div>

<script <?= cspNonceAttr() ?>>
// Current comparison beaches
let comparisonBeaches = <?= json_encode(array_column($beaches, 'id')) ?>;
const MAX_COMPARE = 3;

// Translated strings for JS
const COMPARE_STRINGS = <?= json_encode([
    'no_beaches_found' => __('compare.no_beaches_found'),
    'max_alert' => __('compare.max_alert'),
    'max_alert_js' => __('compare.max_alert_js'),
]) ?>;

// All beaches for selector
let allBeaches = [];

// Load all beaches for selector
async function loadAllBeaches() {
    try {
        const response = await fetch('/api/beaches.php?format=json&limit=500');
        const data = await response.json();
        allBeaches = data.data || data.beaches || [];
        renderBeachList();
    } catch (error) {
        console.error('Failed to load beaches:', error);
    }
}

function renderBeachList(filter = '') {
    const container = document.getElementById('beach-list');
    const filterLower = filter.toLowerCase();

    const filtered = allBeaches.filter(beach => {
        if (comparisonBeaches.includes(beach.id)) return false;
        if (!filter) return true;
        return beach.name.toLowerCase().includes(filterLower) ||
               beach.municipality.toLowerCase().includes(filterLower);
    });

    if (filtered.length === 0) {
        container.innerHTML = '<div class="text-center py-8 text-gray-500">' + escapeHtml(COMPARE_STRINGS.no_beaches_found) + '</div>';
        return;
    }

    container.innerHTML = filtered.slice(0, 50).map(beach => `
        <button data-action="addToComparison" data-action-args='["${beach.id}"]'
                class="w-full flex items-center gap-3 p-3 hover:bg-white/5 rounded-lg transition-colors text-left"
                ${comparisonBeaches.length >= MAX_COMPARE ? 'disabled' : ''}>
            <img src="${beach.cover_image || '/images/beaches/placeholder-beach.webp'}"
                 alt="${escapeHtml(beach.name)}" class="w-12 h-12 rounded-lg object-cover flex-shrink-0">
            <div class="flex-1 min-w-0">
                <div class="font-medium text-white truncate">${escapeHtml(beach.name)}</div>
                <div class="text-sm text-gray-400">${escapeHtml(beach.municipality)}</div>
            </div>
            ${beach.google_rating ? `<div class="text-brand-yellow text-sm">★ ${beach.google_rating.toFixed(1)}</div>` : ''}
        </button>
    `).join('');

    if (comparisonBeaches.length >= MAX_COMPARE) {
        container.innerHTML = '<div class="text-center py-4 text-brand-yellow bg-brand-yellow/10 border border-brand-yellow/30 rounded-lg mb-2">' + escapeHtml(COMPARE_STRINGS.max_alert) + '</div>' + container.innerHTML;
    }
}

function filterBeaches(query) {
    renderBeachList(query);
}

function escapeHtml(str) {
    const div = document.createElement('div');
    div.textContent = str;
    return div.innerHTML;
}

function openBeachSelector() {
    document.getElementById('beach-selector-modal').classList.remove('hidden');
    document.getElementById('beach-selector-modal').classList.add('flex');
    document.body.style.overflow = 'hidden';
    document.getElementById('beach-search').value = '';
    document.getElementById('beach-search').focus();

    if (allBeaches.length === 0) {
        loadAllBeaches();
    } else {
        renderBeachList();
    }

    if (typeof lucide !== 'undefined') lucide.createIcons();
}

function closeBeachSelector() {
    document.getElementById('beach-selector-modal').classList.add('hidden');
    document.getElementById('beach-selector-modal').classList.remove('flex');
    document.body.style.overflow = '';
}

function addToComparison(beachId) {
    if (comparisonBeaches.length >= MAX_COMPARE) {
        alert(COMPARE_STRINGS.max_alert_js);
        return;
    }

    if (!comparisonBeaches.includes(beachId)) {
        comparisonBeaches.push(beachId);
        updateComparisonUrl();
    }
}

function removeFromComparison(beachId) {
    comparisonBeaches = comparisonBeaches.filter(id => id !== beachId);
    updateComparisonUrl();
}

function clearComparison() {
    comparisonBeaches = [];
    updateComparisonUrl();
}

function updateComparisonUrl() {
    if (comparisonBeaches.length === 0) {
        window.location.href = '/compare';
    } else {
        window.location.href = '/compare?beaches=' + comparisonBeaches.join(',');
    }
}

// Keyboard shortcuts
document.addEventListener('keydown', (e) => {
    if (e.key === 'Escape') closeBeachSelector();
});

// Initialize
document.addEventListener('DOMContentLoaded', () => {
    if (typeof lucide !== 'undefined') lucide.createIcons();
});
</script>

<?php include APP_ROOT . '/components/footer.php'; ?>
