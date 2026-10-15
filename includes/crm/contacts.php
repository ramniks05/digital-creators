<?php
declare(strict_types=1);

require_once __DIR__ . '/ContactService.php';

function crm_list_contacts(PDO $pdo, int $tenantId, ?string $search = null, int $limit = 50): array
{
    $svc = new CrmContactService($pdo, $tenantId);

    return $svc->list(['q' => $search], $limit);
}

function crm_get_contact(PDO $pdo, int $tenantId, int $contactId): ?array
{
    return (new CrmContactService($pdo, $tenantId))->get($contactId);
}

/**
 * @param array<string, mixed> $fields
 */
function crm_update_contact(PDO $pdo, int $tenantId, int $contactId, array $fields): bool
{
    return (new CrmContactService($pdo, $tenantId))->update($contactId, $fields);
}

function crm_create_contact(PDO $pdo, int $tenantId, string $waId, ?string $displayName = null): ?int
{
    return (new CrmContactService($pdo, $tenantId))->create($waId, $displayName, 'manual');
}
