<?php
declare(strict_types=1);

require_once __DIR__ . '/compliance/MessagePolicyService.php';
require_once __DIR__ . '/messages.php';

final class CrmOutboundJobService
{
    public function __construct(private PDO $pdo, private int $tenantId)
    {
    }

    /**
     * @param array<string, mixed> $payload Graph-style payload (type text|template)
     * @return array{ok:bool, job_id?:int, error?:string}
     */
    public function enqueueText(int $adminId, string $recipientWaId, string $text, ?string $idempotencyKey = null): array
    {
        $policy = new CrmMessagePolicyService($this->pdo, $this->tenantId);
        $check = $policy->validateOutboundText($recipientWaId, $text);
        if (!$check['ok']) {
            return ['ok' => false, 'error' => $check['error'] ?? 'Policy blocked'];
        }

        require_once dirname(__DIR__) . '/whatsapp_send.php';
        $to = whatsapp_normalize_recipient($recipientWaId);
        $payload = [
            'messaging_product' => 'whatsapp',
            'to' => $to,
            'type' => 'text',
            'text' => ['preview_url' => false, 'body' => trim($text)],
        ];

        return $this->insertJob($adminId, $to, 'text', $payload, $idempotencyKey);
    }

    /**
     * @param array<string, mixed>|null $components
     * @return array{ok:bool, job_id?:int, error?:string}
     */
    public function enqueueTemplate(
        int $adminId,
        string $recipientWaId,
        string $templateName,
        ?string $language = null,
        ?array $components = null,
        ?string $idempotencyKey = null
    ): array {
        $policy = new CrmMessagePolicyService($this->pdo, $this->tenantId);
        $check = $policy->validateTemplateSend($recipientWaId, $templateName);
        if (!$check['ok']) {
            return ['ok' => false, 'error' => $check['error'] ?? 'Policy blocked'];
        }

        require_once dirname(__DIR__) . '/whatsapp_send.php';
        $to = whatsapp_normalize_recipient($recipientWaId);
        $templateName = preg_replace('/[^a-z0-9_]/i', '', $templateName);
        $lang = $language !== null && $language !== ''
            ? $language
            : whatsapp_default_template_language();
        $payload = [
            'messaging_product' => 'whatsapp',
            'to' => $to,
            'type' => 'template',
            'template' => [
                'name' => $templateName,
                'language' => ['code' => $lang],
            ],
        ];
        if ($components !== null && $components !== []) {
            $payload['template']['components'] = $components;
        }

        return $this->insertJob($adminId, $to, 'template', $payload, $idempotencyKey);
    }

    /**
     * @param array<string, mixed> $payload Full Graph API message payload (interactive, document, etc.)
     * @return array{ok:bool, job_id?:int, error?:string}
     */
    public function enqueueGraphPayload(
        int $adminId,
        string $recipientWaId,
        array $payload,
        ?string $idempotencyKey = null
    ): array {
        require_once dirname(__DIR__) . '/whatsapp_send.php';
        $to = whatsapp_normalize_recipient($recipientWaId);
        $payload['to'] = $to;
        $payload['messaging_product'] = 'whatsapp';

        $policy = new CrmMessagePolicyService($this->pdo, $this->tenantId);
        $check = $policy->validateOutboundSession($to);
        if (!$check['ok']) {
            return ['ok' => false, 'error' => $check['error'] ?? 'Policy blocked'];
        }

        $type = (string) ($payload['type'] ?? 'text');
        $jobType = $type === 'document' ? 'document' : 'text';

        return $this->insertJob($adminId, $to, $jobType, $payload, $idempotencyKey);
    }

