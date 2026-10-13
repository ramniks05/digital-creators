<?php
/**
 * Add leads.source, consent_at, wa_message_id. Run: php sql/migrate_leads_enquiry.php
 */
require_once dirname(__DIR__) . '/includes/db.php';

$pdo = db();

$columns = [
    'source' => "ALTER TABLE `leads` ADD COLUMN `source` ENUM('website_form','whatsapp') NOT NULL DEFAULT 'website_form' AFTER `budget`",
    'consent_at' => 'ALTER TABLE `leads` ADD COLUMN `consent_at` TIMESTAMP NULL DEFAULT NULL AFTER `source`',
    'wa_message_id' => 'ALTER TABLE `leads` ADD COLUMN `wa_message_id` VARCHAR(128) DEFAULT NULL AFTER `consent_at`',
];

foreach ($columns as $name => $sql) {
    try {
        $pdo->query("SELECT `{$name}` FROM `leads` LIMIT 1");
        echo "SKIP: leads.{$name} already exists\n";
    } catch (Throwable $e) {
        try {
            $pdo->exec($sql);
            echo "OK: added leads.{$name}\n";
        } catch (Throwable $e2) {
            echo "ERR: leads.{$name}: " . $e2->getMessage() . "\n";
        }
    }
}

try {
    $pdo->exec('CREATE UNIQUE INDEX `idx_leads_wa_message_id` ON `leads` (`wa_message_id`)');
    echo "OK: unique index idx_leads_wa_message_id\n";
} catch (Throwable $e) {
    if (stripos($e->getMessage(), 'Duplicate') !== false) {
        echo "SKIP: idx_leads_wa_message_id\n";
    } else {
        echo "NOTE: index: " . $e->getMessage() . "\n";
    }
}

echo "Leads enquiry columns ready.\n";
