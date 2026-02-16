<?php
/**
 * Best Snorkeling Beaches in Puerto Rico - SEO Landing Page
 * Target keywords: snorkeling puerto rico, best snorkeling beaches puerto rico
 */

require_once $_SERVER['DOCUMENT_ROOT'] . '/../bootstrap.php';

require_once APP_ROOT . '/inc/db.php';
require_once APP_ROOT . '/inc/helpers.php';
require_once APP_ROOT . '/inc/constants.php';
require_once APP_ROOT . '/inc/collection_query.php';
require_once APP_ROOT . '/components/seo-schemas.php';

// Page metadata
$pageTitle = 'Best Snorkeling Beaches in Puerto Rico (2026 Guide)';
$pageDescription = 'Discover the best snorkeling beaches in Puerto Rico for 2026. Crystal-clear waters, vibrant coral reefs, and tropical marine life await at these top snorkeling spots.';
$canonicalUrl = getPublicBaseUrl() . '/best-snorkeling-beaches';

$collectionKey = 'best-snorkeling-beaches';
$collectionAnchorId = 'top-beaches';
$collectionData = fetchCollectionBeaches($collectionKey, collectionFiltersFromRequest($collectionKey, $_GET));
$collectionContext = $collectionData['collection'];
$collectionState = $collectionData['effective_filters'];
$snorkelingBeaches = $collectionData['beaches'];

$userFavorites = [];
if (isAuthenticated()) {
    $favorites = query('SELECT beach_id FROM user_favorites WHERE user_id = :user_id', [':user_id' => $_SESSION['user_id']]) ?: [];
    $userFavorites = array_column($favorites, 'beach_id');
}

// Generate structured data
$extraHead = articleSchema(
    $pageTitle,
    $pageDescription,
    '/best-snorkeling-beaches',
    $snorkelingBeaches[0]['cover_image'] ?? null,
    '2026-01-01'
);
$extraHead .= collectionPageSchema($pageTitle, $pageDescription, $snorkelingBeaches);
$extraHead .= websiteSchema();

// FAQ data
$pageFaqs = [
    [
        'question' => 'Where is the best snorkeling in Puerto Rico?',
        'answer' => 'The best snorkeling in Puerto Rico is found at Culebra Island (especially Tamarindo Beach and Carlos Rosario), Steps Beach in Rincon, Escambron Beach in San Juan, and the beaches of Vieques. These locations offer crystal-clear water, healthy coral reefs, and abundant marine life.'
    ],
    [
        'question' => 'Do I need to bring my own snorkeling gear to Puerto Rico?',
        'answer' => 'While you can bring your own gear, most popular snorkeling beaches have nearby rental shops. Prices typically range from $10-20 per day for mask, snorkel, and fins. For the best fit and hygiene, many visitors prefer to bring their own mask and snorkel.'
    ],
    [
        'question' => 'What marine life can I see snorkeling in Puerto Rico?',
        'answer' => 'Puerto Rico\'s waters are home to sea turtles, tropical fish (parrotfish, angelfish, sergeant majors), rays, octopus, lobster, and various coral species. Manatees are occasionally spotted in certain bays. The best diversity is found around coral reef areas.'
    ],
    [
        'question' => 'When is the best time for snorkeling in Puerto Rico?',
        'answer' => 'The best snorkeling conditions are from December through April when seas are calmest and visibility is highest (often 50-100+ feet). Summer months are also good but may have more rain. Avoid snorkeling after heavy rains when runoff reduces visibility.'
    ],
    [
        'question' => 'Is snorkeling safe in Puerto Rico?',
        'answer' => 'Snorkeling is generally safe in Puerto Rico. Always check water conditions, avoid areas with strong currents, never snorkel alone, and respect marine life. Use reef-safe sunscreen to protect the coral ecosystem. Some beaches have lifeguards during peak hours.'
    ]
];
$extraHead .= faqSchema($pageFaqs);

// Breadcrumbs
$breadcrumbs = [
    ['name' => 'Home', 'url' => '/'],
    ['name' => 'Best Beaches', 'url' => '/best-beaches'],
    ['name' => 'Snorkeling Beaches']
];

