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
    $digits = preg_replace('/\D+/', '', $to) ?: $to;
    // India mobiles are often entered as 10 digits; Meta expects country code (91…).
    if (is_string($digits) && strlen($digits) === 10 && preg_match('/^[6-9]\d{9}$/', $digits)) {
        return '91' . $digits;
    }

    return $digits;
}

/**
 * Same person may appear as 8851613806 vs 918851613806 in DB — match both.
 *
 * @return list<string>
 */
function whatsapp_wa_id_variants(string $waId): array
{
    $digits = whatsapp_normalize_recipient($waId);
    if ($digits === '') {
        return [];
    }
    $variants = [$digits];
    if (str_starts_with($digits, '91') && strlen($digits) === 12) {
        $variants[] = substr($digits, 2);
    }

    return array_values(array_unique($variants));
}

function whatsapp_wa_id_in_clause(string $column, array $variants): array
{
    $variants = array_values(array_filter($variants, static fn ($v) => $v !== ''));
    if ($variants === []) {
        return ['0=1', []];
    }
    $placeholders = implode(',', array_fill(0, count($variants), '?'));

    return ["{$column} IN ({$placeholders})", $variants];
}

/** Prefer Meta-style id when we have inbound history. */
function whatsapp_resolve_chat_wa_id(PDO $pdo, string $waId): string
{
    $variants = whatsapp_wa_id_variants($waId);
    if ($variants === []) {
        return whatsapp_normalize_recipient($waId);
    }
    [$inSql, $inParams] = whatsapp_wa_id_in_clause('from_wa_id', $variants);
    $stmt = $pdo->prepare(
        "SELECT from_wa_id FROM whatsapp_inbound_messages WHERE {$inSql}
         ORDER BY COALESCE(wa_timestamp, UNIX_TIMESTAMP(created_at)) DESC LIMIT 1"
    );
    $stmt->execute($inParams);
    $found = $stmt->fetchColumn();
    if ($found !== false && $found !== '') {
        return (string) $found;
    }

    return whatsapp_normalize_recipient($waId);
}

/** Customer service window: free-form reply within 24h of last inbound from this user. */
function whatsapp_service_window_open(PDO $pdo, string $recipientWaId): bool
{
    $variants = whatsapp_wa_id_variants($recipientWaId);
    if ($variants === []) {
        return false;
    }
    [$inSql, $inParams] = whatsapp_wa_id_in_clause('from_wa_id', $variants);
    $stmt = $pdo->prepare(
        "SELECT COALESCE(wa_timestamp, UNIX_TIMESTAMP(created_at)) AS ts
         FROM whatsapp_inbound_messages
         WHERE {$inSql}
         ORDER BY id DESC LIMIT 1"
    );
    $stmt->execute($inParams);
    $ts = $stmt->fetchColumn();
    if ($ts === false) {
        return false;
    }

    return (time() - (int) $ts) < 86400;
}

