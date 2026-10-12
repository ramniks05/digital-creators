<?php
require_once dirname(__DIR__) . '/includes/auth.php';
require_admin();
require_once __DIR__ . '/layout.php';
require_once dirname(__DIR__) . '/includes/whatsapp_send.php';
require_once dirname(__DIR__) . '/includes/whatsapp_contacts.php';

function wa_campaigns_ready(PDO $pdo): bool
{
    try {
        $pdo->query('SELECT 1 FROM whatsapp_campaigns LIMIT 1');

        return true;
    } catch (Throwable $e) {
        return false;
    }
}

function wa_campaign_params_from_post(): array
{
    if (isset($_POST['template_body_params']) && is_array($_POST['template_body_params'])) {
        return array_values(array_filter(array_map(static fn ($v) => trim((string) $v), $_POST['template_body_params']), static fn ($v) => $v !== ''));
    }
    $raw = trim((string) ($_POST['template_params_lines'] ?? ''));

    return $raw === '' ? [] : array_values(array_filter(array_map('trim', preg_split('/\r\n|\r|\n/', $raw))));
}

$pdo = db();
$ready = wa_campaigns_ready($pdo);

if ($ready && $_SERVER['REQUEST_METHOD'] === 'POST' && verify_csrf($_POST['csrf_token'] ?? null)) {
    $action = $_POST['action'] ?? '';
    if ($action === 'create') {
        $name = trim((string) ($_POST['name'] ?? ''));
        $tpl = preg_replace('/[^a-z0-9_]/i', '', (string) ($_POST['template_name'] ?? ''));
        $lang = whatsapp_normalize_template_language((string) ($_POST['language_code'] ?? 'en_US'));
        $filter = ($_POST['filter_consent'] ?? '') === 'all_not_opted_out' ? 'all_not_opted_out' : 'opted_in_only';
        $params = wa_campaign_params_from_post();
        if ($name !== '' && $tpl !== '') {
            $pdo->prepare(
                'INSERT INTO whatsapp_campaigns (name, template_name, language_code, template_params_json, filter_consent, status, created_by)
                 VALUES (?,?,?,?,?,\'draft\',?)'
            )->execute([
                $name,
                $tpl,
                $lang,
                json_encode($params, JSON_UNESCAPED_UNICODE),
                $filter,
                $_SESSION['admin_username'] ?? 'admin',
            ]);
            flash_set('success', 'Campaign draft saved.');
        }
    } elseif ($action === 'send' && !empty($_POST['campaign_id'])) {
        $cid = (int) $_POST['campaign_id'];
        $camp = $pdo->prepare('SELECT * FROM whatsapp_campaigns WHERE id = ? LIMIT 1');
        $camp->execute([$cid]);
        $c = $camp->fetch(PDO::FETCH_ASSOC);
        if ($c && $c['status'] === 'draft') {
            $pdo->prepare('UPDATE whatsapp_campaigns SET status = \'sending\', started_at = CURRENT_TIMESTAMP WHERE id = ?')->execute([$cid]);
            $recipients = whatsapp_contacts_marketing_eligible((string) $c['filter_consent']);
            $params = json_decode((string) ($c['template_params_json'] ?? '[]'), true);
            if (!is_array($params)) {
                $params = [];
            }
            $sent = 0;
            $failed = 0;
            foreach ($recipients as $r) {
                $wa = (string) $r['wa_id'];
                if ($c['filter_consent'] === 'opted_in_only' && !whatsapp_contact_can_receive_marketing($wa)) {
                    continue;
                }
                $res = whatsapp_send_template_message($wa, (string) $c['template_name'], (string) $c['language_code'], [], $params);
                if ($res['ok']) {
                    $sent++;
                } else {
                    $failed++;
                }
                $pdo->prepare(
                    'INSERT INTO whatsapp_campaign_recipients (campaign_id, recipient_wa_id, delivery_status, outbound_message_id, error_message)
                     VALUES (?,?,?,?,?)
                     ON DUPLICATE KEY UPDATE delivery_status = VALUES(delivery_status), error_message = VALUES(error_message), updated_at = CURRENT_TIMESTAMP'
                )->execute([
                    $cid,
                    $wa,
                    $res['ok'] ? 'sent' : 'failed',
                    $res['outbound_id'] ?? null,
                    $res['ok'] ? null : substr($res['error'] ?? 'failed', 0, 500),
                ]);
                usleep(350000);
            }
            $pdo->prepare(
                'UPDATE whatsapp_campaigns SET status = \'completed\', completed_at = CURRENT_TIMESTAMP, stats_sent = ?, stats_failed = ? WHERE id = ?'
            )->execute([$sent, $failed, $cid]);
            flash_set('success', "Campaign finished: {$sent} accepted by Meta, {$failed} failed at send.");
        }
    }
    header('Location: whatsapp_campaigns.php');
    exit;
}

$tplResult = whatsapp_fetch_message_templates_result();
$marketingTemplates = whatsapp_filter_approved_templates($tplResult['approved'] ?? [], 'MARKETING');
if ($marketingTemplates === []) {
    $marketingTemplates = whatsapp_filter_approved_templates($tplResult['approved'] ?? []);
}

admin_header('WhatsApp Campaigns', 'whatsapp_campaigns.php');
?>
<?php if (!$ready): ?>
<div class="alert alert-error">Run <code>php sql/migrate_whatsapp_extensions.php</code> to enable campaigns.</div>
<?php else: ?>
<p class="muted">Consent-based template broadcasts. Opt-in is recorded when customers message your business WhatsApp (unless marked opted out).</p>
<div class="panel">
  <h2>New campaign</h2>
  <form method="post" class="form-grid" style="max-width:520px">
    <?= csrf_field() ?>
    <input type="hidden" name="action" value="create">
    <label>Campaign name</label>
    <input type="text" name="name" required>
    <label>Approved template</label>
    <select name="template_name" required>
      <?php foreach ($marketingTemplates as $t): ?>
        <option value="<?= e($t['name']) ?>" data-lang="<?= e($t['language']) ?>"><?= e($t['name']) ?> (<?= e($t['language']) ?>) — <?= e($t['category']) ?></option>
      <?php endforeach; ?>
    </select>
    <label>Language code</label>
    <input type="text" name="language_code" value="<?= e($marketingTemplates[0]['language'] ?? 'en_US') ?>">
    <label>Recipients</label>
    <select name="filter_consent">
      <option value="opted_in_only">Opted-in only (recommended)</option>
      <option value="all_not_opted_out">All except opted-out</option>
    </select>
    <label>Template variables (one value per line, in Meta order)</label>
    <textarea name="template_params_lines" rows="3" placeholder="Customer name&#10;Company name"></textarea>
    <button class="btn" type="submit">Save draft</button>
  </form>
</div>
<div class="panel" style="margin-top:16px">
  <h2>Campaigns</h2>
  <table>
    <thead><tr><th>Name</th><th>Template</th><th>Status</th><th>Sent / Failed</th><th></th></tr></thead>
    <tbody>
    <?php
    $rows = $pdo->query('SELECT * FROM whatsapp_campaigns ORDER BY id DESC LIMIT 50')->fetchAll();
    if (!$rows): ?>
      <tr><td colspan="5" class="muted">No campaigns yet.</td></tr>
    <?php endif;
    foreach ($rows as $row):
        ?>
      <tr>
        <td><?= e($row['name']) ?></td>
        <td><code><?= e($row['template_name']) ?></code> (<?= e($row['language_code']) ?>)</td>
        <td><?= e($row['status']) ?></td>
        <td><?= (int) $row['stats_sent'] ?> / <?= (int) $row['stats_failed'] ?></td>
        <td>
          <?php if ($row['status'] === 'draft'): ?>
          <form method="post" class="inline" onsubmit="return confirm('Send this campaign now?');">
            <?= csrf_field() ?>
            <input type="hidden" name="action" value="send">
            <input type="hidden" name="campaign_id" value="<?= (int) $row['id'] ?>">
            <button type="submit" class="btn btn-sm">Send now</button>
          </form>
          <?php endif; ?>
        </td>
      </tr>
    <?php endforeach; ?>
    </tbody>
  </table>
</div>
<?php endif; ?>
<p><a href="whatsapp.php">← WhatsApp inbox</a> · <a href="whatsapp_contacts.php">Contacts & consent</a></p>
<?php admin_footer(); ?>