$bodyVariant = 'collection-dark';
include APP_ROOT . '/components/header.php';
?>
<?php include APP_ROOT . '/components/collection/explorer.php'; ?>

<div class="collection-legacy-content">

<!-- Quick Navigation -->
<section class="collection-content-nav bg-white border-b">
    <div class="max-w-7xl mx-auto px-4 py-4">
        <div class="flex flex-wrap gap-2 justify-center text-sm">
            <span class="text-gray-500">Jump to:</span>
            <a href="#top-beaches" class="text-amber-700 hover:underline">Top Spots</a>
            <span class="text-gray-300">|</span>
            <a href="#tips" class="text-amber-700 hover:underline">Snorkeling Tips</a>
            <span class="text-gray-300">|</span>
            <a href="#faq" class="text-amber-700 hover:underline">FAQs</a>
            <span class="text-gray-300">|</span>
            <a href="#map" class="text-amber-700 hover:underline">Map</a>
        </div>
    </div>
</section>

<!-- Introduction -->
<section class="py-12 bg-gray-50">
    <div class="max-w-4xl mx-auto px-4">
        <div class="prose prose-lg max-w-none beach-description">
            <p>Puerto Rico offers some of the <strong>Caribbean's best snorkeling</strong> with warm, clear waters year-round. The island's diverse marine ecosystems include coral reefs, seagrass beds, and rocky coastlines teeming with tropical fish and sea turtles.</p>

            <p>Whether you're a beginner looking for calm, shallow waters or an experienced snorkeler seeking vibrant reef systems, these carefully selected beaches offer the best underwater experiences in Puerto Rico.</p>
        </div>
    </div>
</section>

<!-- Snorkeling Tips -->
<section id="tips" class="py-12 bg-gray-50">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <h2 class="text-2xl md:text-3xl font-bold text-gray-900 mb-8 text-center">
            Puerto Rico Snorkeling Tips
        </h2>

        <div class="grid md:grid-cols-2 lg:grid-cols-3 gap-6">
            <div class="bg-white rounded-xl p-6 shadow-md">
                <div class="text-3xl mb-4">🌅</div>
                <h3 class="text-lg font-bold text-gray-900 mb-2">Best Time of Day</h3>
                <p class="text-gray-600 text-sm">Morning hours (8-11 AM) offer the calmest waters and best visibility. Winds typically pick up in the afternoon.</p>
            </div>

            <div class="bg-white rounded-xl p-6 shadow-md">
                <div class="text-3xl mb-4">🧴</div>
                <h3 class="text-lg font-bold text-gray-900 mb-2">Use Reef-Safe Sunscreen</h3>
                <p class="text-gray-600 text-sm">Protect coral reefs by using mineral-based sunscreens without oxybenzone or octinoxate. Many local shops sell reef-safe options.</p>
            </div>

            <div class="bg-white rounded-xl p-6 shadow-md">
                <div class="text-3xl mb-4">🐢</div>
                <h3 class="text-lg font-bold text-gray-900 mb-2">Respect Marine Life</h3>
                <p class="text-gray-600 text-sm">Don't touch coral, chase sea turtles, or stand on reef structures. Keep a respectful distance and observe quietly.</p>
            </div>

            <div class="bg-white rounded-xl p-6 shadow-md">
                <div class="text-3xl mb-4">👥</div>
                <h3 class="text-lg font-bold text-gray-900 mb-2">Never Snorkel Alone</h3>
                <p class="text-gray-600 text-sm">Always snorkel with a buddy. If going solo, choose beaches with lifeguards and let someone know your plans.</p>
            </div>

            <div class="bg-white rounded-xl p-6 shadow-md">
                <div class="text-3xl mb-4">🌊</div>
                <h3 class="text-lg font-bold text-gray-900 mb-2">Check Conditions First</h3>
                <p class="text-gray-600 text-sm">Review wave forecasts and avoid snorkeling after heavy rain. Swell direction affects different beaches differently.</p>
            </div>

            <div class="bg-white rounded-xl p-6 shadow-md">
                <div class="text-3xl mb-4">📍</div>
                <h3 class="text-lg font-bold text-gray-900 mb-2">Know Entry Points</h3>
                <p class="text-gray-600 text-sm">Some beaches have rocky entries. Water shoes are recommended for beaches with sea urchins or coral rubble.</p>
            </div>
        </div>
    </div>
