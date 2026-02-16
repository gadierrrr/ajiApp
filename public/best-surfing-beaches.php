<?php
/**
 * Best Surfing Beaches in Puerto Rico - SEO Landing Page
 * Target keywords: surfing puerto rico, best surf beaches puerto rico, rincon surfing
 */

require_once $_SERVER['DOCUMENT_ROOT'] . '/../bootstrap.php';

require_once APP_ROOT . '/inc/db.php';
require_once APP_ROOT . '/inc/helpers.php';
require_once APP_ROOT . '/inc/constants.php';
require_once APP_ROOT . '/inc/collection_query.php';
require_once APP_ROOT . '/components/seo-schemas.php';

// Page metadata
$pageTitle = 'Best Surfing Beaches in Puerto Rico (2026 Guide)';
$pageDescription = 'Discover the best surfing beaches in Puerto Rico for 2026. From Rincon\'s world-class breaks to beginner-friendly spots, find your perfect wave on the island.';
$canonicalUrl = getPublicBaseUrl() . '/best-surfing-beaches';

$collectionKey = 'best-surfing-beaches';
$collectionAnchorId = 'top-beaches';
$collectionData = fetchCollectionBeaches($collectionKey, collectionFiltersFromRequest($collectionKey, $_GET));
$collectionContext = $collectionData['collection'];
$collectionState = $collectionData['effective_filters'];
$surfingBeaches = $collectionData['beaches'];

$userFavorites = [];
if (isAuthenticated()) {
    $favorites = query('SELECT beach_id FROM user_favorites WHERE user_id = :user_id', [':user_id' => $_SESSION['user_id']]) ?: [];
    $userFavorites = array_column($favorites, 'beach_id');
}

// Generate structured data
$extraHead = articleSchema(
    $pageTitle,
    $pageDescription,
    '/best-surfing-beaches',
    $surfingBeaches[0]['cover_image'] ?? null,
    '2026-01-01'
);
$extraHead .= collectionPageSchema($pageTitle, $pageDescription, $surfingBeaches);
$extraHead .= websiteSchema();

// FAQ data
$pageFaqs = [
    [
        'question' => 'Where is the best surfing in Puerto Rico?',
        'answer' => 'Rincon on Puerto Rico\'s west coast is the island\'s premier surfing destination, hosting professional competitions and offering world-class waves. Other top spots include Domes Beach, Maria\'s Beach, Tres Palmas, and Wilderness. For beginners, Jobos Beach in Isabela and Pine Grove in Isla Verde offer gentler waves.'
    ],
    [
        'question' => 'When is the best time to surf in Puerto Rico?',
        'answer' => 'The best surfing season runs from November through March when winter swells from North Atlantic storms reach the north and west coasts. Wave heights can reach 8-20+ feet during peak swells. Summer offers smaller, more consistent waves on the south coast, ideal for beginners.'
    ],
    [
        'question' => 'Can beginners surf in Puerto Rico?',
        'answer' => 'Yes! Puerto Rico has excellent beginner spots including Pine Grove Beach (Isla Verde), Jobos Beach (Isabela), and La Pared (Luquillo). Many beaches have surf schools offering lessons and board rentals. Start with a longboard or foam board in smaller waves.'
    ],
    [
        'question' => 'Do I need to bring my own surfboard to Puerto Rico?',
        'answer' => 'While you can bring your own board (most airlines charge $100-150 each way), there are numerous surf shops offering quality rentals throughout the island. Rincon, Aguadilla, and San Juan have the best selection of rental boards ranging from longboards to shortboards.'
    ],
    [
        'question' => 'Are there surf competitions in Puerto Rico?',
        'answer' => 'Yes, Puerto Rico hosts several major surf competitions. Rincon hosts events during winter including stops on professional tours. The island has produced champion surfers and remains a significant destination on the competitive surfing circuit.'
    ]
];
$extraHead .= faqSchema($pageFaqs);

