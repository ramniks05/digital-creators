<?php
/**
 * Download inbound WhatsApp media via Graph API (admin-only callers).
 */
require_once __DIR__ . '/whatsapp_send.php';

/**
 * @return array{ok:bool, mime?:string, bytes?:string, error?:string}
 */
function whatsapp_fetch_media_bytes(string $mediaId): array
{
    $mediaId = preg_replace('/[^a-zA-Z0-9]/', '', $mediaId);
    if ($mediaId === '') {
        return ['ok' => false, 'error' => 'Invalid media id.'];
    }
    $meta = whatsapp_graph_get($mediaId);
    if (!$meta['ok'] || !is_array($meta['data'])) {
        return ['ok' => false, 'error' => $meta['error'] ?? 'Could not load media metadata.'];
    }
    $url = (string) ($meta['data']['url'] ?? '');
    $mime = (string) ($meta['data']['mime_type'] ?? 'application/octet-stream');
    if ($url === '') {
        return ['ok' => false, 'error' => 'Media URL missing from Meta response.'];
    }
    if (!function_exists('curl_init')) {
        return ['ok' => false, 'error' => 'cURL not available.'];
    }
    $token = trim(whatsapp_cloud_config()['access_token']);
    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_HTTPHEADER => ['Authorization: Bearer ' . $token],
        CURLOPT_TIMEOUT => 60,
        CURLOPT_FOLLOWLOCATION => true,
    ]);
    $bytes = curl_exec($ch);
    $code = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    if ($bytes === false || $code < 200 || $code >= 300) {
        return ['ok' => false, 'error' => 'Media binary download failed (HTTP ' . $code . ').'];
    }

    return ['ok' => true, 'mime' => $mime, 'bytes' => $bytes];
}
