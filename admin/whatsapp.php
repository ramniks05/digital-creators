<?php
require_once dirname(__DIR__) . '/includes/auth.php';
require_admin();
require_once __DIR__ . '/layout.php';
require_once dirname(__DIR__) . '/includes/whatsapp_cloud.php';
require_once dirname(__DIR__) . '/includes/whatsapp_send.php';

function admin_whatsapp_tables_ready(PDO $pdo): bool
{
    try {
        $pdo->query('SELECT 1 FROM whatsapp_inbound_messages LIMIT 1');
        return true;
    } catch (Throwable $e) {
        return false;
    }
}

function admin_whatsapp_excerpt(?string $text, int $max = 80): string
{
    $text = trim((string) $text);
    if ($text === '') {
        return '—';
    }
    if (strlen($text) <= $max) {
        return $text;
    }

    return substr($text, 0, $max - 1) . '…';
}

function admin_whatsapp_outbound_preview(array $row): string
{
    if (!empty($row['payload_json'])) {
        $p = json_decode((string) $row['payload_json'], true);
        if (is_array($p)) {
            if (!empty($p['text'])) {
                return (string) $p['text'];
            }
            if (!empty($p['template'])) {
                return 'Template: ' . $p['template'];
            }
            if (!empty($p['image_url'])) {
                return 'Image: ' . $p['image_url'];
            }
            if (!empty($p['document_url'])) {
                return 'Document: ' . $p['document_url'];
            }
        }
    }
    if (!empty($row['template_name'])) {
        return 'Template: ' . $row['template_name'];
    }

    return 'Outbound message';
}

/** @return list<string> */
function admin_whatsapp_template_body_params_from_post(): array
{
    $raw = $_POST['template_body_params'] ?? [];
    if (!is_array($raw)) {
        return [];
    }

    return array_map(static fn ($v) => trim((string) $v), array_values($raw));
}

/** Safe option attributes for whatsapp-admin.js template variable fields. */
function admin_whatsapp_template_option_attrs(array $t): string
{
    $paramCount = (int) ($t['param_count'] ?? 0);
    $slotJson = json_encode($t['param_slots'] ?? [], JSON_UNESCAPED_UNICODE);
    if ($slotJson === false) {
        $slotJson = '[]';
    }
    $preview = admin_whatsapp_excerpt((string) ($t['body_preview'] ?? ''), 200);

    return sprintf(
        'data-lang="%s" data-param-count="%d" data-param-slots-b64="%s" data-body-preview="%s"',
        e($t['language'] ?? ''),
        $paramCount,
        e(base64_encode($slotJson)),
        e($preview)
    );
}

function admin_whatsapp_send_template(string $to, string $tpl, string $lang): array
{
    return whatsapp_send_template_message($to, $tpl, whatsapp_normalize_template_language($lang), [], admin_whatsapp_template_body_params_from_post());
}

function admin_whatsapp_last_outbound(PDO $pdo, string $waId): ?array
{
    $variants = whatsapp_wa_id_variants($waId);
    [$sql, $params] = whatsapp_wa_id_in_clause('recipient_wa_id', $variants);
    $stmt = $pdo->prepare(
        "SELECT template_name, message_type, delivery_status, last_error_message, wa_message_id, updated_at
         FROM whatsapp_outbound_messages WHERE {$sql} ORDER BY id DESC LIMIT 1"
    );
    $stmt->execute($params);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);

    return $row ?: null;
}

function admin_whatsapp_delivery_status_label(string $status): string
{
    return match (strtolower($status)) {
        'delivered' => 'Delivered to phone',
        'read' => 'Read by customer',
        'sent' => 'Accepted by Meta (delivery pending)',
        'failed' => 'Failed to deliver',
        default => ucfirst($status ?: 'unknown'),
    };
}

function admin_whatsapp_delivery_status_short(string $status): string
{
    return match (strtolower($status)) {
        'delivered' => 'Delivered',
        'read' => 'Read',
        'sent' => 'Sent',
        'failed' => 'Failed',
        default => ucfirst($status ?: ''),
    };
}

function admin_whatsapp_outbound_kind(array $row): string
{
    $mt = strtolower((string) ($row['message_type'] ?? ''));
    if ($mt === 'template' || !empty($row['template_name'])) {
        return 'template';
    }
    if ($mt === 'text') {
        return 'text';
    }

    return 'other';
}

/**
 * @return array{main:list<array>, templates:list<array>, failed:list<array>}
 */
function admin_whatsapp_partition_chat_thread(array $timeline, bool $conversationStarted): array
{
    $main = [];
    $templates = [];
    $failed = [];
    foreach ($timeline as $item) {
        if ($item['dir'] === 'in') {
            $main[] = $item;
            continue;
        }
        $status = strtolower((string) ($item['status'] ?? ''));
        if ($status === 'failed') {
            $failed[] = $item;
            continue;
        }
        $kind = (string) ($item['out_kind'] ?? 'other');
        if ($kind === 'template') {
            $templates[] = $item;
            continue;
        }
        $main[] = $item;
    }
    if (!$conversationStarted && $main === [] && $templates !== []) {
        $main[] = array_pop($templates);
    }

    return ['main' => $main, 'templates' => $templates, 'failed' => $failed];
}

function admin_whatsapp_bubble_display_body(array $item): string
{
    if ($item['dir'] === 'in') {
        return (string) $item['body'];
    }
    if (($item['out_kind'] ?? '') === 'template') {
        $name = (string) ($item['template_name'] ?? '');
        if ($name === '') {
            $name = preg_replace('/^Template:\s*/i', '', (string) $item['body']);
        }

        return $name !== '' ? $name : (string) $item['body'];
    }

    return (string) $item['body'];
}

function admin_whatsapp_avatar_initials(?string $name, string $waId): string
{
    $name = trim((string) $name);
    if ($name !== '') {
        $parts = preg_split('/\s+/', $name, 3);
        if (count($parts) >= 2) {
            return strtoupper(substr($parts[0], 0, 1) . substr($parts[1], 0, 1));
        }

        return strtoupper(substr($name, 0, 2));
    }

    return strtoupper(substr(preg_replace('/\D/', '', $waId), -2));
}

/**
 * @return list<array{wa_id:string,last_at:string,contact_name:?string,preview:string}>
 */
function admin_whatsapp_conversation_summaries(PDO $pdo, int $limit = 100): array
{
    $limit = max(1, min(200, $limit));
    $stmt = $pdo->query(
        'SELECT wa_id, MAX(last_at) AS last_at FROM (
            SELECT from_wa_id AS wa_id, created_at AS last_at FROM whatsapp_inbound_messages
            UNION ALL
            SELECT recipient_wa_id, updated_at FROM whatsapp_outbound_messages
         ) e GROUP BY wa_id ORDER BY last_at DESC LIMIT ' . $limit
    );
    $peers = $stmt->fetchAll(PDO::FETCH_ASSOC);
    $out = [];
    foreach ($peers as $peer) {
        $waId = (string) ($peer['wa_id'] ?? '');
        if ($waId === '') {
            continue;
        }
        $variants = whatsapp_wa_id_variants($waId);
        [$inSql, $inParams] = whatsapp_wa_id_in_clause('from_wa_id', $variants);
        $nameStmt = $pdo->prepare(
            "SELECT contact_name, body_text FROM whatsapp_inbound_messages
             WHERE {$inSql} ORDER BY id DESC LIMIT 1"
        );
        $nameStmt->execute($inParams);
        $inRow = $nameStmt->fetch(PDO::FETCH_ASSOC) ?: [];
        $contactName = trim((string) ($inRow['contact_name'] ?? ''));
        $preview = trim((string) ($inRow['body_text'] ?? ''));

        if ($preview === '') {
            [$outSql, $outParams] = whatsapp_wa_id_in_clause('recipient_wa_id', $variants);
            $outStmt = $pdo->prepare(
                "SELECT message_type, template_name, payload_json, delivery_status
                 FROM whatsapp_outbound_messages WHERE {$outSql} ORDER BY id DESC LIMIT 1"
            );
            $outStmt->execute($outParams);
            $oRow = $outStmt->fetch(PDO::FETCH_ASSOC);
            if (is_array($oRow)) {
                $preview = admin_whatsapp_excerpt(admin_whatsapp_outbound_preview($oRow), 72);
            }
        }

        $out[] = [
            'wa_id' => $waId,
            'last_at' => (string) ($peer['last_at'] ?? ''),
            'contact_name' => $contactName !== '' ? $contactName : null,
            'preview' => $preview !== '' ? $preview : '—',
        ];
    }

    return $out;
}