</section>

<!-- Other Beach Categories -->
<section class="py-12">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <h2 class="text-2xl md:text-3xl font-bold text-gray-900 mb-8 text-center">
            Explore More Beach Categories
        </h2>

        <div class="grid md:grid-cols-3 gap-6">
            <a href="/best-beaches" class="bg-white rounded-xl p-6 shadow-md hover:shadow-lg transition-shadow group">
                <div class="text-4xl mb-4">🏖️</div>
                <h3 class="text-lg font-bold text-gray-900 group-hover:text-brand-darker">Best Overall Beaches</h3>
                <p class="text-gray-600 text-sm mt-2">Top 15 beaches in Puerto Rico</p>
            </a>

            <a href="/best-surfing-beaches" class="bg-white rounded-xl p-6 shadow-md hover:shadow-lg transition-shadow group">
                <div class="text-4xl mb-4">🏄</div>
                <h3 class="text-lg font-bold text-gray-900 group-hover:text-brand-darker">Best for Surfing</h3>
                <p class="text-gray-600 text-sm mt-2">World-class waves on the west coast</p>
            </a>

            <a href="/best-family-beaches" class="bg-white rounded-xl p-6 shadow-md hover:shadow-lg transition-shadow group">
                <div class="text-4xl mb-4">👨‍👩‍👧‍👦</div>
                <h3 class="text-lg font-bold text-gray-900 group-hover:text-brand-darker">Best for Families</h3>
                <p class="text-gray-600 text-sm mt-2">Calm waters and kid-friendly facilities</p>
            </a>
        </div>
    </div>
</section>

<!-- FAQ Section -->
<section id="faq" class="py-12 bg-gray-50">
    <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8">
        <h2 class="text-2xl md:text-3xl font-bold text-gray-900 mb-8 text-center">
            Snorkeling in Puerto Rico: FAQs
        </h2>

        <div class="space-y-4">
            <?php foreach ($pageFaqs as $faq): ?>
            <details class="bg-white rounded-lg shadow-md group">
                <summary class="flex items-center justify-between p-6 cursor-pointer font-semibold text-gray-900">
                    <?= h($faq['question']) ?>
                    <span class="text-amber-700 group-open:rotate-180 transition-transform">▼</span>
                </summary>
                <div class="px-6 pb-6 text-gray-700">
                    <?= h($faq['answer']) ?>
                </div>
            </details>
            <?php endforeach; ?>
        </div>
    </div>
</section>

<!-- Map Section -->
<section id="map" class="py-12">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <h2 class="text-2xl md:text-3xl font-bold text-gray-900 mb-8 text-center">
            Find Snorkeling Beaches on the Map
        </h2>
        <div class="text-center">
            <a href="?view=map&collection=best-snorkeling-beaches#top-beaches" class="inline-flex items-center gap-2 bg-cyan-600 hover:bg-cyan-700 text-white px-6 py-3 rounded-lg font-medium transition-colors">
                <span>🗺️</span>
                <span>View Snorkeling Beaches Map</span>
            </a>
            <p class="text-gray-600 mt-4">Filter the interactive map to show all snorkeling spots.</p>
        </div>
    </div>
</section>

<!-- CTA Section -->
<section class="py-12 bg-cyan-600 text-white">
    <div class="max-w-4xl mx-auto px-4 text-center">
        <h2 class="text-2xl md:text-3xl font-bold mb-4">Find Your Perfect Snorkeling Spot</h2>
        <p class="text-lg opacity-90 mb-6">Answer a few questions and get personalized beach recommendations based on your snorkeling experience and preferences.</p>
        <a href="/quiz" class="inline-block bg-white text-cyan-600 hover:bg-cyan-50 px-8 py-3 rounded-lg font-semibold transition-colors">
            Take the Beach Match Quiz
        </a>
    </div>
</section>

</div>

<?php
$skipAppScripts = true;
$extraScripts = '<script defer src="/assets/js/collection-explorer.min.js"></script>';
?>
<?php include APP_ROOT . '/components/footer.php'; ?>
