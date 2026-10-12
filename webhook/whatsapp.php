<?php
/**
 * Meta WhatsApp Cloud API webhook endpoint.
 *
 * Public URL: https://yourdomain.com/webhook/whatsapp.php
 */
declare(strict_types=1);

require_once dirname(__DIR__) . '/includes/whatsapp_cloud.php';

$method = $_SERVER['REQUEST_METHOD'] ?? 'GET';

if ($method === 'GET') {
    whatsapp_handle_verification_request();
}

if ($method === 'POST') {
    $length = isset($_SERVER['CONTENT_LENGTH']) ? (int) $_SERVER['CONTENT_LENGTH'] : 0;
    if ($length > WHATSAPP_WEBHOOK_MAX_BYTES) {
        http_response_code(413);
        exit;
    }

    $rawBody = file_get_contents('php://input');
    if ($rawBody !== false && strlen($rawBody) > WHATSAPP_WEBHOOK_MAX_BYTES) {
        http_response_code(413);
        exit;
    }

    whatsapp_handle_webhook_post($rawBody !== false ? $rawBody : '');
}

http_response_code(405);
header('Allow: GET, POST');
