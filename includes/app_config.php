<?php
/**
 * Load config once; sets PHP timezone (see config.php).
 *
 * @return array<string, mixed>
 */
function app_config(): array
{
    static $config = null;
    if ($config === null) {
        $config = require __DIR__ . '/config.php';
    }

    return $config;
}

app_config();
