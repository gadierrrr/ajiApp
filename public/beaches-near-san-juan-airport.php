<?php
/**
 * Beaches Near San Juan Airport - SEO Landing Page
 * Target keywords: beaches near san juan airport, sju layover beach, airport beach san juan
 * Monthly searches: 1,300
 */

require_once $_SERVER['DOCUMENT_ROOT'] . '/../bootstrap.php';

require_once APP_ROOT . '/inc/db.php';
require_once APP_ROOT . '/inc/helpers.php';
require_once APP_ROOT . '/inc/constants.php';
require_once APP_ROOT . '/inc/collection_query.php';
require_once APP_ROOT . '/components/seo-schemas.php';

// Page metadata
$pageTitle = '10 Best Beaches Near San Juan Airport (2026 Layover Guide)';
$pageDescription = 'Perfect for layovers! Visit beaches 5-20 minutes from San Juan Airport (SJU). Complete guide to maximizing beach time on your first or last day in Puerto Rico.';
$canonicalUrl = getPublicBaseUrl() . '/beaches-near-san-juan-airport';

$collectionKey = 'beaches-near-san-juan-airport';
$collectionAnchorId = 'beaches';
$collectionData = fetchCollectionBeaches($collectionKey, collectionFiltersFromRequest($collectionKey, $_GET));
$collectionContext = $collectionData['collection'];
$collectionState = $collectionData['effective_filters'];
$airportBeaches = $collectionData['beaches'];

$userFavorites = [];
if (isAuthenticated()) {
    $favorites = query('SELECT beach_id FROM user_favorites WHERE user_id = :user_id', [':user_id' => $_SESSION['user_id']]) ?: [];
    $userFavorites = array_column($favorites, 'beach_id');
}

// Generate structured data
$extraHead = articleSchema(
    $pageTitle,
    $pageDescription,
    '/beaches-near-san-juan-airport',
    $airportBeaches[0]['cover_image'] ?? null,
    '2026-01-01'
);
$extraHead .= collectionPageSchema($pageTitle, $pageDescription, $airportBeaches);
$extraHead .= websiteSchema();

// FAQ data
$pageFaqs = [
    [
        'question' => 'Can I visit a beach during a 4-hour layover in San Juan?',
        'answer' => 'Yes! Isla Verde Beach is only 5 minutes from San Juan Airport (SJU). For a 4-hour layover, you can realistically spend 90 minutes at the beach. Plan 30 minutes for immigration/customs, 10 minutes travel each way, 90 minutes beach time, and return 90 minutes before your next flight. Only attempt this if you have no checked bags and your layover is domestic or between terminals.'
    ],
    [
        'question' => 'What is the closest beach to San Juan Airport?',
        'answer' => 'Isla Verde Beach is the closest major beach to San Juan Airport (SJU), located just 2.5 km (1.5 miles) east of the terminal. The drive takes only 5-7 minutes via PR-26. You can see the beach from the plane when landing at SJU. It offers a wide sandy beach with chair rentals, restaurants, and water sports.'
    ],
    [
        'question' => 'Where can I store luggage near the airport?',
        'answer' => 'San Juan Airport (SJU) does not have official luggage storage inside the terminal. However, many hotels in Isla Verde (5 minutes away) offer luggage storage for non-guests for $5-10 per bag. ESJ Azul Hotel, Courtyard by Marriott, and Hampton Inn are near the beach and often accommodate this request. Call ahead to confirm. Some beach equipment rental shops also offer luggage storage.'
    ],
    [
        'question' => 'How much does a taxi cost from SJU to the beach?',
        'answer' => 'Taxi or Uber from San Juan Airport to Isla Verde Beach costs $8-12 (5 minutes). To Condado Beach: $15-20 (10 minutes). To Ocean Park: $18-25 (12 minutes). Uber is typically 20-30% cheaper than airport taxis. During peak hours (3-6 PM), expect higher surge pricing. For groups of 3-4, splitting a taxi is more economical than bus service.'
    ],
    [
        'question' => 'Is Isla Verde Beach close to the airport?',
        'answer' => 'Yes, Isla Verde Beach is extremely close to San Juan Airport - only 2.5 km (1.5 miles) or a 5-7 minute drive. It is the closest major beach to any international airport in the Caribbean. The beach runs parallel to the airport runway, and you can literally watch planes taking off and landing while swimming. This proximity makes it perfect for first-day arrivals or last-day departures.'
    ],
    [
        'question' => 'Can I rent beach equipment near the airport?',
        'answer' => 'Yes, Isla Verde Beach has numerous beach equipment rental stands along the shore. Expect to pay $15-20 for two chairs and an umbrella for the day, $25-35 for snorkel gear, $40-60 for kayak rentals, and $50-80 for paddleboard rentals. Many stands also offer boogie boards ($15-20) and beach toys. Some vendors accept credit cards, but cash (US dollars) gets better rates.'
    ],
    [
        'question' => 'What beach should I visit on my last day before flying out?',
        'answer' => 'Isla Verde Beach is ideal for last-day beach visits before your flight. Stay at the eastern end near the hotels for easy access to showers, bathrooms, and food. Plan to leave the beach 2.5-3 hours before your flight (international) or 1.5-2 hours (domestic). Many beachfront hotels allow non-guests to use outdoor showers. Keep beach gear minimal so you can pack wet items in a plastic bag in your carry-on.'
    ],
    [
        'question' => 'Are there showers and bathrooms near airport beaches?',
        'answer' => 'Yes, Balneario de Carolina (Isla Verde) has public bathrooms and outdoor showers as an official public beach facility. Many hotels along Isla Verde Beach also have outdoor showers accessible from the beach. Ocean Park and Condado have public facilities but they can be limited. For the cleanest facilities, consider stopping at a beachfront restaurant or hotel bar - most allow usage if you make a purchase.'
    ]
];
$extraHead .= faqSchema($pageFaqs);

