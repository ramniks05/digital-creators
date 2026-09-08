<?php
require_once dirname(__DIR__) . '/includes/auth.php';
require_admin();
require_once __DIR__ . '/layout.php';

$action = $_GET['action'] ?? 'list';
$id = isset($_GET['id']) ? (int) $_GET['id'] : 0;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verify_csrf($_POST['csrf_token'] ?? null)) {
        flash_set('error', 'Invalid CSRF token.');
        header('Location: projects.php');
        exit;
    }
    $postAction = $_POST['action'] ?? '';
    try {
        if ($postAction === 'delete') {
            db()->prepare('DELETE FROM projects WHERE id = ?')->execute([(int) ($_POST['id'] ?? 0)]);
            flash_set('success', 'Project deleted.');
            header('Location: projects.php');
            exit;
        }
        $title = trim($_POST['title'] ?? '');
        $category = trim($_POST['category'] ?? 'webapp');
        $category_label = trim($_POST['category_label'] ?? '');
        $description = trim($_POST['description'] ?? '');
        $link = trim($_POST['link'] ?? '');
        $metric = trim($_POST['metric'] ?? '');
        $metric_desc = trim($_POST['metric_desc'] ?? '');
        $sort = (int) ($_POST['sort_order'] ?? 0);
        $active = isset($_POST['is_active']) ? 1 : 0;
        $stack = array_values(array_filter(array_map('trim', explode(',', trim($_POST['stack'] ?? '')))));
        if ($title === '' || $description === '') {
            throw new RuntimeException('Title and description are required.');
        }
        $image = handle_upload('image', trim($_POST['existing_image'] ?? '') ?: null);
        if ($postAction === 'create') {
            db()->prepare('INSERT INTO projects (title, category, category_label, description, link, image, metric, metric_desc, stack_json, sort_order, is_active) VALUES (?,?,?,?,?,?,?,?,?,?,?)')
                ->execute([$title, $category, $category_label, $description, $link, $image, $metric, $metric_desc, json_encode($stack), $sort, $active]);
            flash_set('success', 'Project created.');
        } else {
            db()->prepare('UPDATE projects SET title=?, category=?, category_label=?, description=?, link=?, image=?, metric=?, metric_desc=?, stack_json=?, sort_order=?, is_active=? WHERE id=?')
                ->execute([$title, $category, $category_label, $description, $link, $image, $metric, $metric_desc, json_encode($stack), $sort, $active, (int) ($_POST['id'] ?? 0)]);
            flash_set('success', 'Project updated.');
        }
        header('Location: projects.php');
        exit;
    } catch (Throwable $e) {
        flash_set('error', $e->getMessage());
        header('Location: projects.php');
        exit;
    }
}

