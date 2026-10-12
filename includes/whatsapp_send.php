<?php
/**
 * Meta WhatsApp Cloud API — outbound messages (Graph API).
 */
require_once __DIR__ . '/whatsapp_cloud.php';

/**
 * @return array{ok:bool, wa_message_id?:string, outbound_id?:int, error?:string, http_code?:int}
 */
function whatsapp_send_graph(array $payload): array
{
    $cfg = whatsapp_cloud_config();
    $token = trim($cfg['access_token']);
    $phoneId = trim($cfg['phone_number_id']);
    if ($token === '' || $phoneId === '') {
        return ['ok' => false, 'error' => 'WhatsApp access token or phone number ID is not configured.'];
    }

    if (!function_exists('curl_init')) {
        return ['ok' => false, 'error' => 'PHP cURL extension is not enabled on the server.'];
    }

    $url = sprintf(
        'https://graph.facebook.com/%s/%s/messages',
        rawurlencode($cfg['api_version']),
        rawurlencode($phoneId)
    );

    $json = json_encode($payload, JSON_UNESCAPED_UNICODE);
    if ($json === false) {
        return ['ok' => false, 'error' => 'Could not encode message payload.'];
    }

    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_POST => true,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_HTTPHEADER => [
            'Authorization: Bearer ' . $token,
            'Content-Type: application/json',
        ],
        CURLOPT_POSTFIELDS => $json,
        CURLOPT_TIMEOUT => 30,
    ]);
    $body = curl_exec($ch);
    $httpCode = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $curlErr = curl_error($ch);
    curl_close($ch);

    if ($body === false) {
        return ['ok' => false, 'error' => 'Network error: ' . $curlErr, 'http_code' => $httpCode];
    }

    $data = json_decode($body, true);
    if ($httpCode < 200 || $httpCode >= 300) {
        $msg = is_array($data) ? ($data['error']['message'] ?? $body) : $body;

        return ['ok' => false, 'error' => (string) $msg, 'http_code' => $httpCode];
    }

    $wamid = is_array($data) ? (string) ($data['messages'][0]['id'] ?? '') : '';
    if ($wamid === '') {
        return ['ok' => false, 'error' => 'Send accepted but no message id in response.', 'http_code' => $httpCode];
    }

    return ['ok' => true, 'wa_message_id' => $wamid, 'http_code' => $httpCode];
}

function whatsapp_normalize_recipient(string $to): string
{
    return preg_replace('/\D+/', '', $to) ?: $to;
}

/** Customer service window: free-form reply within 24h of last inbound from this user. */
function whatsapp_service_window_open(PDO $pdo, string $recipientWaId): bool
{
    $recipientWaId = whatsapp_normalize_recipient($recipientWaId);
    $stmt = $pdo->prepare(
        'SELECT COALESCE(wa_timestamp, UNIX_TIMESTAMP(created_at)) AS ts
         FROM whatsapp_inbound_messages
         WHERE from_wa_id = ?
         ORDER BY id DESC LIMIT 1'
    );
    $stmt->execute([$recipientWaId]);
    $ts = $stmt->fetchColumn();
    if ($ts === false) {
        return false;
    }

    return (time() - (int) $ts) < 86400;
}

/**
 * @return array{ok:bool, wa_message_id?:string, outbound_id?:int, error?:string}
 */
function whatsapp_send_text_message(string $to, string $text, bool $allowOutsideServiceWindow = false): array
{
    $to = whatsapp_normalize_recipient($to);
    $text = trim($text);
    if ($to === '' || $text === '') {
        return ['ok' => false, 'error' => 'Recipient and message text are required.'];
    }

    if (!$allowOutsideServiceWindow && !whatsapp_service_window_open(db(), $to)) {
        return [
            'ok' => false,
            'error' => '24-hour service window closed. Send an approved template instead, or wait for the customer to message again.',
        ];
    }

    $payload = [
        'messaging_product' => 'whatsapp',
        'recipient_type' => 'individual',
        'to' => $to,
        'type' => 'text',
        'text' => ['preview_url' => false, 'body' => $text],
    ];

    $outId = whatsapp_record_outbound_message($to, 'text', null, null, null, ['text' => $text]);
    $result = whatsapp_send_graph($payload);
    if (!$result['ok']) {
        db()->prepare(
            'UPDATE whatsapp_outbound_messages SET delivery_status = ?, last_error_message = ?, updated_at = CURRENT_TIMESTAMP WHERE id = ?'
        )->execute(['failed', substr($result['error'] ?? 'Send failed', 0, 500), $outId]);

        return $result;
    }

    whatsapp_attach_outbound_message_id($outId, $result['wa_message_id']);
    $result['outbound_id'] = $outId;

    return $result;
}