// HowTo Schema for layover beach visit
$extraHead .= <<<HOWTO
<script type="application/ld+json">
{
  "@context": "https://schema.org",
  "@type": "HowTo",
  "name": "How to Visit a Beach During a Layover at San Juan Airport",
  "description": "Step-by-step guide to maximizing beach time during a layover at Luis Muñoz Marín International Airport (SJU)",
  "totalTime": "PT4H",
  "step": [
    {
      "@type": "HowToStep",
      "position": 1,
      "name": "Clear Immigration and Customs",
      "text": "Exit the airport terminal and clear immigration/customs. Have all documents ready. Budget 20-30 minutes.",
      "itemListElement": [{
        "@type": "HowToDirection",
        "text": "Only attempt a layover beach visit if you have carry-on luggage only"
      }]
    },
    {
      "@type": "HowToStep",
      "position": 2,
      "name": "Get Transportation",
      "text": "Take an Uber or taxi from the airport arrivals area to Isla Verde Beach (5-7 minutes, $8-12)",
      "itemListElement": [{
        "@type": "HowToDirection",
        "text": "Request the driver to drop you at the public beach access near Balneario de Carolina"
      }]
    },
    {
      "@type": "HowToStep",
      "position": 3,
      "name": "Store Luggage (if needed)",
      "text": "If carrying a bag, ask a beachfront hotel about luggage storage ($5-10) or keep valuables with you",
      "itemListElement": [{
        "@type": "HowToDirection",
        "text": "ESJ Azul Hotel and Courtyard Isla Verde often accommodate luggage storage requests"
      }]
    },
    {
      "@type": "HowToStep",
      "position": 4,
      "name": "Enjoy Beach Time",
      "text": "Swim, relax, and enjoy the Caribbean. Rent chairs ($15-20) or bring a towel. Stay hydrated.",
      "itemListElement": [{
        "@type": "HowToDirection",
        "text": "Keep track of time and set a phone alarm for your departure time"
      }]
    },
    {
      "@type": "HowToStep",
      "position": 5,
      "name": "Rinse and Change",
      "text": "Use public showers at the beach or hotel outdoor facilities. Change in public bathrooms.",
      "itemListElement": [{
        "@type": "HowToDirection",
        "text": "Bring a change of clothes in a small bag and store wet items in a plastic bag"
      }]
    },
    {
      "@type": "HowToStep",
      "position": 6,
      "name": "Return to Airport",
      "text": "Order Uber/taxi 2.5-3 hours before international flight, 1.5-2 hours before domestic flight",
      "itemListElement": [{
        "@type": "HowToDirection",
        "text": "Factor in traffic during rush hour (3-6 PM weekdays)"
      }]
    }
  ]
}
</script>
HOWTO;

// Breadcrumb schema
$extraHead .= <<<BREADCRUMB
<script type="application/ld+json">
{
  "@context": "https://schema.org",
  "@type": "BreadcrumbList",
  "itemListElement": [{
    "@type": "ListItem",
    "position": 1,
    "name": "Home",
    "item": "https://www.puertoricobeachfinder.com"
  },{
    "@type": "ListItem",
    "position": 2,
    "name": "Beaches Near San Juan Airport",
    "item": "https://www.puertoricobeachfinder.com/beaches-near-san-juan-airport"
  }]
}
</script>
BREADCRUMB;

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
            <a href="#beaches" class="text-amber-600 hover:underline">Beach List</a>
            <span class="text-gray-300">|</span>
            <a href="#travel-times" class="text-amber-600 hover:underline">Travel Times</a>
            <span class="text-gray-300">|</span>
            <a href="#layover-guide" class="text-amber-600 hover:underline">Layover Itineraries</a>
            <span class="text-gray-300">|</span>
            <a href="#transportation" class="text-amber-600 hover:underline">Transportation</a>
            <span class="text-gray-300">|</span>
            <a href="#airport-tips" class="text-amber-600 hover:underline">Airport Tips</a>
            <span class="text-gray-300">|</span>
            <a href="#faq" class="text-amber-600 hover:underline">FAQs</a>
        </div>
    </div>
