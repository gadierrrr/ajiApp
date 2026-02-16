<?php
/**
 * Beaches Near San Juan - SEO Landing Page
 * Target keywords: beaches near san juan, san juan beaches, beaches puerto rico capital
 */

require_once $_SERVER['DOCUMENT_ROOT'] . '/../bootstrap.php';

require_once APP_ROOT . '/inc/db.php';
require_once APP_ROOT . '/inc/helpers.php';
require_once APP_ROOT . '/inc/constants.php';
require_once APP_ROOT . '/inc/collection_query.php';
require_once APP_ROOT . '/components/seo-schemas.php';

// Page metadata
$pageTitle = 'Best Beaches Near San Juan, Puerto Rico (2026 Guide)';
$pageDescription = 'Discover the best beaches near San Juan, Puerto Rico. From Condado and Isla Verde to hidden local favorites, find the perfect beach just minutes from the capital.';
$canonicalUrl = getPublicBaseUrl() . '/beaches-near-san-juan';

$collectionKey = 'beaches-near-san-juan';
$collectionAnchorId = 'beaches';
$collectionData = fetchCollectionBeaches($collectionKey, collectionFiltersFromRequest($collectionKey, $_GET));
$collectionContext = $collectionData['collection'];
$collectionState = $collectionData['effective_filters'];
$sanJuanBeaches = $collectionData['beaches'];

$userFavorites = [];
if (isAuthenticated()) {
    $favorites = query('SELECT beach_id FROM user_favorites WHERE user_id = :user_id', [':user_id' => $_SESSION['user_id']]) ?: [];
    $userFavorites = array_column($favorites, 'beach_id');
}

// Generate structured data
$extraHead = articleSchema(
    $pageTitle,
    $pageDescription,
    '/beaches-near-san-juan',
    $sanJuanBeaches[0]['cover_image'] ?? null,
    '2026-01-01'
);
$extraHead .= collectionPageSchema($pageTitle, $pageDescription, $sanJuanBeaches);
$extraHead .= websiteSchema();

// FAQ data
$pageFaqs = [
    [
        'question' => 'What is the closest beach to San Juan?',
        'answer' => 'Condado Beach is the closest major beach to Old San Juan, just a 10-minute drive or 20-minute walk from the cruise port. Isla Verde Beach is about 15 minutes from Old San Juan and offers a wider, more resort-lined stretch of sand.'
    ],
    [
        'question' => 'Can you walk to beaches from Old San Juan?',
        'answer' => 'While Old San Juan itself doesn\'t have sandy beaches (it\'s a historic walled city), you can walk to Escambron Beach in about 25-30 minutes along the coast. For Condado Beach, it\'s a 20-25 minute walk or quick Uber ride. Most visitors take a taxi or rideshare to the beach areas.'
    ],
    [
        'question' => 'Which San Juan beach is best for swimming?',
        'answer' => 'Condado Beach and Isla Verde Beach are both excellent for swimming with generally calm conditions. For the calmest waters, try the lagoon side of Condado or Balneario Escambron, which is protected by a natural reef. Ocean Park is popular but can have stronger currents.'
    ],
    [
        'question' => 'Are San Juan beaches free to access?',
        'answer' => 'Yes, all beaches in Puerto Rico are public and free to access. Some beach areas may charge for parking or chair/umbrella rentals, but the beach itself is always free. Hotel beaches are also public - you can use the sand and water even if not a hotel guest.'
    ],
    [
        'question' => 'What is the nicest beach in San Juan?',
        'answer' => 'Isla Verde Beach is often considered the nicest beach in the San Juan area, with its wide stretch of golden sand, clear waters, and backdrop of luxury hotels. Ocean Park Beach is favored by locals for its relaxed vibe, while Condado offers the best combination of beach and urban amenities.'
    ]
];
$extraHead .= faqSchema($pageFaqs);

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
            <a href="#beaches" class="text-amber-700 hover:underline">Beach List</a>
            <span class="text-gray-300">|</span>
            <a href="#neighborhoods" class="text-amber-700 hover:underline">By Neighborhood</a>
            <span class="text-gray-300">|</span>
            <a href="#getting-there" class="text-amber-700 hover:underline">Getting There</a>
            <span class="text-gray-300">|</span>
            <a href="#faq" class="text-amber-700 hover:underline">FAQs</a>
        </div>
    </div>
</section>

<!-- Introduction -->
<section class="py-12 bg-gray-50">
    <div class="max-w-4xl mx-auto px-4">
        <div class="prose prose-lg max-w-none beach-description">
            <p>San Juan, Puerto Rico's vibrant capital, offers <strong>easy access to beautiful Caribbean beaches</strong> just minutes from the city center. Whether you're staying in the historic Old San Juan, the trendy Condado district, or near the airport in Isla Verde, you're never far from pristine shores.</p>

            <p>From urban beaches with waterfront restaurants to quiet coves perfect for snorkeling, the San Juan area has options for every type of beach lover. All beaches listed here are <strong>within 30 minutes</strong> of central San Juan.</p>
        </div>
    </div>
</section>