/**
 * @return array{ok:bool, wa_message_id?:string, outbound_id?:int, error?:string}
 */
function whatsapp_send_template_message(
    string $to,
    string $templateName,
    string $languageCode = 'en_US',
    array $components = []
): array {
    $to = whatsapp_normalize_recipient($to);
    $templateName = preg_replace('/[^a-z0-9_]/i', '', $templateName);
    if ($to === '' || $templateName === '') {
        return ['ok' => false, 'error' => 'Recipient and template name are required.'];
    }

    $template = [
        'name' => $templateName,
        'language' => ['code' => $languageCode],
    ];
    if ($components !== []) {
        $template['components'] = $components;
    }

    $payload = [
        'messaging_product' => 'whatsapp',
        'to' => $to,
        'type' => 'template',
        'template' => $template,
    ];

    $outId = whatsapp_record_outbound_message($to, 'template', null, null, $templateName, [
        'template' => $templateName,
        'language' => $languageCode,
    ]);
    $result = whatsapp_send_graph($payload);
    if (!$result['ok']) {
        db()->prepare(
            'UPDATE whatsapp_outbound_messages SET delivery_status = ?, last_error_message = ?, updated_at = CURRENT_TIMESTAMP WHERE id = ?'
        )->execute(['failed', substr($result['error'] ?? 'Send failed', 0, 500), $outId]);

        return $result;
    }

    whatsapp_attach_outbound_message_id($outId, $result['wa_message_id']);
    $result['outbound_id'] = $outId;

    return $result;
}

function whatsapp_default_template_name(): string
{
    $cfg = require __DIR__ . '/config.php';
    $name = (string) ($cfg['whatsapp']['default_template'] ?? getenv('WHATSAPP_DEFAULT_TEMPLATE') ?: 'hello_world');

    return preg_replace('/[^a-z0-9_]/i', '', $name) ?: 'hello_world';
}

function whatsapp_default_template_language(): string
{
    $cfg = require __DIR__ . '/config.php';
    $lang = (string) ($cfg['whatsapp']['default_template_language'] ?? getenv('WHATSAPP_DEFAULT_TEMPLATE_LANG') ?: 'en_US');

    return preg_replace('/[^a-z0-9_]/i', '', $lang) ?: 'en_US';
}

function whatsapp_waba_id(): string
{
    $cfg = require __DIR__ . '/config.php';
    $id = trim((string) ($cfg['whatsapp']['waba_id'] ?? ''));
    if ($id !== '') {
        return $id;
    }
    try {
        $row = db()->query(
            'SELECT waba_id FROM whatsapp_inbound_messages WHERE waba_id IS NOT NULL AND waba_id != \'\' ORDER BY id DESC LIMIT 1'
        )->fetchColumn();

        return $row ? (string) $row : '';
    } catch (Throwable $e) {
        return '';
    }
}

/**
 * @return array{ok:bool, data?:mixed, error?:string}
 */
function whatsapp_graph_get(string $path, array $query = []): array
{
    $cfg = whatsapp_cloud_config();
    $token = trim($cfg['access_token']);
    if ($token === '') {
        return ['ok' => false, 'error' => 'Access token not configured.'];
    }
    if (!function_exists('curl_init')) {
        return ['ok' => false, 'error' => 'PHP cURL extension is not enabled on the server.'];
    }
    $qs = $query !== [] ? ('?' . http_build_query($query)) : '';
    $url = 'https://graph.facebook.com/' . rawurlencode($cfg['api_version']) . '/' . ltrim($path, '/') . $qs;
    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_HTTPHEADER => ['Authorization: Bearer ' . $token],
        CURLOPT_TIMEOUT => 30,
    ]);
    $body = curl_exec($ch);
    $code = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    $data = is_string($body) ? json_decode($body, true) : null;
    if ($code < 200 || $code >= 300) {
        return ['ok' => false, 'error' => is_array($data) ? (string) ($data['error']['message'] ?? $body) : (string) $body];
    }

    return ['ok' => true, 'data' => $data];
}

