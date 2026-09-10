<?php
require_once dirname(__DIR__) . '/includes/auth.php';
require_admin();
require_once __DIR__ . '/layout.php';

$categories = ['gym', 'ecommerce', 'news', 'crm', 'school', 'tuition', 'other'];
$action = $_GET['action'] ?? 'list';
$id = isset($_GET['id']) ? (int) $_GET['id'] : 0;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verify_csrf($_POST['csrf_token'] ?? null)) {
        flash_set('error', 'Invalid CSRF token.');
        header('Location: demo-products.php');
        exit;
    }
    $postAction = $_POST['action'] ?? '';
    try {
        if ($postAction === 'delete') {
            db()->prepare('DELETE FROM demo_products WHERE id = ?')->execute([(int) ($_POST['id'] ?? 0)]);
            flash_set('success', 'Demo product deleted.');
            header('Location: demo-products.php');
            exit;
        }

        $title = trim($_POST['title'] ?? '');
        $category = trim($_POST['category'] ?? 'other');
        $category_label = trim($_POST['category_label'] ?? '');
        $summary = trim($_POST['summary'] ?? '');
        $demo_url = trim($_POST['demo_url'] ?? '');
        $guide_url = trim($_POST['guide_url'] ?? '');
        $status = ($_POST['status'] ?? '') === 'live' ? 'live' : 'coming_soon';
        $sort = (int) ($_POST['sort_order'] ?? 0);
        $active = isset($_POST['is_active']) ? 1 : 0;
        $featured = isset($_POST['is_featured']) ? 1 : 0;
        $features = array_values(array_filter(array_map('trim', preg_split('/\r\n|\r|\n/', (string) ($_POST['features'] ?? '')))));

        if ($title === '' || $summary === '') {
            throw new RuntimeException('Title and summary are required.');
        }
        if (!in_array($category, $categories, true)) {
            $category = 'other';
        }

        $image = handle_upload('image', trim($_POST['existing_image'] ?? '') ?: null);

        if ($featured) {
            db()->exec('UPDATE demo_products SET is_featured = 0');
        }

        if ($postAction === 'create') {
            db()->prepare(
                'INSERT INTO demo_products (title, category, category_label, summary, image, demo_url, guide_url, status, is_featured, features_json, sort_order, is_active) VALUES (?,?,?,?,?,?,?,?,?,?,?,?)'
            )->execute([
                $title, $category, $category_label, $summary, $image, $demo_url, $guide_url,
                $status, $featured, json_encode($features), $sort, $active,
            ]);
            flash_set('success', 'Demo product created.');
        } else {
            $editId = (int) ($_POST['id'] ?? 0);
            db()->prepare(
                'UPDATE demo_products SET title=?, category=?, category_label=?, summary=?, image=?, demo_url=?, guide_url=?, status=?, is_featured=?, features_json=?, sort_order=?, is_active=? WHERE id=?'
            )->execute([
                $title, $category, $category_label, $summary, $image, $demo_url, $guide_url,
                $status, $featured, json_encode($features), $sort, $active, $editId,
            ]);
            flash_set('success', 'Demo product updated.');
        }
        header('Location: demo-products.php');
        exit;
    } catch (Throwable $e) {
        flash_set('error', $e->getMessage());
        header('Location: demo-products.php');
        exit;
    }
}

