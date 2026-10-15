<?php
declare(strict_types=1);

require_once dirname(__DIR__) . '/crm/tenant.php';

final class SaasTenantProvisioner
{
    public function __construct(private PDO $pdo)
    {
    }

    /**
     * @return array{tenant_id:int, owner_role_id:int}
     */
    public function createWorkspace(string $companyName, string $slug, string $businessEmail, string $timezone = 'Asia/Kolkata'): array
    {
        $slug = $this->normalizeSlug($slug);
        $this->pdo->beginTransaction();
        try {
            $this->pdo->prepare(
                'INSERT INTO crm_tenants (slug, name, status, timezone, subscription_status, plan_slug)
                 VALUES (?, ?, \'active\', ?, \'trialing\', \'starter\')'
            )->execute([$slug, substr($companyName, 0, 160), $timezone]);
            $tenantId = (int) $this->pdo->lastInsertId();

            $this->pdo->prepare(
                'INSERT INTO crm_tenant_settings (tenant_id, business_email, currency) VALUES (?,?,\'INR\')'
            )->execute([$tenantId, substr($businessEmail, 0, 160)]);

            $roles = [
                ['owner', 'Owner'],
                ['admin', 'Admin'],
                ['manager', 'Manager'],
                ['agent', 'Agent'],
            ];
            $ownerRoleId = 0;
            $roleStmt = $this->pdo->prepare('INSERT INTO crm_roles (tenant_id, slug, name) VALUES (?,?,?)');
            foreach ($roles as [$rslug, $rname]) {
                $roleStmt->execute([$tenantId, $rslug, $rname]);
                if ($rslug === 'owner') {
                    $ownerRoleId = (int) $this->pdo->lastInsertId();
                }
            }

            $this->seedLeadStages($tenantId);
            $this->seedOnboarding($tenantId);
            $this->seedTrialSubscription($tenantId);
            $this->seedOwnerPermissions($tenantId, $ownerRoleId);

            $this->pdo->commit();

            return ['tenant_id' => $tenantId, 'owner_role_id' => $ownerRoleId];
        } catch (Throwable $e) {
            $this->pdo->rollBack();
            throw $e;
        }
    }

    public function attachUserAsOwner(int $tenantId, int $userId, int $ownerRoleId): void
    {
        $this->pdo->prepare(
            'INSERT INTO crm_tenant_users (user_id, tenant_id, role_id, is_active) VALUES (?,?,?,1)
             ON DUPLICATE KEY UPDATE role_id = VALUES(role_id), is_active = 1'
        )->execute([$userId, $tenantId, $ownerRoleId]);
    }

    private function normalizeSlug(string $slug): string
    {
        $slug = strtolower(trim($slug));
        $slug = preg_replace('/[^a-z0-9-]+/', '-', $slug) ?? '';
        $slug = trim($slug, '-');
        if ($slug === '') {
            $slug = 'workspace-' . bin2hex(random_bytes(4));
        }

        return substr($slug, 0, 64);
    }

    private function seedLeadStages(int $tenantId): void
    {
        $stages = [
            ['new', 'New Lead', 10, 0, 0],
            ['contacted', 'Contacted', 20, 0, 0],
            ['qualified', 'Qualified', 30, 0, 0],
            ['proposal', 'Proposal Sent', 40, 0, 0],
            ['negotiation', 'Negotiation', 50, 0, 0],
            ['won', 'Won', 60, 1, 0],
            ['lost', 'Lost', 70, 0, 1],
        ];
        $stmt = $this->pdo->prepare(
            'INSERT INTO crm_lead_stages (tenant_id, slug, name, sort_order, is_won, is_lost)
             VALUES (?,?,?,?,?,?)'
        );
        foreach ($stages as [$slug, $name, $order, $won, $lost]) {
            try {
                $stmt->execute([$tenantId, $slug, $name, $order, $won, $lost]);
            } catch (Throwable $e) {
                // table may not exist yet on partial installs
            }
        }
    }

    private function seedOnboarding(int $tenantId): void
    {
        $steps = [
            'workspace' => true,
            'plan' => false,
            'whatsapp' => false,
            'team' => false,
            'complete' => false,
        ];
        $this->pdo->prepare(
            'INSERT INTO crm_onboarding_progress (tenant_id, steps_json, current_step) VALUES (?,?,\'plan\')'
        )->execute([$tenantId, json_encode($steps, JSON_THROW_ON_ERROR)]);
    }

    private function seedOwnerPermissions(int $tenantId, int $ownerRoleId): void
    {
        if ($ownerRoleId <= 0) {
            return;
        }
        try {
            $this->pdo->prepare(
                'INSERT IGNORE INTO crm_role_permissions (role_id, permission_id)
                 SELECT ?, p.id FROM crm_permissions p'
            )->execute([$ownerRoleId]);
        } catch (Throwable $e) {
            // crm_permissions may not exist until phase3 migration
        }
    }

    private function seedTrialSubscription(int $tenantId): void
    {
        $planId = (int) ($this->pdo->query("SELECT id FROM crm_subscription_plans WHERE slug = 'starter' LIMIT 1")->fetchColumn() ?: 0);
        if ($planId <= 0) {
            return;
        }
        $trialDays = (int) ($this->pdo->query('SELECT trial_days FROM crm_subscription_plans WHERE id = ' . $planId)->fetchColumn() ?: 14);
        $this->pdo->prepare(
            'INSERT INTO crm_subscriptions (tenant_id, plan_id, status, trial_ends_at, current_period_start, current_period_end)
             VALUES (?,?,\'trialing\', DATE_ADD(UTC_TIMESTAMP(), INTERVAL ? DAY), UTC_TIMESTAMP(), DATE_ADD(UTC_TIMESTAMP(), INTERVAL ? DAY))'
        )->execute([$tenantId, $planId, $trialDays, $trialDays]);
    }
}
