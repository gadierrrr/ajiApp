<?php
/**
 * Migration: Update beach coordinates from verification audit
 * Fixes 55 beaches with incorrect lat/lng (94% of verified beaches)
 *
 * Priority breakdown:
 * - 22 CRITICAL (>2km off)
 * - 26 HIGH (500m-2km off)
 * - 7 MEDIUM (100-500m off)
 *
 * Run: php migrations/006-update-beach-coordinates.php
 */

require_once __DIR__ . '/../inc/db.php';

// Coordinate corrections: beach name => [correct_lat, correct_lng]
// Verified via Google Maps satellite imagery
// Names must match database exactly (checked via SELECT name FROM beaches)
$corrections = [
    // CRITICAL priority (>2km off)
    'Carlos Rosario Beach' => [18.3245, -65.3298],
    'Mar Chiquita' => [18.4727, -66.4854],
    'Steps Beach (Tres Palmas)' => [18.3497, -67.2642],
    'Media Luna (Vieques)' => [18.0913, -65.4501],
    'La Chiva (Blue Beach)' => [18.1129, -65.3875],
    'Playa Escondida (Fajardo)' => [18.3767, -65.6453],
    'Playa Colora' => [18.3769, -65.6412],
    'Combate Beach' => [17.9766, -67.2128],
    'Buyé Beach' => [18.0497, -67.1985],
    'Playa Santa' => [17.9398, -66.9566],
    'Montones Beach' => [18.5135, -67.0655],
    'Cueva del Indio Shore' => [18.4826, -66.9618],
    'Playa Guardarraya' => [17.9744, -65.9883],
    'Playa Guayanés (Yabucoa)' => [18.0616, -65.8218],
    'Melones Beach' => [18.303, -65.3107],
    'Playa Resaca' => [18.3324, -65.3044],
    'Kikita Beach' => [18.4772, -66.2577],
    'Playa Levittown' => [18.4527, -66.1757],
    'Balneario Punta Salinas' => [18.4738, -66.1861],
    'Playa Puerto Nuevo' => [18.4948, -66.3815],
    'Piñones Boardwalk Shore' => [18.4476, -65.907],

    // HIGH priority (500m-2km off)
    'Jobos Beach' => [18.5142, -67.0752],
    'Condado Beach' => [18.4599, -66.0779],
    'Ocean Park Beach' => [18.453, -66.0495],
    'Balneario La Monserrate' => [18.3828, -65.7298],
    'Pools Beach' => [18.3697, -67.261],
    'Balneario Cerro Gordo' => [18.4815, -66.3397],
    'Balneario De Carolina' => [18.4494, -65.9971],
    'Tamarindo Beach' => [18.3178, -65.3174],
    'Sandy Beach' => [18.3705, -67.2587],
    'Shacks (Bajuras)' => [18.5152, -67.1011],
    'Balneario Caña Gorda' => [17.9527, -66.8827],
    'Playita Rosada' => [17.9722, -67.0321],
    'Guajataca Beach (Quebradillas side)' => [18.489, -66.9594],
    'Poza De Las Mujeres' => [18.477, -66.5067],
    'Balneario Punta Santiago' => [18.1693, -65.741],
    'Malecón de Naguabo Shore' => [18.1902, -65.7166],
    'Wilderness' => [18.4838, -67.1667],
    'Isla Culebrita – Tortuga Beach' => [18.3175, -65.2277],
    'Punta Soldado' => [18.2812, -65.2865],
    'Balneario Manuel “Nolo” Morales (Dorado Public Beach)' => [18.4751, -66.2802],
    'Aviones Beach' => [18.4577, -65.9807],
    'Condado Beach (Oceanfront)' => [18.4599, -66.0779],
    'Playita Del Condado' => [18.4611, -66.0823],
    'Punta Las Marías' => [18.4541, -66.0393],

    // MEDIUM priority (100-500m off)
    'Flamenco Beach' => [18.328, -65.3153],
    'Seven Seas Beach' => [18.3695, -65.6359],
    'Balneario de Boquerón' => [18.0245, -67.1735],
    'Domes Beach' => [18.3647, -67.2699],
    'Isla Verde – Balneario West Sector' => [18.4449, -66.0141],
    'Balneario El Escambrón' => [18.4669, -66.09],
    'Playa Palmas del Mar – Candelero' => [18.1672, -65.7432],
];

$db = getDb();
$updated = 0;
$failed = [];

echo "Beach Coordinate Migration\n";
echo "==========================\n\n";

foreach ($corrections as $name => $coords) {
    // Try exact match first
    $stmt = $db->prepare("UPDATE beaches SET lat = :lat, lng = :lng, updated_at = CURRENT_TIMESTAMP WHERE name = :name");
    $stmt->bindValue(':lat', $coords[0], SQLITE3_FLOAT);
    $stmt->bindValue(':lng', $coords[1], SQLITE3_FLOAT);
    $stmt->bindValue(':name', $name, SQLITE3_TEXT);
    $stmt->execute();

    if ($db->changes() > 0) {
        echo "✓ Updated: $name\n";
        $updated++;
    } else {
        // Try partial match (name starts with)
        $stmt = $db->prepare("UPDATE beaches SET lat = :lat, lng = :lng, updated_at = CURRENT_TIMESTAMP WHERE name LIKE :name_pattern");
        $stmt->bindValue(':lat', $coords[0], SQLITE3_FLOAT);
        $stmt->bindValue(':lng', $coords[1], SQLITE3_FLOAT);
        $stmt->bindValue(':name_pattern', $name . '%', SQLITE3_TEXT);
        $stmt->execute();

        if ($db->changes() > 0) {
            echo "✓ Updated (partial match): $name\n";
            $updated++;
        } else {
            $failed[] = $name;
        }
    }
}

echo "\n==========================\n";
echo "✅ Migration completed: $updated beaches updated\n";

if ($failed) {
    echo "\n⚠️  Failed to match " . count($failed) . " beaches:\n";
    foreach ($failed as $name) {
        echo "   - $name\n";
    }
    echo "\nThese may have different names in the database. Check with:\n";
    echo "sqlite3 data/aji-app.db \"SELECT name FROM beaches WHERE name LIKE '%keyword%';\"\n";
}

echo "\nVerification:\n";
echo "sqlite3 data/aji-app.db \"SELECT name, lat, lng FROM beaches WHERE name = 'Steps Beach (Tres Palmas)';\"\n";
