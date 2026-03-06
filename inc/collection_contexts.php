<?php
/**
 * Collection context registry for Collection Guide V2 pages.
 *
 * NOTE: keep keys aligned with page slugs and API `collection` param.
 */

if (defined('COLLECTION_CONTEXTS_INCLUDED')) {
    return;
}
define('COLLECTION_CONTEXTS_INCLUDED', true);

/**
 * @return array<string,array<string,mixed>>
 */
function collectionContextRegistry(): array {
    // Ensure i18n is available (loaded lazily by callers)
    if (!function_exists('__')) {
        require_once __DIR__ . '/i18n.php';
    }
    return [
        'best-beaches' => [
            'key' => 'best-beaches',
            'slug' => 'best-beaches',
            'page_key' => 'best_beaches',
            'mode' => 'best',
            'default_sort' => 'rating',
            'default_limit' => 15,
            'hero' => [
                'title' => __('pages.best_beaches.hero_title'),
                'subtitle' => __('pages.best_beaches.hero_subtitle'),
                'meta' => __('pages.best_beaches.hero_meta'),
            ],
        ],
        'best-beaches-san-juan' => [
            'key' => 'best-beaches-san-juan',
            'slug' => 'best-beaches-san-juan',
            'page_key' => 'best_beaches_san_juan',
            'mode' => 'best',
            'municipalities' => ['San Juan', 'Carolina'],
            'default_sort' => 'rating',
            'default_limit' => 12,
            'hero' => [
                'title' => __('pages.best_beaches_san_juan.hero_title'),
                'subtitle' => __('pages.best_beaches_san_juan.hero_subtitle'),
                'meta' => __('pages.best_beaches_san_juan.hero_meta'),
            ],
        ],
        'best-family-beaches' => [
            'key' => 'best-family-beaches',
            'slug' => 'best-family-beaches',
            'page_key' => 'best_family_beaches',
            'mode' => 'tag',
            'context_tag' => 'family-friendly',
            'default_sort' => 'rating',
            'default_limit' => 15,
            'hero' => [
                'title' => __('pages.best_family_beaches.hero_title'),
                'subtitle' => __('pages.best_family_beaches.hero_subtitle'),
                'meta' => __('pages.best_family_beaches.hero_meta'),
            ],
        ],
        'best-snorkeling-beaches' => [
            'key' => 'best-snorkeling-beaches',
            'slug' => 'best-snorkeling-beaches',
            'page_key' => 'best_snorkeling_beaches',
            'mode' => 'tag',
            'context_tag' => 'snorkeling',
            'default_sort' => 'rating',
            'default_limit' => 15,
            'hero' => [
                'title' => __('pages.best_snorkeling_beaches.hero_title'),
                'subtitle' => __('pages.best_snorkeling_beaches.hero_subtitle'),
                'meta' => __('pages.best_snorkeling_beaches.hero_meta'),
            ],
        ],
        'best-surfing-beaches' => [
            'key' => 'best-surfing-beaches',
            'slug' => 'best-surfing-beaches',
            'page_key' => 'best_surfing_beaches',
            'mode' => 'tag',
            'context_tag' => 'surfing',
            'default_sort' => 'rating',
            'default_limit' => 15,
            'hero' => [
                'title' => __('pages.best_surfing_beaches.hero_title'),
                'subtitle' => __('pages.best_surfing_beaches.hero_subtitle'),
                'meta' => __('pages.best_surfing_beaches.hero_meta'),
            ],
        ],
        'beaches-near-san-juan' => [
            'key' => 'beaches-near-san-juan',
            'slug' => 'beaches-near-san-juan',
            'page_key' => 'beaches_near_san_juan',
            'mode' => 'radius',
            'center_lat' => 18.4655,
            'center_lng' => -66.1057,
            'radius_km' => 30,
            'default_sort' => 'distance',
            'default_limit' => 15,
            'hero' => [
                'title' => __('pages.beaches_near_san_juan.hero_title'),
                'subtitle' => __('pages.beaches_near_san_juan.hero_subtitle'),
                'meta' => __('pages.beaches_near_san_juan.hero_meta'),
            ],
        ],
        'beaches-near-san-juan-airport' => [
            'key' => 'beaches-near-san-juan-airport',
            'slug' => 'beaches-near-san-juan-airport',
            'page_key' => 'beaches_near_airport',
            'mode' => 'radius',
            'center_lat' => 18.4394,
            'center_lng' => -66.0018,
            'radius_km' => 15,
            'default_sort' => 'distance',
            'default_limit' => 10,
            'hero' => [
                'title' => __('pages.beaches_near_airport.hero_title'),
                'subtitle' => __('pages.beaches_near_airport.hero_subtitle'),
                'meta' => __('pages.beaches_near_airport.hero_meta'),
            ],
        ],
        'hidden-beaches-puerto-rico' => [
            'key' => 'hidden-beaches-puerto-rico',
            'slug' => 'hidden-beaches-puerto-rico',
            'page_key' => 'hidden_beaches',
            'mode' => 'hidden',
            'hidden_tags' => ['secluded', 'remote', 'wild'],
            'max_review_count' => 200,
            'default_sort' => 'rating',
            'default_limit' => 15,
            'hero' => [
                'title' => __('pages.hidden_beaches.hero_title'),
                'subtitle' => __('pages.hidden_beaches.hero_subtitle'),
                'meta' => __('pages.hidden_beaches.hero_meta'),
            ],
        ],
    ];
}
