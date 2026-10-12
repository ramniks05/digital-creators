<?php
/**
 * Meta WhatsApp Cloud API — config, webhook verification, and event persistence.
 */
require_once __DIR__ . '/db.php';

/** Max raw webhook body size (Meta allows up to 3 MiB). */
const WHATSAPP_WEBHOOK_MAX_BYTES = 3145728;

/**
 * @return array{access_token:string,phone_number_id:string,webhook_verify_token:string,app_secret:string,api_version:string}
 */
function whatsapp_cloud_config(): array
{
    static $cached = null;
    if (is_array($cached)) {
        return $cached;
    }

    $config = require __DIR__ . '/config.php';
    $wa = $config['whatsapp'] ?? [];

    $cached = [
        'access_token' => (string) ($wa['access_token'] ?? ''),
        'phone_number_id' => (string) ($wa['phone_number_id'] ?? ''),
        'webhook_verify_token' => (string) ($wa['webhook_verify_token'] ?? ''),
        'app_secret' => (string) ($wa['app_secret'] ?? ''),
        'api_version' => (string) ($wa['api_version'] ?? 'v21.0'),
    ];

    return $cached;
}

function whatsapp_webhook_verify_token_configured(): bool
{
    return whatsapp_cloud_config()['webhook_verify_token'] !== '';
}

function whatsapp_app_secret_configured(): bool
{
    return whatsapp_cloud_config()['app_secret'] !== '';
}

/**
 * Meta GET webhook verification (hub.mode, hub.verify_token, hub.challenge).
 */
function whatsapp_webhook_query(string $underscoreKey, string $dottedKey): string
{
    if (isset($_GET[$underscoreKey])) {
        return trim((string) $_GET[$underscoreKey]);
    }
    if (isset($_GET[$dottedKey])) {
        return trim((string) $_GET[$dottedKey]);
    }

    return '';
}

function whatsapp_handle_verification_request(): void
{
    $mode = whatsapp_webhook_query('hub_mode', 'hub.mode');
    $token = whatsapp_webhook_query('hub_verify_token', 'hub.verify_token');
    $challenge = whatsapp_webhook_query('hub_challenge', 'hub.challenge');

    if ($mode !== 'subscribe' || $challenge === '') {
        http_response_code(403);
        exit;
    }

    $expected = trim(whatsapp_cloud_config()['webhook_verify_token']);
    if ($expected === '' || !hash_equals($expected, $token)) {
        http_response_code(403);
        exit;
    }

    header('Content-Type: text/plain; charset=UTF-8');
    http_response_code(200);
    echo $challenge;
    exit;
}

/**
 * Validate X-Hub-Signature-256 against raw body.
 */
function whatsapp_verify_signature(string $rawBody, ?string $signatureHeader): bool
{
    $secret = whatsapp_cloud_config()['app_secret'];
    if ($secret === '') {
        return false;
    }
    if ($signatureHeader === null || $signatureHeader === '') {
        return false;
    }
    if (!str_starts_with($signatureHeader, 'sha256=')) {
        return false;
    }
    $expectedHex = substr($signatureHeader, 7);
    if (!preg_match('/^[a-f0-9]{64}$/i', $expectedHex)) {
        return false;
    }
    $computed = hash_hmac('sha256', $rawBody, $secret);

    return hash_equals(strtolower($computed), strtolower($expectedHex));
}

/**
 * Record an outbound message when your send module receives a Graph API message id.
 */
function whatsapp_record_outbound_message(
    string $recipientWaId,
    string $messageType,
    ?string $waMessageId = null,
    ?string $phoneNumberId = null,
    ?string $templateName = null,
    ?array $payload = null
): int {
    $recipientWaId = preg_replace('/\D+/', '', $recipientWaId) ?: $recipientWaId;
    $pdo = db();
    $stmt = $pdo->prepare(
        'INSERT INTO whatsapp_outbound_messages
         (wa_message_id, phone_number_id, recipient_wa_id, message_type, template_name, payload_json, delivery_status)
         VALUES (?,?,?,?,?,?,?)'
    );
    $stmt->execute([
        $waMessageId,
        $phoneNumberId ?: whatsapp_cloud_config()['phone_number_id'] ?: null,
        $recipientWaId,
        substr($messageType, 0, 32),
        $templateName,
        $payload !== null ? json_encode($payload, JSON_UNESCAPED_UNICODE) : null,
        $waMessageId ? 'sent' : 'pending',
    ]);

    return (int) $pdo->lastInsertId();
}

/**
 * Attach Graph API message id after send response.
 */
