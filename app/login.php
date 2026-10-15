<?php
declare(strict_types=1);

require_once dirname(__DIR__) . '/includes/db.php';
require_once dirname(__DIR__) . '/includes/auth.php';
require_once dirname(__DIR__) . '/includes/saas/UserAuth.php';

if (SaasUserAuth::loggedIn()) {
    header('Location: dashboard.php');
    exit;
}

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verify_csrf($_POST['csrf_token'] ?? null)) {
        $error = 'Invalid security token.';
    } elseif (!SaasUserAuth::login(db(), (string) ($_POST['email'] ?? ''), (string) ($_POST['password'] ?? ''))) {
        $error = 'Invalid email or password.';
    } else {
        header('Location: dashboard.php');
        exit;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Log in | WhatsApp CRM</title>
  <link rel="stylesheet" href="assets/saas.css">
</head>
<body>
<div class="saas-shell">
  <div class="saas-card">
    <h1>Log in</h1>
    <?php if ($error): ?><div class="saas-alert-error"><?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8') ?></div><?php endif; ?>
    <form method="post">
      <?= csrf_field() ?>
      <label>Email</label>
      <input type="email" name="email" required>
      <label>Password</label>
      <input type="password" name="password" required>
      <button class="saas-btn" type="submit">Sign in</button>
    </form>
    <p class="saas-muted" style="margin-top:12px"><a href="register.php">Create account</a></p>
  </div>
</div>
</body>
</html>
