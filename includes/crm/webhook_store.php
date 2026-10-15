<?php
/**
 * Durable WhatsApp webhook capture (before business processing).
 */
declare(strict_types=1);

require_once __DIR__ . '/tenant.php';

function crm_store_webhook_event(PDO $pdo, string $rawBody, int $tenantId = 1): ?int
{
    if (!crm_tables_ready($pdo) || $rawBody === '') {
        return null;
    }
    $hash = hash('sha256', $rawBody);
    $payload = json_decode($rawBody, true);
    if (!is_array($payload)) {
        return null;
    }
    $json = json_encode($payload, JSON_UNESCAPED_UNICODE);
    if ($json === false) {
        return null;
    }

    $stmt = $pdo->prepare(
        'INSERT IGNORE INTO crm_webhook_events (tenant_id, event_sha256, payload_json, payload_bytes)
         VALUES (?, ?, ?, ?)'
    );
    $stmt->execute([$tenantId, $hash, $json, strlen($rawBody)]);
    if ($stmt->rowCount() === 0) {
        $find = $pdo->prepare('SELECT id FROM crm_webhook_events WHERE event_sha256 = ? LIMIT 1');
        $find->execute([$hash]);

        $existingId = $find->fetchColumn();

        return $existingId !== false ? (int) $existingId : null;
    }

    return (int) $pdo->lastInsertId();
}

function crm_mark_webhook_event_processed(PDO $pdo, ?int $eventId, ?string $error = null): void
{
    if ($eventId === null || !crm_tables_ready($pdo)) {
        return;
    }
    $stmt = $pdo->prepare(
        'UPDATE crm_webhook_events
         SET processed_at = UTC_TIMESTAMP(), process_error = ?
         WHERE id = ?'
    );
    $stmt->execute([
        $error !== null ? substr($error, 0, 500) : null,
        $eventId,
    ]);
}
