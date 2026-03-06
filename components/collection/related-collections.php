<?php
/**
 * Related collections cross-links component.
 *
 * Required from calling page:
 * - $currentCollectionKey (string) - current collection context key
 * - $lang (string) - current language code
 */

$_rcLang = $lang ?? (function_exists('getCurrentLanguage') ? getCurrentLanguage() : 'en');
$_rcHasTranslate = function_exists('__');

// Map collection context keys → route page_key, footer translation key, emoji
$_rcCollections = [
    'best-beaches'              => ['page_key' => 'best_beaches',            'label_key' => 'footer.best_beaches',       'emoji' => "\xF0\x9F\x8F\x96\xEF\xB8\x8F"],
    'best-beaches-san-juan'     => ['page_key' => 'best_beaches_san_juan',   'label_key' => 'footer.san_juan_beaches',   'emoji' => "\xF0\x9F\x8C\x87"],
    'best-family-beaches'       => ['page_key' => 'best_family_beaches',     'label_key' => 'footer.family_beaches',     'emoji' => "\xF0\x9F\x91\xA8\xE2\x80\x8D\xF0\x9F\x91\xA9\xE2\x80\x8D\xF0\x9F\x91\xA7\xE2\x80\x8D\xF0\x9F\x91\xA6"],
    'best-snorkeling-beaches'   => ['page_key' => 'best_snorkeling_beaches', 'label_key' => 'footer.snorkeling_beaches', 'emoji' => "\xF0\x9F\xA4\xBF"],
    'best-surfing-beaches'      => ['page_key' => 'best_surfing_beaches',    'label_key' => 'footer.surfing_beaches',    'emoji' => "\xF0\x9F\x8F\x84"],
    'beaches-near-san-juan'     => ['page_key' => 'beaches_near_san_juan',   'label_key' => 'footer.near_san_juan',      'emoji' => "\xF0\x9F\x93\x8D"],
    'beaches-near-san-juan-airport' => ['page_key' => 'beaches_near_airport','label_key' => 'footer.near_airport',       'emoji' => "\xE2\x9C\x88\xEF\xB8\x8F"],
    'hidden-beaches-puerto-rico' => ['page_key' => 'hidden_beaches',         'label_key' => 'footer.hidden_beaches',     'emoji' => "\xF0\x9F\x92\x8E"],
];

// Exclude current collection, pick up to 4
$_rcItems = [];
foreach ($_rcCollections as $key => $meta) {
    if ($key === ($currentCollectionKey ?? '')) {
        continue;
    }
    $_rcItems[] = $meta;
    if (count($_rcItems) >= 4) {
        break;
    }
}

if (!empty($_rcItems)):
    $_rcTitle = $_rcHasTranslate ? __('collection.explore_more') : 'Explore More Beach Collections';
?>
<section class="py-12 bg-gray-50">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <h2 class="text-2xl md:text-3xl font-bold text-gray-900 mb-8 text-center">
            <?= h($_rcTitle) ?>
        </h2>

        <div class="grid sm:grid-cols-2 md:grid-cols-4 gap-6">
            <?php foreach ($_rcItems as $item): ?>
            <a href="<?= h(routeUrl($item['page_key'], $_rcLang)) ?>"
               class="bg-white rounded-xl p-6 shadow-md hover:shadow-lg transition-shadow group text-center">
                <div class="text-4xl mb-4"><?= $item['emoji'] ?></div>
                <h3 class="text-lg font-bold text-gray-900 group-hover:text-brand-darker">
                    <?= h($_rcHasTranslate ? __($item['label_key']) : $item['label_key']) ?>
                </h3>
            </a>
            <?php endforeach; ?>
        </div>
    </div>
</section>
<?php endif; ?>
