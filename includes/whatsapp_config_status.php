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
    if ($token !== '' && $phoneId !== '') {
        $ping = whatsapp_graph_get($phoneId, ['fields' => 'display_phone_number,verified_name']);
        $pingOk = $ping['ok'];
        $pingError = $pingOk ? '' : (string) ($ping['error'] ?? 'Graph API ping failed.');
    }

    return [
        'api_version' => $apiVersion,
        'access_token_set' => $token !== '',
        'access_token_masked' => whatsapp_mask_secret($token),
        'phone_number_id' => $phoneId !== '' ? $phoneId : '—',
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
