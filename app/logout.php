<?php
declare(strict_types=1);

require_once dirname(__DIR__) . '/includes/auth.php';
require_once dirname(__DIR__) . '/includes/saas/UserAuth.php';

SaasUserAuth::logout();
header('Location: login.php');
exit;