function admin_whatsapp_format_list_time(string $datetime): string
{
    $ts = strtotime($datetime);
    if ($ts === false) {
        return $datetime;
    }
    if (date('Y-m-d', $ts) === date('Y-m-d')) {
        return date('g:i A', $ts);
    }
    if (date('Y', $ts) === date('Y')) {
        return date('M j · g:i A', $ts);
    }

    return date('M j, Y', $ts);
}

function admin_whatsapp_bubble_display_meta(array $item): string
{
    $time = (string) ($item['label'] ?? '');
    if (preg_match('/^\d{4}-\d{2}-\d{2}\s+(\d{2}:\d{2})/', $time, $m)) {
        $time = $m[1];
    }
    if ($item['dir'] === 'in') {
        return $time;
    }
    $status = strtolower((string) ($item['status'] ?? ''));
    if ($status === 'failed') {
        return admin_whatsapp_excerpt((string) ($item['meta'] ?? 'Failed'), 80);
    }
    $short = admin_whatsapp_delivery_status_short($status);
    if ($short === '' || ($item['out_kind'] ?? '') === 'template') {
        return $short !== '' ? $short . ' · ' . $time : $time;
    }

    return $short . ' · ' . $time;
}

function admin_whatsapp_chat_timeline(PDO $pdo, string $waId): array
{
    $waId = whatsapp_resolve_chat_wa_id($pdo, $waId);
    $variants = whatsapp_wa_id_variants($waId);
    $items = [];

    [$inSql, $inParams] = whatsapp_wa_id_in_clause('from_wa_id', $variants);
    $in = $pdo->prepare(
        "SELECT id, body_text, message_type, media_json, wa_timestamp, created_at, wa_message_id
         FROM whatsapp_inbound_messages WHERE {$inSql} ORDER BY id ASC"
    );
    $in->execute($inParams);
    foreach ($in->fetchAll() as $row) {
        $body = $row['body_text'] ?: ('[' . $row['message_type'] . ' message]');
        $media = json_decode((string) ($row['media_json'] ?? ''), true);
        if (is_array($media) && !empty($media['id'])) {
            $body .= ' [media: whatsapp_media.php?inbound_id=' . (int) $row['id'] . ']';
        }
        $items[] = [
            'dir' => 'in',
            'time' => $row['wa_timestamp'] ? (int) $row['wa_timestamp'] : strtotime($row['created_at']),
            'label' => $row['created_at'],
            'body' => $body,
            'meta' => $row['message_type'],
            'inbound_wamid' => (string) ($row['wa_message_id'] ?? ''),
        ];
    }

    [$outSql, $outParams] = whatsapp_wa_id_in_clause('recipient_wa_id', $variants);
    $out = $pdo->prepare(
        "SELECT payload_json, template_name, message_type, delivery_status, last_error_message, created_at, updated_at
         FROM whatsapp_outbound_messages WHERE {$outSql} ORDER BY id ASC"
    );
    $out->execute($outParams);
    foreach ($out->fetchAll() as $row) {
        $meta = (string) ($row['delivery_status'] ?: 'sent');
        if ($meta === 'failed' && !empty($row['last_error_message'])) {
            $meta .= ' — ' . admin_whatsapp_excerpt((string) $row['last_error_message'], 100);
        }
        $status = strtolower((string) ($row['delivery_status'] ?? 'sent'));
        $outKind = admin_whatsapp_outbound_kind($row);
        $items[] = [
            'dir' => 'out',
            'time' => strtotime($row['updated_at'] ?: $row['created_at']),
            'label' => $row['updated_at'] ?: $row['created_at'],
            'body' => admin_whatsapp_outbound_preview($row),
            'meta' => $meta,
            'status' => $status,
            'out_kind' => $outKind,
            'template_name' => (string) ($row['template_name'] ?? ''),
            'message_type' => (string) ($row['message_type'] ?? ''),
        ];
    }

    usort($items, static fn ($a, $b) => $a['time'] <=> $b['time']);

    return $items;
}

$pdo = db();
$tablesReady = admin_whatsapp_tables_ready($pdo);

