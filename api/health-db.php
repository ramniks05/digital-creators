<?php
/**
 * Safe DB health check (no secrets). Remove later if desired.
 */
header('Content-Type: application/json');

$configPath = null;
$candidates = [
    dirname(__DIR__, 2) . '/private/digital_creators_config.php',
    dirname(__DIR__) . '/../private/digital_creators_config.php',
    dirname(__DIR__) . '/private/digital_creators_config.php',
    __DIR__ . '/../includes/config.local.php',
];

$found = [];
foreach ($candidates as $path) {
    $found[] = [
        'path' => $path,
        'exists' => is_file($path),
        'readable' => is_file($path) && is_readable($path),
    ];
    if ($configPath === null && is_file($path) && is_readable($path)) {
        $configPath = $path;
    }
}

$dbOk = false;
$dbError = null;
try {
    require_once dirname(__DIR__) . '/includes/db.php';
    db()->query('SELECT 1');
    $dbOk = true;
} catch (Throwable $e) {
    $dbError = $e->getMessage();
}

$tableOk = false;
if ($dbOk) {
    try {
        db()->query('SELECT 1 FROM demo_products LIMIT 1');
        $tableOk = true;
    } catch (Throwable $e) {
        $tableOk = false;
    }
}

echo json_encode([
    'ok' => $dbOk,
    'config_loaded' => $configPath !== null,
    'config_basename' => $configPath ? basename($configPath) : null,
    'config_dir' => $configPath ? basename(dirname($configPath)) : null,
    'demo_products_table' => $tableOk,
    'candidates' => array_map(static function ($row) {
        return [
            'exists' => $row['exists'],
            'readable' => $row['readable'],
            'hint' => basename(dirname($row['path'])) . '/' . basename($row['path']),
        ];
    }, $found),
    'db_error' => $dbOk ? null : $dbError,
], JSON_PRETTY_PRINT);
