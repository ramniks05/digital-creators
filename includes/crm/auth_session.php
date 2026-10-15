<?php
declare(strict_types=1);

require_once dirname(__DIR__) . '/auth.php';
require_once dirname(__DIR__) . '/saas/UserAuth.php';
require_once __DIR__ . '/tenant.php';
require_once __DIR__ . '/rbac.php';

/**
 * Allow legacy site admins or SaaS tenant users into CRM UI.
 */
function crm_require_crm_user(): void
{
    if (admin_logged_in()) {
        return;
    }
    if (SaasUserAuth::loggedIn()) {
        $pdo = db();
        $tenantId = crm_current_tenant_id();
        require_once dirname(__DIR__) . '/saas/TenantGuard.php';
        if (!CrmTenantGuard::userHasTenantAccess($pdo, (int) $_SESSION['crm_user_id'], $tenantId)) {
            http_response_code(403);
            echo 'No access to this workspace.';
            exit;
        }

        return;
    }
    $next = urlencode($_SERVER['REQUEST_URI'] ?? '/admin/crm/');
    if (is_file(dirname(__DIR__, 2) . '/app/login.php')) {
        header('Location: /app/login.php?next=' . $next);
    } else {
        header('Location: /admin/login.php');
    }
    exit;
}

function crm_bootstrap_session_context(): void
{
    $pdo = db();
    if (!crm_tables_ready($pdo)) {
        return;
    }
    if (admin_logged_in()) {
        crm_load_admin_session_context($pdo, (int) $_SESSION['admin_id']);
    } elseif (SaasUserAuth::loggedIn()) {
        require_once dirname(__DIR__) . '/saas/TenantGuard.php';
        $uid = (int) $_SESSION['crm_user_id'];
        $tenants = CrmTenantGuard::listUserTenants($pdo, $uid);
        if ($tenants !== [] && empty($_SESSION['crm_tenant_id'])) {
            crm_set_current_tenant_id((int) $tenants[0]['id']);
            $_SESSION['crm_role_slug'] = (string) $tenants[0]['role_slug'];
        }
    }
}