if ($tablesReady && $_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verify_csrf($_POST['csrf_token'] ?? null)) {
        flash_set('error', 'Invalid CSRF token.');
        header('Location: whatsapp.php');
        exit;
    }

    $action = $_POST['action'] ?? '';
    $redirect = 'whatsapp.php';

    if ($action === 'send_text') {
        $to = whatsapp_normalize_recipient((string) ($_POST['to'] ?? ''));
        $text = trim((string) ($_POST['text'] ?? ''));
        $redirect = 'whatsapp.php?view=chat&wa_id=' . rawurlencode($to);
        $replyTo = trim((string) ($_POST['reply_to_wamid'] ?? ''));
        $result = whatsapp_send_text_message($to, $text, false, $replyTo !== '' ? $replyTo : null);
        flash_set($result['ok'] ? 'success' : 'error', $result['ok'] ? 'Message sent.' : ($result['error'] ?? 'Send failed.'));
    } elseif ($action === 'send_template') {
        $to = whatsapp_normalize_recipient((string) ($_POST['to'] ?? ''));
        $tpl = preg_replace('/[^a-z0-9_]/i', '', (string) ($_POST['template_name'] ?? ''));
        $lang = whatsapp_normalize_template_language((string) ($_POST['language_code'] ?? ''));
        if ($tpl === '') {
            $tpl = whatsapp_default_template_name();
        }
        if ($lang === '') {
            $lang = whatsapp_default_template_language();
        }
        $redirect = 'whatsapp.php?view=chat&wa_id=' . rawurlencode($to);
        $result = admin_whatsapp_send_template($to, $tpl, $lang);
        flash_set(
            $result['ok'] ? 'success' : 'error',
            $result['ok']
                ? 'Meta accepted the template. Refresh in a few seconds to see delivered/failed (webhook). If the phone never gets it, check Meta test numbers & WhatsApp on 917903152429.'
                : ($result['error'] ?? 'Send failed.')
        );
    } elseif ($action === 'compose_template') {
        whatsapp_fetch_message_templates_result(true);
        $to = whatsapp_normalize_recipient((string) ($_POST['to'] ?? ''));
        if (empty($_POST['consent'])) {
            flash_set('error', 'Confirm WhatsApp messaging consent before sending.');
            header('Location: whatsapp.php?view=compose');
            exit;
        }
        if ($to === '') {
            flash_set('error', 'Enter a valid WhatsApp number with country code (e.g. 917903152429).');
            header('Location: whatsapp.php?view=compose');
            exit;
        }
        $tpl = preg_replace('/[^a-z0-9_]/i', '', (string) ($_POST['template_name'] ?? whatsapp_default_template_name()));
        $lang = whatsapp_normalize_template_language((string) ($_POST['language_code'] ?? whatsapp_default_template_language()));
        $params = admin_whatsapp_template_body_params_from_post();
        $def = whatsapp_lookup_template_definition($tpl, $lang);
        if ($def === null) {
            flash_set(
                'error',
                'Template "' . $tpl . '" (' . $lang . ') was not found on your WhatsApp Business account. Open WhatsApp → Templates and confirm APPROVED status and language code.'
            );
            header('Location: whatsapp.php?view=compose');
            exit;
        }
        $lang = whatsapp_normalize_template_language((string) $def['language']);
        $pf = strtolower((string) ($def['parameter_format'] ?? ''));
        $slots = $def['param_slots'] ?? whatsapp_template_param_slots($def['components'] ?? [], $pf);
        $needed = count($slots);
        $filled = count(array_filter($params, static fn ($v) => trim((string) $v) !== ''));
        if ($needed > 0 && $filled < $needed) {
            $labels = array_map(static fn ($s) => (string) ($s['label'] ?? 'variable'), $slots);
            flash_set(
                'error',
                'Template "' . $tpl . '" needs ' . $needed . ' variable(s); you filled ' . $filled . '. Required: '
                . implode('; ', $labels)
                . '. (If fields were hidden, hard-refresh Ctrl+F5 after git pull.)'
            );
            header('Location: whatsapp.php?view=compose');
            exit;
        }
        $result = admin_whatsapp_send_template($to, $tpl, $lang);
        if ($result['ok']) {
            $redirect = 'whatsapp.php?view=chat&wa_id=' . rawurlencode($to);
            flash_set(
                'success',
                'Meta accepted template "' . $tpl . '" (language ' . $lang . '). '
                . '302 redirect is normal — open the customer chat and check Latest send at the top.'
            );
        } else {
            $redirect = 'whatsapp.php?view=compose';
            flash_set('error', (string) ($result['error'] ?? 'Send failed.'));
        }
    } elseif ($action === 'send_image') {
        $to = whatsapp_normalize_recipient((string) ($_POST['to'] ?? ''));
        $redirect = 'whatsapp.php?view=chat&wa_id=' . rawurlencode($to);
        $result = whatsapp_send_image_link($to, (string) ($_POST['image_url'] ?? ''), (string) ($_POST['caption'] ?? ''));
        flash_set($result['ok'] ? 'success' : 'error', $result['ok'] ? 'Image sent.' : ($result['error'] ?? 'Send failed.'));
    } elseif ($action === 'send_document') {
        $to = whatsapp_normalize_recipient((string) ($_POST['to'] ?? ''));
        $redirect = 'whatsapp.php?view=chat&wa_id=' . rawurlencode($to);
        $result = whatsapp_send_document_link(
            $to,
            (string) ($_POST['document_url'] ?? ''),
            (string) ($_POST['filename'] ?? ''),
            (string) ($_POST['caption'] ?? '')
        );
        flash_set($result['ok'] ? 'success' : 'error', $result['ok'] ? 'Document sent.' : ($result['error'] ?? 'Send failed.'));
    } elseif ($action === 'create_lead') {
        $to = whatsapp_normalize_recipient((string) ($_POST['to'] ?? ''));
        $redirect = 'whatsapp.php?view=chat&wa_id=' . rawurlencode($to);
        $result = whatsapp_create_lead_from_chat($to);
        flash_set($result['ok'] ? 'success' : 'error', $result['ok'] ? 'Lead created.' : ($result['error'] ?? 'Failed.'));
    } elseif ($action === 'quick_reply') {
        $to = whatsapp_normalize_recipient((string) ($_POST['to'] ?? ''));
        $redirect = 'whatsapp.php?view=chat&wa_id=' . rawurlencode($to);
        $result = whatsapp_send_text_message($to, trim((string) ($_POST['text'] ?? '')));
        flash_set($result['ok'] ? 'success' : 'error', $result['ok'] ? 'Quick reply sent.' : ($result['error'] ?? 'Send failed.'));
    }

    header('Location: ' . $redirect);
    exit;
}

$config = whatsapp_cloud_config();
$verifyOk = trim($config['webhook_verify_token']) !== '';
$secretOk = trim($config['app_secret']) !== '';
$tokenOk = trim($config['access_token']) !== '';
$phoneOk = trim($config['phone_number_id']) !== '';

$view = $_GET['view'] ?? 'chats';
$allowedViews = ['chats', 'chat', 'compose', 'templates', 'template_inspect', 'status', 'outbound', 'setup'];
if (!in_array($view, $allowedViews, true)) {
    $view = 'chats';
}

$chatWaId = isset($_GET['wa_id']) ? whatsapp_normalize_recipient((string) $_GET['wa_id']) : '';

