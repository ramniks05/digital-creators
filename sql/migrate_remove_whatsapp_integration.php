<?php
/**
 * Drops Meta WhatsApp + chatbot tables from this site DB.
 * Run once on production after deploying external messaging platform:
 *   php sql/migrate_remove_whatsapp_integration.php
 */
declare(strict_types=1);

require_once dirname(__DIR__) . '/includes/db.php';

$pdo = db();

$tables = [
    'agent_handoffs',
    'chatbot_sessions',
    'chatbot_services',
    'chatbot_assets',
    'crm_outbound_jobs',
    'crm_messages',
    'crm_conversations',
    'crm_webhook_events',
    'crm_contact_suppressions',
    'crm_contact_consents',
    'crm_messaging_pause',
    'whatsapp_message_status_log',
    'whatsapp_inbound_messages',
    'whatsapp_outbound_messages',
    'whatsapp_webhook_dedupe',
    'whatsapp_contacts',
    'whatsapp_campaign_recipients',
    'whatsapp_campaigns',
    'whatsapp_media_cache',
];

$pdo->exec('SET FOREIGN_KEY_CHECKS=0');
foreach ($tables as $table) {
    try {
        $pdo->exec('DROP TABLE IF EXISTS `' . str_replace('`', '``', $table) . '`');
        echo "Dropped: {$table}\n";
    } catch (Throwable $e) {
        echo "Skip {$table}: " . substr($e->getMessage(), 0, 80) . "\n";
    }
}
$pdo->exec('SET FOREIGN_KEY_CHECKS=1');

echo "WhatsApp integration tables removed. Leads/contacts/admin users kept.\n";
echo "Point Meta webhook to your external platform; set EXTERNAL_MESSAGING_WEBHOOK_URL on this site.\n";
