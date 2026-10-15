<?php
declare(strict_types=1);

require_once dirname(__DIR__) . '/includes/db.php';
require_once dirname(__DIR__) . '/includes/auth.php';
require_once dirname(__DIR__) . '/includes/saas/RegistrationService.php';
require_once dirname(__DIR__) . '/includes/saas/UserAuth.php';

$error = '';
$success = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verify_csrf($_POST['csrf_token'] ?? null)) {
        $error = 'Invalid security token.';
    } else {
        $svc = new SaasRegistrationService(db());
        $result = $svc->register(
            (string) ($_POST['email'] ?? ''),
            (string) ($_POST['password'] ?? ''),
            (string) ($_POST['full_name'] ?? ''),
            (string) ($_POST['company_name'] ?? ''),
            (string) ($_POST['workspace_slug'] ?? '')
        );
        if (!$result['ok']) {
            $error = (string) ($result['error'] ?? 'Registration failed.');
        } else {
            $success = 'Account created. Check your email to verify (or use verify link if dev token enabled).';
            if (filter_var(getenv('SAAS_EXPOSE_VERIFY_TOKEN') ?: '0', FILTER_VALIDATE_BOOLEAN)) {
                $success .= ' Dev token: ' . ($result['verification_token'] ?? '');
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Register | WhatsApp CRM</title>
  <link rel="stylesheet" href="assets/saas.css">
</head>
<body>
<div class="saas-shell">
  <div class="saas-card">
    <h1>Create workspace</h1>
    <?php if ($error): ?><div class="saas-alert-error"><?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8') ?></div><?php endif; ?>
    <?php if ($success): ?><p class="saas-muted"><?= htmlspecialchars($success, ENT_QUOTES, 'UTF-8') ?></p><p><a href="login.php">Sign in</a></p><?php else: ?>
    <form method="post">
      <?= csrf_field() ?>
      <label>Full name</label>
      <input name="full_name" required>
      <label>Work email</label>
      <input type="email" name="email" required>
      <label>Password (min 10 chars)</label>
      <input type="password" name="password" required minlength="10">
      <label>Company name</label>
      <input name="company_name" required>
      <label>Workspace URL slug (optional)</label>
      <input name="workspace_slug" pattern="[a-z0-9-]+">
      <button class="saas-btn" type="submit">Create account</button>
    </form>
    <p class="saas-muted" style="margin-top:12px"><a href="login.php">Already have an account?</a></p>
    <?php endif; ?>
  </div>
</div>
</body>
</html>
