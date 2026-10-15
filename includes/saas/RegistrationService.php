<?php
declare(strict_types=1);

require_once dirname(__DIR__) . '/db.php';
require_once __DIR__ . '/TenantProvisioner.php';

final class SaasRegistrationService
{
    public function __construct(private PDO $pdo)
    {
    }

    /**
     * @return array{ok:bool, user_id?:int, tenant_id?:int, error?:string}
     */
    public function register(
        string $email,
        string $password,
        string $fullName,
        string $companyName,
        ?string $workspaceSlug = null
    ): array {
        $email = strtolower(trim($email));
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return ['ok' => false, 'error' => 'Invalid email address.'];
        }
        if (strlen($password) < 10) {
            return ['ok' => false, 'error' => 'Password must be at least 10 characters.'];
        }
        if (trim($companyName) === '') {
            return ['ok' => false, 'error' => 'Company name is required.'];
        }

        $exists = $this->pdo->prepare('SELECT 1 FROM crm_users WHERE email = ? LIMIT 1');
        $exists->execute([$email]);
        if ($exists->fetchColumn()) {
            return ['ok' => false, 'error' => 'An account with this email already exists.'];
        }

        $slug = $workspaceSlug !== null && $workspaceSlug !== ''
            ? $workspaceSlug
            : preg_replace('/[^a-z0-9]+/', '-', strtolower($companyName)) ?? '';

        $this->pdo->beginTransaction();
        try {
            $hash = password_hash($password, PASSWORD_DEFAULT);
            $this->pdo->prepare(
                'INSERT INTO crm_users (email, password_hash, full_name, status)
                 VALUES (?,?,?,\'pending_verification\')'
            )->execute([$email, $hash, substr(trim($fullName), 0, 160)]);

            $userId = (int) $this->pdo->lastInsertId();
            $provisioner = new SaasTenantProvisioner($this->pdo);
            $workspace = $provisioner->createWorkspace($companyName, $slug, $email);
            $provisioner->attachUserAsOwner($workspace['tenant_id'], $userId, $workspace['owner_role_id']);

            $token = bin2hex(random_bytes(32));
            $this->pdo->prepare(
                'INSERT INTO crm_email_verification_tokens (user_id, token_hash, expires_at)
                 VALUES (?,?, DATE_ADD(UTC_TIMESTAMP(), INTERVAL 48 HOUR))'
            )->execute([$userId, hash('sha256', $token)]);

            $this->pdo->commit();

            return [
                'ok' => true,
                'user_id' => $userId,
                'tenant_id' => $workspace['tenant_id'],
                'verification_token' => $token,
            ];
        } catch (Throwable $e) {
            $this->pdo->rollBack();
            if (str_contains($e->getMessage(), 'Duplicate') || str_contains($e->getMessage(), 'uq_crm_tenant_slug')) {
                return ['ok' => false, 'error' => 'Workspace URL is already taken. Choose another.'];
            }

            return ['ok' => false, 'error' => 'Registration failed. Please try again.'];
        }
    }

    public function verifyEmail(string $token): bool
    {
        $hash = hash('sha256', $token);
        $stmt = $this->pdo->prepare(
            'SELECT user_id FROM crm_email_verification_tokens
             WHERE token_hash = ? AND used_at IS NULL AND expires_at > UTC_TIMESTAMP() LIMIT 1'
        );
        $stmt->execute([$hash]);
        $userId = $stmt->fetchColumn();
        if ($userId === false) {
            return false;
        }
        $this->pdo->prepare('UPDATE crm_users SET status = \'active\', email_verified_at = UTC_TIMESTAMP() WHERE id = ?')
            ->execute([(int) $userId]);
        $this->pdo->prepare('UPDATE crm_email_verification_tokens SET used_at = UTC_TIMESTAMP() WHERE token_hash = ?')
            ->execute([$hash]);

        return true;
    }
}
