<?php
/**
 * Hostinger production database config.
 *
 * IMPORTANT: Do NOT put this inside public_html.
 * Git deploy overwrites public_html and will delete config.local.php.
 *
 * Create this file on Hostinger at:
 *   domains/digitalcreatorss.com/private/digital_creators_config.php
 *
 * Then replace REPLACE_WITH_DATABASE_PASSWORD with your MySQL password.
 */
return [
    'db' => [
        'host' => 'localhost',
        'port' => '3306',
        'name' => 'u922228303_digitalcreator',
        'user' => 'u922228303_digitalcreator',
        'pass' => 'REPLACE_WITH_DATABASE_PASSWORD',
    ],
    // Meta WhatsApp Cloud API (keep secrets out of Git — use private config or server env vars).
    'whatsapp' => [
        'access_token' => getenv('WHATSAPP_ACCESS_TOKEN') ?: '',
        'phone_number_id' => getenv('WHATSAPP_PHONE_NUMBER_ID') ?: '',
        'webhook_verify_token' => getenv('WHATSAPP_WEBHOOK_VERIFY_TOKEN') ?: '',
        'app_secret' => getenv('META_APP_SECRET') ?: '',
        'api_version' => getenv('WHATSAPP_API_VERSION') ?: 'v21.0',
        'default_template' => 'hello_world',
        'default_template_language' => 'en_US',
        // WhatsApp Business Account ID — Meta → WhatsApp → API Setup (for template list in admin).
        'waba_id' => getenv('WHATSAPP_WABA_ID') ?: '',
    ],
];
