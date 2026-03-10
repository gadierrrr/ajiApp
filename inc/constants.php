<?php
/**
 * Beach vocabulary constants - central source of truth for controlled vocabularies
 * Ported from beachVocab.ts
 *
 * NOTE: Display label functions (getTagLabel, getAmenityLabel, getConditionLabel)
 * are defined in inc/helpers.php to avoid duplicates.
 */

// Include guard to prevent duplicate declarations
if (defined('CONSTANTS_PHP_INCLUDED')) {
    return;
}
define('CONSTANTS_PHP_INCLUDED', true);

const TAGS = [
    'calm-waters',
    'surfing',
    'snorkeling',
    'family-friendly',
    'accessible',
    'secluded',
    'popular',
    'scenic',
    'swimming',
    'diving',
    'fishing',
    'camping'
];

const AMENITIES = [
    'restrooms',
    'showers',
    'lifeguard',
    'parking',
    'food',
    'equipment-rental',
    'accessibility',
    'picnic-areas',
    'shade-structures',
    'water-sports'
];

const CONDITION_SCALES = [
    'sargassum' => ['none', 'light', 'moderate', 'heavy'],
    'surf' => ['calm', 'small', 'medium', 'large'],
    'wind' => ['calm', 'light', 'moderate', 'strong']
];

const MUNICIPALITIES = [
    'Adjuntas', 'Aguada', 'Aguadilla', 'Aguas Buenas', 'Aibonito', 'Arecibo',
    'Arroyo', 'Barceloneta', 'Barranquitas', 'Bayamon', 'Cabo Rojo', 'Caguas',
    'Camuy', 'Canovanas', 'Carolina', 'Catano', 'Cayey', 'Ceiba', 'Cidra',
    'Coamo', 'Comerio', 'Corozal', 'Culebra', 'Dorado', 'Fajardo', 'Florida',
    'Guanica', 'Guayama', 'Guayanilla', 'Guaynabo', 'Gurabo', 'Hatillo',
    'Hormigueros', 'Humacao', 'Isabela', 'Jayuya', 'Juana Diaz', 'Juncos',
    'Lajas', 'Lares', 'Las Marias', 'Las Piedras', 'Loiza', 'Luquillo',
    'Manati', 'Maricao', 'Maunabo', 'Mayaguez', 'Moca', 'Morovis', 'Naguabo',
    'Naranjito', 'Orocovis', 'Patillas', 'Penuelas', 'Ponce', 'Quebradillas',
    'Rincon', 'Rio Grande', 'Sabana Grande', 'Salinas', 'San German',
    'San Juan', 'San Lorenzo', 'San Sebastian', 'Santa Isabel', 'Toa Alta',
    'Toa Baja', 'Trujillo Alto', 'Utuado', 'Vega Alta', 'Vega Baja', 'Vieques',
    'Villalba', 'Yabucoa', 'Yauco'
];

// Puerto Rico coordinate boundaries (including Vieques and Culebra)
const PR_BOUNDS = [
    'lat' => ['min' => 17.8, 'max' => 18.6],
    'lng' => ['min' => -67.4, 'max' => -65.2]
];

// Parking difficulty levels
const PARKING_DIFFICULTY = ['easy', 'moderate', 'difficult', 'very-difficult'];

const PARKING_DIFFICULTY_LABELS = [
    'easy' => 'Easy Parking',
    'moderate' => 'Moderate',
    'difficult' => 'Difficult',
    'very-difficult' => 'Very Difficult'
];

const PARKING_DIFFICULTY_DESCRIPTIONS = [
    'easy' => 'Plenty of parking available, rarely fills up',
    'moderate' => 'Usually find parking, may fill on weekends',
    'difficult' => 'Limited spots, arrive early on busy days',
    'very-difficult' => 'Very limited parking, consider alternate transport'
];

// Display labels for tags
const TAG_LABELS = [
    'calm-waters' => 'Calm Waters',
    'surfing' => 'Surfing',
    'snorkeling' => 'Snorkeling',
    'family-friendly' => 'Family Friendly',
    'accessible' => 'Accessible',
    'secluded' => 'Secluded',
    'popular' => 'Popular',
    'scenic' => 'Scenic',
    'swimming' => 'Swimming',
    'diving' => 'Diving',
    'fishing' => 'Fishing',
    'camping' => 'Camping'
];

// Display labels for amenities
const AMENITY_LABELS = [
    'restrooms' => 'Restrooms',
    'showers' => 'Showers',
    'lifeguard' => 'Lifeguard',
    'parking' => 'Parking',
    'food' => 'Food & Drinks',
    'equipment-rental' => 'Equipment Rental',
    'accessibility' => 'Wheelchair Accessible',
    'picnic-areas' => 'Picnic Areas',
    'shade-structures' => 'Shade/Umbrellas',
    'water-sports' => 'Water Sports'
];

