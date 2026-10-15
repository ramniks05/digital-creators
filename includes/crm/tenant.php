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

function crm_lookup_tenant_by_phone_number_id(PDO $pdo, ?string $phoneNumberId): ?int
{
    $phoneNumberId = trim((string) $phoneNumberId);
    if ($phoneNumberId === '' || !crm_tables_ready($pdo)) {
        return null;
    }
    try {
        $stmt = $pdo->prepare(
            'SELECT tenant_id FROM crm_whatsapp_numbers WHERE phone_number_id = ? AND is_active = 1 LIMIT 1'
        );
        $stmt->execute([$phoneNumberId]);
        $tid = $stmt->fetchColumn();
        if ($tid !== false) {
            return (int) $tid;
        }
    } catch (Throwable $e) {
        // table may not exist
    }
    try {
        $stmt = $pdo->prepare(
            'SELECT tenant_id FROM crm_whatsapp_accounts WHERE phone_number_id = ? AND is_active = 1 LIMIT 1'
        );
        $stmt->execute([$phoneNumberId]);
        $tid = $stmt->fetchColumn();
        if ($tid !== false) {
            return (int) $tid;
        }
    } catch (Throwable $e) {
        // fall through
    }

    require_once dirname(__DIR__) . '/whatsapp_cloud.php';
    $cfgId = trim(whatsapp_cloud_config()['phone_number_id']);
    if ($cfgId !== '' && hash_equals($cfgId, $phoneNumberId)) {
        return crm_default_tenant_id();
    }

    return null;
}

function crm_tenant_id_for_phone_number_id(PDO $pdo, ?string $phoneNumberId): int
{
    $tid = crm_lookup_tenant_by_phone_number_id($pdo, $phoneNumberId);

    return $tid ?? crm_default_tenant_id();
}

/**
 * Webhook tenant resolution — returns null when unmapped and strict SaaS mode is enabled.
 */
function crm_webhook_resolve_tenant(PDO $pdo, ?string $phoneNumberId): ?int
{
    $tid = crm_lookup_tenant_by_phone_number_id($pdo, $phoneNumberId);
    if ($tid !== null) {
        return $tid;
    }
    if (is_file(dirname(__DIR__) . '/saas/config.php')) {
        require_once dirname(__DIR__) . '/saas/config.php';
        if (saas_config()['strict_webhook']) {
            return null;
        }
    }

    return crm_default_tenant_id();
}

/**
 * Resolve tenant from first WhatsApp phone_number_id in a Meta webhook payload.
 */
function crm_tenant_id_from_webhook_payload(PDO $pdo, array $payload): int
{
    if (($payload['object'] ?? '') !== 'whatsapp_business_account') {
        return crm_default_tenant_id();
    }
    foreach ($payload['entry'] ?? [] as $entry) {
        if (!is_array($entry)) {
            continue;
        }
        foreach ($entry['changes'] ?? [] as $change) {
            if (!is_array($change)) {
                continue;
            }
            $value = $change['value'] ?? [];
            if (!is_array($value)) {
                continue;
            }
            $phoneNumberId = isset($value['metadata']['phone_number_id'])
                ? (string) $value['metadata']['phone_number_id']
                : '';
            if ($phoneNumberId !== '') {
                return crm_webhook_resolve_tenant($pdo, $phoneNumberId) ?? crm_default_tenant_id();
            }
        }
    }

    return crm_default_tenant_id();
}

function crm_assert_tenant_access(PDO $pdo, int $adminId, int $tenantId): bool
{
    if (!crm_tables_ready($pdo)) {
        return $tenantId === crm_default_tenant_id();
    }

    return crm_admin_membership_active($pdo, $adminId, $tenantId);
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

// Membership helper used by RBAC (defined here to avoid circular requires).
function crm_admin_membership_active(PDO $pdo, int $adminId, int $tenantId): bool
{
    if (!crm_tables_ready($pdo)) {
        return true;
    }
    $stmt = $pdo->prepare(
        'SELECT 1 FROM crm_admin_tenant_roles WHERE admin_id = ? AND tenant_id = ? AND is_active = 1 LIMIT 1'
    );
    $stmt->execute([$adminId, $tenantId]);

    return (bool) $stmt->fetchColumn();
}
