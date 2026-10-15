<?php
declare(strict_types=1);

require_once dirname(__DIR__, 2) . '/includes/db.php';
require_once dirname(__DIR__, 2) . '/includes/auth.php';
require_once dirname(__DIR__, 2) . '/includes/crm/tenant.php';
require_once dirname(__DIR__, 2) . '/includes/saas/UserAuth.php';
require_once dirname(__DIR__, 2) . '/includes/saas/MetaEmbeddedSignupService.php';

if (!SaasUserAuth::loggedIn()) {
    header('Location: ../login.php');
    exit;
}

$pdo = db();
$tenantId = crm_current_tenant_id();
$userId = (int) $_SESSION['crm_user_id'];
$meta = new MetaEmbeddedSignupService($pdo, $tenantId, $userId);
$config = $meta->clientConfig();
$session = $meta->startSession();
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Connect WhatsApp | Onboarding</title>
  <link rel="stylesheet" href="../assets/saas.css">
</head>
<body>
<div class="saas-shell">
  <h1>Connect WhatsApp Business</h1>
  <p class="saas-muted">Uses Meta Embedded Signup when your Meta App is approved and configured.</p>
  <?php if (!$config['ok']): ?>
    <div class="saas-alert-error">
      <strong>Not ready:</strong>
      <ul><?php foreach ($config['blockers'] ?? [] as $b): ?><li><?= htmlspecialchars($b, ENT_QUOTES, 'UTF-8') ?></li><?php endforeach; ?></ul>
    </div>
    <p>See <code>docs/META_EMBEDDED_SIGNUP.md</code> for prerequisites.</p>
  <?php else: ?>
    <p>App ID is configured server-side. Load Meta SDK on this page only after HTTPS domain authorization in Meta Developer Console.</p>
    <p class="saas-muted">Onboarding session state (server): started — do not store access tokens in the browser.</p>
  <?php endif; ?>
  <p><a href="../dashboard.php">Back to dashboard</a></p>
</div>
</body>
</html>
