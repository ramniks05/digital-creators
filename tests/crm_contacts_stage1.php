<?php
/**
 * Stage 1 contact management tests (requires DB + migrations).
 * Run: php tests/crm_contacts_stage1.php
 */
declare(strict_types=1);

$root = dirname(__DIR__);
require_once $root . '/includes/db.php';
require_once $root . '/includes/crm/ContactService.php';
require_once $root . '/includes/crm/contact_tags.php';
require_once $root . '/includes/crm/contact_import.php';

$pdo = db();
$failures = 0;
$tenantId = 1;

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

try {
    $pdo->query('SELECT 1 FROM crm_contact_tags LIMIT 1');
} catch (Throwable $e) {
    echo "SKIP: run php sql/migrate_crm_stage1_contacts.php first\n";
    exit(0);
}

$svc = new CrmContactService($pdo, $tenantId);
$testWa = '919876543210';
$existing = $pdo->prepare('SELECT id FROM crm_contacts WHERE tenant_id = ? AND wa_id = ?');
$existing->execute([$tenantId, $testWa]);
if ($existing->fetchColumn()) {
    echo "SKIP create: test wa_id already exists\n";
} else {
    $id = $svc->create($testWa, 'Stage1 Test', 'unit_test');
    assert_true($id !== null && $id > 0, 'create contact');
    $tagId = crm_ensure_tag($pdo, $tenantId, 'stage1-test');
    assert_true(crm_contact_assign_tag($pdo, $tenantId, (int) $id, $tagId), 'assign tag');
    assert_true($svc->archive((int) $id), 'archive contact');
    assert_true($svc->unarchive((int) $id), 'unarchive contact');
}

$csv = "wa_id,display_name,source\n919876543211,Import Test,import_csv\n";
$import = crm_import_contacts_csv($pdo, $tenantId, $csv, null);
assert_true($import['ok'] === true, 'import csv');
assert_true(($import['imported'] ?? 0) >= 1, 'imported at least one');

$import2 = crm_import_contacts_csv($pdo, $tenantId, $csv, null);
assert_true(($import2['skipped'] ?? 0) >= 1, 'duplicate import skipped');

$csvNoConsent = "wa_id,marketing_consent\n919876543212,yes\n";
$bad = crm_import_contacts_csv($pdo, $tenantId, $csvNoConsent, null);
$row = $pdo->prepare('SELECT consent_status FROM crm_contacts WHERE wa_id = ?');
$row->execute(['919876543212']);
$status = (string) ($row->fetchColumn() ?: '');
assert_true($status === 'unknown' || $status === '', 'import without evidence keeps unknown consent');

if ($failures > 0) {
    echo "\n{$failures} failure(s)\n";
    exit(1);
}
echo "\nStage 1 contact tests passed.\n";
