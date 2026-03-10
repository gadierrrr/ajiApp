<?php
/**
 * SEO Schema Components
 * Generates JSON-LD structured data for beaches
 */

// Include helper functions for schema generation
require_once __DIR__ . '/../inc/helpers.php';
require_once __DIR__ . '/../inc/constants.php';

/**
 * Wrap an image URL in ImageObject schema with dimensions
 *
 * @param string $imageUrl Image URL (relative or absolute)
 * @param string|null $caption Optional image caption
 * @return array ImageObject schema
 */
function imageObjectSchema($imageUrl, $caption = null) {
    $absoluteUrl = absoluteUrl($imageUrl);

    // Get dimensions
    $dimensions = getImageDimensions($imageUrl);

    $schema = [
        '@type' => 'ImageObject',
        'url' => $absoluteUrl,
        'width' => $dimensions['width'],
        'height' => $dimensions['height']
    ];

    if ($caption) {
        $schema['caption'] = $caption;
    }

    return $schema;
}

/**
 * Get intelligent rating schema for a beach
 * Chooses between user ratings (if >10 reviews) or Google ratings
 * Returns single AggregateRating or null
 *
 * @param array $beach Beach data with rating fields
 * @return array|null AggregateRating schema or null
 */
function getRatingSchema(array $beach) {
    // Prefer user ratings if we have enough (>10 reviews)
    $userReviewCount = $beach['user_review_count'] ?? 0;
    $avgUserRating = $beach['avg_user_rating'] ?? null;

    if ($userReviewCount > 10 && $avgUserRating) {
        return [
            '@type' => 'AggregateRating',
            'ratingValue' => round($avgUserRating, 1),
            'reviewCount' => $userReviewCount,
            'bestRating' => 5,
            'worstRating' => 1
        ];
    }

    // Fall back to Google ratings
    if (!empty($beach['google_rating'])) {
        return [
            '@type' => 'AggregateRating',
            'ratingValue' => $beach['google_rating'],
            'reviewCount' => $beach['google_review_count'] ?? 1,
            'bestRating' => 5,
            'worstRating' => 1
        ];
    }

    return null;
}

/**
 * Get accessibility features as LocationFeatureSpecification array
 * Maps 'accessibility' amenity to structured schema
 *
 * @param array $beach Beach data with amenities
 * @return array Array of LocationFeatureSpecification objects
 */
function getAccessibilityFeatures(array $beach) {
    $amenities = $beach['amenities'] ?? [];

    if (!in_array('accessibility', $amenities)) {
        return [];
    }

    return [
        [
            '@type' => 'LocationFeatureSpecification',
            'name' => 'Wheelchair Accessible',
            'value' => true,
            'hoursAvailable' => [
                '@type' => 'OpeningHoursSpecification',
                'dayOfWeek' => ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday', 'Sunday']
            ]
        ]
    ];
}

/**
 * Generate Beach structured data (schema.org/Beach)
 *
 * @param array $beach Beach data
 * @param array|null $reviews Optional array of user reviews to include
 * @return string JSON-LD script tag
 */
