<?php
require_once dirname(__DIR__) . '/includes/auth.php';
require_admin();
require_once __DIR__ . '/layout.php';
require_once dirname(__DIR__) . '/includes/whatsapp_cloud.php';

function admin_whatsapp_tables_ready(PDO $pdo): bool
{
    try {
        $pdo->query('SELECT 1 FROM whatsapp_inbound_messages LIMIT 1');
        return true;
    } catch (Throwable $e) {
        return false;
    }
}

function admin_whatsapp_format_ts(?int $unix): string
{
    if (!$unix) {
        return '—';
    }

    return gmdate('Y-m-d H:i:s', $unix) . ' UTC';
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

$pdo = db();
$tablesReady = admin_whatsapp_tables_ready($pdo);
$config = whatsapp_cloud_config();
$verifyOk = trim($config['webhook_verify_token']) !== '';
$secretOk = trim($config['app_secret']) !== '';

$view = $_GET['view'] ?? 'inbound';
if (!in_array($view, ['inbound', 'status', 'outbound', 'setup'], true)) {
    $view = 'inbound';
}

$detailId = isset($_GET['id']) ? (int) $_GET['id'] : 0;

if ($tablesReady && $view === 'inbound' && $detailId > 0) {
    $stmt = $pdo->prepare('SELECT * FROM whatsapp_inbound_messages WHERE id = ?');
    $stmt->execute([$detailId]);
    $msg = $stmt->fetch();
    admin_header('WhatsApp message', 'whatsapp.php');
    if (!$msg) {
        echo '<div class="alert alert-error">Message not found.</div>';
        echo '<p><a class="btn btn-secondary" href="whatsapp.php">Back</a></p>';
        admin_footer();
        exit;
    }
    $media = !empty($msg['media_json']) ? json_decode((string) $msg['media_json'], true) : null;
    ?>
    <div class="panel form-grid">
      <p><strong>From:</strong> <?= e($msg['contact_name'] ?: $msg['from_wa_id']) ?>
        <?php if ($msg['contact_name']): ?><span class="muted">(<?= e($msg['from_wa_id']) ?>)</span><?php endif; ?>
      </p>
      <p><strong>Type:</strong> <?= e($msg['message_type']) ?></p>
      <p><strong>WhatsApp message ID:</strong> <code><?= e($msg['wa_message_id']) ?></code></p>
      <p><strong>Phone number ID:</strong> <?= e($msg['phone_number_id'] ?: '—') ?></p>
      <p><strong>WABA ID:</strong> <?= e($msg['waba_id'] ?: '—') ?></p>
      <p><strong>WhatsApp time:</strong> <?= e(admin_whatsapp_format_ts($msg['wa_timestamp'] ? (int) $msg['wa_timestamp'] : null)) ?></p>
      <p><strong>Stored at:</strong> <?= e($msg['created_at']) ?></p>
      <?php if ($msg['body_text']): ?>
      <p><strong>Text:</strong><br><?= nl2br(e($msg['body_text'])) ?></p>
      <?php endif; ?>
      <?php if (is_array($media) && $media): ?>
      <p><strong>Media metadata:</strong><br><pre class="wa-pre"><?= e(json_encode($media, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE)) ?></pre></p>
      <?php endif; ?>
      <div class="actions">
        <a class="btn btn-secondary" href="whatsapp.php?view=inbound">Back to inbox</a>
        <a class="btn btn-secondary" href="https://wa.me/<?= e(preg_replace('/\D+/', '', $msg['from_wa_id'])) ?>" target="_blank" rel="noopener">Open in WhatsApp</a>
      </div>
    </div>
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
  <div class="card"><div class="label">Status events</div><div class="value"><?= $tablesReady ? $counts['status'] : '—' ?></div></div>
  <div class="card"><div class="label">Outbound tracked</div><div class="value"><?= $tablesReady ? $counts['outbound'] : '—' ?></div></div>
</div>

<div class="panel" style="margin-bottom:16px">
  <div class="actions" style="margin-bottom:12px">
    <a class="btn btn-sm <?= $view === 'inbound' ? '' : 'btn-secondary' ?>" href="whatsapp.php?view=inbound">Inbox</a>
    <a class="btn btn-sm <?= $view === 'status' ? '' : 'btn-secondary' ?>" href="whatsapp.php?view=status">Delivery status</a>
    <a class="btn btn-sm <?= $view === 'outbound' ? '' : 'btn-secondary' ?>" href="whatsapp.php?view=outbound">Outbound</a>
    <a class="btn btn-sm <?= $view === 'setup' ? '' : 'btn-secondary' ?>" href="whatsapp.php?view=setup">Setup</a>
  </div>

<?php if (!$tablesReady): ?>
  <div class="alert alert-error">
    WhatsApp tables are missing. On the server run: <code>php sql/migrate_whatsapp_webhook.php</code>
  </div>
<?php elseif ($view === 'setup'): ?>
  <p class="muted" style="margin-top:0">Webhook secrets are not shown here. Configure them in private <code>digital_creators_config.php</code>.</p>
  <table>
    <tbody>
      <tr><th style="width:220px">Verify token configured</th><td><?= $verifyOk ? 'Yes' : 'No' ?></td></tr>
      <tr><th>App secret configured</th><td><?= $secretOk ? 'Yes' : 'No' ?></td></tr>
      <tr><th>Phone number ID</th><td><?= e($config['phone_number_id'] ?: '—') ?></td></tr>
      <tr><th>Meta callback URL</th><td><code>https://digitalcreatorss.com/api/hook-wa</code></td></tr>
      <tr><th>Health check (public)</th><td><a href="/api/wa-health" target="_blank" rel="noopener">/api/wa-health</a></td></tr>
    </tbody>
  </table>
<?php elseif ($view === 'inbound'):
    $rows = $pdo->query(
        'SELECT id, from_wa_id, contact_name, message_type, body_text, wa_timestamp, created_at
         FROM whatsapp_inbound_messages ORDER BY id DESC LIMIT 100'
    )->fetchAll();
    ?>
  <table>
    <thead>
      <tr><th>Received</th><th>From</th><th>Type</th><th>Preview</th><th></th></tr>
    </thead>
    <tbody>
    <?php if (!$rows): ?>
      <tr><td colspan="5" class="muted">No inbound messages yet. Verify webhook in Meta and send a test message to your business number.</td></tr>
    <?php endif; ?>
    <?php foreach ($rows as $row): ?>
      <tr>
        <td><?= e($row['created_at']) ?></td>
        <td>
          <strong><?= e($row['contact_name'] ?: $row['from_wa_id']) ?></strong>
          <?php if ($row['contact_name']): ?><br><span class="muted"><?= e($row['from_wa_id']) ?></span><?php endif; ?>
        </td>
        <td><?= e($row['message_type']) ?></td>
        <td><?= e(admin_whatsapp_excerpt($row['body_text'])) ?></td>
        <td><a class="btn btn-sm btn-secondary" href="whatsapp.php?view=inbound&amp;id=<?= (int) $row['id'] ?>">View</a></td>
      </tr>
    <?php endforeach; ?>
    </tbody>
  </table>
<?php elseif ($view === 'status'):
    $rows = $pdo->query(
        'SELECT id, wa_message_id, recipient_wa_id, status, error_message, wa_timestamp, created_at
         FROM whatsapp_message_status_log ORDER BY id DESC LIMIT 100'
    )->fetchAll();
    ?>
  <table>
    <thead>
      <tr><th>Logged</th><th>Status</th><th>Recipient</th><th>Message ID</th><th>Error</th></tr>
    </thead>
    <tbody>
    <?php if (!$rows): ?>
      <tr><td colspan="5" class="muted">No delivery status events yet.</td></tr>
    <?php endif; ?>
    <?php foreach ($rows as $row): ?>
      <tr>
        <td><?= e($row['created_at']) ?></td>
        <td><span class="badge wa-badge-<?= e(preg_replace('/[^a-z]/', '', strtolower($row['status']))) ?>"><?= e($row['status']) ?></span></td>
        <td><?= e($row['recipient_wa_id'] ?: '—') ?></td>
        <td><code style="font-size:0.75rem"><?= e(admin_whatsapp_excerpt($row['wa_message_id'], 28)) ?></code></td>
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
    <thead>
      <tr><th>Updated</th><th>Status</th><th>Recipient</th><th>Type</th><th>Template</th><th>Message ID</th></tr>
    </thead>
    <tbody>
    <?php if (!$rows): ?>
      <tr><td colspan="6" class="muted">No outbound rows yet (created when you send via Cloud API helpers).</td></tr>
    <?php endif; ?>
    <?php foreach ($rows as $row): ?>
      <tr>
        <td><?= e($row['updated_at']) ?></td>
        <td><span class="badge wa-badge-<?= e(preg_replace('/[^a-z]/', '', strtolower($row['delivery_status']))) ?>"><?= e($row['delivery_status']) ?></span></td>
        <td><?= e($row['recipient_wa_id']) ?></td>
        <td><?= e($row['message_type']) ?></td>
        <td><?= e($row['template_name'] ?: '—') ?></td>
        <td><code style="font-size:0.75rem"><?= e($row['wa_message_id'] ? admin_whatsapp_excerpt($row['wa_message_id'], 28) : '—') ?></code></td>
      </tr>
    <?php endforeach; ?>
    </tbody>
  </table>
<?php endif; ?>
</div>
<?php admin_footer(); ?>
