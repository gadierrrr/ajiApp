<?php
/**
 * Migration: Update beach coordinates - Batch 2
 * Fixes 51 additional beaches with incorrect lat/lng from verification audit
 *
 * Priority breakdown:
 * - 20 CRITICAL (>2km off)
 * - 21 HIGH (500m-2km off)
 * - 10 MEDIUM (100-500m off)
 *
 * Run: php migrations/007-update-beach-coordinates-batch2.php
 */

require_once __DIR__ . '/../inc/db.php';

// Coordinate corrections: beach name => [correct_lat, correct_lng]
// Verified via Google Maps, Apple Maps, and beach directory sites
$corrections = [
    // CRITICAL priority (>2km off)
    'Gas Chambers' => [18.460193, -67.165582],
    'Surfer\'s Beach' => [18.506379, -67.14112],  // Note: apostrophe in name
    'Playa Grande' => [18.0936, -65.5072],
    'Pata Prieta' => [18.0925, -65.4475],
    'Montones Beach' => [18.5134, -67.0655],
    'El Pastillo' => [18.4937, -66.9828],
    'Cueva de las Golondrinas' => [18.492, -66.98],
    'Poza El Pastillo' => [18.4937, -66.9828],
    'Pozuelo' => [17.9363, -66.196],
    'Combate Beach' => [17.98, -67.19],
    'La Pared' => [18.377, -65.7151],
    'Playa Azul' => [18.377, -65.7151],  // Only one Playa Azul in DB
    'Carlos Rosario' => [18.3245, -65.3298],
    'Puerto Hermina' => [18.4835, -66.902],
    'Malecón de Naguabo' => [18.1866, -65.7096],

    // HIGH priority (500m-2km off)
    'Borinquen' => [18.497, -67.149],
    'Sun Bay' => [18.0968, -65.4638],
    'Airport Beach' => [18.135, -65.494],
    'Playa Negra' => [18.09, -65.47],
    'Black Sand Beach' => [18.09, -65.47],
    'Sandy Beach' => [18.135, -65.42],
    'Pelícano' => [18.095, -65.448],
    'Jobos Beach' => [18.5142, -67.0753],
    'Shacks' => [18.5152, -67.1011],
    'Balneario Punta Guilarte' => [17.962041, -66.04],
    'Playa Sucia' => [17.936, -67.189],
    'Bahía Sucia' => [17.936, -67.189],
    'Poza de las Mujeres' => [18.477, -66.5067],
    'Marías' => [18.3697, -67.2687],
    'Playa Fortuna' => [18.3803, -65.7426],
    'Coco Beach' => [18.3885, -65.7558],  // Rio Mar area beach name in DB
    'Playa Río Mar' => [18.3885, -65.7558],

    // MEDIUM priority (100-500m off)
    'Esperanza' => [18.0917, -65.4697],
    'La Guancha' => [17.9697, -66.6181],
    'Condado Beach' => [18.456285, -66.070518],
    'Playita del Condado' => [18.4611, -66.0823],
    'El Escambrón' => [18.4655, -66.0875],
    'Escambrón' => [18.4655, -66.0875],
    'Seven Seas' => [18.3695, -65.636],
    'Flamenco Beach' => [18.328, -65.3153],
    'Domes' => [18.3646, -67.2697],
];

$db = getDb();
$updated = 0;
$failed = [];
$alreadyCorrect = [];

echo "Beach Coordinate Migration - Batch 2\n";
echo "=====================================\n\n";

foreach ($corrections as $name => $coords) {
    // First check if coordinates already match (idempotent check)
    $checkStmt = $db->prepare("SELECT lat, lng FROM beaches WHERE name LIKE :name_pattern LIMIT 1");
    $checkStmt->bindValue(':name_pattern', '%' . $name . '%', SQLITE3_TEXT);
    $result = $checkStmt->execute();
    $row = $result->fetchArray(SQLITE3_ASSOC);

    if ($row) {
        // Check if already correct (within 10 meters)
        $currentLat = (float)$row['lat'];
        $currentLng = (float)$row['lng'];
        $latDiff = abs($currentLat - $coords[0]);
        $lngDiff = abs($currentLng - $coords[1]);

        // If difference is tiny, skip (already correct)
        if ($latDiff < 0.0001 && $lngDiff < 0.0001) {
            $alreadyCorrect[] = $name;
            continue;
        }
    }

    // Try partial match (name contains)
    $stmt = $db->prepare("UPDATE beaches SET lat = :lat, lng = :lng, updated_at = CURRENT_TIMESTAMP WHERE name LIKE :name_pattern");
    $stmt->bindValue(':lat', $coords[0], SQLITE3_FLOAT);
    $stmt->bindValue(':lng', $coords[1], SQLITE3_FLOAT);
    $stmt->bindValue(':name_pattern', '%' . $name . '%', SQLITE3_TEXT);
    $stmt->execute();

    $changes = $db->changes();
    if ($changes > 0) {
        echo "✓ Updated ($changes): $name\n";
        $updated += $changes;
    } else {
        $failed[] = $name;
    }
}

echo "\n=====================================\n";
echo "✅ Migration completed: $updated beaches updated\n";

if ($alreadyCorrect) {
    echo "\n✓ Already correct: " . count($alreadyCorrect) . " beaches\n";
}

if ($failed) {
    echo "\n⚠️  Failed to match " . count($failed) . " patterns:\n";
    foreach ($failed as $name) {
        echo "   - $name\n";
    }
    echo "\nThese may have different names in the database. Check with:\n";
    echo "sqlite3 data/aji-app.db \"SELECT name FROM beaches WHERE name LIKE '%keyword%';\"\n";
}

echo "\nVerification:\n";
echo "sqlite3 data/aji-app.db \"SELECT name, lat, lng FROM beaches WHERE name LIKE '%Surfers%';\"\n";
echo "sqlite3 data/aji-app.db \"SELECT name, lat, lng FROM beaches WHERE name LIKE '%Gas Chambers%';\"\n";
