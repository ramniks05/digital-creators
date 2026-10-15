<?php
declare(strict_types=1);

/**
 * Meta Embedded Signup — server-side scaffold only.
 *
 * External prerequisites (NOT verified by this codebase):
 * - Meta App with WhatsApp product
 * - Business verification as required by Meta
 * - Embedded Signup configuration in Meta Developer Console
 * - App Review for required permissions (e.g. whatsapp_business_management, whatsapp_business_messaging)
 * - Tech Provider / Solution Partner eligibility if onboarding customer WABAs
 *
 * Official docs: https://developers.facebook.com/docs/whatsapp/embedded-signup
 */
require_once __DIR__ . '/config.php';

final class MetaEmbeddedSignupService
{
    public function __construct(private PDO $pdo, private int $tenantId, private int $userId)
    {
    }

    /**
     * @return array{ok:bool, config?:array, error?:string, blockers?:list<string>}
     */
    public function clientConfig(): array
    {
        $cfg = saas_config();
        $blockers = [];
        if ($cfg['meta_app_id'] === '') {
            $blockers[] = 'META_APP_ID is not configured on the server.';
        }
        if ($cfg['meta_config_id'] === '') {
            $blockers[] = 'META_EMBEDDED_SIGNUP_CONFIG_ID is not configured.';
        }
        if ($blockers !== []) {
            return ['ok' => false, 'error' => 'Embedded Signup is not configured.', 'blockers' => $blockers];
        }

        return [
            'ok' => true,
            'config' => [
                'app_id' => $cfg['meta_app_id'],
                'config_id' => $cfg['meta_config_id'],
                'note' => 'Load Meta JS SDK only on HTTPS authorized domains configured in Meta App settings.',
            ],
        ];
    }

    /**
     * Start onboarding — stores CSRF state server-side (no tokens in browser storage).
     *
     * @return array{ok:bool, state?:string, session_id?:int}
     */
    public function startSession(): array
    {
        $state = bin2hex(random_bytes(24));
        $hash = hash('sha256', $state);
        $this->pdo->prepare(
            'INSERT INTO crm_onboarding_sessions (tenant_id, user_id, state_token_hash, expires_at)
             VALUES (?,?,?, DATE_ADD(UTC_TIMESTAMP(), INTERVAL 2 HOUR))'
        )->execute([$this->tenantId, $this->userId, $hash]);

        return ['ok' => true, 'state' => $state, 'session_id' => (int) $this->pdo->lastInsertId()];
    }

    /**
     * @param array<string, mixed> $payload Raw callback payload from your frontend (code, waba_id, phone_number_id, etc.)
     * @return array{ok:bool, error?:string}
     */
    public function completeSession(string $state, array $payload): array
    {
        $hash = hash('sha256', $state);
        $stmt = $this->pdo->prepare(
            'SELECT id FROM crm_onboarding_sessions
             WHERE state_token_hash = ? AND tenant_id = ? AND status = \'started\'
               AND expires_at > UTC_TIMESTAMP() LIMIT 1'
        );
        $stmt->execute([$hash, $this->tenantId]);
        $sessionId = $stmt->fetchColumn();
        if ($sessionId === false) {
            return ['ok' => false, 'error' => 'Invalid or expired onboarding session.'];
        }

        $wabaId = trim((string) ($payload['waba_id'] ?? ''));
        $phoneNumberId = trim((string) ($payload['phone_number_id'] ?? ''));
        if ($wabaId === '' || $phoneNumberId === '') {
            $this->markSessionFailed((int) $sessionId, 'Missing waba_id or phone_number_id in callback.');

            return ['ok' => false, 'error' => 'Incomplete Meta callback payload.'];
        }

        // Server-side token exchange must be implemented when Meta app credentials and review are in place.
        // Do NOT trust long-lived tokens from the browser.
        return [
            'ok' => false,
            'error' => 'Token exchange not implemented. Complete Meta App Review and implement server-side OAuth/code exchange per current Meta docs.',
        ];
    }

    private function markSessionFailed(int $sessionId, string $error): void
    {
        $this->pdo->prepare(
            'UPDATE crm_onboarding_sessions SET status = \'failed\', meta_session_data_json = ? WHERE id = ?'
        )->execute([json_encode(['error' => substr($error, 0, 500)]), $sessionId]);
    }
}