// Display labels for conditions
const CONDITION_LABELS = [
    'sargassum' => [
        'none' => 'No Sargassum',
        'light' => 'Light Sargassum',
        'moderate' => 'Moderate Sargassum',
        'heavy' => 'Heavy Sargassum'
    ],
    'surf' => [
        'calm' => 'Calm',
        'small' => 'Small Waves',
        'medium' => 'Medium Waves',
        'large' => 'Large Waves'
    ],
    'wind' => [
        'calm' => 'Calm',
        'light' => 'Light Breeze',
        'moderate' => 'Moderate Wind',
        'strong' => 'Strong Wind'
    ]
];

// Content sections for extended beach content
const CONTENT_SECTIONS = [
    'history' => [
        'label' => 'History & Background',
        'icon' => 'book-open',
        'order' => 1
    ],
    'best_time' => [
        'label' => 'Best Time to Visit',
        'icon' => 'calendar',
        'order' => 2
    ],
    'getting_there' => [
        'label' => 'Getting There',
        'icon' => 'map-pin',
        'order' => 3
    ],
    'what_to_bring' => [
        'label' => 'What to Bring',
        'icon' => 'backpack',
        'order' => 4
    ],
    'nearby' => [
        'label' => 'Nearby Attractions',
        'icon' => 'compass',
        'order' => 5
    ],
    'local_tips' => [
        'label' => 'Local Tips',
        'icon' => 'lightbulb',
        'order' => 6
    ]
];

// Validation helper functions (keep these in constants.php as they only use constants)
function isValidTag($tag) {
    return in_array($tag, TAGS);
}

function isValidAmenity($amenity) {
    return in_array($amenity, AMENITIES);
}

function isValidMunicipality($municipality) {
    return in_array($municipality, MUNICIPALITIES);
}

function isWithinPRBounds($lat, $lng) {
    return $lat >= PR_BOUNDS['lat']['min'] &&
           $lat <= PR_BOUNDS['lat']['max'] &&
           $lng >= PR_BOUNDS['lng']['min'] &&
           $lng <= PR_BOUNDS['lng']['max'];
}

// Tourist type mappings for schema.org TouristAttraction
// Maps beach tags to tourist demographics
const TOURIST_TYPE_MAPPINGS = [
    'family-friendly' => 'Families',
    'surfing' => 'Surfers',
    'snorkeling' => 'Divers',
    'diving' => 'Divers',
    'scuba-diving' => 'Divers',
    'romantic' => 'Couples',
    'secluded' => 'Couples',
    'hiking' => 'Adventure Seekers',
    'camping' => 'Adventure Seekers',
    'fishing' => 'Anglers',
    'kayaking' => 'Water Sports Enthusiasts',
    'paddleboarding' => 'Water Sports Enthusiasts',
    'water-sports' => 'Water Sports Enthusiasts',
    'scenic' => 'Photographers',
    'calm-waters' => 'Relaxation Seekers',
    'swimming' => 'Beach Lovers'
];

// ── River vocabularies ──────────────────────────────────────────────
const RIVER_TAGS = [
    'swimming', 'cliff-jumping', 'tubing', 'kayaking', 'fishing',
    'family-friendly', 'secluded', 'scenic', 'rope-swing', 'natural-pool',
    'waterslide', 'camping-nearby'
];
const RIVER_AMENITIES = [
    'parking', 'restrooms', 'picnic-areas', 'food', 'shade-structures',
    'changing-areas', 'camping'
];
const RIVER_TAG_LABELS = [
    'swimming' => 'Swimming', 'cliff-jumping' => 'Cliff Jumping',
    'tubing' => 'Tubing', 'kayaking' => 'Kayaking', 'fishing' => 'Fishing',
    'family-friendly' => 'Family Friendly', 'secluded' => 'Secluded',
    'scenic' => 'Scenic', 'rope-swing' => 'Rope Swing',
    'natural-pool' => 'Natural Pool', 'waterslide' => 'Natural Waterslide',
    'camping-nearby' => 'Camping Nearby'
];
const RIVER_AMENITY_LABELS = [
    'parking' => 'Parking', 'restrooms' => 'Restrooms',
    'picnic-areas' => 'Picnic Areas', 'food' => 'Food & Drinks',
    'shade-structures' => 'Shade', 'changing-areas' => 'Changing Areas',
    'camping' => 'Camping'
];

