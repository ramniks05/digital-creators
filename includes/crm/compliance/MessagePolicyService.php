<?php
declare(strict_types=1);

require_once dirname(__DIR__, 2) . '/phone_utils.php';
require_once dirname(__DIR__) . '/tenant.php';
require_once __DIR__ . '/RateLimitService.php';

final class CrmMessagePolicyService
{
    public function __construct(private PDO $pdo, private int $tenantId)
    {
    }

    /**
     * @return array{ok:bool, error?:string, code?:string}
     */
    public function validateOutboundText(string $recipientWaId, string $text): array
    {
        if ($this->isPaused()) {
            return ['ok' => false, 'error' => 'Outbound messaging is paused for this account.', 'code' => 'paused'];
        }

        $to = phone_normalize_digits($recipientWaId);
        if ($to === '' || trim($text) === '') {
            return ['ok' => false, 'error' => 'Recipient and message text are required.', 'code' => 'validation'];
        }

        return [
            'ok' => false,
            'error' => 'WhatsApp send is disabled on this site. Use your external messaging platform.',
            'code' => 'whatsapp_removed',
        ];
    }

    /**
     * @return array{ok:bool, error?:string, code?:string}
     */
    public function validateOutboundSession(string $recipientWaId): array
    {
        return $this->validateOutboundText($recipientWaId, '.');
    }

    /**
     * @return array{ok:bool, error?:string, code?:string}
     */
    public function validateTemplateSend(string $recipientWaId, string $templateName): array
    {
        return [
            'ok' => false,
            'error' => 'WhatsApp templates are disabled on this site. Use your external messaging platform.',
            'code' => 'whatsapp_removed',
        ];
    }

    public function isPaused(): bool
    {
        try {
            $stmt = $this->pdo->prepare('SELECT paused FROM crm_messaging_pause WHERE tenant_id = ? LIMIT 1');
            $stmt->execute([$this->tenantId]);

            return (int) $stmt->fetchColumn() === 1;
        } catch (Throwable $e) {
            return false;
        }
    }
}
