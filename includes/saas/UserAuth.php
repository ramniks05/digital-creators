<?php
declare(strict_types=1);

require_once dirname(__DIR__) . '/auth.php';
require_once __DIR__ . '/TenantGuard.php';
require_once dirname(__DIR__) . '/crm/tenant.php';

final class SaasUserAuth
{
    public static function login(PDO $pdo, string $email, string $password): bool
    {
        $email = strtolower(trim($email));
        $stmt = $pdo->prepare(
            'SELECT id, password_hash, status FROM crm_users WHERE email = ? LIMIT 1'
        );
        $stmt->execute([$email]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$row || !password_verify($password, (string) $row['password_hash'])) {
            return false;
        }
        if (!in_array((string) $row['status'], ['active', 'pending_verification'], true)) {
            return false;
        }

        $_SESSION['crm_user_id'] = (int) $row['id'];
        $_SESSION['crm_user_email'] = $email;
        unset($_SESSION['admin_id'], $_SESSION['admin_username']);
        session_regenerate_id(true);

        $tenants = CrmTenantGuard::listUserTenants($pdo, (int) $row['id']);
        if ($tenants !== []) {
            crm_set_current_tenant_id((int) $tenants[0]['id']);
            $_SESSION['crm_role_slug'] = (string) $tenants[0]['role_slug'];
        }

        $pdo->prepare('UPDATE crm_users SET last_login_at = UTC_TIMESTAMP() WHERE id = ?')->execute([(int) $row['id']]);

        return true;
    }

    public static function loggedIn(): bool
    {
        return !empty($_SESSION['crm_user_id']);
    }

    public static function logout(): void
    {
        unset($_SESSION['crm_user_id'], $_SESSION['crm_user_email'], $_SESSION['crm_tenant_id'], $_SESSION['crm_role_slug']);
    }

    public static function switchWorkspace(PDO $pdo, int $userId, int $tenantId): bool
    {
        if (!CrmTenantGuard::userHasTenantAccess($pdo, $userId, $tenantId)) {
            return false;
        }
        crm_set_current_tenant_id($tenantId);
        $stmt = $pdo->prepare(
            'SELECT r.slug FROM crm_tenant_users tu INNER JOIN crm_roles r ON r.id = tu.role_id
             WHERE tu.user_id = ? AND tu.tenant_id = ? LIMIT 1'
        );
        $stmt->execute([$userId, $tenantId]);
        $_SESSION['crm_role_slug'] = (string) ($stmt->fetchColumn() ?: 'agent');

        return true;
    }

    public static function currentUserId(): ?int
    {
        return !empty($_SESSION['crm_user_id']) ? (int) $_SESSION['crm_user_id'] : null;
    }
}
