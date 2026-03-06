<?php
/**
 * Migration 025: Add notes_es column to beaches table
 */

require_once __DIR__ . '/../inc/db.php';

echo "Migration 025: Adding notes_es column...\n";

$exists = queryOne("SELECT COUNT(*) as cnt FROM pragma_table_info('beaches') WHERE name = 'notes_es'");
if ($exists && $exists['cnt'] > 0) {
    echo "  Column beaches.notes_es already exists, skipping.\n";
} else {
    execute("ALTER TABLE beaches ADD COLUMN notes_es TEXT DEFAULT NULL");
    echo "  Added beaches.notes_es\n";
}

echo "Migration 025 complete.\n";
