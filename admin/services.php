<?php
require_once dirname(__DIR__) . '/includes/auth.php';
require_admin();
require_once __DIR__ . '/layout.php';

$action = $_GET['action'] ?? 'list';
$id = isset($_GET['id']) ? (int) $_GET['id'] : 0;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verify_csrf($_POST['csrf_token'] ?? null)) {
        flash_set('error', 'Invalid CSRF token.');
        header('Location: services.php');
        exit;
    }
    $postAction = $_POST['action'] ?? '';
    try {
        if ($postAction === 'delete') {
            $delId = (int) ($_POST['id'] ?? 0);
            db()->prepare('DELETE FROM services WHERE id = ?')->execute([$delId]);
            flash_set('success', 'Service deleted.');
            header('Location: services.php');
            exit;
        }

        $icon = trim($_POST['icon'] ?? 'code-2');
        $title = trim($_POST['title'] ?? '');
        $description = trim($_POST['description'] ?? '');
        $metric = trim($_POST['metric'] ?? '');
        $tag = trim($_POST['tag'] ?? '');
        $stackRaw = trim($_POST['stack'] ?? '');
        $sort = (int) ($_POST['sort_order'] ?? 0);
        $active = isset($_POST['is_active']) ? 1 : 0;
        $existingImage = trim($_POST['existing_image'] ?? '');
        $stack = array_values(array_filter(array_map('trim', explode(',', $stackRaw))));

        if ($title === '' || $description === '') {
            throw new RuntimeException('Title and description are required.');
        }
        $image = handle_upload('image', $existingImage ?: null);

        if ($postAction === 'create') {
            db()->prepare('INSERT INTO services (icon, title, description, metric, image, tag, stack_json, sort_order, is_active) VALUES (?,?,?,?,?,?,?,?,?)')
                ->execute([$icon, $title, $description, $metric, $image, $tag, json_encode($stack), $sort, $active]);
            flash_set('success', 'Service created.');
        } else {
            $editId = (int) ($_POST['id'] ?? 0);
            db()->prepare('UPDATE services SET icon=?, title=?, description=?, metric=?, image=?, tag=?, stack_json=?, sort_order=?, is_active=? WHERE id=?')
                ->execute([$icon, $title, $description, $metric, $image, $tag, json_encode($stack), $sort, $active, $editId]);
            flash_set('success', 'Service updated.');
        }
        header('Location: services.php');
        exit;
    } catch (Throwable $e) {
        flash_set('error', $e->getMessage());
        header('Location: services.php' . ($postAction === 'edit' ? '?action=edit&id=' . (int) ($_POST['id'] ?? 0) : '?action=create'));
        exit;
    }
}

if ($action === 'create' || $action === 'edit') {
    $item = [
        'id' => 0, 'icon' => 'code-2', 'title' => '', 'description' => '', 'metric' => '',
        'image' => '', 'tag' => '', 'stack_json' => '[]', 'sort_order' => 0, 'is_active' => 1,
    ];
    if ($action === 'edit' && $id) {
        $stmt = db()->prepare('SELECT * FROM services WHERE id = ?');
        $stmt->execute([$id]);
        $item = $stmt->fetch() ?: $item;
    }
    $stackStr = implode(', ', $item['stack_json'] ? (json_decode($item['stack_json'], true) ?: []) : []);
    admin_header($action === 'create' ? 'Add service' : 'Edit service', 'services.php');
    ?>
    <div class="panel">
      <form method="post" enctype="multipart/form-data" class="form-grid">
        <?= csrf_field() ?>
        <input type="hidden" name="action" value="<?= $action === 'create' ? 'create' : 'edit' ?>">
        <input type="hidden" name="id" value="<?= (int) $item['id'] ?>">
        <div class="form-row">
          <div><label>Title</label><input type="text" name="title" required value="<?= e($item['title']) ?>"></div>
          <div><label>Icon (lucide name)</label><input type="text" name="icon" value="<?= e($item['icon']) ?>"></div>
        </div>
        <div><label>Description</label><textarea name="description" required><?= e($item['description']) ?></textarea></div>
        <div class="form-row">
          <div><label>Metric</label><input type="text" name="metric" value="<?= e($item['metric']) ?>"></div>
          <div><label>Tag</label><input type="text" name="tag" value="<?= e($item['tag']) ?>"></div>
        </div>
        <div><label>Stack (comma-separated)</label><input type="text" name="stack" value="<?= e($stackStr) ?>"></div>
        <div class="form-row">
          <div><label>Sort order</label><input type="number" name="sort_order" value="<?= (int) $item['sort_order'] ?>"></div>
          <div class="checkbox-row" style="margin-top:28px"><input type="checkbox" name="is_active" id="is_active" <?= $item['is_active'] ? 'checked' : '' ?>><label for="is_active">Active</label></div>
        </div>
        <div>
          <label>Image path / upload</label>
          <?php if (!empty($item['image'])): ?><div style="margin-bottom:8px"><img class="thumb" src="../<?= e($item['image']) ?>" alt=""></div><?php endif; ?>
          <input type="text" name="existing_image" value="<?= e($item['image'] ?? '') ?>" placeholder="assets/images/...">
          <input type="file" name="image" accept=".jpg,.jpeg,.png,.webp,image/*" style="margin-top:8px">
        </div>
        <div class="actions">
          <button class="btn" type="submit">Save</button>
          <a class="btn btn-secondary" href="services.php">Cancel</a>
        </div>
      </form>
    </div>
    <?php
    admin_footer();
    exit;
}

$rows = db()->query('SELECT * FROM services ORDER BY sort_order ASC, id ASC')->fetchAll();
admin_header('Services', 'services.php');
?>
<div class="topbar" style="margin-top:-8px">
  <div></div>
  <a class="btn" href="services.php?action=create">Add service</a>
</div>
<div class="panel">
  <table>
    <thead><tr><th>Image</th><th>Title</th><th>Metric</th><th>Order</th><th>Status</th><th></th></tr></thead>
    <tbody>
    <?php foreach ($rows as $row): ?>
      <tr>
        <td><?php if ($row['image']): ?><img class="thumb" src="../<?= e($row['image']) ?>" alt=""><?php endif; ?></td>
        <td><strong><?= e($row['title']) ?></strong></td>
        <td><?= e($row['metric']) ?></td>
        <td><?= (int) $row['sort_order'] ?></td>
        <td><?= $row['is_active'] ? 'Active' : 'Hidden' ?></td>
        <td class="actions">
          <a class="btn btn-sm btn-secondary" href="services.php?action=edit&id=<?= (int) $row['id'] ?>">Edit</a>
          <form method="post" onsubmit="return confirm('Delete this service?')">
            <?= csrf_field() ?>
            <input type="hidden" name="action" value="delete">
            <input type="hidden" name="id" value="<?= (int) $row['id'] ?>">
            <button class="btn btn-sm btn-danger" type="submit">Delete</button>
          </form>
        </td>
      </tr>
    <?php endforeach; ?>
    </tbody>
  </table>
</div>
<?php admin_footer(); ?>
