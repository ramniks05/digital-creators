<?php
declare(strict_types=1);

require_once __DIR__ . '/tenant.php';

function crm_contact_activity_ready(PDO $pdo): bool
{
    try {
        $pdo->query('SELECT 1 FROM crm_contact_activity LIMIT 1');

        return true;
    } catch (Throwable $e) {
        return false;
    }
}

function crm_contact_log_activity(
    PDO $pdo,
    int $tenantId,
    int $contactId,
    string $type,
    ?string $summary = null,
    ?array $meta = null,
    ?int $adminId = null
): void {
    if (!crm_contact_activity_ready($pdo)) {
        return;
    }
    if ($adminId === null && !empty($_SESSION['admin_id'])) {
        $adminId = (int) $_SESSION['admin_id'];
    }
    $pdo->prepare(
        'INSERT INTO crm_contact_activity (tenant_id, contact_id, activity_type, summary, meta_json, admin_id)
         VALUES (?,?,?,?,?,?)'
    )->execute([
        $tenantId,
        $contactId,
        substr($type, 0, 64),
        $summary !== null ? substr($summary, 0, 255) : null,
        $meta !== null ? json_encode($meta, JSON_UNESCAPED_UNICODE) : null,
        $adminId,
    ]);
}

/**
 * @return list<array<string, mixed>>
 */
function crm_contact_list_activity(PDO $pdo, int $tenantId, int $contactId, int $limit = 50): array
{
    if (!crm_contact_activity_ready($pdo)) {
        return [];
    }
    $limit = max(1, min(200, $limit));
    $stmt = $pdo->prepare(
        'SELECT * FROM crm_contact_activity
         WHERE tenant_id = ? AND contact_id = ?
         ORDER BY id DESC LIMIT ' . $limit
    );
    $stmt->execute([$tenantId, $contactId]);

    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}
