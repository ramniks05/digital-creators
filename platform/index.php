<?php
declare(strict_types=1);

/**
 * Super-admin panel scaffold — requires crm_platform_admins and separate auth (implement before production).
 */
http_response_code(503);
header('Content-Type: text/plain; charset=UTF-8');
echo "Platform admin UI not enabled. Create crm_platform_admins and implement platform/login.php before use.\n";
