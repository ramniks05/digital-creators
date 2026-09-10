<?php
require_once dirname(__DIR__) . '/includes/auth.php';
require_admin();
require_once __DIR__ . '/layout.php';

$counts = [
    'services' => (int) db()->query('SELECT COUNT(*) FROM services')->fetchColumn(),
    'projects' => (int) db()->query('SELECT COUNT(*) FROM projects')->fetchColumn(),
    'demo_products' => 0,
    'blog' => (int) db()->query('SELECT COUNT(*) FROM blog_posts')->fetchColumn(),
    'team' => (int) db()->query('SELECT COUNT(*) FROM team_members')->fetchColumn(),
    'testimonials' => (int) db()->query('SELECT COUNT(*) FROM testimonials')->fetchColumn(),
    'leads_new' => (int) db()->query("SELECT COUNT(*) FROM leads WHERE status = 'new'")->fetchColumn(),
];
try {
    $counts['demo_products'] = (int) db()->query('SELECT COUNT(*) FROM demo_products')->fetchColumn();
} catch (Throwable $e) {
    $counts['demo_products'] = 0;
}

admin_header('Dashboard', 'index.php');
?>
<div class="cards">
  <div class="card"><div class="label">Services</div><div class="value"><?= $counts['services'] ?></div></div>
  <div class="card"><div class="label">Projects</div><div class="value"><?= $counts['projects'] ?></div></div>
  <div class="card"><div class="label">Demo Products</div><div class="value"><?= $counts['demo_products'] ?></div></div>
  <div class="card"><div class="label">Blog posts</div><div class="value"><?= $counts['blog'] ?></div></div>
  <div class="card"><div class="label">Team</div><div class="value"><?= $counts['team'] ?></div></div>
  <div class="card"><div class="label">Testimonials</div><div class="value"><?= $counts['testimonials'] ?></div></div>
  <div class="card"><div class="label">New leads</div><div class="value"><?= $counts['leads_new'] ?></div></div>
</div>
<div class="panel">
  <p class="muted" style="margin:0">Use the sidebar to manage content. Public pages load from the database automatically.</p>
  <p style="margin:12px 0 0">
    <a class="btn btn-secondary" href="../index.php" target="_blank">View website</a>
    <a class="btn" href="demo-products.php">Manage demo products</a>
  </p>
</div>
<?php admin_footer(); ?>