if ($tablesReady && $view === 'chat' && $chatWaId !== '') {
    try {
        $chatWaId = whatsapp_resolve_chat_wa_id($pdo, $chatWaId);
        $chatVariants = whatsapp_wa_id_variants($chatWaId);
        [$nameInSql, $nameInParams] = whatsapp_wa_id_in_clause('from_wa_id', $chatVariants);
        $nameStmt = $pdo->prepare(
            "SELECT contact_name FROM whatsapp_inbound_messages WHERE {$nameInSql}
             AND contact_name IS NOT NULL AND contact_name != '' ORDER BY id DESC LIMIT 1"
        );
        $nameStmt->execute($nameInParams);
        $contactName = (string) ($nameStmt->fetchColumn() ?: '');
        $windowOpen = whatsapp_service_window_open($pdo, $chatWaId);
        $timeline = admin_whatsapp_chat_timeline($pdo, $chatWaId);
        $hasInboundEarly = whatsapp_chat_has_inbound($pdo, $chatWaId);
        $threadParts = admin_whatsapp_partition_chat_thread($timeline, $hasInboundEarly);
        $threadMain = $threadParts['main'];
        $threadTemplateArchive = $threadParts['templates'];
        $threadFailedArchive = $threadParts['failed'];
        $lastInbound = $pdo->prepare(
            "SELECT wa_message_id FROM whatsapp_inbound_messages WHERE {$nameInSql} ORDER BY id DESC LIMIT 1"
        );
        $lastInbound->execute($nameInParams);
        $lastWamid = (string) ($lastInbound->fetchColumn() ?: '');
    } catch (Throwable $e) {
        error_log('WhatsApp admin chat load failed: ' . $e->getMessage());
        admin_header('WhatsApp', 'whatsapp.php');
        echo '<div class="alert alert-error">Could not load this chat. Run <code>php sql/migrate_whatsapp_webhook.php</code> on the server (adds missing columns/tables). If the problem continues, check the PHP error log.</div>';
        echo '<p class="muted">' . e($e->getMessage()) . '</p>';
        echo '<p><a class="btn btn-secondary" href="whatsapp.php?view=chats">Back to chats</a></p>';
        admin_footer();
        exit;
    }
    if (!empty($lastWamid)) {
        try {
            whatsapp_mark_message_read($lastWamid);
        } catch (Throwable $e) {
            error_log('WhatsApp mark read skipped: ' . $e->getMessage());
        }
    }
    $quickReplies = [
        'Thanks for contacting Digital Creatorss. Our team will reply shortly.',
        'Please share your project requirements or preferred time for a call.',
        'We received your message and will get back to you within one business day.',
    ];
    try {
        $tplResult = whatsapp_fetch_message_templates_result();
        $tplList = $tplResult['approved'];
        $tplFetchError = (string) ($tplResult['error'] ?? '');
    } catch (Throwable $e) {
        error_log('WhatsApp template list skipped: ' . $e->getMessage());
        $tplList = [];
        $tplFetchError = $e->getMessage();
    }
    $preferredTpl = whatsapp_preferred_template($tplList);
    $defaultTpl = $preferredTpl['name'] ?? whatsapp_default_template_name();
    $defaultLang = $preferredTpl['language'] ?? whatsapp_default_template_language();
    $hasInbound = $hasInboundEarly;
    $lastOutbound = admin_whatsapp_last_outbound($pdo, $chatWaId);
    $lastStatus = strtolower((string) ($lastOutbound['delivery_status'] ?? ''));
    $showDeliveryBanner = $lastOutbound && ($lastStatus === 'failed' || ($lastStatus === 'sent' && !$hasInbound));

    $chatInitials = admin_whatsapp_avatar_initials($contactName ?: null, $chatWaId);
    admin_header($contactName ?: $chatWaId, 'whatsapp.php');
    ?>
    <div class="wa-page-wrap">
    <div class="panel wa-chat-panel">
      <div class="wa-chat-head">
        <a class="wa-chat-back" href="whatsapp.php?view=chats" title="All chats">←</a>
        <div class="wa-chat-avatar" aria-hidden="true"><?= e($chatInitials) ?></div>
        <div class="wa-chat-head-text">
          <strong class="wa-chat-title"><?= e($contactName ?: 'Customer') ?></strong>
          <span class="wa-chat-sub"><?= e($chatWaId) ?></span>
        </div>
        <div class="wa-chat-head-actions">
          <span class="wa-window-pill <?= $windowOpen ? 'is-open' : 'is-closed' ?>">
            <?= $windowOpen ? 'Reply open' : 'Template only' ?>
          </span>
          <form method="post" class="inline"><?= csrf_field() ?><input type="hidden" name="action" value="create_lead"><input type="hidden" name="to" value="<?= e($chatWaId) ?>"><button type="submit" class="btn btn-sm btn-secondary">Lead</button></form>
        </div>
      </div>
      <?php if ($showDeliveryBanner): ?>
      <div class="wa-delivery-banner wa-delivery-banner-compact">
        <span class="wa-status-<?= e(preg_replace('/[^a-z]/', '', $lastStatus)) ?>"><?= e(admin_whatsapp_delivery_status_short($lastStatus)) ?></span>
        · <?= e($lastOutbound['template_name'] ?: $lastOutbound['message_type']) ?>
        <?php if ($lastStatus === 'failed' && !empty($lastOutbound['last_error_message'])): ?>
          — <?= e(admin_whatsapp_excerpt((string) $lastOutbound['last_error_message'], 120)) ?>
        <?php endif; ?>
        <a class="wa-delivery-link" href="whatsapp.php?view=outbound">Full log</a>
      </div>
      <?php endif; ?>
      <?php if (!$hasInbound && !empty($tplFetchError)): ?>
      <div class="wa-chat-notices wa-chat-notices-warn"><?= e($tplFetchError) ?></div>
      <?php endif; ?>
      <div class="wa-chat-thread" id="wa-chat-thread">
        <?php if (!$threadMain && !$threadTemplateArchive && !$threadFailedArchive): ?>
          <p class="muted wa-thread-empty">No messages yet. Send a template below to start.</p>
        <?php endif; ?>
        <?php foreach ($threadMain as $item): ?>
          <?php
          $bubbleClass = 'wa-bubble-' . $item['dir'];
          if (($item['out_kind'] ?? '') === 'template') {
              $bubbleClass .= ' wa-bubble-template';
          }
          ?>
          <div class="wa-bubble <?= e($bubbleClass) ?>">
            <?php if (($item['out_kind'] ?? '') === 'template'): ?>
              <div class="wa-bubble-tag">Template</div>
            <?php endif; ?>
            <div class="wa-bubble-body"><?= nl2br(e(admin_whatsapp_bubble_display_body($item))) ?></div>
            <div class="wa-bubble-meta"><?= e(admin_whatsapp_bubble_display_meta($item)) ?></div>
          </div>
        <?php endforeach; ?>
        <?php if ($threadTemplateArchive): ?>
        <details class="wa-thread-archive">
          <summary>Past template sends (<?= count($threadTemplateArchive) ?>)</summary>
          <div class="wa-thread-archive-list">
            <?php foreach ($threadTemplateArchive as $item): ?>
            <div class="wa-bubble wa-bubble-out wa-bubble-template wa-bubble-compact">
              <div class="wa-bubble-tag">Template</div>
              <div class="wa-bubble-body"><?= e(admin_whatsapp_bubble_display_body($item)) ?></div>
              <div class="wa-bubble-meta"><?= e(admin_whatsapp_bubble_display_meta($item)) ?></div>
            </div>
            <?php endforeach; ?>
          </div>
        </details>
        <?php endif; ?>
        <?php if ($threadFailedArchive): ?>
        <details class="wa-failed-archive">
          <summary>Failed sends (<?= count($threadFailedArchive) ?>)</summary>
          <div class="wa-failed-archive-list">
            <?php foreach ($threadFailedArchive as $item): ?>
            <div class="wa-bubble wa-bubble-out wa-bubble-failed wa-bubble-compact">
              <div class="wa-bubble-body"><?= e(admin_whatsapp_bubble_display_body($item)) ?></div>
              <div class="wa-bubble-meta"><?= e(admin_whatsapp_bubble_display_meta($item)) ?></div>
            </div>
            <?php endforeach; ?>
          </div>
        </details>
        <?php endif; ?>
      </div>
      <div class="wa-chat-compose">
        <?php if ($windowOpen): ?>
        <form method="post" class="wa-reply-form">
          <?= csrf_field() ?>
          <input type="hidden" name="action" value="send_text">
          <input type="hidden" name="to" value="<?= e($chatWaId) ?>">
          <?php if (!empty($lastWamid)): ?>
          <input type="hidden" name="reply_to_wamid" value="<?= e($lastWamid) ?>">
          <?php endif; ?>
          <div class="wa-reply-compose">
            <label class="wa-reply-label" for="wa-reply-text">Reply</label>
            <textarea
              id="wa-reply-text"
              class="wa-reply-textarea"
              name="text"
              rows="5"
              placeholder="Type your reply to the customer…"
              required
            ></textarea>
            <div class="wa-reply-actions">
              <p class="wa-reply-hint muted">Shift+Enter new line · Enter to send</p>
              <button class="btn wa-send-btn" type="submit" aria-label="Send message">Send message</button>
            </div>
          </div>
        </form>
        <details class="wa-compose-more">
          <summary>Quick replies</summary>
          <div class="wa-quick-replies">
            <?php foreach ($quickReplies as $qr): ?>
            <form method="post" class="inline"><?= csrf_field() ?><input type="hidden" name="action" value="quick_reply"><input type="hidden" name="to" value="<?= e($chatWaId) ?>"><input type="hidden" name="text" value="<?= e($qr) ?>"><button type="submit" class="btn btn-sm btn-secondary"><?= e(admin_whatsapp_excerpt($qr, 36)) ?></button></form>
            <?php endforeach; ?>
          </div>
        </details>
        <details class="wa-compose-more">
          <summary>Template, image, or document</summary>
          <div class="wa-compose-more-inner">
        <?php else: ?>
        <p class="muted wa-compose-hint">24h window closed — send an approved template to reach this customer again.</p>
        <div class="wa-compose-more-inner wa-compose-more-open">
        <?php endif; ?>
        <form method="post" class="form-grid wa-subform">
          <?= csrf_field() ?>
          <input type="hidden" name="action" value="send_template">
          <input type="hidden" name="to" value="<?= e($chatWaId) ?>">
          <?php if ($tplList): ?>
          <label>Template</label>
          <select name="template_name" class="wa-tpl-select" id="wa-chat-tpl-select">
            <?php foreach ($tplList as $t): ?>
              <?php
              $sel = $t['name'] === $defaultTpl && $t['language'] === $defaultLang;
              $paramCount = (int) ($t['param_count'] ?? 0);
              ?>
              <option value="<?= e($t['name']) ?>" <?= admin_whatsapp_template_option_attrs($t) ?> <?= $sel ? 'selected' : '' ?>>
                <?= e($t['name']) ?><?= $paramCount ? " · {$paramCount} var" : '' ?>
              </option>
            <?php endforeach; ?>
          </select>
          <input type="hidden" name="language_code" id="wa-lang-code" value="<?= e($defaultLang) ?>">
          <div id="wa-chat-tpl-params" class="form-grid"></div>
          <?php else: ?>
          <div class="form-row">
            <div><input type="text" name="template_name" value="<?= e($defaultTpl) ?>" placeholder="hello_world"></div>
            <div><input type="text" name="language_code" value="<?= e($defaultLang) ?>" placeholder="en_US"></div>
          </div>
          <?php endif; ?>
          <button class="btn btn-secondary btn-sm" type="submit">Send template</button>
        </form>
        <?php if ($windowOpen): ?>
        <form method="post" class="form-grid wa-subform">
          <?= csrf_field() ?>
          <input type="hidden" name="action" value="send_image">
          <input type="hidden" name="to" value="<?= e($chatWaId) ?>">
          <label>Image URL</label>
          <input type="url" name="image_url" placeholder="https://…">
          <input type="text" name="caption" placeholder="Caption (optional)">
          <button class="btn btn-secondary btn-sm" type="submit">Send image</button>
        </form>
        <form method="post" class="form-grid wa-subform">
          <?= csrf_field() ?>
          <input type="hidden" name="action" value="send_document">
          <input type="hidden" name="to" value="<?= e($chatWaId) ?>">
          <label>Document URL</label>
          <input type="url" name="document_url" placeholder="https://…">
          <input type="text" name="filename" placeholder="file.pdf">
          <button class="btn btn-secondary btn-sm" type="submit">Send document</button>
        </form>
        <?php endif; ?>
        <?php if ($windowOpen): ?>
          </div>
        </details>
        <?php else: ?>
        </div>
        <?php endif; ?>
      </div>
    </div>
    </div>
    <script src="assets/whatsapp-admin.js?v=20261014"></script>
    <script>window.dcWaInitChatUi && window.dcWaInitChatUi();</script>
    <?php if ($tplList): ?>
    <script>window.dcWaInitTemplateForm('wa-chat-tpl-select', 'wa-lang-code', 'wa-chat-tpl-params', '');</script>
    <?php endif; ?>
    <?php
    admin_footer();
    exit;
}

