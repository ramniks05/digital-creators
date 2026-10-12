<?php
/**
 * Secure inbound media proxy (admin session required).
 */
require_once dirname(__DIR__) . '/includes/auth.php';
require_admin();
require_once dirname(__DIR__) . '/includes/whatsapp_media.php';

$inboundId = (int) ($_GET['inbound_id'] ?? 0);
if ($inboundId <= 0) {
    http_response_code(400);
    exit('Bad request');
}

$row = db()->prepare(
    'SELECT media_json, message_type FROM whatsapp_inbound_messages WHERE id = ? LIMIT 1'
);
$row->execute([$inboundId]);
$message = $row->fetch(PDO::FETCH_ASSOC);
if (!$message) {
    http_response_code(404);
    exit('Not found');
}

$media = json_decode((string) ($message['media_json'] ?? ''), true);
$mediaId = is_array($media) ? (string) ($media['id'] ?? '') : '';
if ($mediaId === '') {
    http_response_code(404);
    exit('No media');
}

$result = whatsapp_fetch_media_bytes($mediaId);
if (!$result['ok']) {
    http_response_code(502);
    exit('Media unavailable');
}

header('Content-Type: ' . ($result['mime'] ?? 'application/octet-stream'));
header('Cache-Control: private, max-age=300');
echo $result['bytes'];
