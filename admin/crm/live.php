<?php
declare(strict_types=1);

require_once dirname(__DIR__, 2) . '/includes/auth.php';
require_admin();

header('Location: ../leads.php', true, 302);
exit;
