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
  <script>(function(){try{var t=localStorage.getItem('crm-theme');if(t==='dark'||t==='light')document.documentElement.setAttribute('data-theme',t);}catch(e){}})();</script>
  <link rel="stylesheet" href="assets/saas.css">
</head>
<body class="crm-auth-page" style="align-items:flex-start;padding-top:48px">
<button type="button" class="crm-icon-btn crm-auth-theme" id="crm-theme-toggle" aria-label="Toggle theme">◐</button>
<div class="saas-shell" style="max-width:720px">
  <h1 style="margin-top:0">Workspace</h1>
  <p class="saas-muted">Signed in as <?= htmlspecialchars((string) ($_SESSION['crm_user_email'] ?? ''), ENT_QUOTES, 'UTF-8') ?></p>
  <div class="saas-card" style="max-width:100%">
    <p style="margin:0 0 8px">Active tenant: <strong>#<?= (int) $tenantId ?></strong></p>
    <p class="saas-muted" style="margin:0">Role: <?= htmlspecialchars((string) ($_SESSION['crm_role_slug'] ?? ''), ENT_QUOTES, 'UTF-8') ?></p>
  </div>
  <nav class="saas-app-nav" aria-label="Workspace actions">
    <a class="saas-btn" href="../admin/crm/live.php">Open CRM inbox</a>
    <a class="saas-btn" href="../admin/crm/index.php" style="background:var(--crm-text-secondary)">CRM dashboard</a>
    <a class="saas-btn" href="onboarding/whatsapp.php" style="background:var(--crm-text-secondary)">Connect WhatsApp</a>
    <a class="saas-btn" href="billing.php" style="background:var(--crm-text-secondary)">Billing</a>
    <a class="saas-btn" href="logout.php" style="background:var(--crm-text-muted)">Log out</a>
  </nav>
  <div class="saas-card" style="max-width:100%">
    <h2 style="margin-top:0;font-size:var(--crm-text-lg)">Your workspaces</h2>
    <?php if ($workspaces === []): ?>
      <p class="saas-muted">No workspaces linked to this account.</p>
    <?php else: ?>
      <ul style="margin:0;padding-left:1.2rem">
        <?php foreach ($workspaces as $w): ?>
          <li><?= htmlspecialchars((string) $w['name'], ENT_QUOTES, 'UTF-8') ?> (<?= htmlspecialchars((string) $w['slug'], ENT_QUOTES, 'UTF-8') ?>)</li>
        <?php endforeach; ?>
      </ul>
    <?php endif; ?>
  </div>
</div>
<script src="../admin/assets/crm-shell.js" defer></script>
</body>
</html>
