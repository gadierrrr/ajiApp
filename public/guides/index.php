<?php
require_once $_SERVER['DOCUMENT_ROOT'] . '/../bootstrap.php';

require_once APP_ROOT . '/inc/db.php';
require_once APP_ROOT . '/inc/helpers.php';
require_once APP_ROOT . '/inc/constants.php';
require_once APP_ROOT . '/inc/i18n.php';

$pageTitle = __('guides_index.page_title');
$pageDescription = __('guides_index.page_description');

$guides = [
    [
        'title' => __('guides_index.guide_transport_title'),
        'slug' => 'getting-to-puerto-rico-beaches',
        'description' => __('guides_index.guide_transport_desc'),
        'icon' => '🚗',
        'readTime' => __('guides_index.guide_transport_time')
    ],
    [
        'title' => __('guides_index.guide_safety_title'),
        'slug' => 'beach-safety-tips',
        'description' => __('guides_index.guide_safety_desc'),
        'icon' => '🛟',
        'readTime' => __('guides_index.guide_safety_time')
    ],
    [
        'title' => __('guides_index.guide_besttime_title'),
        'slug' => 'best-time-visit-puerto-rico-beaches',
        'description' => __('guides_index.guide_besttime_desc'),
        'icon' => '📅',
        'readTime' => __('guides_index.guide_besttime_time')
    ],
    [
        'title' => __('guides_index.guide_packing_title'),
        'slug' => 'beach-packing-list',
        'description' => __('guides_index.guide_packing_desc'),
        'icon' => '🎒',
        'readTime' => __('guides_index.guide_packing_time')
    ],
    [
        'title' => __('guides_index.guide_islands_title'),
        'slug' => 'culebra-vs-vieques',
        'description' => __('guides_index.guide_islands_desc'),
        'icon' => '🏝️',
        'readTime' => __('guides_index.guide_islands_time')
    ],
    [
        'title' => __('guides_index.guide_bio_title'),
        'slug' => 'bioluminescent-bays',
        'description' => __('guides_index.guide_bio_desc'),
        'icon' => '✨',
        'readTime' => __('guides_index.guide_bio_time')
    ],
    [
        'title' => __('guides_index.guide_snorkeling_title'),
        'slug' => 'snorkeling-guide',
        'description' => __('guides_index.guide_snorkeling_desc'),
        'icon' => '🤿',
        'readTime' => __('guides_index.guide_snorkeling_time')
    ],
    [
        'title' => __('guides_index.guide_surfing_title'),
        'slug' => 'surfing-guide',
        'description' => __('guides_index.guide_surfing_desc'),
        'icon' => '🏄',
        'readTime' => __('guides_index.guide_surfing_time')
    ],
    [
        'title' => __('guides_index.guide_photo_title'),
        'slug' => 'beach-photography-tips',
        'description' => __('guides_index.guide_photo_desc'),
        'icon' => '📸',
        'readTime' => __('guides_index.guide_photo_time')
    ],
    [
        'title' => __('guides_index.guide_family_title'),
        'slug' => 'family-beach-vacation-planning',
        'description' => __('guides_index.guide_family_desc'),
        'icon' => '👨‍👩‍👧‍👦',
        'readTime' => __('guides_index.guide_family_time')
    ],
    [
        'title' => __('guides_index.guide_springbreak_title'),
        'slug' => 'spring-break-beaches-puerto-rico',
        'description' => __('guides_index.guide_springbreak_desc'),
        'icon' => '🌊',
        'readTime' => __('guides_index.guide_springbreak_time')
    ]
];

