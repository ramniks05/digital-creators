<?php
/**
 * CRM conversations — sync with existing WhatsApp message tables.
 */
declare(strict_types=1);

require_once __DIR__ . '/tenant.php';

function crm_normalize_peer_wa_id(string $waId): string
{
    require_once dirname(__DIR__) . '/whatsapp_send.php';

    return whatsapp_normalize_recipient($waId);
}

function crm_touch_contact(PDO $pdo, int $tenantId, string $peerWaId, ?string $displayName = null): ?int
{
    if (!crm_tables_ready($pdo)) {
        return null;
    }
    $peerWaId = crm_normalize_peer_wa_id($peerWaId);
    if ($peerWaId === '') {
        return null;
    }

    $exists = $pdo->prepare('SELECT id FROM crm_contacts WHERE tenant_id = ? AND wa_id = ? LIMIT 1');
    $exists->execute([$tenantId, $peerWaId]);
    $wasNew = $exists->fetchColumn() === false;

    $params = [
        $tenantId,
        $peerWaId,
        $displayName !== null && $displayName !== '' ? substr($displayName, 0, 160) : null,
        $peerWaId,
    ];
    try {
        $pdo->prepare(
            'INSERT INTO crm_contacts (tenant_id, wa_id, display_name, phone, source)
             VALUES (?, ?, ?, ?, \'whatsapp_inbound\')
             ON DUPLICATE KEY UPDATE
               display_name = COALESCE(VALUES(display_name), display_name),
               phone = VALUES(phone),
               updated_at = CURRENT_TIMESTAMP'
        )->execute($params);
    } catch (PDOException $e) {
        $pdo->prepare(
            'INSERT INTO crm_contacts (tenant_id, wa_id, display_name, phone)
             VALUES (?, ?, ?, ?)
             ON DUPLICATE KEY UPDATE
               display_name = COALESCE(VALUES(display_name), display_name),
               phone = VALUES(phone),
               updated_at = CURRENT_TIMESTAMP'
        )->execute($params);
    }

    $stmt = $pdo->prepare('SELECT id FROM crm_contacts WHERE tenant_id = ? AND wa_id = ? LIMIT 1');
    $stmt->execute([$tenantId, $peerWaId]);
    $id = $stmt->fetchColumn();
    if ($id === false) {
        return null;
    }
    $contactId = (int) $id;

    if ($wasNew && is_file(__DIR__ . '/contact_activity.php')) {
        require_once __DIR__ . '/contact_activity.php';
        crm_contact_log_activity(
            $pdo,
            $tenantId,
            $contactId,
            'created',
            'Auto-created from WhatsApp',
            ['source' => 'whatsapp_inbound']
        );
    }

    return $contactId;
}

/**
 * @param 'inbound'|'outbound' $direction
 */
function crm_sync_conversation_from_message(
    PDO $pdo,
    int $tenantId,
    string $peerWaId,
    string $direction,
    ?string $preview,
    ?string $contactName = null
): void {
    if (!crm_tables_ready($pdo)) {
        return;
    }
    $peerWaId = crm_normalize_peer_wa_id($peerWaId);
    if ($peerWaId === '') {
        return;
    }

    $preview = trim((string) $preview);
    if ($preview !== '' && strlen($preview) > 255) {
        $preview = substr($preview, 0, 252) . '…';
    }

    $contactId = crm_touch_contact($pdo, $tenantId, $peerWaId, $contactName);

    $insertUnread = $direction === 'inbound' ? 1 : 0;
    $inboundTs = $direction === 'inbound' ? gmdate('Y-m-d H:i:s') : null;

    $pdo->prepare(
        'INSERT INTO crm_conversations
         (tenant_id, channel, peer_wa_id, contact_id, status, last_message_at, last_message_preview, last_inbound_at, unread_count)
         VALUES (?, \'whatsapp\', ?, ?, \'open\', UTC_TIMESTAMP(), ?, ?, ?)
         ON DUPLICATE KEY UPDATE
           contact_id = COALESCE(VALUES(contact_id), contact_id),
           status = IF(status = \'resolved\' AND ? = \'inbound\', \'open\', status),
           last_message_at = UTC_TIMESTAMP(),
           last_message_preview = COALESCE(NULLIF(VALUES(last_message_preview), \'\'), last_message_preview),
           last_inbound_at = IF(? = \'inbound\', UTC_TIMESTAMP(), last_inbound_at),
           unread_count = IF(? = \'inbound\', unread_count + 1, unread_count),
           updated_at = CURRENT_TIMESTAMP'
    )->execute([
        $tenantId,
        $peerWaId,
        $contactId,
        $preview,
        $inboundTs,
        $insertUnread,
        $direction,
        $direction,
        $direction,
    ]);
}

function crm_backfill_conversations_from_whatsapp(PDO $pdo, int $tenantId): int
{
    if (!crm_tables_ready($pdo)) {
        return 0;
    }
    $peers = $pdo->query(
        'SELECT wa_id, MAX(last_at) AS last_at FROM (
            SELECT from_wa_id AS wa_id, created_at AS last_at FROM whatsapp_inbound_messages
            UNION ALL
            SELECT recipient_wa_id, updated_at FROM whatsapp_outbound_messages
         ) u GROUP BY wa_id'
    )->fetchAll(PDO::FETCH_ASSOC);

    $count = 0;
    foreach ($peers as $peer) {
        $waId = (string) ($peer['wa_id'] ?? '');
        if ($waId === '') {
            continue;
        }
        $nameStmt = $pdo->prepare(
            'SELECT contact_name, body_text FROM whatsapp_inbound_messages
             WHERE from_wa_id = ? ORDER BY id DESC LIMIT 1'
        );
        $nameStmt->execute([$waId]);
        $in = $nameStmt->fetch(PDO::FETCH_ASSOC) ?: [];
        $preview = trim((string) ($in['body_text'] ?? ''));
        if ($preview === '') {
            $out = $pdo->prepare(
                'SELECT payload_json FROM whatsapp_outbound_messages WHERE recipient_wa_id = ? ORDER BY id DESC LIMIT 1'
            );
            $out->execute([$waId]);
            $payload = json_decode((string) ($out->fetchColumn() ?: ''), true);
            if (is_array($payload) && !empty($payload['text'])) {
                $preview = (string) $payload['text'];
            }
        }
        crm_sync_conversation_from_message(
            $pdo,
            $tenantId,
            $waId,
            'outbound',
            $preview !== '' ? $preview : 'WhatsApp conversation',
            isset($in['contact_name']) ? (string) $in['contact_name'] : null
        );
        $count++;
    }

    return $count;
}

/**
 * @return list<array<string, mixed>>
 */
function crm_list_conversations(
    PDO $pdo,
    int $tenantId,
    ?string $status = null,
    ?int $assignedAdminId = null,
    int $limit = 100,
    ?string $search = null
): array {
    $limit = max(1, min(200, $limit));
    $sql = 'SELECT c.*, ct.display_name AS contact_name, ct.tags_json, ct.consent_status
            FROM crm_conversations c
            LEFT JOIN crm_contacts ct ON ct.id = c.contact_id
            WHERE c.tenant_id = ?';
    $params = [$tenantId];
    if ($status !== null && $status !== '') {
        $sql .= ' AND c.status = ?';
        $params[] = $status;
    }
    if ($assignedAdminId !== null && $assignedAdminId > 0) {
        $sql .= ' AND c.assigned_admin_id = ?';
        $params[] = $assignedAdminId;
    } elseif ($assignedAdminId === 0) {
        $sql .= ' AND c.assigned_admin_id IS NULL';
    }
    $search = trim((string) $search);
    if ($search !== '') {
        $like = '%' . $search . '%';
        $sql .= ' AND (c.peer_wa_id LIKE ? OR ct.display_name LIKE ? OR ct.phone LIKE ?)';
        $params[] = $like;
        $params[] = $like;
        $params[] = $like;
    }
    $sql .= ' ORDER BY c.last_message_at DESC, c.id DESC LIMIT ' . $limit;
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);

    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

function crm_get_conversation(PDO $pdo, int $tenantId, int $conversationId): ?array
{
    $stmt = $pdo->prepare(
        'SELECT c.*, ct.display_name AS contact_name, ct.email AS contact_email,
                ct.phone AS contact_phone, ct.tags_json, ct.consent_status, ct.custom_fields_json
         FROM crm_conversations c
         LEFT JOIN crm_contacts ct ON ct.id = c.contact_id
         WHERE c.tenant_id = ? AND c.id = ? LIMIT 1'
    );
    $stmt->execute([$tenantId, $conversationId]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);

    return $row ?: null;
}

function crm_dashboard_metrics(PDO $pdo, int $tenantId): array
{
    if (!crm_tables_ready($pdo)) {
        return [];
    }
    $metrics = [];
    $metrics['contacts'] = (int) $pdo->query(
        "SELECT COUNT(*) FROM crm_contacts WHERE tenant_id = {$tenantId}"
    )->fetchColumn();
    $metrics['conversations_open'] = (int) $pdo->query(
        "SELECT COUNT(*) FROM crm_conversations WHERE tenant_id = {$tenantId} AND status = 'open'"
    )->fetchColumn();
    $metrics['conversations_pending'] = (int) $pdo->query(
        "SELECT COUNT(*) FROM crm_conversations WHERE tenant_id = {$tenantId} AND status = 'pending'"
    )->fetchColumn();
    $metrics['conversations_unassigned'] = (int) $pdo->query(
        "SELECT COUNT(*) FROM crm_conversations WHERE tenant_id = {$tenantId} AND assigned_admin_id IS NULL"
    )->fetchColumn();
    $metrics['unread_total'] = (int) $pdo->query(
        "SELECT COALESCE(SUM(unread_count), 0) FROM crm_conversations WHERE tenant_id = {$tenantId}"
    )->fetchColumn();

    try {
        $metrics['leads_new'] = (int) $pdo->query("SELECT COUNT(*) FROM leads WHERE status = 'new'")->fetchColumn();
    } catch (Throwable $e) {
        $metrics['leads_new'] = 0;
    }

    try {
        $metrics['inbound_today'] = (int) $pdo->query(
            'SELECT COUNT(*) FROM whatsapp_inbound_messages WHERE created_at >= CURDATE()'
        )->fetchColumn();
    } catch (Throwable $e) {
        $metrics['inbound_today'] = 0;
    }

    return $metrics;
}

function crm_update_conversation(
    PDO $pdo,
    int $tenantId,
    int $conversationId,
    ?string $status = null,
    ?int $assignedAdminId = null,
    bool $markRead = false
): bool {
    $sets = [];
    $params = [];
    if ($status !== null && in_array($status, ['open', 'pending', 'resolved'], true)) {
        $sets[] = 'status = ?';
        $params[] = $status;
    }
    if ($assignedAdminId !== null) {
        $sets[] = 'assigned_admin_id = ?';
        $params[] = $assignedAdminId > 0 ? $assignedAdminId : null;
    }
    if ($markRead) {
        $sets[] = 'unread_count = 0';
    }
    if ($sets === []) {
        return false;
    }
    $sets[] = 'updated_at = CURRENT_TIMESTAMP';
    $params[] = $tenantId;
    $params[] = $conversationId;
    $sql = 'UPDATE crm_conversations SET ' . implode(', ', $sets) . ' WHERE tenant_id = ? AND id = ?';
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);

    return $stmt->rowCount() > 0;
}
