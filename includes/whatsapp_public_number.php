<?php
/**
 * Public wa.me number — prefer Meta Cloud API display phone (same line as webhook/inbox).
 */
declare(strict_types=1);

function whatsapp_public_number_cache_file(): string
{
    $candidates = [
        dirname(__DIR__, 2) . '/private/wa_display_phone.cache.json',
        dirname(__DIR__) . '/../private/wa_display_phone.cache.json',
        dirname(__DIR__) . '/private/wa_display_phone.cache.json',
    ];
    foreach ($candidates as $path) {
        $dir = dirname($path);
        if (is_dir($dir) && is_writable($dir)) {
            return $path;
        }
    }

    return $candidates[0];
}

function whatsapp_digits_only(string $phone): string
{
    return preg_replace('/\D+/', '', $phone) ?: '';
}

/**
 * Fetch display_phone_number from Graph (Cloud API phone_number_id).
 */
function whatsapp_fetch_cloud_display_phone_digits(): string
{
    if (!is_file(__DIR__ . '/whatsapp_send.php')) {
        return '';
    }
    require_once __DIR__ . '/whatsapp_send.php';
    $cfg = whatsapp_cloud_config();
    $token = trim($cfg['access_token']);
    $phoneId = trim($cfg['phone_number_id']);
    if ($token === '' || $phoneId === '') {
        return '';
    }

    $ping = whatsapp_graph_get($phoneId, ['fields' => 'display_phone_number']);
    if (!$ping['ok'] || !is_array($ping['data'] ?? null)) {
        return '';
    }
    $raw = (string) ($ping['data']['display_phone_number'] ?? '');
    $digits = whatsapp_digits_only($raw);
    if ($digits === '') {
        return '';
    }
    if (strlen($digits) === 10 && preg_match('/^[6-9]\d{9}$/', $digits)) {
        $digits = '91' . $digits;
    }

    $cache = [
        'digits' => $digits,
        'display' => $raw,
        'cached_at' => time(),
    ];
    $path = whatsapp_public_number_cache_file();
    $dir = dirname($path);
    if (!is_dir($dir)) {
        @mkdir($dir, 0750, true);
    }
    if (is_dir($dir) && is_writable($dir)) {
        @file_put_contents($path, json_encode($cache, JSON_UNESCAPED_UNICODE), LOCK_EX);
    }

    return $digits;
}

function whatsapp_read_cloud_display_phone_cache(int $maxAgeSeconds = 3600): string
{
    $path = whatsapp_public_number_cache_file();
    if (!is_readable($path)) {
        return '';
    }
    $raw = @file_get_contents($path);
    if ($raw === false || $raw === '') {
        return '';
    }
    $data = json_decode($raw, true);
    if (!is_array($data) || empty($data['digits'])) {
        return '';
    }
    $cachedAt = (int) ($data['cached_at'] ?? 0);
    if ($cachedAt > 0 && (time() - $cachedAt) > $maxAgeSeconds) {
        return '';
    }

    return whatsapp_digits_only((string) $data['digits']);
}

/**
 * Digits for wa.me links (no +). Priority: env/config override → Meta Cloud display → Admin setting → fallback.
 */
function whatsapp_public_chat_digits(): string
{
    static $resolved = null;
    if (is_string($resolved)) {
        return $resolved;
    }

    $config = require __DIR__ . '/config.php';
    $wa = $config['whatsapp'] ?? [];
    $override = trim((string) ($wa['public_number'] ?? getenv('WHATSAPP_PUBLIC_NUMBER') ?: ''));
    if ($override !== '') {
        $resolved = whatsapp_digits_only($override);

        return $resolved;
    }

    $preferCloud = !isset($wa['prefer_cloud_public_number'])
        || filter_var($wa['prefer_cloud_public_number'] ?? true, FILTER_VALIDATE_BOOLEAN);

    if ($preferCloud) {
        $fromCache = whatsapp_read_cloud_display_phone_cache();
        if ($fromCache !== '') {
            $resolved = $fromCache;

            return $resolved;
        }
        $fromGraph = whatsapp_fetch_cloud_display_phone_digits();
        if ($fromGraph !== '') {
            $resolved = $fromGraph;

            return $resolved;
        }
        $stale = whatsapp_read_cloud_display_phone_cache(86400 * 7);
        if ($stale !== '') {
            $resolved = $stale;

            return $resolved;
        }
    }

    if (!function_exists('setting')) {
        require_once __DIR__ . '/content.php';
    }
    $fromSetting = whatsapp_digits_only(setting('whatsapp', ''));
    if ($fromSetting !== '') {
        $resolved = $fromSetting;

        return $resolved;
    }

    $resolved = '918368142191';

    return $resolved;
}
