<?php
declare(strict_types=1);

require_once dirname(__DIR__) . '/crm/tenant.php';

final class CrmTenantGuard
{
    public static function assertRowBelongsToTenant(PDO $pdo, string $table, int $tenantId, int $rowId, string $idColumn = 'id'): bool
    {
        $allowed = [
            'crm_contacts',
            'crm_conversations',
            'crm_messages',
            'crm_contact_tags',
        ];
        if (!in_array($table, $allowed, true)) {
            return false;
        }
        $tenantColumn = $table === 'crm_contact_tags' ? 'tenant_id' : 'tenant_id';
        $sql = "SELECT 1 FROM `{$table}` WHERE `{$idColumn}` = ? AND `{$tenantColumn}` = ? LIMIT 1";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$rowId, $tenantId]);

        return (bool) $stmt->fetchColumn();
    }

    public static function userHasTenantAccess(PDO $pdo, int $userId, int $tenantId): bool
    {
        try {
            $stmt = $pdo->prepare(
                'SELECT 1 FROM crm_tenant_users WHERE user_id = ? AND tenant_id = ? AND is_active = 1 LIMIT 1'
            );
            $stmt->execute([$userId, $tenantId]);

            return (bool) $stmt->fetchColumn();
        } catch (Throwable $e) {
            return false;
        }
    }

    public static function listUserTenants(PDO $pdo, int $userId): array
    {
        $stmt = $pdo->prepare(
            'SELECT t.id, t.name, t.slug, t.status, r.slug AS role_slug
             FROM crm_tenant_users tu
             INNER JOIN crm_tenants t ON t.id = tu.tenant_id
             INNER JOIN crm_roles r ON r.id = tu.role_id
             WHERE tu.user_id = ? AND tu.is_active = 1 AND t.status = \'active\'
             ORDER BY t.name ASC'
        );
        $stmt->execute([$userId]);

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}