function beachSchema(array $beach, $reviews = null): string {
    $appUrl = getPublicBaseUrl();
    $lang = function_exists('getCurrentLanguage') ? getCurrentLanguage() : 'en';
    $desc = ($lang === 'es' && !empty($beach['description_es'])) ? $beach['description_es'] : ($beach['description'] ?? '');
    $descFallback = $lang === 'es'
        ? "Descubre {$beach['name']} en {$beach['municipality']}, Puerto Rico."
        : "Explore {$beach['name']} in {$beach['municipality']}, Puerto Rico.";

    $schema = [
        '@context' => 'https://schema.org',
        '@type' => ['LocalBusiness', 'Beach'],
        '@id' => $appUrl . '/beach/' . $beach['slug'],
        'name' => $beach['name'],
        'description' => $desc ?: $descFallback,
        'url' => $appUrl . '/beach/' . $beach['slug'],
        'geo' => [
            '@type' => 'GeoCoordinates',
            'latitude' => $beach['lat'],
            'longitude' => $beach['lng']
        ],
        'address' => [
            '@type' => 'PostalAddress',
            'addressLocality' => $beach['municipality'],
            'addressRegion' => 'PR',
            'addressCountry' => 'US'
        ],
        'isAccessibleForFree' => true,
        'publicAccess' => true
    ];

    // Add image with ImageObject wrapper (includes dimensions)
    if (!empty($beach['cover_image'])) {
        $schema['image'] = imageObjectSchema($beach['cover_image'], $beach['name']);
    }

    // Add intelligent rating (user or Google)
    $rating = getRatingSchema($beach);
    if ($rating) {
        $schema['aggregateRating'] = $rating;
    }

    // Add amenities
    if (!empty($beach['amenities'])) {
        $amenityList = [];
        foreach ($beach['amenities'] as $amenity) {
            $amenityList[] = [
                '@type' => 'LocationFeatureSpecification',
                'name' => ucwords(str_replace('-', ' ', $amenity)),
                'value' => true
            ];
        }
        $schema['amenityFeature'] = $amenityList;
    }

    // Add accessibility features if applicable
    $accessibilityFeatures = getAccessibilityFeatures($beach);
    if (!empty($accessibilityFeatures)) {
        if (!isset($schema['amenityFeature'])) {
            $schema['amenityFeature'] = [];
        }
        $schema['amenityFeature'] = array_merge($schema['amenityFeature'], $accessibilityFeatures);
    }

    // Add sameAs links (external URLs + Google Maps)
    $sameAs = buildSameAsLinks($beach);
    if (!empty($sameAs)) {
        $schema['sameAs'] = $sameAs;
    }

    // Add reviews if provided
    if (!empty($reviews) && is_array($reviews)) {
        $reviewItems = [];
        foreach ($reviews as $review) {
            $reviewItems[] = [
                '@type' => 'Review',
                'reviewRating' => [
                    '@type' => 'Rating',
                    'ratingValue' => $review['rating'],
                    'bestRating' => 5,
                    'worstRating' => 1
                ],
                'author' => [
                    '@type' => 'Person',
                    'name' => $review['user_name'] ?? 'Anonymous'
                ],
                'reviewBody' => $review['review_text'] ?? '',
                'datePublished' => $review['created_at'] ?? date('Y-m-d')
            ];
        }
        $schema['review'] = $reviewItems;
    }

    return '<script type="application/ld+json">' . json_encode($schema, JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT) . '</script>';
}

/**
 * Generate ItemList for beach listings
 */
function beachListSchema(array $beaches, string $listName = 'Puerto Rico Beaches'): string {
    $appUrl = getPublicBaseUrl();

    $items = [];
    foreach ($beaches as $index => $beach) {
        $items[] = [
            '@type' => 'ListItem',
            'position' => $index + 1,
            'name' => $beach['name'],
            'url' => $appUrl . '/beach/' . $beach['slug']
        ];
    }

    $schema = [
        '@context' => 'https://schema.org',
        '@type' => 'ItemList',
        'name' => $listName,
        'description' => 'Discover beautiful beaches across Puerto Rico',
        'numberOfItems' => count($beaches),
        'itemListElement' => $items
    ];

    return '<script type="application/ld+json">' . json_encode($schema, JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT) . '</script>';
}

/**
 * Generate Organization schema for the site
 */
