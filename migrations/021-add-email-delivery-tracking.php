<?php
/**
 * Migration: add email delivery/event/contact tracking tables.
 */

require_once __DIR__ . '/../inc/db.php';

echo "Starting migration: email delivery tracking\n";

$db = getDb();

$db->exec("\n    CREATE TABLE IF NOT EXISTS email_messages (\n        id TEXT PRIMARY KEY,\n        template_slug TEXT,\n        category TEXT NOT NULL DEFAULT 'non_critical',\n        to_email_hash TEXT NOT NULL,\n        provider TEXT NOT NULL,\n        provider_message_id TEXT,\n        status TEXT NOT NULL DEFAULT 'pending',\n        failure_code TEXT,\n        failure_reason TEXT,\n        sent_at TEXT,\n        created_at TEXT DEFAULT CURRENT_TIMESTAMP,\n        updated_at TEXT DEFAULT CURRENT_TIMESTAMP\n    )\n");

$db->exec("CREATE INDEX IF NOT EXISTS idx_email_messages_status ON email_messages(status)");
$db->exec("CREATE INDEX IF NOT EXISTS idx_email_messages_template_slug ON email_messages(template_slug)");
$db->exec("CREATE INDEX IF NOT EXISTS idx_email_messages_to_hash ON email_messages(to_email_hash)");
$db->exec("CREATE INDEX IF NOT EXISTS idx_email_messages_provider_id ON email_messages(provider, provider_message_id)");

$db->exec("\n    CREATE TABLE IF NOT EXISTS email_events (\n        id TEXT PRIMARY KEY,\n        email_message_id TEXT,\n        event_type TEXT NOT NULL,\n        provider_event_id TEXT,\n        payload_json TEXT,\n        occurred_at TEXT DEFAULT CURRENT_TIMESTAMP,\n        created_at TEXT DEFAULT CURRENT_TIMESTAMP,\n        FOREIGN KEY (email_message_id) REFERENCES email_messages(id) ON DELETE SET NULL\n    )\n");

$db->exec("CREATE INDEX IF NOT EXISTS idx_email_events_message_id ON email_events(email_message_id)");
$db->exec("CREATE INDEX IF NOT EXISTS idx_email_events_type ON email_events(event_type)");
$db->exec("CREATE UNIQUE INDEX IF NOT EXISTS idx_email_events_provider_event_id ON email_events(provider_event_id) WHERE provider_event_id IS NOT NULL AND provider_event_id <> ''");

$db->exec("\n    CREATE TABLE IF NOT EXISTS email_contacts (\n        email_hash TEXT PRIMARY KEY,\n        plunk_contact_id TEXT,\n        unsubscribed INTEGER NOT NULL DEFAULT 0,\n        suppressed_reason TEXT,\n        last_synced_at TEXT,\n        updated_at TEXT DEFAULT CURRENT_TIMESTAMP\n    )\n");

$db->exec("CREATE INDEX IF NOT EXISTS idx_email_contacts_unsubscribed ON email_contacts(unsubscribed)");

echo "✅ Migration completed: email delivery tracking\n";