function whatsapp_attach_outbound_message_id(int $outboundRowId, string $waMessageId): void
{
    $stmt = db()->prepare(
        'UPDATE whatsapp_outbound_messages
         SET wa_message_id = ?, delivery_status = IF(delivery_status = \'pending\', \'sent\', delivery_status), updated_at = CURRENT_TIMESTAMP
         WHERE id = ? AND (wa_message_id IS NULL OR wa_message_id = ?)'
    );
    $stmt->execute([$waMessageId, $outboundRowId, $waMessageId]);
}

function whatsapp_register_dedupe(PDO $pdo, string $dedupeKey, string $eventKind): bool
{
    $stmt = $pdo->prepare(
        'INSERT IGNORE INTO whatsapp_webhook_dedupe (dedupe_key, event_kind) VALUES (?, ?)'
    );
    $stmt->execute([$dedupeKey, substr($eventKind, 0, 32)]);

    return $stmt->rowCount() > 0;
}

function whatsapp_sanitize_wa_id(?string $id): ?string
{
    if ($id === null || $id === '') {
        return null;
    }
    $digits = preg_replace('/\D+/', '', $id);

    return $digits !== '' ? $digits : null;
}

function whatsapp_extract_message_text(array $message): ?string
{
    $type = (string) ($message['type'] ?? 'unknown');
    if ($type === 'text' && isset($message['text']['body'])) {
        return (string) $message['text']['body'];
    }
    if ($type === 'button' && isset($message['button']['text'])) {
        return (string) $message['button']['text'];
    }
    if ($type === 'interactive') {
        $interactive = $message['interactive'] ?? [];
        if (isset($interactive['button_reply']['title'])) {
            return (string) $interactive['button_reply']['title'];
        }
        if (isset($interactive['list_reply']['title'])) {
            return (string) $interactive['list_reply']['title'];
        }
    }

    return null;
}

/**
 * @return array<string, mixed>|null Media metadata without binary content.
 */
function whatsapp_extract_media_meta(array $message): ?array
{
    $type = (string) ($message['type'] ?? '');
    $mediaTypes = ['image', 'video', 'audio', 'document', 'sticker', 'voice'];
    if (!in_array($type, $mediaTypes, true)) {
        return null;
    }
    $node = $message[$type] ?? null;
    if (!is_array($node)) {
        return null;
    }
    $meta = ['type' => $type];
    foreach (['id', 'mime_type', 'sha256', 'caption', 'filename'] as $key) {
        if (isset($node[$key])) {
            $meta[$key] = $node[$key];
        }
    }

    return $meta;
}

function whatsapp_process_webhook_payload(array $payload, PDO $pdo): void
{
    if (($payload['object'] ?? '') !== 'whatsapp_business_account') {
        return;
    }
    $entries = $payload['entry'] ?? [];
    if (!is_array($entries)) {
        return;
    }

    foreach ($entries as $entry) {
        if (!is_array($entry)) {
            continue;
        }
        $wabaId = isset($entry['id']) ? (string) $entry['id'] : null;
        $changes = $entry['changes'] ?? [];
        if (!is_array($changes)) {
            continue;
        }

        foreach ($changes as $change) {
            if (!is_array($change)) {
                continue;
            }
            $field = (string) ($change['field'] ?? '');
            if ($field !== 'messages') {
                continue;
            }
            $value = $change['value'] ?? [];
            if (!is_array($value)) {
                continue;
            }

            $phoneNumberId = isset($value['metadata']['phone_number_id'])
                ? (string) $value['metadata']['phone_number_id']
                : null;

            $contactNames = whatsapp_contact_name_map($value['contacts'] ?? []);
            whatsapp_process_inbound_messages($pdo, $wabaId, $phoneNumberId, $value['messages'] ?? [], $contactNames);
            whatsapp_process_status_updates($pdo, $wabaId, $phoneNumberId, $value['statuses'] ?? []);
        }
    }
}

/**
 * @param mixed $contacts
 * @return array<string, string> wa_id => display name
 */
function whatsapp_contact_name_map($contacts): array
{
    if (!is_array($contacts)) {
        return [];
    }
    $map = [];
    foreach ($contacts as $contact) {
        if (!is_array($contact)) {
            continue;
        }
        $waId = whatsapp_sanitize_wa_id($contact['wa_id'] ?? null);
        if ($waId === null) {
            continue;
        }
        $name = isset($contact['profile']['name']) ? trim((string) $contact['profile']['name']) : '';
        if ($name !== '') {
            $map[$waId] = strlen($name) > 120 ? substr($name, 0, 120) : $name;
        }
    }

    return $map;
}

/**
 * @param mixed $messages
 * @param array<string, string> $contactNames
 */
function whatsapp_process_inbound_messages(PDO $pdo, ?string $wabaId, ?string $phoneNumberId, $messages, array $contactNames = []): void
{
    if (!is_array($messages)) {
        return;
    }

    foreach ($messages as $message) {
        if (!is_array($message)) {
            continue;
        }
        $wamid = isset($message['id']) ? (string) $message['id'] : '';
        if ($wamid === '') {
            continue;
        }

        $dedupeKey = 'inbound:' . $wamid;
        if (!whatsapp_register_dedupe($pdo, $dedupeKey, 'inbound')) {
            continue;
        }

        $from = whatsapp_sanitize_wa_id($message['from'] ?? null) ?? '';
        if ($from === '') {
            continue;
        }

        $type = substr((string) ($message['type'] ?? 'unknown'), 0, 32);
        $timestamp = isset($message['timestamp']) ? (int) $message['timestamp'] : null;
        $body = whatsapp_extract_message_text($message);
        $media = whatsapp_extract_media_meta($message);
        $contactName = $contactNames[$from] ?? null;

        $stmt = $pdo->prepare(
            'INSERT INTO whatsapp_inbound_messages
             (wa_message_id, waba_id, phone_number_id, from_wa_id, contact_name, message_type, body_text, media_json, wa_timestamp, raw_message_json)
             VALUES (?,?,?,?,?,?,?,?,?,?)
             ON DUPLICATE KEY UPDATE
               message_type = VALUES(message_type),
               contact_name = COALESCE(VALUES(contact_name), contact_name),
               body_text = COALESCE(VALUES(body_text), body_text),
               media_json = COALESCE(VALUES(media_json), media_json),
               wa_timestamp = COALESCE(VALUES(wa_timestamp), wa_timestamp),
               updated_at = CURRENT_TIMESTAMP'
        );
        $stmt->execute([
            $wamid,
            $wabaId,
            $phoneNumberId,
            $from,
            $contactName,
            $type,
            $body,
            $media !== null ? json_encode($media, JSON_UNESCAPED_UNICODE) : null,
            $timestamp,
            json_encode($message, JSON_UNESCAPED_UNICODE),
        ]);
    }
}

/**
 * @param mixed $statuses
 */
function whatsapp_process_status_updates(PDO $pdo, ?string $wabaId, ?string $phoneNumberId, $statuses): void
{
    if (!is_array($statuses)) {
        return;
    }

    foreach ($statuses as $statusRow) {
        if (!is_array($statusRow)) {
            continue;
        }
        $wamid = isset($statusRow['id']) ? (string) $statusRow['id'] : '';
        $status = isset($statusRow['status']) ? substr((string) $statusRow['status'], 0, 32) : '';
        if ($wamid === '' || $status === '') {
            continue;
        }

        $timestamp = isset($statusRow['timestamp']) ? (int) $statusRow['timestamp'] : 0;
        $dedupeKey = 'status:' . $wamid . ':' . $status . ':' . $timestamp;
        if (!whatsapp_register_dedupe($pdo, $dedupeKey, 'status')) {
            continue;
        }

        $recipient = whatsapp_sanitize_wa_id($statusRow['recipient_id'] ?? null);
        $errorCode = null;
        $errorTitle = null;
        $errorMessage = null;
        $errorDetails = null;
        if (!empty($statusRow['errors']) && is_array($statusRow['errors'])) {
            $first = $statusRow['errors'][0] ?? [];
            if (is_array($first)) {
                $errorCode = isset($first['code']) ? (int) $first['code'] : null;
                $errorTitle = isset($first['title']) ? substr((string) $first['title'], 0, 255) : null;
                $errorMessage = isset($first['message']) ? (string) $first['message'] : null;
                $errorDetails = json_encode($statusRow['errors'], JSON_UNESCAPED_UNICODE);
            }
        }

        $log = $pdo->prepare(
            'INSERT IGNORE INTO whatsapp_message_status_log
             (wa_message_id, waba_id, phone_number_id, recipient_wa_id, status, error_code, error_title, error_message, error_details_json, wa_timestamp)
             VALUES (?,?,?,?,?,?,?,?,?,?)'
        );
        $log->execute([
            $wamid,
            $wabaId,
            $phoneNumberId,
            $recipient,
            $status,
            $errorCode,
            $errorTitle,
            $errorMessage,
            $errorDetails,
            $timestamp ?: null,
        ]);

        $outbound = $pdo->prepare(
            'UPDATE whatsapp_outbound_messages
             SET delivery_status = ?,
                 last_error_code = ?,
                 last_error_message = ?,
                 updated_at = CURRENT_TIMESTAMP
             WHERE wa_message_id = ?'
        );
        $outbound->execute([
            $status,
            $errorCode,
            $errorMessage ?: $errorTitle,
            $wamid,
        ]);
    }
}

function whatsapp_handle_webhook_post(string $rawBody): void
{
    if (!whatsapp_app_secret_configured()) {
        error_log('WhatsApp webhook: META_APP_SECRET is not configured.');
        http_response_code(500);
        exit;
    }

    $sig = null;
    if (isset($_SERVER['HTTP_X_HUB_SIGNATURE_256'])) {
        $sig = (string) $_SERVER['HTTP_X_HUB_SIGNATURE_256'];
    }

    if (!whatsapp_verify_signature($rawBody, $sig)) {
        http_response_code(403);
        exit;
    }

    if ($rawBody === '') {
        http_response_code(400);
        exit;
    }

    $payload = json_decode($rawBody, true);
    if (!is_array($payload)) {
        http_response_code(400);
        exit;
    }

    try {
        $pdo = db();
        $pdo->beginTransaction();
        whatsapp_process_webhook_payload($payload, $pdo);
        $pdo->commit();
    } catch (Throwable $e) {
        if (isset($pdo) && $pdo instanceof PDO && $pdo->inTransaction()) {
            $pdo->rollBack();
        }
        error_log('WhatsApp webhook processing failed: ' . $e->getMessage());
        http_response_code(500);
        exit;
    }

    http_response_code(200);
    header('Content-Type: text/plain; charset=UTF-8');
    echo 'EVENT_RECEIVED';
}
