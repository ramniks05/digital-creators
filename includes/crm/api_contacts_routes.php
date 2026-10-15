<?php
declare(strict_types=1);

require_once __DIR__ . '/api.php';
require_once __DIR__ . '/tenant.php';
require_once __DIR__ . '/ContactService.php';
require_once __DIR__ . '/contact_tags.php';
require_once __DIR__ . '/contact_custom_fields.php';
require_once __DIR__ . '/contact_import.php';

/**
 * @return bool true if route handled
 */
function crm_api_handle_contacts_routes(string $method, string $path, PDO $pdo): bool
{
    if ($path === '/contacts/export' && $method === 'GET') {
        crm_require_permission($pdo, 'contacts.manage');
        $tenantId = crm_api_tenant_id($pdo);
        $result = crm_export_contacts_csv($pdo, $tenantId, !empty($_GET['include_archived']));
        if (!$result['ok']) {
            crm_api_error('Export failed', 500);
        }
        header('Content-Type: text/csv; charset=UTF-8');
        header('Content-Disposition: attachment; filename="crm-contacts-' . gmdate('Y-m-d') . '.csv"');
        echo $result['csv'];
        exit;
    }

    if ($path === '/contacts/import' && $method === 'POST') {
        crm_require_permission($pdo, 'contacts.manage');
        crm_api_verify_csrf_header();
        $tenantId = crm_api_tenant_id($pdo);
        $body = crm_api_read_json_body();
        $csv = (string) ($body['csv'] ?? '');
        if ($csv === '') {
            crm_api_error('csv field is required', 422);
        }
        $result = crm_import_contacts_csv($pdo, $tenantId, $csv, (int) ($_SESSION['admin_id'] ?? 0));
        if (!$result['ok']) {
            crm_api_error(implode('; ', $result['errors'] ?? ['Import failed']), 422);
        }
        crm_api_json($result);
    }

    if ($path === '/contacts/duplicates' && $method === 'GET') {
        crm_require_permission($pdo, 'contacts.manage');
        $tenantId = crm_api_tenant_id($pdo);
        $svc = new CrmContactService($pdo, $tenantId);
        crm_api_json(['groups' => $svc->findDuplicates()]);
    }

    if ($path === '/contacts/merge' && $method === 'POST') {
        crm_require_permission($pdo, 'contacts.manage');
        crm_api_verify_csrf_header();
        $tenantId = crm_api_tenant_id($pdo);
        $body = crm_api_read_json_body();
        $sourceId = (int) ($body['source_contact_id'] ?? 0);
        $targetId = (int) ($body['target_contact_id'] ?? 0);
        $svc = new CrmContactService($pdo, $tenantId);
        if (!$svc->merge($sourceId, $targetId, (int) ($_SESSION['admin_id'] ?? 0))) {
            crm_api_error('Merge failed', 400);
        }
        crm_audit_log($pdo, 'contact.merge', 'contact', (string) $targetId, [
            'source' => $sourceId,
        ]);
        crm_api_json(['merged' => true, 'target_contact_id' => $targetId]);
    }

    if ($path === '/contacts/tags' && $method === 'GET') {
        crm_require_permission($pdo, 'contacts.manage');
        $tenantId = crm_api_tenant_id($pdo);
        crm_api_json(['tags' => crm_list_tags($pdo, $tenantId)]);
    }

    if ($path === '/contacts/tags' && $method === 'POST') {
        crm_require_permission($pdo, 'contacts.manage');
        crm_api_verify_csrf_header();
        $tenantId = crm_api_tenant_id($pdo);
        $body = crm_api_read_json_body();
        $label = trim((string) ($body['label'] ?? ''));
        if ($label === '') {
            crm_api_error('label is required', 422);
        }
        $tagId = crm_ensure_tag($pdo, $tenantId, $label, (string) ($body['color'] ?? ''));
        crm_api_json(['tag_id' => $tagId]);
    }

    if ($path === '/contacts/custom-fields' && $method === 'GET') {
        crm_require_permission($pdo, 'contacts.manage');
        $tenantId = crm_api_tenant_id($pdo);
        crm_api_json(['fields' => crm_list_custom_field_defs($pdo, $tenantId)]);
    }

    if ($path === '/contacts/custom-fields' && $method === 'POST') {
        crm_require_permission($pdo, 'contacts.manage');
        crm_api_verify_csrf_header();
        $tenantId = crm_api_tenant_id($pdo);
        $body = crm_api_read_json_body();
        $id = crm_create_custom_field(
            $pdo,
            $tenantId,
            (string) ($body['field_key'] ?? ''),
            (string) ($body['label'] ?? ''),
            (string) ($body['field_type'] ?? 'text')
        );
        crm_api_json(['field_id' => $id]);
    }

    if ($path === '/contacts' && $method === 'GET') {
        crm_require_permission($pdo, 'contacts.manage');
        $tenantId = crm_api_tenant_id($pdo);
        $svc = new CrmContactService($pdo, $tenantId);
        $filters = [
            'q' => $_GET['q'] ?? '',
            'tag_id' => $_GET['tag_id'] ?? '',
            'source' => $_GET['source'] ?? '',
            'owner_admin_id' => $_GET['owner_admin_id'] ?? '',
            'consent_status' => $_GET['consent_status'] ?? '',
            'include_archived' => !empty($_GET['include_archived']),
            'archived_only' => !empty($_GET['archived_only']),
        ];
        $rows = $svc->list($filters, (int) ($_GET['limit'] ?? 50), (int) ($_GET['offset'] ?? 0));
        crm_api_json(['contacts' => $rows]);
    }

    if ($path === '/contacts' && $method === 'POST') {
        crm_require_permission($pdo, 'contacts.manage');
        crm_api_verify_csrf_header();
        $tenantId = crm_api_tenant_id($pdo);
        $body = crm_api_read_json_body();
        $waId = trim((string) ($body['wa_id'] ?? ''));
        if ($waId === '') {
            crm_api_error('wa_id is required', 422);
        }
        $svc = new CrmContactService($pdo, $tenantId);
        $id = $svc->create(
            $waId,
            (string) ($body['display_name'] ?? ''),
            (string) ($body['source'] ?? 'manual'),
            isset($body['owner_admin_id']) ? (int) $body['owner_admin_id'] : null
        );
        crm_api_json(['contact_id' => $id]);
    }

    if (preg_match('#^/contacts/(\d+)$#', $path, $m) && $method === 'GET') {
        crm_require_permission($pdo, 'contacts.manage');
        $tenantId = crm_api_tenant_id($pdo);
        $svc = new CrmContactService($pdo, $tenantId);
        $contact = $svc->get((int) $m[1]);
        if ($contact === null) {
            crm_api_error('Contact not found', 404);
        }
        crm_api_json(['contact' => $contact]);
    }

    if (preg_match('#^/contacts/(\d+)$#', $path, $m) && $method === 'PATCH') {
        crm_require_permission($pdo, 'contacts.manage');
        crm_api_verify_csrf_header();
        $tenantId = crm_api_tenant_id($pdo);
        $body = crm_api_read_json_body();
        $svc = new CrmContactService($pdo, $tenantId);
        if (!$svc->update((int) $m[1], $body)) {
            crm_api_error('No changes applied', 400);
        }
        crm_api_json(['updated' => true]);
    }

    if (preg_match('#^/contacts/(\d+)/archive$#', $path, $m) && $method === 'POST') {
        crm_require_permission($pdo, 'contacts.manage');
        crm_api_verify_csrf_header();
        $tenantId = crm_api_tenant_id($pdo);
        $svc = new CrmContactService($pdo, $tenantId);
        if (!$svc->archive((int) $m[1])) {
            crm_api_error('Could not archive', 400);
        }
        crm_api_json(['archived' => true]);
    }

    if (preg_match('#^/contacts/(\d+)/unarchive$#', $path, $m) && $method === 'POST') {
        crm_require_permission($pdo, 'contacts.manage');
        crm_api_verify_csrf_header();
        $tenantId = crm_api_tenant_id($pdo);
        $svc = new CrmContactService($pdo, $tenantId);
        $svc->unarchive((int) $m[1]);
        crm_api_json(['unarchived' => true]);
    }

    if (preg_match('#^/contacts/(\d+)/activity$#', $path, $m) && $method === 'GET') {
        crm_require_permission($pdo, 'contacts.manage');
        $tenantId = crm_api_tenant_id($pdo);
        crm_api_json([
            'activity' => crm_contact_list_activity($pdo, $tenantId, (int) $m[1], (int) ($_GET['limit'] ?? 50)),
        ]);
    }

    if (preg_match('#^/contacts/(\d+)/notes$#', $path, $m) && $method === 'GET') {
        crm_require_permission($pdo, 'contacts.manage');
        $tenantId = crm_api_tenant_id($pdo);
        $svc = new CrmContactService($pdo, $tenantId);
        crm_api_json(['notes' => $svc->listNotes((int) $m[1])]);
    }

    if (preg_match('#^/contacts/(\d+)/notes$#', $path, $m) && $method === 'POST') {
        crm_require_permission($pdo, 'contacts.manage');
        crm_api_verify_csrf_header();
        $tenantId = crm_api_tenant_id($pdo);
        $body = crm_api_read_json_body();
        $svc = new CrmContactService($pdo, $tenantId);
        try {
            $noteId = $svc->addNote((int) $m[1], crm_api_actor_admin_id(), (string) ($body['body'] ?? ''));
        } catch (InvalidArgumentException $e) {
            crm_api_error($e->getMessage(), 422);
        }
        crm_api_json(['note_id' => $noteId]);
    }

    if (preg_match('#^/contacts/(\d+)/messages$#', $path, $m) && $method === 'GET') {
        crm_require_permission($pdo, 'contacts.manage');
        $tenantId = crm_api_tenant_id($pdo);
        $svc = new CrmContactService($pdo, $tenantId);
        crm_api_json(['messages' => $svc->conversationMessages((int) $m[1])]);
    }

    if (preg_match('#^/contacts/(\d+)/tags$#', $path, $m) && $method === 'POST') {
        crm_require_permission($pdo, 'contacts.manage');
        crm_api_verify_csrf_header();
        $tenantId = crm_api_tenant_id($pdo);
        $body = crm_api_read_json_body();
        $contactId = (int) $m[1];
        if (!empty($body['tag_id'])) {
            crm_contact_assign_tag($pdo, $tenantId, $contactId, (int) $body['tag_id'], (int) $_SESSION['admin_id']);
        } elseif (!empty($body['label'])) {
            $tagId = crm_ensure_tag($pdo, $tenantId, (string) $body['label']);
            crm_contact_assign_tag($pdo, $tenantId, $contactId, $tagId, (int) $_SESSION['admin_id']);
        } else {
            crm_api_error('tag_id or label required', 422);
        }
        crm_api_json(['assigned' => true]);
    }

    if (preg_match('#^/contacts/(\d+)/tags/(\d+)$#', $path, $m) && $method === 'DELETE') {
        crm_require_permission($pdo, 'contacts.manage');
        crm_api_verify_csrf_header();
        $tenantId = crm_api_tenant_id($pdo);
        crm_contact_remove_tag($pdo, $tenantId, (int) $m[1], (int) $m[2]);
        crm_api_json(['removed' => true]);
    }

    if (preg_match('#^/contacts/(\d+)/consent$#', $path, $m) && $method === 'POST') {
        crm_require_permission($pdo, 'contacts.manage');
        crm_api_verify_csrf_header();
        $tenantId = crm_api_tenant_id($pdo);
        $body = crm_api_read_json_body();
        $svc = new CrmContactService($pdo, $tenantId);
        $svc->recordConsent(
            (int) $m[1],
            (string) ($body['purpose'] ?? 'customer_service'),
            (string) ($body['source'] ?? 'admin'),
            isset($body['evidence']) ? (string) $body['evidence'] : null
        );
        crm_api_json(['recorded' => true]);
    }

    if (preg_match('#^/contacts/(\d+)/opt-out$#', $path, $m) && $method === 'POST') {
        crm_require_permission($pdo, 'contacts.manage');
        crm_api_verify_csrf_header();
        $tenantId = crm_api_tenant_id($pdo);
        $svc = new CrmContactService($pdo, $tenantId);
        $svc->recordOptOut((int) $m[1], 'admin');
        crm_api_json(['opted_out' => true]);
    }

    return false;
}
