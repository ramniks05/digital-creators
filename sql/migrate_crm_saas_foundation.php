<?php
declare(strict_types=1);

require_once dirname(__DIR__) . '/includes/db.php';

$pdo = db();
$sql = file_get_contents(__DIR__ . '/crm_saas_foundation.sql');
$sql = preg_replace('/^--.*$/m', '', $sql);

foreach (array_filter(array_map('trim', explode(';', $sql))) as $stmt) {
    if ($stmt === '') {
        continue;
    }
    try {
        $pdo->exec($stmt);
        echo 'OK: ' . substr(str_replace("\n", ' ', $stmt), 0, 70) . "...\n";
    } catch (PDOException $e) {
        echo 'SKIP/ERR: ' . substr($e->getMessage(), 0, 120) . "\n";
    }
}

$tenantCols = [
    'subscription_status' => "ADD COLUMN `subscription_status` VARCHAR(32) NOT NULL DEFAULT 'active' AFTER `status`",
    'plan_slug' => "ADD COLUMN `plan_slug` VARCHAR(32) DEFAULT NULL AFTER `subscription_status`",
];
foreach ($tenantCols as $col => $ddl) {
    try {
        $pdo->query("SELECT `{$col}` FROM crm_tenants LIMIT 1");
    } catch (Throwable $e) {
        try {
            $pdo->exec('ALTER TABLE crm_tenants ' . $ddl);
            echo "OK: crm_tenants.{$col}\n";
        } catch (PDOException $ex) {
            echo "SKIP: crm_tenants.{$col}\n";
        }
    }
}

$plans = [
    ['starter', 'Starter', 0, '{"max_users":2,"max_whatsapp_numbers":1,"max_contacts":2000,"campaigns":false,"automation":false}'],
    ['growth', 'Growth', 0, '{"max_users":10,"max_whatsapp_numbers":2,"max_contacts":25000,"campaigns":true,"automation":true}'],
    ['business', 'Business', 0, '{"max_users":25,"max_whatsapp_numbers":5,"max_contacts":100000,"campaigns":true,"automation":true,"api_access":true}'],
    ['enterprise', 'Enterprise', 0, '{"max_users":999,"max_whatsapp_numbers":99,"max_contacts":9999999,"campaigns":true,"automation":true,"api_access":true,"custom":true}'],
];
$pIns = $pdo->prepare(
    'INSERT INTO crm_subscription_plans (slug, name, price_cents, limits_json, sort_order)
     VALUES (?,?,0,?,?)
     ON DUPLICATE KEY UPDATE name = VALUES(name), limits_json = VALUES(limits_json)'
);
$order = 10;
foreach ($plans as [$slug, $name, , $limits]) {
    $pIns->execute([$slug, $name, $limits, $order]);
    $order += 10;
}
echo "OK: subscription plans seeded (price_cents=0 — configure in admin)\n";

$pdo->exec(
    "INSERT IGNORE INTO crm_tenant_settings (tenant_id, business_email, currency)
     SELECT id, NULL, 'INR' FROM crm_tenants"
);
echo "OK: tenant_settings backfill\n";

$nums = $pdo->query('SELECT tenant_id, id, waba_id, phone_number_id, display_phone, is_active FROM crm_whatsapp_accounts');
foreach ($nums->fetchAll(PDO::FETCH_ASSOC) as $row) {
    try {
        $pdo->prepare(
            'INSERT INTO crm_whatsapp_numbers
             (tenant_id, account_id, waba_id, phone_number_id, display_phone, is_active, is_primary)
             VALUES (?,?,?,?,?,?,1)
             ON DUPLICATE KEY UPDATE tenant_id = VALUES(tenant_id), is_active = VALUES(is_active)'
        )->execute([
            $row['tenant_id'],
            $row['id'],
            $row['waba_id'],
            $row['phone_number_id'],
            $row['display_phone'],
            $row['is_active'],
        ]);
    } catch (Throwable $e) {
        // ignore duplicate global phone
    }
}
echo "OK: whatsapp_numbers synced from crm_whatsapp_accounts\n";

$flags = ['saas_strict_webhook', 'meta_embedded_signup', 'campaigns_v2'];
$fIns = $pdo->prepare('INSERT IGNORE INTO crm_feature_flags (slug, description, default_enabled) VALUES (?,?,0)');
foreach ($flags as $f) {
    $fIns->execute([$f, $f]);
}
echo "OK: feature flags\n";

echo "CRM SaaS foundation migration finished.\n";
