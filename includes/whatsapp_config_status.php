<?php
/**
 * Admin-safe WhatsApp connection status (masked secrets, no token leakage).
 */
require_once __DIR__ . '/whatsapp_send.php';

function whatsapp_mask_secret(string $value, int $showEnd = 4): string
{
    $value = trim($value);
    if ($value === '') {
        return '—';
    }
    if (strlen($value) <= $showEnd + 2) {
        return str_repeat('•', strlen($value));
    }

    return str_repeat('•', max(8, strlen($value) - $showEnd)) . substr($value, -$showEnd);
}

/**
 * @return array<string, mixed>
 */
function whatsapp_connection_status(): array
{
    $cfg = require __DIR__ . '/config.php';
    $wa = $cfg['whatsapp'] ?? [];
    $token = trim((string) ($wa['access_token'] ?? ''));
    $phoneId = trim((string) ($wa['phone_number_id'] ?? ''));
    $waba = whatsapp_resolve_waba_id();
    $verify = trim((string) ($wa['webhook_verify_token'] ?? ''));
    $secret = trim((string) ($wa['app_secret'] ?? ''));
    $apiVersion = trim((string) ($wa['api_version'] ?? 'v21.0'));

    $pingOk = false;
    $pingError = '';
    $displayPhone = '';
    $verifiedName = '';
    if ($token !== '' && $phoneId !== '') {
        $ping = whatsapp_graph_get($phoneId, ['fields' => 'display_phone_number,verified_name']);
        $pingOk = $ping['ok'];
        $pingError = $pingOk ? '' : (string) ($ping['error'] ?? 'Graph API ping failed.');
        if ($pingOk && is_array($ping['data'] ?? null)) {
            $displayPhone = (string) ($ping['data']['display_phone_number'] ?? '');
            $verifiedName = (string) ($ping['data']['verified_name'] ?? '');
        }
    }

    $graphMessagesUrl = ($phoneId !== '' && $apiVersion !== '')
        ? 'https://graph.facebook.com/' . $apiVersion . '/' . $phoneId . '/messages'
        : '—';

    return [
        'api_version' => $apiVersion,
        'access_token_set' => $token !== '',
        'access_token_masked' => whatsapp_mask_secret($token),
        'phone_number_id' => $phoneId !== '' ? $phoneId : '—',
        'display_phone_number' => $displayPhone !== '' ? $displayPhone : '—',
        'verified_name' => $verifiedName !== '' ? $verifiedName : '—',
        'graph_messages_endpoint' => $graphMessagesUrl,
        'waba_id_resolved' => $waba !== '' ? $waba : '—',
        'webhook_verify_token_set' => $verify !== '',
        'webhook_verify_token_masked' => whatsapp_mask_secret($verify, 0),
        'app_secret_set' => $secret !== '',
        'app_secret_masked' => whatsapp_mask_secret($secret, 0),
        'graph_api_ping_ok' => $pingOk,
        'graph_api_ping_error' => $pingError,
        'webhook_url' => 'https://digitalcreatorss.com/api/hook-wa',
        'health_url' => 'https://digitalcreatorss.com/api/wa-health',
    ];
}

/**
 * Latest template send failure details for Meta Developer Support tickets.
 *
 * @return array<string, mixed>|null
 */
function whatsapp_last_template_graph_failure(): ?array
{
    try {
        $row = db()->query(
            "SELECT id, template_name, recipient_wa_id, delivery_status, last_error_code, last_error_message,
                    phone_number_id, payload_json, updated_at
             FROM whatsapp_outbound_messages
             WHERE message_type = 'template' AND delivery_status = 'failed'
             ORDER BY id DESC LIMIT 1"
        )->fetch(PDO::FETCH_ASSOC);
    } catch (Throwable $e) {
        return null;
    }
    if (!$row) {
        return null;
    }
    $payload = [];
    if (!empty($row['payload_json'])) {
        $decoded = json_decode((string) $row['payload_json'], true);
        if (is_array($decoded)) {
            $payload = $decoded;
        }
    }

    return [
        'updated_at' => $row['updated_at'],
        'template_name' => $row['template_name'],
        'recipient_wa_id' => $row['recipient_wa_id'],
        'last_error_code' => $row['last_error_code'],
        'last_error_message' => $row['last_error_message'],
        'phone_number_id' => $row['phone_number_id'] ?: ($payload['phone_number_id'] ?? null),
        'graph_error_code' => $payload['graph_error_code'] ?? null,
        'graph_error_subcode' => $payload['graph_error_subcode'] ?? null,
        'fbtrace_id' => $payload['fbtrace_id'] ?? null,
        'http_code' => $payload['http_code'] ?? null,
        'graph_endpoint' => $payload['graph_endpoint'] ?? null,
    ];
}
