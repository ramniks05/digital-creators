<?php
declare(strict_types=1);

require_once __DIR__ . '/tenant.php';
require_once __DIR__ . '/contact_activity.php';

function crm_contact_tags_ready(PDO $pdo): bool
{
    try {
        $pdo->query('SELECT 1 FROM crm_contact_tags LIMIT 1');

        return true;
    } catch (Throwable $e) {
        return false;
    }
}

function crm_tag_slugify(string $label): string
{
    $slug = strtolower(trim($label));
    $slug = preg_replace('/[^a-z0-9]+/', '-', $slug) ?? '';
    $slug = trim($slug, '-');

    return substr($slug !== '' ? $slug : 'tag', 0, 64);
}

function crm_ensure_tag(PDO $pdo, int $tenantId, string $label, ?string $color = null): int
{
    $label = trim($label);
    if ($label === '') {
        throw new InvalidArgumentException('Tag label is required.');
    }
    $slug = crm_tag_slugify($label);
    $pdo->prepare(
        'INSERT INTO crm_contact_tags (tenant_id, slug, label, color)
         VALUES (?,?,?,?)
         ON DUPLICATE KEY UPDATE label = VALUES(label)'
    )->execute([
        $tenantId,
        $slug,
        substr($label, 0, 120),
        $color !== null && preg_match('/^#[0-9a-fA-F]{6}$/', $color) ? $color : '#64748b',
    ]);
    $stmt = $pdo->prepare('SELECT id FROM crm_contact_tags WHERE tenant_id = ? AND slug = ? LIMIT 1');
    $stmt->execute([$tenantId, $slug]);

    return (int) $stmt->fetchColumn();
}

/**
 * @return list<array<string, mixed>>
 */
function crm_list_tags(PDO $pdo, int $tenantId): array
{
    if (!crm_contact_tags_ready($pdo)) {
        return [];
    }
    $stmt = $pdo->prepare('SELECT * FROM crm_contact_tags WHERE tenant_id = ? ORDER BY label ASC');
    $stmt->execute([$tenantId]);

    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

/**
 * @return list<array<string, mixed>>
 */
function crm_contact_tags(PDO $pdo, int $tenantId, int $contactId): array
{
    if (!crm_contact_tags_ready($pdo)) {
        return [];
    }
    $stmt = $pdo->prepare(
        'SELECT t.* FROM crm_contact_tags t
         INNER JOIN crm_contact_tag_assignments a ON a.tag_id = t.id
         WHERE t.tenant_id = ? AND a.contact_id = ?
         ORDER BY t.label ASC'
    );
    $stmt->execute([$tenantId, $contactId]);

    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

function crm_contact_assign_tag(PDO $pdo, int $tenantId, int $contactId, int $tagId, ?int $adminId = null): bool
{
    if (!crm_contact_tags_ready($pdo)) {
        return false;
    }
    $check = $pdo->prepare('SELECT 1 FROM crm_contact_tags WHERE id = ? AND tenant_id = ?');
    $check->execute([$tagId, $tenantId]);
    if (!$check->fetchColumn()) {
        return false;
    }
    $stmt = $pdo->prepare(
        'INSERT IGNORE INTO crm_contact_tag_assignments (contact_id, tag_id, assigned_by_admin_id)
         VALUES (?,?,?)'
    );
    $stmt->execute([$contactId, $tagId, $adminId]);
    if ($stmt->rowCount() > 0) {
        crm_contact_log_activity($pdo, $tenantId, $contactId, 'tag_added', 'Tag assigned', ['tag_id' => $tagId], $adminId);
    }

    return true;
}

function crm_contact_remove_tag(PDO $pdo, int $tenantId, int $contactId, int $tagId): bool
{
    $stmt = $pdo->prepare(
        'DELETE a FROM crm_contact_tag_assignments a
         INNER JOIN crm_contact_tags t ON t.id = a.tag_id
         WHERE a.contact_id = ? AND a.tag_id = ? AND t.tenant_id = ?'
    );
    $stmt->execute([$contactId, $tagId, $tenantId]);
    if ($stmt->rowCount() > 0) {
        crm_contact_log_activity($pdo, $tenantId, $contactId, 'tag_removed', 'Tag removed', ['tag_id' => $tagId]);
    }

    return $stmt->rowCount() > 0;
}

function crm_tags_backfill_from_json(PDO $pdo, int $tenantId): int
{
    if (!crm_contact_tags_ready($pdo)) {
        return 0;
    }
    $rows = $pdo->prepare('SELECT id, tags_json FROM crm_contacts WHERE tenant_id = ? AND tags_json IS NOT NULL');
    $rows->execute([$tenantId]);
    $count = 0;
    foreach ($rows->fetchAll(PDO::FETCH_ASSOC) as $row) {
        $tags = json_decode((string) ($row['tags_json'] ?? ''), true);
        if (!is_array($tags)) {
            continue;
        }
        $contactId = (int) $row['id'];
        foreach ($tags as $label) {
            if (!is_string($label) || trim($label) === '') {
                continue;
            }
            try {
                $tagId = crm_ensure_tag($pdo, $tenantId, $label);
                crm_contact_assign_tag($pdo, $tenantId, $contactId, $tagId, null);
                $count++;
            } catch (Throwable $e) {
                continue;
            }
        }
    }

    return $count;
}