/**
 * @return array{
 *   ok:bool,
 *   templates:list<array{name:string,language:string,status:string,category:string}>,
 *   error?:string,
 *   waba_id?:string
 * }
 */
function whatsapp_fetch_message_templates_result(): array
{
    $waba = whatsapp_waba_id();
    if ($waba === '') {
        return [
            'ok' => false,
            'templates' => [],
            'error' => 'WhatsApp Business Account ID (waba_id) is not set. Add whatsapp.waba_id in private digital_creators_config.php (Meta → WhatsApp → API Setup → WhatsApp Business Account ID).',
            'waba_id' => '',
        ];
    }

    $token = trim(whatsapp_cloud_config()['access_token']);
    if ($token === '') {
        return [
            'ok' => false,
            'templates' => [],
            'error' => 'Access token is missing in config.',
            'waba_id' => $waba,
        ];
    }

    $res = whatsapp_graph_get($waba . '/message_templates', ['limit' => 100]);
    if (!$res['ok']) {
        $msg = (string) ($res['error'] ?? 'Graph API request failed.');
        if (stripos($msg, 'permission') !== false || stripos($msg, '(#200)') !== false) {
            $msg .= ' Generate a System User token with whatsapp_business_management (and whatsapp_business_messaging).';
        }
        if (stripos($msg, 'does not exist') !== false || stripos($msg, '(#100)') !== false) {
            $msg .= ' Check that waba_id matches the WABA linked to this app and phone number.';
        }

        return ['ok' => false, 'templates' => [], 'error' => $msg, 'waba_id' => $waba];
    }

    if (!is_array($res['data']['data'] ?? null)) {
        return [
            'ok' => false,
            'templates' => [],
            'error' => 'Meta returned no template list (empty data). Create templates in WhatsApp Manager → Message templates.',
            'waba_id' => $waba,
        ];
    }

    $out = [];
    foreach ($res['data']['data'] as $tpl) {
        if (!is_array($tpl)) {
            continue;
        }
        $name = (string) ($tpl['name'] ?? '');
        if ($name === '') {
            continue;
        }
        $lang = (string) ($tpl['language'] ?? 'en_US');
        $out[] = [
            'name' => $name,
            'language' => $lang,
            'status' => (string) ($tpl['status'] ?? ''),
            'category' => (string) ($tpl['category'] ?? ''),
        ];
    }

    if ($out === []) {
        return [
            'ok' => true,
            'templates' => [],
            'error' => 'API OK but no templates found for this WABA. Add templates in Meta Business Suite / WhatsApp Manager.',
            'waba_id' => $waba,
        ];
    }

    return ['ok' => true, 'templates' => $out, 'waba_id' => $waba];
}

/**
 * @return list<array{name:string,language:string,status:string,category:string}>
 */
function whatsapp_fetch_message_templates(): array
{
    $result = whatsapp_fetch_message_templates_result();

    return $result['templates'];
}

/**
 * @return array{ok:bool, error?:string}
 */
function whatsapp_mark_message_read(string $waMessageId): array
{
    $waMessageId = trim($waMessageId);
    if ($waMessageId === '') {
        return ['ok' => false, 'error' => 'Message id required.'];
    }
    $result = whatsapp_send_graph([
        'messaging_product' => 'whatsapp',
        'status' => 'read',
        'message_id' => $waMessageId,
    ]);
    if (!$result['ok']) {
        return ['ok' => false, 'error' => $result['error'] ?? 'Mark read failed.'];
    }

    return ['ok' => true];
}

function whatsapp_require_service_window(string $to): ?array
{
    if (!whatsapp_service_window_open(db(), $to)) {
        return [
            'ok' => false,
            'error' => '24-hour service window closed. Use an approved template or wait for the customer to message you.',
        ];
    }

    return null;
}

/**
 * @return array{ok:bool, wa_message_id?:string, outbound_id?:int, error?:string}
 */
