<?php
/**
 * Audit logging helpers for security-sensitive actions.
 */

if (defined('AUDIT_LOG_INCLUDED')) {
    return;
}
define('AUDIT_LOG_INCLUDED', true);

require_once __DIR__ . '/db.php';

function auditLogsAvailable(): bool
{
    static $available = null;

    if ($available !== null) {
        return $available;
    }

    $row = queryOne("SELECT name FROM sqlite_master WHERE type = 'table' AND name = 'audit_logs'");
    $available = is_array($row) && !empty($row['name']);

    return $available;
}

function auditLogCurrentActorId(): ?string
{
    if (session_status() !== PHP_SESSION_ACTIVE) {
        return null;
    }

    $userId = trim((string) ($_SESSION['user_id'] ?? ''));
    return $userId !== '' ? $userId : null;
}

function auditLogHashValue(?string $value): ?string
{
    $value = trim((string) $value);
    return $value !== '' ? hash('sha256', $value) : null;
}

function auditLogRecord(string $action, array $options = []): bool
{
    if (!auditLogsAvailable()) {
        return false;
    }

    $action = trim($action);
    if ($action === '') {
        return false;
    }

    $actorUserId = trim((string) ($options['actor_user_id'] ?? auditLogCurrentActorId() ?? ''));
    $targetType = trim((string) ($options['target_type'] ?? ''));
    $targetId = trim((string) ($options['target_id'] ?? ''));
    $targetEmailHash = trim((string) ($options['target_email_hash'] ?? ''));
    $requestUri = trim((string) ($_SERVER['REQUEST_URI'] ?? ''));
    $ipHash = auditLogHashValue((string) ($_SERVER['REMOTE_ADDR'] ?? ''));
    $uaHash = auditLogHashValue((string) ($_SERVER['HTTP_USER_AGENT'] ?? ''));

    $metadata = $options['metadata'] ?? [];
    if (!is_array($metadata)) {
        $metadata = ['value' => $metadata];
    }

    $metadataJson = json_encode($metadata, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
    if (!is_string($metadataJson)) {
        $metadataJson = '{}';
    }

    return execute(
        'INSERT INTO audit_logs (
            id, actor_user_id, action, target_type, target_id, target_email_hash,
            request_uri, ip_hash, ua_hash, metadata_json, created_at
        ) VALUES (
            :id, :actor_user_id, :action, :target_type, :target_id, :target_email_hash,
            :request_uri, :ip_hash, :ua_hash, :metadata_json, CURRENT_TIMESTAMP
        )',
        [
            ':id' => uuid(),
            ':actor_user_id' => $actorUserId !== '' ? $actorUserId : null,
            ':action' => $action,
            ':target_type' => $targetType !== '' ? $targetType : null,
            ':target_id' => $targetId !== '' ? $targetId : null,
            ':target_email_hash' => $targetEmailHash !== '' ? $targetEmailHash : null,
            ':request_uri' => $requestUri !== '' ? $requestUri : null,
            ':ip_hash' => $ipHash,
            ':ua_hash' => $uaHash,
            ':metadata_json' => $metadataJson,
        ]
    );
}