// ── Waterfall vocabularies ──────────────────────────────────────────
const WATERFALL_TAGS = [
    'swimming', 'hiking-required', 'easy-access', 'secluded', 'scenic',
    'family-friendly', 'photography', 'rappelling', 'multi-tier', 'natural-pool'
];
const WATERFALL_AMENITIES = [
    'parking', 'restrooms', 'trail-markers', 'guide-service', 'picnic-areas'
];
const WATERFALL_TAG_LABELS = [
    'swimming' => 'Swimming', 'hiking-required' => 'Hiking Required',
    'easy-access' => 'Easy Access', 'secluded' => 'Secluded',
    'scenic' => 'Scenic', 'family-friendly' => 'Family Friendly',
    'photography' => 'Photography', 'rappelling' => 'Rappelling',
    'multi-tier' => 'Multi-Tier', 'natural-pool' => 'Natural Pool'
];
const WATERFALL_AMENITY_LABELS = [
    'parking' => 'Parking', 'restrooms' => 'Restrooms',
    'trail-markers' => 'Trail Markers', 'guide-service' => 'Guide Service',
    'picnic-areas' => 'Picnic Areas'
];

// ── Trail vocabularies ──────────────────────────────────────────────
const TRAIL_TAGS = [
    'hiking', 'mountain-biking', 'birdwatching', 'scenic', 'waterfall',
    'family-friendly', 'dog-friendly', 'loop-trail', 'out-and-back',
    'forest', 'coastal', 'river-crossing'
];
const TRAIL_AMENITIES = [
    'parking', 'restrooms', 'trail-markers', 'picnic-areas',
    'visitor-center', 'water-fountain', 'camping'
];
const TRAIL_TAG_LABELS = [
    'hiking' => 'Hiking', 'mountain-biking' => 'Mountain Biking',
    'birdwatching' => 'Birdwatching', 'scenic' => 'Scenic',
    'waterfall' => 'Waterfall', 'family-friendly' => 'Family Friendly',
    'dog-friendly' => 'Dog Friendly', 'loop-trail' => 'Loop Trail',
    'out-and-back' => 'Out & Back', 'forest' => 'Forest',
    'coastal' => 'Coastal', 'river-crossing' => 'River Crossing'
];
const TRAIL_AMENITY_LABELS = [
    'parking' => 'Parking', 'restrooms' => 'Restrooms',
    'trail-markers' => 'Trail Markers', 'picnic-areas' => 'Picnic Areas',
    'visitor-center' => 'Visitor Center', 'water-fountain' => 'Water Fountain',
    'camping' => 'Camping'
];

// ── Trail difficulty levels ─────────────────────────────────────────
const TRAIL_DIFFICULTIES = ['easy', 'moderate', 'difficult', 'expert'];
const TRAIL_DIFFICULTY_LABELS = [
    'easy' => 'Easy', 'moderate' => 'Moderate',
    'difficult' => 'Difficult', 'expert' => 'Expert'
];

// ── Restaurant vocabularies ─────────────────────────────────────────
const RESTAURANT_TAGS = [
    'seafood', 'criollo', 'pizza', 'sushi', 'vegan', 'vegetarian',
    'brunch', 'fine-dining', 'casual', 'street-food', 'food-truck',
    'beachfront', 'rooftop', 'live-music', 'family-friendly', 'bar'
];
const RESTAURANT_AMENITIES = [
    'parking', 'wifi', 'outdoor-seating', 'reservations', 'delivery',
    'takeout', 'full-bar', 'kids-menu', 'wheelchair-accessible', 'credit-cards'
];
const RESTAURANT_TAG_LABELS = [
    'seafood' => 'Seafood', 'criollo' => 'Criollo', 'pizza' => 'Pizza',
    'sushi' => 'Sushi', 'vegan' => 'Vegan', 'vegetarian' => 'Vegetarian',
    'brunch' => 'Brunch', 'fine-dining' => 'Fine Dining', 'casual' => 'Casual',
    'street-food' => 'Street Food', 'food-truck' => 'Food Truck',
    'beachfront' => 'Beachfront', 'rooftop' => 'Rooftop',
    'live-music' => 'Live Music', 'family-friendly' => 'Family Friendly',
    'bar' => 'Bar'
];
const RESTAURANT_AMENITY_LABELS = [
    'parking' => 'Parking', 'wifi' => 'WiFi',
    'outdoor-seating' => 'Outdoor Seating', 'reservations' => 'Reservations',
    'delivery' => 'Delivery', 'takeout' => 'Takeout',
    'full-bar' => 'Full Bar', 'kids-menu' => 'Kids Menu',
    'wheelchair-accessible' => 'Wheelchair Accessible',
    'credit-cards' => 'Accepts Cards'
];

// ── Restaurant price ranges ─────────────────────────────────────────
const PRICE_RANGES = ['$', '$$', '$$$', '$$$$'];
const PRICE_RANGE_LABELS = [
    '$' => 'Budget', '$$' => 'Moderate',
    '$$$' => 'Upscale', '$$$$' => 'Fine Dining'
];

