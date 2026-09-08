<?php
require_once dirname(__DIR__) . '/includes/auth.php';
require_admin();
require_once __DIR__ . '/layout.php';

$action = $_GET['action'] ?? 'list';
$id = isset($_GET['id']) ? (int) $_GET['id'] : 0;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verify_csrf($_POST['csrf_token'] ?? null)) {
        flash_set('error', 'Invalid CSRF token.');
        header('Location: blog.php');
        exit;
    }
    $postAction = $_POST['action'] ?? '';
    try {
        if ($postAction === 'delete') {
            db()->prepare('DELETE FROM blog_posts WHERE id = ?')->execute([(int) ($_POST['id'] ?? 0)]);
            flash_set('success', 'Post deleted.');
            header('Location: blog.php');
            exit;
        }
        $title = trim($_POST['title'] ?? '');
        $category = trim($_POST['category'] ?? 'engineering');
        $excerpt = trim($_POST['excerpt'] ?? '');
        $read_time = trim($_POST['read_time'] ?? '5 Min Read');
        $published_at = trim($_POST['published_at'] ?? '') ?: null;
        $sort = (int) ($_POST['sort_order'] ?? 0);
        $active = isset($_POST['is_active']) ? 1 : 0;
        if ($title === '' || $excerpt === '') {
            throw new RuntimeException('Title and excerpt are required.');
        }
        $image = handle_upload('image', trim($_POST['existing_image'] ?? '') ?: null);
        if ($postAction === 'create') {
            db()->prepare('INSERT INTO blog_posts (title, category, excerpt, image, read_time, published_at, sort_order, is_active) VALUES (?,?,?,?,?,?,?,?)')
                ->execute([$title, $category, $excerpt, $image, $read_time, $published_at, $sort, $active]);
            flash_set('success', 'Post created.');
        } else {
            db()->prepare('UPDATE blog_posts SET title=?, category=?, excerpt=?, image=?, read_time=?, published_at=?, sort_order=?, is_active=? WHERE id=?')
                ->execute([$title, $category, $excerpt, $image, $read_time, $published_at, $sort, $active, (int) ($_POST['id'] ?? 0)]);
            flash_set('success', 'Post updated.');
        }
        header('Location: blog.php');
        exit;
    } catch (Throwable $e) {
        flash_set('error', $e->getMessage());
        header('Location: blog.php');
        exit;
    }
}

if ($action === 'create' || $action === 'edit') {
    $item = [
        'id' => 0, 'title' => '', 'category' => 'engineering', 'excerpt' => '', 'image' => '',
        'read_time' => '5 Min Read', 'published_at' => date('Y-m-d'), 'sort_order' => 0, 'is_active' => 1,
    ];
    if ($action === 'edit' && $id) {
        $stmt = db()->prepare('SELECT * FROM blog_posts WHERE id = ?');
        $stmt->execute([$id]);
        $item = $stmt->fetch() ?: $item;
    }
    admin_header($action === 'create' ? 'Add blog post' : 'Edit blog post', 'blog.php');
    ?>
    <div class="panel">
      <form method="post" enctype="multipart/form-data" class="form-grid">
        <?= csrf_field() ?>
        <input type="hidden" name="action" value="<?= $action === 'create' ? 'create' : 'edit' ?>">
        <input type="hidden" name="id" value="<?= (int) $item['id'] ?>">
        <div><label>Title</label><input type="text" name="title" required value="<?= e($item['title']) ?>"></div>
        <div class="form-row">
          <div>
            <label>Category</label>
            <select name="category">
              <?php foreach (['engineering','hosting','uiux'] as $cat): ?>
                <option value="<?= $cat ?>" <?= $item['category'] === $cat ? 'selected' : '' ?>><?= $cat ?></option>
              <?php endforeach; ?>
            </select>
          </div>
          <div><label>Read time</label><input type="text" name="read_time" value="<?= e($item['read_time']) ?>"></div>
        </div>
        <div><label>Excerpt</label><textarea name="excerpt" required><?= e($item['excerpt']) ?></textarea></div>
        <div class="form-row">
          <div><label>Published date</label><input type="date" name="published_at" value="<?= e($item['published_at']) ?>"></div>
          <div><label>Sort order</label><input type="number" name="sort_order" value="<?= (int) $item['sort_order'] ?>"></div>
        </div>
        <div class="checkbox-row"><input type="checkbox" name="is_active" id="is_active" <?= $item['is_active'] ? 'checked' : '' ?>><label for="is_active">Active</label></div>
        <div>
          <label>Image path / upload</label>
          <?php if (!empty($item['image'])): ?><div style="margin-bottom:8px"><img class="thumb" src="../<?= e($item['image']) ?>" alt=""></div><?php endif; ?>
          <input type="text" name="existing_image" value="<?= e($item['image'] ?? '') ?>">
          <input type="file" name="image" accept=".jpg,.jpeg,.png,.webp,image/*" style="margin-top:8px">
        </div>
        <div class="actions"><button class="btn" type="submit">Save</button><a class="btn btn-secondary" href="blog.php">Cancel</a></div>
      </form>
    </div>
    <?php admin_footer(); exit;
}

$rows = db()->query('SELECT * FROM blog_posts ORDER BY published_at DESC, id DESC')->fetchAll();
admin_header('Blog', 'blog.php');
?>
<div class="topbar" style="margin-top:-8px"><div></div><a class="btn" href="blog.php?action=create">Add post</a></div>
<div class="panel">
  <table>
    <thead><tr><th></th><th>Title</th><th>Category</th><th>Date</th><th>Status</th><th></th></tr></thead>
    <tbody>
    <?php foreach ($rows as $row): ?>
      <tr>
        <td><?php if ($row['image']): ?><img class="thumb" src="../<?= e($row['image']) ?>" alt=""><?php endif; ?></td>
        <td><strong><?= e($row['title']) ?></strong></td>
        <td><?= e($row['category']) ?></td>
        <td><?= e($row['published_at']) ?></td>
        <td><?= $row['is_active'] ? 'Active' : 'Hidden' ?></td>
        <td class="actions">
          <a class="btn btn-sm btn-secondary" href="blog.php?action=edit&id=<?= (int) $row['id'] ?>">Edit</a>
          <form method="post" onsubmit="return confirm('Delete this post?')">
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
