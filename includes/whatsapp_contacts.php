<?php
/**
 * WhatsApp contact registry: consent and opt-out (Meta marketing compliance).
 */
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/whatsapp_send.php';

function whatsapp_contacts_table_exists(PDO $pdo): bool
{
    try {
        $pdo->query('SELECT 1 FROM whatsapp_contacts LIMIT 1');

        return true;
    } catch (Throwable $e) {
        return false;
    }
}

function whatsapp_contact_touch_inbound(PDO $pdo, string $waId, ?string $displayName = null): void
{
    if (!whatsapp_contacts_table_exists($pdo)) {
        return;
    }
    $waId = whatsapp_normalize_recipient($waId);
    $stmt = $pdo->prepare(
        'INSERT INTO whatsapp_contacts (wa_id, display_name, last_inbound_at, consent_status, consent_at, consent_source)
         VALUES (?, ?, CURRENT_TIMESTAMP, \'opted_in\', CURRENT_TIMESTAMP, \'whatsapp_inbound\')
         ON DUPLICATE KEY UPDATE
           display_name = COALESCE(VALUES(display_name), display_name),
           last_inbound_at = CURRENT_TIMESTAMP,
           consent_status = IF(consent_status = \'opted_out\', consent_status, \'opted_in\'),
           consent_at = IF(consent_status = \'opted_out\', consent_at, COALESCE(consent_at, CURRENT_TIMESTAMP)),
           consent_source = IF(consent_status = \'opted_out\', consent_source, COALESCE(consent_source, \'whatsapp_inbound\')),
           updated_at = CURRENT_TIMESTAMP'
    );
    $stmt->execute([$waId, $displayName]);
}

function whatsapp_contact_record_opt_in(string $waId, string $source = 'admin'): void
{
    $pdo = db();
    if (!whatsapp_contacts_table_exists($pdo)) {
        return;
    }
    $waId = whatsapp_normalize_recipient($waId);
    $pdo->prepare(
        'INSERT INTO whatsapp_contacts (wa_id, consent_status, consent_source, consent_at)
         VALUES (?, \'opted_in\', ?, CURRENT_TIMESTAMP)
         ON DUPLICATE KEY UPDATE consent_status = \'opted_in\', consent_source = VALUES(consent_source),
           consent_at = CURRENT_TIMESTAMP, opt_out_at = NULL, updated_at = CURRENT_TIMESTAMP'
    )->execute([$waId, substr($source, 0, 64)]);
}

function whatsapp_contact_record_opt_out(string $waId, string $source = 'admin'): void
{
    $pdo = db();
    if (!whatsapp_contacts_table_exists($pdo)) {
        return;
    }
    $waId = whatsapp_normalize_recipient($waId);
    $pdo->prepare(
        'INSERT INTO whatsapp_contacts (wa_id, consent_status, consent_source, opt_out_at)
         VALUES (?, \'opted_out\', ?, CURRENT_TIMESTAMP)
         ON DUPLICATE KEY UPDATE consent_status = \'opted_out\', opt_out_at = CURRENT_TIMESTAMP, updated_at = CURRENT_TIMESTAMP'
    )->execute([$waId, substr($source, 0, 64)]);
}

function whatsapp_contact_can_receive_marketing(string $waId): bool
{
    $pdo = db();
    if (!whatsapp_contacts_table_exists($pdo)) {
        return false;
    }
    $waId = whatsapp_normalize_recipient($waId);
    $stmt = $pdo->prepare('SELECT consent_status FROM whatsapp_contacts WHERE wa_id = ? LIMIT 1');
    $stmt->execute([$waId]);
    $status = $stmt->fetchColumn();

    return $status === 'opted_in';
}

/**
 * @return list<array{wa_id:string,display_name:?string,consent_status:string}>
 */
function whatsapp_contacts_marketing_eligible(string $filter = 'opted_in_only'): array
{
    $pdo = db();
    if (!whatsapp_contacts_table_exists($pdo)) {
        return [];
    }
    if ($filter === 'all_not_opted_out') {
        $sql = 'SELECT wa_id, display_name, consent_status FROM whatsapp_contacts WHERE consent_status != \'opted_out\' ORDER BY last_inbound_at DESC';
    } else {
        $sql = 'SELECT wa_id, display_name, consent_status FROM whatsapp_contacts WHERE consent_status = \'opted_in\' ORDER BY last_inbound_at DESC';
    }

    return $pdo->query($sql)->fetchAll(PDO::FETCH_ASSOC) ?: [];
}
