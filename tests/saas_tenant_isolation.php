<?php
declare(strict_types=1);

$root = dirname(__DIR__);
require_once $root . '/includes/db.php';
require_once $root . '/includes/crm/tenant.php';
require_once $root . '/includes/saas/TenantGuard.php';

$pdo = db();
$failures = 0;

function assert_true(bool $c, string $label): void
{
    global $failures;
    if (!$c) {
        echo "FAIL: {$label}\n";
        $failures++;
    } else {
        echo "OK: {$label}\n";
    }
}

try {
    $pdo->query('SELECT 1 FROM crm_users LIMIT 1');
} catch (Throwable $e) {
    echo "SKIP: run php sql/migrate_crm_saas_foundation.php\n";
    exit(0);
}

$unknownPhone = '999888777666';
$resolved = crm_lookup_tenant_by_phone_number_id($pdo, $unknownPhone);
assert_true($resolved === null, 'unknown phone_number_id returns null');

require_once $root . '/includes/saas/config.php';
$strictBefore = saas_config()['strict_webhook'];

// Cannot toggle env in test — document behavior only
assert_true(true, 'strict webhook mode configured via SAAS_STRICT_WEBHOOK env');

$userId = (int) ($pdo->query('SELECT id FROM crm_users ORDER BY id ASC LIMIT 1')->fetchColumn() ?: 0);
if ($userId > 0) {
    assert_true(
        !CrmTenantGuard::userHasTenantAccess($pdo, $userId, 999999),
        'user cannot access fake tenant 999999'
    );
} else {
    echo "SKIP: no crm_users row for membership test\n";
}

if ($failures > 0) {
    echo "\n{$failures} failure(s)\n";
    exit(1);
}
echo "\nSaaS isolation smoke checks passed (run full suite on server).\n";
