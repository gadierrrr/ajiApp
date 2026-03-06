#!/usr/bin/env php
<?php
/**
 * Migration: Add Spanish content columns for i18n support.
 *
 * Adds:
 *   beaches.description_es          – Spanish translation of beach description
 *   beach_content_sections.content_es – Spanish translation of section content
 *   beach_content_sections.heading_es – Spanish translation of section heading
 */

require_once __DIR__ . '/../inc/db.php';

$db = getDb();

$db->exec('ALTER TABLE beaches ADD COLUMN description_es TEXT DEFAULT NULL');
echo "Added beaches.description_es\n";

$db->exec('ALTER TABLE beach_content_sections ADD COLUMN content_es TEXT DEFAULT NULL');
echo "Added beach_content_sections.content_es\n";

$db->exec('ALTER TABLE beach_content_sections ADD COLUMN heading_es TEXT DEFAULT NULL');
echo "Added beach_content_sections.heading_es\n";

echo "Done.\n";
