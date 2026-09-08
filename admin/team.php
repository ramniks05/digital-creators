<?php
require_once dirname(__DIR__) . '/includes/auth.php';
require_admin();
require_once __DIR__ . '/layout.php';

$action = $_GET['action'] ?? 'list';
$id = isset($_GET['id']) ? (int) $_GET['id'] : 0;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verify_csrf($_POST['csrf_token'] ?? null)) {
        flash_set('error', 'Invalid CSRF token.');
        header('Location: team.php');
        exit;
    }
    $postAction = $_POST['action'] ?? '';
    try {
        if ($postAction === 'delete') {
            db()->prepare('DELETE FROM team_members WHERE id = ?')->execute([(int) ($_POST['id'] ?? 0)]);
            flash_set('success', 'Team member deleted.');
            header('Location: team.php');
            exit;
        }
        $data = [
            trim($_POST['name'] ?? ''),
            trim($_POST['role'] ?? ''),
            trim($_POST['bio'] ?? ''),
            trim($_POST['specialty'] ?? '') ?: null,
            $_POST['member_type'] === 'director' ? 'director' : 'core',
            trim($_POST['email'] ?? '') ?: null,
            trim($_POST['phone'] ?? '') ?: null,
            trim($_POST['education'] ?? '') ?: null,
            trim($_POST['icon'] ?? 'user'),
            (int) ($_POST['sort_order'] ?? 0),
            isset($_POST['is_active']) ? 1 : 0,
        ];
        if ($data[0] === '' || $data[1] === '') {
            throw new RuntimeException('Name and role are required.');
        }
        $image = handle_upload('image', trim($_POST['existing_image'] ?? '') ?: null);
        if ($postAction === 'create') {
            db()->prepare('INSERT INTO team_members (name, role, bio, specialty, member_type, email, phone, education, icon, sort_order, is_active, image) VALUES (?,?,?,?,?,?,?,?,?,?,?,?)')
                ->execute([...$data, $image]);
            flash_set('success', 'Team member created.');
        } else {
            db()->prepare('UPDATE team_members SET name=?, role=?, bio=?, specialty=?, member_type=?, email=?, phone=?, education=?, icon=?, sort_order=?, is_active=?, image=? WHERE id=?')
                ->execute([...$data, $image, (int) ($_POST['id'] ?? 0)]);
            flash_set('success', 'Team member updated.');
        }
        header('Location: team.php');
        exit;
    } catch (Throwable $e) {
        flash_set('error', $e->getMessage());
        header('Location: team.php');
        exit;
    }
}

if ($action === 'create' || $action === 'edit') {
    $item = [
        'id' => 0, 'name' => '', 'role' => '', 'bio' => '', 'image' => '', 'specialty' => '',
        'member_type' => 'core', 'email' => '', 'phone' => '', 'education' => '', 'icon' => 'user',
        'sort_order' => 0, 'is_active' => 1,
    ];
    if ($action === 'edit' && $id) {
        $stmt = db()->prepare('SELECT * FROM team_members WHERE id = ?');
        $stmt->execute([$id]);
        $item = $stmt->fetch() ?: $item;
    }
    admin_header($action === 'create' ? 'Add team member' : 'Edit team member', 'team.php');
    ?>
    <div class="panel">
      <form method="post" enctype="multipart/form-data" class="form-grid">
        <?= csrf_field() ?>
        <input type="hidden" name="action" value="<?= $action === 'create' ? 'create' : 'edit' ?>">
        <input type="hidden" name="id" value="<?= (int) $item['id'] ?>">
        <div class="form-row">
          <div><label>Name</label><input type="text" name="name" required value="<?= e($item['name']) ?>"></div>
          <div><label>Role</label><input type="text" name="role" required value="<?= e($item['role']) ?>"></div>
        </div>
        <div><label>Bio</label><textarea name="bio"><?= e($item['bio']) ?></textarea></div>
        <div class="form-row">
          <div>
            <label>Type</label>
            <select name="member_type">
              <option value="director" <?= $item['member_type'] === 'director' ? 'selected' : '' ?>>Director</option>
              <option value="core" <?= $item['member_type'] === 'core' ? 'selected' : '' ?>>Core team</option>
            </select>
          </div>
          <div><label>Specialty</label><input type="text" name="specialty" value="<?= e($item['specialty']) ?>"></div>
        </div>
        <div class="form-row">
          <div><label>Email</label><input type="email" name="email" value="<?= e($item['email']) ?>"></div>
          <div><label>Phone</label><input type="text" name="phone" value="<?= e($item['phone']) ?>"></div>
        </div>
        <div class="form-row">
          <div><label>Education</label><input type="text" name="education" value="<?= e($item['education']) ?>"></div>
          <div><label>Icon</label><input type="text" name="icon" value="<?= e($item['icon']) ?>"></div>
        </div>
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
          <a class="btn btn-secondary" href="team.php">Cancel</a>
        </div>
      </form>
    </div>
    <?php admin_footer(); exit;
}

$rows = db()->query('SELECT * FROM team_members ORDER BY member_type ASC, sort_order ASC, id ASC')->fetchAll();
admin_header('Team', 'team.php');
?>
<div class="topbar" style="margin-top:-8px"><div></div><a class="btn" href="team.php?action=create">Add member</a></div>
<div class="panel">
  <table>
    <thead><tr><th></th><th>Name</th><th>Role</th><th>Type</th><th>Status</th><th></th></tr></thead>
    <tbody>
    <?php foreach ($rows as $row): ?>
      <tr>
        <td><?php if ($row['image']): ?><img class="thumb" src="../<?= e($row['image']) ?>" alt=""><?php endif; ?></td>
        <td><strong><?= e($row['name']) ?></strong></td>
        <td><?= e($row['role']) ?></td>
        <td><?= e($row['member_type']) ?></td>
        <td><?= $row['is_active'] ? 'Active' : 'Hidden' ?></td>
        <td class="actions">
          <a class="btn btn-sm btn-secondary" href="team.php?action=edit&id=<?= (int) $row['id'] ?>">Edit</a>
          <form method="post" onsubmit="return confirm('Delete this member?')">
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