function organizationSchema(): string {
    $appUrl = getPublicBaseUrl();
    $appName = $_ENV['APP_NAME'] ?? 'Puerto Rico Beach Finder';

    // Social media profiles - add/remove as needed
    $socialLinks = [
        'https://www.instagram.com/puertoricobeachfinder'
    ];

    $schema = [
        '@context' => 'https://schema.org',
        '@type' => 'Organization',
        'name' => $appName,
        'alternateName' => 'PR Beach Finder',
        'url' => $appUrl,
        'logo' => [
            '@type' => 'ImageObject',
            'url' => $appUrl . '/assets/icons/icon-512x512.png',
            'width' => 512,
            'height' => 512
        ],
        'description' => 'The most comprehensive database of beaches in Puerto Rico. Discover 233+ beaches with GPS coordinates, amenities, conditions, photos, and reviews.',
        'foundingDate' => '2024',
        'areaServed' => [
            '@type' => 'Place',
            'name' => 'Puerto Rico',
            'geo' => [
                '@type' => 'GeoCoordinates',
                'latitude' => 18.2208,
                'longitude' => -66.5901
            ]
        ],
        'sameAs' => $socialLinks,
        'contactPoint' => [
            '@type' => 'ContactPoint',
            'contactType' => 'customer support',
            'url' => $appUrl . '/contact',
            'availableLanguage' => ['English', 'Spanish']
        ]
    ];

    return '<script type="application/ld+json">' . json_encode($schema, JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT) . '</script>';
}

/**
 * Generate WebSite schema with search action
 */
function websiteSchema(): string {
    $appUrl = getPublicBaseUrl();
    $appName = $_ENV['APP_NAME'] ?? 'Puerto Rico Beach Finder';

    $schema = [
        '@context' => 'https://schema.org',
        '@type' => 'WebSite',
        'name' => $appName,
        'url' => $appUrl,
        'potentialAction' => [
            '@type' => 'SearchAction',
            'target' => [
                '@type' => 'EntryPoint',
                'urlTemplate' => $appUrl . '/?search={search_term_string}'
            ],
            'query-input' => 'required name=search_term_string'
        ]
    ];

    return '<script type="application/ld+json">' . json_encode($schema, JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT) . '</script>';
}

/**
 * Generate BreadcrumbList schema
 */
function breadcrumbSchema(array $items): string {
    $appUrl = getPublicBaseUrl();

    $listItems = [];
    foreach ($items as $index => $item) {
        $listItems[] = [
            '@type' => 'ListItem',
            'position' => $index + 1,
            'name' => $item['name'],
            'item' => strpos($item['url'], 'http') === 0 ? $item['url'] : $appUrl . $item['url']
        ];
    }

    $schema = [
        '@context' => 'https://schema.org',
        '@type' => 'BreadcrumbList',
        'itemListElement' => $listItems
    ];

    return '<script type="application/ld+json">' . json_encode($schema, JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT) . '</script>';
}

/**
 * Generate FAQPage schema
 */
function faqSchema(array $faqs): string {
    $items = [];
    foreach ($faqs as $faq) {
        $items[] = [
            '@type' => 'Question',
            'name' => $faq['question'],
            'acceptedAnswer' => [
                '@type' => 'Answer',
                'text' => $faq['answer']
            ]
        ];
    }

    $schema = [
        '@context' => 'https://schema.org',
        '@type' => 'FAQPage',
        'mainEntity' => $items
    ];

    return '<script type="application/ld+json">' . json_encode($schema, JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT) . '</script>';
}

/**
 * Generate Review schema for user reviews
 * @deprecated Use beachSchema() with $reviews parameter instead
 */
function reviewsSchema(array $beach, array $reviews): string {
    if (empty($reviews)) return '';

    $appUrl = getPublicBaseUrl();

    $reviewItems = [];
    foreach ($reviews as $review) {
        $reviewItems[] = [
            '@type' => 'Review',
            'reviewRating' => [
                '@type' => 'Rating',
                'ratingValue' => $review['rating'],
                'bestRating' => 5,
                'worstRating' => 1
            ],
            'author' => [
                '@type' => 'Person',
                'name' => $review['user_name'] ?? 'Anonymous'
            ],
            'reviewBody' => $review['review_text'] ?? '',
            'datePublished' => $review['created_at'] ?? date('Y-m-d')
        ];
    }

    $schema = [
        '@context' => 'https://schema.org',
        '@type' => ['LocalBusiness', 'Beach'],
        'name' => $beach['name'],
        'url' => $appUrl . '/beach/' . $beach['slug'],
        'review' => $reviewItems
    ];

    if (!empty($beach['avg_user_rating'])) {
        $schema['aggregateRating'] = [
            '@type' => 'AggregateRating',
            'ratingValue' => round($beach['avg_user_rating'], 1),
            'reviewCount' => $beach['user_review_count'] ?? count($reviews),
            'bestRating' => 5,
            'worstRating' => 1
        ];
    }

    return '<script type="application/ld+json">' . json_encode($schema, JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT) . '</script>';
}

