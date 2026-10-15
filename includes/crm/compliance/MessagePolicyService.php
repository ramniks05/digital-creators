<?php
declare(strict_types=1);

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
        require_once dirname(__DIR__, 2) . '/whatsapp_send.php';
        require_once dirname(__DIR__, 2) . '/whatsapp_contacts.php';

        if ($this->isPaused()) {
            return ['ok' => false, 'error' => 'Outbound messaging is paused for this account.', 'code' => 'paused'];
        }

        $to = whatsapp_normalize_recipient($recipientWaId);
        if ($to === '' || trim($text) === '') {
            return ['ok' => false, 'error' => 'Recipient and message text are required.', 'code' => 'validation'];
        }

        if (whatsapp_contact_is_opted_out($this->pdo, $to)) {
            return ['ok' => false, 'error' => 'Contact has opted out (STOP).', 'code' => 'opt_out'];
        }

        if ($this->isSuppressed($to)) {
            return ['ok' => false, 'error' => 'Contact is on suppression list.', 'code' => 'suppressed'];
        }

        $rate = new CrmRateLimitService($this->pdo, $this->tenantId);
        if (!$rate->allowOutboundToRecipient($to)) {
            return ['ok' => false, 'error' => 'Daily send limit reached for this contact.', 'code' => 'rate_limit'];
        }

        if (!whatsapp_service_window_open($this->pdo, $to)) {
            return [
                'ok' => false,
                'error' => '24-hour customer service window closed. Send an approved template instead.',
                'code' => 'window_closed',
            ];
        }

        return ['ok' => true];
    }

    /**
     * Session messages (interactive menus, documents) within the 24h window.
     *
     * @return array{ok:bool, error?:string, code?:string}
     */
    public function validateOutboundSession(string $recipientWaId): array
    {
        require_once dirname(__DIR__, 2) . '/whatsapp_send.php';
        require_once dirname(__DIR__, 2) . '/whatsapp_contacts.php';

        if ($this->isPaused()) {
            return ['ok' => false, 'error' => 'Outbound messaging is paused for this account.', 'code' => 'paused'];
        }

        $to = whatsapp_normalize_recipient($recipientWaId);
        if ($to === '') {
            return ['ok' => false, 'error' => 'Recipient is required.', 'code' => 'validation'];
        }

        if (whatsapp_contact_is_opted_out($this->pdo, $to)) {
            return ['ok' => false, 'error' => 'Contact has opted out (STOP).', 'code' => 'opt_out'];
        }

        if ($this->isSuppressed($to)) {
            return ['ok' => false, 'error' => 'Contact is on suppression list.', 'code' => 'suppressed'];
        }

        $rate = new CrmRateLimitService($this->pdo, $this->tenantId);
        if (!$rate->allowOutboundToRecipient($to)) {
            return ['ok' => false, 'error' => 'Daily send limit reached for this contact.', 'code' => 'rate_limit'];
        }

        if (!whatsapp_service_window_open($this->pdo, $to)) {
            return [
                'ok' => false,
                'error' => '24-hour customer service window closed. Send an approved template instead.',
                'code' => 'window_closed',
            ];
        }

        return ['ok' => true];
    }

    /**
     * @return array{ok:bool, error?:string, code?:string}
     */
    public function validateTemplateSend(string $recipientWaId, string $templateName): array
    {
        require_once dirname(__DIR__, 2) . '/whatsapp_send.php';
        require_once dirname(__DIR__, 2) . '/whatsapp_contacts.php';

        if ($this->isPaused()) {
            return ['ok' => false, 'error' => 'Outbound messaging is paused.', 'code' => 'paused'];
        }

        $to = whatsapp_normalize_recipient($recipientWaId);
        $templateName = preg_replace('/[^a-z0-9_]/i', '', $templateName);
        if ($to === '' || $templateName === '') {
            return ['ok' => false, 'error' => 'Recipient and template are required.', 'code' => 'validation'];
        }

        if (whatsapp_contact_is_opted_out($this->pdo, $to)) {
            return ['ok' => false, 'error' => 'Contact has opted out.', 'code' => 'opt_out'];
        }

        $def = whatsapp_lookup_template_definition($templateName, whatsapp_default_template_language());
        if ($def === null || strtoupper((string) ($def['status'] ?? '')) !== 'APPROVED') {
            return ['ok' => false, 'error' => 'Template is not approved or not found.', 'code' => 'template'];
        }

        return ['ok' => true];
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

    private function isSuppressed(string $waId): bool
    {
        try {
            $stmt = $this->pdo->prepare('SELECT 1 FROM crm_contact_suppressions WHERE tenant_id = ? AND wa_id = ? LIMIT 1');
            $stmt->execute([$this->tenantId, $waId]);

            return (bool) $stmt->fetchColumn();
        } catch (Throwable $e) {
            return false;
        }
    }
}
