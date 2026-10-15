<?php
/**
 * Meta WhatsApp Cloud API — outbound messages (Graph API).
 */
require_once __DIR__ . '/whatsapp_cloud.php';

/**
 * @return array{ok:bool, wa_message_id?:string, outbound_id?:int, error?:string, http_code?:int}
 */
function whatsapp_graph_is_transient_error(int $httpCode, string $message): bool
{
    if ($httpCode === 429 || ($httpCode >= 500 && $httpCode < 600)) {
        return true;
    }

    return stripos($message, 'rate limit') !== false || stripos($message, 'temporarily unavailable') !== false;
}

/**
 * @return array{message:string,code:?int,error_subcode:?int,type:?string,fbtrace_id:?string,raw:?array}
 */
function whatsapp_parse_graph_error(mixed $data): array
{
    if (!is_array($data) || empty($data['error']) || !is_array($data['error'])) {
        return [
            'message' => is_string($data) ? $data : 'Graph API error.',
            'code' => null,
            'error_subcode' => null,
            'type' => null,
            'fbtrace_id' => null,
            'raw' => is_array($data) ? $data : null,
        ];
    }
    $err = $data['error'];
    $code = isset($err['code']) ? (int) $err['code'] : null;
    $sub = isset($err['error_subcode']) ? (int) $err['error_subcode'] : null;
    $msg = (string) ($err['message'] ?? 'Graph API error.');
    $parts = [$msg];
    if ($code !== null) {
        $parts[] = '(code ' . $code . ')';
    }
    if ($sub !== null) {
        $parts[] = '(error_subcode ' . $sub . ')';
    }
    if (!empty($err['fbtrace_id'])) {
        $parts[] = 'fbtrace_id=' . $err['fbtrace_id'];
    }

    return [
        'message' => implode(' ', $parts),
        'code' => $code,
        'error_subcode' => $sub,
        'type' => isset($err['type']) ? (string) $err['type'] : null,
        'fbtrace_id' => isset($err['fbtrace_id']) ? (string) $err['fbtrace_id'] : null,
        'raw' => $err,
    ];
}

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

    $lastError = ['ok' => false, 'error' => 'Send failed.', 'http_code' => 0];
    for ($attempt = 0; $attempt < 2; $attempt++) {
        if ($attempt > 0) {
            usleep(750000);
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
            $lastError = ['ok' => false, 'error' => 'Network error: ' . $curlErr, 'http_code' => $httpCode];
            continue;
        }

        $data = json_decode($body, true);
        if ($httpCode < 200 || $httpCode >= 300) {
            $parsed = whatsapp_parse_graph_error(is_array($data) ? $data : (string) $body);
            $lastError = [
                'ok' => false,
                'error' => $parsed['message'],
                'http_code' => $httpCode,
                'graph_error_code' => $parsed['code'],
                'graph_error_subcode' => $parsed['error_subcode'],
                'graph_error_type' => $parsed['type'],
                'fbtrace_id' => $parsed['fbtrace_id'],
                'graph_error' => $parsed['raw'],
                'phone_number_id' => $phoneId,
                'graph_endpoint' => $url,
            ];
            if ($attempt === 0 && whatsapp_graph_is_transient_error($httpCode, $parsed['message'])) {
                continue;
            }

            return $lastError;
        }

        $wamid = is_array($data) ? (string) ($data['messages'][0]['id'] ?? '') : '';
        if ($wamid === '') {
            return ['ok' => false, 'error' => 'Send accepted but no message id in response.', 'http_code' => $httpCode];
        }

        return ['ok' => true, 'wa_message_id' => $wamid, 'http_code' => $httpCode];
    }

    return $lastError;
}

/**
 * Persist outbound body when send uses Graph JSON (portal or /api/wa-messages).
 */