/**
 * Generate HowTo schema for guide pages
 * Perfect for step-by-step beach guides, travel tips, etc.
 *
 * @param string $name Guide title (e.g., "How to Find the Best Beaches in Puerto Rico")
 * @param string $description Brief description of the guide
 * @param array $steps Array of steps, each with 'name', 'text', optional 'image', 'url'
 * @param string|null $image Optional hero image for the guide
 * @param string|null $totalTime Optional time estimate (ISO 8601 duration, e.g., "PT30M" for 30 minutes)
 * @return string JSON-LD script tag
 */
function howToSchema($name, $description, $steps, $image = null, $totalTime = null): string {
    $appUrl = getPublicBaseUrl();

    // Validate inputs
    if (empty($name) || empty($steps) || !is_array($steps)) {
        return '';
    }

    $schema = [
        '@context' => 'https://schema.org',
        '@type' => 'HowTo',
        'name' => $name,
        'description' => $description
    ];

    // Add total time if provided
    if ($totalTime) {
        $schema['totalTime'] = $totalTime;
    }

    // Add image if provided
    if ($image) {
        $schema['image'] = imageObjectSchema($image, $name);
    }

    // Build steps
    $stepItems = [];
    foreach ($steps as $index => $step) {
        if (empty($step['name']) || empty($step['text'])) {
            continue; // Skip invalid steps
        }

        $stepItem = [
            '@type' => 'HowToStep',
            'position' => $index + 1,
            'name' => $step['name'],
            'text' => $step['text']
        ];

        // Add step image if provided
        if (!empty($step['image'])) {
            $stepItem['image'] = imageObjectSchema($step['image'], $step['name']);
        }

        // Add step URL if provided
        if (!empty($step['url'])) {
            $stepItem['url'] = strpos($step['url'], 'http') === 0
                ? $step['url']
                : $appUrl . $step['url'];
        }

        $stepItems[] = $stepItem;
    }

    $schema['step'] = $stepItems;

    return '<script type="application/ld+json">' . json_encode($schema, JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT) . '</script>';
}

/**
 * Generate TouristAttraction schema for travel search visibility
 *
 * @param array $beach Beach data
 * @return string JSON-LD script tag
 */
function touristAttractionSchema(array $beach): string {
    $appUrl = getPublicBaseUrl();

    // Map tags to tourist types using TOURIST_TYPE_MAPPINGS constant
    $touristTypes = ['Beach Lovers', 'Nature Enthusiasts'];
    $tags = $beach['tags'] ?? [];

    foreach ($tags as $tag) {
        if (isset(TOURIST_TYPE_MAPPINGS[$tag])) {
            $touristTypes[] = TOURIST_TYPE_MAPPINGS[$tag];
        }
    }

    $schema = [
        '@context' => 'https://schema.org',
        '@type' => 'TouristAttraction',
        'name' => $beach['name'],
        'description' => (function_exists('getCurrentLanguage') && getCurrentLanguage() === 'es' && !empty($beach['description_es']))
            ? $beach['description_es']
            : ($beach['description'] ?? "Beautiful beach in {$beach['municipality']}, Puerto Rico"),
        'url' => $appUrl . '/beach/' . $beach['slug'],
        'touristType' => array_values(array_unique($touristTypes)),
        'geo' => [
            '@type' => 'GeoCoordinates',
            'latitude' => $beach['lat'],
            'longitude' => $beach['lng']
        ],
        'address' => [
            '@type' => 'PostalAddress',
            'addressLocality' => $beach['municipality'],
            'addressRegion' => 'Puerto Rico',
            'addressCountry' => 'US'
        ],
        'isAccessibleForFree' => true,
        'publicAccess' => true
    ];

    // Add image with ImageObject wrapper
    if (!empty($beach['cover_image'])) {
        $schema['image'] = imageObjectSchema($beach['cover_image'], $beach['name']);
    }

    // Note: AggregateRating is already included in the Beach schema
    // We don't duplicate it here to avoid schema validation errors

    return '<script type="application/ld+json">' . json_encode($schema, JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT) . '</script>';
}

