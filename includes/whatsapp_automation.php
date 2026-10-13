<?php
/**
 * WhatsApp inbound automation (opt-out, optional welcome reply).
 * All automatic outbound messaging is OFF unless explicitly enabled in config/env.
 */
declare(strict_types=1);

require_once __DIR__ . '/whatsapp_contacts.php';
require_once __DIR__ . '/whatsapp_send.php';
require_once __DIR__ . '/whatsapp_webhook_diagnostics.php';

/**
 * @return array{
 *   enabled:bool,
 *   welcome_hi_enabled:bool,
 *   welcome_hi_message:string,
 *   opt_out_confirm_enabled:bool,
 *   opt_out_confirm_message:string
 * }
 */
/**
 * Do not repeat welcome/ack while a chat is already active (recent business outbound to this user).
 */
function whatsapp_inbound_autoreply_cooldown_seconds(): int
{
    static $seconds = null;
    if ($seconds !== null) {
        return $seconds;
    }

    if (is_file(__DIR__ . '/business_enquiry.php')) {
        require_once __DIR__ . '/business_enquiry.php';
        $cfg = business_enquiry_config();
        $hours = (float) ($cfg['inbound_autoreply_cooldown_hours'] ?? 4);
    } else {
        $config = require __DIR__ . '/config.php';
        $be = is_array($config['whatsapp']['business_enquiry'] ?? null)
            ? $config['whatsapp']['business_enquiry']
            : [];
        $hours = $be['inbound_autoreply_cooldown_hours'] ?? getenv('DC_ENQUIRY_COOLDOWN_HOURS') ?: 4;
        $hours = is_numeric($hours) ? (float) $hours : 4.0;
    }
    $seconds = (int) max(600, round($hours * 3600));

    return $seconds;
}

function whatsapp_should_skip_autoreply_for_active_chat(PDO $pdo, string $waId): bool
{
    require_once __DIR__ . '/whatsapp_send.php';
    $variants = whatsapp_wa_id_variants($waId);
    if ($variants === []) {
        return false;
    }
    [$inSql, $inParams] = whatsapp_wa_id_in_clause('recipient_wa_id', $variants);
    $cooldown = whatsapp_inbound_autoreply_cooldown_seconds();
    $stmt = $pdo->prepare(
        "SELECT 1 FROM whatsapp_outbound_messages
         WHERE {$inSql}
           AND created_at >= DATE_SUB(NOW(), INTERVAL ? SECOND)
           AND wa_message_id IS NOT NULL AND wa_message_id != ''
           AND delivery_status NOT IN ('failed')
         LIMIT 1"
    );
    $stmt->execute(array_merge($inParams, [$cooldown]));

    return (bool) $stmt->fetchColumn();
}

function whatsapp_automation_config(): array
{
    static $cached = null;
    if (is_array($cached)) {
        return $cached;
    }

    $config = require __DIR__ . '/config.php';
    $wa = $config['whatsapp'] ?? [];
    $auto = is_array($wa['automation'] ?? null) ? $wa['automation'] : [];

    $bool = static function (mixed $value, bool $default = false): bool {
        if ($value === null || $value === '') {
            return $default;
        }
        if (is_bool($value)) {
            return $value;
        }

        return filter_var($value, FILTER_VALIDATE_BOOLEAN);
    };

    $welcome = trim((string) ($auto['welcome_hi_message'] ?? getenv('WHATSAPP_WELCOME_MESSAGE') ?: ''));
    if ($welcome === '') {
        $welcome = 'Hi! Thanks for messaging Digital Creatorss. Our team will reply shortly. Reply STOP anytime to opt out of marketing messages.';
    }
    $optOutMsg = trim((string) ($auto['opt_out_confirm_message'] ?? getenv('WHATSAPP_OPT_OUT_MESSAGE') ?: ''));
    if ($optOutMsg === '') {
        $optOutMsg = 'You have been unsubscribed from marketing messages. You can still message us for support.';
    }

    $cached = [
        'enabled' => $bool($auto['enabled'] ?? getenv('WHATSAPP_AUTO_REPLY') ?: false, false),
        'welcome_hi_enabled' => $bool($auto['welcome_hi_enabled'] ?? getenv('WHATSAPP_WELCOME_HI') ?: false, false),
        'welcome_hi_message' => $welcome,
        'opt_out_confirm_enabled' => $bool($auto['opt_out_confirm_enabled'] ?? getenv('WHATSAPP_OPT_OUT_CONFIRM') ?: false, false),
        'opt_out_confirm_message' => $optOutMsg,
    ];

    return $cached;
}

function whatsapp_automation_normalize_keyword(string $text): string
{
    $text = trim($text);
    $text = preg_replace('/\s+/u', ' ', $text) ?? $text;

    return mb_strtolower($text, 'UTF-8');
}

/**
 * Detect STOP / UNSUBSCRIBE style opt-out in inbound text (Meta compliance).
 */
function whatsapp_automation_is_opt_out_message(?string $body): bool
{
    if ($body === null || trim($body) === '') {
        return false;
    }
    $norm = whatsapp_automation_normalize_keyword($body);
    if ($norm === '') {
        return false;
    }

    $exact = [
        'stop',
        'unsubscribe',
        'cancel',
        'opt out',
        'opt-out',
        'optout',
        'stop all',
        'stop messages',
        'stop promotions',
    ];
    if (in_array($norm, $exact, true)) {
        return true;
    }

    return (bool) preg_match('/^(stop|unsubscribe|cancel)(\s|$|[!.])/', $norm);
}

