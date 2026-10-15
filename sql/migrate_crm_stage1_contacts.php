<?php
/**
 * Stage 1 contact management migration.
 * Run: php sql/migrate_crm_stage1_contacts.php
 */
declare(strict_types=1);

require_once dirname(__DIR__) . '/includes/db.php';

$pdo = db();

$alterColumns = [
    'source' => "ADD COLUMN `source` VARCHAR(64) NOT NULL DEFAULT 'whatsapp_inbound' AFTER `consent_status`",
    'owner_admin_id' => 'ADD COLUMN `owner_admin_id` INT UNSIGNED DEFAULT NULL AFTER `source`',
    'archived_at' => 'ADD COLUMN `archived_at` TIMESTAMP NULL DEFAULT NULL AFTER `owner_admin_id`',
    'merged_into_contact_id' => 'ADD COLUMN `merged_into_contact_id` BIGINT UNSIGNED DEFAULT NULL AFTER `archived_at`',
    'import_batch_id' => 'ADD COLUMN `import_batch_id` VARCHAR(64) DEFAULT NULL AFTER `merged_into_contact_id`',
];

foreach ($alterColumns as $col => $ddl) {
    try {
        $pdo->query("SELECT `{$col}` FROM crm_contacts LIMIT 1");
        echo "SKIP: crm_contacts.{$col} exists\n";
    } catch (Throwable $e) {
        $pdo->exec('ALTER TABLE crm_contacts ' . $ddl);
        echo "OK: crm_contacts.{$col}\n";
    }
}

$sql = file_get_contents(__DIR__ . '/crm_stage1_contacts.sql');
$sql = preg_replace('/^--.*$/m', '', $sql);
$sql = preg_replace('/ALTER TABLE.*?;/s', '', $sql);
$sql = preg_replace('/CREATE INDEX IF NOT EXISTS.*?;/s', '', $sql);

foreach (array_filter(array_map('trim', explode(';', $sql))) as $stmt) {
    if ($stmt === '') {
        continue;
    }
    try {
        $pdo->exec($stmt);
        echo 'OK: ' . substr(str_replace("\n", ' ', $stmt), 0, 72) . "...\n";
    } catch (PDOException $e) {
        if (str_contains($e->getMessage(), 'Duplicate')) {
            echo 'SKIP: ' . substr($stmt, 0, 40) . "...\n";
        } else {
            throw $e;
        }
    }
}

$indexes = [
    'idx_crm_contacts_archived' => 'CREATE INDEX idx_crm_contacts_archived ON crm_contacts (tenant_id, archived_at)',
    'idx_crm_contacts_owner' => 'CREATE INDEX idx_crm_contacts_owner ON crm_contacts (tenant_id, owner_admin_id)',
    'idx_crm_contacts_source' => 'CREATE INDEX idx_crm_contacts_source ON crm_contacts (tenant_id, source)',
];
foreach ($indexes as $name => $ddl) {
    try {
        $pdo->exec($ddl);
        echo "OK: {$name}\n";
    } catch (PDOException $e) {
        echo "SKIP: {$name}\n";
    }
}

require_once dirname(__DIR__) . '/includes/crm/contact_tags.php';
$backfill = crm_tags_backfill_from_json($pdo, 1);
echo "OK: backfilled {$backfill} tag assignments from tags_json\n";

echo "CRM Stage 1 contacts ready.\n";