function whatsapp_sync_outbound_from_graph_payload(array $payload, string $waMessageId): void
{
    $to = whatsapp_normalize_recipient((string) ($payload['to'] ?? ''));
    if ($to === '' || $waMessageId === '') {
        return;
    }

    $type = substr((string) ($payload['type'] ?? 'text'), 0, 32);
    $templateName = null;
    $templateLang = null;
    $dbPayload = ['source' => 'graph_api'];

    if ($type === 'text' && is_array($payload['text'] ?? null)) {
        $dbPayload['text'] = trim((string) ($payload['text']['body'] ?? ''));
    } elseif ($type === 'template' && is_array($payload['template'] ?? null)) {
        $templateName = preg_replace('/[^a-z0-9_]/i', '', (string) ($payload['template']['name'] ?? ''));
        if ($templateName === '') {
            $templateName = null;
        }
        if (is_array($payload['template']['language'] ?? null)) {
            $templateLang = trim((string) ($payload['template']['language']['code'] ?? ''));
        }
        if ($templateName !== null) {
            $dbPayload['template'] = $templateName;
        }
        if ($templateLang !== null && $templateLang !== '') {
            $dbPayload['template_language'] = $templateLang;
        }
        $preview = whatsapp_outbound_body_preview('', $templateName, $templateLang);
        if ($preview !== 'Outbound message (Meta Cloud API)') {
            $dbPayload['text'] = $preview;
        }
    } elseif ($type === 'image' && is_array($payload['image'] ?? null)) {
        $dbPayload['image_url'] = (string) ($payload['image']['link'] ?? $payload['image']['id'] ?? '');
        $cap = trim((string) ($payload['image']['caption'] ?? ''));
        if ($cap !== '') {
            $dbPayload['text'] = $cap;
        }
    } elseif ($type === 'document' && is_array($payload['document'] ?? null)) {
        $dbPayload['document_url'] = (string) ($payload['document']['link'] ?? $payload['document']['id'] ?? '');
        $dbPayload['text'] = trim((string) ($payload['document']['filename'] ?? 'Document'));
    }

    $messageType = $templateName !== null ? 'template' : $type;
    whatsapp_upsert_outbound_from_webhook(
        db(),
        $waMessageId,
        null,
        $to,
        $messageType,
        $templateName,
        $dbPayload,
        'sent'
    );

    if (is_file(__DIR__ . '/crm/messages.php')) {
        require_once __DIR__ . '/crm/tenant.php';
        require_once __DIR__ . '/crm/messages.php';
        try {
            $preview = whatsapp_outbound_body_preview(
                (string) ($dbPayload['text'] ?? ''),
                $templateName,
                $templateLang ?? null
            );
            crm_record_message(
                db(),
                crm_default_tenant_id(),
                $to,
                'outbound',
                $waMessageId,
                $messageType,
                $preview !== 'Outbound message (Meta Cloud API)' ? $preview : null,
                null,
                'sent'
            );
        } catch (Throwable $e) {
            error_log('CRM message sync (graph send): ' . $e->getMessage());
        }
    }
}