/**
 * Optional welcome when customer sends a short greeting (only if automation enabled).
 */
function whatsapp_automation_is_welcome_trigger(?string $body): bool
{
    if ($body === null || trim($body) === '') {
        return false;
    }
    $norm = whatsapp_automation_normalize_keyword($body);
    if (mb_strlen($norm, 'UTF-8') > 32) {
        return false;
    }
    $norm = rtrim($norm, '!.?');

    return in_array($norm, ['hi', 'hello', 'hey', 'hii', 'hiii', 'namaste', 'good morning', 'good evening'], true);
}

function whatsapp_contact_consent_status(PDO $pdo, string $waId): string
{
    if (!whatsapp_contacts_table_exists($pdo)) {
        return 'unknown';
    }
    $waId = whatsapp_normalize_recipient($waId);
    $stmt = $pdo->prepare('SELECT consent_status FROM whatsapp_contacts WHERE wa_id = ? LIMIT 1');
    $stmt->execute([$waId]);
    $status = $stmt->fetchColumn();

    return is_string($status) && $status !== '' ? $status : 'unknown';
}

/**
 * @param list<array{from:string,wamid:string,body:?string,type:string}> $newInbound
 */
function whatsapp_automation_after_inbound(PDO $pdo, array $newInbound): void
{
    if ($newInbound === []) {
        return;
    }

    $cfg = whatsapp_automation_config();

    foreach ($newInbound as $row) {
        $from = (string) ($row['from'] ?? '');
        $wamid = (string) ($row['wamid'] ?? '');
        $body = isset($row['body']) ? (string) $row['body'] : null;
        $type = (string) ($row['type'] ?? 'unknown');

        if ($from === '' || $wamid === '') {
            continue;
        }

        if ($type === 'text' || $body !== null) {
            if (whatsapp_automation_is_opt_out_message($body)) {
                whatsapp_contact_record_opt_out($from, 'whatsapp_stop');
                whatsapp_log_event('opt_out', [
                    'wamid' => $wamid,
                    'from_masked' => whatsapp_mask_wa_id($from),
                    'source' => 'whatsapp_stop',
                ]);

                if ($cfg['enabled'] && $cfg['opt_out_confirm_enabled']) {
                    whatsapp_automation_send_once($pdo, $from, $wamid, 'opt_out_confirm', $cfg['opt_out_confirm_message']);
                }
                continue;
            }
        }

        $enquiryReplySent = false;
        if (is_file(__DIR__ . '/business_enquiry.php')) {
            require_once __DIR__ . '/business_enquiry.php';
            $enquiryReplySent = business_enquiry_process_whatsapp_inbound($pdo, $row);
        }

        if ($enquiryReplySent) {
            continue;
        }

        if (!$cfg['enabled'] || !$cfg['welcome_hi_enabled']) {
            continue;
        }

        if ($type !== 'text' || !whatsapp_automation_is_welcome_trigger($body)) {
            continue;
        }

        if (whatsapp_contact_consent_status($pdo, $from) === 'opted_out') {
            whatsapp_log_event('welcome_skipped', [
                'wamid' => $wamid,
                'from_masked' => whatsapp_mask_wa_id($from),
                'reason' => 'opted_out',
            ]);
            continue;
        }

        if (!whatsapp_service_window_open($pdo, $from)) {
            continue;
        }

        if (whatsapp_should_skip_autoreply_for_active_chat($pdo, $from)) {
            whatsapp_log_event('welcome_skipped', [
                'wamid' => $wamid,
                'from_masked' => whatsapp_mask_wa_id($from),
                'reason' => 'active_conversation',
            ]);
            continue;
        }

        whatsapp_automation_send_once($pdo, $from, $wamid, 'welcome_hi', $cfg['welcome_hi_message']);
    }
}

/**
 * Idempotent outbound for automation (dedupe key autoreply:{kind}:{inbound_wamid}).
 */
function whatsapp_automation_send_once(PDO $pdo, string $to, string $inboundWamid, string $kind, string $text): void
{
    $text = trim($text);
    if ($text === '') {
        return;
    }

    $dedupeKey = 'autoreply:' . $kind . ':' . $inboundWamid;
    try {
        $pdo->beginTransaction();
        if (!whatsapp_register_dedupe($pdo, $dedupeKey, 'autoreply')) {
            $pdo->commit();
            whatsapp_log_event('autoreply_skipped', [
                'kind' => $kind,
                'wamid' => $inboundWamid,
                'from_masked' => whatsapp_mask_wa_id($to),
                'reason' => 'dedupe',
            ]);

            return;
        }
        $pdo->commit();
    } catch (Throwable $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        whatsapp_log_event('autoreply_failed', [
            'kind' => $kind,
            'wamid' => $inboundWamid,
            'stage' => 'dedupe',
            'error' => substr($e->getMessage(), 0, 120),
        ]);

        return;
    }

    $result = whatsapp_send_text_message($to, $text, false, $inboundWamid);
    if (!$result['ok']) {
        whatsapp_log_event('autoreply_failed', [
            'kind' => $kind,
            'wamid' => $inboundWamid,
            'from_masked' => whatsapp_mask_wa_id($to),
            'error' => substr((string) ($result['error'] ?? 'send_failed'), 0, 200),
        ]);

        return;
    }

    whatsapp_log_event('autoreply_sent', [
        'kind' => $kind,
        'inbound_wamid' => $inboundWamid,
        'outbound_wamid' => (string) ($result['wa_message_id'] ?? ''),
        'from_masked' => whatsapp_mask_wa_id($to),
    ]);
}