// Breadcrumbs
$breadcrumbs = [
    ['name' => 'Home', 'url' => '/'],
    ['name' => 'Best Beaches', 'url' => '/best-beaches'],
    ['name' => 'Surfing Beaches']
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
            <a href="#by-level" class="text-amber-700 hover:underline">By Level</a>
            <span class="text-gray-300">|</span>
            <a href="#season" class="text-amber-700 hover:underline">Surf Season</a>
            <span class="text-gray-300">|</span>
            <a href="#faq" class="text-amber-700 hover:underline">FAQs</a>
        </div>
    </div>
</section>

<!-- Introduction -->
<section class="py-12 bg-gray-50">
    <div class="max-w-4xl mx-auto px-4">
        <div class="prose prose-lg max-w-none beach-description">
            <p>Puerto Rico is a <strong>world-renowned surfing destination</strong> with consistent swells, warm water year-round, and a variety of breaks for all skill levels. The island's unique position in the Caribbean receives swells from multiple directions.</p>

            <p>Rincon, often called the <strong>"Caribbean's Hawaii,"</strong> put Puerto Rico on the surfing map when it hosted the 1968 World Surfing Championship. Today, the island continues to attract surfers from around the globe seeking quality waves without the crowds of more popular destinations.</p>
        </div>
    </div>
</section>

<!-- By Skill Level -->
<section id="by-level" class="py-12 bg-gray-50">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <h2 class="text-2xl md:text-3xl font-bold text-gray-900 mb-8 text-center">
            Surf Spots by Skill Level
        </h2>

        <div class="grid md:grid-cols-3 gap-6">
            <div class="bg-white rounded-xl p-6 shadow-md">
                <div class="text-3xl mb-4">🌱</div>
                <h3 class="text-lg font-bold text-gray-900 mb-3">Beginner Friendly</h3>
                <ul class="text-gray-600 text-sm space-y-2">
                    <li><strong>Pine Grove Beach</strong> - Isla Verde, gentle waves</li>
                    <li><strong>Jobos Beach</strong> - Isabela, soft sand bottom</li>
                    <li><strong>La Pared</strong> - Luquillo, consistent small waves</li>
                    <li><strong>Aviones</strong> - Loiza, beginner lessons available</li>
                </ul>
            </div>

            <div class="bg-white rounded-xl p-6 shadow-md">
                <div class="text-3xl mb-4">🌊</div>
                <h3 class="text-lg font-bold text-gray-900 mb-3">Intermediate</h3>
                <ul class="text-gray-600 text-sm space-y-2">
                    <li><strong>Crash Boat</strong> - Aguadilla, reef break</li>
                    <li><strong>Maria's Beach</strong> - Rincon, point break</li>
                    <li><strong>Sandy Beach</strong> - Rincon, beach break</li>
                    <li><strong>La Selva</strong> - Hatillo, less crowded</li>
                </ul>
            </div>

            <div class="bg-white rounded-xl p-6 shadow-md">
                <div class="text-3xl mb-4">🔥</div>
                <h3 class="text-lg font-bold text-gray-900 mb-3">Advanced / Expert</h3>
                <ul class="text-gray-600 text-sm space-y-2">
                    <li><strong>Tres Palmas</strong> - Big wave spot, reef break</li>
                    <li><strong>Domes</strong> - Powerful reef break</li>
                    <li><strong>Wilderness</strong> - Remote, challenging</li>
                    <li><strong>Gas Chambers</strong> - Heavy, hollow waves</li>
                </ul>
            </div>
        </div>
    </div>
</section>

<!-- Surf Season Info -->
<section id="season" class="py-12">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <h2 class="text-2xl md:text-3xl font-bold text-gray-900 mb-8 text-center">
            Puerto Rico Surf Season Guide
        </h2>

        <div class="grid md:grid-cols-2 gap-8">
            <div class="bg-slate-50 rounded-xl p-6">
                <h3 class="text-xl font-bold text-gray-900 mb-4">🌊 Winter Season (Nov-Mar)</h3>
                <ul class="text-gray-700 space-y-2">
                    <li><strong>Swell:</strong> North Atlantic winter storms</li>
                    <li><strong>Wave Height:</strong> 4-20+ feet</li>
                    <li><strong>Best Coasts:</strong> North and West</li>
                    <li><strong>Water Temp:</strong> 77-80°F</li>
                    <li><strong>Top Spots:</strong> Rincon, Aguadilla, Isabela</li>
                    <li><strong>Crowds:</strong> Peak season, more surfers</li>
                </ul>
            </div>

            <div class="bg-slate-50 rounded-xl p-6">
                <h3 class="text-xl font-bold text-gray-900 mb-4">☀️ Summer Season (Apr-Oct)</h3>
                <ul class="text-gray-700 space-y-2">
                    <li><strong>Swell:</strong> Southern Caribbean swells</li>
                    <li><strong>Wave Height:</strong> 2-6 feet</li>
                    <li><strong>Best Coasts:</strong> South and East</li>
                    <li><strong>Water Temp:</strong> 82-86°F</li>
                    <li><strong>Top Spots:</strong> Playa Guanica, Ponce</li>
                    <li><strong>Crowds:</strong> Less crowded, great for learning</li>
                </ul>
            </div>
        </div>
    </div>
</section>

<!-- Other Beach Categories -->
<section class="py-12 bg-gray-50">
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

            <a href="/best-snorkeling-beaches" class="bg-white rounded-xl p-6 shadow-md hover:shadow-lg transition-shadow group">
                <div class="text-4xl mb-4">🤿</div>
                <h3 class="text-lg font-bold text-gray-900 group-hover:text-brand-darker">Best for Snorkeling</h3>
                <p class="text-gray-600 text-sm mt-2">Crystal-clear waters and coral reefs</p>
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
<section id="faq" class="py-12">
    <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8">
        <h2 class="text-2xl md:text-3xl font-bold text-gray-900 mb-8 text-center">
            Surfing in Puerto Rico: FAQs
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
<section class="py-12 bg-gray-50">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <h2 class="text-2xl md:text-3xl font-bold text-gray-900 mb-8 text-center">
            Find Surf Spots on the Map
        </h2>
        <div class="text-center">
            <a href="?view=map&collection=best-surfing-beaches#top-beaches" class="inline-flex items-center gap-2 bg-orange-600 hover:bg-orange-700 text-white px-6 py-3 rounded-lg font-medium transition-colors">
                <span>🗺️</span>
                <span>View Surf Beaches Map</span>
            </a>
            <p class="text-gray-600 mt-4">Filter the interactive map to show all surf spots.</p>
        </div>
    </div>
</section>

<!-- CTA Section -->
<section class="py-12 bg-orange-600 text-white">
    <div class="max-w-4xl mx-auto px-4 text-center">
        <h2 class="text-2xl md:text-3xl font-bold mb-4">Find Your Perfect Wave</h2>
        <p class="text-lg opacity-90 mb-6">Tell us your skill level and preferences, and we'll match you with the ideal surf spots in Puerto Rico.</p>
        <a href="/quiz" class="inline-block bg-white text-orange-600 hover:bg-orange-50 px-8 py-3 rounded-lg font-semibold transition-colors">
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