if ($action === 'create' || $action === 'edit') {
    $item = [
        'id' => 0, 'title' => '', 'category' => 'webapp', 'category_label' => '', 'description' => '',
        'link' => '', 'image' => '', 'metric' => '', 'metric_desc' => '', 'stack_json' => '[]',
        'sort_order' => 0, 'is_active' => 1,
    ];
    if ($action === 'edit' && $id) {
        $stmt = db()->prepare('SELECT * FROM projects WHERE id = ?');
        $stmt->execute([$id]);
        $item = $stmt->fetch() ?: $item;
    }
    $stackStr = implode(', ', $item['stack_json'] ? (json_decode($item['stack_json'], true) ?: []) : []);
    admin_header($action === 'create' ? 'Add project' : 'Edit project', 'projects.php');
    ?>
    <div class="panel">
      <form method="post" enctype="multipart/form-data" class="form-grid">
        <?= csrf_field() ?>
        <input type="hidden" name="action" value="<?= $action === 'create' ? 'create' : 'edit' ?>">
        <input type="hidden" name="id" value="<?= (int) $item['id'] ?>">
        <div><label>Title</label><input type="text" name="title" required value="<?= e($item['title']) ?>"></div>
        <div class="form-row">
          <div>
            <label>Category slug</label>
            <select name="category">
              <?php foreach (['webapp','ecommerce','hosting','nonprofit'] as $cat): ?>
                <option value="<?= $cat ?>" <?= $item['category'] === $cat ? 'selected' : '' ?>><?= $cat ?></option>
              <?php endforeach; ?>
            </select>
          </div>
          <div><label>Category label</label><input type="text" name="category_label" value="<?= e($item['category_label']) ?>"></div>
        </div>
        <div><label>Description</label><textarea name="description" required><?= e($item['description']) ?></textarea></div>
        <div><label>Link</label><input type="url" name="link" value="<?= e($item['link']) ?>"></div>
        <div class="form-row">
          <div><label>Metric</label><input type="text" name="metric" value="<?= e($item['metric']) ?>"></div>
          <div><label>Metric description</label><input type="text" name="metric_desc" value="<?= e($item['metric_desc']) ?>"></div>
        </div>
        <div><label>Stack (comma-separated)</label><input type="text" name="stack" value="<?= e($stackStr) ?>"></div>
        <div class="form-row">
          <div><label>Sort order</label><input type="number" name="sort_order" value="<?= (int) $item['sort_order'] ?>"></div>
          <div class="checkbox-row" style="margin-top:28px"><input type="checkbox" name="is_active" id="is_active" <?= $item['is_active'] ? 'checked' : '' ?>><label for="is_active">Active</label></div>
        </div>
        <div>
          <label>Image path / upload</label>
          <?php if (!empty($item['image'])): ?><div style="margin-bottom:8px"><img class="thumb" src="../<?= e($item['image']) ?>" alt=""></div><?php endif; ?>
          <input type="text" name="existing_image" value="<?= e($item['image'] ?? '') ?>">
          <input type="file" name="image" accept=".jpg,.jpeg,.png,.webp,image/*" style="margin-top:8px">
        </div>
        <div class="actions"><button class="btn" type="submit">Save</button><a class="btn btn-secondary" href="projects.php">Cancel</a></div>
      </form>
    </div>
    <?php admin_footer(); exit;
}

$filter = $_GET['category'] ?? '';
if ($filter) {
    $stmt = db()->prepare('SELECT * FROM projects WHERE category = ? ORDER BY sort_order ASC, id ASC');
    $stmt->execute([$filter]);
    $rows = $stmt->fetchAll();
} else {
    $rows = db()->query('SELECT * FROM projects ORDER BY sort_order ASC, id ASC')->fetchAll();
}
admin_header('Projects', 'projects.php');
?>
<div class="topbar" style="margin-top:-8px">
  <div class="actions">
    <a class="btn btn-sm btn-secondary" href="projects.php">All</a>
    <?php foreach (['webapp','ecommerce','hosting','nonprofit'] as $cat): ?>
      <a class="btn btn-sm btn-secondary" href="projects.php?category=<?= e($cat) ?>"><?= e($cat) ?></a>
    <?php endforeach; ?>
  </div>
  <a class="btn" href="projects.php?action=create">Add project</a>
</div>
<div class="panel">
  <table>
    <thead><tr><th></th><th>Title</th><th>Category</th><th>Order</th><th>Status</th><th></th></tr></thead>
    <tbody>
    <?php foreach ($rows as $row): ?>
      <tr>
        <td><?php if ($row['image']): ?><img class="thumb" src="../<?= e($row['image']) ?>" alt=""><?php endif; ?></td>
        <td><strong><?= e($row['title']) ?></strong></td>
        <td><?= e($row['category']) ?></td>
        <td><?= (int) $row['sort_order'] ?></td>
        <td><?= $row['is_active'] ? 'Active' : 'Hidden' ?></td>
        <td class="actions">
          <a class="btn btn-sm btn-secondary" href="projects.php?action=edit&id=<?= (int) $row['id'] ?>">Edit</a>
          <form method="post" onsubmit="return confirm('Delete this project?')">
            <?= csrf_field() ?><input type="hidden" name="action" value="delete"><input type="hidden" name="id" value="<?= (int) $row['id'] ?>">
            <button class="btn btn-sm btn-danger" type="submit">Delete</button>
          </form>
        </td>
      </tr>
    <?php endforeach; ?>
    </tbody>
  </table>
</div>
<?php admin_footer(); ?>
