<?php
/**
 * Database & app config.
 * Local XAMPP defaults can be overridden by env vars or a local config file.
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
    'whatsapp' => [
        'access_token' => getenv('WHATSAPP_ACCESS_TOKEN') ?: '',
        'phone_number_id' => getenv('WHATSAPP_PHONE_NUMBER_ID') ?: '',
        'webhook_verify_token' => getenv('WHATSAPP_WEBHOOK_VERIFY_TOKEN') ?: '',
        'app_secret' => getenv('META_APP_SECRET') ?: '',
        'api_version' => getenv('WHATSAPP_API_VERSION') ?: 'v21.0',
        // Optional: Bearer key for POST /api/wa-messages (Graph-compatible send + portal chat log)
        'messages_api_key' => getenv('WHATSAPP_MESSAGES_API_KEY') ?: '',
        'default_template' => getenv('WHATSAPP_DEFAULT_TEMPLATE') ?: 'hello_world',
        'default_template_language' => getenv('WHATSAPP_DEFAULT_TEMPLATE_LANG') ?: 'en_US',
        'form_welcome_template' => getenv('DC_ENQUIRY_FORM_TEMPLATE') ?: 'enquiry_thank_you',
        'form_welcome_language' => getenv('DC_ENQUIRY_FORM_TEMPLATE_LANG') ?: 'en',
        'waba_id' => getenv('WHATSAPP_WABA_ID') ?: '',
        // wa.me links: use Meta Cloud display number when set (see whatsapp_public_number.php)
        'public_number' => getenv('WHATSAPP_PUBLIC_NUMBER') ?: '',
        'prefer_cloud_public_number' => filter_var(getenv('WHATSAPP_PREFER_CLOUD_NUMBER') ?: '1', FILTER_VALIDATE_BOOLEAN),
        // Automatic replies are disabled by default (enable only when ready for production).
        'automation' => [
            'enabled' => filter_var(getenv('WHATSAPP_AUTO_REPLY') ?: '0', FILTER_VALIDATE_BOOLEAN),
            'welcome_hi_enabled' => filter_var(getenv('WHATSAPP_WELCOME_HI') ?: '0', FILTER_VALIDATE_BOOLEAN),
            'welcome_hi_message' => getenv('WHATSAPP_WELCOME_MESSAGE') ?: '',
            'opt_out_confirm_enabled' => filter_var(getenv('WHATSAPP_OPT_OUT_CONFIRM') ?: '0', FILTER_VALIDATE_BOOLEAN),
            'opt_out_confirm_message' => getenv('WHATSAPP_OPT_OUT_MESSAGE') ?: '',
        ],
        'business_enquiry' => [
            'enabled' => filter_var(getenv('DC_ENQUIRY_AUTOMATION') ?: '0', FILTER_VALIDATE_BOOLEAN),
            'whatsapp_leads' => filter_var(getenv('DC_ENQUIRY_WHATSAPP_LEADS') ?: '1', FILTER_VALIDATE_BOOLEAN),
            'whatsapp_ack' => filter_var(getenv('DC_ENQUIRY_WHATSAPP_ACK') ?: '1', FILTER_VALIDATE_BOOLEAN),
            'staff_notify' => filter_var(getenv('DC_ENQUIRY_STAFF_NOTIFY') ?: '0', FILTER_VALIDATE_BOOLEAN),
            'staff_wa_ids' => getenv('DC_ENQUIRY_STAFF_WA_IDS') ?: '',
            'services_menu_enabled' => filter_var(getenv('DC_ENQUIRY_SERVICES_MENU') ?: '1', FILTER_VALIDATE_BOOLEAN),
            'form_welcome_enabled' => filter_var(getenv('DC_ENQUIRY_FORM_WELCOME') ?: '1', FILTER_VALIDATE_BOOLEAN),
            'form_welcome_template' => getenv('DC_ENQUIRY_FORM_TEMPLATE') ?: 'enquiry_thank_you',
            'form_welcome_language' => getenv('DC_ENQUIRY_FORM_TEMPLATE_LANG') ?: 'en',
            'inbound_welcome_enabled' => filter_var(getenv('DC_ENQUIRY_INBOUND_WELCOME') ?: '1', FILTER_VALIDATE_BOOLEAN),
            // Skip repeat welcome/ack if we already messaged this user recently (default 4 hours)
            'inbound_autoreply_cooldown_hours' => getenv('DC_ENQUIRY_COOLDOWN_HOURS') ?: 4,
            'whatsapp_lead_cooldown_hours' => getenv('DC_ENQUIRY_LEAD_COOLDOWN_HOURS') ?: 24,
        ],
    ],
];

/**
 * Hostinger-safe locations (checked first to last):
 * 1) domains/.../private/digital_creators_config.php  <- survives Git deploy
 * 2) public_html/private/...                          <- if created in wrong place
 * 3) includes/config.local.php                        <- wiped by Git deploy
 */
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
