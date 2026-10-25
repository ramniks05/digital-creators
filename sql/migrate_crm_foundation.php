<?php
/**
 * CRM foundation migration + seed tenant, roles, default pipeline stages.
 * Run: php sql/migrate_crm_foundation.php
 */
declare(strict_types=1);

require_once dirname(__DIR__) . '/includes/db.php';

$pdo = db();
$sql = file_get_contents(__DIR__ . '/crm_foundation.sql');
$sql = preg_replace('/^--.*$/m', '', $sql);

foreach (array_filter(array_map('trim', explode(';', $sql))) as $stmt) {
    if ($stmt === '') {
        continue;
    }
    $pdo->exec($stmt);
    echo 'OK: ' . substr(str_replace("\n", ' ', $stmt), 0, 72) . "...\n";
}

$pdo->exec(
    "INSERT INTO crm_tenants (id, slug, name, timezone, status)
     VALUES (1, 'digital-creatorss', 'Digital Creatorss', 'Asia/Kolkata', 'active')
     ON DUPLICATE KEY UPDATE name = VALUES(name), timezone = VALUES(timezone)"
);
echo "OK: seed tenant\n";

$roles = [
    ['owner', 'Owner'],
    ['admin', 'Admin'],
    ['manager', 'Manager'],
    ['agent', 'Agent'],
];
$roleStmt = $pdo->prepare(
    'INSERT INTO crm_roles (tenant_id, slug, name) VALUES (1, ?, ?)
     ON DUPLICATE KEY UPDATE name = VALUES(name)'
);
foreach ($roles as [$slug, $name]) {
    $roleStmt->execute([$slug, $name]);
}
echo "OK: seed roles\n";

$stages = [
    ['new', 'New', 10],
    ['contacted', 'Contacted', 20],
    ['qualified', 'Qualified', 30],
    ['proposal', 'Proposal', 40],
    ['won', 'Won', 50],
    ['lost', 'Lost', 60],
];
$stageStmt = $pdo->prepare(
    'INSERT INTO crm_lead_stages (tenant_id, slug, name, sort_order, is_won, is_lost)
     VALUES (1, ?, ?, ?, ?, ?)
     ON DUPLICATE KEY UPDATE name = VALUES(name), sort_order = VALUES(sort_order)'
);
foreach ($stages as [$slug, $name, $order]) {
    $stageStmt->execute([
        $slug,
        $name,
        $order,
        $slug === 'won' ? 1 : 0,
        $slug === 'lost' ? 1 : 0,
    ]);
}
echo "OK: seed lead stages\n";

// Assign all admins to tenant as owner (bootstrap).
$admins = $pdo->query('SELECT id FROM admins')->fetchAll(PDO::FETCH_COLUMN);
$ownerRoleId = (int) $pdo->query("SELECT id FROM crm_roles WHERE tenant_id = 1 AND slug = 'owner' LIMIT 1")->fetchColumn();
if ($ownerRoleId > 0) {
    $mem = $pdo->prepare(
        'INSERT INTO crm_admin_tenant_roles (admin_id, tenant_id, role_id, is_active)
         VALUES (?, 1, ?, 1)
         ON DUPLICATE KEY UPDATE role_id = VALUES(role_id), is_active = 1'
    );
    foreach ($admins as $adminId) {
        $mem->execute([(int) $adminId, $ownerRoleId]);
    }
    echo "OK: admin tenant memberships\n";
}

require_once dirname(__DIR__) . '/includes/crm/conversations.php';
$count = crm_backfill_conversations_from_whatsapp($pdo, 1);
echo "OK: backfilled {$count} conversations from WhatsApp history\n";

echo "CRM foundation ready.\n";
