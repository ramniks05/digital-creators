<?php
/**
 * Local HMAC checks (no live Razorpay API). Run: php tests/razorpay_signature_test.php
 */
declare(strict_types=1);

putenv('RAZORPAY_KEY_ID=rzp_test_dummy');
putenv('RAZORPAY_KEY_SECRET=test_secret_123');
putenv('RAZORPAY_WEBHOOK_SECRET=whsec_test');

require_once dirname(__DIR__) . '/includes/saas/billing/RazorpayGateway.php';

$gw = new RazorpayGateway();
$fail = 0;

$payId = 'pay_test123';
$subId = 'sub_test456';
$sig = hash_hmac('sha256', $payId . '|' . $subId, 'test_secret_123');
if (!$gw->verifySubscriptionSignature($payId, $subId, $sig)) {
    echo "FAIL: subscription signature\n";
    $fail++;
} else {
    echo "OK: subscription signature\n";
}

$body = '{"event":"payment.captured"}';
$whSig = hash_hmac('sha256', $body, 'whsec_test');
if (!$gw->verifyWebhookSignature($body, $whSig)) {
    echo "FAIL: webhook signature\n";
    $fail++;
} else {
    echo "OK: webhook signature\n";
}

exit($fail > 0 ? 1 : 0);
