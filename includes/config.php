<?php
/**
 * Database & app config.
 * Local XAMPP defaults can be overridden by env vars or a local config file.
 */
$config = [
    'db' => [
        'host' => getenv('DB_HOST') ?: '127.0.0.1',
        'port' => getenv('DB_PORT') ?: '3306',
        'name' => getenv('DB_NAME') ?: 'digital_creators',
        'user' => getenv('DB_USER') ?: 'root',
        'pass' => getenv('DB_PASS') ?: '',
        'charset' => 'utf8mb4',
    ],
    'app' => [
        'name' => 'Digital Creatorss',
        'upload_dir' => __DIR__ . '/../assets/uploads',
        'upload_url' => 'assets/uploads',
    ],
];

/**
 * Hostinger-safe locations (checked first to last):
 * 1) domains/.../private/digital_creators_config.php  <- survives Git deploy
 * 2) public_html/private/...                          <- if created in wrong place
 * 3) includes/config.local.php                        <- wiped by Git deploy
 */
$candidateConfigs = [
    dirname(__DIR__, 2) . '/private/digital_creators_config.php',
    dirname(__DIR__) . '/../private/digital_creators_config.php',
    dirname(__DIR__) . '/private/digital_creators_config.php',
    __DIR__ . '/config.local.php',
];

foreach ($candidateConfigs as $localConfig) {
    if (!is_file($localConfig) || !is_readable($localConfig)) {
        continue;
    }
    $overrides = require $localConfig;
    if (is_array($overrides)) {
        $config = array_replace_recursive($config, $overrides);
        break;
    }
}

return $config;
