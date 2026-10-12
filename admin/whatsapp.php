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
        $items[] = [
            'dir' => 'out',
            'time' => strtotime($row['updated_at'] ?: $row['created_at']),
            'label' => $row['updated_at'] ?: $row['created_at'],
            'body' => admin_whatsapp_outbound_preview($row),
            'meta' => $meta,
            'status' => $status,
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
        $lang = preg_replace('/[^a-z0-9_]/i', '', (string) ($_POST['language_code'] ?? ''));
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
        $to = whatsapp_normalize_recipient((string) ($_POST['to'] ?? ''));
        if (empty($_POST['consent'])) {
            flash_set('error', 'Confirm WhatsApp messaging consent before sending.');
            header('Location: whatsapp.php?view=compose');
            exit;
        }
        $tpl = preg_replace('/[^a-z0-9_]/i', '', (string) ($_POST['template_name'] ?? whatsapp_default_template_name()));
        $lang = preg_replace('/[^a-z0-9_]/i', '', (string) ($_POST['language_code'] ?? whatsapp_default_template_language()));
        $redirect = 'whatsapp.php?view=chat&wa_id=' . rawurlencode($to);
        $result = admin_whatsapp_send_template($to, $tpl, $lang);
        flash_set(
            $result['ok'] ? 'success' : 'error',
            $result['ok']
                ? 'Meta accepted the template. Delivery status updates when Meta webhook fires.'
                : ($result['error'] ?? 'Send failed.')
        );
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
$allowedViews = ['chats', 'chat', 'compose', 'templates', 'status', 'outbound', 'setup'];
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
    $hasInbound = whatsapp_chat_has_inbound($pdo, $chatWaId);
    $lastOutbound = admin_whatsapp_last_outbound($pdo, $chatWaId);

    admin_header($contactName ?: $chatWaId, 'whatsapp.php');
    ?>
    <div class="panel wa-chat-panel">
      <div class="wa-chat-head">
        <div>
          <strong><?= e($contactName ?: 'Customer') ?></strong>
          <span class="muted"> · <?= e($chatWaId) ?></span>
        </div>
        <div class="actions">
          <span class="badge <?= $windowOpen ? 'wa-badge-delivered' : 'wa-badge-pending' ?>">
            <?= $windowOpen ? '24h window open' : '24h window closed — use template' ?>
          </span>
          <a class="btn btn-sm btn-secondary" href="whatsapp.php?view=chats">All chats</a>
          <form method="post" class="inline"><?= csrf_field() ?><input type="hidden" name="action" value="create_lead"><input type="hidden" name="to" value="<?= e($chatWaId) ?>"><button type="submit" class="btn btn-sm btn-secondary">Create lead</button></form>
        </div>
      </div>
      <?php if ($lastOutbound): ?>
      <div class="wa-delivery-banner">
        <strong>Latest send:</strong>
        <?= e($lastOutbound['template_name'] ?: $lastOutbound['message_type']) ?>
        · <span class="wa-status-<?= e(preg_replace('/[^a-z]/', '', strtolower((string) $lastOutbound['delivery_status']))) ?>"><?= e(admin_whatsapp_delivery_status_label((string) $lastOutbound['delivery_status'])) ?></span>
        · <?= e($lastOutbound['updated_at']) ?>
        <?php if (strtolower((string) $lastOutbound['delivery_status']) === 'failed' && !empty($lastOutbound['last_error_message'])): ?>
          <br><span class="muted"><?= e($lastOutbound['last_error_message']) ?></span>
        <?php elseif (in_array(strtolower((string) $lastOutbound['delivery_status']), ['sent', 'delivered'], true)): ?>
          <br><span class="muted">“Sent” means Meta accepted the message. If the phone did not receive it, confirm the number uses WhatsApp, and in Dev mode add it under Meta → WhatsApp → API Setup → test recipients.</span>
        <?php endif; ?>
      </div>
      <?php endif; ?>
      <?php if (!$hasInbound || !empty($tplFetchError)): ?>
      <div class="wa-chat-notices" style="margin:12px 20px 0;padding:12px 14px;border-radius:8px;background:#fef2f2;border:1px solid #fecaca;font-size:0.85rem;color:#991b1b">
        <?php if (!$hasInbound): ?>
        <p style="margin:0 0 8px"><strong>Template-only mode.</strong> Free-text replies unlock for 24h after the customer messages your business number on WhatsApp.</p>
        <?php endif; ?>
        <?php if (!empty($tplFetchError)): ?>
        <p style="margin:0"><?= e($tplFetchError) ?></p>
        <?php endif; ?>
      </div>
      <?php endif; ?>
      <?php if ($windowOpen): ?>
      <div class="wa-quick-replies actions" style="padding:0 20px 12px">
        <?php foreach ($quickReplies as $qr): ?>
        <form method="post" class="inline"><?= csrf_field() ?><input type="hidden" name="action" value="quick_reply"><input type="hidden" name="to" value="<?= e($chatWaId) ?>"><input type="hidden" name="text" value="<?= e($qr) ?>"><button type="submit" class="btn btn-sm btn-secondary"><?= e(admin_whatsapp_excerpt($qr, 42)) ?></button></form>
        <?php endforeach; ?>
      </div>
      <?php endif; ?>
      <div class="wa-chat-thread">
        <?php if (!$timeline): ?>
          <p class="muted">No messages yet for this number. Send a template to start.</p>
        <?php endif; ?>
        <?php foreach ($timeline as $item): ?>
          <?php
          $bubbleClass = 'wa-bubble-' . $item['dir'];
          if (($item['status'] ?? '') === 'failed') {
              $bubbleClass .= ' wa-bubble-failed';
          }
          ?>
          <div class="wa-bubble <?= e($bubbleClass) ?>">
            <div class="wa-bubble-body"><?= nl2br(e($item['body'])) ?></div>
            <div class="wa-bubble-meta"><?= e($item['label']) ?> · <?= e($item['meta']) ?></div>
          </div>
        <?php endforeach; ?>
      </div>
      <div class="wa-chat-compose">
        <form method="post" class="form-grid">
          <?= csrf_field() ?>
          <input type="hidden" name="action" value="send_text">
          <input type="hidden" name="to" value="<?= e($chatWaId) ?>">
          <?php if (!empty($lastWamid)): ?>
          <input type="hidden" name="reply_to_wamid" value="<?= e($lastWamid) ?>">
          <?php endif; ?>
          <label>Reply (text)</label>
          <textarea name="text" rows="3" placeholder="<?= $windowOpen ? 'Type your reply…' : 'Service window closed — use template below' ?>" <?= $windowOpen ? '' : 'disabled' ?> required></textarea>
          <button class="btn" type="submit" <?= $windowOpen ? '' : 'disabled' ?>>Send reply</button>
        </form>
        <form method="post" class="form-grid" style="margin-top:16px">
          <?= csrf_field() ?>
          <input type="hidden" name="action" value="send_template">
          <input type="hidden" name="to" value="<?= e($chatWaId) ?>">
          <label>Send template</label>
          <?php if ($tplList): ?>
          <label>Approved templates (from Meta API)</label>
          <select name="template_name" class="wa-tpl-select" id="wa-chat-tpl-select">
            <?php foreach ($tplList as $t): ?>
              <?php
              $sel = $t['name'] === $defaultTpl && $t['language'] === $defaultLang;
              $paramCount = (int) ($t['param_count'] ?? 0);
              $preview = (string) ($t['body_preview'] ?? '');
              $slotJson = json_encode($t['param_slots'] ?? [], JSON_UNESCAPED_UNICODE);
              ?>
              <option value="<?= e($t['name']) ?>" data-lang="<?= e($t['language']) ?>" data-param-slots="<?= e($slotJson ?: '[]') ?>" data-body-preview="<?= e(admin_whatsapp_excerpt($preview, 200)) ?>" <?= $sel ? 'selected' : '' ?>>
                <?= e($t['name']) ?> (<?= e($t['language']) ?>) — <?= e($t['category']) ?><?= $paramCount ? " · {$paramCount} var(s)" : '' ?>
              </option>
            <?php endforeach; ?>
          </select>
          <input type="hidden" name="language_code" id="wa-lang-code" value="<?= e($defaultLang) ?>">
          <div id="wa-chat-tpl-params" class="form-grid" style="margin-top:8px"></div>
          <p class="muted" id="wa-chat-tpl-preview" style="margin:4px 0 0"></p>
          <?php else: ?>
          <div class="form-row">
            <div><input type="text" name="template_name" value="<?= e($defaultTpl) ?>" placeholder="hello_world"></div>
            <div><input type="text" name="language_code" value="<?= e($defaultLang) ?>" placeholder="en_US"></div>
          </div>
          <?php endif; ?>
          <button class="btn btn-secondary" type="submit">Send template</button>
        </form>
        <form method="post" class="form-grid" style="margin-top:16px">
          <?= csrf_field() ?>
          <input type="hidden" name="action" value="send_image">
          <input type="hidden" name="to" value="<?= e($chatWaId) ?>">
          <label>Send image (HTTPS URL, 24h window)</label>
          <input type="url" name="image_url" placeholder="https://digitalcreatorss.com/..." <?= $windowOpen ? '' : 'disabled' ?>>
          <input type="text" name="caption" placeholder="Optional caption" <?= $windowOpen ? '' : 'disabled' ?>>
          <button class="btn btn-secondary" type="submit" <?= $windowOpen ? '' : 'disabled' ?>>Send image</button>
        </form>
        <form method="post" class="form-grid" style="margin-top:16px">
          <?= csrf_field() ?>
          <input type="hidden" name="action" value="send_document">
          <input type="hidden" name="to" value="<?= e($chatWaId) ?>">
          <label>Send document (HTTPS URL, 24h window)</label>
          <input type="url" name="document_url" placeholder="https://..." <?= $windowOpen ? '' : 'disabled' ?>>
          <input type="text" name="filename" placeholder="proposal.pdf" <?= $windowOpen ? '' : 'disabled' ?>>
          <button class="btn btn-secondary" type="submit" <?= $windowOpen ? '' : 'disabled' ?>>Send document</button>
        </form>
      </div>
    </div>
    <?php if ($tplList): ?>
    <script src="assets/whatsapp-admin.js"></script>
    <script>window.dcWaInitTemplateForm('wa-chat-tpl-select', 'wa-lang-code', 'wa-chat-tpl-params', 'wa-chat-tpl-preview');</script>
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
  <div class="actions" style="margin-bottom:12px">
    <a class="btn btn-sm <?= $view === 'chats' ? '' : 'btn-secondary' ?>" href="whatsapp.php?view=chats">Chats</a>
    <a class="btn btn-sm <?= $view === 'compose' ? '' : 'btn-secondary' ?>" href="whatsapp.php?view=compose">New message</a>
    <a class="btn btn-sm <?= $view === 'templates' ? '' : 'btn-secondary' ?>" href="whatsapp.php?view=templates">Templates</a>
    <a class="btn btn-sm <?= $view === 'status' ? '' : 'btn-secondary' ?>" href="whatsapp.php?view=status">Delivery log</a>
    <a class="btn btn-sm <?= $view === 'outbound' ? '' : 'btn-secondary' ?>" href="whatsapp.php?view=outbound">Outbound</a>
    <a class="btn btn-sm <?= $view === 'setup' ? '' : 'btn-secondary' ?>" href="whatsapp.php?view=setup">Setup</a>
    <a class="btn btn-sm btn-secondary" href="whatsapp_campaigns.php">Campaigns</a>
    <a class="btn btn-sm btn-secondary" href="whatsapp_contacts.php">Contacts</a>
  </div>

<?php if (!$tablesReady): ?>
  <div class="alert alert-error">
    WhatsApp tables are missing. Run: <code>php sql/migrate_whatsapp_webhook.php</code>
  </div>
<?php elseif ($view === 'setup'):
    require_once dirname(__DIR__) . '/includes/whatsapp_config_status.php';
    $conn = whatsapp_connection_status();
    ?>
  <p class="muted" style="margin-top:0">Secrets live in private <code>digital_creators_config.php</code> (masked below).</p>
  <table>
    <tbody>
      <tr><th style="width:220px">Graph API version</th><td><?= e($conn['api_version']) ?></td></tr>
      <tr><th>Access token</th><td><?= $conn['access_token_set'] ? e($conn['access_token_masked']) : 'Missing' ?></td></tr>
      <tr><th>Phone number ID</th><td><?= e($conn['phone_number_id']) ?></td></tr>
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
  <form method="post" class="form-grid" style="max-width:480px">
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
        $preview = (string) ($t['body_preview'] ?? '');
        $slotJson = json_encode($t['param_slots'] ?? [], JSON_UNESCAPED_UNICODE);
        ?>
        <option value="<?= e($t['name']) ?>" data-lang="<?= e($t['language']) ?>" data-param-slots="<?= e($slotJson ?: '[]') ?>" data-body-preview="<?= e(admin_whatsapp_excerpt($preview, 200)) ?>" <?= $sel ? 'selected' : '' ?>>
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
  <script src="assets/whatsapp-admin.js"></script>
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
  <table>
    <thead><tr><th>Name</th><th>Language</th><th>Category</th><th>Status</th></tr></thead>
    <tbody>
    <?php if (!$tplList): ?>
      <tr><td colspan="4" class="muted">No templates to show. Fix the issue above, then refresh.</td></tr>
    <?php endif; ?>
    <?php foreach ($tplList as $t): ?>
      <tr>
        <td><code><?= e($t['name']) ?></code></td>
        <td><?= e($t['language']) ?></td>
        <td><?= e($t['category']) ?></td>
        <td><?= e($t['status']) ?></td>
      </tr>
    <?php endforeach; ?>
    </tbody>
  </table>
<?php elseif ($view === 'chats'):
    $rows = $pdo->query(
        'SELECT m.from_wa_id, m.contact_name, m.body_text, m.created_at
         FROM whatsapp_inbound_messages m
         INNER JOIN (
           SELECT from_wa_id, MAX(id) AS max_id FROM whatsapp_inbound_messages GROUP BY from_wa_id
         ) t ON m.id = t.max_id
         ORDER BY m.id DESC
         LIMIT 100'
    )->fetchAll();
    ?>
  <table>
    <thead><tr><th>Last activity</th><th>Contact</th><th>Last message</th><th></th></tr></thead>
    <tbody>
    <?php if (!$rows): ?>
      <tr><td colspan="4" class="muted">No chats yet. Use <strong>New message</strong> or wait for customers to write on WhatsApp.</td></tr>
    <?php endif; ?>
    <?php foreach ($rows as $row): ?>
      <tr>
        <td><?= e($row['created_at']) ?></td>
        <td><strong><?= e($row['contact_name'] ?: $row['from_wa_id']) ?></strong><br><span class="muted"><?= e($row['from_wa_id']) ?></span></td>
        <td><?= e(admin_whatsapp_excerpt($row['body_text'])) ?></td>
        <td><a class="btn btn-sm" href="whatsapp.php?view=chat&amp;wa_id=<?= e($row['from_wa_id']) ?>">Open</a></td>
      </tr>
    <?php endforeach; ?>
    </tbody>
  </table>
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
