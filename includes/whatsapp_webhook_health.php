<?php
/**
 * Safe webhook config check (no secrets).
 */
declare(strict_types=1);

header('Content-Type: application/json');

$configPath = null;
$candidates = [
    dirname(__DIR__, 2) . '/private/digital_creators_config.php',
    dirname(__DIR__) . '/../private/digital_creators_config.php',
    dirname(__DIR__) . '/private/digital_creators_config.php',
    dirname(__DIR__) . '/includes/config.local.php',
];

foreach ($candidates as $path) {
    if (is_file($path) && is_readable($path)) {
        $configPath = $path;
        break;
    }
}

$full = require __DIR__ . '/config.php';
$wa = $full['whatsapp'] ?? [];
$verify = trim((string) ($wa['webhook_verify_token'] ?? ''));
$secret = trim((string) ($wa['app_secret'] ?? ''));
$waba = trim((string) ($wa['waba_id'] ?? ''));
$token = trim((string) ($wa['access_token'] ?? ''));

echo json_encode([
    'ok' => $verify !== '',
    'config_file_loaded' => $configPath !== null,
    'config_hint' => $configPath ? basename(dirname($configPath)) . '/' . basename($configPath) : null,
    'webhook_verify_token_set' => $verify !== '',
    'verify_token_length' => $verify !== '' ? strlen($verify) : 0,
    'meta_app_secret_set' => $secret !== '',
    'whatsapp_access_token_set' => $token !== '',
    'whatsapp_waba_id_set' => $waba !== '',
    'whatsapp_waba_id_length' => $waba !== '' ? strlen($waba) : 0,
    'callback_urls' => [
        'recommended' => 'https://digitalcreatorss.com/api/hook-wa',
        'health' => 'https://digitalcreatorss.com/api/wa-health',
    ],
], JSON_PRETTY_PRINT);
