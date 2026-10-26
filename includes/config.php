<?php
/**
 * Database & app config.
 * Local XAMPP defaults can be overridden by env vars or a private config file.
 */
$appTimezone = getenv('APP_TIMEZONE') ?: 'Asia/Kolkata';
if ($appTimezone !== '') {
    date_default_timezone_set($appTimezone);
}

$config = [
    'db' => [
        'host' => getenv('DB_HOST') ?: '127.0.0.1',
        'port' => getenv('DB_PORT') ?: '3306',
        'name' => getenv('DB_NAME') ?: 'digital_creators',
        'user' => getenv('DB_USER') ?: 'root',
        'pass' => getenv('DB_PASS') ?: '',
        'charset' => 'utf8mb4',
    ],
    'app' => [
        'name' => 'Digital Creatorss',
        'timezone' => $appTimezone,
        'upload_dir' => __DIR__ . '/../assets/uploads',
        'upload_url' => 'assets/uploads',
    ],
    'public_contact' => [
        // Digits only for wa.me links (no Meta API in this project).
        'whatsapp_number' => getenv('WHATSAPP_PUBLIC_NUMBER') ?: '',
    ],
    'external_messaging' => [
        'enabled' => filter_var(getenv('EXTERNAL_MESSAGING_ENABLED') ?: '0', FILTER_VALIDATE_BOOLEAN),
        'webhook_url' => getenv('EXTERNAL_MESSAGING_WEBHOOK_URL') ?: '',
        'api_key' => getenv('EXTERNAL_MESSAGING_API_KEY') ?: '',
        'platform_url' => getenv('EXTERNAL_MESSAGING_PLATFORM_URL') ?: '',
        'timeout_seconds' => 10,
    ],
    'whatsjet' => [
        'api_base' => getenv('WHATSJET_API_BASE') ?: '',
        'vendor_uid' => getenv('WHATSJET_VENDOR_UID') ?: '',
        'api_token' => getenv('WHATSJET_API_TOKEN') ?: '',
        'template_name' => getenv('WHATSJET_TEMPLATE_NAME') ?: 'enquiry_thank_you',
        'template_language' => getenv('WHATSJET_TEMPLATE_LANGUAGE') ?: 'en',
        'contact_group' => getenv('WHATSJET_CONTACT_GROUP') ?: 'Website Form Leads',
        'timeout_seconds' => 15,
    ],
    'saas' => [
        'strict_webhook' => filter_var(getenv('SAAS_STRICT_WEBHOOK') ?: '0', FILTER_VALIDATE_BOOLEAN),
        'credentials_key' => getenv('CRM_CREDENTIALS_KEY') ?: '',
        'app_url' => getenv('SAAS_APP_URL') ?: '',
        'meta_app_id' => getenv('META_APP_ID') ?: '',
        'meta_config_id' => getenv('META_EMBEDDED_SIGNUP_CONFIG_ID') ?: '',
    ],
    'razorpay' => [
        'key_id' => getenv('RAZORPAY_KEY_ID') ?: '',
        'key_secret' => getenv('RAZORPAY_KEY_SECRET') ?: '',
        'webhook_secret' => getenv('RAZORPAY_WEBHOOK_SECRET') ?: '',
    ],
];

$candidateConfigs = [
    dirname(__DIR__, 2) . '/private/digital_creators_config.php',
    dirname(__DIR__) . '/../private/digital_creators_config.php',
    dirname(__DIR__) . '/private/digital_creators_config.php',
    __DIR__ . '/config.local.php',
];

foreach ($candidateConfigs as $localConfig) {
    if (!is_file($localConfig) || !is_readable($localConfig)) {
        continue;
    }
    $overrides = require $localConfig;
    if (is_array($overrides)) {
        $config = array_replace_recursive($config, $overrides);
        break;
    }
}

return $config;
