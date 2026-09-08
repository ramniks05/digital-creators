<?php
require_once dirname(__DIR__) . '/includes/auth.php';

if (admin_logged_in()) {
    header('Location: index.php');
    exit;
}

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verify_csrf($_POST['csrf_token'] ?? null)) {
        $error = 'Invalid security token. Please try again.';
    } else {
        $username = trim($_POST['username'] ?? '');
        $password = $_POST['password'] ?? '';
        if ($username === '' || $password === '') {
            $error = 'Username and password are required.';
        } elseif (attempt_login($username, $password)) {
            header('Location: index.php');
            exit;
        } else {
            $error = 'Invalid username or password.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Admin Login | Digital Creatorss</title>
  <link rel="stylesheet" href="assets/admin.css">
</head>
<body>
  <div class="login-wrap">
    <form class="login-card" method="post" action="">
      <h1>Admin Login</h1>
      <p>Manage website content for Digital Creatorss.</p>
      <?php if ($error): ?>
        <div class="alert alert-error"><?= e($error) ?></div>
      <?php endif; ?>
      <?= csrf_field() ?>
      <div class="form-grid">
        <div>
          <label for="username">Username</label>
          <input type="text" id="username" name="username" required autofocus value="<?= e($_POST['username'] ?? '') ?>">
        </div>
        <div>
          <label for="password">Password</label>
          <input type="password" id="password" name="password" required>
        </div>
        <button class="btn" type="submit">Sign in</button>
      </div>
    </form>
  </div>
</body>
</html>
