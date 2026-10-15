<?php
/**
 * Process CRM outbound job queue. Run every minute via cron:
 * * * * * * php /path/to/scripts/crm_outbound_worker.php
 */
declare(strict_types=1);

require_once dirname(__DIR__) . '/includes/db.php';
require_once dirname(__DIR__) . '/includes/crm/tenant.php';
require_once dirname(__DIR__) . '/includes/crm/OutboundJobService.php';

$pdo = db();
$tenantId = crm_default_tenant_id();
if (crm_tables_ready($pdo)) {
    $stmt = $pdo->query('SELECT id FROM crm_tenants WHERE status = \'active\' ORDER BY id ASC');
    $tenants = $stmt->fetchAll(PDO::FETCH_COLUMN) ?: [$tenantId];
} else {
    $tenants = [$tenantId];
}

$total = 0;
foreach ($tenants as $tid) {
    $service = new CrmOutboundJobService($pdo, (int) $tid);
    $total += $service->processPendingJobs(15);
}

echo gmdate('c') . " processed={$total}\n";
