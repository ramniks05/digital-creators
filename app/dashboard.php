<?php
declare(strict_types=1);

require_once dirname(__DIR__) . '/includes/db.php';
require_once dirname(__DIR__) . '/includes/auth.php';
require_once dirname(__DIR__) . '/includes/crm/tenant.php';
require_once dirname(__DIR__) . '/includes/saas/UserAuth.php';
require_once dirname(__DIR__) . '/includes/saas/TenantGuard.php';

if (!SaasUserAuth::loggedIn()) {
    header('Location: login.php');
    exit;
}

$pdo = db();
$userId = (int) $_SESSION['crm_user_id'];
$workspaces = CrmTenantGuard::listUserTenants($pdo, $userId);
$tenantId = crm_current_tenant_id();
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Dashboard | WhatsApp CRM</title>
  <link rel="stylesheet" href="assets/saas.css">
</head>
<body>
<div class="saas-shell">
  <h1>Workspace dashboard</h1>
  <p class="saas-muted">Signed in as <?= htmlspecialchars((string) ($_SESSION['crm_user_email'] ?? ''), ENT_QUOTES, 'UTF-8') ?></p>
  <p>Active tenant ID: <strong><?= (int) $tenantId ?></strong> · Role: <?= htmlspecialchars((string) ($_SESSION['crm_role_slug'] ?? ''), ENT_QUOTES, 'UTF-8') ?></p>
  <p>
    <a class="saas-btn" href="../admin/crm/live.php">Open CRM inbox</a>
    <a class="saas-btn" href="onboarding/whatsapp.php" style="background:#475569;margin-left:8px">Connect WhatsApp</a>
    <a class="saas-btn" href="billing.php" style="background:#475569;margin-left:8px">Billing</a>
    <a class="saas-btn" href="logout.php" style="background:#64748b;margin-left:8px">Log out</a>
  </p>
  <h2>Your workspaces</h2>
  <ul>
    <?php foreach ($workspaces as $w): ?>
      <li><?= htmlspecialchars((string) $w['name'], ENT_QUOTES, 'UTF-8') ?> (<?= htmlspecialchars((string) $w['slug'], ENT_QUOTES, 'UTF-8') ?>)</li>
    <?php endforeach; ?>
  </ul>
</div>
</body>
</html>
