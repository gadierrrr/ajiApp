<?php
/**
 * Migration 024: Add Spanish content columns to beach detail tables
 */

require_once __DIR__ . '/../inc/db.php';

echo "Migration 024: Adding Spanish content columns...\n";

$columns = [
    ['beaches', 'safety_info_es', 'TEXT DEFAULT NULL'],
    ['beaches', 'best_time_es', 'TEXT DEFAULT NULL'],
    ['beaches', 'parking_details_es', 'TEXT DEFAULT NULL'],
    ['beach_features', 'title_es', 'TEXT DEFAULT NULL'],
    ['beach_features', 'description_es', 'TEXT DEFAULT NULL'],
    ['beach_tips', 'tip_es', 'TEXT DEFAULT NULL'],
];

foreach ($columns as [$table, $column, $type]) {
    $exists = queryOne("SELECT COUNT(*) as cnt FROM pragma_table_info('$table') WHERE name = '$column'");
    if ($exists && $exists['cnt'] > 0) {
        echo "  Column {$table}.{$column} already exists, skipping.\n";
        continue;
    }
    execute("ALTER TABLE {$table} ADD COLUMN {$column} {$type}");
    echo "  Added {$table}.{$column}\n";
}

echo "Migration 024 complete.\n";