/**
 * Generate dynamic FAQs for a beach
 */
function generateBeachFAQs(array $beach): array {
    $faqs = [];
    $name = $beach['name'];
    $municipality = $beach['municipality'];
    $tags = $beach['tags'] ?? [];
    $amenities = $beach['amenities'] ?? [];
    $lang = function_exists('getCurrentLanguage') ? getCurrentLanguage() : 'en';
    $isEs = ($lang === 'es');

    // Location FAQ
    $faqs[] = [
        'question' => $isEs ? "¿Dónde está ubicada {$name}?" : "Where is {$name} located?",
        'answer' => $isEs
            ? "{$name} está ubicada en {$municipality}, Puerto Rico. Las coordenadas exactas son {$beach['lat']}, {$beach['lng']}. Puedes usar navegación GPS o seguir las señales hacia {$municipality} y buscar los accesos a la playa."
            : "{$name} is located in {$municipality}, Puerto Rico. The exact coordinates are {$beach['lat']}, {$beach['lng']}. You can use GPS navigation or follow signs to {$municipality} and look for beach access points."
    ];

    // Swimming FAQ
    if ($isEs) {
        $swimmingInfo = "Sí, {$name} es una playa pública abierta para nadar.";
        if (in_array('calm-waters', $tags)) {
            $swimmingInfo .= " La playa tiene aguas tranquilas, ideal para nadar.";
        } elseif (in_array('surfing', $tags)) {
            $swimmingInfo .= " Ten en cuenta que esta playa es conocida por sus condiciones de surf, por lo que las olas pueden ser fuertes.";
        }
        if (in_array('lifeguard', $amenities)) {
            $swimmingInfo .= " Hay salvavidas disponibles.";
        }
    } else {
        $swimmingInfo = "Yes, {$name} is a public beach open for swimming.";
        if (in_array('calm-waters', $tags)) {
            $swimmingInfo .= " The beach has calm waters, making it ideal for swimming.";
        } elseif (in_array('surfing', $tags)) {
            $swimmingInfo .= " Note that this beach is known for surfing conditions, so waves can be strong.";
        }
        if (in_array('lifeguard', $amenities)) {
            $swimmingInfo .= " Lifeguards are available on duty.";
        }
    }
    $faqs[] = [
        'question' => $isEs ? "¿Es {$name} buena para nadar?" : "Is {$name} good for swimming?",
        'answer' => $swimmingInfo
    ];

    // Facilities FAQ
    $facilitiesAnswer = "";
    if (!empty($amenities)) {
        $amenityNames = array_map(function($a) use ($isEs) {
            if ($isEs && function_exists('getAmenityLabel')) return getAmenityLabel($a);
            return ucwords(str_replace('-', ' ', $a));
        }, $amenities);
        $facilitiesAnswer = $isEs
            ? "{$name} ofrece las siguientes instalaciones: " . implode(', ', $amenityNames) . "."
            : "{$name} offers the following facilities: " . implode(', ', $amenityNames) . ".";
    } else {
        $facilitiesAnswer = $isEs
            ? "{$name} es una playa natural. Las instalaciones pueden ser limitadas, así que considera traer tus propios suministros."
            : "{$name} is a natural beach. Facilities may be limited, so consider bringing your own supplies.";
    }
    $faqs[] = [
        'question' => $isEs ? "¿Qué instalaciones hay en {$name}?" : "What facilities are available at {$name}?",
        'answer' => $facilitiesAnswer
    ];

    // Best activities FAQ
    if ($isEs) {
        $activities = [];
        if (in_array('swimming', $tags) || in_array('calm-waters', $tags)) $activities[] = 'natación';
        if (in_array('snorkeling', $tags)) $activities[] = 'snorkel';
        if (in_array('surfing', $tags)) $activities[] = 'surfing';
        if (in_array('kayaking', $tags)) $activities[] = 'kayak';
        if (in_array('paddleboarding', $tags)) $activities[] = 'paddleboard';
        if (in_array('fishing', $tags)) $activities[] = 'pesca';
        if (in_array('hiking', $tags)) $activities[] = 'senderismo cercano';
        if (empty($activities)) $activities = ['natación', 'relajación', 'tomar el sol'];
        $faqs[] = [
            'question' => "¿Qué actividades puedo hacer en {$name}?",
            'answer' => "Las actividades populares en {$name} incluyen " . implode(', ', $activities) .
                       ". La playa es " . (in_array('family-friendly', $tags) ? "familiar y " : "") .
                       "perfecta para una excursión desde " . $municipality . "."
        ];
    } else {
        $activities = [];
        if (in_array('swimming', $tags) || in_array('calm-waters', $tags)) $activities[] = 'swimming';
        if (in_array('snorkeling', $tags)) $activities[] = 'snorkeling';
        if (in_array('surfing', $tags)) $activities[] = 'surfing';
        if (in_array('kayaking', $tags)) $activities[] = 'kayaking';
        if (in_array('paddleboarding', $tags)) $activities[] = 'paddleboarding';
        if (in_array('fishing', $tags)) $activities[] = 'fishing';
        if (in_array('hiking', $tags)) $activities[] = 'hiking nearby';
        if (empty($activities)) $activities = ['swimming', 'relaxing', 'sunbathing'];
        $faqs[] = [
            'question' => "What activities can I do at {$name}?",
            'answer' => "Popular activities at {$name} include " . implode(', ', $activities) .
                       ". The beach is " . (in_array('family-friendly', $tags) ? "family-friendly and " : "") .
                       "perfect for a day trip from " . $municipality . "."
        ];
    }

    // Parking FAQ
    $parkingAnswer = "";
    if (in_array('parking', $amenities) || in_array('free-parking', $amenities)) {
        $parkingAnswer = $isEs
            ? "Sí, {$name} tiene estacionamiento disponible" . (in_array('free-parking', $amenities) ? " y es gratis" : "") . "."
            : "Yes, {$name} has parking available" . (in_array('free-parking', $amenities) ? " and it's free" : "") . ".";
    } else {
        $parkingAnswer = $isEs
            ? "El estacionamiento cerca de {$name} puede ser limitado. Se recomienda llegar temprano, especialmente los fines de semana y días feriados."
            : "Parking near {$name} may be limited. It's recommended to arrive early, especially on weekends and holidays.";
    }
    $faqs[] = [
        'question' => $isEs ? "¿Hay estacionamiento en {$name}?" : "Is there parking at {$name}?",
        'answer' => $parkingAnswer
    ];

    // Best time FAQ
    $faqs[] = [
        'question' => $isEs ? "¿Cuál es la mejor época para visitar {$name}?" : "What is the best time to visit {$name}?",
        'answer' => $isEs
            ? "La mejor época para visitar {$name} es durante la temporada seca de Puerto Rico, de diciembre a abril. Para menos multitudes, visita en las mañanas entre semana. " .
               (in_array('surfing', $tags) ? "Para surfing, los meses de invierno (noviembre-marzo) típicamente tienen las mejores olas. " : "") .
               "Siempre verifica las condiciones del clima antes de visitar."
            : "The best time to visit {$name} is during Puerto Rico's dry season from December to April. " .
               "For fewer crowds, visit on weekday mornings. " .
               (in_array('surfing', $tags) ? "For surfing, winter months (November-March) typically have the best swells. " : "") .
               "Always check weather conditions before visiting."
    ];

    return $faqs;
}

