<?php
/**
 * Chatbot tables + default services. Run after CRM foundation:
 * php sql/migrate_crm_chatbot.php
 */
declare(strict_types=1);

require_once dirname(__DIR__) . '/includes/db.php';
require_once dirname(__DIR__) . '/includes/crm/tenant.php';

$pdo = db();
$sql = file_get_contents(__DIR__ . '/crm_chatbot.sql');
if ($sql === false) {
    echo "ERR: could not read crm_chatbot.sql\n";
    exit(1);
}

foreach (array_filter(array_map('trim', explode(';', $sql))) as $stmt) {
    // Strip line comments so the first CREATE is not skipped when bundled with file header "-- ..."
    $stmt = trim(preg_replace('/^\s*--[^\n]*\n/m', '', $stmt) ?? $stmt);
    if ($stmt === '') {
        continue;
    }
    try {
        $pdo->exec($stmt);
        if (preg_match('/CREATE TABLE(?:\s+IF NOT EXISTS)?\s+`?(\w+)`?/i', $stmt, $m)) {
            echo 'OK: ' . $m[1] . "\n";
        }
    } catch (Throwable $e) {
        echo 'ERR: ' . substr($e->getMessage(), 0, 200) . "\n";
    }
}

if (!crm_tables_ready($pdo)) {
    echo "WARN: crm_tenants missing — seed services manually after foundation migrate.\n";
    exit(0);
}

$tenantId = crm_default_tenant_id();
$defaults = [
    ['web-development', 'Web Development', 'Websites, web apps, and portals'],
    ['mobile-apps', 'Mobile Apps', 'Android, iOS, and cross-platform apps'],
    ['whatsapp-crm', 'WhatsApp CRM & Automation', 'Business messaging and CRM setups'],
    ['digital-marketing', 'Digital Marketing', 'SEO, ads, and social campaigns'],
];

foreach ($defaults as $i => [$slug, $title, $desc]) {
    try {
        $pdo->prepare(
            'INSERT IGNORE INTO chatbot_services (tenant_id, slug, title, description, sort_order)
             VALUES (?,?,?,?,?)'
        )->execute([$tenantId, $slug, $title, $desc, $i + 1]);
    } catch (Throwable $e) {
        echo 'Seed skip: ' . $e->getMessage() . "\n";
    }
}

try {
    $pdo->prepare(
        'INSERT IGNORE INTO chatbot_assets (tenant_id, asset_key, filename) VALUES (?,?,?)'
    )->execute([$tenantId, 'company_brochure', 'digital-creatorss-brochure.pdf']);
} catch (Throwable $e) {
    echo 'Asset seed: ' . $e->getMessage() . "\n";
}

echo "Chatbot migration complete.\n";