</section>

<!-- Introduction -->
<section class="py-12 bg-gray-50">
    <div class="max-w-4xl mx-auto px-4">
        <div class="prose prose-lg max-w-none beach-description">
            <p><strong>San Juan Airport (SJU) is remarkably close to beautiful Caribbean beaches</strong> - in fact, Isla Verde Beach is only 5 minutes away. This makes it one of the most beach-accessible airports in the world, perfect for turning a layover into a mini beach vacation or maximizing your time on arrival or departure days.</p>

            <p>Whether you have a 4-hour layover, want to hit the beach before your evening flight, or are looking to make the most of your first or last day in Puerto Rico, the beaches near SJU Airport offer crystal-clear waters, golden sand, and convenient access. <strong>No need to sacrifice beach time to airport logistics.</strong></p>

            <p>This guide covers the 10 closest beaches to San Juan Airport, all within 15km (9 miles). We'll show you exactly how long it takes to reach each beach, what transportation options are available, where to store luggage, and how to plan layover beach visits of varying lengths. All distances and drive times are measured from the main terminal at Luis Muñoz Marín International Airport.</p>

            <p><strong>Pro tip:</strong> Isla Verde Beach is visible from the plane when landing at SJU - if you see that turquoise water from your window seat, you'll be swimming in it within 15 minutes of wheels down.</p>
        </div>
    </div>
</section>

<!-- Travel Time Table -->
<section id="travel-times" class="py-12 bg-gray-50">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <h2 class="text-2xl md:text-3xl font-bold text-gray-900 mb-8 text-center">
            Travel Times from San Juan Airport
        </h2>

        <div class="bg-white rounded-xl shadow-md overflow-hidden">
            <div class="overflow-x-auto">
                <table class="w-full">
                    <thead class="bg-amber-50 a11y-on-light-amber">
                        <tr>
                            <th class="px-6 py-4 text-left text-sm font-semibold text-gray-900">Beach Name</th>
                            <th class="px-6 py-4 text-left text-sm font-semibold text-gray-900">Distance</th>
                            <th class="px-6 py-4 text-left text-sm font-semibold text-gray-900">Drive Time</th>
                            <th class="px-6 py-4 text-left text-sm font-semibold text-gray-900">Taxi Cost</th>
                            <th class="px-6 py-4 text-left text-sm font-semibold text-gray-900">Best For</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200">
                        <?php
                        $travelData = [
                            ['beach' => 'Isla Verde Beach', 'distance' => '2.5 km', 'time' => '5-7 min', 'cost' => '$8-12', 'bestFor' => 'Layovers, quick visits'],
                            ['beach' => 'Balneario de Carolina', 'distance' => '3.1 km', 'time' => '7-10 min', 'cost' => '$10-14', 'bestFor' => 'Families, facilities'],
                            ['beach' => 'Isla Verde Pine Grove', 'distance' => '3.8 km', 'time' => '8-12 min', 'cost' => '$12-15', 'bestFor' => 'Local vibe, less crowded'],
                            ['beach' => 'Ocean Park Beach', 'distance' => '6.2 km', 'time' => '12-15 min', 'cost' => '$18-25', 'bestFor' => 'Surfing, kite boarding'],
                            ['beach' => 'Condado Beach', 'distance' => '7.8 km', 'time' => '15-18 min', 'cost' => '$20-28', 'bestFor' => 'Resorts, dining, nightlife'],
                            ['beach' => 'Escambrón Beach', 'distance' => '9.5 km', 'time' => '18-22 min', 'cost' => '$25-32', 'bestFor' => 'Snorkeling, calm waters'],
                        ];

                        foreach ($travelData as $row): ?>
                        <tr class="hover:bg-gray-50">
                            <td class="px-6 py-4 text-sm font-medium text-gray-900"><?= h($row['beach']) ?></td>
                            <td class="px-6 py-4 text-sm text-gray-700"><?= h($row['distance']) ?></td>
                            <td class="px-6 py-4 text-sm text-gray-700"><?= h($row['time']) ?></td>
                            <td class="px-6 py-4 text-sm text-gray-700"><?= h($row['cost']) ?></td>
                            <td class="px-6 py-4 text-sm text-gray-600"><?= h($row['bestFor']) ?></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <p class="text-sm text-gray-600 text-center mt-4">
            <strong>Note:</strong> Drive times are estimates based on typical traffic. During rush hour (7-9 AM, 4-7 PM weekdays), add 5-10 minutes.
            Taxi costs are approximate for standard taxis/Uber from airport arrivals.
        </p>
    </div>
