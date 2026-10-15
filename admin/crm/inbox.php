<?php
declare(strict_types=1);

require_once dirname(__DIR__, 2) . '/includes/crm/auth_session.php';
crm_require_crm_user();
crm_bootstrap_session_context();

require_once dirname(__DIR__, 2) . '/includes/crm/conversations.php';
require_once dirname(__DIR__, 2) . '/includes/crm/tenant.php';
require_once __DIR__ . '/layout.php';

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

$admins = [];
try {
    $admins = $pdo->query('SELECT id, username FROM admins ORDER BY username ASC')->fetchAll(PDO::FETCH_ASSOC);
} catch (Throwable $e) {
    $admins = [];
}

crm_app_header('Team inbox (classic)', 'inbox', [
    ['label' => 'Dashboard', 'href' => 'index.php'],
    ['label' => 'Classic inbox'],
]);
?>
<?php if (!$ready): ?>
  <div class="crm-alert crm-alert-error">Run <code>php sql/migrate_crm_foundation.php</code> first.</div>
  <?php crm_app_footer(); exit; endif; ?>

<div class="crm-panel" style="margin-bottom:var(--crm-space-4)">
  <p style="margin:0;color:var(--crm-text-muted)">Table view for bulk assignment. For messaging, use the <a href="live.php">live inbox</a>.</p>
</div>

<nav class="crm-contacts-toolbar" aria-label="Inbox filters" style="margin-bottom:var(--crm-space-3)">
  <a class="crm-btn crm-btn-sm <?= $filterStatus === '' && $filterAssigned === '' ? 'crm-btn-primary' : 'crm-btn-secondary' ?>" href="inbox.php">All</a>
  <a class="crm-btn crm-btn-sm <?= $filterStatus === 'open' ? 'crm-btn-primary' : 'crm-btn-secondary' ?>" href="inbox.php?status=open">Open</a>
  <a class="crm-btn crm-btn-sm <?= $filterStatus === 'pending' ? 'crm-btn-primary' : 'crm-btn-secondary' ?>" href="inbox.php?status=pending">Pending</a>
  <a class="crm-btn crm-btn-sm <?= $filterStatus === 'resolved' ? 'crm-btn-primary' : 'crm-btn-secondary' ?>" href="inbox.php?status=resolved">Resolved</a>
  <a class="crm-btn crm-btn-sm <?= $filterAssigned === 'unassigned' ? 'crm-btn-primary' : 'crm-btn-secondary' ?>" href="inbox.php?assigned=unassigned">Unassigned</a>
  <a class="crm-btn crm-btn-sm <?= $filterAssigned === 'me' ? 'crm-btn-primary' : 'crm-btn-secondary' ?>" href="inbox.php?assigned=me">Mine</a>
</nav>

<div class="crm-table-wrap">
  <table class="crm-table">
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
      <tr><td colspan="7"><div class="crm-empty">No conversations yet.</div></td></tr>
    <?php endif; ?>
    <?php foreach ($rows as $row): ?>
      <?php
      $name = trim((string) ($row['contact_name'] ?? ''));
      $peer = (string) ($row['peer_wa_id'] ?? '');
      $title = $name !== '' ? $name : $peer;
      ?>
      <tr>
        <td><strong><?= e($title) ?></strong><br><span style="color:var(--crm-text-muted);font-size:var(--crm-text-xs)"><?= e($peer) ?></span></td>
        <td><?= e(crm_inbox_excerpt((string) ($row['last_message_preview'] ?? ''), 80)) ?></td>
        <td><span class="crm-chip"><?= e((string) $row['status']) ?></span></td>
        <td><?= e($row['assigned_admin_id'] ? ('#' . $row['assigned_admin_id']) : '—') ?></td>
        <td><?= (int) ($row['unread_count'] ?? 0) ?></td>
        <td><?= e((string) ($row['last_message_at'] ?? '')) ?></td>
        <td>
          <a class="crm-btn crm-btn-sm crm-btn-primary" href="live.php?conversation=<?= (int) ($row['id'] ?? 0) ?>">Inbox</a>
          <details style="margin-top:6px">
            <summary style="cursor:pointer;color:var(--crm-text-muted)">Manage</summary>
            <form method="post" style="margin-top:8px;display:grid;gap:6px;max-width:220px">
              <?= csrf_field() ?>
              <input type="hidden" name="action" value="update">
              <input type="hidden" name="conversation_id" value="<?= (int) $row['id'] ?>">
              <label class="crm-field"><span>Status</span>
              <select name="status">
                <?php foreach (['open', 'pending', 'resolved'] as $st): ?>
                  <option value="<?= e($st) ?>" <?= ($row['status'] ?? '') === $st ? 'selected' : '' ?>><?= e(ucfirst($st)) ?></option>
                <?php endforeach; ?>
              </select></label>
              <label class="crm-field"><span>Assign to</span>
              <select name="assigned_admin_id">
                <option value="0">Unassigned</option>
                <?php foreach ($admins as $adm): ?>
                  <option value="<?= (int) $adm['id'] ?>" <?= (int) ($row['assigned_admin_id'] ?? 0) === (int) $adm['id'] ? 'selected' : '' ?>>
                    <?= e($adm['username']) ?>
                  </option>
                <?php endforeach; ?>
              </select></label>
              <button type="submit" class="crm-btn crm-btn-sm crm-btn-secondary">Save</button>
            </form>
          </details>
        </td>
      </tr>
    <?php endforeach; ?>
    </tbody>
  </table>
</div>
<?php crm_app_footer(); ?>
