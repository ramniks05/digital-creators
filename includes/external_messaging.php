<?php
declare(strict_types=1);

/**
 * Bridge to your independent WhatsApp / messaging platform (no Meta Cloud API in this repo).
 */
function external_messaging_config(): array
{
    $config = require __DIR__ . '/config.php';
    $ext = $config['external_messaging'] ?? [];

    return [
        'enabled' => filter_var($ext['enabled'] ?? getenv('EXTERNAL_MESSAGING_ENABLED') ?: '0', FILTER_VALIDATE_BOOLEAN),
        'webhook_url' => trim((string) ($ext['webhook_url'] ?? getenv('EXTERNAL_MESSAGING_WEBHOOK_URL') ?: '')),
        'api_key' => trim((string) ($ext['api_key'] ?? getenv('EXTERNAL_MESSAGING_API_KEY') ?: '')),
        'platform_url' => trim((string) ($ext['platform_url'] ?? getenv('EXTERNAL_MESSAGING_PLATFORM_URL') ?: '')),
        'timeout_seconds' => max(3, min(30, (int) ($ext['timeout_seconds'] ?? 10))),
    ];
}

/**
 * Notify external platform after website lead saved (same payload shape you can implement on your side).
 *
 * @param array<string, mixed> $payload
 */
function external_messaging_post_lead(int $leadId, array $payload): bool
{
    $cfg = external_messaging_config();
    if (!$cfg['enabled'] || $cfg['webhook_url'] === '') {
        error_log('external_messaging: skipped (disabled or no webhook_url) lead_id=' . $leadId);

        return false;
    }
    if (!function_exists('curl_init')) {
        error_log('external_messaging: cURL missing');

        return false;
    }

    $body = json_encode(array_merge(['event' => 'website_form_lead', 'lead_id' => $leadId], $payload), JSON_UNESCAPED_UNICODE);
    if ($body === false) {
        return false;
    }

    $headers = ['Content-Type: application/json', 'Accept: application/json'];
    if ($cfg['api_key'] !== '') {
        $headers[] = 'Authorization: Bearer ' . $cfg['api_key'];
    }

    $ch = curl_init($cfg['webhook_url']);
    curl_setopt_array($ch, [
        CURLOPT_POST => true,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_HTTPHEADER => $headers,
        CURLOPT_POSTFIELDS => $body,
        CURLOPT_TIMEOUT => $cfg['timeout_seconds'],
    ]);
    $response = curl_exec($ch);
    $code = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($code < 200 || $code >= 300) {
        error_log('external_messaging: HTTP ' . $code . ' lead_id=' . $leadId . ' body=' . substr((string) $response, 0, 200));

        return false;
    }

    return true;
}
