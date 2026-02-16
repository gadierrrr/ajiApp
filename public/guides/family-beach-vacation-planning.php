<?php
require_once $_SERVER['DOCUMENT_ROOT'] . '/../bootstrap.php';

require_once APP_ROOT . '/inc/db.php';
require_once APP_ROOT . '/inc/helpers.php';
require_once APP_ROOT . '/components/seo-schemas.php';
$pageTitle = 'Family Beach Vacation Planning Guide for Puerto Rico';
$pageDescription = 'Plan the perfect family beach trip with kid-friendly beaches, sample itineraries, budgeting tips, accommodation recommendations, and activities for all ages.';
$family_beaches = query("SELECT id, name, municipality, slug FROM beaches WHERE id IN (SELECT beach_id FROM beach_amenities WHERE amenity IN ('lifeguards','restrooms','showers')) LIMIT 5");
$familyMapBeachIds = array_values(array_filter(array_map(static function ($id): string {
    if (!is_scalar($id)) {
        return '';
    }
    return trim((string)$id);
}, array_column($family_beaches, 'id'))));
$relatedGuides = [['title' => 'Beach Safety Tips', 'slug' => 'beach-safety-tips'],['title' => 'Beach Packing List', 'slug' => 'beach-packing-list'],['title' => 'Getting to Puerto Rico Beaches', 'slug' => 'getting-to-puerto-rico-beaches']];
$faqs = [['question' => 'What are the best family-friendly beaches in Puerto Rico?', 'answer' => 'Top family beaches include Balneario de Luquillo (calm water, facilities, food vendors), Balneario de Carolina (near San Juan, lifeguards), Seven Seas Fajardo (shallow water, shade), and Flamenco Beach Culebra (stunning beauty, calm conditions). Look for beaches with lifeguards and amenities.'],['question' => 'Is Puerto Rico safe for families with young children?', 'answer' => 'Yes! Puerto Rico is very family-friendly. Stay at balnearios (public beaches) with lifeguards for maximum safety. Book family-oriented hotels/resorts with kids clubs. Use common sense with valuables and stay in tourist areas. Puerto Ricans are welcoming to families.'],['question' => 'What is a realistic budget for a family beach vacation to Puerto Rico?', 'answer' => 'Budget $2,000-4,000 for a family of 4 for one week including flights ($800-1,600), car rental ($350-500), hotel ($700-1,400), food ($500-800), and activities ($300-500). Condo rentals with kitchens save money on dining.'],['question' => 'What activities beyond beaches keep kids entertained?', 'answer' => 'El Yunque Rainforest, bioluminescent bay kayaking, Camuy Caves, Arecibo Observatory, Old San Juan forts (Castillo San Felipe del Morro), Toro Verde zipline park, snorkeling trips, and wildlife refuges. Puerto Rico offers excellent variety beyond beaches.'],['question' => 'When is the best time for a family beach vacation in Puerto Rico?', 'answer' => 'April-May and November offer great weather with fewer crowds and lower prices than peak winter. Summer (June-August) aligns with school vacations but brings rain risk and humidity. Winter (Dec-March) has perfect weather but highest prices and crowds.']];

$extraHead = $extraHead ?? "";
$extraHead .= articleSchema($pageTitle, $pageDescription, '/guides/family-beach-vacation-planning', null, '2024-01-15');
$extraHead .= howToSchema(
    'How to Plan Family Beach Vacation to Puerto Rico',
    'Complete planning guide for families',
    [
        ['name' => 'Choose Best Time to Visit', 'text' => 'Select travel dates based on school schedules, weather preferences, and budget. April-May offers best value.'],
        ['name' => 'Book Flights Early', 'text' => 'Book 2-3 months ahead for best prices. Nonstop flights to San Juan from major U.S. cities available.'],
        ['name' => 'Reserve Family Accommodation', 'text' => 'Book hotels with pools and kids clubs, or condos with kitchens to save on dining costs.'],
        ['name' => 'Rent Appropriate Vehicle', 'text' => 'Reserve car seats with rental car or bring portable boosters. SUV provides space for family gear.'],
        ['name' => 'Plan Balanced Itinerary', 'text' => 'Mix beach days with cultural sites, rainforest, and adventure activities. Don\'t overschedule.'],
        ['name' => 'Pack Smart for Kids', 'text' => 'Bring beach toys, life jackets, first aid kit, snacks, entertainment for travel, and sun protection.'],
        ['name' => 'Build in Downtime', 'text' => 'Schedule rest days at hotel pool or easy beaches. Avoid burnout with realistic daily plans.']
    ]
);
$extraHead .= faqSchema($faqs);
$extraHead .= breadcrumbSchema([
    ['name' => 'Home', 'url' => '/'],
    ['name' => 'Guides', 'url' => '/guides/'],
    ['name' => 'Family Vacation Planning', 'url' => '/guides/family-beach-vacation-planning']
]);

