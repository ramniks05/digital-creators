<?php
declare(strict_types=1);

final class CrmConsentService
{
    public function __construct(private PDO $pdo, private int $tenantId)
    {
    }

    public function hasMarketingConsent(string $waId): bool
    {
        require_once dirname(__DIR__, 2) . '/whatsapp_contacts.php';

        return whatsapp_contact_can_receive_marketing($waId);
    }

    public function recordConsent(int $contactId, string $purpose, string $source, ?string $evidence = null): void
    {
        try {
            $this->pdo->prepare(
                'INSERT INTO crm_contact_consents (tenant_id, contact_id, purpose, source, evidence)
                 VALUES (?,?,?,?,?)'
            )->execute([
                $this->tenantId,
                $contactId,
                substr($purpose, 0, 64),
                substr($source, 0, 64),
                $evidence !== null ? substr($evidence, 0, 255) : null,
            ]);
        } catch (Throwable $e) {
            error_log('CRM consent record: ' . $e->getMessage());
        }
    }
}
