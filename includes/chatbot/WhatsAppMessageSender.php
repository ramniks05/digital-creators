<?php
declare(strict_types=1);

final class ChatbotWhatsAppMessageSender
{
    public function __construct(private PDO $pdo, private int $tenantId)
    {
    }

    public function sendText(string $phone, string $text, ?string $idempotencyKey = null): bool
    {
        require_once dirname(__DIR__) . '/crm/OutboundJobService.php';
        require_once dirname(__DIR__) . '/whatsapp_send.php';
        $jobs = new CrmOutboundJobService($this->pdo, $this->tenantId);
        $result = $jobs->enqueueText(0, $phone, $text, $idempotencyKey);
        if (!$result['ok']) {
            $this->logSendFailure((string) ($result['error'] ?? 'enqueue failed'), $phone);

            return false;
        }

        return $jobs->processPendingJobs(1) >= 1;
    }

    /**
     * @param list<array{id:string,title:string}> $buttons max 3
     */
    public function sendButtons(string $phone, string $text, array $buttons, ?string $idempotencyKey = null): bool
    {
        require_once dirname(__DIR__) . '/whatsapp_send.php';
        $to = whatsapp_normalize_recipient($phone);
        $btnPayload = [];
        foreach (array_slice($buttons, 0, 3) as $b) {
            $btnPayload[] = [
                'type' => 'reply',
                'reply' => [
                    'id' => substr((string) $b['id'], 0, 256),
                    'title' => substr((string) $b['title'], 0, 20),
                ],
            ];
        }
        $payload = [
            'messaging_product' => 'whatsapp',
            'to' => $to,
            'type' => 'interactive',
            'interactive' => [
                'type' => 'button',
                'body' => ['text' => substr($text, 0, 1024)],
                'action' => ['buttons' => $btnPayload],
            ],
        ];

        return $this->sendGraph($phone, $payload, $idempotencyKey);
    }

    /**
     * @param list<array{id:string,title:string,description?:string}> $rows
     */
    public function sendServiceList(string $phone, string $bodyText, array $rows, ?string $idempotencyKey = null): bool
    {
        require_once dirname(__DIR__) . '/whatsapp_send.php';
        $to = whatsapp_normalize_recipient($phone);
        $listRows = [];
        foreach (array_slice($rows, 0, 10) as $row) {
            $listRows[] = [
                'id' => substr((string) $row['id'], 0, 200),
                'title' => substr((string) $row['title'], 0, 24),
                'description' => substr((string) ($row['description'] ?? ''), 0, 72),
            ];
        }
        $payload = [
            'messaging_product' => 'whatsapp',
            'to' => $to,
            'type' => 'interactive',
            'interactive' => [
                'type' => 'list',
                'body' => ['text' => substr($bodyText, 0, 1024)],
                'action' => [
                    'button' => 'View services',
                    'sections' => [
                        ['title' => 'Our services', 'rows' => $listRows],
                    ],
                ],
            ],
        ];

        return $this->sendGraph($phone, $payload, $idempotencyKey);
    }

    public function sendDocument(string $phone, string $mediaId, ?string $filename = null, ?string $caption = null): bool
    {
        require_once dirname(__DIR__) . '/whatsapp_send.php';
        $to = whatsapp_normalize_recipient($phone);
        $doc = ['id' => $mediaId];
        if ($filename !== null && $filename !== '') {
            $doc['filename'] = substr($filename, 0, 240);
        }
        if ($caption !== null && $caption !== '') {
            $doc['caption'] = substr($caption, 0, 1024);
        }
        $payload = [
            'messaging_product' => 'whatsapp',
            'to' => $to,
            'type' => 'document',
            'document' => $doc,
        ];

        return $this->sendGraph($phone, $payload, 'doc-' . $mediaId . '-' . time());
    }

    /**
     * @param array<string, mixed> $payload
     */
    private function sendGraph(string $phone, array $payload, ?string $idempotencyKey): bool
    {
        require_once dirname(__DIR__) . '/crm/OutboundJobService.php';
        require_once dirname(__DIR__) . '/whatsapp_send.php';
        $jobs = new CrmOutboundJobService($this->pdo, $this->tenantId);
        $result = $jobs->enqueueGraphPayload(0, $phone, $payload, $idempotencyKey);
        if (!$result['ok']) {
            $this->logSendFailure((string) ($result['error'] ?? 'enqueue failed'), $phone);

            return false;
        }
        $processed = $jobs->processPendingJobs(1);
        if ($processed < 1) {
            $this->logSendFailure('job queued but send did not complete (check crm_outbound_jobs.last_error)', $phone);
        }

        return $processed >= 1;
    }

    private function logSendFailure(string $error, string $phone): void
    {
        if (!is_file(dirname(__DIR__) . '/whatsapp_webhook_diagnostics.php')) {
            return;
        }
        require_once dirname(__DIR__) . '/whatsapp_webhook_diagnostics.php';
        whatsapp_log_event('chatbot_send_failed', [
            'error' => substr($error, 0, 200),
            'to_masked' => function_exists('whatsapp_mask_wa_id') ? whatsapp_mask_wa_id($phone) : '***',
        ]);
    }
}
