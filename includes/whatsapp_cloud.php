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

/**
 * Upsert outbound row from webhook (message echo or delivery status when send was outside admin).
 */
function whatsapp_upsert_outbound_from_webhook(
    PDO $pdo,
    string $waMessageId,
    ?string $phoneNumberId,
    string $recipientWaId,
    string $messageType,
    ?string $templateName,
    array $payload,
    string $deliveryStatus
): void {
    $recipientWaId = preg_replace('/\D+/', '', $recipientWaId) ?: $recipientWaId;
    if ($recipientWaId === '' || $waMessageId === '') {
        return;
    }
    $payloadJson = json_encode($payload, JSON_UNESCAPED_UNICODE);
    if ($payloadJson === false) {
        $payloadJson = null;
    }
    $forceContent = in_array((string) ($payload['source'] ?? ''), ['message_echo', 'graph_api'], true);
    if ($forceContent) {
        $force = $pdo->prepare(
            'UPDATE whatsapp_outbound_messages
             SET phone_number_id = COALESCE(?, phone_number_id),
                 recipient_wa_id = ?,
                 message_type = ?,
                 template_name = COALESCE(?, template_name),
                 payload_json = ?,
                 delivery_status = ?,
                 updated_at = CURRENT_TIMESTAMP
             WHERE wa_message_id = ?'
        );
        $force->execute([
            $phoneNumberId ?: whatsapp_cloud_config()['phone_number_id'] ?: null,
            $recipientWaId,
            substr($messageType, 0, 32),
            $templateName !== null && $templateName !== '' ? substr($templateName, 0, 128) : null,
            $payloadJson,
            substr($deliveryStatus, 0, 32),
            $waMessageId,
        ]);
        if ($force->rowCount() > 0) {
            return;
        }
    }

    $stmt = $pdo->prepare(
        'INSERT INTO whatsapp_outbound_messages
         (wa_message_id, phone_number_id, recipient_wa_id, message_type, template_name, payload_json, delivery_status)
         VALUES (?,?,?,?,?,?,?)
         ON DUPLICATE KEY UPDATE
           phone_number_id = COALESCE(VALUES(phone_number_id), phone_number_id),
           recipient_wa_id = VALUES(recipient_wa_id),
           message_type = IF(VALUES(message_type) = \'external\' AND message_type NOT IN (\'external\', \'unknown\'), message_type, VALUES(message_type)),
           template_name = COALESCE(VALUES(template_name), template_name),
           payload_json = IF(
             JSON_UNQUOTE(JSON_EXTRACT(VALUES(payload_json), \'$.source\')) IN (\'message_echo\', \'graph_api\'),
             VALUES(payload_json),
             IF(
               JSON_UNQUOTE(JSON_EXTRACT(payload_json, \'$.source\')) = \'meta_status_webhook\'
               AND JSON_UNQUOTE(JSON_EXTRACT(VALUES(payload_json), \'$.source\')) = \'graph_api\',
               VALUES(payload_json),
               IF(
                 JSON_UNQUOTE(JSON_EXTRACT(payload_json, \'$.source\')) = \'meta_status_webhook\',
                 COALESCE(VALUES(payload_json), payload_json),
                 COALESCE(VALUES(payload_json), payload_json)
               )
             )
           ),
           delivery_status = VALUES(delivery_status),
           updated_at = CURRENT_TIMESTAMP'
    );
    $stmt->execute([
        $waMessageId,
        $phoneNumberId ?: whatsapp_cloud_config()['phone_number_id'] ?: null,
        $recipientWaId,
        substr($messageType, 0, 32),
        $templateName !== null && $templateName !== '' ? substr($templateName, 0, 128) : null,
        $payloadJson,
        substr($deliveryStatus, 0, 32),
    ]);
}

/**
 * @return array{name:string,language:string}|null
 */
function whatsapp_extract_template_from_status(array $statusRow): ?array
{
    if (!is_array($statusRow['template'] ?? null)) {
        return null;
    }
    $tpl = $statusRow['template'];
    $name = preg_replace('/[^a-z0-9_]/i', '', (string) ($tpl['name'] ?? ''));
    if ($name === '') {
        return null;
    }
    $lang = 'en_US';
    if (is_array($tpl['language'] ?? null)) {
        $lang = trim((string) ($tpl['language']['code'] ?? 'en_US'));
    }

    return ['name' => $name, 'language' => $lang !== '' ? $lang : 'en_US'];
}

function whatsapp_echo_recipient_wa_id(array $message): string
{
    $to = whatsapp_sanitize_wa_id($message['to'] ?? null);
    if ($to !== null) {
        return $to;
    }

    return whatsapp_sanitize_wa_id($message['recipient_id'] ?? null) ?? '';
}

/**
 * Meta often puts Cloud API / Business App sends in value.messages[] with a "to" field (not only message_echoes).
 */
function whatsapp_is_outbound_echo_message(array $message, ?string $phoneNumberId): bool
{
    if (isset($message['to']) && trim((string) $message['to']) !== '') {
        return true;
    }
    $from = trim((string) ($message['from'] ?? ''));
    if ($phoneNumberId !== null && $phoneNumberId !== '' && $from === $phoneNumberId) {
        return true;
    }

    return false;
}

/**
 * @param mixed $messages
 * @return array{inbound:list<array>, echoes:list<array>}
 */
function whatsapp_partition_webhook_messages($messages, ?string $phoneNumberId): array
{
    $inbound = [];
    $echoes = [];
    if (!is_array($messages)) {
        return ['inbound' => $inbound, 'echoes' => $echoes];
    }
    foreach ($messages as $message) {
        if (!is_array($message)) {
            continue;
        }
        if (whatsapp_is_outbound_echo_message($message, $phoneNumberId)) {
            $echoes[] = $message;
        } else {
            $inbound[] = $message;
        }
    }

    return ['inbound' => $inbound, 'echoes' => $echoes];
}

/**
 * @return array{name:string,language:string}|null
 */
function whatsapp_resolve_template_for_meta_status(?array $tplFromStatus): ?array
{
    if ($tplFromStatus !== null) {
        return $tplFromStatus;
    }
    if (!is_file(__DIR__ . '/whatsapp_send.php')) {
        return null;
    }
    require_once __DIR__ . '/whatsapp_send.php';
    foreach (whatsapp_meta_status_template_guess_candidates() as $candidate) {
        $def = whatsapp_lookup_template_definition($candidate['name'], $candidate['language']);
        if (is_array($def) && strtoupper((string) ($def['status'] ?? '')) === 'APPROVED') {
            return $candidate;
        }
    }

    return null;
}

function whatsapp_template_language_from_message(array $message): string
{
    if (!is_array($message['template'] ?? null)) {
        return 'en_US';
    }
    $lang = $message['template']['language'] ?? [];
    if (is_array($lang)) {
        $code = trim((string) ($lang['code'] ?? ''));

        return $code !== '' ? $code : 'en_US';
    }

    return 'en_US';
}

function whatsapp_extract_template_name_from_message(array $message): ?string
{
    if (($message['type'] ?? '') === 'template' && is_array($message['template'] ?? null)) {
        $name = trim((string) ($message['template']['name'] ?? ''));

        return $name !== '' ? $name : null;
    }

    return null;
}

/**
 * @param mixed $echoes
 */
function whatsapp_process_message_echoes(PDO $pdo, ?string $wabaId, ?string $phoneNumberId, $echoes): void
{
    if (!is_array($echoes)) {
        return;
    }

    if (is_file(__DIR__ . '/whatsapp_send.php')) {
        require_once __DIR__ . '/whatsapp_send.php';
    }

    foreach ($echoes as $message) {
        if (!is_array($message)) {
            continue;
        }
        $wamid = isset($message['id']) ? (string) $message['id'] : '';
        if ($wamid === '') {
            continue;
        }

        $dedupeKey = 'echo:' . $wamid;
        whatsapp_register_dedupe($pdo, $dedupeKey, 'echo');

        $recipient = whatsapp_echo_recipient_wa_id($message);
        if ($recipient === '') {
            continue;
        }

        $type = substr((string) ($message['type'] ?? 'unknown'), 0, 32);
        $body = whatsapp_extract_message_text($message);
        $templateName = whatsapp_extract_template_name_from_message($message);
        $templateLang = whatsapp_template_language_from_message($message);
        if (($body === null || $body === '') && $templateName !== null && function_exists('whatsapp_outbound_body_preview')) {
            $body = whatsapp_outbound_body_preview('', $templateName, $templateLang);
        }
        $payload = [
            'source' => 'message_echo',
            'text' => $body,
            'raw_type' => $type,
        ];
        if ($templateName !== null) {
            $payload['template'] = $templateName;
            $payload['template_language'] = $templateLang;
        }

        whatsapp_upsert_outbound_from_webhook(
            $pdo,
            $wamid,
            $phoneNumberId,
            $recipient,
            $templateName !== null ? 'template' : $type,
            $templateName,
            $payload,
            'sent'
        );

        if (is_file(__DIR__ . '/crm/conversations.php')) {
            require_once __DIR__ . '/crm/conversations.php';
            try {
                crm_sync_conversation_from_message(
                    $pdo,
                    crm_default_tenant_id(),
                    $recipient,
                    'outbound',
                    is_string($body) && $body !== '' ? $body : ($templateName !== null ? 'Template: ' . $templateName : 'Outbound message')
                );
            } catch (Throwable $e) {
                error_log('CRM conversation sync (echo): ' . $e->getMessage());
            }
        }
    }
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

/**
 * @return list<array{from:string,wamid:string,body:?string,type:string}>
 */
function whatsapp_process_webhook_payload(array $payload, PDO $pdo): array
{
    $newInbound = [];
    if (($payload['object'] ?? '') !== 'whatsapp_business_account') {
        return $newInbound;
    }
    $entries = $payload['entry'] ?? [];
    if (!is_array($entries)) {
        return $newInbound;
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
            if ($field !== 'messages' && $field !== 'message_echoes' && $field !== 'smb_message_echoes') {
                continue;
            }
            $value = $change['value'] ?? [];
            if (!is_array($value)) {
                continue;
            }

            $phoneNumberId = isset($value['metadata']['phone_number_id'])
                ? (string) $value['metadata']['phone_number_id']
                : null;

            $echoes = $value['message_echoes'] ?? $value['smb_message_echoes'] ?? [];
            if (($field === 'message_echoes' || $field === 'smb_message_echoes') && $echoes === [] && is_array($value['messages'] ?? null)) {
                $echoes = $value['messages'];
            }

            if ($field !== 'messages') {
                whatsapp_process_message_echoes($pdo, $wabaId, $phoneNumberId, $echoes);
                continue;
            }

            $partition = whatsapp_partition_webhook_messages($value['messages'] ?? [], $phoneNumberId);
            $echoes = array_merge($echoes, $partition['echoes']);
            whatsapp_process_message_echoes($pdo, $wabaId, $phoneNumberId, $echoes);

            $contactNames = whatsapp_contact_name_map($value['contacts'] ?? []);
            $newInbound = array_merge(
                $newInbound,
                whatsapp_process_inbound_messages($pdo, $wabaId, $phoneNumberId, $partition['inbound'], $contactNames)
            );
            whatsapp_process_status_updates($pdo, $wabaId, $phoneNumberId, $value['statuses'] ?? []);
        }
    }

    return $newInbound;
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
/**
 * @return list<array{from:string,wamid:string,body:?string,type:string}>
 */
function whatsapp_process_inbound_messages(PDO $pdo, ?string $wabaId, ?string $phoneNumberId, $messages, array $contactNames = []): array
{
    $newInbound = [];
    if (!is_array($messages)) {
        return $newInbound;
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

        if (is_file(__DIR__ . '/whatsapp_contacts.php')) {
            require_once __DIR__ . '/whatsapp_contacts.php';
            whatsapp_contact_touch_inbound($pdo, $from, $contactName);
        }

        $newInbound[] = [
            'from' => $from,
            'wamid' => $wamid,
            'body' => $body,
            'type' => $type,
            'contact_name' => $contactName,
        ];
    }

    return $newInbound;
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

        $rank = static function (string $s): int {
            return match (strtolower($s)) {
                'pending' => 0,
                'sent' => 1,
                'delivered' => 2,
                'read' => 3,
                'failed' => 99,
                default => 1,
            };
        };
        $newRank = $rank($status);
        $cur = $pdo->prepare('SELECT delivery_status FROM whatsapp_outbound_messages WHERE wa_message_id = ? LIMIT 1');
        $cur->execute([$wamid]);
        $curStatus = (string) ($cur->fetchColumn() ?: '');
        if ($curStatus !== '' && $newRank !== 99 && $rank($curStatus) > $newRank) {
            continue;
        }

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

        if ($outbound->rowCount() === 0 && $recipient !== null && $recipient !== '') {
            $tplFromStatus = whatsapp_extract_template_from_status($statusRow);
            $templateName = $tplFromStatus['name'] ?? null;
            $templateLang = $tplFromStatus['language'] ?? null;
            $payload = [
                'source' => 'meta_status_webhook',
                'text' => null,
            ];
            if ($templateName !== null) {
                $payload['template'] = $templateName;
                if ($templateLang !== null) {
                    $payload['template_language'] = $templateLang;
                }
                if (is_file(__DIR__ . '/whatsapp_send.php')) {
                    require_once __DIR__ . '/whatsapp_send.php';
                    $previewText = whatsapp_outbound_body_preview('', $templateName, $templateLang);
                    if ($previewText !== 'Outbound message (Meta Cloud API)') {
                        $payload['text'] = $previewText;
                    }
                }
            }

            whatsapp_upsert_outbound_from_webhook(
                $pdo,
                $wamid,
                $phoneNumberId,
                $recipient,
                $templateName !== null ? 'template' : 'text',
                $templateName,
                $payload,
                $status
            );
        }
    }
}

function whatsapp_handle_webhook_post(string $rawBody): void
{
    require_once __DIR__ . '/whatsapp_webhook_diagnostics.php';

    if (!whatsapp_app_secret_configured()) {
        error_log('WhatsApp webhook: META_APP_SECRET is not configured.');
        whatsapp_webhook_trace(['event' => 'post', 'result' => '500', 'reason' => 'app_secret_missing']);
        http_response_code(500);
        exit;
    }

    $sig = null;
    if (isset($_SERVER['HTTP_X_HUB_SIGNATURE_256'])) {
        $sig = (string) $_SERVER['HTTP_X_HUB_SIGNATURE_256'];
    }

    if (!whatsapp_verify_signature($rawBody, $sig)) {
        error_log('WhatsApp webhook POST: signature verification failed (check META_APP_SECRET matches Meta App → Basic).');
        whatsapp_webhook_trace([
            'event' => 'post',
            'result' => '403',
            'reason' => 'signature_failed',
            'signature_header_present' => $sig !== null && $sig !== '',
        ]);
        http_response_code(403);
        exit;
    }

    if ($rawBody === '') {
        whatsapp_webhook_trace(['event' => 'post', 'result' => '400', 'reason' => 'empty_body']);
        http_response_code(400);
        exit;
    }

    $payload = json_decode($rawBody, true);
    if (!is_array($payload)) {
        whatsapp_webhook_trace(['event' => 'post', 'result' => '400', 'reason' => 'invalid_json']);
        http_response_code(400);
        exit;
    }

    $stats = whatsapp_webhook_payload_stats($payload);

    $newInbound = [];
    $pdo = db();
    $webhookEventId = null;
    if (is_file(__DIR__ . '/crm/webhook_store.php')) {
        require_once __DIR__ . '/crm/webhook_store.php';
        $webhookEventId = crm_store_webhook_event($pdo, $rawBody);
    }
    $processError = null;
    try {
        $pdo->beginTransaction();
        $newInbound = whatsapp_process_webhook_payload($payload, $pdo);
        $pdo->commit();
    } catch (Throwable $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        $processError = $e->getMessage();
        error_log('WhatsApp webhook processing failed: ' . $processError);
        whatsapp_webhook_trace([
            'event' => 'post',
            'result' => '500',
            'reason' => 'db_or_exception',
            'exception' => substr($processError, 0, 120),
            'messages' => $stats['messages'],
            'statuses' => $stats['statuses'],
            'fields' => implode(',', $stats['fields']),
        ]);
        if (is_file(__DIR__ . '/crm/webhook_store.php')) {
            crm_mark_webhook_event_processed($pdo, $webhookEventId, $processError);
        }
        http_response_code(500);
        exit;
    }

    if (is_file(__DIR__ . '/crm/webhook_store.php')) {
        crm_mark_webhook_event_processed($pdo, $webhookEventId, null);
    }

    if ($newInbound !== [] && is_file(__DIR__ . '/crm/conversations.php')) {
        require_once __DIR__ . '/crm/conversations.php';
        foreach ($newInbound as $inRow) {
            try {
                crm_sync_conversation_from_message(
                    $pdo,
                    crm_default_tenant_id(),
                    (string) ($inRow['from'] ?? ''),
                    'inbound',
                    (string) ($inRow['body'] ?? '[message]'),
                    isset($inRow['contact_name']) ? (string) $inRow['contact_name'] : null
                );
            } catch (Throwable $e) {
                error_log('CRM conversation sync (inbound): ' . $e->getMessage());
            }
        }
    }

    whatsapp_log_event('webhook_post_ok', [
        'result' => '200',
        'messages' => $stats['messages'],
        'statuses' => $stats['statuses'],
        'echoes' => $stats['echoes'] ?? 0,
        'fields' => implode(',', $stats['fields']),
        'object' => $stats['object'],
        'phone_number_id' => $stats['phone_number_id'] ?? '',
        'new_inbound' => count($newInbound),
    ]);

    if ($newInbound !== [] && is_file(__DIR__ . '/whatsapp_automation.php')) {
        require_once __DIR__ . '/whatsapp_automation.php';
        try {
            whatsapp_automation_after_inbound($pdo, $newInbound);
        } catch (Throwable $e) {
            whatsapp_log_event('automation_error', [
                'error' => substr($e->getMessage(), 0, 120),
            ]);
        }
    }

    http_response_code(200);
    header('Content-Type: text/plain; charset=UTF-8');
    echo 'EVENT_RECEIVED';
}