$cmsGuides = query("
    SELECT slug, title_en, description_en, status
    FROM guide_articles
    WHERE status = 'published'
    ORDER BY COALESCE(published_at, updated_at, created_at) DESC
");
$cmsGuides = is_array($cmsGuides) ? $cmsGuides : [];

if (!empty($cmsGuides)) {
    $bySlug = [];
    foreach ($guides as $idx => $guide) {
        $bySlug[(string) ($guide['slug'] ?? '')] = $idx;
    }

    foreach ($cmsGuides as $cmsGuide) {
        $slug = trim((string) ($cmsGuide['slug'] ?? ''));
        if ($slug === '') {
            continue;
        }

        $title = trim((string) ($cmsGuide['title_en'] ?? ''));
        $description = trim((string) ($cmsGuide['description_en'] ?? ''));

        if (isset($bySlug[$slug])) {
            $i = $bySlug[$slug];
            if ($title !== '') {
                $guides[$i]['title'] = $title;
            }
            if ($description !== '') {
                $guides[$i]['description'] = $description;
            }
            continue;
        }

        $guides[] = [
            'title' => $title !== '' ? $title : ucwords(str_replace('-', ' ', $slug)),
            'slug' => $slug,
            'description' => $description !== '' ? $description : 'CMS-managed guide.',
            'icon' => '🧭',
            'readTime' => 'Updated guide',
        ];
    }
}

$collectionPageSchema = [
    "@context" => "https://schema.org",
    "@type" => "CollectionPage",
    "name" => $pageTitle,
    "description" => $pageDescription,
    "url" => absoluteUrl('/guides/'),
    "breadcrumb" => [
        "@type" => "BreadcrumbList",
        "itemListElement" => [
            [
                "@type" => "ListItem",
                "position" => 1,
                "name" => __('guides_index.breadcrumb_home'),
                "item" => absoluteUrl('/')
            ],
            [
                "@type" => "ListItem",
                "position" => 2,
                "name" => __('guides_index.breadcrumb_guides'),
                "item" => absoluteUrl('/guides/')
            ]
        ]
    ]
];
$extraHead = $extraHead ?? "";
$extraHead .= '<script type="application/ld+json">' . json_encode($collectionPageSchema, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) . '</script>';

$pageTheme = "guide";
$skipMapCSS = true;
$skipMapScripts = true;
$pageShellMode = "start";
include APP_ROOT . "/components/page-shell.php";
?>

    <!-- Hero Section -->
    <?php
    $breadcrumbs = [
        ['name' => __('guides_index.breadcrumb_home'), 'url' => '/'],
        ['name' => __('guides_index.breadcrumb_guides')]
    ];
    include APP_ROOT . '/components/hero-guide.php';
    ?>

    <!-- Guides Grid -->
    <main class="container mx-auto px-4 container-padding py-12">
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
            <?php foreach ($guides as $guide): ?>
                <a href="/guides/<?php echo h($guide['slug']); ?>"
                   class="block bg-white rounded-lg shadow-card hover:shadow-lg transition-all duration-300 overflow-hidden group">
                    <div class="p-6">
                        <div class="text-5xl mb-4"><?php echo $guide['icon']; ?></div>
                        <h2 class="text-2xl font-bold text-gray-900 mb-3 group-hover:text-green-600 transition-colors">
                            <?php echo h($guide['title']); ?>
                        </h2>
                        <p class="text-gray-600 mb-4 leading-relaxed">
                            <?php echo h($guide['description']); ?>
                        </p>
                        <div class="flex items-center justify-between text-sm">
                            <span class="text-green-600 font-medium"><?php echo h($guide['readTime']); ?></span>
                            <span class="text-gray-400 group-hover:text-green-600 transition-colors"><?= __('guides_index.read_guide') ?> →</span>
                        </div>
                    </div>
                </a>
            <?php endforeach; ?>
        </div>

        <!-- CTA Section -->
        <div class="mt-16 bg-gradient-to-r from-green-50 to-blue-50 rounded-lg p-8 text-center">
            <h2 class="text-3xl font-bold text-gray-900 mb-4"><?= __('guides_index.cta_title') ?></h2>
            <p class="text-lg text-gray-700 mb-6 max-w-2xl mx-auto">
                <?= __('guides_index.cta_desc') ?>
            </p>
            <a href="/" class="inline-block bg-green-600 text-white px-8 py-3 rounded-lg font-semibold hover:bg-green-700 transition-colors">
                <?= __('guides_index.cta_button') ?>
            </a>
        </div>
    </main>

<?php
$pageShellMode = "end";
include APP_ROOT . "/components/page-shell.php";
?>