function whatsapp_chat_has_inbound(PDO $pdo, string $waId): bool
{
    $variants = whatsapp_wa_id_variants($waId);
    if ($variants === []) {
        return false;
    }
    [$inSql, $inParams] = whatsapp_wa_id_in_clause('from_wa_id', $variants);
    $stmt = $pdo->prepare("SELECT 1 FROM whatsapp_inbound_messages WHERE {$inSql} LIMIT 1");
    $stmt->execute($inParams);

    return (bool) $stmt->fetchColumn();
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

function whatsapp_normalize_template_language(string $languageCode): string
{
    $languageCode = trim($languageCode);
    if (preg_match('/^[a-z]{2}(_[A-Z]{2})?$/', $languageCode)) {
        return $languageCode;
    }

    return preg_replace('/[^a-zA-Z_]/', '', $languageCode) ?: 'en_US';
}

/**
 * @param mixed $components Meta template definition components[] from message_templates API.
 */
function whatsapp_template_variable_count_in_text(string $text): int
{
    if (!preg_match_all('/\{\{(\d+)\}\}/', $text, $matches)) {
        return 0;
    }
    $max = 0;
    foreach ($matches[1] as $num) {
        $max = max($max, (int) $num);
    }

    return $max;
}

/**
 * @param mixed $components
 * @return list<array{section:string,label:string}>
 */
function whatsapp_template_param_slots($components): array
{
    if (!is_array($components)) {
        return [];
    }
    $slots = [];
    foreach ($components as $comp) {
        if (!is_array($comp)) {
            continue;
        }
        $type = strtoupper((string) ($comp['type'] ?? ''));
        if ($type === 'HEADER' && strtoupper((string) ($comp['format'] ?? '')) === 'TEXT') {
            $n = whatsapp_template_variable_count_in_text((string) ($comp['text'] ?? ''));
            for ($i = 1; $i <= $n; $i++) {
                $slots[] = ['section' => 'header', 'label' => 'Header {{' . $i . '}}'];
            }
        }
        if ($type === 'BODY') {
            $n = whatsapp_template_variable_count_in_text((string) ($comp['text'] ?? ''));
            for ($i = 1; $i <= $n; $i++) {
                $slots[] = ['section' => 'body', 'label' => 'Body {{' . $i . '}}'];
            }
        }
    }

    return $slots;
}

/**
 * @param mixed $components Meta template definition components[] from message_templates API.
 */
function whatsapp_template_body_param_count($components): int
{
    if (!is_array($components)) {
        return 0;
    }
    $max = 0;
    foreach ($components as $comp) {
        if (!is_array($comp) || strtoupper((string) ($comp['type'] ?? '')) !== 'BODY') {
            continue;
        }
        $max = max($max, whatsapp_template_variable_count_in_text((string) ($comp['text'] ?? '')));
    }

    return $max;
}

function whatsapp_template_body_preview_text($components): string
{
    if (!is_array($components)) {
        return '';
    }
    foreach ($components as $comp) {
        if (is_array($comp) && strtoupper((string) ($comp['type'] ?? '')) === 'BODY') {
            return (string) ($comp['text'] ?? '');
        }
    }

    return '';
}

/**
 * @param list<string> $paramValues Flat list matching whatsapp_template_param_slots() order.
 * @return list<array<string, mixed>>
 */
function whatsapp_build_template_send_components($components, array $paramValues): array
{
    if (!is_array($components)) {
        return [];
    }
    $valueIndex = 0;
    $send = [];
    foreach ($components as $comp) {
        if (!is_array($comp)) {
            continue;
        }
        $type = strtoupper((string) ($comp['type'] ?? ''));
        if ($type === 'HEADER' && strtoupper((string) ($comp['format'] ?? '')) === 'TEXT') {
            $n = whatsapp_template_variable_count_in_text((string) ($comp['text'] ?? ''));
            if ($n > 0) {
                $parameters = [];
                for ($i = 0; $i < $n; $i++) {
                    $text = trim((string) ($paramValues[$valueIndex] ?? ''));
                    if ($text === '') {
                        return [];
                    }
                    $parameters[] = ['type' => 'text', 'text' => $text];
                    $valueIndex++;
                }
                $send[] = ['type' => 'header', 'parameters' => $parameters];
            }
        }
        if ($type === 'BODY') {
            $n = whatsapp_template_variable_count_in_text((string) ($comp['text'] ?? ''));
            if ($n > 0) {
                $parameters = [];
                for ($i = 0; $i < $n; $i++) {
                    $text = trim((string) ($paramValues[$valueIndex] ?? ''));
                    if ($text === '') {
                        return [];
                    }
                    $parameters[] = ['type' => 'text', 'text' => $text];
                    $valueIndex++;
                }
                $send[] = ['type' => 'body', 'parameters' => $parameters];
            }
        }
    }

    return $send;
}

/**
 * @return array<string, mixed>|null
 */
function whatsapp_lookup_template_definition(string $templateName, string $languageCode): ?array
{
    $templateName = preg_replace('/[^a-z0-9_]/i', '', $templateName);
    $languageCode = whatsapp_normalize_template_language($languageCode);
    if ($templateName === '') {
        return null;
    }
    $result = whatsapp_fetch_message_templates_result();
    $pool = $result['templates'] ?? [];
    foreach ($pool as $tpl) {
        if ($tpl['name'] === $templateName && $tpl['language'] === $languageCode) {
            return $tpl;
        }
    }
    foreach ($pool as $tpl) {
        if ($tpl['name'] === $templateName) {
            return $tpl;
        }
    }

    return null;
}

/**
 * @param list<string> $bodyParamValues
 * @return array{ok:bool, wa_message_id?:string, outbound_id?:int, error?:string}
 */
function whatsapp_send_template_message(
    string $to,
    string $templateName,
    string $languageCode = 'en_US',
    array $components = [],
    array $bodyParamValues = []
): array {
    $to = whatsapp_normalize_recipient($to);
    $templateName = preg_replace('/[^a-z0-9_]/i', '', $templateName);
    if ($to === '' || $templateName === '') {
        return ['ok' => false, 'error' => 'Recipient and template name are required.'];
    }

    $languageCode = whatsapp_normalize_template_language($languageCode);
    $def = whatsapp_lookup_template_definition($templateName, $languageCode);
    if ($def !== null) {
        $languageCode = whatsapp_normalize_template_language((string) $def['language']);
    }

    $slots = $def !== null ? ($def['param_slots'] ?? whatsapp_template_param_slots($def['components'] ?? [])) : [];
    $needed = count($slots);

    if ($components === [] && $needed > 0) {
        $components = whatsapp_build_template_send_components($def['components'] ?? [], $bodyParamValues);
        if ($components === []) {
            return [
                'ok' => false,
                'error' => "Template \"{$templateName}\" requires {$needed} variable(s). Fill in all fields below the template dropdown.",
            ];
        }
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
        'body_params' => $bodyParamValues,
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

/**
 * WABA linked to your configured phone number (best source for template list).
 */
function whatsapp_waba_id_from_phone_number(): string
{
    $phoneId = trim(whatsapp_cloud_config()['phone_number_id']);
    if ($phoneId === '') {
        return '';
    }
    $res = whatsapp_graph_get($phoneId, ['fields' => 'whatsapp_business_account']);
    if (!$res['ok'] || !is_array($res['data']['whatsapp_business_account'] ?? null)) {
        return '';
    }
    $id = trim((string) ($res['data']['whatsapp_business_account']['id'] ?? ''));

    return preg_replace('/\D+/', '', $id) ?: $id;
}

/**
 * Resolve WhatsApp Business Account ID for Graph API (templates, etc.).
 */
function whatsapp_resolve_waba_id(): string
{
    $cfg = require __DIR__ . '/config.php';
    $configured = trim((string) ($cfg['whatsapp']['waba_id'] ?? ''));
    $fromPhone = whatsapp_waba_id_from_phone_number();

    if ($fromPhone !== '') {
        if ($configured !== '' && $configured !== $fromPhone) {
            error_log('WhatsApp waba_id config (' . $configured . ') differs from phone-linked WABA (' . $fromPhone . '); using phone-linked WABA for API.');
        }

        return $fromPhone;
    }

    if ($configured !== '') {
        return $configured;
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

function whatsapp_waba_id(): string
{
    return whatsapp_resolve_waba_id();
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
 * @return array{ok:bool, data?:mixed, error?:string}
 */
function whatsapp_graph_get_absolute(string $absoluteUrl): array
{
    $token = trim(whatsapp_cloud_config()['access_token']);
    if ($token === '' || !function_exists('curl_init')) {
        return ['ok' => false, 'error' => 'Access token or cURL not available.'];
    }
    $ch = curl_init($absoluteUrl);
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
 * @param list<array{name:string,language:string,status:string,category:string}> $templates
 * @return list<array{name:string,language:string,status:string,category:string}>
 */
function whatsapp_filter_approved_templates(array $templates): array
{
    $approved = [];
    foreach ($templates as $tpl) {
        if (strtoupper((string) ($tpl['status'] ?? '')) === 'APPROVED') {
            $approved[] = $tpl;
        }
    }
    usort($approved, static function ($a, $b) {
        $cmp = strcmp($a['name'], $b['name']);
        if ($cmp !== 0) {
            return $cmp;
        }

        return strcmp($a['language'], $b['language']);
    });

    return $approved;
}

/**
 * @param list<array{name:string,language:string,status:string,category:string}> $approved
 * @return array{name:string,language:string}|null
 */
function whatsapp_preferred_template(array $approved): ?array
{
    if ($approved === []) {
        return null;
    }
    $wantName = whatsapp_default_template_name();
    $wantLang = whatsapp_default_template_language();
    foreach ($approved as $t) {
        if ($t['name'] === $wantName && $t['language'] === $wantLang) {
            return ['name' => $t['name'], 'language' => $t['language']];
        }
    }
    foreach ($approved as $t) {
        if ($t['name'] === $wantName) {
            return ['name' => $t['name'], 'language' => $t['language']];
        }
    }

    return ['name' => $approved[0]['name'], 'language' => $approved[0]['language']];
}

/**
 * @return array{
 *   ok:bool,
 *   templates:list<array{name:string,language:string,status:string,category:string}>,
 *   approved:list<array{name:string,language:string,status:string,category:string}>,
 *   error?:string,
 *   waba_id?:string
 * }
 */
function whatsapp_fetch_message_templates_result(bool $forceRefresh = false): array
{
    static $cached = null;
    if ($forceRefresh) {
        $cached = null;
    }
    if (is_array($cached)) {
        return $cached;
    }

    $waba = whatsapp_resolve_waba_id();
    if ($waba === '') {
        return $cached = [
            'ok' => false,
            'templates' => [],
            'approved' => [],
            'error' => 'Could not resolve WhatsApp Business Account ID. Set whatsapp.waba_id or a valid phone_number_id in private config (Meta → WhatsApp → API Setup).',
            'waba_id' => '',
        ];
    }

    $token = trim(whatsapp_cloud_config()['access_token']);
    if ($token === '') {
        return [
            'ok' => false,
            'templates' => [],
            'approved' => [],
            'error' => 'Access token is missing in config.',
            'waba_id' => $waba,
        ];
    }

    $out = [];
    $appendBatch = static function (array $batch) use (&$out): void {
        foreach ($batch as $tpl) {
            if (!is_array($tpl)) {
                continue;
            }
            $name = (string) ($tpl['name'] ?? '');
            if ($name === '') {
                continue;
            }
            $components = is_array($tpl['components'] ?? null) ? $tpl['components'] : [];
            $paramSlots = whatsapp_template_param_slots($components);
            $out[] = [
                'name' => $name,
                'language' => whatsapp_normalize_template_language((string) ($tpl['language'] ?? 'en_US')),
                'status' => (string) ($tpl['status'] ?? ''),
                'category' => (string) ($tpl['category'] ?? ''),
                'components' => $components,
                'param_slots' => $paramSlots,
                'param_count' => count($paramSlots),
                'body_param_count' => whatsapp_template_body_param_count($components),
                'body_preview' => whatsapp_template_body_preview_text($components),
            ];
        }
    };

    $nextUrl = '';
    for ($page = 0; $page < 20; $page++) {
        if ($nextUrl !== '') {
            $res = whatsapp_graph_get_absolute($nextUrl);
        } else {
            $res = whatsapp_graph_get($waba . '/message_templates', [
                'limit' => 100,
                'fields' => 'name,status,language,category,components',
            ]);
        }
        if (!$res['ok']) {
            $msg = (string) ($res['error'] ?? 'Graph API request failed.');
            if (stripos($msg, 'permission') !== false || stripos($msg, '(#200)') !== false) {
                $msg .= ' Use a System User token with whatsapp_business_management.';
            }
            if (stripos($msg, 'does not exist') !== false || stripos($msg, '(#100)') !== false) {
                $msg .= ' waba_id must match the account tied to your phone_number_id.';
            }

            return ['ok' => false, 'templates' => [], 'approved' => [], 'error' => $msg, 'waba_id' => $waba];
        }

        $batch = $res['data']['data'] ?? null;
        if (!is_array($batch)) {
            break;
        }
        $appendBatch($batch);
        $nextUrl = (string) ($res['data']['paging']['next'] ?? '');
        if ($nextUrl === '') {
            break;
        }
    }

    $approved = whatsapp_filter_approved_templates($out);

    if ($out === []) {
        return [
            'ok' => true,
            'templates' => [],
            'approved' => [],
            'error' => 'API OK but no templates on this WABA. Create them in WhatsApp Manager → Message templates.',
            'waba_id' => $waba,
        ];
    }

    if ($approved === []) {
        return [
            'ok' => true,
            'templates' => $out,
            'approved' => [],
            'error' => count($out) . ' template(s) found but none are APPROVED yet. Wait for Meta approval or check WhatsApp Manager.',
            'waba_id' => $waba,
        ];
    }

    $cached = ['ok' => true, 'templates' => $out, 'approved' => $approved, 'waba_id' => $waba];

    return $cached;
}

/**
 * Approved templates only (for send dropdowns).
 *
 * @return list<array{name:string,language:string,status:string,category:string}>
 */
function whatsapp_fetch_message_templates(): array
{
    $result = whatsapp_fetch_message_templates_result();

    return $result['approved'];
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