<!-- By Neighborhood -->
<section id="neighborhoods" class="py-12 bg-gray-50">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <h2 class="text-2xl md:text-3xl font-bold text-gray-900 mb-8 text-center">
            San Juan Beach Neighborhoods
        </h2>

        <div class="grid md:grid-cols-2 lg:grid-cols-4 gap-6">
            <div class="bg-white rounded-xl p-6 shadow-md">
                <h3 class="text-lg font-bold text-gray-900 mb-3">Condado</h3>
                <p class="text-gray-600 text-sm mb-3">Upscale neighborhood with resorts, restaurants, and a long beach strip. Great for people-watching.</p>
                <ul class="text-sm text-gray-700 space-y-1">
                    <li>• Condado Beach</li>
                    <li>• La Ventana al Mar</li>
                    <li>• Playita del Condado</li>
                </ul>
            </div>

            <div class="bg-white rounded-xl p-6 shadow-md">
                <h3 class="text-lg font-bold text-gray-900 mb-3">Isla Verde</h3>
                <p class="text-gray-600 text-sm mb-3">Near the airport with the widest beaches in San Juan. Resort hotels and water sports.</p>
                <ul class="text-sm text-gray-700 space-y-1">
                    <li>• Isla Verde Beach</li>
                    <li>• Pine Grove Beach</li>
                    <li>• Balneario Carolina</li>
                </ul>
            </div>

            <div class="bg-white rounded-xl p-6 shadow-md">
                <h3 class="text-lg font-bold text-gray-900 mb-3">Ocean Park</h3>
                <p class="text-gray-600 text-sm mb-3">Trendy residential area popular with locals. Kite surfing and LGBT-friendly beaches.</p>
                <ul class="text-sm text-gray-700 space-y-1">
                    <li>• Ocean Park Beach</li>
                    <li>• Ultimo Trolley</li>
                    <li>• Parque Barbosa</li>
                </ul>
            </div>

            <div class="bg-white rounded-xl p-6 shadow-md">
                <h3 class="text-lg font-bold text-gray-900 mb-3">Puerta de Tierra</h3>
                <p class="text-gray-600 text-sm mb-3">Between Old San Juan and Condado. Protected beach perfect for families.</p>
                <ul class="text-sm text-gray-700 space-y-1">
                    <li>• Escambron Beach</li>
                    <li>• Playita Escambron</li>
                    <li>• Third Millennium Park</li>
                </ul>
            </div>
        </div>
    </div>
</section>

<!-- Getting There -->
<section id="getting-there" class="py-12">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <h2 class="text-2xl md:text-3xl font-bold text-gray-900 mb-8 text-center">
            Getting to San Juan Beaches
        </h2>

        <div class="grid md:grid-cols-3 gap-6">
            <div class="bg-white rounded-xl p-6 shadow-md">
                <div class="text-3xl mb-4">🚕</div>
                <h3 class="text-lg font-bold text-gray-900 mb-2">Taxi / Uber</h3>
                <p class="text-gray-600 text-sm">Most convenient option. Uber and local taxis are widely available. From Old San Juan to Condado: ~$12-15. To Isla Verde: ~$20-25.</p>
            </div>

            <div class="bg-white rounded-xl p-6 shadow-md">
                <div class="text-3xl mb-4">🚌</div>
                <h3 class="text-lg font-bold text-gray-900 mb-2">Public Bus (AMA)</h3>
                <p class="text-gray-600 text-sm">Bus T5 runs from Old San Juan through Condado, Ocean Park, and Isla Verde. Only $0.75 but can be slow. Limited on weekends.</p>
            </div>

            <div class="bg-white rounded-xl p-6 shadow-md">
                <div class="text-3xl mb-4">🚶</div>
                <h3 class="text-lg font-bold text-gray-900 mb-2">Walking</h3>
                <p class="text-gray-600 text-sm">Condado is walkable from Old San Juan (~25 min). A scenic coastal walk connects many beaches. Not ideal in hot midday sun.</p>
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
            San Juan Beaches: FAQs
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
            View San Juan Area Beaches
        </h2>
        <div class="text-center">
            <a href="?view=map&collection=beaches-near-san-juan#top-beaches" class="inline-flex items-center gap-2 bg-brand-yellow hover:bg-yellow-300 text-brand-darker px-6 py-3 rounded-lg font-medium transition-colors">
                <span>🗺️</span>
                <span>Open Map Near San Juan</span>
            </a>
            <p class="text-gray-600 mt-4">See all beaches within 30 minutes of San Juan on the interactive map.</p>
        </div>
    </div>
</section>

<!-- CTA Section -->
<section class="py-12 bg-brand-yellow text-brand-darker">
    <div class="max-w-4xl mx-auto px-4 text-center">
        <h2 class="text-2xl md:text-3xl font-bold mb-4">Planning a Day Trip from San Juan?</h2>
        <p class="text-lg opacity-90 mb-6">Take our quiz to find the perfect beach based on what you're looking for - whether it's snorkeling, surfing, or just relaxing.</p>
        <a href="/quiz" class="inline-block bg-white text-amber-700 hover:bg-slate-50 px-8 py-3 rounded-lg font-semibold transition-colors">
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