admin_header('WhatsApp', 'whatsapp.php');

$counts = ['inbound' => 0, 'status' => 0, 'outbound' => 0];
if ($tablesReady) {
    try {
        $counts['inbound'] = (int) $pdo->query('SELECT COUNT(*) FROM whatsapp_inbound_messages')->fetchColumn();
        $counts['status'] = (int) $pdo->query('SELECT COUNT(*) FROM whatsapp_message_status_log')->fetchColumn();
        $counts['outbound'] = (int) $pdo->query('SELECT COUNT(*) FROM whatsapp_outbound_messages')->fetchColumn();
    } catch (Throwable $e) {
        $tablesReady = false;
    }
}
?>
<div class="cards">
  <div class="card"><div class="label">Inbound</div><div class="value"><?= $tablesReady ? $counts['inbound'] : '—' ?></div></div>
  <div class="card"><div class="label">Sent from portal</div><div class="value"><?= $tablesReady ? $counts['outbound'] : '—' ?></div></div>
  <div class="card"><div class="label">API ready</div><div class="value" style="font-size:1rem"><?= ($tokenOk && $phoneOk) ? 'Yes' : 'No' ?></div></div>
</div>

<div class="panel" style="margin-bottom:16px">
  <nav class="wa-nav actions" style="margin-bottom:12px" aria-label="WhatsApp sections">
    <a class="btn btn-sm <?= $view === 'chats' ? '' : 'btn-secondary' ?>" href="whatsapp.php?view=chats">Chats</a>
    <a class="btn btn-sm <?= $view === 'compose' ? '' : 'btn-secondary' ?>" href="whatsapp.php?view=compose">New message</a>
    <a class="btn btn-sm <?= $view === 'templates' ? '' : 'btn-secondary' ?>" href="whatsapp.php?view=templates">Templates</a>
    <a class="btn btn-sm <?= $view === 'status' ? '' : 'btn-secondary' ?>" href="whatsapp.php?view=status">Delivery log</a>
    <a class="btn btn-sm <?= $view === 'outbound' ? '' : 'btn-secondary' ?>" href="whatsapp.php?view=outbound">Outbound</a>
    <a class="btn btn-sm <?= $view === 'setup' ? '' : 'btn-secondary' ?>" href="whatsapp.php?view=setup">Setup</a>
    <a class="btn btn-sm btn-secondary" href="whatsapp_campaigns.php">Campaigns</a>
    <a class="btn btn-sm btn-secondary" href="whatsapp_contacts.php">Contacts</a>
  </nav>

<?php if (!$tablesReady): ?>
  <div class="alert alert-error">
    WhatsApp tables are missing. Run: <code>php sql/migrate_whatsapp_webhook.php</code>
  </div>
