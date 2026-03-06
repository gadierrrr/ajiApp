<?php
/**
 * Migration: add audit_logs for security-sensitive actions.
 */

require_once __DIR__ . '/../inc/db.php';

echo "Starting migration: audit logs\n";

try {
    $db = getDB();

    $db->exec("
        CREATE TABLE IF NOT EXISTS audit_logs (
            id TEXT PRIMARY KEY,
            actor_user_id TEXT,
            action TEXT NOT NULL,
            target_type TEXT,
            target_id TEXT,
            target_email_hash TEXT,
            request_uri TEXT,
            ip_hash TEXT,
            ua_hash TEXT,
            metadata_json TEXT NOT NULL DEFAULT '{}',
            created_at TEXT DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (actor_user_id) REFERENCES users(id) ON DELETE SET NULL
        )
    ");
    $db->exec('CREATE INDEX IF NOT EXISTS idx_audit_logs_action_created ON audit_logs(action, created_at)');
    $db->exec('CREATE INDEX IF NOT EXISTS idx_audit_logs_actor_created ON audit_logs(actor_user_id, created_at)');
    $db->exec('CREATE INDEX IF NOT EXISTS idx_audit_logs_target_created ON audit_logs(target_type, target_id, created_at)');

    echo "✅ Migration completed successfully!\n";
} catch (Throwable $e) {
    echo "❌ Migration failed: " . $e->getMessage() . "\n";
    exit(1);
}
