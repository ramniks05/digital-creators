<?php
require_once dirname(__DIR__) . '/includes/auth.php';
require_admin();
require_once __DIR__ . '/layout.php';

$action = $_GET['action'] ?? 'list';
$id = isset($_GET['id']) ? (int) $_GET['id'] : 0;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verify_csrf($_POST['csrf_token'] ?? null)) {
        flash_set('error', 'Invalid CSRF token.');
        header('Location: testimonials.php');
        exit;
    }
    $postAction = $_POST['action'] ?? '';
    try {
        if ($postAction === 'delete') {
            db()->prepare('DELETE FROM testimonials WHERE id = ?')->execute([(int) ($_POST['id'] ?? 0)]);
            flash_set('success', 'Testimonial deleted.');
            header('Location: testimonials.php');
            exit;
        }
        $name = trim($_POST['name'] ?? '');
        $role = trim($_POST['role'] ?? '');
        $review = trim($_POST['review'] ?? '');
        $rating = max(1, min(5, (int) ($_POST['rating'] ?? 5)));
        $sort = (int) ($_POST['sort_order'] ?? 0);
        $active = isset($_POST['is_active']) ? 1 : 0;
        if ($name === '' || $review === '') {
            throw new RuntimeException('Name and review are required.');
        }
        if ($postAction === 'create') {
            db()->prepare('INSERT INTO testimonials (name, role, review, rating, sort_order, is_active) VALUES (?,?,?,?,?,?)')
                ->execute([$name, $role, $review, $rating, $sort, $active]);
            flash_set('success', 'Testimonial created.');
        } else {
            db()->prepare('UPDATE testimonials SET name=?, role=?, review=?, rating=?, sort_order=?, is_active=? WHERE id=?')
                ->execute([$name, $role, $review, $rating, $sort, $active, (int) ($_POST['id'] ?? 0)]);
            flash_set('success', 'Testimonial updated.');
        }
        header('Location: testimonials.php');
        exit;
    } catch (Throwable $e) {
        flash_set('error', $e->getMessage());
        header('Location: testimonials.php');
        exit;
    }
}

if ($action === 'create' || $action === 'edit') {
    $item = ['id' => 0, 'name' => '', 'role' => '', 'review' => '', 'rating' => 5, 'sort_order' => 0, 'is_active' => 1];
    if ($action === 'edit' && $id) {
        $stmt = db()->prepare('SELECT * FROM testimonials WHERE id = ?');
        $stmt->execute([$id]);
        $item = $stmt->fetch() ?: $item;
    }
    admin_header($action === 'create' ? 'Add testimonial' : 'Edit testimonial', 'testimonials.php');
    ?>
    <div class="panel">
      <form method="post" class="form-grid">
        <?= csrf_field() ?>
        <input type="hidden" name="action" value="<?= $action === 'create' ? 'create' : 'edit' ?>">
        <input type="hidden" name="id" value="<?= (int) $item['id'] ?>">
        <div class="form-row">
          <div><label>Name</label><input type="text" name="name" required value="<?= e($item['name']) ?>"></div>
          <div><label>Role</label><input type="text" name="role" value="<?= e($item['role']) ?>"></div>
        </div>
        <div><label>Review</label><textarea name="review" required><?= e($item['review']) ?></textarea></div>
        <div class="form-row">
          <div><label>Rating (1-5)</label><input type="number" name="rating" min="1" max="5" value="<?= (int) $item['rating'] ?>"></div>
          <div><label>Sort order</label><input type="number" name="sort_order" value="<?= (int) $item['sort_order'] ?>"></div>
        </div>
        <div class="checkbox-row"><input type="checkbox" name="is_active" id="is_active" <?= $item['is_active'] ? 'checked' : '' ?>><label for="is_active">Active</label></div>
        <div class="actions"><button class="btn" type="submit">Save</button><a class="btn btn-secondary" href="testimonials.php">Cancel</a></div>
      </form>
    </div>
    <?php admin_footer(); exit;
}

$rows = db()->query('SELECT * FROM testimonials ORDER BY sort_order ASC, id ASC')->fetchAll();
admin_header('Testimonials', 'testimonials.php');
?>
<div class="topbar" style="margin-top:-8px"><div></div><a class="btn" href="testimonials.php?action=create">Add testimonial</a></div>
<div class="panel">
  <table>
    <thead><tr><th>Name</th><th>Role</th><th>Rating</th><th>Status</th><th></th></tr></thead>
    <tbody>
    <?php foreach ($rows as $row): ?>
      <tr>
        <td><strong><?= e($row['name']) ?></strong></td>
        <td><?= e($row['role']) ?></td>
        <td><?= (int) $row['rating'] ?>/5</td>
        <td><?= $row['is_active'] ? 'Active' : 'Hidden' ?></td>
        <td class="actions">
          <a class="btn btn-sm btn-secondary" href="testimonials.php?action=edit&id=<?= (int) $row['id'] ?>">Edit</a>
          <form method="post" onsubmit="return confirm('Delete this testimonial?')">
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
