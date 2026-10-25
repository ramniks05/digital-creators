<?php
declare(strict_types=1);

header('Content-Type: application/json; charset=UTF-8');
http_response_code(410);
echo json_encode([
    'error' => 'whatsapp_webhook_removed',
    'message' => 'Meta WhatsApp webhook is not handled on this site. Use your external messaging platform.',
    'doc' => '/docs/EXTERNAL_MESSAGING_PROMPT.md',
], JSON_UNESCAPED_UNICODE);
