<?php
declare(strict_types=1);

require_once dirname(__DIR__, 2) . '/includes/auth.php';
require_admin();
require_once dirname(__DIR__, 2) . '/includes/crm/conversations.php';
require_once dirname(__DIR__, 2) . '/includes/crm/tenant.php';
require_once __DIR__ . '/../layout.php';

function crm_inbox_excerpt(?string $text, int $max = 80): string
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
$tenantId = crm_current_tenant_id();
$ready = crm_tables_ready($pdo);

if ($ready && $_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verify_csrf($_POST['csrf_token'] ?? null)) {
        flash_set('error', 'Invalid CSRF token.');
        header('Location: inbox.php');
        exit;
    }
    $convId = (int) ($_POST['conversation_id'] ?? 0);
    $action = (string) ($_POST['action'] ?? '');
    if ($convId > 0 && $action === 'update') {
        $status = (string) ($_POST['status'] ?? '');
        $assign = isset($_POST['assigned_admin_id']) ? (int) $_POST['assigned_admin_id'] : null;
        crm_update_conversation($pdo, $tenantId, $convId, $status !== '' ? $status : null, $assign);
        crm_audit_log($pdo, 'conversation.update', 'conversation', (string) $convId, [
            'status' => $status,
            'assigned_admin_id' => $assign,
        ]);
        flash_set('success', 'Conversation updated.');
    }
    header('Location: inbox.php?' . http_build_query(array_filter([
        'status' => $_GET['status'] ?? '',
        'assigned' => $_GET['assigned'] ?? '',
    ])));
    exit;
}

$filterStatus = (string) ($_GET['status'] ?? '');
$filterAssigned = isset($_GET['assigned']) ? (string) $_GET['assigned'] : '';
$assignedParam = $filterAssigned === 'unassigned' ? 0 : ($filterAssigned === 'me' ? (int) ($_SESSION['admin_id'] ?? 0) : null);

$rows = $ready
    ? crm_list_conversations($pdo, $tenantId, $filterStatus !== '' ? $filterStatus : null, $assignedParam, 150)
    : [];

$admins = $pdo->query('SELECT id, username FROM admins ORDER BY username ASC')->fetchAll(PDO::FETCH_ASSOC);

admin_header('CRM Inbox', 'crm/inbox.php');

if (!$ready): ?>
  <div class="alert alert-error">Run <code>php sql/migrate_crm_foundation.php</code> first.</div>
  <?php admin_footer(); exit; endif;
?>
<div class="panel" style="margin-bottom:12px">
  <nav class="wa-nav actions" aria-label="Inbox filters">
    <a class="btn btn-sm <?= $filterStatus === '' && $filterAssigned === '' ? '' : 'btn-secondary' ?>" href="inbox.php">All</a>
    <a class="btn btn-sm <?= $filterStatus === 'open' ? '' : 'btn-secondary' ?>" href="inbox.php?status=open">Open</a>
    <a class="btn btn-sm <?= $filterStatus === 'pending' ? '' : 'btn-secondary' ?>" href="inbox.php?status=pending">Pending</a>
    <a class="btn btn-sm <?= $filterStatus === 'resolved' ? '' : 'btn-secondary' ?>" href="inbox.php?status=resolved">Resolved</a>
    <a class="btn btn-sm <?= $filterAssigned === 'unassigned' ? '' : 'btn-secondary' ?>" href="inbox.php?assigned=unassigned">Unassigned</a>
    <a class="btn btn-sm <?= $filterAssigned === 'me' ? '' : 'btn-secondary' ?>" href="inbox.php?assigned=me">Mine</a>
    <a class="btn btn-sm btn-secondary" href="index.php">Dashboard</a>
  </nav>
</div>

<table>
  <thead>
    <tr>
      <th>Contact</th>
      <th>Preview</th>
      <th>Status</th>
      <th>Assignee</th>
      <th>Unread</th>
      <th>Last activity</th>
      <th></th>
    </tr>
  </thead>
  <tbody>
  <?php if (!$rows): ?>
    <tr><td colspan="7" class="muted">No conversations yet. WhatsApp messages will appear here after webhook activity or migration backfill.</td></tr>
  <?php endif; ?>
  <?php foreach ($rows as $row): ?>
    <?php
    $name = trim((string) ($row['contact_name'] ?? ''));
    $peer = (string) ($row['peer_wa_id'] ?? '');
    $title = $name !== '' ? $name : $peer;
    ?>
    <tr>
      <td><strong><?= e($title) ?></strong><br><span class="muted"><?= e($peer) ?></span></td>
      <td><?= e(crm_inbox_excerpt((string) ($row['last_message_preview'] ?? ''), 80)) ?></td>
      <td><span class="badge"><?= e((string) $row['status']) ?></span></td>
      <td><?= e($row['assigned_admin_id'] ? ('#' . $row['assigned_admin_id']) : '—') ?></td>
      <td><?= (int) ($row['unread_count'] ?? 0) ?></td>
      <td><?= e((string) ($row['last_message_at'] ?? '')) ?></td>
      <td>
        <a class="btn btn-sm" href="../whatsapp.php?view=chat&amp;wa_id=<?= e(rawurlencode($peer)) ?>">Chat</a>
        <details style="margin-top:6px">
          <summary class="muted" style="cursor:pointer">Manage</summary>
          <form method="post" style="margin-top:8px;display:grid;gap:6px;max-width:220px">
            <?= csrf_field() ?>
            <input type="hidden" name="action" value="update">
            <input type="hidden" name="conversation_id" value="<?= (int) $row['id'] ?>">
            <label class="muted">Status</label>
            <select name="status">
              <?php foreach (['open', 'pending', 'resolved'] as $st): ?>
                <option value="<?= e($st) ?>" <?= ($row['status'] ?? '') === $st ? 'selected' : '' ?>><?= e(ucfirst($st)) ?></option>
              <?php endforeach; ?>
            </select>
            <label class="muted">Assign to</label>
            <select name="assigned_admin_id">
              <option value="0">Unassigned</option>
              <?php foreach ($admins as $adm): ?>
                <option value="<?= (int) $adm['id'] ?>" <?= (int) ($row['assigned_admin_id'] ?? 0) === (int) $adm['id'] ? 'selected' : '' ?>>
                  <?= e($adm['username']) ?>
                </option>
              <?php endforeach; ?>
            </select>
            <button type="submit" class="btn btn-sm btn-secondary">Save</button>
          </form>
        </details>
      </td>
    </tr>
  <?php endforeach; ?>
  </tbody>
</table>
<?php admin_footer(); ?>
