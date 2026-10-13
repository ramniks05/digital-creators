<?php
/**
 * Verify app + DB timezone (run from public_html): php scripts/check_timezone.php
 */
declare(strict_types=1);

$root = dirname(__DIR__);
require_once $root . '/includes/db.php';

echo "PHP default timezone: " . date_default_timezone_get() . PHP_EOL;
echo "PHP now:              " . date('Y-m-d H:i:s T') . PHP_EOL;

try {
    $pdo = db();
    $row = $pdo->query("SELECT NOW() AS db_now, @@session.time_zone AS session_tz")->fetch(PDO::FETCH_ASSOC);
    if (is_array($row)) {
        echo "MySQL NOW():          " . ($row['db_now'] ?? '?') . PHP_EOL;
        echo "MySQL session TZ:     " . ($row['session_tz'] ?? '?') . PHP_EOL;
    }
} catch (Throwable $e) {
    echo "MySQL: ERROR " . $e->getMessage() . PHP_EOL;
}

echo PHP_EOL . "Note: plain php -r does not load includes/config.php; this script does." . PHP_EOL;
