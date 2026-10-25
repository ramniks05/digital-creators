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
        $next = (string) ($_GET['next'] ?? 'dashboard.php');
        if (!str_starts_with($next, '/') && !str_starts_with($next, 'http')) {
            header('Location: ' . $next);
        } else {
            header('Location: dashboard.php');
        }
        exit;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Log in | Digital Creatorss</title>
  <script>(function(){try{var t=localStorage.getItem('crm-theme');if(t==='dark'||t==='light')document.documentElement.setAttribute('data-theme',t);}catch(e){}})();</script>
  <link rel="stylesheet" href="assets/saas.css">
</head>
<body class="crm-auth-page">
<button type="button" class="crm-icon-btn crm-auth-theme" id="crm-theme-toggle" aria-label="Toggle theme">◐</button>
<div class="crm-auth-card">
  <h1>Sign in</h1>
  <p class="crm-auth-sub">Digital Creatorss account</p>
  <?php if ($error): ?><div class="saas-alert-error" role="alert"><?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8') ?></div><?php endif; ?>
  <form method="post">
    <?= csrf_field() ?>
    <div class="crm-field">
      <label for="email">Email</label>
      <input type="email" id="email" name="email" required autocomplete="email">
    </div>
    <div class="crm-field">
      <label for="password">Password</label>
      <input type="password" id="password" name="password" required autocomplete="current-password">
    </div>
    <button class="saas-btn" type="submit" style="width:100%">Sign in</button>
  </form>
  <p class="crm-auth-footer"><a href="register.php">Create an account</a></p>
</div>
<script src="assets/saas-theme.js" defer></script>
</body>
</html>
