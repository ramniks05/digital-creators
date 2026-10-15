<?php
declare(strict_types=1);

require_once dirname(__DIR__) . '/crm/api.php';
require_once __DIR__ . '/RegistrationService.php';
require_once __DIR__ . '/UserAuth.php';
require_once __DIR__ . '/TenantGuard.php';
require_once __DIR__ . '/MetaEmbeddedSignupService.php';
require_once __DIR__ . '/UsageMeter.php';
require_once __DIR__ . '/billing/SubscriptionBillingService.php';

/**
 * @return bool true if handled
 */
function saas_api_handle_routes(string $method, string $path, PDO $pdo): bool
{
    if ($path === '/saas/register' && $method === 'POST') {
        $body = crm_api_read_json_body();
        $svc = new SaasRegistrationService($pdo);
        $result = $svc->register(
            (string) ($body['email'] ?? ''),
            (string) ($body['password'] ?? ''),
            (string) ($body['full_name'] ?? ''),
            (string) ($body['company_name'] ?? ''),
            isset($body['workspace_slug']) ? (string) $body['workspace_slug'] : null
        );
        if (!$result['ok']) {
            crm_api_error((string) ($result['error'] ?? 'Registration failed'), 422);
        }
        $response = [
            'user_id' => $result['user_id'],
            'tenant_id' => $result['tenant_id'],
            'message' => 'Account created. Verify your email to activate.',
        ];
        if (filter_var(getenv('SAAS_EXPOSE_VERIFY_TOKEN') ?: '0', FILTER_VALIDATE_BOOLEAN)) {
            $response['verification_token_dev_only'] = $result['verification_token'] ?? null;
        }
        crm_api_json($response, 201);
    }

    if ($path === '/saas/auth/verify-email' && $method === 'POST') {
        $body = crm_api_read_json_body();
        $token = trim((string) ($body['token'] ?? ''));
        if ($token === '') {
            crm_api_error('token is required', 422);
        }
        $svc = new SaasRegistrationService($pdo);
        if (!$svc->verifyEmail($token)) {
            crm_api_error('Invalid or expired token', 400);
        }
        crm_api_json(['verified' => true]);
    }

    if ($path === '/saas/auth/login' && $method === 'POST') {
        $body = crm_api_read_json_body();
        if (!SaasUserAuth::login($pdo, (string) ($body['email'] ?? ''), (string) ($body['password'] ?? ''))) {
            crm_api_error('Invalid credentials', 401);
        }
        crm_api_json([
            'user_id' => SaasUserAuth::currentUserId(),
            'tenant_id' => crm_current_tenant_id(),
            'csrf_token' => csrf_token(),
            'workspaces' => CrmTenantGuard::listUserTenants($pdo, (int) $_SESSION['crm_user_id']),
        ]);
    }

    if ($path === '/saas/auth/logout' && $method === 'POST') {
        crm_api_verify_csrf_header();
        SaasUserAuth::logout();
        crm_api_json(['logged_out' => true]);
    }

    if ($path === '/saas/auth/me' && $method === 'GET') {
        crm_api_require_session();
        if (SaasUserAuth::loggedIn()) {
            $uid = (int) $_SESSION['crm_user_id'];
            crm_api_json([
                'type' => 'saas_user',
                'user_id' => $uid,
                'email' => (string) ($_SESSION['crm_user_email'] ?? ''),
                'tenant_id' => crm_current_tenant_id(),
                'role' => (string) ($_SESSION['crm_role_slug'] ?? ''),
                'workspaces' => CrmTenantGuard::listUserTenants($pdo, $uid),
                'csrf_token' => csrf_token(),
            ]);
        }
        crm_api_json([
            'type' => 'admin',
            'admin_id' => (int) $_SESSION['admin_id'],
            'tenant_id' => crm_current_tenant_id(),
            'csrf_token' => csrf_token(),
        ]);
    }

    if ($path === '/saas/workspace/switch' && $method === 'POST') {
        crm_api_require_session();
        crm_api_verify_csrf_header();
        if (!SaasUserAuth::loggedIn()) {
            crm_api_error('SaaS user session required', 403);
        }
        $body = crm_api_read_json_body();
        $tenantId = (int) ($body['tenant_id'] ?? 0);
        if (!SaasUserAuth::switchWorkspace($pdo, (int) $_SESSION['crm_user_id'], $tenantId)) {
            crm_api_error('Cannot switch to this workspace', 403);
        }
        crm_api_json(['tenant_id' => $tenantId, 'role' => (string) ($_SESSION['crm_role_slug'] ?? '')]);
    }

    if ($path === '/saas/onboarding' && $method === 'GET') {
        crm_api_require_session();
        $tenantId = crm_api_tenant_id($pdo);
        $stmt = $pdo->prepare('SELECT * FROM crm_onboarding_progress WHERE tenant_id = ? LIMIT 1');
        $stmt->execute([$tenantId]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC) ?: [];
        crm_api_json(['onboarding' => $row]);
    }

    if ($path === '/saas/plans' && $method === 'GET') {
        $plans = $pdo->query(
            'SELECT slug, name, billing_interval, price_cents, currency, trial_days, limits_json
             FROM crm_subscription_plans WHERE is_active = 1 ORDER BY sort_order ASC'
        )->fetchAll(PDO::FETCH_ASSOC);
        crm_api_json(['plans' => $plans]);
    }

    if ($path === '/saas/meta/embedded-signup/config' && $method === 'GET') {
        crm_api_require_session();
        $tenantId = crm_api_tenant_id($pdo);
        $uid = SaasUserAuth::currentUserId() ?? 0;
        $meta = new MetaEmbeddedSignupService($pdo, $tenantId, $uid);
        $cfg = $meta->clientConfig();
        if (!$cfg['ok']) {
            crm_api_error((string) ($cfg['error'] ?? 'Not configured'), 503, 'meta_prerequisite');
        }
        crm_api_json($cfg);
    }

    if ($path === '/saas/meta/embedded-signup/start' && $method === 'POST') {
        crm_api_require_session();
        crm_api_verify_csrf_header();
        $tenantId = crm_api_tenant_id($pdo);
        $uid = SaasUserAuth::currentUserId() ?? 0;
        $meta = new MetaEmbeddedSignupService($pdo, $tenantId, $uid);
        crm_api_json($meta->startSession());
    }

    if ($path === '/saas/usage' && $method === 'GET') {
        crm_api_require_session();
        $tenantId = crm_api_tenant_id($pdo);
        $meter = new SaasUsageMeter($pdo, $tenantId);
        crm_api_json(['usage' => $meter->periodTotals()]);
    }

    if ($path === '/saas/billing/subscription' && $method === 'GET') {
        crm_api_require_session();
        $tenantId = crm_api_tenant_id($pdo);
        $billing = new SaasSubscriptionBillingService($pdo);
        $summary = $billing->getSubscriptionSummary($tenantId);
        if (!$summary['ok']) {
            crm_api_error((string) ($summary['error'] ?? 'No subscription'), 404);
        }
        crm_api_json(['subscription' => $summary['subscription']]);
    }

    if ($path === '/saas/billing/history' && $method === 'GET') {
        crm_api_require_session();
        $tenantId = crm_api_tenant_id($pdo);
        $billing = new SaasSubscriptionBillingService($pdo);
        crm_api_json(['transactions' => $billing->billingHistory($tenantId)]);
    }

    if ($path === '/saas/billing/checkout' && $method === 'POST') {
        crm_api_require_session();
        crm_api_verify_csrf_header();
        $tenantId = crm_api_tenant_id($pdo);
        $body = crm_api_read_json_body();
        $planSlug = (string) ($body['plan_slug'] ?? 'growth');
        $interval = (string) ($body['interval'] ?? 'month');
        $billing = new SaasSubscriptionBillingService($pdo);
        $result = $billing->createSubscriptionCheckout($tenantId, $planSlug, $interval);
        if (!$result['ok']) {
            crm_api_error((string) ($result['error'] ?? 'Checkout failed'), 422);
        }
        crm_api_json(['checkout' => $result['checkout']]);
    }

    if ($path === '/saas/billing/confirm' && $method === 'POST') {
        crm_api_require_session();
        crm_api_verify_csrf_header();
        $tenantId = crm_api_tenant_id($pdo);
        $body = crm_api_read_json_body();
        $billing = new SaasSubscriptionBillingService($pdo);
        $result = $billing->confirmSubscriptionPayment(
            $tenantId,
            (string) ($body['razorpay_payment_id'] ?? ''),
            (string) ($body['razorpay_subscription_id'] ?? ''),
            (string) ($body['razorpay_signature'] ?? '')
        );
        if (!$result['ok']) {
            crm_api_error((string) ($result['error'] ?? 'Confirmation failed'), 422);
        }
        crm_api_json(['activated' => true]);
    }

    if ($path === '/saas/billing/config' && $method === 'GET') {
        crm_api_require_session();
        crm_api_tenant_id($pdo);
        require_once __DIR__ . '/config.php';
        crm_api_json([
            'razorpay_configured' => saas_razorpay_configured(),
            'key_id' => saas_razorpay_configured() ? saas_config()['razorpay_key_id'] : null,
        ]);
    }

    return false;
}
