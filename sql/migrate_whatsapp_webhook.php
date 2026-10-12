<?php
/**
 * Create WhatsApp webhook tables. Run: php sql/migrate_whatsapp_webhook.php
 */
require_once dirname(__DIR__) . '/includes/db.php';

$pdo = db();
$sql = file_get_contents(__DIR__ . '/whatsapp_webhook.sql');
$sql = preg_replace('/^USE\s+`?digital_creators`?\s*;/im', '', $sql);

foreach (array_filter(array_map('trim', explode(';', $sql))) as $stmt) {
    if ($stmt === '') {
        continue;
    }
    $pdo->exec($stmt);
    echo "OK: " . substr(str_replace("\n", ' ', $stmt), 0, 80) . "...\n";
}

$hasContactName = (int) $pdo->query(
    "SELECT COUNT(*) FROM information_schema.COLUMNS
     WHERE TABLE_SCHEMA = DATABASE()
       AND TABLE_NAME = 'whatsapp_inbound_messages'
       AND COLUMN_NAME = 'contact_name'"
)->fetchColumn();
if ($hasContactName === 0) {
    $patch = trim(file_get_contents(__DIR__ . '/patch_whatsapp_contact_name.sql'));
    $patch = preg_replace('/^--.*$/m', '', $patch);
    if ($patch !== '') {
        $pdo->exec($patch);
        echo "OK: added whatsapp_inbound_messages.contact_name\n";
    }
}

echo "WhatsApp webhook tables ready.\n";
