<?php
/**
 * Local smoke tests (no Meta network). Run: php tests/crm_smoke.php
 */
declare(strict_types=1);

$root = dirname(__DIR__);
require_once $root . '/includes/db.php';
require_once $root . '/includes/crm/tenant.php';
require_once $root . '/includes/crm/rbac.php';
require_once $root . '/includes/crm/messages.php';
require_once $root . '/includes/crm/compliance/MessagePolicyService.php';
require_once $root . '/includes/whatsapp_contacts.php';

$failures = 0;

function assert_true(bool $cond, string $label): void
{
    global $failures;
    if (!$cond) {
        echo "FAIL: {$label}\n";
        $failures++;
    } else {
        echo "OK: {$label}\n";
    }
}

$pdo = db();
assert_true(crm_default_tenant_id() === 1, 'default tenant id');
assert_true(is_int(crm_tenant_id_for_phone_number_id($pdo, null)), 'tenant resolve null phone');

$policy = new CrmMessagePolicyService($pdo, 1);
$blocked = $policy->validateOutboundText('', '');
assert_true($blocked['ok'] === false, 'empty text blocked');

assert_true(!whatsapp_contact_is_opted_out($pdo, '919999999999'), 'unknown contact not opted out');

if ($failures > 0) {
    echo "\n{$failures} failure(s)\n";
    exit(1);
}

echo "\nAll local smoke checks passed.\n";
echo "Live Meta tests (webhook signature, send, delivery) require staging credentials.\n";
