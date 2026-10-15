<?php
declare(strict_types=1);

final class SaasUsageMeter
{
    public function __construct(private PDO $pdo, private int $tenantId)
    {
    }

    public function record(string $metric, int $quantity = 1, ?string $idempotencyKey = null): void
    {
        try {
            $this->pdo->prepare(
                'INSERT INTO crm_usage_events (tenant_id, event_type, quantity, idempotency_key)
                 VALUES (?,?,?,?)
                 ON DUPLICATE KEY UPDATE quantity = quantity'
            )->execute([
                $this->tenantId,
                substr($metric, 0, 64),
                max(1, $quantity),
                $idempotencyKey !== null ? substr($idempotencyKey, 0, 64) : null,
            ]);
        } catch (Throwable $e) {
            return;
        }
        $period = gmdate('Y-m');
        $this->pdo->prepare(
            'INSERT INTO crm_usage_counters (tenant_id, period_ym, metric, value)
             VALUES (?,?,?,?)
             ON DUPLICATE KEY UPDATE value = value + VALUES(value), updated_at = UTC_TIMESTAMP()'
        )->execute([$this->tenantId, $period, substr($metric, 0, 64), max(1, $quantity)]);
    }

    /**
     * @return array<string, int>
     */
    public function periodTotals(?string $periodYm = null): array
    {
        $periodYm = $periodYm ?? gmdate('Y-m');
        $stmt = $this->pdo->prepare(
            'SELECT metric, value FROM crm_usage_counters WHERE tenant_id = ? AND period_ym = ?'
        );
        $stmt->execute([$this->tenantId, $periodYm]);
        $out = [];
        foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
            $out[(string) $row['metric']] = (int) $row['value'];
        }

        return $out;
    }
}