function whatsapp_messages_api_key(): string
{
    $config = require __DIR__ . '/config.php';

    return trim((string) ($config['whatsapp']['messages_api_key'] ?? ''));
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
function whatsapp_send_text_message(
    string $to,
    string $text,
    bool $allowOutsideServiceWindow = false,
    ?string $replyToWaMessageId = null
): array {
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
    if ($replyToWaMessageId !== null && trim($replyToWaMessageId) !== '') {
        $payload['context'] = ['message_id' => trim($replyToWaMessageId)];
    }

    $outId = whatsapp_record_outbound_message($to, 'text', null, null, null, [
        'text' => $text,
        'reply_to' => $replyToWaMessageId,
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

function whatsapp_normalize_template_language(string $languageCode): string
{
    $languageCode = trim($languageCode);
    if (preg_match('/^[a-z]{2}(_[A-Z]{2})?$/', $languageCode)) {
        return $languageCode;
    }

    return preg_replace('/[^a-zA-Z_]/', '', $languageCode) ?: 'en_US';
}

/**
 * Positional placeholders {{1}}, {{2}} in template component text.
 */
function whatsapp_template_positional_var_count(string $text): int
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
 * @param array{button_index?:int,button_sub_type?:string} $extra
 * @return list<array{section:string,label:string,send_type:string,param_name?:string,button_index?:int,button_sub_type?:string}>
 */
function whatsapp_template_text_var_slots(string $text, string $sendType, array $extra = []): array
{
    $section = $sendType === 'button' ? 'button' : $sendType;
    $labelPrefix = match ($sendType) {
        'header' => 'Header ',
        'button' => 'Button URL ',
        default => 'Body ',
    };

    if (preg_match_all('/\{\{([a-z][a-z0-9_]*)\}\}/', $text, $namedMatches)) {
        $seen = [];
        $slots = [];
        foreach ($namedMatches[1] as $name) {
            if (isset($seen[$name])) {
                continue;
            }
            $seen[$name] = true;
            $slots[] = array_merge($extra, [
                'section' => $section,
                'send_type' => $sendType,
                'label' => $labelPrefix . '{{' . $name . '}}',
                'param_name' => $name,
            ]);
        }
        if ($slots !== []) {
            return $slots;
        }
    }

    $n = whatsapp_template_positional_var_count($text);
    $slots = [];
    for ($i = 1; $i <= $n; $i++) {
        $slots[] = array_merge($extra, [
            'section' => $section,
            'send_type' => $sendType,
            'label' => $labelPrefix . '{{' . $i . '}}',
        ]);
    }

    return $slots;
}

/**
 * @return list<array{section:string,label:string,send_type:string,param_name?:string,button_index?:int,button_sub_type?:string}>
 */
function whatsapp_template_slots_from_component(array $comp): array
{
    $type = strtoupper((string) ($comp['type'] ?? ''));
    if ($type === 'BUTTONS') {
        $buttons = is_array($comp['buttons'] ?? null) ? $comp['buttons'] : [];
        $slots = [];
        foreach ($buttons as $idx => $btn) {
            if (!is_array($btn) || strtoupper((string) ($btn['type'] ?? '')) !== 'URL') {
                continue;
            }
            $url = (string) ($btn['url'] ?? '');
            foreach (whatsapp_template_text_var_slots($url, 'button', [
                'button_index' => (int) $idx,
                'button_sub_type' => 'url',
            ]) as $slot) {
                $slots[] = $slot;
            }
        }

        return $slots;
    }

    if ($type !== 'HEADER' && $type !== 'BODY') {
        return [];
    }
    if ($type === 'HEADER' && strtoupper((string) ($comp['format'] ?? 'TEXT')) !== 'TEXT') {
        return [];
    }
    $sendType = strtolower($type);
    $example = is_array($comp['example'] ?? null) ? $comp['example'] : [];
    $namedKey = $type === 'HEADER' ? 'header_text_named_params' : 'body_text_named_params';
    if (!empty($example[$namedKey]) && is_array($example[$namedKey])) {
        $slots = [];
        foreach ($example[$namedKey] as $row) {
            if (!is_array($row)) {
                continue;
            }
            $name = preg_replace('/[^a-z0-9_]/', '', strtolower((string) ($row['param_name'] ?? '')));
            if ($name === '') {
                continue;
            }
            $slots[] = [
                'section' => $sendType,
                'send_type' => $sendType,
                'label' => '{{' . $name . '}}',
                'param_name' => $name,
            ];
        }
        if ($slots !== []) {
            return $slots;
        }
    }

    if ($type === 'BODY' && !empty($example['body_text']) && is_array($example['body_text'])) {
        $row = $example['body_text'][0] ?? [];
        if (is_array($row) && $row !== []) {
            $n = count($row);
            $text = (string) ($comp['text'] ?? '');
            $slots = [];
            for ($i = 1; $i <= $n; $i++) {
                $slots[] = [
                    'section' => 'body',
                    'send_type' => 'body',
                    'label' => 'Body {{' . $i . '}}',
                ];
            }
            if ($slots !== [] && whatsapp_template_positional_var_count($text) >= $n) {
                return $slots;
            }
        }
    }

    return whatsapp_template_text_var_slots((string) ($comp['text'] ?? ''), $sendType);
}

/**
 * Ordered param names from Meta example blocks (named templates).
 *
 * @param mixed $components
 * @return list<string>
 */
function whatsapp_template_named_param_names_from_components($components): array
{
    if (!is_array($components)) {
        return [];
    }
    $names = [];
    foreach ($components as $comp) {
        if (!is_array($comp)) {
            continue;
        }
        $type = strtoupper((string) ($comp['type'] ?? ''));
        $example = is_array($comp['example'] ?? null) ? $comp['example'] : [];
        $keys = $type === 'HEADER'
            ? ['header_text_named_params']
            : ($type === 'BODY' ? ['body_text_named_params'] : []);
        foreach ($keys as $key) {
            if (empty($example[$key]) || !is_array($example[$key])) {
                continue;
            }
            foreach ($example[$key] as $row) {
                if (!is_array($row)) {
                    continue;
                }
                $name = preg_replace('/[^a-z0-9_]/', '', strtolower((string) ($row['param_name'] ?? '')));
                if ($name !== '') {
                    $names[] = $name;
                }
            }
        }
    }

    return $names;
}

/**
 * Meta named templates require parameter_name on every value in the send payload.
 *
 * @param list<array<string, mixed>> $slots
 * @return list<array<string, mixed>>
 */
function whatsapp_template_apply_parameter_format(array $slots, string $parameterFormat, $components): array
{
    if ($parameterFormat !== 'named' || $slots === []) {
        return $slots;
    }
    $names = whatsapp_template_named_param_names_from_components($components);
    foreach ($slots as $i => &$slot) {
        if (!empty($slot['param_name'])) {
            continue;
        }
        if (isset($names[$i])) {
            $slot['param_name'] = $names[$i];
            $slot['label'] = '{{' . $names[$i] . '}}';
        }
    }
    unset($slot);

    return $slots;
}

/**
 * @param mixed $components
 * @return list<array{section:string,label:string,send_type:string,param_name?:string,button_index?:int,button_sub_type?:string}>
 */
function whatsapp_template_param_slots($components, string $parameterFormat = ''): array
{
    if (!is_array($components)) {
        return [];
    }
    $parameterFormat = strtolower($parameterFormat);
    $slots = [];
    foreach ($components as $comp) {
        if (!is_array($comp)) {
            continue;
        }
        foreach (whatsapp_template_slots_from_component($comp) as $slot) {
            $slots[] = $slot;
        }
    }

    return whatsapp_template_apply_parameter_format($slots, $parameterFormat, $components);
}

/**
 * @param mixed $components Meta template definition components[] from message_templates API.
 */
function whatsapp_template_body_param_count($components): int
{
    if (!is_array($components)) {
        return 0;
    }
    $n = 0;
    foreach ($components as $comp) {
        if (!is_array($comp) || strtoupper((string) ($comp['type'] ?? '')) !== 'BODY') {
            continue;
        }
        $n += count(whatsapp_template_slots_from_component($comp));
    }

    return $n;
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

/** Placeholder stored when Meta sends only delivery status (no message body in webhook). */
function whatsapp_meta_status_placeholder_text(): string
{
    return 'Message sent via Meta (API / test tool). Open the recipient WhatsApp app to view the full text.';
}

function whatsapp_is_meta_status_placeholder_text(?string $text): bool
{
    $text = trim((string) $text);

    return $text === '' || $text === whatsapp_meta_status_placeholder_text();
}

/**
 * Best-effort chat preview for outbound rows (portal, echo, or Meta status webhook).
 */
function whatsapp_outbound_body_preview(?string $bodyText, ?string $templateName, ?string $templateLanguage = null): string
{
    $bodyText = trim((string) $bodyText);
    if ($bodyText !== '' && !whatsapp_is_meta_status_placeholder_text($bodyText)) {
        return $bodyText;
    }

    $templateName = preg_replace('/[^a-z0-9_]/i', '', (string) $templateName);
    if ($templateName !== '') {
        $lang = $templateLanguage !== null && $templateLanguage !== ''
            ? whatsapp_normalize_template_language($templateLanguage)
            : whatsapp_default_template_language();
        $def = whatsapp_lookup_template_definition($templateName, $lang);
        if (is_array($def)) {
            $preview = trim((string) ($def['body_preview'] ?? ''));
            if ($preview !== '') {
                return $preview;
            }
        }

        return 'Template: ' . $templateName;
    }

    if ($bodyText !== '') {
        return $bodyText;
    }

    return 'Outbound message (Meta Cloud API)';
}

/**
 * Templates to try when Meta sends only delivery status (no body / no template name in webhook).
 *
 * @return list<array{name:string,language:string}>
 */
function whatsapp_meta_status_template_guess_candidates(): array
{
    $config = require __DIR__ . '/config.php';
    $wa = $config['whatsapp'] ?? [];
    $candidates = [];
    $pairs = [
        [whatsapp_default_template_name(), whatsapp_default_template_language()],
        [
            (string) ($wa['form_welcome_template'] ?? ''),
            (string) ($wa['form_welcome_language'] ?? 'en'),
        ],
        [
            (string) ($wa['business_enquiry']['form_welcome_template'] ?? ''),
            (string) ($wa['business_enquiry']['form_welcome_language'] ?? 'en'),
        ],
    ];
    $seen = [];
    foreach ($pairs as [$name, $lang]) {
        $name = preg_replace('/[^a-z0-9_]/i', '', (string) $name);
        if ($name === '' || isset($seen[$name])) {
            continue;
        }
        $seen[$name] = true;
        $candidates[] = [
            'name' => $name,
            'language' => $lang !== '' ? whatsapp_normalize_template_language($lang) : whatsapp_default_template_language(),
        ];
    }

    return $candidates;
}

/**
 * Preview for outbound DB rows (portal send, echo, or Meta status-only).
 */
function whatsapp_outbound_row_preview(array $row): string
{
    $p = [];
    if (!empty($row['payload_json'])) {
        $decoded = json_decode((string) $row['payload_json'], true);
        if (is_array($decoded)) {
            $p = $decoded;
        }
    }

    $templateName = (string) ($row['template_name'] ?? ($p['template'] ?? ''));
    $templateLang = (string) ($p['template_language'] ?? '');
    $storedText = isset($p['text']) ? (string) $p['text'] : '';

    $preview = whatsapp_outbound_body_preview(
        $storedText,
        $templateName !== '' ? $templateName : null,
        $templateLang !== '' ? $templateLang : null
    );

    $source = (string) ($p['source'] ?? '');
    $messageType = (string) ($row['message_type'] ?? '');
    $needsGuess = $preview === 'Outbound message (Meta Cloud API)'
        || whatsapp_is_meta_status_placeholder_text($preview);
    if (!$needsGuess || ($source !== 'meta_status_webhook' && $messageType !== 'external')) {
        return $preview;
    }

    foreach (whatsapp_meta_status_template_guess_candidates() as $candidate) {
        $try = whatsapp_outbound_body_preview('', $candidate['name'], $candidate['language']);
        if ($try !== 'Outbound message (Meta Cloud API)' && trim($try) !== '') {
            return $try;
        }
    }

    return $preview;
}

/**
 * @param list<string> $paramValues Flat list matching whatsapp_template_param_slots() order.
 * @return list<array<string, mixed>>
 */
function whatsapp_build_template_send_components($components, array $paramValues, string $parameterFormat = ''): array
{
    $parameterFormat = strtolower($parameterFormat);
    $slots = whatsapp_template_param_slots($components, $parameterFormat);
    if ($slots === []) {
        return [];
    }

    $send = [];
    $valueIndex = 0;
    $currentSection = null;
    /** @var list<array<string, mixed>> $parameters */
    $parameters = [];

    $flushHeaderBody = static function () use (&$send, &$parameters, &$currentSection): void {
        if ($currentSection !== null && $parameters !== []) {
            $send[] = ['type' => $currentSection, 'parameters' => $parameters];
        }
        $parameters = [];
        $currentSection = null;
    };

    foreach ($slots as $slot) {
        $sendType = (string) ($slot['send_type'] ?? $slot['section'] ?? 'body');
        $text = trim((string) ($paramValues[$valueIndex] ?? ''));
        if ($text === '') {
            return [];
        }
        $param = ['type' => 'text', 'text' => $text];
        if (!empty($slot['param_name'])) {
            $param['parameter_name'] = $slot['param_name'];
        } elseif ($parameterFormat === 'named') {
            return [];
        }

        if ($sendType === 'button') {
            $flushHeaderBody();
            $send[] = [
                'type' => 'button',
                'sub_type' => (string) ($slot['button_sub_type'] ?? 'url'),
                'index' => (string) (int) ($slot['button_index'] ?? 0),
                'parameters' => [$param],
            ];
            $valueIndex++;

            continue;
        }

        if ($currentSection !== null && $currentSection !== $sendType) {
            $flushHeaderBody();
        }
        $currentSection = $sendType;
        $parameters[] = $param;
        $valueIndex++;
    }
    $flushHeaderBody();

    return $send;
}

/**
 * Build the Graph API template payload (for send + admin diagnostics). Does not call Meta.
 *
 * @return array{ok:bool, payload?:array<string,mixed>, error?:string, meta?:array<string,mixed>}
 */
function whatsapp_template_send_payload_preview(
    string $to,
    string $templateName,
    string $languageCode,
    array $bodyParamValues = []
): array {
    $to = whatsapp_normalize_recipient($to);
    $templateName = preg_replace('/[^a-z0-9_]/i', '', $templateName);
    $languageCode = whatsapp_normalize_template_language($languageCode);
    if ($templateName === '') {
        return ['ok' => false, 'error' => 'Template name required.'];
    }
    $def = whatsapp_lookup_template_definition($templateName, $languageCode);
    if ($def === null) {
        return ['ok' => false, 'error' => 'Template not found on this WABA.'];
    }
    $languageCode = whatsapp_normalize_template_language((string) $def['language']);
    $parameterFormat = strtolower((string) ($def['parameter_format'] ?? ''));
    $components = $def['components'] ?? [];
    $slots = whatsapp_template_param_slots($components, $parameterFormat);
    $sendComponents = [];
    if ($slots !== []) {
        $sendComponents = whatsapp_build_template_send_components($components, $bodyParamValues, $parameterFormat);
        if ($sendComponents === []) {
            return [
                'ok' => false,
                'error' => 'Could not build template components — check variable count and named placeholders.',
                'meta' => [
                    'parameter_format' => $parameterFormat,
                    'param_slots' => $slots,
                    'values_provided' => count($bodyParamValues),
                ],
            ];
        }
    }
    $template = [
        'name' => $templateName,
        'language' => ['code' => $languageCode],
    ];
    if ($sendComponents !== []) {
        $template['components'] = $sendComponents;
    }

    return [
        'ok' => true,
        'payload' => [
            'messaging_product' => 'whatsapp',
            'to' => $to !== '' ? $to : '000000000000',
            'type' => 'template',
            'template' => $template,
        ],
        'meta' => [
            'parameter_format' => $parameterFormat,
            'language_resolved' => $languageCode,
            'param_slots' => $slots,
            'category' => (string) ($def['category'] ?? ''),
            'status' => (string) ($def['status'] ?? ''),
            'body_preview' => whatsapp_template_body_preview_text($components),
        ],
    ];
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
    $baseLang = strtolower(explode('_', $languageCode)[0]);
    foreach ($pool as $tpl) {
        if ($tpl['name'] !== $templateName) {
            continue;
        }
        $tplBase = strtolower(explode('_', (string) $tpl['language'])[0]);
        if ($tplBase === $baseLang) {
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
    if ($def === null) {
        return [
            'ok' => false,
            'error' => "Template \"{$templateName}\" ({$languageCode}) is not on this WhatsApp Business account. Open Admin → WhatsApp → Templates and confirm the name, language, and APPROVED status match the phone number in config.",
        ];
    }

    $languageCode = whatsapp_normalize_template_language((string) $def['language']);
    $parameterFormat = strtolower((string) ($def['parameter_format'] ?? ''));
    $slots = $def['param_slots'] ?? whatsapp_template_param_slots($def['components'] ?? [], $parameterFormat);
    $needed = count($slots);

    if ($components === [] && $needed > 0) {
        $components = whatsapp_build_template_send_components($def['components'] ?? [], $bodyParamValues, $parameterFormat);
        if ($components === []) {
            $hint = $parameterFormat === 'named'
                ? 'Named template: each value needs a Meta parameter name (see Templates → Inspect).'
                : 'Fill every variable field below the template dropdown.';

            return [
                'ok' => false,
                'error' => "Template \"{$templateName}\" requires {$needed} variable(s). {$hint}",
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
        'parameter_format' => $parameterFormat,
        'body_params' => $bodyParamValues,
        'param_slots' => $slots,
        'graph_payload' => $payload,
    ]);
    $result = whatsapp_send_graph($payload);
    if (!$result['ok']) {
        $err = (string) ($result['error'] ?? 'Send failed.');
        $httpCode = (int) ($result['http_code'] ?? 0);
        $graphCode = isset($result['graph_error_code']) ? (int) $result['graph_error_code'] : null;
        $graphSub = isset($result['graph_error_subcode']) ? (int) $result['graph_error_subcode'] : null;
        $phoneIdUsed = (string) ($result['phone_number_id'] ?? whatsapp_cloud_config()['phone_number_id']);
        error_log(
            'WhatsApp template send failed: template=' . $templateName
            . ' lang=' . $languageCode
            . ' phone_number_id=' . $phoneIdUsed
            . ' http=' . $httpCode
            . ' graph_code=' . ($graphCode ?? 'null')
            . ' graph_subcode=' . ($graphSub ?? 'null')
            . ' fbtrace_id=' . ($result['fbtrace_id'] ?? '')
            . ' message=' . substr($err, 0, 300)
        );
        if (stripos($err, '132000') !== false || $graphSub === 132000) {
            $err .= ' See Meta template fundamentals (named vs positional parameters): https://developers.facebook.com/documentation/business-messaging/whatsapp/templates/overview';
        }
        $result['error'] = $err;
        db()->prepare(
            'UPDATE whatsapp_outbound_messages SET delivery_status = ?, last_error_code = ?, last_error_message = ?, phone_number_id = ?, payload_json = ?, updated_at = CURRENT_TIMESTAMP WHERE id = ?'
        )->execute([
            'failed',
            $graphCode ?? $graphSub,
            substr($err, 0, 500),
            $phoneIdUsed !== '' ? $phoneIdUsed : null,
            json_encode([
                'template' => $templateName,
                'language' => $languageCode,
                'parameter_format' => $parameterFormat,
                'body_params' => $bodyParamValues,
                'graph_payload' => $payload,
                'http_code' => $httpCode,
                'graph_error_code' => $graphCode,
                'graph_error_subcode' => $graphSub,
                'graph_error_type' => $result['graph_error_type'] ?? null,
                'fbtrace_id' => $result['fbtrace_id'] ?? null,
                'graph_error_message' => $err,
                'graph_error_raw' => $result['graph_error'] ?? null,
                'phone_number_id' => $phoneIdUsed,
                'graph_endpoint' => $result['graph_endpoint'] ?? null,
            ], JSON_UNESCAPED_UNICODE),
            $outId,
        ]);

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
function whatsapp_filter_approved_templates(array $templates, ?string $category = null): array
{
    $approved = [];
    foreach ($templates as $tpl) {
        if (strtoupper((string) ($tpl['status'] ?? '')) !== 'APPROVED') {
            continue;
        }
        if ($category !== null && strtoupper((string) ($tpl['category'] ?? '')) !== strtoupper($category)) {
            continue;
        }
        $approved[] = $tpl;
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
            $paramFormat = strtolower((string) ($tpl['parameter_format'] ?? ''));
            $paramSlots = whatsapp_template_param_slots($components, $paramFormat);
            if ($paramFormat === '' && $paramSlots !== []) {
                $paramFormat = !empty($paramSlots[0]['param_name']) ? 'named' : 'positional';
                $paramSlots = whatsapp_template_param_slots($components, $paramFormat);
            }
            $out[] = [
                'name' => $name,
                'language' => whatsapp_normalize_template_language((string) ($tpl['language'] ?? 'en_US')),
                'status' => (string) ($tpl['status'] ?? ''),
                'category' => (string) ($tpl['category'] ?? ''),
                'parameter_format' => $paramFormat,
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
                'fields' => 'name,status,language,category,parameter_format,components',
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
/**
 * @return array{ok:bool, media_id?:string, error?:string}
 */
function whatsapp_upload_media_from_path(string $filePath, string $mimeType = 'application/pdf'): array
{
    $cfg = whatsapp_cloud_config();
    $token = trim($cfg['access_token']);
    $phoneId = trim($cfg['phone_number_id']);
    if ($token === '' || $phoneId === '') {
        return ['ok' => false, 'error' => 'WhatsApp is not configured.'];
    }
    if (!is_readable($filePath)) {
        return ['ok' => false, 'error' => 'File not readable.'];
    }
    if (!function_exists('curl_init')) {
        return ['ok' => false, 'error' => 'cURL not available.'];
    }

    $url = sprintf(
        'https://graph.facebook.com/%s/%s/media',
        rawurlencode($cfg['api_version']),
        rawurlencode($phoneId)
    );

    $cfile = new CURLFile($filePath, $mimeType, basename($filePath));
    $post = [
        'messaging_product' => 'whatsapp',
        'type' => $mimeType,
        'file' => $cfile,
    ];

    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_POST => true,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_HTTPHEADER => ['Authorization: Bearer ' . $token],
        CURLOPT_POSTFIELDS => $post,
        CURLOPT_TIMEOUT => 120,
    ]);
    $body = curl_exec($ch);
    $httpCode = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    $data = is_string($body) ? json_decode($body, true) : null;
    if ($httpCode < 200 || $httpCode >= 300 || !is_array($data) || empty($data['id'])) {
        $err = is_array($data) ? whatsapp_parse_graph_error($data)['message'] : 'Upload failed.';

        return ['ok' => false, 'error' => $err];
    }

    return ['ok' => true, 'media_id' => (string) $data['id']];
}

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