// ── Photo Spot vocabularies ─────────────────────────────────────────
const PHOTO_SPOT_TAGS = [
    'sunrise', 'sunset', 'golden-hour', 'night-sky', 'aerial',
    'scenic', 'architecture', 'street-art', 'nature', 'wildlife',
    'panoramic', 'underwater', 'historical', 'instagram-worthy'
];
const PHOTO_SPOT_AMENITIES = [
    'parking', 'restrooms', 'tripod-friendly', 'drone-allowed',
    'shade-structures', 'food'
];
const PHOTO_SPOT_TAG_LABELS = [
    'sunrise' => 'Sunrise', 'sunset' => 'Sunset',
    'golden-hour' => 'Golden Hour', 'night-sky' => 'Night Sky',
    'aerial' => 'Aerial/Drone', 'scenic' => 'Scenic',
    'architecture' => 'Architecture', 'street-art' => 'Street Art',
    'nature' => 'Nature', 'wildlife' => 'Wildlife',
    'panoramic' => 'Panoramic', 'underwater' => 'Underwater',
    'historical' => 'Historical', 'instagram-worthy' => 'Instagram Worthy'
];
const PHOTO_SPOT_AMENITY_LABELS = [
    'parking' => 'Parking', 'restrooms' => 'Restrooms',
    'tripod-friendly' => 'Tripod Friendly', 'drone-allowed' => 'Drone Allowed',
    'shade-structures' => 'Shade', 'food' => 'Food Nearby'
];

// ── Water clarity scales (rivers, waterfalls) ───────────────────────
const WATER_CLARITY_LEVELS = ['crystal', 'clear', 'murky', 'muddy'];
const WATER_CLARITY_LABELS = [
    'crystal' => 'Crystal Clear', 'clear' => 'Clear',
    'murky' => 'Murky', 'muddy' => 'Muddy'
];

// ── Current strength (rivers) ───────────────────────────────────────
const CURRENT_STRENGTH_LEVELS = ['calm', 'gentle', 'moderate', 'strong'];
const CURRENT_STRENGTH_LABELS = [
    'calm' => 'Calm', 'gentle' => 'Gentle',
    'moderate' => 'Moderate', 'strong' => 'Strong'
];

// ── Best light conditions (photo spots) ─────────────────────────────
const BEST_LIGHT_CONDITIONS = ['sunrise', 'morning', 'midday', 'golden-hour', 'sunset', 'blue-hour', 'night'];
const BEST_LIGHT_LABELS = [
    'sunrise' => 'Sunrise', 'morning' => 'Morning', 'midday' => 'Midday',
    'golden-hour' => 'Golden Hour', 'sunset' => 'Sunset',
    'blue-hour' => 'Blue Hour', 'night' => 'Night'
];

// ── Per-type tag/amenity label accessors ─────────────────────────────
const PLACE_TAG_LABELS = [
    'beach'      => 'TAG_LABELS',
    'river'      => 'RIVER_TAG_LABELS',
    'waterfall'  => 'WATERFALL_TAG_LABELS',
    'trail'      => 'TRAIL_TAG_LABELS',
    'restaurant' => 'RESTAURANT_TAG_LABELS',
    'photo_spot' => 'PHOTO_SPOT_TAG_LABELS',
];

const PLACE_AMENITY_LABELS = [
    'beach'      => 'AMENITY_LABELS',
    'river'      => 'RIVER_AMENITY_LABELS',
    'waterfall'  => 'WATERFALL_AMENITY_LABELS',
    'trail'      => 'TRAIL_AMENITY_LABELS',
    'restaurant' => 'RESTAURANT_AMENITY_LABELS',
    'photo_spot' => 'PHOTO_SPOT_AMENITY_LABELS',
];

function isValidPlaceTypeTag(string $type, string $tag): bool {
    $constName = PLACE_TAG_LABELS[$type] ?? null;
    if (!$constName) return false;
    $labels = defined($constName) ? constant($constName) : [];
    return isset($labels[$tag]);
}

function getPlaceTagLabel(string $type, string $tag): string {
    $constName = PLACE_TAG_LABELS[$type] ?? null;
    if (!$constName) return ucfirst(str_replace('-', ' ', $tag));
    $labels = defined($constName) ? constant($constName) : [];
    return $labels[$tag] ?? ucfirst(str_replace('-', ' ', $tag));
}

function getPlaceAmenityLabel(string $type, string $amenity): string {
    $constName = PLACE_AMENITY_LABELS[$type] ?? null;
    if (!$constName) return ucfirst(str_replace('-', ' ', $amenity));
    $labels = defined($constName) ? constant($constName) : [];
    return $labels[$amenity] ?? ucfirst(str_replace('-', ' ', $amenity));
}

// NOTE: Display label functions (getTagLabel, getAmenityLabel, getConditionLabel)
// are defined in inc/helpers.php to avoid duplicate declarations.
// Include helpers.php if you need these functions.
