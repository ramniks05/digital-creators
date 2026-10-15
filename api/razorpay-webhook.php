<?php
/**
 * Razorpay billing webhooks.
 * URL: https://yourdomain.com/api/razorpay-webhook.php
 *
 * Configure in Razorpay Dashboard → Webhooks with secret RAZORPAY_WEBHOOK_SECRET.
 */
declare(strict_types=1);

require_once dirname(__DIR__) . '/includes/db.php';
require_once dirname(__DIR__) . '/includes/saas/billing/SubscriptionBillingService.php';

if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') {
    http_response_code(405);
    header('Allow: POST');
    exit;
}

$raw = file_get_contents('php://input');
if ($raw === false) {
    http_response_code(400);
    exit;
}

$sig = (string) ($_SERVER['HTTP_X_RAZORPAY_SIGNATURE'] ?? '');

$svc = new SaasSubscriptionBillingService(db());
$result = $svc->handleWebhook($raw, $sig !== '' ? $sig : null);

if (!$result['ok']) {
    error_log('Razorpay webhook: ' . ($result['error'] ?? 'failed'));
    http_response_code($result['error'] === 'Invalid webhook signature' ? 403 : 500);
    header('Content-Type: application/json');
    echo json_encode(['ok' => false]);
    exit;
}

http_response_code(200);
header('Content-Type: application/json');
echo json_encode(['ok' => true]);
