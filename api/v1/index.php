<?php
/**
 * CRM REST API v1 (session-authenticated; same-origin with admin cookies).
 *
 * Base URL: /api/v1/index.php  or /api/v1/... with Apache rewrite.
 */
declare(strict_types=1);

require_once dirname(__DIR__, 2) . '/includes/db.php';
require_once dirname(__DIR__, 2) . '/includes/crm/api.php';
require_once dirname(__DIR__, 2) . '/includes/crm/conversations.php';
require_once dirname(__DIR__, 2) . '/includes/crm/messages.php';
require_once dirname(__DIR__, 2) . '/includes/crm/contacts.php';
require_once dirname(__DIR__, 2) . '/includes/crm/api_contacts_routes.php';
require_once dirname(__DIR__, 2) . '/includes/saas/api_saas_routes.php';
require_once dirname(__DIR__, 2) . '/includes/crm/OutboundJobService.php';
require_once dirname(__DIR__, 2) . '/includes/whatsapp_cloud.php';
require_once dirname(__DIR__, 2) . '/includes/whatsapp_send.php';

$method = strtoupper((string) ($_SERVER['REQUEST_METHOD'] ?? 'GET'));
$path = crm_api_request_path();
$pdo = db();

try {
    if (saas_api_handle_routes($method, $path, $pdo)) {
        exit;
    }

    if ($path === '/auth/login' && $method === 'POST') {
        $body = crm_api_read_json_body();
        $username = trim((string) ($body['username'] ?? ''));
        $password = (string) ($body['password'] ?? '');
        if ($username === '' || $password === '') {
            crm_api_error('Username and password are required.', 422);
        }
        if (!attempt_login($username, $password)) {
            crm_api_error('Invalid credentials or rate limited.', 401, 'login_failed');
        }
        crm_api_json([
            'admin_id' => (int) $_SESSION['admin_id'],
            'username' => (string) ($_SESSION['admin_username'] ?? ''),
            'tenant_id' => crm_current_tenant_id(),
            'role' => (string) ($_SESSION['crm_role_slug'] ?? ''),
            'csrf_token' => csrf_token(),
        ]);
    }

    if ($path === '/auth/logout' && $method === 'POST') {
        crm_api_require_session();
        crm_api_verify_csrf_header();
        admin_logout();
        crm_api_json(['logged_out' => true]);
    }

    if ($path === '/auth/me' && $method === 'GET') {
        crm_api_require_session();
        $tenantId = crm_api_tenant_id($pdo);
        crm_api_json([
            'admin_id' => crm_api_actor_admin_id(),
            'crm_user_id' => (int) ($_SESSION['crm_user_id'] ?? 0),
            'username' => (string) ($_SESSION['admin_username'] ?? $_SESSION['crm_user_email'] ?? ''),
            'tenant_id' => $tenantId,
            'role' => (string) ($_SESSION['crm_role_slug'] ?? ''),
            'csrf_token' => csrf_token(),
        ]);
    }

    if ($path === '/conversations' && $method === 'GET') {
        crm_require_permission($pdo, 'inbox.view');
        $tenantId = crm_api_tenant_id($pdo);
        $status = isset($_GET['status']) ? (string) $_GET['status'] : null;
        $assigned = isset($_GET['assigned']) ? (string) $_GET['assigned'] : '';
        $search = isset($_GET['q']) ? (string) $_GET['q'] : null;
        $assignedParam = null;
        if ($assigned === 'unassigned') {
            $assignedParam = 0;
        } elseif ($assigned === 'me') {
            $assignedParam = crm_api_actor_admin_id();
        } elseif ($assigned !== '' && ctype_digit($assigned)) {
            $assignedParam = (int) $assigned;
        }
        $rows = crm_list_conversations(
            $pdo,
            $tenantId,
            $status !== '' ? $status : null,
            $assignedParam,
            (int) ($_GET['limit'] ?? 100),
            $search
        );
        crm_api_json(['conversations' => $rows]);
    }

    if (preg_match('#^/conversations/(\d+)$#', $path, $m) && $method === 'GET') {
        crm_require_permission($pdo, 'inbox.view');
        $tenantId = crm_api_tenant_id($pdo);
        $conv = crm_get_conversation($pdo, $tenantId, (int) $m[1]);
        if ($conv === null) {
            crm_api_error('Conversation not found', 404);
        }
        crm_update_conversation($pdo, $tenantId, (int) $m[1], null, null, true);
        crm_api_json(['conversation' => $conv]);
    }

    if (preg_match('#^/conversations/(\d+)/messages$#', $path, $m) && $method === 'GET') {
        crm_require_permission($pdo, 'inbox.view');
        $tenantId = crm_api_tenant_id($pdo);
        $convId = (int) $m[1];
        if (crm_get_conversation($pdo, $tenantId, $convId) === null) {
            crm_api_error('Conversation not found', 404);
        }
        $before = isset($_GET['before_id']) ? (int) $_GET['before_id'] : null;
        $messages = crm_list_messages($pdo, $tenantId, $convId, (int) ($_GET['limit'] ?? 50), $before);
        crm_api_json(['messages' => $messages]);
    }

    if (preg_match('#^/conversations/(\d+)/messages$#', $path, $m) && $method === 'POST') {
        crm_require_permission($pdo, 'inbox.reply');
        crm_api_verify_csrf_header();
        $tenantId = crm_api_tenant_id($pdo);
        $convId = (int) $m[1];
        $conv = crm_get_conversation($pdo, $tenantId, $convId);
        if ($conv === null) {
            crm_api_error('Conversation not found', 404);
        }
        $body = crm_api_read_json_body();
        $text = trim((string) ($body['text'] ?? ''));
        $template = trim((string) ($body['template'] ?? ''));
        $adminId = crm_api_actor_admin_id();
        $peer = (string) ($conv['peer_wa_id'] ?? '');
        $idempotency = isset($body['idempotency_key']) ? (string) $body['idempotency_key'] : null;
        $jobs = new CrmOutboundJobService($pdo, $tenantId);
        if ($template !== '') {
            crm_require_permission($pdo, 'templates.send');
            $result = $jobs->enqueueTemplate($adminId, $peer, $template, (string) ($body['language'] ?? ''), null, $idempotency);
        } else {
            if ($text === '') {
                crm_api_error('Message text or template is required.', 422);
            }
            $result = $jobs->enqueueText($adminId, $peer, $text, $idempotency);
        }
        if (!$result['ok']) {
            crm_api_error((string) ($result['error'] ?? 'Send blocked'), 422, 'policy');
        }
        $jobs->processPendingJobs(1);
        crm_audit_log($pdo, 'message.send', 'conversation', (string) $convId, ['job_id' => $result['job_id'] ?? null]);
        crm_api_json(['job_id' => $result['job_id'] ?? null]);
    }

    if (preg_match('#^/conversations/(\d+)/assignment$#', $path, $m) && $method === 'PATCH') {
        crm_require_permission($pdo, 'conversations.assign');
        crm_api_verify_csrf_header();
        $tenantId = crm_api_tenant_id($pdo);
        $body = crm_api_read_json_body();
        $assign = array_key_exists('assigned_admin_id') ? (int) $body['assigned_admin_id'] : null;
        if (!crm_update_conversation($pdo, $tenantId, (int) $m[1], null, $assign)) {
            crm_api_error('Could not update assignment', 400);
        }
        crm_api_json(['updated' => true]);
    }

    if (preg_match('#^/conversations/(\d+)/status$#', $path, $m) && $method === 'PATCH') {
        crm_require_permission($pdo, 'conversations.assign');
        crm_api_verify_csrf_header();
        $tenantId = crm_api_tenant_id($pdo);
        $body = crm_api_read_json_body();
        $status = (string) ($body['status'] ?? '');
        if (!crm_update_conversation($pdo, $tenantId, (int) $m[1], $status, null)) {
            crm_api_error('Could not update status', 400);
        }
        crm_api_json(['updated' => true]);
    }

    if (crm_api_handle_contacts_routes($method, $path, $pdo)) {
        exit;
    }

    if ($path === '/templates' && $method === 'GET') {
        crm_require_permission($pdo, 'templates.send');
        crm_api_tenant_id($pdo);
        $result = whatsapp_fetch_message_templates_result();
        $list = [];
        foreach ($result['templates'] ?? [] as $tpl) {
            if (strtoupper((string) ($tpl['status'] ?? '')) === 'APPROVED') {
                $list[] = $tpl;
            }
        }
        crm_api_json(['templates' => $list, 'fetch_error' => $result['error'] ?? null]);
    }

    if ($path === '/whatsapp/status' && $method === 'GET') {
        crm_require_permission($pdo, 'inbox.view');
        crm_api_tenant_id($pdo);
        $cfg = whatsapp_cloud_config();
        crm_api_json([
            'configured' => $cfg['access_token'] !== '' && $cfg['phone_number_id'] !== '',
            'phone_number_id' => $cfg['phone_number_id'] !== '' ? $cfg['phone_number_id'] : null,
            'webhook_verify_token_set' => whatsapp_webhook_verify_token_configured(),
            'app_secret_set' => whatsapp_app_secret_configured(),
        ]);
    }

    crm_api_error('Not found', 404);
} catch (Throwable $e) {
    error_log('CRM API error: ' . $e->getMessage());
    crm_api_error('Server error', 500);
}