function whatsapp_send_image_link(string $to, string $imageUrl, ?string $caption = null): array
{
    $to = whatsapp_normalize_recipient($to);
    $imageUrl = trim($imageUrl);
    if ($to === '' || $imageUrl === '' || !filter_var($imageUrl, FILTER_VALIDATE_URL)) {
        return ['ok' => false, 'error' => 'Valid recipient and HTTPS image URL are required.'];
    }
    if ($err = whatsapp_require_service_window($to)) {
        return $err;
    }
    $image = ['link' => $imageUrl];
    if ($caption !== null && trim($caption) !== '') {
        $image['caption'] = trim($caption);
    }
    $payload = [
        'messaging_product' => 'whatsapp',
        'to' => $to,
        'type' => 'image',
        'image' => $image,
    ];
    $outId = whatsapp_record_outbound_message($to, 'image', null, null, null, ['image_url' => $imageUrl, 'caption' => $caption]);
    $result = whatsapp_send_graph($payload);
    if (!$result['ok']) {
        db()->prepare(
            'UPDATE whatsapp_outbound_messages SET delivery_status = ?, last_error_message = ?, updated_at = CURRENT_TIMESTAMP WHERE id = ?'
        )->execute(['failed', substr($result['error'] ?? 'Send failed', 0, 500), $outId]);

        return $result;
    }
    whatsapp_attach_outbound_message_id($outId, $result['wa_message_id']);
    $result['outbound_id'] = $outId;

    return $result;
}

/**
 * @return array{ok:bool, wa_message_id?:string, outbound_id?:int, error?:string}
 */
function whatsapp_send_document_link(string $to, string $docUrl, ?string $filename = null, ?string $caption = null): array
{
    $to = whatsapp_normalize_recipient($to);
    $docUrl = trim($docUrl);
    if ($to === '' || $docUrl === '' || !filter_var($docUrl, FILTER_VALIDATE_URL)) {
        return ['ok' => false, 'error' => 'Valid recipient and HTTPS document URL are required.'];
    }
    if ($err = whatsapp_require_service_window($to)) {
        return $err;
    }
    $doc = ['link' => $docUrl];
    if ($filename !== null && trim($filename) !== '') {
        $doc['filename'] = trim($filename);
    }
    if ($caption !== null && trim($caption) !== '') {
        $doc['caption'] = trim($caption);
    }
    $payload = [
        'messaging_product' => 'whatsapp',
        'to' => $to,
        'type' => 'document',
        'document' => $doc,
    ];
    $outId = whatsapp_record_outbound_message($to, 'document', null, null, null, [
        'document_url' => $docUrl,
        'filename' => $filename,
        'caption' => $caption,
    ]);
    $result = whatsapp_send_graph($payload);
    if (!$result['ok']) {
        db()->prepare(
            'UPDATE whatsapp_outbound_messages SET delivery_status = ?, last_error_message = ?, updated_at = CURRENT_TIMESTAMP WHERE id = ?'
        )->execute(['failed', substr($result['error'] ?? 'Send failed', 0, 500), $outId]);

        return $result;
    }
    whatsapp_attach_outbound_message_id($outId, $result['wa_message_id']);
    $result['outbound_id'] = $outId;

    return $result;
}

/**
 * @return array{ok:bool, lead_id?:int, error?:string}
 */
function whatsapp_create_lead_from_chat(string $waId): array
{
    $waId = whatsapp_normalize_recipient($waId);
    $stmt = db()->prepare(
        'SELECT contact_name, body_text FROM whatsapp_inbound_messages WHERE from_wa_id = ? ORDER BY id DESC LIMIT 1'
    );
    $stmt->execute([$waId]);
    $row = $stmt->fetch();
    if (!$row) {
        return ['ok' => false, 'error' => 'No inbound messages for this number.'];
    }
    $name = trim((string) ($row['contact_name'] ?? ''));
    if ($name === '') {
        $name = 'WhatsApp ' . $waId;
    }
    $phone = '+' . $waId;
    $message = 'WhatsApp lead (' . $waId . "):\n" . trim((string) ($row['body_text'] ?? ''));
    db()->prepare(
        'INSERT INTO leads (name, email, phone, service, message, budget, status) VALUES (?,?,?,?,?,?,\'new\')'
    )->execute([$name, 'whatsapp+' . $waId . '@digitalcreatorss.local', $phone, 'WhatsApp', $message, '']);

    return ['ok' => true, 'lead_id' => (int) db()->lastInsertId()];
}
