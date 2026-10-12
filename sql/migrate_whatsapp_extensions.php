<?php
/**
 * WhatsApp contacts + campaigns tables. Run: php sql/migrate_whatsapp_extensions.php
 */
require_once dirname(__DIR__) . '/includes/db.php';
require_once dirname(__DIR__) . '/includes/whatsapp_contacts.php';

$pdo = db();
$sql = file_get_contents(__DIR__ . '/whatsapp_contacts_campaigns.sql');
$sql = preg_replace('/^USE\s+`?[\w]+`?\s*;/im', '', $sql);

foreach (array_filter(array_map('trim', explode(';', $sql))) as $stmt) {
    if ($stmt === '') {
        continue;
    }
    $pdo->exec($stmt);
    echo 'OK: ' . substr(str_replace("\n", ' ', $stmt), 0, 70) . "...\n";
}

if (whatsapp_contacts_table_exists($pdo)) {
    $pdo->exec(
        'INSERT INTO whatsapp_contacts (wa_id, display_name, consent_status, consent_source, consent_at, last_inbound_at)
         SELECT DISTINCT from_wa_id, MAX(contact_name), \'opted_in\', \'backfill_inbound\', MIN(created_at), MAX(created_at)
         FROM whatsapp_inbound_messages
         GROUP BY from_wa_id
         ON DUPLICATE KEY UPDATE
           display_name = COALESCE(VALUES(display_name), whatsapp_contacts.display_name),
           last_inbound_at = GREATEST(COALESCE(whatsapp_contacts.last_inbound_at, \'1970-01-01\'), VALUES(last_inbound_at))'
    );
    echo "OK: backfilled whatsapp_contacts from inbound history\n";
}

echo "WhatsApp extension tables ready.\n";
