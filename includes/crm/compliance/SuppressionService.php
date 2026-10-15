<?php
declare(strict_types=1);

final class CrmSuppressionService
{
    public function __construct(private PDO $pdo, private int $tenantId)
    {
    }

    public function isSuppressed(string $waId): bool
    {
        require_once dirname(__DIR__, 2) . '/whatsapp_send.php';
        $waId = whatsapp_normalize_recipient($waId);
        try {
            $stmt = $this->pdo->prepare(
                'SELECT 1 FROM crm_contact_suppressions WHERE tenant_id = ? AND wa_id = ? LIMIT 1'
            );
            $stmt->execute([$this->tenantId, $waId]);

            return (bool) $stmt->fetchColumn();
        } catch (Throwable $e) {
            return false;
        }
    }

    public function suppress(string $waId, string $reason = 'opt_out', string $source = 'system'): void
    {
        require_once dirname(__DIR__, 2) . '/whatsapp_send.php';
        $waId = whatsapp_normalize_recipient($waId);
        $this->pdo->prepare(
            'INSERT INTO crm_contact_suppressions (tenant_id, wa_id, reason, source)
             VALUES (?,?,?,?)
             ON DUPLICATE KEY UPDATE reason = VALUES(reason), source = VALUES(source)'
        )->execute([$this->tenantId, $waId, substr($reason, 0, 64), substr($source, 0, 64)]);
    }
}