</section>

<!-- Layover Beach Day Guide -->
<section id="layover-guide" class="py-12">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <h2 class="text-2xl md:text-3xl font-bold text-gray-900 mb-8 text-center">
            Layover Beach Day Itineraries
        </h2>

        <p class="text-center text-gray-600 mb-8 max-w-3xl mx-auto">
            Plan your beach visit based on your layover length. <strong>Important:</strong> Only attempt layover beach visits if you have carry-on luggage only and are comfortable with tight timing. Always return to the airport earlier than you think necessary.
        </p>

        <div class="grid md:grid-cols-3 gap-6">
            <!-- 4-Hour Layover -->
            <div class="bg-white rounded-xl shadow-md overflow-hidden border-2 border-amber-200">
                <div class="bg-amber-600 text-white p-6">
                    <div class="text-3xl mb-2">⚡</div>
                    <h3 class="text-xl font-bold">4-Hour Layover</h3>
                    <p class="text-sm opacity-90 mt-1">Quick dip, tight timing</p>
                </div>
                <div class="p-6">
                    <div class="space-y-3 text-sm">
                        <div class="flex items-start gap-3">
                            <span class="text-amber-600 font-bold">0:00</span>
                            <span class="text-gray-700">Land, clear immigration (30 min)</span>
                        </div>
                        <div class="flex items-start gap-3">
                            <span class="text-amber-600 font-bold">0:30</span>
                            <span class="text-gray-700">Uber to Isla Verde Beach (7 min)</span>
                        </div>
                        <div class="flex items-start gap-3">
                            <span class="text-amber-600 font-bold">0:40</span>
                            <span class="text-gray-700"><strong>Beach time!</strong> (90 minutes)</span>
                        </div>
                        <div class="flex items-start gap-3">
                            <span class="text-amber-600 font-bold">2:10</span>
                            <span class="text-gray-700">Rinse off, change clothes (10 min)</span>
                        </div>
                        <div class="flex items-start gap-3">
                            <span class="text-amber-600 font-bold">2:20</span>
                            <span class="text-gray-700">Return to airport (7 min)</span>
                        </div>
                        <div class="flex items-start gap-3">
                            <span class="text-amber-600 font-bold">2:30</span>
                            <span class="text-gray-700">Check-in, security (90 min buffer)</span>
                        </div>
                    </div>
                    <div class="mt-6 pt-6 border-t border-gray-200">
                        <p class="text-xs text-gray-600">
                            <strong>Beach time:</strong> 90 minutes<br>
                            <strong>Recommended beach:</strong> Isla Verde only<br>
                            <strong>Risk level:</strong> High - not recommended for international connections
                        </p>
                    </div>
                </div>
            </div>

            <!-- 6-Hour Layover -->
            <div class="bg-white rounded-xl shadow-md overflow-hidden border-2 border-slate-200">
                <div class="bg-brand-yellow text-brand-darker p-6">
                    <div class="text-3xl mb-2">🏖️</div>
                    <h3 class="text-xl font-bold">6-Hour Layover</h3>
                    <p class="text-sm opacity-90 mt-1">Comfortable beach visit</p>
                </div>
                <div class="p-6">
                    <div class="space-y-3 text-sm">
                        <div class="flex items-start gap-3">
                            <span class="text-amber-700 font-bold">0:00</span>
                            <span class="text-gray-700">Land, clear immigration (30 min)</span>
                        </div>
                        <div class="flex items-start gap-3">
                            <span class="text-amber-700 font-bold">0:30</span>
                            <span class="text-gray-700">Taxi to beach (10-15 min)</span>
                        </div>
                        <div class="flex items-start gap-3">
                            <span class="text-amber-700 font-bold">0:45</span>
                            <span class="text-gray-700"><strong>Beach time!</strong> (2.5 hours)</span>
                        </div>
                        <div class="flex items-start gap-3">
                            <span class="text-amber-700 font-bold">3:15</span>
                            <span class="text-gray-700">Lunch at beach restaurant (45 min)</span>
                        </div>
                        <div class="flex items-start gap-3">
                            <span class="text-amber-700 font-bold">4:00</span>
                            <span class="text-gray-700">Rinse, change, return (30 min)</span>
                        </div>
                        <div class="flex items-start gap-3">
                            <span class="text-amber-700 font-bold">4:30</span>
                            <span class="text-gray-700">Airport security & check-in</span>
                        </div>
                    </div>
                    <div class="mt-6 pt-6 border-t border-gray-200">
                        <p class="text-xs text-gray-600">
                            <strong>Beach time:</strong> 2.5-3 hours<br>
                            <strong>Recommended beaches:</strong> Isla Verde, Balneario Carolina, Ocean Park<br>
                            <strong>Risk level:</strong> Low - comfortable timing
                        </p>
                    </div>
                </div>
            </div>

            <!-- 8+ Hour Layover -->
            <div class="bg-white rounded-xl shadow-md overflow-hidden border-2 border-slate-200">
                <div class="bg-brand-yellow text-brand-darker p-6">
                    <div class="text-3xl mb-2">🌴</div>
                    <h3 class="text-xl font-bold">8+ Hour Layover</h3>
                    <p class="text-sm opacity-90 mt-1">Full beach day experience</p>
                </div>
                <div class="p-6">
                    <div class="space-y-3 text-sm">
                        <div class="flex items-start gap-3">
                            <span class="text-amber-700 font-bold">0:00</span>
                            <span class="text-gray-700">Land, immigration, store luggage</span>
                        </div>
                        <div class="flex items-start gap-3">
                            <span class="text-amber-700 font-bold">0:45</span>
                            <span class="text-gray-700">Travel to any nearby beach</span>
                        </div>
                        <div class="flex items-start gap-3">
                            <span class="text-amber-700 font-bold">1:00</span>
                            <span class="text-gray-700"><strong>Full beach day!</strong> (4+ hours)</span>
                        </div>
                        <div class="flex items-start gap-3">
                            <span class="text-amber-700 font-bold">5:00</span>
                            <span class="text-gray-700">Lunch, explore neighborhood</span>
                        </div>
                        <div class="flex items-start gap-3">
                            <span class="text-amber-700 font-bold">6:00</span>
                            <span class="text-gray-700">Shower, change, pick up luggage</span>
                        </div>
                        <div class="flex items-start gap-3">
                            <span class="text-amber-700 font-bold">6:30</span>
                            <span class="text-gray-700">Return to airport (relaxed)</span>
                        </div>
                    </div>
                    <div class="mt-6 pt-6 border-t border-gray-200">
                        <p class="text-xs text-gray-600">
                            <strong>Beach time:</strong> 4-5 hours<br>
                            <strong>All beaches accessible:</strong> Try Condado or Escambrón for variety<br>
                            <strong>Risk level:</strong> Very low - plenty of buffer time
                        </p>
                    </div>
                </div>
            </div>
        </div>

        <div class="mt-8 bg-yellow-50 border border-yellow-200 rounded-lg p-6">
            <div class="flex gap-3">
                <div class="text-2xl">⚠️</div>
                <div>
                    <h4 class="font-bold text-gray-900 mb-2">Layover Beach Visit Disclaimer</h4>
                    <p class="text-sm text-gray-700">
                        Attempting a beach visit during a layover carries risk of missing your connection. Only attempt this if: (1) You have <strong>carry-on bags only</strong>, (2) Your layover is <strong>domestic or within the same terminal</strong>, (3) You're comfortable with tight timing, (4) Traffic is light (avoid weekday rush hours). <strong>We recommend 6+ hour layovers minimum</strong>. Always prioritize making your flight over beach time. Consider travel insurance for missed connections.
                    </p>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- Transportation Options -->