/**
 * Generate Speakable schema for voice assistants
 */
function speakableSchema(): string {
    $schema = [
        '@context' => 'https://schema.org',
        '@type' => 'WebPage',
        'speakable' => [
            '@type' => 'SpeakableSpecification',
            'cssSelector' => [
                '.beach-description',
                '.beach-highlights',
                '.beach-facts',
                'h1',
                '.page-description'
            ]
        ]
    ];

    return '<script type="application/ld+json">' . json_encode($schema, JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT) . '</script>';
}

/**
 * Generate Article schema for landing pages
 */
function articleSchema(string $title, string $description, string $url, ?string $image = null, ?string $datePublished = null): string {
    $appUrl = getPublicBaseUrl();
    $appName = $_ENV['APP_NAME'] ?? 'Puerto Rico Beach Finder';

    // Backward compatibility: some callers historically passed date as arg #4.
    if ($datePublished === null && is_string($image) && preg_match('/^\d{4}-\d{2}-\d{2}$/', $image)) {
        $datePublished = $image;
        $image = null;
    }

    $schema = [
        '@context' => 'https://schema.org',
        '@type' => 'Article',
        'headline' => $title,
        'description' => $description,
        'url' => absoluteUrl($url),
        'author' => [
            '@type' => 'Organization',
            'name' => $appName,
            'url' => $appUrl
        ],
        'publisher' => [
            '@type' => 'Organization',
            'name' => $appName,
            'url' => $appUrl,
            'logo' => [
                '@type' => 'ImageObject',
                'url' => $appUrl . '/assets/icons/icon-512x512.png'
            ]
        ],
        'datePublished' => $datePublished ?? date('Y-m-d'),
        'dateModified' => date('Y-m-d')
    ];

    if ($image) {
        // Use ImageObject wrapper for proper schema structure
        $schema['image'] = imageObjectSchema($image);
    }

    return '<script type="application/ld+json">' . json_encode($schema, JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT) . '</script>';
}

