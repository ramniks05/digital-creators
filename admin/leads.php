<?php
require_once dirname(__DIR__) . '/includes/auth.php';
require_admin();
require_once __DIR__ . '/layout.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verify_csrf($_POST['csrf_token'] ?? null)) {
        flash_set('error', 'Invalid CSRF token.');
        header('Location: leads.php');
        exit;
    }
    $action = $_POST['action'] ?? '';
    $id = (int) ($_POST['id'] ?? 0);
    if ($action === 'delete' && $id) {
        db()->prepare('DELETE FROM leads WHERE id = ?')->execute([$id]);
        flash_set('success', 'Lead deleted.');
    } elseif ($action === 'status' && $id) {
        $status = $_POST['status'] ?? 'read';
        if (!in_array($status, ['new', 'read', 'archived'], true)) {
            $status = 'read';
        }
        db()->prepare('UPDATE leads SET status = ? WHERE id = ?')->execute([$status, $id]);
        flash_set('success', 'Lead updated.');
    }
    header('Location: leads.php');
    exit;
}

$viewId = isset($_GET['id']) ? (int) $_GET['id'] : 0;
if ($viewId) {
    $stmt = db()->prepare('SELECT * FROM leads WHERE id = ?');
    $stmt->execute([$viewId]);
    $lead = $stmt->fetch();
    if ($lead && $lead['status'] === 'new') {
        db()->prepare("UPDATE leads SET status = 'read' WHERE id = ?")->execute([$viewId]);
        $lead['status'] = 'read';
    }
    admin_header('Lead detail', 'leads.php');
    if (!$lead) {
        echo '<div class="alert alert-error">Lead not found.</div>';
        admin_footer();
        exit;
    }
    ?>
    <div class="panel form-grid">
      <p><strong>Name:</strong> <?= e($lead['name']) ?></p>
      <p><strong>Email:</strong> <?= e($lead['email']) ?></p>
      <p><strong>Phone:</strong> <?= e($lead['phone']) ?></p>
      <p><strong>Service:</strong> <?= e($lead['service']) ?></p>
      <p><strong>Budget:</strong> <?= e($lead['budget']) ?></p>
      <p><strong>Status:</strong> <span class="badge badge-<?= e($lead['status']) ?>"><?= e($lead['status']) ?></span></p>
      <p><strong>Date:</strong> <?= e($lead['created_at']) ?></p>
      <p><strong>Message:</strong><br><?= nl2br(e($lead['message'])) ?></p>
      <div class="actions">
        <form method="post"><?= csrf_field() ?><input type="hidden" name="action" value="status"><input type="hidden" name="id" value="<?= (int) $lead['id'] ?>"><input type="hidden" name="status" value="archived"><button class="btn btn-secondary" type="submit">Archive</button></form>
        <form method="post" onsubmit="return confirm('Delete this lead?')"><?= csrf_field() ?><input type="hidden" name="action" value="delete"><input type="hidden" name="id" value="<?= (int) $lead['id'] ?>"><button class="btn btn-danger" type="submit">Delete</button></form>
        <a class="btn btn-secondary" href="leads.php">Back</a>
      </div>
    </div>
    <?php
    admin_footer();
    exit;
}

$rows = db()->query('SELECT * FROM leads ORDER BY created_at DESC')->fetchAll();
admin_header('Leads', 'leads.php');
?>
<div class="panel">
  <table>
    <thead><tr><th>Date</th><th>Name</th><th>Email</th><th>Service</th><th>Status</th><th></th></tr></thead>
    <tbody>
    <?php if (!$rows): ?>
      <tr><td colspan="6" class="muted">No leads yet.</td></tr>
    <?php endif; ?>
    <?php foreach ($rows as $row): ?>
      <tr>
        <td><?= e($row['created_at']) ?></td>
        <td><strong><?= e($row['name']) ?></strong></td>
        <td><?= e($row['email']) ?></td>
        <td><?= e($row['service']) ?></td>
        <td><span class="badge badge-<?= e($row['status']) ?>"><?= e($row['status']) ?></span></td>
        <td class="actions">
          <a class="btn btn-sm btn-secondary" href="leads.php?id=<?= (int) $row['id'] ?>">View</a>
        </td>
      </tr>
    <?php endforeach; ?>
    </tbody>
  </table>
</div>
<?php admin_footer(); ?>