<section id="transportation" class="py-12 bg-gray-50">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <h2 class="text-2xl md:text-3xl font-bold text-gray-900 mb-8 text-center">
            Transportation from the Airport
        </h2>

        <div class="grid md:grid-cols-3 gap-6">
            <!-- Taxi/Uber -->
            <div class="bg-white rounded-xl p-6 shadow-md">
                <div class="flex items-center gap-3 mb-4">
                    <div class="text-3xl">🚕</div>
                    <h3 class="text-lg font-bold text-gray-900">Taxi & Rideshare</h3>
                </div>
                <div class="space-y-3 text-sm text-gray-700">
                    <p><strong>Best for:</strong> Quick, convenient beach access</p>
                    <p><strong>Cost:</strong> $8-12 to Isla Verde, $15-25 to Condado, $25-32 to Escambrón</p>
                    <p><strong>Uber/Lyft:</strong> Typically 20-30% cheaper than airport taxis. Pick up at departures level for faster service.</p>
                    <p><strong>Airport taxis:</strong> Fixed-rate zone pricing. Available 24/7 at arrivals. No surge pricing but higher base rates.</p>
                    <p><strong>Pro tip:</strong> Split costs with travel companions. For 3-4 people, taxi is more economical than bus.</p>
                </div>
            </div>

            <!-- Rental Car -->
            <div class="bg-white rounded-xl p-6 shadow-md">
                <div class="flex items-center gap-3 mb-4">
                    <div class="text-3xl">🚗</div>
                    <h3 class="text-lg font-bold text-gray-900">Rental Car</h3>
                </div>
                <div class="space-y-3 text-sm text-gray-700">
                    <p><strong>Best for:</strong> Multi-day stays, exploring beyond San Juan</p>
                    <p><strong>Cost:</strong> $30-60/day plus parking ($15-25/day at beach hotels)</p>
                    <p><strong>Pros:</strong> Freedom to visit multiple beaches, explore the island, carry beach gear</p>
                    <p><strong>Cons:</strong> Parking fees, traffic in San Juan, not ideal for quick layover visits</p>
                    <p><strong>Pro tip:</strong> NOT recommended for layover beach visits - getting rental car takes 30+ minutes and returning adds stress.</p>
                </div>
            </div>

            <!-- Public Transit -->
            <div class="bg-white rounded-xl p-6 shadow-md">
                <div class="flex items-center gap-3 mb-4">
                    <div class="text-3xl">🚌</div>
                    <h3 class="text-lg font-bold text-gray-900">Public Bus (AMA)</h3>
                </div>
                <div class="space-y-3 text-sm text-gray-700">
                    <p><strong>Best for:</strong> Budget travelers with time to spare</p>
                    <p><strong>Cost:</strong> $0.75 per ride (exact change required)</p>
                    <p><strong>Route:</strong> Bus C53 connects airport to Isla Verde and beyond. Runs approximately every 30-60 minutes.</p>
                    <p><strong>Cons:</strong> Slow (20-30 min to Isla Verde), infrequent, limited luggage space, reduced weekend service</p>
                    <p><strong>Pro tip:</strong> Only viable for 8+ hour layovers or non-time-sensitive first/last day visits.</p>
                </div>
            </div>
        </div>

        <div class="mt-8 bg-amber-50 a11y-on-light-amber border border-amber-200 rounded-lg p-6">
            <h4 class="font-bold text-gray-900 mb-3 flex items-center gap-2">
                <span>💡</span>
                <span>Transportation Recommendations by Scenario</span>
            </h4>
            <div class="grid md:grid-cols-2 gap-4 text-sm text-gray-700">
                <div>
                    <p class="font-semibold mb-1">4-6 Hour Layover:</p>
                    <p>Uber/Taxi only - speed is essential. Request pickup at departures level to avoid taxi line wait.</p>
                </div>
                <div>
                    <p class="font-semibold mb-1">First Day Arrival:</p>
                    <p>Uber/Taxi to hotel, then walk or short ride to beach. Store luggage at hotel before checking in.</p>
                </div>
                <div>
                    <p class="font-semibold mb-1">Last Day Departure:</p>
                    <p>Use hotel luggage storage, Uber/Taxi to beach, return to hotel, then airport. Budget 3 hours before flight.</p>
                </div>
                <div>
                    <p class="font-semibold mb-1">8+ Hour Layover:</p>
                    <p>Any option works. Consider taxi out, bus back if saving money. More flexibility in timing.</p>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- Airport Tips -->