/**
 * Generate CollectionPage schema for list pages
 */
function collectionPageSchema(string $title, string $description, array $beaches): string {
    $appUrl = getPublicBaseUrl();

    $items = [];
    foreach (array_slice($beaches, 0, 20) as $index => $beach) {
        $items[] = [
            '@type' => 'ListItem',
            'position' => $index + 1,
            'item' => [
                '@type' => 'Beach',
                'name' => $beach['name'],
                'url' => $appUrl . '/beach/' . $beach['slug'],
                'description' => substr($beach['description'] ?? '', 0, 150)
            ]
        ];
    }

    $schema = [
        '@context' => 'https://schema.org',
        '@type' => 'CollectionPage',
        'name' => $title,
        'description' => $description,
        'mainEntity' => [
            '@type' => 'ItemList',
            'numberOfItems' => count($beaches),
            'itemListElement' => $items
        ]
    ];

    return '<script type="application/ld+json">' . json_encode($schema, JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT) . '</script>';
}

/**
 * Generate structured data for any non-beach place type.
 * Dispatches to appropriate schema.org type based on place_type.
 *
 * @param array $place Place data with place_type field
 * @param array|null $reviews Optional reviews
 * @return string JSON-LD script tag
 */
function placeSchema(array $place, ?array $reviews = null): string {
    require_once __DIR__ . '/../inc/place_types.php';
    require_once __DIR__ . '/../inc/place_helpers.php';

    $appUrl = getPublicBaseUrl();
    $type = $place['place_type'] ?? 'beach';
    $config = getPlaceTypeConfig($type);
    if (!$config) return '';

    $lang = function_exists('getCurrentLanguage') ? getCurrentLanguage() : 'en';
    $desc = ($lang === 'es' && !empty($place['description_es']))
        ? $place['description_es']
        : ($place['description'] ?? '');
    $placeUrl = $appUrl . getPlaceUrl($place, $lang);
    $schemaTypes = $config['schema_type'] ?? ['TouristAttraction'];

    $schema = [
        '@context' => 'https://schema.org',
        '@type' => count($schemaTypes) === 1 ? $schemaTypes[0] : $schemaTypes,
        '@id' => $placeUrl,
        'name' => $place['name'],
        'description' => $desc ?: "{$place['name']} in {$place['municipality']}, Puerto Rico.",
        'url' => $placeUrl,
        'isAccessibleForFree' => ($type !== 'restaurant'),
        'publicAccess' => true,
    ];

    if (!empty($place['lat']) && !empty($place['lng'])) {
        $schema['geo'] = [
            '@type' => 'GeoCoordinates',
            'latitude' => $place['lat'],
            'longitude' => $place['lng'],
        ];
    }

    if (!empty($place['municipality'])) {
        $schema['address'] = [
            '@type' => 'PostalAddress',
            'addressLocality' => $place['municipality'],
            'addressRegion' => 'PR',
            'addressCountry' => 'US',
        ];
    }

    if (!empty($place['cover_image'])) {
        $schema['image'] = imageObjectSchema($place['cover_image'], $place['name']);
    }

    $rating = getRatingSchema($place);
    if ($rating) {
        $schema['aggregateRating'] = $rating;
    }

    if ($type === 'restaurant') {
        if (!empty($place['price_range'])) $schema['priceRange'] = $place['price_range'];
        if (!empty($place['cuisine_type'])) $schema['servesCuisine'] = $place['cuisine_type'];
        if (!empty($place['phone'])) $schema['telephone'] = $place['phone'];
    }

    if (!empty($reviews)) {
        $reviewItems = [];
        foreach ($reviews as $review) {
            $reviewItems[] = [
                '@type' => 'Review',
                'reviewRating' => [
                    '@type' => 'Rating',
                    'ratingValue' => $review['rating'],
                    'bestRating' => 5,
                    'worstRating' => 1,
                ],
                'author' => [
                    '@type' => 'Person',
                    'name' => $review['user_name'] ?? 'Anonymous',
                ],
                'reviewBody' => $review['review_text'] ?? '',
                'datePublished' => $review['created_at'] ?? date('Y-m-d'),
            ];
        }
        $schema['review'] = $reviewItems;
    }

    return '<script type="application/ld+json">' . json_encode($schema, JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT) . '</script>';
}

