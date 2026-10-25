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
  <title>Register | Digital Creatorss</title>
  <script>(function(){try{var t=localStorage.getItem('crm-theme');if(t==='dark'||t==='light')document.documentElement.setAttribute('data-theme',t);}catch(e){}})();</script>
  <link rel="stylesheet" href="assets/saas.css">
</head>
<body class="crm-auth-page">
<button type="button" class="crm-icon-btn crm-auth-theme" id="crm-theme-toggle" aria-label="Toggle theme">◐</button>
<div class="crm-auth-card" style="max-width:480px">
  <h1>Create workspace</h1>
  <p class="crm-auth-sub">Create a workspace account</p>
  <?php if ($error): ?><div class="saas-alert-error" role="alert"><?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8') ?></div><?php endif; ?>
  <?php if ($success): ?>
    <p class="saas-muted"><?= htmlspecialchars($success, ENT_QUOTES, 'UTF-8') ?></p>
    <p class="crm-auth-footer"><a href="login.php">Sign in</a></p>
  <?php else: ?>
  <form method="post">
    <?= csrf_field() ?>
    <div class="crm-field"><label for="full_name">Full name</label><input id="full_name" name="full_name" required></div>
    <div class="crm-field"><label for="email">Work email</label><input id="email" type="email" name="email" required></div>
    <div class="crm-field"><label for="password">Password (min 10 chars)</label><input id="password" type="password" name="password" required minlength="10"></div>
    <div class="crm-field"><label for="company_name">Company name</label><input id="company_name" name="company_name" required></div>
    <div class="crm-field"><label for="workspace_slug">Workspace URL slug (optional)</label><input id="workspace_slug" name="workspace_slug" pattern="[a-z0-9-]+" placeholder="my-company"></div>
    <button class="saas-btn" type="submit" style="width:100%">Create account</button>
  </form>
  <p class="crm-auth-footer"><a href="login.php">Already have an account?</a></p>
  <?php endif; ?>
</div>
<script src="assets/saas-theme.js" defer></script>
</body>
</html>