<section id="airport-tips" class="py-12">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <h2 class="text-2xl md:text-3xl font-bold text-gray-900 mb-8 text-center">
            Airport Beach Visit Tips
        </h2>

        <div class="grid md:grid-cols-2 gap-6">
            <!-- Luggage Storage -->
            <div class="bg-white rounded-xl p-6 shadow-md">
                <h3 class="text-lg font-bold text-gray-900 mb-4 flex items-center gap-2">
                    <span class="text-2xl">🎒</span>
                    <span>Luggage Storage Options</span>
                </h3>
                <div class="space-y-4 text-sm text-gray-700">
                    <div>
                        <p class="font-semibold text-gray-900 mb-1">Hotel Luggage Holds (Best Option)</p>
                        <p>Most Isla Verde hotels allow non-guest luggage storage for $5-10/bag. Call ahead to confirm.</p>
                        <ul class="mt-2 space-y-1 ml-4">
                            <li>• <strong>ESJ Azul Hotel:</strong> Often accommodates requests, beachfront location</li>
                            <li>• <strong>Courtyard by Marriott Isla Verde:</strong> Professional luggage service</li>
                            <li>• <strong>Hampton Inn Isla Verde:</strong> Near beach, reliable storage</li>
                        </ul>
                    </div>
                    <div>
                        <p class="font-semibold text-gray-900 mb-1">Beach Equipment Rentals</p>
                        <p>Some beach chair rental vendors offer luggage holding for customers ($5-10). Ask the attendant when renting chairs.</p>
                    </div>
                    <div>
                        <p class="font-semibold text-gray-900 mb-1">Airport Lockers</p>
                        <p><strong>Not available</strong> - SJU does not have public luggage storage lockers inside the terminal.</p>
                    </div>
                    <div class="bg-yellow-50 border border-yellow-200 rounded p-3">
                        <p class="text-xs"><strong>Pro tip:</strong> Pack beach essentials (swimsuit, towel, sunscreen) in carry-on. Wear or bring a change of clothes. Store wet items in a gallon Ziploc bag for return trip.</p>
                    </div>
                </div>
            </div>

            <!-- Beach Gear Rental -->
            <div class="bg-white rounded-xl p-6 shadow-md">
                <h3 class="text-lg font-bold text-gray-900 mb-4 flex items-center gap-2">
                    <span class="text-2xl">🏖️</span>
                    <span>Beach Equipment & Facilities</span>
                </h3>
                <div class="space-y-4 text-sm text-gray-700">
                    <div>
                        <p class="font-semibold text-gray-900 mb-1">Equipment Rental Prices (Isla Verde)</p>
                        <ul class="space-y-1 ml-4">
                            <li>• <strong>2 chairs + umbrella:</strong> $15-20/day</li>
                            <li>• <strong>Beach towel:</strong> $5-8</li>
                            <li>• <strong>Snorkel gear:</strong> $25-35/day</li>
                            <li>• <strong>Paddleboard:</strong> $50-80/hour</li>
                            <li>• <strong>Kayak rental:</strong> $40-60/hour</li>
                            <li>• <strong>Boogie board:</strong> $15-20/day</li>
                        </ul>
                    </div>
                    <div>
                        <p class="font-semibold text-gray-900 mb-1">Public Facilities</p>
                        <p><strong>Balneario de Carolina</strong> (official public beach) has:</p>
                        <ul class="space-y-1 ml-4 mt-1">
                            <li>• Free outdoor showers</li>
                            <li>• Public restrooms (bring $1 for attendant tip)</li>
                            <li>• Covered pavilions</li>
                            <li>• Lifeguards on duty</li>
                        </ul>
                    </div>
                    <div>
                        <p class="font-semibold text-gray-900 mb-1">Hotel Beach Access</p>
                        <p>Hotels along Isla Verde have outdoor showers accessible from the beach. Purchase a drink at a hotel bar to use indoor bathrooms for changing.</p>
                    </div>
                    <div class="bg-slate-50 border border-slate-200 rounded p-3">
                        <p class="text-xs"><strong>Money-saving tip:</strong> Bring your own beach towel in carry-on. Buy sunscreen at airport Walgreens (cheaper than beach vendors). Bring refillable water bottle.</p>
                    </div>
                </div>
            </div>

            <!-- Timing Your Return -->
            <div class="bg-white rounded-xl p-6 shadow-md">
                <h3 class="text-lg font-bold text-gray-900 mb-4 flex items-center gap-2">
                    <span class="text-2xl">⏰</span>
                    <span>Timing Your Airport Return</span>
                </h3>
                <div class="space-y-4 text-sm text-gray-700">
                    <div>
                        <p class="font-semibold text-gray-900 mb-1">When to Leave the Beach</p>
                        <ul class="space-y-2 ml-4">
                            <li>• <strong>International flights:</strong> Leave beach 3 hours before departure</li>
                            <li>• <strong>Domestic flights:</strong> Leave beach 2 hours before departure</li>
                            <li>• <strong>Layover connections:</strong> Return 90 minutes before departure (tight!)</li>
                            <li>• <strong>Rush hour (3-6 PM):</strong> Add 15-20 minutes to drive time</li>
                        </ul>
                    </div>
                    <div>
                        <p class="font-semibold text-gray-900 mb-1">Post-Beach Tasks (Budget Time)</p>
                        <ul class="space-y-1 ml-4">
                            <li>• Rinse sand off: 5 minutes</li>
                            <li>• Change clothes in bathroom: 10 minutes</li>
                            <li>• Pick up stored luggage: 5 minutes</li>
                            <li>• Order Uber/taxi: 5-10 minutes wait time</li>
                            <li>• Drive to airport: 7-20 minutes depending on beach</li>
                        </ul>
                    </div>
                    <div class="bg-red-50 border border-red-200 rounded p-3">
                        <p class="text-xs"><strong>Critical reminder:</strong> Airlines close boarding doors 15 minutes before departure. Security lines at SJU can take 30-45 minutes during peak times (morning and evening). Don't risk missing your flight for extra beach time!</p>
                    </div>
                </div>
            </div>

            <!-- What to Bring -->
            <div class="bg-white rounded-xl p-6 shadow-md">
                <h3 class="text-lg font-bold text-gray-900 mb-4 flex items-center gap-2">
                    <span class="text-2xl">🎒</span>
                    <span>Beach Day Packing List</span>
                </h3>
                <div class="space-y-4 text-sm text-gray-700">
                    <div>
                        <p class="font-semibold text-gray-900 mb-1">Essential Items</p>
                        <ul class="space-y-1 ml-4">
                            <li>✅ Swimsuit (wear under clothes)</li>
                            <li>✅ Beach towel (quick-dry travel towel ideal)</li>
                            <li>✅ Sunscreen (reef-safe, TSA-compliant size)</li>
                            <li>✅ Change of clothes + underwear</li>
                            <li>✅ Gallon Ziploc bags (for wet items)</li>
                            <li>✅ Sunglasses & hat</li>
                            <li>✅ Flip-flops or water shoes</li>
                            <li>✅ Waterproof phone case or dry bag</li>
                            <li>✅ Cash (US dollars for rentals/tips)</li>
                            <li>✅ Boarding pass & ID (keep in waterproof bag!)</li>
                        </ul>
                    </div>
                    <div>
                        <p class="font-semibold text-gray-900 mb-1">Nice to Have</p>
                        <ul class="space-y-1 ml-4">
                            <li>• Snorkel mask (if you have compact one)</li>
                            <li>• Beach read or Kindle</li>
                            <li>• Portable phone charger</li>
                            <li>• Refillable water bottle</li>
                            <li>• Snacks</li>
                        </ul>
                    </div>
                    <div class="bg-slate-50 border border-slate-200 rounded p-3">
                        <p class="text-xs"><strong>Packing hack:</strong> Use a lightweight drawstring backpack as your beach bag. It packs flat in carry-on and works for wet clothes on return. Everything stays together and TSA-compliant.</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- Cross-Links Section -->
