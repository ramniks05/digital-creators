<?php
declare(strict_types=1);

require_once __DIR__ . '/phone_utils.php';

/**
 * WhatsJet (WhatsApp template) — website form thank-you message only.
 * Uses send-template-message, never send-message.
 */
function whatsjet_config(): array
{
    $config = require __DIR__ . '/config.php';
    $wj = $config['whatsjet'] ?? [];

    $apiBase = rtrim(trim((string) ($wj['api_base'] ?? getenv('WHATSJET_API_BASE') ?: '')), '/');
    $vendorUid = trim((string) ($wj['vendor_uid'] ?? getenv('WHATSJET_VENDOR_UID') ?: ''));
    $apiToken = trim((string) ($wj['api_token'] ?? getenv('WHATSJET_API_TOKEN') ?: ''));

    return [
        'enabled' => $apiBase !== '' && $vendorUid !== '' && $apiToken !== '',
        'api_base' => $apiBase,
        'vendor_uid' => $vendorUid,
        'api_token' => $apiToken,
        'template_name' => trim((string) ($wj['template_name'] ?? 'enquiry_thank_you')),
        'template_language' => trim((string) ($wj['template_language'] ?? 'en')),
        'contact_group' => trim((string) ($wj['contact_group'] ?? 'Website Form Leads')),
        'timeout_seconds' => max(5, min(30, (int) ($wj['timeout_seconds'] ?? 15))),
    ];
}

/**
 * @param array{name?:string,email?:string,phone?:string,service?:string,message?:string,budget?:string} $data
 */
function whatsjet_send_enquiry_thank_you_template(int $leadId, array $data): bool
{
    $cfg = whatsjet_config();
    if (!$cfg['enabled']) {
        error_log('whatsjet: skipped (not configured) lead_id=' . $leadId);

        return false;
    }
    if (!function_exists('curl_init')) {
        error_log('whatsjet: cURL missing lead_id=' . $leadId);

        return false;
    }

    $phone = phone_normalize_digits((string) ($data['phone'] ?? ''));
    if ($phone === '' || !preg_match('/^\d{10,15}$/', $phone)) {
        error_log('whatsjet: invalid phone_number after normalize lead_id=' . $leadId);

        return false;
    }

    $firstName = trim((string) ($data['name'] ?? ''));
    if ($firstName === '') {
        $firstName = 'Website lead';
    }
    if (strlen($firstName) > 200) {
        $firstName = substr($firstName, 0, 200);
    }

    $email = trim((string) ($data['email'] ?? ''));

    $payload = [
        'phone_number' => $phone,
        'template_name' => $cfg['template_name'],
        'template_language' => $cfg['template_language'],
        'contact' => [
            'first_name' => $firstName,
            'email' => $email,
            'language_code' => 'en',
            'country' => 'India',
            'groups' => $cfg['contact_group'],
        ],
    ];

    $body = json_encode($payload, JSON_UNESCAPED_UNICODE);
    if ($body === false) {
        error_log('whatsjet: json_encode failed lead_id=' . $leadId);

        return false;
    }

    $url = $cfg['api_base'] . '/' . rawurlencode($cfg['vendor_uid']) . '/contact/send-template-message';

    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_POST => true,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_HTTPHEADER => [
            'Content-Type: application/json',
            'Accept: application/json',
            'Authorization: Bearer ' . $cfg['api_token'],
        ],
        CURLOPT_POSTFIELDS => $body,
        CURLOPT_TIMEOUT => $cfg['timeout_seconds'],
    ]);
    $response = curl_exec($ch);
    $errno = curl_errno($ch);
    $code = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($errno !== 0) {
        error_log('whatsjet: curl error ' . $errno . ' lead_id=' . $leadId);

        return false;
    }

    if ($code < 200 || $code >= 300) {
        error_log(
            'whatsjet: HTTP ' . $code . ' lead_id=' . $leadId . ' phone=' . $phone
            . ' body=' . substr((string) $response, 0, 400)
        );

        return false;
    }

    return true;
}
