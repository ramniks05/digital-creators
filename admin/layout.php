<?php
require_once dirname(__DIR__) . '/includes/auth.php';

function admin_header(string $title, string $active = ''): void
{
    $user = $_SESSION['admin_username'] ?? 'admin';
    $flash = flash_get();
    $nav = [
        'index.php' => 'Dashboard',
        'services.php' => 'Services',
        'team.php' => 'Team',
        'projects.php' => 'Projects',
        'blog.php' => 'Blog',
        'testimonials.php' => 'Testimonials',
        'leads.php' => 'Leads',
        'settings.php' => 'Settings',
    ];
    ?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title><?= e($title) ?> | Admin</title>
  <link rel="stylesheet" href="assets/admin.css">
</head>
<body>
<div class="admin-shell">
  <aside class="sidebar">
    <div class="brand">DC ADMIN</div>
    <?php foreach ($nav as $href => $label): ?>
      <a href="<?= e($href) ?>" class="<?= $active === $href ? 'active' : '' ?>"><?= e($label) ?></a>
    <?php endforeach; ?>
    <a href="logout.php">Logout</a>
  </aside>
  <main class="main">
    <div class="topbar">
      <h1><?= e($title) ?></h1>
      <div class="muted">Signed in as <?= e($user) ?></div>
    </div>
    <?php if ($flash): ?>
      <div class="alert alert-<?= e($flash['type'] === 'error' ? 'error' : 'success') ?>">
        <?= e($flash['message']) ?>
      </div>
    <?php endif; ?>
<?php
}

function admin_footer(): void
{
    ?>
  </main>
</div>
</body>
</html>
<?php
}