<section class="py-12 bg-gray-50">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <h2 class="text-2xl md:text-3xl font-bold text-gray-900 mb-8 text-center">
            Explore More San Juan Beaches
        </h2>

        <div class="grid md:grid-cols-3 gap-6">
            <a href="/beaches-near-san-juan" class="bg-white rounded-xl p-6 shadow-md hover:shadow-lg transition-shadow group">
                <div class="text-4xl mb-4">🏙️</div>
                <h3 class="text-lg font-bold text-gray-900 group-hover:text-amber-600">Beaches Near San Juan</h3>
                <p class="text-gray-600 text-sm mt-2">All beaches within 30 minutes of San Juan city center, including Old San Juan, Condado, and beyond</p>
            </a>

            <a href="/best-beaches" class="bg-white rounded-xl p-6 shadow-md hover:shadow-lg transition-shadow group">
                <div class="text-4xl mb-4">🏖️</div>
                <h3 class="text-lg font-bold text-gray-900 group-hover:text-amber-600">Best Beaches in Puerto Rico</h3>
                <p class="text-gray-600 text-sm mt-2">Top 15 beaches across the entire island - if you have a rental car, venture beyond San Juan</p>
            </a>

            <a href="/best-family-beaches" class="bg-white rounded-xl p-6 shadow-md hover:shadow-lg transition-shadow group">
                <div class="text-4xl mb-4">👨‍👩‍👧‍👦</div>
                <h3 class="text-lg font-bold text-gray-900 group-hover:text-amber-600">Best Family Beaches</h3>
                <p class="text-gray-600 text-sm mt-2">Calm waters, facilities, and kid-friendly amenities - perfect for traveling families with layovers</p>
            </a>
        </div>
    </div>
