<?php
/**
 * Chatbot diagnostics (run on server via SSH).
 * php scripts/chatbot_health.php [peer_wa_id optional]
 */
declare(strict_types=1);

require_once dirname(__DIR__) . '/includes/db.php';
require_once dirname(__DIR__) . '/includes/chatbot/config.php';
require_once dirname(__DIR__) . '/includes/whatsapp_send.php';
require_once dirname(__DIR__) . '/includes/crm/tenant.php';

$pdo = db();
$peer = isset($argv[1]) ? preg_replace('/\D+/', '', (string) $argv[1]) : '';

echo "=== Chatbot health ===\n\n";

$enabled = chatbot_enabled();
echo 'chatbot_enabled: ' . ($enabled ? 'YES' : 'NO') . "\n";
if (!$enabled) {
    echo "  Fix: private/digital_creators_config.php -> whatsapp.chatbot.enabled = true\n";
    echo "  Or env WHATSAPP_CHATBOT_ENABLED=1\n\n";
}

$tables = chatbot_tables_ready($pdo);
echo 'chatbot_tables: ' . ($tables ? 'YES' : 'NO') . "\n";
if (!$tables) {
    echo "  Fix: php sql/migrate_crm_chatbot.php\n\n";
}

$cfg = whatsapp_cloud_config();
echo 'whatsapp_token_set: ' . (trim($cfg['access_token'] ?? '') !== '' ? 'YES' : 'NO') . "\n";
echo 'phone_number_id: ' . (trim($cfg['phone_number_id'] ?? '') !== '' ? $cfg['phone_number_id'] : '(missing)') . "\n\n";

if ($tables) {
    try {
        $svc = (int) $pdo->query('SELECT COUNT(*) FROM chatbot_services')->fetchColumn();
        echo "chatbot_services rows: {$svc}\n";
    } catch (Throwable $e) {
        echo "chatbot_services: error\n";
    }
    try {
        $human = (int) $pdo->query("SELECT COUNT(*) FROM chatbot_sessions WHERE mode = 'human'")->fetchColumn();
        $bot = (int) $pdo->query("SELECT COUNT(*) FROM chatbot_sessions WHERE mode = 'bot'")->fetchColumn();
        echo "sessions bot/human: {$bot} / {$human}\n";
    } catch (Throwable $e) {
        echo "chatbot_sessions: error\n";
    }
}

$brochure = dirname(__DIR__) . '/assets/uploads/brochures/digital-creatorss-brochure.pdf';
echo 'brochure_file: ' . (is_readable($brochure) ? 'YES' : 'NO') . " ({$brochure})\n\n";

if ($peer !== '') {
    require_once dirname(__DIR__) . '/includes/whatsapp_send.php';
    $peer = whatsapp_normalize_recipient($peer);
    echo "=== Session for +{$peer} ===\n";
    $stmt = $pdo->prepare('SELECT * FROM chatbot_sessions WHERE peer_wa_id = ? LIMIT 1');
    $stmt->execute([$peer]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$row) {
        echo "No chatbot_sessions row (first message will create one).\n";
    } else {
        echo 'mode: ' . ($row['mode'] ?? '?') . " (human = bot will NOT reply)\n";
        echo 'step: ' . ($row['current_step'] ?? '?') . "\n";
        echo 'last_activity: ' . ($row['last_activity_at'] ?? '') . "\n";
    }
    echo "\n";
}

try {
    $jobs = $pdo->query(
        "SELECT id, status, last_error, job_type, created_at FROM crm_outbound_jobs
         ORDER BY id DESC LIMIT 5"
    )->fetchAll(PDO::FETCH_ASSOC);
    if ($jobs !== []) {
        echo "=== Last outbound jobs ===\n";
        foreach ($jobs as $j) {
            echo sprintf(
                "#%s %s %s %s\n  error: %s\n",
                $j['id'],
                $j['status'],
                $j['job_type'],
                $j['created_at'],
                $j['last_error'] ?? ''
            );
        }
    }
} catch (Throwable $e) {
    echo "crm_outbound_jobs: (table missing or error)\n";
}

echo "\nIf inbound appears in CRM Inbox but no bot reply:\n";
echo "  1) mode=human -> Inbox Resume bot, or: UPDATE chatbot_sessions SET mode='bot' WHERE peer_wa_id='...';\n";
echo "  2) Check last_error on crm_outbound_jobs (window closed, paused, opt-out).\n";
echo "  3) php scripts/crm_outbound_worker.php\n";
