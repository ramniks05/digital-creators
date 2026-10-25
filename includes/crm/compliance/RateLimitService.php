<?php
declare(strict_types=1);

final class CrmRateLimitService
{
    public function __construct(private PDO $pdo, private int $tenantId)
    {
    }

    /** Max outbound jobs to same recipient in rolling 24h (customer care). */
    public function allowOutboundToRecipient(string $waId, int $maxPerDay = 50): bool
    {
        require_once dirname(__DIR__, 2) . '/phone_utils.php';
        $waId = phone_normalize_digits($waId);
        try {
            $stmt = $this->pdo->prepare(
                'SELECT COUNT(*) FROM crm_outbound_jobs
                 WHERE tenant_id = ? AND recipient_wa_id = ? AND status IN (\'sent\',\'pending\',\'processing\',\'failed\')
                   AND created_at >= DATE_SUB(UTC_TIMESTAMP(), INTERVAL 1 DAY)'
            );
            $stmt->execute([$this->tenantId, $waId]);

            return (int) $stmt->fetchColumn() < $maxPerDay;
        } catch (Throwable $e) {
            return true;
        }
    }
}