</section>

<!-- FAQ Section -->
<section id="faq" class="py-12">
    <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8">
        <h2 class="text-2xl md:text-3xl font-bold text-gray-900 mb-8 text-center">
            San Juan Airport Beach FAQs
        </h2>

        <div class="space-y-4">
            <?php foreach ($pageFaqs as $faq): ?>
            <details class="bg-white rounded-lg shadow-md group">
                <summary class="flex items-center justify-between p-6 cursor-pointer font-semibold text-gray-900">
                    <?= h($faq['question']) ?>
                    <span class="text-amber-600 group-open:rotate-180 transition-transform">▼</span>
                </summary>
                <div class="px-6 pb-6 text-gray-700">
                    <?= h($faq['answer']) ?>
                </div>
            </details>
            <?php endforeach; ?>
        </div>
    </div>
</section>

<!-- CTA Section -->
<section class="py-12 bg-amber-600 text-white">
    <div class="max-w-4xl mx-auto px-4 text-center">
        <h2 class="text-2xl md:text-3xl font-bold mb-4">Making the Most of Your Puerto Rico Visit?</h2>
        <p class="text-lg opacity-90 mb-6">Discover your perfect beach based on your interests. Our Beach Match Quiz considers your priorities - whether it's quick airport access, snorkeling, surfing, or family-friendly facilities.</p>
        <a href="/quiz" class="inline-block bg-white text-amber-600 hover:bg-amber-50 px-8 py-3 rounded-lg font-semibold transition-colors">
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
