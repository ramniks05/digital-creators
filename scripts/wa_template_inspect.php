<?php
/**
 * CLI: inspect template variables + Graph payload (no send).
 * Usage: php scripts/wa_template_inspect.php digital_creatorss_intro en Ramesh
 */
require_once dirname(__DIR__) . '/includes/whatsapp_send.php';

$name = preg_replace('/[^a-z0-9_]/i', '', (string) ($argv[1] ?? 'digital_creatorss_intro'));
$lang = whatsapp_normalize_template_language((string) ($argv[2] ?? 'en'));
$sample = trim((string) ($argv[3] ?? 'Ramesh'));
$values = $sample === '' ? [] : [$sample];

whatsapp_fetch_message_templates_result(true);
$preview = whatsapp_template_send_payload_preview('917903152429', $name, $lang, $values);

if (!$preview['ok']) {
    fwrite(STDERR, ($preview['error'] ?? 'Failed') . PHP_EOL);
    if (!empty($preview['meta'])) {
        fwrite(STDERR, json_encode($preview['meta'], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) . PHP_EOL);
    }
    exit(1);
}

echo json_encode($preview, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) . PHP_EOL;
