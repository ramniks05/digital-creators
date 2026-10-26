<?php
declare(strict_types=1);

require_once __DIR__ . '/phone_utils.php';

/**
 * Public wa.me / tel digits only (no Meta Graph API).
 */
function public_whatsapp_chat_digits(): string
{
    static $resolved = null;
    if (is_string($resolved)) {
        return $resolved;
    }

    $config = require __DIR__ . '/config.php';
    $pub = $config['public_contact'] ?? [];
    $override = trim((string) ($pub['whatsapp_number'] ?? getenv('WHATSAPP_PUBLIC_NUMBER') ?: ''));
    if ($override !== '') {
        $resolved = phone_normalize_digits($override);

        return $resolved;
    }

    if (!function_exists('setting')) {
        require_once __DIR__ . '/content.php';
    }
    $fromSetting = phone_normalize_digits(setting('whatsapp', ''));
    if ($fromSetting !== '') {
        $resolved = $fromSetting;

        return $resolved;
    }

    if (!function_exists('business_whatsapp_digits_default')) {
        require_once __DIR__ . '/content.php';
    }
    $resolved = business_whatsapp_digits_default();

    return $resolved;
}
