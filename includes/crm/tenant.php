<?php
/**
 * CRM tenant context (single-tenant default, multi-tenant ready).
 */
declare(strict_types=1);

function crm_tables_ready(PDO $pdo): bool
{
    try {
        $pdo->query('SELECT 1 FROM crm_tenants LIMIT 1');

        return true;
    } catch (Throwable $e) {
        return false;
    }
}

function crm_default_tenant_id(): int
{
    return 1;
}

function crm_current_tenant_id(): int
{
    if (!empty($_SESSION['crm_tenant_id'])) {
        return (int) $_SESSION['crm_tenant_id'];
    }

    return crm_default_tenant_id();
}

function crm_set_current_tenant_id(int $tenantId): void
{
    $_SESSION['crm_tenant_id'] = max(1, $tenantId);
}

function crm_audit_log(
    PDO $pdo,
    string $action,
    ?string $entityType = null,
    ?string $entityId = null,
    ?array $meta = null,
    ?int $adminId = null,
    ?int $tenantId = null
): void {
    if (!crm_tables_ready($pdo)) {
        return;
    }
    $tenantId = $tenantId ?? crm_current_tenant_id();
    if ($adminId === null && !empty($_SESSION['admin_id'])) {
        $adminId = (int) $_SESSION['admin_id'];
    }
    $stmt = $pdo->prepare(
        'INSERT INTO crm_audit_logs (tenant_id, admin_id, action, entity_type, entity_id, meta_json)
         VALUES (?, ?, ?, ?, ?, ?)'
    );
    $stmt->execute([
        $tenantId,
        $adminId,
        substr($action, 0, 64),
        $entityType !== null ? substr($entityType, 0, 64) : null,
        $entityId !== null ? substr($entityId, 0, 64) : null,
        $meta !== null ? json_encode($meta, JSON_UNESCAPED_UNICODE) : null,
    ]);
}
