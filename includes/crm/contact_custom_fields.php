<?php
declare(strict_types=1);

require_once __DIR__ . '/tenant.php';

function crm_custom_fields_ready(PDO $pdo): bool
{
    try {
        $pdo->query('SELECT 1 FROM crm_contact_custom_fields LIMIT 1');

        return true;
    } catch (Throwable $e) {
        return false;
    }
}

/**
 * @return list<array<string, mixed>>
 */
function crm_list_custom_field_defs(PDO $pdo, int $tenantId): array
{
    if (!crm_custom_fields_ready($pdo)) {
        return [];
    }
    $stmt = $pdo->prepare(
        'SELECT * FROM crm_contact_custom_fields WHERE tenant_id = ? ORDER BY sort_order ASC, label ASC'
    );
    $stmt->execute([$tenantId]);

    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

function crm_create_custom_field(PDO $pdo, int $tenantId, string $key, string $label, string $type = 'text'): int
{
    $key = preg_replace('/[^a-z0-9_]/', '_', strtolower(trim($key))) ?? '';
    if ($key === '') {
        throw new InvalidArgumentException('Field key is required.');
    }
    $type = in_array($type, ['text', 'number', 'date', 'boolean', 'select'], true) ? $type : 'text';
    $pdo->prepare(
        'INSERT INTO crm_contact_custom_fields (tenant_id, field_key, label, field_type)
         VALUES (?,?,?,?)
         ON DUPLICATE KEY UPDATE label = VALUES(label), field_type = VALUES(field_type)'
    )->execute([$tenantId, substr($key, 0, 64), substr($label, 0, 120), $type]);
    $stmt = $pdo->prepare('SELECT id FROM crm_contact_custom_fields WHERE tenant_id = ? AND field_key = ?');
    $stmt->execute([$tenantId, $key]);

    return (int) $stmt->fetchColumn();
}

/**
 * @param array<string, string|null> $values keyed by field_key
 */
function crm_save_contact_custom_values(PDO $pdo, int $tenantId, int $contactId, array $values): void
{
    if (!crm_custom_fields_ready($pdo)) {
        return;
    }
    $defs = crm_list_custom_field_defs($pdo, $tenantId);
    $byKey = [];
    foreach ($defs as $def) {
        $byKey[(string) $def['field_key']] = (int) $def['id'];
    }
    $stmt = $pdo->prepare(
        'INSERT INTO crm_contact_custom_values (contact_id, field_id, value_text)
         VALUES (?,?,?)
         ON DUPLICATE KEY UPDATE value_text = VALUES(value_text), updated_at = CURRENT_TIMESTAMP'
    );
    foreach ($values as $key => $val) {
        if (!isset($byKey[$key])) {
            continue;
        }
        $stmt->execute([$contactId, $byKey[$key], $val !== null ? substr((string) $val, 0, 65535) : null]);
    }
}

/**
 * @return array<string, string|null>
 */
function crm_contact_custom_values(PDO $pdo, int $tenantId, int $contactId): array
{
    if (!crm_custom_fields_ready($pdo)) {
        return [];
    }
    $stmt = $pdo->prepare(
        'SELECT f.field_key, v.value_text
         FROM crm_contact_custom_values v
         INNER JOIN crm_contact_custom_fields f ON f.id = v.field_id
         WHERE f.tenant_id = ? AND v.contact_id = ?'
    );
    $stmt->execute([$tenantId, $contactId]);
    $out = [];
    foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
        $out[(string) $row['field_key']] = $row['value_text'];
    }

    return $out;
}