<?php elseif ($view === 'setup'):
    require_once dirname(__DIR__) . '/includes/whatsapp_config_status.php';
    $conn = whatsapp_connection_status();
    $metaFail = $tablesReady ? whatsapp_last_template_graph_failure() : null;
    ?>
  <p class="muted" style="margin-top:0">Secrets live in private <code>digital_creators_config.php</code> (masked below).</p>
  <table>
    <tbody>
      <tr><th style="width:220px">Graph API version</th><td><?= e($conn['api_version']) ?></td></tr>
      <tr><th>Access token</th><td><?= $conn['access_token_set'] ? e($conn['access_token_masked']) : 'Missing' ?></td></tr>
      <tr><th>Phone number ID (send routing)</th><td><code><?= e($conn['phone_number_id']) ?></code></td></tr>
      <tr><th>Display number (Graph)</th><td><?= e($conn['display_phone_number']) ?><?= ($conn['verified_name'] ?? '') !== '—' ? ' · ' . e($conn['verified_name']) : '' ?></td></tr>
      <tr><th>Graph messages endpoint</th><td><code style="word-break:break-all"><?= e($conn['graph_messages_endpoint']) ?></code></td></tr>
      <tr><th>WABA ID (resolved)</th><td><?= e($conn['waba_id_resolved']) ?></td></tr>
      <tr><th>Webhook verify token</th><td><?= $conn['webhook_verify_token_set'] ? e($conn['webhook_verify_token_masked']) : 'Missing' ?></td></tr>
      <tr><th>App secret</th><td><?= $conn['app_secret_set'] ? e($conn['app_secret_masked']) : 'Missing' ?></td></tr>
      <tr><th>Graph API ping</th><td><?= $conn['graph_api_ping_ok'] ? 'OK' : e($conn['graph_api_ping_error'] ?: 'Failed') ?></td></tr>
      <tr><th>Default template</th><td><?= e(whatsapp_default_template_name()) ?> (<?= e(whatsapp_default_template_language()) ?>)</td></tr>
      <tr><th>Webhook URL</th><td><code><?= e($conn['webhook_url']) ?></code></td></tr>
      <tr><th>Health</th><td><a href="<?= e($conn['health_url']) ?>" target="_blank" rel="noopener"><?= e($conn['health_url']) ?></a></td></tr>
      <tr><th>Docs</th><td><a href="../docs/WHATSAPP_INTEGRATION.md" target="_blank" rel="noopener">WHATSAPP_INTEGRATION.md</a></td></tr>
    </tbody>
  </table>
  <div class="panel" style="margin-top:16px;background:#f8fafc">
    <h3 style="margin-top:0">For Meta Developer Support</h3>
    <p class="muted" style="margin-top:0">Copy the block below into your Meta ticket. After <code>git pull</code>, failed sends also log <code>graph_code</code>, <code>graph_subcode</code>, and <code>fbtrace_id</code> in PHP error log.</p>
    <?php if ($metaFail): ?>
    <pre style="background:#fff;border:1px solid #e2e8f0;padding:12px;border-radius:8px;font-size:0.82rem;white-space:pre-wrap"><?php
      $support = [
          'issue' => 'WhatsApp Cloud API template send (custom template)',
          'template_name' => $metaFail['template_name'],
          'recipient_wa_id' => $metaFail['recipient_wa_id'],
          'phone_number_id_used' => $metaFail['phone_number_id'],
          'graph_messages_endpoint' => $metaFail['graph_endpoint'] ?: $conn['graph_messages_endpoint'],
          'http_status' => $metaFail['http_code'],
          'graph_error_code' => $metaFail['graph_error_code'],
          'graph_error_subcode' => $metaFail['graph_error_subcode'],
          'fbtrace_id' => $metaFail['fbtrace_id'],
          'error_message' => $metaFail['last_error_message'],
          'logged_at_utc' => $metaFail['updated_at'],
          'waba_id' => $conn['waba_id_resolved'],
      ];
      echo e(json_encode($support, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
    ?></pre>
    <?php else: ?>
    <p class="muted">No failed template send in DB yet. Send <code>digital_creatorss_intro</code> once from Compose; if Graph rejects it, details appear here. Successful API accepts (HTTP 200) may still show delivery issues via webhook — check Delivery log.</p>
    <?php endif; ?>
    <p class="muted">Known errors from your project history: <strong>(#132000)</strong> parameter count mismatch on <code>digital_creatorss_intro</code>; older <strong>Authentication Error</strong> on <code>hello_world</code> (token fixed later).</p>
  </div>
  <?php
    require_once dirname(__DIR__) . '/includes/whatsapp_webhook_diagnostics.php';
    $subApps = whatsapp_fetch_subscribed_apps();
    $trace = whatsapp_webhook_trace_read(15);
    $inboundTotal = 0;
    if ($tablesReady) {
        try {
            $inboundTotal = (int) $pdo->query('SELECT COUNT(*) FROM whatsapp_inbound_messages')->fetchColumn();
        } catch (Throwable $e) {
            $inboundTotal = -1;
        }
    }
    ?>
  <div class="panel" style="margin-top:16px;background:#fff">
    <h3 style="margin-top:0">Incoming messages (webhook)</h3>
    <p class="muted" style="margin-top:0">Callback URL: <code>https://digitalcreatorss.com/api/hook-wa</code> → <code>api/hook-wa.php</code> → <code>webhook/whatsapp.php</code>. Subscribe <strong>messages</strong> (inbound + delivery statuses) and <strong>message_echoes</strong> (business sends from Meta / WhatsApp Business app — shows text in admin chat). <code>value.statuses[]</code> alone creates a chat row with a short note if the send was outside the portal.</p>
    <table>
      <tbody>
        <tr><th style="width:220px">Inbound rows in DB</th><td><?= $inboundTotal >= 0 ? (string) $inboundTotal : '— (tables missing?)' ?></td></tr>
        <tr><th>Meta app secret set</th><td><?= $conn['app_secret_set'] ? 'Yes (required for POST signature)' : 'No — POST webhooks will return 500' ?></td></tr>
        <tr><th>WABA subscribed apps</th><td>
          <?php if (!$subApps['ok']): ?>
            <span class="muted"><?= e($subApps['error'] ?? 'Could not query Graph.') ?></span>
          <?php elseif (empty($subApps['apps'])): ?>
            <strong style="color:#b91c1c">None — subscribe your app to the WABA in Meta Dashboard</strong>
          <?php else: ?>
            <?php foreach ($subApps['apps'] as $app): ?>
              <div><code><?= e($app['id'] ?? '') ?></code> <?= e($app['name'] ?? '') ?></div>
            <?php endforeach; ?>
          <?php endif; ?>
        </td></tr>
      </tbody>
    </table>
    <p class="muted">Server CLI: <code>php scripts/wa_webhook_subscribed_check.php</code></p>
    <h4>Recent webhook POST trace (no message bodies)</h4>
    <?php if (!$trace): ?>
      <p class="muted">No trace yet. After <code>git pull</code>, send &quot;Hello&quot; from a personal WhatsApp to your business number; lines should appear here and in PHP error log.</p>
    <?php else: ?>
    <table>
      <thead><tr><th>Time (UTC)</th><th>HTTP</th><th>Messages</th><th>Statuses</th><th>Fields</th><th>Note</th></tr></thead>
      <tbody>
      <?php foreach (array_reverse($trace) as $row): ?>
        <tr>
          <td><?= e((string) ($row['ts'] ?? '')) ?></td>
          <td><?= e((string) ($row['result'] ?? '')) ?></td>
          <td><?= e((string) ($row['messages'] ?? '—')) ?></td>
          <td><?= e((string) ($row['statuses'] ?? '—')) ?></td>
          <td><?= e((string) ($row['fields'] ?? '')) ?></td>
          <td class="muted"><?= e((string) ($row['reason'] ?? 'ok')) ?></td>
        </tr>
      <?php endforeach; ?>
      </tbody>
    </table>
    <?php endif; ?>
  </div>
<?php elseif ($view === 'compose'):
    $composeTplResult = whatsapp_fetch_message_templates_result();
    $composeTplList = $composeTplResult['approved'];
    $composePreferred = whatsapp_preferred_template($composeTplList);
    $composeDefaultTpl = $composePreferred['name'] ?? whatsapp_default_template_name();
    $composeDefaultLang = $composePreferred['language'] ?? whatsapp_default_template_language();
    ?>
  <?php if (!empty($composeTplResult['error'])): ?>
    <div class="alert alert-error"><?= e($composeTplResult['error']) ?></div>
  <?php endif; ?>
  <p class="muted">Start a conversation with an approved <strong>template</strong>. Only message users who expect contact from you. See <a href="../terms.php" target="_blank" rel="noopener">Terms</a> and <a href="../privacy.php" target="_blank" rel="noopener">Privacy</a>.</p>
  <div class="alert" style="background:#f0f9ff;border:1px solid #bae6fd;color:#0c4a6e;font-size:0.85rem;margin-bottom:12px">
    <strong>About “302 Found” in browser DevTools:</strong> that is <em>normal</em> after you click Send — the server redirects you to the chat page (or back here if something failed). It is not an error by itself. Read the <strong>green or red banner</strong> at the top after the page loads.
  </div>
  <form method="post" action="whatsapp.php?view=compose" class="form-grid" style="max-width:480px">
    <?= csrf_field() ?>
    <input type="hidden" name="action" value="compose_template">
    <label>Customer WhatsApp number (country code, digits only)</label>
    <input type="text" name="to" placeholder="918851613806" required pattern="[0-9]{11,15}" title="Country code + number, e.g. 91 then 10-digit mobile">
    <p class="muted" style="margin:0">Include country code (India: <code>91</code> + 10 digits). Example: <code>918851613806</code>, not only <code>8851613806</code>.</p>
    <label class="checkbox-row">
      <input type="checkbox" name="consent" value="1" required>
      <span>I confirm this person consented to WhatsApp messages from Digital Creatorss.</span>
    </label>
    <?php if ($composeTplList): ?>
    <label>Approved template</label>
    <select name="template_name" class="wa-tpl-select" id="compose-tpl-select">
      <?php foreach ($composeTplList as $t): ?>
        <?php
        $sel = $t['name'] === $composeDefaultTpl && $t['language'] === $composeDefaultLang;
        $paramCount = (int) ($t['param_count'] ?? 0);
        ?>
        <option value="<?= e($t['name']) ?>" <?= admin_whatsapp_template_option_attrs($t) ?> <?= $sel ? 'selected' : '' ?>>
          <?= e($t['name']) ?> (<?= e($t['language']) ?>) — <?= e($t['category']) ?><?= $paramCount ? " · {$paramCount} var(s)" : '' ?>
        </option>
      <?php endforeach; ?>
    </select>
    <input type="hidden" name="language_code" id="compose-lang-code" value="<?= e($composeDefaultLang) ?>">
    <div id="compose-tpl-params" class="form-grid" style="margin-top:8px"></div>
    <p class="muted" id="compose-tpl-preview" style="margin:4px 0 0"></p>
    <?php else: ?>
    <div class="form-row">
      <div>
        <label>Template name</label>
        <input type="text" name="template_name" value="<?= e($composeDefaultTpl) ?>">
      </div>
      <div>
        <label>Language code</label>
        <input type="text" name="language_code" value="<?= e($composeDefaultLang) ?>">
      </div>
    </div>
    <?php endif; ?>
    <button class="btn" type="submit">Send template</button>
  </form>
  <?php if ($composeTplList): ?>
  <script src="assets/whatsapp-admin.js?v=20261012"></script>
  <script>window.dcWaInitTemplateForm('compose-tpl-select', 'compose-lang-code', 'compose-tpl-params', 'compose-tpl-preview');</script>
  <?php endif; ?>
<?php elseif ($view === 'templates'):
    $tplResult = whatsapp_fetch_message_templates_result();
    $tplList = $tplResult['templates'];
    $tplApproved = $tplResult['approved'];
    $tplWaba = (string) ($tplResult['waba_id'] ?? whatsapp_waba_id());
    ?>
  <p class="muted">Meta Graph API · WABA <code><?= e($tplWaba ?: '—') ?></code> · <?= count($tplApproved) ?> approved / <?= count($tplList) ?> total</p>
  <?php if (!empty($tplResult['error'])): ?>
    <div class="alert alert-error"><?= e($tplResult['error']) ?></div>
  <?php endif; ?>
  <p class="muted" style="margin-top:0">Custom templates often use <strong>named</strong> variables (<code>{{customer_name}}</code>). <code>hello_world</code> has none. Only <strong>APPROVED</strong> templates appear in the send dropdown.</p>
  <table>
    <thead><tr><th>Name</th><th>Language</th><th>Category</th><th>Variables</th><th>Status</th><th></th></tr></thead>
    <tbody>
    <?php if (!$tplList): ?>
      <tr><td colspan="6" class="muted">No templates to show. Fix the issue above, then refresh.</td></tr>
    <?php endif; ?>
    <?php foreach ($tplList as $t): ?>
      <?php
      $pc = (int) ($t['param_count'] ?? 0);
      $pf = (string) ($t['parameter_format'] ?? '');
      $varLabel = $pc === 0 ? 'none' : $pc . ($pf !== '' ? " ({$pf})" : '');
      ?>
      <tr>
        <td><code><?= e($t['name']) ?></code></td>
        <td><?= e($t['language']) ?></td>
        <td><?= e($t['category']) ?></td>
        <td class="muted"><?= e($varLabel) ?><?php if ($pc > 0 && !empty($t['body_preview'])): ?><br><span style="font-size:0.8rem"><?= e(admin_whatsapp_excerpt((string) $t['body_preview'], 60)) ?></span><?php endif; ?></td>
        <td><?= e($t['status']) ?></td>
        <td><a class="btn btn-sm btn-secondary" href="whatsapp.php?view=template_inspect&amp;name=<?= e($t['name']) ?>&amp;lang=<?= e($t['language']) ?>">Inspect</a></td>
      </tr>
    <?php endforeach; ?>
    </tbody>
  </table>
<?php elseif ($view === 'template_inspect'):
    whatsapp_fetch_message_templates_result(true);
    $inspectName = preg_replace('/[^a-z0-9_]/i', '', (string) ($_GET['name'] ?? ''));
    $inspectLang = whatsapp_normalize_template_language((string) ($_GET['lang'] ?? 'en_US'));
    $sampleParam = trim((string) ($_GET['sample'] ?? 'Ramesh'));
    $sampleValues = $sampleParam === '' ? [] : [$sampleParam];
    $inspectPreview = $inspectName !== ''
        ? whatsapp_template_send_payload_preview('917903152429', $inspectName, $inspectLang, $sampleValues)
        : ['ok' => false, 'error' => 'Missing template name.'];
    $inspectDef = $inspectName !== '' ? whatsapp_lookup_template_definition($inspectName, $inspectLang) : null;
    $recentOutbound = [];
    if ($tablesReady && $inspectName !== '') {
        try {
            $st = $pdo->prepare(
                'SELECT id, recipient_wa_id, delivery_status, last_error_message, payload_json, updated_at
                 FROM whatsapp_outbound_messages WHERE template_name = ? ORDER BY id DESC LIMIT 8'
            );
            $st->execute([$inspectName]);
            $recentOutbound = $st->fetchAll(PDO::FETCH_ASSOC);
        } catch (Throwable $e) {
            $recentOutbound = [];
        }
    }
    ?>
  <p><a href="whatsapp.php?view=templates">← Templates</a></p>
  <h2 style="margin-top:0">Template inspect: <code><?= e($inspectName ?: '—') ?></code></h2>
  <p class="muted">Compare this JSON with <a href="https://developers.facebook.com/documentation/business-messaging/whatsapp/templates/overview" target="_blank" rel="noopener">Meta template send format</a> (named vs positional). Sample variable: <code><?= e($sampleParam) ?></code> — change with <code>?sample=YourName</code> in the URL.</p>
  <?php if (!$inspectPreview['ok']): ?>
    <div class="alert alert-error"><?= e((string) ($inspectPreview['error'] ?? 'Preview failed.')) ?></div>
    <?php if (!empty($inspectPreview['meta'])): ?>
      <pre style="background:#f8fafc;padding:12px;border-radius:8px;overflow:auto;font-size:0.8rem"><?= e(json_encode($inspectPreview['meta'], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE)) ?></pre>
    <?php endif; ?>
  <?php else: ?>
    <div class="alert" style="background:#ecfdf5;border:1px solid #a7f3d0;color:#065f46">
      Meta language code: <strong><?= e((string) ($inspectPreview['meta']['language_resolved'] ?? '')) ?></strong>
      · format: <strong><?= e((string) ($inspectPreview['meta']['parameter_format'] ?: 'positional')) ?></strong>
      · variables: <strong><?= count($inspectPreview['meta']['param_slots'] ?? []) ?></strong>
      · status: <strong><?= e((string) ($inspectPreview['meta']['status'] ?? '')) ?></strong>
    </div>
    <h3>Payload we send to Graph API</h3>
    <pre style="background:#0f172a;color:#e2e8f0;padding:14px;border-radius:8px;overflow:auto;font-size:0.78rem;max-height:420px"><?= e(json_encode($inspectPreview['payload'], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE)) ?></pre>
    <?php if ($inspectDef !== null && !empty($inspectDef['components'])): ?>
    <h3>Raw components (from Meta)</h3>
    <pre style="background:#f8fafc;padding:12px;border-radius:8px;overflow:auto;font-size:0.75rem;max-height:360px"><?= e(json_encode($inspectDef['components'], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE)) ?></pre>
    <?php endif; ?>
  <?php endif; ?>
  <?php if ($recentOutbound): ?>
  <h3>Recent portal sends (DB log)</h3>
  <table>
    <thead><tr><th>When</th><th>To</th><th>Status</th><th>Error / payload</th></tr></thead>
    <tbody>
    <?php foreach ($recentOutbound as $row): ?>
      <tr>
        <td><?= e($row['updated_at']) ?></td>
        <td><?= e($row['recipient_wa_id']) ?></td>
        <td><?= e($row['delivery_status']) ?></td>
        <td class="muted" style="max-width:420px;word-break:break-word">
          <?= e(admin_whatsapp_excerpt((string) ($row['last_error_message'] ?? ''), 80)) ?>
          <?php if (!empty($row['payload_json'])): ?>
            <details style="margin-top:4px"><summary>payload_json</summary>
              <pre style="font-size:0.7rem;white-space:pre-wrap"><?= e($row['payload_json']) ?></pre>
            </details>
          <?php endif; ?>
        </td>
      </tr>
    <?php endforeach; ?>
    </tbody>
  </table>
  <?php else: ?>
    <p class="muted">No outbound rows for this template yet (or tables missing).</p>
  <?php endif; ?>
  <p class="muted">Server PHP error log also records failed sends: <code>WhatsApp template send failed: template=…</code></p>
<?php elseif ($view === 'chats'):
    $rows = admin_whatsapp_conversation_summaries($pdo, 100);
    ?>
  <div class="wa-page-wrap">
  <?php if (!$rows): ?>
    <div class="wa-empty-state">
      <p><strong>No conversations yet</strong></p>
      <p class="muted">Customer inbound chats and outbound sends (portal or Meta webhook) appear here. Start with <a href="whatsapp.php?view=compose">New message</a>.</p>
    </div>
  <?php else: ?>
  <ul class="wa-chat-list">
    <?php foreach ($rows as $row): ?>
      <?php
      $listName = $row['contact_name'] ?: $row['wa_id'];
      $initials = admin_whatsapp_avatar_initials($row['contact_name'] ?: null, (string) $row['wa_id']);
      $preview = admin_whatsapp_excerpt((string) $row['preview'], 72) ?: '—';
      ?>
    <li>
      <a class="wa-chat-list-item" href="whatsapp.php?view=chat&amp;wa_id=<?= e($row['wa_id']) ?>">
        <span class="wa-chat-avatar wa-chat-avatar-sm"><?= e($initials) ?></span>
        <span class="wa-chat-list-body">
          <span class="wa-chat-list-top">
            <strong><?= e($listName) ?></strong>
            <time><?= e(admin_whatsapp_format_list_time((string) $row['last_at'])) ?></time>
          </span>
          <span class="wa-chat-list-preview"><?= e($preview) ?></span>
          <?php if (!$row['contact_name']): ?>
          <span class="wa-chat-list-sub"><?= e($row['wa_id']) ?></span>
          <?php endif; ?>
        </span>
      </a>
    </li>
    <?php endforeach; ?>
  </ul>
  <?php endif; ?>
  </div>
<?php elseif ($view === 'status'):
    $rows = $pdo->query(
        'SELECT id, wa_message_id, recipient_wa_id, status, error_message, created_at
         FROM whatsapp_message_status_log ORDER BY id DESC LIMIT 100'
    )->fetchAll();
    ?>
  <table>
    <thead><tr><th>Logged</th><th>Status</th><th>Recipient</th><th>Error</th></tr></thead>
    <tbody>
    <?php if (!$rows): ?><tr><td colspan="4" class="muted">No status events yet.</td></tr><?php endif; ?>
    <?php foreach ($rows as $row): ?>
      <tr>
        <td><?= e($row['created_at']) ?></td>
        <td><span class="badge wa-badge-<?= e(preg_replace('/[^a-z]/', '', strtolower($row['status']))) ?>"><?= e($row['status']) ?></span></td>
        <td><?= e($row['recipient_wa_id'] ?: '—') ?></td>
        <td class="muted"><?= e(admin_whatsapp_excerpt($row['error_message'], 60)) ?></td>
      </tr>
    <?php endforeach; ?>
    </tbody>
  </table>
<?php elseif ($view === 'outbound'):
    $rows = $pdo->query(
        'SELECT id, wa_message_id, recipient_wa_id, message_type, template_name, delivery_status, last_error_message, updated_at
         FROM whatsapp_outbound_messages ORDER BY id DESC LIMIT 100'
    )->fetchAll();
    ?>
  <table>
    <thead><tr><th>Updated</th><th>Status</th><th>To</th><th>Preview</th><th></th></tr></thead>
    <tbody>
    <?php if (!$rows): ?><tr><td colspan="5" class="muted">Nothing sent from the portal yet.</td></tr><?php endif; ?>
    <?php foreach ($rows as $row): ?>
      <tr>
        <td><?= e($row['updated_at']) ?></td>
        <td><span class="badge wa-badge-<?= e(preg_replace('/[^a-z]/', '', strtolower($row['delivery_status']))) ?>"><?= e($row['delivery_status']) ?></span></td>
        <td><a href="whatsapp.php?view=chat&amp;wa_id=<?= e($row['recipient_wa_id']) ?>"><?= e($row['recipient_wa_id']) ?></a></td>
        <td><?= e(admin_whatsapp_excerpt(admin_whatsapp_outbound_preview($row), 50)) ?></td>
        <td class="muted"><?= e(admin_whatsapp_excerpt($row['last_error_message'], 40)) ?></td>
      </tr>
    <?php endforeach; ?>
    </tbody>
  </table>
<?php endif; ?>
</div>
<?php admin_footer(); ?>
