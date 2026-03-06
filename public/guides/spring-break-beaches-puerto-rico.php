<?php
require_once $_SERVER['DOCUMENT_ROOT'] . '/../bootstrap.php';

require_once APP_ROOT . '/inc/db.php';
require_once APP_ROOT . '/inc/helpers.php';
require_once APP_ROOT . '/components/seo-schemas.php';
require_once APP_ROOT . '/inc/affiliate.php';

$pageTitle = 'Best Puerto Rico Beaches for Spring Break 2026';
$pageDescription = 'Your complete guide to Puerto Rico spring break beaches — from Isla Verde party vibes and Rincón surf breaks to Culebra\'s crystal waters and Vieques\' wild shores.';

// Party & Nightlife Beaches (San Juan)
$party_beaches = query("SELECT id, name, municipality, slug, description, cover_image FROM beaches
    WHERE slug IN ('condado-beach', 'ocean-park-beach-san-juan-18452-6606', 'escambron-beach')
    AND publish_status = 'published'
    ORDER BY google_rating DESC");

// Surf Beaches (Rincón & West Coast)
$surf_beaches = query("SELECT id, name, municipality, slug, description, cover_image FROM beaches
    WHERE slug IN ('playa-do-a-lala-beach', 'playa-c-rcega', 'jobos-beach-isabela-18513-67085', 'montones-beach-isabela-18506-67081')
    AND publish_status = 'published'
    ORDER BY google_rating DESC");

// Day Trips (Culebra)
$culebra_beaches = query("SELECT id, name, municipality, slug, description, cover_image FROM beaches
    WHERE slug IN ('flamenco-beach-culebra-18329-65318', 'carlos-rosario-beach-culebra-18327-65308', 'tamarindo-beach-culebra-culebra-18326-65313')
    AND publish_status = 'published'
    ORDER BY google_rating DESC");

// Off the Beaten Path (Vieques)
$vieques_beaches = query("SELECT id, name, municipality, slug, description, cover_image FROM beaches
    WHERE slug IN ('sun-bay-vieques-18097-65457', 'pata-prieta-secret-beach-vieques-18098-65412', 'mosquito-bay-beach')
    AND publish_status = 'published'
    ORDER BY google_rating DESC");

// Attach tags & amenities to all groups (avoids N+1 queries)
attachBeachMetadata($party_beaches);
attachBeachMetadata($surf_beaches);
attachBeachMetadata($culebra_beaches);
attachBeachMetadata($vieques_beaches);

// Semantic tag colors — used across all 4 beach sections
$tagColor = static fn(string $tag): string => match($tag) {
    'snorkeling'      => 'bg-teal-50 text-teal-700',
    'surfing'         => 'bg-amber-50 text-amber-700',
    'popular'         => 'bg-red-50 text-red-700',
    'calm-waters'     => 'bg-green-50 dark:bg-green-900/40 text-green-700 dark:text-green-300',
    'scenic'          => 'bg-purple-50 text-purple-700',
    'family-friendly' => 'bg-pink-50 text-pink-700',
    'swimming'        => 'bg-sky-50 text-sky-700',
    'diving'          => 'bg-indigo-50 text-indigo-700',
    'accessible'      => 'bg-gray-100 dark:bg-slate-700 text-gray-600 dark:text-slate-200',
    default           => 'bg-gray-100 text-gray-600',
};

// English-only tag labels — bypasses i18n so this EN-only page never shows Spanish
$tagLabel = static fn(string $tag): string => ([
    'calm-waters'     => 'Calm Water',
    'surfing'         => 'Surfing',
    'snorkeling'      => 'Snorkeling',
    'family-friendly' => 'Family-friendly',
    'accessible'      => 'Accessible',
    'secluded'        => 'Secluded',
    'popular'         => 'Popular',
    'scenic'          => 'Scenic',
    'swimming'        => 'Swimming',
    'diving'          => 'Diving',
    'fishing'         => 'Fishing',
    'camping'         => 'Camping',
][$tag] ?? ucwords(str_replace('-', ' ', $tag)));

// Hero background: use Condado beach cover image if available
$heroBeach = queryOne(
    "SELECT cover_image FROM beaches WHERE slug = 'condado-beach' AND publish_status = 'published'"
);
$heroImage = $heroBeach['cover_image'] ?? '';

// Build combined map IDs from all groups
$toMapIds = static function (array $beaches): array {
    return array_values(array_filter(array_map(static function ($id): string {
        if (!is_scalar($id)) {
            return '';
        }
        return trim((string)$id);
    }, array_column($beaches, 'id'))));
};

$allMapIds = array_merge(
    $toMapIds($party_beaches),
    $toMapIds($surf_beaches),
    $toMapIds($culebra_beaches),
    $toMapIds($vieques_beaches)
);

$relatedGuides = [
    ['title' => 'Culebra vs Vieques', 'slug' => 'culebra-vs-vieques'],
    ['title' => 'Puerto Rico Surfing Guide', 'slug' => 'surfing-guide'],
    ['title' => 'Bioluminescent Bays Guide', 'slug' => 'bioluminescent-bays'],
];

$faqs = [
    [
        'question' => 'When is spring break in Puerto Rico in 2026?',
        'answer' => 'Spring break in Puerto Rico peaks between March 7 and April 5, 2026, with the heaviest crowds typically March 12–30. Most US colleges and universities schedule their breaks within this window, making it the busiest beach period of the year.',
    ],
    [
        'question' => 'Do I need a passport to go to Puerto Rico for spring break?',
        'answer' => 'No. Puerto Rico is a US territory, so American citizens only need a valid government-issued photo ID — the same as any domestic flight. International visitors follow standard US entry requirements.',
    ],
    [
        'question' => 'What is the best beach in Puerto Rico for spring break?',
        'answer' => 'It depends on your style. For a classic party beach scene with bars and nightlife, Condado Beach and Ocean Park in San Juan are the top picks. For the most beautiful beach in the Caribbean, Flamenco Beach on Culebra is hard to beat. For surfing, Rincón\'s María\'s Beach is legendary during spring swell season (February–April).',
    ],
    [
        'question' => 'How do I get to Culebra for spring break?',
        'answer' => 'Take the ferry from Ceiba (about 90 minutes east of San Juan) or fly from Isla Grande or Luis Muñoz Marín Airport. Book ferry tickets as early as possible — they sell out 4–6 weeks in advance during spring break. Arrive at the terminal well before departure time, as standby spots are very limited.',
    ],
    [
        'question' => 'Is Puerto Rico safe for spring break?',
        'answer' => 'Yes. The main tourist beach areas of Condado, Isla Verde, and Ocean Park are well-patrolled and safe. As with any destination, use common sense: stay in groups at night, avoid displaying expensive items, and stick to populated beach areas. The resort strips are very walkable and lively during spring break.',
    ],
];

$extraHead = $extraHead ?? '';
$extraHead .= articleSchema($pageTitle, $pageDescription, '/guides/spring-break-beaches-puerto-rico', null, '2026-02-22');
$extraHead .= faqSchema($faqs);
$extraHead .= breadcrumbSchema([
    ['name' => 'Home', 'url' => '/'],
    ['name' => 'Guides', 'url' => '/guides/'],
    ['name' => 'Spring Break Beaches', 'url' => '/guides/spring-break-beaches-puerto-rico'],
]);

$pageTheme = "guide";
$pageShellMode = "start";
include APP_ROOT . "/components/page-shell.php";
?>
    <?php
    $breadcrumbs = [
        ['name' => 'Home', 'url' => '/'],
        ['name' => 'Guides', 'url' => '/guides/'],
        ['name' => 'Spring Break Beaches'],
    ];
    $heroCtas = '<div class="flex gap-3 flex-wrap mt-6">'
        . '<a href="' . h(AFFILIATE_LINKS['flights_sju']) . '" class="inline-flex items-center gap-2 bg-blue-600 text-white font-semibold text-sm px-5 py-3 rounded-lg hover:bg-blue-700 transition-colors" rel="nofollow sponsored" target="_blank">✈️ Search Flights</a>'
        . '<a href="' . h(AFFILIATE_LINKS['hotels_sanjuan']) . '" class="inline-flex items-center gap-2 font-semibold text-sm px-5 py-3 rounded-lg hover:opacity-90 transition-colors" style="background:rgba(255,255,255,0.12);color:#fff;border:1px solid rgba(255,255,255,0.25);" rel="nofollow sponsored" target="_blank">🏨 Browse Hotels</a>'
        . '<a href="/mapa" class="inline-flex items-center gap-2 font-semibold text-sm px-5 py-3 rounded-lg hover:opacity-90 transition-colors" style="background:rgba(255,255,255,0.12);color:#fff;border:1px solid rgba(255,255,255,0.25);">🗺️ View Beach Map</a>'
        . '</div>';
    include APP_ROOT . '/components/hero-guide.php';
    ?>

    <main class="guide-layout">
        <aside class="guide-sidebar">
            <div class="guide-toc">
                <h2 class="text-lg font-bold text-gray-900 mb-4">Table of Contents</h2>
                <nav class="space-y-2">
                    <a href="#why-pr"  class="guide-toc-link" style="display:flex;align-items:center;gap:8px;"><span style="width:6px;height:6px;border-radius:50%;background:#60a5fa;display:inline-block;flex-shrink:0;"></span>Why Puerto Rico?</a>
                    <a href="#party"   class="guide-toc-link" style="display:flex;align-items:center;gap:8px;"><span style="width:6px;height:6px;border-radius:50%;background:#f59e0b;display:inline-block;flex-shrink:0;"></span>Party &amp; Nightlife</a>
                    <a href="#surf"    class="guide-toc-link" style="display:flex;align-items:center;gap:8px;"><span style="width:6px;height:6px;border-radius:50%;background:#06b6d4;display:inline-block;flex-shrink:0;"></span>Surf Beaches</a>
                    <a href="#culebra" class="guide-toc-link" style="display:flex;align-items:center;gap:8px;"><span style="width:6px;height:6px;border-radius:50%;background:#10b981;display:inline-block;flex-shrink:0;"></span>Culebra Day Trips</a>
                    <a href="#vieques" class="guide-toc-link" style="display:flex;align-items:center;gap:8px;"><span style="width:6px;height:6px;border-radius:50%;background:#8b5cf6;display:inline-block;flex-shrink:0;"></span>Vieques</a>
                    <a href="#tips"    class="guide-toc-link" style="display:flex;align-items:center;gap:8px;"><span style="width:6px;height:6px;border-radius:50%;background:#9ca3af;display:inline-block;flex-shrink:0;"></span>Planning Tips</a>
                    <a href="#faq"     class="guide-toc-link" style="display:flex;align-items:center;gap:8px;"><span style="width:6px;height:6px;border-radius:50%;background:#9ca3af;display:inline-block;flex-shrink:0;"></span>FAQ</a>
                </nav>
            </div>
        </aside>

        <article class="guide-article bg-white rounded-lg shadow-card p-8">
            <div class="prose prose-lg max-w-none">

                <p class="text-xs text-gray-400 italic mb-6">
                    This guide contains affiliate links. If you book through them, we may earn a small commission at no extra cost to you.
                </p>

                <p class="lead text-xl text-gray-700 mb-8">
                    Puerto Rico is consistently one of the top spring break destinations in the Caribbean — and for good reason. No passport required, direct flights from major US cities, year-round 83°F weather, and some of the most beautiful beaches in the hemisphere. Whether you're after the party scene, the perfect wave, crystal-clear snorkeling water, or a quiet island escape, Puerto Rico has a beach for your vibe.
                </p>

                <h2 id="why-pr" class="text-3xl font-bold text-gray-900 mt-12 mb-6">Why Puerto Rico for Spring Break?</h2>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-4 my-6">
                    <div class="rounded-lg p-4" style="background:#1e3a5f;">
                        <p class="font-semibold text-white">No passport needed</p>
                        <p class="text-sm" style="color:rgba(255,255,255,0.65)">US citizens only need a valid photo ID — same as any domestic flight. No customs, no exchange rate.</p>
                    </div>
                    <div class="rounded-lg p-4" style="background:#1e3a5f;">
                        <p class="font-semibold text-white">Direct flights from major hubs</p>
                        <p class="text-sm" style="color:rgba(255,255,255,0.65)">Non-stop service from New York, Miami, Boston, Chicago, Atlanta, Philadelphia, and more.</p>
                    </div>
                    <div class="rounded-lg p-4" style="background:#1e3a5f;">
                        <p class="font-semibold text-white">Perfect March weather</p>
                        <p class="text-sm" style="color:rgba(255,255,255,0.65)">Average 83°F, low humidity, nearly zero hurricane risk. One of the driest months of the year.</p>
                    </div>
                    <div class="rounded-lg p-4" style="background:#1e3a5f;">
                        <p class="font-semibold text-white">US dollars everywhere</p>
                        <p class="text-sm" style="color:rgba(255,255,255,0.65)">No currency exchange hassle. ATMs everywhere, credit cards widely accepted.</p>
                    </div>
                </div>

                <p class="text-gray-700 mb-4">
                    Puerto Rico also ranked among Spirit Airlines' top spring break destinations for 2026 — meaning flight deals are plentiful and competition for bookings is real. Lock in flights and hotels early.
                </p>

                <?php
                $stripFlight  = affiliateCTA('flights_sju',    'Search Flights',  'primary');
                $stripSJ      = affiliateCTA('hotels_sanjuan', 'San Juan Hotels', 'primary');
                $stripRincon  = affiliateCTA('hotels_rincon',  'Rincón Hotels',   'primary');
                $stripCars    = affiliateCTA('cars_sju',       'Rent a Car',      'primary');
                if ($stripFlight || $stripSJ || $stripRincon || $stripCars): ?>
                <div class="bg-blue-50 border border-blue-100 rounded-xl p-4 my-6">
                    <div class="flex flex-wrap items-center gap-3">
                        <p class="text-sm text-amber-500 dark:text-amber-400 font-medium flex-1 min-w-40">
                            🔥 Spring break sells out fast — lock in now
                        </p>
                        <?= $stripFlight ?>
                        <?= $stripSJ ?>
                        <?= $stripRincon ?>
                        <?= $stripCars ?>
                    </div>
                </div>
                <?php endif; ?>

                <div class="flex items-center gap-3 mt-12 mb-3">
                    <div class="w-8 h-8 bg-amber-100 rounded-lg flex items-center justify-center text-amber-600 text-base">🎉</div>
                    <span class="text-xs font-semibold uppercase tracking-widest text-amber-600 dark:text-amber-400">Party &amp; Nightlife</span>
                </div>
                <h2 id="party" class="text-3xl font-bold text-gray-900 mb-4">San Juan Beaches</h2>

                <p class="text-gray-700 mb-6">
                    San Juan's beachfront neighborhoods of <strong>Condado</strong> and <strong>Isla Verde</strong> are the epicenter of spring break activity. The strips are lined with hotels, beach bars, rooftop lounges, and clubs open until 4 AM. You can walk from beach to bar to restaurant without ever getting in a car — and the party doesn't stop when the sun goes down.
                </p>

                <?php if (!empty(AFFILIATE_LINKS['hotels_sanjuan'])): ?>
                <p class="text-xs font-semibold uppercase tracking-widest text-gray-400 mb-2 mt-6">🏨 Where to Stay</p>
                <div class="grid grid-cols-1 md:grid-cols-3 gap-3 my-4">
                    <?php foreach ([
                        ['La Concha Resort',  '4.4', 'Condado',    '199', '/images/thumbnails/LaConchaOffersBG-1758068284593.webp'],
                        ['AC Hotel San Juan', '4.5', 'Condado',    '159', '/images/thumbnails/trypHotelDeals-1757619761075.webp'],
                        ['El San Juan Hotel', '4.2', 'Isla Verde', '229', '/images/thumbnails/FairmontHotel-1757084065163.webp'],
                    ] as [$hotel, $rating, $area, $price, $photo]): ?>
                    <a href="<?= h(AFFILIATE_LINKS['hotels_sanjuan']) ?>"
                       rel="nofollow sponsored" target="_blank"
                       class="flex flex-col border border-gray-200 rounded-xl overflow-hidden hover:border-blue-400 hover:shadow-md transition-all bg-white group no-underline">
                        <div class="relative h-36 overflow-hidden">
                            <img src="<?= h($photo) ?>"
                                 alt="<?= h($hotel) ?>"
                                 class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-300"
                                 loading="lazy"
                                 data-fallback-src="/images/beaches/placeholder-beach.webp">
                            <span class="absolute top-2 left-2 bg-white/90 backdrop-blur-sm text-amber-800 text-xs font-semibold px-2 py-0.5 rounded-full shadow-sm">
                                ⭐ <?= h($rating) ?>
                            </span>
                        </div>
                        <div class="p-4">
                            <p class="font-semibold text-gray-900 text-sm group-hover:text-blue-700"><?= h($hotel) ?></p>
                            <p class="text-xs text-gray-500"><?= h($area) ?></p>
                            <p class="text-xs text-green-700 font-semibold mt-1">From ~$<?= h($price) ?>/night</p>
                            <span class="block mt-2 text-center bg-blue-600 text-white text-xs font-semibold py-2 px-3 rounded-lg">Book on Expedia →</span>
                        </div>
                    </a>
                    <?php endforeach; ?>
                </div>
                <?php endif; ?>

                <?php if (!empty($party_beaches)): ?>
                <p class="text-xs font-semibold uppercase tracking-widest text-gray-400 mb-2 mt-6">🏖 Beaches</p>
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 mb-6">
                    <?php
                    $beachCount = count($party_beaches);
                    $i = 0;
                    foreach ($party_beaches as $beach):
                        $i++;
                        $isLastOdd = ($i === $beachCount && $beachCount % 2 === 1);
                        $thumb = !empty($beach['cover_image'])
                            ? htmlspecialchars($beach['cover_image'], ENT_QUOTES, 'UTF-8')
                            : '/images/beaches/placeholder-beach.webp';
                        $desc = !empty($beach['description'])
                            ? mb_substr(strip_tags($beach['description']), 0, 100) . '…'
                            : '';
                        $tags = array_slice($beach['tags'] ?? [], 0, 3);
                    ?>
                    <a href="/beach/<?= h($beach['slug']) ?>"
                       class="flex flex-col bg-white border border-gray-200 rounded-xl overflow-hidden shadow-sm hover:shadow-md hover:border-blue-300 transition-all group no-underline <?= $isLastOdd ? 'col-span-2' : '' ?>">
                        <div class="relative h-40 overflow-hidden flex-shrink-0">
                            <img src="<?= $thumb ?>" alt="<?= h($beach['name']) ?>"
                                 class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-300"
                                 loading="lazy">
                        </div>
                        <div class="p-4 flex-1 flex flex-col">
                            <p class="font-bold text-gray-900 group-hover:text-blue-700 transition-colors"><?= h($beach['name']) ?></p>
                            <p class="text-xs text-gray-500 mb-2"><?= h($beach['municipality']) ?></p>
                            <?php if ($desc): ?>
                            <p class="text-sm text-gray-600 leading-snug flex-1"><?= h($desc) ?></p>
                            <?php endif; ?>
                            <?php if ($tags): ?>
                            <div class="flex flex-wrap gap-1 mt-3">
                                <?php foreach ($tags as $tag): ?>
                                <span class="text-xs <?= $tagColor($tag) ?> px-2 py-0.5 rounded-full"><?= h($tagLabel($tag)) ?></span>
                                <?php endforeach; ?>
                            </div>
                            <?php endif; ?>
                        </div>
                    </a>
                    <?php endforeach; ?>
                </div>
                <?php endif; ?>

                <div class="bg-amber-50 border border-amber-200 rounded-lg p-4 mb-4">
                    <p class="text-amber-800 text-sm"><strong>Local tip:</strong> Clubs in Isla Verde and Condado often have free entry before midnight with guest list signup on Instagram. La Placita de Santurce is a must — an outdoor plaza that becomes a street party every weekend night, with salsa spilling out of every bar.</p>
                </div>

                <?php $sjCta = affiliateCTA('hotels_sanjuan', 'Book San Juan Hotels'); ?>
                <?php if ($sjCta): ?>
                <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 bg-gray-50 border border-gray-200 rounded-xl p-4 mt-4 mb-8">
                    <div>
                        <p class="text-sm font-semibold text-gray-800">Hotels from ~$159/night in March</p>
                        <p class="text-xs text-gray-500">Prices rise fast during spring break</p>
                    </div>
                    <?= $sjCta ?>
                </div>
                <?php endif; ?>

                <div class="flex items-center gap-3 mt-12 mb-3">
                    <div class="w-8 h-8 bg-cyan-100 rounded-lg flex items-center justify-center text-cyan-600 text-base">🏄</div>
                    <span class="text-xs font-semibold uppercase tracking-widest text-cyan-600 dark:text-cyan-400">Surf Beaches</span>
                </div>
                <h2 id="surf" class="text-3xl font-bold text-gray-900 mb-4">Rincón &amp; the West Coast</h2>

                <p class="text-gray-700 mb-6">
                    February through April is prime surf season on Puerto Rico's west coast. <strong>Rincón</strong> — known as the "Hawaii of the Caribbean" — hosted the 1968 World Surfing Championships and regularly draws international pros during spring swells. The vibe here is laid-back and bohemian: rum punch at sunset rather than nightclubs. <strong>Isabela</strong>, an hour north, delivers equally excellent waves with a quieter crowd.
                </p>

                <?php if (!empty(AFFILIATE_LINKS['hotels_rincon'])): ?>
                <p class="text-xs font-semibold uppercase tracking-widest text-gray-400 mb-2 mt-6">🏨 Where to Stay</p>
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3 my-4">
                    <?php foreach ([
                        ['Rincon Beach Resort', '4.3', 'Rincón', '149', '/images/thumbnails/Lazy-Parrot-Hotel-Rincon-1761899804209.webp'],
                        ['Casa Isleña Inn',     '4.6', 'Rincón', '125', '/images/thumbnails/ClubCalaResort-1757079250606.webp'],
                    ] as [$hotel, $rating, $area, $price, $photo]): ?>
                    <a href="<?= h(AFFILIATE_LINKS['hotels_rincon']) ?>"
                       rel="nofollow sponsored" target="_blank"
                       class="flex flex-col border border-gray-200 rounded-xl overflow-hidden hover:border-green-400 hover:shadow-md transition-all bg-white group no-underline">
                        <div class="relative h-36 overflow-hidden">
                            <img src="<?= h($photo) ?>"
                                 alt="<?= h($hotel) ?>"
                                 class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-300"
                                 loading="lazy"
                                 data-fallback-src="/images/beaches/placeholder-beach.webp">
                            <span class="absolute top-2 left-2 bg-white/90 backdrop-blur-sm text-amber-800 text-xs font-semibold px-2 py-0.5 rounded-full shadow-sm">
                                ⭐ <?= h($rating) ?>
                            </span>
                        </div>
                        <div class="p-4">
                            <p class="font-semibold text-gray-900 text-sm group-hover:text-green-700"><?= h($hotel) ?></p>
                            <p class="text-xs text-gray-500"><?= h($area) ?></p>
                            <p class="text-xs text-green-700 font-semibold mt-1">From ~$<?= h($price) ?>/night</p>
                            <span class="block mt-2 text-center bg-blue-600 text-white text-xs font-semibold py-2 px-3 rounded-lg">Book on Expedia →</span>
                        </div>
                    </a>
                    <?php endforeach; ?>
                </div>
                <?php endif; ?>

                <?php if (!empty($surf_beaches)): ?>
                <p class="text-xs font-semibold uppercase tracking-widest text-gray-400 mb-2 mt-6">🏖 Beaches</p>
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 mb-6">
                    <?php
                    $beachCount = count($surf_beaches);
                    $i = 0;
                    foreach ($surf_beaches as $beach):
                        $i++;
                        $isLastOdd = ($i === $beachCount && $beachCount % 2 === 1);
                        $thumb = !empty($beach['cover_image'])
                            ? htmlspecialchars($beach['cover_image'], ENT_QUOTES, 'UTF-8')
                            : '/images/beaches/placeholder-beach.webp';
                        $desc = !empty($beach['description'])
                            ? mb_substr(strip_tags($beach['description']), 0, 100) . '…'
                            : '';
                        $tags = array_slice($beach['tags'] ?? [], 0, 3);
                    ?>
                    <a href="/beach/<?= h($beach['slug']) ?>"
                       class="flex flex-col bg-white border border-gray-200 rounded-xl overflow-hidden shadow-sm hover:shadow-md hover:border-green-300 transition-all group no-underline <?= $isLastOdd ? 'col-span-2' : '' ?>">
                        <div class="relative h-40 overflow-hidden flex-shrink-0">
                            <img src="<?= $thumb ?>" alt="<?= h($beach['name']) ?>"
                                 class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-300"
                                 loading="lazy">
                        </div>
                        <div class="p-4 flex-1 flex flex-col">
                            <p class="font-bold text-gray-900 group-hover:text-green-700 transition-colors"><?= h($beach['name']) ?></p>
                            <p class="text-xs text-gray-500 mb-2"><?= h($beach['municipality']) ?></p>
                            <?php if ($desc): ?>
                            <p class="text-sm text-gray-600 leading-snug flex-1"><?= h($desc) ?></p>
                            <?php endif; ?>
                            <?php if ($tags): ?>
                            <div class="flex flex-wrap gap-1 mt-3">
                                <?php foreach ($tags as $tag): ?>
                                <span class="text-xs <?= $tagColor($tag) ?> px-2 py-0.5 rounded-full"><?= h($tagLabel($tag)) ?></span>
                                <?php endforeach; ?>
                            </div>
                            <?php endif; ?>
                        </div>
                    </a>
                    <?php endforeach; ?>
                </div>
                <?php endif; ?>

                <div class="bg-amber-50 border border-amber-200 rounded-lg p-4 mb-4">
                    <p class="text-amber-800 text-sm"><strong>Surf tip:</strong> Rincón has surf schools and board rentals for every skill level. Even first-timers do well in spring — water is 80°F and instructors are plentiful. María's Beach and Steps Beach suit experienced surfers; Córcega Beach and Domes are ideal for beginners and intermediates.</p>
                </div>

                <?php $rinconCta = affiliateCTA('hotels_rincon', 'Book Rincón Hotels'); ?>
                <?php if ($rinconCta): ?>
                <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 bg-gray-50 border border-gray-200 rounded-xl p-4 mt-4 mb-8">
                    <div>
                        <p class="text-sm font-semibold text-gray-800">Hotels from ~$125/night</p>
                        <p class="text-xs text-gray-500">Book ahead — Rincón fills up in spring swell season</p>
                    </div>
                    <?= $rinconCta ?>
                </div>
                <?php endif; ?>

                <div class="flex items-center gap-3 mt-12 mb-3">
                    <div class="w-8 h-8 bg-green-100 rounded-lg flex items-center justify-center text-green-600 text-base">🐠</div>
                    <span class="text-xs font-semibold uppercase tracking-widest text-green-600">Day Trips · Culebra</span>
                </div>
                <h2 id="culebra" class="text-3xl font-bold text-gray-900 mb-4">Crystal Clear Day Trips — Culebra</h2>

                <p class="text-gray-700 mb-6">
                    <strong>Culebra</strong> is consistently ranked among the top beaches in the entire Caribbean. The water at Flamenco Beach is a surreal turquoise-white that looks photoshopped in real life. Snorkeling at Carlos Rosario — a short walk or short swim from Flamenco — puts you over some of the healthiest coral reefs in Puerto Rico, with 50+ foot visibility on calm days. Plan a full day trip from San Juan or stay overnight.
                </p>

                <?php if (!empty($culebra_beaches)): ?>
                <p class="text-xs font-semibold uppercase tracking-widest text-gray-400 mb-2 mt-6">🏖 Beaches</p>
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 mb-6">
                    <?php
                    $beachPhotoOverrides = [
                        'flamenco-beach-culebra-18329-65318' =>
                            '/images/beaches/flamenco-beach-culebra.webp',
                    ];
                    $beachCount = count($culebra_beaches);
                    $i = 0;
                    foreach ($culebra_beaches as $beach):
                        $i++;
                        $isLastOdd = ($i === $beachCount && $beachCount % 2 === 1);
                        $thumb = $beachPhotoOverrides[$beach['slug']]
                            ?? (!empty($beach['cover_image'])
                                ? htmlspecialchars($beach['cover_image'], ENT_QUOTES, 'UTF-8')
                                : '/images/beaches/placeholder-beach.webp');
                        $desc = !empty($beach['description'])
                            ? mb_substr(strip_tags($beach['description']), 0, 100) . '…'
                            : '';
                        $tags = array_slice($beach['tags'] ?? [], 0, 3);
                    ?>
                    <a href="/beach/<?= h($beach['slug']) ?>"
                       class="flex flex-col bg-white border border-gray-200 rounded-xl overflow-hidden shadow-sm hover:shadow-md hover:border-cyan-300 transition-all group no-underline <?= $isLastOdd ? 'col-span-2' : '' ?>">
                        <div class="relative h-40 overflow-hidden flex-shrink-0">
                            <img src="<?= $thumb ?>" alt="<?= h($beach['name']) ?>"
                                 class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-300"
                                 loading="lazy">
                        </div>
                        <div class="p-4 flex-1 flex flex-col">
                            <p class="font-bold text-gray-900 group-hover:text-cyan-700 transition-colors"><?= h($beach['name']) ?></p>
                            <p class="text-xs text-gray-500 mb-2"><?= h($beach['municipality']) ?></p>
                            <?php if ($desc): ?>
                            <p class="text-sm text-gray-600 leading-snug flex-1"><?= h($desc) ?></p>
                            <?php endif; ?>
                            <?php if ($tags): ?>
                            <div class="flex flex-wrap gap-1 mt-3">
                                <?php foreach ($tags as $tag): ?>
                                <span class="text-xs <?= $tagColor($tag) ?> px-2 py-0.5 rounded-full"><?= h($tagLabel($tag)) ?></span>
                                <?php endforeach; ?>
                            </div>
                            <?php endif; ?>
                        </div>
                    </a>
                    <?php endforeach; ?>
                </div>
                <?php endif; ?>

                <div class="bg-amber-50 border border-amber-200 rounded-lg p-4 mb-8">
                    <p class="text-amber-800 text-sm"><strong>Critical tip:</strong> Book Culebra ferry tickets the moment you finalize your plans — they sell out 4–6 weeks in advance during spring break. Buy online through the Autoridad de Transporte Maritimo website. Flying from San Juan (15 min, under $100 round-trip with Vieques Air Link or Cape Air) is a reliable backup when ferries are sold out.</p>
                </div>

                <?php $culebraCarCta = affiliateCTA('cars_sju', 'Rent a Car to Ceiba Ferry'); ?>
                <?php if ($culebraCarCta): ?><div class="mt-2 mb-8"><?= $culebraCarCta ?></div><?php endif; ?>

                <div class="flex items-center gap-3 mt-12 mb-3">
                    <div class="w-8 h-8 bg-purple-100 rounded-lg flex items-center justify-center text-purple-600 text-base">🌿</div>
                    <span class="text-xs font-semibold uppercase tracking-widest text-purple-600 dark:text-violet-300">Off the Beaten Path</span>
                </div>
                <h2 id="vieques" class="text-3xl font-bold text-gray-900 mb-4">Vieques</h2>

                <p class="text-gray-700 mb-6">
                    <strong>Vieques</strong> is Culebra's quieter, wilder sibling — larger, more rugged, and home to Mosquito Bay, the world's brightest bioluminescent bay. The former US Navy bombing range left behind enormous stretches of untouched beach accessible only by 4x4. Vieques attracts a more adventurous spring break crowd who want to escape the resort scene without giving up turquoise water.
                </p>

                <?php if (!empty($vieques_beaches)): ?>
                <p class="text-xs font-semibold uppercase tracking-widest text-gray-400 mb-2 mt-6">🏖 Beaches</p>
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 mb-6">
                    <?php
                    $beachCount = count($vieques_beaches);
                    $i = 0;
                    foreach ($vieques_beaches as $beach):
                        $i++;
                        $isLastOdd = ($i === $beachCount && $beachCount % 2 === 1);
                        $thumb = !empty($beach['cover_image'])
                            ? htmlspecialchars($beach['cover_image'], ENT_QUOTES, 'UTF-8')
                            : '/images/beaches/placeholder-beach.webp';
                        $desc = !empty($beach['description'])
                            ? mb_substr(strip_tags($beach['description']), 0, 100) . '…'
                            : '';
                        $tags = array_slice($beach['tags'] ?? [], 0, 3);
                    ?>
                    <a href="/beach/<?= h($beach['slug']) ?>"
                       class="flex flex-col bg-white border border-gray-200 rounded-xl overflow-hidden shadow-sm hover:shadow-md hover:border-purple-300 transition-all group no-underline <?= $isLastOdd ? 'col-span-2' : '' ?>">
                        <div class="relative h-40 overflow-hidden flex-shrink-0">
                            <img src="<?= $thumb ?>" alt="<?= h($beach['name']) ?>"
                                 class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-300"
                                 loading="lazy">
                        </div>
                        <div class="p-4 flex-1 flex flex-col">
                            <p class="font-bold text-gray-900 group-hover:text-purple-700 transition-colors"><?= h($beach['name']) ?></p>
                            <p class="text-xs text-gray-500 mb-2"><?= h($beach['municipality']) ?></p>
                            <?php if ($desc): ?>
                            <p class="text-sm text-gray-600 leading-snug flex-1"><?= h($desc) ?></p>
                            <?php endif; ?>
                            <?php if ($tags): ?>
                            <div class="flex flex-wrap gap-1 mt-3">
                                <?php foreach ($tags as $tag): ?>
                                <span class="text-xs <?= $tagColor($tag) ?> px-2 py-0.5 rounded-full"><?= h($tagLabel($tag)) ?></span>
                                <?php endforeach; ?>
                            </div>
                            <?php endif; ?>
                        </div>
                    </a>
                    <?php endforeach; ?>
                </div>
                <?php endif; ?>

                <div class="bg-amber-50 border border-amber-200 rounded-lg p-4 mb-4">
                    <p class="text-amber-800 text-sm"><strong>Vieques tip:</strong> Rent a Jeep or golf cart to reach the wild east-end beaches on your own schedule. Book your bioluminescent bay kayak tour well in advance and aim for a night close to the new moon for the strongest glow — the bio bay effect is dimmed by moonlight.</p>
                </div>

                <?php $viequesCarCta = affiliateCTA('cars_sju', 'Rent a Jeep or 4×4'); ?>
                <?php if ($viequesCarCta): ?><div class="mt-2 mb-8"><?= $viequesCarCta ?></div><?php endif; ?>

                <?php $viequesCta = affiliateCTA('hotels_vieques', 'Find Vieques Hotels on Expedia'); ?>
                <?php if ($viequesCta): ?>
                <div class="mt-4 mb-8"><?= $viequesCta ?></div>
                <?php endif; ?>

                <div class="flex items-center gap-3 mt-12 mb-3">
                    <div class="w-8 h-8 bg-gray-100 rounded-lg flex items-center justify-center text-gray-600 text-base">📅</div>
                    <span class="text-xs font-semibold uppercase tracking-widest text-gray-500">Planning Tips</span>
                </div>
                <h2 id="tips" class="text-3xl font-bold text-gray-900 mb-6">Spring Break Planning Tips</h2>

                <?php $tips = [
                    ['📅', 'Peak dates 2026',       'March 7 – April 5. Heaviest crowds March 12–30.'],
                    ['🚢', 'Culebra ferry',          'Sells out 4–6 weeks ahead — book as soon as dates are set.'],
                    ['🏖', 'Flamenco Beach arrival', 'Parking fills by 9–10 AM. Aim for 8 AM or take the water taxi.'],
                    ['🏄', 'Best surf window',        'February–April brings consistent north and west swells to Rincón.'],
                    ['🏨', 'Hotel costs',             '30–50% above shoulder rates during spring break. Book early.'],
                    ['☀️', 'Weather',                'Highs ~83°F, low humidity, abundant sun. One of the driest months.'],
                    ['🌊', 'Water temperature',       '80–82°F — no wetsuit needed anywhere on the island.'],
                    ['🧴', 'Sunscreen',               'Use reef-safe (mineral-based), especially at Culebra coral reefs.'],
                ]; ?>
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3 mb-6">
                    <?php foreach ($tips as [$icon, $label, $value]): ?>
                    <div class="bg-gray-50 rounded-xl p-4 border border-gray-100">
                        <p class="font-semibold text-gray-900 text-sm"><?= $icon ?> <?= h($label) ?></p>
                        <p class="text-gray-600 text-sm mt-1"><?= h($value) ?></p>
                    </div>
                    <?php endforeach; ?>
                </div>

                <?php
                $bookingCtaFlight  = affiliateCTA('flights_sju',    'Search Flights',    'yellow');
                $bookingCtaSj      = affiliateCTA('hotels_sanjuan', 'San Juan Hotels',   'secondary');
                $bookingCtaRincon  = affiliateCTA('hotels_rincon',  'Rincón Hotels',     'secondary');
                $bookingCtaCars    = affiliateCTA('cars_sju',       'Rent a Car',        'secondary');
                $hasBookingCtas    = $bookingCtaFlight || $bookingCtaSj || $bookingCtaRincon || $bookingCtaCars;
                ?>
                <?php if ($hasBookingCtas): ?>
                <div class="bg-gradient-to-r from-blue-600 to-blue-700 rounded-xl p-6 my-8 text-white">
                    <h3 class="text-xl font-bold mb-2">Ready to Book?</h3>
                    <p class="text-blue-100 text-sm mb-4">
                        Spring break dates sell out fast — lock in hotels and flights now.
                    </p>
                    <div class="flex flex-wrap gap-3">
                        <?= $bookingCtaFlight ?>
                        <?= $bookingCtaSj ?>
                        <?= $bookingCtaRincon ?>
                        <?= $bookingCtaCars ?>
                    </div>
                </div>
                <?php endif; ?>

                <h2 id="faq" class="text-3xl font-bold text-gray-900 mt-12 mb-6">Frequently Asked Questions</h2>

                <div class="space-y-6">
                    <?php foreach ($faqs as $faq): ?>
                    <div class="border-l-4 border-blue-500 pl-4">
                        <h3 class="text-xl font-bold text-gray-900 mb-2"><?php echo h($faq['question']); ?></h3>
                        <p class="text-gray-700"><?php echo h($faq['answer']); ?></p>
                    </div>
                    <?php endforeach; ?>
                </div>

                <?php
                $guideMapIds = $allMapIds;
                $guideMapTitle = 'Spring Break Beach Map';
                $guideMapDescription = 'Explore all the spring break beaches across San Juan, Rincón, Culebra, and Vieques.';
                $guideMapButtonLabel = 'View Spring Break Beaches';
                $guideMapEmptyNotice = 'Spring break beaches are temporarily unavailable on the map.';
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
    </main>

<?php
$pageShellMode = "end";
include APP_ROOT . "/components/page-shell.php";
?>