/**
 * Generate CollectionPage schema for any place type.
 */
function placeCollectionSchema(string $title, string $description, array $places, string $placeType = 'beach'): string {
    require_once __DIR__ . '/../inc/place_types.php';
    require_once __DIR__ . '/../inc/place_helpers.php';

    $appUrl = getPublicBaseUrl();
    $config = getPlaceTypeConfig($placeType);
    $schemaTypes = $config['schema_type'] ?? ['TouristAttraction'];
    $itemType = is_array($schemaTypes) ? $schemaTypes[0] : $schemaTypes;

    $items = [];
    foreach (array_slice($places, 0, 20) as $index => $p) {
        $p['place_type'] = $placeType;
        $items[] = [
            '@type' => 'ListItem',
            'position' => $index + 1,
            'item' => [
                '@type' => $itemType,
                'name' => $p['name'],
                'url' => $appUrl . getPlaceUrl($p),
                'description' => mb_substr($p['description'] ?? '', 0, 150),
            ],
        ];
    }

    $schema = [
        '@context' => 'https://schema.org',
        '@type' => 'CollectionPage',
        'name' => $title,
        'description' => $description,
        'mainEntity' => [
            '@type' => 'ItemList',
            'numberOfItems' => count($places),
            'itemListElement' => $items,
        ],
    ];

    return '<script type="application/ld+json">' . json_encode($schema, JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT) . '</script>';
}
