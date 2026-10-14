<?php
/**
 * Graph-compatible WhatsApp send + portal chat log.
 *
 * Use this instead of POST https://graph.facebook.com/.../messages when you need
 * the exact message text in admin WhatsApp chat (direct Graph curl does not hit our DB).
 *
 * Auth: Authorization: Bearer {WHATSAPP_MESSAGES_API_KEY} (private config / env)
 * Body: same JSON as Meta Cloud API /messages endpoint.
 */
declare(strict_types=1);

require_once dirname(__DIR__) . '/includes/whatsapp_send.php';

header('Content-Type: application/json; charset=UTF-8');

if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') {
    http_response_code(405);
    header('Allow: POST');
    echo json_encode(['error' => ['message' => 'Method not allowed', 'type' => 'OAuthException', 'code' => 405]]);
    exit;
}

$apiKey = whatsapp_messages_api_key();
if ($apiKey === '') {
    http_response_code(503);
    echo json_encode([
        'error' => [
            'message' => 'Messages API key is not configured. Set whatsapp.messages_api_key in private digital_creators_config.php or WHATSAPP_MESSAGES_API_KEY on the server.',
            'type' => 'configuration_error',
            'code' => 503,
        ],
    ]);
    exit;
}

$authHeader = (string) ($_SERVER['HTTP_AUTHORIZATION'] ?? '');
if (!preg_match('/^\s*Bearer\s+(\S+)\s*$/i', $authHeader, $authMatch)) {
    http_response_code(401);
    echo json_encode([
        'error' => [
            'message' => 'Missing or invalid Authorization header. Use: Bearer YOUR_MESSAGES_API_KEY',
            'type' => 'OAuthException',
            'code' => 401,
        ],
    ]);
    exit;
}
if (!hash_equals($apiKey, $authMatch[1])) {
    http_response_code(401);
    echo json_encode([
        'error' => [
            'message' => 'Invalid API key.',
            'type' => 'OAuthException',
            'code' => 401,
        ],
    ]);
    exit;
}

$raw = file_get_contents('php://input');
if ($raw === false || trim($raw) === '') {
    http_response_code(400);
    echo json_encode(['error' => ['message' => 'Empty request body.', 'code' => 400]]);
    exit;
}

$payload = json_decode($raw, true);
if (!is_array($payload)) {
    http_response_code(400);
    echo json_encode(['error' => ['message' => 'Invalid JSON body.', 'code' => 400]]);
    exit;
}

if (($payload['messaging_product'] ?? '') !== 'whatsapp') {
    http_response_code(400);
    echo json_encode(['error' => ['message' => 'messaging_product must be "whatsapp".', 'code' => 400]]);
    exit;
}

$to = whatsapp_normalize_recipient((string) ($payload['to'] ?? ''));
if ($to === '') {
    http_response_code(400);
    echo json_encode(['error' => ['message' => 'Field "to" is required.', 'code' => 400]]);
    exit;
}
$payload['to'] = $to;

$result = whatsapp_send_graph($payload);
if (!$result['ok']) {
    $httpCode = (int) ($result['http_code'] ?? 400);
    if ($httpCode < 400 || $httpCode >= 600) {
        $httpCode = 400;
    }
    http_response_code($httpCode);
    echo json_encode([
        'error' => [
            'message' => (string) ($result['error'] ?? 'Send failed.'),
            'code' => $result['graph_error_code'] ?? $httpCode,
            'error_subcode' => $result['graph_error_subcode'] ?? null,
            'fbtrace_id' => $result['fbtrace_id'] ?? null,
        ],
    ]);
    exit;
}

$wamid = (string) ($result['wa_message_id'] ?? '');
if ($wamid !== '') {
    try {
        whatsapp_sync_outbound_from_graph_payload($payload, $wamid);
    } catch (Throwable $e) {
        error_log('wa-messages: chat log failed: ' . $e->getMessage());
    }
}

http_response_code(200);
echo json_encode([
    'messaging_product' => 'whatsapp',
    'contacts' => [
        ['input' => (string) ($payload['to'] ?? ''), 'wa_id' => $to],
    ],
    'messages' => [
        ['id' => $wamid],
    ],
], JSON_UNESCAPED_UNICODE);
