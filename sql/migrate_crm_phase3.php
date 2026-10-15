<?php
declare(strict_types=1);

require_once dirname(__DIR__) . '/includes/db.php';

$pdo = db();
$sql = file_get_contents(__DIR__ . '/crm_phase3_messaging.sql');
$sql = preg_replace('/^--.*$/m', '', $sql);

foreach (array_filter(array_map('trim', explode(';', $sql))) as $stmt) {
    if ($stmt === '') {
        continue;
    }
    $pdo->exec($stmt);
    echo 'OK: ' . substr(str_replace("\n", ' ', $stmt), 0, 70) . "...\n";
}

$perms = [
    ['inbox.view', 'View inbox'],
    ['inbox.reply', 'Reply in inbox'],
    ['contacts.manage', 'Manage contacts'],
    ['conversations.assign', 'Assign conversations'],
    ['templates.send', 'Send templates'],
    ['campaigns.manage', 'Manage campaigns'],
    ['settings.whatsapp', 'WhatsApp settings'],
    ['admin.users', 'Manage team'],
];
$pIns = $pdo->prepare('INSERT IGNORE INTO crm_permissions (slug, name) VALUES (?, ?)');
foreach ($perms as [$slug, $name]) {
    $pIns->execute([$slug, $name]);
}
echo "OK: permissions seeded\n";

$rolePermMap = [
    'owner' => ['inbox.view', 'inbox.reply', 'contacts.manage', 'conversations.assign', 'templates.send', 'campaigns.manage', 'settings.whatsapp', 'admin.users'],
    'admin' => ['inbox.view', 'inbox.reply', 'contacts.manage', 'conversations.assign', 'templates.send', 'campaigns.manage', 'settings.whatsapp'],
    'manager' => ['inbox.view', 'inbox.reply', 'contacts.manage', 'conversations.assign', 'templates.send'],
    'agent' => ['inbox.view', 'inbox.reply', 'templates.send'],
];
$rp = $pdo->prepare(
    'INSERT IGNORE INTO crm_role_permissions (role_id, permission_id)
     SELECT r.id, p.id FROM crm_roles r, crm_permissions p
     WHERE r.tenant_id = 1 AND r.slug = ? AND p.slug = ?'
);
foreach ($rolePermMap as $roleSlug => $permSlugs) {
    foreach ($permSlugs as $permSlug) {
        $rp->execute([$roleSlug, $permSlug]);
    }
}
echo "OK: role permissions seeded\n";

$pdo->exec('INSERT IGNORE INTO crm_messaging_pause (tenant_id, paused) VALUES (1, 0)');
echo "OK: messaging pause row\n";

require_once dirname(__DIR__) . '/includes/crm/messages.php';
$synced = crm_backfill_messages_from_whatsapp($pdo, 1);
echo "OK: backfilled {$synced} crm_messages rows\n";

echo "CRM phase 3 ready.\n";
