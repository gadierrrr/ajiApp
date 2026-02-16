<?php
require_once $_SERVER['DOCUMENT_ROOT'] . '/../bootstrap.php';

require_once APP_ROOT . '/inc/db.php';
require_once APP_ROOT . '/inc/helpers.php';
require_once APP_ROOT . '/inc/constants.php';
require_once APP_ROOT . '/components/seo-schemas.php';

$pageTitle = 'Beach Safety Tips for Puerto Rico: Essential Guide';
$pageDescription = 'Stay safe at Puerto Rico beaches with expert advice on rip currents, sun protection, marine life, water quality, and emergency contacts.';

$featuredBeaches = query("
    SELECT id, name, municipality, slug
    FROM beaches
    WHERE slug LIKE 'balneario-la-monserrate-luquillo-luquillo%'
       OR slug LIKE 'seven-seas-beach-fajardo%'
       OR slug LIKE 'crash-boat-beach-aguadilla%'
    ORDER BY CASE
        WHEN slug LIKE 'balneario-la-monserrate-luquillo-luquillo%' THEN 1
        WHEN slug LIKE 'seven-seas-beach-fajardo%' THEN 2
        WHEN slug LIKE 'crash-boat-beach-aguadilla%' THEN 3
        ELSE 4
    END
    LIMIT 3
");

// Fallback to known guarded beaches if canonical slug variants are unavailable.
if (empty($featuredBeaches)) {
    $featuredBeaches = query("
        SELECT DISTINCT b.id, b.name, b.municipality, b.slug
        FROM beaches b
        INNER JOIN beach_amenities a ON a.beach_id = b.id
        WHERE a.amenity = 'lifeguard'
        ORDER BY b.name ASC
        LIMIT 3
    ");
}

$safetyMapBeachIds = array_values(array_filter(array_map(static function ($id): string {
    if (!is_scalar($id)) {
        return '';
    }
    return trim((string)$id);
}, array_column($featuredBeaches, 'id'))));

$relatedGuides = [
    ['title' => 'Getting to Puerto Rico Beaches', 'slug' => 'getting-to-puerto-rico-beaches'],
    ['title' => 'Beach Packing List', 'slug' => 'beach-packing-list'],
    ['title' => 'Snorkeling Guide', 'slug' => 'snorkeling-guide']
];

$faqs = [
    [
        'question' => 'What should I do if I get caught in a rip current?',
        'answer' => 'Don\'t panic or swim directly toward shore. Instead, swim parallel to the beach until you\'re out of the current, then swim diagonally back to shore. If you can\'t escape, float or tread water and signal for help. Rip currents don\'t pull you under—they pull you out.'
    ],
    [
        'question' => 'Are there sharks in Puerto Rico waters?',
        'answer' => 'Yes, but shark attacks are extremely rare. Most sharks in Puerto Rico waters are small species that avoid humans. Follow standard precautions: don\'t swim at dawn/dusk, avoid murky water, don\'t wear shiny jewelry, and don\'t swim if bleeding.'
    ],
    [
        'question' => 'What is the emergency number in Puerto Rico?',
        'answer' => '911 works in Puerto Rico just like the U.S. mainland. For marine emergencies, the Coast Guard can be reached at (787) 289-2041. Most beaches with lifeguards also have emergency phones or stations.'
    ],
    [
        'question' => 'How can I tell if a beach is safe for swimming?',
        'answer' => 'Look for lifeguard presence, warning flag systems (green=safe, yellow=caution, red=dangerous), and posted signs. Balnearios (public beaches) have lifeguards and safety infrastructure. Check for strong waves, visible currents, and local advisories before entering.'
    ],
    [
        'question' => 'What should I do if stung by a jellyfish?',
        'answer' => 'Rinse with vinegar or salt water (never fresh water), remove tentacles with a card edge (don\'t use bare hands), and apply heat or cold pack for pain. Seek medical attention if you experience severe pain, difficulty breathing, or signs of allergic reaction.'
    ],
    [
        'question' => 'Is it safe to leave belongings on the beach?',
        'answer' => 'Theft can occur at beaches. Never leave valuables unattended. Use waterproof bags you can take in the water, lock items in your car trunk (out of sight), or have one person stay with belongings while others swim. Many beaches have paid lockers available.'
    ],
    [
        'question' => 'What sun protection factor (SPF) should I use?',
        'answer' => 'Use minimum SPF 30 broad-spectrum sunscreen, applying every 2 hours and after swimming. The Caribbean sun is intense year-round. Reef-safe sunscreen is required at some beaches and recommended everywhere to protect coral reefs.'
    ],
    [
        'question' => 'Are there dangerous currents at all Puerto Rico beaches?',
        'answer' => 'Not all beaches have dangerous currents, but ocean conditions change daily. North coast beaches generally have stronger currents and waves, especially in winter. South and west coast beaches tend to be calmer. Always check local conditions and ask lifeguards.'
    ]
];

$extraHead = $extraHead ?? "";
$extraHead .= articleSchema($pageTitle, $pageDescription, '/guides/beach-safety-tips', null, '2024-01-15');
$extraHead .= howToSchema(
    'How to Stay Safe at Puerto Rico Beaches',
    'Essential safety steps for enjoying Puerto Rico beaches without incidents',
    [
        ['name' => 'Check Beach Conditions', 'text' => 'Look for warning flags, lifeguard presence, and posted advisories before entering the water.'],
        ['name' => 'Apply Reef-Safe Sunscreen', 'text' => 'Use SPF 30+ broad-spectrum sunscreen and reapply every 2 hours and after swimming.'],
        ['name' => 'Identify Rip Current Escape Routes', 'text' => 'Learn to spot rip currents (darker, calmer channels) and know to swim parallel to shore if caught.'],
        ['name' => 'Secure Valuables', 'text' => 'Never leave belongings unattended—use waterproof bags, car trunk, or have someone stay with items.'],
        ['name' => 'Stay Hydrated', 'text' => 'Drink water regularly, especially in hot sun. Bring more water than you think you\'ll need.'],
        ['name' => 'Know Emergency Contacts', 'text' => 'Save 911 and local emergency numbers. Note nearest lifeguard station location.'],
        ['name' => 'Monitor Weather', 'text' => 'Check forecasts for storms, high surf advisories, and leave immediately if lightning appears.']
    ]
);
$extraHead .= faqSchema($faqs);
$extraHead .= breadcrumbSchema([
    ['name' => 'Home', 'url' => '/'],
    ['name' => 'Guides', 'url' => '/guides/'],
    ['name' => 'Beach Safety Tips', 'url' => '/guides/beach-safety-tips']
]);

$pageTheme = "guide";
$pageShellMode = "start";
include APP_ROOT . "/components/page-shell.php";
?>
    <?php
    $breadcrumbs = [
        ['name' => 'Home', 'url' => '/'],
        ['name' => 'Guides', 'url' => '/guides/'],
        ['name' => 'Beach Safety Tips']
    ];
    include APP_ROOT . '/components/hero-guide.php';
    ?>

    <main class="guide-layout">
        <aside class="guide-sidebar">
            <div class="guide-toc">
                    <h2 class="text-lg font-bold text-gray-900 mb-4">Table of Contents</h2>
                    <nav class="space-y-2">
                        <a href="#rip-currents" class="guide-toc-link">Rip Currents</a>
                        <a href="#sun-protection" class="guide-toc-link">Sun Protection</a>
                        <a href="#marine-life" class="guide-toc-link">Marine Life</a>
                        <a href="#water-quality" class="guide-toc-link">Water Quality</a>
                        <a href="#theft-security" class="guide-toc-link">Theft Prevention</a>
                        <a href="#weather" class="guide-toc-link">Weather Hazards</a>
                        <a href="#emergency" class="guide-toc-link">Emergency Contacts</a>
                        <a href="#faq" class="guide-toc-link">FAQ</a>
                    </nav>
                </div>
            </aside>

        <article class="guide-article bg-white rounded-lg shadow-card p-8">
                <div class="prose prose-lg max-w-none">
                    <p class="lead text-xl text-gray-700 mb-8">
                        Puerto Rico's beaches are stunning, but ocean environments demand respect and awareness. Every year,
                        preventable accidents occur when visitors underestimate water conditions, sun exposure, or marine hazards.
                        This comprehensive safety guide covers everything you need to know to protect yourself, your family, and
                        the environment while enjoying Puerto Rico's incredible coastline.
                    </p>

                    <h2 id="rip-currents" class="text-3xl font-bold text-gray-900 mt-12 mb-6">Understanding and Surviving Rip Currents</h2>

                    <p class="mb-4">
                        <strong>Rip currents are the leading cause of beach rescues and fatalities</strong> in Puerto Rico.
                        These powerful, narrow channels of water flow rapidly away from shore, reaching speeds of 5-8 feet per
                        second—faster than Olympic swimmers. They can occur at any beach with breaking waves, are invisible to
                        untrained eyes, and can form suddenly as wave and tide conditions change.
                    </p>

                    <h3 class="text-2xl font-bold text-gray-900 mt-8 mb-4">How to Spot Rip Currents</h3>

                    <p class="mb-4">
                        Look for these warning signs before entering the water:
                    </p>

                    <ul class="list-disc list-inside space-y-2 text-gray-700 mb-6">
                        <li><strong>Darker, calmer patches</strong> of water between areas with breaking waves</li>
                        <li><strong>Foam, seaweed, or debris</strong> moving steadily seaward</li>
                        <li><strong>Gaps in the wave pattern</strong> where waves aren't breaking</li>
                        <li><strong>Discolored or murky water</strong> channels extending from shore</li>
                        <li><strong>Areas where waves break on both sides</strong> but not in the middle</li>
                    </ul>

                    <p class="mb-4">
                        Rip currents are most common at beaches with sandbars, piers, jetties, or rocky formations that channel
                        water. North coast beaches like Crash Boat, Isabela, and Quebradillas experience stronger rip currents,
                        especially during winter when Atlantic swells increase.
                    </p>

                    <h3 class="text-2xl font-bold text-gray-900 mt-8 mb-4">Escape Strategy: Don't Fight the Current</h3>

                    <p class="mb-4">
                        If caught in a rip current, <strong>your instinct to swim directly toward shore will exhaust you</strong>.
                        Instead, follow this proven escape method:
                    </p>

                    <ol class="list-decimal list-inside space-y-3 text-gray-700 mb-6">
                        <li><strong>Stay calm and conserve energy</strong>—panic causes fatigue and poor decisions</li>
                        <li><strong>Don't swim against the current</strong>—you cannot overpower it</li>
                        <li><strong>Swim parallel to the shore</strong> (perpendicular to the current) until you escape the channel</li>
                        <li><strong>Once free, swim diagonally</strong> back toward shore at an angle</li>
                        <li><strong>If unable to escape, float or tread water</strong> and signal for help by waving and calling</li>
                        <li><strong>Let the current carry you out</strong> if necessary—rip currents dissipate beyond the surf zone</li>
                    </ol>

                    <p class="mb-4">
                        Remember: rip currents pull you away from shore, not underwater. You can breathe and float while waiting
                        for rescue. Many drownings occur when swimmers exhaust themselves fighting the current.
                    </p>

                    <?php if (!empty($featuredBeaches[0])): ?>
                    <div class="bg-green-50 border-l-4 border-green-600 p-6 my-8">
                        <h4 class="font-bold text-green-900 mb-2">Lifeguard-Protected Beach</h4>
                        <p class="text-green-800 mb-3">
                            <a href="/beach/<?php echo h($featuredBeaches[0]['slug']); ?>" class="text-green-600 font-semibold hover:underline">
                                <?php echo h($featuredBeaches[0]['name']); ?>
                            </a> has full-time lifeguards and safety infrastructure,
                            making it ideal for families and less experienced swimmers.
                        </p>
                    </div>
                    <?php endif; ?>

                    <h2 id="sun-protection" class="text-3xl font-bold text-gray-900 mt-12 mb-6">Sun Protection and Heat Safety</h2>

                    <p class="mb-4">
                        The Caribbean sun is intense year-round, especially between 10 AM and 4 PM when UV radiation peaks.
                        <strong>Sunburn can occur in as little as 15 minutes</strong> for fair-skinned individuals. Beyond
                        discomfort, excessive sun exposure causes long-term skin damage, premature aging, and increases melanoma risk.
                    </p>

                    <h3 class="text-2xl font-bold text-gray-900 mt-8 mb-4">Sunscreen Best Practices</h3>

                    <p class="mb-4">
                        Use <strong>SPF 30 or higher broad-spectrum sunscreen</strong> that protects against both UVA and UVB rays.
                        Apply generously 15-30 minutes before sun exposure—most people use only 25-50% of the recommended amount.
                        One ounce (a shot glass full) should cover your entire body.
                    </p>

                    <p class="mb-4">
                        <strong>Reapply every 2 hours</strong> and immediately after swimming, sweating, or toweling off, even if
                        labeled "waterproof" or "water-resistant." No sunscreen is truly waterproof; these products only resist
                        washing off for 40-80 minutes.
                    </p>

                    <h3 class="text-2xl font-bold text-gray-900 mt-8 mb-4">Reef-Safe Sunscreen Requirements</h3>

                    <p class="mb-4">
                        Puerto Rico law now <strong>prohibits sunscreens containing oxybenzone and octinoxate</strong>, chemicals
                        that damage coral reefs and marine ecosystems. Look for mineral-based sunscreens with zinc oxide or titanium
                        dioxide as active ingredients. These are labeled "reef-safe" or "reef-friendly."
                    </p>

                    <p class="mb-4">
                        Enforcement is particularly strict at marine reserves and protected areas. Bringing prohibited sunscreen to
                        beaches near coral reefs may result in confiscation. Popular reef-safe brands include Stream2Sea, ThinkSport,
                        and Blue Lizard.
                    </p>

                    <h3 class="text-2xl font-bold text-gray-900 mt-8 mb-4">Heat Exhaustion and Dehydration</h3>

                    <p class="mb-4">
                        High temperatures combined with humidity, sun exposure, and physical activity create serious heat illness risk.
                        <strong>Drink water consistently throughout the day</strong>—waiting until you feel thirsty means you're
                        already mildly dehydrated.
                    </p>

                    <p class="mb-4">
                        Bring at least one gallon of water per person for a full beach day. Avoid alcohol during peak sun hours, as
                        it accelerates dehydration. Watch for warning signs of heat exhaustion: excessive sweating, weakness, nausea,
                        dizziness, headache, or muscle cramps. Move to shade, drink water, and cool down immediately if symptoms appear.
                    </p>

                    <h3 class="text-2xl font-bold text-gray-900 mt-8 mb-4">Additional Sun Defense</h3>

                    <ul class="list-disc list-inside space-y-2 text-gray-700 mb-6">
                        <li>Wear <strong>wide-brimmed hats</strong> (3+ inches) to shade face, ears, and neck</li>
                        <li>Use <strong>UV-protective sunglasses</strong> (100% UVA/UVB blocking) to prevent eye damage</li>
                        <li>Consider <strong>rash guards or UV swim shirts</strong> (UPF 50+) for extended water time</li>
                        <li>Seek <strong>shade during peak hours</strong> (10 AM - 4 PM) when UV index is highest</li>
                        <li>Apply <strong>lip balm with SPF</strong>—lips burn easily and heal slowly</li>
                    </ul>

                    <h2 id="marine-life" class="text-3xl font-bold text-gray-900 mt-12 mb-6">Marine Life Encounters</h2>

                    <p class="mb-4">
                        Puerto Rico's waters host diverse marine life, most of which is harmless if you respect their space.
                        Understanding potential encounters helps you react appropriately and avoid injuries.
                    </p>

                    <h3 class="text-2xl font-bold text-gray-900 mt-8 mb-4">Jellyfish and Portuguese Man O' War</h3>

                    <p class="mb-4">
                        <strong>Jellyfish populations fluctuate seasonally</strong>, with higher concentrations from April through
                        August. Most jellyfish in Puerto Rico deliver mild stings causing temporary pain and irritation. The
                        Portuguese Man O' War, while rare, produces more severe stings requiring medical attention.
                    </p>

                    <p class="mb-4">
                        If stung, exit the water immediately. <strong>Rinse with vinegar or salt water</strong>—never use fresh water,
                        as it can trigger remaining nematocysts to fire. Remove visible tentacles using a card edge or towel (never
                        bare hands). Apply heat or cold packs for pain relief. Seek emergency care if you experience difficulty
                        breathing, chest pain, or signs of allergic reaction.
                    </p>

                    <h3 class="text-2xl font-bold text-gray-900 mt-8 mb-4">Sea Urchins</h3>

                    <p class="mb-4">
                        Black sea urchins cluster on rocky areas, reef edges, and tide pools. Their spines easily penetrate water
                        shoes and skin, breaking off and causing painful wounds. <strong>Watch where you step</strong> when entering
                        from rocky shores or walking in shallow areas with visible rocks.
                    </p>

                    <p class="mb-4">
                        If stepped on, remove visible spines with tweezers, soak the area in hot water (as hot as tolerable) for
                        30-90 minutes to dissolve protein-based toxins, and monitor for signs of infection. Seek medical care if
                        spines are deep, near joints, or if redness and swelling develop.
                    </p>

                    <h3 class="text-2xl font-bold text-gray-900 mt-8 mb-4">Sharks and Barracudas</h3>

                    <p class="mb-4">
                        <strong>Shark attacks are extraordinarily rare in Puerto Rico</strong>, with only a handful of incidents
                        recorded in modern history. Most sharks in local waters are small species that actively avoid humans.
                        Similarly, barracudas look intimidating but rarely approach swimmers.
                    </p>

                    <p class="mb-4">
                        Reduce already-minimal risk by avoiding swimming at dawn, dusk, or night when sharks feed. Don't wear shiny
                        jewelry that resembles fish scales. Avoid murky water or areas with schools of baitfish. Never swim if bleeding
                        from cuts or wounds.
                    </p>

                    <h3 class="text-2xl font-bold text-gray-900 mt-8 mb-4">Stingrays</h3>

                    <p class="mb-4">
                        Stingrays bury themselves in sand in shallow water. Most injuries occur when someone steps directly on a ray,
                        causing it to whip its barbed tail in defense. <strong>Do the "stingray shuffle"</strong>—slide your feet
                        along the bottom rather than taking steps. This gives rays time to swim away before you reach them.
                    </p>

                    <?php if (!empty($featuredBeaches[1])): ?>
                    <div class="bg-green-50 border-l-4 border-green-600 p-6 my-8">
                        <h4 class="font-bold text-green-900 mb-2">Calm, Clear Waters</h4>
                        <p class="text-green-800 mb-3">
                            <a href="/beach/<?php echo h($featuredBeaches[1]['slug']); ?>" class="text-green-600 font-semibold hover:underline">
                                <?php echo h($featuredBeaches[1]['name']); ?>
                            </a> offers calm conditions and visibility,
                            making it easier to spot and avoid marine life.
                        </p>
                    </div>
                    <?php endif; ?>

                    <h2 id="water-quality" class="text-3xl font-bold text-gray-900 mt-12 mb-6">Water Quality and Pollution</h2>

                    <p class="mb-4">
                        Water quality varies significantly across Puerto Rico's beaches, influenced by weather, sewage systems,
                        river runoff, and proximity to development. <strong>Swimming in contaminated water</strong> can cause ear
                        infections, skin rashes, gastrointestinal illness, and respiratory problems.
                    </p>

                    <h3 class="text-2xl font-bold text-gray-900 mt-8 mb-4">When to Avoid Swimming</h3>

                    <ul class="list-disc list-inside space-y-2 text-gray-700 mb-6">
                        <li><strong>After heavy rainfall</strong>—runoff carries bacteria, sewage, and debris into coastal waters</li>
                        <li><strong>Near river mouths</strong> following storms—elevated bacteria levels persist 2-3 days</li>
                        <li><strong>When posted advisories exist</strong>—respect beach closure signs and water quality warnings</li>
                        <li><strong>If water appears discolored, murky, or has unusual odor</strong>—clear water doesn't guarantee safety but is a positive indicator</li>
                        <li><strong>Near storm drains or outflow pipes</strong>—these discharge untreated runoff</li>
                    </ul>

                    <p class="mb-4">
                        The Puerto Rico Department of Health monitors water quality at popular beaches and issues advisories when
                        bacteria levels exceed safe thresholds. Check their website or look for posted signs at beach entrances
                        before swimming.
                    </p>

                    <h2 id="theft-security" class="text-3xl font-bold text-gray-900 mt-12 mb-6">Theft Prevention and Security</h2>

                    <p class="mb-4">
                        <strong>Theft is the most common crime at Puerto Rico beaches</strong>, particularly at remote, unattended
                        beaches and parking areas. Criminals target rental cars and unattended belongings, knowing tourists carry
                        valuables like phones, cameras, wallets, and passports.
                    </p>

                    <h3 class="text-2xl font-bold text-gray-900 mt-8 mb-4">Protecting Your Belongings</h3>

                    <ul class="list-disc list-inside space-y-3 text-gray-700 mb-6">
                        <li><strong>Never leave valuables unattended</strong> on the beach, even briefly</li>
                        <li><strong>Use waterproof pouches</strong> worn around neck or waist to carry essentials in the water</li>
                        <li><strong>Lock everything in car trunk</strong> before arriving at the beach—don't move items to trunk in parking lot</li>
                        <li><strong>Don't leave items visible in car</strong>—even phone chargers signal valuables may be present</li>
                        <li><strong>Take turns swimming</strong> if in a group, so someone watches belongings</li>
                        <li><strong>Use beach locker facilities</strong> when available (many balnearios offer them for $5-10)</li>
                        <li><strong>Bring minimal cash and copies</strong> of important documents rather than originals</li>
                    </ul>

                    <h3 class="text-2xl font-bold text-gray-900 mt-8 mb-4">Vehicle Security</h3>

                    <p class="mb-4">
                        Rental cars are obvious targets. <strong>Park in well-lit, populated areas</strong> near beach entrances when
                        possible. Official balneario parking lots have better security than roadside parking. Never leave rental
                        agreements, tourist maps, or guidebooks visible—these advertise you're a visitor.
                    </p>

                    <p class="mb-4">
                        If you find your car broken into, file a police report immediately for insurance purposes. Contact your rental
                        company and credit card company (if you used card coverage instead of rental insurance).
                    </p>

                    <h2 id="weather" class="text-3xl font-bold text-gray-900 mt-12 mb-6">Weather Hazards</h2>

                    <p class="mb-4">
                        Caribbean weather can change rapidly. <strong>Monitor forecasts daily</strong> and be prepared to adjust plans
                        based on conditions. Lightning, tropical storms, and high surf pose serious risks.
                    </p>

                    <h3 class="text-2xl font-bold text-gray-900 mt-8 mb-4">Lightning Safety</h3>

                    <p class="mb-4">
                        If you hear thunder or see lightning, <strong>exit the water immediately</strong>. Water conducts electricity
                        extremely well; you don't need to be directly struck to be injured or killed. Seek shelter in a building or
                        vehicle (not under trees or beach shelters). Wait 30 minutes after the last thunder before returning to the water.
                    </p>

                    <h3 class="text-2xl font-bold text-gray-900 mt-8 mb-4">High Surf and Storm Swells</h3>

                    <p class="mb-4">
                        North coast beaches experience dangerous surf during winter months (November-March) when Atlantic storms generate
                        large swells. <strong>High surf advisories</strong> are issued when wave heights reach 8+ feet. These conditions
                        create powerful shore break, dangerous rip currents, and can wash people off rocks.
                    </p>

                    <p class="mb-4">
                        Check surf forecasts at Surfline.com or NOAA before visiting north coast beaches. Respect red warning flags and
                        beach closures. Even experienced swimmers can be overpowered by storm surf.
                    </p>

                    <h3 class="text-2xl font-bold text-gray-900 mt-8 mb-4">Hurricane Season</h3>

                    <p class="mb-4">
                        Atlantic hurricane season runs <strong>June 1 through November 30</strong>, with peak activity August-October.
                        Monitor tropical weather closely if traveling during these months. Beaches close when tropical storm or hurricane
                        warnings are issued. Dangerous surf and rip currents can appear 2-3 days before storms arrive.
                    </p>

                    <h2 id="emergency" class="text-3xl font-bold text-gray-900 mt-12 mb-6">Emergency Contacts and Response</h2>

                    <p class="mb-4">
                        Knowing who to call and how to respond in emergencies can save lives. <strong>Save these numbers</strong>
                        in your phone before heading to beaches:
                    </p>

                    <div class="bg-gray-50 rounded-lg p-6 my-6">
                        <h3 class="text-xl font-bold text-gray-900 mb-4">Critical Emergency Numbers</h3>
                        <ul class="space-y-2 text-gray-700">
                            <li><strong>Emergency (Police, Fire, EMS):</strong> 911</li>
                            <li><strong>U.S. Coast Guard:</strong> (787) 289-2041</li>
                            <li><strong>Poison Control Center:</strong> 1-800-222-1222</li>
                            <li><strong>Tourist Police:</strong> (787) 726-7020</li>
                            <li><strong>Department of Natural Resources:</strong> (787) 999-2200</li>
                        </ul>
                    </div>

                    <h3 class="text-2xl font-bold text-gray-900 mt-8 mb-4">What to Report</h3>

                    <p class="mb-4">
                        When calling 911 from a beach, provide:
                    </p>

                    <ul class="list-disc list-inside space-y-2 text-gray-700 mb-6">
                        <li><strong>Exact beach name and nearest town</strong></li>
                        <li><strong>GPS coordinates if available</strong> (from phone maps app)</li>
                        <li><strong>Visible landmarks</strong> (parking lot, lifeguard station, kilometer markers)</li>
                        <li><strong>Nature of emergency</strong> (drowning, injury, medical issue)</li>
                        <li><strong>Number of people involved</strong></li>
                    </ul>

                    <h3 class="text-2xl font-bold text-gray-900 mt-8 mb-4">First Aid Essentials</h3>

                    <p class="mb-4">
                        Carry a basic beach first aid kit containing bandages, antibiotic ointment, pain relievers, antihistamine
                        (for allergic reactions), tweezers (for splinters/spines), and any personal medications. Many beach injuries
                        are minor cuts, scrapes, and stings that don't require emergency response but benefit from immediate treatment.
                    </p>

                    <h2 id="faq" class="text-3xl font-bold text-gray-900 mt-12 mb-6">Frequently Asked Questions</h2>

                    <div class="space-y-6">
                        <?php foreach ($faqs as $faq): ?>
                        <div class="border-l-4 border-green-600 pl-4">
                            <h3 class="text-xl font-bold text-gray-900 mb-2"><?php echo h($faq['question']); ?></h3>
                            <p class="text-gray-700"><?php echo h($faq['answer']); ?></p>
                        </div>
                        <?php endforeach; ?>
                    </div>

                    <?php
                    $guideMapIds = $safetyMapBeachIds;
                    $guideMapTitle = 'Find Safe Beaches';
                    $guideMapDescription = 'Browse beaches with lifeguards, safety amenities, and calm conditions perfect for families.';
                    $guideMapButtonLabel = 'View Lifeguard-Protected Beaches';
                    $guideMapEmptyNotice = 'No safe-beach picks from this guide are available on the map right now.';
                    include APP_ROOT . '/components/guide-map-panel.php';
                    ?>
                </div>

                <div class="mt-12 pt-8 border-t border-gray-200">
                    <h3 class="text-xl font-bold text-gray-900 mb-4">Related Guides</h3>
                    <div class="related-guides-grid">
                        <?php foreach ($relatedGuides as $guide): ?>
                        <a href="/guides/<?php echo h($guide['slug']); ?>" class="related-guide-card">
                            <span class="related-guide-title"><?php echo h($guide['title']); ?></span>
                        </a>
                        <?php endforeach; ?>
                    </div>
                </div>
            </article>
        </div>
    </main>

<?php
$pageShellMode = "end";
include APP_ROOT . "/components/page-shell.php";
?>