    /**
     * @param array<string, mixed> $payload
     * @return array{ok:bool, job_id?:int, error?:string}
     */
    private function insertJob(int $adminId, string $to, string $jobType, array $payload, ?string $idempotencyKey): array
    {
        $convId = crm_conversation_id_for_peer($this->pdo, $this->tenantId, $to);
        $json = json_encode($payload, JSON_UNESCAPED_UNICODE);
        if ($json === false) {
            return ['ok' => false, 'error' => 'Invalid payload'];
        }
        $key = $idempotencyKey !== null && $idempotencyKey !== '' ? substr($idempotencyKey, 0, 64) : null;

        try {
            $stmt = $this->pdo->prepare(
                'INSERT INTO crm_outbound_jobs
                 (tenant_id, conversation_id, recipient_wa_id, job_type, payload_json, status, next_attempt_at, requested_by_admin_id, idempotency_key)
                 VALUES (?,?,?,?,?,\'pending\',UTC_TIMESTAMP(),?,?)'
            );
            $stmt->execute([$this->tenantId, $convId, $to, $jobType, $json, $adminId, $key]);
        } catch (PDOException $e) {
            if ($key !== null) {
                $find = $this->pdo->prepare(
                    'SELECT id FROM crm_outbound_jobs WHERE tenant_id = ? AND idempotency_key = ? LIMIT 1'
                );
                $find->execute([$this->tenantId, $key]);
                $existing = $find->fetchColumn();
                if ($existing) {
                    return ['ok' => true, 'job_id' => (int) $existing];
                }
            }

            return ['ok' => false, 'error' => 'Could not enqueue job'];
        }

        return ['ok' => true, 'job_id' => (int) $this->pdo->lastInsertId()];
    }

    public function processPendingJobs(int $limit = 10): int
    {
        $limit = max(1, min(50, $limit));
        $stmt = $this->pdo->prepare(
            'SELECT * FROM crm_outbound_jobs
             WHERE tenant_id = ? AND status IN (\'pending\',\'failed\')
               AND attempts < max_attempts
               AND (next_attempt_at IS NULL OR next_attempt_at <= UTC_TIMESTAMP())
             ORDER BY id ASC LIMIT ' . $limit
        );
        $stmt->execute([$this->tenantId]);
        $jobs = $stmt->fetchAll(PDO::FETCH_ASSOC);
        $processed = 0;

        require_once dirname(__DIR__) . '/whatsapp_send.php';

        foreach ($jobs as $job) {
            $jobId = (int) $job['id'];
            $this->pdo->prepare(
                'UPDATE crm_outbound_jobs SET status = \'processing\', attempts = attempts + 1, updated_at = UTC_TIMESTAMP() WHERE id = ?'
            )->execute([$jobId]);

            $payload = json_decode((string) ($job['payload_json'] ?? ''), true);
            if (!is_array($payload)) {
                $this->failJob($jobId, 'Invalid job payload');
                continue;
            }

            $policy = new CrmMessagePolicyService($this->pdo, $this->tenantId);
            $to = (string) ($job['recipient_wa_id'] ?? '');
            $type = (string) ($payload['type'] ?? 'text');
            if ($type === 'text') {
                $text = (string) ($payload['text']['body'] ?? '');
                $check = $policy->validateOutboundText($to, $text);
                if (!$check['ok']) {
                    $this->failJob($jobId, $check['error'] ?? 'Policy blocked', false);
                    continue;
                }
            } elseif ($type === 'template') {
                $tplName = (string) ($payload['template']['name'] ?? '');
                $check = $policy->validateTemplateSend($to, $tplName);
                if (!$check['ok']) {
                    $this->failJob($jobId, $check['error'] ?? 'Policy blocked', false);
                    continue;
                }
            } elseif ($type === 'interactive' || $type === 'document') {
                $check = $policy->validateOutboundSession($to);
                if (!$check['ok']) {
                    $this->failJob($jobId, $check['error'] ?? 'Policy blocked', false);
                    continue;
                }
            }

            $result = whatsapp_send_graph($payload);
            if (!$result['ok']) {
                $permanent = ((int) ($result['http_code'] ?? 0)) >= 400 && ((int) ($result['http_code'] ?? 0)) < 500;
                $this->failJob($jobId, (string) ($result['error'] ?? 'Send failed'), !$permanent);
                continue;
            }

            $wamid = (string) ($result['wa_message_id'] ?? '');
            whatsapp_sync_outbound_from_graph_payload($payload, $wamid);
            $bodyText = null;
            if ($type === 'text') {
                $bodyText = (string) ($payload['text']['body'] ?? '');
            } elseif ($type === 'interactive') {
                $bodyText = (string) ($payload['interactive']['body']['text'] ?? 'Interactive message');
            } elseif ($type === 'document') {
                $bodyText = (string) ($payload['document']['caption'] ?? 'Document');
            } elseif ($type === 'template' && function_exists('whatsapp_outbound_body_preview')) {
                $tpl = (string) ($payload['template']['name'] ?? '');
                $lang = (string) ($payload['template']['language']['code'] ?? '');
                $bodyText = whatsapp_outbound_body_preview('', $tpl, $lang !== '' ? $lang : null);
            }
            crm_record_message(
                $this->pdo,
                $this->tenantId,
                $to,
                'outbound',
                $wamid,
                $type,
                $bodyText,
                null,
                'sent'
            );

            $this->pdo->prepare(
                'UPDATE crm_outbound_jobs SET status = \'sent\', wa_message_id = ?, last_error = NULL, updated_at = UTC_TIMESTAMP() WHERE id = ?'
            )->execute([$wamid, $jobId]);
            $processed++;
        }

        return $processed;
    }

    private function failJob(int $jobId, string $error, bool $retry = true): void
    {
        $err = substr($error, 0, 500);
        if ($retry) {
            $this->pdo->prepare(
                'UPDATE crm_outbound_jobs SET status = \'failed\', last_error = ?,
                 next_attempt_at = DATE_ADD(UTC_TIMESTAMP(), INTERVAL 5 MINUTE), updated_at = UTC_TIMESTAMP() WHERE id = ?'
            )->execute([$err, $jobId]);
        } else {
            $this->pdo->prepare(
                'UPDATE crm_outbound_jobs SET status = \'cancelled\', last_error = ?,
                 next_attempt_at = NULL, updated_at = UTC_TIMESTAMP() WHERE id = ?'
            )->execute([$err, $jobId]);
        }
    }
}
