<?php
declare(strict_types=1);

require_once dirname(__DIR__) . '/auth.php';
require_once __DIR__ . '/tenant.php';
require_once __DIR__ . '/rbac.php';

function crm_api_json(mixed $data, int $status = 200): never
{
    http_response_code($status);
    header('Content-Type: application/json; charset=UTF-8');
    header('X-Content-Type-Options: nosniff');
    echo json_encode(['ok' => $status >= 200 && $status < 300, 'data' => $data], JSON_UNESCAPED_UNICODE);
    exit;
}

function crm_api_error(string $message, int $status = 400, ?string $code = null): never
{
    http_response_code($status);
    header('Content-Type: application/json; charset=UTF-8');
    header('X-Content-Type-Options: nosniff');
    $body = ['ok' => false, 'error' => $message];
    if ($code !== null) {
        $body['code'] = $code;
    }
    echo json_encode($body, JSON_UNESCAPED_UNICODE);
    exit;
}

function crm_api_read_json_body(): array
{
    $raw = file_get_contents('php://input');
    if ($raw === false || trim($raw) === '') {
        return [];
    }
    $data = json_decode($raw, true);

    return is_array($data) ? $data : [];
}

function crm_api_request_path(): string
{
    if (!empty($_GET['path'])) {
        return '/' . trim((string) $_GET['path'], '/');
    }
    $uri = parse_url($_SERVER['REQUEST_URI'] ?? '', PHP_URL_PATH) ?: '';
    $script = (string) ($_SERVER['SCRIPT_NAME'] ?? '');
    if ($script !== '' && str_starts_with($uri, $script)) {
        $suffix = substr($uri, strlen($script));

        return '/' . trim($suffix, '/');
    }
    $base = '/api/v1';
    if (str_starts_with($uri, $base)) {
        return '/' . trim(substr($uri, strlen($base)), '/');
    }

    return '/';
}

function crm_api_require_session(): void
{
    if (!admin_logged_in() && empty($_SESSION['crm_user_id'])) {
        crm_api_error('Unauthorized', 401, 'auth');
    }
}

function crm_api_tenant_id(PDO $pdo): int
{
    crm_api_require_session();
    $tenantId = crm_current_tenant_id();
    if (!empty($_SESSION['admin_id'])) {
        if (!crm_assert_tenant_access($pdo, (int) $_SESSION['admin_id'], $tenantId)) {
            crm_api_error('Forbidden: no tenant access', 403, 'tenant');
        }
    } elseif (!empty($_SESSION['crm_user_id'])) {
        require_once dirname(__DIR__) . '/saas/TenantGuard.php';
        if (!CrmTenantGuard::userHasTenantAccess($pdo, (int) $_SESSION['crm_user_id'], $tenantId)) {
            crm_api_error('Forbidden: no tenant access', 403, 'tenant');
        }
    } else {
        crm_api_error('Unauthorized', 401, 'auth');
    }

    return $tenantId;
}

function crm_api_verify_csrf_header(): void
{
    $token = (string) ($_SERVER['HTTP_X_CSRF_TOKEN'] ?? $_SERVER['HTTP_X_CSRF-TOKEN'] ?? '');
    if ($token === '' || !verify_csrf($token)) {
        crm_api_error('Invalid CSRF token', 403, 'csrf');
    }
}
