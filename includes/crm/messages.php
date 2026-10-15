<?php
declare(strict_types=1);

require_once __DIR__ . '/conversations.php';
require_once __DIR__ . '/contact_activity.php';

function crm_messages_table_ready(PDO $pdo): bool
{
    try {
        $pdo->query('SELECT 1 FROM crm_messages LIMIT 1');

        return true;
    } catch (Throwable $e) {
        return false;
    }
}

function crm_conversation_id_for_peer(PDO $pdo, int $tenantId, string $peerWaId): ?int
{
    $peerWaId = crm_normalize_peer_wa_id($peerWaId);
    $stmt = $pdo->prepare(
        'SELECT id FROM crm_conversations WHERE tenant_id = ? AND channel = \'whatsapp\' AND peer_wa_id = ? LIMIT 1'
    );
    $stmt->execute([$tenantId, $peerWaId]);
    $id = $stmt->fetchColumn();

    return $id !== false ? (int) $id : null;
}

function crm_record_message(
    PDO $pdo,
    int $tenantId,
    string $peerWaId,
    string $direction,
    ?string $waMessageId,
    string $messageType,
    ?string $bodyText,
    ?array $mediaJson = null,
    ?string $deliveryStatus = null,
    ?int $sourceInboundId = null,
    ?int $sourceOutboundId = null
): ?int {
    if (!crm_messages_table_ready($pdo)) {
        return null;
    }
    crm_sync_conversation_from_message(
        $pdo,
        $tenantId,
        $peerWaId,
        $direction === 'inbound' ? 'inbound' : 'outbound',
        $bodyText ?? ''
    );
    $convId = crm_conversation_id_for_peer($pdo, $tenantId, $peerWaId);
    if ($convId === null) {
        return null;
    }

    $media = $mediaJson !== null ? json_encode($mediaJson, JSON_UNESCAPED_UNICODE) : null;
    if ($waMessageId !== null && $waMessageId !== '') {
        $stmt = $pdo->prepare(
            'INSERT INTO crm_messages
             (tenant_id, conversation_id, direction, wa_message_id, message_type, body_text, media_json, delivery_status, source_inbound_id, source_outbound_id, sent_at)
             VALUES (?,?,?,?,?,?,?,?,?,?,UTC_TIMESTAMP())
             ON DUPLICATE KEY UPDATE
               body_text = COALESCE(VALUES(body_text), body_text),
               delivery_status = COALESCE(VALUES(delivery_status), delivery_status),
               media_json = COALESCE(VALUES(media_json), media_json)'
        );
        $stmt->execute([
            $tenantId,
            $convId,
            $direction,
            $waMessageId,
            substr($messageType, 0, 32),
            $bodyText,
            $media,
            $deliveryStatus,
            $sourceInboundId,
            $sourceOutboundId,
        ]);
        if ($stmt->rowCount() === 1) {
            crm_log_message_contact_activity($pdo, $tenantId, $convId, $direction, $bodyText, $waMessageId);
        }
        $find = $pdo->prepare('SELECT id FROM crm_messages WHERE tenant_id = ? AND wa_message_id = ? LIMIT 1');
        $find->execute([$tenantId, $waMessageId]);
        $mid = $find->fetchColumn();

        return $mid !== false ? (int) $mid : null;
    }

    $stmt = $pdo->prepare(
        'INSERT INTO crm_messages
         (tenant_id, conversation_id, direction, message_type, body_text, media_json, delivery_status, source_inbound_id, source_outbound_id, sent_at)
         VALUES (?,?,?,?,?,?,?,?,?,UTC_TIMESTAMP())'
    );
    $stmt->execute([
        $tenantId,
        $convId,
        $direction,
        substr($messageType, 0, 32),
        $bodyText,
        $media,
        $deliveryStatus,
        $sourceInboundId,
        $sourceOutboundId,
    ]);

    $newId = (int) $pdo->lastInsertId();
    if ($newId > 0) {
        crm_log_message_contact_activity($pdo, $tenantId, $convId, $direction, $bodyText, $waMessageId);
    }

    return $newId;
}

function crm_log_message_contact_activity(
    PDO $pdo,
    int $tenantId,
    ?int $conversationId,
    string $direction,
    ?string $bodyText,
    ?string $waMessageId
): void {
    if ($conversationId === null) {
        return;
    }
    $cstmt = $pdo->prepare('SELECT contact_id FROM crm_conversations WHERE id = ? AND tenant_id = ? LIMIT 1');
    $cstmt->execute([$conversationId, $tenantId]);
    $contactId = $cstmt->fetchColumn();
    if ($contactId === false || (int) $contactId <= 0) {
        return;
    }
    $actType = $direction === 'inbound' ? 'inbound_message' : 'outbound_message';
    crm_contact_log_activity(
        $pdo,
        $tenantId,
        (int) $contactId,
        $actType,
        $bodyText !== null ? substr($bodyText, 0, 255) : null,
        $waMessageId !== null ? ['wa_message_id' => $waMessageId] : null
    );
}