if ($action === 'create' || $action === 'edit') {
    $item = [
        'id' => 0,
        'title' => '',
        'category' => 'gym',
        'category_label' => '',
        'summary' => '',
        'image' => '',
        'demo_url' => '',
        'guide_url' => '',
        'status' => 'coming_soon',
        'is_featured' => 0,
        'features_json' => '[]',
        'sort_order' => 0,
        'is_active' => 1,
    ];
    if ($action === 'edit' && $id) {
        $stmt = db()->prepare('SELECT * FROM demo_products WHERE id = ?');
        $stmt->execute([$id]);
        $item = $stmt->fetch() ?: $item;
    }
    $featuresText = implode("\n", $item['features_json'] ? (json_decode($item['features_json'], true) ?: []) : []);
    admin_header($action === 'create' ? 'Add demo product' : 'Edit demo product', 'demo-products.php');
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
              <?php foreach ($categories as $cat): ?>
                <option value="<?= e($cat) ?>" <?= $item['category'] === $cat ? 'selected' : '' ?>><?= e($cat) ?></option>
              <?php endforeach; ?>
            </select>
          </div>
          <div><label>Category label</label><input type="text" name="category_label" value="<?= e($item['category_label']) ?>" placeholder="e.g. Fitness"></div>
        </div>
        <div><label>Summary</label><textarea name="summary" required rows="4"><?= e($item['summary']) ?></textarea></div>
        <div class="form-row">
          <div><label>Demo URL</label><input type="url" name="demo_url" value="<?= e($item['demo_url']) ?>" placeholder="https://..."></div>
          <div><label>User guide URL</label><input type="url" name="guide_url" value="<?= e($item['guide_url']) ?>" placeholder="https://..."></div>
        </div>
        <div class="form-row">
          <div>
            <label>Status</label>
            <select name="status">
              <option value="coming_soon" <?= $item['status'] === 'coming_soon' ? 'selected' : '' ?>>Coming soon</option>
              <option value="live" <?= $item['status'] === 'live' ? 'selected' : '' ?>>Live demo</option>
            </select>
          </div>
          <div><label>Sort order</label><input type="number" name="sort_order" value="<?= (int) $item['sort_order'] ?>"></div>
        </div>
        <div><label>Features (one per line, for featured product)</label><textarea name="features" rows="6"><?= e($featuresText) ?></textarea></div>
        <div class="form-row">
          <div class="checkbox-row" style="margin-top:8px">
            <input type="checkbox" name="is_featured" id="is_featured" <?= !empty($item['is_featured']) ? 'checked' : '' ?>>
            <label for="is_featured">Featured on products page</label>
          </div>
          <div class="checkbox-row" style="margin-top:8px">
            <input type="checkbox" name="is_active" id="is_active" <?= !empty($item['is_active']) ? 'checked' : '' ?>>
            <label for="is_active">Active (visible on site)</label>
          </div>
        </div>
        <div>
          <label>Image path / upload</label>
          <?php if (!empty($item['image'])): ?>
            <div style="margin-bottom:8px"><img class="thumb" src="../<?= e($item['image']) ?>" alt=""></div>
          <?php endif; ?>
          <input type="text" name="existing_image" value="<?= e($item['image'] ?? '') ?>" placeholder="assets/images/products/...">
          <input type="file" name="image" accept=".jpg,.jpeg,.png,.webp,image/*" style="margin-top:8px">
        </div>
        <div class="actions">
          <button class="btn" type="submit">Save</button>
          <a class="btn btn-secondary" href="demo-products.php">Cancel</a>
        </div>
      </form>
    </div>
    <?php
    admin_footer();
    exit;
}

$filter = $_GET['category'] ?? '';
try {
    if ($filter) {
        $stmt = db()->prepare('SELECT * FROM demo_products WHERE category = ? ORDER BY sort_order ASC, id ASC');
        $stmt->execute([$filter]);
        $rows = $stmt->fetchAll();
    } else {
        $rows = db()->query('SELECT * FROM demo_products ORDER BY sort_order ASC, id ASC')->fetchAll();
    }
} catch (Throwable $e) {
    $rows = [];
    flash_set('error', 'demo_products table missing. Run: php sql/migrate_demo_products.php');
}

admin_header('Demo Products', 'demo-products.php');
?>
<div class="topbar" style="margin-top:-8px">
  <div class="actions">
    <a class="btn btn-sm btn-secondary" href="demo-products.php">All</a>
    <?php foreach ($categories as $cat): ?>
      <a class="btn btn-sm btn-secondary" href="demo-products.php?category=<?= e($cat) ?>"><?= e($cat) ?></a>
    <?php endforeach; ?>
  </div>
  <a class="btn" href="demo-products.php?action=create">Add product</a>
</div>
<p class="muted" style="margin:0 0 12px">Manage ready-made software demos shown on <a href="../products.php" target="_blank">/products</a>. Set Demo URL + User Guide URL when a temp domain goes live.</p>
<div class="panel">
  <table>
    <thead>
      <tr>
        <th></th>
        <th>Title</th>
        <th>Category</th>
        <th>Status</th>
        <th>Featured</th>
        <th>Order</th>
        <th></th>
      </tr>
    </thead>
    <tbody>
    <?php if (!$rows): ?>
      <tr><td colspan="7" class="muted">No products yet. Add one, or run the migrate script to seed defaults.</td></tr>
    <?php endif; ?>
    <?php foreach ($rows as $row): ?>
      <tr>
        <td><?php if ($row['image']): ?><img class="thumb" src="../<?= e($row['image']) ?>" alt=""><?php endif; ?></td>
        <td><strong><?= e($row['title']) ?></strong></td>
        <td><?= e($row['category_label'] ?: $row['category']) ?></td>
        <td><?= $row['status'] === 'live' ? 'Live' : 'Coming soon' ?><?= $row['is_active'] ? '' : ' (hidden)' ?></td>
        <td><?= !empty($row['is_featured']) ? 'Yes' : '—' ?></td>
        <td><?= (int) $row['sort_order'] ?></td>
        <td class="actions">
          <a class="btn btn-sm btn-secondary" href="demo-products.php?action=edit&id=<?= (int) $row['id'] ?>">Edit</a>
          <form method="post" onsubmit="return confirm('Delete this demo product?')">
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
