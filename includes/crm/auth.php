<?php
declare(strict_types=1);

require_once __DIR__ . '/tenant.php';

function crm_login_attempts_ready(PDO $pdo): bool
{
    try {
        $pdo->query('SELECT 1 FROM crm_login_attempts LIMIT 1');

        return true;
    } catch (Throwable $e) {
        return false;
    }
}

function crm_login_is_rate_limited(PDO $pdo, string $username, string $ip): bool
{
    if (!crm_login_attempts_ready($pdo)) {
        return false;
    }
    $username = substr(trim($username), 0, 80);
    $since = gmdate('Y-m-d H:i:s', time() - 900);
    $stmt = $pdo->prepare(
        'SELECT COUNT(*) FROM crm_login_attempts
         WHERE username = ? AND success = 0 AND created_at >= ?'
    );
    $stmt->execute([$username, $since]);
    if ((int) $stmt->fetchColumn() >= 8) {
        return true;
    }

    $stmtIp = $pdo->prepare(
        'SELECT COUNT(*) FROM crm_login_attempts
         WHERE ip_address = ? AND success = 0 AND created_at >= ?'
    );
    $stmtIp->execute([substr($ip, 0, 45), $since]);

    return (int) $stmtIp->fetchColumn() >= 20;
}

function crm_record_login_attempt(PDO $pdo, string $username, string $ip, bool $success): void
{
    if (!crm_login_attempts_ready($pdo)) {
        return;
    }
    $pdo->prepare(
        'INSERT INTO crm_login_attempts (username, ip_address, success) VALUES (?,?,?)'
    )->execute([
        substr(trim($username), 0, 80),
        substr($ip, 0, 45),
        $success ? 1 : 0,
    ]);
}