$pageTheme = "guide";
$pageShellMode = "start";
include APP_ROOT . "/components/page-shell.php";
?>
<?php
$breadcrumbs = [
    ['name' => 'Home', 'url' => '/'],
    ['name' => 'Guides', 'url' => '/guides/'],
    ['name' => 'Family Planning']
];
include APP_ROOT . '/components/hero-guide.php';
?>
<main class="guide-layout">
<aside class="guide-sidebar"><div class="guide-toc">
<h2 class="text-lg font-bold text-gray-900 mb-4">Table of Contents</h2>
<nav class="space-y-2"><a href="#why-pr" class="guide-toc-link">Why Puerto Rico?</a><a href="#best-beaches" class="guide-toc-link">Best Family Beaches</a><a href="#where-stay" class="guide-toc-link">Where to Stay</a><a href="#itineraries" class="guide-toc-link">Sample Itineraries</a><a href="#budget" class="guide-toc-link">Budgeting</a><a href="#activities" class="guide-toc-link">Kid Activities</a><a href="#faq" class="guide-toc-link">FAQ</a></nav>
</div></aside>
<article class="guide-article bg-white rounded-lg shadow-card p-8">
<div class="prose prose-lg max-w-none">
<p class="lead text-xl text-gray-700 mb-8">Puerto Rico delivers the perfect family beach vacation—stunning beaches with calm water, no passport requirement for U.S. citizens, rich culture, rainforest adventures, and kid-friendly infrastructure. This comprehensive guide helps you plan every detail from choosing beaches to budgeting, ensuring a memorable stress-free trip for families with children of all ages.</p>
<h2 id="why-pr" class="text-3xl font-bold text-gray-900 mt-12 mb-6">Why Puerto Rico for Families?</h2>
<ul class="list-disc list-inside space-y-3 text-gray-700 mb-6"><li><strong>No passport needed</strong> for U.S. citizens—easy travel</li><li><strong>Short flights</strong> from East Coast (3-4 hours)</li><li><strong>English widely spoken</strong> alongside Spanish</li><li><strong>Family-friendly culture</strong>—locals love children</li><li><strong>Safe swimming beaches</strong> with lifeguards and facilities</li><li><strong>Variety beyond beaches</strong>—rainforest, history, caves, bioluminescence</li><li><strong>Familiar infrastructure</strong>—chain hotels, U.S. brands, easy navigation</li></ul>
<h2 id="best-beaches" class="text-3xl font-bold text-gray-900 mt-12 mb-6">Best Family-Friendly Beaches</h2>
<?php if(!empty($family_beaches)):?>
<div class="space-y-4 mb-8"><?php foreach($family_beaches as $beach):?>
<div class="bg-green-50 border-l-4 border-green-600 p-4"><a href="/beach/<?php echo h($beach['slug']); ?>" class="text-green-900 font-bold hover:underline"><?php echo h($beach['name']);?></a><p class="text-green-800 text-sm"><?php echo h($beach['municipality']);?></p></div>
<?php endforeach;?></div>
<?php endif;?>
<p class="mb-4"><strong>Key features for family beaches:</strong> Lifeguards on duty, calm shallow water, restrooms and showers, food vendors or nearby restaurants, shade (trees or rentals), parking, and easy access.</p>
<h2 id="where-stay" class="text-3xl font-bold text-gray-900 mt-12 mb-6">Where to Stay with Families</h2>
<h3 class="text-2xl font-bold text-gray-900 mt-8 mb-4">All-Inclusive Resorts</h3>
<p class="mb-4"><strong>Best for hassle-free vacations.</strong> Include meals, kids clubs, activities, and entertainment. Higher upfront cost but predictable budget. Choices: Rio Mar Resort (Rio Grande), El Conquistador (Fajardo).</p>
<h3 class="text-2xl font-bold text-gray-900 mt-8 mb-4">Beachfront Hotels</h3>
<p class="mb-4"><strong>Good balance.</strong> Pool, beach access, on-site dining. More flexibility than all-inclusive. San Juan options: Caribe Hilton, Condado Vanderbilt. East coast: Wyndham Grand Rio Mar.</p>
<h3 class="text-2xl font-bold text-gray-900 mt-8 mb-4">Vacation Condos</h3>
<p class="mb-4"><strong>Best value for longer stays.</strong> Full kitchens save money on meals. Space for kids to spread out. Washer/dryer for beach clothes. Book via VRBO or Airbnb.</p>
<h2 id="itineraries" class="text-3xl font-bold text-gray-900 mt-12 mb-6">Sample Family Itineraries</h2>
<div class="bg-blue-50 rounded-lg p-6 my-6"><h3 class="text-xl font-bold text-blue-900 mb-4">3-Day Weekend Getaway (San Juan Area)</h3>
<ul class="space-y-2 text-gray-700"><li><strong>Day 1:</strong> Arrive, settle in, Balneario de Carolina or Isla Verde Beach</li><li><strong>Day 2:</strong> Old San Juan forts (El Morro), lunch in Old San Juan, Condado Beach afternoon</li><li><strong>Day 3:</strong> El Yunque Rainforest waterfalls, Luquillo Beach, return home</li></ul></div>
<div class="bg-green-50 rounded-lg p-6 my-6"><h3 class="text-xl font-bold text-green-900 mb-4">5-Day Island Explorer</h3>
<ul class="space-y-2 text-gray-700"><li><strong>Day 1:</strong> Arrive, Ocean Park or Isla Verde</li><li><strong>Day 2:</strong> Old San Juan exploration, forts, city beaches</li><li><strong>Day 3:</strong> Drive to Fajardo, Seven Seas Beach, bio bay kayak tour evening</li><li><strong>Day 4:</strong> Ferry to Culebra, Flamenco Beach day trip</li><li><strong>Day 5:</strong> El Yunque Rainforest, return home</li></ul></div>
<div class="bg-yellow-50 rounded-lg p-6 my-6"><h3 class="text-xl font-bold text-yellow-900 mb-4">7-Day Comprehensive Family Tour</h3>
<ul class="space-y-2 text-gray-700"><li><strong>Days 1-2:</strong> San Juan area, Old San Juan, nearby beaches</li><li><strong>Day 3:</strong> El Yunque + Luquillo Beach</li><li><strong>Days 4-5:</strong> Culebra overnight (Flamenco Beach, snorkeling, island life)</li><li><strong>Day 6:</strong> Drive to west coast, Camuy Caves, Crash Boat Beach Aguadilla</li><li><strong>Day 7:</strong> Arecibo Lighthouse, return to San Juan, depart</li></ul></div>
<h2 id="budget" class="text-3xl font-bold text-gray-900 mt-12 mb-6">Family Budget Planning</h2>
<div class="bg-gray-50 rounded-lg p-6 my-6"><h3 class="text-xl font-bold text-gray-900 mb-4">7-Day Family of 4 Budget Estimates</h3>
<div class="space-y-4"><div><h4 class="font-bold text-green-900">Flights</h4><p class="text-gray-700">$800-1,600 round-trip for family (varies by origin city)</p></div>
<div><h4 class="font-bold text-green-900">Accommodation</h4><p class="text-gray-700">Budget condo: $700 (6 nights × $115/night)<br>Mid-range hotel: $1,050 (6 nights × $175/night)<br>All-inclusive resort: $2,400 (6 nights × $400/night)</p></div>
<div><h4 class="font-bold text-green-900">Car Rental</h4><p class="text-gray-700">$350-500 (7 days, gas, car seats, insurance)</p></div>
<div><h4 class="font-bold text-green-900">Food</h4><p class="text-gray-700">Condo with cooking: $400-500<br>Mix dining out/groceries: $600-800<br>All restaurants: $900-1,200</p></div>
<div><h4 class="font-bold text-green-900">Activities</h4><p class="text-gray-700">Beach days (free-low cost): $100<br>Bio bay tour: $200 (4 people)<br>El Yunque: Free entry<br>Culebra ferry: $16<br>Misc attractions: $150<br><strong>Total activities: $300-500</strong></p></div>
<div class="border-t pt-4 mt-4"><h4 class="font-bold text-blue-900 text-lg">Total Budget Range</h4><p class="text-gray-700">Budget trip: $2,250-2,900<br>Mid-range trip: $3,000-4,000<br>Upscale trip: $4,500-6,000+</p></div></div></div>
<h2 id="activities" class="text-3xl font-bold text-gray-900 mt-12 mb-6">Kid-Friendly Activities Beyond Beaches</h2>
<ul class="list-disc list-inside space-y-3 text-gray-700 mb-6"><li><strong>El Yunque Rainforest:</strong> Waterfall swimming, nature trails, wildlife</li><li><strong>Bioluminescent bay:</strong> Magical glowing water kayak tours (Laguna Grande easiest with kids)</li><li><strong>Castillo San Felipe del Morro:</strong> Historic fort, kite flying, cannons</li><li><strong>Camuy Caves:</strong> Massive limestone caves, underground river</li><li><strong>Arecibo Observatory:</strong> World-famous radio telescope (currently under renovation, check status)</li><li><strong>Toro Verde Adventure Park:</strong> Ziplines including "The Monster" (older kids/teens)</li><li><strong>Culebra Island:</strong> Ferry adventure, pristine beaches, sea turtles</li><li><strong>Beach camping:</strong> Flamenco or Sun Bay for family adventure</li></ul>
<h2 id="tips" class="text-3xl font-bold text-gray-900 mt-12 mb-6">Money-Saving Family Tips</h2>
<ul class="list-disc list-inside space-y-2 text-gray-700 mb-6"><li>Rent condo with kitchen, cook breakfast and some dinners</li><li>Shop at local grocery stores (Walmart, Pueblo, Selectos)</li><li>Visit free beaches (balnearios charge $3-5 parking)</li><li>Pack snacks, beach toys, life jackets from home</li><li>Use hotel pools on non-beach days</li><li>Take advantage of kids-eat-free restaurant promotions</li><li>Travel shoulder season (April-May, November) for 30-40% savings</li></ul>
<h2 id="faq" class="text-3xl font-bold text-gray-900 mt-12 mb-6">Frequently Asked Questions</h2>
<div class="space-y-6"><?php foreach($faqs as $faq):?>
<div class="border-l-4 border-green-600 pl-4"><h3 class="text-xl font-bold text-gray-900 mb-2"><?php echo h($faq['question']);?></h3><p class="text-gray-700"><?php echo h($faq['answer']);?></p></div>
<?php endforeach;?></div>
<?php
$guideMapIds = $familyMapBeachIds;
$guideMapTitle = 'Find Family-Friendly Beaches';
$guideMapDescription = 'Browse beaches with lifeguards, facilities, and calm water perfect for families.';
$guideMapButtonLabel = 'View Family Beaches';
$guideMapEmptyNotice = 'No family-oriented beaches from this guide are available on the map right now.';
include APP_ROOT . '/components/guide-map-panel.php';
?></div>
<div class="mt-12 pt-8 border-t border-gray-200"><h3 class="text-xl font-bold text-gray-900 mb-4">Related Guides</h3>
<div class="related-guides-grid"><?php foreach($relatedGuides as $guide):?>
<a href="/guides/<?php echo h($guide['slug']);?>" class="related-guide-card"><span class="related-guide-title"><?php echo h($guide['title']);?></span></a>
<?php endforeach;?></div></div>
</article></div></main>

<?php
$pageShellMode = "end";
include APP_ROOT . "/components/page-shell.php";
?>
