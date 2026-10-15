<?php
declare(strict_types=1);

require_once __DIR__ . '/tenant.php';

function crm_admin_has_permission(PDO $pdo, int $adminId, int $tenantId, string $permissionSlug): bool
{
    if (!crm_tables_ready($pdo)) {
        return $adminId > 0;
    }
    $stmt = $pdo->prepare(
        'SELECT 1 FROM crm_admin_tenant_roles atr
         INNER JOIN crm_roles r ON r.id = atr.role_id
         INNER JOIN crm_role_permissions rp ON rp.role_id = r.id
         INNER JOIN crm_permissions p ON p.id = rp.permission_id
         WHERE atr.admin_id = ? AND atr.tenant_id = ? AND atr.is_active = 1
           AND p.slug = ? LIMIT 1'
    );
    $stmt->execute([$adminId, $tenantId, $permissionSlug]);

    return (bool) $stmt->fetchColumn();
}

function crm_user_has_permission(PDO $pdo, int $userId, int $tenantId, string $permissionSlug): bool
{
    if (!crm_tables_ready($pdo)) {
        return $userId > 0;
    }
    $stmt = $pdo->prepare(
        'SELECT 1 FROM crm_tenant_users tu
         INNER JOIN crm_roles r ON r.id = tu.role_id
         INNER JOIN crm_role_permissions rp ON rp.role_id = r.id
         INNER JOIN crm_permissions p ON p.id = rp.permission_id
         WHERE tu.user_id = ? AND tu.tenant_id = ? AND tu.is_active = 1 AND p.slug = ? LIMIT 1'
    );
    $stmt->execute([$userId, $tenantId, $permissionSlug]);

    return (bool) $stmt->fetchColumn();
}

function crm_actor_has_permission(PDO $pdo, int $tenantId, string $permissionSlug): bool
{
    if (!empty($_SESSION['admin_id'])) {
        return crm_admin_has_permission($pdo, (int) $_SESSION['admin_id'], $tenantId, $permissionSlug);
    }
    if (!empty($_SESSION['crm_user_id'])) {
        return crm_user_has_permission($pdo, (int) $_SESSION['crm_user_id'], $tenantId, $permissionSlug);
    }

    return false;
}

function crm_require_permission(PDO $pdo, string $permissionSlug): void
{
    if (empty($_SESSION['admin_id']) && empty($_SESSION['crm_user_id'])) {
        http_response_code(401);
        header('Content-Type: application/json');
        echo json_encode(['ok' => false, 'error' => 'Unauthorized']);
        exit;
    }
    $tenantId = crm_current_tenant_id();
    if (!empty($_SESSION['admin_id'])) {
        $adminId = (int) $_SESSION['admin_id'];
        if (!crm_admin_membership_active($pdo, $adminId, $tenantId)) {
            http_response_code(403);
            header('Content-Type: application/json');
            echo json_encode(['ok' => false, 'error' => 'No tenant access']);
            exit;
        }
    } elseif (!empty($_SESSION['crm_user_id'])) {
        require_once dirname(__DIR__) . '/saas/TenantGuard.php';
        if (!CrmTenantGuard::userHasTenantAccess($pdo, (int) $_SESSION['crm_user_id'], $tenantId)) {
            http_response_code(403);
            header('Content-Type: application/json');
            echo json_encode(['ok' => false, 'error' => 'No tenant access']);
            exit;
        }
    }
    if (!crm_actor_has_permission($pdo, $tenantId, $permissionSlug)) {
        http_response_code(403);
        header('Content-Type: application/json');
        echo json_encode(['ok' => false, 'error' => 'Forbidden']);
        exit;
    }
}

function crm_load_admin_session_context(PDO $pdo, int $adminId): void
{
    if (!crm_tables_ready($pdo)) {
        crm_set_current_tenant_id(crm_default_tenant_id());

        return;
    }
    $stmt = $pdo->prepare(
        'SELECT atr.tenant_id, r.slug AS role_slug
         FROM crm_admin_tenant_roles atr
         INNER JOIN crm_roles r ON r.id = atr.role_id
         WHERE atr.admin_id = ? AND atr.is_active = 1
         ORDER BY atr.tenant_id ASC LIMIT 1'
    );
    $stmt->execute([$adminId]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    if ($row) {
        crm_set_current_tenant_id((int) $row['tenant_id']);
        $_SESSION['crm_role_slug'] = (string) $row['role_slug'];
    }
}