function crm_update_message_delivery(PDO $pdo, int $tenantId, string $waMessageId, string $status): void
{
    if (!crm_messages_table_ready($pdo)) {
        return;
    }
    $pdo->prepare(
        'UPDATE crm_messages SET delivery_status = ? WHERE tenant_id = ? AND wa_message_id = ?'
    )->execute([substr($status, 0, 32), $tenantId, $waMessageId]);
}

function crm_record_status_event(
    PDO $pdo,
    int $tenantId,
    string $waMessageId,
    string $status,
    ?string $recipientWaId = null,
    ?int $waTimestamp = null,
    ?string $errorMessage = null
): void {
    if (!crm_messages_table_ready($pdo)) {
        return;
    }
    try {
        $pdo->prepare(
            'INSERT IGNORE INTO crm_message_status_events
             (tenant_id, wa_message_id, status, recipient_wa_id, error_message, wa_timestamp)
             VALUES (?,?,?,?,?,?)'
        )->execute([
            $tenantId,
            $waMessageId,
            substr($status, 0, 32),
            $recipientWaId,
            $errorMessage !== null ? substr($errorMessage, 0, 500) : null,
            $waTimestamp !== null && $waTimestamp > 0 ? $waTimestamp : null,
        ]);
    } catch (Throwable $e) {
        error_log('CRM status event: ' . $e->getMessage());
    }
    crm_update_message_delivery($pdo, $tenantId, $waMessageId, $status);
}

function crm_list_messages(
    PDO $pdo,
    int $tenantId,
    int $conversationId,
    int $limit = 50,
    ?int $beforeId = null
): array {
    $limit = max(1, min(100, $limit));
    $sql = 'SELECT * FROM crm_messages WHERE tenant_id = ? AND conversation_id = ?';
    $params = [$tenantId, $conversationId];
    if ($beforeId !== null && $beforeId > 0) {
        $sql .= ' AND id < ?';
        $params[] = $beforeId;
    }
    $sql .= ' ORDER BY id DESC LIMIT ' . $limit;
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $rows = array_reverse($stmt->fetchAll(PDO::FETCH_ASSOC));

    return $rows;
}

function crm_backfill_messages_from_whatsapp(PDO $pdo, int $tenantId): int
{
    if (!crm_messages_table_ready($pdo)) {
        return 0;
    }
    $count = 0;
    $in = $pdo->query('SELECT id, from_wa_id, wa_message_id, message_type, body_text, media_json, delivery_status FROM whatsapp_inbound_messages ORDER BY id ASC');
    foreach ($in->fetchAll(PDO::FETCH_ASSOC) as $row) {
        $media = !empty($row['media_json']) ? json_decode((string) $row['media_json'], true) : null;
        crm_record_message(
            $pdo,
            $tenantId,
            (string) $row['from_wa_id'],
            'inbound',
            (string) $row['wa_message_id'],
            (string) $row['message_type'],
            $row['body_text'],
            is_array($media) ? $media : null,
            null,
            (int) $row['id'],
            null
        );
        $count++;
    }
    require_once dirname(__DIR__) . '/whatsapp_send.php';
    $out = $pdo->query('SELECT id, recipient_wa_id, wa_message_id, message_type, delivery_status, payload_json FROM whatsapp_outbound_messages ORDER BY id ASC');
    foreach ($out->fetchAll(PDO::FETCH_ASSOC) as $row) {
        $payload = json_decode((string) ($row['payload_json'] ?? ''), true);
        $text = is_array($payload) ? (string) ($payload['text'] ?? '') : '';
        if ($text === '' && function_exists('whatsapp_outbound_row_preview')) {
            $text = whatsapp_outbound_row_preview($row);
        }
        crm_record_message(
            $pdo,
            $tenantId,
            (string) $row['recipient_wa_id'],
            'outbound',
            $row['wa_message_id'] !== null ? (string) $row['wa_message_id'] : null,
            (string) $row['message_type'],
            $text !== '' ? $text : null,
            null,
            (string) ($row['delivery_status'] ?? 'sent'),
            null,
            (int) $row['id']
        );
        $count++;
    }

    return $count;
}
