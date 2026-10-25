<?php
declare(strict_types=1);

/**
 * Legacy WhatsApp CRM UI removed — use site admin Leads + external messaging platform.
 */
require_once dirname(__DIR__, 2) . '/includes/auth.php';
require_admin();

header('Location: ../leads.php', true, 302);
exit;
