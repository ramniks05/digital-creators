<?php
/**
 * Diagnose form submit WhatsApp template (no send). Run: php scripts/check_form_autoreply.php
 */
declare(strict_types=1);

$root = dirname(__DIR__);
require_once $root . '/includes/business_enquiry.php';
require_once $root . '/includes/whatsapp_config_status.php';

$cfg = business_enquiry_config();
$status = whatsapp_connection_status();

echo "=== Form auto-reply (enquiry_thank_you) ===\n";
echo 'business_enquiry.enabled:      ' . ($cfg['enabled'] ? 'YES' : 'NO (set true in private config)') . "\n";
echo 'form_welcome_enabled:          ' . ($cfg['form_welcome_enabled'] ? 'YES' : 'NO') . "\n";
echo 'form_welcome_template:         ' . $cfg['form_welcome_template'] . "\n";
echo 'form_welcome_language (cfg):   ' . $cfg['form_welcome_language'] . "\n";
echo 'Graph API ping OK:             ' . (!empty($status['graph_api_ping_ok']) ? 'YES' : 'NO') . "\n";
echo 'Display phone:                 ' . ($status['display_phone_number'] ?? '—') . "\n";
echo 'Phone number ID set:           ' . (($status['phone_number_id'] ?? '—') !== '—' ? 'YES' : 'NO') . "\n";

$resolved = business_enquiry_resolve_form_welcome_template(
    $cfg['form_welcome_template'],
    $cfg['form_welcome_language']
);
if ($resolved === null) {
    echo "\nFAIL: Template \"" . $cfg['form_welcome_template'] . "\" not found on this WABA.\n";
    echo "Fix: Meta → approved template name + language (try en_US in config).\n";
    exit(1);
}

echo 'Template resolved language:    ' . $resolved['language'] . "\n";

$sampleParams = business_enquiry_template_params_for_data(
    $cfg['form_welcome_template'],
    $resolved['language'],
    ['name' => 'Test User', 'service' => 'website-development']
);
echo 'Sample param values (' . count($sampleParams) . '): ' . implode(' | ', $sampleParams) . "\n";

require_once $root . '/includes/whatsapp_send.php';
$preview = whatsapp_template_send_payload_preview(
    '919999999999',
    $cfg['form_welcome_template'],
    $resolved['language'],
    $sampleParams
);
if (!$preview['ok']) {
    echo "\nFAIL payload build: " . ($preview['error'] ?? '') . "\n";
    exit(1);
}

echo "\nOK: Form auto-reply is configured correctly (dry run — no message sent).\n";
echo "If live submit still fails, check private/wa_webhook_trace.jsonl for form_welcome_failed.\n";
