<?php
declare(strict_types=1);

/**
 * @return array{
 *   strict_webhook:bool,
 *   credentials_key:string,
 *   app_url:string,
 *   meta_app_id:string,
 *   meta_config_id:string,
 *   razorpay_key_id:string,
 *   razorpay_key_secret:string,
 *   razorpay_webhook_secret:string
 * }
 */
function saas_config(): array
{
    $config = require dirname(__DIR__) . '/config.php';
    $saas = $config['saas'] ?? [];
    $rz = $config['razorpay'] ?? [];

    return [
        'strict_webhook' => filter_var($saas['strict_webhook'] ?? getenv('SAAS_STRICT_WEBHOOK') ?: '0', FILTER_VALIDATE_BOOLEAN),
        'credentials_key' => trim((string) ($saas['credentials_key'] ?? getenv('CRM_CREDENTIALS_KEY') ?: '')),
        'app_url' => rtrim(trim((string) ($saas['app_url'] ?? getenv('SAAS_APP_URL') ?: '')), '/'),
        'meta_app_id' => trim((string) ($saas['meta_app_id'] ?? getenv('META_APP_ID') ?: '')),
        'meta_config_id' => trim((string) ($saas['meta_config_id'] ?? getenv('META_EMBEDDED_SIGNUP_CONFIG_ID') ?: '')),
        'razorpay_key_id' => trim((string) ($rz['key_id'] ?? getenv('RAZORPAY_KEY_ID') ?: '')),
        'razorpay_key_secret' => trim((string) ($rz['key_secret'] ?? getenv('RAZORPAY_KEY_SECRET') ?: '')),
        'razorpay_webhook_secret' => trim((string) ($rz['webhook_secret'] ?? getenv('RAZORPAY_WEBHOOK_SECRET') ?: '')),
    ];
}

function saas_razorpay_configured(): bool
{
    $c = saas_config();

    return $c['razorpay_key_id'] !== '' && $c['razorpay_key_secret'] !== '';
}
