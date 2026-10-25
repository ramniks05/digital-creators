<?php
declare(strict_types=1);

final class CrmConsentService
{
    public function __construct(private PDO $pdo, private int $tenantId)
    {
    }

    public function hasMarketingConsent(string $waId): bool
    {
        $waId = preg_replace('/\D+/', '', $waId) ?? '';
        if ($waId === '') {
            return false;
        }
        try {
            $stmt = $this->pdo->prepare(
                'SELECT consent_status FROM crm_contacts WHERE tenant_id = ? AND wa_id = ? LIMIT 1'
            );
            $stmt->execute([$this->tenantId, $waId]);
            $status = $stmt->fetchColumn();
            if ($status === false) {
                return true;
            }

            return (string) $status !== 'opted_out';
        } catch (Throwable $e) {
            return false;
        }
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
