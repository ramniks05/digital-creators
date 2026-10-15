<?php
declare(strict_types=1);

require_once __DIR__ . '/config.php';

final class SaasCredentialVault
{
    public static function encrypt(string $plaintext): string
    {
        $key = self::keyMaterial();
        if ($key === '') {
            throw new RuntimeException('CRM_CREDENTIALS_KEY is not configured.');
        }
        $iv = random_bytes(16);
        $cipher = openssl_encrypt($plaintext, 'aes-256-gcm', $key, OPENSSL_RAW_DATA, $iv, $tag);

        return base64_encode($iv . $tag . $cipher);
    }

    public static function decrypt(string $payload): string
    {
        $key = self::keyMaterial();
        if ($key === '') {
            throw new RuntimeException('CRM_CREDENTIALS_KEY is not configured.');
        }
        $raw = base64_decode($payload, true);
        if ($raw === false || strlen($raw) < 32) {
            throw new RuntimeException('Invalid encrypted payload.');
        }
        $iv = substr($raw, 0, 16);
        $tag = substr($raw, 16, 16);
        $cipher = substr($raw, 32);
        $plain = openssl_decrypt($cipher, 'aes-256-gcm', $key, OPENSSL_RAW_DATA, $iv, $tag);
        if ($plain === false) {
            throw new RuntimeException('Decryption failed.');
        }

        return $plain;
    }

    public static function store(PDO $pdo, int $tenantId, string $type, string $secret, ?int $integrationId = null): void
    {
        $enc = self::encrypt($secret);
        $pdo->prepare(
            'INSERT INTO crm_integration_credentials (tenant_id, integration_id, credential_type, encrypted_payload)
             VALUES (?,?,?,?)'
        )->execute([$tenantId, $integrationId, substr($type, 0, 32), $enc]);
    }

    private static function keyMaterial(): string
    {
        $cfg = saas_config()['credentials_key'];
        if ($cfg === '') {
            return '';
        }

        return hash('sha256', $cfg, true);
    }
}
